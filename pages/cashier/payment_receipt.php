<?php
// Acknowledgment Receipt Generator
// Size: 2.5" x 5.5" (thermal receipt size)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get receipt data from POST or session
$receiptData = null;
if (isset($_POST['receiptData'])) {
    $receiptData = json_decode($_POST['receiptData'], true);
} elseif (isset($_SESSION['receipt_data'])) {
    $receiptData = $_SESSION['receipt_data'];
    unset($_SESSION['receipt_data']); // Clear after use
} elseif (isset($_GET['test'])) {
    // Create sample receipt data for testing
    $receiptData = array(
        "ORNO" => "OR-" . date('Ymd') . "-001",
        "TRANSACTION_DATE" => date('Y-m-d H:i:s'),
        "CLIENT_NAME" => "JUAN DELA CRUZ",
        "CLIENT_ADDRESS" => "123 MAIN STREET, BARANGAY SAN JOSE, QUEZON CITY",
        "CLIENT_TIN" => "123-456-789-000",
        "PAYMENT_TYPE" => "CASH",
        "CHECK_DATE" => "",
        "CHECK_NO" => "",
        "BANK_NAME" => "",
        "BANK_BRANCH" => "",
        "PARTICULARS" => "LOAN PAYMENT",
        "PAYMENTS" => array(
            array(
                "CLIENT_NAME" => "JUAN DELA CRUZ",
                "LOAN_ID" => "LOAN-2024-001",
                "PRINCIPAL" => 5000.00,
                "INTEREST" => 500.00,
                "PENALTY" => 0.00,
                "TOTAL" => 5500.00
            )
        ),
        "TOTAL_AMOUNT" => 5500.00
    );
}

