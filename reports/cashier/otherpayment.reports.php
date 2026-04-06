<?php
require_once('../../assets/tcpdf/tcpdf.php');

class Reports extends Database 
{
    public function ORReport($ordate,$orfund,$orno,$orseries,$pytin,$pyaddress,$nontax){
        
        $datacontent = "";
        $DatePaid = "";
        $ClientName = "";
        $Amount = 0;
        $AmountToWords = "";
        $RowsToAdd = 8;
        $TotalSales = 0;
        $Particulars = "";

        $Payment = 0;
        $TotalPayment = 0;
        $TotalAmountDue = 0;
        $VatAmount = 0;
        $VatSales = 0;
        $OtherAssets = 0;

        
        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'CRB' AND  STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ? AND ORNo = ? AND PO = ?");
        $stmt->bind_param("ssss",$ordate,$orfund,$orno,$orseries);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $DatePaid = $row["CDate"];
            $ClientName = $row["Payee"];
            $Particulars = $row["Explanation"];
            if ($row["AcctNo"] == "11120"){
                $Payment += ($row["CrDr"] == "DEBIT") ? $row["DrOther"] : $row["CrOther"];
            } else if ($row["AcctNo"] != "11120") {
                $OtherAssets += ($row["CrDr"] == "DEBIT") ? $row["DrOther"] : 0;
            }

            $stmt = $this->conn->prepare("SELECT SUM(SLDrCr) AS TotalPaid FROM tbl_books WHERE BookType = 'CRB' AND  STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ? AND ORNo = ? AND PO = ?");
            $stmt->bind_param("ssss",$ordate,$orfund,$orno,$orseries);
            $stmt->execute();
            $resultTtlPaid = $stmt->get_result();
            $stmt->close();
            $rowTtlPaid = $resultTtlPaid->fetch_assoc();
            $Amount = (abs($rowTtlPaid["TotalPaid"]) <> "0") ? abs($rowTtlPaid["TotalPaid"]) : $Amount;
            $AmountToWords = (abs($rowTtlPaid["TotalPaid"]) <> "0") ? ucwords($this->numberTowords(abs($rowTtlPaid["TotalPaid"]))) : $AmountToWords;
        }

        if ($nontax == "YES"){
            $TotalAmountDue = $Payment;
            $OtherAssets = 0;
            $TotalPayment = 0;
        } else {
            $TotalAmountDue = $Payment + $OtherAssets;
            $TotalPayment = $Payment;
        }

        $VatSales = $TotalAmountDue / 1.12;
        $VatAmount = $VatSales * 0.12;

