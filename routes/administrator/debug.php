<?php
// 1. Force Error Display to MAX
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostic Test</h1>";

// 2. Define the paths we want to test
$path_to_process = __DIR__ . "/../../process/administrator/maintenanceprefix.process.php";
$path_to_db      = __DIR__ . "/../../database/connection.php";
$path_to_const   = __DIR__ . "/../../database/constants.php";

// 3. Test File Existence
echo "<p>Checking file locations...</p>";

if (file_exists($path_to_process)) {
    echo "<span style='color:green'>[OK] Process file found.</span><br>";
} else {
    echo "<span style='color:red'>[ERROR] Process file NOT found at: $path_to_process</span><br>";
}

if (file_exists($path_to_db)) {
    echo "<span style='color:green'>[OK] Connection file found.</span><br>";
} else {
    echo "<span style='color:red'>[ERROR] Connection file NOT found at: $path_to_db</span><br>";
}

if (file_exists($path_to_const)) {
    echo "<span style='color:green'>[OK] Constants file found.</span><br>";
} else {
    echo "<span style='color:red'>[ERROR] Constants file NOT found at: $path_to_const</span><br>";
}

// 4. Test Inclusion (This is where it usually crashes)
echo "<hr><p>Attempting to include Database...</p>";

try {
    if (file_exists($path_to_db)) {
        require_once($path_to_db);
        echo "<span style='color:green'>[OK] Database file included successfully.</span><br>";
        
        // 5. Test Class Instantiation
        if (class_exists('Database')) {
            $db = new Database();
            if (is_object($db->conn)) {
                echo "<span style='color:green'>[OK] Database Connected Successfully!</span>";
            } else {
                echo "<span style='color:red'>[ERROR] Database Class loaded, but connection failed: " . $db . "</span>";
            }
        } else {
            echo "<span style='color:red'>[ERROR] Class 'Database' not found. Check your class name in connection.php.</span>";
        }
    }
} catch (Throwable $e) {
    echo "<h2 style='color:red'>CRITICAL ERROR CAUGHT:</h2>";
    echo "<strong>" . $e->getMessage() . "</strong><br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine();
}
?>