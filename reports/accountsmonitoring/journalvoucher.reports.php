<?php
require_once('../../assets/tcpdf/tcpdf.php');

class Reports extends Database 
{
    public function JVReport($date,$fund,$jvno){
        ob_clean();
		ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);
        
		$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('JOURNAL VOUCHER');
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
        // $fund = '';
        $payee = '';
        $particular = '';
        $voucherno = '';
        $checkno = '';
        $checkdate = '';
        $amount = 0;
        $branchcode = "HO";
        $totaldr = 0;
        $totalcr = 0;

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

        $date = date('Y-m-d', strtotime($date));

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ? AND JVNo = ?");
        $stmt->bind_param("sss",$date,$fund,$jvno);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $branch = $row["Branch"];
            $fund = $row["Fund"];
            $explanation = $row["Explanation"];
            $date = $row["CDate"];
            $SLDrCr = number_format(abs(str_replace(",","",$row["SLDrCr"])),2);
            $SLDrCr1 =  number_format(str_replace(",","",$row["SLDrCr"]),2);

            $contentdata .= '
                    <tr style="font-size:9pt;">
                        <td width="40%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;"><pre>&nbsp;'.$row["AcctTitle"].'</pre></td>
                        <td width="16%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;text-align:center;"><pre>'.$row["AcctNo"].''.(($row["LoanID"] != "-" && $row["SLNo"] == "11251") ? "-".$row["LoanID"] : "").'</pre></td>
                        <td width="16%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;text-align:right;"><pre>'.($row["SLDrCr"] < 0 ? '('.$SLDrCr.')' : $SLDrCr1).'&nbsp;</pre></td>
                        <td width="14%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;text-align:right;"><pre>'.number_format(str_replace(",","",$row["DrOther"]),2).'&nbsp;</pre></td>
                        <td width="14%" height="20px" style="border-left: 1px solid black;border-right: 1px solid black;text-align:right;"><pre>'.number_format(str_replace(",","",$row["CrOther"]),2).'&nbsp;</pre></td>
                    </tr>
            ';
            
            $totaldr = floatval($totaldr) + floatval(str_replace(",","",$row["DrOther"]));
            $totalcr = floatval($totalcr) + floatval(str_replace(",","",$row["CrOther"]));
        }
        
        $content = '';

        $content .= '<table>
                        <tr>
                            <td width="10%"></td>
                            <td width="80%"><p style="font-weight:bold;text-align:center;"><label style="font-size:12pt;">'.$orgname.'</label></p></td>
                            <td width="10%"></td>
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
                            <td width="100%" height="30px" style="font-size:12pt;font-weight:bold;text-align:center;"><p>JOURNAL VOUCHER</p></td>
                        </tr>
                        <tr>
                            <td width="70%" style="font-size:9pt;"><table>
                                    <tr>
                                        <td width="13%" height="15" style="font-weight:bold;">BRANCH:</td>
                                        <td width="87%" height="15">'.$branch.'</td>
                                    </tr>
                                    <tr>
                                        <td width="10%" height="15" style="font-weight:bold;">FUND:</td>
                                        <td width="90%" height="15">'.$fund.'</td>
                                    </tr>
                                </table>
                            </td>
                            <td width="30%" style="font-size:9pt;"><table>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">JV NO:</td>
                                        <td width="82%" height="15">'.$jvno.'</td>
                                    </tr>
                                    <tr>
                                        <td width="18%" height="15" style="font-weight:bold;">DATE:</td>
                                        <td width="82%" height="15">'.$date.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="100%"><table cellpadding="4" style="font-size:10pt;font-weight:bold;">
                                <tr>
                                    <td width="100%" style="border: 1px solid black;text-align:center;">EXPLANATION</td>
                                </tr>
                            </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="100%" height="50px" style="border: 1px solid black;text-align:center;font-size:10pt;font-style:italic;">'.$explanation.'</td>
                        </tr>
                        <tr style="font-weight:bold;font-size:9pt;text-align:center;line-height:30px;">
                            <td width="40%" height="30px" style="border: 1px solid black;">Particulars</td>
                            <td width="16%" height="30px" style="border: 1px solid black;">Account   No</td>
                            <td width="16%" height="30px" style="border: 1px solid black;">Subsidiary DR(CR)</td>
                            <td width="14%" height="30px" style="border: 1px solid black;">Debit</td>
                            <td width="14%" height="30px" style="border: 1px solid black;">Credit</td>
                        </tr>
                        '.$contentdata.'
                        <tr style="font-weight:bold;font-size:9pt;text-align:right;line-height:30px;">
                            <td width="40%" height="30px" style="border: 1px solid black;"></td>
                            <td width="16%" height="30px" style="border: 1px solid black;"></td>
                            <td width="16%" height="30px" style="border: 1px solid black;"></td>
                            <td width="14%" height="30px" style="border: 1px solid black;">'.number_format($totaldr,2).'&nbsp;&nbsp;&nbsp;</td>
                            <td width="14%" height="30px" style="border: 1px solid black;">'.number_format($totalcr,2).'&nbsp;&nbsp;&nbsp;</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr style="font-weight:bold;font-size:9pt;line-height:30px;">
                            <td width="30%" height="30px">&nbsp;&nbsp;Prepared By:</td>
                            <td width="30%" height="30px">&nbsp;&nbsp;Checked By:</td>
                            <td width="40%" height="30px">&nbsp;&nbsp;Approved By:</td>
                        </tr>
                        <tr style="font-size:9pt;line-height:50px;">
                            <td width="30%" height="50px">&nbsp;&nbsp;'.$aasig.'<label style="line-height:18px;"><br/>&nbsp;&nbsp;Accouting Assistant</label></td>
                            <td width="30%" height="50px">&nbsp;&nbsp;'.$bksig.'<label style="line-height:18px;"><br/>&nbsp;&nbsp;Bookeeper</label></td>
                            <td width="40%" height="30px">&nbsp;&nbsp;'.$bmsig.'<label style="line-height:18px;"><br/>&nbsp;&nbsp;General Manager</label></td>
                        </tr>
                       
                    </table>
        ';

        // logs($_SESSION['usertype'], "Printed JV Report", "btnJVPrint", $_SESSION['username'], "JV Reports");        
        
        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
        $pdf->IncludeJS("print();");
		$pdf->Output('journalvoucher.pdf', 'I');
    }
}
?>