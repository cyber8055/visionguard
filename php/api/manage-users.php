<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/auth-helper.php';

$auth = verifyAuthToken('Admin');
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}

$envFile = __DIR__ . '/../../data/env.json';
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET: List all users ───────────────────────────────────────────────────
if ($method === 'GET') {
    // Read active sessions to determine online status
    $sessionsFile = __DIR__ . '/../../data/sessions.json';
    $activeEmails = [];
    if (file_exists($sessionsFile)) {
        $sessionsData = json_decode(file_get_contents($sessionsFile), true) ?? [];
        foreach ($sessionsData as $token => $sData) {
            if ($sData['expires_at'] > time() && !empty($sData['is_active'])) {
                $activeEmails[] = strtolower($sData['email']);
            }
        }
    }

    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, '••••••••' as password, u.plant, u.reports_to, r.name as role
        FROM users u
        JOIN roles r ON u.role_id = r.id
        ORDER BY r.id, u.name
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $authLogsFile = __DIR__ . '/../../data/auth_logs.json';
    $authLogs = file_exists($authLogsFile) ? json_decode(file_get_contents($authLogsFile), true) ?? [] : [];
    
    // Group logs by email (newest first)
    $logsByEmail = [];
    foreach (array_reverse($authLogs) as $log) {
        $email = strtolower($log['email']);
        if (!isset($logsByEmail[$email])) {
            $logsByEmail[$email] = [];
        }
        $action = 'Unknown';
        if ($log['status'] === 'Success') $action = 'Logged In';
        elseif ($log['status'] === 'Offline') $action = 'Went Offline';
        elseif ($log['status'] === 'Logged Out') $action = 'Logged Out';
        else $action = 'Failed Login';

        $logsByEmail[$email][] = [
            'action' => $action,
            'details' => 'Role requested: ' . $log['role'] . ' | Browser: ' . (explode(' ', $log['browser'])[0] ?? 'Unknown'),
            'timestamp' => $log['timestamp'],
            'ip' => $log['ip_address']
        ];
    }

    // Inject is_online flag and history
    foreach ($users as &$u) {
        $emailKey = strtolower($u['email']);
        $u['is_online'] = in_array($emailKey, $activeEmails);
        $u['history'] = $logsByEmail[$emailKey] ?? [];
    }
    unset($u);

    echo json_encode(['success' => true, 'data' => $users]);
    exit;
}

// ─── POST: Create user ─────────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['email']) || empty($input['password']) || empty($input['role'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
        exit;
    }

    // Check duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([strtolower($input['email'])]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already exists!']);
        exit;
    }

    // Resolve role ID
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$input['role']]);
    $roleId = $stmt->fetchColumn();
    if (!$roleId) {
        echo json_encode(['success' => false, 'message' => 'Invalid role: ' . $input['role']]);
        exit;
    }

    $newId = 'USR-' . strtoupper(substr(uniqid(), -6));
    $name = $input['name'] ?? 'New User';
    $email = strtolower($input['email']);
    $plant = $input['plant'] ?? null;
    $reportsTo = !empty($input['reports_to']) ? $input['reports_to'] : null;
    
    // Securely hash password with standard bcrypt
    $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$newId, $name, $email, $passwordHash, $roleId, $plant, $reportsTo]);

    echo json_encode(['success' => true, 'message' => 'User added successfully!', 'data' => [
        'id' => $newId, 'name' => $name, 'email' => $email, 'role' => $input['role'],
        'plant' => $plant, 'reports_to' => $reportsTo
    ]]);
    exit;
}

// ─── PUT: Update user ──────────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing User ID.']);
        exit;
    }

    // Resolve role ID if role is being changed
    $setClauses = [];
    $params = [];

    if (!empty($input['name'])) {
        $setClauses[] = "name = ?";
        $params[] = $input['name'];
    }
    if (!empty($input['email'])) {
        $setClauses[] = "email = ?";
        $params[] = strtolower($input['email']);
    }
    if (!empty($input['password']) && $input['password'] !== '••••••••') {
        $setClauses[] = "password_hash = ?";
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    if (!empty($input['role'])) {
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$input['role']]);
        $roleId = $stmt->fetchColumn();
        if ($roleId) {
            $setClauses[] = "role_id = ?";
            $params[] = $roleId;
        }
    }
    if (array_key_exists('plant', $input)) {
        $setClauses[] = "plant = ?";
        $params[] = $input['plant'];
    }
    if (array_key_exists('reports_to', $input)) {
        $setClauses[] = "reports_to = ?";
        $params[] = !empty($input['reports_to']) ? $input['reports_to'] : null;
    }

    if (empty($setClauses)) {
        echo json_encode(['success' => false, 'message' => 'Nothing to update.']);
        exit;
    }

    $params[] = $id;
    $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    exit;
}

// ─── DELETE: Remove user ───────────────────────────────────────────────────
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing User ID.']);
        exit;
    }

    // Set children's reports_to to NULL before deleting to avoid FK constraint errors
    $stmt = $pdo->prepare("UPDATE users SET reports_to = NULL WHERE reports_to = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported method.']);
