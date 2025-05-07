<?php
header('Content-Type: application/json');

function read_csv(string $path): array {
    if (!file_exists($path)) return [];
    $lines = array_filter(file($path, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES));
    return array_map('str_getcsv', $lines);
}

// paths to your data CSVs
$sevCsv = 'severity/severity_data.csv';
$occCsv = 'occurrence/occurrence_data.csv';
$detCsv = 'detection/detection_data.csv';

// load into arrays
$sev = read_csv($sevCsv);
$occ = read_csv($occCsv);
$det = read_csv($detCsv);

// validate
if (empty($sev) || empty($occ) || empty($det)
  || count($sev[0]) !== count($occ[0])
  || count($sev[0]) !== count($det[0])) {
    http_response_code(500);
    echo json_encode(['error'=>'CSV files missing or malformed']);
    exit;
}

$activities = $sev[0];
$userCount  = max(count($sev), count($occ), count($det)) - 1;

// initialize sums
$n = count($activities);
$sumSev = array_fill(0, $n, 0);
$sumOcc = array_fill(0, $n, 0);
$sumDet = array_fill(0, $n, 0);

// accumulate values
for ($r = 1; $r <= $userCount; $r++) {
    for ($c = 0; $c < $n; $c++) {
        $sumSev[$c] += isset($sev[$r][$c]) && is_numeric($sev[$r][$c]) ? $sev[$r][$c] : 0;
        $sumOcc[$c] += isset($occ[$r][$c]) && is_numeric($occ[$r][$c]) ? $occ[$r][$c] : 0;
        $sumDet[$c] += isset($det[$r][$c]) && is_numeric($det[$r][$c]) ? $det[$r][$c] : 0;
    }
}

// compute averages
$avgSev = $avgOcc = $avgDet = [];
for ($i = 0; $i < $n; $i++) {
    $avgSev[$i] = $userCount ? round($sumSev[$i] / $userCount, 2) : 0;
    $avgOcc[$i] = $userCount ? round($sumOcc[$i] / $userCount, 2) : 0;
    $avgDet[$i] = $userCount ? round($sumDet[$i] / $userCount, 2) : 0;
}

// build results
$results = [];
for ($i = 0; $i < $n; $i++) {
    $rpn = (int) round($avgSev[$i] * $avgOcc[$i] * $avgDet[$i]);
    $results[] = [
        'activity'   => $activities[$i],
        'severity'   => number_format($avgSev[$i], 2),
        'occurrence' => number_format($avgOcc[$i], 2),
        'detection'  => number_format($avgDet[$i], 2),
        'rpn'        => $rpn
    ];
}

// sort by RPN desc & assign rank
usort($results, fn($a,$b) => $b['rpn'] <=> $a['rpn']);
foreach ($results as $idx => &$row) {
    $row['rank'] = $idx + 1;
}
unset($row);

// --- WRITE final_outcome.csv ---
$outCsv = 'final_outcome.csv';
$fp = fopen($outCsv, 'w');
// header
fputcsv($fp, ['Activity','Severity','Occurrence','Detection','RPN','Rank']);
// rows
foreach ($results as $row) {
    fputcsv($fp, [
        $row['activity'],
        $row['severity'],
        $row['occurrence'],
        $row['detection'],
        $row['rpn'],
        $row['rank']
    ]);
}
fclose($fp);

// --- RETURN JSON for your HTML front-end ---
echo json_encode([
    'severity_data'   => array_combine($activities, $avgSev),
    'occurrence_data' => array_combine($activities, $avgOcc),
    'detection_data'  => array_combine($activities, $avgDet),
    'results'         => $results
]);
