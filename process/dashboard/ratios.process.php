<?php
include_once("../../database/connection.php");

class RatiosProcess extends Database
{
    public function GetRatioData($data){
        $year = $data['year'];
        
        // Initialize all ratio values
        $ratios = [
            'currentRatio' => 0,
            'acidTestRatio' => 0,
            'cashRatio' => 0,
            'roa' => 0,
            'roe' => 0,
            'grossMargin' => 0,
            'operatingMargin' => 0,
            'assetTurnover' => 0,
            'receivablesTurnover' => 0,
            'inventoryTurnover' => 0,
            'daysSalesInventory' => 0,
            'debtRatio' => 0,
            'debtEquityRatio' => 0,
            'totalEquity' => 0,
            'totalShares' => 1,
            'netMarketPerShare' => 0
        ];
        
        if ($this->ensureTableExists('tbl_glsnapshot')) {
            // Get Current Assets (111xx, 112xx, 113xx, 114xx, 115xx, 116xx, 117xx, 118xx, 119xx)
            $currentAssets = $this->getAccountBalance($year, ['111%', '112%', '113%', '114%', '115%', '116%', '117%', '118%', '119%'], 'debit');
            error_log("Current Assets: $currentAssets");
            
            // Get Cash and Cash Equivalents (111xx)
            $cash = $this->getAccountBalance($year, ['111%'], 'debit');
            error_log("Cash: $cash");
            
            // Get Accounts Receivable (115xx)
            $receivables = $this->getAccountBalance($year, ['115%'], 'debit');
            error_log("Receivables: $receivables");
            
            // Get Inventory (119xx)
            $inventory = $this->getAccountBalance($year, ['119%'], 'debit');
            error_log("Inventory: $inventory");
            
            // Get Total Assets (11xxx, 12xxx, 13xxx, 14xxx)
            $totalAssets = $this->getAccountBalance($year, ['11%', '12%', '13%', '14%'], 'debit');
            error_log("Total Assets: $totalAssets");
            
            // Get Current Liabilities (211xx, 212xx, 213xx, 214xx, 215xx, 216xx, 217xx)
            $currentLiabilities = $this->getAccountBalance($year, ['211%', '212%', '213%', '214%', '215%', '216%', '217%'], 'credit');
            error_log("Current Liabilities: $currentLiabilities");
            
            // Get Total Liabilities (21xxx, 22xxx, 23xxx, 24xxx)
            $totalLiabilities = $this->getAccountBalance($year, ['21%', '22%', '23%', '24%'], 'credit');
            error_log("Total Liabilities: $totalLiabilities");
            
            // Get Equity components
            // Stockholders' Equity = Share Capital + Retained Earnings + OCI
            $shareCapital = $this->getAccountBalance($year, ['311%', '312%', '313%', '314%', '315%', '316%', '317%', '318%'], 'credit');
            $retainedEarnings = $this->getAccountBalance($year, ['31900'], 'credit');
            $oci = $this->getAccountBalance($year, ['32000'], 'credit');
            
            // Beginning Equity (for ROE calculation)
            $beginningEquity = $shareCapital + $retainedEarnings + $oci;
            error_log("Beginning Equity Components:");
            error_log("  Share Capital (311%-318%): $shareCapital");
            error_log("  Retained Earnings (31900): $retainedEarnings");
            error_log("  OCI (32000): $oci");
            error_log("  Total Beginning Equity: $beginningEquity");
            
            // Get Revenue (41xxx, 42xxx, 43xxx)
            $revenue = $this->getAccountBalance($year, ['41%', '42%', '43%'], 'credit');
            error_log("Total Revenue (41%, 42%, 43%): $revenue");
            
            // Get Merchandise Sales only (41100) for calculations
            $merchandiseSales = $this->getAccountBalance($year, ['41100'], 'credit');
            error_log("Merchandise Sales (41100): $merchandiseSales");
            
            // Get Cost of Sales (41200) - This is under REVENUES section but represents costs
            // Cost of Sales includes: Purchases, Freight In, minus Returns and Discounts
            $costOfSales = $this->getAccountBalance($year, ['41200', '41210', '41240'], 'debit');
            $purchaseReturns = $this->getAccountBalance($year, ['41220', '41230'], 'credit');
            $cogs = $costOfSales - $purchaseReturns;
            error_log("Cost of Sales calculation:");
            error_log("  Purchases + Freight (41200, 41210, 41240): $costOfSales");
            error_log("  Purchase Returns/Discounts (41220, 41230): $purchaseReturns");
            error_log("  Net COGS: $cogs");
            
            // Get Operating Expenses (51xxx, 52xxx, 53xxx, 54xxx, 55xxx)
            $operatingExpenses = $this->getAccountBalance($year, ['51%', '52%', '53%', '54%', '55%'], 'debit');
            error_log("Operating Expenses: $operatingExpenses");
            
            // Get Total Expenses (51xxx, 52xxx, 53xxx, 54xxx, 55xxx, 56xxx, 57xxx)
            $totalExpenses = $this->getAccountBalance($year, ['51%', '52%', '53%', '54%', '55%', '56%', '57%'], 'debit');
            error_log("Total Expenses: $totalExpenses");
            
            // Get Donation (Account 53100 - part of expenses but tracked separately)
            $donation = $this->getAccountBalance($year, ['53100'], 'debit');
            error_log("Donation: $donation");
            
            // Get Provision for Income Tax (Account 56000)
            // This is Credit normal, so use credit - debit
            $provision = $this->getAccountBalance($year, ['56000'], 'credit');
            // If negative, convert to positive (same logic as dashboard.process.php)
            $provision = $provision < 0 ? abs($provision) : $provision;
            error_log("Provision for Income Tax: $provision");
            
            // Calculate Net Income Before Tax (Revenue - Total Expenses)
            // Note: Donation (53100) is already included in Total Expenses (51%-57%)
            $netIncomeBeforeTax = $revenue - $totalExpenses;
            error_log("Net Income Before Tax: $netIncomeBeforeTax");
            
            // Calculate Net Income After Tax (Income Before Tax - Provision)
            // This matches the Statement of Operations calculation
            $netIncomeAfterTax = $netIncomeBeforeTax - $provision;
            error_log("Net Income After Tax: $netIncomeAfterTax");
            
            // NOW calculate Total Equity (after we have netIncomeAfterTax)
            // For balance sheet purposes, total equity includes current year income
            // Total Equity = Beginning Equity + Net Income After Tax
            $totalEquity = $beginningEquity + $netIncomeAfterTax;
            error_log("Total Equity (for balance sheet): $totalEquity");
            
            // For ROE calculation, use BEGINNING equity (before current year income)
            // Standard practice: ROE = Net Income / Average Equity
            // Simplified: ROE = Net Income / Beginning Equity (more conservative)
            $equityForROE = $beginningEquity;
            error_log("Equity for ROE calculation: $equityForROE");
            
            // Calculate Gross Profit
            $grossProfit = $revenue - $cogs;
            
            // Calculate Operating Income
            $operatingIncome = $grossProfit - $operatingExpenses;
            
            // LIQUIDITY RATIOS
            // Current Ratio = Current Assets / Current Liabilities
            if ($currentLiabilities != 0) {
                $ratios['currentRatio'] = $currentAssets / $currentLiabilities;
                error_log("Current Ratio calculated: " . $ratios['currentRatio']);
            } else {
                error_log("Current Ratio = 0 (Current Liabilities is 0)");
            }
            
            // Acid-Test Ratio = (Current Assets - Inventory) / Current Liabilities
            if ($currentLiabilities != 0) {
                $ratios['acidTestRatio'] = ($currentAssets - $inventory) / $currentLiabilities;
                error_log("Acid-Test Ratio calculated: " . $ratios['acidTestRatio']);
            } else {
                error_log("Acid-Test Ratio = 0 (Current Liabilities is 0)");
            }
            
            // Cash Ratio = Cash / Current Liabilities
            if ($currentLiabilities != 0) {
                $ratios['cashRatio'] = $cash / $currentLiabilities;
                error_log("Cash Ratio calculated: " . $ratios['cashRatio']);
            } else {
                error_log("Cash Ratio = 0 (Current Liabilities is 0)");
            }
            
            // PROFITABILITY RATIOS
            // ROA = Net Income After Tax / Total Assets (return as decimal, not percentage)
            if ($totalAssets != 0) {
                $ratios['roa'] = $netIncomeAfterTax / $totalAssets;
                error_log("ROA calculated: " . $ratios['roa']);
            } else {
                error_log("ROA = 0 (Total Assets is 0)");
            }
            
            // ROE = Net Income After Tax / Beginning Equity (return as decimal, not percentage)
            // Use beginning equity (before adding current year's income)
            if ($equityForROE != 0) {
                $ratios['roe'] = $netIncomeAfterTax / $equityForROE;
                error_log("ROE Calculation Details:");
                error_log("  Net Income After Tax: $netIncomeAfterTax");
                error_log("  Beginning Equity (for ROE): $equityForROE");
                error_log("  ROE Formula: ($netIncomeAfterTax / $equityForROE)");
                error_log("  ROE Result: " . $ratios['roe']);
            } else {
                error_log("ROE = 0 (Equity is 0)");
            }
            
            // Gross Margin = (Total Revenue - COGS) / Total Revenue (return as decimal)
            // Use Total Revenue, not just Merchandise Sales
            if ($revenue != 0) {
                $grossProfit = $revenue - $cogs;
                $ratios['grossMargin'] = $grossProfit / $revenue;
                error_log("Gross Margin calculated: " . $ratios['grossMargin']);
                error_log("  Total Revenue: $revenue");
                error_log("  COGS: $cogs");
                error_log("  Gross Profit: $grossProfit");
            } else {
                error_log("Gross Margin = 0 (Revenue is 0)");
            }
            
            // Operating Margin = Operating Income before tax / Total Revenue (return as decimal, not percentage)
            // Operating Income before tax = Revenue - Expenses (before provision)
            if ($revenue != 0) {
                $ratios['operatingMargin'] = $netIncomeBeforeTax / $revenue;
                error_log("Operating Margin calculated: " . $ratios['operatingMargin']);
            } else {
                error_log("Operating Margin = 0 (Revenue is 0)");
            }
            
            // EFFICIENCY RATIOS
            // Asset Turnover = Net Sales Revenue / Average Total Assets
            // For average, we'd need beginning and ending balance, using current year total for now
            if ($totalAssets != 0) {
                $ratios['assetTurnover'] = $revenue / $totalAssets;
                error_log("Asset Turnover calculated: " . $ratios['assetTurnover']);
            } else {
                error_log("Asset Turnover = 0 (Total Assets is 0)");
            }
            
            // Receivables Turnover = Net Credit Sales / Average Accounts Receivable
            // Using total revenue as proxy for net credit sales
            if ($receivables != 0) {
                $ratios['receivablesTurnover'] = $revenue / $receivables;
                error_log("Receivables Turnover calculated: " . $ratios['receivablesTurnover']);
            } else {
                error_log("Receivables Turnover = 0 (Receivables is 0)");
            }
            
            // Inventory Turnover = COGS / Average Inventory
            if ($inventory != 0) {
                $ratios['inventoryTurnover'] = $cogs / $inventory;
                error_log("Inventory Turnover calculated: " . $ratios['inventoryTurnover']);
            } else {
                error_log("Inventory Turnover = 0 (Inventory is 0)");
            }
            
            // Days Sales in Inventory = 365 / Inventory Turnover
            if ($ratios['inventoryTurnover'] != 0) {
                $ratios['daysSalesInventory'] = 365 / $ratios['inventoryTurnover'];
                error_log("Days Sales in Inventory calculated: " . $ratios['daysSalesInventory']);
            } else {
                error_log("Days Sales in Inventory = 0 (Inventory Turnover is 0)");
            }
            
            // LEVERAGE RATIOS
            // Debt Ratio = Total Liabilities / Total Assets
            if ($totalAssets != 0) {
                $ratios['debtRatio'] = $totalLiabilities / $totalAssets;
                error_log("Debt Ratio calculated: " . $ratios['debtRatio']);
            } else {
                error_log("Debt Ratio = 0 (Total Assets is 0)");
            }
            
            // Debt to Equity = Total Liabilities / Total Equity (including current income)
            if ($totalEquity != 0) {
                $ratios['debtEquityRatio'] = $totalLiabilities / $totalEquity;
                error_log("Debt to Equity calculated: " . $ratios['debtEquityRatio']);
            } else {
                error_log("Debt to Equity = 0 (Equity is 0)");
            }
            
            // NET MARKET PER SHARE
            $ratios['totalEquity'] = $totalEquity;
            $ratios['totalShares'] = $this->getTotalShares($year);
            if ($ratios['totalShares'] != 0) {
                $ratios['netMarketPerShare'] = $totalEquity / $ratios['totalShares'];
            }
        }
        
        echo json_encode(['STATUS' => 'SUCCESS', 'DATA' => $ratios, 'DEBUG' => [
            'currentAssets' => $currentAssets,
            'currentLiabilities' => $currentLiabilities,
            'cash' => $cash,
            'receivables' => $receivables,
            'inventory' => $inventory,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'beginningEquity' => $beginningEquity,
            'totalEquity' => $totalEquity,
            'shareCapital' => $shareCapital,
            'retainedEarnings' => $retainedEarnings,
            'oci' => $oci,
            'revenue' => $revenue,
            'merchandiseSales' => $merchandiseSales,
            'cogs' => $cogs,
            'operatingExpenses' => $operatingExpenses,
            'totalExpenses' => $totalExpenses,
            'donation' => $donation,
            'provision' => $provision,
            'netIncomeBeforeTax' => $netIncomeBeforeTax,
            'netIncomeAfterTax' => $netIncomeAfterTax,
            'grossProfit' => $grossProfit,
            'roaCalculation' => "($netIncomeAfterTax / $totalAssets) = " . (($totalAssets != 0) ? ($netIncomeAfterTax / $totalAssets) : 0),
            'roeCalculation' => "($netIncomeAfterTax / $equityForROE) = " . (($equityForROE != 0) ? ($netIncomeAfterTax / $equityForROE) : 0),
            'roePercentage' => (($equityForROE != 0) ? (($netIncomeAfterTax / $equityForROE) * 100) : 0) . '%',
            'accountingEquation' => "Assets ($totalAssets) = Liabilities ($totalLiabilities) + Total Equity ($totalEquity) = " . ($totalLiabilities + $totalEquity),
            'accountingEquationBalanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 1
        ]]);
    }
    
