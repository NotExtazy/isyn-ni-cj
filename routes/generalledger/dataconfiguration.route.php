<?php
// Data Configuration Route Handler
$processPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/process/generalledger/dataconfiguration.process.php';
include_once($processPath);

$process = new DataConfigurationProcess();

// Get action from POST request
$action = $_POST['action'] ?? '';

// Get username from session
$username = $_SESSION['USERNAME'] ?? 'system';

switch ($action) {
    
    // ═══════════════════════════════════════════════════════════════════════
    // LOAD PAGE
    // ═══════════════════════════════════════════════════════════════════════
    case 'LoadPage':
        $result = $process->LoadPage();
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TAB 1: BEGINNING BALANCE
    // ═══════════════════════════════════════════════════════════════════════
    case 'GetBeginningBalances':
        $fund = $_POST['fund'] ?? '';
        $fiscalYear = $_POST['fiscalYear'] ?? null;
        $result = $process->GetBeginningBalances($fund, $fiscalYear);
        echo json_encode($result);
        break;

    case 'SaveBeginningBalance':
        $fund = $_POST['fund'] ?? '';
        $acctno = $_POST['acctno'] ?? '';
        $accttitle = $_POST['accttitle'] ?? '';
        $balance = $_POST['balance'] ?? 0;
        $fiscalYear = $_POST['fiscalYear'] ?? date('Y');
        $result = $process->SaveBeginningBalance($fund, $acctno, $accttitle, $balance, $fiscalYear, $username);
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TAB 2: SL BALANCE
    // ═══════════════════════════════════════════════════════════════════════
    case 'GetAccountCodes':
        $fund = $_POST['fund'] ?? '';
        $result = $process->GetAccountCodes($fund);
        echo json_encode($result);
        break;

    case 'GetSLBalances':
        $fund = $_POST['fund'] ?? '';
        $acctno = $_POST['acctno'] ?? '';
        $fiscalYear = $_POST['fiscalYear'] ?? null;
        $result = $process->GetSLBalances($fund, $acctno, $fiscalYear);
        echo json_encode($result);
        break;

    case 'SaveSLBalance':
        $fund = $_POST['fund'] ?? '';
        $acctno = $_POST['acctno'] ?? '';
        $slNo = $_POST['slNo'] ?? '';
        $slName = $_POST['slName'] ?? '';
        $balance = $_POST['balance'] ?? 0;
        $fiscalYear = $_POST['fiscalYear'] ?? date('Y');
        $result = $process->SaveSLBalance($fund, $acctno, $slNo, $slName, $balance, $fiscalYear, $username);
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TAB 3: YEAR END BALANCE
    // ═══════════════════════════════════════════════════════════════════════
    case 'GetYearEndBalances':
        $fund = $_POST['fund'] ?? '';
        $yearendDate = $_POST['yearendDate'] ?? '';
        $result = $process->GetYearEndBalances($fund, $yearendDate);
        echo json_encode($result);
        break;

    case 'SaveYearEndBalance':
        $fund = $_POST['fund'] ?? '';
        $acctno = $_POST['acctno'] ?? '';
        $accttitle = $_POST['accttitle'] ?? '';
        $balance = $_POST['balance'] ?? 0;
        $yearendDate = $_POST['yearendDate'] ?? '';
        $result = $process->SaveYearEndBalance($fund, $acctno, $accttitle, $balance, $yearendDate, $username);
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TAB 4: BUDGET VARIANCE
    // ═══════════════════════════════════════════════════════════════════════
    case 'GetBudgetData':
        $fund = $_POST['fund'] ?? '';
        $budgetMonth = $_POST['budgetMonth'] ?? '';
        $result = $process->GetBudgetData($fund, $budgetMonth);
        echo json_encode($result);
        break;

    case 'SaveBudgetData':
        $fund = $_POST['fund'] ?? '';
        $acctno = $_POST['acctno'] ?? '';
        $accttitle = $_POST['accttitle'] ?? '';
        $budgetAmount = $_POST['budgetAmount'] ?? 0;
        $budgetMonth = $_POST['budgetMonth'] ?? '';
        $result = $process->SaveBudgetData($fund, $acctno, $accttitle, $budgetAmount, $budgetMonth, $username);
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════════════════════
    // TAB 5: PESO DATA
    // ═══════════════════════════════════════════════════════════════════════
    case 'GetPESOData':
        $result = $process->GetPESOData();
        echo json_encode($result);
        break;

    case 'SavePESOData':
        $itemName = $_POST['itemName'] ?? '';
        $itemValue = $_POST['itemValue'] ?? 0;
        $result = $process->SavePESOData($itemName, $itemValue, $username);
        echo json_encode($result);
        break;

    default:
        echo json_encode([
            'STATUS' => 'ERROR',
            'MESSAGE' => 'Invalid action'
        ]);
        break;
}
?>
