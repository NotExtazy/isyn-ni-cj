<?php
// Suppress all output before JSON
ob_start();

// Suppress PHP warnings/notices
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

$basePath = dirname(__DIR__, 2);
include_once($basePath . "/process/cashier/loanspayment.process.php");
include_once($basePath . "/reports/cashier/loanspayment.reports.php");

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

$process = new Process();
$report = new Reports();

if(isset($_POST['action']) AND $_POST['action'] == 'Initialize'){
    $process->Initialize();
}

if(isset($_POST['action']) AND $_POST['action'] == 'BuildReportTable'){
    $process->BuildReportTable($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadTransactClientName'){
    $process->LoadTransactClientName($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadAccountDetails'){
    $process->LoadAccountDetails($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadORSeries'){
    $process->LoadORSeries($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadDepositoryBank'){
    $process->LoadDepositoryBank($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadClientType'){
    $process->LoadClientType();
}

if(isset($_POST['action']) AND $_POST['action'] == 'LoadClientName'){
    $process->LoadClientName($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'GetClientInfo'){
    $process->GetClientInfo($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SaveTransaction'){
    $process->SaveTransaction($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'UpdateLoanAmounts'){
    $process->UpdateLoanAmounts($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'SyncAgingData'){
    $process->SyncAgingData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'CheckMissingData'){
    $process->CheckMissingData($_POST);
}

if(isset($_POST['action']) AND $_POST['action'] == 'CheckLatePayment'){
    // Include the late payment check function
    include_once(dirname(__DIR__, 2) . '/check_late_payment_simple.php');
    
    $loanID = $_POST['loanID'] ?? '';
    $paymentDate = $_POST['paymentDate'] ?? date('Y-m-d');
    
    $result = checkIfPaymentIsLate($process->conn, $loanID, $paymentDate);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// if(isset($_POST['action']) AND $_POST['action'] == 'LoadCategory'){
//     $process->LoadCategory($_POST);
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'LoadSerialProduct'){
//     $process->LoadSerialProduct($_POST);
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'LoadProductSummary'){
//     $process->LoadProductSummary($_POST);
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomerName'){
//     $process->LoadCustomerName($_POST);
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'LoadCustomerNameInfo'){
//     $process->LoadCustomerNameInfo($_POST);
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'AddToItems'){
//     $process->AddToItems($_POST);
// }

// ======================================================================
// if(isset($_POST['action']) AND $_POST['action'] == 'LoadTransaction'){
//     $process->LoadTransaction();
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'DeleteFromItems'){
//     $process->DeleteFromItems($_POST);
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'SearchTransmittal'){
//     $process->SearchTransmittal($_POST);
// }

// if(isset($_POST['action']) AND $_POST['action'] == 'SubmitInvOut'){
//     $process->SubmitInvOut($_POST);
// }

// if(isset($_GET["type"]) && $_GET["type"] == 'PrintSalesInvoice'){
//     $report->PrintOutgoingSalesInvoice($_SESSION['tableData'],$_SESSION['SalesNoVAT'],$_SESSION['SalesWithVAT'], $_SESSION['SIRef'],$_SESSION['DateAdded']);
// }
// Receipt Printing Routes
if(isset($_POST['action']) AND $_POST['action'] == 'PrintReceipt'){
    $receiptData = json_decode($_POST['receiptData'], true);
    $report->PrintPaymentReceipt($receiptData);
}

if(isset($_POST['action']) AND $_POST['action'] == 'PrintPaymentReport'){
    $dateFrom = $_POST['dateFrom'];
    $dateTo = $_POST['dateTo'];
    $transactionType = $_POST['transactionType'] ?? 'ALL';
    $report->PrintPaymentReport($dateFrom, $dateTo, $transactionType);
}

// GET request for direct receipt printing (for URL access)
if(isset($_GET['action']) AND $_GET['action'] == 'PrintReceipt' AND isset($_GET['orno'])){
    // Fetch receipt data from database using OR number
    $orNo = $_GET['orno'];

    // Get payment data from database
    $stmt = $process->conn->prepare("SELECT * FROM tbl_loanspayment WHERE ORNo = ? ORDER BY TransactDate DESC LIMIT 1");
    $stmt->bind_param('s', $orNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $paymentRow = $result->fetch_assoc();

        // Get all payments for this OR number
        $stmt2 = $process->conn->prepare("SELECT * FROM tbl_loanspayment WHERE ORNo = ? ORDER BY TransactDate ASC");
        $stmt2->bind_param('s', $orNo);
        $stmt2->execute();
        $paymentsResult = $stmt2->get_result();

        $payments = array();
        $totalAmount = 0;

        while ($payment = $paymentsResult->fetch_assoc()) {
            $payments[] = array(
                "CLIENT_NAME" => $payment['ClientName'],
                "LOAN_ID" => $payment['LoanID'],
                "PRINCIPAL" => floatval($payment['Principal']),
                "INTEREST" => floatval($payment['Interest']),
                "PENALTY" => floatval($payment['Penalty']),
                "TOTAL" => floatval($payment['Total'])
            );
            $totalAmount += floatval($payment['Total']);
        }

        // Build receipt data
        $receiptData = array(
            "ORNO" => $paymentRow['ORNo'],
            "TRANSACTION_DATE" => $paymentRow['TransactDate'],
            "CLIENT_NAME" => $paymentRow['ClientName'],
            "CLIENT_ADDRESS" => $paymentRow['ClientAddress'],
            "CLIENT_TIN" => $paymentRow['ClientTIN'],
            "PAYMENT_TYPE" => $paymentRow['PaymentType'],
            "CHECK_DATE" => $paymentRow['CheckDate'],
            "CHECK_NO" => $paymentRow['CheckNo'],
            "BANK_NAME" => $paymentRow['BankName'],
            "BANK_BRANCH" => $paymentRow['BankBranch'],
            "PARTICULARS" => $paymentRow['Particulars'],
            "PAYMENTS" => $payments,
            "TOTAL_AMOUNT" => $totalAmount
        );

        $report->PrintPaymentReceipt($receiptData);
        $stmt2->close();
    } else {
        echo "Receipt not found for OR Number: " . htmlspecialchars($orNo);
    }
    $stmt->close();
}
