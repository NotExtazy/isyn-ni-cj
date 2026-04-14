<?php
require_once("database/connection.php");

class DataConfigurationProcess extends Database {
    public function __construct() {
        parent::__construct();
    }
    
    public function LoadPage() {
        try {
            $sql = "SELECT module AS fundname FROM tbl_maintenance_module WHERE module_no = 1691 AND status = 1 ORDER BY module";
            $funds = $this->SelectQuery($sql);
            return [
                'STATUS' => 'SUCCESS',
                'FUNDS' => $funds,
                'CURRENT_YEAR' => date('Y')
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function GetBeginningBalances($fund, $fiscalYear = null) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $sql = "SELECT acctno, accttitle, category, consolidated, isynergies, isynilagan, isynsantiago, acash FROM tbl_glbeginningbalance ORDER BY acctno";
            $data = $this->SelectQuery($sql);
            $result = [];
            $fundColumn = $this->getFundColumn($fund);
            
            foreach ($data as $row) {
                $result[] = [
                    'acctno' => $row['acctno'],
                    'accttitle' => $row['accttitle'],
                    'category' => $row['category'],
                    'consolidated' => $row['consolidated'] ?? '0',
                    'isynergies' => $row['isynergies'] ?? '0',
                    'isynilagan' => $row['isynilagan'] ?? '0',
                    'isynsantiago' => $row['isynsantiago'] ?? '0',
                    'acash' => $row['acash'] ?? '0',
                    'selected_fund_balance' => $row[$fundColumn] ?? '0'
                ];
            }
            return ['STATUS' => 'SUCCESS', 'ACCOUNTS' => $result, 'SELECTED_FUND' => $fund];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    private function getFundColumn($fundName) {
        $fundMap = [
            'CONSOLIDATED' => 'consolidated',
            'ISYNERGIES' => 'isynergies',
            'ISYN-ILAGAN' => 'isynilagan',
            'ISYN-SANTIAGO' => 'isynsantiago',
            'ACASH' => 'acash'
        ];
        $fundUpper = strtoupper($fundName);
        return $fundMap[$fundUpper] ?? 'consolidated';
    }
    
    public function SaveBeginningBalance($fund, $acctno, $accttitle, $balance, $fiscalYear = null) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $acctno = $this->conn->real_escape_string($acctno);
            $accttitle = $this->conn->real_escape_string($accttitle);
            $balance = floatval($balance);
            
            $fundColumn = $this->getFundColumn($fund);
            
            // Check if account exists
            $checkSql = "SELECT acctno FROM tbl_glbeginningbalance WHERE acctno = '$acctno'";
            $exists = $this->SelectQuery($checkSql);
            
            if (!empty($exists)) {
                // Update existing record
                $sql = "UPDATE tbl_glbeginningbalance SET $fundColumn = '$balance', accttitle = '$accttitle' WHERE acctno = '$acctno'";
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_glbeginningbalance (acctno, accttitle, category, $fundColumn) VALUES ('$acctno', '$accttitle', '', '$balance')";
            }
            
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'Beginning balance saved successfully'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function GetAccountCodes() {
        try {
            // Only return accounts that have SL balance data
            $sql = "SELECT DISTINCT bb.acctno, bb.accttitle 
                    FROM tbl_glbeginningbalance bb
                    INNER JOIN tbl_glslbeginningbalance sl ON bb.acctno = sl.acctno
                    ORDER BY bb.acctno";
            $accounts = $this->SelectQuery($sql);
            return ['STATUS' => 'SUCCESS', 'ACCOUNTS' => $accounts];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function GetSLBalances($fund, $acctno, $fiscalYear = null) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $acctno = $this->conn->real_escape_string($acctno);
            $fundColumn = $this->getFundColumn($fund);
            
            $sql = "SELECT slno AS sl_no, slname AS sl_name, $fundColumn AS balance 
                    FROM tbl_glslbeginningbalance 
                    WHERE acctno = '$acctno' 
                    ORDER BY slno";
            $slBalances = $this->SelectQuery($sql);
            
            return ['STATUS' => 'SUCCESS', 'SL_BALANCES' => $slBalances];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function SaveSLBalance($fund, $acctno, $slNo, $slName, $balance, $fiscalYear = null) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $acctno = $this->conn->real_escape_string($acctno);
            $slNo = $this->conn->real_escape_string($slNo);
            $slName = $this->conn->real_escape_string($slName);
            $balance = floatval($balance);
            
            $fundColumn = $this->getFundColumn($fund);
            
            // Get account title
            $acctSql = "SELECT accttitle FROM tbl_glbeginningbalance WHERE acctno = '$acctno'";
            $acctData = $this->SelectQuery($acctSql);
            $accttitle = !empty($acctData) ? $acctData[0]['accttitle'] : '';
            
            // Check if SL exists
            $checkSql = "SELECT id FROM tbl_glslbeginningbalance WHERE acctno = '$acctno' AND slno = '$slNo'";
            $exists = $this->SelectQuery($checkSql);
            
            if (!empty($exists)) {
                // Update existing record
                $sql = "UPDATE tbl_glslbeginningbalance SET $fundColumn = '$balance', slname = '$slName' WHERE acctno = '$acctno' AND slno = '$slNo'";
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_glslbeginningbalance (acctno, accttitle, slno, slname, $fundColumn) 
                        VALUES ('$acctno', '$accttitle', '$slNo', '$slName', '$balance')";
            }
            
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'SL balance saved successfully'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function GetYearEndBalances($fund, $yearendDate) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $yearendDate = $this->conn->real_escape_string($yearendDate);
            $fundColumn = $this->getFundColumn($fund);
            
            // Get fiscal year start date (assuming January 1st of the year)
            $year = date('Y', strtotime($yearendDate));
            $fiscalYearStart = $year . '-01-01';
            
            // Debug: Check what funds exist in tbl_glsnapshot for this date range
            $debugSql = "SELECT DISTINCT fund FROM tbl_glsnapshot 
                         WHERE STR_TO_DATE(cdate, '%Y-%m-%d') BETWEEN '$fiscalYearStart' AND '$yearendDate' 
                         LIMIT 10";
            $debugFunds = $this->SelectQuery($debugSql);
            
            // Get all accounts from beginning balance with saved year-end data
            $sql = "SELECT 
                        bb.acctno,
                        bb.accttitle,
                        bb.category,
                        COALESCE(bb.$fundColumn, 0) as beginning_balance,
                        COALESCE(ye.$fundColumn, 0) as yearend_balance,
                        COALESCE(ye.is_locked, 0) as is_locked
                    FROM tbl_glbeginningbalance bb
                    LEFT JOIN tbl_glyearendbalance ye ON bb.acctno = ye.acctno AND ye.yearend_date = '$yearendDate'
                    ORDER BY bb.acctno";
            
            $accounts = $this->SelectQuery($sql);
            
            // Calculate transactions for each account
            foreach ($accounts as &$account) {
                $acctno = $account['acctno'];
                
                // Get total debits and credits from tbl_glsnapshot
                $transSql = "SELECT 
                                SUM(debit) as total_debits,
                                SUM(credit) as total_credits
                             FROM tbl_glsnapshot
                             WHERE acctno = '$acctno'
                             AND STR_TO_DATE(cdate, '%Y-%m-%d') BETWEEN '$fiscalYearStart' AND '$yearendDate'
                             AND fund = '$fund'";
                
                $transData = $this->SelectQuery($transSql);
                
                $totalDebits = !empty($transData) ? floatval($transData[0]['total_debits']) : 0;
                $totalCredits = !empty($transData) ? floatval($transData[0]['total_credits']) : 0;
                
                // Calculate year-end balance based on account category
                $beginningBalance = floatval($account['beginning_balance']);
                $category = strtoupper($account['category']);
                
                // Asset and Expense accounts: Beginning + Debits - Credits
                // Liability, Equity, and Revenue accounts: Beginning - Debits + Credits
                if (in_array($category, ['ASSET', 'ASSETS', 'EXPENSE', 'EXPENSES'])) {
                    $calculatedBalance = $beginningBalance + $totalDebits - $totalCredits;
                } else {
                    $calculatedBalance = $beginningBalance - $totalDebits + $totalCredits;
                }
                
                $account['total_debits'] = $totalDebits;
                $account['total_credits'] = $totalCredits;
                $account['calculated_balance'] = $calculatedBalance;
                
                // Use saved balance if exists and locked, otherwise use calculated
                if ($account['is_locked'] == 1 && $account['yearend_balance'] != 0) {
                    $account['final_balance'] = $account['yearend_balance'];
                } else {
                    $account['final_balance'] = $calculatedBalance;
                }
            }
            
            return [
                'STATUS' => 'SUCCESS',
                'ACCOUNTS' => $accounts,
                'YEAREND_DATE' => $yearendDate,
                'FUND' => $fund,
                'DEBUG_FUNDS' => $debugFunds,
                'DEBUG_QUERY_FUND' => $fund,
                'FISCAL_YEAR_START' => $fiscalYearStart
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function SaveYearEndBalance($fund, $acctno, $accttitle, $balance, $yearendDate, $username = '') {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $acctno = $this->conn->real_escape_string($acctno);
            $accttitle = $this->conn->real_escape_string($accttitle);
            $balance = floatval($balance);
            $yearendDate = $this->conn->real_escape_string($yearendDate);
            
            $fundColumn = $this->getFundColumn($fund);
            
            // Get category from beginning balance
            $catSql = "SELECT category FROM tbl_glbeginningbalance WHERE acctno = '$acctno'";
            $catData = $this->SelectQuery($catSql);
            $category = !empty($catData) ? $catData[0]['category'] : '';
            
            // Check if record exists
            $checkSql = "SELECT id, is_locked FROM tbl_glyearendbalance WHERE acctno = '$acctno' AND yearend_date = '$yearendDate'";
            $exists = $this->SelectQuery($checkSql);
            
            if (!empty($exists)) {
                // Check if locked
                if ($exists[0]['is_locked'] == 1) {
                    return [
                        'STATUS' => 'ERROR',
                        'MESSAGE' => 'This year-end period is locked. Please unlock before editing.'
                    ];
                }
                
                // Update existing record
                $sql = "UPDATE tbl_glyearendbalance 
                        SET $fundColumn = '$balance', 
                            accttitle = '$accttitle',
                            category = '$category'
                        WHERE acctno = '$acctno' AND yearend_date = '$yearendDate'";
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_glyearendbalance 
                        (acctno, accttitle, category, yearend_date, $fundColumn) 
                        VALUES ('$acctno', '$accttitle', '$category', '$yearendDate', '$balance')";
            }
            
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'Year-end balance saved successfully'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function LockYearEnd($fund, $yearendDate) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $yearendDate = $this->conn->real_escape_string($yearendDate);
            
            // Lock all records for this date
            $sql = "UPDATE tbl_glyearendbalance 
                    SET is_locked = 1 
                    WHERE yearend_date = '$yearendDate'";
            
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'Year-end period locked successfully. No further changes allowed.'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function UnlockYearEnd($fund, $yearendDate) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $yearendDate = $this->conn->real_escape_string($yearendDate);
            
            // Unlock all records for this date
            $sql = "UPDATE tbl_glyearendbalance 
                    SET is_locked = 0 
                    WHERE yearend_date = '$yearendDate'";
            
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'Year-end period unlocked successfully. You can now make changes.'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // BUDGET VARIANCE DATA METHODS
    // ══════════════════════════════════════════════════════════════════════
    
    public function GetBudgetData($fund, $budgetMonth) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $budgetMonth = $this->conn->real_escape_string($budgetMonth);
            $fundColumn = $this->getFundColumn($fund);
            
            // Get first and last day of the month
            $firstDay = date('Y-m-01', strtotime($budgetMonth));
            $lastDay = date('Y-m-t', strtotime($budgetMonth));
            
            // Get all accounts from beginning balance with budget data
            $sql = "SELECT 
                        bb.acctno,
                        bb.accttitle,
                        bb.category,
                        COALESCE(bg.$fundColumn, 0) as budget_amount
                    FROM tbl_glbeginningbalance bb
                    LEFT JOIN tbl_glbudget bg ON bb.acctno = bg.acctno 
                        AND DATE_FORMAT(bg.budget_month, '%Y-%m') = DATE_FORMAT('$budgetMonth', '%Y-%m')
                    ORDER BY bb.acctno";
            
            $accounts = $this->SelectQuery($sql);
            
            // Calculate actual amounts for each account
            foreach ($accounts as &$account) {
                $acctno = $account['acctno'];
                
                // Get actual debits and credits from tbl_glsnapshot
                $actualSql = "SELECT 
                                SUM(debit) as total_debits,
                                SUM(credit) as total_credits
                             FROM tbl_glsnapshot
                             WHERE acctno = '$acctno'
                             AND STR_TO_DATE(cdate, '%Y-%m-%d') BETWEEN '$firstDay' AND '$lastDay'
                             AND fund = '$fund'";
                
                $actualData = $this->SelectQuery($actualSql);
                
                $totalDebits = !empty($actualData) ? floatval($actualData[0]['total_debits']) : 0;
                $totalCredits = !empty($actualData) ? floatval($actualData[0]['total_credits']) : 0;
                
                // Calculate net actual amount based on category
                $category = strtoupper($account['category']);
                
                // For expense accounts, actual = debits - credits
                // For revenue accounts, actual = credits - debits
                if (in_array($category, ['EXPENSE', 'EXPENSES'])) {
                    $actualAmount = $totalDebits - $totalCredits;
                } elseif (in_array($category, ['REVENUE', 'INCOME'])) {
                    $actualAmount = $totalCredits - $totalDebits;
                } else {
                    // For asset/liability accounts, show net change
                    $actualAmount = $totalDebits - $totalCredits;
                }
                
                $budgetAmount = floatval($account['budget_amount']);
                $variance = $actualAmount - $budgetAmount;
                $variancePercent = $budgetAmount != 0 ? ($variance / $budgetAmount) * 100 : 0;
                
                $account['actual_amount'] = $actualAmount;
                $account['variance'] = $variance;
                $account['variance_percent'] = $variancePercent;
                $account['total_debits'] = $totalDebits;
                $account['total_credits'] = $totalCredits;
                
                // Determine status
                if ($budgetAmount == 0) {
                    $account['status'] = 'No Budget';
                } elseif (abs($variancePercent) <= 5) {
                    $account['status'] = 'On Track';
                } elseif ($variance > 0) {
                    $account['status'] = 'Over Budget';
                } else {
                    $account['status'] = 'Under Budget';
                }
            }
            
            return [
                'STATUS' => 'SUCCESS',
                'ACCOUNTS' => $accounts,
                'BUDGET_MONTH' => $budgetMonth,
                'FUND' => $fund,
                'PERIOD' => date('F Y', strtotime($budgetMonth))
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function SaveBudgetAmount($fund, $acctno, $accttitle, $budgetAmount, $budgetMonth) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $acctno = $this->conn->real_escape_string($acctno);
            $accttitle = $this->conn->real_escape_string($accttitle);
            $budgetAmount = floatval($budgetAmount);
            $budgetMonth = $this->conn->real_escape_string($budgetMonth);
            
            $fundColumn = $this->getFundColumn($fund);
            
            // Get category from beginning balance
            $catSql = "SELECT category FROM tbl_glbeginningbalance WHERE acctno = '$acctno'";
            $catData = $this->SelectQuery($catSql);
            $category = !empty($catData) ? $catData[0]['category'] : '';
            
            // Normalize budget_month to first day of month
            $budgetMonth = date('Y-m-01', strtotime($budgetMonth));
            
            // Check if record exists
            $checkSql = "SELECT id FROM tbl_glbudget 
                         WHERE acctno = '$acctno' 
                         AND DATE_FORMAT(budget_month, '%Y-%m') = DATE_FORMAT('$budgetMonth', '%Y-%m')";
            $exists = $this->SelectQuery($checkSql);
            
            if (!empty($exists)) {
                // Update existing record
                $sql = "UPDATE tbl_glbudget 
                        SET $fundColumn = '$budgetAmount', 
                            accttitle = '$accttitle',
                            category = '$category'
                        WHERE acctno = '$acctno' 
                        AND DATE_FORMAT(budget_month, '%Y-%m') = DATE_FORMAT('$budgetMonth', '%Y-%m')";
            } else {
                // Insert new record
                $sql = "INSERT INTO tbl_glbudget 
                        (acctno, accttitle, category, budget_month, $fundColumn) 
                        VALUES ('$acctno', '$accttitle', '$category', '$budgetMonth', '$budgetAmount')";
            }
            
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'Budget amount saved successfully'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function CopyBudgetToMonth($fund, $sourceMonth, $targetMonth) {
        try {
            $fund = $this->conn->real_escape_string($fund);
            $sourceMonth = $this->conn->real_escape_string($sourceMonth);
            $targetMonth = $this->conn->real_escape_string($targetMonth);
            $fundColumn = $this->getFundColumn($fund);
            
            // Normalize to first day of month
            $sourceMonth = date('Y-m-01', strtotime($sourceMonth));
            $targetMonth = date('Y-m-01', strtotime($targetMonth));
            
            // Check if source month has data
            $checkSql = "SELECT COUNT(*) as count FROM tbl_glbudget 
                         WHERE DATE_FORMAT(budget_month, '%Y-%m') = DATE_FORMAT('$sourceMonth', '%Y-%m')
                         AND $fundColumn > 0";
            $checkData = $this->SelectQuery($checkSql);
            
            if (empty($checkData) || $checkData[0]['count'] == 0) {
                return [
                    'STATUS' => 'ERROR',
                    'MESSAGE' => 'No budget data found for source month'
                ];
            }
            
            // Delete existing target month data for this fund
            $deleteSql = "DELETE FROM tbl_glbudget 
                          WHERE DATE_FORMAT(budget_month, '%Y-%m') = DATE_FORMAT('$targetMonth', '%Y-%m')";
            $this->ExecuteQuery($deleteSql);
            
            // Copy budget data from source to target month
            $copySql = "INSERT INTO tbl_glbudget (acctno, accttitle, category, budget_month, $fundColumn)
                        SELECT acctno, accttitle, category, '$targetMonth', $fundColumn
                        FROM tbl_glbudget
                        WHERE DATE_FORMAT(budget_month, '%Y-%m') = DATE_FORMAT('$sourceMonth', '%Y-%m')
                        AND $fundColumn > 0";
            
            $this->ExecuteQuery($copySql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'Budget copied successfully from ' . date('F Y', strtotime($sourceMonth)) . ' to ' . date('F Y', strtotime($targetMonth))
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // ══════════════════════════════════════════════════════════════════════
    // PESO DATA METHODS
    // ══════════════════════════════════════════════════════════════════════
    
    public function GetPESOData() {
        try {
            $sql = "SELECT item_name, item_value, description FROM tbl_glpesodata ORDER BY id";
            $pesoData = $this->SelectQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'PESO_DATA' => $pesoData
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function SavePESOData($itemName, $itemValue) {
        try {
            $itemName = $this->conn->real_escape_string($itemName);
            $itemValue = floatval($itemValue);
            
            // Check if item exists
            $checkSql = "SELECT id FROM tbl_glpesodata WHERE item_name = '$itemName'";
            $exists = $this->SelectQuery($checkSql);
            
            if (!empty($exists)) {
                // Update existing item
                $sql = "UPDATE tbl_glpesodata SET item_value = '$itemValue' WHERE item_name = '$itemName'";
            } else {
                // Insert new item
                $sql = "INSERT INTO tbl_glpesodata (item_name, item_value) VALUES ('$itemName', '$itemValue')";
            }
            
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'PESO data saved successfully'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
    
    public function DeletePESOData($itemName) {
        try {
            $itemName = $this->conn->real_escape_string($itemName);
            
            $sql = "DELETE FROM tbl_glpesodata WHERE item_name = '$itemName'";
            $this->ExecuteQuery($sql);
            
            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'PESO data deleted successfully'
            ];
        } catch (Exception $e) {
            return ['STATUS' => 'ERROR', 'MESSAGE' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
