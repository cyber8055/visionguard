<?php
header('Content-Type: application/json');
session_start();

$input = json_decode(file_get_contents('php://input'), true);
$jobDescription = $input['job_description'] ?? 'Chemical vessel internal cleaning and pipe excavation';
$permitTypes = (array)($input['permit_types'] ?? [$input['permit_type'] ?? 'Confined Space']);

$jsaNumber = 'JSA-' . date('Ymd') . '-' . rand(1000, 9999);
$isCombined = count($permitTypes) > 1;

// Base Step 1: Alignment & Toolbox Talk
$steps = [
    [
        'step_number' => 1,
        'step_description' => 'Pre-job Alignment & Multi-Permit Toolbox Talk',
        'hazards' => 'Lack of awareness regarding combined hazards (' . implode(', ', $permitTypes) . ')',
        'consequences' => 'Improper PPE usage, unauthorized entry, cross-hazard incidents',
        'controls' => 'Conduct mandatory joint toolbox talk, verify worker competency for all selected work types (' . implode(' + ', $permitTypes) . '), review combined JSA with team',
        'responsible_person' => 'Initiator / Supervisor',
        'required_ppe' => 'Helmet, Safety Shoes, Safety Goggles'
    ]
];

$precautions = [
    'Combined work scope requires strict adherence to all associated permit checklists.',
    'Gas test must be repeated at mandatory configured intervals.'
];

// Combine Logic for Confined Space
if (in_array('Confined Space', $permitTypes)) {
    $steps[] = [
        'step_number' => count($steps) + 1,
        'step_description' => 'Confined Space Isolation & Standby Observer Deputation',
        'hazards' => 'Toxic gas buildup (H2S, CO), O2 deficiency (<19.5%), accidental motive power startup',
        'consequences' => 'Asphyxiation, toxic poisoning, entrapment',
        'controls' => 'Verify blinding, disconnect electrical motive power, deploy 22-duty standby observer, test O2/LEL/Toxic gases',
        'responsible_person' => 'Safety Officer & Standby Observer',
        'required_ppe' => 'Full Body Harness, Airline Respirator, 24V Flameproof Light'
    ];
    $precautions[] = 'Standby observer must remain outside entry point at all times.';
    $precautions[] = 'Confined Space permit renewal is strictly prohibited.';
}

// Combine Logic for Excavation
if (in_array('Excavation', $permitTypes)) {
    $steps[] = [
        'step_number' => count($steps) + 1,
        'step_description' => 'Underground Service Clearance & Trench Shoring',
        'hazards' => 'Underground 33kV electrical cables, IT cables, Fire water lines, soil collapse',
        'consequences' => 'Electrocution, communications blackout, trench cave-in',
        'controls' => 'Obtain 4-department clearance (Elec, Inst, Fire, Ops). Cable tracer inspection. Install shoring & sloping for depth > 1.5m. Keep loose earth 1m away.',
        'responsible_person' => 'Civil In-Charge & Permittee',
        'required_ppe' => 'Rubber Boots, Heavy Duty Leather Gloves, Barricading'
    ];

    if (in_array('Confined Space', $permitTypes)) {
        $precautions[] = 'Excavation depth > 1.5m combined with Confined Space: Mandatory escape ladder and continuous O2 monitoring required.';
    }
}

// Combine Logic for Electrical Work
if (in_array('Electrical Work', $permitTypes)) {
    $steps[] = [
        'step_number' => count($steps) + 1,
        'step_description' => 'Electrical Source Lockout / Tagout (LOTO)',
        'hazards' => 'Live current shock, arc flash, unexpected machine re-energization',
        'consequences' => 'Fatal electrocution, severe arc burns',
        'controls' => 'Isolate main breakers, apply physical locks & tags, test before touch with calibrated voltmeter, use rubber matting & wooden platform',
        'responsible_person' => 'Electrical In-Charge',
        'required_ppe' => 'Shock-proof Rubber Gloves, Face Shield, Arc Flash Suit'
    ];
}

// Combine Logic for Fragile Roof / Work at Height
if (in_array('Fragile Roof', $permitTypes) || in_array('Work at Height', $permitTypes)) {
    $steps[] = [
        'step_number' => count($steps) + 1,
        'step_description' => 'Height Access & Roof Fall Protection',
        'hazards' => 'Fragile roof sheet breakage, fall from height, overhead live lines',
        'consequences' => 'Fatal fall, trauma, impact injuries',
        'controls' => 'Install crawling boards / duck ladders, anchor double cross-rope lifeline, deploy safety nets underneath, de-energize overhead power lines',
        'responsible_person' => 'Civil In-Charge & Safety Officer',
        'required_ppe' => 'Full Body Harness with Shock Absorber, Safety Net'
    ];
}

// Final Step: Job Handover
$steps[] = [
    'step_number' => count($steps) + 1,
    'step_description' => 'Work Site Restoration & 10-Point Job Handover',
    'hazards' => 'Unrestored belt/coupling guards, leftover scrap, loose flange bolts',
    'consequences' => 'Mechanical failure on startup, tripping hazards',
    'controls' => 'Remove all scrap & tools, refit all safety guards, tighten flange bolts, complete 10-point handover checklist with dual sign-off',
    'responsible_person' => 'Production & Maintenance In-Charges',
    'required_ppe' => 'Helmet, Safety Shoes, Leather Gloves'
];

echo json_encode([
    'success' => true,
    'jsa_number' => $jsaNumber,
    'is_combined' => $isCombined,
    'combined_types' => $permitTypes,
    'job_summary' => "Dynamic AI JSA generated for " . ($isCombined ? "COMBINED PERMIT (" . implode(' + ', $permitTypes) . ")" : $permitTypes[0]) . " covering: {$jobDescription}",
    'recommended_permit' => implode(' + ', $permitTypes),
    'steps' => $steps,
    'special_precautions' => $precautions
]);
