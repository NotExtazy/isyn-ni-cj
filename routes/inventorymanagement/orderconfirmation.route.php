<?php
// Debug: Log access to this file
error_log("Order confirmation route accessed: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For AJAX requests, check if user is authenticated
if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !$_SESSION["AUTHENTICATED"]) {
    error_log("Authentication failed for order confirmation route");
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Authentication required']);
    exit;
}

include_once(__DIR__ . "/../../process/inventorymanagement/orderconfirmation.process.php");

$process = new OrderConfirmationProcess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json');
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
    register_shutdown_function(function() {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>"Server error: ".$e['message']]);
        }
    });
}

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadBranch'){
    $process->LoadBranch($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadCategory'){
    $process->LoadCategory($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadSerialProduct'){
    $process->LoadSerialProduct($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadProductSummary'){
    $process->LoadProductSummary($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SubmitOC'){
    $process->SubmitOC($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'OCSearch'){
    $process->OCSearch($_POST);
}

if(isset($_GET["type"]) && $_GET["type"] == 'PrintOC'){
    // Print functionality not available - reports class missing
    echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>"Print functionality not available"]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || empty($_POST['action']))) {
    echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>"Missing action"]);
}


