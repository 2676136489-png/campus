<?php
/**
 * CSV export helpers.
 */

/**
 * Send CSV response with UTF-8 BOM.
 * @param string $filename
 * @param array $headers
 * @param array $rows
 */
function csvResponse($filename, $headers, $rows)
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, array_values($row));
    }
    fclose($out);
    exit;
}
