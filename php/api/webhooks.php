<?php
header('Content-Type: application/json');

$configFile = __DIR__ . '/../../data/webhooks.json';

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

    if (!$input || empty($input['name']) || empty($input['url'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload or missing fields.']);
        exit;
    }

    $webhooks = file_exists($configFile) ? json_decode(file_get_contents($configFile), true) : [];

    // Generate a simple ID
    $newId = 'WH-' . (count($webhooks) + 101);

    $newWebhook = [
        'id' => $newId,
        'name' => $input['name'],
        'url' => $input['url'],
        'events' => $input['events'] ?? [],
        'status' => $input['status'] ?? 'Active'
    ];

    $webhooks[] = $newWebhook;

    $bytes = file_put_contents($configFile, json_encode($webhooks, JSON_PRETTY_PRINT));
    if ($bytes !== false) {
        echo json_encode(['success' => true, 'message' => 'Webhook added successfully.', 'data' => $newWebhook]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to write webhooks file.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
