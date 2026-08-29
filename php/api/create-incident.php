<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$sessionsFile = __DIR__ . '/../../data/sessions.json';

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
    
    // Get User Details for the incident
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'User not found in database']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['type']) || empty($input['severity']) || empty($input['location']) || empty($input['description'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    $incidentId = 'INC-' . strtoupper(substr(uniqid(), -6));
    $timestamp = date('Y-m-d H:i:s');
    $plant = $currentUser['plant'] ?? 'Unknown';
    $status = 'Open';

    $stmt = $pdo->prepare("INSERT INTO incidents (id, incident_timestamp, type, severity, plant, location, reported_by, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $incidentId, 
        $timestamp, 
        $input['type'], 
        $input['severity'], 
        $plant, 
        $input['location'], 
        $email, 
        $input['description'], 
        $status
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Incident reported successfully!',
        'incident_id' => $incidentId
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
