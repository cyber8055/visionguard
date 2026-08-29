<?php
// scripts/inject_chemical_matrix.php
// Safely injects the top 20 chemical LEL/UEL/PEL matrix into the main database array

$dbFile = __DIR__ . '/../php/data/ptw_database.json';

if (!file_exists($dbFile)) {
    die("Database file not found at: $dbFile\n");
}

// 1. Read existing data
$rawData = file_get_contents($dbFile);
$jsonArray = json_decode($rawData, true);

if (!is_array($jsonArray)) {
    die("Invalid JSON structure. Could not decode.\n");
}

// 2. Define the new chemical matrix block
$chemicalMatrix = [
    "chemical_gas_matrix" => [
        "purpose" => "Provides LEL, UEL, and PEL limits for gas detection during Confined Space and Hot Work. Values based on OSHA/NIOSH standards.",
        "chemicals" => [
            ["name" => "Methane", "LEL" => "5.0%", "UEL" => "15.0%", "PEL" => "Simple Asphyxiant"],
            ["name" => "Benzene", "LEL" => "1.2%", "UEL" => "7.8%", "PEL" => "1 ppm (NIOSH: 0.1 ppm)"],
            ["name" => "Toluene", "LEL" => "1.1%", "UEL" => "7.1%", "PEL" => "200 ppm (NIOSH: 100 ppm)"],
            ["name" => "Hydrogen Sulfide", "LEL" => "4.0%", "UEL" => "44.0%", "PEL" => "20 ppm (NIOSH: 10 ppm)"],
            ["name" => "Hexane", "LEL" => "1.1%", "UEL" => "7.5%", "PEL" => "500 ppm (NIOSH: 50 ppm)"],
            ["name" => "Ammonia", "LEL" => "15.0%", "UEL" => "28.0%", "PEL" => "50 ppm (NIOSH: 25 ppm)"],
            ["name" => "Carbon Monoxide", "LEL" => "12.5%", "UEL" => "74.0%", "PEL" => "50 ppm (NIOSH: 35 ppm)"],
            ["name" => "Propane", "LEL" => "2.1%", "UEL" => "9.5%", "PEL" => "1000 ppm"],
            ["name" => "Butane", "LEL" => "2.1%", "UEL" => "8.5%", "PEL" => "800 ppm"],
            ["name" => "Hydrogen", "LEL" => "4.0%", "UEL" => "75.0%", "PEL" => "Simple Asphyxiant"],
            ["name" => "Acetylene", "LEL" => "2.5%", "UEL" => "100.0%", "PEL" => "2500 ppm"],
            ["name" => "Ethylene", "LEL" => "2.7%", "UEL" => "36.0%", "PEL" => "200 ppm"],
            ["name" => "Methanol", "LEL" => "6.0%", "UEL" => "36.0%", "PEL" => "200 ppm"],
            ["name" => "Ethanol", "LEL" => "3.3%", "UEL" => "19.0%", "PEL" => "1000 ppm"],
            ["name" => "Chlorine", "LEL" => "Non-Flammable", "UEL" => "Non-Flammable", "PEL" => "1 ppm (NIOSH: 0.5 ppm)"],
            ["name" => "Sulfur Dioxide", "LEL" => "Non-Flammable", "UEL" => "Non-Flammable", "PEL" => "5 ppm (NIOSH: 2 ppm)"],
            ["name" => "Xylene", "LEL" => "1.1%", "UEL" => "7.0%", "PEL" => "100 ppm"],
            ["name" => "Isopropyl Alcohol", "LEL" => "2.0%", "UEL" => "12.0%", "PEL" => "400 ppm"],
            ["name" => "Acetone", "LEL" => "2.5%", "UEL" => "12.8%", "PEL" => "1000 ppm (NIOSH: 250 ppm)"],
            ["name" => "Formaldehyde", "LEL" => "7.0%", "UEL" => "73.0%", "PEL" => "0.75 ppm (NIOSH: 0.016 ppm)"]
        ]
    ]
];

// Check if it already exists to prevent duplication
$exists = false;
foreach ($jsonArray as $item) {
    if (isset($item['chemical_gas_matrix'])) {
        $exists = true;
        break;
    }
}

if (!$exists) {
    // 3. Append to the main array
    $jsonArray[] = $chemicalMatrix;

    // 4. Save safely
    $newJson = json_encode($jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($dbFile, $newJson)) {
        echo "Successfully injected 20 verified chemicals into the database.\n";
    } else {
        echo "Error saving database.\n";
    }
} else {
    echo "Chemical matrix already exists in the database. No changes made.\n";
}
