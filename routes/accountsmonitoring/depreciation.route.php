<?php
include_once(__DIR__ . "/../../process/accountsmonitoring/depreciation.process.php");

$process = new Process();
$action  = $_POST['action'] ?? '';

switch ($action) {
    case 'LoadPage':             $process->LoadPage();             break;
    case 'LoadEquipmentList':    $process->LoadEquipmentList();    break;
    case 'SaveEquipment':        $process->SaveEquipment($_POST);  break;
    case 'SavePPE':              $process->SavePPE($_POST);        break;
    case 'GetNextTransactionID': $process->GetNextTransactionID($_POST); break;
    case 'DisposeEquipment':     $process->DisposeEquipment($_POST); break;
    case 'DeleteEquipment':      $process->DeleteEquipment($_POST); break;
    case 'RunMonthlyDepreciation': $process->RunMonthlyDepreciation(); break;
    case 'GenerateJV':           $process->GenerateJV($_POST);     break;
    default: echo json_encode(['error' => 'Invalid action']); break;
}
