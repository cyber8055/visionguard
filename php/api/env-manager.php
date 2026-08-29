<?php
header('Content-Type: application/json');
// Security Settings Manager API

// Basic Auth Check
// session_start();
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

$envPath = '../../data/env.json';

// Fetch settings
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($envPath)) {
        $data = file_get_contents($envPath);
        $decoded = json_decode($data, true);
        if(isset($decoded['DEV_PASSWORD'])) {
             // Do not send dev password back to the frontend
             unset($decoded['DEV_PASSWORD']);
        }
        echo json_encode(['success' => true, 'data' => $decoded]);
    } else {
        echo json_encode(['success' => false, 'message' => 'env.json not found']);
    }
    exit;
}

// Update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }
    
    $existingEnv = [];
    if (file_exists($envPath)) {
        $existingEnv = json_decode(file_get_contents($envPath), true);
    }
    
    // Verify Developer Password before allowing save
    $providedPassword = isset($data['DEV_PASSWORD']) ? $data['DEV_PASSWORD'] : '';
    $actualPassword = isset($existingEnv['DEV_PASSWORD']) ? $existingEnv['DEV_PASSWORD'] : '';
    
    if ($actualPassword !== '' && $providedPassword !== $actualPassword) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Incorrect Developer Password.']);
        exit;
    }
    
    // Merge new values
    foreach ($data as $key => $val) {
        $existingEnv[$key] = $val;
    }
    
    // Write back
    if (file_put_contents($envPath, json_encode($existingEnv, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'message' => 'Environment settings updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to write to env.json']);
    }
    exit;
}
?>
