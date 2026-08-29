<?php
header('Content-Type: application/json');
require_once __DIR__ . '/auth-helper.php';

// Verify authentication
$auth = verifyAuthToken();
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}

$jsaId = $_GET['id'] ?? '';
if (empty($jsaId)) {
    echo json_encode(['success' => false, 'message' => 'Missing JSA ID.']);
    exit;
}

// Security: Prevent directory traversal attacks
if (!preg_match('/^JSA-\d{8}-\d{4}$/', $jsaId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSA ID format.']);
    exit;
}

$recordsDir = __DIR__ . '/../jsa_records/';
$files = glob($recordsDir . '*/' . $jsaId . '.json');

if (empty($files) || !file_exists($files[0])) {
    echo json_encode(['success' => false, 'message' => 'JSA record not found.']);
    exit;
}

$filePath = $files[0];

$recordData = json_decode(file_get_contents($filePath), true);
if ($recordData) {
    echo json_encode([
        'success' => true,
        'data' => $recordData
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to parse JSA record.']);
}
