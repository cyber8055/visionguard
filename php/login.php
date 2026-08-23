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

// Read users database
$usersFile = __DIR__ . '/data/users.json';
if (!file_exists($usersFile)) {
    echo json_encode(['success' => false, 'message' => 'User database not found.']);
    exit;
}
$usersData = json_decode(file_get_contents($usersFile), true);

$authenticated = false;
$userRole = null;
$isAdminSuperLogin = false;

foreach ($usersData as $u) {
    if ($u['email'] === $email && $u['password'] === $password) {
        if ($u['role'] === 'Admin') {
            // Admin can login to any role requested
            $authenticated = true;
            $userRole = $role; // Set the session role to what they requested
            $isAdminSuperLogin = true;
            break;
        } else if ($u['role'] === $role) {
            // Exact match for non-admins
            $authenticated = true;
            $userRole = $u['role'];
            break;
        }
    }
}

if (!$authenticated) {
    echo json_encode(['success' => false, 'message' => 'Invalid email, password, or role.']);
    exit;
}

$_SESSION['user_role'] = $userRole;

// Role-Based Target Redirection
$redirectUrl = 'dashboard-manager.html';
if ($userRole === 'Supervisor') {
    $redirectUrl = 'dashboard-supervisor.html';
} else if ($userRole === 'Safety Officer') {
    $redirectUrl = 'dashboard-safety.html';
} else if ($userRole === 'Worker') {
    $redirectUrl = 'dashboard-worker.html';
} else if ($userRole === 'Admin') {
    $redirectUrl = 'dashboard-admin.html';
}

$msg = $isAdminSuperLogin && $userRole !== 'Admin' 
       ? "Admin Override: Logged in as {$userRole}"
       : "Welcome back, {$userRole}! Sign-in successful.";

echo json_encode([
    'success' => true,
    'message' => $msg,
    'redirect' => $redirectUrl
]);
