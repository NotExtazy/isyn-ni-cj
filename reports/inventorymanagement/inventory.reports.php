<?php
include_once(__DIR__ . "/../../database/connection.php");

require_once(__DIR__ . "/../../assets/tcpdf/tcpdf.php");

class Reports extends Database
{
    public function PrintInventoryReport($headerData, $tableData, $isynbranch, $reportType) {
        ob_clean();

        ini_set('memory_limit', '-1');
        set_time_limit(0);

        // Create new PDF document
        $pdf = new TCPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('iSynergies Inc.');
        $pdf->SetTitle('Inventory Report - ' . $reportType);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        // Set margins
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 15);

        // Set font
        $pdf->SetFont('helvetica', '', 9);

        // Add a page
        $pdf->AddPage();

        // --- Custom Header ---
        $headerHtml = '
        <table width="100%" cellpadding="0">
            <tr>
                <td style="text-align:center;">
                    <span style="font-size:16pt; font-weight:bold; color:#333;">iSynergies Inc.</span><br>
                    <span style="font-size:12pt; font-weight:bold; color:#666;">INVENTORY MANAGEMENT SYSTEM</span><br>
                    <span style="font-size:10pt; color:#444;">' . strtoupper($reportType) . ' REPORT</span><br>
                    <span style="font-size:9pt; color:#666;">Branch: ' . ($isynbranch ?: 'OVERALL') . ' | Printed: ' . date('F d, Y h:i A') . '</span>
                </td>
            </tr>
        </table>
        <br><br>';

        // --- Table Section ---
        $html = '<table border="0.5" cellpadding="5" cellspacing="0" style="width:100%; border-collapse: collapse;">';
        
        // Table Header
        $html .= '<tr style="background-color: #4e73df; color: #ffffff; font-weight: bold;">';
        $columnNames = [];
        foreach ($headerData as $col) {
            $name = is_array($col) ? ($col['ColumnName'] ?? '') : (isset($col->ColumnName) ? $col->ColumnName : $col);
            if ($name) {
                $columnNames[] = $name;
                $html .= '<th style="text-align:center; border: 1px solid #333;">' . $name . '</th>';
            }
        }
        $html .= '</tr>';

        // Table Body
        if (is_array($tableData) && !empty($tableData)) {
            $fill = false;
            $totals = array_fill(0, count($columnNames), null); // Initialize totals array
            
            foreach ($tableData as $row) {
                $bgColor = $fill ? '#f8f9fc' : '#ffffff';
                $html .= '<tr style="background-color: ' . $bgColor . ';">';
                
                if (is_array($row)) {
                    foreach ($row as $index => $val) {
                        $colName = $columnNames[$index] ?? '';
                        $align = 'left';
                        
                        // Right-align numeric columns and track totals
                        $numericKeywords = ['Quantity', 'Price', 'SRP', 'Vat', 'Amount', 'Total', 'Stock'];
                        $isNumeric = false;
                        foreach ($numericKeywords as $key) {
                            if (stripos($colName, $key) !== false) {
                                $align = 'right';
                                $isNumeric = true;
                                break;
                            }
                        }

                        if ($isNumeric) {
                            $cleanVal = (float)str_replace(',', '', (string)$val);
                            if ($totals[$index] === null) $totals[$index] = 0;
                            $totals[$index] += $cleanVal;
                        }

                        $html .= '<td style="text-align:' . $align . '; border: 1px solid #ccc;">' . htmlspecialchars((string)$val) . '</td>';
                    }
                }
                $html .= '</tr>';
                $fill = !$fill;
            }

            // --- Add Totals Row ---
            $html .= '<tr style="background-color: #eee; font-weight: bold;">';
            foreach ($columnNames as $index => $name) {
                if ($index === 0) {
                    $html .= '<td style="text-align:center; border: 1px solid #333;">TOTALS</td>';
                } else if ($totals[$index] !== null) {
                    // Format numeric totals with commas and 2 decimal places, unless it's Quantity/Stock (integers)
                    $isInteger = (stripos($name, 'Quantity') !== false || stripos($name, 'Stock') !== false);
                    $formattedTotal = $isInteger ? number_format($totals[$index], 0) : number_format($totals[$index], 2);
                    $html .= '<td style="text-align:right; border: 1px solid #333;">' . $formattedTotal . '</td>';
                } else {
                    $html .= '<td style="border: 1px solid #333;"></td>';
                }
            }
            $html .= '</tr>';
        } else {
            $html .= '<tr><td colspan="' . count($columnNames) . '" style="text-align:center;">No data found</td></tr>';
        }

        $html .= '</table>';

        // Write content
        $pdf->writeHTML($headerHtml . $html, true, false, true, false, '');

        // Output PDF
        $pdf->Output('Inventory_Report_' . date('Ymd_His') . '.pdf', 'I');
        exit;
    }
}