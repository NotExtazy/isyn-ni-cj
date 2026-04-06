<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . "/../../database/connection.php");

class PrefixProcess extends Database
{
    public function LoadPrefixList(){
        $sql = "SELECT * FROM tbl_contactnum_prefixes ORDER BY prefix_type ASC, prefix_code ASC";
        $list = $this->SelectQuery($sql);
        echo json_encode(array("LIST" => $list));
    }

    public function GetPrefixInfo($data){
        $id = intval($data['id']);
        $stmt = $this->conn->prepare("SELECT * FROM tbl_contactnum_prefixes WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode(array("INFO" => $row, "STATUS" => "success"));
        } else {
            echo json_encode(array("STATUS" => "error", "MESSAGE" => "Record not found"));
        }
    }

    public function SavePrefix($data){
        try {
            $raw_input = isset($data['prefix_code']) ? trim($data['prefix_code']) : '';

            $network = '';
            if(isset($data['network_name']) && !empty($data['network_name'])) $network = strtoupper(trim($data['network_name']));
            elseif(isset($data['network']) && !empty($data['network'])) $network = strtoupper(trim($data['network']));

            $category = '';
            if(isset($data['prefix_type']) && !empty($data['prefix_type'])) $category = strtoupper(trim($data['prefix_type']));
            elseif(isset($data['category']) && !empty($data['category'])) $category = strtoupper(trim($data['category']));

            $status = 1;

            if (empty($raw_input)) throw new Exception("Prefix Code is empty.");
            if (empty($network)) throw new Exception("Network is empty.");
            if (empty($category)) throw new Exception("Category/Type is empty.");

            $prefixes = preg_split('/[\s,]+/', $raw_input, -1, PREG_SPLIT_NO_EMPTY);

            $added_count = 0;
            $skipped_count = 0;
            $errors = [];

            $this->conn->autocommit(false);

            $checkStmt = $this->conn->prepare("SELECT id FROM tbl_contactnum_prefixes WHERE prefix_code = ?");
            $insertStmt = $this->conn->prepare("INSERT INTO tbl_contactnum_prefixes (prefix_code, network_name, prefix_type, status) VALUES (?, ?, ?, ?)");

            foreach ($prefixes as $code) {
                $code = trim($code);
                if (!ctype_digit($code)) { $skipped_count++; continue; }

                $checkStmt->bind_param("s", $code);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $skipped_count++;
                } else {
                    $insertStmt->bind_param("sssi", $code, $network, $category, $status);
                    if ($insertStmt->execute()) {
                        $added_count++;
                    } else {
                        $errors[] = $insertStmt->error;
                    }
                }
            }

            $this->conn->commit();
            $this->conn->autocommit(true);
            $checkStmt->close();
            $insertStmt->close();

            $msg = "Added: $added_count, Skipped: $skipped_count";
            if ($added_count == 0 && $skipped_count > 0) {
                echo json_encode(array("STATUS" => "error", "MESSAGE" => "No new prefixes added. All entries were duplicates."));
            } elseif(count($errors) > 0) {
                echo json_encode(array("STATUS" => "error", "MESSAGE" => "Some errors occurred: " . implode(", ", $errors)));
            } else {
                echo json_encode(array("STATUS" => "success", "MESSAGE" => $msg));
            }
        } catch (Exception $e) {
            if($this->conn) $this->conn->rollback();
            echo json_encode(array("STATUS" => "error", "MESSAGE" => $e->getMessage()));
        }
    }

    public function UpdatePrefix($data){
        try {
            $this->conn->autocommit(false);
            $id = intval($data['id']);
            $type = isset($data['prefix_type']) ? strtoupper(trim($data['prefix_type'])) : '';
            $code = isset($data['prefix_code']) ? trim($data['prefix_code']) : '';
            $network = isset($data['network_name']) ? strtoupper(trim($data['network_name'])) : '';

            $stmt = $this->conn->prepare("UPDATE tbl_contactnum_prefixes SET prefix_type=?, prefix_code=?, network_name=? WHERE id=?");
            $stmt->bind_param('sssi', $type, $code, $network, $id);

            if(!$stmt->execute()) throw new Exception($stmt->error);

            $this->conn->commit();
            $this->conn->autocommit(true);
            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Prefix Updated Successfully"));
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "error", "MESSAGE" => $e->getMessage()));
        }
    }

    public function UpdateStatus($data){
        try {
            $id = intval($data['id']);
            $status = intval($data['status']);

            $stmt = $this->conn->prepare("UPDATE tbl_contactnum_prefixes SET status = ? WHERE id = ?");
            $stmt->bind_param('ii', $status, $id);

            if($stmt->execute()){
                echo json_encode(array("STATUS" => "success", "MESSAGE" => "Status Updated"));
            } else {
                echo json_encode(array("STATUS" => "error", "MESSAGE" => "Update Failed"));
            }
        } catch (Exception $e) {
            echo json_encode(array("STATUS" => "error", "MESSAGE" => $e->getMessage()));
        }
    }

    public function GetValidPrefixes(){
        $mobile = [];
        $landline = [];
        $sql = "SELECT prefix_type, prefix_code FROM tbl_contactnum_prefixes WHERE status = 1";
        $res = $this->conn->query($sql);
        if($res){
            while($row = $res->fetch_assoc()){
                if(strtoupper($row['prefix_type']) == 'MOBILE'){
                    $mobile[] = $row['prefix_code'];
                } else {
                    $landline[] = $row['prefix_code'];
                }
            }
        }
        echo json_encode(array("MOBILE_PREFIXES" => $mobile, "LANDLINE_PREFIXES" => $landline));
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