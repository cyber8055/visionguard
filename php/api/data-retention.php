<?php
header('Content-Type: application/json');

$configFile = __DIR__ . '/../../data/retention_policy.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($configFile)) {
        echo file_get_contents($configFile);
    } else {
        echo json_encode([
            'incident_reports_days' => 90,
            'auth_logs_days' => 90,
            'camera_footage_days' => 30,
            'auto_purge_enabled' => true
        ]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
        exit;
    }

    $current = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

    $current['incident_reports_days'] = (int)($input['incident_reports_days'] ?? $current['incident_reports_days'] ?? 90);
    $current['auth_logs_days'] = (int)($input['auth_logs_days'] ?? $current['auth_logs_days'] ?? 90);
    $current['camera_footage_days'] = (int)($input['camera_footage_days'] ?? $current['camera_footage_days'] ?? 30);
    $current['auto_purge_enabled'] = isset($input['auto_purge_enabled']) ? (bool)$input['auto_purge_enabled'] : ($current['auto_purge_enabled'] ?? true);

    $bytes = file_put_contents($configFile, json_encode($current, JSON_PRETTY_PRINT));
    if ($bytes !== false) {
        echo json_encode(['success' => true, 'message' => 'Data Retention Policies saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to write policy file.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
