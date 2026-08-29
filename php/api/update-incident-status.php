<?php
header('Content-Type: application/json');

require_once __DIR__ . '/auth-helper.php';

$auth = verifyAuthToken();
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['incident_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing incident ID or new status.']);
    exit;
}

$incidentId = $input['incident_id'];
$newStatus = $input['status'];

$validStatuses = ['Open', 'Investigating', 'Resolved'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $newStatus]);
    exit;
}

$envFile = __DIR__ . '/../../data/env.json';
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user has permission (Admin, CSO, Manager, or Supervisor)
    // Since verifyAuthToken checks if a user is logged in, we'll allow standard authenticated users to update for now, 
    // but ideally we should restrict this by role.
    
    $stmt = $pdo->prepare("UPDATE incidents SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $incidentId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Incident status updated successfully.']);
    } else {
        // If rowCount is 0, either the incident wasn't found or the status was already the same
        echo json_encode(['success' => true, 'message' => 'No changes made or incident not found.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
