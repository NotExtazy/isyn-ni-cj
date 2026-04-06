<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('../../assets/tcpdf/tcpdf.php');
require_once('../../assets/PHPSpreadsheet/vendor/autoload.php');
// Ensure database connection is available since we extend Database
include_once("../../database/connection.php"); 

class Reports extends Database 
{
    // CONFIGURATION: Set to ID 20 (Board of Directors)
    private $logModuleId = 20; 

    public function PrintBODReport($headerData, $tableData, $year){
        ob_clean();
        ob_flush();

        $pdf = new TCPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle('BOARD OF DIRECTORS LIST');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        $content = '<h2 style="text-align:center;">Board of Directors List</h2>';
        $content .= '<h4 style="text-align:center;">Year: ' . htmlspecialchars($year, ENT_QUOTES, 'UTF-8') . '</h4><br>';
        
        $content .= '<table border="1" cellpadding="4">';
        $content .= '<thead><tr style="font-weight:bold; background-color:#f0f0f0;">';
        foreach ($headerData as $col) {
            $content .= '<th style="text-align:center;">' . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $content .= '</tr></thead><tbody>';

        foreach ($tableData as $row) {
            $content .= '<tr>';
            foreach ($row as $cell) {
                $content .= '<td>' . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $content .= '</tr>';
        }
        $content .= '</tbody></table>';

        $pdf->writeHTML($content, true, false, true, false, '');

        // ============================================
        //  LOGGING (BOD Report)
        // ============================================
        try {
            $userId = $_SESSION['ID'] ?? null;
            $action = "EXPORT";
            $desc = "Generated Board of Directors Report (Year: $year)";
            $this->LogActivity($userId, $action, $this->logModuleId, $desc);
        } catch (Exception $e) {}
        // ============================================

        $pdf->IncludeJS("print();");
        $pdf->Output('bod_list.pdf', 'I');
    }

    public function PrintCommitteeReport($headerData, $tableData, $year){
        ob_clean();
        ob_flush();

        // Use Landscape ('L') because Committee has more columns
        $pdf = new TCPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('isynergiesinc');
        $pdf->SetTitle('COMMITTEE LIST');
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();

        $content = '<h2 style="text-align:center;">Committee List</h2>';
        $content .= '<h4 style="text-align:center;">Year: ' . htmlspecialchars($year, ENT_QUOTES, 'UTF-8') . '</h4><br>';
        
        $content .= '<table border="1" cellpadding="4">';
        $content .= '<thead><tr style="font-weight:bold; background-color:#f0f0f0;">';
        
        // Define widths for 6 columns (Total 100%)
        // Fullname, Designation, Type, Position, From, To
        $widths = ['25%', '15%', '20%', '20%', '10%', '10%'];
        $i = 0;

        foreach ($headerData as $col) {
            $w = isset($widths[$i]) ? $widths[$i] : 'auto';
            $content .= '<th width="'.$w.'" style="text-align:center;">' . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . '</th>';
            $i++;
        }
        $content .= '</tr></thead><tbody>';

        foreach ($tableData as $row) {
            $content .= '<tr>';
            $i = 0;
            foreach ($row as $cell) {
                $w = isset($widths[$i]) ? $widths[$i] : 'auto';
                $content .= '<td width="'.$w.'">' . $cell . '</td>';
                $i++;
            }
            $content .= '</tr>';
        }
        $content .= '</tbody></table>';

        $pdf->writeHTML($content, true, false, true, false, '');

        // ============================================
        //  LOGGING (Committee Report)
        // ============================================
        try {
            $userId = $_SESSION['ID'] ?? null;
            $action = "EXPORT";
            $desc = "Generated Committee Report (Year: $year)";
            $this->LogActivity($userId, $action, $this->logModuleId, $desc);
        } catch (Exception $e) {}
        // ============================================

        $pdf->IncludeJS("print();");
        $pdf->Output('committee_list.pdf', 'I');
    }

    // ==========================================
    // EXCEL EXPORT METHODS - NEW
    // ==========================================

    public function ExportBODToExcel($headerData, $tableData, $year) {
        try {
            // Create new Spreadsheet object
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('BOD List');

            // Set title
            $sheet->setCellValue('A1', 'Board of Directors List');
            $sheet->mergeCells('A1:D1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set year
            $sheet->setCellValue('A2', 'Year: ' . $year);
            $sheet->mergeCells('A2:D2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set headers (row 4)
            $col = 'A';
            foreach ($headerData as $header) {
                $sheet->setCellValue($col . '4', $header);
                $col++;
            }

            // Style headers
            $sheet->getStyle('A4:D4')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A4:D4')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('3a57e8');
            $sheet->getStyle('A4:D4')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A4:D4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Add data
            $row = 5;
            foreach ($tableData as $data) {
                $col = 'A';
                foreach ($data as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Auto-fit columns
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add borders to all data cells
            $lastRow = $row - 1;
            $sheet->getStyle('A4:D' . $lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // Set output headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Board_of_Directors_' . $year . '.xlsx"');
            header('Cache-Control: max-age=0');

            // LOGGING
            try {
                $userId = $_SESSION['ID'] ?? null;
                $action = "EXPORT";
                $desc = "Exported Board of Directors Report to Excel (Year: $year)";
                $this->LogActivity($userId, $action, $this->logModuleId, $desc);
            } catch (Exception $e) {}

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();

        } catch (Exception $e) {
            error_log("Excel export error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => 'Failed to generate Excel file']);
            exit();
        }
    }

    public function ExportCommitteeToExcel($headerData, $tableData, $year) {
        try {
            // Create new Spreadsheet object
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Committee List');

            // Set title
            $sheet->setCellValue('A1', 'Committee List');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set year
            $sheet->setCellValue('A2', 'Year: ' . $year);
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set headers (row 4)
            $col = 'A';
            foreach ($headerData as $header) {
                $sheet->setCellValue($col . '4', $header);
                $col++;
            }

            // Style headers
            $sheet->getStyle('A4:F4')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A4:F4')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('3a57e8');
            $sheet->getStyle('A4:F4')->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A4:F4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Add data
            $row = 5;
            foreach ($tableData as $data) {
                $col = 'A';
                foreach ($data as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Auto-fit columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add borders to all data cells
            $lastRow = $row - 1;
            $sheet->getStyle('A4:F' . $lastRow)->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // Set output headers
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Committee_Report_' . $year . '.xlsx"');
            header('Cache-Control: max-age=0');

            // LOGGING
            try {
                $userId = $_SESSION['ID'] ?? null;
                $action = "EXPORT";
                $desc = "Exported Committee Report to Excel (Year: $year)";
                $this->LogActivity($userId, $action, $this->logModuleId, $desc);
            } catch (Exception $e) {}

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();

        } catch (Exception $e) {
            error_log("Excel export error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => 'Failed to generate Excel file']);
            exit();
        }
    }
}
?>