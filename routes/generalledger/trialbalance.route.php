<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log for debugging
error_log("Trial Balance Route - Action: " . ($_POST['action'] ?? 'none'));

try {
    $processPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/process/generalledger/trialbalance.process.php';
    
    if (!file_exists($processPath)) {
        error_log("Process file not found: $processPath");
        http_response_code(500);
        echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Process file not found']);
        exit;
    }
    
    include_once($processPath);
    
    $process = new Process();
    
    if (isset($_POST['action']) && $_POST['action'] === 'LoadPage') {
        $process->LoadPage();
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'Retrieve') {
        $process->Retrieve($_POST);
        exit;
    }
    
    // No valid action
    http_response_code(400);
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Invalid action']);
    
} catch (Exception $e) {
    error_log("Trial Balance Route Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => $e->getMessage()]);
}
