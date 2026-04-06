<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $processPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/process/generalledger/financialstatement.process.php';
    
    if (!file_exists($processPath)) {
        throw new Exception('Process file not found');
    }
    
    require_once($processPath);

    $process = new FinancialStatementProcess();

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'LoadPage':
            $result = $process->LoadPage();
            break;

        case 'GetIncomeStatement':
            $fund = $_POST['fund'] ?? '';
            $startDate = $_POST['startDate'] ?? '';
            $endDate = $_POST['endDate'] ?? '';
            
            if (empty($startDate) || empty($endDate)) {
                $result = [
                    'STATUS' => 'ERROR',
                    'MESSAGE' => 'Start date and end date are required'
                ];
            } else {
                $result = $process->GetIncomeStatement($fund, $startDate, $endDate);
            }
            break;

        case 'GetBalanceSheet':
            $fund = $_POST['fund'] ?? '';
            $asOfDate = $_POST['asOfDate'] ?? '';
            
            if (empty($asOfDate)) {
                $result = [
                    'STATUS' => 'ERROR',
                    'MESSAGE' => 'As of date is required'
                ];
            } else {
                $result = $process->GetBalanceSheet($fund, $asOfDate);
            }
            break;

        case 'GetCashFlowStatement':
            $fund = $_POST['fund'] ?? '';
            $startDate = $_POST['startDate'] ?? '';
            $endDate = $_POST['endDate'] ?? '';
            
            if (empty($startDate) || empty($endDate)) {
                $result = [
                    'STATUS' => 'ERROR',
                    'MESSAGE' => 'Start date and end date are required'
                ];
            } else {
                $result = $process->GetCashFlowStatement($fund, $startDate, $endDate);
            }
            break;

        default:
            $result = [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Invalid action: ' . $action
            ];
            break;
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'STATUS' => 'ERROR',
        'MESSAGE' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
