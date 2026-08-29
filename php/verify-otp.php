<?php
header('Content-Type: application/json');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($input['email'] ?? $_POST['email'] ?? ''));
$otp = trim($input['otp'] ?? $_POST['otp'] ?? '');
$role = trim($input['role'] ?? $_POST['role'] ?? 'Manager');

if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both your email and OTP code.']);
    exit;
}

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

if (!isset($_SESSION['otp_code']) || !isset($_SESSION['otp_expiry'])) {
    echo json_encode(['success' => false, 'message' => 'No active OTP request found. Please request a new OTP.']);
    exit;
}

if (time() > $_SESSION['otp_expiry']) {
    unset($_SESSION['otp_code'], $_SESSION['otp_expiry'], $_SESSION['otp_email']);
    logAuthAttempt($email, $role, 'Failed (OTP Expired)');
    echo json_encode(['success' => false, 'message' => 'OTP code has expired. Please click Resend OTP.']);
    exit;
}

if ($_SESSION['otp_code'] === $otp) {
    unset($_SESSION['otp_code'], $_SESSION['otp_expiry']);
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

    logAuthAttempt($email, $role, 'Success (OTP Verified)');
    echo json_encode([
        'success' => true,
        'message' => "2-Step OTP Verified! Welcome back, {$role}.",
        'redirect' => $redirectUrl
    ]);
} else {
    logAuthAttempt($email, $role, 'Failed (Invalid OTP)');
    echo json_encode(['success' => false, 'message' => 'Invalid OTP code. Please check your email and try again.']);
}
