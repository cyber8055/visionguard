<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=vision_guard_db;charset=utf8mb4', 'root', 'aashish123');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Delete user "abhishek" (the redundant admin with email abhisheksharmaarts@gmail.com)
$stmt = $db->prepare("DELETE FROM users WHERE email = 'abhisheksharmaarts@gmail.com'");
$stmt->execute();
echo "Deleted 'abhishek' admin user.\n";

// 2. Rename System Administrator to Chief Manager
$stmt = $db->prepare("UPDATE users SET name = 'Chief Manager' WHERE email = 'abhisheksharana9@gmail.com'");
$stmt->execute();
echo "Renamed System Administrator to Chief Manager.\n";

// 3. The user wants Operations Manager Plant A and Operations Manager Plant B to report to the Chief Manager.
// We currently have "USR-M1" (Manager Plant A) and "USR-M2" (Manager Plant B), and we just added "USR-O1" (Operations Manager).
// Let's delete USR-O1, and rename USR-M1/USR-M2.
$stmt = $db->prepare("DELETE FROM users WHERE id = 'USR-O1'");
$stmt->execute();
echo "Deleted intermediate Operations Manager.\n";

// Rename Plant Managers to Operations Manager Plant A/B and set them to report to USR-1 (Chief Manager)
$stmt = $db->prepare("UPDATE users SET name = 'Operations Manager Plant A', reports_to = 'USR-1' WHERE id = 'USR-M1'");
$stmt->execute();
$stmt = $db->prepare("UPDATE users SET name = 'Operations Manager Plant B', reports_to = 'USR-1' WHERE id = 'USR-M2'");
$stmt->execute();
echo "Updated Plant Managers to Operations Managers reporting to Chief Manager.\n";
