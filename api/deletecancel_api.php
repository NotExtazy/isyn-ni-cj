<?php
// API for deletecancel functionality
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['AUTHENTICATED']) || $_SESSION['AUTHENTICATED'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once('../database/connection.php');

$db = new Database();
$conn = $db->conn;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_or_types':
            getORTypes($conn);
            break;
            
        case 'get_transactions':
            $orType = $_GET['or_type'] ?? '';
            getTransactions($conn, $orType);
            break;
            
        case 'get_transaction_details':
            $orNo = $_GET['or_no'] ?? '';
            getTransactionDetails($conn, $orNo);
            break;
            
        case 'delete_transaction':
            $orNo = $_POST['or_no'] ?? '';
            deleteTransaction($conn, $orNo);
            break;
            
        case 'cancel_transaction':
            $orNo = $_POST['or_no'] ?? '';
            cancelTransaction($conn, $orNo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getORTypes($conn) {
    // Get distinct OR types from transactions
    $sql = "SELECT DISTINCT 
                CASE 
                    WHEN nature LIKE '%loan%' OR nature LIKE '%payment%' THEN 'LOAN'
                    WHEN nature LIKE '%other%' OR nature LIKE '%misc%' THEN 'OTHER'
                    ELSE 'GENERAL'
                END as or_type,
                COUNT(*) as count
            FROM tbl_loanspayment 
            WHERE DATE(cdate) = CURDATE()
            GROUP BY or_type
            ORDER BY or_type";
    
    $result = $conn->query($sql);
    $types = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $types[] = [
                'type' => $row['or_type'],
                'label' => $row['or_type'] . ' (' . $row['count'] . ')',
                'count' => $row['count']
            ];
        }
    }
    
    // Add default types if none found
    if (empty($types)) {
        $types = [
            ['type' => 'LOAN', 'label' => 'Loan Payments', 'count' => 0],
            ['type' => 'OTHER', 'label' => 'Other Payments', 'count' => 0]
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $types]);
}

function getTransactions($conn, $orType) {
    $whereClause = "WHERE DATE(cdate) = CURDATE()";
    
    if ($orType) {
        switch ($orType) {
            case 'LOAN':
                $whereClause .= " AND (nature LIKE '%loan%' OR nature LIKE '%payment%')";
                break;
            case 'OTHER':
                $whereClause .= " AND (nature LIKE '%other%' OR nature LIKE '%misc%')";
                break;
        }
    }
    
    $sql = "SELECT 
                lp.orno,
                COALESCE(c.fullname, lp.clientname, 'Unknown') as client_name,
                lp.clientno,
                lp.loanid,
                lp.nature,
                lp.fund,
                DATE_FORMAT(lp.cdate, '%m/%d/%Y') as cdate,
                lp.principal,
                lp.interest,
                lp.cbu,
                lp.penalty,
                lp.mba,
                (lp.principal + lp.interest + lp.cbu + lp.penalty + lp.mba) as total
            FROM tbl_loanspayment lp
            LEFT JOIN tbl_clientlist c ON lp.clientno = c.clientno
            $whereClause
            ORDER BY lp.cdate DESC, lp.orno DESC
            LIMIT 100";
    
    $result = $conn->query($sql);
    $transactions = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $transactions]);
}

function getTransactionDetails($conn, $orNo) {
    $sql = "SELECT 
                lp.*,
                COALESCE(c.fullname, lp.clientname, 'Unknown') as client_name
            FROM tbl_loanspayment lp
            LEFT JOIN tbl_clientlist c ON lp.clientno = c.clientno
            WHERE lp.orno = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $orNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Transaction not found']);
    }
}

function deleteTransaction($conn, $orNo) {
    // Check if transaction exists and is from today
    $checkSql = "SELECT orno FROM tbl_loanspayment WHERE orno = ? AND DATE(cdate) = CURDATE()";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $orNo);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Transaction not found or not from today']);
        return;
    }
    
    // Log the deletion
    $logSql = "INSERT INTO tbl_transaction_log (orno, action, performed_by, performed_at) VALUES (?, 'DELETE', ?, NOW())";
    $logStmt = $conn->prepare($logSql);
    if ($logStmt) {
        $logStmt->bind_param("ss", $orNo, $_SESSION['USERNAME']);
        $logStmt->execute();
    }
    
    // Delete the transaction
    $deleteSql = "DELETE FROM tbl_loanspayment WHERE orno = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("s", $orNo);
    
    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Transaction deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete transaction']);
    }
}

function cancelTransaction($conn, $orNo) {
    // Check if transaction exists and is from today
    $checkSql = "SELECT orno FROM tbl_loanspayment WHERE orno = ? AND DATE(cdate) = CURDATE()";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $orNo);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Transaction not found or not from today']);
        return;
    }
    
    // Log the cancellation
    $logSql = "INSERT INTO tbl_transaction_log (orno, action, performed_by, performed_at) VALUES (?, 'CANCEL', ?, NOW())";
    $logStmt = $conn->prepare($logSql);
    if ($logStmt) {
        $logStmt->bind_param("ss", $orNo, $_SESSION['USERNAME']);
        $logStmt->execute();
    }
    
    // Mark as cancelled (add cancelled flag or update status)
    $cancelSql = "UPDATE tbl_loanspayment SET status = 'CANCELLED', cancelled_by = ?, cancelled_at = NOW() WHERE orno = ?";
    $cancelStmt = $conn->prepare($cancelSql);
    $cancelStmt->bind_param("ss", $_SESSION['USERNAME'], $orNo);
    
    if ($cancelStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Transaction cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to cancel transaction']);
    }
}
?>