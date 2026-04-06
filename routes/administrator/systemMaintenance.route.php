<?php
    session_start();
    include_once('../../process/administrator/systemMaintenance.process.php');

    $process = new SystemMaintenance();

    // HANDLE FILE DOWNLOAD (GET REQUEST)
    if (isset($_GET['action']) && $_GET['action'] == 'DownloadBackup') {
        $process->DownloadBackup();
    }
?>