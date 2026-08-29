<?php
header('Content-Type: application/json');

$configFile = __DIR__ . '/../../data/plant_config.json';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($configFile)) {
        echo file_get_contents($configFile);
    } else {
        echo json_encode([]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
        exit;
    }

    // Load existing to merge or override
    $current = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

    $current['facility_name'] = $input['facility_name'] ?? $current['facility_name'] ?? '';
    $current['total_zones'] = $input['total_zones'] ?? $current['total_zones'] ?? 1;
    $current['operating_shifts'] = $input['operating_shifts'] ?? $current['operating_shifts'] ?? 1;
    $current['timezone'] = $input['timezone'] ?? $current['timezone'] ?? 'UTC';
    $current['emergency_protocol'] = $input['emergency_protocol'] ?? $current['emergency_protocol'] ?? 'Standard';
    $current['max_occupancy'] = $input['max_occupancy'] ?? $current['max_occupancy'] ?? 100;

    $bytes = file_put_contents($configFile, json_encode($current, JSON_PRETTY_PRINT));
    if ($bytes !== false) {
        echo json_encode(['success' => true, 'message' => 'Configuration saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to write configuration file. Check permissions.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
