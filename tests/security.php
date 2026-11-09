<?php
/**
 * Security regression tests that do not require a database.
 * Usage: php tests/security.php
 */

require_once __DIR__ . '/../p_dbInfo.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/autoload.php';

$tests = 0;
$failures = 0;

function secIt($name, $condition)
{
    global $tests, $failures;
    $tests++;
    if ($condition) {
        echo "[PASS] " . $name . PHP_EOL;
    } else {
        $failures++;
        echo "[FAIL] " . $name . PHP_EOL;
    }
}

$_POST['csrf_token'] = str_repeat('0', 64);
$csrfRejectsBad = verifyCsrf() === false;
$validToken = csrfToken();
$_POST['csrf_token'] = $validToken;
$csrfAcceptsValid = verifyCsrf() === true;
$csrfField = csrfField();
$csrfFieldOk = strpos($csrfField, 'name="csrf_token"') !== false && strpos($csrfField, $validToken) !== false;
unset($_POST['csrf_token']);

$nonImageTmp = tempnam(sys_get_temp_dir(), 'sec_');
$uploadRejectsNonImage = false;
if ($nonImageTmp !== false) {
    file_put_contents($nonImageTmp, '<?php echo "not an image";');
    $fake = array(
        'name' => 'evil.php',
        'type' => 'text/php',
        'tmp_name' => $nonImageTmp,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($nonImageTmp)
    );
    $result = uploadImageFile('security_test', 'security', 1048576, true, $fake);
    $uploadRejectsNonImage = is_string($result) && $result !== '' && strpos($result, '/') === false;
    @unlink($nonImageTmp);
}

$pngTmp = tempnam(sys_get_temp_dir(), 'sec_png_');
$uploadRejectsTraversal = false;
if ($pngTmp !== false) {
    $im = @imagecreatetruecolor(2, 2);
    if ($im !== false) {
        imagepng($im, $pngTmp);
        imagedestroy($im);
        $fake = array(
            'name' => 'ok.png',
            'type' => 'image/png',
            'tmp_name' => $pngTmp,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($pngTmp)
        );
        $result = uploadImageFile('security_test', '../evil', 1048576, true, $fake);
        $uploadRejectsTraversal = is_string($result) && $result !== '' && strpos($result, '/') === false;
    }
    @unlink($pngTmp);
}

secIt('html escaping blocks script injection', h('<script>alert(1)</script>') === '&lt;script&gt;alert(1)&lt;/script&gt;');
secIt('invalid CSRF token is rejected', $csrfRejectsBad);
secIt('valid CSRF token is accepted', $csrfAcceptsValid);
secIt('CSRF field contains hidden token', $csrfFieldOk);
secIt('upload rejects non-image payload', $uploadRejectsNonImage);
secIt('upload rejects path traversal directory', $uploadRejectsTraversal);
secIt('asset URL rejects traversal', assetUrl('../secret.php') === '');

echo PHP_EOL;
echo sprintf("%d security tests, %d failures%s", $tests, $failures, PHP_EOL);
exit($failures > 0 ? 1 : 0);