<?php
require_once(__DIR__ . "/../../assets/tcpdf/tcpdf.php");
require_once(__DIR__ . "/../../database/connection.php");

class Reports extends Database 
{
    public function PrintOutgoingSalesInvoice($tableData,$SalesNoVAT,$SalesWithVAT,$SIRef,$DateAdded){
        ob_clean();
        ob_flush();

        ini_set('memory_limit','-1');
        set_time_limit(0);
        
        $transmittalNo = $_SESSION['TransmittalNO'] ?? '-';
        $salesInvoice = $_SESSION['SIRef'] ?? $SIRef;

        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle('TRANSMITTAL RECEIPT');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 10, 15);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // 1. Data Gathering
        $datesold = $DateAdded ?: "-";
        $soldto = "-";
        $from = "ISYNERGIES INC"; // Default sender
        $totalAmount = 0;

        if (is_array($tableData) && count($tableData) > 0) {
            $first = $tableData[0];
            if (isset($first[3]) && trim((string)$first[3]) !== '') {
                $soldto = trim((string)$first[3]);
            }
        }

        // If soldto is still missing, try lookup
        if ($soldto === "-") {
            $stmt = $this->conn->prepare("SELECT Soldto, User, DateAdded FROM tbl_inventoryout WHERE SI = ? LIMIT 1");
            $stmt->bind_param("s", $SIRef);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $soldto = $row["Soldto"] ?: $soldto;
                $from = $row["User"] ?: $from;
                $datesold = $row["DateAdded"] ?: $datesold;
            }
            $stmt->close();
        }

        // 2. Header: Logo, Address, and Title
        $logoPath = 'C:/xamppp/htdocs/isyn-app-v3/logo/complete-logo.png';
        if (!file_exists($logoPath)) {
            $logoPath = realpath(__DIR__ . '/../../logo/complete-logo.png');
        }

        $pdf->SetY(10);
        if ($logoPath && file_exists($logoPath)) {
            $logoWidth = 50; // mm
            $logoHeight = 15; // mm
            $logoX = ($pdf->getPageWidth() - $logoWidth) / 2;
            
            // Re-add extension check for PNG with alpha channel
            $supportsPngAlpha = extension_loaded('gd') || class_exists('Imagick');
            $isPng = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION)) === 'png';

            if ($isPng && !$supportsPngAlpha) {
                // Skip logo if extensions are missing to avoid crash
                $pdf->SetY(10);
            } else {
                try {
                    $pdf->Image($logoPath, $logoX, 10, $logoWidth, $logoHeight, '', '', '', false, 300, '', false, false, 0);
                    $pdf->SetY(26); // Move below logo
                } catch (\Exception $e) {
                    $pdf->SetY(10);
                }
            }
        } else {
            $pdf->SetY(10);
        }

        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 5, '105 Maharlika Highway, Cabanatuan City', 0, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 8, 'TRANSMITTAL RECEIPT', 0, 1, 'C');

        // 3. Header Section: TO, FROM, No, DATE
        $pdf->SetY(48);
        $pdf->SetFont('helvetica','',10);
        
        $htmlHeader = '
        <table border="0" cellpadding="2" style="width:100%;">
            <tr>
                <td width="10%">TO:</td>
                <td width="50%" style="border-bottom:0.5px solid #000;"><b>'.$soldto.'</b></td>
                <td width="10%">No.:</td>
                <td width="30%" style="border-bottom:0.5px solid #000;"><b>'.$transmittalNo.'</b></td>
            </tr>
            <tr>
                <td width="10%">FROM:</td>
                <td width="50%" style="border-bottom:0.5px solid #000;"><b>'.$from.'</b></td>
                <td width="10%">DATE:</td>
                <td width="30%" style="border-bottom:0.5px solid #000;"><b>'.$datesold.'</b></td>
            </tr>
            <tr>
                <td width="10%"></td>
                <td width="50%"></td>
                <td width="10%">SI:</td>
                <td width="30%" style="border-bottom:0.5px solid #000;"><b>'.$salesInvoice.'</b></td>
            </tr>
        </table>';
        $pdf->writeHTML($htmlHeader, true, false, true, false, '');

        // 4. Items Table
        $pdf->SetY(65);
        $contentRows = '';
        foreach ($tableData as $row) {
            $qty = $row[4];
            $particulars = $row[6];
            $amt = floatval(str_replace(",","",$row[8]));
            $totalAmount += $amt;

            $contentRows .= '
                <tr>
                    <td width="15%" style="text-align:center; border-right:0.5px solid #000; border-bottom:0.5px solid #000;">'.$qty.'</td>
                    <td width="65%" style="border-right:0.5px solid #000; border-bottom:0.5px solid #000;"> '.$particulars.'</td>
                    <td width="20%" style="text-align:right; border-bottom:0.5px solid #000;">'.number_format($amt,2).' </td>
                </tr>';
        }

        // Pad with empty rows to match the image look
        $padRows = max(10, 15 - count($tableData));
        for ($i=0; $i<$padRows; $i++) {
            $contentRows .= '
                <tr>
                    <td width="15%" style="border-right:0.5px solid #000; border-bottom:0.5px solid #000;"></td>
                    <td width="65%" style="border-right:0.5px solid #000; border-bottom:0.5px solid #000;"></td>
                    <td width="20%" style="border-bottom:0.5px solid #000;"></td>
                </tr>';
        }

        $htmlTable = '
        <table border="0.5" cellpadding="4" cellspacing="0" style="width:100%;">
            <tr style="font-weight:bold; font-size:9pt; text-align:center;">
                <td width="15%" style="border:0.5px solid #000;">QUANTITY</td>
                <td width="65%" style="border:0.5px solid #000;">PARTICULARS</td>
                <td width="20%" style="border:0.5px solid #000;">AMOUNT</td>
            </tr>
            '.$contentRows.'
            <tr style="font-weight:bold; font-size:10pt;">
                <td colspan="2" style="text-align:center; border-top:0.5px solid #000;">TOTAL</td>
                <td style="text-align:right; border-top:0.5px solid #000;">'.number_format($totalAmount, 2).'</td>
            </tr>
        </table>
        <div style="text-align:center; font-size:8pt; font-weight:bold; margin-top:5px;">Pls. write special instructions below</div>';
        
        $pdf->writeHTML($htmlTable, true, false, true, false, '');

        // 5. Footer Section: Carrier and Received by
        $pdf->SetY($pdf->GetY() + 15);
        $htmlFooter = '
        <table border="0" cellpadding="2" style="width:100%; font-size:10pt;">
            <tr>
                <td width="12%">Carrier:</td>
                <td width="38%" style="border-bottom:0.5px solid #000;"></td>
                <td width="15%">Received by:</td>
                <td width="35%" style="border-bottom:0.5px solid #000;"></td>
            </tr>
            <tr>
                <td width="12%">Date:</td>
                <td width="38%" style="border-bottom:0.5px solid #000;"></td>
                <td width="15%">Date:</td>
                <td width="35%" style="border-bottom:0.5px solid #000;"></td>
            </tr>
        </table>';
        $pdf->writeHTML($htmlFooter, true, false, true, false, '');

        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('sales_invoice.pdf', 'I');
    }

    public function PrintRecentInventoryOut($rows){
        ob_clean();
        ob_flush();
        ini_set('memory_limit','-1');
        set_time_limit(0);
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle('Recent Inventory Out');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(10, 12, 10);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();
        $content = '<table border="0" cellpadding="4">
            <tr>
                <td width="100%" style="font-size:14px;font-weight:bold;text-align:center;">RECENT STOCK OUT</td>
            </tr>
            <tr>
                <td width="100%" style="font-size:9px;text-align:center;">Generated '.date('m/d/Y').'</td>
            </tr>
            <tr><td width="100%"></td></tr>
        </table>';
        $content .= '<table border="1" cellpadding="4" cellspacing="0">
            <thead>
                <tr style="font-weight:bold;font-size:9pt;text-align:center;">
                    <td width="12%">Supplier SI</td>
                    <td width="12%">Serial No</td>
                    <td width="26%">Product</td>
                    <td width="8%">Qty</td>
                    <td width="12%">Unit Price</td>
                    <td width="12%">Amount</td>
                    <td width="18%">Sold To</td>
                </tr>
            </thead>
            <tbody>';
        foreach ($rows as $r) {
            $content .= '
                <tr style="font-size:8pt;">
                    <td width="12%" style="text-align:center;">'.htmlspecialchars($r[0]??'',ENT_QUOTES).'</td>
                    <td width="12%" style="text-align:center;">'.htmlspecialchars($r[1]??'',ENT_QUOTES).'</td>
                    <td width="26%">'.htmlspecialchars($r[2]??'',ENT_QUOTES).'</td>
                    <td width="8%" style="text-align:center;">'.htmlspecialchars($r[4]??'',ENT_QUOTES).'</td>
                    <td width="12%" style="text-align:right;">'.number_format(floatval(str_replace(",","",$r[7]??'0')),2).'</td>
                    <td width="12%" style="text-align:right;">'.number_format(floatval(str_replace(",","",$r[8]??'0')),2).'</td>
                    <td width="18%" style="text-align:center;">'.htmlspecialchars($r[3]??'',ENT_QUOTES).'</td>
                </tr>
                <tr style="font-size:7pt; color: #555;">
                    <td colspan="7">
                        <b>Category:</b> '.htmlspecialchars($r[16]??'',ENT_QUOTES).' | 
                        <b>Supplier:</b> '.htmlspecialchars($r[17]??'',ENT_QUOTES).' | 
                        <b>Warranty:</b> '.htmlspecialchars($r[18]??'',ENT_QUOTES).' |
                        <b>TIN:</b> '.htmlspecialchars($r[19]??'',ENT_QUOTES).' |
                        <b>Address:</b> '.htmlspecialchars($r[20]??'',ENT_QUOTES).'
                    </td>
                </tr>';
        }
        $content .= '</tbody></table>';
        $pdf->writeHTML($content, true, 0, true, 0);
        $pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('recent_inventory_out.pdf', 'I');
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