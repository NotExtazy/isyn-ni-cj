<?php
require_once('../../assets/tcpdf/tcpdf.php');

class Reports extends Database 
{
    public function TellerProofSheet($billcoins,$TotalUndepPrev,$TotalCollections,$TotalDeposit,$TotalUndepDayEnd,$schedA,$schedB,$schedC,$todayUndep,$cdate){
        ini_set('memory_limit','-1');
        set_time_limit(0);

        $encoder = $_SESSION['USERNAME'];

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','BRANCHNAME','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';
        $branchname = '';

        $cashiersig = $_SESSION['FULLNAME'];
        $aasig = '';
        $bksig = '';
        $bmsig = '';

        while ($bsrow = $bsresult->fetch_assoc()) {
            switch ($bsrow['ConfigName']) {
                case 'ORGNAME':
                    $orgname = $bsrow["Value"];
                    break;
                case 'BRANCHADDRESS':
                    $orgaddress = $bsrow["Value"];
                    break;
                case 'BRANCHTELNO':
                    $orgtelno = $bsrow["Value"];
                    break;
                case 'BRANCHNAME':
                    $branchname = $bsrow["Value"];
                    break;
                case 'AASIG':
                    $aasig = $bsrow["Value"];
                    break;
                case 'BKSIG':
                    $bksig = $bsrow["Value"];
                    break;
                case 'BMSIG':
                    $bmsig = $bsrow["Value"];
                    break;
            }
        }
        $branchsetup->close();

        $datacontent = '';
        $datainsert1 = '';

        $totalprincipal = 0;
        $totalinterest = 0;
        $totalcbu = 0;
        $totalpenalty = 0;
        $totalmba = 0;
        $grandtotal = 0;

        $undepprev = 0;
        $totalcollections = 0;
        $deposit = 0;
        $undepend = 0;

        $scheduleAdata = "";
        $scheduleBdata = "";

        foreach ($schedA as $row) {
            $scheduleAdata .= '<tr>
                <td width="20%">' . $row[0] . '</td>
                <td width="20%">' . $row[1] . '</td>
                <td width="20%">' . $row[2] . '</td>
                <td width="20%">' . $row[3] . '</td>
                <td width="20%">' . $row[4] . '</td>
            </tr>';
        }

        foreach ($schedB as $row) {
            $scheduleBdata .= '<tr>
                <td width="25%">' . $row[0] . '</td>
                <td width="25%">' . $row[1] . '</td>
                <td width="25%">' . $row[2] . '</td>
                <td width="25%">' . $row[3] . '</td>
            </tr>';
        }

        // create new PDF document
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        // set document information
        $pdf->SetCreator('iSynergies');
        $pdf->SetAuthor('iSynergies');
        $pdf->SetTitle('CASH RECEIPT');
        $pdf->SetSubject('BOOK OF ACCOUNTS');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(7, 5, 7);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }
        // set font
        $pdf->SetFont('cid0kr', '', 10);
        $pdf->AddPage();

        $datacontent .= '
                <table border="0">
                    <tr>
                        <td width="100%"><table border="0">
                                <tr>
                                    <td style="width: 80%; font-size: 12pt;" align="left"><b>'.$orgname.'</b></td>
                                    <td style="width: 20%; font-size: 6pt" >Date Printed: '.date('Y-m-d H:i:s').'</td>
                                </tr>
                                <tr>
                                    <td style="width: 100%; font-size: 8pt" align="left">'.$orgaddress.'</td>
                                </tr>
                                <tr>
                                    <td style="width: 6%; font-size: 8pt" align="left">Tel No.</td>
                                    <td style="width: 94%; font-size: 8pt" align="left">'.$orgtelno.'</td>
                                </tr>                                
                                <tr>
                                    <td style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <td style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <td style="width: 15%; font-size: 10pt; border-bottom: 1px solid black;" align="left"><b><i>Teller\'s Proofsheet</i></b></td>
                                    <td style="width: 85%;"></td>
                                </tr>
                                <tr>
                                    <td style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <td style="width: 13%;"><b>Teller:</b></td>
                                    <td style="width: 87%;"><b>'.$encoder.'</b></td>
                                </tr>
                                <tr>
                                    <td style="width: 13%;"><b>Date:</b></td>
                                    <td style="width: 87%;"><b>'.$cdate.'</b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table>
                    <tr><td></td></tr>
                </table>

