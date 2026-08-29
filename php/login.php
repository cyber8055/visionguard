<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($input['email'] ?? $_POST['email'] ?? ''));
$password = trim($input['password'] ?? $_POST['password'] ?? '');
$requestedRole = trim($input['role'] ?? $_POST['role'] ?? 'Manager');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both work email and password.']);
    exit;
}

// Global Auth Logging Helper
function logAuthAttempt($email, $role, $status) {
    $logFile = __DIR__ . '/../data/auth_logs.json';
    $logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) ?? [] : [];
    $logs[] = [
        'timestamp' => date('c'),
        'email' => $email,
        'role' => $role,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'status' => $status,
        'browser' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    if (count($logs) > 500) array_shift($logs);
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

// Master Kill Switch: Check Maintenance Mode
$envFile = __DIR__ . '/../data/env.json';
if (file_exists($envFile)) {
    $envData = json_decode(file_get_contents($envFile), true);
    if (isset($envData['DISABLE_LOGINS']) && $envData['DISABLE_LOGINS'] === true) {
        if ($requestedRole !== 'Admin') {
            echo json_encode(['success' => false, 'message' => 'SYSTEM MAINTENANCE: All non-admin logins are temporarily disabled by the Developer.']);
            exit;
        }
    }
}

// Get DB Password
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Fetch user from DB
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    logAuthAttempt($email, $requestedRole, 'Failed (User Not Found)');
    echo json_encode(['success' => false, 'message' => 'Invalid email, password, or role.']);
    exit;
}

// Password verify (Handle both BCrypt hashes and legacy plaintext)
$passwordMatch = false;
if (strpos($user['password_hash'], '$2y$') === 0 || strpos($user['password_hash'], '$2b$') === 0) {
    $passwordMatch = password_verify($password, $user['password_hash']);
} else {
    // Legacy plain text fallback
    $passwordMatch = ($user['password_hash'] === $password);
}

if (!$passwordMatch) {
    logAuthAttempt($email, $requestedRole, 'Failed (Invalid Password)');
    echo json_encode(['success' => false, 'message' => 'Invalid email, password, or role.']);
    exit;
}

// Check role
$authenticated = false;
$userRole = null;
$isAdminSuperLogin = false;

if ($user['role_name'] === 'Admin') {
    // Admin can login to any role requested
    $authenticated = true;
    $userRole = $requestedRole; 
    $isAdminSuperLogin = true;
} else if ($user['role_name'] === $requestedRole || ($user['role_name'] === 'Operations Manager' && $requestedRole === 'Manager')) {
    // Exact match for non-admins (or mapping Operations Manager to Manager)
    $authenticated = true;
    $userRole = $user['role_name'];
} else if ($user['role_name'] === 'Chief Safety Officer') {
     $authenticated = true;
     $userRole = 'Chief Safety Officer';
}

if (!$authenticated) {
    logAuthAttempt($email, $requestedRole, 'Failed (Role Mismatch)');
    echo json_encode(['success' => false, 'message' => "Your account is assigned the role of {$user['role_name']}, not {$requestedRole}."]);
    exit;
}

// Generate Auth Token
$token = bin2hex(random_bytes(16));
$sessionsFile = __DIR__ . '/../data/sessions.json';
if (!file_exists($sessionsFile)) file_put_contents($sessionsFile, '{}');
$sessionsData = json_decode(file_get_contents($sessionsFile), true);

$sessionsData[$token] = [
    'email' => $email,
    'role' => $userRole,
    'isAdminSuperLogin' => $isAdminSuperLogin,
    'plant_assigned' => $user['plant'],
    'created_at' => time(),
    'expires_at' => time() + (24 * 60 * 60), // 24 hours
    'is_active' => true
];
file_put_contents($sessionsFile, json_encode($sessionsData, JSON_PRETTY_PRINT));

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
} else if ($userRole === 'Chief Safety Officer') {
    $redirectUrl = 'dashboard-cso.html';
}

$msg = $isAdminSuperLogin && $userRole !== 'Admin' 
       ? "Admin Override: Logged in as {$userRole}"
       : "Welcome back, {$user['name']}! Sign-in successful.";

// RECORD LOG IN USER's HISTORY inside users.json ONLY IF STILL USING JSON FALLBACK
// Actually, since we migrated to MySQL, let's just log to auth_logs
logAuthAttempt($email, $userRole, 'Success');

echo json_encode([
    'success' => true,
    'message' => $msg,
    'redirect' => $redirectUrl,
    'token' => $token
]);

