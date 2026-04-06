<?php
include_once(__DIR__ . "/../../database/connection.php");
require_once(__DIR__ . "/../../includes/permissions.php");

class ConfigAccounts extends Database {

    private $permissions;

    public function __construct() {
        parent::__construct();
        // Initialize Permissions
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        $userId = $_SESSION['ID'] ?? 0;
        $this->permissions = new Permissions($this->conn);
    }

    // =====================================================================
    //  USERS MANAGEMENT
    // =====================================================================

    public function LoadUsers() {
        $data = [];
        $sql = "SELECT u.ID, u.Username, u.Owner as FullName, u.Status, u.role_id, u.Password, r.role_name 
                FROM tbl_users u 
                LEFT JOIN tbl_roles r ON u.role_id = r.id 
                ORDER BY u.Username ASC";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_assoc()){
            $data[] = $row;
        }
        echo json_encode(['STATUS' => 'success', 'DATA' => $data]);
    }

    public function SaveUser($data) {
        try {
            $id = intval($data['userId']);
            $username = trim($data['username']);
            $fullname = trim($data['fullname']);
            $password = trim($data['password']);
            $roleId = intval($data['roleSelect']);
            // Convert status to legacy format if needed, or keep consistent
            // Existing login uses 'ENABLED', new plan proposed '1/0'. Let's stick to 'ENABLED'/'DISABLED' to match login.process.php
            // $status = $data['userStatus']; // Removed from form

            if($username == "" || $fullname == "" || $roleId <= 0) {
                throw new Exception("Missing required fields.");
            }

            if($id == 0) {
                // INSERT
                if($password == "") throw new Exception("Password is required for new users.");
                
                // Check dup
                $chk = $this->conn->prepare("SELECT ID FROM tbl_users WHERE Username = ?");
                $chk->bind_param("s", $username);
                $chk->execute();
                if($chk->get_result()->num_rows > 0) throw new Exception("Username already exists.");
                $chk->close();

                // Default status to ENABLED for new users
                $status = 'ENABLED';
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->conn->prepare("INSERT INTO tbl_users (Username, Password, Owner, role_id, Status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssis", $username, $hashed_password, $fullname, $roleId, $status);
                if(!$stmt->execute()) throw new Exception($stmt->error);
                
                $this->LogActivity($_SESSION['ID'], "INSERT", 0, "Created User: $username");
                echo json_encode(['STATUS' => 'success', 'MESSAGE' => 'User created successfully']);

            } else {
                // UPDATE
                // Check dup
                $chk = $this->conn->prepare("SELECT ID FROM tbl_users WHERE Username = ? AND ID != ?");
                $chk->bind_param("si", $username, $id);
                $chk->execute();
                if($chk->get_result()->num_rows > 0) throw new Exception("Username already exists.");
                $chk->close();

                if($password !== "") {
                    // Update with password - Status is NOT updated here
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $this->conn->prepare("UPDATE tbl_users SET Username=?, Password=?, Owner=?, role_id=? WHERE ID=?");
                    $stmt->bind_param("sssii", $username, $hashed_password, $fullname, $roleId, $id);
                } else {
                    // Update without password
                    $stmt = $this->conn->prepare("UPDATE tbl_users SET Username=?, Owner=?, role_id=? WHERE ID=?");
                    $stmt->bind_param("ssii", $username, $fullname, $roleId, $id);
                }
                
                if(!$stmt->execute()) throw new Exception($stmt->error);

                $this->LogActivity($_SESSION['ID'], "UPDATE", 0, "Updated User: $username");
                echo json_encode(['STATUS' => 'success', 'MESSAGE' => 'User updated successfully']);
            }

        } catch (Exception $e) {
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => $e->getMessage()]);
        }
    }

    // =====================================================================
    //  ROLES MANAGEMENT
    // =====================================================================

    public function LoadRoles() {
        $data = [];
        $sql = "SELECT * FROM tbl_roles ORDER BY role_name ASC";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_assoc()){
            $data[] = $row;
        }
        echo json_encode(['STATUS' => 'success', 'DATA' => $data]);
    }

    public function SaveRole($data) {
        try {
            $id = intval($data['roleId']);
            $name = trim($data['roleName']);
            $desc = trim($data['roleDesc']);
            
            if($name == "") throw new Exception("Role Name is required.");

            if($id == 0) {
                // INSERT
                $chk = $this->conn->prepare("SELECT id FROM tbl_roles WHERE role_name = ?");
                $chk->bind_param("s", $name);
                $chk->execute();
                if($chk->get_result()->num_rows > 0) throw new Exception("Role Name already exists.");
                
                // Insert with default status 1 (Active) if not exists, or database default. 
                // Table schema isn't fully visible but let's assume valid default or we set it if column exists.
                // Looking at LoadRoles, there IS a status column.
                // Let's set it to 1 by default.
                $stmt = $this->conn->prepare("INSERT INTO tbl_roles (role_name, description, status) VALUES (?, ?, 1)");
                $stmt->bind_param("ss", $name, $desc);
                if(!$stmt->execute()) throw new Exception($stmt->error);

                $this->LogActivity($_SESSION['ID'], "INSERT", 0, "Created Role: $name");
                echo json_encode(['STATUS' => 'success', 'MESSAGE' => 'Role created successfully']);

            } else {
                // UPDATE
                $chk = $this->conn->prepare("SELECT id FROM tbl_roles WHERE role_name = ? AND id != ?");
                $chk->bind_param("si", $name, $id);
                $chk->execute();
                if($chk->get_result()->num_rows > 0) throw new Exception("Role Name already exists.");

                $stmt = $this->conn->prepare("UPDATE tbl_roles SET role_name=?, description=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $desc, $id);
                if(!$stmt->execute()) throw new Exception($stmt->error);

                $this->LogActivity($_SESSION['ID'], "UPDATE", 0, "Updated Role: $name");
                echo json_encode(['STATUS' => 'success', 'MESSAGE' => 'Role updated successfully']);
            }
        } catch (Exception $e) {
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => $e->getMessage()]);
        }
    }

    // =====================================================================
    //  PERMISSIONS
    // =====================================================================

    public function GetRolePermissions($data) {
        $roleId = intval($data['roleId']);
        
        // 1. Get all modules structure
        $modules = [];
        // Top Level
        $res = $this->conn->query("SELECT * FROM tbl_maintenance_module WHERE module_type = 0 ORDER BY module ASC");
        while($row = $res->fetch_assoc()){
            $mod = [
                'id' => $row['id_module'],
                'name' => $row['module'],
                'type' => 'module',
                'children' => [],
                'checked' => false
            ];
            
            // Submodules
            $subRes = $this->conn->query("SELECT * FROM tbl_maintenance_module WHERE module_type = 1 AND module_no = " . $row['id_module'] . " ORDER BY module ASC");
            while($subRow = $subRes->fetch_assoc()){
                $sub = [
                    'id' => $subRow['id_module'],
                    'name' => $subRow['module'],
                    'type' => 'submodule',
                    'checked' => false
                ];
                $mod['children'][] = $sub;
            }
            $modules[] = $mod;
        }

        // 2. Get Permissions for this Role
        $perms = [];
        $pRes = $this->conn->query("SELECT module_id FROM tbl_role_permissions WHERE role_id = $roleId AND can_access = 1");
        while($r = $pRes->fetch_assoc()){
            $perms[] = intval($r['module_id']);
        }

        // 3. Mark checked
        foreach($modules as &$m){
            if(in_array($m['id'], $perms)) $m['checked'] = true;
            foreach($m['children'] as &$s){
                if(in_array($s['id'], $perms)) $s['checked'] = true;
            }
        }

        echo json_encode(['STATUS' => 'success', 'DATA' => $modules]);
    }

    public function SaveRolePermissions($data) {
        try {
            $roleId = intval($data['roleId']);
            $moduleIds = isset($data['moduleIds']) ? $data['moduleIds'] : []; // Array of IDs

            if($roleId <= 0) throw new Exception("Invalid Role ID");

            $this->conn->autocommit(false);

            // 1. Clear existing permissions for this role
            $this->conn->query("DELETE FROM tbl_role_permissions WHERE role_id = $roleId");

            // 2. Insert new permissions
            if(!empty($moduleIds)){
                $stmt = $this->conn->prepare("INSERT INTO tbl_role_permissions (role_id, module_id, can_access) VALUES (?, ?, 1)");
                foreach($moduleIds as $mid){
                    $mid = intval($mid);
                    $stmt->bind_param("ii", $roleId, $mid);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $this->conn->commit();
            $this->conn->autocommit(true);

            $this->LogActivity($_SESSION['ID'], "UPDATE", 0, "Updated Permissions for Role ID: $roleId");
            echo json_encode(['STATUS' => 'success', 'MESSAGE' => 'Permissions updated successfully']);

        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => $e->getMessage()]);
        }
    }
    public function checkAccess() {
        // Module ID 164: Config Accounts
        if (!$this->permissions->hasAccess(164)) {
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => 'Access Denied: You do not have permission to access Config Accounts.']);
            exit;
        }
    }

    public function ToggleUserStatus($data) {
        try {
            $id = intval($data['id']);
            $status = $data['status']; // 'ENABLED' or 'DISABLED'

            if($id <= 0) throw new Exception("Invalid User ID");
            
            $stmt = $this->conn->prepare("UPDATE tbl_users SET Status = ? WHERE ID = ?");
            $stmt->bind_param("si", $status, $id);
            if(!$stmt->execute()) throw new Exception($stmt->error);

            $this->LogActivity($_SESSION['ID'], "UPDATE", 0, "Toggled User Status ID: $id to $status");
            echo json_encode(['STATUS' => 'success', 'MESSAGE' => 'User status updated']);

        } catch (Exception $e) {
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => $e->getMessage()]);
        }
    }

    public function ToggleRoleStatus($data) {
        try {
            $id = intval($data['id']);
            $status = intval($data['status']); // 1 or 0

            if($id <= 0) throw new Exception("Invalid Role ID");

            $stmt = $this->conn->prepare("UPDATE tbl_roles SET status = ? WHERE id = ?");
            $stmt->bind_param("ii", $status, $id);
            if(!$stmt->execute()) throw new Exception($stmt->error);

            $this->LogActivity($_SESSION['ID'], "UPDATE", 0, "Toggled Role Status ID: $id to $status");
            echo json_encode(['STATUS' => 'success', 'MESSAGE' => 'Role status updated']);

        } catch (Exception $e) {
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => $e->getMessage()]);
        }
    }
}

// Request Handler
if (isset($_POST['action'])) {
    $svc = new ConfigAccounts();
    
    // Check Global Access to Module
    $svc->checkAccess();
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'LoadUsers':
            $svc->LoadUsers();
            break;
        case 'SaveUser':
            $svc->SaveUser($_POST);
            break;
        case 'LoadRoles':
            $svc->LoadRoles();
            break;
        case 'SaveRole':
            $svc->SaveRole($_POST);
            break;
        case 'GetRolePermissions':
            $svc->GetRolePermissions($_POST);
            break;
        case 'SaveRolePermissions':
            $svc->SaveRolePermissions($_POST);
            break;
        case 'ToggleUserStatus':
            $svc->ToggleUserStatus($_POST);
            break;
        case 'ToggleRoleStatus':
            $svc->ToggleRoleStatus($_POST);
            break;
        default:
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => 'Invalid Action']);
            break;
    }
}

?>