    private function getAccountBalance($year, $accountPrefixes, $normalBalance) {
        $balance = 0;
        
        // Escape the year value
        $yearEscaped = $this->conn->real_escape_string($year);
        
        // Build WHERE clause for multiple account prefixes
        $conditions = [];
        foreach ($accountPrefixes as $prefix) {
            $conditions[] = "acctno LIKE '" . $this->conn->real_escape_string($prefix) . "'";
        }
        $whereClause = implode(' OR ', $conditions);
        
        // Determine account type based on first character
        $firstChar = substr($accountPrefixes[0], 0, 1);
        $isBalanceSheetAccount = in_array($firstChar, ['1', '2', '3']);
        
        if ($isBalanceSheetAccount) {
            // Balance Sheet accounts: Need CUMULATIVE balance from ALL time up to selected year
            // This includes all transactions from the beginning of your records
            if ($normalBalance === 'debit') {
                $sql = "SELECT ROUND(SUM(debit - credit), 2) as Amount 
                        FROM tbl_glsnapshot 
                        WHERE YEAR(cdate) <= '$yearEscaped' AND ($whereClause)";
            } else {
                $sql = "SELECT ROUND(SUM(credit - debit), 2) as Amount 
                        FROM tbl_glsnapshot 
                        WHERE YEAR(cdate) <= '$yearEscaped' AND ($whereClause)";
            }
            error_log("Balance Sheet Query for year $yearEscaped: $sql");
        } else {
            // Income Statement accounts: Only current year transactions
            if ($normalBalance === 'debit') {
                $sql = "SELECT ROUND(SUM(debit - credit), 2) as Amount 
                        FROM tbl_glsnapshot 
                        WHERE YEAR(cdate) = '$yearEscaped' AND ($whereClause)";
            } else {
                $sql = "SELECT ROUND(SUM(credit - debit), 2) as Amount 
                        FROM tbl_glsnapshot 
                        WHERE YEAR(cdate) = '$yearEscaped' AND ($whereClause)";
            }
            error_log("Income Statement Query for year $yearEscaped: $sql");
        }
        
        $result = $this->conn->query($sql);
        if ($result) {
            if ($row = $result->fetch_assoc()) {
                $balance = ($row['Amount'] !== null && $row['Amount'] !== '') ? floatval($row['Amount']) : 0;
            }
            $result->free();
        } else {
            error_log("SQL Error in getAccountBalance: " . $this->conn->error);
            error_log("SQL Query: " . $sql);
        }
        
        error_log("Account balance for prefixes " . implode(',', $accountPrefixes) . " in year $yearEscaped: $balance");
        return $balance;
    }
    
