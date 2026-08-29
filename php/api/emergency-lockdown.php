<?php
header('Content-Type: application/json');

// Emergency Lockdown Endpoint
// This endpoint suspends all currently active/approved permits.

$permitsFile = '../../data/permits.json';

if (!file_exists($permitsFile)) {
    echo json_encode(['success' => false, 'message' => 'Permits database not found.']);
    exit;
}

$permitsData = json_decode(file_get_contents($permitsFile), true);
if (!$permitsData) {
    echo json_encode(['success' => false, 'message' => 'Failed to parse permits database.']);
    exit;
}

$suspendedCount = 0;
$timestamp = date('Y-m-d H:i:s');

foreach ($permitsData as &$permit) {
    // If the permit is active or approved, suspend it immediately.
    if (in_array(strtolower($permit['status']), ['active', 'approved'])) {
        $permit['status'] = 'suspended';
        
        // Add to history log if exists, else create it
        if (!isset($permit['history'])) {
            $permit['history'] = [];
        }
        
        $permit['history'][] = [
            'action' => 'emergency_lockdown',
            'timestamp' => $timestamp,
            'note' => 'System-wide emergency lockdown initiated by Super Admin. Permit suspended immediately.'
        ];
        
        $suspendedCount++;
    }
}

// Save back to DB
if (file_put_contents($permitsFile, json_encode($permitsData, JSON_PRETTY_PRINT))) {
    echo json_encode([
        'success' => true, 
        'message' => 'Lockdown successful.',
        'suspended_count' => $suspendedCount
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to write to database.']);
}
?>
