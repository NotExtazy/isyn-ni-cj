<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once("../../process/administrator/maintenanceaddress.process.php");

$process = new AddressProcess();

// --- FRONTEND / PROFILING HANDLERS ---
if(isset($_POST['maintenance_action'])) {
    $action = $_POST['maintenance_action'];
    
    if($action == 'get_All'){
        $process->GetRegions(); 
    }
    if($action == 'get_province'){
        $process->GetProvinces(['filter' => $_POST['region_selected']]); 
    }
    if($action == 'get_citytown'){
        $process->GetCities(['filter' => $_POST['province_selected']]); 
    }
    if($action == 'get_brgy'){
        $process->GetBarangays(['filter' => $_POST['citytown_selected']]); 
    }
}

// --- ADMIN MAINTENANCE HANDLERS ---
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'LoadAddressList': $process->LoadAddressList(); break;
        case 'GetAddressInfo': $process->GetAddressInfo($_POST); break;
        // Admin Mode: pass all data so admin_mode might be inside $_POST
        case 'GetProvinces': $process->GetProvinces($_POST); break;
        case 'GetCities': $process->GetCities($_POST); break;
        
        case 'SaveInfo': $process->SaveInfo($_POST); break;
        case 'UpdateInfo': $process->UpdateInfo($_POST); break;
        case 'UpdateStatus': $process->UpdateStatus($_POST); break;
    }
}
?>