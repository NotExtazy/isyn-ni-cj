<?php
session_start();
include_once("../../process/administrator/userlogs.process.php");

$process = new Process();

if (isset($_POST["action"])) {
    $action = $_POST["action"];

    switch ($action) {
        case "LoadLogs":
            $process->LoadLogs($_POST);
            break;

        case "GetFilterOptions":
            $process->GetFilterOptions();
            break;
            
        default:
            echo json_encode(["status" => "error", "message" => "Invalid Action"]);
            break;
    }
}
?>