<?php
include(__DIR__ . "/../../database/connection.php");

header('Content-Type: application/json');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'individual'; // individual, branch, department
$metric = isset($_GET['metric']) ? $_GET['metric'] : 'count'; // count, amount
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

// Drill-down filters
$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : ''; // e.g., 'branch', 'department'
$filterValue = isset($_GET['filter_value']) ? $_GET['filter_value'] : ''; // e.g., 'CALASIAO'

$db = new Database();
$conn = $db->conn;

// Shared Categorization Logic for both Top Performers and Filters
$categoryCase = "CASE 
    WHEN b.Explanation LIKE '%(Load)%' OR b.Payee LIKE '%(Load)%' THEN 'LOADS'
    WHEN b.Explanation LIKE '%(Bill)%' OR b.Payee LIKE '%(Bill)%' THEN 'PAYBILLS'
    WHEN b.Explanation LIKE '%(Service)%' OR b.Payee LIKE '%(Service)%' THEN 'SERVICES'
    WHEN b.Branch IN ('ISYNERGIES, INC', 'ISYNERGIES, INC.', '37568 : ISYNERGIES, INC.') THEN 'ISYNERGIES, INC'
    WHEN b.Branch IN ('HEADOFFICE', 'HO') THEN 'HEADOFFICE'
    WHEN b.AcctNo LIKE '%@isynergies.com%' OR b.AcctNo LIKE '%@mfi.org%' THEN 'STAFF'
    WHEN (b.AcctNo LIKE '%@gmail.com%' OR b.AcctNo LIKE '%@yahoo.com%' OR b.AcctNo LIKE '%@hotmail.com%' OR b.AcctNo LIKE '%@outlook.com%') 
         AND (b.Branch IS NULL OR b.Branch = '' OR b.Branch = 'Unknown' OR LOWER(b.Branch) NOT LIKE CONCAT('%', LOWER(SUBSTRING_INDEX(b.AcctNo, '@', 1)), '%')) 
         THEN 'INDIVIDUAL'
    WHEN b.Branch LIKE '%BUSINESS%' OR b.Branch LIKE '%UNIT%' OR b.Branch LIKE '%DEPT%' THEN 'BUSINESS UNIT'
    WHEN b.Branch IS NOT NULL AND b.Branch != '' AND b.Branch != 'Unknown' THEN 'MFI BRANCHES'
    WHEN b.AcctNo LIKE '%@gmail.com%' OR b.AcctNo LIKE '%@yahoo.com%' OR b.AcctNo LIKE '%@hotmail.com%' OR b.AcctNo LIKE '%@outlook.com%' THEN 'EXTERNAL CLIENT'
    WHEN (b.Payee IS NOT NULL AND b.Payee != '' AND b.Payee != '-') OR (b.AcctTitle IS NOT NULL AND b.AcctTitle != '') THEN 'INDIVIDUAL'
    ELSE 'OTHERS'
END";

