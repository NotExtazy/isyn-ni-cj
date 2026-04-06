<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$processPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/process/generalledger/fundconfiguration.process.php';
include_once($processPath);

$process = new FundConfigurationProcess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'LoadPage':
            $result = $process->LoadPage();
            echo json_encode($result);
            break;

        case 'SaveConfiguration':
            $fundsJson = $_POST['funds'] ?? '[]';
            $funds = json_decode($fundsJson, true);
            $result = $process->SaveConfiguration($funds);
            echo json_encode($result);
            break;

        default:
            echo json_encode([
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Invalid action'
            ]);
            break;
    }
} else {
    echo json_encode([
        'STATUS' => 'ERROR',
        'MESSAGE' => 'Invalid request method'
    ]);
}
?>
