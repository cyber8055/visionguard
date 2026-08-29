<?php
function verifyAuthToken($requiredRole = null) {
    $authHeader = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? '';
    }
    
    if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        return ['success' => false, 'message' => 'Unauthorized Access. Missing token.'];
    }
    
    $token = $matches[1];
    $sessionsFile = __DIR__ . '/../../data/sessions.json';
    
    if (!file_exists($sessionsFile)) {
        return ['success' => false, 'message' => 'Session database missing.'];
    }
    
    $sessions = json_decode(file_get_contents($sessionsFile), true);
    if (!isset($sessions[$token])) {
        return ['success' => false, 'message' => 'Unauthorized Access. Invalid token.'];
    }
    
    $sessionData = $sessions[$token];
    
    if (time() > $sessionData['expires_at']) {
        unset($sessions[$token]);
        file_put_contents($sessionsFile, json_encode($sessions, JSON_PRETTY_PRINT));
        return ['success' => false, 'message' => 'Session expired. Please log in again.'];
    }
    
    if ($requiredRole !== null && $sessionData['role'] !== $requiredRole) {
        // Admin and Chief Safety Officer have elevated access
        if ($sessionData['role'] !== 'Admin' && $sessionData['role'] !== 'Chief Safety Officer') {
            return ['success' => false, 'message' => "Unauthorized Access. Requires {$requiredRole} role."];
        }
    }
    
    return [
        'success' => true,
        'user' => $sessionData
    ];
}
