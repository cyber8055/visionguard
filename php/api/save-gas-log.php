<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/auth-helper.php';
$auth = verifyAuthToken();
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['permit_id']) || !isset($input['o2']) || !isset($input['lel']) || !isset($input['h2s'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$dataFile = __DIR__ . '/../../data/gas_logs.json';

// Ensure data directory exists
if (!is_dir(dirname($dataFile))) {
    mkdir(dirname($dataFile), 0755, true);
}

$logs = [];
if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    if (!empty($content)) {
        $logs = json_decode($content, true) ?: [];
    }
}

$newLog = [
    'id' => uniqid('gas_'),
    'permit_id' => $input['permit_id'],
    'o2' => $input['o2'],
    'lel' => $input['lel'],
    'h2s' => $input['h2s'],
    'timestamp' => date('Y-m-d\TH:i:sP'),
    'recorded_by' => $auth['user']['name'] ?? 'Supervisor'
];

array_unshift($logs, $newLog);

if (file_put_contents($dataFile, json_encode($logs, JSON_PRETTY_PRINT))) {
    echo json_encode(['success' => true, 'message' => 'Gas log saved securely', 'log' => $newLog]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save log to file']);
}
