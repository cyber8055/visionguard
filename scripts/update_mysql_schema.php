<?php
$env = json_decode(file_get_contents(__DIR__ . '/../data/env.json'), true);
$host = '127.0.0.1';
$db = 'vision_guard_db';
$user = 'root';
$pass = $env['DB_PASSWORD'] ?? 'aashish123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Add columns to users table
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN plant VARCHAR(100) NULL");
        echo "Added 'plant' to users.\n";
    } catch(Exception $e) { echo "plant column may already exist in users.\n"; }

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN reports_to VARCHAR(64) NULL");
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_users_reports_to FOREIGN KEY (reports_to) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL");
        echo "Added 'reports_to' and FK to users.\n";
    } catch(Exception $e) { echo "reports_to column or FK may already exist in users.\n"; }

    // 2. Ensure incidents table exists and has plant
    $incidentsTableSQL = "
    CREATE TABLE IF NOT EXISTS incidents (
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
    echo "Ensured incidents table exists with plant column.\n";

    echo "Schema update successful!\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage() . "\n");
}
