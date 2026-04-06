<?php
// Print-Ready Receipt for 2.5" x 5.5" POS Paper
// Optimized for thermal printers

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
    die('No receipt data available');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt - OR #<?= htmlspecialchars($receiptData['ORNO']) ?></title>
    <style>
        /* POS Paper 2.5" x 5.5" Print Styles */
        @page {
            size: 2.5in 5.5in;
            margin: 0.05in;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', 'Consolas', monospace;
            font-size: 7pt;
            line-height: 1.1;
            color: #000;
            background: white;
            width: 2.4in;
            padding: 2px;
        }
        
        .receipt {
            width: 100%;
            max-width: 2.4in;
        }
        
        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 4px;
            border-bottom: 1px dashed #000;
            padding-bottom: 2px;
        }
        
        .logo-container {
            margin-bottom: 3px;
        }
        
        .logo-container img {
            max-width: 120px;
            height: auto;
            max-height: 30px;
            display: block;
            margin: 0 auto;
        }
        
        .company-name {
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        
        .receipt-title {
            font-size: 8pt;
            font-weight: bold;
            margin: 1px 0;
        }
        
        .or-number {
            font-size: 8pt;
            font-weight: bold;
            margin: 1px 0;
        }
        
        /* Info Section */
        .info-section {
            margin: 3px 0;
            font-size: 6pt;
        }
        
        .info-line {
            margin: 0.5px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 40px;
        }
        
        .info-value {
            flex: 1;
            text-align: left;
            margin-left: 5px;
        }
        
        /* Payment Details */
        .payments {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 2px 0;
            margin: 3px 0;
        }
        
        .payments-header {
            font-weight: bold;
            font-size: 7pt;
            text-align: center;
            margin-bottom: 2px;
        }
        
        .payment-item {
            margin: 2px 0;
        }
        
        .loan-id {
            font-weight: bold;
            font-size: 6pt;
            margin-bottom: 1px;
        }
        
        .amount-line {
            display: flex;
            justify-content: space-between;
            font-size: 6pt;
            margin: 0.5px 0;
        }
        
        .amount-label {
            min-width: 50px;
        }
        
        .amount-value {
            font-weight: normal;
        }
        
        .subtotal {
            border-top: 1px dotted #000;
            margin-top: 1px;
            padding-top: 1px;
            font-weight: bold;
        }
        
        .payment-separator {
            border-bottom: 1px dotted #ccc;
            margin: 2px 0;
        }
        
        /* Total Section */
        .total-section {
            border-top: 2px solid #000;
            padding-top: 2px;
            margin-top: 3px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 8pt;
        }
        
        /* Particulars */
        .particulars {
            margin: 3px 0;
            font-size: 6pt;
        }
        
        .particulars-label {
            font-weight: bold;
            margin-bottom: 1px;
        }
        
        /* Signature */
        .signature {
            margin-top: 6px;
            text-align: center;
            font-size: 6pt;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            width: 70%;
            margin: 3px auto 1px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 4px;
            font-size: 5pt;
            border-top: 1px dashed #000;
            padding-top: 2px;
        }
        
        .footer-line {
            margin: 1px 0;
        }
        
        /* Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            .receipt {
                page-break-inside: avoid;
            }
        }
        
        /* Print Button */
        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            margin: 2px;
            font-size: 12px;
        }
        
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button class="print-btn" onclick="printReceipt()">🖨️ Print Receipt</button>
        <button class="print-btn" onclick="generatePDF()">📄 Save as PDF</button>
        <button class="print-btn" onclick="window.close()">❌ Close</button>
    </div>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="logo-container" style="text-align: center; margin-bottom: 3px;">
                <img src="../../assets/images/complete-logo.png" alt="iSynergies Logo" style="max-width: 120px; height: auto; max-height: 30px;">
            </div>
            <div class="company-name">ISYN MICROFINANCE</div>
            <div class="receipt-title">ACKNOWLEDGMENT RECEIPT</div>
            <div class="or-number">OR #<?= htmlspecialchars($receiptData['ORNO']) ?></div>
        </div>
        
        <!-- Transaction Info -->
        <div class="info-section">
            <div class="info-line">
                <span class="info-label">Date:</span>
                <span class="info-value"><?= date('M d, Y g:i A', strtotime($receiptData['TRANSACTION_DATE'])) ?></span>
            </div>
            <div class="info-line">
                <span class="info-label">Client:</span>
                <span class="info-value"><?= htmlspecialchars($receiptData['CLIENT_NAME']) ?></span>
            </div>
            <?php if (!empty($receiptData['CLIENT_ADDRESS'])): ?>
            <div class="info-line">
                <span class="info-label">Address:</span>
                <span class="info-value"><?= htmlspecialchars($receiptData['CLIENT_ADDRESS']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($receiptData['CLIENT_TIN']) && $receiptData['CLIENT_TIN'] !== '-'): ?>
            <div class="info-line">
                <span class="info-label">TIN:</span>
                <span class="info-value"><?= htmlspecialchars($receiptData['CLIENT_TIN']) ?></span>
            </div>
            <?php endif; ?>
            <div class="info-line">
                <span class="info-label">Payment:</span>
                <span class="info-value"><?= htmlspecialchars($receiptData['PAYMENT_TYPE']) ?></span>
            </div>
            <?php if ($receiptData['PAYMENT_TYPE'] === 'CHECK'): ?>
                <?php if (!empty($receiptData['CHECK_NO']) && $receiptData['CHECK_NO'] !== '-'): ?>
                <div class="info-line">
                    <span class="info-label">Check #:</span>
                    <span class="info-value"><?= htmlspecialchars($receiptData['CHECK_NO']) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($receiptData['BANK_NAME']) && $receiptData['BANK_NAME'] !== '-'): ?>
                <div class="info-line">
                    <span class="info-label">Bank:</span>
                    <span class="info-value"><?= htmlspecialchars($receiptData['BANK_NAME']) ?></span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Payment Details -->
        <div class="payments">
            <div class="payments-header">PAYMENT DETAILS</div>
            <?php foreach ($receiptData['PAYMENTS'] as $index => $payment): ?>
            <div class="payment-item">
                <div class="loan-id">Loan ID: <?= htmlspecialchars($payment['LOAN_ID']) ?></div>
                
                <?php if ($payment['PRINCIPAL'] > 0): ?>
                <div class="amount-line">
                    <span class="amount-label">Principal:</span>
                    <span class="amount-value">₱<?= number_format($payment['PRINCIPAL'], 2) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($payment['INTEREST'] > 0): ?>
                <div class="amount-line">
                    <span class="amount-label">Interest:</span>
                    <span class="amount-value">₱<?= number_format($payment['INTEREST'], 2) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($payment['PENALTY'] > 0): ?>
                <div class="amount-line">
                    <span class="amount-label">Penalty:</span>
                    <span class="amount-value">₱<?= number_format($payment['PENALTY'], 2) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="amount-line subtotal">
                    <span class="amount-label">Subtotal:</span>
                    <span class="amount-value">₱<?= number_format($payment['TOTAL'], 2) ?></span>
                </div>
            </div>
            
            <?php if ($index < count($receiptData['PAYMENTS']) - 1): ?>
            <div class="payment-separator"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Total -->
        <div class="total-section">
            <div class="total-line">
                <span>TOTAL AMOUNT:</span>
                <span>₱<?= number_format($receiptData['TOTAL_AMOUNT'], 2) ?></span>
            </div>
        </div>
        
        <!-- Particulars -->
        <?php if (!empty($receiptData['PARTICULARS'])): ?>
        <div class="particulars">
            <div class="particulars-label">Particulars:</div>
            <div><?= htmlspecialchars($receiptData['PARTICULARS']) ?></div>
        </div>
        <?php endif; ?>
        
        <!-- Signature -->
        <div class="signature">
            <div class="signature-line"></div>
            <div>Cashier Signature</div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-line">Thank you for your payment!</div>
            <div class="footer-line">Keep this receipt for your records</div>
            <div class="footer-line">Generated: <?= date('Y-m-d H:i:s') ?></div>
        </div>
    </div>
    
    <script>
        // Auto-print when page loads (optional)
        window.onload = function() {
            // Uncomment to auto-print
            // setTimeout(function() { window.print(); }, 500);
        };
        
        // Enhanced print function
        function printReceipt() {
            // Hide print controls before printing
            var controls = document.querySelector('.print-controls');
            if (controls) controls.style.display = 'none';
            
            // Print
            window.print();
            
            // Show controls again after print dialog
            setTimeout(function() {
                if (controls) controls.style.display = 'block';
            }, 1000);
        }
        function generatePDF() {
            // Open PDF version in new window
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'receipt_pdf.php';
            form.target = '_blank';
            
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'receiptData';
            input.value = '<?= addslashes(json_encode($receiptData)) ?>';
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
        
        // Close window after printing
        window.onafterprint = function() {
            // Uncomment to auto-close after printing
            // window.close();
        };
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>