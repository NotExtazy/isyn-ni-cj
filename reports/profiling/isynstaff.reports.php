<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('../../assets/tcpdf/tcpdf.php');
include_once("../../database/connection.php");

class StaffReports extends Database 
{
    // CONFIGURATION: Staff Module ID
    private $logModuleId = 18; 

    private function GetStaffData($filter) {
        // 1. Fetch Data
        $sql = "SELECT employee_no, first_name, middle_name, last_name, employee_status, designation, date_hired, contact_num, email_address,
                       tin_num, sss_num, philhealth_num, pag_ibig
                FROM tbl_isynergies_info";
        
        // Filter Logic (if needed in future)
        // if ($filter != 'ALL') { ... }

        $sql .= " ORDER BY last_name ASC, first_name ASC";

        $result = $this->conn->query($sql);
        $data = [];
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Format Name: LAST, FIRST MIDDLE
                $middle = !empty($row['middle_name']) ? substr($row['middle_name'], 0, 1) . '.' : '';
                $row['fullName'] = strtoupper($row['last_name'] . ', ' . $row['first_name'] . ' ' . $middle);
                
                $row['dateHiredFormatted'] = !empty($row['date_hired']) ? date('M d, Y', strtotime($row['date_hired'])) : '-';
                
                // Capitalize others
                $row['employee_status'] = strtoupper($row['employee_status']);
                $row['designation'] = strtoupper($row['designation']);
                
                $data[] = $row;
            }
        }
        return $data;
    }

    public function PrintStaffReportPDF($filter = 'ALL'){
        ob_clean();
        ob_flush();

        $data = $this->GetStaffData($filter);

        // 2. Setup PDF (Landscape 8.5 x 13)
        $long_format = array(215.9, 330.2); 
        $pdf = new TCPDF('L', PDF_UNIT, $long_format, true, 'UTF-8', false);
        
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Staff Report');
        $pdf->SetMargins(10, 10, 10); 
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // 3. Header & Title Logic
        $html = '<h2 style="text-align:center;">iSynergies Staff Report</h2>';
        $html .= '<p style="text-align:center;">Filter: <strong>ALL</strong> | Date: '.date('F d, Y').'</p>';
        $html .= '<hr>';
        
        // 4. Build Table HTML
        // Columns: Emp No(10), Name(25), Status(10), Designation(15), Date Hired(10), Contact(15), Email(15)
        
        // 4. Build Table HTML
        // Total Width ~ 100%
        // Emp No(7), Name(18), Status(8), Desig(10), Hired(8), Contact(10), TIN(10), SSS(10), PH(10), PagIBIG(10)
        
        $html .= '<table border="1" cellspacing="0" cellpadding="3" style="font-size:8px;">';
        $html .= '<tr style="background-color:#f2f2f2; font-weight:bold;">
                    <th width="7%" align="center">Emp No.</th>
                    <th width="17%" align="center">Name</th>
                    <th width="8%" align="center">Status</th>
                    <th width="10%" align="center">Designation</th>
                    <th width="8%" align="center">Date Hired</th>
                    <th width="10%" align="center">Contact No.</th>
                    <th width="10%" align="center">TIN</th>
                    <th width="10%" align="center">SSS</th>
                    <th width="10%" align="center">PhilHealth</th>
                    <th width="10%" align="center">Pag-IBIG</th>
                  </tr>';

        if (count($data) > 0) {
            foreach($data as $row) {
                // Decrypt government IDs before display
                $tinDecrypted = Encryption::decrypt($row['tin_num']);
                $sssDecrypted = Encryption::decrypt($row['sss_num']);
                $philhealthDecrypted = Encryption::decrypt($row['philhealth_num']);
                $pagibigDecrypted = Encryption::decrypt($row['pag_ibig']);
                
                $html .= '<tr nobr="true"> 
                            <td>'.htmlspecialchars($row['employee_no'], ENT_QUOTES, 'UTF-8').'</td>
                            <td>'.htmlspecialchars($row['fullName'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['employee_status'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['designation'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['dateHiredFormatted'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($row['contact_num'], ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($tinDecrypted, ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($sssDecrypted, ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($philhealthDecrypted, ENT_QUOTES, 'UTF-8').'</td>
                            <td align="center">'.htmlspecialchars($pagibigDecrypted, ENT_QUOTES, 'UTF-8').'</td>
                          </tr>';
            }
            // TOTAL ROW
            $html .= '<tr style="font-weight:bold; background-color:#f9f9f9;">
                        <td colspan="10" align="right">Total Staff: ' . count($data) . '</td>
                      </tr>';
        } else {
            $html .= '<tr><td colspan="10" align="center">No records found.</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // LOGGING
        $this->LogExport("Generated Staff Report PDF");

        $pdf->Output('Staff_Report.pdf', 'I');
    }

    public function PrintStaffReportExcel($filter = 'ALL'){
        $data = $this->GetStaffData($filter);
        $filename = "Staff_Report_" . date('Ymd') . ".xls";

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
        echo '  table { border-collapse: collapse; width: 100%; }';
        echo '  th, td { border: 1px solid #000000; padding: 5px; vertical-align: middle; }';
        echo '  th { background-color: #f2f2f2; font-weight: bold; text-align: center; }';
        echo '  .text-center { text-align: center; }';
        echo '  .text-right { text-align: right; }';
        echo '  .autofit { white-space: nowrap; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        // Title Row
        echo '<table>';
        echo '<tr><td colspan="11" style="font-size: 14pt; font-weight: bold; text-align: center; border:none;">iSynergies Staff Report</td></tr>';
        echo '<tr><td colspan="11" style="text-align: center; border:none;">Filter: ALL</td></tr>';
        echo '<tr><td colspan="11" style="text-align: center; border:none;">Date: ' . date('F d, Y') . '</td></tr>';
        echo '<tr><td colspan="11" style="border:none;"></td></tr>'; 

        // Header
        // Header
        echo '<tr>
                <th>Emp No.</th>
                <th>Name</th>
                <th>Status</th>
                <th>Designation</th>
                <th>Date Hired</th>
                <th>Contact No.</th>
                <th>TIN</th>
                <th>SSS</th>
                <th>PhilHealth</th>
                <th>Pag-IBIG</th>
                <th>Email</th>
              </tr>';

        if (count($data) > 0) {
            foreach ($data as $row) {
                // Decrypt government IDs before display
                $tinDecrypted = Encryption::decrypt($row['tin_num']);
                $sssDecrypted = Encryption::decrypt($row['sss_num']);
                $philhealthDecrypted = Encryption::decrypt($row['philhealth_num']);
                $pagibigDecrypted = Encryption::decrypt($row['pag_ibig']);
                
                echo '<tr>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">'. htmlspecialchars($row['employee_no'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="autofit">' . htmlspecialchars($row['fullName'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit">' . htmlspecialchars($row['employee_status'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit">' . htmlspecialchars($row['designation'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit">' . htmlspecialchars($row['dateHiredFormatted'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($row['contact_num'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($tinDecrypted, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($sssDecrypted, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($philhealthDecrypted, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="text-center autofit" style="mso-number-format:\@;">' . htmlspecialchars($pagibigDecrypted, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="autofit">' . htmlspecialchars($row['email_address'], ENT_QUOTES, 'UTF-8') . '</td>';
                echo '</tr>';
            }
            // TOTAL ROW
            echo '<tr style="font-weight:bold; background-color:#f9f9f9;">
                    <td colspan="11" class="text-right">Total Staff: ' . count($data) . '</td>
                  </tr>';
        } else {
            echo '<tr><td colspan="11" class="text-center">No records found.</td></tr>';
        }

        echo '</table>';
        echo '</body>';
        echo '</html>';

        $this->LogExport("Generated Staff Report Excel");
        exit(); 
    }

    private function LogExport($description){
         try {
            $userId = $_SESSION['ID'] ?? null;
            if($userId) $this->LogActivity($userId, "EXPORT", $this->logModuleId, $description);
        } catch (Exception $e) { }
    }
}
?>
