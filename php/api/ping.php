<?php
header('Content-Type: application/json');

$token = '';
if (isset($_POST['token']) && !empty($_POST['token'])) {
    $token = $_POST['token'];
} else {
    // Also try header
    $authHeader = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? '';
    }
    if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    }
}

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'No token']); 
    exit;
}

$sessionsFile = __DIR__ . '/../../data/sessions.json';
if (!file_exists($sessionsFile)) {
    echo json_encode(['success' => false, 'message' => 'No sessions']);
    exit;
}

$sessionsData = json_decode(file_get_contents($sessionsFile), true);
if (!isset($sessionsData[$token])) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

$email = $sessionsData[$token]['email'];

// Mark session as active in sessions.json
$sessionsData[$token]['is_active'] = true;
file_put_contents($sessionsFile, json_encode($sessionsData, JSON_PRETTY_PRINT));

echo json_encode(['success' => true, 'message' => 'Pinged successfully']);
