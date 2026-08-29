<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/auth-helper.php';
$auth = verifyAuthToken();
if (!$auth['success']) {
    echo json_encode($auth);
    exit;
}
$user = $auth['user'];

$dataFile = __DIR__ . '/../../data/permits.json';

// Initialize default data if file doesn't exist
if (!file_exists($dataFile)) {
    $defaultPermits = [
        [
            "id" => 1,
            "serial_no" => "PTW-20260822-5501",
            "permit_type" => "Confined Space + Excavation",
            "location" => "Zone 12 - Unit 01",
            "equipment" => "Reactor Vessel V-101",
            "company_supervisor" => "Deepak Kumar",
            "status" => "APPROVED",
            "job_type" => "MOC / GPC / BD / PM / USC / Project",
            "plant" => "Plant A",
            "created_at" => "2026-08-22 08:00:00"
        ],
        [
            "id" => 2,
            "serial_no" => "PTW-20260822-5502",
            "permit_type" => "Electrical Work / LOTO",
            "location" => "Substation 04 - MCC-02",
            "equipment" => "Feeder Pump P-204",
            "company_supervisor" => "Rajesh Sharma",
            "status" => "PENDING MANAGER",
            "job_type" => "PM Maintenance",
            "plant" => "Plant B",
            "created_at" => "2026-08-22 09:30:00"
        ],
        [
            "id" => 3,
            "serial_no" => "PTW-20260822-5503",
            "permit_type" => "Fragile Roof / Work at Height",
            "location" => "Warehouse Shed 3",
            "equipment" => "Roof Sheet Replacement",
            "company_supervisor" => "Amit Verma",
            "status" => "ACTIVE",
            "job_type" => "Civil Repair",
            "plant" => "Plant A",
            "created_at" => "2026-08-22 11:15:00"
        ]
    ];
    file_put_contents($dataFile, json_encode($defaultPermits, JSON_PRETTY_PRINT));
}

$permits = json_decode(file_get_contents($dataFile), true) ?? [];

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $newId = count($permits) + 1;
    $serialNo = 'PTW-' . date('Ymd') . '-' . rand(5000, 9999);
    
    $newPermit = [
        "id" => $newId,
        "serial_no" => $serialNo,
        "permit_type" => $input['permit_type'] ?? 'General Work',
        "location" => $input['location'] ?? 'Zone 1',
        "equipment" => $input['equipment'] ?? 'N/A',
        "company_supervisor" => $input['company_supervisor'] ?? $user['name'] ?? 'Supervisor',
        "status" => "SUBMITTED",
        "job_type" => $input['job_type'] ?? 'Maintenance',
        "description" => $input['description'] ?? '',
        "plant" => $user['plant_assigned'] ?? 'Global',
        "created_at" => date('Y-m-d H:i:s')
    ];

    array_unshift($permits, $newPermit);
    file_put_contents($dataFile, json_encode($permits, JSON_PRETTY_PRINT));

    echo json_encode([
        'success' => true,
        'serial_no' => $serialNo,
        'id' => $newId,
        'permit' => $newPermit
    ]);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'extend') {
    $input = json_decode(file_get_contents('php://input'), true);
    $permitId = $input['permit_id'] ?? null;
    $extendDays = (int)($input['extend_days'] ?? 1);
    $remarks = $input['remarks'] ?? '';

    if (!$permitId) {
        echo json_encode(['success' => false, 'message' => 'Permit ID missing']);
        exit;
    }

    $found = false;
    foreach ($permits as &$p) {
        if ($p['serial_no'] === $permitId || $p['id'] == $permitId) {
            // Found the permit
            $p['extended_by_days'] = $extendDays;
            $p['extension_remarks'] = $remarks;
            $p['extended_at'] = date('Y-m-d H:i:s');
            $p['extended_by_user'] = $user['name'] ?? 'Supervisor';
            $found = true;
            break;
        }
    }

    if ($found) {
        file_put_contents($dataFile, json_encode($permits, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'message' => "Permit extended by $extendDays days."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Permit not found.']);
    }
    exit;
}

// GET - return list or specific permit
$id = $_GET['id'] ?? null;
if ($id) {
    $found = array_filter($permits, fn($p) => $p['id'] == $id);
    if (!empty($found)) {
        echo json_encode(['success' => true, 'permit' => array_values($found)[0]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Permit not found']);
    }
    exit;
}

// Data Segregation: Filter by Plant if user is a Supervisor or Worker
if (in_array($user['role'], ['Supervisor', 'Worker']) && !empty($user['plant_assigned'])) {
    $permits = array_filter($permits, function($p) use ($user) {
        return isset($p['plant']) && $p['plant'] === $user['plant_assigned'];
    });
    // Re-index array
    $permits = array_values($permits);
}

echo json_encode([
    'success' => true,
    'permits' => $permits
]);
