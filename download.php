<?php
require_once 'config.php';
require_once 'ftpService.php';
requireLogin();

if (!isset($_GET['file'])) {
    header('Location: index.php');
    exit();
}

$remotePath = $_GET['file'];
$fileName = basename($remotePath);

try {
    $ftp = new FtpService();
    
    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'ftp_');
    
    if ($ftp->downloadFile($remotePath, $tempFile)) {
        // Send appropriate headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . filesize($tempFile));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Output file contents
        readfile($tempFile);
        
        // Clean up
        unlink($tempFile);
        exit();
    } else {
        throw new Exception($ftp->getError());
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Download failed: ' . $e->getMessage();
    header('Location: index.php');
    exit();
}