<?php
/**
 * Data Migration Script
 * Migrates data from JSON stores to MySQL Database
 */

$envPath = __DIR__ . '/../data/env.json';
if (!file_exists($envPath)) {
    die("Error: env.json not found.\n");
}

$env = json_decode(file_get_contents($envPath), true);
$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? 'vision_guard_db';
$dbUser = $env['DB_USER'] ?? 'root';
$dbPass = $env['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database '$dbName' successfully.\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// 1. Roles mapping
$rolesMap = [];
$stmt = $pdo->query("SELECT id, name FROM roles");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rolesMap[$row['name']] = $row['id'];
}
echo "Loaded " . count($rolesMap) . " roles from DB.\n";

$pdo->beginTransaction();

try {
    // 2. Migrate Users
    $usersFile = __DIR__ . '/../data/users.json';
    $usersData = [];
    if (file_exists($usersFile)) {
        $usersData = json_decode(file_get_contents($usersFile), true) ?? [];
        
        $stmtUser = $pdo->prepare("INSERT INTO users (id, role_id, email, password_hash, name, is_online, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtAuthLog = $pdo->prepare("INSERT INTO auth_logs (user_id, email_attempted, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?)");

        $usersInserted = 0;
        $logsInserted = 0;

        foreach ($usersData as $u) {
            $roleId = $rolesMap[$u['role']] ?? 5; // Default to Worker if not found
            $hashedPass = password_hash($u['password'], PASSWORD_DEFAULT);
            $isOnline = isset($u['is_online']) && $u['is_online'] ? 1 : 0;
            
            // Insert user
            try {
                $stmtUser->execute([
                    $u['id'],
                    $roleId,
                    strtolower(trim($u['email'])),
                    $hashedPass,
                    trim($u['name']),
                    $isOnline,
                    1 // is_active
                ]);
                $usersInserted++;
            } catch (PDOException $e) {
                // Ignore duplicates if re-running
                if ($e->getCode() != 23000) { throw $e; }
            }

            // Insert histories to auth_logs
            if (!empty($u['history']) && is_array($u['history'])) {
                foreach ($u['history'] as $log) {
                    $ts = date('Y-m-d H:i:s', strtotime($log['timestamp']));
                    $stmtAuthLog->execute([
                        $u['id'],
                        strtolower(trim($u['email'])),
                        $log['action'] ?? 'Unknown',
                        $log['details'] ?? null,
                        $log['ip'] ?? null,
                        $ts
                    ]);
                    $logsInserted++;
                }
            }
        }
        echo "Migrated $usersInserted users.\n";
        echo "Migrated $logsInserted user history logs to auth_logs.\n";
    } else {
        echo "Warning: data/users.json not found.\n";
    }

    // 3. Migrate standalone auth_logs.json
    $authLogsFile = __DIR__ . '/../data/auth_logs.json';
    if (file_exists($authLogsFile)) {
        $logsData = json_decode(file_get_contents($authLogsFile), true) ?? [];
        $stmtAuthLog = $pdo->prepare("INSERT INTO auth_logs (email_attempted, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        
        $standaloneLogs = 0;
        foreach ($logsData as $log) {
            $ts = date('Y-m-d H:i:s', strtotime($log['timestamp']));
            $email = $log['email'] ?? null;
            $action = $log['status'] ?? 'Log';
            $details = ($log['role'] ?? 'Unknown Role');
            
            $stmtAuthLog->execute([
                $email,
                $action,
                $details,
                $log['ip_address'] ?? null,
                $log['browser'] ?? null,
                $ts
            ]);
            $standaloneLogs++;
        }
        echo "Migrated $standaloneLogs standalone auth logs.\n";
    }

    $pdo->commit();
    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Migration failed! Transaction rolled back. Error: " . $e->getMessage() . "\n");
}
?>
