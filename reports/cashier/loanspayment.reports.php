<?php
$basePath = dirname(__DIR__, 2);
include_once($basePath . "/database/connection.php");
require_once($basePath . '/assets/tcpdf/tcpdf.php');

class Reports extends Database 
{
    public function PrintOutgoingSalesInvoice($tableData,$SalesNoVAT,$SalesWithVAT,$SIRef,$DateAdded){
        ob_clean();
		ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);
        
		$pdf = new TCPDF('P', PDF_UNIT, 'A5', true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('SUPPLIER\'S RECEIPT');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(4, 8, 4);
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
		$pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        $contentdata = '';

        $datesold = "-";
        $soldto = "-";
        $tin = "-";
        $address = "-";
        $totalAmountDue = 0;

        // $stmt = $this->conn->prepare("SELECT * FROM tbl_salesjournal WHERE STR_TO_DATE(DateSold,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y') AND Reference = ?");
        $stmt = $this->conn->prepare("SELECT * FROM tbl_salesjournal");
        // $stmt->bind_param("ss",$dateSold,$refNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $datesold = $row["DateSold"];
            $soldto = $row["Customer"];
            $tin = $row["TIN"];
            $address = $row["Address"];
        }

        foreach ($tableData as $row){            
            $contentdata .= '
                    <tr style="font-size:6pt;">
                        <td style="text-align:center;border: 1px solid black;">'.$row[4].'</td>
                        <td style="text-align:center;border: 1px solid black;">'.$row[5].'</td>
                        <td style="border: 1px solid black;">'.$row[6].'</td>
                        <td style="text-align:right;border: 1px solid black;">'.number_format(str_replace(",","",$row[7]),2).'</td>
                        <td style="text-align:right;border: 1px solid black;">'.number_format(str_replace(",","",$row[8]),2).'</td>
                    </tr>
            ';

            $totalAmountDue = floatval($totalAmountDue) + floatval(str_replace(",","",$row[8]));
        }
        
        $content = '';

        $content .= '<table border="1">
                        <tr>
                            <td width="100%" style="line-height:100px;"></td>
                        </tr>
                        <tr>
                            <td width="70%" style="font-size:7px;"><table border="1">
                                    <tr>
                                        <td width="10%" style="font-weight:bold;">SOLD to </td>
                                        <td width="90%">'.$soldto.'</td>
                                    </tr>
                                </table>
                            </td>
                            <td width="30%" style="font-size:7px;"><table border="1">
                                    <tr>
                                        <td width="15%" style="font-weight:bold;">Date </td>
                                        <td width="85%">'.$datesold.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="100%" style="font-size:7px;"><table border="1">
                                    <tr>
                                        <td width="7%" style="font-weight:bold;">TIN </td>
                                        <td width="93%">'.$tin.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="100%" style="font-size:7px;"><table border="1">
                                    <tr>
                                        <td width="7%" style="font-weight:bold;">Address </td>
                                        <td width="93%">'.$address.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr><td width="100%"></td></tr>
                        <tr><td width="100%"></td></tr>
                        <tr style="font-weight:bold;font-size:6pt;text-align:center;">
                            <td width="10%" style="border: 1px solid black;">Qty</td>
                            <td width="5%" style="border: 1px solid black;">Unit.</td>
                            <td width="65%" style="border: 1px solid black;">Articles</td>
                            <td width="10%" style="border: 1px solid black;">Unit Price</td>
                            <td width="10%" style="border: 1px solid black;">Amount</td>
                        </tr>
                        '.$contentdata.'
                        <tr style="font-size:6pt;">
                            <td width="15%"></td>
                            <td width="10%">VATable Sales</td>
                            <td width="75%">'.$SalesNoVAT.'</td>
                        </tr>
                        <tr style="font-size:6pt;">
                        <td width="15%"></td>
                        <td width="10%"></td>
                        <td width="75%"></td>
                        </tr>
                        <tr style="font-size:6pt;">
                        <td width="15%"></td>
                        <td width="10%"></td>
                        <td width="75%"></td>
                        </tr>
                        <tr style="font-size:6pt;">
                            <td width="15%"></td>
                            <td width="10%">VAT Amount</td>
                            <td width="75%">'.$SalesWithVAT.'</td>
                        </tr>
                        <tr style="font-size:6pt;text-align:right;">
                            <td width="90%"></td>
                            <td width="10%">'.number_format($totalAmountDue,2).'</td>
                        </tr>
                    </table>
        ';

