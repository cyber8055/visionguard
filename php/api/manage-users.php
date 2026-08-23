<?php
header('Content-Type: application/json');
session_start();

// Ensure only Admin can manage users (Basic check for demo)
if (!isset($_SESSION['authenticated_user']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access. Admin only.']);
    exit;
}

$usersFile = __DIR__ . '/../data/users.json';
$method = $_SERVER['REQUEST_METHOD'];

// Helper to save data
function saveUsers($data, $file) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Ensure the file exists
if (!file_exists($usersFile)) {
    saveUsers([], $usersFile);
}
$usersData = json_decode(file_get_contents($usersFile), true);

if ($method === 'GET') {
    // Return all users
    echo json_encode(['success' => true, 'data' => $usersData]);
    exit;
}

if ($method === 'POST') {
    // Create new user
    $input = json_decode(file_get_contents('php://input'), true);
    if(empty($input['email']) || empty($input['password']) || empty($input['role'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }
    
    // Check if email already exists
    foreach($usersData as $u) {
        if(strtolower($u['email']) === strtolower($input['email'])) {
            echo json_encode(['success' => false, 'message' => 'Email already exists!']);
            exit;
        }
    }

    $newUser = [
        'id' => uniqid(),
        'email' => strtolower($input['email']),
        'password' => $input['password'],
        'role' => $input['role'],
        'name' => $input['name'] ?? 'New User'
    ];
    $usersData[] = $newUser;
    
    if (saveUsers($usersData, $usersFile)) {
        echo json_encode(['success' => true, 'message' => 'User added successfully!', 'data' => $newUser]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save user data.']);
    }
    exit;
}

if ($method === 'PUT') {
    // Edit existing user
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    
    if(!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing User ID.']);
        exit;
    }

    $updated = false;
    foreach($usersData as &$u) {
        if($u['id'] === $id) {
            $u['email'] = strtolower($input['email'] ?? $u['email']);
            if(!empty($input['password'])) $u['password'] = $input['password'];
            $u['role'] = $input['role'] ?? $u['role'];
            $u['name'] = $input['name'] ?? $u['name'];
            $updated = true;
            break;
        }
    }

    if ($updated && saveUsers($usersData, $usersFile)) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user.']);
    }
    exit;
}

if ($method === 'DELETE') {
    // Delete user
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;

    if(!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing User ID.']);
        exit;
    }

    $initialCount = count($usersData);
    $usersData = array_values(array_filter($usersData, function($u) use ($id) {
        return $u['id'] !== $id;
    }));

    if (count($usersData) < $initialCount) {
        saveUsers($usersData, $usersFile);
        echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported method.']);
