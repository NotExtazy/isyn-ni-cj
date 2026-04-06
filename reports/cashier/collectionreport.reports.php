<?php
require_once('../../assets/tcpdf/tcpdf.php');

class Reports extends Database 
{
    public function CollectionReport($crdata,$date,$encoder,$from,$to){
        ini_set('memory_limit','-1');
        set_time_limit(0);

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
    
        foreach ($crdata as $row) {
            $datainsert1 .= '<tr>
                    <td width="10%" style="text-align:left; border-left: 1px solid black;">'.$row[0].'</td>
                    <td width="30%" style="text-align:left;">'.$row[1].'</td>
                    <td width="10%" style="text-align:right;">'.$row[2].'</td>
                    <td width="10%" style="text-align:right;">'.$row[3].'</td>
                    <td width="10%" style="text-align:right;">'.$row[4].'</td>
                    <td width="10%" style="text-align:right;">'.$row[5].'</td>
                    <td width="10%" style="text-align:right;">'.$row[6].'</td>
                    <td width="10%" style="text-align:right; border-right: 1px solid black;">'.$row[7].'</td>
                </tr>';

            $totalprincipal += str_replace(",", "",$row[2]);
            $totalinterest += str_replace(",", "",$row[3]);
            $totalcbu += str_replace(",", "",$row[4]);
            $totalpenalty += str_replace(",", "",$row[5]);
            $totalmba += str_replace(",", "",$row[6]);
            $grandtotal += str_replace(",", "",$row[7]);
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
                        <td width="100%"><table border = "0">
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
                                    <td style="width: 15%; font-size: 10pt; border-bottom: 1px solid black;" align="left"><b>Collection Report</b></td>
                                    <td style="width: 85%;"></td>
                                </tr>
                                <tr>
                                    <td style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <td style="width: 13%;"><b>Date</b></td>
                                    <td style="width: 87%;">'.$date.'</td>
                                </tr>
                                <tr>
                                    <td style="width: 13%;"><b>Project Officer</b></td>
                                    <td style="width: 87%;">'.$encoder.'</td>
                                </tr>
                                <tr>
                                    <td style="width: 13%;"><b>OR Series</b></td>
                                    <td style="width: 87%;">'.$from.' - '.$to.'</td>
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
                        <td width="10%" style="font-size: 10pt; font-weight: bold; text-align:left; border: 1px solid black;">ORNo</td>
                        <td width="30%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Client Name</td>
                        <td width="10%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">Principal</td>
                        <td width="10%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">Interest</td>
                        <td width="10%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">CBU</td>
                        <td width="10%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">Penalty</td>
                        <td width="10%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">MBA</td>
                        <td width="10%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">Total</td>
                    </tr>
                    '.$datainsert1.'
                    <tr>
                        <td width="40%" style="font-size: 8pt; font-weight: bold; text-align:center; border: 1px solid black;">Other Payments: '.number_format($grandtotal, 2).'</td>
                        <td width="10%" style="font-size: 8pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($totalprincipal, 2).'</td>
                        <td width="10%" style="font-size: 8pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($totalinterest, 2).'</td>
                        <td width="10%" style="font-size: 8pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($totalcbu, 2).'</td>
                        <td width="10%" style=" font-size: 8pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($totalpenalty, 2).'</td>
                        <td width="10%" style=" font-size: 8pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($totalmba, 2).'</td>
                        <td width="10%" style=" font-size: 8pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($grandtotal, 2).'</td>
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