<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once("../../database/connection.php");

class AddressProcess extends Database
{
    // CONFIGURATION: Set this to the ID of 'Address Maintenance' in your DB
    // private $logModuleId = 99; // REMOVED: Now logging as System (0) 

    // 1. GET REGIONS (Standardized to return LIST)
    public function GetRegions(){
        try {
            $sql = "SELECT DISTINCT REGION AS Region FROM tbl_locate ORDER BY REGION ASC";
            $list = $this->SelectQuery($sql);
            
            // Debug: If list is empty, this message will help us know
            if(empty($list)) {
                echo json_encode(array("region" => [], "DEBUG" => "Query success but no regions found in DB"));
            } else {
                echo json_encode(array("region" => $list));
            }
        } catch (Exception $e) {
            echo json_encode(array("region" => [], "ERROR" => $e->getMessage()));
        }
    }

    // 2. GET PROVINCES (Active Only by Default)
    public function GetProvinces($data){
        $region = $data['filter'];
        $statusClause = isset($data['admin_mode']) ? "" : "AND status = 1"; 
        
        $sql = "SELECT DISTINCT PROVINCE AS Province FROM tbl_locate WHERE REGION = ? $statusClause ORDER BY PROVINCE ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $region);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) { $list[] = $row; }
        echo json_encode(array("LIST" => $list));
    }

    // 3. GET CITIES (Active Only by Default)
    public function GetCities($data){
        $province = $data['filter'];
        $statusClause = isset($data['admin_mode']) ? "" : "AND status = 1";

        $sql = "SELECT DISTINCT MUNICIPALITY AS CityTown FROM tbl_locate WHERE PROVINCE = ? $statusClause ORDER BY MUNICIPALITY ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $province);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) { $list[] = $row; }
        echo json_encode(array("LIST" => $list));
    }
    
    // 4. GET BARANGAYS (Active Only by Default)
    public function GetBarangays($data){
        $city = $data['filter'];
        $statusClause = isset($data['admin_mode']) ? "" : "AND status = 1";

        $sql = "SELECT DISTINCT BARANGAY FROM tbl_locate WHERE MUNICIPALITY = ? $statusClause ORDER BY BARANGAY ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $city);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) { $list[] = $row; }
        echo json_encode(array("LIST" => $list));
    }

    // --- ADMIN TABLE FUNCTIONS ---
    public function LoadAddressList(){
        // Status 1 = Active, 0 = Inactive
        $sql = "SELECT Id AS id_barangay, REGION AS Region, PROVINCE AS Province, 
                MUNICIPALITY AS CityTown, BARANGAY AS Barangay, status 
                FROM tbl_locate ORDER BY Id DESC";
        $list = $this->SelectQuery($sql);
        echo json_encode(array("LIST" => $list ?? []));
    }

    public function GetAddressInfo($data){
        $id = $data['id_barangay'];
        $stmt = $this->conn->prepare("SELECT Id AS id_barangay, REGION AS Region, PROVINCE AS Province, 
                                      MUNICIPALITY AS CityTown, BARANGAY AS Barangay, status 
                                      FROM tbl_locate WHERE Id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(array("INFO" => $result->fetch_assoc(), "STATUS" => "LOADED"));
        } else {            
            echo json_encode(array("STATUS" => "EMPTY"));
        }
        $stmt->close();
    }

