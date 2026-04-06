<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once("../../process/inventorymanagement/consignment.process.php");

$process = new Process();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'Initialize':
                $process->Initialize();
                break;

            case 'FetchItems':
                $process->FetchItems($_POST);
                break;

            case 'LoadProductSummary':
                $process->LoadProductSummary($_POST);
                break;

            case 'SaveConsignment':
                // Handle JSON payload
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                if ($data) {
                    $process->SaveConsignment($data);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                }
                break;

            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid Action']);
                break;
        }
    }
}
?>