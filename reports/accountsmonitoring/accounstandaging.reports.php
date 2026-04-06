<?php
$tcpdfPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/assets/tcpdf/tcpdf.php';
$dbPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/database/connection.php';
require_once($tcpdfPath);
include_once($dbPath);

class Reports extends Database
{
    private function getBranchSetup() {
        $data = ['orgname' => '', 'orgaddress' => '', 'orgtelno' => ''];
        $stmt = $this->conn->prepare("SELECT ConfigName, Value FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME','BRANCHADDRESS','BRANCHTELNO')");
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        while ($row = $res->fetch_assoc()) {
            switch ($row['ConfigName']) {
                case 'ORGNAME':       $data['orgname']    = $row['Value']; break;
                case 'BRANCHADDRESS': $data['orgaddress'] = $row['Value']; break;
                case 'BRANCHTELNO':   $data['orgtelno']   = $row['Value']; break;
            }
        }
        return $data;
    }

    public function SLReport($clientno, $loanid) {
        $bs = $this->getBranchSetup();

        // Loan header from tbl_aging
        $stmt = $this->conn->prepare("SELECT * FROM tbl_aging WHERE ClientNo = ? AND LoanID = ? LIMIT 1");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Transactions from tbl_books
        $stmt2 = $this->conn->prepare("
            SELECT CDate, ORNo, CVNo, JVNo, BookType, AcctNo, AcctTitle,
                   DrOther, CrOther, SLDrCr, Explanation
            FROM tbl_books
            WHERE ClientNo = ? AND LoanID = ?
            ORDER BY CDate ASC, ID ASC
        ");
        $stmt2->bind_param("ss", $clientno, $loanid);
        $stmt2->execute();
        $txnResult = $stmt2->get_result();
        $stmt2->close();

        $fullname   = $loan['FULLNAME']     ?? $clientno;
        $program    = $loan['PROGRAM']      ?? '';
        $product    = $loan['PRODUCT']      ?? '';
        $loanamt    = number_format(floatval($loan['LOANAMOUNT'] ?? 0), 2);
        $daterel    = !empty($loan['DATERELEASE']) ? date('m/d/Y', strtotime($loan['DATERELEASE'])) : '';
        $datemature = !empty($loan['DATEMATURE'])  ? date('m/d/Y', strtotime($loan['DATEMATURE']))  : '';
        $mode       = $loan['MODE']  ?? '';
        $term       = $loan['TERM']  ?? '';
        $rate       = $loan['INTERESTRATE'] ?? '';
        $po         = $loan['PO']    ?? '';

        $txnRows = '';
        $runBalance = 0;
        while ($row = $txnResult->fetch_assoc()) {
            $ref = $row['ORNo'] ?: ($row['CVNo'] ?: ($row['JVNo'] ?: '-'));
            $dr  = floatval($row['DrOther']);
            $cr  = floatval($row['CrOther']);
            $sl  = floatval($row['SLDrCr']);
            $runBalance += $dr - $cr;
            $txnRows .= '
            <tr style="font-size:8pt;">
                <td style="border:1px solid #ccc;padding:3px;">' . htmlspecialchars($row['CDate'] ?? '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;">' . htmlspecialchars($ref) . '</td>
                <td style="border:1px solid #ccc;padding:3px;">' . htmlspecialchars($row['BookType'] ?? '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;">' . htmlspecialchars($row['AcctTitle'] ?? '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;">' . ($dr > 0 ? number_format($dr, 2) : '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;">' . ($cr > 0 ? number_format($cr, 2) : '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;">' . number_format(abs($sl), 2) . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;font-weight:bold;">' . number_format($runBalance, 2) . '</td>
            </tr>';
        }
        if (!$txnRows) {
            $txnRows = '<tr><td colspan="8" style="text-align:center;padding:8px;font-size:8pt;color:#888;">No transactions on record.</td></tr>';
        }

        ob_clean(); ob_flush();
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false); // Landscape for wide table
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('SUBSIDIARY LEDGER');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        // Logo
        $logoPath = realpath(__DIR__ . '/../../assets/images/complete-logo.jpg');
        if ($logoPath && file_exists($logoPath)) {
            $lw = 40;
            $lx = ($pdf->getPageWidth() - $lw) / 2;
            $pdf->Image($logoPath, $lx, 10, $lw, 0, 'JPG', '', '', true, 150);
            $pdf->SetY(10 + 16);
        }

        $content = '
        <table cellpadding="2">
            <tr>
                <td style="text-align:center;">
                    <span style="font-size:12pt;font-weight:bold;">' . htmlspecialchars($bs['orgname']) . '</span><br/>
                    <span style="font-size:8pt;font-style:italic;">' . htmlspecialchars($bs['orgaddress']) . ' &nbsp;|&nbsp; Tel No. ' . htmlspecialchars($bs['orgtelno']) . '</span>
                </td>
            </tr>
        </table>
        <br/>
        <table cellpadding="0">
            <tr>
                <td style="background-color:#1e3a5f;color:white;text-align:center;font-size:12pt;font-weight:bold;padding:6px;">
                    SUBSIDIARY LEDGER
                </td>
            </tr>
        </table>
        <br/>

        <!-- Loan Info -->
        <table cellpadding="3" style="border:1px solid #1e3a5f;font-size:8.5pt;">
            <tr style="background-color:#e8eef5;">
                <td width="15%" style="border:1px solid #ccc;font-weight:bold;">Client Name:</td>
                <td width="35%" style="border:1px solid #ccc;">' . htmlspecialchars($fullname) . '</td>
                <td width="15%" style="border:1px solid #ccc;font-weight:bold;">Program:</td>
                <td width="35%" style="border:1px solid #ccc;">' . htmlspecialchars($program) . '</td>
            </tr>
            <tr>
                <td style="border:1px solid #ccc;font-weight:bold;">Client No.:</td>
                <td style="border:1px solid #ccc;">' . htmlspecialchars($clientno) . '</td>
                <td style="border:1px solid #ccc;font-weight:bold;">Product:</td>
                <td style="border:1px solid #ccc;">' . htmlspecialchars($product) . '</td>
            </tr>
            <tr>
                <td style="border:1px solid #ccc;font-weight:bold;">Loan ID:</td>
                <td style="border:1px solid #ccc;">' . htmlspecialchars($loanid) . '</td>
                <td style="border:1px solid #ccc;font-weight:bold;">Loan Amount:</td>
                <td style="border:1px solid #ccc;">' . $loanamt . '</td>
            </tr>
            <tr>
                <td style="border:1px solid #ccc;font-weight:bold;">Date Released:</td>
                <td style="border:1px solid #ccc;">' . $daterel . '</td>
                <td style="border:1px solid #ccc;font-weight:bold;">Date Matured:</td>
                <td style="border:1px solid #ccc;">' . $datemature . '</td>
            </tr>
            <tr>
                <td style="border:1px solid #ccc;font-weight:bold;">Mode / Term:</td>
                <td style="border:1px solid #ccc;">' . htmlspecialchars($mode) . ' / ' . htmlspecialchars($term) . '</td>
                <td style="border:1px solid #ccc;font-weight:bold;">Interest Rate:</td>
                <td style="border:1px solid #ccc;">' . htmlspecialchars($rate) . '%</td>
            </tr>
            <tr>
                <td style="border:1px solid #ccc;font-weight:bold;">Loan Officer:</td>
                <td style="border:1px solid #ccc;" colspan="3">' . htmlspecialchars($po) . '</td>
            </tr>
        </table>
        <br/>

        <!-- Transactions -->
        <table cellpadding="0" style="border:1px solid #ccc;">
            <tr style="background-color:#1e3a5f;color:white;font-weight:bold;font-size:8pt;">
                <td width="10%" style="border:1px solid #555;padding:4px;text-align:center;">Date</td>
                <td width="9%"  style="border:1px solid #555;padding:4px;text-align:center;">Ref No.</td>
                <td width="7%"  style="border:1px solid #555;padding:4px;text-align:center;">Book</td>
                <td width="30%" style="border:1px solid #555;padding:4px;text-align:center;">Account Title</td>
                <td width="11%" style="border:1px solid #555;padding:4px;text-align:center;">Debit</td>
                <td width="11%" style="border:1px solid #555;padding:4px;text-align:center;">Credit</td>
                <td width="11%" style="border:1px solid #555;padding:4px;text-align:center;">SL Dr/Cr</td>
                <td width="11%" style="border:1px solid #555;padding:4px;text-align:center;">Balance</td>
            </tr>
            ' . $txnRows . '
        </table>
        <br/>
        <table cellpadding="2" style="background-color:#f0f4f8;border:1px solid #ccc;">
            <tr>
                <td style="font-size:8pt;">Printed: ' . date('m/d/Y h:i A') . '</td>
                <td style="font-size:8pt;text-align:right;">This is a system-generated document.</td>
            </tr>
        </table>';

        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('subsidiary_ledger.pdf', 'I');
    }

