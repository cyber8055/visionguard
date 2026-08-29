<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4', 'root', 'aashish123');
$stmt = $db->query("SELECT id, name, email FROM users WHERE email LIKE '%manager%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
