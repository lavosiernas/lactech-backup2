<?php
/**
 * SafeCode Installer Service
 * Serves the .ps1 script for Windows installations
 */

$scriptPath = __DIR__ . '/../scripts/install.ps1';

if (file_exists($scriptPath)) {
    // Set headers to indicate a PowerShell script
    header('Content-Type: text/plain; charset=utf-8');
    
    // Read and output the script
    readfile($scriptPath);
} else {
    header("HTTP/1.1 404 Not Found");
    echo "Installer script not found.";
}
