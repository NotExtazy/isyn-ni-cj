<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('../../assets/tcpdf/tcpdf.php');
include_once("../../database/connection.php");

class SupplierReports extends Database 
{
    // CONFIGURATION: Supplier Module ID
    private $logModuleId = 52; 

    private function GetSupplierData($filter) {
        // 1. Fetch Data
        $sql = "SELECT supplierNo, supplierName, tinNumber, email, mobileNumber, telephoneNumber, supplierSince, fullAddress
                FROM tbl_supplier_info";
        
        // Filter Logic (if needed in future, currently 'ALL' is standard)
        // if ($filter != 'ALL') { ... }

        $sql .= " ORDER BY supplierName ASC";

        $result = $this->conn->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['supplierName'] = strtoupper($row['supplierName']);
                $row['fullAddress'] = strtoupper($row['fullAddress']);
                $row['supplierSinceFormatted'] = !empty($row['supplierSince']) ? date('M d, Y', strtotime($row['supplierSince'])) : '-';
                $data[] = $row;
            }
        }
        return $data;
    }

    public function PrintSupplierReport($filter = 'ALL'){
        // Determine type based on action if possible, or just default to PDF/Excel separated?
        // The previous pattern used separate functions or checking GET. 
        // For consistency with Customer, I will create separate methods if needed, or check logic.
        // But here I'll stick to the class method structure.
        
        // Actually, the route calls specific methods. I'll implement PrintSupplierReport (PDF) and PrintSupplierReportExcel.
        $this->PrintSupplierReportPDF($filter);
    }

    public function PrintSupplierReportPDF($filter = 'ALL'){
        ob_clean();
        ob_flush();

        $data = $this->GetSupplierData($filter);

        // 2. Setup PDF (Landscape 8.5 x 13)
        $long_format = array(215.9, 330.2); 
        $pdf = new TCPDF('L', PDF_UNIT, $long_format, true, 'UTF-8', false);
        
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Supplier Report');
        $pdf->SetMargins(10, 10, 10); 
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // 3. Header & Title Logic
        $html = '<h2 style="text-align:center;">Supplier Information Report</h2>';
        $html .= '<p style="text-align:center;">Filter: <strong>ALL</strong> | Date: '.date('F d, Y').'</p>';
        $html .= '<hr>';
        
        // 4. Build Table HTML
        // Columns: Supplier No, Name, TIN, Mobile, Tel, Email, Since
        // Widths: ID(10), Name(20), TIN(12), Mob(10), Tel(10), Email(15), Since(10), Address (Remaining/Hidden? Address is usually long. Let's include Address?)
        // User asked for: "supplier no, supplier name, tin, separate column for the tel and mobile num, and when they started"
        // Let's try to fit Address if possible, or omit if too crowded. Customer report had Address.
        // Let's fit: ID(8), Name(18), TIN(10), Mob(9), Tel(9), Since(9), Address(37)
        
        $html .= '<table border="1" cellspacing="0" cellpadding="4" style="font-size:9px;">';
        $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;">
                    <th width="8%" align="center">ID</th>
                    <th width="18%" align="center">Name</th>
                    <th width="10%" align="center">TIN</th>
                    <th width="9%" align="center">Mobile</th>
                    <th width="9%" align="center">Tel</th>
                    <th width="9%" align="center">Supplier Since</th>
                    <th width="37%" align="center">Address</th>
                  </tr>';

        if (count($data) > 0) {
            foreach($data as $row) {
                // Decrypt TIN before display
                $tinDecrypted = Encryption::decrypt($row['tinNumber']);
                $tin = !empty($tinDecrypted) ? $tinDecrypted : '-';
                $tel = !empty($row['telephoneNumber']) ? $row['telephoneNumber'] : '-';

                $html .= '<tr nobr="true"> 
                            <td>'.htmlspecialchars($row['supplierNo'], ENT_QUOTES, 'UTF-8').'</td>
                            <td>'.htmlspecialchars($row['supplierName'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($tin, ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['mobileNumber'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($tel, ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['supplierSinceFormatted'], ENT_QUOTES, 'UTF-8').'</td>
                            <td>'.htmlspecialchars($row['fullAddress'], ENT_QUOTES, 'UTF-8').'</td>
                          </tr>';
            }
            // TOTAL ROW
            $html .= '<tr style="font-weight:bold; background-color:#f9f9f9;">
                        <td colspan="7" align="right">Total Suppliers: ' . count($data) . '</td>
                      </tr>';
        } else {
            $html .= '<tr><td colspan="7" align="center">No records found.</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // LOGGING
        $this->LogExport("Generated Supplier Report PDF");

        $pdf->Output('Supplier_Report.pdf', 'I');
    }

    public function PrintSupplierReportExcel($filter = 'ALL'){
        $data = $this->GetSupplierData($filter);
        $filename = "Supplier_Report_" . date('Ymd') . ".xls";

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Sheet1</x:Name><x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
        echo '<style>';
        echo '  body { font-family: Arial, sans-serif; }';
        echo '  table { border-collapse: collapse; }';
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
        echo '<tr><td colspan="7" style="font-size: 14pt; font-weight: bold; text-align: center; border:none;">Supplier Information Report</td></tr>';
        echo '<tr><td colspan="7" style="text-align: center; border:none;">Filter: ALL</td></tr>';
        echo '<tr><td colspan="7" style="text-align: center; border:none;">Date: ' . date('F d, Y') . '</td></tr>';
        echo '<tr><td colspan="7" style="border:none;"></td></tr>'; 

        // Header
        echo '<tr>
                <th>Supplier No</th>
                <th>Name</th>
                <th>TIN</th>
                <th>Mobile Number</th>
                <th>Tel Number</th>
                <th>Supplier Since</th>
                <th class="address-col">Address</th>
              </tr>';

        if (count($data) > 0) {
            foreach ($data as $row) {
                // Decrypt TIN before display
                $tinDecrypted = Encryption::decrypt($row['tinNumber']);
                $tin = !empty($tinDecrypted) ? $tinDecrypted : '-';
                $tel = !empty($row['telephoneNumber']) ? $row['telephoneNumber'] : '-';
                
                echo '<tr>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($row['supplierNo'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="autofit">' . htmlspecialchars($row['supplierName'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($tin, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($row['mobileNumber'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($tel, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit">' . htmlspecialchars($row['supplierSinceFormatted'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="address-col">' . htmlspecialchars($row['fullAddress'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '</tr>';
            }
            // TOTAL ROW
            echo '<tr style="font-weight:bold; background-color:#f9f9f9;">
                    <td colspan="7" class="text-right">Total Suppliers: ' . count($data) . '</td>
                  </tr>';
        } else {
            echo '<tr><td colspan="7" class="text-center">No records found.</td></tr>';
        }

        echo '</table>';
        echo '</body>';
        echo '</html>';

        $this->LogExport("Generated Supplier Report Excel");
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
