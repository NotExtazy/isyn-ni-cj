<?php
// Use absolute path for database connection
$dbPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/database/connection.php';
if (file_exists($dbPath)) {
    include_once($dbPath);
} else {
    // Fallback to relative path
    include_once("../../database/connection.php");
}

class Process extends Database
{
    public function LoadPage() {
        try {
            $Funds = $this->SelectQuery(
                "SELECT module AS fundname FROM tbl_maintenance_module WHERE module_no = 1691 AND status = 1 ORDER BY module ASC"
            );
            
            // Log for debugging
            error_log("LoadPage - Found " . count($Funds) . " funds");
            
            header('Content-Type: application/json');
            echo json_encode(['FUNDS' => $Funds]);
        } catch (Exception $e) {
            error_log("LoadPage Error: " . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => $e->getMessage()]);
        }
    }

    /**
     * Returns the dynamic fund columns from tbl_gltrialbalance (excluding fixed columns).
     */
    private function GetFundColumns() {
        $fixed = ['id','cdate','acctno','accttitle','slno','slname','category','consolidated'];
        $in    = implode(',', array_map(function($c) { return "'$c'"; }, $fixed));

        $stmt = $this->conn->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'tbl_gltrialbalance'
               AND COLUMN_NAME NOT IN ($in)
             ORDER BY ORDINAL_POSITION ASC"
        );
        $stmt->execute();
        $res  = $stmt->get_result();
        $cols = [];
        while ($row = $res->fetch_assoc()) $cols[] = $row['COLUMN_NAME'];
        $stmt->close();
        return $cols;
    }

    public function Retrieve($data) {
        $fund       = $data['fund']       ?? '';
        $reportType = $data['reportType'] ?? 'standard';

        // Check table exists
        $check = $this->conn->query("SHOW TABLES LIKE 'tbl_gltrialbalance'");
        if ($check->num_rows === 0) {
            echo json_encode([
                'STATUS'  => 'ERROR',
                'MESSAGE' => 'Trial balance table not found. Please run Post & Undo Post first.',
            ]);
            return;
        }

        $fundCols = $this->GetFundColumns();
        
        // Determine which fund columns to return based on selection
        $selectedFundCols = [];
        if (!empty($fund)) {
            // Specific fund selected - only return that fund's column
            $fundCol = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fund));
            if (in_array($fundCol, $fundCols)) {
                $selectedFundCols = [$fundCol];
            }
        } else {
            // "All Funds" selected - return all fund columns
            $selectedFundCols = $fundCols;
        }

        // Build SELECT — always include consolidated; add selected fund cols
        $selectCols = 'acctno, accttitle, category, consolidated, cdate';
        if (!empty($selectedFundCols)) {
            $selectCols .= ', ' . implode(', ', $selectedFundCols);
        }

        // Base WHERE clause - exclude header/title rows
        $where = "WHERE acctno <> '' AND category = 'AMOUNT'";
        
        // Apply report type filter based on account number ranges
        switch ($reportType) {
            case 'adjusted':
                // Adjusted Trial Balance: All accounts (same as standard)
                break;
                
            case 'postclosing':
                // Post-Closing Trial Balance: Only permanent accounts
                $where .= " AND (
                    acctno LIKE '1%' OR 
                    acctno LIKE '2%' OR 
                    acctno LIKE '3%'
                )";
                break;
                
            case 'standard':
            default:
                // Standard Trial Balance: All accounts
                break;
        }

        // Fund filter: only show rows with non-zero balance in selected fund
        if (!empty($fund)) {
            $fundCol = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fund));
            if (in_array($fundCol, $fundCols)) {
                $where .= " AND `$fundCol` <> 0";
            }
        }

        $sql  = "SELECT $selectCols FROM tbl_gltrialbalance $where ORDER BY acctno ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $stmt->close();

        // Totals row
        $totals = ['acctno' => 'TOTAL', 'accttitle' => '', 'category' => '', 'consolidated' => 0];
        foreach ($selectedFundCols as $col) $totals[$col] = 0;
        foreach ($rows as $r) {
            $totals['consolidated'] += (float)($r['consolidated'] ?? 0);
            foreach ($selectedFundCols as $col) $totals[$col] += (float)($r[$col] ?? 0);
        }

        echo json_encode([
            'STATUS'      => 'SUCCESS',
            'ROWS'        => $rows,
            'FUND_COLS'   => $selectedFundCols,
            'SELECTED_FUND' => $fund,
            'TOTALS'      => $totals,
            'REPORT_TYPE' => $reportType,
            'HAS_DATA'    => count($rows) > 0,  // Flag to indicate if there's data
        ]);
    }
}