<?php
// Script to mock plant assignments and reporting hierarchy in users.json and incidents.json

$usersFile = __DIR__ . '/../data/users.json';
$incidentsFile = __DIR__ . '/../data/incidents.json';

// 1. Update Users
$users = json_decode(file_get_contents($usersFile), true);

// Create some dummy users if they don't exist to ensure we have enough hierarchy
$dummyUsers = [
    ["id" => "USR-M1", "email" => "managerA@visionguard.com", "password" => "pass123", "role" => "Manager", "name" => "Manager Plant A", "plant" => "Plant A"],
    ["id" => "USR-M2", "email" => "managerB@visionguard.com", "password" => "pass123", "role" => "Manager", "name" => "Manager Plant B", "plant" => "Plant B"],
    
    ["id" => "USR-S1", "email" => "supA1@visionguard.com", "password" => "pass123", "role" => "Supervisor", "name" => "Supervisor A1", "plant" => "Plant A", "reports_to" => "USR-M1"],
    ["id" => "USR-S2", "email" => "supA2@visionguard.com", "password" => "pass123", "role" => "Supervisor", "name" => "Supervisor A2", "plant" => "Plant A", "reports_to" => "USR-M1"],
    ["id" => "USR-S3", "email" => "supB1@visionguard.com", "password" => "pass123", "role" => "Supervisor", "name" => "Supervisor B1", "plant" => "Plant B", "reports_to" => "USR-M2"],
    
    ["id" => "USR-W1", "email" => "workerA1@visionguard.com", "password" => "pass123", "role" => "Worker", "name" => "Worker A1", "plant" => "Plant A", "reports_to" => "USR-S1"],
    ["id" => "USR-W2", "email" => "workerA2@visionguard.com", "password" => "pass123", "role" => "Worker", "name" => "Worker A2", "plant" => "Plant A", "reports_to" => "USR-S1"],
    ["id" => "USR-W3", "email" => "workerA3@visionguard.com", "password" => "pass123", "role" => "Worker", "name" => "Worker A3", "plant" => "Plant A", "reports_to" => "USR-S2"],
    ["id" => "USR-W4", "email" => "workerB1@visionguard.com", "password" => "pass123", "role" => "Worker", "name" => "Worker B1", "plant" => "Plant B", "reports_to" => "USR-S3"]
];

$existingIds = array_column($users, 'id');
foreach ($dummyUsers as $du) {
    if (!in_array($du['id'], $existingIds)) {
        $du['is_online'] = false;
        $du['history'] = [];
        $users[] = $du;
    }
}

// Assign plants to existing users if missing
foreach ($users as &$u) {
    if (!isset($u['plant'])) {
        if ($u['role'] === 'Admin') {
            $u['plant'] = 'All';
        } else {
            // Randomly assign to Plant A or B
            $u['plant'] = (rand(0, 1) == 0) ? 'Plant A' : 'Plant B';
        }
    }
    
    // Assign reports_to if missing for hierarchy
    if ($u['role'] === 'Supervisor' && !isset($u['reports_to'])) {
        $u['reports_to'] = ($u['plant'] === 'Plant A') ? 'USR-M1' : 'USR-M2';
    }
    if ($u['role'] === 'Worker' && !isset($u['reports_to'])) {
        $u['reports_to'] = ($u['plant'] === 'Plant A') ? 'USR-S1' : 'USR-S3';
    }
}
file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));

// 2. Update Incidents
$incidents = json_decode(file_get_contents($incidentsFile), true);
foreach ($incidents as &$inc) {
    if (!isset($inc['plant'])) {
        $inc['plant'] = (rand(0, 1) == 0) ? 'Plant A' : 'Plant B';
        // Prefix location if not already
        if (strpos($inc['location'], 'Plant') === false) {
            $inc['location'] = $inc['plant'] . ' - ' . $inc['location'];
        }
    }
}
file_put_contents($incidentsFile, json_encode($incidents, JSON_PRETTY_PRINT));

echo "Mock data injected successfully.\n";
