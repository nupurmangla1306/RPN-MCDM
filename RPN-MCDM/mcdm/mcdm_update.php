<?php
header('Content-Type: application/json');

$csvFile = 'mcdm_results.csv';
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['rows']) || !is_array($input['rows'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// prepare header row:
// Activity, [Score & WeightedScore for each criterion], Total, Rank
// we'll extract header from sessionStorage in JS if you need; here we assume fixed criteria order:
$example = $input['rows'][0];
$numCols = count($example);
$header = [];

// First column
$header[] = 'Activity';

// For the rest, we don't know criterion names here. To keep it simple,
// assume you want columns like Score1, Weighted1, Score2, Weighted2...
for ($i = 1; $i < $numCols - 2; $i += 2) {
    $idx = ($i+1)/2;
    $header[] = "Score{$idx}";
    $header[] = "Weighted{$idx}";
}

// then Total, Rank
$header[] = 'Total';
$header[] = 'Rank';

// Aggregate existing CSV if any
$rows = [];
if (file_exists($csvFile)) {
    $rows = array_map('str_getcsv', file($csvFile));
}

// If no file, write header
if (empty($rows)) {
    $rows[] = $header;
}

// Append each new result row
foreach ($input['rows'] as $r) {
    $rows[] = $r;
}

// Write back to CSV
$fp = fopen($csvFile, 'w');
foreach ($rows as $r) {
    fputcsv($fp, $r);
}
fclose($fp);

// Respond back
echo json_encode(['status'=>'ok']);
