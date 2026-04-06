<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include_once("../../database/connection.php");

class SystemMaintenance extends Database
{
    private $logModuleId = 70; 

    // CONFIGURATION
    private $mysqldumpPath = "C:/xampp/mysql/bin/mysqldump.exe"; 
    private $db_name = "isynappdb";
    private $db_user = "root";
    private $db_pass = ""; 

    public function DownloadBackup() {
        // 1. SETUP RESOURCES
        // We set time limit to 0 (infinite) because large files take time.
        // We do NOT need a huge memory_limit because we are chunking.
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $date = date('Y-m-d_H-i-s');
        $baseFilename = $this->db_name . "_backup_" . $date;
        $tempDir = __DIR__ . "/../../assets/backups/";
        
        // Ensure folder exists
        if (!file_exists($tempDir)) { mkdir($tempDir, 0777, true); }

        $sqlPath = $tempDir . $baseFilename . ".sql";
        $zipPath = $tempDir . $baseFilename . ".zip";

        try {
            // =========================================================
            // STEP 1: DUMP TO FILE (Using your optimization flags)
            // =========================================================
            // --net_buffer_length=128K: Uses smaller packet sizes for stability
            // --compact: Makes the output smaller (less comments/whitespace)
            // --skip-lock-tables: Prevents locking up the DB during export
            
            $cmd = "\"{$this->mysqldumpPath}\" --host=localhost --user={$this->db_user}";
            if (!empty($this->db_pass)) {
                $cmd .= " --password={$this->db_pass}";
            }
            // The flags you requested + result file redirection
            $cmd .= " --net_buffer_length=128K --compact --skip-lock-tables {$this->db_name} > \"{$sqlPath}\"";

            // Execute (0 RAM usage for PHP)
            exec($cmd, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception("mysqldump failed with error code: $returnVar");
            }

            // =========================================================
            // STEP 2: COMPRESS (Disk-to-Disk)
            // =========================================================
            // We use ZipArchive::addFile(). This is memory efficient because 
            // it tells PHP "Go read this file from the disk", it does NOT 
            // load the file into a variable.
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $zip->addFile($sqlPath, $baseFilename . ".sql");
                $zip->close();
            } else {
                throw new Exception("Could not create ZIP file.");
            }

            // Clean up the raw SQL file immediately to free space
            if (file_exists($sqlPath)) unlink($sqlPath);

            // =========================================================
            // STEP 3: STREAM DOWNLOAD (The "Chunking" Strategy)
            // =========================================================
            // This is the most important part for saving RAM.
            // We do NOT use readfile(). We read 8KB chunks at a time.
            
            if (file_exists($zipPath)) {
                
                // Headers for download
                header('Content-Description: File Transfer');
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="'.basename($zipPath).'"');
                header('Content-Length: ' . filesize($zipPath));
                header('Pragma: public');
                header('Cache-Control: must-revalidate');
                header('Expires: 0');

                // Clear buffers to ensure clean stream
                if (ob_get_level()) ob_end_clean();
                flush();

                // Open file for reading
                $handle = fopen($zipPath, 'rb');
                
                // Read in 8KB chunks until the end of the file
                while (!feof($handle)) {
                    // Read 8192 bytes (8KB)
                    echo fread($handle, 8192);
                    // Send to browser immediately
                    flush();
                }
                
                fclose($handle);

                // Log the action
                $this->LogBackupAction(basename($zipPath));

                // Delete the zip file from server after user has it (Optional)
                // unlink($zipPath); 
                exit;
            } else {
                throw new Exception("ZIP file was not found.");
            }

        } catch (Exception $e) {
            // If something breaks, show error (turn off for production)
            die("Error: " . $e->getMessage());
        }
    }

    private function LogBackupAction($filename) {
        try {
            $userId = $_SESSION['ID'] ?? null;
            $this->LogActivity($userId, "EXPORT", $this->logModuleId, "Generated Backup ($filename)");
        } catch (Exception $e) {}
    }
}
?>