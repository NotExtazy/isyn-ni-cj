<?php
include_once("../../database/connection.php");
require_once("../../assets/PHPSpreadsheet/vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\IOFactory;

class AcashInfoProcess extends Database
{
    public function LoadAcashInfo($data){
        $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
        if ($limit <= 0) { $limit = 5000; }
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $type = isset($data['type']) ? $data['type'] : 'Main';

        if ($type == 'Custom') {
            $this->LoadData('ACASH', 'CUSTOM', $page, $limit);
        } else if ($type == 'Raw') {
            $this->LoadData('ACASH', 'RAW', $page, $limit);
        } else {
            $this->LoadMainData('ACASH', $page, $limit);
        }
    }

    public function LoadEcpayTransactions($data){
        $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
        if ($limit <= 0) { $limit = 5000; }
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $type = isset($data['type']) ? $data['type'] : 'Main';

        if ($type == 'Custom') {
            $this->LoadData('ECPAY', 'CUSTOM', $page, $limit);
        } else if ($type == 'Raw') {
            $this->LoadData('ECPAY', 'RAW', $page, $limit);
        } else if ($type == 'ECPAY') {
             // Return empty data if no category selected
             $category = isset($_POST['category']) ? $_POST['category'] : '';
             
             if (empty($category)) {
                 echo json_encode([
                    "STATUS" => "LOADED", 
                    "ECPAYTXNS" => [],
                    "TOTAL" => 0
                ]);
             } else {
                 $this->LoadMainData('ECPAY', $page, $limit, $category);
             }
        } else {
             $this->LoadMainData('ECPAY', $page, $limit);
        }
    }
    
    public function LoadMainData($type, $page = 1, $limit = 5000, $category = '') {
        $offset = ($page - 1) * $limit;
        
        $where = "";
        if ($type == 'ACASH') {
            $where = "WHERE b.SLName = 'ACASH'";
        } else {
            $like = "%EC%PAY%";
            $like2 = "%ECPAY%";
            // Legacy ECPAY detection (old uploads that contain the word ECPAY)
            $legacyWhere = "(b.Payee LIKE '$like' OR b.Explanation LIKE '$like' OR b.Explanation LIKE '$like2')";

            // For categorized views (LOADS/PAYBILLS/SERVICES), also include records that
            // only have the new category tags "(Load)/(Bill)/(Service)" that we add on upload.
            if (!empty($category)) {
                if ($category == 'LOADS') {
                    $categoryWhere = "(b.Explanation LIKE '%(Load)%' OR b.Payee LIKE '%(Load)%')";
                } else if ($category == 'PAYBILLS') {
                    $categoryWhere = "(b.Explanation LIKE '%(Bill)%' OR b.Payee LIKE '%(Bill)%')";
                } else if ($category == 'SERVICES') {
                    $categoryWhere = "(b.Explanation LIKE '%(Service)%' OR b.Payee LIKE '%(Service)%')";
                } else {
                    $categoryWhere = "1=0";
                }

                // Match either legacy ECPAY-patterned rows OR newly uploaded categorized rows
                $baseWhere = "($legacyWhere OR $categoryWhere)";
            } else {
                // Default ECPAY main view – keep legacy detection
                $baseWhere = $legacyWhere;
            }

            $where = "WHERE " . $baseWhere;
        }

        $sqlCount = "SELECT COUNT(*) as total FROM tbl_books b $where";
        $resCount = $this->conn->query($sqlCount);
        $total = $resCount ? $resCount->fetch_assoc()['total'] : 0;
        
        // Join with tbl_master_identities to get the latest Owner Name (full_name)
        // We match primarily on AcctNo (which holds the email for Acash/Some ECPay)
        // or Payee (which might hold the email for raw ECPay)
        $sql = "SELECT b.*, m.full_name as MasterName 
                FROM tbl_books b 
                LEFT JOIN tbl_master_identities m ON (m.email = b.AcctNo OR (m.email = b.Payee AND (b.AcctNo IS NULL OR b.AcctNo = '')))
                $where 
                ORDER BY STR_TO_DATE(b.CDate, '%Y-%m-%d') DESC, b.ID DESC LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);
        
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // If MasterName exists, prefer it over Payee if Payee is just an email or empty
                // But for display in frontend, we map MasterName to 'OwnerName' or overwrite 'Payee'
                if (!empty($row['MasterName'])) {
                    // If AcctNo is empty, the Email was likely in Payee. Preserve it in AcctNo.
                    if (empty($row['AcctNo'])) {
                        $row['AcctNo'] = $row['Payee'];
                    }
                    $row['Payee'] = $row['MasterName']; // Override Payee for display purposes
                    $row['AcctTitle'] = $row['MasterName']; // Override AcctTitle for display purposes
                }
                $data[] = $row;
            }
        }
        
        $key = ($type == 'ACASH') ? 'ACASHINFO' : 'ECPAYTXNS';
        
        echo json_encode([
            "STATUS" => "LOADED", 
            $key => $data,
            "TOTAL" => $total
        ]);
    }

    public function LoadEmails($data) {
        $emails = [];
        // Only fetch emails that don't have a branch assigned (or branch is empty)
        $sql = "SELECT DISTINCT email FROM tbl_master_identities WHERE email IS NOT NULL AND email != '' AND (branch_name IS NULL OR branch_name = '') ORDER BY email ASC";
        $result = $this->conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $emails[] = $row['email'];
            }
        }
        echo json_encode(["STATUS" => "SUCCESS", "EMAILS" => $emails]);
    }

    public function LoadBranches($data) {
        $branches = [];
        $sql = "SELECT DISTINCT branch_name FROM tbl_master_identities WHERE branch_name IS NOT NULL AND branch_name != '' ORDER BY branch_name ASC";
        $result = $this->conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $branches[] = $row['branch_name'];
            }
        }
        echo json_encode(["STATUS" => "SUCCESS", "BRANCHES" => $branches]);
    }

    public function LoadMaintenanceData($data) {
        $this->CheckTables();
        $rows = [];
        
        // Debug: Check what's in tbl_master_identities
        $debugSql = "SELECT COUNT(*) as total FROM tbl_master_identities WHERE branch_name IS NOT NULL AND branch_name != ''";
        $debugResult = $this->conn->query($debugSql);
        if ($debugResult) {
            $debugRow = $debugResult->fetch_assoc();
            error_log("Master identities with branch: " . $debugRow['total']);
        }
        
        // Simplified approach - get master identities first, then get counts separately
        $sql = "SELECT email, branch_name, full_name FROM tbl_master_identities 
                WHERE email IS NOT NULL AND email != '' 
                AND branch_name IS NOT NULL AND branch_name != '' 
                ORDER BY id DESC";
        
        $result = $this->conn->query($sql);
        if ($result) {
            while ($master = $result->fetch_assoc()) {
                $email = $master['email'];
                
                // Get ACASH counts for this email
                $acashSql = "SELECT TransactionYear, COUNT(*) as count 
                             FROM tbl_books 
                             WHERE SLName = 'ACASH' AND AcctNo = '$email' 
                             AND TransactionYear IS NOT NULL AND TransactionYear != ''
                             GROUP BY TransactionYear";
                $acashResult = $this->conn->query($acashSql);
                $acashCounts = [];
                if ($acashResult) {
                    while ($row = $acashResult->fetch_assoc()) {
                        $acashCounts[$row['TransactionYear']] = $row['count'];
                    }
                }
                
                // Get ECPAY counts for this email
                $ecpaySql = "SELECT TransactionYear, COUNT(*) as count 
                              FROM tbl_books 
                              WHERE (Payee LIKE '%EC%PAY%' OR Explanation LIKE '%EC%PAY%' OR Explanation LIKE '%ECPAY%')
                              AND (AcctNo = '$email' OR Payee = '$email')
                              AND TransactionYear IS NOT NULL AND TransactionYear != ''
                              GROUP BY TransactionYear";
                $ecpayResult = $this->conn->query($ecpaySql);
                $ecpayCounts = [];
                if ($ecpayResult) {
                    while ($row = $ecpayResult->fetch_assoc()) {
                        $ecpayCounts[$row['TransactionYear']] = $row['count'];
                    }
                }
                
                $master['acash_year_counts'] = $acashCounts;
                $master['ecpay_year_counts'] = $ecpayCounts;
                $rows[] = $master;
            }
            error_log("Maintenance data rows found: " . count($rows));
        } else {
            error_log("Maintenance query failed: " . $this->conn->error);
        }
        echo json_encode(["STATUS" => "SUCCESS", "DATA" => $rows]);
    }

    public function SaveMaintenanceData($data) {
        // Ensure schema is up to date (e.g. OwnerName column exists)
        $this->CheckTables();

        $email = isset($data['email']) ? trim($data['email']) : '';
        $branch = isset($data['branch']) ? trim($data['branch']) : '';
        $ownerName = isset($data['owner_name']) ? trim($data['owner_name']) : '';

        if (empty($email) || empty($branch)) {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Email and Branch are required."]);
            return;
        }

        // Update the branch and owner name for this email
        // We use full_name column for Owner Name
        $stmt = $this->conn->prepare("UPDATE tbl_master_identities SET branch_name = ?, full_name = ? WHERE email = ?");
        $stmt->bind_param("sss", $branch, $ownerName, $email);
        
        if ($stmt->execute()) {
             // If no rows updated, it might mean the email doesn't exist (shouldn't happen if selected from dropdown)
             // or the branch/name is already the same.
             
             // Check if it exists just in case
             if ($stmt->affected_rows == 0) {
                 // Check existence
                 $check = $this->conn->query("SELECT id FROM tbl_master_identities WHERE email = '$email'");
                 if ($check->num_rows == 0) {
                     // Insert new
                     $stmtIns = $this->conn->prepare("INSERT INTO tbl_master_identities (email, branch_name, full_name, source_table) VALUES (?, ?, ?, 'manual')");
                     $stmtIns->bind_param("sss", $email, $branch, $ownerName);
                     $stmtIns->execute();
                     $stmtIns->close();
                 }
             }
             
             // === IMPORTANT: Also update existing transactions in tbl_staging_transactions and tbl_books ===
             
             // Escape variables for manual queries
             $safeBranch = $this->conn->real_escape_string($branch);
             $safeOwner = $this->conn->real_escape_string($ownerName);
             $safeEmail = $this->conn->real_escape_string($email);
             
             // 1. Update Custom/Raw (tbl_staging_transactions)
             // ACASH and ECPAY both use this table. For ACASH, 'Identity' is usually AccountNo/Email.
             // But we have a dedicated 'Email' column now for ACASH/ECPAY if properly populated.
             // Let's update where Email matches OR Identity matches (just to be safe)
             
             // Update Branch and OwnerName
             $sqlUpdateStaging = "UPDATE tbl_staging_transactions SET Branch = '$safeBranch'";
             if (!empty($ownerName)) {
                 $sqlUpdateStaging .= ", OwnerName = '$safeOwner'";
             }
             $sqlUpdateStaging .= " WHERE Email = '$safeEmail' OR Identity = '$safeEmail'";
             $this->conn->query($sqlUpdateStaging);

             // 2. Update Main (tbl_books)
             // Logic:
             // - ACASH uses AcctNo for Email.
             // - ECPAY uses Payee for Email (initially), and AcctNo is empty.
             // - We want to unify: AcctNo = Email, Payee = OwnerName.
             
             // A. Update rows where AcctNo is the Email (ACASH, or already migrated ECPAY)
             $sqlUpdateBooksA = "UPDATE tbl_books SET Branch = '$safeBranch'";
             if (!empty($ownerName)) {
                 $sqlUpdateBooksA .= ", Payee = '$safeOwner'";
             }
             $sqlUpdateBooksA .= " WHERE AcctNo = '$safeEmail'";
             $this->conn->query($sqlUpdateBooksA);
             
             // B. Update rows where Payee is the Email (Fresh ECPAY)
             // If OwnerName is provided, we migrate Email to AcctNo and set Payee to OwnerName.
             $sqlUpdateBooksB = "UPDATE tbl_books SET Branch = '$safeBranch'";
             if (!empty($ownerName)) {
                 $sqlUpdateBooksB .= ", Payee = '$safeOwner', AcctNo = '$safeEmail'";
             }
             // Ensure we don't accidentally update rows that already have AcctNo set (avoid conflicts)
             // But for ECPAY, AcctNo is empty.
             $sqlUpdateBooksB .= " WHERE Payee = '$safeEmail' AND (AcctNo IS NULL OR AcctNo = '')";
             $this->conn->query($sqlUpdateBooksB);
             
             echo json_encode(["STATUS" => "SUCCESS", "MESSAGE" => "Mapping saved and existing transactions updated."]);
        } else {
             echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Database error: " . $this->conn->error]);
        }
        $stmt->close();
    }

    public function LoadZeroAmountAcash($data) {
        $rows = [];
        $sql = "SELECT ID, CDate, Branch, Fund, AcctNo, AcctTitle, COALESCE(DrOther, 0) AS Amount
                FROM tbl_books
                WHERE SLName = 'ACASH' AND COALESCE(DrOther, 0) = 0
                ORDER BY STR_TO_DATE(CDate, '%Y-%m-%d') DESC, ID DESC
                LIMIT 500";
        $result = $this->conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        echo json_encode(["STATUS" => "SUCCESS", "DATA" => $rows]);
    }

    public function UpdateZeroAmounts($data) {
        $entries = isset($data['entries']) ? $data['entries'] : [];
        if (!is_array($entries) || empty($entries)) {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "No entries provided"]);
            return;
        }
        $stmt = $this->conn->prepare("UPDATE tbl_books SET DrOther = ? WHERE ID = ?");
        foreach ($entries as $entry) {
            $id = isset($entry['id']) ? (int)$entry['id'] : 0;
            $amount = isset($entry['amount']) ? (float)$entry['amount'] : 0.0;
            if ($id <= 0 || $amount <= 0) {
                continue;
            }
            $stmt->bind_param("di", $amount, $id);
            $stmt->execute();
        }
        $stmt->close();
        echo json_encode(["STATUS" => "SUCCESS", "MESSAGE" => "Amounts updated"]);
    }

    public function SimpleDataTest() {
        // Simple test to check what's in ACASH table
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    MIN(CDate) as min_date,
                    MAX(CDate) as max_date,
                    COUNT(CASE WHEN DrOther IS NOT NULL AND DrOther > 0 THEN 1 END) as records_with_amount,
                    SUM(DrOther) as total_amount,
                    AVG(DrOther) as avg_amount,
                    MAX(DrOther) as max_amount
                FROM tbl_books 
                WHERE SLName = 'ACASH'
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            echo json_encode([
                "STATUS" => "SUCCESS", 
                "DATA" => $row,
                "MESSAGE" => "Simple ACASH data test completed"
            ]);
        } else {
            echo json_encode([
                "STATUS" => "ERROR", 
                "MESSAGE" => "Database query failed"
            ]);
        }
    }

    public function TestDataCheck() {
        // Check what ACASH data exists
        $sql = "SELECT 
                    COUNT(*) as total_records,
                    MIN(CDate) as min_date,
                    MAX(CDate) as max_date,
                    COUNT(CASE WHEN DrOther IS NOT NULL AND DrOther > 0 THEN 1 END) as records_with_amount,
                    COUNT(CASE WHEN TransactionYear IS NOT NULL THEN 1 END) as records_with_year,
                    SUM(CASE WHEN DrOther > 0 THEN 1 ELSE 0 END) as positive_amount_count,
                    SUM(DrOther) as total_amount
                FROM tbl_books 
                WHERE SLName = 'ACASH'";
        
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            echo json_encode([
                "STATUS" => "SUCCESS", 
                "DATA" => $row,
                "MESSAGE" => "ACASH data check completed"
            ]);
        } else {
            echo json_encode([
                "STATUS" => "ERROR", 
                "MESSAGE" => "Database query failed"
            ]);
        }
    }

    public function LoadYearlyData($data) {
        $source = isset($data['source']) ? $data['source'] : 'acash';
        $years = isset($data['years']) ? $data['years'] : [];
        $months = isset($data['months']) ? $data['months'] : [];
        
        // Debug: Log input parameters
        error_log("LoadYearlyData called with: source=" . $source . ", years=" . json_encode($years) . ", months=" . json_encode($months));
        
        if (empty($years)) {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "No years selected"]);
            return;
        }
        
        $results = [];
        
        foreach ($years as $year) {
            // Debug: Check what data exists for this year
            $debugSql = "SELECT COUNT(*) as total_records, 
                                MIN(CDate) as min_date, 
                                MAX(CDate) as max_date,
                                COUNT(CASE WHEN DrOther IS NOT NULL AND DrOther > 0 THEN 1 END) as records_with_amount,
                                COUNT(CASE WHEN TransactionYear IS NOT NULL THEN 1 END) as records_with_year
                        FROM tbl_books 
                        WHERE SLName = 'ACASH' AND (CDate LIKE '%$year%' OR TransactionYear = '$year')";
            
            $debugResult = $this->conn->query($debugSql);
            if ($debugResult) {
                $debugRow = $debugResult->fetch_assoc();
                error_log("Debug for year $year: " . json_encode($debugRow));
            }
            
            $whereConditions = [];
            $params = [];
            
            if ($source === 'acash') {
                $whereConditions[] = "SLName = 'ACASH'";
                
                // Try both TransactionYear and CDate LIKE for year matching
                $yearCondition = "(TransactionYear = ? OR CDate LIKE ?)";
                $whereConditions[] = $yearCondition;
                $params[] = $year;
                $params[] = "%$year%";
                
                if (!empty($months)) {
                    $placeholders = str_repeat('?,', count($months) - 1) . '?';
                    $whereConditions[] = "MONTH(STR_TO_DATE(CDate, '%Y-%m-%d')) IN ($placeholders)";
                    $params = array_merge($params, $months);
                }
                
                $whereClause = "WHERE " . implode(' AND ', $whereConditions);
                
                // Debug: Log the final SQL
                error_log("Final SQL for year $year: " . $whereClause);
                error_log("Parameters: " . json_encode($params));
                
                // Get monthly data with more flexible date matching
                $sql = "SELECT 
                            MONTH(CASE 
                                WHEN CDate REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN STR_TO_DATE(CDate, '%Y-%m-%d')
                                WHEN CDate REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$' THEN STR_TO_DATE(CONCAT(CDate, '-01'), '%Y-%m-%d')
                                WHEN CDate LIKE '%-%' THEN STR_TO_DATE(SUBSTRING(CDate, 1, 10), '%Y-%m-%d')
                                ELSE STR_TO_DATE(CDate)
                            END) as month,
                            COALESCE(SUM(DrOther), 0) as amount,
                            COUNT(*) as count,
                            COALESCE(SUM(DrOther) * 0.05, 0) as commission
                        FROM tbl_books 
                        $whereClause
                        GROUP BY MONTH(CASE 
                                WHEN CDate REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$' THEN STR_TO_DATE(CDate, '%Y-%m-%d')
                                WHEN CDate REGEXP '^[0-9]{2}/[0-9]{2}/[0-9]{4}$' THEN STR_TO_DATE(CONCAT(CDate, '-01'), '%Y-%m-%d')
                                WHEN CDate LIKE '%-%' THEN STR_TO_DATE(SUBSTRING(CDate, 1, 10), '%Y-%m-%d')
                                ELSE STR_TO_DATE(CDate)
                            END)
                        HAVING amount > 0
                        ORDER BY month";
                
                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param(str_repeat('i', count($params)), ...$params);
                    $stmt->execute();
                    $monthlyData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    
                    // Debug: Log monthly data
                    error_log("Monthly data for year $year: " . json_encode($monthlyData));
                    error_log("SQL executed: " . $sql);
                    error_log("Params bound: " . json_encode($params));
                } else {
                    error_log("Failed to prepare monthly data statement for year $year");
                }
                
                // Get totals
                $totalSql = "SELECT 
                                COALESCE(SUM(DrOther) + SUM(CrOther), 0) as total_amount,
                                COUNT(*) as total_count
                            FROM tbl_books 
                        $whereClause";
                
                $totalStmt = $this->conn->prepare($totalSql);
                $totalAmount = 0;
                $totalCount = 0;
                if ($totalStmt) {
                    $totalStmt->bind_param(str_repeat('i', count($params)), ...$params);
                    $totalStmt->execute();
                    $totalResult = $totalStmt->get_result()->fetch_assoc();
                    $totalAmount = $totalResult['total_amount'];
                    $totalCount = $totalResult['total_count'];
                    $totalStmt->close();
                    
                    // Debug: Log totals
                    error_log("Totals for year $year: amount=$totalAmount, count=$totalCount");
                }
                
            } else { // ECPAY
                // Debug: Check what ECPAY data exists for this year
                $debugSql = "SELECT COUNT(*) as total_records, 
                                    MIN(CDate) as min_date, 
                                    MAX(CDate) as max_date,
                                    COUNT(CASE WHEN DrOther IS NOT NULL AND DrOther > 0 THEN 1 END) as dr_records,
                                    COUNT(CASE WHEN CrOther IS NOT NULL AND CrOther > 0 THEN 1 END) as cr_records,
                                    COUNT(CASE WHEN TransactionYear IS NOT NULL THEN 1 END) as year_records,
                                    COUNT(CASE WHEN CDate LIKE '%$year%' THEN 1 END) as date_records,
                                    SUM(DrOther) as dr_sum,
                                    SUM(CrOther) as cr_sum
                            FROM tbl_books 
                            WHERE (Payee LIKE '%EC%PAY%' OR Explanation LIKE '%EC%PAY%' OR Explanation LIKE '%ECPAY%')
                            AND (CDate LIKE '%$year%' OR TransactionYear = '$year')";
                
                $debugResult = $this->conn->query($debugSql);
                if ($debugResult) {
                    $debugRow = $debugResult->fetch_assoc();
                    error_log("ECPAY Debug for year $year: " . json_encode($debugRow));
                }
                
                $whereConditions[] = "(Payee LIKE '%EC%PAY%' OR Explanation LIKE '%EC%PAY%' OR Explanation LIKE '%ECPAY%')";
                
                // Try more flexible year matching - ECPAY might not have TransactionYear populated
                $yearCondition = "(CDate LIKE ? OR YEAR(STR_TO_DATE(CDate, '%Y-%m-%d')) = ? OR TransactionYear = ?)";
                $whereConditions[] = $yearCondition;
                $params[] = "%$year%";
                $params[] = $year;
                $params[] = $year;
                
                if (!empty($months)) {
                    $placeholders = str_repeat('?,', count($months) - 1) . '?';
                    $whereConditions[] = "MONTH(STR_TO_DATE(CDate, '%Y-%m-%d')) IN ($placeholders)";
                    $params = array_merge($params, $months);
                }
                
                $whereClause = "WHERE " . implode(' AND ', $whereConditions);
                
                // Get monthly data
                $sql = "SELECT 
                            MONTH(STR_TO_DATE(CDate, '%Y-%m-%d')) as month,
                            COALESCE(SUM(DrOther) + SUM(CrOther), 0) as amount,
                            COUNT(*) as count,
                            COALESCE((SUM(DrOther) + SUM(CrOther)) * 0.05, 0) as commission
                        FROM tbl_books 
                        $whereClause
                        GROUP BY MONTH(STR_TO_DATE(CDate, '%Y-%m-%d'))
                        ORDER BY month";
                
                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param(str_repeat('i', count($params)), ...$params);
                    $stmt->execute();
                    $monthlyData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                }
                
                // Get totals
                $totalSql = "SELECT 
                                COALESCE(SUM(DrOther) + SUM(CrOther), 0) as total_amount,
                                COUNT(*) as total_count
                            FROM tbl_books 
                            $whereClause";
                
                $totalStmt = $this->conn->prepare($totalSql);
                $totalAmount = 0;
                $totalCount = 0;
                if ($totalStmt) {
                    $totalStmt->bind_param(str_repeat('i', count($params)), ...$params);
                    $totalStmt->execute();
                    $totalResult = $totalStmt->get_result()->fetch_assoc();
                    $totalAmount = $totalResult['total_amount'];
                    $totalCount = $totalResult['total_count'];
                    $totalStmt->close();
                }
            }
            
            // Calculate commission (5% of total amount)
            $commission = $totalAmount * 0.05;
            
            $results[] = [
                'year' => $year,
                'commission' => number_format($commission, 2, '.', ''),
                'amount' => number_format($totalAmount, 2, '.', ''),
                'count' => $totalCount,
                'monthlyData' => $monthlyData ?: []
            ];
        }
        
        // Debug: Log final results
        error_log("Final results: " . json_encode($results));
        
        echo json_encode(["STATUS" => "SUCCESS", "DATA" => $results]);
    }
    public function LoadGraphData($data) {
        $rows = [];
        
        // 1. ACash Counts by Branch (Main Data from tbl_books)
        // Aggregating from tbl_books ensures we show ALL data (Historical + Uploaded)
        $acashData = [];
        $sqlAcash = "SELECT Branch, COUNT(*) as Count FROM tbl_books WHERE SLName = 'ACASH' GROUP BY Branch";
        $resultAcash = $this->conn->query($sqlAcash);
        if ($resultAcash) {
            while($row = $resultAcash->fetch_assoc()) {
                $acashData[] = $row;
            }
        }

        // 2. ECPay Amounts by Branch (Main Data from tbl_books)
        $ecpayData = [];
        $like = "%EC%PAY%";
        $like2 = "%ECPAY%";
        $sqlEcpay = "SELECT Branch, SUM(DrOther) as TotalAmount FROM tbl_books 
                     WHERE (Payee LIKE '$like' OR Explanation LIKE '$like' OR Explanation LIKE '$like2') 
                     GROUP BY Branch";
        $resultEcpay = $this->conn->query($sqlEcpay);
        if ($resultEcpay) {
            while($row = $resultEcpay->fetch_assoc()) {
                $ecpayData[] = $row;
            }
        }

        echo json_encode([
            "STATUS" => "LOADED",
            "ACASH_GRAPH" => $acashData,
            "ECPAY_GRAPH" => $ecpayData
        ]);
    }

    public function UploadCustom($files){
        // Capture category from POST request
        $category = isset($_POST['category']) ? $_POST['category'] : 'Custom';

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate(__FILE__, true);
        }
        ob_start();
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
                while (ob_get_level()) ob_end_clean();
                http_response_code(200);
                echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Server Fatal Error: " . $error['message'] . " in " . $error['file'] . " line " . $error['line']]);
            }
        });

        if (!isset($files['file']) || $files['file']['error'] != 0) {
            $msg = "No file uploaded or error occurred.";
            if (isset($files['file']['error'])) {
                $msg .= " Error code: " . $files['file']['error'];
            }
            while (ob_get_level()) ob_end_clean();
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => $msg]);
            return;
        }

        $file = $files['file']['tmp_name'];
        
        // Ensure unified staging table and master data schema
        $this->CheckTables();

        try {
            $inputFileType = IOFactory::identify($file);
            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            
            $insertedCount = 0;
            $debugInfo = [];
            $lastFailureReason = ""; // Track why the last row failed
            $failures = []; // Track failure reasons for debugging
            
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $rows = $worksheet->toArray();
                $currentSection = ''; 
                $colMap = [];
                $firstFailureReason = "";
                
                $findCol = function($map, $possibleNames) {
                    foreach ($possibleNames as $name) {
                        if (isset($map[$name])) return $map[$name];
                        foreach ($map as $key => $index) {
                            if (strcasecmp(trim($key), trim($name)) === 0) return $index;
                        }
                    }
                    return false;
                };

                $uploadCategory = $category; // Ensure variable is in scope
                foreach ($rows as $rowIndex => $data) {
                    if (empty(array_filter($data))) continue;
                    
                    $data = array_map(function($v){ 
                        $v = (string)$v;
                        $v = str_replace(["\xc2\xa0", "\xa0"], ' ', $v);
                        return trim($v); 
                    }, $data);
                    $tempMap = array_flip($data);
                    
                    if (count($debugInfo) < 20) {
                        $info = "Row $rowIndex: " . implode(", ", array_slice($data, 0, 5));
                        $info .= " | Section: '$currentSection'";
                        $debugInfo[] = $info;
                    }

                    // Header Detection
                    //
                    // Priority 1: If user explicitly selected an ECPAY category (LOADS/PAYBILLS/SERVICES),
                    // we only want to treat the FIRST matching row as the header so that $colMap
                    // is built from the header labels and then reused for all data rows.
                    if (in_array($uploadCategory, ['LOADS', 'PAYBILLS', 'SERVICES']) && $currentSection === '') {
                        // Attempt to treat this row as the header for ECPAY
                        $payeeIdx = $findCol($tempMap, ['Payee', 'Payee Name', 'Name', 'Payer/Payee', 'Biller', 'Service', 'Email']);
                        $dateIdx  = $findCol($tempMap, ['Date', 'CDate', 'Posting Date', 'Trans. Date', 'Transaction Date']);

                        // If we found a Date column, assume this is the header row for ECPAY
                        if ($dateIdx !== false) {
                            $currentSection = 'ECPAY';
                            $colMap = $tempMap;
                            continue; // move to next row; data rows will use this $colMap
                        }
                        // If we didn't find a usable Date column, fall through to generic detection below.
                    }
                    
                    $acctNoIdx = $findCol($tempMap, ['AcctNo', 'Account No', 'Account Number', 'Acct #', 'Acct. No.', 'Mobile No', 'Accountno', 'Email']);
                    $dateIdx = $findCol($tempMap, ['Date', 'CDate', 'Posting Date', 'Trans. Date', 'Transaction Date']);
                    $payeeIdx = $findCol($tempMap, ['Payee', 'Payee Name', 'Name', 'Payer/Payee', 'Biller', 'Service', 'Email']);
                    
                    if ($payeeIdx === false) {
                        // Auto-detect ECPAY columns regardless of category
                        $telcoIdx = $findCol($tempMap, ['Telco']);
                        if ($telcoIdx !== false) {
                             $payeeIdx = $telcoIdx;
                             if ($uploadCategory == 'Custom' || $uploadCategory == 'Raw' || empty($uploadCategory)) $uploadCategory = 'LOADS';
                        }
                    }
                    if ($payeeIdx === false) {
                        $billerIdx = $findCol($tempMap, ['Biller']);
                        if ($billerIdx !== false) {
                             $payeeIdx = $billerIdx;
                             if ($uploadCategory == 'Custom' || $uploadCategory == 'Raw' || empty($uploadCategory)) $uploadCategory = 'PAYBILLS';
                        }
                    }
                    if ($payeeIdx === false) {
                        $billerIdx = $findCol($tempMap, ['Biller']);
                        if ($billerIdx !== false) {
                             $payeeIdx = $billerIdx;
                             if ($uploadCategory == 'Raw') $uploadCategory = 'PAYBILLS';
                        }
                    }
                    
                    // If Category is specific (Loads/Paybills/Services), bias towards ECPAY if ambiguous, 
                    // or just rely on standard detection but we will append tag later.
                    // Actually, if user selected "Loads", "Paybills", "Services", it IS ECPAY.
                    // So if we find Date and (Payee OR AcctNo), and we are in specific category, we can default to ECPAY?
                    // But wait, Acash also has Date + AcctNo.
                    // Let's stick to standard detection for now, but if we fail to detect, maybe fallback?
                    // Standard detection is quite specific.
                    
                    if ($dateIdx !== false && isset($tempMap['Email'])) {
                         $currentSection = 'ACASH';
                         $colMap = $tempMap;
                         continue;
                    }
                    if ($acctNoIdx !== false && $dateIdx !== false) {
                        $currentSection = 'ACASH';
                        $colMap = $tempMap;
                        continue;
                    }
                    if ($payeeIdx !== false && $dateIdx !== false) {
                        $currentSection = 'ECPAY';
                        $colMap = $tempMap;
                        continue;
                    }

                    // Fallback for Specific Categories if headers are slightly off but user insisted?
                    // For now, let's trust the file headers.
                    
                    $rowString = implode(" ", $data);
                    if (stripos($rowString, "ACash Information") !== false || stripos($rowString, "ECpay Transaction") !== false) {
                        $currentSection = ''; 
                        continue;
                    }

                    if ($currentSection == 'ACASH') {
                        $dateIdx = $findCol($colMap, ['Date', 'CDate']);
                        $branchIdx = $findCol($colMap, ['Branch', 'Branch Name']);
                        $fundIdx = $findCol($colMap, ['Fund']);
                        $acctNoIdx = $findCol($colMap, ['AcctNo', 'Account No', 'Email', 'Accountno']);
                        $titleIdx = $findCol($colMap, ['AcctTitle', 'Account Title', 'Title']);

                        $date = ($dateIdx !== false && isset($data[$dateIdx])) ? $data[$dateIdx] : '';
                        if (is_numeric($date)) {
                             try { 
                                 $dtObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                                 $date = $dtObj->format('Y-m-d H:i:s'); 
                             } catch (\Throwable $e) {
                                 // Keep original date
                             }
                        } else {
                            $ts = strtotime($date);
                            if ($ts) $date = date('Y-m-d H:i:s', $ts);
                        }

                        $branch = ($branchIdx !== false && isset($data[$branchIdx])) ? $data[$branchIdx] : '';
                        $branch = $this->normalizeBranch($branch);
                        
                        // Resolve Branch
                        if (empty($branch)) {
                             $potentialEmail = ($acctNoIdx !== false && isset($data[$acctNoIdx])) ? trim($data[$acctNoIdx]) : '';
                             $emailIdx = $findCol($colMap, ['Email']);
                             if ($emailIdx !== false && isset($data[$emailIdx])) $potentialEmail = trim($data[$emailIdx]);

                             if (!empty($potentialEmail) && filter_var($potentialEmail, FILTER_VALIDATE_EMAIL)) {
                                 $stmtMap = $this->conn->prepare("SELECT branch_name FROM tbl_master_identities WHERE email = ? AND branch_name IS NOT NULL LIMIT 1");
                                 if ($stmtMap) {
                                     $stmtMap->bind_param("s", $potentialEmail);
                                     $stmtMap->execute();
                                     $resMap = $stmtMap->get_result();
                                     if ($rowMap = $resMap->fetch_assoc()) $branch = $rowMap['branch_name'];
                                     $stmtMap->close();
                                 }
                             }
                        }

                        $fund = ($fundIdx !== false && isset($data[$fundIdx])) ? $data[$fundIdx] : '';
                        $acctNo = ($acctNoIdx !== false && isset($data[$acctNoIdx])) ? $data[$acctNoIdx] : '';
                        $acctTitle = ($titleIdx !== false && isset($data[$titleIdx])) ? $data[$titleIdx] : '';
                        
                        $variantIdx = $findCol($colMap, ['Variant']);
                        if ($variantIdx !== false && isset($data[$variantIdx]) && !empty($data[$variantIdx])) {
                            $acctTitle .= " " . $data[$variantIdx];
                        }

                        if (!empty($acctNo) || !empty($acctTitle)) {
                            // Validate Date before inserting
                            if (empty($date)) {
                                $lastFailureReason = "Row $rowIndex: Date empty.";
                                continue;
                            }

                            // Insert into Staging
                            $stmt = $this->conn->prepare("INSERT INTO tbl_staging_transactions (CDate, Branch, Fund, Identity, Description, Source, UploadType, Email) VALUES (?, ?, ?, ?, ?, 'ACASH', 'CUSTOM', ?)");
                            if ($stmt) {
                                $emailToSave = $potentialEmail ?? '';
                                $stmt->bind_param("ssssss", $date, $branch, $fund, $acctNo, $acctTitle, $emailToSave);
                                $stmt->execute();
                                $stmt->close();
                            }
                            
                            // Insert into tbl_books (Legacy/Main)
                            $stmt = $this->conn->prepare("INSERT INTO tbl_books (CDate, Branch, Fund, AcctNo, AcctTitle, SLName) VALUES (?, ?, ?, ?, ?, 'ACASH')");
                            if ($stmt) {
                                $stmt->bind_param("sssss", $date, $branch, $fund, $acctNo, $acctTitle);
                                $stmt->execute();
                                $stmt->close();
                            }

                            $this->updateMasterData($acctTitle, $branch, $potentialEmail ?? null);
                            $insertedCount++;
                        }
                    } elseif ($currentSection == 'ECPAY') {
                        $dateIdx = $findCol($colMap, ['Date', 'CDate']);
                        $branchIdx = $findCol($colMap, ['Branch', 'Branch Name']);
                        $payeeIdx   = $findCol($colMap, ['Payee', 'Payee Name', 'Name', 'Email']);
                        $explIdx    = $findCol($colMap, ['Explanation', 'Particulars', 'Account No']);
                        // Separate indices for Amount Transacted (DrOther) and Amount Deducted (CrOther)
                        $amtDrIdx   = $findCol($colMap, ['Amount Transacted', 'Amount', 'Debit', 'DrOther']);
                        $amtCrIdx   = $findCol($colMap, ['Amount Deducted', 'Credit', 'CrOther']);

                        $date = ($dateIdx !== false && isset($data[$dateIdx])) ? $data[$dateIdx] : '';
                        if (is_numeric($date)) {
                             try { 
                                 $dtObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                                 $date = $dtObj->format('Y-m-d'); 
                             } catch (Throwable $e) {
                                 // Keep original date if conversion fails
                             }
                        } else {
                            $ts = strtotime($date);
                            if ($ts) $date = date('Y-m-d', $ts);
                        }
                        
                        $branch = ($branchIdx !== false && isset($data[$branchIdx])) ? $data[$branchIdx] : '';
                        $branch = $this->normalizeBranch($branch);

                        // Resolve Branch
                        if (empty($branch)) {
                             $potentialEmail = ($payeeIdx !== false && isset($data[$payeeIdx])) ? trim($data[$payeeIdx]) : '';
                             $emailIdx = $findCol($colMap, ['Email']);
                             if ($emailIdx !== false && isset($data[$emailIdx])) $potentialEmail = trim($data[$emailIdx]);

                             if (!empty($potentialEmail) && filter_var($potentialEmail, FILTER_VALIDATE_EMAIL)) {
                                 $stmtMap = $this->conn->prepare("SELECT branch_name FROM tbl_master_identities WHERE email = ? AND branch_name IS NOT NULL LIMIT 1");
                                 if ($stmtMap) {
                                     $stmtMap->bind_param("s", $potentialEmail);
                                     $stmtMap->execute();
                                     $resMap = $stmtMap->get_result();
                                     if ($rowMap = $resMap->fetch_assoc()) $branch = $rowMap['branch_name'];
                                     $stmtMap->close();
                                 }
                             }
                        }

                        // Extended Columns Extraction
                        $userIdx    = $findCol($colMap, ['User', 'Username', 'Processed By']);
                        $telcoIdx   = $findCol($colMap, ['Telco']);
                        $variantIdx = $findCol($colMap, ['Variant']);
                        // Support both spaced and unspaced header variants (e.g. "Mobile No" and "MobileNo")
                        $mobileNoIdx = $findCol($colMap, ['Mobile No', 'MobileNo', 'Mobile Number']);
                        $traceNoIdx  = $findCol($colMap, ['Trace No', 'TraceNo', 'Trace Number']);
                        $billerIdx = $findCol($colMap, ['Biller']);
                        $acctNoIdx = $findCol($colMap, ['Account No', 'AccountNo', 'Acct No', 'Account Number']);
                        $refIdx = $findCol($colMap, ['Reference No', 'Reference', 'ServiceRef']);
                        $serviceIdx = $findCol($colMap, ['Service']);
                        
                        $commIdx = $findCol($colMap, ['Commission']);
                        $convFeeIdx = $findCol($colMap, ['Convenience Fee']);
                        $servChargeIdx = $findCol($colMap, ['Service Charge']);
                        $totalIdx = $findCol($colMap, ['Total']);
                        $statusIdx = $findCol($colMap, ['Status']);
                        
                        $monthIdx = $findCol($colMap, ['month', 'Month']);
                        $yearIdx  = $findCol($colMap, ['year', 'Year']);

                        // Fallback: for ECPAY LOADS, many providers use a fixed column order.
                        // If we couldn't detect some columns by header name, map them by index:
                        // 0: Date, 1: Branch, 2: User, 3: Telco, 4: Variant, 5: Mobile No,
                        // 6: Trace No, 7: Amount Transacted, 8: Amount Deducted,
                        // 9: Commission, 10: Convenience Fee, 11: Total,
                        // 12: Status, 13: Month, 14: Year.
                        if ($uploadCategory === 'LOADS') {
                            if ($userIdx === false && isset($data[2]))       $userIdx = 2;
                            if ($telcoIdx === false && isset($data[3]))      $telcoIdx = 3;
                            if ($variantIdx === false && isset($data[4]))    $variantIdx = 4;
                            if ($mobileNoIdx === false && isset($data[5]))   $mobileNoIdx = 5;
                            if ($traceNoIdx === false && isset($data[6]))    $traceNoIdx = 6;
                            if ($amtDrIdx === false && isset($data[7]))      $amtDrIdx = 7;
                            if ($amtCrIdx === false && isset($data[8]))      $amtCrIdx = 8;
                            if ($commIdx === false && isset($data[9]))       $commIdx = 9;
                            if ($convFeeIdx === false && isset($data[10]))   $convFeeIdx = 10;
                            if ($totalIdx === false && isset($data[11]))     $totalIdx = 11;
                            if ($statusIdx === false && isset($data[12]))    $statusIdx = 12;
                            if ($monthIdx === false && isset($data[13]))     $monthIdx = 13;
                            if ($yearIdx === false && isset($data[14]))      $yearIdx = 14;
                        }

                        $userVal = ($userIdx !== false && isset($data[$userIdx])) ? $data[$userIdx] : '';
                        $telcoVal = ($telcoIdx !== false && isset($data[$telcoIdx])) ? $data[$telcoIdx] : '';
                        $variantVal = ($variantIdx !== false && isset($data[$variantIdx])) ? $data[$variantIdx] : '';
                        $mobileNoVal = ($mobileNoIdx !== false && isset($data[$mobileNoIdx])) ? $data[$mobileNoIdx] : '';
                        $traceNoVal = ($traceNoIdx !== false && isset($data[$traceNoIdx])) ? $data[$traceNoIdx] : '';
                        $billerVal = ($billerIdx !== false && isset($data[$billerIdx])) ? $data[$billerIdx] : '';
                        $accountNoVal = ($acctNoIdx !== false && isset($data[$acctNoIdx])) ? $data[$acctNoIdx] : '';
                        $refVal = ($refIdx !== false && isset($data[$refIdx])) ? $data[$refIdx] : '';
                        $serviceVal = ($serviceIdx !== false && isset($data[$serviceIdx])) ? $data[$serviceIdx] : '';
                        
                        $commVal = ($commIdx !== false && isset($data[$commIdx])) ? (float)str_replace(',', '', $data[$commIdx]) : 0;
                        $convFeeVal = ($convFeeIdx !== false && isset($data[$convFeeIdx])) ? (float)str_replace(',', '', $data[$convFeeIdx]) : 0;
                        $servChargeVal = ($servChargeIdx !== false && isset($data[$servChargeIdx])) ? (float)str_replace(',', '', $data[$servChargeIdx]) : 0;
                        $totalVal = ($totalIdx !== false && isset($data[$totalIdx])) ? (float)str_replace(',', '', $data[$totalIdx]) : 0;
                        $statusVal = ($statusIdx !== false && isset($data[$statusIdx])) ? $data[$statusIdx] : '';
                        
                        $monthVal = ($monthIdx !== false && isset($data[$monthIdx])) ? $data[$monthIdx] : '';
                        $yearVal  = ($yearIdx  !== false && isset($data[$yearIdx]))  ? $data[$yearIdx]  : '';

                        // If Month/Year not provided in the file, derive them from Date
                        if (empty($monthVal) && !empty($date)) {
                            $monthVal = date('F', strtotime($date));
                        }
                        if (empty($yearVal) && !empty($date)) {
                            $yearVal = date('Y', strtotime($date));
                        }

                        $payee = ($payeeIdx !== false && isset($data[$payeeIdx])) ? $data[$payeeIdx] : '';
                        
                        // Ensure Category is correct if we have Telco/Biller columns (in case header detection missed it or it wasn't persisted)
                        if ($uploadCategory == 'Custom' || $uploadCategory == 'Raw' || empty($uploadCategory)) {
                            if ($findCol($colMap, ['Telco']) !== false) $uploadCategory = 'LOADS';
                            elseif ($findCol($colMap, ['Biller']) !== false) $uploadCategory = 'PAYBILLS';
                        }
                        
                        // If Payee came from Telco/Biller columns (Auto-detected), prefix it
                        if ($uploadCategory == 'LOADS') {
                            $telcoIdx = $findCol($colMap, ['Telco']);
                            if ($payeeIdx === $telcoIdx && !empty($payee)) $payee = "Telco: " . $payee;
                        } elseif ($uploadCategory == 'PAYBILLS' || $uploadCategory == 'SERVICES') {
                            $billerIdx = $findCol($colMap, ['Biller']);
                            if ($payeeIdx === $billerIdx && !empty($payee)) $payee = "Biller: " . $payee;
                        }

                        $explanation = ($explIdx !== false && isset($data[$explIdx])) ? $data[$explIdx] : '';
                        $amountDr   = ($amtDrIdx !== false && isset($data[$amtDrIdx])) ? $data[$amtDrIdx] : 0;
                        $amountCr   = ($amtCrIdx !== false && isset($data[$amtCrIdx])) ? $data[$amtCrIdx] : 0;
                        
                        // Fallback: If payee is empty but we have MobileNo/AccountNo for specific categories, use them
                        if (empty($payee)) {
                            // If still Custom/Raw, try to detect Telco/Biller directly
                            if (($uploadCategory == 'Custom' || $uploadCategory == 'Raw' || empty($uploadCategory)) && empty($payee)) {
                                 $telcoIdx = $findCol($colMap, ['Telco']);
                                 if ($telcoIdx !== false && isset($data[$telcoIdx])) {
                                     $payee = "Telco: " . $data[$telcoIdx];
                                     $uploadCategory = 'LOADS';
                                 }
                                 
                                 if (empty($payee)) {
                                     $billerIdx = $findCol($colMap, ['Biller']);
                                     if ($billerIdx !== false && isset($data[$billerIdx])) {
                                         $payee = "Biller: " . $data[$billerIdx];
                                         $uploadCategory = 'PAYBILLS';
                                     }
                                 }
                            }
                            
                            if ($uploadCategory == 'LOADS') {
                                $mobileNoIdx = $findCol($colMap, ['Mobile No', 'Mobile Number']);
                                if ($mobileNoIdx !== false && isset($data[$mobileNoIdx])) $payee = "Mobile: " . $data[$mobileNoIdx];
                                
                                if (empty($payee)) {
                                    $telcoIdx = $findCol($colMap, ['Telco']);
                                    if ($telcoIdx !== false && isset($data[$telcoIdx])) $payee = "Telco: " . $data[$telcoIdx];
                                }
                            } else if ($uploadCategory == 'PAYBILLS' || $uploadCategory == 'SERVICES') {
                                $acctNoIdx = $findCol($colMap, ['Account No', 'AccountNo', 'Account Number']);
                                if ($acctNoIdx !== false && isset($data[$acctNoIdx])) $payee = "Acct: " . $data[$acctNoIdx];
                                
                                if (empty($payee)) {
                                    $billerIdx = $findCol($colMap, ['Biller']);
                                    if ($billerIdx !== false && isset($data[$billerIdx])) $payee = "Biller: " . $data[$billerIdx];
                                }
                            }
                        }

                        // Fallback: If explanation is empty but we have Service/Variant, use them
                        if (empty($explanation)) {
                            if ($uploadCategory == 'LOADS') {
                                $variantIdx = $findCol($colMap, ['Variant']);
                                if ($variantIdx !== false && isset($data[$variantIdx])) $explanation = $data[$variantIdx];
                            } else if ($uploadCategory == 'PAYBILLS') {
                                $billerIdx = $findCol($colMap, ['Biller']);
                                if ($billerIdx !== false && isset($data[$billerIdx])) $explanation = $data[$billerIdx];
                            } else if ($uploadCategory == 'SERVICES') {
                                $serviceIdx = $findCol($colMap, ['Service']);
                                if ($serviceIdx !== false && isset($data[$serviceIdx])) $explanation = $data[$serviceIdx];
                            }
                        }

                        if ($explIdx !== false) {
                             if (!empty($refVal)) {
                                 $explanation = "Acct: " . $explanation . " Ref: " . $refVal;
                             }
                        }
                        
                        // Enforce Category Keyword for Filtering (Ensure data shows up in the selected tab)
                        if ($uploadCategory == 'LOADS' && stripos($explanation, '(Load)') === false && stripos($payee, '(Load)') === false) {
                            $explanation .= " (Load)";
                        } else if ($uploadCategory == 'PAYBILLS' && stripos($explanation, '(Bill)') === false && stripos($payee, '(Bill)') === false) {
                            $explanation .= " (Bill)";
                        } else if ($uploadCategory == 'SERVICES' && stripos($explanation, '(Service)') === false && stripos($payee, '(Service)') === false) {
                            $explanation .= " (Service)";
                        }

                        $amountDr = str_replace(',', '', $amountDr);
                        $amountCr = str_replace(',', '', $amountCr);
                        $dr = is_numeric($amountDr) ? $amountDr : 0;
                        $cr = is_numeric($amountCr) ? $amountCr : 0;

                        if (!empty($payee) || !empty($explanation)) {
                            // Validate Date before inserting
                            if (empty($date)) {
                                $reason = "Row $rowIndex: Date empty.";
                                $lastFailureReason = $reason;
                                if (empty($firstFailureReason)) $firstFailureReason = $reason;
                                if (count($failures) < 10) $failures[] = $reason;
                                continue;
                            }

                            // Determine UploadType
                            $uploadTypeToSave = (in_array($uploadCategory, ['LOADS', 'PAYBILLS', 'SERVICES'])) ? $uploadCategory : 'CUSTOM';

                            // Insert into Staging
                            // 8 placeholders => 8 type definitions (s,s,s,s,d,d,s,s)
                            $stmtStaging = $this->conn->prepare("INSERT INTO tbl_staging_transactions (CDate, Branch, Identity, Description, DrOther, CrOther, Source, UploadType, Email) VALUES (?, ?, ?, ?, ?, ?, 'ECPAY', ?, ?)");
                            if ($stmtStaging) {
                                $emailToSave = $potentialEmail ?? '';
                                $stmtStaging->bind_param("ssssddss", $date, $branch, $payee, $explanation, $dr, $cr, $uploadTypeToSave, $emailToSave);
                                $stmtStaging->execute();
                                $stmtStaging->close();
                            }

                            // Insert into tbl_books (Legacy/Main) + New Columns
                            $sql = "INSERT INTO tbl_books (CDate, Branch, Payee, Explanation, DrOther, CrOther, 
                                    User, Telco, Variant, MobileNo, TraceNo, Biller, AccountNo, ReferenceNo, 
                                    Service, ServiceRef, Commission, ConvenienceFee, ServiceCharge, Status, 
                                    TransactionTotal, TransactionMonth, TransactionYear) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            
                            $stmt = $this->conn->prepare($sql);
                            if ($stmt) {
                                $stmt->bind_param("ssssddsssssssssssdddsss", 
                                    $date, $branch, $payee, $explanation, $dr, $cr,
                                    $userVal, $telcoVal, $variantVal, $mobileNoVal, $traceNoVal, $billerVal, $accountNoVal, $refVal,
                                    $serviceVal, $refVal, $commVal, $convFeeVal, $servChargeVal, $statusVal,
                                    $totalVal, $monthVal, $yearVal
                                );
                                $stmt->execute();
                                $stmt->close();
                            }
                            
                            $this->updateMasterData($payee, $branch, $potentialEmail ?? null);
                            $insertedCount++;
                        } else {
                            $debugDetail = "Cat: $uploadCategory. PayeeIdx: " . ($payeeIdx !== false ? $payeeIdx : 'false') . ". TelcoIdx: " . ($telcoIdx !== false ? $telcoIdx : 'false');
                            if ($telcoIdx !== false && isset($data[$telcoIdx])) $debugDetail .= ". TelcoVal: " . $data[$telcoIdx];
                            
                            $reason = "Row $rowIndex: Payee & Explanation empty. $debugDetail";
                            $lastFailureReason = $reason;
                            if (empty($firstFailureReason)) $firstFailureReason = $reason;
                            if (count($failures) < 10) $failures[] = $reason;
                        }
                    }
                }
            }

            if ($insertedCount > 0) {
                 echo json_encode(["STATUS" => "SUCCESS", "MESSAGE" => "Uploaded $insertedCount records successfully."]);
            } else {
                 $msg = "[DEBUG_V3] No records uploaded. Could not detect 'Date' AND 'AcctNo'/'Payee' headers.";
                 if (!empty($failures)) $msg .= " Failures: " . implode(" | ", $failures);
                 else {
                     if (!empty($firstFailureReason)) $msg .= " First Skipped: " . $firstFailureReason;
                     if (!empty($lastFailureReason)) $msg .= " Last Skipped: " . $lastFailureReason;
                 }
                 echo json_encode(["STATUS" => "ERROR", "MESSAGE" => $msg . " Debug Info: " . implode(" | ", $debugInfo)]);
            }

        } catch (Exception $e) {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Error processing file: " . $e->getMessage()]);
        }
    }

    public function UploadRaw($files){
        // Capture category from POST request
        $category = isset($_POST['category']) ? $_POST['category'] : 'Raw';
        if(isset($_POST['action']) AND $_POST['action'] == 'TestDataCheck'){
    $this->TestDataCheck();
}

if(isset($_POST['action']) AND $_POST['action'] == 'SimpleDataTest'){
    $this->SimpleDataTest();
}
        ini_set('memory_limit', '512M');
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
                while (ob_get_level()) ob_end_clean();
                http_response_code(200); 
                echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Server Fatal Error: " . $error['message'] . " in " . $error['file'] . " line " . $error['line']]);
            }
        });

        if (!isset($files['file']) || $files['file']['error'] != 0) {
            $msg = "No file uploaded or error occurred.";
            if (isset($files['file']['error'])) {
                $msg .= " Error code: " . $files['file']['error'];
            }
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => $msg]);
            return;
        }

        $file = $files['file']['tmp_name'];
        
        // Ensure unified staging table and master data schema
        $this->CheckTables();
        $this->conn->query("DELETE FROM tbl_staging_transactions WHERE UploadType = 'RAW'");

        try {
            $inputFileType = IOFactory::identify($file);
            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            
            $insertedCount = 0;
            $debugInfo = [];
            $lastFailureReason = "";
            $firstFailureReason = "";
            $failures = [];
            
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $rows = $worksheet->toArray();
                $currentSection = ''; 
                $colMap = [];
                
                $findCol = function($map, $possibleNames) {
                    foreach ($possibleNames as $name) {
                        if (isset($map[$name])) return $map[$name];
                        foreach ($map as $key => $index) {
                            if (strcasecmp(trim($key), trim($name)) === 0) return $index;
                        }
                    }
                    return false;
                };

                $uploadCategory = $category; // Ensure variable is in scope
                foreach ($rows as $rowIndex => $data) {
                    if (empty(array_filter($data))) continue;
                    
                    $data = array_map(function($v){ 
                        $v = (string)$v;
                        $v = str_replace(["\xc2\xa0", "\xa0"], ' ', $v);
                        return trim($v); 
                    }, $data);
                    $tempMap = array_flip($data);
                    
                    if (count($debugInfo) < 20) {
                        $info = "Row $rowIndex: " . implode(", ", array_slice($data, 0, 5));
                        $info .= " | Section: '$currentSection'";
                        $debugInfo[] = $info;
                    }
                    
                    $acctNoIdx = $findCol($tempMap, ['AcctNo', 'Account No', 'Account Number', 'Acct #', 'Acct. No.', 'Mobile No', 'Accountno', 'Email']);
                    $dateIdx = $findCol($tempMap, ['Date', 'CDate', 'Posting Date', 'Trans. Date', 'Transaction Date']);
                    $payeeIdx = $findCol($tempMap, ['Payee', 'Payee Name', 'Name', 'Payer/Payee', 'Biller', 'Service']);

                    if ($payeeIdx === false) {
                        $payeeIdx = $findCol($tempMap, ['AcctTitle', 'Account Title', 'Title']);
                        if ($payeeIdx === false) $payeeIdx = $findCol($tempMap, ['AcctNo', 'Account No', 'Accountno']);
                        
                        // Auto-detect ECPAY columns regardless of category
                        if ($payeeIdx === false) {
                             $telcoIdx = $findCol($tempMap, ['Telco']);
                             if ($telcoIdx !== false) {
                                 $payeeIdx = $telcoIdx;
                                 if ($uploadCategory == 'Raw') $uploadCategory = 'LOADS';
                             }
                        }
                        if ($payeeIdx === false) {
                             $billerIdx = $findCol($tempMap, ['Biller']);
                             if ($billerIdx !== false) {
                                 $payeeIdx = $billerIdx;
                                 if ($uploadCategory == 'Raw') $uploadCategory = 'PAYBILLS';
                             }
                        }
                        
                        if ($payeeIdx === false && $uploadCategory == 'LOADS') {
                            $payeeIdx = $findCol($tempMap, ['Mobile No', 'Mobile Number']);
                            if ($payeeIdx === false) $payeeIdx = $findCol($tempMap, ['Telco']);
                            if ($payeeIdx === false) $payeeIdx = $findCol($tempMap, ['Variant']);
                        }
                        if ($payeeIdx === false && ($uploadCategory == 'PAYBILLS' || $uploadCategory == 'SERVICES')) {
                            $payeeIdx = $findCol($tempMap, ['Account No', 'AccountNo', 'Account Number']);
                            if ($payeeIdx === false) $payeeIdx = $findCol($tempMap, ['Biller']);
                            if ($payeeIdx === false) $payeeIdx = $findCol($tempMap, ['Service']);
                        }
                    }

                    if ($dateIdx !== false && isset($tempMap['Email'])) {
                         $currentSection = 'ACASH';
                         $colMap = $tempMap;
                         continue;
                    }
                    if ($acctNoIdx !== false && $dateIdx !== false) {
                        $currentSection = 'ACASH';
                        $colMap = $tempMap;
                        continue;
                    }
                    if ($payeeIdx !== false && $dateIdx !== false) {
                        $currentSection = 'ECPAY';
                        $colMap = $tempMap;
                        continue;
                    }

                    $rowString = implode(" ", $data);
                    if (stripos($rowString, "ACash Information") !== false || stripos($rowString, "ECpay Transaction") !== false) {
                        $currentSection = ''; 
                        continue;
                    }

                    if ($currentSection == 'ACASH') {
                        $dateIdx = $findCol($colMap, ['Date', 'CDate']);
                        $branchIdx = $findCol($colMap, ['Branch', 'Branch Name']);
                        $fundIdx = $findCol($colMap, ['Fund']);
                        $acctNoIdx = $findCol($colMap, ['AcctNo', 'Account No', 'Email', 'Accountno']);
                        $titleIdx = $findCol($colMap, ['AcctTitle', 'Account Title', 'Title']);

                        $date = ($dateIdx !== false && isset($data[$dateIdx])) ? $data[$dateIdx] : '';
                        if (is_numeric($date)) {
                             try { $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d'); } catch (Exception $e) {}
                        } else {
                            $ts = strtotime($date);
                            if ($ts) $date = date('Y-m-d', $ts);
                        }

                        $branch = ($branchIdx !== false && isset($data[$branchIdx])) ? $data[$branchIdx] : '';
                        $branch = $this->normalizeBranch($branch);
                        
                        // Resolve Branch
                        if (empty($branch)) {
                             $potentialEmail = ($acctNoIdx !== false && isset($data[$acctNoIdx])) ? trim($data[$acctNoIdx]) : '';
                             $emailIdx = $findCol($colMap, ['Email']);
                             if ($emailIdx !== false && isset($data[$emailIdx])) $potentialEmail = trim($data[$emailIdx]);

                             if (!empty($potentialEmail) && filter_var($potentialEmail, FILTER_VALIDATE_EMAIL)) {
                                 $stmtMap = $this->conn->prepare("SELECT branch_name FROM tbl_master_identities WHERE email = ? AND branch_name IS NOT NULL LIMIT 1");
                                 if ($stmtMap) {
                                     $stmtMap->bind_param("s", $potentialEmail);
                                     $stmtMap->execute();
                                     $resMap = $stmtMap->get_result();
                                     if ($rowMap = $resMap->fetch_assoc()) $branch = $rowMap['branch_name'];
                                     $stmtMap->close();
                                 }
                             }
                        }

                        $fund = ($fundIdx !== false && isset($data[$fundIdx])) ? $data[$fundIdx] : '';
                        $acctNo = ($acctNoIdx !== false && isset($data[$acctNoIdx])) ? $data[$acctNoIdx] : '';
                        $acctTitle = ($titleIdx !== false && isset($data[$titleIdx])) ? $data[$titleIdx] : '';
                        
                        $variantIdx = $findCol($colMap, ['Variant']);
                        if ($variantIdx !== false && isset($data[$variantIdx]) && !empty($data[$variantIdx])) {
                            $acctTitle .= " " . $data[$variantIdx];
                        }

                        if (!empty($acctNo) || !empty($acctTitle)) {
                            // Validate Date before inserting
                            if (empty($date)) continue;

                            // Insert into Staging
                            $stmt = $this->conn->prepare("INSERT INTO tbl_staging_transactions (CDate, Branch, Fund, Identity, Description, Source, UploadType, Email) VALUES (?, ?, ?, ?, ?, 'ACASH', 'RAW', ?)");
                            if ($stmt) {
                                $emailToSave = $potentialEmail ?? '';
                                $stmt->bind_param("ssssss", $date, $branch, $fund, $acctNo, $acctTitle, $emailToSave);
                                $stmt->execute();
                                $stmt->close();
                            }
                            
                            // Insert into tbl_books (Legacy/Main)
                            $stmt = $this->conn->prepare("INSERT INTO tbl_books (CDate, Branch, Fund, AcctNo, AcctTitle, SLName) VALUES (?, ?, ?, ?, ?, 'ACASH')");
                            if ($stmt) {
                                $stmt->bind_param("sssss", $date, $branch, $fund, $acctNo, $acctTitle);
                                $stmt->execute();
                                $stmt->close();
                            }

                            $this->updateMasterData($acctTitle, $branch, $potentialEmail ?? null);
                            $insertedCount++;
                        }
                    } elseif ($currentSection == 'ECPAY') {
                        $dateIdx = $findCol($colMap, ['Date', 'CDate']);
                        $branchIdx = $findCol($colMap, ['Branch', 'Branch Name']);
                        $payeeIdx = $findCol($colMap, ['Payee', 'Payee Name', 'Name', 'Email']);
                        $explIdx  = $findCol($colMap, ['Explanation', 'Particulars', 'Account No']);
                        // Separate indices for Amount Transacted (DrOther) and Amount Deducted (CrOther)
                        $amtDrIdx = $findCol($colMap, ['Amount Transacted', 'Amount', 'Debit', 'DrOther']);
                        $amtCrIdx = $findCol($colMap, ['Amount Deducted', 'Credit', 'CrOther']);

                        $date = ($dateIdx !== false && isset($data[$dateIdx])) ? $data[$dateIdx] : '';
                        if (is_numeric($date)) {
                             try { 
                                $dtObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                                $date = $dtObj->format('Y-m-d H:i:s'); 
                            } catch (\Throwable $e) {
                                // Keep original date
                            }
                       } else {
                           $ts = strtotime($date);
                           if ($ts) $date = date('Y-m-d H:i:s', $ts);
                       }
                        
                        $branch = ($branchIdx !== false && isset($data[$branchIdx])) ? $data[$branchIdx] : '';
                        $branch = $this->normalizeBranch($branch);

                        // Resolve Branch
                        if (empty($branch)) {
                             $potentialEmail = ($payeeIdx !== false && isset($data[$payeeIdx])) ? trim($data[$payeeIdx]) : '';
                             $emailIdx = $findCol($colMap, ['Email']);
                             if ($emailIdx !== false && isset($data[$emailIdx])) $potentialEmail = trim($data[$emailIdx]);

                             if (!empty($potentialEmail) && filter_var($potentialEmail, FILTER_VALIDATE_EMAIL)) {
                                 $stmtMap = $this->conn->prepare("SELECT branch_name FROM tbl_master_identities WHERE email = ? AND branch_name IS NOT NULL LIMIT 1");
                                 if ($stmtMap) {
                                     $stmtMap->bind_param("s", $potentialEmail);
                                     $stmtMap->execute();
                                     $resMap = $stmtMap->get_result();
                                     if ($rowMap = $resMap->fetch_assoc()) $branch = $rowMap['branch_name'];
                                     $stmtMap->close();
                                 }
                             }
                        }

                        $payee = ($payeeIdx !== false && isset($data[$payeeIdx])) ? $data[$payeeIdx] : '';

                        // Ensure Category is correct if we have Telco/Biller columns (in case header detection missed it or it wasn't persisted)
                        if ($uploadCategory == 'Custom' || $uploadCategory == 'Raw' || empty($uploadCategory)) {
                            if ($findCol($colMap, ['Telco']) !== false) $uploadCategory = 'LOADS';
                            elseif ($findCol($colMap, ['Biller']) !== false) $uploadCategory = 'PAYBILLS';
                        }
                        
                        // If Payee came from Telco/Biller columns (Auto-detected), prefix it
                        if ($uploadCategory == 'LOADS') {
                            $telcoIdx = $findCol($colMap, ['Telco']);
                            if ($payeeIdx === $telcoIdx && !empty($payee)) $payee = "Telco: " . $payee;
                        } elseif ($uploadCategory == 'PAYBILLS' || $uploadCategory == 'SERVICES') {
                            $billerIdx = $findCol($colMap, ['Biller']);
                            if ($payeeIdx === $billerIdx && !empty($payee)) $payee = "Biller: " . $payee;
                        }

                        $explanation = ($explIdx !== false && isset($data[$explIdx])) ? $data[$explIdx] : '';
                        $amountDr   = ($amtDrIdx !== false && isset($data[$amtDrIdx])) ? $data[$amtDrIdx] : 0;
                        $amountCr   = ($amtCrIdx !== false && isset($data[$amtCrIdx])) ? $data[$amtCrIdx] : 0;
                        
                        // Fallback: If payee is empty but we have MobileNo/AccountNo for specific categories, use them
                        if (empty($payee)) {
                            // If still Custom/Raw, try to detect Telco/Biller directly
                            if (($uploadCategory == 'Custom' || $uploadCategory == 'Raw' || empty($uploadCategory)) && empty($payee)) {
                                 $telcoIdx = $findCol($colMap, ['Telco']);
                                 if ($telcoIdx !== false && isset($data[$telcoIdx])) {
                                     $payee = "Telco: " . $data[$telcoIdx];
                                     $uploadCategory = 'LOADS';
                                 }
                                 
                                 if (empty($payee)) {
                                     $billerIdx = $findCol($colMap, ['Biller']);
                                     if ($billerIdx !== false && isset($data[$billerIdx])) {
                                         $payee = "Biller: " . $data[$billerIdx];
                                         $uploadCategory = 'PAYBILLS';
                                     }
                                 }
                            }

                            if ($uploadCategory == 'LOADS') {
                                $mobileNoIdx = $findCol($colMap, ['Mobile No', 'Mobile Number']);
                                if ($mobileNoIdx !== false && isset($data[$mobileNoIdx])) $payee = "Mobile: " . $data[$mobileNoIdx];
                                
                                if (empty($payee)) {
                                    $telcoIdx = $findCol($colMap, ['Telco']);
                                    if ($telcoIdx !== false && isset($data[$telcoIdx])) $payee = "Telco: " . $data[$telcoIdx];
                                }
                            } else if ($uploadCategory == 'PAYBILLS' || $uploadCategory == 'SERVICES') {
                                $acctNoIdx = $findCol($colMap, ['Account No', 'AccountNo', 'Account Number']);
                                if ($acctNoIdx !== false && isset($data[$acctNoIdx])) $payee = "Acct: " . $data[$acctNoIdx];
                                
                                if (empty($payee)) {
                                    $billerIdx = $findCol($colMap, ['Biller']);
                                    if ($billerIdx !== false && isset($data[$billerIdx])) $payee = "Biller: " . $data[$billerIdx];
                                }
                            }
                        }

                        // Fallback: If explanation is empty but we have Service/Variant, use them
                        if (empty($explanation)) {
                            if ($uploadCategory == 'LOADS') {
                                $variantIdx = $findCol($colMap, ['Variant']);
                                if ($variantIdx !== false && isset($data[$variantIdx])) $explanation = $data[$variantIdx];
                            } else if ($uploadCategory == 'PAYBILLS') {
                                $billerIdx = $findCol($colMap, ['Biller']);
                                if ($billerIdx !== false && isset($data[$billerIdx])) $explanation = $data[$billerIdx];
                            } else if ($uploadCategory == 'SERVICES') {
                                $serviceIdx = $findCol($colMap, ['Service']);
                                if ($serviceIdx !== false && isset($data[$serviceIdx])) $explanation = $data[$serviceIdx];
                            }
                        }

                        if ($explIdx !== false) {
                             $refIdx = $findCol($colMap, ['Reference No', 'Reference', 'ServiceRef']);
                             if ($refIdx !== false && isset($data[$refIdx]) && !empty($data[$refIdx])) {
                                 $explanation = "Acct: " . $explanation . " Ref: " . $data[$refIdx];
                             }
                        }

                        // Enforce Category Keyword for Filtering (Ensure data shows up in the selected tab)
                        if ($uploadCategory == 'LOADS' && stripos($explanation, '(Load)') === false && stripos($payee, '(Load)') === false) {
                            $explanation .= " (Load)";
                        } else if ($uploadCategory == 'PAYBILLS' && stripos($explanation, '(Bill)') === false && stripos($payee, '(Bill)') === false) {
                            $explanation .= " (Bill)";
                        } else if ($uploadCategory == 'SERVICES' && stripos($explanation, '(Service)') === false && stripos($payee, '(Service)') === false) {
                            $explanation .= " (Service)";
                        }

                        $amountDr = str_replace(',', '', $amountDr);
                        $amountCr = str_replace(',', '', $amountCr);
                        $dr = is_numeric($amountDr) ? $amountDr : 0;
                        $cr = is_numeric($amountCr) ? $amountCr : 0;

                        // Extract ECPAY specific columns
                        $userIdx = $findCol($colMap, ['User', 'Username']);
                        $telcoIdx = $findCol($colMap, ['Telco']);
                        $variantIdx = $findCol($colMap, ['Variant']);
                        $mobileNoIdx = $findCol($colMap, ['Mobile No', 'MobileNo', 'Mobile Number']);
                        $traceNoIdx = $findCol($colMap, ['Trace No', 'TraceNo', 'Trace Number']);
                        $billerIdx = $findCol($colMap, ['Biller']);
                        $accountNoIdx = $findCol($colMap, ['Account No', 'AccountNo', 'Account Number']);
                        $refNoIdx = $findCol($colMap, ['Reference No', 'ReferenceNo', 'Reference']);
                        $serviceIdx = $findCol($colMap, ['Service']);
                        $serviceRefIdx = $findCol($colMap, ['Service Ref', 'ServiceRef']);
                        $commIdx = $findCol($colMap, ['Commission']);
                        $convFeeIdx = $findCol($colMap, ['Convenience Fee', 'ConvenienceFee']);
                        $servChargeIdx = $findCol($colMap, ['Service Charge', 'ServiceCharge']);
                        $statusIdx = $findCol($colMap, ['Status']);
                        $totalIdx = $findCol($colMap, ['Total', 'Transaction Total']);
                        $monthIdx = $findCol($colMap, ['Month']);
                        $yearIdx  = $findCol($colMap, ['Year']);

                        // Fallback fixed-column mapping for LOADS in RAW uploads as well
                        if ($uploadCategory === 'LOADS') {
                            if ($userIdx === false && isset($data[2]))       $userIdx = 2;
                            if ($telcoIdx === false && isset($data[3]))      $telcoIdx = 3;
                            if ($variantIdx === false && isset($data[4]))    $variantIdx = 4;
                            if ($mobileNoIdx === false && isset($data[5]))   $mobileNoIdx = 5;
                            if ($traceNoIdx === false && isset($data[6]))    $traceNoIdx = 6;
                            if ($amtDrIdx === false && isset($data[7]))      $amtDrIdx = 7;
                            if ($amtCrIdx === false && isset($data[8]))      $amtCrIdx = 8;
                            if ($commIdx === false && isset($data[9]))       $commIdx = 9;
                            if ($convFeeIdx === false && isset($data[10]))   $convFeeIdx = 10;
                            if ($totalIdx === false && isset($data[11]))     $totalIdx = 11;
                            if ($statusIdx === false && isset($data[12]))    $statusIdx = 12;
                            if ($monthIdx === false && isset($data[13]))     $monthIdx = 13;
                            if ($yearIdx === false && isset($data[14]))      $yearIdx = 14;
                        }

                        $user = ($userIdx !== false && isset($data[$userIdx])) ? $data[$userIdx] : '';
                        $telco = ($telcoIdx !== false && isset($data[$telcoIdx])) ? $data[$telcoIdx] : '';
                        $variant = ($variantIdx !== false && isset($data[$variantIdx])) ? $data[$variantIdx] : '';
                        $mobileNo = ($mobileNoIdx !== false && isset($data[$mobileNoIdx])) ? $data[$mobileNoIdx] : '';
                        if (is_numeric($mobileNo) && strpos(strtoupper((string)$mobileNo), 'E') !== false) {
                            $mobileNo = number_format((float)$mobileNo, 0, '.', '');
                        }

                        $traceNo = ($traceNoIdx !== false && isset($data[$traceNoIdx])) ? $data[$traceNoIdx] : '';
                        $biller = ($billerIdx !== false && isset($data[$billerIdx])) ? $data[$billerIdx] : '';
                        $accountNo = ($accountNoIdx !== false && isset($data[$accountNoIdx])) ? $data[$accountNoIdx] : '';
                        $referenceNo = ($refNoIdx !== false && isset($data[$refNoIdx])) ? $data[$refNoIdx] : '';
                        $service = ($serviceIdx !== false && isset($data[$serviceIdx])) ? $data[$serviceIdx] : '';
                        $serviceRef = ($serviceRefIdx !== false && isset($data[$serviceRefIdx])) ? $data[$serviceRefIdx] : '';

                        $commission = ($commIdx !== false && isset($data[$commIdx])) ? str_replace(',', '', $data[$commIdx]) : 0;
                        $convenienceFee = ($convFeeIdx !== false && isset($data[$convFeeIdx])) ? str_replace(',', '', $data[$convFeeIdx]) : 0;
                        $serviceCharge = ($servChargeIdx !== false && isset($data[$servChargeIdx])) ? str_replace(',', '', $data[$servChargeIdx]) : 0;
                        $status = ($statusIdx !== false && isset($data[$statusIdx])) ? $data[$statusIdx] : '';
                        $txnTotal = ($totalIdx !== false && isset($data[$totalIdx])) ? str_replace(',', '', $data[$totalIdx]) : 0;

                        $txnMonth = ($monthIdx !== false && isset($data[$monthIdx])) ? $data[$monthIdx] : '';
                        $txnYear = ($yearIdx !== false && isset($data[$yearIdx])) ? $data[$yearIdx] : '';

                        // If Month/Year empty, derive from Date
                        if (empty($txnMonth) && !empty($date)) {
                            $txnMonth = date('F', strtotime($date));
                        }
                        if (empty($txnYear) && !empty($date)) {
                            $txnYear = date('Y', strtotime($date));
                        }

                        if (!empty($payee) || !empty($explanation)) {
                            // Validate Date before inserting
                            if (empty($date)) {
                                $reason = "Row $rowIndex: Date empty.";
                                $lastFailureReason = $reason;
                                if (empty($firstFailureReason)) $firstFailureReason = $reason;
                                if (count($failures) < 10) $failures[] = $reason;
                                continue;
                            }

                            // Determine UploadType
                            // Fix: Allow 'CUSTOM' to be saved if explicitly selected, otherwise default to RAW
                            $uploadTypeToSave = (in_array($uploadCategory, ['LOADS', 'PAYBILLS', 'SERVICES'])) ? $uploadCategory : (($uploadCategory == 'Custom') ? 'CUSTOM' : 'RAW');

                            // Insert into Staging
                            // 8 placeholders => 8 type definitions (s,s,s,s,d,d,s,s)
                            $stmtStaging = $this->conn->prepare("INSERT INTO tbl_staging_transactions (CDate, Branch, Identity, Description, DrOther, CrOther, Source, UploadType, Email) VALUES (?, ?, ?, ?, ?, ?, 'ECPAY', ?, ?)");
                            if ($stmtStaging) {
                                $emailToSave = $potentialEmail ?? '';
                                $stmtStaging->bind_param("ssssddss", $date, $branch, $payee, $explanation, $dr, $cr, $uploadTypeToSave, $emailToSave);
                                $stmtStaging->execute();
                                $stmtStaging->close();
                            }

                            // Insert into tbl_books (Legacy/Main)
                            $stmt = $this->conn->prepare("INSERT INTO tbl_books (CDate, Branch, Payee, Explanation, DrOther, CrOther, User, Telco, Variant, MobileNo, TraceNo, Biller, AccountNo, ReferenceNo, Service, ServiceRef, Commission, ConvenienceFee, ServiceCharge, Status, TransactionTotal, TransactionMonth, TransactionYear) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            if ($stmt) {
                                $stmt->bind_param("ssssddssssssssssdddsdss", $date, $branch, $payee, $explanation, $dr, $cr, $user, $telco, $variant, $mobileNo, $traceNo, $biller, $accountNo, $referenceNo, $service, $serviceRef, $commission, $convenienceFee, $serviceCharge, $status, $txnTotal, $txnMonth, $txnYear);
                                $stmt->execute();
                                $stmt->close();
                            }
                            
                            $this->updateMasterData($payee, $branch, $potentialEmail ?? null);
                            $insertedCount++;
                        } else {
                            $debugDetail = "Cat: $uploadCategory. PayeeIdx: " . ($payeeIdx !== false ? $payeeIdx : 'false') . ". TelcoIdx: " . ($telcoIdx !== false ? $telcoIdx : 'false');
                            if ($telcoIdx !== false && isset($data[$telcoIdx])) $debugDetail .= ". TelcoVal: " . $data[$telcoIdx];

                            $reason = "Row $rowIndex: Payee & Explanation empty. $debugDetail";
                            $lastFailureReason = $reason;
                            if (empty($firstFailureReason)) $firstFailureReason = $reason;
                            if (count($failures) < 10) $failures[] = $reason;
                        }
                    }
                }
            }

            if ($insertedCount > 0) {
                 while (ob_get_level()) ob_end_clean();
                 echo json_encode(["STATUS" => "SUCCESS", "MESSAGE" => "Uploaded $insertedCount records successfully."]);
            } else {
                 while (ob_get_level()) ob_end_clean();
                 $msg = "No records uploaded. Could not detect 'Date' AND 'AcctNo'/'Payee' headers in any sheet.";
                 if (!empty($failures)) $msg .= " Failures: " . implode(" | ", $failures);
                 else {
                     if (!empty($firstFailureReason)) $msg .= " First Skipped: " . $firstFailureReason;
                     if (!empty($lastFailureReason)) $msg .= " Last Skipped: " . $lastFailureReason;
                 }
                 echo json_encode(["STATUS" => "ERROR", "MESSAGE" => $msg . " First rows seen: " . implode(" | ", $debugInfo)]);
            }

        } catch (Exception $e) {
            while (ob_get_level()) ob_end_clean();
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Error processing file: " . $e->getMessage()]);
        }
    }

    private function CheckTables() {
        // 1. Staging Table
        $this->conn->query("CREATE TABLE IF NOT EXISTS tbl_staging_transactions (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            CDate VARCHAR(50),
            Branch VARCHAR(100),
            Fund VARCHAR(100),
            Identity VARCHAR(255),
            Description TEXT,
            DrOther DECIMAL(15,2) DEFAULT 0,
            CrOther DECIMAL(15,2) DEFAULT 0,
            Source VARCHAR(20),
            UploadType VARCHAR(20),
            Email VARCHAR(255),
            OwnerName VARCHAR(255),
            INDEX idx_source_type (Source, UploadType)
        )");
        
        // Add Email column if it doesn't exist (for existing tables)
        $checkCol = $this->conn->query("SHOW COLUMNS FROM tbl_staging_transactions LIKE 'Email'");
        if ($checkCol && $checkCol->num_rows == 0) {
            $this->conn->query("ALTER TABLE tbl_staging_transactions ADD COLUMN Email VARCHAR(255) AFTER UploadType");
        }
        
        // Add OwnerName column if it doesn't exist
        $checkCol = $this->conn->query("SHOW COLUMNS FROM tbl_staging_transactions LIKE 'OwnerName'");
        if ($checkCol && $checkCol->num_rows == 0) {
            $this->conn->query("ALTER TABLE tbl_staging_transactions ADD COLUMN OwnerName VARCHAR(255) AFTER Email");
        }

        // 1.1 Add ECPAY specific columns to tbl_books if they don't exist
        $ecpayColumns = [
            'User' => 'VARCHAR(255)',
            'Telco' => 'VARCHAR(100)',
            'Variant' => 'VARCHAR(100)',
            'MobileNo' => 'VARCHAR(50)',
            'TraceNo' => 'VARCHAR(100)',
            'Biller' => 'VARCHAR(255)',
            'AccountNo' => 'VARCHAR(100)',
            'ReferenceNo' => 'VARCHAR(100)',
            'Service' => 'VARCHAR(255)',
            'ServiceRef' => 'VARCHAR(100)',
            'Commission' => 'DECIMAL(15,2) DEFAULT 0',
            'ConvenienceFee' => 'DECIMAL(15,2) DEFAULT 0',
            'ServiceCharge' => 'DECIMAL(15,2) DEFAULT 0',
            'Status' => 'VARCHAR(50)',
            'TransactionTotal' => 'DECIMAL(15,2) DEFAULT 0',
            'TransactionMonth' => 'VARCHAR(20)',
            'TransactionYear' => 'VARCHAR(10)'
        ];

        foreach ($ecpayColumns as $col => $type) {
            $check = $this->conn->query("SHOW COLUMNS FROM tbl_books LIKE '$col'");
            if ($check && $check->num_rows == 0) {
                $this->conn->query("ALTER TABLE tbl_books ADD COLUMN $col $type");
            }
        }

        // 2. Master Identities - Ensure branch_name and full_name columns exist
        $checkCol = $this->conn->query("SHOW COLUMNS FROM tbl_master_identities LIKE 'branch_name'");
        if ($checkCol && $checkCol->num_rows == 0) {
            $this->conn->query("ALTER TABLE tbl_master_identities ADD COLUMN branch_name VARCHAR(100) AFTER email");
            
            // 3. Migrate data if branch_id exists
            $checkBranchId = $this->conn->query("SHOW COLUMNS FROM tbl_master_identities LIKE 'branch_id'");
            if ($checkBranchId && $checkBranchId->num_rows > 0) {
                $checkTable = $this->conn->query("SHOW TABLES LIKE 'tbl_master_branches'");
                if ($checkTable && $checkTable->num_rows > 0) {
                     $this->conn->query("UPDATE tbl_master_identities i 
                                         JOIN tbl_master_branches b ON i.branch_id = b.id 
                                         SET i.branch_name = b.branch_name 
                                         WHERE (i.branch_name IS NULL OR i.branch_name = '')");
                }
            }
        }

        $checkCol = $this->conn->query("SHOW COLUMNS FROM tbl_master_identities LIKE 'full_name'");
        if ($checkCol && $checkCol->num_rows == 0) {
            $this->conn->query("ALTER TABLE tbl_master_identities ADD COLUMN full_name VARCHAR(255) AFTER branch_name");
        }
    }

    private function normalizeBranch($branch) {
        $mfiBranches = [
            'ABULUG', 'AGOO', 'ALCALA', 'ALFONSO LISTA', 'ALIAGA', 'ALICIA', 'ALLACAPAN', 
            'ANGELES', 'APARRI', 'ARAYAT', 'ARITAO', 'BAGABAG', 'BAGGAO', 'BALER', 
            'BALIUAG', 'BAMBANG', 'BAMBANG SOUTH', 'BENITO SOLIVEN', 'BINMALEY', 
            'BONGABON', 'CABAGAN', 'CABANATUAN', 'CABARROGUIS', 'CABATUAN', 'CABIAO', 
            'CALASIAO', 'CAMALANIUGAN', 'CAMILING', 'CARRANGLAN', 'CASIGURAN', 
            'CAUAYAN', 'CONCEPCION', 'CUYAPO', 'DIFFUN', 'DIPACULAO', 'DUPAX', 
            'ECHAGUE', 'GABALDON', 'GAPAN', 'GATTARAN', 'GENERAL TINIO', 'GONZAGA', 
            'GUIMBA', 'ILAGAN', 'ILAGAN NORTH', 'JAEN', 'JONES', 'LAGAWE', 'LASAM', 
            'LOURDES', 'LUPAO', 'MADELLA', 'MAGALANG', 'MALLIG', 'MANGALDAN', 
            'MANGATAREM', 'MARIA', 'MEXICO', 'MONCADA', 'MUNOZ', 'PALAYAN', 
            'PANIQUI', 'PANTABANGAN', 'PIAT', 'PLARIDEL', 'PORAC', 'QUEZON', 'RIZAL', 
            'ROSALES', 'ROSARIO', 'ROXAS', 'SAN FERNANDO', 'SAN GUILLERMO', 
            'SAN ILDEFONSO', 'SAN ISIDRO', 'SAN JOSE', 'SAN JOSE DEL MONTE', 
            'SAN MATEO', 'SAN MIGUEL', 'SAN RAFAEL', 'SANTIAGO', 'SOLANA', 'SOLANO', 
            'STA IGNACIA', 'STA MARIA', 'STA ROSA', 'TABUK', 'TALAVERA', 'TARLAC', 
            'TARLAC SOUTH', 'TAYUG', 'TUGUEGARAO', 'TUGUEGARAO CARIG', 
            'TUGUEGARAO SOUTH', 'TUMAUINI', 'URDANETA', 'VICTORIA', 'ZARAGOSA'
        ];
        
        $upperBranch = strtoupper(trim($branch));
        foreach ($mfiBranches as $mfi) {
             if (strpos($upperBranch, $mfi) !== false) {
                 return 'MFI BRANCHES';
             }
        }
        return $branch;
    }

    private function updateMasterData($name, $branch, $email = null) {
        if (empty($name) && empty($email)) return;
        
        $branch = trim($branch);
        $name = trim($name);
        $email = trim($email);
        if ($email === '') $email = null; // Treat empty string as NULL to avoid duplicate key error
        $identityId = null;

        // Try to find by email first
        if (!empty($email)) {
            $stmt = $this->conn->prepare("SELECT id FROM tbl_master_identities WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $identityId = $result->fetch_assoc()['id'];
            }
            $stmt->close();
        }
        
        // If not found by email, try by name
        if (!$identityId && !empty($name)) {
            $stmt = $this->conn->prepare("SELECT id FROM tbl_master_identities WHERE full_name = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $identityId = $result->fetch_assoc()['id'];
            }
            $stmt->close();
        }

        // Insert if new
        if (!$identityId) {
            $insertName = !empty($name) ? $name : $email; // Fallback to email as name
            $stmt = $this->conn->prepare("INSERT INTO tbl_master_identities (full_name, email, branch_name, source_table) VALUES (?, ?, ?, 'upload')");
            $stmt->bind_param("sss", $insertName, $email, $branch);
            $stmt->execute();
            $stmt->close();
        } else {
            // Update branch if missing
            if (!empty($branch)) {
                $stmt = $this->conn->prepare("UPDATE tbl_master_identities SET branch_name = ? WHERE id = ? AND (branch_name IS NULL OR branch_name = '')");
                $stmt->bind_param("si", $branch, $identityId);
                $stmt->execute();
                $stmt->close();
            }
            // Update email if missing
            if (!empty($email)) {
                $stmt = $this->conn->prepare("UPDATE tbl_master_identities SET email = ? WHERE id = ? AND (email IS NULL OR email = '')");
                $stmt->bind_param("si", $email, $identityId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    private function LoadData($source, $uploadType, $page, $limit) {
        $offset = ($page - 1) * $limit;
        
        // Count Total
        $sqlCount = "SELECT COUNT(*) as total FROM tbl_staging_transactions WHERE Source = '$source' AND UploadType = '$uploadType'";
        $resCount = $this->conn->query($sqlCount);
        $total = $resCount ? $resCount->fetch_assoc()['total'] : 0;

        // Fetch Data
        // Reverting to simple SELECT to ensure data visibility. 
        // We will fetch names separately to avoid JOIN issues.
        $sql = "SELECT * FROM tbl_staging_transactions WHERE Source = '$source' AND UploadType = '$uploadType' ORDER BY ID DESC LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);
        
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Map Description to AcctTitle for ACASH Custom view
                if (!isset($row['AcctTitle']) && isset($row['Description'])) {
                    $row['AcctTitle'] = $row['Description'];
                }
                $data[] = $row;
            }
        }
        
        // Populate OwnerName from Master Data (PHP-side merge)
        if (!empty($data)) {
            $emails = [];
            foreach ($data as $r) {
                if (!empty($r['Email'])) $emails[] = "'" . $this->conn->real_escape_string($r['Email']) . "'";
                if (!empty($r['Identity'])) $emails[] = "'" . $this->conn->real_escape_string($r['Identity']) . "'";
            }
            
            if (!empty($emails)) {
                $emailList = implode(',', array_unique($emails));
                $sqlMap = "SELECT email, full_name FROM tbl_master_identities WHERE email IN ($emailList) AND full_name IS NOT NULL AND full_name != ''";
                $resMap = $this->conn->query($sqlMap);
                $map = [];
                if ($resMap) {
                    while ($mRow = $resMap->fetch_assoc()) {
                        $map[strtoupper($mRow['email'])] = $mRow['full_name'];
                    }
                }
                
                // Assign names back to data
                foreach ($data as &$row) {
                    $emailKey = !empty($row['Email']) ? strtoupper($row['Email']) : (!empty($row['Identity']) ? strtoupper($row['Identity']) : '');
                    if ($emailKey && isset($map[$emailKey])) {
                        $row['OwnerName'] = $map[$emailKey];
                        $row['AcctTitle'] = $map[$emailKey]; // Override AcctTitle
                    }
                }
            }
        }
        
        // Return ACASHINFO or ECPAYTXNS key to match frontend expectation
        $key = ($source == 'ACASH') ? 'ACASHINFO' : 'ECPAYTXNS';
        
        echo json_encode([
            "STATUS" => "SUCCESS", 
            $key => $data, 
            "TOTAL" => $total, 
            "PAGE" => $page, 
            "LIMIT" => $limit
        ]);
    }
}
