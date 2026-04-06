<?php
// 1. Start Session to access User ID for logs
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('../../assets/tcpdf/tcpdf.php');
include_once("../../database/connection.php");

class MYPDF extends TCPDF {
    public function Header() {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set background image
        $width = $this->getPageWidth();
        $height = $this->getPageHeight();
        // $width = 217;
        // $height = 133;
        $img_file = '../../assets/images/cert_bg.jpg';
        $this->Image($img_file, 0, 0, $width, $height, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
    }
}

class Reports extends Database 
{
    // CONFIGURATION: Set to ID 19 (Shareholder Information)
    private $logModuleId = 19; 

    // ==========================================
    //  PRINT CERTIFICATE (With Logging)
    // ==========================================
    public function PrintCertificate($shno, $format, $certId = null){
        // $fontname = TCPDF_FONTS::addTTFfont('C:/xampp/htdocs/GitHub/IsynAppV3/assets/tcpdf/fonts/eras-itc-bold.ttf', '', 96);

        ob_clean();
        ob_flush();

        $SIGN = $this->GetSHCERTSignatories();
        $sign2Name = "";
        $sign2Desig = "";

        $fullname = "";
        $NoOfShare = "";
        $certNo = "";
        $OtherSign = "";
        $dateIssued = "";

        // FETCH SHAREHOLDER INFO
        $stmt = $this->conn->prepare("SELECT * FROM tbl_shareholder_info WHERE shareholderNo = ? LIMIT 1");
        $stmt->bind_param("s" ,$shno);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $fullname = $row["fullname"];
            $OtherSign = $row["OtherSignatories"];
            
            // Default to main table if no certId (Legacy/Total)
            $NoOfShare = $row["noofshare"];
            $certNo = $row["cert_no"];
            $dateIssued = $row["dateEncoded"];
        }
        $stmt->close();

        // FETCH SPECIFIC CERTIFICATE IF ID PROVIDED
        if ($certId) {
            $stmt = $this->conn->prepare("SELECT cert_no, noofshare, date_issued FROM tbl_sharecert_issuances WHERE id = ?");
            $stmt->bind_param("i", $certId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $cRow = $res->fetch_assoc();
                $NoOfShare = $cRow['noofshare'];
                $certNo = $cRow['cert_no'];
                $dateIssued = $cRow['date_issued'];
            }
            $stmt->close();
        }

        // Parse Date for "Day of Month"
        $day = "___";
        $month = "________";
        $year = "____";
        
        if($dateIssued){
            $ts = strtotime($dateIssued);
            if($ts){
                $day = date('jS', $ts); // 1st, 2nd
                $month = date('F', $ts); // January
                $year = date('Y', $ts);
            }
        }

        $certPrinted = "Yes";
        $stmt = $this->conn->prepare("UPDATE tbl_shareholder_info SET certPrinted = ? WHERE shareholderNo = ?");
        $stmt->bind_param("ss", $certPrinted,$shno);
        $stmt->execute();   
        $stmt->close();

        $nameLenght = strlen($fullname); // name max lenght is 26

        if ($OtherSign == "Yes") {
            $sign2Name = $SIGN["SIGNATORYSUB2NAME"];
            $sign2Desig = $SIGN["SIGNATORYSUB2DESIG"];
        } else {
            $sign2Name = $SIGN["SIGNATORY2NAME"];
            $sign2Desig = $SIGN["SIGNATORY2DESIG"];
        }
        
        $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('SHAREHOLDER CERTIFICATE');
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->SetDefaultMonospacedFont('helvetica');       
        $pdf->SetMargins('14', '7', '13');        
        $pdf->SetAutoPageBreak(TRUE, 10);
        $pdf->AddPage();

        // $pdf->AddPage() is already called above

        $content = 
                    '
                        <table border="0">
                            <tr>
                                <td width="10%"></td>
                                <td width="80%" style="text-align:center;"><img src="../../assets/images/complete-logo.png" style="width:200px"></td>
                                <td width="10%"></td>
                            </tr>
                            <tr><td></td></tr>
                            <tr>
                                <td width="5%"></td>
                                <td width="20%" style="font-size:12px;">NUMBER : <span style="font-family: brushsci; font-size:18px;">'.htmlspecialchars($certNo, ENT_QUOTES, 'UTF-8').'</span></td>
                                <td width="50%" style="text-align:center;"></td>
                                <td width="20%" style="text-align:right;">SHARES : <span style="font-family: brushsci; font-size:18px;">'.htmlspecialchars($NoOfShare, ENT_QUOTES, 'UTF-8').'</span></td>
                                <td width="5%"></td>
                            </tr>
                            ';