        ob_clean();
		ob_flush();
        $width = 215;
        $height = 330;
        $page_size = array($width,$width);
		$pdf = new TCPDF('P', "mm", $page_size, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('CASH RECEIPT | OR');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(0, 4, 0);
		$pdf->SetAutoPageBreak(TRUE, 5);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
        $fontname = TCPDF_FONTS::addTTFfont('../fonts/segoesc.ttf', 'TrueTypeUnicode', '', 96);
		$pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        $content = '
                    <table border="0">
                        <tr>
                            <td width="35%">
                                <table border="0" cellpadding="0" style="font-size:10pt;">
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr>
                                        <td style="text-align:left;" width="45%"></td>
                                        <td style="text-align:right;" width="35%">'.number_format($OtherAssets,2).'</td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:left;" width="45%"></td>
                                        <td style="text-align:right;" width="35%">'.number_format($TotalPayment,2).'</td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:left;" width="45%"></td>
                                        <td style="text-align:right;" width="35%"></td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:left;" width="45%"></td>
                                        <td style="text-align:right;" width="35%">'.number_format($TotalPayment,2).'</td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:left;" width="45%"></td>
                                        <td style="text-align:right;" width="35%"></td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;" width="45%"></td>
                                        <td style="text-align:right;" width="35%"></td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:left;" width="45%"></td>
                                        <td style="text-align:right;" width="35%">'.number_format($VatSales,2).'</td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;" width="45%"></td>
                                        <td style="text-align:right;" width="35%"></td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;" width="45%"></td>
                                        <td style="text-align:right;" width="35%">'.number_format($VatAmount,2).'</td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                    <tr>
                                        <td style="text-align:center;" width="45%"></td>
                                        <td style="text-align:right;" width="35%">'.number_format($TotalAmountDue,2).'</td>
                                        <td style="text-align:center;" width="20%"></td>
                                    </tr>
                                </table>
                            </td>
                            <td width="65%">
                                <table border = "0" style="font-size: 10pt;">
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr>
                                        <td width="72%"></td>
                                        <td width="21%" style="font-size:10pt;">'.date("m/d/Y",strtotime($DatePaid)).'</td>
                                        <td width="7%"></td>
                                    </tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr>
                                        <td width="26%" style="line-height:20px;text-align:right;"></td>
                                        <td width="67%" style="line-height:20px;text-indent:10px;font-size:10pt;">'.$ClientName.'</td>
                                        <td width="7%"></td>
                                    </tr>
                                    <tr>
                                        <td width="11%" style="line-height:20px;text-align:right;"></td>
                                        <td width="89%" style="line-height:20px;text-indent:10px;font-size:10pt;">'.$pytin.'</td>
                                    </tr>
                                    <tr>
                                        <td width="11%" style="line-height:20px;text-align:right;"></td>
                                        <td width="89%" style="line-height:20px;text-indent:10px;font-size:10pt;">'.$pyaddress.'</td>
                                    </tr>
                                    <tr>
                                        <td width="11%" style="line-height:20px;text-align:right;"></td>
                                        <td width="79%" style="line-height:20px;text-indent:10px;font-size:8pt;">'.$AmountToWords.'</td>
                                        <td width="10%" style="line-height:20px;font-size:8pt;">'.number_format($Amount,2).'</td>
                                    </tr>
                                    <tr>
                                        <td width="25%"></td>
                                        <td width="75%" style="text-align:left;line-height:20px;text-indent:5px;">'.$Particulars.'</td>
                                        <td></td>
                                    </tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr><td width="100%"></td></tr>
                                    <tr>
                                        <td width="67%"></td>
                                        <td width="33%" style="line-height:15px;text-align:left;font-style:italic;">'.$_SESSION['FULLNAME'].'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
        ';

        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
		$pdf->Output('crb.pdf', 'I');
    }

