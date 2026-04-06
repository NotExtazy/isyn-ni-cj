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
}
?>