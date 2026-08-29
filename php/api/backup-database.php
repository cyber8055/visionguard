<?php
// backup-database.php
// Creates a zip archive of the data directory and forces a download.
// SUPER ADMIN ONLY

session_start();
// Basic Auth check (assuming admin)
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     die("Unauthorized access.");
// }

require_once __DIR__ . '/../config.php';

// Verify Developer Password
$devPassProvided = $_GET['dev_pass'] ?? '';
$envPath = __DIR__ . '/../../data/env.json';
if (file_exists($envPath)) {
    $envData = json_decode(file_get_contents($envPath), true);
    $actualDevPass = $envData['DEV_PASSWORD'] ?? '';
    if ($actualDevPass !== '' && $devPassProvided !== $actualDevPass) {
        die("Unauthorized: Incorrect Developer Password.");
    }
} else {
    die("FATAL: Cannot load environment configuration to verify identity.");
}

// Ensure error reporting is off for clean JSON output
error_reporting(0);

$dataDir = '../../data';
$backupFilename = 'visionguard_backup_' . date('Y-m-d_H-i-s') . '.zip';
$backupFilePath = sys_get_temp_dir() . '/' . $backupFilename;

if (!extension_loaded('zip')) {
    die("ZIP extension is not enabled on this server.");
}

$zip = new ZipArchive();
if ($zip->open($backupFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Cannot create backup file.");
}

// Add all files in the data directory to the zip
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dataDir),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($files as $name => $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen(realpath($dataDir)) + 1);
        $zip->addFile($filePath, $relativePath);
    }
}

$zip->close();

// Force Download
if (file_exists($backupFilePath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($backupFilePath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($backupFilePath));
    
    // Clear output buffer before sending the file
    ob_clean();
    flush();
    
    readfile($backupFilePath);
    
    // Clean up temp file
    unlink($backupFilePath);
    exit;
} else {
    die("Backup creation failed.");
}
?>
