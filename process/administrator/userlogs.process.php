<?php
include_once("../../database/connection.php");

class Process extends Database
{
    // ==========================================
    //  LOAD LOGS (With Dynamic Filtering)
    // ==========================================
    public function LoadLogs($data)
    {
        $startDate = $data['startDate'] ?? date('Y-m-d');
        $endDate   = $data['endDate'] ?? date('Y-m-d');
        $moduleId  = isset($data['moduleId']) ? $data['moduleId'] : '';
        $userId    = isset($data['userId']) ? $data['userId'] : '';
        $action    = isset($data['logAction']) ? $data['logAction'] : '';

        // UPDATED QUERY: Joins tbl_users to get the real Username
        $sql = "SELECT 
                    l.timestamp,
                    -- Priority: Active User Table Name > Saved Log Name > Default '-'
                    COALESCE(u.Username, l.username, '-') as username, 
                    IFNULL(m.module, 'Administrator') as module_name, 
                    l.action,
                    l.description,
                    l.ip_address
                FROM tbl_system_logs l
                LEFT JOIN tbl_maintenance_module m ON l.module_id = m.id_module
                LEFT JOIN tbl_users u ON l.user_id = u.ID 
                WHERE DATE(l.timestamp) BETWEEN ? AND ?";

        // Array to hold parameters for binding
        $params = ["ss", $startDate, $endDate];

        // --- Dynamic Filters ---
        if (!empty($moduleId)) {
            $sql .= " AND l.module_id = ?";
            $params[0] .= "i"; // Add integer type
            $params[] = intval($moduleId);
        }

        if (!empty($userId)) {
            $sql .= " AND l.user_id = ?";
            $params[0] .= "i";
            $params[] = intval($userId);
        }

        if (!empty($action)) {
            $sql .= " AND l.action = ?";
            $params[0] .= "s"; // Add string type
            $params[] = $action;
        }

        $sql .= " ORDER BY l.timestamp DESC";

        // Execute
        $stmt = $this->conn->prepare($sql);
        
        // Dynamic Binding using Splat Operator (...)
        // We remove the first element (type string) to bind correctly if needed, 
        // but bind_param requires the type string as the first arg.
        // A simple way for varying params in vanilla PHP:
        
        $bindNames[] = $params[0]; 
        for ($i = 1; $i < count($params); $i++) {
            $bindNames[] = &$params[$i];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bindNames);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $list = [];
        while ($row = $result->fetch_assoc()) {
            // Format Date for better readability
            $row['timestamp'] = date("M d, Y h:i A", strtotime($row['timestamp']));
            $list[] = $row;
        }
        
        echo json_encode(["LIST" => $list]);
        $stmt->close();
    }

    // ==========================================
    //  POPULATE DROPDOWNS
    // ==========================================
    public function GetFilterOptions()
    {
        $modules = [];
        $users = [];

        // 1. Get Modules (Only Top Level Modules type 0)
        $modSql = "SELECT id_module, module FROM tbl_maintenance_module WHERE module_type = 0 ORDER BY module ASC";
        $modRes = $this->conn->query($modSql);
        while ($row = $modRes->fetch_assoc()) {
            $modules[] = $row;
        }

        // 2. Get Users
        $userSql = "SELECT ID, Username FROM tbl_users ORDER BY Username ASC";
        $userRes = $this->conn->query($userSql);
        while ($row = $userRes->fetch_assoc()) {
            $users[] = $row;
        }

        echo json_encode([
            "MODULES" => $modules,
            "USERS"   => $users
        ]);
    }
}
?>