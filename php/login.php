<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($input['email'] ?? $_POST['email'] ?? ''));
$password = trim($input['password'] ?? $_POST['password'] ?? '');
$role = trim($input['role'] ?? $_POST['role'] ?? 'Manager');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both work email and password.']);
    exit;
}

$_SESSION['authenticated_user'] = $email;
$_SESSION['user_role'] = $role;

// Role-Based Target Redirection
$redirectUrl = 'dashboard-manager.html';
if ($role === 'Supervisor') {
    $redirectUrl = 'dashboard-supervisor.html';
} else if ($role === 'Safety Officer') {
    $redirectUrl = 'dashboard-safety.html';
} else if ($role === 'Worker') {
    $redirectUrl = 'dashboard-worker.html';
}

echo json_encode([
    'success' => true,
    'message' => "Welcome back, {$role}! Sign-in successful.",
    'redirect' => $redirectUrl
]);
