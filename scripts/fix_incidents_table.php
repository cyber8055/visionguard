<?php
$env = json_decode(file_get_contents(__DIR__ . '/../data/env.json'), true);
$host = '127.0.0.1';
$db = 'vision_guard_db';
$user = 'root';
$pass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("DROP TABLE IF EXISTS incidents");
    
    $incidentsTableSQL = "
    CREATE TABLE incidents (
        id VARCHAR(64) PRIMARY KEY,
        incident_timestamp DATETIME NOT NULL,
        severity VARCHAR(30) NOT NULL,
        type VARCHAR(100) NOT NULL,
        plant VARCHAR(100) NULL,
        location VARCHAR(255) NOT NULL,
        reported_by VARCHAR(255) NULL,
        status VARCHAR(50) NOT NULL,
        description TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_incidents_timestamp (incident_timestamp),
        INDEX idx_incidents_plant (plant),
        INDEX idx_incidents_severity (severity),
        INDEX idx_incidents_status (status)
    ) ENGINE=InnoDB;
    ";
    $pdo->exec($incidentsTableSQL);
    echo "Recreated incidents table successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
