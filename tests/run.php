<?php
/**
 * Lightweight unit tests for pure helper functions.
 * Run: php tests/run.php
 */

require_once __DIR__ . '/../lib/helpers.php';

$tests = 0;
$failures = 0;

function check($name, $actual, $expected) {
    global $tests, $failures;
    $tests++;
    if ($actual === $expected) {
        echo "[PASS] " . $name . PHP_EOL;
        return;
    }
    $failures++;
    echo "[FAIL] " . $name . PHP_EOL;
    echo "  expected: " . var_export($expected, true) . PHP_EOL;
    echo "  actual:   " . var_export($actual, true) . PHP_EOL;
}

check('textLength counts Chinese chars', textLength('校园圈子abc'), 7);
check('normalizeTags replaces Chinese comma', normalizeTags('篮球，摄影、阅读 音乐'), '篮球,摄影,阅读,音乐');
check('normalizeTags deduplicates', normalizeTags('篮球,篮球,摄影'), '篮球,摄影');
check('normalizeTags limits to 10', count(explode(',', normalizeTags('1,2,3,4,5,6,7,8,9,10,11'))), 10);
check('normalizeTags trims long tag', normalizeTags(str_repeat('长', 30)), str_repeat('长', 20));

$valid = validateStudentFields([
    'userName' => 'student01',
    'pwd' => 'abc123',
    'stuNo' => '20240001',
    'name' => '张三',
    'gender' => '1',
    'birth_date' => '2004-01-01',
    'college' => '计算机学院',
    'grade' => '2023级',
    'major' => '软件工程',
    'phone' => '13800138000',
    'email' => 'stu@example.com',
    'QQ' => '123456789'
], true);
check('validateStudentFields accepts valid data', $valid, true);
check('validateStudentFields rejects bad phone', validateStudentFields([
    'userName' => 'student01',
    'pwd' => 'abc123',
    'stuNo' => '20240001',
    'name' => '张三',
    'gender' => '1',
    'birth_date' => '2004-01-01',
    'college' => '计算机学院',
    'grade' => '2023级',
    'major' => '软件工程',
    'phone' => '123',
    'email' => 'stu@example.com',
    'QQ' => '123456789'
], true), '手机号需为 11-13 位数字。');
check('validateStudentFields rejects bad email', validateStudentFields([
    'userName' => 'student01',
    'pwd' => 'abc123',
    'stuNo' => '20240001',
    'name' => '张三',
    'gender' => '1',
    'birth_date' => '2004-01-01',
    'college' => '计算机学院',
    'grade' => '2023级',
    'major' => '软件工程',
    'phone' => '13800138000',
    'email' => 'bad-email',
    'QQ' => '123456789'
], true), 'Email 格式不正确。');

check('assetUrl keeps safe upload path', assetUrl('uploads/avatar_1.png'), 'uploads/avatar_1.png');
check('assetUrl rejects traversal', assetUrl('../secret.php'), '');
check('assetUrl rejects non-upload path', assetUrl('config.php'), '');
check('genderText male', genderText(0), '男');
check('genderText female', genderText(1), '女');
check('statusText pending', statusText('N'), '待审核');
check('statusText verified', statusText('V'), '已通过');
check('maskContact phone', maskContact('13800138000'), '138****8000');
check('maskContact email', maskContact('zhangsan@example.com'), 'zh***@example.com');
check('maskContact short name email', maskContact('ab@example.com'), 'ab***@example.com');

echo PHP_EOL;
echo sprintf("%d tests, %d failures%s", $tests, $failures, PHP_EOL);
exit($failures > 0 ? 1 : 0);