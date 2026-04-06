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
        
		$pdf = new TCPDF('P', PDF_UNIT, 'A5', true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('isynergiesinc');
		$pdf->SetTitle('SUPPLIER\'S RECEIPT');
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

        $datesold = "-";
        $soldto = "-";
        $tin = "-";
        $address = "-";
        $totalAmountDue = 0;

        $stmt = $this->conn->prepare("SELECT * FROM tbl_salesjournal WHERE STR_TO_DATE(DateSold,'%m/%d/%Y') = STR_TO_DATE(?,'%m/%d/%Y') AND Reference = ?");
        $stmt->bind_param("ss",$DateAdded,$SIRef);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $datesold = $row["DateSold"];
            $soldto = $row["Customer"];
            $tin = $row["TIN"];
            $address = $row["Address"];
        }

        $rowsqty = "8";

        foreach ($tableData as $row){
            $rowsqty--;
            $contentdata .= '
                    <tr style="font-size:6pt;">
                        <td style="text-align:center;">'.$row[4].'</td>
                        <td style="text-align:center;">'.$row[5].'</td>
                        <td style="">'.$row[6].'</td>
                        <td style="text-align:right;">'.number_format(str_replace(",","",$row[7]),2).'</td>
                        <td style="text-align:right;">'.number_format(str_replace(",","",$row[8]),2).'</td>
                    </tr>
            ';

            $totalAmountDue = floatval($totalAmountDue) + floatval(str_replace(",","",$row[8]));
        }

        for ($i = 0; $i < $rowsqty; $i++) {
            $contentdata .= '
                    <tr style="font-size:6pt;">
                        <td style="text-align:center;"></td>
                        <td style="text-align:center;"></td>
                        <td style=""></td>
                        <td style="text-align:right;"></td>
                        <td style="text-align:right;"></td>
                    </tr>
            ';
        }
        
        $content = '';

        $content .= '<table border="0">
                        <tr>
                            <td width="100%" style="line-height:100px;"></td>
                        </tr>
                        <tr>
                            <td width="70%" style="font-size:7px;"><table border="0">
                                    <tr>
                                        <td width="10%" style="font-weight:bold;">SOLD to </td>
                                        <td width="90%">'.$soldto.'</td>
                                    </tr>
                                </table>
                            </td>
                            <td width="30%" style="font-size:7px;"><table border="0">
                                    <tr>
                                        <td width="15%" style="font-weight:bold;">Date </td>
                                        <td width="85%">'.$datesold.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="100%" style="font-size:7px;"><table border="0">
                                    <tr>
                                        <td width="7%" style="font-weight:bold;">TIN </td>
                                        <td width="93%">'.$tin.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td width="100%" style="font-size:7px;"><table border="0">
                                    <tr>
                                        <td width="7%" style="font-weight:bold;">Address </td>
                                        <td width="93%">'.$address.'</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr><td width="100%"></td></tr>
                        <tr><td width="100%"></td></tr>
                        <tr style="font-weight:bold;font-size:6pt;text-align:center;">
                            <td width="10%">QUANTITY</td>
                            <td width="5%">UNIT</td>
                            <td width="65%">ARTICLES</td>
                            <td width="10%">UNIT PRICE</td>
                            <td width="10%">AMOUNT</td>
                        </tr>
                        '.$contentdata.'
                        <tr style="font-size:6pt;">
                            <td width="10%"></td>
                            <td width="5%"></td>
                            <td width="10%">VATable Sales</td>
                            <td width="55%">'.$SalesNoVAT.'</td>
                            <td width="10%"></td>
                            <td width="10%"></td>
                        </tr>
                        <tr style="font-size:6pt;">
                            <td width="10%"></td>
                            <td width="5%"></td>
                            <td width="10%"></td>
                            <td width="55%"></td>
                            <td width="10%"></td>
                            <td width="10%"></td>
                        </tr>
                        <tr style="font-size:6pt;">
                            <td width="10%"></td>
                            <td width="5%"></td>
                            <td width="10%"></td>
                            <td width="55%"></td>
                            <td width="10%"></td>
                            <td width="10%"></td>
                        </tr>
                        <tr style="font-size:6pt;">
                            <td width="10%"></td>
                            <td width="5%"></td>
                            <td width="10%">VAT Amount</td>
                            <td width="55%">'.$SalesWithVAT.'</td>
                            <td width="10%"></td>
                            <td width="10%"></td>
                        </tr>
                        <tr style="font-size:6pt;text-align:right;">
                            <td width="90%"></td>
                            <td width="10%">'.number_format($totalAmountDue,2).'</td>
                        </tr>
                    </table>
        ';

        // logs($_SESSION['usertype'], "Printed JV Report", "btnJVPrint", $_SESSION['username'], "JV Reports");        
        
        $pdf->writeHTML($content, true, 0, true, 0);
		$pdf->lastPage();
        $pdf->IncludeJS("print();");
        $pdf->Output('supplierreceipt.pdf', 'I');
    }

    public function PrintTransmittalByNo($transNo, $templatePath = null){
        ob_clean();
        ob_flush();
        ini_set('memory_limit','-1');
        set_time_limit(0);
        $pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle('Transmittal Receipt');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(15, 10, 15); // Increased side margins to 15mm to bring content inside
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        // Use the specific template file path if no template path provided
        if (!$templatePath) {
            // FORCE JPG TEMPLATE ONLY - Removing PDF support to prevent gray box issues
            $templatePath = realpath(__DIR__ . '/../../template/resibo.jpg');
            error_log("Forcing usage of JPG template: " . $templatePath);
        }
        
        // Debug: Log template path for troubleshooting
        error_log("Template path: " . $templatePath);
        error_log("Template exists: " . (file_exists($templatePath) ? 'Yes' : 'No'));
        
        // Check if file exists before processing
        if (!file_exists($templatePath)) {
            error_log("Template file not found: " . $templatePath);
            // Continue without template if file doesn't exist
        } else {
            // Get more details about the file
            $fileSize = filesize($templatePath);
            $filePerms = substr(sprintf('%o', fileperms($templatePath)), -4);
            error_log("Template file size: " . $fileSize . " bytes");
            error_log("Template file permissions: " . $filePerms);
            
            // Check if file is readable
            if (!is_readable($templatePath)) {
                error_log("Template file is not readable: " . $templatePath);
            }
        }

        // ====================================================================================
        // POSITIONING CONFIGURATION GUIDE - ADJUST THESE VALUES FOR PERFECT TEMPLATE ALIGNMENT
        // ====================================================================================
        
        // POSITIONING INSTRUCTIONS:
        // 1. Start with $textStartOffset - this controls where your text begins vertically
        // 2. Adjust column widths to match your template's pre-printed columns
        // 3. Use font sizes to match your template's text size
        // 4. Fine-tune with vertical offsets for specific elements
        
        // MEASUREMENT TIPS:
        // - Use a ruler to measure distances on your physical template
        // - All measurements are in pixels for text, millimeters for images
        // - Start with larger adjustments, then fine-tune with small changes
        // - Test frequently with small changes to avoid overshooting
        
        // COMMON ADJUSTMENTS:
        // - Text too high? Increase $textStartOffset
        // - Text too low? Decrease $textStartOffset  
        // - Columns misaligned? Adjust $quantityColumnWidth, $articlesColumnWidth, $amountColumnWidth
        // - Text too small/large? Change $fontSizeHeader, $fontSizeItems, $fontSizeLabels
        // - Specific field misaligned? Use its vertical offset variable
        
        // BASIC POSITIONING (Start with these)
        $templatePosition = [ 'x' => 0, 'y' => 0 ]; // Template position in mm (0,0 = top-left)
        $templateSize = [ 'width' => 210, 'height' => 297 ]; // Template size in mm (A4: 210x297)
        
        // FULL-SIZE TEMPLATE CONFIGURATION - ENSURES TEMPLATE COVERS ENTIRE PAGE
        $useFullSizeTemplate = true; // Set to true to make template full page size
        $templateScaleMode = 'FULL_PAGE'; // Options: 'FULL_PAGE', 'STRETCH', 'FIT', 'ORIGINAL'
        $templateStretchToFit = true; // Stretch template to cover entire page area
        
        // TEMPLATE SIZING GUIDE - Choose the best option for your template
        // 'FULL_PAGE' - Template covers entire A4 page (210x297mm) - RECOMMENDED
        // 'STRETCH' - Template stretches to fill page, may distort aspect ratio
        // 'FIT' - Template fits within page, maintains aspect ratio, may have borders
        // 'ORIGINAL' - Template at original size, may be smaller than page
        $textStartOffset = 95;
        
        // FINE-TUNING PARAMETERS - Adjust these for perfect alignment
        $headerSectionOffset = 0; // Additional space after template before header
        $itemsTableOffset = 0; // Additional space before items table starts
        $fontSizeHeader = "9pt"; // Header text size
        $fontSizeItems = "9pt"; // Items table text size
        $fontSizeLabels = "8pt"; // Column headers text size (QUANTITY, ARTICLES, AMOUNT)
        $fontSizeTransNo = "9pt"; // Transmittal number text size
        
        // COLUMN WIDTH CONFIGURATION - Match your template exactly
        // Based on A4 width and image layout
        $quantityColumnWidth = "12%"; // Width of QUANTITY column
        $articlesColumnWidth = "66%"; // Width of ARTICLES column  
        $amountColumnWidth = "22%"; // Width of AMOUNT column
        
        // ADVANCED POSITIONING - For precise alignment
        $leftMargin = "15"; // Increased left margin for A4
        $topMargin = "10"; // Top margin
        $headerVerticalSpacing = "8"; // Space between header lines (in pixels)
        $itemsRowHeight = "14"; // Height of each item row (in pixels)
        $totalRowSpacing = "10"; // Space before total amount row
        
        // POSITIONING HELPERS - Use these to fine-tune specific elements
        $transmittalNoVerticalOffset = -3; // Move Transmittal No up/down
        $toFieldVerticalOffset = -2; // Move To field up/down
        $fromFieldVerticalOffset = -2; // Move From field up/down
        $dateFieldVerticalOffset = 0; // Move Date field up/down
        $itemsTableVerticalOffset = -14; // Move entire items table up/down
        $totalAmountVerticalOffset = -36; // Move total amount up/down
        
        // Apply template first (if exists) as background
        $useCodeTemplateOnly = true;
        if (!$useCodeTemplateOnly && $templatePath && file_exists($templatePath)) {
            $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));
            
            // Log template sizing configuration
            error_log("=== TEMPLATE SIZING CONFIGURATION ===");
            error_log("Template scaling mode: " . ($useFullSizeTemplate ? "FULL_SIZE" : "CUSTOM") . " (" . $templateScaleMode . ")");
            error_log("Template position: (" . $templatePosition['x'] . ", " . $templatePosition['y'] . ")");
            error_log("Template size: (" . $templateSize['width'] . "x" . $templateSize['height'] . ")");
            error_log("Target page size: A5 (148x210mm)");
            
            try {
                // Use sizing helper for consistent template sizing
                $dimensions = $this->getTemplateDimensions($templateScaleMode, 210, 297);
                
                // Simplified Image Rendering - Supports JPG, JPEG, PNG
                error_log("✓ Image template detected: " . $templatePath . " (Format: " . strtoupper($ext) . ")");
                
                if ($useFullSizeTemplate) {
                    // Force full page coverage - WIDER to crop gray bar
                    // Using 220mm width to push the right-side gray bar off the page
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        $pdf->Image($templatePath, 0, 0, 220, 297, 'JPG', '', '', false, 300, '', false, false, 0);
                    } elseif ($ext === 'png') {
                        $pdf->Image($templatePath, 0, 0, 220, 297, 'PNG', '', '', false, 300, '', false, false, 0);
                    } else {
                        // Fallback for other formats
                        $pdf->Image($templatePath, 0, 0, 220, 297, '', '', '', false, 300, '', false, false, 0);
                    }
                    error_log("✓ Image template applied WIDER (220mm) to crop gray sidebar");
                } else {
                    // Use calculated dimensions from helper
                    if ($ext === 'jpg' || $ext === 'jpeg') {
                        error_log("✓ Using optimized JPG rendering");
                        $pdf->Image($templatePath, $dimensions['x'], $dimensions['y'], $dimensions['width'], $dimensions['height'], 'JPG', '', '', false, 300, '', false, false, 0);
                    } elseif ($ext === 'png') {
                        error_log("✓ Using optimized PNG rendering");
                        $pdf->Image($templatePath, $dimensions['x'], $dimensions['y'], $dimensions['width'], $dimensions['height'], 'PNG', '', '', false, 300, '', false, false, 0);
                    } else {
                        $pdf->Image($templatePath, $dimensions['x'], $dimensions['y'], $dimensions['width'], $dimensions['height'], '', '', '', false, 300, '', false, false, 0);
                    }
                    error_log("✓ Image template applied successfully at position (" . $dimensions['x'] . ", " . $dimensions['y'] . ") with size (" . $dimensions['width'] . "x" . $dimensions['height'] . ")");
                }
            } catch (\Exception $e) {
                error_log("Template processing error: " . $e->getMessage());
                // Continue without template if there's an error
            }
        } else {
            error_log("No template applied - file not found or path invalid");
        }

        $header = [
            "TransmittalNO" => $transNo,
            "NameTO" => "-",
            "NameFROM" => "-",
            "DatePrepared" => "-",
            "Carrier" => "-",
            "DateCarrier" => "-",
            "ReceivedBy" => "-",
            "DateReceived" => "-",
            "Remarks" => "-",
        ];
        $items = [];
        $totalAmount = 0.0;

        $stmt = $this->conn->prepare("SELECT * FROM tbl_transmittal WHERE TransmittalNO = ?");
        $stmt->bind_param("s", $transNo);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if ($header["NameTO"] === "-" && !empty($row["NameTO"])) $header["NameTO"] = $row["NameTO"];
            if ($header["NameFROM"] === "-" && !empty($row["NameFROM"])) $header["NameFROM"] = $row["NameFROM"];
            if ($header["DatePrepared"] === "-" && !empty($row["DatePrepared"])) $header["DatePrepared"] = $row["DatePrepared"];
            if ($header["Carrier"] === "-" && !empty($row["Carrier"])) $header["Carrier"] = $row["Carrier"];
            if ($header["DateCarrier"] === "-" && !empty($row["DateCarrier"])) $header["DateCarrier"] = $row["DateCarrier"];
            if ($header["ReceivedBy"] === "-" && !empty($row["ReceivedBy"])) $header["ReceivedBy"] = $row["ReceivedBy"];
            if ($header["DateReceived"] === "-" && !empty($row["DateReceived"])) $header["DateReceived"] = $row["DateReceived"];
            if ($header["Remarks"] === "-" && !empty($row["Remarks"])) $header["Remarks"] = $row["Remarks"];
            $qty = isset($row["Quantity"]) ? $row["Quantity"] : (isset($row["InOrder"]) ? $row["InOrder"] : 0);
            $article = isset($row["Product"]) ? $row["Product"] : (isset($row["ProductSerialNo"]) ? $row["ProductSerialNo"] : "");
            $amount = isset($row["Amount"]) ? floatval($row["Amount"]) : 0.0;
            $items[] = [
                "Quantity" => $qty,
                "Article" => $article,
                "Amount" => $amount,
            ];
            $totalAmount += $amount;
        }
        $stmt->close();

        // Add centered logo at the very top (with resilient rendering)
        $rootPath = dirname(__DIR__, 2);
        $logoPath = $rootPath . '/logo/complete-logo.png';
        if (!file_exists($logoPath)) {
            $logoPath = $rootPath . '/assets/images/complete-logo.png';
        }

        if (file_exists($logoPath)) {
            $logoWidth = 60; // mm
            $logoHeight = 18; // mm fixed height to allow precise address placement
            $logoX = ($pdf->getPageWidth() - $logoWidth) / 2;
            $logoY = 12; // mm from top
            
            try {
                $supportsPngAlpha = extension_loaded('gd') || class_exists('Imagick');
                $logoExt = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                
                if ($logoExt === 'png' && !$supportsPngAlpha) {
                    $logoJpg = preg_replace('/\.png$/i', '.jpg', $logoPath);
                    if (file_exists($logoJpg)) {
                        $pdf->Image($logoJpg, $logoX, $logoY, $logoWidth, $logoHeight, 'JPG', '', '', false, 300, '', false, false, 0);
                    }
                } else {
                    $pdf->Image($logoPath, $logoX, $logoY, $logoWidth, $logoHeight, '', '', '', false, 300, '', false, false, 0);
                }
                
                // Address just under logo, centered
                $pdf->SetFont('helvetica','',10);
                $pdf->SetXY(0, $logoY + $logoHeight + 1);
                $pdf->Cell($pdf->getPageWidth(), 5, '105 Maharlika Highway, Cabanatuan City', 0, 1, 'C');
                // Title under address
                $pdf->SetFont('helvetica','B',18);
                $pdf->SetXY(0, $logoY + $logoHeight + 7);
                $pdf->Cell($pdf->getPageWidth(), 7, 'TRANSMITTAL RECEIPT', 0, 1, 'C');
            } catch (\Exception $e) {
                error_log('Logo render error: '.$e->getMessage());
            }
        }

        $contentRows = '';
        foreach ($items as $it) {
            $contentRows .= '
                <tr style="font-size:'.$fontSizeItems.'; font-weight: normal;">
                    <td width="'.$quantityColumnWidth.'" style="text-align:center;">'.$it["Quantity"].'</td>
                    <td width="'.$articlesColumnWidth.'" style="padding-left:15px;">'.$it["Article"].'</td>
                    <td width="'.$amountColumnWidth.'" style="text-align:left; padding-left:5px;">'.number_format($it["Amount"],2).'</td>
                </tr>';
        }
        for ($i = count($items); $i < 10; $i++) {
            $contentRows .= '
                <tr style="font-size:'.$fontSizeItems.'; font-weight: normal;">
                    <td width="'.$quantityColumnWidth.'" style="text-align:center;"></td>
                    <td width="'.$articlesColumnWidth.'" style="padding-left:15px;"></td>
                    <td width="'.$amountColumnWidth.'" style="text-align:left; padding-left:5px;"></td>
                </tr>';
        }
        
        // Build content using code-only template
        $content = '
            <table border="0" style="width:100%; position: relative; z-index: 10;">
                <!-- Transmittal No moved to right header block to align with FROM -->
                <tr style="font-size:'.$fontSizeHeader.';">
                    <td width="65%" colspan="2">
                        <table border="0" cellpadding="2" style="width:100%;">




                        
                            <tr>
                                <td width="18%" style="white-space:nowrap; text-align:left;">TO:</td>
                                <td width="82%" style="border-bottom:1px solid #000; line-height:'.(18 + $toFieldVerticalOffset).'px; white-space:nowrap;">'.$header["NameTO"].'</td>
                            </tr>
                            <tr>
                                <td width="18%" style="white-space:nowrap; text-align:left;">FROM:</td>
                                <td width="82%" style="border-bottom:1px solid #000; line-height:'.(18 + $fromFieldVerticalOffset).'px; white-space:nowrap;">'.$header["NameFROM"].'</td>
                            </tr>
                        </table>
                    </td>
                    <td width="35%">
                        <table border="0" cellpadding="2" style="width:100%;">
                            <tr>
                                <td width="30%" style="white-space:nowrap; text-align:left;">No.:</td>
                                <td width="70%" style="border-bottom:1px solid #000; line-height:'.(18 + $toFieldVerticalOffset).'px; vertical-align:bottom;"><span style="font-size:'.$fontSizeTransNo.';">'.$header["TransmittalNO"].'</span></td>
                            </tr>
                            <tr>
                                <td width="30%" style="white-space:nowrap; text-align:left;">DATE:</td>
                                <td width="70%" style="border-bottom:1px solid #000; line-height:'.(18 + $toFieldVerticalOffset).'px; vertical-align:bottom;">'.$header["DatePrepared"].'</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr><td width="100%" colspan="3" style="line-height:'.(40 + $itemsTableVerticalOffset).'px;"></td></tr>
            </table>
            <table border="1" cellpadding="3" cellspacing="0" style="border-collapse:collapse; width:100%; font-size:'.$fontSizeItems.';">
                <tr style="font-weight:bold; text-align:center;">
                    <td width="'.$quantityColumnWidth.'">QUANTITY</td>
                    <td width="'.$articlesColumnWidth.'">PARTICULARS</td>
                    <td width="'.$amountColumnWidth.'">AMOUNT</td>
                </tr>
                '.$contentRows.'
                <tr style="font-weight:bold;">
                    <td colspan="2" style="text-align:center;">TOTAL</td>
                    <td width="'.$amountColumnWidth.'" style="text-align:right; padding-right:5px; padding-top:'.$totalAmountVerticalOffset.'px;">'.number_format($totalAmount,2).'</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:center;">'.$header["Remarks"].'</td>
                </tr>
            </table>
            <table border="0" style="width:100%;"><tr><td style="line-height:20px;"></td></tr></table>
            <table border="0" style="width:100%; font-size:10pt; margin-top:20px;">
                <tr style="line-height:20px;">
                    <td width="50%">Carrier: <span style="border-bottom:1px solid #000;">'.$header["Carrier"].'</span></td>
                    <td width="50%" style="text-align:right">Received by: <span style="border-bottom:1px solid #000;">'.$header["ReceivedBy"].'</span></td>
                </tr>
                <tr style="line-height:20px;">
                    <td width="50%">Date: <span style="border-bottom:1px solid #000;">'.$header["DateCarrier"].'</span></td>
                    <td width="50%" style="text-align:right">Date: <span style="border-bottom:1px solid #000;">'.$header["DateReceived"].'</span></td>
                </tr>
            </table>
        ';

        // OPTIONAL: Enable positioning guide for debugging (uncomment to use)
        // $this->showPositioningGuide($pdf);
        
        // Log current positioning configuration for debugging
        error_log("=== CURRENT POSITIONING CONFIGURATION ===");
        error_log("Template Position: (" . $templatePosition['x'] . ", " . $templatePosition['y'] . ")");
        error_log("Template Size: (" . $templatePosition['x'] . "x" . $templatePosition['y'] . ")");
        error_log("Text Start Offset: " . $textStartOffset . "px");
        error_log("Column Widths - QTY: " . $quantityColumnWidth . ", Articles: " . $articlesColumnWidth . ", Amount: " . $amountColumnWidth);
        error_log("Font Sizes - Header: " . $fontSizeHeader . ", Items: " . $fontSizeItems . ", Labels: " . $fontSizeLabels);
        
        // Use WriteHTML with transparency preservation
        error_log("About to write HTML content: " . strlen($content) . " characters");
        $pdf->writeHTML($content, true, false, true, false, '');
        error_log("HTML content written successfully");
        $pdf->lastPage();
        $pdf->IncludeJS("print()");
        $pdf->Output('transmittal.pdf', 'I');
    }
    
    // POSITIONING DEBUG HELPER - Use this to find perfect coordinates
    private function showPositioningGuide($pdf) {
        // This function adds positioning guides to help with alignment
        // Uncomment the lines below to enable positioning guides
        
        // Add grid lines for reference (uncomment to enable)
        // $pdf->SetDrawColor(200, 200, 200); // Light gray
        // for ($i = 0; $i <= 150; $i += 10) {
        //     $pdf->Line($i, 0, $i, 210); // Vertical lines every 10mm
        //     $pdf->Line(0, $i, 148, $i); // Horizontal lines every 10mm
        // }
        
        // Add measurement markers (uncomment to enable)
        // $pdf->SetDrawColor(255, 0, 0); // Red
        // $pdf->SetFont('helvetica', 'B', 8);
        // for ($i = 0; $i <= 150; $i += 20) {
        //     $pdf->Text($i, 5, $i . 'mm'); // Top ruler
        //     $pdf->Text(5, $i, $i . 'mm'); // Left ruler
        // }
        
        error_log("Positioning guide: Enable the commented code above to show alignment guides");
    }

    // Function to determine the best font size
    private function adjustFontSizeToFitWidth($text, $maxWidth, $initialFontSize, $minFontSize, $characterWidthFactor) {
        $fontSize = $initialFontSize;

        while ($this->calculateTextWidth($text, $fontSize, $characterWidthFactor) > $maxWidth && $fontSize > $minFontSize) {
            $fontSize -= 0.5; // Decrease font size incrementally
        }

        return $fontSize;
    }
    
    // TEMPLATE SIZING HELPER - Provides different sizing options for templates
    private function getTemplateDimensions($mode, $pageWidth, $pageHeight, $templateOriginalWidth = null, $templateOriginalHeight = null) {
        switch ($mode) {
            case 'FULL_PAGE':
                // Template covers entire page (may stretch/distort)
                return ['width' => $pageWidth, 'height' => $pageHeight, 'x' => 0, 'y' => 0];
                
            case 'FIT':
                // Template fits within page, maintains aspect ratio
                if ($templateOriginalWidth && $templateOriginalHeight) {
                    $aspectRatio = $templateOriginalWidth / $templateOriginalHeight;
                    $pageAspectRatio = $pageWidth / $pageHeight;
                    
                    if ($aspectRatio > $pageAspectRatio) {
                        // Template is wider than page
                        $width = $pageWidth;
                        $height = $pageWidth / $aspectRatio;
                        $x = 0;
                        $y = ($pageHeight - $height) / 2; // Center vertically
                    } else {
                        // Template is taller than page
                        $height = $pageHeight;
                        $width = $pageHeight * $aspectRatio;
                        $x = ($pageWidth - $width) / 2; // Center horizontally
                        $y = 0;
                    }
                    return ['width' => $width, 'height' => $height, 'x' => $x, 'y' => $y];
                }
                // Fallback to full page if original dimensions unknown
                return ['width' => $pageWidth, 'height' => $pageHeight, 'x' => 0, 'y' => 0];
                
            case 'STRETCH':
                // Template stretches to fill page exactly (may distort)
                return ['width' => $pageWidth, 'height' => $pageHeight, 'x' => 0, 'y' => 0];
                
            case 'ORIGINAL':
            default:
                // Template at original size, centered on page
                if ($templateOriginalWidth && $templateOriginalHeight) {
                    $x = ($pageWidth - $templateOriginalWidth) / 2;
                    $y = ($pageHeight - $templateOriginalHeight) / 2;
                    return ['width' => $templateOriginalWidth, 'height' => $templateOriginalHeight, 'x' => $x, 'y' => $y];
                }
                // Fallback to full page if original dimensions unknown
                return ['width' => $pageWidth, 'height' => $pageHeight, 'x' => 0, 'y' => 0];
        }
    }

    // Function to estimate text width based on character count and font size
    private function calculateTextWidth($text, $fontSize, $characterWidthFactor) {
        return strlen($text) * $fontSize * $characterWidthFactor;
    }

}
?>
