<?php
// 1. Force Errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Include Process
require_once(__DIR__ . "/../../process/administrator/maintenanceprefix.process.php");

// 3. Instantiate
$process = new PrefixProcess();

// --- DEBUGGING BLOCK START ---
// If no data was sent, tell us immediately
if (empty($_POST)) {
    die(json_encode(array("STATUS" => "error", "MESSAGE" => "DEBUG: $_POST is empty! Data not reaching PHP.")));
}

// If action is missing
if (!isset($_POST['action'])) {
    die(json_encode(array("STATUS" => "error", "MESSAGE" => "DEBUG: Action key missing in POST data.")));
}
// --- DEBUGGING BLOCK END ---

$action = $_POST['action'];

switch($action){
    case 'LoadPrefixList':
        $process->LoadPrefixList();
        break;

    case 'SavePrefix':
        // DEBUG: Confirm we reached this specific case
        // If the code crashes AFTER this, we know the issue is inside the SavePrefix function
        // die(json_encode(array("STATUS" => "error", "MESSAGE" => "DEBUG: Reached SavePrefix Case!"))); 
        
        $process->SavePrefix($_POST);
        break;

    case 'UpdatePrefix':
        $process->UpdatePrefix($_POST);
        break;
        
    case 'GetPrefixInfo':
        $process->GetPrefixInfo($_POST);
        break;

    case 'UpdateStatus':
        $process->UpdateStatus($_POST);
        break;

    case 'GetValidPrefixes':
        $process->GetValidPrefixes();
        break;
        
    default:
        echo json_encode(array("STATUS" => "error", "MESSAGE" => "DEBUG: Unknown Action: " . $action));
        break;
}
?>