<?php
$dbPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/database/connection.php';
if (file_exists($dbPath)) {
    include_once($dbPath);
} else {
    include_once("../../database/connection.php");
}

class FundConfigurationProcess extends Database {
    
    public function LoadPage() {
        try {
            // Get all AMS funds from tbl_maintenance_module
            $sqlAms = "SELECT module AS fundname 
                       FROM tbl_maintenance_module 
                       WHERE module_no = 1691 AND status = 1 
                       ORDER BY module";
            $amsFunds = $this->SelectQuery($sqlAms);

            // Get configured GL funds from tbl_glfunds
            $sqlGl = "SELECT fundname FROM tbl_glfunds ORDER BY fundname";
            $glFunds = $this->SelectQuery($sqlGl);

            return [
                'STATUS' => 'SUCCESS',
                'AMS_FUNDS' => $amsFunds,
                'GL_FUNDS' => $glFunds
            ];
        } catch (Exception $e) {
            return [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Error loading funds: ' . $e->getMessage()
            ];
        }
    }

    public function SaveConfiguration($funds) {
        try {
            if (empty($funds) || !is_array($funds)) {
                return [
                    'STATUS' => 'ERROR',
                    'MESSAGE' => 'No funds provided'
                ];
            }

            // Start transaction
            $this->conn->begin_transaction();

            // Clear existing GL funds
            $sqlDelete = "DELETE FROM tbl_glfunds";
            $this->conn->query($sqlDelete);

            // Insert new GL funds
            $stmt = $this->conn->prepare("INSERT INTO tbl_glfunds (fundname) VALUES (?)");
            
            foreach ($funds as $fundName) {
                $stmt->bind_param("s", $fundName);
                $stmt->execute();
            }

            $stmt->close();

            // Commit transaction
            $this->conn->commit();

            return [
                'STATUS' => 'SUCCESS',
                'MESSAGE' => 'Fund configuration saved successfully'
            ];
        } catch (Exception $e) {
            // Rollback on error
            if ($this->conn) {
                $this->conn->rollback();
            }
            
            return [
                'STATUS' => 'ERROR',
                'MESSAGE' => 'Error saving configuration: ' . $e->getMessage()
            ];
        }
    }
}
?>
