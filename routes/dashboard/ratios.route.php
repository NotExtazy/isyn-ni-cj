<?php
// Enable error logging but prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/ratios_errors.log');

// Start output buffering to catch any unexpected output
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// For AJAX requests, check if user is authenticated
if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !$_SESSION["AUTHENTICATED"]) {
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Authentication required']);
    exit;
}

header('Content-Type: application/json');

// Include Database class first
include_once(__DIR__ . "/../../database/connection.php");

// Include Process class
include_once(__DIR__ . "/../../process/dashboard/ratios.process.php");

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        // Log the action being called
        error_log("Ratios action called: " . $action);
        
        // Check database connection first
        $testConn = new Database();
        if (!$testConn->conn || $testConn->conn->connect_error) {
            throw new Exception("Database connection failed");
        }
        
        $process = new RatiosProcess();
        
        switch ($action) {
            case 'GetRatioData':
                $process->GetRatioData($_POST);
                break;
                
            default:
                error_log("Invalid action: " . $action);
                echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Invalid action: ' . $action]);
                break;
        }
    } catch (Exception $e) {
        error_log("Ratios exception: " . $e->getMessage());
        echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => $e->getMessage()]);
    } catch (Error $e) {
        error_log("Ratios fatal error: " . $e->getMessage());
        echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Fatal error: ' . $e->getMessage()]);
    }
} else {
    error_log("No action specified in ratios request");
    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'No action specified']);
}

// Clean output buffer and send response
ob_end_flush();
?>