                            if ($format == "10M") {
                            $content .= '<tr>
                                            <td width="5%"></td>
                                            <td width="90%" style="text-align:center;font-family: mtcorsva; font-size:16px;">Authorized Capital Stock of Ten Million Pesos (10,000,000.00)</td>
                                            <td width="5%"></td>
                                        </tr>
                                        ';
                            } elseif ($format == "4M") {
                            $content .= '<tr>
                                            <td width="15%"></td>
                                            <td width="70%" style="text-align:center;font-family: mtcorsva; font-size:16px;">Authorized Capital Stock of Four Million Pesos (4,000,000.00)</td>
                                            <td width="15%"></td>
                                        </tr>
                                        ';

                            }

                $content .= '<tr><td></td></tr>';
                $content .= '<tr><td></td></tr>';
                            
                            if ($nameLenght < 27) {
                                $content .= '
                                                <tr>
                                                    <td width="2%"></td>
                                                    <td width="22%" style="font-family: oldengl; font-size:18px; text-align:right;" valign="bottom">This Certifies that&nbsp;</td>
                                                    <td width="41%" style="border-bottom: solid black 1px;text-align:center;" valign="bottom"><span style="font-family: mtcorsva; font-size:22px;">'.htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8').'</span></td>
                                                    <td width="15%" style="text-align:center;font-family: mtcorsva; font-size:20px;" valign="bottom">is the owner of</td>
                                                    <td width="15%" style="border-bottom: solid black 1px;text-align:center;" valign="bottom"><span style="font-family: brushsci; font-size:22px;">'.htmlspecialchars($NoOfShare, ENT_QUOTES, 'UTF-8').'</span></td>
                                                    <td width="5%"></td>
                                                </tr>
                                                <tr>
                                                    <td width="100%" colspan="6" style="text-align:center;font-family: mtcorsva; font-size:20px;">Shares of the Capital Stock of</td>
                                                </tr>
                                                <tr><td><br></td></tr>
                                            '; 
                            } else {

                                $maxWidthMm = 110; 
                                $initialFontSizePt = 22; 
                                $minFontSizePt = 12; 
                                $characterWidthFactor = 0.4; 

                                $adjustedFontSize = $this->adjustFontSizeToFitWidth($fullname, $maxWidthMm, $initialFontSizePt, $minFontSizePt, $characterWidthFactor);

                                $content .= '
                                                <tr>
                                                    <td width="2%"></td>
                                                    <td width="22%" style="font-family: oldengl; font-size:18px; text-align:right;" valign="bottom">This Certifies that&nbsp;</td>
                                                    <td width="41%" style="border-bottom: solid black 1px;text-align:center;font-family: mtcorsva; font-size:'.$adjustedFontSize.'px;" valign="bottom">'.htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8').'</td>
                                                    <td width="15%" style="text-align:center;font-family: mtcorsva; font-size:20px;" valign="bottom">is the owner of</td>
                                                    <td width="15%" style="border-bottom: solid black 1px;text-align:center;" valign="bottom"><span style="font-family: brushsci; font-size:22px;">'.htmlspecialchars($NoOfShare, ENT_QUOTES, 'UTF-8').'</span></td>
                                                    <td width="5%"></td>
                                                </tr>
                                                <tr>
                                                    <td width="100%" colspan="6" style="text-align:center;font-family: mtcorsva; font-size:20px;">Shares of the Capital Stock of</td>
                                                </tr>
                                                <tr><td><br></td></tr>
                                            '; 
                            }

                $content .= '<tr><td width="100%"></td></tr>
                            <tr>
                                <td width="25%"></td>
                                <td width="50%" style="text-align:center;font-family:erasitcb;font-size:30px;">iSynergies Inc.,</td>
                                <td width="25%"></td>
                            </tr>
                            <tr><td width="100%"><br><br></td></tr>
                            <tr>
                                <td width="15%"></td>
                                <td width="70%" style="text-align:center;font-family: mtcorsva;font-size:13px;">Transferable only on the books of the Corporation by the holder hereof in person or by Attorney, upon surrender of this Certificate properly endorsed.</td>
                                <td width="15%"></td>
                            </tr>
                            
                            <tr><td width="100%"><br></td></tr>
                            <tr>
                                <td width="15%"></td>
                                <td width="70%" style="text-align:center;"><span style="font-family: oldengl;font-size:12px;">In Witness Whereof,</span> <span style="font-family: mtcorsva;font-size:13px;">the said Corporation has caused this Certificate  to be signed by its duly authorized officers and to be sealed with the Seal of the Corporation this <?php echo $day; ?> day of <?php echo $month; ?> A.D <?php echo $year; ?>.</span></td>
                                <td width="15%"></td>
                            </tr>
                            <tr><td width="100%"><br><br><br></td></tr>
                            <tr>
                                <td width="20%"></td>
                                <td width="20%" style="text-align:center;font-family: mtcorsva;font-size:16px;border-bottom: 1px solid black;">'.htmlspecialchars($SIGN["SIGNATORY1NAME"], ENT_QUOTES, 'UTF-8').'</td>
                                <td width="20%" style="text-align:center;"></td>
                                <td width="20%" style="text-align:center;font-family: mtcorsva;font-size:16px;border-bottom: 1px solid black;">'.htmlspecialchars($sign2Name, ENT_QUOTES, 'UTF-8').'</td>
                                <td width="20%" style="text-align:center;"></td>
                            </tr>
                            <tr>
                                <td width="20%"></td>
                                <td width="20%" style="text-align:center;font-family: mtcorsva;font-size:12px;">'.htmlspecialchars($SIGN["SIGNATORY1DESIG"], ENT_QUOTES, 'UTF-8').'</td>
                                <td width="20%" style="text-align:center;"></td>
                                <td width="20%" style="text-align:center;font-family: mtcorsva;font-size:12px;">'.htmlspecialchars($sign2Desig, ENT_QUOTES, 'UTF-8').'</td>
                                <td width="20%" style="text-align:center;"></td>
                            </tr>
                            <tr><td width="100%"><br><br></td></tr>
                            ';
                $content .= '<tr>
                                <td width="25%"></td>
                                <td width="50%" style="text-align:center;font-family: brushsci;">Shares 100 Each.</td>
                                <td width="25%"></td>
                            </tr>
                        </table>
                    ';

        $pdf->writeHTML($content, true, 0, true, 0);

        // ============================================
        //  LOGGING (Certificate)
        // ============================================
        try {
            $userId = $_SESSION['ID'] ?? null;
            $action = "EXPORT";
            $desc = "Generated Certificate #$certNo for $fullname";
            $this->LogActivity($userId, $action, $this->logModuleId, $desc);
        } catch (Exception $e) {}
        // ============================================

        $pdf->lastPage();
        $pdf->Output('shareholder_certificate.pdf', 'I');
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

    // ==========================================
    //  PRINT SHAREHOLDER LIST (With Logging)
    // ==========================================
    public function PrintShareholderReport($filter = 'ALL'){
        ob_clean();
        ob_flush();

        // 1. Fetch Data
        $sql = "SELECT shareholderNo, fullname, shareholder_type, type, noofshare, amount_share, dateEncoded, Address, contact_number 
                FROM tbl_shareholder_info";
        $result = $this->conn->query($sql);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Name Formatting Logic
                $rawName = trim($row['fullname']);
                $shType  = strtoupper($row['shareholder_type']);
                
                if (strpos($rawName, ',') === false && strpos($shType, 'COMPANY') === false && strpos($shType, 'CORPORATE') === false) {
                    $parts = explode(' ', $rawName);
                    if(count($parts) > 1){
                        $lastName = array_pop($parts);
                        $firstName = implode(' ', $parts);
                        $formattedName = strtoupper($lastName . ', ' . $firstName);
                    } else {
                        $formattedName = strtoupper($rawName);
                    }
                } else {
                    $formattedName = strtoupper($rawName);
                }

                $row['display_name'] = $formattedName;
                $row['Address'] = strtoupper($row['Address'] ?? '');

                $data[] = $row;
            }
        }

        // 2. Filter & Sort Logic
        if ($filter != 'ALL' && $filter != 'HIGHEST_SHARES' && $filter != 'LOWEST_SHARES') {
            $data = array_filter($data, function($item) use ($filter) {
                return $item['type'] === $filter; 
            });
        }

        usort($data, function($a, $b) use ($filter) {
            if ($filter == 'HIGHEST_SHARES') {
                return $b['noofshare'] - $a['noofshare'];
            } elseif ($filter == 'LOWEST_SHARES') {
                return $a['noofshare'] - $b['noofshare'];
            } else {
                return strcmp($a['display_name'], $b['display_name']);
            }
        });

        // 3. Initialize PDF
        $pdf = new TCPDF('L', PDF_UNIT, $this->long_format, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Shareholder List Report');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // 4. Content Setup
        $titleInfo = "All Share Types";
        if($filter == 'HIGHEST_SHARES') $titleInfo = "Highest to Lowest Shares";
        elseif($filter == 'LOWEST_SHARES') $titleInfo = "Lowest to Highest Shares";
        elseif($filter != 'ALL') $titleInfo = $filter . " SHARES";
        
        $html = '<h2 style="text-align:center;">Shareholder Information Report</h2>';
        $html .= '<p style="text-align:center;">Filter: <strong>'.htmlspecialchars($titleInfo, ENT_QUOTES, 'UTF-8').'</strong> | Date: '.date('F d, Y').'</p>';
        $html .= '<hr>';
        
        // --- DEFINE COLUMN WIDTHS (Must equal 100%) ---
        $w = [
            'id'      => '9%',
            'name'    => '18%',
            'address' => '17%',  // Reduced from 20% to 17%
            'mobile'  => '10%',
            'shtype'  => '11%',
            'type'    => '8%',
            'shares'  => '7%',
            'amount'  => '10%',
            'date'    => '10%'   // Increased from 7% to 10%
        ];

        // --- UPDATED TABLE STRUCTURE ---
        $html .= '<table border="1" cellspacing="0" cellpadding="4" style="font-size:10px;">';
        
        // HEADERS: Using specific widths from $w array
        $html .= '<thead>
                    <tr style="background-color:#f2f2f2; font-weight:bold;">
                        <th width="'.$w['id'].'" align="center">ID</th>
                        <th width="'.$w['name'].'" align="center">Full Name</th>
                        <th width="'.$w['address'].'" align="center">Address</th>
                        <th width="'.$w['mobile'].'" align="center">Mobile</th>
                        <th width="'.$w['shtype'].'" align="center">SH Type</th>
                        <th width="'.$w['type'].'" align="center">Type</th>
                        <th width="'.$w['shares'].'" align="center">Shares</th>
                        <th width="'.$w['amount'].'" align="center">Amount</th>
                        <th width="'.$w['date'].'" align="center">As of</th>
                    </tr>
                  </thead>';

        $html .= '<tbody>';

        if (count($data) > 0) {
            foreach($data as $row) {
                // DATA ROWS: Added width="" attribute to EVERY cell to force alignment
                $html .= '<tr nobr="true">
                            <td width="'.$w['id'].'">'.htmlspecialchars($row['shareholderNo'], ENT_QUOTES, 'UTF-8').'</td>
                            <td width="'.$w['name'].'">'.htmlspecialchars($row['display_name'], ENT_QUOTES, 'UTF-8').'</td>
                            <td width="'.$w['address'].'">'.htmlspecialchars($row['Address'], ENT_QUOTES, 'UTF-8').'</td> 
                            <td width="'.$w['mobile'].'" align="center">'.htmlspecialchars($row['contact_number'], ENT_QUOTES, 'UTF-8').'</td>
                            <td width="'.$w['shtype'].'">'.htmlspecialchars($row['shareholder_type'], ENT_QUOTES, 'UTF-8').'</td>
                            <td width="'.$w['type'].'">'.htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8').'</td>
                            <td width="'.$w['shares'].'" align="center">'.htmlspecialchars($row['noofshare'], ENT_QUOTES, 'UTF-8').'</td>
                            <td width="'.$w['amount'].'" align="right">'.number_format((float)$row['amount_share'], 2).'</td>
                            <td width="'.$w['date'].'" align="center">'.date('M. d, Y', strtotime($row['dateEncoded'])).'</td>
                          </tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" align="center">No records found.</td></tr>';
        }

        // TOTAL ROW
        if (count($data) > 0) {
            $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;">
                        <td colspan="9" align="right">TOTAL SHAREHOLDERS: ' . count($data) . '</td>
                      </tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // ============================================
        //  LOGGING (Report)
        // ============================================
        try {
            $userId = $_SESSION['ID'] ?? null;
            $action = "EXPORT"; 
            $desc = "Generated Shareholder Report PDF (Filter: $filter)";
            $this->LogActivity($userId, $action, $this->logModuleId, $desc);
        } catch (Exception $e) {}
        // ============================================

        $pdf->Output('Shareholder_Report.pdf', 'I');
    }
    // ==========================================
    //  EXPORT SHAREHOLDER EXCEL
    // ==========================================
    public function ExportShareholderExcel($filter = 'ALL'){
        ob_clean();
        ob_flush();

        // 1. Fetch Data (Same logic as PDF)
        $sql = "SELECT shareholderNo, fullname, shareholder_type, type, noofshare, amount_share, dateEncoded, Address, contact_number 
                FROM tbl_shareholder_info";
        $result = $this->conn->query($sql);
        
        $data = [];
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $rawName = trim($row['fullname']);
                $shType  = strtoupper($row['shareholder_type']);
                
                if (strpos($rawName, ',') === false && strpos($shType, 'COMPANY') === false && strpos($shType, 'CORPORATE') === false) {
                    $parts = explode(' ', $rawName);
                    if(count($parts) > 1){
                        $lastName = array_pop($parts);
                        $firstName = implode(' ', $parts);
                        $formattedName = strtoupper($lastName . ', ' . $firstName);
                    } else {
                        $formattedName = strtoupper($rawName);
                    }
                } else {
                    $formattedName = strtoupper($rawName);
                }

                $row['display_name'] = $formattedName;
                $row['Address'] = strtoupper($row['Address'] ?? '');
                $data[] = $row;
            }
        }

        // 2. Filter & Sort
        if ($filter != 'ALL' && $filter != 'HIGHEST_SHARES' && $filter != 'LOWEST_SHARES') {
            $data = array_filter($data, function($item) use ($filter) {
                return $item['type'] === $filter; 
            });
        }

        usort($data, function($a, $b) use ($filter) {
            if ($filter == 'HIGHEST_SHARES') {
                return $b['noofshare'] - $a['noofshare'];
            } elseif ($filter == 'LOWEST_SHARES') {
                return $a['noofshare'] - $b['noofshare'];
            } else {
                return strcmp($a['display_name'], $b['display_name']);
            }
        });

        // 3. Output Headers for Excel
        $filename = "Shareholder_Report_" . date('Ymd') . ".xls";
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // 4. Build HTML Table
        echo '<table border="1">';
        echo '<thead>';
        echo '<tr><th colspan="9" style="font-size:14pt; font-weight:bold; text-align:center;">Shareholder Information Report</th></tr>';
        echo '<tr><th colspan="9" style="font-size:14pt; font-weight:bold; text-align:center;">Shareholder Information Report</th></tr>';
        echo '<tr><th colspan="9" style="text-align:center;">Filter: ' . htmlspecialchars($filter, ENT_QUOTES, 'UTF-8') . ' | Date: ' . date('F d, Y') . '</th></tr>';
        echo '<tr style="background-color:#CCC; font-weight:bold;">';
        echo '<tr style="background-color:#CCC; font-weight:bold;">';
        echo '<th>ID</th>';
        echo '<th>Full Name</th>';
        echo '<th>Address</th>';
        echo '<th>Mobile</th>';
        echo '<th>SH Type</th>';
        echo '<th>Type</th>';
        echo '<th>Shares</th>';
        echo '<th>Amount</th>';
        echo '<th>As of</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if (count($data) > 0) {
            foreach($data as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['shareholderNo'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['display_name'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['Address'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td style="mso-number-format:\@;">' . htmlspecialchars($row['contact_number'], ENT_QUOTES, 'UTF-8') . '</td>'; // Text format for mobile
                echo '<td>' . htmlspecialchars($row['shareholder_type'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($row['noofshare'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . number_format((float)$row['amount_share'], 2) . '</td>';
                echo '<td>' . date('M. d, Y', strtotime($row['dateEncoded'])) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="9" style="text-align:center;">No records found.</td></tr>';
        }

        // TOTAL ROW
        if (count($data) > 0) {
            echo '<tr style="background-color:#f2f2f2; font-weight:bold;">
                        <td colspan="9" align="right">TOTAL SHAREHOLDERS: ' . count($data) . '</td>
                  </tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // Log Action
        try {
            $userId = $_SESSION['ID'] ?? null;
            $this->LogActivity($userId, "EXPORT", $this->logModuleId, "Exported Shareholder Excel (Filter: $filter)");
        } catch (Exception $e) {}
        exit;
    }
}
?>