    // ==========================================
    //  SAVE ADDRESS (With Logs)
    // ==========================================
    public function SaveInfo($data){
        try {
            $this->conn->autocommit(false);
            $Region = mb_strtoupper(trim($data['Region']), 'UTF-8');
            $Province = mb_strtoupper(trim($data['Province']), 'UTF-8');
            $Municipality = mb_strtoupper(trim($data['CityTown']), 'UTF-8');
            $Barangay = mb_strtoupper(trim($data['Barangay']), 'UTF-8'); 
            
            // Check Duplicate
            $stmtCheck = $this->conn->prepare("SELECT Id FROM tbl_locate WHERE MUNICIPALITY = ? AND PROVINCE = ? AND BARANGAY = ?");
            $stmtCheck->bind_param('sss', $Municipality, $Province, $Barangay);
            $stmtCheck->execute();
            if($stmtCheck->get_result()->num_rows > 0){ throw new Exception("Address already exists."); }
            $stmtCheck->close();

            // Insert with status = 1 (Active)
            $stmt = $this->conn->prepare("INSERT INTO tbl_locate (REGION, PROVINCE, MUNICIPALITY, BARANGAY, status) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param('ssss', $Region, $Province, $Municipality, $Barangay);
            
            if ($stmt->execute()){
                // --- LOGGING ---
                $userId = $_SESSION['ID'] ?? null;
                $desc = "Added Address: $Barangay, $Municipality, $Province (Parent: Address Maintenance)";
                $this->LogActivity($userId, "INSERT", 0, $desc);
                // ----------------

                $this->conn->commit();
                echo json_encode(array("STATUS" => "success", "MESSAGE" => "Address added."));
            } else { throw new Exception($stmt->error); }
            $stmt->close();
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    // ==========================================
    //  UPDATE STATUS (With Logs)
    // ==========================================
    public function UpdateStatus($data){
        try {
            $id = intval($data['id']);
            $status = intval($data['status']); 
            
            // 1. Fetch Info for Log
            $addressInfo = "Unknown ID $id";
            $getStmt = $this->conn->prepare("SELECT MUNICIPALITY, BARANGAY FROM tbl_locate WHERE Id = ?");
            $getStmt->bind_param("i", $id);
            $getStmt->execute();
            $res = $getStmt->get_result();
            if($row = $res->fetch_assoc()){ 
                $addressInfo = $row['BARANGAY'] . ", " . $row['MUNICIPALITY']; 
            }
            $getStmt->close();

            // 2. Update
            $stmt = $this->conn->prepare("UPDATE tbl_locate SET status = ? WHERE Id = ?");
            $stmt->bind_param('ii', $status, $id);
            
            if($stmt->execute()){
                // --- LOGGING ---
                $userId = $_SESSION['ID'] ?? null;
                $statusText = ($status == 1) ? "Active" : "Inactive";
                $desc = "Set status of Address '$addressInfo' to $statusText (Parent: Address Maintenance)";
                $this->LogActivity($userId, "UPDATE", 0, $desc);
                // ----------------

                echo json_encode(array("STATUS" => "success", "MESSAGE" => "Status updated."));
            } else { throw new Exception("Update failed."); }
            $stmt->close();
        } catch (Exception $e) { echo json_encode(array("STATUS" => "error", "MESSAGE" => $e->getMessage())); }
    }

    // ==========================================
    //  UPDATE INFO (With Logs)
    // ==========================================
    public function UpdateInfo($data){
        try {
            $this->conn->autocommit(false);
            $id = $data['id_barangay']; 
            
            $Region = mb_strtoupper(trim($data['Region']), 'UTF-8');
            $Province = mb_strtoupper(trim($data['Province']), 'UTF-8');
            $Municipality = mb_strtoupper(trim($data['CityTown']), 'UTF-8');
            $Barangay = mb_strtoupper(trim($data['Barangay']), 'UTF-8');

            $stmt = $this->conn->prepare("UPDATE tbl_locate SET REGION=?, PROVINCE=?, MUNICIPALITY=?, BARANGAY=? WHERE Id=?");
            $stmt->bind_param('ssssi', $Region, $Province, $Municipality, $Barangay, $id);

            if ($stmt->execute()){
                // --- LOGGING ---
                $userId = $_SESSION['ID'] ?? null;
                $desc = "Updated Address Details: $Barangay, $Municipality (Parent: Address Maintenance)";
                $this->LogActivity($userId, "UPDATE", 0, $desc);
                // ----------------

                $this->conn->commit();
                echo json_encode(array("STATUS" => "success", "MESSAGE" => "Address updated successfully."));
            } else { throw new Exception($stmt->error); }
            $stmt->close();
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    private function SelectQuery($string){
        $data = [];
        $stmt = $this->conn->prepare($string);
        if($stmt){
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) { $data[] = $row; }
            $stmt->close();
        }
        return $data;
    }
}
?>