    private function getTotalShares($year) {
        // Get total shares from tbl_shareholder_info table
        // Sum all noofshare values from all shareholders
        
        $totalShares = 0;
        
        if ($this->ensureTableExists('tbl_shareholder_info')) {
            $sql = "SELECT SUM(noofshare) as TotalShares FROM tbl_shareholder_info";
            $result = $this->conn->query($sql);
            
            if ($result) {
                if ($row = $result->fetch_assoc()) {
                    $totalShares = ($row['TotalShares'] !== null && $row['TotalShares'] !== '') ? floatval($row['TotalShares']) : 0;
                    error_log("Total Shares from tbl_shareholder_info: $totalShares");
                }
                $result->free();
            } else {
                error_log("SQL Error in getTotalShares: " . $this->conn->error);
            }
        }
        
        // If no shares found, return 1 to avoid division by zero
        return $totalShares > 0 ? $totalShares : 1;
    }
    
    private function ensureTableExists($tableName) {
        $tableNameEscaped = $this->conn->real_escape_string($tableName);
        $sql = "SHOW TABLES LIKE '$tableNameEscaped'";
        $result = $this->conn->query($sql);
        if ($result) {
            $exists = $result->num_rows > 0;
            $result->free();
            return $exists;
        }
        return false;
    }
}
?>
