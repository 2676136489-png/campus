<?php
/**
 * Content moderation helpers backed by the sensitive_word table.
 */

/**
 * @return string[]
 */
function sensitiveWords()
{
    $words = [];
    try {
        $conn = dbConnect();
        $result = $conn->query("SELECT `word` FROM `sensitive_word` ORDER BY `pk` ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $words[] = $row['word'];
            }
            $result->free();
        }
        $conn->close();
    } catch (Throwable $e) {
        $words = [];
    }
    if (empty($words)) {
        $file = __DIR__ . '/../config/sensitive_words.php';
        if (is_file($file)) {
            $configWords = require $file;
            if (is_array($configWords)) {
                $words = $configWords;
            }
        }
    }
    return $words;
}

/**
 * 检查文本是否命中敏感词
 * @param string $text
 * @return array{blocked: bool, word: string}
 */
function filterSensitiveText($text)
{
    $text = (string)$text;
    foreach (sensitiveWords() as $word) {
        if ($word === '') {
            continue;
        }
        $found = function_exists('mb_stripos') ? mb_stripos($text, $word, 0, 'UTF-8') : stripos($text, $word);
        if ($found !== false) {
            return ['blocked' => true, 'word' => $word];
        }
    }
    return ['blocked' => false, 'word' => ''];
}

/**
 * @param string $word
 * @return true|string
 */
function addSensitiveWord($word)
{
    $word = trim($word);
    if ($word === '' || textLength($word) > 100) {
        return '敏感词不能为空且不能超过 100 字。';
    }
    $conn = dbConnect();
    try {
        $stmt = $conn->prepare("INSERT INTO `sensitive_word` (`word`) VALUES (?)");
        $stmt->bind_param('s', $word);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return true;
    } catch (mysqli_sql_exception $e) {
        $conn->close();
        if ((int)$e->getCode() === 1062) {
            return '该敏感词已存在。';
        }
        return '敏感词添加失败。';
    }
}

/**
 * @param string $word
 * @return bool
 */
function deleteSensitiveWord($word)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM `sensitive_word` WHERE `word` = ?");
    $stmt->bind_param('s', $word);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}
