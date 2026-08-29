<?php
header('Content-Type: application/json');
require_once __DIR__ . '/auth-helper.php';
$auth = verifyAuthToken();
if (!$auth['success'] || $auth['user']['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$logFile = __DIR__ . '/../../data/auth_logs.json';
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    file_put_contents($logFile, json_encode([]));
    echo json_encode(['success' => true]);
    exit;
}
$logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
echo json_encode(['success' => true, 'data' => $logs]);
?>