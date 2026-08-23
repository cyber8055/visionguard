<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($input['email'] ?? ''));
$role = trim($input['role'] ?? '');
$newPassword = $input['newPassword'] ?? '';

if (empty($email) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$usersFile = __DIR__ . '/../data/users.json';
if (!file_exists($usersFile)) {
    echo json_encode(['success' => false, 'message' => 'Database not found.']);
    exit;
}

$usersData = json_decode(file_get_contents($usersFile), true);
$updated = false;

foreach ($usersData as &$u) {
    if ($u['email'] === $email) {
        // We only allow reset if the role matches, or if it's the Admin
        if ($u['role'] === $role || $u['role'] === 'Admin') {
            $u['password'] = $newPassword;
            $updated = true;
            break;
        }
    }
}

if ($updated) {
    file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Account not found or role mismatch.']);
}
