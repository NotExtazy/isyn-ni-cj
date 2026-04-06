<?php
require_once(__DIR__ . "/../../assets/tcpdf/tcpdf.php");
require_once(__DIR__ . "/../../database/connection.php");

class Reports extends Database 
{
    public function PrintSupplierReceipt($tableData){
        ob_clean();
		ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);
        
		$pdf = new TCPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('INVENTORY LIST');
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

        $SINo = "-";
        $totalUnitPrice = 0;
        $totalAmount = 0;
        $totalVATSales = 0;
        $totalVAT = 0;
        $totalTotal = 0;

        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO','AASIG','BKSIG','BMSIG')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();

        $orgname = '';
        $orgaddress = '';
        $orgtelno = '';

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
            }
        }
        $branchsetup->close();

        foreach ($tableData as $row){
            // Adjust indices based on the actual array structure from dataInvTbl
            // 0: SINo, 1: SerialNo, 2: Product, 3: Supplier, 4: Category, 5: Type, 6: Branch, 
            // 7: DatePurchase, 8: Warranty, 9: DateAdded, 10: Quantity, 11: DealerPrice, 
            // 12: SRP, 13: TotalPrice, 14: TotalSRP, 15: Markup, 16: TotalMarkup, 
            // 17: VatSales, 18: Vat, 19: AmountDue, 20: imgname

            $SINo = $row[0];
            
            $contentdata .= '
                    <tr style="font-size:9pt;">
                        <td style="text-align:center;border: 1px solid black;">'.$row[0].'</td>
                        <td style="text-align:center;border: 1px solid black;">'.$row[1].'</td>
                        <td style="border: 1px solid black;">'.$row[2].'</td>
                        <td style="border: 1px solid black;">'.$row[3].'</td>
                        <td style="text-align:center;border: 1px solid black;">'.$row[7].'</td>
                        <td style="text-align:center;border: 1px solid black;">'.$row[10].'</td>
                        <td style="text-align:right;border: 1px solid black;">'.number_format(floatval(str_replace(",","",$row[11])),2).'</td>
                        <td style="text-align:right;border: 1px solid black;">'.number_format(floatval(str_replace(",","",$row[13])),2).'</td>
                        <td style="text-align:right;border: 1px solid black;">'.number_format(floatval(str_replace(",","",$row[17])),2).'</td>
                        <td style="text-align:right;border: 1px solid black;">'.number_format(floatval(str_replace(",","",$row[18])),2).'</td>
                        <td style="text-align:right;border: 1px solid black;">'.number_format(floatval(str_replace(",","",$row[19])),2).'</td>
                    </tr>
            ';

            $totalUnitPrice = floatval($totalUnitPrice) + floatval(str_replace(",","",$row[11]));
            $totalAmount = floatval($totalAmount) + floatval(str_replace(",","",$row[13]));
            $totalVATSales = floatval($totalVATSales) + floatval(str_replace(",","",$row[17]));
            $totalVAT = floatval($totalVAT) + floatval(str_replace(",","",$row[18]));
            $totalTotal = floatval($totalTotal) + floatval(str_replace(",","",$row[19]));
        }
        
        $content = '';

        $content .= '<table border="0" cellpadding="2">
                        <tr>
                            <td width="100%" style="font-size:8pt;font-weight:bold;text-align:left;">We make IT possible</td>
                        </tr>
                        <tr>
                            <td width="100%" style="font-family:centurynormal;font-size:16pt;font-weight:bold;text-align:left;">iSynergies Inc.</td>
                        </tr>
                        <tr>
                            <td width="100%" style="font-size:8pt;text-align:left;">'.$orgaddress.'</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="100%" style="font-size:14pt;font-weight:bold;text-align:center;">INVENTORY LIST</td>
                        </tr>
                        <tr><td></td></tr>
                        <tr style="font-weight:bold;font-size:9pt;text-align:center;background-color:#f0f0f0;">
                            <td width="8%" style="border: 1px solid black;">SI No.</td>
                            <td width="8%" style="border: 1px solid black;">Serial No.</td>
                            <td width="18%" style="border: 1px solid black;">Product Name</td>
                            <td width="14%" style="border: 1px solid black;">Supplier</td>
                            <td width="8%" style="border: 1px solid black;">Purchase Date</td>
                            <td width="4%" style="border: 1px solid black;">Qty</td>
                            <td width="8%" style="border: 1px solid black;">Unit Price</td>
                            <td width="8%" style="border: 1px solid black;">Total Price</td>
                            <td width="8%" style="border: 1px solid black;">VAT Sales</td>
                            <td width="8%" style="border: 1px solid black;">VAT</td>
                            <td width="8%" style="border: 1px solid black;">Amount Due</td>
                        </tr>
                        '.$contentdata.'
                        <tr style="font-weight:bold;font-size:9.5pt;text-align:right;background-color:#e0e0e0;">
                            <td width="56%" style="border: 1px solid black;text-align:center;">GRAND TOTAL</td>
                            <td width="4%" style="border: 1px solid black;text-align:center;"></td>
                            <td width="8%" style="border: 1px solid black;">'.number_format($totalUnitPrice,2).'</td>
                            <td width="8%" style="border: 1px solid black;">'.number_format($totalAmount,2).'</td>
                            <td width="8%" style="border: 1px solid black;">'.number_format($totalVATSales,2).'</td>
                            <td width="8%" style="border: 1px solid black;">'.number_format($totalVAT,2).'</td>
                            <td width="8%" style="border: 1px solid black;">'.number_format($totalTotal,2).'</td>
                        </tr>
                    </table>
        ';

        // logs($_SESSION['usertype'], "Printed Inventory List", "btnPrint", $_SESSION['username'], "Inventory Reports");        
        
        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
        $pdf->IncludeJS("print();");
		$pdf->Output('inventory_list.pdf', 'I');
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