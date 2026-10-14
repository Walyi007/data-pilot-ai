<?php
require __DIR__ . '/vendor/autoload.php';
use League\Csv\Reader;
$file = 'C:/xampp/htdocs/data-pilot-ai/storage/app/private/datasets/2rSyjZQK4QNvWMlWDgwA3WEOLOi1vsFy97WDW4s7.csv';
try {
    $csv = Reader::createFromPath($file, 'r');
    // No header offset to avoid duplicate header error
    $records = $csv->getRecords();
    $rows = iterator_to_array($records);
    $rowCount = count($rows);
    if ($rowCount > 0) {
        $firstRow = $rows[0];
        $colCount = count($firstRow);
        echo 'Row count: ' . $rowCount . PHP_EOL;
        echo 'Column count (based on first row): ' . $colCount . PHP_EOL;
    } else {
        echo 'No rows found.' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
