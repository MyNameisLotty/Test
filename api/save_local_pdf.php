<?php
// api/save_local_pdf.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = preg_replace('/[^A-Za-z0-9]/', '', $_POST['filename'] ?? 'INV_UNKNOWN');
    $htmlContent = $_POST['html'] ?? '';

    // Hard path target configuration
    $targetFolder = 'C:/xampp/htdocs/hvf-app/Invoices/';
    
    if (!file_exists($targetFolder)) {
        if (!mkdir($targetFolder, 0777, true)) {
            die("Error: Failed to dynamically generate output directory path.");
        }
    }

    $targetFile = $targetFolder . $filename . '.html';

    if (empty($htmlContent)) {
        die("Error: HTML data packet was received completely blank.");
    }

    if (file_put_contents($targetFile, $htmlContent) !== false) {
        echo "Success: Snapshot file stored safely inside " . $targetFile;
    } else {
        echo "Error: Windows denied write access to folder path direction structural locations.";
    }
} else {
    echo "Error: Invalid request delivery method used.";
}