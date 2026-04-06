<?php
// Simple router for iSynApp
// Start session for authentication checks
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$requestPath = rtrim($requestPath, '/');

// Detect base path dynamically (e.g., /iSynApp-main) and strip it from the request path
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($basePath === '.' || $basePath === '/') { $basePath = ''; }
if ($basePath !== '' && strpos($requestPath, $basePath) === 0) {
    $requestPath = substr($requestPath, strlen($basePath));
}

// Normalize with leading slash for route matching
$requestPath = '/' . ltrim($requestPath, '/');

// Set a constant for the requested path to be used in included files
define('ROUTED_PATH', $requestPath);

// Check if user is authenticated
$isAuthenticated = isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true;

// If it's the root, serve index.php (main dashboard)
if ($requestPath === '' || $requestPath === '/') {
    if (!$isAuthenticated) {
        // Redirect to login
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    include_once 'index.php';
    exit;
}

// Special handle for dashboard route
if ($requestPath === '/dashboard') {
    if (!$isAuthenticated) {
        // Redirect to login
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    include_once 'dashboard.php';
    exit;
}

// Special handle for login
if ($requestPath === '/login') {
    include_once 'login.php';
    exit;
}

// Special handle for depreciation (accounts monitoring module)
if ($requestPath === '/depreciation' || $requestPath === '/accountsmonitoring/depreciation') {
    if (!$isAuthenticated) {
        // Redirect to login
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    if (file_exists('pages/accountsmonitoring/depreciation.php')) {
        include_once 'pages/accountsmonitoring/depreciation.php';
        exit;
    }
}

// Special handle for pendingreleases (accounts monitoring module)
if ($requestPath === '/pendingreleases' || $requestPath === '/accountsmonitoring/pendingreleases') {
    if (!$isAuthenticated) {
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    $_SESSION['parent_module'] = 'Accountsmonitoring';
    $_SESSION['current_module'] = 'Pendingreleases';
    
    if (file_exists('pages/accountsmonitoring/pendingreleases_copy.php')) {
        include_once 'pages/accountsmonitoring/pendingreleases_copy.php';
        exit;
    }
}

// Special handle for deletecancel minimal version (for debugging)
if ($requestPath === '/deletecancel_minimal' || $requestPath === '/cashier/deletecancel_minimal') {
    if (file_exists('pages/cashier/deletecancel_minimal.php')) {
        include_once 'pages/cashier/deletecancel_minimal.php';
        exit;
    }
}

// Special handle for trial balance (general ledger module)
if ($requestPath === '/generalledger/trialbalance') {
    if (!$isAuthenticated) {
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    
    // Check if it's an AJAX request (POST with action parameter)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // Route to the route file which handles AJAX
        if (file_exists('routes/generalledger/trialbalance.route.php')) {
            include_once 'routes/generalledger/trialbalance.route.php';
            exit;
        }
    } else {
        // Regular page load
        if (file_exists('pages/generalledger/trialbalance.php')) {
            include_once 'pages/generalledger/trialbalance.php';
            exit;
        }
    }
}

// Special handle for financial statement (general ledger module)
if ($requestPath === '/generalledger/financialstatement') {
    if (!$isAuthenticated) {
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    
    // Check if it's an AJAX request (POST with action parameter)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // Route to the route file which handles AJAX
        if (file_exists('routes/generalledger/financialstatement.route.php')) {
            include_once 'routes/generalledger/financialstatement.route.php';
            exit;
        }
    } else {
        // Regular page load
        if (file_exists('pages/generalledger/financialstatement.php')) {
            include_once 'pages/generalledger/financialstatement.php';
            exit;
        }
    }
}

// Special handle for fund configuration (general ledger module)
if ($requestPath === '/generalledger/fundconfiguration') {
    if (!$isAuthenticated) {
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    
    // Check if it's an AJAX request (POST with action parameter)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // Route to the route file which handles AJAX
        if (file_exists('routes/generalledger/fundconfiguration.route.php')) {
            include_once 'routes/generalledger/fundconfiguration.route.php';
            exit;
        }
    } else {
        // Regular page load
        if (file_exists('pages/generalledger/fundconfiguration.php')) {
            include_once 'pages/generalledger/fundconfiguration.php';
            exit;
        }
    }
}

// Special handle for data configuration (general ledger module)
if ($requestPath === '/generalledger/dataconfiguration') {
    if (!$isAuthenticated) {
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    
    // Check if it's an AJAX request (POST with action parameter)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // Route to the route file which handles AJAX
        if (file_exists('routes/generalledger/dataconfiguration.route.php')) {
            include_once 'routes/generalledger/dataconfiguration.route.php';
            exit;
        }
    } else {
        // Regular page load
        if (file_exists('pages/generalledger/dataconfiguration.php')) {
            include_once 'pages/generalledger/dataconfiguration.php';
            exit;
        }
    }
}

// Special handle for deletecancel complete version (self-contained)
if ($requestPath === '/deletecancel_complete' || $requestPath === '/cashier/deletecancel_complete') {
    if (file_exists('deletecancel_complete.php')) {
        include_once 'deletecancel_complete.php';
        exit;
    }
}

// Special handle for deletecancel (cashier module)
if ($requestPath === '/deletecancel' || $requestPath === '/cashier/deletecancel') {
    if (!$isAuthenticated) {
        // Redirect to login
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    if (file_exists('pages/cashier/deletecancel.php')) {
        include 'pages/cashier/deletecancel.php';
        exit;
    }
}

// Special handle for deletecancel working version
if ($requestPath === '/deletecancel_working' || $requestPath === '/cashier/deletecancel_working') {
    if (file_exists('pages/cashier/deletecancel_working.php')) {
        include_once 'pages/cashier/deletecancel_working.php';
        exit;
    }
}

// Special handle for deletecancel backup version
if ($requestPath === '/deletecancel_backup' || $requestPath === '/cashier/deletecancel_backup') {
    if (!$isAuthenticated) {
        // Redirect to login
        header('Location: ' . ($basePath ?: '') . '/login');
        exit;
    }
    if (file_exists('pages/cashier/deletecancel_backup.php')) {
        include_once 'pages/cashier/deletecancel_backup.php';
        exit;
    }
}

// Handle clean URLs for pages
// Check if the file exists in root directory first
if (file_exists(ltrim($requestPath, '/') . '.php')) {
    include_once ltrim($requestPath, '/') . '.php';
    exit;
}

// Then check in pages directory (remove /pages prefix if it exists)
$cleanPath = $requestPath;
if (strpos($requestPath, '/pages/') === 0) {
    $cleanPath = substr($requestPath, 6); // Remove '/pages' prefix
}
if (file_exists('pages' . $cleanPath . '.php')) {
    include_once 'pages' . $cleanPath . '.php';
    exit;
}

// Also try the original path in pages directory
if (file_exists('pages' . $requestPath . '.php')) {
    include_once 'pages' . $requestPath . '.php';
    exit;
}

// Check if it's a route file
if (strpos($requestPath, '/routes/') === 0) {
    $routeFile = ltrim($requestPath, '/');
    if (file_exists($routeFile)) {
        include_once $routeFile;
        exit;
    }
}

// Check if it's a process file
if (strpos($requestPath, '/process/') === 0) {
    $processFile = ltrim($requestPath, '/');
    if (file_exists($processFile)) {
        include_once $processFile;
        exit;
    }
}

// If still not found, show 404
http_response_code(404);
echo '<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #dc3545; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The page you are looking for does not exist.</p>
    <p><a href="' . ($basePath ?: '') . '/dashboard">Go to Dashboard</a></p>
</body>
</html>';
?>
