<?php
require_once('../../assets/tcpdf/tcpdf.php');

class Reports extends Database 
{
    public function PrintJournalReport($purchaseSelect,$option,$fromAsOf,$toAsOf,$month){
        ob_clean();
        ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);
        
        $pdf = new TCPDF('L', PDF_UNIT, 'FOLIO', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle('JOURNAL REPORT');
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

        $contentheader = '';
        $contentdata = '';

        // Determine Report Details
        if ($purchaseSelect == "PJ") {
            $reportType = "PURCHASE JOURNAL";
            $table = "tbl_purchasejournal";
            $dateCol = "DatePurchase";
            $headers = ["Date", "Ref No.", "Supplier", "TIN", "Address", "Gross Purchase", "Input VAT", "Net Purchase"];
            $colKeys = ["DatePurchase", "Reference", "Supplier", "TIN", "Address", "GrossPurchase", "InputVAT", "NetPurchase"];
        } else {
            $reportType = "SALES JOURNAL";
            $table = "tbl_salesjournal";
            $dateCol = "DateSold";
            $headers = ["Date", "Ref No.", "Customer", "TIN", "Address", "Gross Sales", "Output VAT", "Net Sales"];
            $colKeys = ["DateSold", "Reference", "Customer", "TIN", "Address", "GrossSales", "VAT", "NetSales"];
        }

        // Fetch Branch Info
        $branchsetup = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigOwner = 'BRANCH SETUP' AND ConfigName IN ('ORGNAME', 'BRANCHADDRESS', 'BRANCHTELNO')");
        $branchsetup->execute();
        $bsresult = $branchsetup->get_result();
        $orgname = ''; $orgaddress = ''; $orgtelno = '';
        while ($bsrow = $bsresult->fetch_assoc()) {
            if ($bsrow['ConfigName'] == 'ORGNAME') $orgname = $bsrow["Value"];
            if ($bsrow['ConfigName'] == 'BRANCHADDRESS') $orgaddress = $bsrow["Value"];
            if ($bsrow['ConfigName'] == 'BRANCHTELNO') $orgtelno = $bsrow["Value"];
        }
        $branchsetup->close();
        $isynbranch = $orgname; 

        // Fetch Data
        if ($option == "month") {
            $stmt = $this->conn->prepare("SELECT * FROM $table WHERE DATE_FORMAT($dateCol, '%Y-%m') = DATE_FORMAT(STR_TO_DATE(?, '%m/%d/%Y'), '%Y-%m') ORDER BY $dateCol ASC");
            $stmt->bind_param("s", $month);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM $table WHERE DATE($dateCol) BETWEEN STR_TO_DATE(?, '%m/%d/%Y') AND STR_TO_DATE(?, '%m/%d/%Y') ORDER BY $dateCol ASC");
            $stmt->bind_param("ss", $fromAsOf, $toAsOf);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $totalGross = 0;
        $totalVAT = 0;
        $totalNet = 0;

        // Build Header Row
        $contentheader .= '<tr style="font-weight:bold;font-size:8pt;text-align:center;line-height:20px;">';
        $widths = [10, 10, 20, 10, 20, 10, 10, 10];
        foreach ($headers as $i => $h) {
             $contentheader .= '<td width="'.$widths[$i].'%" style="border: 1px solid black;">'.$h.'</td>';
        }
        $contentheader .= '</tr>';

        // Build Data Rows
        while ($row = $result->fetch_assoc()) {
            $gross = floatval($row[$colKeys[5]]);
            $vat = floatval($row[$colKeys[6]]);
            $net = floatval($row[$colKeys[7]]);
            
            $totalGross += $gross;
            $totalVAT += $vat;
            $totalNet += $net;

            $dateVal = date('m/d/Y', strtotime($row[$colKeys[0]]));

            $contentdata .= '<tr style="font-size:8pt;line-height:15px;">';
            $contentdata .= '<td style="text-align:center;border: 1px solid black;">'.$dateVal.'</td>'; 
            $contentdata .= '<td style="text-align:center;border: 1px solid black;">'.$row[$colKeys[1]].'</td>'; 
            $contentdata .= '<td style="text-align:left;border: 1px solid black;">'.$row[$colKeys[2]].'</td>'; 
            $contentdata .= '<td style="text-align:center;border: 1px solid black;">'.$row[$colKeys[3]].'</td>'; 
            $contentdata .= '<td style="text-align:left;border: 1px solid black;">'.$row[$colKeys[4]].'</td>'; 
            $contentdata .= '<td style="text-align:right;border: 1px solid black;">'.number_format($gross, 2).'</td>'; 
            $contentdata .= '<td style="text-align:right;border: 1px solid black;">'.number_format($vat, 2).'</td>'; 
            $contentdata .= '<td style="text-align:right;border: 1px solid black;">'.number_format($net, 2).'</td>'; 
            $contentdata .= '</tr>';
        }
        $stmt->close();

        // Total Row
        $contentdata .= '<tr style="font-weight:bold;font-size:8pt;line-height:20px;">';
        $contentdata .= '<td colspan="5" style="text-align:right;border: 1px solid black;">TOTAL</td>';
        $contentdata .= '<td style="text-align:right;border: 1px solid black;">'.number_format($totalGross, 2).'</td>';
        $contentdata .= '<td style="text-align:right;border: 1px solid black;">'.number_format($totalVAT, 2).'</td>';
        $contentdata .= '<td style="text-align:right;border: 1px solid black;">'.number_format($totalNet, 2).'</td>';
        $contentdata .= '</tr>';

        $content = '';
        $content .= '<table border="0">
                        <tr>
                            <td width="2%"></td>
                            <td width="98%" style="font-size:8pt;font-weight:bold;text-align:left;"><p>We make IT possible</p></td>
                        </tr>
                        <tr>
                            <td width="100%" style="font-family:centurynormal;font-size:20pt;font-weight:bold;text-align:left;"><p>iSynergies Inc.</p></td>
                        </tr>
                        <tr>
                            <td width="100%"><p style="font-size:8pt;text-align:left;">'.$orgaddress.'</p></td>
                        </tr>
                        <tr><td></td></tr>
                        <tr>
                            <td width="15%" style="font-size:9px;font-weight:bold;text-align:left;"><p>Report Type:</p></td>
                            <td width="30%" style="font-size:9px;font-weight:bold;text-align:left;"><p>'.$reportType.'</p></td>
                            <td width="55%"></td>
                        </tr>
                        <tr>
                            <td width="15%" style="font-size:9px;font-weight:bold;text-align:left;"><p>Isynergies Branch:</p></td>
                            <td width="30%" style="font-size:9px;font-weight:bold;text-align:left;"><p>'.$isynbranch.'</p></td>
                            <td width="55%"></td>
                        </tr>
                        <tr><td width="100%"></td></tr>
                        <tr><td width="100%"></td></tr>
                        '.$contentheader.'
                        '.$contentdata.'
                    </table>
        ';

        // logs($_SESSION['usertype'], "Printed JV Report", "btnJVPrint", $_SESSION['username'], "JV Reports");        
        
        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('journal_report.pdf', 'I');
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