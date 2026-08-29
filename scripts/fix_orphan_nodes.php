<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4', 'root', 'aashish123');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fix USR-2 (old Operations Manager placeholder) — link it to Chief Manager
$stmt = $db->prepare("UPDATE users SET reports_to = 'USR-1' WHERE id = 'USR-2'");
$stmt->execute();
echo "Fixed USR-2 (Operations Manager) -> now reports to Chief Manager (USR-1)\n";

// Also rename it to be more specific
$stmt = $db->prepare("UPDATE users SET name = 'Operations Manager (Legacy)' WHERE id = 'USR-2'");
$stmt->execute();
echo "Renamed USR-2 to 'Operations Manager (Legacy)'\n";

echo "Done!\n";
