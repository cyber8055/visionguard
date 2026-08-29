<?php
header('Content-Type: application/json');

$sessionsFile = __DIR__ . '/../../data/sessions.json';
$usersFile = __DIR__ . '/../../data/users.json';
$incidentsFile = __DIR__ . '/../../data/incidents.json';

require_once __DIR__ . '/auth-helper.php';

$auth = verifyAuthToken();
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}
$email = $auth['user']['email'];
$envFile = __DIR__ . '/../../data/env.json';
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'User not found in database']);
        exit;
    }

    $roleId = $currentUser['role_id'];
    // 1=Admin, 2=Manager, 3=Supervisor, 4=Safety Officer, 5=Worker, 6=CSO
    $role = 'Unknown';
    if($roleId == 1) $role = 'Admin';
    if($roleId == 2) $role = 'Manager';
    if($roleId == 6) $role = 'Chief Safety Officer';

    $userPlant = $currentUser['plant'] ?? 'Unknown';
    $filteredIncidents = [];

    if ($role === 'Admin' || $role === 'Chief Safety Officer') {
        $incStmt = $pdo->query("SELECT * FROM incidents ORDER BY incident_timestamp DESC");
        $filteredIncidents = $incStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $incStmt = $pdo->prepare("SELECT * FROM incidents WHERE plant = ? ORDER BY incident_timestamp DESC");
        $incStmt->execute([$userPlant]);
        $filteredIncidents = $incStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Map MySQL output to match expected JSON structure
    foreach($filteredIncidents as &$inc) {
        $inc['timestamp'] = $inc['incident_timestamp'];
    }

    echo json_encode(['success' => true, 'data' => $filteredIncidents]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
}
