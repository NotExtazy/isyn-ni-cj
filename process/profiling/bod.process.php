<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once("../../database/connection.php");

class Process extends Database
{
    // CONFIGURATION: Set to ID 20 (Board of Directors)
    private $logModuleId = 20; 

    public function Initialize(){
        $yrlist = $this->SelectQuery("SELECT DISTINCT YEAR(fromdate) AS Year 
                                      FROM tbl_board_committee 
                                      WHERE YEAR(fromdate) > 0
                                      ORDER BY Year DESC");

        echo json_encode(array(
            "YRLIST" => $yrlist,
        ));
    }

    public function LoadBODList($data){
        $whereClause = "WHERE designation != 'COMMITTEE'";

        if ($data["Year"] != "DEFAULT" && $data["Year"] != "None" && $data["Year"] != "All" && $data["Year"] != "") {
            $year = $this->conn->real_escape_string($data["Year"]);
            $whereClause .= " AND YEAR(fromdate) = '$year'";
        }

        $bodlist = $this->SelectQuery("SELECT * FROM tbl_board_committee $whereClause ORDER BY id DESC");
        
        // Format dates for display
        foreach ($bodlist as &$row) {
            // dateEncoded as m-d-y
            if (!empty($row['dateEncoded']) && $row['dateEncoded'] != '0000-00-00') {
                 $row['dateEncoded'] = date("m-d-Y", strtotime($row['dateEncoded']));
            } else {
                 $row['dateEncoded'] = '-';
            }
            
            // fromdate and toDate as m/d/Y
            if (!empty($row['fromdate']) && $row['fromdate'] != '0000-00-00') {
                 $row['fromdate'] = date("m-d-Y", strtotime($row['fromdate']));
            }
            if (!empty($row['toDate']) && $row['toDate'] != '0000-00-00') {
                 $row['toDate'] = date("m-d-Y", strtotime($row['toDate']));
            }
        }

        echo json_encode(array( 
            "BODLIST" => $bodlist,
        ));
    }

    public function LoadCMMTTList($data){
        $whereClause = "WHERE designation = 'COMMITTEE'";

        if ($data["Year"] != "DEFAULT" && $data["Year"] != "None" && $data["Year"] != "All" && $data["Year"] != "") {
            $year = $this->conn->real_escape_string($data["Year"]);
            $whereClause .= " AND YEAR(fromdate) = '$year'";
        }

        $committeelist = $this->SelectQuery("SELECT * FROM tbl_board_committee $whereClause ORDER BY id DESC");

        // Format dates for display
        foreach ($committeelist as &$row) {
            if (!empty($row['fromdate']) && $row['fromdate'] != '0000-00-00') {
                 $row['fromdate'] = date("m/d/Y", strtotime($row['fromdate']));
            }
            if (!empty($row['toDate']) && $row['toDate'] != '0000-00-00') {
                 $row['toDate'] = date("m/d/Y", strtotime($row['toDate']));
            }
        }

        echo json_encode(array( 
            "CMMTTLIST" => $committeelist,
        ));
    }

    // --- REPORT GENERATION (With Logs) ---
    public function GenerateBODReport($data){
        $year = $data["Year"];
        $whereClause = "WHERE designation != 'COMMITTEE'";
        
        if ($year != "All" && $year != "None" && $year != "" && $year != "DEFAULT") {
            $year = intval($year);
            // Updated to support DATE type or Y-m-d text
            $whereClause .= " AND YEAR(fromdate) = $year";
        }

        $bodlist = $this->SelectQuery("SELECT firstname, middlename, lastname, fullname, designation, fromdate, toDate FROM tbl_board_committee $whereClause ORDER BY lastname ASC, firstname ASC");

        $headerData = array("Fullname", "Designation", "From", "To");
        $tableData = array();

        if ($bodlist) {
            foreach ($bodlist as $row) {
                $fullname = $row['fullname'];
                if(empty($fullname)){
                    $fullname = $row['lastname'] . ", " . $row['firstname'];
                    if (!empty($row['middlename'])) {
                        $fullname .= " " . $row['middlename'];
                    }
                }
                
                // Format dates to short month format (Jan. 01, 2004)
                $fromDateFormatted = (!empty($row['fromdate']) && $row['fromdate'] != '0000-00-00') 
                    ? date('M. d, Y', strtotime($row['fromdate'])) 
                    : '';
                $toDateFormatted = (!empty($row['toDate']) && $row['toDate'] != '0000-00-00') 
                    ? date('M. d, Y', strtotime($row['toDate'])) 
                    : '';
                
                $tableData[] = array(
                    strtoupper($fullname),
                    strtoupper($row['designation']),
                    $fromDateFormatted,
                    $toDateFormatted
                );
            }
        }

        $_SESSION['headerData'] = $headerData;
        $_SESSION['tableData'] = $tableData;
        $_SESSION['reportYear'] = ($year == "All" || $year == "None" || $year == "" || $year == "DEFAULT") ? "All Years" : $year;

        // --- LOGGING ---
        try {
            $userId = $_SESSION['ID'] ?? null;
            $action = "GENERATE";
            $desc = "Generated BOD List Preview (Year: $year)";
            $this->LogActivity($userId, $action, $this->logModuleId, $desc);
        } catch (Exception $e) {}
        // ----------------

        echo json_encode(array("STATUS" => "SUCCESS"));
    }

    public function GenerateCommitteeReport($data){
        $year = $data["Year"];
        $whereClause = "WHERE designation = 'COMMITTEE'";
        
        if ($year != "All" && $year != "None" && $year != "" && $year != "DEFAULT") {
            $year = intval($year);
             // Updated to support DATE type or Y-m-d text
            $whereClause .= " AND YEAR(fromdate) = $year";
        }

        $committeelist = $this->SelectQuery("SELECT firstname, middlename, lastname, fullname, designation, committeeType, specializedposition, fromdate, toDate FROM tbl_board_committee $whereClause ORDER BY lastname ASC, firstname ASC");

        $headerData = array("Fullname", "Designation", "Committee Type", "Position", "From", "To");
        $tableData = array();

        if ($committeelist) {
            foreach ($committeelist as $row) {
                $fullname = $row['fullname'];
                if(empty($fullname)){
                    $fullname = $row['lastname'] . ", " . $row['firstname'];
                    if (!empty($row['middlename'])) {
                        $fullname .= " " . $row['middlename'];
                    }
                }
                
                
                // Format dates to short month format (Jan. 01, 2004)
                $fromDateFormatted = (!empty($row['fromdate']) && $row['fromdate'] != '0000-00-00') 
                    ? date('M. d, Y', strtotime($row['fromdate'])) 
                    : '';
                $toDateFormatted = (!empty($row['toDate']) && $row['toDate'] != '0000-00-00') 
                    ? date('M. d, Y', strtotime($row['toDate'])) 
                    : '';
                
                $tableData[] = array(
                    strtoupper($fullname),
                    strtoupper($row['designation']),
                    strtoupper($row['committeeType']),
                    strtoupper($row['specializedposition']),
                    $fromDateFormatted,
                    $toDateFormatted
                );
            }
        }

        $_SESSION['cmmttHeaderData'] = $headerData;
        $_SESSION['cmmttTableData'] = $tableData;
        $_SESSION['cmmttReportYear'] = ($year == "All" || $year == "None" || $year == "" || $year == "DEFAULT") ? "All Years" : $year;

        // --- LOGGING ---
        try {
            $userId = $_SESSION['ID'] ?? null;
            $action = "GENERATE";
            $desc = "Generated Committee List Preview (Year: $year)";
            $this->LogActivity($userId, $action, $this->logModuleId, $desc);
        } catch (Exception $e) {}
        // ----------------

        echo json_encode(array("STATUS" => "SUCCESS"));
    }

    // --- DROPDOWNS ---
    public function LoadSelectOptions(){
        $module = "Profiling";
        $submodule = "Board of Directors";

        $map = function($rows, $keyName) {
            $out = [];
            foreach($rows as $r) {
                if(!empty($r['choice_value'])) {
                    $out[] = [$keyName => $r['choice_value']];
                }
            }
            return $out;
        };

        $designationsRaw = $this->GetChoices($module, $submodule, "Designation");
        if(empty($designationsRaw)){
            $defaults = ["Chairman", "Vice Chairman", "Member", "Secretary", "Treasurer", "Manager"];
            foreach($defaults as $d) $designationsRaw[] = ["choice_value" => $d];
        }
        $designations = $map($designationsRaw, "designation");

        $committeeTypesRaw = $this->GetChoices($module, $submodule, "Committee Type");
        if(empty($committeeTypesRaw)){
            $defaults = ["Audit", "Election", "Ethics", "Credit", "Mediation", "Education"];
            foreach($defaults as $d) $committeeTypesRaw[] = ["choice_value" => $d];
        }
        $committeeTypes = $map($committeeTypesRaw, "committeeType");

        $specializedPositionsRaw = $this->GetChoices($module, $submodule, "Specialized Position");
        if(empty($specializedPositionsRaw)){
             $defaults = ["None", "Head", "Vice Head"];
             foreach($defaults as $d) $specializedPositionsRaw[] = ["choice_value" => $d];
        }
        $specializedPositions = $map($specializedPositionsRaw, "specializedposition");

        echo json_encode(array(
            "DESIGNATIONS" => $designations,
            "COMMITTEETYPES" => $committeeTypes,
            "SPECIALIZEDPOSITIONS" => $specializedPositions,
        ));
    }

    private function GetChoices($module, $submodule, $item){
        $choices = [];
        $sql = "SELECT t4.module as choice_value 
                FROM tbl_maintenance_module t4
                JOIN tbl_maintenance_module t3 ON t4.module_no = t3.id_module
                JOIN tbl_maintenance_module t2 ON t3.module_no = t2.id_module
                JOIN tbl_maintenance_module t1 ON t2.module_no = t1.id_module
                WHERE t1.module_type = 0 AND t1.module = ?
                AND t2.module_type = 1 AND t2.module = ?
                AND t3.module_type = 2 AND t3.module = ?
                AND t4.module_type = 3 AND t4.status = 1
                ORDER BY t4.module ASC";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $module, $submodule, $item);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $choices[] = $row;
                }
            }
            $stmt->close();
        }
        return $choices;
    }

    public function getBODInfo($data){
        $id = $data['id'];
        $stmt = $this->conn->prepare("SELECT * from tbl_board_committee WHERE id = ?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(array("INFO" => $row, "STATUS" => "LOADED"));
        } else {            
            echo json_encode(array("STATUS" => "EMPTY"));
        }
    }

    // --- CRUD OPERATIONS (With Logs) ---
    public function SaveInfo($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);

            $requiredFields = ['shareholderName', 'fromdate', 'toDate'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $fullname = trim(strip_tags($_POST['shareholderName']));
            
            // No need to parse name - save directly to fullname column
            $firstname = '';
            $middlename = '';
            $lastname = '';

            $designation = strip_tags($_POST['BODdesignation']?? '');
            $committeeType = strip_tags($_POST['committeeType'] ?? '');
            $specializedposition = strip_tags($_POST['specializedposition'] ?? '');
            
            // Validate and convert dates
            $fromdateInput = trim($_POST['fromdate']);
            $toDateInput = trim($_POST['toDate']);
            
            // Validate date format and values
            $fromdateObj = DateTime::createFromFormat('m/d/Y', $fromdateInput);
            $toDateObj = DateTime::createFromFormat('m/d/Y', $toDateInput);
            
            if (!$fromdateObj || $fromdateObj->format('m/d/Y') !== $fromdateInput) {
                throw new Exception("Invalid 'From' date format. Please use MM/DD/YYYY.");
            }
            
            if (!$toDateObj || $toDateObj->format('m/d/Y') !== $toDateInput) {
                throw new Exception("Invalid 'To' date format. Please use MM/DD/YYYY.");
            }
            

            
            // Check date range
            if ($fromdateObj > $toDateObj) {
                throw new Exception("'From' date cannot be after 'To' date.");
            }
            
            // Convert to Y-m-d for database storage (DATE column)
            $fromdate = $fromdateObj->format('Y-m-d');
            $toDate = $toDateObj->format('Y-m-d');

            // Overlap Validation
            $this->validatePositionOverlap($designation, $fromdate, $toDate);
            
            date_default_timezone_set('Asia/Manila');
            $asof = date("Y-m-d");
    
            $stmt1 = $this->conn->prepare("INSERT INTO tbl_board_committee (firstname, middlename, lastname, designation, committeeType, specializedposition, fromdate, toDate, fullname, dateEncoded) VALUES (?,?,?,?,?,?,?,?,?,?)");
            if (!$stmt1) throw new Exception("Prepare failed: " . $this->conn->error);
            
            $stmt1->bind_param('ssssssssss', $firstname, $middlename, $lastname, $designation, $committeeType, $specializedposition, $fromdate, $toDate, $fullname, $asof);
            if (!$stmt1->execute()) throw new Exception("Execute failed: " . $stmt1->error);
            
            // --- LOGGING ---
            $userId = $_SESSION['ID'] ?? null;
            $desc = "Added BOD/Committee: $fullname ($designation)";
            $this->LogActivity($userId, "INSERT", $this->logModuleId, $desc);
            // ----------------

            $this->conn->commit();
            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Board of Director Information successfully added"));
            $stmt1->close();
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    public function UpdateInfo($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);

            $requiredFields = ['shareholderName', 'fromdate', 'toDate'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $boardID = $_POST['boardID'];
            $fullname = trim(strip_tags($_POST['shareholderName']));
            
            // No need to parse name - save directly to fullname column
            $firstname = '';
            $middlename = '';
            $lastname = '';

            $designation = strip_tags($_POST['BODdesignation']?? '');
            $committeeType = strip_tags($_POST['committeeType'] ?? '');
            $specializedposition = strip_tags($_POST['specializedposition'] ?? '');
            
            // Validate and convert dates
            $fromdateInput = trim($_POST['fromdate']);
            $toDateInput = trim($_POST['toDate']);
            
            // Validate date format and values
            $fromdateObj = DateTime::createFromFormat('m/d/Y', $fromdateInput);
            $toDateObj = DateTime::createFromFormat('m/d/Y', $toDateInput);
            
            if (!$fromdateObj || $fromdateObj->format('m/d/Y') !== $fromdateInput) {
                throw new Exception("Invalid 'From' date format. Please use MM/DD/YYYY.");
            }
            
            if (!$toDateObj || $toDateObj->format('m/d/Y') !== $toDateInput) {
                throw new Exception("Invalid 'To' date format. Please use MM/DD/YYYY.");
            }
            

            
            // Check date range
            if ($fromdateObj > $toDateObj) {
                throw new Exception("'From' date cannot be after 'To' date.");
            }
            
            // Convert to Y-m-d for database storage (DATE column)
            $fromdate = $fromdateObj->format('Y-m-d');
            $toDate = $toDateObj->format('Y-m-d');

            // Overlap Validation
            $this->validatePositionOverlap($designation, $fromdate, $toDate, $boardID);
            
            date_default_timezone_set('Asia/Manila');
            $asof = date("Y-m-d");   
            
            $stmt1 = $this->conn->prepare("UPDATE tbl_board_committee SET firstname=?, middlename=?, lastname=?, designation=?, committeeType=?, specializedposition=?, fromdate=?, toDate=?, fullname=?, dateEncoded=? WHERE id = ?");
            if (!$stmt1) throw new Exception("Prepare failed: " . $this->conn->error);
            
            $stmt1->bind_param('sssssssssss', $firstname, $middlename, $lastname, $designation, $committeeType, $specializedposition, $fromdate, $toDate, $fullname, $asof, $boardID);
            if (!$stmt1->execute()) throw new Exception("Execute failed: " . $stmt1->error);
            
            // --- LOGGING ---
            $userId = $_SESSION['ID'] ?? null;
            $desc = "Updated BOD/Committee Info: $fullname ($designation)";
            $this->LogActivity($userId, "UPDATE", $this->logModuleId, $desc);
            // ----------------

            $this->conn->commit();
            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Board of Director Information successfully updated"));
            
            $stmt1->close();
            $this->conn->autocommit(true);
        } catch (Exception $e) {
             $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));            
        }
    }

    // --- OVERLAP VALIDATION FUNCTION ---
    private function validatePositionOverlap($designation, $startDate, $endDate, $excludeId = null) {
        $restrictedPositions = ['PRESIDENT', 'VICE PRESIDENT', 'SECRETARY', 'TREASURER', 'AUDITOR', 'MANAGER'];
        $checkDesignation = strtoupper(trim($designation));

        if (!in_array($checkDesignation, $restrictedPositions)) {
            return;
        }

        // Since dates are now stored as Y-m-d (DATE type), use direct comparison
        $sql = "SELECT id, fromdate, toDate, fullname FROM tbl_board_committee 
                WHERE UPPER(designation) = ? 
                AND fromdate <= ? 
                AND toDate >= ?";
        
        $params = [$checkDesignation, $endDate, $startDate];
        $types = "sss";

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "s";
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new Exception("Validation prepare failed: " . $this->conn->error);
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            
            // Format dates for display as m-d-Y
            $displayFrom = date('m-d-Y', strtotime($row['fromdate']));
            $displayTo = date('m-d-Y', strtotime($row['toDate']));
            
            throw new Exception("Overlap detected! " . $row['fullname'] . " is already " . $designation . " for the period " . $displayFrom . " to " . $displayTo);
        }
        $stmt->close();
    }

    public function SelectQuery($string){
        $data = [];
        $stmt = $this->conn->prepare($string);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }
}
?>