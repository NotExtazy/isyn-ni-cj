<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . '/iSynApp-main/assets/tcpdf/tcpdf.php');
include_once($docRoot . '/iSynApp-main/database/connection.php');

class Reports extends Database
{
    public function numberToWords($number) {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        $teens = ['ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        
        $number = number_format($number, 2, '.', '');
        list($whole, $decimal) = explode('.', $number);
        $whole = intval($whole);
        
        if ($whole == 0) {
            $words = 'zero pesos';
        } else {
            $words = '';
            if ($whole >= 1000000) {
                $millions = intval($whole / 1000000);
                $words .= $this->convertHundreds($millions, $ones, $tens, $teens) . ' million ';
                $whole %= 1000000;
            }
            if ($whole >= 1000) {
                $thousands = intval($whole / 1000);
                $words .= $this->convertHundreds($thousands, $ones, $tens, $teens) . ' thousand ';
                $whole %= 1000;
            }
            if ($whole > 0) {
                $words .= $this->convertHundreds($whole, $ones, $tens, $teens);
            }
            $words .= ' pesos';
        }
        
        if (intval($decimal) > 0) {
            $words .= ' and ' . $this->convertHundreds(intval($decimal), $ones, $tens, $teens) . ' centavos';
        }
        
        return trim($words);
    }
    
    public function convertHundreds($num, $ones, $tens, $teens) {
        $words = '';
        if ($num >= 100) {
            $hundreds = intval($num / 100);
            $words .= $ones[$hundreds] . ' hundred ';
            $num %= 100;
        }
        if ($num >= 20) {
            $words .= $tens[intval($num / 10)] . ' ';
            $num %= 10;
        } elseif ($num >= 10) {
            $words .= $teens[$num - 10] . ' ';
            return $words;
        }
        if ($num > 0) {
            $words .= $ones[$num] . ' ';
        }
        return $words;
    }
    
    public function generatePaymentSchedule($dateRelease, $mode, $term, $loanAmount, $totalInterest, $principalAmo, $interestAmo, $totalAmo) {
        if (empty($dateRelease) || $term <= 0) {
            return '<tr><td colspan="5" style="border:1px solid black;text-align:center;padding:10px;font-style:italic;">No payment schedule available</td></tr>';
        }
        
        // Parse release date
        $startDate = new DateTime($dateRelease);
        
        // Determine payment interval based on mode
        $intervalMap = [
            'WEEKLY' => 'P1W',
            'SEMI-MONTHLY' => 'P15D',
            'MONTHLY' => 'P1M',
            'BI-MONTHLY' => 'P2M',
            'QUARTERLY' => 'P3M',
            'SEMI-ANNUAL' => 'P6M',
            'ANNUAL' => 'P1Y',
            'LUMPSUM' => null
        ];
        
        $interval = $intervalMap[strtoupper($mode)] ?? 'P1M'; // Default to monthly
        
        // Calculate number of payments based on mode and term
        $numPayments = $term;
        if (strtoupper($mode) === 'SEMI-MONTHLY') {
            $numPayments = $term * 2; // 2 payments per month
        } elseif (strtoupper($mode) === 'WEEKLY') {
            $numPayments = $term * 4; // Approximately 4 weeks per month
        } elseif (strtoupper($mode) === 'BI-MONTHLY') {
            $numPayments = ceil($term / 2); // 1 payment every 2 months
        } elseif (strtoupper($mode) === 'QUARTERLY') {
            $numPayments = ceil($term / 3); // 1 payment every 3 months
        } elseif (strtoupper($mode) === 'SEMI-ANNUAL') {
            $numPayments = ceil($term / 6); // 1 payment every 6 months
        } elseif (strtoupper($mode) === 'ANNUAL') {
            $numPayments = ceil($term / 12); // 1 payment every 12 months
        }
        
        // Ensure at least 1 payment
        if ($numPayments < 1) $numPayments = 1;
        
        // Calculate per-payment amounts to ensure totals match exactly
        // Use loan amount and total interest to calculate, not the stored amortization values
        $principalPerPayment = $loanAmount / $numPayments;
        $interestPerPayment = $totalInterest / $numPayments;
        $totalPerPayment = ($loanAmount + $totalInterest) / $numPayments;
        
        // For LUMPSUM, show single payment at maturity with total amounts
        if (strtoupper($mode) === 'LUMPSUM' || $interval === null) {
            $dueDate = clone $startDate;
            $dueDate->add(new DateInterval('P' . $term . 'M')); // Add term in months
            
            return '
                <tr>
                    <td width="10%" style="border:1px solid black;text-align:center;">1</td>
                    <td width="25%" style="border:1px solid black;text-align:center;">' . $dueDate->format('m/d/Y') . '</td>
                    <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($loanAmount, 2) . '</td>
                    <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($totalInterest, 2) . '</td>
                    <td width="25%" style="border:1px solid black;text-align:right;font-weight:bold;">' . number_format($loanAmount + $totalInterest, 2) . '</td>
                </tr>
                <tr style="font-weight:bold;background-color:#eeeeee;">
                    <td colspan="2" style="border:1px solid black;text-align:right;padding-right:10px;">TOTAL (1 payment):</td>
                    <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($loanAmount, 2) . '</td>
                    <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($totalInterest, 2) . '</td>
                    <td width="25%" style="border:1px solid black;text-align:right;">' . number_format($loanAmount + $totalInterest, 2) . '</td>
                </tr>';
        }
        
        // Generate schedule rows
        $scheduleRows = '';
        $currentDate = clone $startDate;
        
        // Track cumulative totals for verification
        $cumulativePrincipal = 0;
        $cumulativeInterest = 0;
        $cumulativeTotal = 0;
        
        for ($i = 1; $i <= $numPayments; $i++) {
            // Calculate due date
            if (strtoupper($mode) === 'SEMI-MONTHLY') {
                // For semi-monthly: 15th and end of month
                if ($i % 2 == 1) {
                    // First payment of month - 15th
                    $currentDate->setDate($currentDate->format('Y'), $currentDate->format('m'), 15);
                } else {
                    // Second payment of month - last day
                    $currentDate->setDate($currentDate->format('Y'), $currentDate->format('m'), $currentDate->format('t'));
                    // Move to next month for next iteration
                    $currentDate->add(new DateInterval('P1D'));
                }
            } else {
                // For other modes, add interval
                $currentDate->add(new DateInterval($interval));
            }
            
            // For last payment, adjust to ensure totals match exactly (handle rounding)
            if ($i == $numPayments) {
                $principalPerPayment = $loanAmount - $cumulativePrincipal;
                $interestPerPayment = $totalInterest - $cumulativeInterest;
                $totalPerPayment = ($loanAmount + $totalInterest) - $cumulativeTotal;
            }
            
            // Add to cumulative totals
            $cumulativePrincipal += $principalPerPayment;
            $cumulativeInterest += $interestPerPayment;
            $cumulativeTotal += $totalPerPayment;
            
            $rowBg = ($i % 2 == 0) ? 'background-color:#f9f9f9;' : '';
            
            $scheduleRows .= '
                <tr style="' . $rowBg . '">
                    <td width="10%" style="border:1px solid black;text-align:center;">' . $i . '</td>
                    <td width="25%" style="border:1px solid black;text-align:center;">' . $currentDate->format('m/d/Y') . '</td>
                    <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($principalPerPayment, 2) . '</td>
                    <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($interestPerPayment, 2) . '</td>
                    <td width="25%" style="border:1px solid black;text-align:right;font-weight:bold;">' . number_format($totalPerPayment, 2) . '</td>
                </tr>';
        }
        
        // Add totals row - should match loan amount and total interest exactly
        $scheduleRows .= '
            <tr style="font-weight:bold;background-color:#eeeeee;">
                <td colspan="2" style="border:1px solid black;text-align:right;padding-right:10px;">TOTAL (' . $numPayments . ' payments):</td>
                <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($loanAmount, 2) . '</td>
                <td width="20%" style="border:1px solid black;text-align:right;">' . number_format($totalInterest, 2) . '</td>
                <td width="25%" style="border:1px solid black;text-align:right;">' . number_format($loanAmount + $totalInterest, 2) . '</td>
            </tr>';
        
        return $scheduleRows;
    }
    
    public function updatePrintStatus($clientno, $loanid, $field) {
        // Update tbl_loans print status
        $stmt = $this->conn->prepare("UPDATE tbl_loans SET $field = 'YES' WHERE ClientNo = ? AND LoanID = ?");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $stmt->close();
        
        // Note: ReleaseStatus is calculated dynamically in the LoadList query
        // based on CVPrinted and CheckPrinted flags, no need to store it
        
        // Save or update in tbl_loanhistory
        $this->saveOrUpdateLoanHistory($clientno, $loanid, $field);
    }
    
    public function saveOrUpdateLoanHistory($clientno, $loanid, $field) {
        // Check if tbl_loanhistory exists
        $checkTable = $this->conn->query("SHOW TABLES LIKE 'tbl_loanhistory'");
        if ($checkTable->num_rows === 0) {
            return; // Table doesn't exist, skip
        }
        
        // Check if this loan already exists in history
        $stmt = $this->conn->prepare("SELECT ID FROM tbl_loanhistory WHERE ClientNo = ? AND LoanID = ? LIMIT 1");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        if ($exists) {
            // UPDATE existing record - just update the print status field
            $stmt = $this->conn->prepare("UPDATE tbl_loanhistory SET $field = 'YES' WHERE ClientNo = ? AND LoanID = ?");
            $stmt->bind_param("ss", $clientno, $loanid);
            $stmt->execute();
            $stmt->close();
        } else {
            // INSERT new record - copy from tbl_loans with print status
            $this->insertLoanHistory($clientno, $loanid, $field);
        }
    }
    
    public function insertLoanHistory($clientno, $loanid, $field) {
        // Get current loan data from tbl_loans
        $stmt = $this->conn->prepare("SELECT * FROM tbl_loans WHERE ClientNo = ? AND LoanID = ? LIMIT 1");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($loan = $result->fetch_assoc()) {
            // Get columns from tbl_loanhistory
            $historyColumns = $this->conn->query("DESCRIBE tbl_loanhistory");
            $validColumns = [];
            while ($col = $historyColumns->fetch_assoc()) {
                $colName = $col['Field'];
                // Skip ID and only include columns that exist in tbl_loans
                if ($colName !== 'ID' && isset($loan[$colName])) {
                    $validColumns[] = $colName;
                }
            }
            
            if (empty($validColumns)) {
                return; // No matching columns found
            }
            
            // Build INSERT statement
            $columnList = implode(', ', $validColumns);
            $placeholders = implode(', ', array_fill(0, count($validColumns), '?'));
            
            $insertSQL = "INSERT INTO tbl_loanhistory ($columnList) VALUES ($placeholders)";
            
            $stmt = $this->conn->prepare($insertSQL);
            
            // Build parameter types string
            $types = str_repeat('s', count($validColumns));
            
            // Build values array - set the print status field to YES
            $values = [];
            foreach ($validColumns as $colName) {
                if ($colName === $field) {
                    $values[] = 'YES'; // Set the current print field to YES
                } else {
                    $values[] = $loan[$colName];
                }
            }
            
            // Bind parameters dynamically
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function getBranchSetup() {
        $data = ['orgname'=>'','orgaddress'=>'','orgtelno'=>'','aasig'=>'','bksig'=>'','bmsig'=>''];
        $stmt = $this->conn->prepare("SELECT ConfigName, Value FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME','BRANCHADDRESS','BRANCHTELNO','AASIG','BKSIG','BMSIG')");
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        while ($row = $res->fetch_assoc()) {
            switch ($row['ConfigName']) {
                case 'ORGNAME':       $data['orgname']    = $row['Value']; break;
                case 'BRANCHADDRESS': $data['orgaddress'] = $row['Value']; break;
                case 'BRANCHTELNO':   $data['orgtelno']   = $row['Value']; break;
                case 'AASIG':         $data['aasig']      = $row['Value']; break;
                case 'BKSIG':         $data['bksig']      = $row['Value']; break;
                case 'BMSIG':         $data['bmsig']      = $row['Value']; break;
            }
        }
        return $data;
    }

    public function makePdf($title = 'DOCUMENT') {
        ob_clean(); ob_flush();
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle($title);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(4, 8, 4);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        return $pdf;
    }

    // ── Voucher (Check Voucher) ──────────────────────────────────────────────
    public function VoucherReport($clientno, $loanid) {
        // Update tbl_loans CVPrinted to YES
        $this->updatePrintStatus($clientno, $loanid, 'CVPrinted');
        
        $bs = $this->getBranchSetup();

        // Pull from tbl_books (already saved by SaveRelease)
        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'CDB' AND ClientNo = ? AND LoanID = ? ORDER BY ID ASC");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $branch = $fund = $payee = $particular = $cvno = $checkno = $checkdate = '';
        $amount = 0; $amtwords = '';
        $contentdata = '';
        $hasData = false;

        while ($row = $result->fetch_assoc()) {
            $hasData = true;
            $branch    = $row['Branch'];
            $fund      = $row['Fund'];
            $payee     = $row['Payee'];
            $particular = $row['Explanation'];
            $cvno      = $row['CVNo'];
            $checkno   = $row['CheckNo'];
            $checkdate = $row['CDate'];
            if ($row['AcctNo'] == '11130') {
                $amount   = abs(floatval($row['CrOther']));
                $amtwords = ucwords($this->numberToWords(floatval($amount)));
            }
            $sl = number_format(abs(floatval($row['SLDrCr'])), 2);
            $contentdata .= '
                <tr style="font-size:9pt;">
                    <td width="40%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;"><pre>&nbsp;' . $row['AcctTitle'] . '</pre></td>
                    <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:center;"><pre>' . $row['AcctNo'] . '</pre></td>
                    <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:right;"><pre>' . ($row['SLDrCr'] < 0 ? '(' . $sl . ')' : $sl) . '&nbsp;</pre></td>
                    <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:right;"><pre>' . number_format($row['DrOther'], 2) . '&nbsp;</pre></td>
                    <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:right;"><pre>' . number_format($row['CrOther'], 2) . '&nbsp;</pre></td>
                </tr>';
        }

        // If no data in tbl_books, generate from tbl_loans and use temporary CV/Check numbers from session
        if (!$hasData) {
            $stmt2 = $this->conn->prepare("
                SELECT l.*, 
                       CONCAT(c.LastName, ', ', c.FirstName, ' ', COALESCE(c.MiddleName, '')) as FULLNAME
                FROM tbl_loans l
                LEFT JOIN tbl_clientinfo c ON c.ClientNo = l.ClientNo
                WHERE l.ClientNo = ? AND l.LoanID = ? 
                LIMIT 1
            ");
            $stmt2->bind_param("ss", $clientno, $loanid);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $stmt2->close();
            
            if ($loan = $res2->fetch_assoc()) {
                $loanamt  = floatval($loan["LoanAmount"]);
                $netamt   = floatval($loan["NetAmount"]);
                $interest = floatval($loan["Interest"]);
                $cbu      = floatval($loan["CBU"]);
                $ef       = floatval($loan["EF"]);
                $mba      = floatval($loan["MBA"]);
                $fullname = $loan["FULLNAME"];
                $bankname = $loan["Bank"] ?? "CASH";
                
                if ($netamt <= 0) $netamt = $loanamt;
                
                $branch = $bs['orgname'];
                $fund = $loan["Fund"] ?? $_SESSION['TEMP_FUND'] ?? "-";
                $payee = $fullname;
                $particular = "LOAN RELEASE - " . $fullname;
                
                // Use temporary CV and Check numbers from session if available (set when bank is selected)
                $cvno = $_SESSION['TEMP_CVNO'] ?? "-";
                $checkno = $_SESSION['TEMP_CHECKNO'] ?? "-";
                $checkdate = date('Y-m-d');
                $amount = $netamt;
                $amtwords = ucwords($this->numberToWords($netamt));
                
                // Generate entries
                $entries = [
                    ["AcctTitle" => "LOANS RECEIVABLE", "AcctNo" => "11920", "SL" => $fullname, "Debit" => $loanamt, "Credit" => 0],
                    ["AcctTitle" => "CASH IN BANK", "AcctNo" => "11130", "SL" => $bankname, "Debit" => 0, "Credit" => $netamt],
                ];
                if ($interest > 0) $entries[] = ["AcctTitle" => "INTEREST INCOME", "AcctNo" => "21370", "SL" => "-", "Debit" => 0, "Credit" => $interest];
                if ($cbu > 0) $entries[] = ["AcctTitle" => "CBU PAYABLE", "AcctNo" => "31100", "SL" => "-", "Debit" => 0, "Credit" => $cbu];
                if ($ef > 0) $entries[] = ["AcctTitle" => "ENTRANCE FEE", "AcctNo" => "43400", "SL" => "-", "Debit" => 0, "Credit" => $ef];
                if ($mba > 0) $entries[] = ["AcctTitle" => "MBA PAYABLE", "AcctNo" => "21210", "SL" => "-", "Debit" => 0, "Credit" => $mba];
                
                foreach ($entries as $e) {
                    $sl = number_format(abs($e["Debit"] - $e["Credit"]), 2);
                    $contentdata .= '
                        <tr style="font-size:9pt;">
                            <td width="40%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;"><pre>&nbsp;' . $e['AcctTitle'] . '</pre></td>
                            <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:center;"><pre>' . $e['AcctNo'] . '</pre></td>
                            <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:right;"><pre>' . ($e['Credit'] > 0 ? '(' . $sl . ')' : $sl) . '&nbsp;</pre></td>
                            <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:right;"><pre>' . number_format($e['Debit'], 2) . '&nbsp;</pre></td>
                            <td width="15%" height="20px" style="border-left:1px solid black;border-right:1px solid black;line-height:20px;text-align:right;"><pre>' . number_format($e['Credit'], 2) . '&nbsp;</pre></td>
                        </tr>';
                }
            }
        }

        $pdf = $this->makePdf('CHECK VOUCHER');
        
        // Add logo at top center
        $logoPath = realpath(__DIR__ . '/../../assets/images/complete-logo.png');
        if ($logoPath && file_exists($logoPath)) {
            $lw = 45;
            $lx = ($pdf->getPageWidth() - $lw) / 2;
            $pdf->Image($logoPath, $lx, 8, $lw, 0, 'PNG', '', '', true, 150);
            $pdf->SetY(8 + 18);
        }

        $content = '
        <table>
            <tr><td width="25%"></td><td width="50%"><p style="font-weight:bold;text-align:center;font-size:12pt;">' . $bs['orgname'] . '</p></td><td width="25%"></td></tr>
            <tr><td width="25%"></td><td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">' . $bs['orgaddress'] . '</p></td><td width="25%"></td></tr>
            <tr><td width="25%"></td><td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">Tel No. ' . $bs['orgtelno'] . '</p></td><td width="25%"></td></tr>
            <tr><td height="20px"></td></tr>
            <tr><td width="100%" height="30px" style="font-size:12px;font-weight:bold;text-align:center;"><p>CHECK VOUCHER</p></td></tr>
            <tr>
                <td width="50%" style="font-size:9px;">
                    <table>
                        <tr><td width="20%" style="font-weight:bold;">BRANCH:</td><td width="80%">' . $branch . '</td></tr>
                        <tr><td width="20%" style="font-weight:bold;">FUND:</td><td width="80%">' . $fund . '</td></tr>
                        <tr><td width="20%" style="font-weight:bold;">PAYEE:</td><td width="80%">' . $payee . '</td></tr>
                    </table>
                </td>
                <td width="50%" style="font-size:9px;text-align:right;">
                    <table>
                        <tr><td width="60%" style="font-weight:bold;">CV NO:</td><td width="40%">' . $cvno . '</td></tr>
                        <tr><td width="60%" style="font-weight:bold;">CHECK DATE:</td><td width="40%">' . $checkdate . '</td></tr>
                        <tr><td width="60%" style="font-weight:bold;">CHECK NO:</td><td width="40%">' . $checkno . '</td></tr>
                    </table>
                </td>
            </tr>
            <tr><td></td></tr>
            <tr><td width="100%">
                <table cellpadding="4" style="font-size:10pt;font-weight:bold;">
                    <tr>
                        <td width="85%" style="border:1px solid black;text-align:center;">PARTICULARS</td>
                        <td width="15%" style="border:1px solid black;text-align:center;">AMOUNT</td>
                    </tr>
                </table>
            </td></tr>
            <tr>
                <td width="85%" height="50px" style="border:1px solid black;text-align:center;font-size:10pt;font-style:italic;">' . $particular . '</td>
                <td width="15%" height="50px" style="border:1px solid black;text-align:center;font-size:10pt;font-weight:bold;line-height:50px">' . number_format($amount, 2) . '</td>
            </tr>
            <tr style="font-weight:bold;font-size:9pt;text-align:center;line-height:30px;">
                <td width="40%" height="30px" style="border:1px solid black;">Account Title</td>
                <td width="15%" height="30px" style="border:1px solid black;">Account No</td>
                <td width="15%" height="30px" style="border:1px solid black;">Subsidiary DR(CR)</td>
                <td width="15%" height="30px" style="border:1px solid black;">Debit</td>
                <td width="15%" height="30px" style="border:1px solid black;">Credit</td>
            </tr>
            ' . $contentdata . '
            <tr style="font-weight:bold;font-size:9pt;line-height:30px;">
                <td width="25%" height="30px" style="border:1px solid black;">&nbsp;Prepared By:</td>
                <td width="25%" height="30px" style="border:1px solid black;">&nbsp;Checked By:</td>
                <td width="50%" height="30px" style="border:1px solid black;">&nbsp;Approved By:</td>
            </tr>
            <tr style="font-size:9pt;line-height:50px;text-align:center;">
                <td width="25%" height="50px" style="border:1px solid black;">' . $bs['aasig'] . '</td>
                <td width="25%" height="50px" style="border:1px solid black;">' . $bs['bksig'] . '</td>
                <td width="25%" height="50px" style="border:1px solid black;">' . $bs['bmsig'] . '</td>
                <td width="25%" height="50px" style="border:1px solid black;"></td>
            </tr>
            <tr><td height="40px"></td></tr>
            <tr><td width="8%"></td><td width="90%" style="font-size:10pt;">Received from ' . $bs['orgname'] . ' the sum of</td></tr>
            <tr><td></td></tr>
            <tr><td width="8%"></td><td width="90%" style="font-size:10pt;font-weight:bold;">' . $amtwords . '</td></tr>
            <tr><td></td></tr>
            <tr><td width="8%"></td><td width="90%" style="font-size:10pt;">in full/partial payment of the above mentioned account.</td></tr>
            <tr><td height="30px"></td></tr>
            <tr><td width="75%"></td><td width="25%" style="font-size:10pt;">Payee: __________________</td></tr>
            <tr><td height="10px"></td></tr>
            <tr><td width="69%"></td><td width="31%" style="font-size:10pt;">Date Received: __________________</td></tr>
        </table>';

        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('voucher.pdf', 'I');
    }

    // ── Check / Confirm ──────────────────────────────────────────────────────
    public function CheckReport($clientno, $loanid) {
        // Update tbl_loans CheckPrinted to YES
        $this->updatePrintStatus($clientno, $loanid, 'CheckPrinted');
        
        // Try tbl_books first (after SaveRelease), fall back to tbl_loans, then tbl_aging
        $checkName = ''; $dateStr = ''; $netAmount = 0.0; $inWords = '';

        $stmt = $this->conn->prepare("SELECT Payee, CDate, CheckNo, CrOther FROM tbl_books WHERE BookType = 'CDB' AND ClientNo = ? AND LoanID = ? ORDER BY ID ASC LIMIT 1");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($row = $result->fetch_assoc()) {
            $checkName = $row['Payee'] ?? '';
            $dateStr   = !empty($row['CDate']) ? date('m/d/Y', strtotime($row['CDate'])) : '';
            $netAmount = abs(floatval($row['CrOther']));
        } else {
            // Fall back to tbl_loans
            $stmt2 = $this->conn->prepare("
                SELECT CONCAT(c.LastName, ', ', c.FirstName, ' ', COALESCE(c.MiddleName, '')) as FULLNAME,
                       l.DateRelease, l.NetAmount, l.LoanAmount
                FROM tbl_loans l
                LEFT JOIN tbl_clientinfo c ON c.ClientNo = l.ClientNo
                WHERE l.ClientNo = ? AND l.LoanID = ? 
                LIMIT 1
            ");
            $stmt2->bind_param("ss", $clientno, $loanid);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $stmt2->close();
            
            if ($row2 = $res2->fetch_assoc()) {
                $checkName = $row2['FULLNAME'] ?? '';
                $dateStr   = !empty($row2['DateRelease']) ? date('m/d/Y', strtotime($row2['DateRelease'])) : '';
                $netAmount = floatval($row2['NetAmount']) > 0 ? floatval($row2['NetAmount']) : floatval($row2['LoanAmount']);
            } else {
                // Final fallback to tbl_aging
                $stmt3 = $this->conn->prepare("SELECT FULLNAME, DATERELEASE, NETAMOUNT, LOANAMOUNT FROM tbl_aging WHERE ClientNo = ? AND LoanID = ? LIMIT 1");
                $stmt3->bind_param("ss", $clientno, $loanid);
                $stmt3->execute();
                $res3 = $stmt3->get_result();
                $stmt3->close();
                if ($row3 = $res3->fetch_assoc()) {
                    $checkName = $row3['FULLNAME'] ?? '';
                    $dateStr   = !empty($row3['DATERELEASE']) ? date('m/d/Y', strtotime($row3['DATERELEASE'])) : '';
                    $netAmount = floatval($row3['NETAMOUNT']) > 0 ? floatval($row3['NETAMOUNT']) : floatval($row3['LOANAMOUNT']);
                }
            }
        }

        $netAmount = floatval($netAmount); // ensure float always
        $inWords   = $netAmount > 0 ? ucwords($this->numberToWords($netAmount)) : '';
        $amtFmt    = number_format($netAmount, 2);

        ob_clean(); ob_flush();
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle('CHECK CONFIRMATION');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // Centered logo
        $logoPath = realpath(__DIR__ . '/../../assets/images/complete-logo.png');
        if ($logoPath && file_exists($logoPath)) {
            $lw = 45; // mm
            $lx = ($pdf->getPageWidth() - $lw) / 2;
            $pdf->Image($logoPath, $lx, 15, $lw, 0, 'PNG', '', '', true, 150);
            $pdf->SetY(15 + 18); // push cursor below logo (~18mm tall)
        }

        $bs = $this->getBranchSetup();

        $content = '
        <!-- ORG HEADER -->
        <table cellpadding="2">
            <tr>
                <td width="100%" style="text-align:center;">
                    <span style="font-size:13pt;font-weight:bold;">' . $bs['orgname'] . '</span><br/>
                    <span style="font-size:9pt;font-style:italic;">' . $bs['orgaddress'] . '</span><br/>
                    <span style="font-size:9pt;font-style:italic;">Tel No. ' . $bs['orgtelno'] . '</span>
                </td>
            </tr>
        </table>

        <br/>

        <!-- TITLE BAR -->
        <table cellpadding="0">
            <tr>
                <td width="100%" style="background-color:#1e3a5f;color:white;text-align:center;font-size:13pt;font-weight:bold;padding:8px;border-radius:4px;">
                    CHECK CONFIRMATION
                </td>
            </tr>
        </table>

        <br/>

        <!-- CHECK BODY BORDER -->
        <table cellpadding="8" style="border:2px solid #1e3a5f;border-radius:4px;">

            <!-- Date row -->
            <tr>
                <td width="70%"></td>
                <td width="30%" style="font-size:9pt;color:#555555;">
                    <span style="font-weight:bold;">Date:</span>&nbsp;' . ($dateStr ?: '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;') . '
                </td>
            </tr>

            <!-- Pay to -->
            <tr>
                <td colspan="2" style="font-size:9pt;padding-top:4px;padding-bottom:2px;">
                    <span style="font-weight:bold;color:#1e3a5f;">PAY TO THE ORDER OF:</span>
                </td>
            </tr>
            <tr>
                <td width="70%" style="border-bottom:1px solid #333333;font-size:12pt;font-weight:bold;padding-bottom:4px;">
                    ' . $checkName . '
                </td>
                <td width="30%" style="border:2px solid #1e3a5f;text-align:center;font-size:13pt;font-weight:bold;padding:6px;">
                    PHP ' . $amtFmt . '
                </td>
            </tr>

            <!-- Amount in words -->
            <tr>
                <td colspan="2" style="font-size:9pt;padding-top:6px;">
                    <span style="font-weight:bold;color:#1e3a5f;">AMOUNT IN WORDS:</span>&nbsp;
                    <span style="font-style:italic;">' . $inWords . ' ONLY</span>
                </td>
            </tr>

            <!-- Spacer -->
            <tr><td colspan="2" style="height:20px;"></td></tr>

            <!-- Signature -->
            <tr>
                <td width="50%" style="text-align:center;">
                    <br/>
                    <span style="border-top:1px solid #333333;padding-top:4px;font-size:9pt;">Authorized Signature</span>
                </td>
                <td width="50%" style="text-align:center;">
                    <br/>
                    <span style="border-top:1px solid #333333;padding-top:4px;font-size:9pt;">Received By / Date</span>
                </td>
            </tr>

        </table>

        <br/>

        <!-- FOOTER NOTE -->
        <table cellpadding="4" style="background-color:#f0f4f8;border:1px solid #cccccc;">
            <tr>
                <td style="font-size:8pt;color:#555555;text-align:center;font-style:italic;">
                    This document serves as official confirmation of check issuance. Please keep for your records.
                </td>
            </tr>
        </table>';

        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('check.pdf', 'I');
    }

    // ── LRS / Disclosure ─────────────────────────────────────────────────────
    public function LRSReport($clientno, $loanid) {
        $bs = $this->getBranchSetup();

        // Fetch from tbl_loans with client name from tbl_clientinfo
        $stmt = $this->conn->prepare("
            SELECT l.*, 
                   CONCAT(c.LastName, ', ', c.FirstName, ' ', COALESCE(c.MiddleName, '')) as FULLNAME
            FROM tbl_loans l
            LEFT JOIN tbl_clientinfo c ON c.ClientNo = l.ClientNo
            WHERE l.ClientNo = ? AND l.LoanID = ? 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $loan = $result->fetch_assoc();

        if (!$loan) { echo "Record not found."; return; }

        $fullname    = $loan['FULLNAME'];
        $program     = $loan['Program'] ?? '-';
        $product     = $loan['Product'] ?? '-';
        $loanamt     = number_format(floatval($loan['LoanAmount']), 2);
        $netamt      = number_format(floatval($loan['NetAmount']) > 0 ? floatval($loan['NetAmount']) : floatval($loan['LoanAmount']), 2);
        $interest    = number_format(floatval($loan['Interest']), 2);
        $cbu         = number_format(floatval($loan['CBU']), 2);
        $ef          = number_format(floatval($loan['EF']), 2);
        $mba         = number_format(floatval($loan['MBA']), 2);
        $mode        = $loan['Mode'] ?? '-';
        $term        = $loan['Term'] ?? '-';
        $rate        = $loan['InterestRate'] ?? '0';
        $intcomp     = $loan['IntComputation'] ?? '-';
        $daterel     = $loan['DateRelease'] ? date('m/d/Y', strtotime($loan['DateRelease'])) : '';
        $datemature  = $loan['DateMature'] ? date('m/d/Y', strtotime($loan['DateMature'])) : '';
        $principalamo = $loan['PrincipalAmo'] ?? '0';
        $interestamo  = $loan['InterestAmo'] ?? '0';
        $totalamo     = $loan['TotalAmo'] ?? '0';
        $po           = $loan['PO'] ?? '-';
        $tag          = $loan['Tag'] ?? '-';

        // Calculate total interest for the schedule
        // Try multiple sources to get the correct interest amount
        $totalInterest = floatval($loan['Interest'] ?? 0);
        
        // If Interest field is 0 or empty, calculate from other fields
        if ($totalInterest == 0) {
            $termValue = intval($loan['Term'] ?? 0);
            $interestAmoValue = floatval($interestamo);
            
            // Calculate from InterestAmo × Term
            if ($interestAmoValue > 0 && $termValue > 0) {
                $totalInterest = $interestAmoValue * $termValue;
            } 
            // Or calculate from TotalAmo - LoanAmount
            else {
                $loanAmountValue = floatval($loan['LoanAmount'] ?? 0);
                $totalAmoValue = floatval($totalamo);
                $totalInterest = ($totalAmoValue * $termValue) - $loanAmountValue;
                
                // Ensure interest is not negative
                if ($totalInterest < 0) $totalInterest = 0;
            }
        }

        // Generate payment schedule based on mode and term
        // Pass loan amount and calculated interest to ensure totals match exactly
        $paymentSchedule = $this->generatePaymentSchedule(
            $loan['DateRelease'],
            $loan['Mode'],
            intval($loan['Term'] ?? 0),
            floatval($loan['LoanAmount'] ?? 0),
            $totalInterest,
            floatval($principalamo),
            floatval($interestamo),
            floatval($totalamo)
        );

        $pdf = $this->makePdf('LOAN RELEASE SCHEDULE');
        
        // Add logo at top center
        $logoPath = realpath(__DIR__ . '/../../assets/images/complete-logo.png');
        if ($logoPath && file_exists($logoPath)) {
            $lw = 45;
            $lx = ($pdf->getPageWidth() - $lw) / 2;
            $pdf->Image($logoPath, $lx, 8, $lw, 0, 'PNG', '', '', true, 150);
            $pdf->SetY(8 + 18);
        }

        $content = '
        <table>
            <tr><td width="25%"></td><td width="50%"><p style="font-weight:bold;text-align:center;font-size:12pt;">' . $bs['orgname'] . '</p></td><td width="25%"></td></tr>
            <tr><td width="25%"></td><td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">' . $bs['orgaddress'] . '</p></td><td width="25%"></td></tr>
            <tr><td width="25%"></td><td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">Tel No. ' . $bs['orgtelno'] . '</p></td><td width="25%"></td></tr>
            <tr><td height="10px"></td></tr>
            <tr><td width="100%" style="font-size:13pt;font-weight:bold;text-align:center;"><p>LOAN RELEASE SCHEDULE</p></td></tr>
            <tr><td width="100%" style="font-size:9pt;text-align:center;font-style:italic;"><p>Disclosure Statement</p></td></tr>
            <tr><td height="10px"></td></tr>
        </table>
        <table style="font-size:9pt;" cellpadding="3">
            <tr>
                <td width="25%" style="font-weight:bold;">Borrower:</td>
                <td width="75%">' . $fullname . '</td>
            </tr>
            <tr>
                <td width="25%" style="font-weight:bold;">Program:</td>
                <td width="75%">' . $program . '</td>
            </tr>
            <tr>
                <td width="25%" style="font-weight:bold;">Product:</td>
                <td width="75%">' . $product . '</td>
            </tr>
            <tr>
                <td width="25%" style="font-weight:bold;">Loan Officer:</td>
                <td width="75%">' . $po . '</td>
            </tr>
            <tr>
                <td width="25%" style="font-weight:bold;">Tag:</td>
                <td width="75%">' . $tag . '</td>
            </tr>
        </table>
        <br/>
        <table style="font-size:9pt;border:1px solid black;" cellpadding="4">
            <tr style="font-weight:bold;background-color:#eeeeee;">
                <td width="50%" style="border:1px solid black;text-align:center;">LOAN DETAILS</td>
                <td width="50%" style="border:1px solid black;text-align:center;">AMORTIZATION</td>
            </tr>
            <tr>
                <td width="50%" style="border:1px solid black;">
                    <table style="font-size:9pt;" cellpadding="2">
                        <tr><td width="55%">Loan Amount:</td><td width="45%" style="text-align:right;">' . $loanamt . '</td></tr>
                        <tr><td width="55%">Interest:</td><td width="45%" style="text-align:right;">' . $interest . '</td></tr>
                        <tr><td width="55%">CBU:</td><td width="45%" style="text-align:right;">' . $cbu . '</td></tr>
                        <tr><td width="55%">EF:</td><td width="45%" style="text-align:right;">' . $ef . '</td></tr>
                        <tr><td width="55%">MBA:</td><td width="45%" style="text-align:right;">' . $mba . '</td></tr>
                        <tr><td width="55%" style="font-weight:bold;">Net Amount:</td><td width="45%" style="text-align:right;font-weight:bold;">' . $netamt . '</td></tr>
                    </table>
                </td>
                <td width="50%" style="border:1px solid black;">
                    <table style="font-size:9pt;" cellpadding="2">
                        <tr><td width="55%">Principal:</td><td width="45%" style="text-align:right;">' . $principalamo . '</td></tr>
                        <tr><td width="55%">Interest:</td><td width="45%" style="text-align:right;">' . $interestamo . '</td></tr>
                        <tr><td width="55%" style="font-weight:bold;">Total:</td><td width="45%" style="text-align:right;font-weight:bold;">' . $totalamo . '</td></tr>
                    </table>
                </td>
            </tr>
        </table>
        <br/>
        <table style="font-size:9pt;" cellpadding="3">
            <tr>
                <td width="25%" style="font-weight:bold;">Mode:</td><td width="25%">' . $mode . '</td>
                <td width="25%" style="font-weight:bold;">Term:</td><td width="25%">' . $term . '</td>
            </tr>
            <tr>
                <td width="25%" style="font-weight:bold;">Interest Rate:</td><td width="25%">' . $rate . '%</td>
                <td width="25%" style="font-weight:bold;">Int. Computation:</td><td width="25%">' . $intcomp . '</td>
            </tr>
            <tr>
                <td width="25%" style="font-weight:bold;">Date Released:</td><td width="25%">' . $daterel . '</td>
                <td width="25%" style="font-weight:bold;">Date Matured:</td><td width="25%">' . $datemature . '</td>
            </tr>
        </table>
        <br/>
        
        <!-- PAYMENT SCHEDULE TABLE -->
        <table style="font-size:9pt;border:1px solid black;" cellpadding="4">
            <tr style="font-weight:bold;background-color:#1e3a5f;color:white;">
                <td colspan="5" style="border:1px solid black;text-align:center;padding:6px;">PAYMENT SCHEDULE (LAPSING SCHEDULE)</td>
            </tr>
            <tr style="font-weight:bold;background-color:#eeeeee;">
                <td width="10%" style="border:1px solid black;text-align:center;">No.</td>
                <td width="25%" style="border:1px solid black;text-align:center;">Due Date</td>
                <td width="20%" style="border:1px solid black;text-align:center;">Principal</td>
                <td width="20%" style="border:1px solid black;text-align:center;">Interest</td>
                <td width="25%" style="border:1px solid black;text-align:center;">Total Payment</td>
            </tr>
            ' . $paymentSchedule . '
        </table>
        <br/><br/>
        <table style="font-size:9pt;" cellpadding="3">
            <tr>
                <td width="33%" style="text-align:center;border-top:1px solid black;">' . $bs['aasig'] . '<br/>Prepared By</td>
                <td width="33%" style="text-align:center;border-top:1px solid black;">' . $bs['bksig'] . '<br/>Checked By</td>
                <td width="33%" style="text-align:center;border-top:1px solid black;">' . $bs['bmsig'] . '<br/>Approved By</td>
            </tr>
            <tr><td height="30px"></td></tr>
            <tr>
                <td width="50%" style="text-align:center;border-top:1px solid black;">' . $fullname . '<br/>Borrower Signature</td>
                <td width="50%"></td>
            </tr>
        </table>';

        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('lrs.pdf', 'I');
    }
}