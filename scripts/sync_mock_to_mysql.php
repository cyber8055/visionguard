<?php
// Script to sync mock JSON data to MySQL Database
$env = json_decode(file_get_contents(__DIR__ . '/../data/env.json'), true);
$host = '127.0.0.1';
$db = 'vision_guard_db';
$user = 'root';
$pass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usersData = json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true);
    $incidentsData = json_decode(file_get_contents(__DIR__ . '/../data/incidents.json'), true);

    $pdo->beginTransaction();

    // 1. Sync Users
    $rolesStmt = $pdo->query("SELECT id, name FROM roles");
    $rolesMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $rolesMap[$r['name']] = $r['id'];
    }

    $insertUser = $pdo->prepare("
        INSERT INTO users (id, role_id, email, password_hash, name, plant, reports_to)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            role_id=VALUES(role_id), 
            email=VALUES(email),
            name=VALUES(name),
            plant=VALUES(plant),
            reports_to=VALUES(reports_to)
    ");

    $userMap = []; // Collect IDs to ensure foreign keys don't fail by sorting or running twice
    foreach ($usersData as $u) {
        $userMap[$u['id']] = $u;
    }

    // Insert all users first without reports_to (to satisfy foreign key constraints)
    foreach ($userMap as $u) {
        $roleId = $rolesMap[$u['role']] ?? 5; // Default to worker
        // Only hash if it's not already a bcrypt hash (mock script uses "pass123" or "abhishek8055")
        $pwd = $u['password'];
        $hash = (strpos($pwd, '$2y$') === 0) ? $pwd : password_hash($pwd, PASSWORD_DEFAULT);
        $plant = $u['plant'] ?? null;
        
        $insertUser->execute([
            $u['id'], 
            $roleId, 
            $u['email'], 
            $hash, 
            $u['name'], 
            $plant, 
            null // Null out reports_to initially
        ]);
    }

    // Update with reports_to now that all user records exist
    $updateReportsTo = $pdo->prepare("UPDATE users SET reports_to = ? WHERE id = ?");
    foreach ($userMap as $u) {
        if (isset($u['reports_to']) && $u['reports_to']) {
            $updateReportsTo->execute([$u['reports_to'], $u['id']]);
        }
    }
    echo "Synced " . count($userMap) . " users to MySQL.\n";

    // 2. Sync Incidents
    // Empty the table first as requested to avoid duplicate clashes with timestamps
    $pdo->exec("DELETE FROM incidents");
    
    $insertInc = $pdo->prepare("
        INSERT INTO incidents (id, incident_timestamp, severity, type, plant, location, reported_by, status, description)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $incCount = 0;
    foreach ($incidentsData as $inc) {
        // Handle ISO8601 to MySQL DATETIME
        $ts = date("Y-m-d H:i:s", strtotime($inc['timestamp']));
        
        $insertInc->execute([
            $inc['id'],
            $ts,
            $inc['severity'],
            $inc['type'],
            $inc['plant'] ?? null,
            $inc['location'],
            $inc['reported_by'],
            $inc['status'],
            $inc['description']
        ]);
        $incCount++;
    }
    
    echo "Synced $incCount incidents to MySQL.\n";

    $pdo->commit();
    echo "Data migration successful.\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error during sync: " . $e->getMessage() . "\n");
}
