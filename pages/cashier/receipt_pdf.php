<?php
// PDF Receipt Generator for 2.5" x 5.5" POS Paper
require_once('../../vendor/autoload.php'); // Assuming you have TCPDF or similar

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get receipt data
$receiptData = null;
if (isset($_POST['receiptData'])) {
    $receiptData = json_decode($_POST['receiptData'], true);
} elseif (isset($_SESSION['receipt_data'])) {
    $receiptData = $_SESSION['receipt_data'];
} elseif (isset($_GET['test'])) {
    // Sample data for testing
    $receiptData = array(
        "ORNO" => "OR-" . date('Ymd') . "-001",
        "TRANSACTION_DATE" => date('Y-m-d H:i:s'),
        "CLIENT_NAME" => "MARIA SANTOS",
        "CLIENT_ADDRESS" => "456 RIZAL STREET, BARANGAY POBLACION, MANILA CITY",
        "CLIENT_TIN" => "987-654-321-000",
        "PAYMENT_TYPE" => "CASH",
        "PARTICULARS" => "LOAN PAYMENT - REGULAR INSTALLMENT",
        "PAYMENTS" => array(
            array(
                "CLIENT_NAME" => "MARIA SANTOS",
                "LOAN_ID" => "LOAN-2024-001",
                "PRINCIPAL" => 3000.00,
                "INTEREST" => 300.00,
                "PENALTY" => 50.00,
                "TOTAL" => 3350.00
            )
        ),
        "TOTAL_AMOUNT" => 3350.00
    );
}

if (!$receiptData) {
    die('No receipt data available for PDF generation');
}

// Simple HTML to PDF conversion (works without external libraries)
// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="receipt-' . $receiptData['ORNO'] . '.pdf"');