    public function ARReport($ardate,$arfund,$arno,$arseries,$pytin,$pyaddress){
        
        $datacontent = "";
        $DatePaid = "";
        $ClientName = "";
        $Amount = 0;
        $AmountToWords = "";
        $RowsToAdd = 2;
        $TotalSales = 0;
        $Particulars = "";

        $stmt = $this->conn->prepare("SELECT * FROM tbl_books WHERE BookType = 'CRB' AND  STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ? AND ORNo = ? AND PO = ?");
        $stmt->bind_param("ssss",$ardate,$arfund,$arno,$arseries);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        while ($row = $result->fetch_assoc()) {
            $DatePaid = $row["CDate"];
            $ClientName = $row["Payee"];
            $Particulars = $row["Explanation"];


            $stmt = $this->conn->prepare("SELECT SUM(SLDrCr) AS TotalPaid FROM tbl_books WHERE BookType = 'CRB' AND  STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ? AND ORNo = ? AND PO = ?");
            $stmt->bind_param("ssss",$ardate,$arfund,$arno,$arseries);
            $stmt->execute();
            $resultTtlPaid = $stmt->get_result();
            $stmt->close();
            $rowTtlPaid = $resultTtlPaid->fetch_assoc();
            $Amount = (abs($rowTtlPaid["TotalPaid"]) <> "0") ? abs($rowTtlPaid["TotalPaid"]) : $Amount;
            $AmountToWords = (abs($rowTtlPaid["TotalPaid"]) <> "0") ? ucwords($this->numberTowords(abs($rowTtlPaid["TotalPaid"]))) : $AmountToWords;
        }

        $maxWidthMm = 140; // Target width in mm
        $initialFontSizePt = 8; // Starting font size in points
        $minFontSizePt = 5; // Minimum font size for readability
        $characterWidthFactor = 0.4; // Average character width in mm at 11pt font size

        $adjustedFontSize = $this->adjustFontSizeToFitWidth($ClientName, $maxWidthMm, $initialFontSizePt, $minFontSizePt, $characterWidthFactor);

        ob_clean();
		ob_flush();
        $pdf = new TCPDF('P', "mm", 'B6', true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('CASH RECEIPT | AR');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(0, 4, 0);
		$pdf->SetAutoPageBreak(TRUE, 5);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		    require_once(dirname(__FILE__).'/lang/eng.php');
		    $pdf->setLanguageArray($l);
		}
        $fontname = TCPDF_FONTS::addTTFfont('../fonts/segoesc.ttf', 'TrueTypeUnicode', '', 96);
		$pdf->SetFont('helvetica', '', 8);
        $pdf->AddPage();

        $content = '
                    <table border="1" width="100%">
                        <tr>
                            <td width="100%" colspan="3" style="line-height:25px;"></td>
                        </tr>
                        <tr>
                            <td width="5%"></td>
                            <td width="90%"><table>
                                    <tr>
                                        <td width="100%"><table border="1" cellpadding="2" style="font-size:8pt;">
                                                <tr>
                                                    <td width="100%" style="line-height:56px;">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="100%"></td>
                                                </tr>
                                                <tr>
                                                    <td width="63%"></td>
                                                    <td width="37%">'.date("m/d/Y",strtotime($DatePaid)).'</td>
                                                </tr>                                                
                                                <tr>
                                                    <td width="100%"><table border="1" cellpadding="5" style="font-size:8pt;">
                                                            <tr>
                                                                <td width="100%"></td>
                                                            </tr>
                                                            <tr>
                                                                <td width="25%"></td>
                                                                <td style="text-align:left;font-size:'.$adjustedFontSize.'pt;" width="75%" > '.$ClientName.'</td>
                                                            </tr>
                                                            <tr>
                                                                <td width="25%"></td>
                                                                <td width="75%" style="text-align:left;">'.$pytin.'</td>
                                                            </tr>
                                                            <tr>
                                                                <td width="25%"></td>
                                                                <td width="75%" style="text-align:left;">'.$pyaddress.'</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="100%"></td>
                                                </tr>
                                                <tr>
                                                    <td width="100%"><table border="1" cellpadding="3" style="font-size:8pt;">
                                                            <tr>
                                                                <td width="100%"></td>
                                                            </tr>
                                                            <tr>
                                                                <td width="16%"></td>
                                                                <td width="45%" style="text-align:left;">'.$Particulars.'</td>
                                                                <td width="39%" style="text-align:left;">'.number_format($Amount, 2).'</td>
                                                            </tr>
                                                            <tr>
                                                                <td width="61%" style="text-align:left;"></td>
                                                                <td width="39%" style="text-align:center;"></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td width="3%"></td>
                                                    <td width="97%">'.$AmountToWords.'</td>
                                                </tr>
                                                <tr>
                                                    <td width="100%" style="line-height: 15px"></td>
                                                </tr>
                                                <tr>
                                                    <td width="40%"><table border="1" style="font-size:8pt;">
                                                            <tr>
                                                                <td width="100%" style="line-height: 35px"></td>
                                                            </tr>
                                                            <tr>
                                                                <td width="7%" style="line-height: 22px;"></td>
                                                                <td width="93%" style="line-height: 22px;">'.$_SESSION["FULLNAME"].'</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td width="60%"><table border="1" style="font-size:8pt; padding-top: 5px; padding-bottom: 5px;">
                                                            <tr>
                                                                <td width="100%" style="line-height: 1px"></td>
                                                            </tr>
                                                            <tr>
                                                                <td width="41%" style="text-align:right;"></td>
                                                                <td width="59%" style="text-align:left;"> '.number_format($Amount,2).'</td>
                                                            </tr>
                                                        </table>                                                        
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td width="5%"></td>
                        </tr>
                    </table>
        ';

        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
		$pdf->Output('crbar.pdf', 'I');
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