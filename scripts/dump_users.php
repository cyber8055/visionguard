<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4', 'root', 'aashish123');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->query("SELECT u.id, u.name, u.email, u.plant, u.reports_to, r.name as role FROM users u JOIN roles r ON u.role_id = r.id ORDER BY r.id, u.name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "[{$u['role']}] {$u['name']} | email: {$u['email']} | plant: {$u['plant']} | reports_to: {$u['reports_to']} | id: {$u['id']}\n";
}
