<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$role = $input['role'] ?? '';
$sheet_url = $input['sheet_url'] ?? '';

if (empty($role) || empty($sheet_url)) {
    echo json_encode(['success' => false, 'message' => 'Missing role or Google Sheet URL.']);
    exit;
}

// Extract Sheet ID from URL
if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $sheet_url, $matches)) {
    $sheet_id = $matches[1];
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Google Sheet URL format. Make sure you pasted the full link.']);
    exit;
}

// Fetch CSV from Google Sheets
$csv_url = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format=csv";
$csv_content = @file_get_contents($csv_url);

if ($csv_content === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch data. Ensure the Google Sheet is set to "Anyone with the link can view".']);
    exit;
}

// Parse CSV
$lines = explode("\n", $csv_content);
if (count($lines) < 2) {
    echo json_encode(['success' => false, 'message' => 'Sheet is empty or missing headers.']);
    exit;
}

$headers = str_getcsv(array_shift($lines));
$headers = array_map('strtolower', array_map('trim', $headers)); // Normalize headers

// Define mapping between expected CSV headers and internal JSON keys based on role
$columnMapping = [];
if ($role === 'Admin' || $role === 'Manager') {
    $columnMapping = ['name' => 'name', 'email' => 'email'];
    if ($role === 'Manager') $columnMapping['department'] = 'department';
} else if ($role === 'Supervisor') {
    $columnMapping = ['name' => 'name', 'email' => 'email', 'passport' => 'passport / id', 'plant_assigned' => 'plant assigned'];
} else if ($role === 'Safety Officer') {
    $columnMapping = ['name' => 'name', 'email' => 'email', 'certifications' => 'certifications'];
} else if ($role === 'Worker') {
    $columnMapping = ['name' => 'name', 'email' => 'email', 'trade' => 'trade (e.g. welder)', 'blood_group' => 'blood group', 'violations' => 'safety violations'];
}

// Read existing users
$usersFile = __DIR__ . '/../../data/users.json';
if (!file_exists($usersFile)) {
    $usersData = [];
} else {
    $usersData = json_decode(file_get_contents($usersFile), true);
    if (!is_array($usersData)) $usersData = [];
}

// Create map of existing users by email
$existingByEmail = [];
foreach ($usersData as $k => $u) {
    $existingByEmail[$u['email']] = $k;
}

$updatedCount = 0;
$addedCount = 0;

foreach ($lines as $line) {
    if (empty(trim($line))) continue;
    $row = str_getcsv($line);
    
    // Map CSV row to associative array
    $rowData = [];
    foreach ($headers as $index => $headerName) {
        $rowData[$headerName] = $row[$index] ?? '';
    }

    // Extract expected fields
    $parsedUser = [];
    foreach ($columnMapping as $internalKey => $expectedHeader) {
        $found = '';
        foreach ($rowData as $k => $v) {
            // Check if the header contains the first word of our expected header (e.g. "Name")
            if (strpos($k, strtolower(explode(' ', $expectedHeader)[0])) !== false) { 
                $found = $v;
                break;
            }
        }
        $parsedUser[$internalKey] = $found;
    }

    if (empty($parsedUser['email'])) continue; // Skip if no email found

    $email = trim($parsedUser['email']);
    if (isset($existingByEmail[$email])) {
        // Update existing user
        $index = $existingByEmail[$email];
        foreach ($parsedUser as $key => $val) {
            if ($key !== 'id' && $key !== 'history' && $key !== 'password') {
                $usersData[$index][$key] = $val;
            }
        }
        $updatedCount++;
    } else {
        // Add new user
        $newUser = [
            'id' => uniqid('user_', true),
            'role' => $role,
            'email' => $email,
            'password' => password_hash('default123', PASSWORD_BCRYPT),
            'history' => [
                [
                    'timestamp' => date('c'),
                    'action' => 'Bulk Account Created',
                    'details' => 'Registered via Google Sheets Sync',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ]
            ]
        ];
        foreach ($parsedUser as $key => $val) {
            $newUser[$key] = $val;
        }
        $usersData[] = $newUser;
        $addedCount++;
    }
}

if (file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true, 
        'message' => "Google Sheet Sync complete! Added: $addedCount, Updated: $updatedCount",
        'added' => $addedCount,
        'updated' => $updatedCount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save to users.json']);
}