if (!$receiptData) {
    // Show error page with debug information
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Receipt Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .error { color: red; background: #ffe6e6; padding: 15px; border-radius: 5px; }
            .debug { background: #f0f0f0; padding: 10px; margin-top: 10px; font-family: monospace; }
            .test-link { display: inline-block; margin-top: 15px; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>No Receipt Data Available</h2>
            <p>The receipt could not be generated because no payment data was provided.</p>
        </div>
        
        <div class="debug">
            <strong>Debug Information:</strong><br>
            POST Data: <?= isset($_POST['receiptData']) ? 'Present' : 'Not Present' ?><br>
            Session Data: <?= isset($_SESSION['receipt_data']) ? 'Present' : 'Not Present' ?><br>
            Current Time: <?= date('Y-m-d H:i:s') ?><br>
        </div>
        
        <a href="?test=1" class="test-link">View Sample Receipt</a>
        
        <script>
            // Auto-close after 5 seconds if opened in popup
            if (window.opener) {
                setTimeout(function() {
                    window.close();
                }, 5000);
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - OR #<?= htmlspecialchars($receiptData['ORNO']) ?></title>
    <style>
        /* Receipt styling for 2.5" x 5.5" thermal paper */
        @page {
            size: 2.5in 5.5in;
            margin: 0.1in;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            line-height: 1.2;
            margin: 0;
            padding: 5px;
            width: 2.3in;
            background: white;
            color: black;
        }
        
        .receipt-container {
            width: 100%;
            max-width: 2.3in;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        
        .company-name {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 2px;
        }
        
        .receipt-title {
            font-weight: bold;
            font-size: 9pt;
            margin: 3px 0;
        }
        
        .or-number {
            font-weight: bold;
            font-size: 9pt;
            margin: 2px 0;
        }
        
        .info-section {
            margin: 5px 0;
            font-size: 7pt;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
        }
        
        .label {
            font-weight: bold;
        }
        
        .payments-section {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            margin: 5px 0;
        }
        
        .payment-item {
            margin: 3px 0;
            font-size: 7pt;
        }
        
        .payment-header {
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 2px;
        }
        
        .amount-row {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
        }
        
        .total-section {
            border-top: 1px solid #000;
            padding-top: 3px;
            margin-top: 5px;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 8px;
            font-size: 6pt;
            border-top: 1px dashed #000;
            padding-top: 5px;
        }
        
        .signature-line {
            margin-top: 10px;
            text-align: center;
            font-size: 7pt;
        }
        
        .line {
            border-bottom: 1px solid #000;
            width: 80%;
            margin: 5px auto 2px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Print Receipt</button>
    
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">ISYN MICROFINANCE</div>
            <div class="receipt-title">ACKNOWLEDGMENT RECEIPT</div>
            <div class="or-number">OR #<?= htmlspecialchars($receiptData['ORNO'] ?? 'N/A') ?></div>
        </div>
        
        <!-- Transaction Info -->
        <div class="info-section">
            <div class="info-row">
                <span class="label">Date:</span>
                <span><?= date('M d, Y g:i A', strtotime($receiptData['TRANSACTION_DATE'] ?? 'now')) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Client:</span>
                <span><?= htmlspecialchars($receiptData['CLIENT_NAME'] ?? 'N/A') ?></span>
            </div>
            <?php if (!empty($receiptData['CLIENT_ADDRESS'])): ?>
            <div class="info-row">
                <span class="label">Address:</span>
                <span><?= htmlspecialchars($receiptData['CLIENT_ADDRESS']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($receiptData['CLIENT_TIN']) && $receiptData['CLIENT_TIN'] !== '-'): ?>
            <div class="info-row">
                <span class="label">TIN:</span>
                <span><?= htmlspecialchars($receiptData['CLIENT_TIN']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">Payment:</span>
                <span><?= htmlspecialchars($receiptData['PAYMENT_TYPE'] ?? 'CASH') ?></span>
            </div>
            <?php if (($receiptData['PAYMENT_TYPE'] ?? '') === 'CHECK'): ?>
                <?php if (!empty($receiptData['CHECK_NO']) && $receiptData['CHECK_NO'] !== '-'): ?>
                <div class="info-row">
                    <span class="label">Check #:</span>
                    <span><?= htmlspecialchars($receiptData['CHECK_NO']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($receiptData['BANK_NAME']) && $receiptData['BANK_NAME'] !== '-'): ?>
                <div class="info-row">
                    <span class="label">Bank:</span>
                    <span><?= htmlspecialchars($receiptData['BANK_NAME']) ?></span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Payment Details -->
        <div class="payments-section">
            <div class="payment-header">PAYMENT DETAILS:</div>
            <?php 
            $payments = $receiptData['PAYMENTS'] ?? [];
            if (empty($payments)): 
            ?>
                <div class="payment-item">
                    <div style="text-align: center; color: #666;">No payment details available</div>
                </div>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                <div class="payment-item">
                    <div style="font-weight: bold; margin-bottom: 1px;">
                        Loan ID: <?= htmlspecialchars($payment['LOAN_ID'] ?? 'N/A') ?>
                    </div>
                    <?php if (($payment['PRINCIPAL'] ?? 0) > 0): ?>
                    <div class="amount-row">
                        <span>Principal:</span>
                        <span>₱<?= number_format($payment['PRINCIPAL'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($payment['INTEREST'] ?? 0) > 0): ?>
                    <div class="amount-row">
                        <span>Interest:</span>
                        <span>₱<?= number_format($payment['INTEREST'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($payment['PENALTY'] ?? 0) > 0): ?>
                    <div class="amount-row">
                        <span>Penalty:</span>
                        <span>₱<?= number_format($payment['PENALTY'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="amount-row" style="font-weight: bold; border-top: 1px dotted #000; margin-top: 2px; padding-top: 1px;">
                        <span>Subtotal:</span>
                        <span>₱<?= number_format($payment['TOTAL'] ?? 0, 2) ?></span>
                    </div>
                </div>
                <?php if (count($payments) > 1 && $payment !== end($payments)): ?>
                    <div style="border-bottom: 1px dotted #ccc; margin: 3px 0;"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Total -->
        <div class="total-section">
            <div class="amount-row" style="font-size: 9pt;">
                <span>TOTAL AMOUNT:</span>
                <span>₱<?= number_format($receiptData['TOTAL_AMOUNT'] ?? 0, 2) ?></span>
            </div>
        </div>
        
        <!-- Particulars -->
        <?php if (!empty($receiptData['PARTICULARS'])): ?>
        <div class="info-section" style="margin-top: 8px;">
            <div class="label">Particulars:</div>
            <div style="font-size: 7pt; margin-top: 2px;">
                <?= htmlspecialchars($receiptData['PARTICULARS']) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Signature -->
        <div class="signature-line">
            <div class="line"></div>
            <div>Cashier Signature</div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>Thank you for your payment!</div>
            <div>Keep this receipt for your records</div>
            <div style="margin-top: 3px; font-size: 5pt;">
                System Generated: <?= date('Y-m-d H:i:s') ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // };
        
        // Close window after printing
        window.onafterprint = function() {
            // Uncomment if you want to auto-close after printing
            // window.close();
        };
    </script>
</body>
</html>