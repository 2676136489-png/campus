<?php
/**
 * Image upload, validation and storage helpers.
 */




/**
 * 获取上传错误信息
 * @param int $code 错误码
 * @return string 错误信息
 */
function uploadErrorMsg($code)
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return '图片大小超过限制。';
        case UPLOAD_ERR_PARTIAL:
            return '图片只上传了一部分。';
        case UPLOAD_ERR_NO_FILE:
            return '请选择图片。';
        default:
            return '图片上传失败。';
    }
}


/**
 * 上传图片文件
 * @param string $fieldName 表单字段名
 * @param string $subDir 子目录
 * @param int $maxSize 最大大小（字节）
 * @param bool $required 是否必填
 * @return string 文件路径或错误信息
 */
function uploadImageFile($fieldName, $subDir = '', $maxSize = 5242880, $required = false, $fileOverride = null)
{
    $file = $fileOverride !== null ? $fileOverride : ($_FILES[$fieldName] ?? null);
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $required ? '请上传图片。' : '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return uploadErrorMsg($file['error']);
    }
    if ($file['size'] > $maxSize) {
        return '图片大小超过限制。';
    }

    $tmpName = $file['tmp_name'];
    $info = @getimagesize($tmpName);
    if ($info === false) {
        return '文件不是有效的图片。';
    }
    $allowMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];
    $mime = isset($info['mime']) ? $info['mime'] : '';
    if (!isset($allowMimes[$mime])) {
        return '仅支持 JPG、PNG、GIF 图片。';
    }
    $width = (int)$info[0];
    $height = (int)$info[1];
    if ($width <= 0 || $height <= 0 || $width > 12000 || $height > 12000 || ($width * $height) > 80000000) {
        return '图片尺寸过大，请压缩后再上传。';
    }

    $subDir = trim($subDir, '/\\');
    if ($subDir !== '' && !preg_match('/^[A-Za-z0-9_-]+$/', $subDir)) {
        return '非法的上传目录。';
    }
    $relativeDir = 'uploads' . ($subDir === '' ? '' : '/' . $subDir);
    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid($fieldName . '_', true) . '.' . $allowMimes[$mime];
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        return '图片保存失败。';
    }

    // JPEG/PNG 重新编码：去掉 EXIF 等元数据，并把超长边压缩到 2000px
    if ($mime === 'image/jpeg' || $mime === 'image/png') {
        $image = $mime === 'image/jpeg' ? @imagecreatefromjpeg($targetPath) : @imagecreatefrompng($targetPath);
        if ($image !== false) {
            $maxDim = 2000;
            $srcW = imagesx($image);
            $srcH = imagesy($image);
            if ($srcW > $maxDim || $srcH > $maxDim) {
                $ratio = min($maxDim / $srcW, $maxDim / $srcH);
                $newW = max(1, (int)round($srcW * $ratio));
                $newH = max(1, (int)round($srcH * $ratio));
                $scaled = imagescale($image, $newW, $newH);
                if ($scaled !== false) {
                    imagedestroy($image);
                    $image = $scaled;
                }
            }
            $tempTarget = $targetPath . '.tmp';
            if ($mime === 'image/jpeg') {
                imagejpeg($image, $tempTarget, 88);
            } else {
                imagepng($image, $tempTarget, 8);
            }
            imagedestroy($image);
            if (is_file($tempTarget)) {
                rename($tempTarget, $targetPath);
            }
        }
    }

    $relativePath = $relativeDir . '/' . $fileName;
    createImageThumbnail($relativePath, 320, 320, 'sm');
    if (strpos($fieldName, 'avatar') !== false) {
        createImageThumbnail($relativePath, 128, 128, 'avatar');
    }
    if (strpos($subDir, 'dynamic') !== false || strpos($subDir, 'feedback') !== false) {
        createImageThumbnail($relativePath, 640, 640, 'md');
    }

    return $relativePath;
}



/**
 * 获取本地文件路径（安全验证，防止路径穿越）
 * @param string $path 文件路径
 * @return string 安全的本地路径
 */
function localUploadPath($path)
{
    $path = assetUrl($path);
    if ($path === '') {
        return '';
    }
    $base = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads');
    $local = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $dir = realpath(dirname($local));
    if ($base === false || $dir === false || ($dir !== $base && strpos($dir, $base . DIRECTORY_SEPARATOR) !== 0)) {
        return '';
    }
    return $local;
}


/**
 * 检查上传文件是否真实存在
 * @param string $path 文件路径
 * @return bool
 */
