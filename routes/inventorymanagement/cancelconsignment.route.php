<?php
// Start output buffering to catch any unexpected output
ob_start();

// Handle session properly
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Use absolute path to ensure the file is found
    $basePath = dirname(__DIR__, 2);
    include_once($basePath . "/process/inventorymanagement/cancelconsignment.process.php");
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Clean any output that might have been generated
        if (ob_get_level() > 0) { 
            ob_clean(); 
        }
        header('Content-Type: application/json');
        ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        
        $process = new CancelConsignmentProcess();
        
        if (isset($_POST['action']) && $_POST['action'] === 'Initialize') { 
            $process->Initialize(); 
        }
        if (isset($_POST['action']) && $_POST['action'] === 'LoadTypes') { 
            $process->LoadTypes($_POST); 
        }
        if (isset($_POST['action']) && $_POST['action'] === 'LoadCategories') { 
            $process->LoadCategories($_POST); 
        }
        if (isset($_POST['action']) && $_POST['action'] === 'SearchProducts') { 
            $process->SearchProducts($_POST); 
        }
        if (isset($_POST['action']) && $_POST['action'] === 'CancelConsignment') { 
            $process->CancelConsignment($_POST); 
        }
    } else {
        // For non-POST requests, just end the buffer
        ob_end_clean();
    }
    
} catch (Exception $e) {
    // Clean any output and send error response
    if (ob_get_level() > 0) { 
        ob_clean(); 
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>$e->getMessage()]);
} catch (Error $e) {
    // Handle fatal errors
    if (ob_get_level() > 0) { 
        ob_clean(); 
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>$e->getMessage()]);
}

// End output buffering
if (ob_get_level() > 0) { 
    ob_end_flush(); 
}
?>
