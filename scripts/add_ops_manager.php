<?php
$env = json_decode(file_get_contents(__DIR__ . '/../data/env.json'), true);
$host = '127.0.0.1';
$db = 'vision_guard_db';
$user = 'root';
$pass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Get role IDs
    $rolesStmt = $pdo->query("SELECT id, name FROM roles");
    $rolesMap = [];
    while ($r = $rolesStmt->fetch(PDO::FETCH_ASSOC)) {
        $rolesMap[$r['name']] = $r['id'];
    }

    $managerRoleId = $rolesMap['Manager'];

    // 2. Insert Operations Manager
    $opsId = 'USR-O1';
    $opsEmail = 'opsmanager@visionguard.com';
    $opsPass = password_hash('pass123', PASSWORD_DEFAULT);
    
    $insertOps = $pdo->prepare("
        INSERT INTO users (id, role_id, email, password_hash, name, plant, reports_to)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE reports_to=VALUES(reports_to)
    ");
    // Ops manager reports to Admin 'USR-1'
    $insertOps->execute([$opsId, $managerRoleId, $opsEmail, $opsPass, 'Operations Manager', null, 'USR-1']);
    echo "Operations Manager inserted successfully.\n";

    // 3. Update existing Plant Managers to report to the Operations Manager
    $updateManagers = $pdo->prepare("UPDATE users SET reports_to = ? WHERE id IN (?, ?)");
    $updateManagers->execute([$opsId, 'USR-M1', 'USR-M2']);
    echo "Plant Managers updated to report to Operations Manager.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
