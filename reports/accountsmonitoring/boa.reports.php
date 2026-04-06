<?php
require_once('../../assets/tcpdf/tcpdf.php');
require_once('../../assets/PHPSpreadsheet/vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Color;

class BOAHEADER extends TCPDF {
    public $report;
    public $type;
    public $orgname;
    public $orgaddress;
    public $orgtelno;
    public $branchname;
    public $fund;
    public $fromdate;
    public $todate;

    // Custom header
    public function Header() {
        $this->SetFont('helvetica', '', 8);

        $html = '
        <table border="0" cellpadding="1">
            <tr>
                <td style="width: 60%; font-size: 12pt;" align="left"><b>'.$this->orgname.'</b></td>
                <td style="width: 40%; font-size: 6pt; text-align: right;">
                    '.date('m/d/Y h:i:s A').' by '.$_SESSION['USERNAME'].' | Page '.$this->getAliasNumPage().' of '.$this->getAliasNbPages().'
                </td>
            </tr>
            <tr>
                <td style="width: 100%; font-size: 8pt" align="left">'.$this->orgaddress.'</td>
            </tr>
            <tr>
                <td style="width: 100%; font-size: 8pt" align="left">'.$this->orgtelno.'</td>
            </tr>
            <tr>
                <td style="width: 70%;"></td>
                <td style="width: 10%;">Branch</td>
                <td style="width: 20%;"><b>'.$this->branchname.'</b></td>
            </tr>
            <tr>
                <td style="width: 70%;"></td>
                <td style="width: 10%;">Fund</td>
                <td style="width: 20%;"><b>'.$this->fund.'</b></td>
            </tr>
            <tr>
                <td style="width: 70%;"></td>
                <td style="width: 10%;">Date</td>
                <td style="width: 20%;"><b>'.$this->fromdate.' - '.$this->todate.'</b></td>
            </tr>
            <tr>
                <td style="width: 100%; font-size: 10pt" align="left"><b>'.$this->report.'</b></td>
            </tr>
            <tr>
                <td style="width: 100%; font-size: 10pt"></td>
            </tr>
        </table>
        ';

        if ($this->type == 'CRB'){
            $html .= '
                <table border="0" style="font-size: 8pt;text-align: center;" cellpadding="3">
                    <tr style="font-weight: bold;">
                        <td width="8%" style="font-weight: bold; border: 1px solid black;"></td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Date</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">OR No.</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Account No</td>
                        <td width="32%" style="font-weight: bold; border: 1px solid black;">Account Title / Particulars</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Subsidiary</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Debit</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Credit</td>
                    </tr>
                </table>
            ';
        } else if ($this->type == 'JV') {
            $html .= '
                <table border="0" style="font-size: 8pt;text-align: center;" cellpadding="3">
                    <tr style="font-weight: bold;">
                        <td width="8%" style="font-weight: bold; border: 1px solid black;"></td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Date</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">JV No.</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Account No</td>
                        <td width="32%" style="font-weight: bold; border: 1px solid black;">Account Title / Particulars</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Subsidiary</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Debit</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Credit</td>
                    </tr>
                </table>
            ';
        } else if ($this->type == 'CDB') {
            $html .= '
                <table border="0" style="font-size: 8pt;text-align: center;" cellpadding="3">
                    <tr style="font-weight: bold;">
                        <td width="8%" style="font-weight: bold; border: 1px solid black;"></td>
                        <td width="8%" style="font-weight: bold; border: 1px solid black;">Date</td>
                        <td width="8%" style="font-weight: bold; border: 1px solid black;">CV No.</td>
                        <td width="8%" style="font-weight: bold; border: 1px solid black;">Check No.</td>
                        <td width="8%" style="font-weight: bold; border: 1px solid black;">Account No</td>
                        <td width="30%" style="font-weight: bold; border: 1px solid black;">Account Title / Particulars</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Subsidiary</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Debit</td>
                        <td width="10%" style="font-weight: bold; border: 1px solid black;">Credit</td>
                    </tr>
                </table>
            ';
        }
        
        // Write the HTML header
        $this->writeHTML($html, true, false, false, false, '');
    }
}

class Reports extends Database 
{
    public function BOADetailedCRBPDF(){
        ini_set('memory_limit','-1');
        set_time_limit(0);

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','BRANCHNAME','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';
        $branchname = '';

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

        $fromdate = $_SESSION["PRINTFROMDATE"];
        $todate = $_SESSION["PRINTTODATE"];
        $booktype = $_SESSION["PRINTBOOKTYPE"];
        $fund = $_SESSION["PRINTFUND"];

        $datacontent = '';
        $summarycontent = '';
        $datainsert1 = '';
        $summary = '';
        $datainsert2 = '';
        $datainsert3 = '';

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'CRB' AND Fund = '".$fund."' AND  STR_TO_DATE(CDate,'%Y-%m-%d') >= STR_TO_DATE('".date("Y-m-d",strtotime($fromdate))."','%Y-%m-%d') AND STR_TO_DATE(CDate,'%Y-%m-%d') <= STR_TO_DATE('".date("Y-m-d",strtotime($todate))."','%Y-%m-%d')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $CurrJVNo = "";
        $Explanation = "";
        while ($row = $result->fetch_assoc()) {

            if($CurrJVNo != $row["ORNo"]){
                if($Explanation != ""){
                    $datainsert1 .= '
                        <tr>
                            <td width="8%" style="font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="32%" style="font-size: 8pt;text-align: left; border-bottom: 1px solid black"><b>'.$Explanation.'</b></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-right: 1px solid black; border-bottom: 1px solid black;"></td>
                        </tr>
                    ';
                }
            }

            if($CurrJVNo != $row["ORNo"]){
                $CurrJVNo = $row["ORNo"];
                $Explanation = $row["Explanation"];

                $SLDrCrA = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther1 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther1 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datainsert1 .= '
                    <tr>
                        <td width="8%" style="border: 1px solid black;"><b>Payee</b></td>
                        <td width="92%" colspan="7" style="border: 1px solid black; text-align: left;"><b>'.$row["Payee"].'</b></td>
                    </tr>
                    <tr>
                        <td width="8%" style="border-left: 1px solid black;">'.$row["BookPage"].'</td>
                        <td width="10%">'.$row["CDate"].'</td>
                        <td width="10%" style="font-weight: bold;">'.$row["ORNo"].'</td>
                        <td width="10%">'.$row["AcctNo"].'</td>
                        <td width="32%" style="font-size: 8pt;text-align: left;">'.$row["AcctTitle"].'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$SLDrCrA.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$DrOther1.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right; border-right: 1px solid black;">'.$CrOther1.'</td>
                    </tr>
                ';
            }else{

                $SLDrCrB = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther2 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther2 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datainsert1 .= '
                    <tr>
                        <td width="8%" style="border-left: 1px solid black;"></td>
                        <td width="10%"></td>
                        <td width="10%"></td>
                        <td width="10%">'.$row["AcctNo"].'</td>
                        <td width="32%" style="font-size: 8pt;text-align: left;">'.$row["AcctTitle"].'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$SLDrCrB.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$DrOther2.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right; border-right: 1px solid black;">'.$CrOther2.'</td>
                    </tr>
                ';
            }
        }

        if($Explanation != ""){
            $datainsert1 .= '
                <tr>
                    <td width="8%" style="font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="32%" style="font-size: 8pt;text-align: left; border-bottom: 1px solid black"><b>'.$Explanation.'</b></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-right: 1px solid black; border-bottom: 1px solid black;"></td>
                </tr>
            ';
        }

        // Summary ===================================================

        $squery = "SELECT * FROM tbl_books WHERE BookType = 'CRB' AND ";

        $DateField = "STR_TO_DATE(CDate,'%Y-%m-%d')";
        $From = "STR_TO_DATE('" . date("Y-m-d",strtotime($fromdate)) . "','%Y-%m-%d')";
        $To = "STR_TO_DATE('" . date("Y-m-d",strtotime($todate)) . "','%Y-%m-%d')";
        $squery = $squery . $DateField . " >= " . $From . " AND " . $DateField . " <= " . $To;
        if ($fund != "CONSOLIDATED") {
            $squery = $squery . " AND Fund = '$fund'";
        }

        $GroupQuery = str_replace("*", "ACCTTITLE, SUM(DROTHER) as TOTALDR, SUM(CROTHER) as TOTALCR",$squery);
        $GroupQuery = $GroupQuery . " AND LEFT(ACCTTITLE,1) <> ' ' GROUP BY ACCTTITLE";

        $stmt = $this->conn->prepare($GroupQuery);
        $stmt->execute();
        $GroupResult = $stmt->get_result();
        $stmt->close();

        $fTtlDR = 0;
        $fTtlCR = 0;

        while ($GroupRow = $GroupResult->fetch_assoc()) {
            $summary .= '
                <tr>
                    <td width="38%" style="text-align:left; border-left: 1px solid black;">'.$GroupRow["ACCTTITLE"].'</td>
                    <td width="16%" style="font-weight: bold; text-align:right;  border-left: 1px solid black;  border-right: 1px solid black;">'.number_format($GroupRow["TOTALDR"], 2).'</td>
                    <td width="16%" style="font-weight: bold; text-align:right;  border-left: 1px solid black; border-right: 1px solid black;">'.number_format($GroupRow["TOTALCR"], 2).'</td>
                </tr>
            ';

            $fTtlDR += $GroupRow["TOTALDR"];
            $fTtlCR += $GroupRow["TOTALCR"];
        }

        // create new PDF document
        // $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf = new BOAHEADER('L', PDF_UNIT, array(216, 279), true, 'UTF-8', false);
        // Header Details
        $pdf->report      = "CASH RECEIPT / PAYMENTS JOURNAL";
        $pdf->type        = "CRB";
        $pdf->orgname     = $orgname;
        $pdf->orgaddress  = $orgaddress;
        $pdf->orgtelno    = $orgtelno;
        $pdf->branchname  = $branchname;
        $pdf->fund        = $fund;
        $pdf->fromdate    = $fromdate;
        $pdf->todate      = $todate;
        // set document information
        $pdf->SetCreator('iSynergies');
        $pdf->SetAuthor('iSynergies');
        $pdf->SetTitle('CASH RECEIPT');
        $pdf->SetSubject('BOOK OF ACCOUNTS');
        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(false);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(7, 47, 7);
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
                
                <table border="0" style="font-size: 8pt;text-align: center;" cellpadding="3">
                    '.$datainsert1.'
                </table>

                <table border="0">
                    <tr><td></td></tr>
                    <tr><td></td></tr>
                </table>
        ';

        $pdf->writeHTML($datacontent, true, false, true, false, '');

        $pdf->SetPrintHeader(false);
        $pdf->SetMargins(7, 7, 7);
        $pdf->AddPage();

        $summarycontent .='

                <table border="0" style="font-size: 8pt;">
                    <tr>
                        <td width="38%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Summary of Accounts</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Debits</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Credits</td>
                    </tr>
                    '.$summary.'
                    <tr>
                        <td width="38%" style="font-size: 10pt; font-weight: bold; text-align:left; border: 1px solid black;">Totals</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($fTtlDR, 2).'</td>
                        <td width="16%" style=" font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($fTtlCR, 2).'</td>
                    </tr>
                </table>

                <table border="0">
                    <tr><td></td></tr>
                    <tr><td></td></tr>
                </table>

                <table border="0" cellpadding="3">
                    <tr>
                        <td width="23%">Prepared By:</td>
                        <td width="23%">Checked By:</td>
                        <td width="23%">Approved By:</td>
                    </tr>
                    <tr>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$aasig.'</b></td>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bksig.'</b></td>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bmsig.'</b></td>
                        <td width="1%"></td>
                    </tr>
                    <tr>
                        <td width="23%">Accounting Assistant</td>
                        <td width="23%">Bookkeeper</td>
                        <td width="23%">General Manager</td>
                    </tr>
                </table>
        ';

        // output the HTML content
        $pdf->writeHTML($summarycontent, true, false, true, false, '');
        // reset pointer to the last page
        $pdf->lastPage();
        //Close and output PDF document
        $pdf->Output(('Cash Receipt Detailed-'.time().'.pdf'), 'I');
    }

    public function BOADetailedCRBEXCEL(){
        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','BRANCHNAME','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';
        $branchname = '';

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

        $fromdate = $_SESSION["PRINTFROMDATE"];
        $todate = $_SESSION["PRINTTODATE"];
        $booktype = $_SESSION["PRINTBOOKTYPE"];
        $fund = $_SESSION["PRINTFUND"];

        $datacontent = [];        

        $datacontent[] = array($orgname);
        $datacontent[] = array($orgaddress);
        $datacontent[] = array($orgtelno);
        $datacontent[] = array("","","","","","","Branch",$branchname);
        $datacontent[] = array("","","","","","","Fund",$fund);
        $datacontent[] = array("","","","","","","Date",$fromdate);
        $datacontent[] = array("GENERAL JOURNAL");
        $datacontent[] = array("");
        $datacontent[] = array("","Date","JV No.","Account No","Account Title / Particulars","Subsidiary","Debit","Credit");

        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getStyle("A9:H9")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'CRB' AND Fund = '".$fund."' AND  STR_TO_DATE(CDate,'%Y-%m-%d') >= STR_TO_DATE('".date("Y-m-d",strtotime($fromdate))."','%Y-%m-%d') AND STR_TO_DATE(CDate,'%Y-%m-%d') <= STR_TO_DATE('".date("Y-m-d",strtotime($todate))."','%Y-%m-%d')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $CurrJVNo = "";
        $Explanation = "";
        $RowCount = 10;
        while ($row = $result->fetch_assoc()) {

            if($CurrJVNo != $row["ORNo"]){
                if($Explanation != ""){
                    $datacontent[] = array("","","","",$Explanation,"","","");

                    $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                    // $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                    $RowCount++;
                }
            }

            if($CurrJVNo != $row["ORNo"]){
                $CurrJVNo = $row["ORNo"];
                $Explanation = $row["Explanation"];

                $SLDrCrA = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther1 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther1 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                $datacontent[] = array("Payee","","","",$row["Payee"],"","","");

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $RowCount++;
                
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                $datacontent[] = array($row["BookPage"],$row["CDate"],$row["JVNo"],$row["AcctNo"],$row["AcctTitle"],$SLDrCrA,$DrOther1,$CrOther1);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $RowCount++;
            }else{

                $SLDrCrB = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther2 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther2 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datacontent[] = array("","","",$row["AcctNo"],$row["AcctTitle"],$SLDrCrB,$DrOther2,$CrOther2);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $RowCount++;
            }
        }

        if($Explanation != ""){
            $datacontent[] = array("","","","",$Explanation,"","","");

            $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            // $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

            $RowCount++;
        }

        $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        $datacontent[] = array("","","","","","","","");
        $RowCount++;

        // ===================
        $datacontent[] = array("","","","","Summary of Accounts","Debits","Credits","");
        $summaryHeadCount = $RowCount;
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getFont()->setBold(true);
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $RowCount++;

        $smryTtlDR = 0;
        $smryTtlCR = 0;
        
        $squery = "SELECT * FROM tbl_books WHERE BookType = 'GJ' AND ";

        $DateField = "STR_TO_DATE(CDate,'%Y-%m-%d')";
        $From = "STR_TO_DATE('" . date("Y-m-d",strtotime($fromdate)) . "','%Y-%m-%d')";
        $To = "STR_TO_DATE('" . date("Y-m-d",strtotime($todate)) . "','%Y-%m-%d')";
        $squery = $squery . $DateField . " >= " . $From . " AND " . $DateField . " <= " . $To;
        if ($fund != "CONSOLIDATED") {
            $squery = $squery . " AND Fund = '$fund'";
        }

        $GroupQuery = str_replace("*", "ACCTTITLE, SUM(DROTHER) as TOTALDR, SUM(CROTHER) as TOTALCR",$squery);
        $GroupQuery = $GroupQuery . " AND LEFT(ACCTTITLE,1) <> ' ' GROUP BY ACCTTITLE";

        $stmt = $this->conn->prepare($GroupQuery);
        $stmt->execute();
        $GroupResult = $stmt->get_result();
        $stmt->close();

        while ($GroupRow = $GroupResult->fetch_assoc()) {
            $datacontent[] = array("","","","",$GroupRow["ACCTTITLE"],number_format($GroupRow["TOTALDR"], 2),number_format($GroupRow["TOTALCR"], 2),"");
            
            $sheet->getStyle("E".$RowCount.":E".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("E".$RowCount.":E".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("F".$RowCount.":F".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("G".$RowCount.":G".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

            $RowCount++;

            $smryTtlDR += $GroupRow["TOTALDR"];
            $smryTtlCR += $GroupRow["TOTALCR"];
        }
        
        $datacontent[] = array("","","","","Totals",number_format($smryTtlDR, 2),number_format($smryTtlCR, 2),"");
        $summaryTotalsCount = $RowCount;
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getFont()->setBold(true);
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $RowCount++;
        
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        
        // ===================
        $datacontent[] = array("Prepared By:","","Checked By:","","Approved By:","","","");
        $signCount1 = $RowCount;
        $sheet->mergeCells("A".($signCount1).":B".($signCount1)."");
        $sheet->mergeCells("C".($signCount1).":D".($signCount1)."");
        $sheet->getStyle("A".$signCount1)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount1)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;

        $datacontent[] = array($aasig,"",$bksig,"",$bmsig,"","","");
        $signCount2 = $RowCount;
        $sheet->mergeCells("A".($signCount2).":B".($signCount2)."");
        $sheet->mergeCells("C".($signCount2).":D".($signCount2)."");
        $sheet->getStyle("A".$signCount2)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount2)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;
        
        $datacontent[] = array("Accounting Assistant","","Bookkeeper","","General Manager","","","");
        $signCount3 = $RowCount;
        $sheet->mergeCells("A".($signCount3).":B".($signCount3)."");
        $sheet->mergeCells("C".($signCount3).":D".($signCount3)."");
        $sheet->getStyle("A".$signCount3)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount3)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;

        $sheet->fromArray(
            $datacontent
        );

        $styleArray = [
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
				],
			],
		];

        $sheet->mergeCells("A1:E1");
        $sheet->mergeCells("A2:E2");
        $sheet->mergeCells("A3:C3");
        $sheet->mergeCells("A7:C7");

        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        $sheet->getStyle("A1:D1")->getFont()->setBold(true);
        $sheet->getStyle("A2:A3")->getFont()->setSize(8);
        $sheet->getStyle("A7")->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        // $sheet->getStyle('A6:K7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B4C6E7');
        // $sheet->getStyle("A1:F3")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A9:H9")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("E5:F5")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("A7:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("D7:D".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A10:A".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B10:B".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C10:C".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D10:D".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F10:F".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G10:G".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H10:H".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A10:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->getFont()->setSize(8);

        $sheet->getStyle("E".$summaryHeadCount.":G".$summaryHeadCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E".$summaryTotalsCount.":E".$summaryTotalsCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F".$summaryTotalsCount.":G".$summaryTotalsCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        // $sheet->getStyle("A5:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->applyFromArray($styleArray);

        $sheet->getColumnDimension('A')->setWidth(10,'pt');
        $sheet->getColumnDimension('B')->setWidth(10,'pt');
        $sheet->getColumnDimension('C')->setWidth(10,'pt');
        $sheet->getColumnDimension('D')->setWidth(15,'pt');
        $sheet->getColumnDimension('E')->setWidth(50,'pt');
        $sheet->getColumnDimension('F')->setWidth(15,'pt');
        $sheet->getColumnDimension('G')->setWidth(15,'pt');
        $sheet->getColumnDimension('H')->setWidth(15,'pt');
        // foreach (range('A',$sheet->getHighestDataColumn()) as $col) {
		// 	$sheet->getColumnDimension($col)->setAutoSize(true);
		// }
        
        $filename = "Cash Receipt Detailed-".time().".xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        header('Content-Type: application/x-www-form-urlencoded');
        header('Content-Transfer-Encoding: Binary');
        header("Content-disposition: attachment; filename=\"".$filename."\"");
        readfile($filename);
        unlink($filename);
        exit;
    }
    
    public function BOADetailedGJPDF(){
        ini_set('memory_limit','-1');
        set_time_limit(0);

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','BRANCHNAME','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';
        $branchname = '';

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

        $fromdate = $_SESSION["PRINTFROMDATE"];
        $todate = $_SESSION["PRINTTODATE"];
        $booktype = $_SESSION["PRINTBOOKTYPE"];
        $fund = $_SESSION["PRINTFUND"];

        $datacontent = '';
        $summarycontent = '';
        $datainsert1 = '';
        $summary = '';
        $datainsert2 = '';
        $datainsert3 = '';

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'GJ' AND Fund = '".$fund."' AND  STR_TO_DATE(CDate,'%Y-%m-%d') >= STR_TO_DATE('".date("Y-m-d",strtotime($fromdate))."','%Y-%m-%d') AND STR_TO_DATE(CDate,'%Y-%m-%d') <= STR_TO_DATE('".date("Y-m-d",strtotime($todate))."','%Y-%m-%d')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $CurrJVNo = "";
        $Explanation = "";
        while ($row = $result->fetch_assoc()) {

            if($CurrJVNo != $row["JVNo"]){
                if($Explanation != ""){
                    $datainsert1 .= '
                        <tr>
                            <td width="8%" style="font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="32%" style="font-size: 8pt;text-align: left; border-bottom: 1px solid black"><b>'.$Explanation.'</b></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-right: 1px solid black; border-bottom: 1px solid black;"></td>
                        </tr>
                    ';
                }
            }

            if($CurrJVNo != $row["JVNo"]){
                $CurrJVNo = $row["JVNo"];
                $Explanation = $row["Explanation"];

                $SLDrCrA = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther1 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther1 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datainsert1 .= '
                    <tr>
                        <td width="8%" style="border-left: 1px solid black;">'.$row["BookPage"].'</td>
                        <td width="10%">'.$row["CDate"].'</td>
                        <td width="10%" style="font-weight: bold;">'.$row["JVNo"].'</td>
                        <td width="10%">'.$row["AcctNo"].'</td>
                        <td width="32%" style="font-size: 8pt;text-align: left;">'.$row["AcctTitle"].'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$SLDrCrA.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$DrOther1.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right; border-right: 1px solid black;">'.$CrOther1.'</td>
                    </tr>
                ';
            }else{

                $SLDrCrB = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther2 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther2 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datainsert1 .= '
                    <tr>
                        <td width="8%" style="border-left: 1px solid black;"></td>
                        <td width="10%"></td>
                        <td width="10%"></td>
                        <td width="10%">'.$row["AcctNo"].'</td>
                        <td width="32%" style="font-size: 8pt;text-align: left;">'.$row["AcctTitle"].'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$SLDrCrB.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$DrOther2.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right; border-right: 1px solid black;">'.$CrOther2.'</td>
                    </tr>
                ';
            }
        }

        if($Explanation != ""){
            $datainsert1 .= '
                <tr>
                    <td width="8%" style="font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="32%" style="font-size: 8pt;text-align: left; border-bottom: 1px solid black"><b>'.$Explanation.'</b></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-right: 1px solid black; border-bottom: 1px solid black;"></td>
                </tr>
            ';
        }

        // Summary ===================================================

        $squery = "SELECT * FROM tbl_books WHERE BookType = 'GJ' AND ";

        $DateField = "STR_TO_DATE(CDate,'%Y-%m-%d')";
        $From = "STR_TO_DATE('" . date("Y-m-d",strtotime($fromdate)) . "','%Y-%m-%d')";
        $To = "STR_TO_DATE('" . date("Y-m-d",strtotime($todate)) . "','%Y-%m-%d')";
        $squery = $squery . $DateField . " >= " . $From . " AND " . $DateField . " <= " . $To;
        if ($fund != "CONSOLIDATED") {
            $squery = $squery . " AND Fund = '$fund'";
        }

        $GroupQuery = str_replace("*", "ACCTTITLE, SUM(DROTHER) as TOTALDR, SUM(CROTHER) as TOTALCR",$squery);
        $GroupQuery = $GroupQuery . " AND LEFT(ACCTTITLE,1) <> ' ' GROUP BY ACCTTITLE";

        $stmt = $this->conn->prepare($GroupQuery);
        $stmt->execute();
        $GroupResult = $stmt->get_result();
        $stmt->close();

        $fTtlDR = 0;
        $fTtlCR = 0;

        while ($GroupRow = $GroupResult->fetch_assoc()) {
            $summary .= '
                <tr>
                    <td width="38%" style="text-align:left; border-left: 1px solid black;">'.$GroupRow["ACCTTITLE"].'</td>
                    <td width="16%" style="font-weight: bold; text-align:right;  border-left: 1px solid black;  border-right: 1px solid black;">'.number_format($GroupRow["TOTALDR"], 2).'</td>
                    <td width="16%" style="font-weight: bold; text-align:right;  border-left: 1px solid black; border-right: 1px solid black;">'.number_format($GroupRow["TOTALCR"], 2).'</td>
                </tr>
            ';

            $fTtlDR += $GroupRow["TOTALDR"];
            $fTtlCR += $GroupRow["TOTALCR"];
        }

        // create new PDF document
        $pdf = new BOAHEADER('L', PDF_UNIT, array(216, 279), true, 'UTF-8', false);
        // Header Details
        $pdf->report      = "GENERAL JOURNAL";
        $pdf->type        = "JV";
        $pdf->orgname     = $orgname;
        $pdf->orgaddress  = $orgaddress;
        $pdf->orgtelno    = $orgtelno;
        $pdf->branchname  = $branchname;
        $pdf->fund        = $fund;
        $pdf->fromdate    = $fromdate;
        $pdf->todate      = $todate;
        // set document information
        $pdf->SetCreator('iSynergies');
        $pdf->SetAuthor('iSynergies');
        $pdf->SetTitle('GENERAL JOURNAL');
        $pdf->SetSubject('BOOK OF ACCOUNTS');
        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(false);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(7, 47, 7);
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
                <table border="0" style="font-size: 8pt;text-align: center;" cellpadding="3">
                    '.$datainsert1.'
                </table>
                
                <table border="0">
                    <tr><td></td></tr>
                    <tr><td></td></tr>
                </table>
        ';

        $pdf->writeHTML($datacontent, true, false, true, false, '');

        $pdf->SetPrintHeader(false);
        $pdf->SetMargins(7, 7, 7);
        $pdf->AddPage();

        $summarycontent .='
                <table border="0" style="font-size: 8pt;">
                    <tr>
                        <td width="38%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Summary of Accounts</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Debits</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Credits</td>
                    </tr>
                    '.$summary.'
                    <tr>
                        <td width="38%" style="font-size: 10pt; font-weight: bold; text-align:left; border: 1px solid black;">Totals</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($fTtlDR, 2).'</td>
                        <td width="16%" style=" font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($fTtlCR, 2).'</td>
                    </tr>
                </table>

                <table border="0">
                    <tr><td></td></tr>
                    <tr><td></td></tr>
                </table>

                <table border="0" cellpadding="3">
                    <tr>
                        <td width="23%">Prepared By:</td>
                        <td width="23%">Checked By:</td>
                        <td width="23%">Approved By:</td>
                    </tr>
                    <tr>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$aasig.'</b></td>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bksig.'</b></td>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bmsig.'</b></td>
                        <td width="1%"></td>
                    </tr>
                    <tr>
                        <td width="23%">Accounting Assistant</td>
                        <td width="23%">Bookkeeper</td>
                        <td width="23%">General Manager</td>
                    </tr>
                </table>
        ';

        // output the HTML content
        $pdf->writeHTML($summarycontent, true, false, true, false, '');
        // reset pointer to the last page
        $pdf->lastPage();
        //Close and output PDF document
        $pdf->Output(('General Journal Detailed-'.time().'.pdf'), 'I');
    }

    public function BOADetailedGJEXCEL(){
        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','BRANCHNAME','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';
        $branchname = '';

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

        $fromdate = $_SESSION["PRINTFROMDATE"];
        $todate = $_SESSION["PRINTTODATE"];
        $booktype = $_SESSION["PRINTBOOKTYPE"];
        $fund = $_SESSION["PRINTFUND"];

        $datacontent = [];        

        $datacontent[] = array($orgname);
        $datacontent[] = array($orgaddress);
        $datacontent[] = array($orgtelno);
        $datacontent[] = array("","","","","","","Branch",$branchname);
        $datacontent[] = array("","","","","","","Fund",$fund);
        $datacontent[] = array("","","","","","","Date",$fromdate);
        $datacontent[] = array("GENERAL JOURNAL");
        $datacontent[] = array("");
        $datacontent[] = array("","Date","JV No.","Account No","Account Title / Particulars","Subsidiary","Debit","Credit");

        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getStyle("A9:H9")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'GJ' AND Fund = '".$fund."' AND  STR_TO_DATE(CDate,'%Y-%m-%d') >= STR_TO_DATE('".date("Y-m-d",strtotime($fromdate))."','%Y-%m-%d') AND STR_TO_DATE(CDate,'%Y-%m-%d') <= STR_TO_DATE('".date("Y-m-d",strtotime($todate))."','%Y-%m-%d')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $CurrJVNo = "";
        $Explanation = "";
        $RowCount = 10;
        while ($row = $result->fetch_assoc()) {

            if($CurrJVNo != $row["JVNo"]){
                if($Explanation != ""){
                    $datacontent[] = array("","","","",$Explanation,"","","");

                    $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                    // $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                    $RowCount++;
                }
            }

            if($CurrJVNo != $row["JVNo"]){
                $CurrJVNo = $row["JVNo"];
                $Explanation = $row["Explanation"];

                $SLDrCrA = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther1 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther1 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                $datacontent[] = array($row["BookPage"],$row["CDate"],$row["JVNo"],$row["AcctNo"],$row["AcctTitle"],$SLDrCrA,$DrOther1,$CrOther1);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $RowCount++;
            }else{

                $SLDrCrB = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther2 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther2 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datacontent[] = array("","","",$row["AcctNo"],$row["AcctTitle"],$SLDrCrB,$DrOther2,$CrOther2);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

                $RowCount++;
            }
        }

        if($Explanation != ""){
            $datacontent[] = array("","","","",$Explanation,"","","");

            $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            // $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

            $RowCount++;
        }

        $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        $datacontent[] = array("","","","","","","","");
        $RowCount++;

        // ===================
        $datacontent[] = array("","","","","Summary of Accounts","Debits","Credits","");
        $summaryHeadCount = $RowCount;
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getFont()->setBold(true);
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $RowCount++;

        $smryTtlDR = 0;
        $smryTtlCR = 0;
        
        $squery = "SELECT * FROM tbl_books WHERE BookType = 'GJ' AND ";

        $DateField = "STR_TO_DATE(CDate,'%Y-%m-%d')";
        $From = "STR_TO_DATE('" . date("Y-m-d",strtotime($fromdate)) . "','%Y-%m-%d')";
        $To = "STR_TO_DATE('" . date("Y-m-d",strtotime($todate)) . "','%Y-%m-%d')";
        $squery = $squery . $DateField . " >= " . $From . " AND " . $DateField . " <= " . $To;
        if ($fund != "CONSOLIDATED") {
            $squery = $squery . " AND Fund = '$fund'";
        }

        $GroupQuery = str_replace("*", "ACCTTITLE, SUM(DROTHER) as TOTALDR, SUM(CROTHER) as TOTALCR",$squery);
        $GroupQuery = $GroupQuery . " AND LEFT(ACCTTITLE,1) <> ' ' GROUP BY ACCTTITLE";

        $stmt = $this->conn->prepare($GroupQuery);
        $stmt->execute();
        $GroupResult = $stmt->get_result();
        $stmt->close();

        while ($GroupRow = $GroupResult->fetch_assoc()) {
            $datacontent[] = array("","","","",$GroupRow["ACCTTITLE"],number_format($GroupRow["TOTALDR"], 2),number_format($GroupRow["TOTALCR"], 2),"");
            
            $sheet->getStyle("E".$RowCount.":E".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("E".$RowCount.":E".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("F".$RowCount.":F".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("G".$RowCount.":G".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

            $RowCount++;

            $smryTtlDR += $GroupRow["TOTALDR"];
            $smryTtlCR += $GroupRow["TOTALCR"];
        }
        
        $datacontent[] = array("","","","","Totals",number_format($smryTtlDR, 2),number_format($smryTtlCR, 2),"");
        $summaryTotalsCount = $RowCount;
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getFont()->setBold(true);
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $RowCount++;
        
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        
        // ===================
        $datacontent[] = array("Prepared By:","","Checked By:","","Approved By:","","","");
        $signCount1 = $RowCount;
        $sheet->mergeCells("A".($signCount1).":B".($signCount1)."");
        $sheet->mergeCells("C".($signCount1).":D".($signCount1)."");
        $sheet->getStyle("A".$signCount1)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount1)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;

        $datacontent[] = array($aasig,"",$bksig,"",$bmsig,"","","");
        $signCount2 = $RowCount;
        $sheet->mergeCells("A".($signCount2).":B".($signCount2)."");
        $sheet->mergeCells("C".($signCount2).":D".($signCount2)."");
        $sheet->getStyle("A".$signCount2)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount2)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;
        
        $datacontent[] = array("Accounting Assistant","","Bookkeeper","","General Manager","","","");
        $signCount3 = $RowCount;
        $sheet->mergeCells("A".($signCount3).":B".($signCount3)."");
        $sheet->mergeCells("C".($signCount3).":D".($signCount3)."");
        $sheet->getStyle("A".$signCount3)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount3)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;
        
        $sheet->fromArray(
            $datacontent
        );

        $styleArray = [
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
				],
			],
		];

        $sheet->mergeCells("A1:E1");
        $sheet->mergeCells("A2:E2");
        $sheet->mergeCells("A3:C3");
        $sheet->mergeCells("A7:C7");

        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        $sheet->getStyle("A1:D1")->getFont()->setBold(true);
        $sheet->getStyle("A2:A3")->getFont()->setSize(8);
        $sheet->getStyle("A7")->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        // $sheet->getStyle('A6:K7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B4C6E7');
        // $sheet->getStyle("A1:F3")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A9:H9")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("E5:F5")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("A7:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("D7:D".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A10:A".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B10:B".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C10:C".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D10:D".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F10:F".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G10:G".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H10:H".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A10:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->getFont()->setSize(8);

        $sheet->getStyle("E".$summaryHeadCount.":G".$summaryHeadCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E".$summaryTotalsCount.":E".$summaryTotalsCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F".$summaryTotalsCount.":G".$summaryTotalsCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        // $sheet->getStyle("A10:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        // $sheet->getStyle("A5:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->applyFromArray($styleArray);

        $sheet->getColumnDimension('A')->setWidth(10,'pt');
        $sheet->getColumnDimension('B')->setWidth(10,'pt');
        $sheet->getColumnDimension('C')->setWidth(10,'pt');
        $sheet->getColumnDimension('D')->setWidth(15,'pt');
        $sheet->getColumnDimension('E')->setWidth(50,'pt');
        $sheet->getColumnDimension('F')->setWidth(15,'pt');
        $sheet->getColumnDimension('G')->setWidth(15,'pt');
        $sheet->getColumnDimension('H')->setWidth(15,'pt');
        // foreach (range('A',$sheet->getHighestDataColumn()) as $col) {
		// 	$sheet->getColumnDimension($col)->setAutoSize(true);
		// }
        
        $filename = "General Journal Detailed-".time().".xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        header('Content-Type: application/x-www-form-urlencoded');
        header('Content-Transfer-Encoding: Binary');
        header("Content-disposition: attachment; filename=\"".$filename."\"");
        readfile($filename);
        unlink($filename);
        exit;
    }

    public function BOADetailedCDBPDF(){
        ini_set('memory_limit','-1');
        set_time_limit(0);

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','BRANCHNAME','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';
        $branchname = '';

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

        $fromdate = $_SESSION["PRINTFROMDATE"];
        $todate = $_SESSION["PRINTTODATE"];
        $booktype = $_SESSION["PRINTBOOKTYPE"];
        $fund = $_SESSION["PRINTFUND"];

        $datacontent = '';
        $summarycontent = '';
        $datainsert1 = '';
        $summary = '';
        $datainsert2 = '';
        $datainsert3 = '';

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'CDB' AND Fund = '".$fund."' AND  STR_TO_DATE(CDate,'%Y-%m-%d') >= STR_TO_DATE('".date("Y-m-d",strtotime($fromdate))."','%Y-%m-%d') AND STR_TO_DATE(CDate,'%Y-%m-%d') <= STR_TO_DATE('".date("Y-m-d",strtotime($todate))."','%Y-%m-%d')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $CurrJVNo = "";
        $Explanation = "";
        while ($row = $result->fetch_assoc()) {

            if($CurrJVNo != $row["CVNo"]){
                if($Explanation != ""){
                    $datainsert1 .= '
                        <tr>
                            <td width="8%" style="font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;"></td>
                            <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="30%" style="font-size: 8pt;text-align: left; border-bottom: 1px solid black"><b>'.$Explanation.'</b></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                            <td width="10%" style="font-weight: bold; border-right: 1px solid black; border-bottom: 1px solid black;"></td>
                        </tr>
                    ';
                }
            }

            if($CurrJVNo != $row["CVNo"]){
                $CurrJVNo = $row["CVNo"];
                $Explanation = $row["Explanation"];

                $SLDrCrA = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther1 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther1 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datainsert1 .= '
                    <tr>
                        <td width="8%" style="border: 1px solid black;"><b>Payee</b></td>
                        <td width="92%" colspan="7" style="border: 1px solid black; text-align: left;"><b>'.$row["Payee"].'</b></td>
                    </tr>
                    <tr>
                        <td width="8%" style="border-left: 1px solid black;">'.$row["BookPage"].'</td>
                        <td width="8%">'.$row["CDate"].'</td>
                        <td width="8%" style="font-weight: bold;">'.$row["CVNo"].'</td>
                        <td width="8%" style="font-weight: bold;">'.$row["CheckNo"].'</td>
                        <td width="8%">'.$row["AcctNo"].'</td>
                        <td width="30%" style="font-size: 8pt;text-align: left;">'.$row["AcctTitle"].'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$SLDrCrA.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$DrOther1.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right; border-right: 1px solid black;">'.$CrOther1.'</td>
                    </tr>
                ';
            }else{

                $SLDrCrB = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther2 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther2 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datainsert1 .= '
                    <tr>
                        <td width="8%" style="border-left: 1px solid black;"></td>
                        <td width="8%"></td>
                        <td width="8%"></td>
                        <td width="8%"></td>
                        <td width="8%">'.$row["AcctNo"].'</td>
                        <td width="30%" style="font-size: 8pt;text-align: left;">'.$row["AcctTitle"].'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$SLDrCrB.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right;">'.$DrOther2.'</td>
                        <td width="10%" style="font-weight: bold; font-size: 8pt;text-align: right; border-right: 1px solid black;">'.$CrOther2.'</td>
                    </tr>
                ';
            }
        }

        if($Explanation != ""){
            $datainsert1 .= '
                <tr>
                    <td width="8%" style="font-weight: bold; border-left: 1px solid black; border-bottom: 1px solid black;"></td>
                    <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="8%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="30%" style="font-size: 8pt;text-align: left; border-bottom: 1px solid black"><b>'.$Explanation.'</b></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-bottom: 1px solid black;"></td>
                    <td width="10%" style="font-weight: bold; border-right: 1px solid black; border-bottom: 1px solid black;"></td>
                </tr>
            ';
        }

        // Summary ===================================================

        $squery = "SELECT * FROM tbl_books WHERE BookType = 'CDB' AND ";

        $DateField = "STR_TO_DATE(CDate,'%Y-%m-%d')";
        $From = "STR_TO_DATE('" . date("Y-m-d",strtotime($fromdate)) . "','%Y-%m-%d')";
        $To = "STR_TO_DATE('" . date("Y-m-d",strtotime($todate)) . "','%Y-%m-%d')";
        $squery = $squery . $DateField . " >= " . $From . " AND " . $DateField . " <= " . $To;
        if ($fund != "CONSOLIDATED") {
            $squery = $squery . " AND Fund = '$fund'";
        }

        $GroupQuery = str_replace("*", "ACCTTITLE, SUM(DROTHER) as TOTALDR, SUM(CROTHER) as TOTALCR",$squery);
        $GroupQuery = $GroupQuery . " AND LEFT(ACCTTITLE,1) <> ' ' GROUP BY ACCTTITLE";

        $stmt = $this->conn->prepare($GroupQuery);
        $stmt->execute();
        $GroupResult = $stmt->get_result();
        $stmt->close();

        $fTtlDR = 0;
        $fTtlCR = 0;

        while ($GroupRow = $GroupResult->fetch_assoc()) {
            $summary .= '
                <tr>
                    <td width="38%" style="text-align:left; border-left: 1px solid black;">'.$GroupRow["ACCTTITLE"].'</td>
                    <td width="16%" style="font-weight: bold; text-align:right;  border-left: 1px solid black;  border-right: 1px solid black;">'.number_format($GroupRow["TOTALDR"], 2).'</td>
                    <td width="16%" style="font-weight: bold; text-align:right;  border-left: 1px solid black; border-right: 1px solid black;">'.number_format($GroupRow["TOTALCR"], 2).'</td>
                </tr>
            ';

            $fTtlDR += $GroupRow["TOTALDR"];
            $fTtlCR += $GroupRow["TOTALCR"];
        }

        // create new PDF document
        $pdf = new BOAHEADER('L', PDF_UNIT, array(216, 279), true, 'UTF-8', false);
        // Header Details
        $pdf->report      = "CASH DISBURSEMENT JOURNAL";
        $pdf->type        = "CDB";
        $pdf->orgname     = $orgname;
        $pdf->orgaddress  = $orgaddress;
        $pdf->orgtelno    = $orgtelno;
        $pdf->branchname  = $branchname;
        $pdf->fund        = $fund;
        $pdf->fromdate    = $fromdate;
        $pdf->todate      = $todate;
        // set document information
        $pdf->SetCreator('iSynergies');
        $pdf->SetAuthor('iSynergies');
        $pdf->SetTitle('CASH DISBURSEMENT JOURNAL');
        $pdf->SetSubject('BOOK OF ACCOUNTS');
        $pdf->SetPrintHeader(true);
        $pdf->SetPrintFooter(false);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        // set margins
        $pdf->SetMargins(7, 47, 7);
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
                <table border="0" style="font-size: 8pt;text-align: center;" cellpadding="3">
                    '.$datainsert1.'
                </table>

                <table border="0">
                    <tr><td></td></tr>
                    <tr><td></td></tr>
                </table>
        ';

        $pdf->writeHTML($datacontent, true, false, true, false, '');

        $pdf->SetPrintHeader(false);
        $pdf->SetMargins(7, 7, 7);
        $pdf->AddPage();

        $summarycontent .='

                <table border="0" style="font-size: 8pt;">
                    <tr>
                        <td width="38%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Summary of Accounts</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Debits</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:center; border: 1px solid black;">Credits</td>
                    </tr>
                    '.$summary.'
                    <tr>
                        <td width="38%" style="font-size: 10pt; font-weight: bold; text-align:left; border: 1px solid black;">Totals</td>
                        <td width="16%" style="font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($fTtlDR, 2).'</td>
                        <td width="16%" style=" font-size: 10pt; font-weight: bold; text-align:right; border: 1px solid black;">'.number_format($fTtlCR, 2).'</td>
                    </tr>
                </table>

                <table border="0">
                    <tr><td></td></tr>
                    <tr><td></td></tr>
                </table>

                <table border="0" cellpadding="3">
                    <tr>
                        <td width="23%">Prepared By:</td>
                        <td width="23%">Checked By:</td>
                        <td width="23%">Approved By:</td>
                    </tr>
                    <tr>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$aasig.'</b></td>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bksig.'</b></td>
                        <td width="1%"></td>
                        <td width="22%" style="text-align:center; border-bottom: 1px solid black;"><b>'.$bmsig.'</b></td>
                        <td width="1%"></td>
                    </tr>
                    <tr>
                        <td width="23%">Accounting Assistant</td>
                        <td width="23%">Bookkeeper</td>
                        <td width="23%">General Manager</td>
                    </tr>
                </table>
        ';

        // output the HTML content
        $pdf->writeHTML($summarycontent, true, false, true, false, '');
        // reset pointer to the last page
        $pdf->lastPage();
        //Close and output PDF document
        $pdf->Output(('Cash Disbursement Journal Detailed-'.time().'.pdf'), 'I');
    }

    public function BOADetailedCDBEXCEL(){
        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','BRANCHNAME','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';
        $branchname = '';

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

        $fromdate = $_SESSION["PRINTFROMDATE"];
        $todate = $_SESSION["PRINTTODATE"];
        $booktype = $_SESSION["PRINTBOOKTYPE"];
        $fund = $_SESSION["PRINTFUND"];

        $datacontent = [];        

        $datacontent[] = array($orgname);
        $datacontent[] = array($orgaddress);
        $datacontent[] = array($orgtelno);
        $datacontent[] = array("","","","","","","Branch",$branchname);
        $datacontent[] = array("","","","","","","Fund",$fund);
        $datacontent[] = array("","","","","","","Date",$fromdate);
        $datacontent[] = array("GENERAL JOURNAL");
        $datacontent[] = array("");
        $datacontent[] = array("","Date","JV No.","Account No","Account Title / Particulars","Subsidiary","Debit","Credit");

        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->getStyle("A9:H9")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'CDB' AND Fund = '".$fund."' AND  STR_TO_DATE(CDate,'%Y-%m-%d') >= STR_TO_DATE('".date("Y-m-d",strtotime($fromdate))."','%Y-%m-%d') AND STR_TO_DATE(CDate,'%Y-%m-%d') <= STR_TO_DATE('".date("Y-m-d",strtotime($todate))."','%Y-%m-%d')");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $CurrJVNo = "";
        $Explanation = "";
        $RowCount = 10;
        while ($row = $result->fetch_assoc()) {

            if($CurrJVNo != $row["CVNo"]){
                if($Explanation != ""){
                    $datacontent[] = array("","","","",$Explanation,"","","");

                    $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                    // $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                    $RowCount++;
                }
            }

            if($CurrJVNo != $row["CVNo"]){
                $CurrJVNo = $row["CVNo"];
                $Explanation = $row["Explanation"];

                $SLDrCrA = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther1 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther1 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                $datacontent[] = array("Payee","","","",$row["Payee"],"","","");

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $RowCount++;

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

                $datacontent[] = array($row["BookPage"],$row["CDate"],$row["JVNo"],$row["AcctNo"],$row["AcctTitle"],$SLDrCrA,$DrOther1,$CrOther1);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $RowCount++;
            }else{

                $SLDrCrB = ($row["SLDrCr"] == 0) ? "" : number_format(abs($row["SLDrCr"]),2);
                $DrOther2 = ($row["DrOther"] <= 0) ? "" : number_format($row["DrOther"],2);
                $CrOther2 = ($row["CrOther"] <= 0) ? "" : number_format($row["CrOther"],2);

                $datacontent[] = array("","","",$row["AcctNo"],$row["AcctTitle"],$SLDrCrB,$DrOther2,$CrOther2);

                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
                $RowCount++;
            }
        }

        if($Explanation != ""){
            $datacontent[] = array("","","","",$Explanation,"","","");

            $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            // $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $RowCount++;
        }

        $sheet->getStyle("A".$RowCount.":H".$RowCount)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        $datacontent[] = array("","","","","","","","");
        $RowCount++;

        // ===================
        $datacontent[] = array("","","","","Summary of Accounts","Debits","Credits","");
        $summaryHeadCount = $RowCount;
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getFont()->setBold(true);
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $RowCount++;

        $smryTtlDR = 0;
        $smryTtlCR = 0;
        
        $squery = "SELECT * FROM tbl_books WHERE BookType = 'CDB' AND ";

        $DateField = "STR_TO_DATE(CDate,'%Y-%m-%d')";
        $From = "STR_TO_DATE('" . date("Y-m-d",strtotime($fromdate)) . "','%Y-%m-%d')";
        $To = "STR_TO_DATE('" . date("Y-m-d",strtotime($todate)) . "','%Y-%m-%d')";
        $squery = $squery . $DateField . " >= " . $From . " AND " . $DateField . " <= " . $To;
        if ($fund != "CONSOLIDATED") {
            $squery = $squery . " AND Fund = '$fund'";
        }

        $GroupQuery = str_replace("*", "ACCTTITLE, SUM(DROTHER) as TOTALDR, SUM(CROTHER) as TOTALCR",$squery);
        $GroupQuery = $GroupQuery . " AND LEFT(ACCTTITLE,1) <> ' ' GROUP BY ACCTTITLE";

        $stmt = $this->conn->prepare($GroupQuery);
        $stmt->execute();
        $GroupResult = $stmt->get_result();
        $stmt->close();

        while ($GroupRow = $GroupResult->fetch_assoc()) {
            $datacontent[] = array("","","","",$GroupRow["ACCTTITLE"],number_format($GroupRow["TOTALDR"], 2),number_format($GroupRow["TOTALCR"], 2),"");
            
            $sheet->getStyle("E".$RowCount.":E".$RowCount)->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("E".$RowCount.":E".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("F".$RowCount.":F".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("G".$RowCount.":G".$RowCount)->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

            $RowCount++;

            $smryTtlDR += $GroupRow["TOTALDR"];
            $smryTtlCR += $GroupRow["TOTALCR"];
        }
        
        $datacontent[] = array("","","","","Totals",number_format($smryTtlDR, 2),number_format($smryTtlCR, 2),"");
        $summaryTotalsCount = $RowCount;
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getFont()->setBold(true);
        $sheet->getStyle("E".$RowCount.":G".$RowCount)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $RowCount++;
        
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        $datacontent[] = array("","","","","","","","");
        $RowCount++;
        
        // ===================
        $datacontent[] = array("Prepared By:","","Checked By:","","Approved By:","","","");
        $signCount1 = $RowCount;
        $sheet->mergeCells("A".($signCount1).":B".($signCount1)."");
        $sheet->mergeCells("C".($signCount1).":D".($signCount1)."");
        $sheet->getStyle("A".$signCount1)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount1)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;

        $datacontent[] = array($aasig,"",$bksig,"",$bmsig,"","","");
        $signCount2 = $RowCount;
        $sheet->mergeCells("A".($signCount2).":B".($signCount2)."");
        $sheet->mergeCells("C".($signCount2).":D".($signCount2)."");
        $sheet->getStyle("A".$signCount2)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount2)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;
        
        $datacontent[] = array("Accounting Assistant","","Bookkeeper","","General Manager","","","");
        $signCount3 = $RowCount;
        $sheet->mergeCells("A".($signCount3).":B".($signCount3)."");
        $sheet->mergeCells("C".($signCount3).":D".($signCount3)."");
        $sheet->getStyle("A".$signCount3)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C".$signCount3)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $RowCount++;

        $sheet->fromArray(
            $datacontent
        );

        $styleArray = [
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
				],
			],
		];

        $sheet->mergeCells("A1:E1");
        $sheet->mergeCells("A2:E2");
        $sheet->mergeCells("A3:C3");
        $sheet->mergeCells("A7:C7");

        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);

        $sheet->getStyle("A1:D1")->getFont()->setBold(true);
        $sheet->getStyle("A2:A3")->getFont()->setSize(8);
        $sheet->getStyle("A7")->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        // $sheet->getStyle('A6:K7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('B4C6E7');
        // $sheet->getStyle("A1:F3")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A9:H9")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("E5:F5")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("A7:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        // $sheet->getStyle("D7:D".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A10:A".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B10:B".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C10:C".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D10:D".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F10:F".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G10:G".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H10:H".$sheet->getHighestRow())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A10:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->getFont()->setSize(8);

        $sheet->getStyle("E".$summaryHeadCount.":G".$summaryHeadCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E".$summaryTotalsCount.":E".$summaryTotalsCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F".$summaryTotalsCount.":G".$summaryTotalsCount."")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        // $sheet->getStyle("A5:".$sheet->getHighestDataColumn().$sheet->getHighestRow())->applyFromArray($styleArray);

        $sheet->getColumnDimension('A')->setWidth(10,'pt');
        $sheet->getColumnDimension('B')->setWidth(10,'pt');
        $sheet->getColumnDimension('C')->setWidth(10,'pt');
        $sheet->getColumnDimension('D')->setWidth(15,'pt');
        $sheet->getColumnDimension('E')->setWidth(50,'pt');
        $sheet->getColumnDimension('F')->setWidth(15,'pt');
        $sheet->getColumnDimension('G')->setWidth(15,'pt');
        $sheet->getColumnDimension('H')->setWidth(15,'pt');
        // foreach (range('A',$sheet->getHighestDataColumn()) as $col) {
		// 	$sheet->getColumnDimension($col)->setAutoSize(true);
		// }
        
        $filename = "Cash Disbursement Journal Detailed-".time().".xlsx";
        $writer = new Xlsx($spreadsheet);
        $writer->save($filename);

        header('Content-Type: application/x-www-form-urlencoded');
        header('Content-Transfer-Encoding: Binary');
        header("Content-disposition: attachment; filename=\"".$filename."\"");
        readfile($filename);
        unlink($filename);
        exit;
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