        // logs($_SESSION['usertype'], "Printed JV Report", "btnJVPrint", $_SESSION['username'], "JV Reports");        
        
        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
        $pdf->IncludeJS("print();");
		$pdf->Output('supplierreceipt.pdf', 'I');
    }
    // ==========================================
    //  PRINT PAYMENT RECEIPT (2.5" x 5.5" POS Paper)
    // ==========================================
    public function PrintPaymentReceipt($receiptData){
        ob_clean();
        ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);

        // Create PDF with custom page size for 2.5" x 5.5" POS paper
        // 2.5 inches = 180 points, 5.5 inches = 396 points
        $pdf = new TCPDF('P', 'pt', array(180, 396), true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('ISYN MICROFINANCE');
        $pdf->SetTitle('Payment Receipt - OR #' . $receiptData['ORNO']);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetDefaultMonospacedFont('courier');
        $pdf->SetMargins(5, 5, 5);
        $pdf->SetAutoPageBreak(TRUE, 5);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('courier', '', 7);
        $pdf->AddPage();

        // Add logo if it exists
        $logoPath = dirname(__FILE__) . '/../../assets/images/complete-logo.png';
        if (file_exists($logoPath)) {
            // Add logo at the top center
            $pdf->Image($logoPath, 30, 10, 120, 0, 'PNG', '', 'T', false, 300, 'C', false, false, 0, false, false, false);
            $pdf->Ln(20); // Add space after logo
        }

        // Build receipt content
        $content = '<table border="0" cellpadding="1" cellspacing="0" style="font-family: courier; font-size: 7pt;">';

        // Header
        $content .= '<tr><td style="text-align: center; font-weight: bold; font-size: 9pt;">ISYN MICROFINANCE</td></tr>';
        $content .= '<tr><td style="text-align: center; font-weight: bold; font-size: 8pt;">ACKNOWLEDGMENT RECEIPT</td></tr>';
        $content .= '<tr><td style="text-align: center; font-weight: bold; font-size: 8pt;">OR #' . htmlspecialchars($receiptData['ORNO']) . '</td></tr>';
        $content .= '<tr><td style="border-bottom: 1px dashed #000; padding-bottom: 2pt;"></td></tr>';

        // Transaction Info
        $content .= '<tr><td style="font-size: 6pt; padding-top: 3pt;"><strong>Date:</strong> ' . date('M d, Y g:i A', strtotime($receiptData['TRANSACTION_DATE'])) . '</td></tr>';
        $content .= '<tr><td style="font-size: 6pt;"><strong>Client:</strong> ' . htmlspecialchars($receiptData['CLIENT_NAME']) . '</td></tr>';

        if (!empty($receiptData['CLIENT_ADDRESS'])) {
            $content .= '<tr><td style="font-size: 6pt;"><strong>Address:</strong> ' . htmlspecialchars($receiptData['CLIENT_ADDRESS']) . '</td></tr>';
        }

        if (!empty($receiptData['CLIENT_TIN']) && $receiptData['CLIENT_TIN'] !== '-') {
            $content .= '<tr><td style="font-size: 6pt;"><strong>TIN:</strong> ' . htmlspecialchars($receiptData['CLIENT_TIN']) . '</td></tr>';
        }

        $content .= '<tr><td style="font-size: 6pt;"><strong>Payment:</strong> ' . htmlspecialchars($receiptData['PAYMENT_TYPE']) . '</td></tr>';

        // Check details for CHECK payments
        if ($receiptData['PAYMENT_TYPE'] === 'CHECK') {
            if (!empty($receiptData['CHECK_NO']) && $receiptData['CHECK_NO'] !== '-') {
                $content .= '<tr><td style="font-size: 6pt;"><strong>Check #:</strong> ' . htmlspecialchars($receiptData['CHECK_NO']) . '</td></tr>';
            }
            if (!empty($receiptData['BANK_NAME']) && $receiptData['BANK_NAME'] !== '-') {
                $content .= '<tr><td style="font-size: 6pt;"><strong>Bank:</strong> ' . htmlspecialchars($receiptData['BANK_NAME']) . '</td></tr>';
            }
        }

        // Payment Details Section
        $content .= '<tr><td style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 3pt 0; font-weight: bold; font-size: 7pt; text-align: center;">PAYMENT DETAILS</td></tr>';

        foreach ($receiptData['PAYMENTS'] as $payment) {
            $content .= '<tr><td style="font-size: 6pt; font-weight: bold; padding-top: 2pt;">Loan ID: ' . htmlspecialchars($payment['LOAN_ID']) . '</td></tr>';

            if ($payment['PRINCIPAL'] > 0) {
                $content .= '<tr><td style="font-size: 6pt;">Principal: ₱' . number_format($payment['PRINCIPAL'], 2) . '</td></tr>';
            }

            if ($payment['INTEREST'] > 0) {
                $content .= '<tr><td style="font-size: 6pt;">Interest: ₱' . number_format($payment['INTEREST'], 2) . '</td></tr>';
            }

            if ($payment['PENALTY'] > 0) {
                $content .= '<tr><td style="font-size: 6pt;">Penalty: ₱' . number_format($payment['PENALTY'], 2) . '</td></tr>';
            }

            $content .= '<tr><td style="font-size: 6pt; font-weight: bold; border-top: 1px dotted #000; padding-top: 1pt;">Subtotal: ₱' . number_format($payment['TOTAL'], 2) . '</td></tr>';

            // Add separator if multiple payments
            if (count($receiptData['PAYMENTS']) > 1 && $payment !== end($receiptData['PAYMENTS'])) {
                $content .= '<tr><td style="border-bottom: 1px dotted #ccc; padding: 1pt;"></td></tr>';
            }
        }

        // Total Amount
        $content .= '<tr><td style="border-top: 2px solid #000; padding-top: 2pt; font-weight: bold; font-size: 8pt; text-align: center;">TOTAL: ₱' . number_format($receiptData['TOTAL_AMOUNT'], 2) . '</td></tr>';

        // Particulars
        if (!empty($receiptData['PARTICULARS'])) {
            $content .= '<tr><td style="font-size: 6pt; padding-top: 3pt;"><strong>Particulars:</strong></td></tr>';
            $content .= '<tr><td style="font-size: 6pt;">' . htmlspecialchars($receiptData['PARTICULARS']) . '</td></tr>';
        }

        // Signature Line
        $content .= '<tr><td style="padding-top: 8pt; text-align: center; font-size: 6pt;">_________________</td></tr>';
        $content .= '<tr><td style="text-align: center; font-size: 6pt;">Cashier Signature</td></tr>';

        // Footer
        $content .= '<tr><td style="border-top: 1px dashed #000; padding-top: 3pt; text-align: center; font-size: 5pt;">Thank you for your payment!</td></tr>';
        $content .= '<tr><td style="text-align: center; font-size: 5pt;">Keep this receipt for your records</td></tr>';
        $content .= '<tr><td style="text-align: center; font-size: 4pt;">Generated: ' . date('Y-m-d H:i:s') . '</td></tr>';

        $content .= '</table>';

        // Write content and output
        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();

        // Auto-print JavaScript (optional)
        $pdf->IncludeJS("print();");

        $pdf->Output('payment_receipt_' . $receiptData['ORNO'] . '.pdf', 'I');
    }

    // ==========================================
    //  PRINT PAYMENT RECEIPT LIST/REPORT
    // ==========================================
    public function PrintPaymentReport($dateFrom, $dateTo, $transactionType = 'ALL'){
        ob_clean();
        ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);

        // Create PDF for report
        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('ISYN MICROFINANCE');
        $pdf->SetTitle('Payment Report');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetDefaultMonospacedFont('helvetica');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // Fetch payment data
        $sql = "SELECT p.*, DATE(p.TransactDate) as PaymentDate
                FROM tbl_loanspayment p
                WHERE DATE(p.TransactDate) BETWEEN ? AND ?";

        if ($transactionType !== 'ALL') {
            $sql .= " AND p.PaymentType = ?";
        }

        $sql .= " ORDER BY p.TransactDate DESC";

        $stmt = $this->conn->prepare($sql);
        if ($transactionType !== 'ALL') {
            $stmt->bind_param('sss', $dateFrom, $dateTo, $transactionType);
        } else {
            $stmt->bind_param('ss', $dateFrom, $dateTo);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Build report content
        $html = '<h2 style="text-align:center;">Payment Report</h2>';
        $html .= '<p style="text-align:center;">Period: ' . date('M d, Y', strtotime($dateFrom)) . ' to ' . date('M d, Y', strtotime($dateTo)) . '</p>';
        $html .= '<p style="text-align:center;">Transaction Type: ' . htmlspecialchars($transactionType) . '</p>';
        $html .= '<hr>';

        $html .= '<table border="1" cellspacing="0" cellpadding="4" style="font-size:9px;">';
        $html .= '<thead>
                    <tr style="background-color:#f2f2f2; font-weight:bold;">
                        <th width="8%">Date</th>
                        <th width="10%">OR No</th>
                        <th width="15%">Client Name</th>
                        <th width="10%">Loan ID</th>
                        <th width="8%">Principal</th>
                        <th width="8%">Interest</th>
                        <th width="8%">Penalty</th>
                        <th width="8%">Total</th>
                        <th width="10%">Payment Type</th>
                        <th width="15%">Particulars</th>
                    </tr>
                  </thead>';

        $html .= '<tbody>';

        $totalAmount = 0;
        $recordCount = 0;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $html .= '<tr>
                            <td>' . date('M d, Y', strtotime($row['TransactDate'])) . '</td>
                            <td>' . htmlspecialchars($row['ORNo']) . '</td>
                            <td>' . htmlspecialchars($row['ClientName']) . '</td>
                            <td>' . htmlspecialchars($row['LoanID']) . '</td>
                            <td style="text-align:right;">₱' . number_format($row['Principal'], 2) . '</td>
                            <td style="text-align:right;">₱' . number_format($row['Interest'], 2) . '</td>
                            <td style="text-align:right;">₱' . number_format($row['Penalty'], 2) . '</td>
                            <td style="text-align:right;">₱' . number_format($row['Total'], 2) . '</td>
                            <td>' . htmlspecialchars($row['PaymentType']) . '</td>
                            <td>' . htmlspecialchars($row['Particulars']) . '</td>
                          </tr>';

                $totalAmount += $row['Total'];
                $recordCount++;
            }
        } else {
            $html .= '<tr><td colspan="10" style="text-align:center;">No payments found for the selected period.</td></tr>';
        }

        // Summary row
        if ($recordCount > 0) {
            $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;">
                        <td colspan="7" style="text-align:right;">TOTAL (' . $recordCount . ' payments):</td>
                        <td style="text-align:right;">₱' . number_format($totalAmount, 2) . '</td>
                        <td colspan="2"></td>
                      </tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Payment_Report_' . date('Ymd') . '.pdf', 'I');

        $stmt->close();
    }

    // Function to determine the best font size
    private function adjustFontSizeToFitWidth($text, $maxWidth, $initialFontSize, $minFontSize, $characterWidthFactor) {
        $fontSize = $initialFontSize;

        while ($this->calculateTextWidth($text, $fontSize, $characterWidthFactor) > $maxWidth && $fontSize > $minFontSize) {
            $fontSize -= 0.5; // Decrease font size incrementally
        }
        return $fontSize;
    }

    // Function to estimate text width based on character count and font size
    private function calculateTextWidth($text, $fontSize, $characterWidthFactor) {
        return strlen($text) * $fontSize * $characterWidthFactor;
    }

}
?>