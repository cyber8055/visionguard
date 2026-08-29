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

$envFile = __DIR__ . '/../../data/env.json';
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify user exists and check role
    $stmt = $pdo->prepare("
        SELECT u.id, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Account not found.']);
        exit;
    }

    // Role check (Admin or exact role or mapped role)
    $allowed = ($user['role_name'] === $role || $user['role_name'] === 'Admin' || 
               ($user['role_name'] === 'Operations Manager' && $role === 'Manager') ||
               $role === 'Chief Safety Officer');

    if (!$allowed) {
        echo json_encode(['success' => false, 'message' => "Role mismatch: Account is registered as {$user['role_name']}."]);
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $updateStmt->execute([$hashedPassword, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password reset successfully! You can now log in.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error during password reset.']);
}

