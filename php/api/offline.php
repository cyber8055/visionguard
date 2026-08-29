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
    echo json_encode(['success' => true]); 
    exit;
}

$sessionsFile = __DIR__ . '/../../data/sessions.json';
if (!file_exists($sessionsFile)) {
    echo json_encode(['success' => true]);
    exit;
}

$sessionsData = json_decode(file_get_contents($sessionsFile), true);
if (!isset($sessionsData[$token])) {
    echo json_encode(['success' => true]);
    exit;
}

$email = $sessionsData[$token]['email'];

// Mark session as offline in sessions.json
$sessionsData[$token]['is_active'] = false;
file_put_contents($sessionsFile, json_encode($sessionsData, JSON_PRETTY_PRINT));

$role = $sessionsData[$token]['role'] ?? 'Unknown';
if ($email) {
    $logFile = __DIR__ . '/../../data/auth_logs.json';
    $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) ?? [] : [];
    
    // Check if the last log for this user was already 'Offline' to prevent spam
    $lastLog = null;
    foreach (array_reverse($logs) as $l) {
        if ($l['email'] === $email) {
            $lastLog = $l;
            break;
        }
    }
    
    if (!$lastLog || ($lastLog['status'] !== 'Offline' && $lastLog['status'] !== 'Logged Out')) {
        $logs[] = [
            'timestamp' => date('c'),
            'email' => $email,
            'role' => $role,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'status' => 'Offline',
            'browser' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        if (count($logs) > 500) array_shift($logs);
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
}

echo json_encode(['success' => true, 'message' => 'Marked offline']);
