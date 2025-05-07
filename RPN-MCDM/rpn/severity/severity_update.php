<?php
// severity_update.php
header('Content-Type: application/json; charset=UTF-8');
$csvFile = __DIR__ . '/severity_data.csv';
$data    = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'No data received.']);
    exit;
}

// Load existing CSV (or initialize)
$fileExists = file_exists($csvFile);
$rows       = $fileExists
    ? array_map('str_getcsv', file($csvFile))
    : [];

// On first run, write header row if missing
if (!$fileExists) {
    $headers = array_keys($data);
    $rows[]  = $headers;
} else {
    $headers = $rows[0];
}

// Build new row in header order
$newRow = [];
foreach ($headers as $col) {
    $newRow[] = isset($data[$col]) ? floatval($data[$col]) : '';
}
$rows[] = $newRow;

// Save CSV
$fp = fopen($csvFile, 'w');
foreach ($rows as $r) {
    fputcsv($fp, $r);
}
fclose($fp);

// Compute averages column-wise
$count = count($rows) - 1;               // exclude header
$totals = array_fill(0, count($headers), 0);
for ($i = 1; $i <= $count; $i++) {
    foreach ($rows[$i] as $j => $val) {
        if (is_numeric($val)) $totals[$j] += $val;
    }
}
$averages = [];
foreach ($headers as $j => $col) {
    $averages[$col] = $count ? round($totals[$j] / $count, 2) : 0;
}

echo json_encode(['averages' => $averages]);
