<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

$dataFile = __DIR__ . '/../../data/gas_logs.json';
$logs = [];

if (file_exists($dataFile)) {
    $content = file_get_contents($dataFile);
    if (!empty($content)) {
        $logs = json_decode($content, true) ?: [];
    }
}

// Filter by permit_id if provided
if (isset($_GET['permit_id']) && !empty($_GET['permit_id'])) {
    $permitId = $_GET['permit_id'];
    $logs = array_filter($logs, function($log) use ($permitId) {
        return isset($log['permit_id']) && $log['permit_id'] === $permitId;
    });
    // Re-index array after filtering
    $logs = array_values($logs);
}

echo json_encode(['success' => true, 'data' => $logs]);
