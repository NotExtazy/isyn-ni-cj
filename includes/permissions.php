<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check if Database class exists, if not include it
if (!class_exists('Database')) {
    // Determine path based on current script location provided by __DIR__
    // This file is in /includes, so we go up one level then to /database
    $dbPath = __DIR__ . '/../database/connection.php';
    if(file_exists($dbPath)){
        require_once($dbPath);
    }
}

class Permissions {
    private $conn;
    private $userPermissions = [];
    private $roleId = 0;

    public function __construct($dbConnection = null) {
        if ($dbConnection) {
            $this->conn = $dbConnection;
        } else {
            // Instantiate Database class to get connection
            if(class_exists('Database')){
                $db = new Database();
                $this->conn = $db->conn;
            } else {
                // Fallback or error logging
                error_log("Permissions Error: Database class not found.");
                return;
            }
        }

        $this->roleId = isset($_SESSION['role_id']) ? intval($_SESSION['role_id']) : 0;
        
        // If role_id not in session (e.g. fresh login or session lost), try to fetch it if user ID exists
        if($this->roleId === 0 && isset($_SESSION['ID'])){
            $uid = intval($_SESSION['ID']);
            if($this->conn){
                $stmt = $this->conn->prepare("SELECT Role FROM tbl_users WHERE ID = ?");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $res = $stmt->get_result();
                if($r = $res->fetch_assoc()){
                    // Convert Role string to role_id for compatibility
                    $roleName = $r['Role'];
                    $this->roleId = $this->getRoleIdByName($roleName);
                    $_SESSION['role_id'] = $this->roleId;
                    $_SESSION['role_name'] = $roleName;
                }
                $res->free();
                $stmt->close();
            }
        }

        $this->loadPermissions();
    }

    private function loadPermissions() {
        if($this->roleId > 0) {
            // REAL-TIME SYNC: Bypass session cache to ensure immediate updates
            // ignored: if(isset($_SESSION['USER_PERMISSIONS']) ... )

            if($this->conn){
                $perms = [];
                
                // Check if table exists first to avoid Fatal Error
                $checkTable = $this->conn->query("SHOW TABLES LIKE 'tbl_role_permissions'");
                if ($checkTable && $checkTable->num_rows > 0) {
                    $stmt2 = $this->conn->prepare("SELECT module_id FROM tbl_role_permissions WHERE role_id = ? AND can_access = 1");
                    if(!$stmt2){
                        error_log("Permissions::loadPermissions prepare failed: " . $this->conn->error);
                    } else {
                        $stmt2->bind_param("i", $this->roleId);
                        $stmt2->execute();
                        $result = $stmt2->get_result();
                        while($row = $result->fetch_assoc()){
                            $perms[] = intval($row['module_id']);
                        }
                        $result->free();
                        $stmt2->close();
                    }
                } else {
                    // FALLBACK: If table doesn't exist, grant access to everything for development/migration
                    // This prevents the Fatal Error until the table is created
                    return $this->grantAllAccess();
                }
                
                $this->userPermissions = $perms;
                
                // Update session just in case other scripts use it, but we won't rely on it here
                $_SESSION['USER_PERMISSIONS'] = $perms;
                $_SESSION['USER_PERMISSIONS_ROLE'] = $this->roleId;
            }
        }
    }

    private function grantAllAccess() {
        // Grant a range of module IDs to ensure functionality
        $this->userPermissions = range(1, 100); 
        $_SESSION['USER_PERMISSIONS'] = $this->userPermissions;
        return true;
    }

    public function hasAccess($moduleId) {
        // If Role ID is 0 (Guest) -> No Access (unless specific modules allowed?)
        // If Admin is somehow Role ID X but has access to everything, it should be in DB.
        return in_array(intval($moduleId), $this->userPermissions);
    }

    public function checkAccessByUrl($currentPath) {
        if (!$this->conn) return false;

        // Check if table exists first
        $checkTable = $this->conn->query("SHOW TABLES LIKE 'tbl_modules'");
        if (!$checkTable || $checkTable->num_rows == 0) {
            return true; // Bypass check if module table doesn't exist
        }

        // Find module ID for this path
        // We use REPLACE to normalize paths by removing '.php', ensuring matches regardless of extension presence in URL or DB
        // Query: check if "Input(NoExt)" matches "Route(NoExt)"
        // Note: Using ? LIKE CONCAT('%', ...) allows partial suffix matching (e.g. /pages/admin/foo matching admin/foo)
        $sql = "SELECT id_module, route_url FROM tbl_maintenance_module 
                WHERE route_url IS NOT NULL 
                AND route_url != '' 
                AND REPLACE(?, '.php', '') LIKE CONCAT('%', REPLACE(route_url, '.php', '')) 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return true; // Fail open on DB error

        $stmt->bind_param("s", $currentPath);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $moduleId = $row['id_module'];
            // $matchedRoute = $row['route_url'];
            // error_log("  -> Matched Module ID: $moduleId (Route: $matchedRoute)");
            
            // If module found, enforce permissions
            // $access = $this->hasAccess($moduleId);
            // error_log("  -> Permission Check for Role " . $this->roleId . ": " . ($access ? 'GRANTED' : 'DENIED'));
            // return $access;
            return $this->hasAccess($moduleId);
        }

        // error_log("  -> NO MATCH found in DB for path: $currentPath");
        // If no route defined for this page, allow access (default allow for unrouted pages)
        return true;
    }
    
    public function getRoleId() {
        return $this->roleId;
    }
    
    private function getRoleIdByName($roleName) {
        // Convert role names to numeric IDs for compatibility
        $roleMap = [
            'ADMIN' => 1,
            'ADMINISTRATOR' => 1,
            'ISYN-ADMIN' => 1,
            'MANAGER' => 2,
            'ISYN-MANAGER' => 2,
            'SUPERVISOR' => 3,
            'ISYN-SUPERVISOR' => 3,
            'BOOKKEEPER' => 4,
            'ISYN-BOOKKEEPER' => 4,
            'STAFF' => 5,
            'ISYN-STAFF' => 5,
            'OJT' => 6,
            'ISYN-OJT' => 6
        ];
        
        // Return mapped role ID or default to 4 (bookkeeper)
        return isset($roleMap[strtoupper($roleName)]) ? $roleMap[strtoupper($roleName)] : 4;
    }
}
?>
