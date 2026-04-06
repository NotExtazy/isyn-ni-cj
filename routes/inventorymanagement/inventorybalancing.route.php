<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For AJAX requests, check if user is authenticated
if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !$_SESSION["AUTHENTICATED"]) {
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Authentication required']);
    exit;
}

include_once(__DIR__ . "/../../process/inventorymanagement/inventorybalancing.process.php");

$process = new Process();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}