<?php
// Use absolute path for database connection
$dbPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/database/connection.php';
if (file_exists($dbPath)) {
    include_once($dbPath);
} else {
    // Fallback to relative path
    include_once("../../database/connection.php");
}

class FinancialStatementProcess extends Database {
    
    public function LoadPage() {
        try {
            $sql = "SELECT module AS fundname 
                    FROM tbl_maintenance_module 
                    WHERE module_no = 1691 AND status = 1 
                    ORDER BY module";
            
            $funds = $this->SelectQuery($sql);

            return [
                'STATUS' => 'SUCCESS',
                'FUNDS' => $funds
            ];
        } catch (Exception $e) {
            return [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Error loading funds: ' . $e->getMessage()
            ];
        }
    }

    // Get Income Statement
    public function GetIncomeStatement($fund, $startDate, $endDate) {
        try {
            // Determine fund column
            $fundCol = $this->getFundColumn($fund);
            
            // Get revenue accounts (4xxxx)
            $revenue = $this->getAccountsByRange($fundCol, '4', $startDate, $endDate);
            
            // Get expense accounts (5xxxx)
            $expenses = $this->getAccountsByRange($fundCol, '5', $startDate, $endDate);
            
            // Calculate totals
            $totalRevenue = array_sum(array_column($revenue, 'balance'));
            $totalExpenses = array_sum(array_column($expenses, 'balance'));
            $netIncome = $totalRevenue - $totalExpenses;

            return [
                'STATUS' => 'SUCCESS',
                'STATEMENT_TYPE' => 'income',
                'FUND' => $fund ?: 'All Funds',
                'START_DATE' => $startDate,
                'END_DATE' => $endDate,
                'REVENUE' => $revenue,
                'EXPENSES' => $expenses,
                'TOTAL_REVENUE' => $totalRevenue,
                'TOTAL_EXPENSES' => $totalExpenses,
                'NET_INCOME' => $netIncome
            ];
        } catch (Exception $e) {
            return [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Error generating income statement: ' . $e->getMessage()
            ];
        }
    }

    // Get Balance Sheet
    public function GetBalanceSheet($fund, $asOfDate) {
        try {
            $fundCol = $this->getFundColumn($fund);
            
            // Get assets (1xxxx)
            $assets = $this->getAccountsByRange($fundCol, '1', null, $asOfDate);
            
            // Get liabilities (2xxxx)
            $liabilities = $this->getAccountsByRange($fundCol, '2', null, $asOfDate);
            
            // Get equity (3xxxx)
            $equity = $this->getAccountsByRange($fundCol, '3', null, $asOfDate);
            
            // Calculate totals
            $totalAssets = array_sum(array_column($assets, 'balance'));
            $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
            $totalEquity = array_sum(array_column($equity, 'balance'));

            return [
                'STATUS' => 'SUCCESS',
                'STATEMENT_TYPE' => 'balance',
                'FUND' => $fund ?: 'All Funds',
                'AS_OF_DATE' => $asOfDate,
                'ASSETS' => $assets,
                'LIABILITIES' => $liabilities,
                'EQUITY' => $equity,
                'TOTAL_ASSETS' => $totalAssets,
                'TOTAL_LIABILITIES' => $totalLiabilities,
                'TOTAL_EQUITY' => $totalEquity,
                'BALANCE_CHECK' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01
            ];
        } catch (Exception $e) {
            return [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Error generating balance sheet: ' . $e->getMessage()
            ];
        }
    }

    // Get Cash Flow Statement
    public function GetCashFlowStatement($fund, $startDate, $endDate) {
        try {
            $fundCol = $this->getFundColumn($fund);
            
            // Get net income from income statement
            $incomeData = $this->GetIncomeStatement($fund, $startDate, $endDate);
            $netIncome = $incomeData['NET_INCOME'];
            
            // Get cash accounts (11xxx)
            $cashAccounts = $this->getAccountsByRange($fundCol, '11', $startDate, $endDate, true);
            
            // Calculate cash flow components
            $operatingCash = $netIncome;
            $investingCash = 0;
            $financingCash = 0;
            
            // Get beginning and ending cash
            $beginningCash = $this->getCashBalance($fundCol, $startDate);
            $endingCash = $this->getCashBalance($fundCol, $endDate);
            $netCashChange = $endingCash - $beginningCash;

            return [
                'STATUS' => 'SUCCESS',
                'STATEMENT_TYPE' => 'cashflow',
                'FUND' => $fund ?: 'All Funds',
                'START_DATE' => $startDate,
                'END_DATE' => $endDate,
                'NET_INCOME' => $netIncome,
                'OPERATING_CASH' => $operatingCash,
                'INVESTING_CASH' => $investingCash,
                'FINANCING_CASH' => $financingCash,
                'BEGINNING_CASH' => $beginningCash,
                'ENDING_CASH' => $endingCash,
                'NET_CASH_CHANGE' => $netCashChange,
                'CASH_ACCOUNTS' => $cashAccounts
            ];
        } catch (Exception $e) {
            return [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Error generating cash flow statement: ' . $e->getMessage()
            ];
        }
    }

    // Helper: Get fund column name
    private function getFundColumn($fund) {
        if (empty($fund)) {
            return 'consolidated';
        }
        return strtolower(str_replace(['-', ' '], '', $fund));
    }

    // Helper: Get accounts by range
    private function getAccountsByRange($fundCol, $prefix, $startDate = null, $endDate = null, $cashOnly = false) {
        $sql = "SELECT acctno, accttitle, $fundCol as balance, category, cdate
                FROM tbl_gltrialbalance
                WHERE acctno LIKE '" . $prefix . "%'
                AND category = 'AMOUNT'
                AND acctno <> ''";
        
        if ($endDate) {
            $sql .= " AND cdate <= '" . $endDate . "'";
        }
        
        $sql .= " ORDER BY acctno";
        
        $results = $this->SelectQuery($sql);
        
        // Convert balance to float
        foreach ($results as &$row) {
            $row['balance'] = floatval($row['balance']);
        }
        
        return $results;
    }

    // Helper: Get cash balance
    private function getCashBalance($fundCol, $date) {
        $sql = "SELECT SUM($fundCol) as total
                FROM tbl_gltrialbalance
                WHERE acctno LIKE '11%'
                AND category = 'AMOUNT'
                AND cdate <= '" . $date . "'";
        
        $result = $this->SelectQuery($sql);
        return floatval($result[0]['total'] ?? 0);
    }
}
?>
