<?php
// fragment_database.php
// Safely splits the 2.1 MB ptw_database.json into smaller, category-specific files.

$sourceFile = __DIR__ . '/../php/data/ptw_database.json';
$outputDir = __DIR__ . '/../php/data/';

if (!file_exists($sourceFile)) {
    die("Source file not found!\n");
}

$rawData = file_get_contents($sourceFile);
$jsonArray = json_decode($rawData, true);

if (!is_array($jsonArray)) {
    die("Invalid JSON structure in source file!\n");
}

$categories = [
    'hot_work' => ['hot work', 'welding', 'grinding', 'cutting', 'spark'],
    'confined_space' => ['confined space', 'vessel', 'tank', 'trench', 'asphyxiation'],
    'electrical' => ['electrical', 'loto', 'voltage', 'lockout', 'tagout', 'shock'],
    'height' => ['height', 'scaffold', 'fall', 'fragile roof', 'harness', 'lanyard'],
    'excavation' => ['excavation', 'digging', 'earth', 'trench', 'underground'],
    'lifting' => ['lifting', 'crane', 'rigging', 'hoist', 'sling', 'suspended load']
];

$fragmentedData = [
    'common' => [],
    'hot_work' => [],
    'confined_space' => [],
    'electrical' => [],
    'height' => [],
    'excavation' => [],
    'lifting' => []
];

foreach ($jsonArray as $item) {
    $itemStr = strtolower(json_encode($item));
    
    // As per user request: We don't omit architecture/UI data here, 
    // we keep EVERYTHING but put it in common if it has no specific hazard keywords.
    // The API itself can filter out UI/UX later if needed.
    
    $assignedToCategory = false;
    
    foreach ($categories as $catName => $keywords) {
        $matches = false;
        foreach ($keywords as $kw) {
            if (strpos($itemStr, $kw) !== false) {
                $matches = true;
                break;
            }
        }
        
        if ($matches) {
            $fragmentedData[$catName][] = $item;
            $assignedToCategory = true;
        }
    }
    
    // Always add to common so nothing is ever missed.
    // The common file will basically be the entire database,
    // ensuring zero omission, but the categorized files will be smaller.
    $fragmentedData['common'][] = $item;
}

// Write the fragmented files safely
foreach ($fragmentedData as $catName => $data) {
    $outFile = $outputDir . 'ptw_' . $catName . '.json';
    file_put_contents($outFile, json_encode($data, JSON_PRETTY_PRINT));
    echo "Created ptw_{$catName}.json with " . count($data) . " items.\n";
}

echo "\nFragmentation completed successfully. Original file remains untouched.\n";
