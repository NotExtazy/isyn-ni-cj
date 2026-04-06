<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
require_once($docRoot . '/iSynApp-main/assets/tcpdf/tcpdf.php');
include_once($docRoot . "/iSynApp-main/database/connection.php");

class Reports extends Database 
{
    public function CDReport($batchno){
        ob_clean();
		ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);

		$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('CHECK VOUCHER');
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
        $branch = '';
        $fund = '';
        $payee = '';
        $particular = '';
        $voucherno = '';
        $checkno = '';
        $checkdate = '';
        $amount = 0;
        $amtwords = 0;

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';

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

        $stmt = $this->conn->prepare("SELECT * FROM tbl_othercv WHERE BatchNo = ?");
        $stmt->bind_param("s",$batchno);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $branch = $row["branch"];
            $fund = $row["fund"];
            $payee = $row["payee"];
            $particular = $row["particular"];
            $voucherno = $row["cvno"];
            $checkno = $row["checkno"];
            $checkdate = $row["checkdate"];
            $amount = $row["amtothercv"];
            $amtwords = $row["amtwords"];
            
            $SLDrCr = number_format(abs(str_replace(",","",$row["sldrcr"])),2);
            $SLDrCr1 =  number_format(str_replace(",","",$row["sldrcr"]),2);

            $contentdata .= '
                <tr style="font-size: 9pt;">
                    <td width="40%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;"><pre>&nbsp;'.$row["accttitle"].'</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:center;"><pre>'.$row["acctno"].'</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:right;"><pre>'.($row["sldrcr"] < 0 ? '('.$SLDrCr.')' : $SLDrCr1).'&nbsp;</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:right;"><pre>'.number_format($row["drother"], 2, '.', ',').'&nbsp;</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:right;"><pre>'.number_format($row["crother"], 2, '.', ',').'&nbsp;</pre></td>
                </tr>
            ';
        }
        
        $content = '';

        $content .= '<table>
                        <tr>
                            <td width="25%"></td>
                            <td width="50%"><p style="font-weight:bold;text-align:center;"><label style="font-size:12pt;">'.$orgname.'</label></p></td>
                            <td width="25%"></td>
                        </tr>
                        <tr>
                            <td width="25%"></td>
                            <td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">'.$orgaddress.'</p></td>
                            <td width="25%"></td>
                        </tr>
                        <tr>
                            <td width="25%"></td>
                            <td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">Tel No. '.$orgtelno.'</p></td>
                            <td width="25%"></td>
                        </tr>
                        <tr><td height="20px"></td></tr>
                        <tr>
                            <td width="100%" height="30px" style="font-size:12px;font-weight:bold;text-align:center;"><p>CHECK VOUCHER</p></td>
                        </tr>
                        <tr>
                            <td width="50%" style="font-size:9px;text-align:left;"><table>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">BRANCH:</td>
                                        <td width="82%" height="15">'.$branch.'</td>
                                    </tr>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">FUND:</td>
                                        <td width="82%" height="15">'.$fund.'</td>
                                    </tr>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">PAYEE:</td>
                                        <td width="82%" height="15">'.$payee.'</td>
                                    </tr>
                                </table>
                            </td>
                            <td width="50%" style="font-size:9px;text-align:right;"><table>
                                    <tr>
                                        <td width="60%" height="15" style="font-weight:bold;">CV NO:</td>
                                        <td width="40%" height="15">'.$voucherno.'</td>
                                    </tr>
                                    <tr>
                                        <td width="60%" height="15" style="font-weight:bold;">CHECK DATE:</td>
                                        <td width="40%" height="15">'.$checkdate.'</td>
                                    </tr>
                                    <tr>
                                        <td width="60%" height="15" style="font-weight:bold;">CHECK NO:</td>
                                        <td width="40%" height="15">'.$checkno.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="100%"><table cellpadding="4" style="font-size:10pt;font-weight:bold;">
                                <tr>
                                    <td width="85%" style="border: 1px solid black;text-align:center;">  PARTICULARS</td>
                                    <td width="15%" style="border: 1px solid black;text-align:center;">  AMOUNT</td>
                                </tr>
                            </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="85%" height="50px" style="border: 1px solid black;text-align:center;font-size:10pt;line-height:50px;font-style:italic;">'.$particular.'</td>
                            <td width="15%" height="50px" style="border: 1px solid black;text-align:center;font-size:10pt;font-weight:bold;line-height:50px">'.number_format($amount, 2, '.', ',').'</td>
                        </tr>
                        <tr style="font-weight:bold;font-size:9pt;text-align:center;line-height:30px;">
                            <td width="40%" height="30px" style="border: 1px solid black;">Account Title</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Account No</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Subsidiary DR(CR)</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Debit</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Credit</td>
                        </tr>
                        '.$contentdata.'
                        <tr style="font-weight:bold;font-size:9pt;line-height:30px;">
                            <td width="25%" height="30px" style="border: 1px solid black;">&nbsp;&nbsp;Prepared By:</td>
                            <td width="25%" height="30px" style="border: 1px solid black;">&nbsp;&nbsp;Checked By:</td>
                            <td width="50%" height="30px" style="border: 1px solid black;">&nbsp;&nbsp;Approved By:</td>
                        </tr>
                        <tr style="font-size:9pt;line-height:50px;text-align:center;">
                            <td width="25%" height="50px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;">'.$aasig.'</td>
                            <td width="25%" height="50px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;">'.$bksig.'</td>
                            <td width="25%" height="30px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;">'.$bmsig.'</td>
                            <td width="25%" height="30px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;"></td>
                        </tr>
                        <tr><td height="40px"></td></tr>
                        <tr>
                            <td width="8%"></td>
                            <td width="90%"style="font-size:10pt;">Received from '.$orgname.' the sum of</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="8%"></td>
                            <td width="90%" style="font-size:10pt;font-weight:bold;">'. $amtwords .'</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="8%"></td>
                            <td width="90%" style="font-size:10pt;">in full/partial payment of the above mentioned account.</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="75%"></td>
                            <td width="25%" style="font-size:10pt;">Payee : __________________</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="69%"></td>
                            <td width="31%" style="font-size:10pt;">Date Received : __________________</td>
                        </tr>
                    </table>
        ';

        // logs($_SESSION['usertype'], "Printed CDReport", "btnCDReportPrint", $_SESSION['username'], "Cash Disbursements");
        
        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
        $pdf->IncludeJS("print();");
		$pdf->Output('crb.pdf', 'I');
    }

    public function CDReprintReport($date,$cvno,$fund){
        ob_clean();
		ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);

		$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('CHECK VOUCHER');
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
        $branch = '';
        $payee = '';
        $particular = '';
        $voucherno = '';
        $checkno = '';
        $checkdate = '';
        $amount = 0;
        $amtwords = 0;

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';

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

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE CVNo = ? AND STR_TO_DATE(CDate, '%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ?");
        $stmt->bind_param("sss",$cvno,$date,$fund);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $branch = $row["Branch"];
            $fund = $row["Fund"];
            $payee = $row["Payee"];
            $particular = $row["Explanation"];
            $voucherno = $row["CVNo"];
            $checkno = $row["CheckNo"];
            $checkdate = $row["CDate"];
            if ($row["AcctNo"] == "11130"){
                $amount = number_format(abs(str_replace(",","",$row["CrOther"])),2);
                $amtwords = ucwords($this->numberTowords($amount));
            }

            // echo $amount . "</br>";
            
            $SLDrCr = number_format(abs(str_replace(",","",$row["SLDrCr"])),2);
            $SLDrCr1 =  number_format(str_replace(",","",$row["SLDrCr"]),2);

            $contentdata .= '
                <tr style="font-size: 9pt;">
                    <td width="40%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;"><pre>&nbsp;'.$row["AcctTitle"].'</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:center;"><pre>'.$row["AcctNo"].'</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:right;"><pre>'.($row["SLDrCr"] < 0 ? '('.$SLDrCr.')' : $SLDrCr1).'&nbsp;</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:right;"><pre>'.number_format($row["DrOther"], 2, '.', ',').'&nbsp;</pre></td>
                    <td width="15%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;line-height:20px;text-align:right;"><pre>'.number_format($row["CrOther"], 2, '.', ',').'&nbsp;</pre></td>
                </tr>
            ';
        }
        
        $content = '';

        $content .= '<table>
                        <tr>
                            <td width="25%"></td>
                            <td width="50%"><p style="font-weight:bold;text-align:center;"><label style="font-size:12pt;">'.$orgname.'</label></p></td>
                            <td width="25%"></td>
                        </tr>
                        <tr>
                            <td width="25%"></td>
                            <td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">'.$orgaddress.'</p></td>
                            <td width="25%"></td>
                        </tr>
                        <tr>
                            <td width="25%"></td>
                            <td width="50%"><p style="font-size:9pt;text-align:center;font-style:italic;">Tel No. '.$orgtelno.'</p></td>
                            <td width="25%"></td>
                        </tr>
                        <tr><td height="20px"></td></tr>
                        <tr>
                            <td width="100%" height="30px" style="font-size:12px;font-weight:bold;text-align:center;"><p>CHECK VOUCHER</p></td>
                        </tr>
                        <tr>
                            <td width="50%" style="font-size:9px;text-align:left;"><table>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">BRANCH:</td>
                                        <td width="82%" height="15">'.$branch.'</td>
                                    </tr>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">FUND:</td>
                                        <td width="82%" height="15">'.$fund.'</td>
                                    </tr>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">PAYEE:</td>
                                        <td width="82%" height="15">'.$payee.'</td>
                                    </tr>
                                </table>
                            </td>
                            <td width="50%" style="font-size:9px;text-align:right;"><table>
                                    <tr>
                                        <td width="60%" height="15" style="font-weight:bold;">CV NO:</td>
                                        <td width="40%" height="15">'.$voucherno.'</td>
                                    </tr>
                                    <tr>
                                        <td width="60%" height="15" style="font-weight:bold;">CHECK DATE:</td>
                                        <td width="40%" height="15">'.$checkdate.'</td>
                                    </tr>
                                    <tr>
                                        <td width="60%" height="15" style="font-weight:bold;">CHECK NO:</td>
                                        <td width="40%" height="15">'.$checkno.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="100%"><table cellpadding="4" style="font-size:10pt;font-weight:bold;">
                                <tr>
                                    <td width="85%" style="border: 1px solid black;text-align:center;">  PARTICULARS</td>
                                    <td width="15%" style="border: 1px solid black;text-align:center;">  AMOUNT</td>
                                </tr>
                            </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="85%" height="50px" style="border: 1px solid black;text-align:center;font-size:10pt;font-style:italic;">'.$particular.'</td>
                            <td width="15%" height="50px" style="border: 1px solid black;text-align:center;font-size:10pt;font-weight:bold;line-height:50px">'.$amount.'</td>
                        </tr>
                        <tr style="font-weight:bold;font-size:9pt;text-align:center;line-height:30px;">
                            <td width="40%" height="30px" style="border: 1px solid black;">Account Title</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Account No</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Subsidiary DR(CR)</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Debit</td>
                            <td width="15%" height="30px" style="border: 1px solid black;">Credit</td>
                        </tr>
                        '.$contentdata.'
                        <tr style="font-weight:bold;font-size:9pt;line-height:30px;">
                            <td width="25%" height="30px" style="border: 1px solid black;">&nbsp;&nbsp;Prepared By:</td>
                            <td width="25%" height="30px" style="border: 1px solid black;">&nbsp;&nbsp;Checked By:</td>
                            <td width="50%" height="30px" style="border: 1px solid black;">&nbsp;&nbsp;Approved By:</td>
                        </tr>
                        <tr style="font-size:9pt;line-height:50px;text-align:center;">
                            <td width="25%" height="50px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;">'.$aasig.'</td>
                            <td width="25%" height="50px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;">'.$bksig.'</td>
                            <td width="25%" height="30px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;">'.$bmsig.'</td>
                            <td width="25%" height="30px" style="border-right: 1px solid black;border-left: 1px solid black;border-top: 1px solid black;border-bottom: 1px solid black;text-align:center;"></td>
                        </tr>
                        <tr><td height="40px"></td></tr>
                        <tr>
                            <td width="8%"></td>
                            <td width="90%"style="font-size:10pt;">Received from '.$orgname.' the sum of</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="8%"></td>
                            <td width="90%" style="font-size:10pt;font-weight:bold;">'. $amtwords .'</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="8%"></td>
                            <td width="90%" style="font-size:10pt;">in full/partial payment of the above mentioned account.</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="75%"></td>
                            <td width="25%" style="font-size:10pt;">Payee : __________________</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="69%"></td>
                            <td width="31%" style="font-size:10pt;">Date Received : __________________</td>
                        </tr>
                    </table>
        ';

        // logs($_SESSION['usertype'], "Printed CDReport", "btnCDReportPrint", $_SESSION['username'], "Cash Disbursements");
        
        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
        $pdf->IncludeJS("print();");
		$pdf->Output('crb.pdf', 'I');
    }

    public function CheckReceiptLBP($cvno){

        $CheckName = "";
        $DatePrepared = "";
        $NetAmount = "";
        $InWords = "";

        $stmt = $this->conn->prepare("SELECT * FROM tbl_othercv WHERE cvno = ? LIMIT 1");
        $stmt->bind_param("s" ,$cvno);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $CheckName = $row["payee"];
            $DatePrepared = $row["cdate"];
            $dateArray = str_split($DatePrepared);
            $NetAmount = $row["amtothercv"];
            $InWords = $row["amtwords"];
        }
        $stmt->close();

        $stmt = $this->conn->prepare("DELETE FROM tbl_othercv WHERE cvno = ?");
        $stmt->bind_param("s",$cvno);
        $stmt->execute();
        $stmt->close();
        
        ob_clean();
		ob_flush();
        $height = 330.2;
        $width = 205;
        $pageLayout = array($width, $height);
		$pdf = new TCPDF('P', PDF_UNIT, $pageLayout, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('CHECK VOUCHER');
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

        $content = 
                    '
                        <table>
                            <tr style="line-height:8px;"><td></td></tr>
                            <tr>
                                <td width="75%"></td>
                                <td width="23%">
                                    <table>
                                        <tr>
                                            <td width="1%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[0].'</td>
                                            <td width="6%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[1].'</td>
                                            <td width="10%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[3].'</td>
                                            <td width="5%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[4].'</td>
                                            <td width="10%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[6].'</td>
                                            <td width="8%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[7].'</td>
                                            <td width="8%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[8].'</td>
                                            <td width="8%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[9].'</td>                                         
                                        </tr>
                                    </table>
                                </td>
                                <td width="2%"></td>
                            </tr>
                            <tr><td style="line-height:16px;"></td></tr>
                            <tr>
                                <td width="16%"></td>
                                <td width="64%" style="font-size:11pt;">'.$CheckName.'</td>
                                <td width="19%" style="font-size:11pt;text-align:left;">'.number_format($NetAmount, 2, '.', ',').'</td>
								<td width="1%"></td>
                            </tr>
                            <tr style="line-height:12px;"><td></td></tr>
                            <tr>
                                <td width="13%"></td>
                                <td width="87%" style="font-size:8.5pt;">'.$InWords.' ONLY</td>
                            </tr>
                        </table>
                    ';

        // logs($_SESSION['usertype'], "Printed LBP Receipt", "btnLBPReceiptPrint", $_SESSION['username'], "LBP Rceipts");

        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
		$pdf->Output('CheckReceiptLBP.pdf', 'I');
    }

    public function CheckReceiptMBTC($batchno){
        $CheckName = "";
        $DatePrepared = "";
        $NetAmount = "";
        $InWords = "";

        $stmt = $this->conn->prepare("SELECT * FROM tbl_othercv WHERE BatchNo = ? LIMIT 1");
        $stmt->bind_param("s" ,$batchno);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $CheckName = $row["payee"];
                $DatePrepared = date('m-d-Y',strtotime($row["cdate"]));
                $dateArray = str_split($DatePrepared);
                $NetAmount = $row["amtothercv"];
                $InWords = $row["amtwords"];
            }
        } else {
            echo "The check receipt has already been printed. Please go back to the previous page.";
            exit;
        }

        $stmt = $this->conn->prepare("DELETE FROM tbl_othercv WHERE BatchNo = ?");
        $stmt->bind_param("s",$batchno);
        $stmt->execute();
        $stmt->close();
        
        ob_clean();
		ob_flush();
        $height = 330.2;
        $width = 205;
        $pageLayout = array($width, $height);
		$pdf = new TCPDF('P', PDF_UNIT, $pageLayout, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('CHECK RECEIPT');
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

        $content = 
                    '
                        <table>
                            <tr style="line-height:8px;"><td></td></tr>
                            <tr>
                                <td width="76%"></td>
                                <td width="23%">
                                    <table>
                                        <tr>
                                            <td width="1%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[0].'</td>
                                            <td width="6%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[1].'</td>
                                            <td width="10%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[3].'</td>
                                            <td width="5%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[4].'</td>
                                            <td width="10%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[6].'</td>
                                            <td width="8%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[7].'</td>
                                            <td width="8%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[8].'</td>
                                            <td width="8%"></td>
                                            <td width="5%" style="font-size:10pt;">'.$dateArray[9].'</td>                                         
                                        </tr>
                                    </table>
                                </td>
                                <td width="1%"></td>
                            </tr>
                            <tr><td style="line-height:16px;"></td></tr>
                            <tr>
                                <td width="17%"></td>
                                <td width="63%" style="font-size:11pt;">'.$CheckName.'</td>
                                <td width="19%" style="font-size:11pt;text-align:left;">'.number_format($NetAmount, 2, '.', ',').'</td>
								<td width="1%"></td>
                            </tr>
                            <tr style="line-height:12px;"><td></td></tr>
                            <tr>
                                <td width="14%"></td>
                                <td width="86%" style="font-size:8.5pt;">'.$InWords.' ONLY</td>
                            </tr>
                        </table>
                    ';

        // logs($_SESSION['usertype'], "Printed Metro Receipt", "btnMetroReceiptPrint", $_SESSION['username'], "Metro Receipts");

        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
		$pdf->Output('CheckReceiptMBTC.pdf', 'I');
    }

    private function FillRow($data){
        $arr = [];
        while ($row = $data->fetch_assoc()) {
            $arr[] = $row;
        }
        return $arr;
    }
}
?>