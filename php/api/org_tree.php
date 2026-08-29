<?php
header('Content-Type: application/json');

$sessionsFile = __DIR__ . '/../../data/sessions.json';

// Validate Token
$authHeader = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? '';
}
$token = str_replace('Bearer ', '', $authHeader);

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: No token provided']);
    exit;
}

$sessions = json_decode(file_get_contents($sessionsFile), true) ?? [];
if (!isset($sessions[$token]) || time() > $sessions[$token]['expires_at']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid token']);
    exit;
}

// All roles that can view the org tree
$allowedRoles = ['Admin', 'Manager', 'Supervisor', 'Safety Officer', 'Chief Safety Officer', 'Executive Manager'];
$requesterRole = $sessions[$token]['role'] ?? '';
$requesterEmail = $sessions[$token]['email'] ?? '';

if (!in_array($requesterRole, $allowedRoles)) {
    echo json_encode(['success' => false, 'message' => 'Forbidden: Insufficient privileges']);
    exit;
}

$envFile = __DIR__ . '/../../data/env.json';
$env = file_exists($envFile) ? json_decode(file_get_contents($envFile), true) : [];
$dbPass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4", "root", $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch ALL users with their roles (excluding password for security)
    $stmt = $pdo->query("
        SELECT u.id, u.email, u.name, u.plant, u.reports_to, r.name as role
        FROM users u
        JOIN roles r ON u.role_id = r.id
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mock blood groups for the UI modal
    $bloodGroups = ['A+', 'O+', 'B+', 'AB+', 'O-', 'A-'];
    $bgIndex = 0;

    // Build user map — include ALL roles (Workers too)
    $userMap = [];
    foreach ($users as $u) {
        $u['children'] = [];
        $u['blood_group'] = $bloodGroups[$bgIndex % count($bloodGroups)];
        $bgIndex++;
        $userMap[$u['id']] = $u;
    }

    // Build tree by linking children to parents
    $tree = [];
    foreach ($userMap as $id => &$u) {
        if (!empty($u['reports_to']) && isset($userMap[$u['reports_to']])) {
            $userMap[$u['reports_to']]['children'][] = &$u;
        } else {
            // No parent found => root node
            $tree[] = &$u;
        }
    }
    unset($u);

    // Determine output based on role
    $adminRoles = ['Admin'];

    if (in_array($requesterRole, $adminRoles)) {
        // Admins see FULL tree
        $outputData = $tree;
    } else {
        // Everyone else sees their own sub-tree only
        $requesterNode = null;
        foreach ($userMap as $id => $u) {
            if (strtolower($u['email']) === strtolower($requesterEmail)) {
                $requesterNode = $u;
                break;
            }
        }
        $outputData = $requesterNode ? [$requesterNode] : [];
    }

    echo json_encode(['success' => true, 'data' => $outputData]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