// Create HTML content optimized for PDF conversion
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - OR #' . htmlspecialchars($receiptData['ORNO']) . '</title>
    <style>
        @page {
            size: 2.5in 5.5in;
            margin: 0.1in;
        }
        
        body {
            font-family: "Courier New", monospace;
            font-size: 8pt;
            line-height: 1.2;
            margin: 0;
            padding: 2px;
            width: 2.3in;
            color: black;
        }
        
        .receipt-container {
            width: 100%;
            max-width: 2.3in;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 1px;
        }
        
        .receipt-title {
            font-weight: bold;
            font-size: 9pt;
            margin: 2px 0;
        }
        
        .or-number {
            font-weight: bold;
            font-size: 9pt;
            margin: 1px 0;
        }
        
        .info-section {
            margin: 3px 0;
            font-size: 7pt;
        }
        
        .info-row {
            margin: 1px 0;
        }
        
        .label {
            font-weight: bold;
            display: inline-block;
            width: 50px;
        }
        
        .payments-section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 3px 0;
            margin: 3px 0;
        }
        
        .payment-item {
            margin: 2px 0;
            font-size: 7pt;
        }
        
        .payment-header {
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 2px;
        }
        
        .amount-row {
            margin: 1px 0;
        }
        
        .amount-row .label {
            width: 60px;
        }
        
        .amount-row .amount {
            float: right;
        }
        
        .total-section {
            border-top: 1px solid #000;
            padding-top: 2px;
            margin-top: 3px;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 5px;
            font-size: 6pt;
            border-top: 1px dashed #000;
            padding-top: 3px;
        }
        
        .signature-line {
            margin-top: 8px;
            text-align: center;
            font-size: 7pt;
        }
        
        .line {
            border-bottom: 1px solid #000;
            width: 80%;
            margin: 3px auto 1px;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div style="text-align: center; margin-bottom: 3px;">
                <img src="../../assets/images/complete-logo.png" alt="iSynergies Logo" style="max-width: 120px; height: auto; max-height: 30px;">
            </div>
            <div class="company-name">ISYN MICROFINANCE</div>
            <div class="receipt-title">ACKNOWLEDGMENT RECEIPT</div>
            <div class="or-number">OR #' . htmlspecialchars($receiptData['ORNO']) . '</div>
        </div>
        
        <!-- Transaction Info -->
        <div class="info-section">
            <div class="info-row">
                <span class="label">Date:</span>
                <span>' . date('M d, Y g:i A', strtotime($receiptData['TRANSACTION_DATE'])) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Client:</span>
                <span>' . htmlspecialchars($receiptData['CLIENT_NAME']) . '</span>
            </div>';

if (!empty($receiptData['CLIENT_ADDRESS'])) {
    $html .= '<div class="info-row">
                <span class="label">Address:</span>
                <span>' . htmlspecialchars($receiptData['CLIENT_ADDRESS']) . '</span>
            </div>';
}

if (!empty($receiptData['CLIENT_TIN']) && $receiptData['CLIENT_TIN'] !== '-') {
    $html .= '<div class="info-row">
                <span class="label">TIN:</span>
                <span>' . htmlspecialchars($receiptData['CLIENT_TIN']) . '</span>
            </div>';
}

$html .= '<div class="info-row">
                <span class="label">Payment:</span>
                <span>' . htmlspecialchars($receiptData['PAYMENT_TYPE']) . '</span>
            </div>';

if ($receiptData['PAYMENT_TYPE'] === 'CHECK') {
    if (!empty($receiptData['CHECK_NO']) && $receiptData['CHECK_NO'] !== '-') {
        $html .= '<div class="info-row">
                    <span class="label">Check #:</span>
                    <span>' . htmlspecialchars($receiptData['CHECK_NO']) . '</span>
                </div>';
    }
    if (!empty($receiptData['BANK_NAME']) && $receiptData['BANK_NAME'] !== '-') {
        $html .= '<div class="info-row">
                    <span class="label">Bank:</span>
                    <span>' . htmlspecialchars($receiptData['BANK_NAME']) . '</span>
                </div>';
    }
}

$html .= '</div>
        
        <!-- Payment Details -->
        <div class="payments-section">
            <div class="payment-header">PAYMENT DETAILS:</div>';

foreach ($receiptData['PAYMENTS'] as $payment) {
    $html .= '<div class="payment-item">
                <div style="font-weight: bold; margin-bottom: 1px;">
                    Loan ID: ' . htmlspecialchars($payment['LOAN_ID']) . '
                </div>';
    
    if ($payment['PRINCIPAL'] > 0) {
        $html .= '<div class="amount-row clearfix">
                    <span class="label">Principal:</span>
                    <span class="amount">₱' . number_format($payment['PRINCIPAL'], 2) . '</span>
                </div>';
    }
    
    if ($payment['INTEREST'] > 0) {
        $html .= '<div class="amount-row clearfix">
                    <span class="label">Interest:</span>
                    <span class="amount">₱' . number_format($payment['INTEREST'], 2) . '</span>
                </div>';
    }
    
    if ($payment['PENALTY'] > 0) {
        $html .= '<div class="amount-row clearfix">
                    <span class="label">Penalty:</span>
                    <span class="amount">₱' . number_format($payment['PENALTY'], 2) . '</span>
                </div>';
    }
    
    $html .= '<div class="amount-row clearfix" style="font-weight: bold; border-top: 1px dotted #000; margin-top: 2px; padding-top: 1px;">
                <span class="label">Subtotal:</span>
                <span class="amount">₱' . number_format($payment['TOTAL'], 2) . '</span>
            </div>
        </div>';
    
    if (count($receiptData['PAYMENTS']) > 1 && $payment !== end($receiptData['PAYMENTS'])) {
        $html .= '<div style="border-bottom: 1px dotted #ccc; margin: 2px 0;"></div>';
    }
}

$html .= '</div>
        
        <!-- Total -->
        <div class="total-section">
            <div class="amount-row clearfix" style="font-size: 9pt;">
                <span class="label">TOTAL:</span>
                <span class="amount">₱' . number_format($receiptData['TOTAL_AMOUNT'], 2) . '</span>
            </div>
        </div>';

if (!empty($receiptData['PARTICULARS'])) {
    $html .= '<div class="info-section" style="margin-top: 5px;">
                <div class="label">Particulars:</div>
                <div style="font-size: 7pt; margin-top: 1px;">
                    ' . htmlspecialchars($receiptData['PARTICULARS']) . '
                </div>
            </div>';
}

$html .= '<!-- Signature -->
        <div class="signature-line">
            <div class="line"></div>
            <div>Cashier Signature</div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>Thank you for your payment!</div>
            <div>Keep this receipt for your records</div>
            <div style="margin-top: 2px; font-size: 5pt;">
                Generated: ' . date('Y-m-d H:i:s') . '
            </div>
        </div>
    </div>
</body>
</html>';

// For simple PDF generation without external libraries
// This creates an HTML file that can be printed to PDF
echo $html;
?>