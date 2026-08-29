<?php
header('Content-Type: application/json');
require_once __DIR__ . '/auth-helper.php';

$auth = verifyAuthToken();
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}

echo json_encode([
    'success' => true,
    'user' => $auth['user']
]);
