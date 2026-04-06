<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function ListSubmodules($post){
        $module_id = $post['module_id']; // This is actually the parent module ID (e.g. System Settings)
        // In this schema, it seems we might need to filter by some parent linkage or return all submodules?
        // But looking at schema, there is no parent_id.
        // Wait, the JS sends `module_id`.
        // Let's assume `module_type` or similar might be used, or we just return all for now or filter by `module_no`.
        // Actually, let's look at `tbl_maintenance_module` again.
        // It has `id_module`, `module`, `module_type`.
        // If the tree is flat or categorized by `module_type`, we can use that.
        // For now, let's return all modules that are NOT main headers if possible, or just all.
        // BUT `maintenance.js` expects a JSON list.
        
        // Let's assume we return fields relevant to the tree.
        $stmt = $this->conn->prepare("SELECT * FROM tbl_maintenance_module ORDER BY module ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while($row = $result->fetch_assoc()){
            $data[] = $row;
        }
        echo json_encode(['STATUS'=>'success', 'DATA'=>$data]);
    }

    public function ListItems($post){
        $itemType = $post['itemType'] ?? '';
        $itemId   = isset($post['item_id']) ? intval($post['item_id']) : 0;
        
        // -------------------------------------------------------------------
        // STRATEGY: Check if the selected item (by id_module) exists in
        // tbl_maintenance_module. If it does, fetch its children (module_no = itemId).
        // This covers ALL module-type items: Customer Type, Gender, Suffix, 
        // Designation, Shareholder Type, Employee Status, Committee Type, etc.
        // Only fall back to tbl_maintenance for classic legacy items 
        // (LOCATION, RELIGION, CITIZENSHIP, etc.) that are NOT in the module tree.
        // -------------------------------------------------------------------
        
        if($itemId > 0){
            // Check if this id is a valid module parent in tbl_maintenance_module
            $checkStmt = $this->conn->prepare("SELECT id_module FROM tbl_maintenance_module WHERE id_module = ? LIMIT 1");
            $checkStmt->bind_param("i", $itemId);
            $checkStmt->execute();
            if($checkStmt->get_result()->num_rows > 0){
                // Fetch children where module_no = itemId
                $stmt = $this->conn->prepare("SELECT id_module as id, module as choice_value, status FROM tbl_maintenance_module WHERE module_no = ? ORDER BY module ASC");
                $stmt->bind_param("i", $itemId);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];
                while($row = $result->fetch_assoc()){
                    $data[] = $row;
                }
                echo json_encode(['STATUS'=>'success', 'DATA'=>$data]);
                return;
            }
        }
        
        // Legacy fallback: query tbl_maintenance by ItemType
        // (used for LOCATION, RELIGION, CITIZENSHIP, LOANTYPE, MODE, etc.)
        // Note: tbl_maintenance has no 'status' column, default to 1
        if(!empty($itemType)){
            $stmt = $this->conn->prepare("SELECT ID as id, ItemName as choice_value, 1 as status FROM tbl_maintenance WHERE ItemType = ? ORDER BY ItemName ASC");
            $stmt->bind_param("s", $itemType);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while($row = $result->fetch_assoc()){
                $data[] = $row;
            }
            echo json_encode(['STATUS'=>'success', 'DATA'=>$data]);
            return;
        }
        
        echo json_encode(['STATUS'=>'success', 'DATA'=>[]]);
    }

    public function LoadChoices($post){
       // Alias to ListItems
       $this->ListItems($post);
    }
    public function CreateItem($post){
        // Frontend sends: action:'CreateItem', parent:submoduleId, name:newItemName (for creating a tree node)
        // OR action:'SaveChoice', item:CategoryName, choice:NewValue (for creating a dropdown option)
        
        // This method seems to handle creating a TREE ITEM (module), based on "parent" param in JS.
        // But `tbl_maintenance` is for the DROPDOWN OPTIONS.
        // `tbl_maintenance_module` is for the TREE.
        
        if(isset($post['parent'])){
            // Creating a new MODULE/SUBMODULE/ITEM in the tree
            $parent = $post['parent'];
            $module = $post['name'];
            $type = 3; // Item
            // We need to insert into `tbl_maintenance_module`
            // But we don't have a CreateModule method in this class yet for the tree.
            // Let's add it or map it here.
            $stmt = $this->conn->prepare("INSERT INTO tbl_maintenance_module (module, module_type, module_no) VALUES (?, ?, ?)");
            // We'd need to handle parentage logic which seems missing in schema (no parent_id).
            // Maybe `module_no` is parent? Or schema is incomplete/different.
            // For now, let's assume valid dropdowns use `tbl_maintenance`.
            // If the user adds a "New..." item in the tree, we might skip implementation if not critical.
            // Focus on "dropdown of the system" -> Adding choices.
             echo json_encode(['STATUS'=>'error', 'MESSAGE'=>'Tree item creation not fully implemented.']);
             return;
        }

        // Creating a Dropdown Option (tbl_maintenance)
        $itemType = isset($post['itemType']) ? $post['itemType'] : (isset($post['item']) ? $post['item'] : '');
        $itemName = isset($post['itemName']) ? $post['itemName'] : (isset($post['choice']) ? $post['choice'] : '');
        
        if(empty($itemType) || empty($itemName)){
             echo json_encode(['STATUS'=>'error', 'MESSAGE'=>'Missing ItemType or ItemName']);
             return;
        }

        // Check duplicate
        $check = $this->conn->prepare("SELECT ID FROM tbl_maintenance WHERE ItemType=? AND ItemName=?");
        $check->bind_param("ss", $itemType, $itemName);
        $check->execute();
        if($check->get_result()->num_rows > 0){
             echo json_encode(['STATUS'=>'error', 'MESSAGE'=>'Item already exists']);
             return;
        }

        $stmt = $this->conn->prepare("INSERT INTO tbl_maintenance (ItemType, ItemName) VALUES (?, ?)");
        $stmt->bind_param("ss", $itemType, $itemName);
        if($stmt->execute()){
             echo json_encode(['STATUS'=>'success']);
        } else {
             echo json_encode(['STATUS'=>'error', 'MESSAGE'=>$stmt->error]);
        }
    }
    
    // For handling the "Config" of a choice (e.g. Customer Type requirements)
    // The JS calls `SaveChoice` with `config` data?
    // Wait, `maintenance.js` calls `UpdateName` or `SaveChoice`.
    // Let's look at `maintenance.route.php`:
    // `CreateItem` -> `process->CreateItem`
    // `SaveChoice` -> `process->SaveChoice`
    
    // We need `GetFieldConfig` which is called in JS via `action: 'GetFieldConfig'`?
    // The route file didn't have `GetFieldConfig`. I need to Add it to route file and process file.
    
    public function GetFieldConfig($post){
        // JS sends: action:'GetFieldConfig', customerType:'MFI HO' (the specific customer type name)
        $name = $post['customerType'] ?? $post['name'] ?? '';
        
        $stmt = $this->conn->prepare("SELECT fieldConfig FROM tbl_maintenance_module WHERE module = ? AND module_type = 3 LIMIT 1");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $res = $stmt->get_result();
        if($row = $res->fetch_assoc()){
            $config = $row['fieldConfig'] ? json_decode($row['fieldConfig'], true) : null;
            echo json_encode(['STATUS'=>'success', 'CONFIG'=>$config, 'config'=>$row['fieldConfig']]);
        } else {
            // No config found — return null so frontend can show all fields
            echo json_encode(['STATUS'=>'success', 'CONFIG'=>null, 'config'=>null]);
        }
    }
    public function SaveFieldConfig($post){
        // maintenance.js sends:  action, customerType, enabledFields (JSON), requiredFields (JSON)
        // Some callers may also send: name, config (full JSON string)
        // Handle both formats:
        $name = $post['name'] ?? $post['customerType'] ?? '';
        
        if(isset($post['config']) && !empty($post['config'])){
            // Format A: pre-built config JSON string
            $config = $post['config'];
        } else {
            // Format B: separate enabledFields / requiredFields arrays (from maintenance.js modal)
            $enabledFields  = json_decode($post['enabledFields']  ?? '[]', true) ?? [];
            $requiredFields = json_decode($post['requiredFields'] ?? '[]', true) ?? [];
            // Compute disabledFields as complement of enabled
            $allConfigurableFields = [
                'companyName','firstName','lastName','middleName','suffix',
                'birthdate','age','gender','mobileNumber','email',
                'tin'
            ];
            $disabledFields = array_values(array_diff($allConfigurableFields, $enabledFields));
            $config = json_encode([
                'enabledFields'  => $enabledFields,
                'requiredFields' => $requiredFields,
                'disabledFields' => $disabledFields
            ]);
        }
        
        // Update using ID if valid, otherwise fallback to name
        $id = isset($post['id']) ? intval($post['id']) : 0;

        if ($id > 0) {
             // Robust update by ID
             $stmt = $this->conn->prepare("UPDATE tbl_maintenance_module SET fieldConfig = ? WHERE id_module = ?");
             $stmt->bind_param("si", $config, $id);
        } else {
             // Fallback to Name (Legacy/Safety)
             $stmt = $this->conn->prepare("UPDATE tbl_maintenance_module SET fieldConfig = ? WHERE module = ? AND module_type = 3");
             $stmt->bind_param("ss", $config, $name);
        }

        if($stmt->execute()){
            // Check if any row was actually updated
            $affected = $stmt->affected_rows;
            $info = "ID: $id, Name: $name, Affected: $affected";
            
            if ($affected > 0) {
                echo json_encode(['STATUS'=>'success', 'DEBUG'=>$info]);
            } else {
                // If 0 affected, meaningful only if we expected a change.
                // But could also mean ID didn't match.
                // Let's verify if the row exists
                if ($id > 0) {
                    $check = $this->conn->prepare("SELECT id_module FROM tbl_maintenance_module WHERE id_module = ?");
                    $check->bind_param("i", $id);
                    $check->execute();
                    $exists = $check->get_result()->num_rows > 0;
                    $info .= ", Exists: " . ($exists ? 'Yes' : 'No');
                }
                echo json_encode(['STATUS'=>'success', 'MESSAGE'=>'Saved (No changes detected)', 'DEBUG'=>$info]);
            }
        } else {
            echo json_encode(['STATUS'=>'error', 'MESSAGE'=>$this->conn->error . " (ID: $id, Name: $name)"]);
        }
    }

    public function SaveChoice($post){
        $itemType = $post['itemType'] ?? $post['item'] ?? '';
        $itemName = $post['itemName'] ?? $post['choice'] ?? '';
        $parentId = isset($post['item_id']) ? intval($post['item_id']) : 0;
        
        // -------------------------------------------------------------------
        // UNIVERSAL: If item_id corresponds to a module in tbl_maintenance_module,
        // insert new choice as a child (module_no = parentId) there.
        // This handles ALL module items: Customer Type, Gender, Suffix,
        // Shareholder Type, Designation, Employee Status, Committee Type, etc.
        // -------------------------------------------------------------------
        if($parentId > 0){
            $checkStmt = $this->conn->prepare("SELECT id_module FROM tbl_maintenance_module WHERE id_module = ? LIMIT 1");
            $checkStmt->bind_param("i", $parentId);
            $checkStmt->execute();
            if($checkStmt->get_result()->num_rows > 0){
                // Check duplicate
                $dupStmt = $this->conn->prepare("SELECT id_module FROM tbl_maintenance_module WHERE module_no = ? AND module = ?");
                $dupStmt->bind_param("is", $parentId, $itemName);
                $dupStmt->execute();
                if($dupStmt->get_result()->num_rows > 0){
                    echo json_encode(['STATUS'=>'error', 'MESSAGE'=>'"'.$itemName.'" already exists']);
                    return;
                }
                $insertStmt = $this->conn->prepare("INSERT INTO tbl_maintenance_module (module, module_type, module_no, status) VALUES (?, 3, ?, 1)");
                $insertStmt->bind_param("si", $itemName, $parentId);
                if($insertStmt->execute()){
                    echo json_encode(['STATUS'=>'success']);
                } else {
                    echo json_encode(['STATUS'=>'error', 'MESSAGE'=>$insertStmt->error]);
                }
                return;
            }
        }
        
        // Legacy fallback — insert into tbl_maintenance by ItemType
        if(isset($post['id']) && !empty($post['id'])){
            $this->UpdateName($post);
        } else {
            $this->CreateItem($post);
        }
    }

    public function UpdateChoice($post){
        $this->UpdateName($post);
    }

    public function ArchiveChoice($post){
        // If item is from tbl_maintenance_module (Customer Type children) - toggle status
        // If item is from tbl_maintenance (regular items) - delete (no status column)
        $id = intval($post['id']);
        $newStatus = isset($post['status']) ? intval($post['status']) : null;
        
        // Try tbl_maintenance_module first
        $checkStmt = $this->conn->prepare("SELECT id_module FROM tbl_maintenance_module WHERE id_module = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        if($checkStmt->get_result()->num_rows > 0){
            // It's a module item - toggle status
            $status = ($newStatus !== null) ? $newStatus : 0;
            $stmt = $this->conn->prepare("UPDATE tbl_maintenance_module SET status = ? WHERE id_module = ?");
            $stmt->bind_param("ii", $status, $id);
            if($stmt->execute()){
                echo json_encode(['STATUS'=>'success']);
            } else {
                echo json_encode(['STATUS'=>'error', 'MESSAGE'=>$stmt->error]);
            }
        } else {
            // It's from tbl_maintenance - just delete
            $this->DeleteChoice($post);
        }
    }


    
    public function UpdateName($post){
        $id = $post['id'];
        $name = $post['name'];
        $stmt = $this->conn->prepare("UPDATE tbl_maintenance SET ItemName=? WHERE ID=?");
        $stmt->bind_param("si", $name, $id);
        if($stmt->execute()){
            echo json_encode(['STATUS'=>'success']);
        } else {
             echo json_encode(['STATUS'=>'error', 'MESSAGE'=>$stmt->error]);
        }
    }

    public function DeleteChoice($post){
        $id = $post['id'];
         $stmt = $this->conn->prepare("DELETE FROM tbl_maintenance WHERE ID=?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()){
            echo json_encode(['STATUS'=>'success']);
        } else {
             echo json_encode(['STATUS'=>'error', 'MESSAGE'=>$stmt->error]);
        }
    }
}
?>