if ($action == 'get_top_performers') {
    $data = [];
    
    // Source Logic
    $source = isset($_GET['source']) ? $_GET['source'] : 'all';
    $sourceWhere = "";
    if ($source == 'acash') {
        $sourceWhere = " AND b.SLName = 'ACASH' AND COALESCE(b.DrOther, 0) > 0";
    } elseif ($source == 'ecpay') {
        $sourceWhere = " AND (b.Payee LIKE '%EC%PAY%' OR b.Explanation LIKE '%EC%PAY%' OR b.Explanation LIKE '%ECPAY%' OR b.Branch LIKE '%ISYNERGIES%')";
    }

    // Date Filters
    $year = isset($_GET['year']) ? $_GET['year'] : '';
    $month = isset($_GET['month']) ? $_GET['month'] : '';
    
    if (!empty($year)) {
        $sourceWhere .= " AND YEAR(b.CDate) = '" . $conn->real_escape_string($year) . "'";
    }
    if (!empty($month)) {
        $sourceWhere .= " AND MONTH(b.CDate) = '" . $conn->real_escape_string($month) . "'";
    }

    // If a filter is applied, we are always looking for Individuals within that context
    if (!empty($filterType) && !empty($filterValue)) {
        $whereClause = "";
        $joinClause = "";
        $havingClause = "";
        
        if ($filterType == 'branch') {
            // Filter by tbl_books.Branch as requested by user ("choices i can see on the table")
            $joinClause = "LEFT JOIN tbl_master_identities i ON i.full_name = COALESCE(NULLIF(b.Payee, ''), NULLIF(b.AcctTitle, ''))";
            $whereClause = "WHERE b.Branch = '" . $conn->real_escape_string($filterValue) . "'";
        } elseif ($filterType == 'department') {
            $joinClause = "JOIN tbl_master_identities i ON i.full_name = COALESCE(NULLIF(b.Payee, ''), NULLIF(b.AcctTitle, ''))
                           JOIN tbl_master_departments d ON i.department_id = d.id";
            $whereClause = "WHERE d.department_name = '" . $conn->real_escape_string($filterValue) . "'";
        } elseif ($filterType == 'category') {
            // For category, we filter using HAVING on the computed column.
            $joinClause = "LEFT JOIN tbl_master_identities i ON i.full_name = COALESCE(NULLIF(b.Payee, ''), NULLIF(b.AcctTitle, ''))";
            $havingClause = "HAVING Category = '" . $conn->real_escape_string($filterValue) . "'";
            
            // Initialize WHERE if not set by others
            $whereClause = "WHERE 1=1";
        }
        
        // Append Source Filter
        if (empty($whereClause)) $whereClause = "WHERE 1=1";
        $whereClause .= $sourceWhere;

        if ($source == 'ecpay') {
            $nameExpr = "COALESCE(NULLIF(SUBSTRING_INDEX(b.User, ' : ', -1), ''), NULLIF(b.Payee, ''), 'Unknown')";
        } else {
            $nameExpr = "COALESCE(i.full_name, NULLIF(b.Payee, ''), NULLIF(b.AcctTitle, ''), 'Unknown')";
        }

        $sql = "SELECT $nameExpr as Name, 
                       COUNT(*) as TransactionCount, 
                       SUM(b.DrOther) as TotalAmount,
                       $categoryCase as Category
                FROM tbl_books b
                $joinClause
                $whereClause
                GROUP BY Name, Category
                $havingClause
                ORDER BY " . (($metric == 'amount' || $metric == 'all') ? "TotalAmount DESC" : "CASE WHEN TransactionCount BETWEEN 0 AND 1000 THEN 1000 - TransactionCount ELSE -1 END") . " 
                LIMIT $limit";
        
        $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            echo json_encode($data);
            exit;
        }
// Removed duplicate code block
    
    // Default Logic (No drill-down)
    $groupBy = "";
    $selectName = "";
    
    if ($type == 'individual') {
        if ($source == 'ecpay') {
            $selectName = "COALESCE(NULLIF(SUBSTRING_INDEX(b.User, ' : ', -1), ''), NULLIF(b.Payee, ''), 'Unknown') as Name";
        } else {
            $selectName = "COALESCE(NULLIF(b.Payee, ''), NULLIF(b.AcctTitle, ''), 'Unknown') as Name";
        }
        $groupBy = "Name";
    } elseif ($type == 'branch') {
        // Updated to include LOADS, PAYBILLS, SERVICES in the "Overview" graph
        $selectName = "CASE 
            WHEN b.Explanation LIKE '%(Load)%' OR b.Payee LIKE '%(Load)%' THEN 'LOADS'
            WHEN b.Explanation LIKE '%(Bill)%' OR b.Payee LIKE '%(Bill)%' THEN 'PAYBILLS'
            WHEN b.Explanation LIKE '%(Service)%' OR b.Payee LIKE '%(Service)%' THEN 'SERVICES'
            WHEN b.Branch IN ('ISYNERGIES, INC', 'ISYNERGIES, INC.', '37568 : ISYNERGIES, INC.') THEN 'ISYNERGIES, INC'
            WHEN b.Branch IN ('HEADOFFICE', 'HO') THEN 'HEADOFFICE'
            ELSE b.Branch 
        END as Name";
        $groupBy = "Name";
    } elseif ($type == 'category') {
        $selectName = "$categoryCase as Name";
        $groupBy = "Name";
    } elseif ($type == 'department') {
        $selectName = "d.department_name as Name";
        $groupBy = "d.department_name";
        
        $sql = "SELECT $selectName, 
                       COUNT(*) as TransactionCount, 
                       SUM(b.DrOther) as TotalAmount 
                FROM tbl_books b
                LEFT JOIN tbl_master_identities i ON i.full_name = COALESCE(NULLIF(b.Payee, ''), NULLIF(b.AcctTitle, ''))
                LEFT JOIN tbl_master_departments d ON i.department_id = d.id
                WHERE d.department_name IS NOT NULL" . ($source == 'acash' ? " AND COALESCE(b.DrOther, 0) > 0" : "") . "
                $sourceWhere
                GROUP BY $groupBy 
                ORDER BY " . ($metric == 'amount' ? "TotalAmount DESC" : "CASE WHEN TransactionCount BETWEEN 0 AND 1000 THEN 1000 - TransactionCount ELSE -1 END") . " 
                LIMIT $limit";
                
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        exit;
    }

    if ($type != 'department') {
        $sql = "SELECT $selectName, 
                       COUNT(*) as TransactionCount, 
                       SUM(b.DrOther) as TotalAmount 
                FROM tbl_books b
                WHERE 1=1 $sourceWhere
                GROUP BY $groupBy 
                ORDER BY " . (($metric == 'amount' || $metric == 'all') ? "TotalAmount DESC" : "CASE WHEN TransactionCount BETWEEN 0 AND 1000 THEN 1000 - TransactionCount ELSE -1 END") . " 
                LIMIT $limit";

        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
    }
} elseif ($action == 'get_filters') {
    $source = isset($_GET['source']) ? $_GET['source'] : 'all';
    $sourceWhere = "1=1";
    if ($source == 'acash') {
        $sourceWhere = "b.SLName = 'ACASH'";
    } elseif ($source == 'ecpay') {
        $sourceWhere = "(b.Payee LIKE '%EC%PAY%' OR b.Explanation LIKE '%EC%PAY%' OR b.Explanation LIKE '%ECPAY%' OR b.Branch LIKE '%ISYNERGIES%')";
    }

    $filters = [
        'categories' => [],
        'branches' => []
    ];
    
    // Get Categories that have data for this source
    $sqlCat = "SELECT DISTINCT $categoryCase as Category 
               FROM tbl_books b 
               WHERE $sourceWhere 
               ORDER BY Category";
    $resCat = $conn->query($sqlCat);
    while ($row = $resCat->fetch_assoc()) {
        if (!empty($row['Category'])) $filters['categories'][] = $row['Category'];
    }

    // Get Branches that have data for this source (excluding those already in categories)
    $sqlBranch = "SELECT DISTINCT b.Branch 
                  FROM tbl_books b 
                  WHERE $sourceWhere AND b.Branch IS NOT NULL AND b.Branch != '' 
                  ORDER BY b.Branch";
    $resBranch = $conn->query($sqlBranch);
    while ($row = $resBranch->fetch_assoc()) {
        $branch = $row['Branch'];
        // Avoid duplicates if the branch name matches a category name exactly
        if (!in_array(strtoupper($branch), $filters['categories'])) {
            $filters['branches'][] = $branch;
        }
    }
    
    echo json_encode($filters);
    exit;
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
