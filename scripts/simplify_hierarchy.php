<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4', 'root', 'aashish123');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Starting hierarchy simplification and cleanup...\n";

// Disable foreign key checks for clean reorganization
$db->exec("SET FOREIGN_KEY_CHECKS = 0");

// Truncate users table
$db->exec("TRUNCATE TABLE users");
echo "Users table cleared.\n";

// 1. Clean up unused / redundant roles
$db->exec("DELETE FROM roles WHERE name = 'Operational Safety Manager'");
echo "Cleaned up Operational Safety Manager role.\n";

// Ensure required roles exist
$requiredRoles = ['Admin', 'Manager', 'Supervisor', 'Safety Officer', 'Worker', 'Chief Safety Officer'];
foreach ($requiredRoles as $rName) {
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$rName]);
    if (!$stmt->fetch()) {
        $db->prepare("INSERT INTO roles (name) VALUES (?)")->execute([$rName]);
        echo "Created role: $rName\n";
    }
}

// Map role names to IDs
$roleMap = [];
$stmt = $db->query("SELECT id, name FROM roles");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $roleMap[$row['name']] = $row['id'];
}

$pass = password_hash('password123', PASSWORD_DEFAULT); // Secure standard bcrypt hash

// Insert Users Hierarchy
// -------------------------------------------------------------
// ROOT 1: OPERATIONS HIERARCHY (Admin / Chief Manager)
// -------------------------------------------------------------
$cManagerId = 'USR-ADM-1';
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute([$cManagerId, 'Chief Manager (Admin)', 'abhisheksharana9@gmail.com', $pass, $roleMap['Admin'], 'All', null]);

// Operations Manager Plant A
$omPlantAId = 'USR-OM-A';
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute([$omPlantAId, 'Operations Manager Plant A', 'managerA@visionguard.com', $pass, $roleMap['Manager'], 'Plant A', $cManagerId]);

// Operations Manager Plant B
$omPlantBId = 'USR-OM-B';
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute([$omPlantBId, 'Operations Manager Plant B', 'managerB@visionguard.com', $pass, $roleMap['Manager'], 'Plant B', $cManagerId]);

// Supervisors for Plant A (Under Operations Manager Plant A)
$supA1Id = 'USR-SUP-A1';
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute([$supA1Id, 'Supervisor A1 (Shift 1)', 'supA1@visionguard.com', $pass, $roleMap['Supervisor'], 'Plant A', $omPlantAId]);

$supA2Id = 'USR-SUP-A2';
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute([$supA2Id, 'Supervisor A2 (Shift 2)', 'supA2@visionguard.com', $pass, $roleMap['Supervisor'], 'Plant A', $omPlantAId]);

// Supervisor for Plant B (Under Operations Manager Plant B)
$supB1Id = 'USR-SUP-B1';
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute([$supB1Id, 'Supervisor Plant B', 'supB1@visionguard.com', $pass, $roleMap['Supervisor'], 'Plant B', $omPlantBId]);

// Workers for Plant A (Under Supervisor A1)
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute(['USR-WRK-A1', 'Worker A1 (Assembly)', 'workerA1@visionguard.com', $pass, $roleMap['Worker'], 'Plant A', $supA1Id]);

$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute(['USR-WRK-A2', 'Worker A2 (Welding)', 'workerA2@visionguard.com', $pass, $roleMap['Worker'], 'Plant A', $supA1Id]);

// Workers for Plant A (Under Supervisor A2)
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute(['USR-WRK-A3', 'Worker A3 (Packaging)', 'workerA3@visionguard.com', $pass, $roleMap['Worker'], 'Plant A', $supA2Id]);

// Workers for Plant B (Under Supervisor Plant B)
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute(['USR-WRK-B1', 'Worker B1 (Operations)', 'workerB1@visionguard.com', $pass, $roleMap['Worker'], 'Plant B', $supB1Id]);


// -------------------------------------------------------------
// ROOT 2: SAFETY HIERARCHY (Chief Safety Officer)
// -------------------------------------------------------------
$csoId = 'USR-CSO-1';
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute([$csoId, 'Chief Safety Officer', 'cso@visionguard.local', $pass, $roleMap['Chief Safety Officer'], 'All', null]);

// Safety Officer 1 (Plant A)
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute(['USR-SO-A', 'Safety Officer Plant A', 'safety1@visionguard.local', $pass, $roleMap['Safety Officer'], 'Plant A', $csoId]);

// Safety Officer 2 (Plant B)
$db->prepare("INSERT INTO users (id, name, email, password_hash, role_id, plant, reports_to) VALUES (?, ?, ?, ?, ?, ?, ?)")
   ->execute(['USR-SO-B', 'Safety Officer Plant B', 'safety2@visionguard.local', $pass, $roleMap['Safety Officer'], 'Plant B', $csoId]);

$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "Hierarchy successfully simplified and populated!\n";
