<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$email = strtolower(trim($input['email'] ?? ''));
$password = trim($input['password'] ?? '');
$role = trim($input['role'] ?? '');

if (empty($name) || empty($email) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

$envFile = __DIR__ . '/../data/env.json';
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email is already registered.']);
        exit;
    }

    // Resolve role ID
    $roleName = ($role === 'Manager') ? 'Manager' : $role;
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$roleName]);
    $roleId = $stmt->fetchColumn();

    if (!$roleId) {
        // Fallback to Worker or create role
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Worker'");
        $stmt->execute();
        $roleId = $stmt->fetchColumn() ?: 5;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $newId = 'USR-REG-' . strtoupper(substr(uniqid(), -6));
    $plant = in_array($role, ['Supervisor', 'Manager']) ? 'Plant A' : null;

    $insertStmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant) VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->execute([$newId, $name, $email, $hashedPassword, $roleId, $plant]);

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully! You can now log in.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error during registration.'
    ]);
}