function uploadFileExists($path)
{
    $local = localUploadPath($path);
    return $local !== '' && is_file($local);
}


/**
 * 删除已上传的文件
 * @param string $path 文件路径
 */
function deleteUploadedFile($path)
{
    $local = localUploadPath($path);
    if ($local !== '' && is_file($local)) {
        unlink($local);
    }
}

/**
 * 生成图片缩略图。
 * @param string $relativePath 上传相对路径，如 uploads/avatar/x.jpg
 * @param int $maxWidth
 * @param int $maxHeight
 * @param string $suffix 缩略图后缀
 * @return string|false 缩略图相对路径，失败返回 false
 */
function createImageThumbnail($relativePath, $maxWidth, $maxHeight, $suffix)
{
    if (!function_exists('imagecreatefromjpeg')) {
        return false;
    }
    $local = localUploadPath($relativePath);
    if ($local === '' || !is_file($local)) {
        return false;
    }
    $info = @getimagesize($local);
    if ($info === false) {
        return false;
    }
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($local);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($local);
            break;
        case IMAGETYPE_GIF:
            $image = @imagecreatefromgif($local);
            break;
        default:
            return false;
    }
    if ($image === false) {
        return false;
    }
    $srcW = imagesx($image);
    $srcH = imagesy($image);
    if ($srcW <= 0 || $srcH <= 0) {
        imagedestroy($image);
        return false;
    }
    $ratio = min($maxWidth / $srcW, $maxHeight / $srcH, 1);
    $newW = max(1, (int)round($srcW * $ratio));
    $newH = max(1, (int)round($srcH * $ratio));
    $thumb = imagecreatetruecolor($newW, $newH);
    if ($thumb === false) {
        imagedestroy($image);
        return false;
    }
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    imagedestroy($image);

    $thumbDirLocal = dirname($local) . DIRECTORY_SEPARATOR . 'thumbs';
    if (!is_dir($thumbDirLocal)) {
        mkdir($thumbDirLocal, 0755, true);
    }
    $base = pathinfo($local, PATHINFO_FILENAME);
    $thumbLocal = $thumbDirLocal . DIRECTORY_SEPARATOR . $base . '_' . $suffix . '.jpg';
    $ok = imagejpeg($thumb, $thumbLocal, 82);
    imagedestroy($thumb);
    if (!$ok) {
        return false;
    }
    return dirname($relativePath) . '/thumbs/' . $base . '_' . $suffix . '.jpg';
}


/**
 * 获取缩略图 URL；不存在时回退到原图。
 * @param string $path 上传相对路径
 * @param string $suffix 缩略图后缀
 * @return string
 */
function thumbnailUrl($path, $suffix = 'sm')
{
    if ($path === '') {
        return '';
    }
    $local = localUploadPath($path);
    if ($local !== '' && is_file($local)) {
        $base = pathinfo($local, PATHINFO_FILENAME);
        $thumbLocal = dirname($local) . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $base . '_' . $suffix . '.jpg';
        if (is_file($thumbLocal)) {
            return assetUrl(dirname($path) . '/thumbs/' . $base . '_' . $suffix . '.jpg');
        }
    }
    return assetUrl($path);
}


/**
 * 批量上传图片（最多 $maxCount 张）
 * @param string $fieldName
 * @param string $subDir
 * @param int $maxSize
 * @param int $maxCount
 * @return array|string 成功返回路径数组，失败返回错误信息
 */
function uploadMultipleImages($fieldName, $subDir = '', $maxSize = 5242880, $maxCount = 3)
{
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'] ?? null)) {
        return [];
    }
    $names = $_FILES[$fieldName]['name'];
    if (count($names) > $maxCount) {
        return '最多上传 ' . $maxCount . ' 张图片。';
    }
    $paths = [];
    for ($i = 0; $i < count($names); $i++) {
        if (($_FILES[$fieldName]['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $file = [
            'name' => $names[$i],
            'type' => $_FILES[$fieldName]['type'][$i] ?? '',
            'tmp_name' => $_FILES[$fieldName]['tmp_name'][$i] ?? '',
            'error' => $_FILES[$fieldName]['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $_FILES[$fieldName]['size'][$i] ?? 0
        ];
        $result = uploadImageFile($fieldName, $subDir, $maxSize, false, $file);
        if (strpos($result, 'uploads/') !== 0) {
            foreach ($paths as $path) {
                deleteUploadedFile($path);
            }
            return '第 ' . ($i + 1) . ' 张图片上传失败：' . $result;
        }
        $paths[] = $result;
    }
    return $paths;
}
