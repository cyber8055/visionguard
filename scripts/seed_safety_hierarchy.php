<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4', 'root', 'aashish123');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Add Roles
$roles = ['Chief Safety Officer', 'Operational Safety Manager'];
foreach ($roles as $role) {
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$role]);
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO roles (name) VALUES (?)");
        $stmt->execute([$role]);
        echo "Inserted role: $role\n";
    }
}

// Get Role IDs
$stmt = $db->prepare("SELECT id FROM roles WHERE name = 'Chief Safety Officer'");
$stmt->execute();
$csoRoleId = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT id FROM roles WHERE name = 'Operational Safety Manager'");
$stmt->execute();
$osmRoleId = $stmt->fetchColumn();

// 2. Add Users
// Helper to hash password
$hash = password_hash('password123', PASSWORD_DEFAULT);

// Delete existing users if we are re-running
$db->exec("DELETE FROM users WHERE email IN ('cso@visionguard.local', 'osm_a@visionguard.local', 'osm_b@visionguard.local')");

// Chief Safety Officer
$csoId = 'USR-CSO-' . uniqid();
$stmt = $db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $csoId,
    'Chief Safety Officer',
    'cso@visionguard.local',
    $hash,
    $csoRoleId,
    null,
    null // Doesn't report to Chief Manager, is a separate root
]);
echo "Inserted Chief Safety Officer\n";

// Operational Safety Manager Plant A
$osmIdA = 'USR-OSM-A-' . uniqid();
$stmt->execute([
    $osmIdA,
    'Operational Safety Manager Plant A',
    'osm_a@visionguard.local',
    $hash,
    $osmRoleId,
    'Plant A',
    $csoId
]);
echo "Inserted Operational Safety Manager Plant A\n";

// Operational Safety Manager Plant B
$osmIdB = 'USR-OSM-B-' . uniqid();
$stmt->execute([
    $osmIdB,
    'Operational Safety Manager Plant B',
    'osm_b@visionguard.local',
    $hash,
    $osmRoleId,
    'Plant B',
    $csoId
]);
echo "Inserted Operational Safety Manager Plant B\n";

echo "Database Safety Hierarchy Setup Complete.\n";
