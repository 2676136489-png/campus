<?php
/**
 * Image captcha generated with GD.
 */

const CAPTCHA_CHARS = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

/**
 * Generate a random captcha code.
 * @param int $length
 * @return string
 */
function captchaGenerate($length = 4)
{
    $length = max(4, min(6, (int)$length));
    $chars = CAPTCHA_CHARS;
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Render captcha PNG and exit.
 * @param string $code
 */
function captchaRender($code)
{
    $width = 128;
    $height = 46;
    $image = imagecreatetruecolor($width, $height);
    if ($image === false) {
        http_response_code(500);
        exit('Captcha unavailable');
    }

    $bg = imagecolorallocate($image, 243, 240, 232);
    imagefilledrectangle($image, 0, 0, $width, $height, $bg);

    $palette = [
        imagecolorallocate($image, 23, 107, 85),
        imagecolorallocate($image, 45, 82, 96),
        imagecolorallocate($image, 99, 76, 46),
        imagecolorallocate($image, 98, 84, 70)
    ];

    // Interference lines
    for ($i = 0; $i < 5; $i++) {
        $color = $palette[array_rand($palette)];
        imageline(
            $image,
            random_int(0, $width),
            random_int(0, $height),
            random_int(0, $width),
            random_int(0, $height),
            $color
        );
    }

    // Noise dots
    for ($i = 0; $i < 90; $i++) {
        $color = $palette[array_rand($palette)];
        imagesetpixel($image, random_int(0, $width), random_int(0, $height), $color);
    }

    // Characters using built-in GD font
    $x = 14;
    $length = strlen($code);
    for ($i = 0; $i < $length; $i++) {
        $char = $code[$i];
        $color = $palette[$i % count($palette)];
        $y = random_int(10, 18);
        imagechar($image, 5, $x, $y, $char, $color);
        $x += 24;
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    imagepng($image);
    imagedestroy($image);
    exit;
}

/**
 * Generate a captcha question and store the answer in session.
 * Kept for compatibility; the current UI uses image captcha.
 * @return string
 */
function captchaQuestion()
{
    $code = captchaGenerate(4);
    $_SESSION['captcha_answer'] = $code;
    return '验证码已生成，请输入图片中的内容';
}

/**
 * Verify the user's answer.
 * @param string $answer
 * @return bool
 */
function verifyCaptcha($answer)
{
    $expected = isset($_SESSION['captcha_answer']) ? strtoupper(trim((string)$_SESSION['captcha_answer'])) : '';
    unset($_SESSION['captcha_answer']);
    return $expected !== '' && strtoupper(trim((string)$answer)) === $expected;
}
