<?php
$csvFile = 'occurrence_data.csv';
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "No data received."]);
    exit;
}

$parameterValues = $data; // ["parameter_name" => value, ...]

// Load or initialize CSV
$fileExists = file_exists($csvFile);
$csv = [];

if ($fileExists) {
    $csv = array_map('str_getcsv', file($csvFile));
    $headers = $csv[0];
} else {
    $headers = array_keys($parameterValues);
    $csv[] = $headers;
}

// Ensure column order matches
$row = [];
foreach ($headers as $param) {
    $row[] = $parameterValues[$param] ?? '';
}
$csv[] = $row;

// Save updated CSV
$fp = fopen($csvFile, 'w');
foreach ($csv as $line) {
    fputcsv($fp, $line);
}
fclose($fp);

// Calculate averages
$totals = array_fill(0, count($headers), 0);
$count = count($csv) - 1;

for ($i = 1; $i < count($csv); $i++) {
    for ($j = 0; $j < count($csv[$i]); $j++) {
        $totals[$j] += is_numeric($csv[$i][$j]) ? floatval($csv[$i][$j]) : 0;
    }
}

$averages = [];
for ($i = 0; $i < count($headers); $i++) {
    $averages[$headers[$i]] = round($totals[$i] / $count, 2);
}

echo json_encode(["averages" => $averages]);
?>
