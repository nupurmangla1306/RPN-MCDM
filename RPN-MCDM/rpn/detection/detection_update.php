<?php
// detection_update.php
$csvFile = 'detection_data.csv';
$input   = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "No data received"]);
    exit;
}

// Load existing CSV (or init)
$fileExists = file_exists($csvFile);
$rows       = $fileExists
    ? array_map('str_getcsv', file($csvFile))
    : [];

// If brand-new, write header row based on keys of incoming data
if (!$fileExists) {
    $headers = array_keys($input);
    $rows[]  = $headers;
} else {
    $headers = $rows[0];
}

// Build new row in header order
$newRow = [];
foreach ($headers as $param) {
    $newRow[] = isset($input[$param]) ? $input[$param] : '';
}
$rows[] = $newRow;

// Save CSV back out
$fp = fopen($csvFile, 'w');
foreach ($rows as $r) {
    fputcsv($fp, $r);
}
fclose($fp);

// Compute averages
$count  = count($rows) - 1; // exclude header
$totals = array_fill(0, count($headers), 0);
for ($i = 1; $i < count($rows); $i++) {
    for ($j = 0; $j < count($rows[$i]); $j++) {
        if (is_numeric($rows[$i][$j])) {
            $totals[$j] += (float) $rows[$i][$j];
        }
    }
}
$avgs = [];
for ($i = 0; $i < count($headers); $i++) {
    $avgs[$headers[$i]] = $count
        ? round($totals[$i] / $count, 2)
        : 0;
}

// Return JSON
header('Content-Type: application/json');
echo json_encode(['averages' => $avgs]);