                <table border="0" style="font-size: 8pt;">
                    <tr>
                        <td width="42%"><table style="border: 1px solid black;text-align:center;line-height:20px;font-size:7pt;font-weight:bold;">                    
                                <tr>
                                    <td colspan="7" style="text-align:left; border: 1px solid black">CASH COUNT</td>
                                </tr>
                                <tr>
                                    <td width="2%"></td>
                                    <td width="98%" style="text-align:left;">Bills</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">1,000.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input1000'].'</td>
                                    <td width="10%" style="text-align:left;">PHP</td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total1000'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">500.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input500'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total500'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">200.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input200'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total200'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">100.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input100'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total100'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">50.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input50'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total50'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">20.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input20'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total20'].'</td>
                                </tr>
                                <tr>
                                    <td width="2%"></td>
                                    <td width="98%" style="text-align:left;">Coins</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">20.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input20_coin'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total20_coin'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">10.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input10'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total10'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">5.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input5'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total5'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">1.00</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input1'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total1'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">0.50</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input0_50'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total0_50'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">0.25</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input0_25'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total0_25'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">0.05</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input0_05'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="text-align:right;">'.$billcoins['total0_05'].'</td>
                                </tr>
                                <tr>
                                    <td width="10%"></td>
                                    <td width="15%" style="text-align:right;">0.01</td>
                                    <td width="10%" style="text-align:left;">x</td>
                                    <td width="10%" style="text-align:left;">'.$billcoins['input0_01'].'</td>
                                    <td width="10%" style="text-align:left;"></td>
                                    <td width="20%" style="border-bottom: 1px solid black;text-align:right;">'.$billcoins['total0_01'].'</td>
                                </tr>
                                <tr><td style="line-height:20px;"></td></tr>
                                <tr>
                                    <td width="5%"></td>
                                    <td width="50%" style="text-align:left;">Total Cash</td>
                                    <td width="20%" style="border-bottom: 1px solid black;text-align:right;">'.$billcoins['totalBills'].'</td>
                                </tr>
                                <tr>
                                    <td width="5%"></td>
                                    <td width="50%" style="text-align:left;">Total Checks</td>
                                    <td width="20%" style="border-bottom: 1px solid black;text-align:right;">'.$billcoins['totalChecks'].'</td>
                                </tr>
                                <tr><td style="line-height:15px;"></td></tr>
                                <tr>
                                    <td width="5%"></td>
                                    <td width="50%" style="text-align:left;">Grand Total</td>
                                    <td width="20%" style="border-bottom: 1px solid black;text-align:right;">'.$billcoins['grandTotal'].'</td>
                                </tr>
                                <tr><td style="line-height:15px;"></td></tr>
                            </table>
                        </td>

                        <td width="58%">
                            <table style="border: 1px solid black;text-align:center;line-height:20px;font-size:7pt;">                                
                                <tr>
                                    <td width="100%" style="text-align:left;font-weight:bold;">DEBITS</td>
                                </tr>
                                <tr>
                                    <td width="4%"></td>
                                    <td width="66%" style="text-align:left;">Undeposited Collections(Beginning)</td>
                                    <td width="30%" style="text-align:right;font-weight:bold;">'.number_format($TotalUndepPrev, 2).'</td>
                                </tr>
                                <tr>
                                    <td width="4%"></td>
                                    <td width="66%" style="text-align:left;">Collections (See Schedule A)</td>
                                    <td width="30%"></td>
                                </tr>
                                <tr>
                                    <td width="6%"></td>
                                    <td width="64%" style="text-align:left;">Total DEBITS</td>
                                    <td width="30%" style="text-align:right;font-weight:bold;">'.number_format($TotalCollections, 2).'</td>
                                </tr>
                                <tr>
                                    <td width="100%" style="text-align:left;font-weight:bold;">CREDITS</td>
                                </tr>
                                <tr>
                                    <td width="4%"></td>
                                    <td width="66%" style="text-align:left;">Deposits(See Schedule A)</td>
                                    <td width="30%" style="text-align:right;font-weight:bold;border-bottom: 1px solid black;">'.number_format($TotalDeposit, 2).'</td>
                                </tr>
                                <tr>
                                    <td width="4%"></td>
                                    <td width="66%" style="text-align:left;">Undeposited Collections (End)</td>
                                    <td width="30%" style="text-align:right;font-weight:bold;">'.number_format($TotalUndepDayEnd, 2).'</td>
                                </tr>
                            </table>

                            <table>
                                <tr><td></td></tr>
                            </table>

                            <table border="1" style="text-align:center;line-height:15px;font-size:7pt;font-weight:bold;">
                                <tr>
                                    <td width="100%" style="text-align:left;">  SCHEDULE A - COLLECTIONS</td>
                                </tr>
                                <tr>
                                    <td width="20%"></td>
                                    <td width="20%">Collections</td>
                                    <td width="20%">Undeposited Previous Day</td>
                                    <td width="20%">Deposit</td>
                                    <td width="20%">Undeposited End Day</td>
                                </tr>
                                '.$scheduleAdata.'
                            </table>
                            <table>
                                <tr><td></td></tr>
                            </table>
                            <table border="1" style="text-align:center;line-height:15px;font-size:7pt;font-weight:bold;">
                                <tr>
                                    <td width="100%" style="text-align:left;">  SCHEDULE B - CHECKS</td>
                                </tr>
                                <tr>
                                    <td width="25%">Check No.</td>
                                    <td width="25%">Bank</td>
                                    <td width="25%">Bank Branch</td>
                                    <td width="25%">Amount</td>
                                </tr>
                                '.$scheduleBdata.'
                            </table>
                        </td>

                    </tr>
                </table>

                <table border="0">
                    <tr><td></td></tr>
                    <tr><td></td></tr>
                </table>

                <table border="0" cellpadding="3">
                    <tr>
                        <td>Prepared By:</td>
                        <td>Checked By:</td>
                        <td>Approved By:</td>
                    </tr>
                    <tr>
                        <td width="2%"></td>
                        <td width="30%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$cashiersig.'</b></td>
                        <td width="2%"></td>
                        <td width="30%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bksig.'</b></td>
                        <td width="2%"></td>
                        <td width="32%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bmsig.'</b></td>
                        <td width="2%"></td>
                    </tr>
                    <tr>
                        <td width="33%">Cashier</td>
                        <td width="34%">Bookkeeper</td>
                        <td width="33%">General Manager</td>
                    </tr>
                </table>
        ';

        // output the HTML content
        $pdf->writeHTML($datacontent, true, false, true, false, '');
        // reset pointer to the last page
        $pdf->lastPage();
        //Close and output PDF document
        $pdf->Output(('Collection Report-'.time().'.pdf'), 'I');
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