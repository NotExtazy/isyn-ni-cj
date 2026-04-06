<?php
// Master Configuration - All fixes merged
session_start();

// Define constants
define('APP_VERSION', '2.0.0');
define('APP_NAME', 'iSynergies Inc.');
define('APP_BASE_PATH', '/iSynApp-main');

// Unified session handling
function safeSessionStart() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Unified authentication check
function checkAuthentication() {
    if (!isset($_SESSION['EMPNO']) || !isset($_SESSION['USERNAME']) || !$_SESSION["AUTHENTICATED"]) {
        header("Location: login.php");
        exit;
    }
}

// Unified permissions (temporary bypass)
function createPermissions() {
    return new class {
        public function hasAccess($moduleId) {
            return true; // Allow all access for now
        }
    };
}

// Unified asset path helper
function assetPath($path) {
    return APP_BASE_PATH . '/' . ltrim($path, '/');
}

// Unified include helper
function safeInclude($file) {
    if (file_exists($file)) {
        include($file);
    } else {
        error_log("File not found: $file");
    }
}

// Auto-load session and authentication
safeSessionStart();
checkAuthentication();

// Create global permissions object
$GLOBALS['permissions'] = createPermissions();
?>