    public function SOAReport($clientno, $loanid) {
        $bs = $this->getBranchSetup();

        // Load aging record
        $stmt = $this->conn->prepare("SELECT * FROM tbl_aging WHERE ClientNo = ? AND LoanID = ? LIMIT 1");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$loan) { echo "Record not found."; return; }

        // Load SL transactions from tbl_books
        $stmt2 = $this->conn->prepare("
            SELECT CDate, ORNo, CVNo, JVNo, BookType, AcctNo, AcctTitle,
                   DrOther, CrOther, SLDrCr, Explanation
            FROM tbl_books
            WHERE ClientNo = ? AND LoanID = ?
            ORDER BY CDate ASC, ID ASC
        ");
        $stmt2->bind_param("ss", $clientno, $loanid);
        $stmt2->execute();
        $txnResult = $stmt2->get_result();
        $stmt2->close();

        $fullname   = $loan['FULLNAME']    ?? '';
        $program    = $loan['PROGRAM']     ?? '';
        $product    = $loan['PRODUCT']     ?? '';
        $loanamt    = floatval($loan['LOANAMOUNT'] ?? 0);
        $netamt     = floatval($loan['NETAMOUNT']  ?? 0) > 0 ? floatval($loan['NETAMOUNT']) : $loanamt;
        $interest   = floatval($loan['INTEREST']   ?? 0);
        $cbu        = floatval($loan['CBUFTL']     ?? 0);
        $ef         = floatval($loan['EF']         ?? 0);
        $mba        = floatval($loan['MBA']        ?? 0);
        $mode       = $loan['MODE']        ?? '';
        $term       = $loan['TERM']        ?? '';
        $rate       = $loan['INTERESTRATE']?? '';
        $po         = $loan['PO']          ?? '';
        $tag        = $loan['TAG']         ?? '';
        $daterel    = !empty($loan['DATERELEASE']) ? date('m/d/Y', strtotime($loan['DATERELEASE'])) : '';
        $datemature = !empty($loan['DATEMATURE'])  ? date('m/d/Y', strtotime($loan['DATEMATURE']))  : '';
        $balance    = floatval($loan['Balance']    ?? $loanamt);
        $amtpaid    = floatval($loan['AmountPaid'] ?? 0);
        $intpaid    = floatval($loan['InterestPaid'] ?? 0);
        $totaldue   = floatval($loan['AmountDue']  ?? 0) + floatval($loan['InterestDue'] ?? 0);
        $duedate    = !empty($loan['DueDate']) ? date('m/d/Y', strtotime($loan['DueDate'])) : '';

        // Build transaction rows
        $txnRows = '';
        $runBalance = 0;
        while ($row = $txnResult->fetch_assoc()) {
            $ref  = $row['ORNo'] ?: ($row['CVNo'] ?: ($row['JVNo'] ?: '-'));
            $dr   = floatval($row['DrOther']);
            $cr   = floatval($row['CrOther']);
            $sl   = floatval($row['SLDrCr']);
            $runBalance += $dr - $cr;
            $txnRows .= '
            <tr style="font-size:8pt;">
                <td style="border:1px solid #ccc;padding:3px;">' . htmlspecialchars($row['CDate'] ?? '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;">' . htmlspecialchars($ref) . '</td>
                <td style="border:1px solid #ccc;padding:3px;">' . htmlspecialchars($row['AcctTitle'] ?? '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;">' . ($dr > 0 ? number_format($dr, 2) : '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;">' . ($cr > 0 ? number_format($cr, 2) : '') . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;">' . number_format(abs($sl), 2) . '</td>
                <td style="border:1px solid #ccc;padding:3px;text-align:right;">' . number_format($runBalance, 2) . '</td>
            </tr>';
        }
        if (!$txnRows) {
            $txnRows = '<tr><td colspan="7" style="text-align:center;padding:6px;font-size:8pt;color:#888;">No transactions on record.</td></tr>';
        }

        ob_clean(); ob_flush();
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('STATEMENT OF ACCOUNT');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        // Logo
        $logoPath = realpath(__DIR__ . '/../../assets/images/complete-logo.jpg');
        if ($logoPath && file_exists($logoPath)) {
            $lw = 40;
            $lx = ($pdf->getPageWidth() - $lw) / 2;
            $pdf->Image($logoPath, $lx, 10, $lw, 0, 'JPG', '', '', true, 150);
            $pdf->SetY(10 + 16);
        }

        $printDate = date('m/d/Y');

        $content = '
        <!-- Header -->
        <table cellpadding="2">
            <tr>
                <td style="text-align:center;">
                    <span style="font-size:12pt;font-weight:bold;">' . htmlspecialchars($bs['orgname']) . '</span><br/>
                    <span style="font-size:8pt;font-style:italic;">' . htmlspecialchars($bs['orgaddress']) . '</span><br/>
                    <span style="font-size:8pt;font-style:italic;">Tel No. ' . htmlspecialchars($bs['orgtelno']) . '</span>
                </td>
            </tr>
        </table>
        <br/>

        <!-- Title bar -->
        <table cellpadding="0">
            <tr>
                <td style="background-color:#1e3a5f;color:white;text-align:center;font-size:12pt;font-weight:bold;padding:7px;">
                    STATEMENT OF ACCOUNT
                </td>
            </tr>
        </table>
        <br/>

        <!-- Client + Loan Info -->
        <table cellpadding="4" style="border:1px solid #1e3a5f;">
            <tr style="background-color:#e8eef5;">
                <td width="50%" style="border:1px solid #ccc;font-weight:bold;font-size:9pt;" colspan="2">CLIENT INFORMATION</td>
                <td width="50%" style="border:1px solid #ccc;font-weight:bold;font-size:9pt;" colspan="2">LOAN DETAILS</td>
            </tr>
            <tr style="font-size:8.5pt;">
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Client Name:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($fullname) . '</td>
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Program:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($program) . '</td>
            </tr>
            <tr style="font-size:8.5pt;">
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Client No.:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($clientno) . '</td>
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Product:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($product) . '</td>
            </tr>
            <tr style="font-size:8.5pt;">
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Loan ID:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($loanid) . '</td>
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Mode / Term:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($mode) . ' / ' . htmlspecialchars($term) . '</td>
            </tr>
            <tr style="font-size:8.5pt;">
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Loan Officer:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($po) . '</td>
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Interest Rate:</td>
                <td width="25%" style="border:1px solid #ccc;">' . htmlspecialchars($rate) . '%</td>
            </tr>
            <tr style="font-size:8.5pt;">
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Date Released:</td>
                <td width="25%" style="border:1px solid #ccc;">' . $daterel . '</td>
                <td width="25%" style="border:1px solid #ccc;font-weight:bold;">Date Matured:</td>
                <td width="25%" style="border:1px solid #ccc;">' . $datemature . '</td>
            </tr>
        </table>
        <br/>

        <!-- Loan Summary -->
        <table cellpadding="4" style="border:1px solid #1e3a5f;">
            <tr style="background-color:#e8eef5;font-weight:bold;font-size:8.5pt;">
                <td width="33%" style="border:1px solid #ccc;text-align:center;">LOAN SUMMARY</td>
                <td width="33%" style="border:1px solid #ccc;text-align:center;">PAYMENTS MADE</td>
                <td width="34%" style="border:1px solid #ccc;text-align:center;">CURRENT DUE</td>
            </tr>
            <tr style="font-size:8.5pt;">
                <td width="33%" style="border:1px solid #ccc;">
                    <table cellpadding="2">
                        <tr><td width="55%">Loan Amount:</td><td width="45%" style="text-align:right;">' . number_format($loanamt, 2) . '</td></tr>
                        <tr><td>Interest:</td><td style="text-align:right;">' . number_format($interest, 2) . '</td></tr>
                        <tr><td>CBU:</td><td style="text-align:right;">' . number_format($cbu, 2) . '</td></tr>
                        <tr><td>EF:</td><td style="text-align:right;">' . number_format($ef, 2) . '</td></tr>
                        <tr><td>MBA:</td><td style="text-align:right;">' . number_format($mba, 2) . '</td></tr>
                        <tr><td style="font-weight:bold;">Net Amount:</td><td style="text-align:right;font-weight:bold;">' . number_format($netamt, 2) . '</td></tr>
                    </table>
                </td>
                <td width="33%" style="border:1px solid #ccc;">
                    <table cellpadding="2">
                        <tr><td width="55%">Principal Paid:</td><td width="45%" style="text-align:right;">' . number_format($amtpaid, 2) . '</td></tr>
                        <tr><td>Interest Paid:</td><td style="text-align:right;">' . number_format($intpaid, 2) . '</td></tr>
                        <tr style="font-weight:bold;"><td>Balance:</td><td style="text-align:right;">' . number_format($balance, 2) . '</td></tr>
                    </table>
                </td>
                <td width="34%" style="border:1px solid #ccc;">
                    <table cellpadding="2">
                        <tr><td width="55%">Due Date:</td><td width="45%">' . $duedate . '</td></tr>
                        <tr style="font-weight:bold;color:#c00000;"><td>Total Due:</td><td style="text-align:right;">' . number_format($totaldue, 2) . '</td></tr>
                    </table>
                </td>
            </tr>
        </table>
        <br/>

        <!-- Transaction History -->
        <table cellpadding="0">
            <tr>
                <td style="font-weight:bold;font-size:9pt;background-color:#e8eef5;padding:5px;border-left:3px solid #1e3a5f;">
                    TRANSACTION HISTORY
                </td>
            </tr>
        </table>
        <table cellpadding="0" style="border:1px solid #ccc;">
            <tr style="background-color:#1e3a5f;color:white;font-weight:bold;font-size:8pt;">
                <td width="12%" style="border:1px solid #555;padding:4px;text-align:center;">Date</td>
                <td width="10%" style="border:1px solid #555;padding:4px;text-align:center;">Ref</td>
                <td width="28%" style="border:1px solid #555;padding:4px;text-align:center;">Account</td>
                <td width="12%" style="border:1px solid #555;padding:4px;text-align:center;">Debit</td>
                <td width="12%" style="border:1px solid #555;padding:4px;text-align:center;">Credit</td>
                <td width="13%" style="border:1px solid #555;padding:4px;text-align:center;">SL Dr/Cr</td>
                <td width="13%" style="border:1px solid #555;padding:4px;text-align:center;">Balance</td>
            </tr>
            ' . $txnRows . '
        </table>
        <br/>

        <!-- Footer -->
        <table cellpadding="4" style="background-color:#f0f4f8;border:1px solid #ccc;">
            <tr>
                <td width="50%" style="font-size:8pt;">As of: ' . $printDate . '</td>
                <td width="50%" style="font-size:8pt;text-align:right;">This is a system-generated document.</td>
            </tr>
        </table>
        <br/><br/>
        <table cellpadding="4">
            <tr>
                <td width="33%" style="text-align:center;border-top:1px solid #333;font-size:8pt;">Prepared By</td>
                <td width="33%"></td>
                <td width="34%" style="text-align:center;border-top:1px solid #333;font-size:8pt;">Received By / Date</td>
            </tr>
        </table>';

        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('soa.pdf', 'I');
    }
}