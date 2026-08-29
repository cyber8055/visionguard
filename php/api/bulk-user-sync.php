<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$role = $input['role'] ?? '';
$action = $input['action'] ?? '';
$data = $input['data'] ?? [];

if (empty($role) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

$usersFile = __DIR__ . '/../../data/users.json';
if (!file_exists($usersFile)) {
    $usersData = [];
} else {
    $usersData = json_decode(file_get_contents($usersFile), true);
    if (!is_array($usersData)) $usersData = [];
}

if ($action === 'fetch') {
    // Return all users for the specific role
    $roleUsers = array_filter($usersData, function($u) use ($role) {
        return (isset($u['role']) && $u['role'] === $role);
    });
    // Strip passwords before sending to frontend, unless it's a new system where admin can reset.
    // For this Excel sheet, maybe we want to see/edit everything? Let's just remove hash for security, but allow resetting.
    $sanitized = [];
    foreach ($roleUsers as $u) {
        unset($u['password']);
        $sanitized[] = $u;
    }
    echo json_encode(['success' => true, 'data' => array_values($sanitized)]);
    exit;
}

if ($action === 'sync') {
    // $data contains the full grid data for the given role.
    // We should update existing users and insert new ones.
    
    // Create a map of existing users by email
    $existingByEmail = [];
    foreach ($usersData as $k => $u) {
        $existingByEmail[$u['email']] = $k;
    }

    $updatedCount = 0;
    $addedCount = 0;

    foreach ($data as $row) {
        if (empty($row['email'])) continue; // Skip empty rows

        $email = trim($row['email']);
        if (isset($existingByEmail[$email])) {
            // Update existing user
            $index = $existingByEmail[$email];
            foreach ($row as $key => $val) {
                // Don't overwrite history or id directly if not provided
                if ($key !== 'id' && $key !== 'history' && $key !== 'password') {
                    $usersData[$index][$key] = $val;
                }
            }
            $updatedCount++;
        } else {
            // New user from Excel copy-paste
            $newUser = [
                'id' => uniqid('user_', true),
                'role' => $role,
                'email' => $email,
                'password' => password_hash('default123', PASSWORD_BCRYPT), // default password
                'history' => [
                    [
                        'timestamp' => date('c'),
                        'action' => 'Bulk Account Created',
                        'details' => 'Registered via Admin Bulk Sync Grid',
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                    ]
                ]
            ];
            foreach ($row as $key => $val) {
                if ($key !== 'id' && $key !== 'history' && $key !== 'password') {
                    $newUser[$key] = $val;
                }
            }
            $usersData[] = $newUser;
            $addedCount++;
        }
    }

    if (file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT))) {
        echo json_encode([
            'success' => true, 
            'message' => "Synced successfully. Added: $addedCount, Updated: $updatedCount",
            'added' => $addedCount,
            'updated' => $updatedCount
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save to users.json']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
