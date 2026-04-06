<?php
// process/administrator/download_stream.php

// 1. CONFIGURATION
// IMPORTANT: Use forward slashes (/) for the path.
// Check if your XAMPP is on C: or D: drive.
$mysqldumpPath = "C:/xampp/mysql/bin/mysqldump.exe"; 

$db_host = "localhost";
$db_user = "root";
$db_pass = ""; // Leave empty if you have no password

// UPDATE: Your specific database name
$db_name = "isynappdb"; 

// 2. SECURITY (Optional)
session_start();
if (!isset($_SESSION['AUTHENTICATED'])) { 
    die("Access Denied"); 
}

// 3. DISABLE TIMEOUTS (Critical for 500MB+ files)
set_time_limit(0); 

// 4. HEADERS (Force Browser Download)
$date = date('Y-m-d_H-i-s');
$filename = $db_name . "_backup_" . $date . ".sql";

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header('Expires: 0');
header('Pragma: public');

// 5. BUILD COMMAND
// --quick: Forces row-by-row retrieval
// --single-transaction: Prevents locking the table
// --column-statistics=0: Fixes common XAMPP error
$cmd = "\"$mysqldumpPath\" --host=$db_host --user=$db_user";

if (!empty($db_pass)) {
    $cmd .= " --password=$db_pass";
}

$cmd .= " --quick --single-transaction --column-statistics=0 $db_name";

// 6. STREAM OUTPUT
// This sends data straight to browser. 0 RAM usage.
passthru($cmd);
exit;
?>