<?php
include_once("../../database/connection.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class BackupProcess extends Database
{
    // A temporary folder to store progress files
    private $progressDir = '../../temp_backup_progress/';

    public function __construct() {
        parent::__construct();
        if (!file_exists($this->progressDir)) {
            mkdir($this->progressDir, 0777, true);
        }
    }

    public function StartBackup() {
        // Generate a unique ID for this backup process
        $backupId = uniqid('backup_');
        
        // Initialize progress
        $this->updateProgress($backupId, 0, 'Initializing backup process...');
        
        // Return the ID to the client so they can poll
        echo json_encode(['status' => 'started', 'backup_id' => $backupId]);
        
        // Note: In a real async environment (like Node.js or with queues), we would trigger a background worker.
        // In PHP standard request-response, we can't easily "return" and "keep working" without specific setups (like fastcgi_finish_request).
        // For this implementation, we will use a separate AJAX call to 'PerformBackup' which will actually do the work,
        // while the client polls 'GetProgress'.
    }

    public function PerformBackup($backupId) {
        // Increase time limit for large databases
        set_time_limit(600); 
        
        $sqlFilename = 'isynappdb_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $zipFilename = 'isynappdb_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $tempSqlPath = $this->progressDir . $sqlFilename;
        $tempZipPath = $this->progressDir . $zipFilename;

        try {
            // Step 1: Dump Database
            $this->updateProgress($backupId, 10, 'Locating mysqldump...');
            
            $dumpPath = 'mysqldump'; 
            $possiblePaths = [
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\xamppp\\mysql\\bin\\mysqldump.exe', 
                'D:\\xampp\\mysql\\bin\\mysqldump.exe'
            ];
            
            foreach($possiblePaths as $path) {
                if (file_exists($path)) {
                    $dumpPath = '"' . $path . '"';
                    break;
                }
            }

            $host = HOST;
            $user = USER;
            $pass = PASS;
            $db = DB;
            $passStr = ($pass !== '') ? " -p$pass" : "";

            $this->updateProgress($backupId, 20, 'Exporting database...');
            
            // Execute mysqldump to a temporary file
            $command = "$dumpPath --host=$host --user=$user $passStr --databases $db --add-drop-database --add-drop-table --routines --events --result-file=\"$tempSqlPath\"";
            
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new Exception("mysqldump failed with exit code $returnVar");
            }

            if (!file_exists($tempSqlPath)) {
                throw new Exception("SQL dump file was not created.");
            }

            // Step 2: Compress to ZIP (Optional - fallback to SQL if zip missing)
            $this->updateProgress($backupId, 60, 'Compressing file...');
            
            $finalFile = $sqlFilename; // Default to SQL if zip fails/skipped
            
            if (class_exists('ZipArchive')) {
                try {
                    $zip = new ZipArchive();
                    $res = $zip->open($tempZipPath, ZipArchive::CREATE);
                    if ($res === TRUE) {
                        if ($zip->addFile($tempSqlPath, $sqlFilename)) {
                            $zip->close();
                            // If successful, update final file to zip and remove sql
                            $finalFile = $zipFilename;
                            unlink($tempSqlPath); 
                        } else {
                            // Failed to add, just use SQL
                            $zip->close(); 
                            if(file_exists($tempZipPath)) unlink($tempZipPath);
                        }
                    }
                } catch (Exception $zipErr) {
                    // Log error but continue with SQL file
                    // error_log("Zip failed: " . $zipErr->getMessage());
                }
            } else {
                // ZipArchive not found, skip compression
                $this->updateProgress($backupId, 70, 'Zip extension missing, skipping compression...');
            }

            $this->updateProgress($backupId, 90, 'Finalizing...');

            // Step 3: Done
            $this->updateProgress($backupId, 100, 'Backup complete!', $finalFile);

        } catch (Exception $e) {
            $this->updateProgress($backupId, -1, 'Error: ' . $e->getMessage());
        }
    }

    public function DownloadFile($filename) {
        $filePath = $this->progressDir . basename($filename);
        
        if (file_exists($filePath)) {
            // Determine MIME type based on extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if ($ext === 'zip') {
                header('Content-Type: application/zip');
            } else {
                header('Content-Type: application/sql');
            }
            
            header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
            header('Content-Length: ' . filesize($filePath));
            
            readfile($filePath);
            exit;
        } else {
            http_response_code(404);
            echo "File not found.";
        }
    }

    public function GetProgress($backupId) {
        $statusFile = $this->progressDir . $backupId . '.json';
        if (file_exists($statusFile)) {
            echo file_get_contents($statusFile);
        } else {
            echo json_encode(['percent' => 0, 'message' => 'Waiting...']);
        }
    }

    private function updateProgress($backupId, $percent, $message, $downloadFile = null) {
        $status = [
            'percent' => $percent,
            'message' => $message,
            'download_file' => $downloadFile
        ];
        file_put_contents($this->progressDir . $backupId . '.json', json_encode($status));
    }
}
?>