<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('../../assets/tcpdf/tcpdf.php');
include_once("../../database/connection.php");

class Reports extends Database 
{
    // CONFIGURATION: Set to ID 2 (Customer Information)
    private $logModuleId = 2; 

    private function GetCustomerData($filter) {
        // 1. Fetch Data (Added clientSince to query)
        $sql = "SELECT clientNo, customerType, companyName, firstName, middleName, lastName, suffix, mobileNumber, email, 
                        tinNumber, street, Barangay, CityTown, Province, Region, clientSince 
                FROM tbl_customer_profiles";
        
        // Filter Logic
        if ($filter != 'ALL') {
            $sql .= " WHERE customerType = '" . $this->conn->real_escape_string($filter) . "'";
        }
        $sql .= " ORDER BY lastName ASC, firstName ASC";

        $result = $this->conn->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Name Logic
                $nameDisplay = "";
                $type = $row["customerType"];

                // Corporate Types check
                if(in_array($type, ['MFI HO', 'MFI BRANCH', 'BUSINESS UNIT', 'DEPARTMENT'])){
                     $nameDisplay = $row["companyName"];
                } else {
                    $l = trim($row["lastName"]);
                    $f = trim($row["firstName"]);
                    $m = trim($row["middleName"]);
                    $s = trim($row["suffix"]);
                    
                    $nameDisplay = $l;
                    if($f) $nameDisplay .= ", " . $f;
                    if($m) $nameDisplay .= " " . $m;
                    if($s) $nameDisplay .= " " . $s;
                }
                $row['display_name'] = strtoupper($nameDisplay);
                
                // Format Date
                $row['clientSinceFormatted'] = !empty($row['clientSince']) ? date('M d, Y', strtotime($row['clientSince'])) : '-';

                // Address Logic (Concatenate parts)
                $parts = array_filter([
                    $row['street'], 
                    $row['Barangay'], 
                    $row['CityTown'], 
                    $row['Province'], 
                    $row['Region']
                ]);
                $row['FullAddress'] = strtoupper(implode(", ", $parts));
                
                $data[] = $row;
            }
        }
        return $data;
    }

    public function PrintCustomerReport($filter = 'ALL'){
        ob_clean();
        ob_flush();

        $data = $this->GetCustomerData($filter);

        // 2. Setup PDF (Landscape 8.5 x 13)
        $long_format = array(215.9, 330.2); 
        $pdf = new TCPDF('L', PDF_UNIT, $long_format, true, 'UTF-8', false);
        
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Customer Report');
        $pdf->SetMargins(10, 10, 10); 
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // 3. Header & Title Logic
        $titleInfo = ($filter == 'ALL') ? "All Customer Types" : $filter;
        
        $html = '<h2 style="text-align:center;">Customer Information Report</h2>';
        $html .= '<p style="text-align:center;">Filter: <strong>'.htmlspecialchars($titleInfo, ENT_QUOTES, 'UTF-8').'</strong> | Date: '.date('F d, Y').'</p>';
        $html .= '<hr>';
        
        // 4. Build Table HTML (Added TIN Column)
        // Adjusted widths to fit TIN column: ID(8), Name(18), Type(10), TIN(12), Mobile(10), Email(15), Address(17), ClientSince(10)
        $html .= '<table border="1" cellspacing="0" cellpadding="4" style="font-size:10px;">';
        $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;">
                    <th width="8%" align="center">ID</th>
                    <th width="18%" align="center">Name</th>
                    <th width="10%" align="center">Type</th>
                    <th width="12%" align="center">TIN</th>
                    <th width="10%" align="center">Mobile</th>
                    <th width="15%" align="center">Email</th>
                    <th width="17%" align="center">Address</th>
                    <th width="10%" align="center">Client Since</th>
                  </tr>';

        if (count($data) > 0) {
            foreach($data as $row) {
                // Decrypt TIN before display
                $tinDecrypted = '';
                try {
                    $tinDecrypted = !empty($row['tinNumber']) ? Encryption::decrypt($row['tinNumber']) : '';
                } catch (Exception $e) {
                    error_log("TIN Decryption Error in PDF Report: " . $e->getMessage());
                    $tinDecrypted = '';
                }
                $tin = !empty($tinDecrypted) ? $tinDecrypted : '-';

                $html .= '<tr nobr="true"> 
                            <td>'.htmlspecialchars($row['clientNo'], ENT_QUOTES, 'UTF-8').'</td>
                            <td>'.htmlspecialchars($row['display_name'], ENT_QUOTES, 'UTF-8').'</td>
                            <td>'.htmlspecialchars($row['customerType'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($tin, ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['mobileNumber'], ENT_QUOTES, 'UTF-8').'</td>
                            <td>'.htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8').'</td>
                            <td>'.htmlspecialchars($row['FullAddress'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['clientSinceFormatted'], ENT_QUOTES, 'UTF-8').'</td>
                          </tr>';
            }
            // TOTAL ROW FOR PDF
            $html .= '<tr style="font-weight:bold; background-color:#f9f9f9;">
                        <td colspan="8" align="right">Total Customers: ' . count($data) . '</td>
                      </tr>';
        } else {
            $html .= '<tr><td colspan="8" align="center">No records found.</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // LOGGING
        $this->LogExport("Generated Customer Report PDF (Filter: $filter)");

        $pdf->Output('Customer_Report.pdf', 'I');
    }

    public function PrintCustomerReportExcel($filter = 'ALL'){
        $data = $this->GetCustomerData($filter);
        $filename = "Customer_Report_" . date('Ymd') . ".xls";

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<!--[if gte mso 9]>';
        echo '<xml>';
        echo '<x:ExcelWorkbook>';
        echo '<x:ExcelWorksheets>';
        echo '<x:ExcelWorksheet>';
        echo '<x:Name>Sheet1</x:Name>';
        echo '<x:WorksheetOptions>';
        echo '<x:Print>';
        echo '<x:ValidPrinterInfo/>';
        echo '</x:Print>';
        echo '</x:WorksheetOptions>';
        echo '</x:ExcelWorksheet>';
        echo '</x:ExcelWorksheets>';
        echo '</x:ExcelWorkbook>';
        echo '</xml>';
        echo '<![endif]-->';
        echo '<style>';
        echo '  body { font-family: Arial, sans-serif; }';
        echo '  table { border-collapse: collapse; width: 100%; }';
        echo '  th, td { border: 1px solid #000000; padding: 5px; vertical-align: middle; }';
        echo '  th { background-color: #f2f2f2; font-weight: bold; text-align: center; }';
        echo '  .text-center { text-align: center; }';
        echo '  .text-right { text-align: right; }';
        echo '  .autofit { white-space: nowrap; }';
        echo '  .address-col { width: 350px; white-space: normal; word-wrap: break-word; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        // Title Row
        echo '<table>';
        echo '<tr><td colspan="8" style="font-size: 14pt; font-weight: bold; text-align: center; border:none;">Customer Information Report</td></tr>';
        echo '<tr><td colspan="8" style="text-align: center; border:none;">Filter: ' . htmlspecialchars(($filter == 'ALL' ? 'All Customer Types' : $filter), ENT_QUOTES, 'UTF-8') . '</td></tr>';
        echo '<tr><td colspan="8" style="text-align: center; border:none;">Date: ' . date('F d, Y') . '</td></tr>';
        echo '<tr><td colspan="8" style="border:none;"></td></tr>'; // Spacer

        // Header
        echo '<tr>
                <th>Customer No</th>
                <th>Name</th>
                <th>Customer Type</th>
                <th>TIN</th>
                <th>Mobile Number</th>
                <th>Email</th>
                <th class="address-col">Address</th>
                <th>Client Since</th>
              </tr>';

        if (count($data) > 0) {
            foreach ($data as $row) {
                // Decrypt TIN before display
                $tinDecrypted = '';
                try {
                    $tinDecrypted = !empty($row['tinNumber']) ? Encryption::decrypt($row['tinNumber']) : '';
                } catch (Exception $e) {
                    error_log("TIN Decryption Error in Excel Report: " . $e->getMessage());
                    $tinDecrypted = '';
                }
                $tin = !empty($tinDecrypted) ? $tinDecrypted : '-';
                
                echo '<tr>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($row['clientNo'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="autofit">' . htmlspecialchars($row['display_name'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="autofit">' . htmlspecialchars($row['customerType'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($tin, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($row['mobileNumber'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="autofit">' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="address-col">' . htmlspecialchars($row['FullAddress'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit">' . htmlspecialchars($row['clientSinceFormatted'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '</tr>';
            }
            // TOTAL ROW
            echo '<tr style="font-weight:bold; background-color:#f9f9f9;">
                    <td colspan="8" class="text-right">Total Customers: ' . count($data) . '</td>
                  </tr>';
        } else {
            echo '<tr><td colspan="8" class="text-center">No records found.</td></tr>';
        }

        echo '</table>';
        echo '</body>';
        echo '</html>';

        // LOGGING
        $this->LogExport("Generated Customer Report Excel (Filter: $filter)");
        exit(); 
    }

    private function LogExport($description){
         try {
            $userId = $_SESSION['ID'] ?? null;
            $this->LogActivity($userId, "EXPORT", $this->logModuleId, $description);
        } catch (Exception $e) { }
    }
}
?>