<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    // CONFIGURATION: Set to ID 19 (Shareholder Information)
    private $logModuleId = 19; 

    // Constructor for Dependency Injection (Testing)
    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
        } else {
            parent::__construct();
        }
    } 

    // ==========================================
    //    NEW: DYNAMIC PREFIX CHECK (DB BASED)
    // ==========================================
    private function validatePrefixDB($fullNumber) {
        $prefix = substr($fullNumber, 0, 4);
        $stmt = $this->conn->prepare("SELECT id FROM tbl_contactnum_prefixes WHERE prefix_code = ? AND status = 1 LIMIT 1");
        $stmt->bind_param('s', $prefix);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    // ==========================================
    //    VALIDATION LOGIC
    // ==========================================

    // NEW: FUZZY DUPLICATE NAME CHECK (Like Customer Module)
    private function CheckFuzzyDuplicateName($inputName, $excludeId = null) {
        // Normalize: Uppercase + AlphaNumeric Only
        $normInput = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $inputName));
        if (strlen($normInput) < 3) return; // Skip very short names

        // Fetch all shareholders (exclude current record if updating)
        $sql = "SELECT id, fullname FROM tbl_shareholder_info";
        if ($excludeId) {
            $sql .= " WHERE id != " . intval($excludeId);
        }
        $result = $this->conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $dbName = $row['fullname'];
            $normDB = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $dbName));

            // Exact Match (Normalized) - Throw exception
            if ($normInput === $normDB) {
                throw new Exception("Shareholder '$dbName' already exists (normalized match).");
            }

            // Fuzzy Match (Levenshtein) - Check for typos
            $dist = levenshtein($normInput, $normDB);
            $len = strlen($normInput);
            
            // Only check if lengths are similar
            if (abs(strlen($normInput) - strlen($normDB)) <= 2) {
                $threshold = 0;
                if ($len > 10) $threshold = 2;
                elseif ($len > 4) $threshold = 1;

                if ($dist > 0 && $dist <= $threshold) {
                    throw new Exception("Similar Shareholder Name Found: '$dbName'. Please verify.");
                }
            }
        }
    }

    // ==========================================
    //    VALIDATION LOGIC
    // ==========================================
    private function ValidateShareholderInputs($data, $isUpdate = false){
        $fullname = trim(strip_tags((string)($data['shareholderName'] ?? '')));
        $contact = trim(strip_tags((string)($data['contact_number'] ?? '')));
        $email = trim(strip_tags((string)($data['email'] ?? ''))); 
        $fb = trim(strip_tags((string)($data['facebook_account'] ?? '')));
        
        if ($fullname === '') throw new Exception("Shareholder Name is required.");
        if (strlen($fullname) > 100) throw new Exception("Shareholder Name exceeds 100 characters.");

        if ($contact === '' || $contact === '09') throw new Exception("Contact No. is required.");
        $cleanContact = preg_replace('/\D/', '', $contact);
        if (!preg_match('/^09\d{9}$/', $cleanContact)) {
            throw new Exception("Contact No. must be 11 digits starting with 09.");
        }

        if (!$this->validatePrefixDB($cleanContact)) {
            $invalidPrefix = substr($cleanContact, 0, 4);
            throw new Exception("Invalid Network Prefix ($invalidPrefix). Please enter a valid PH mobile number.");
        }

        if ($email === '') throw new Exception("Email Address is required.");
        if (strlen($email) > 50) throw new Exception("Email exceeds 50 characters.");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid Email format.");

        if ($email === '') throw new Exception("Email Address is required.");
        if (strlen($email) > 50) throw new Exception("Email exceeds 50 characters.");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception("Invalid Email format.");

        if (strlen($fb) > 100) throw new Exception("Facebook Link exceeds 100 characters.");

        // TIN Validation
        $tin = trim(strip_tags((string)($data['tin'] ?? '')));
        if ($tin === '') throw new Exception("Tax Identification No. (TIN) is required.");

        $cleanTin = preg_replace('/\D/', '', $tin);
        if (strlen($cleanTin) !== 12) {
             throw new Exception("Invalid TIN. Must be exactly 12 digits.");
        }
    }

    // ==========================================
    //    DATA LOADING & CONFIG
    // ==========================================
    public function LoadDropdowns(){
        $module = "Profiling";
        $submodules = ["Shareholder Information", "Shareholder Info", "Shareholder"];
        $shtypeItems = ["Shareholder Type", "Shareholder Type:", "Shareholder Types"];
        $typeItems = ["Type of Shares", "Type of Shares:", "Types of Shares", "Share Type"];

        $shtypes = $this->GetChoicesAny($module, $submodules, $shtypeItems);
        $types = $this->GetChoicesAny($module, $submodules, $typeItems);

        if (empty($shtypes)) $shtypes = [["choice_value" => "INDIVIDUAL"], ["choice_value" => "CORPORATE"]];
        if (empty($types)) $types = [["choice_value" => "COMMON"], ["choice_value" => "PREFERRED"]];

        echo json_encode(array("SHTYPES" => $shtypes, "TYPES" => $types, "ERROR" => null));
    }

    private function GetChoices($module, $submodule, $item){
        $choices = [];
        $sql = "SELECT t4.module as choice_value FROM tbl_maintenance_module t4 JOIN tbl_maintenance_module t3 ON t4.module_no = t3.id_module JOIN tbl_maintenance_module t2 ON t3.module_no = t2.id_module JOIN tbl_maintenance_module t1 ON t2.module_no = t1.id_module WHERE t1.module_type = 0 AND t1.module = ? AND t2.module_type = 1 AND t2.module = ? AND t3.module_type = 2 AND t3.module = ? AND t4.module_type = 3 AND t4.status = 1 ORDER BY t4.module ASC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $module, $submodule, $item);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) { $choices[] = $row; }
            }
            $stmt->close();
        }
        return $choices;
    }

    private function GetChoicesAny($module, $submodules, $items){
        $all = [];
        foreach ($submodules as $submodule) {
            foreach ($items as $item) {
                $rows = $this->GetChoices($module, $submodule, $item);
                foreach ($rows as $r) {
                    if (!isset($r["choice_value"])) continue;
                    $key = trim((string)$r["choice_value"]);
                    if ($key === "") continue;
                    $all[$key] = ["choice_value" => $key];
                }
                if (!empty($all)) break 2;
            }
        }
        $out = array_values($all);
        usort($out, function($a, $b){ return strcmp($a["choice_value"], $b["choice_value"]); });
        return $out;
    }

    public function LoadShareHolderNames(){
        $names = [];
        $sql = "SELECT DISTINCT fullname FROM tbl_shareholder_info ORDER BY fullname ASC";
        $result = $this->conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) { $names[] = $row; }
        }
        echo json_encode(array("NAMES" => $names));
    }

    public function LoadShareHolderList($data){
        ob_clean(); header('Content-Type: application/json');
        $list = [];
        $error = null;
        try {
            $sql = "SELECT shareholderNo, fullname, shareholder_type, type, noofshare, dateEncoded FROM tbl_shareholder_info";
            $result = $this->conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()){
                    $row['shareholderNo'] = $row['shareholderNo'] ?? '-';
                    $rawName = trim($row['fullname'] ?? '');
                    $shType = strtoupper($row['shareholder_type'] ?? '');
                    $row['type'] = $row['type'] ?? '-';
                    $row['noofshare'] = $row['noofshare'] ?? '0';
                    $row['dateEncoded'] = $row['dateEncoded'] ?? '';
                    // Convert d/m/Y or Y-m-d to m-d-Y
                    if (!empty($row['dateEncoded'])) {
                        $dt = DateTime::createFromFormat('Y-m-d', $row['dateEncoded']); // Try Standard DB Date
                        if (!$dt) $dt = DateTime::createFromFormat('d/m/Y', $row['dateEncoded']); // Try Legacy 1
                        if (!$dt) $dt = DateTime::createFromFormat('m-d-Y', $row['dateEncoded']); // Try Legacy 2
                        
                        if ($dt) {
                            $row['dateEncoded'] = $dt->format('m-d-Y');
                        }
                    }
                    $row['fullname'] = strtoupper($rawName);
                    $list[] = $row;
                }
            }
            usort($list, function($a, $b) { return strcmp($a['fullname'], $b['fullname']); });
        } catch (Exception $e) { $error = $e->getMessage(); }
        echo json_encode(array("LIST" => $list, "ERROR" => $error));
        exit; 
    }

    public function getShareholderInfo($data){
        $shareholderNo = $data['shareholderNo'];
        $stmt = $this->conn->prepare("SELECT * from tbl_shareholder_info WHERE shareholderNo = ?");
        $stmt->bind_param('s', $shareholderNo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Format Date for Frontend (m-d-Y)
            if(!empty($row['dateEncoded'])){
                $dt = DateTime::createFromFormat('Y-m-d', $row['dateEncoded']);
                if($dt) $row['dateEncoded'] = $dt->format('m-d-Y');
            }
            if(!empty($row['dateEncoded'])){
                $dt = DateTime::createFromFormat('Y-m-d', $row['dateEncoded']);
                if($dt) $row['dateEncoded'] = $dt->format('m-d-Y');
            }
            // Decrypt TIN
            $row['tin'] = Encryption::decrypt($row['TIN']); // Decrypt DB Column TIN to frontend field tin
            echo json_encode(array("INFO" => $row, "STATUS" => "LOADED"));
        } else {            
            echo json_encode(array("STATUS" => "ERROR"));
        }
    }

    public function getShareholderByName($data){
        $fullname = $data['fullname'];
        $stmt = $this->conn->prepare("SELECT * from tbl_shareholder_info WHERE fullname = ? LIMIT 1");
        $stmt->bind_param('s', $fullname);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
             // Format Date for Frontend (m-d-Y)
             if(!empty($row['dateEncoded'])){
                $dt = DateTime::createFromFormat('Y-m-d', $row['dateEncoded']);
                if($dt) $row['dateEncoded'] = $dt->format('m-d-Y');
            }
            if(!empty($row['dateEncoded'])){
                $dt = DateTime::createFromFormat('Y-m-d', $row['dateEncoded']);
                if($dt) $row['dateEncoded'] = $dt->format('m-d-Y');
            }
            // Decrypt TIN
            $row['tin'] = Encryption::decrypt($row['TIN']);
            echo json_encode(array("INFO" => $row, "STATUS" => "LOADED"));
        } else {
            echo json_encode(array("STATUS" => "NOT_FOUND"));
        }
    }
    
    public function getShareholderConfig(){
        $certNo = $this->SelectQuery("SELECT * FROM tbl_configuration t WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = 'CERTIFICATENO'");
        $sign1 = $this->SelectQuery("SELECT * FROM tbl_configuration t WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = 'SIGNATORIES_1'");
        $sign2 = $this->SelectQuery("SELECT * FROM tbl_configuration t WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = 'SIGNATORIES_2'");
        $signsub2 = $this->SelectQuery("SELECT * FROM tbl_configuration t WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = 'SIGNATORIES_SUB_2'");
        echo json_encode(array("certNo" => $certNo, "SIGN1" => $sign1, "SIGN2" => $sign2, "SIGNSUB2" => $signsub2));
    }

    public function searchNames($data){
        $names = [];
        $stmt = $this->conn->prepare("SELECT fullname FROM tbl_shareholder_info WHERE fullname LIKE ? ORDER BY fullname ASC");
        $searchName = '%' . $data["name"] . '%';
        $stmt->bind_param("s", $searchName);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) { $names[] = $row['fullname']; }
        $stmt->close();
        echo json_encode($names);
    }

    public function gnrtCertID(){
        $data = $this->_generateCertNo();
        echo json_encode($data);
    }

    private function _generateCertNo(){
        $stmt = $this->conn->prepare("SELECT Value FROM tbl_configuration t WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = 'CERTIFICATENO';");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $no = $row['Value'];
            $certNo = str_pad($no, 4, 0, STR_PAD_LEFT);
        } else {
            $no = 1;
            $certNo = str_pad($no, 4, 0, STR_PAD_LEFT);
        }
        return array("certNo" => $certNo, "actualNo" => $no);
    }

    public function updateCertNo($no){
        $updatedCertNo = intval($no) + 1;
        $stmt1 = $this->conn->prepare("UPDATE tbl_configuration SET Value= ? WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = 'CERTIFICATENO'");
        $stmt1->bind_param('s', $updatedCertNo);
        $stmt1->execute();
        $stmt1->close();
    }

    public function gnrtSID(){
        $stmt = $this->conn->prepare("SELECT shareholderNo FROM tbl_shareholder_info ORDER BY CAST(SUBSTRING(shareholderNo, 5) AS UNSIGNED) DESC LIMIT 1;");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_no = substr($row['shareholderNo'], 4);
            $next_number = intval($last_no) + 1;
            $shareNo = 'ISYN' . str_pad($next_number, 7, 0, STR_PAD_LEFT);
        } else {
            $next_number = 0000001; 
            $shareNo = 'ISYN' . str_pad($next_number, 7, 0, STR_PAD_LEFT);
        }
        echo json_encode(array("shareNo" => $shareNo));
    }

    // ==========================================
    //    SAVE SHAREHOLDER
    // ==========================================
    public function getShareholderCertificates($data){
        $shareholderNo = $data['shareholderNo'];
        $list = [];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_sharecert_issuances WHERE shareholderNo = ? AND status = 'Active' ORDER BY id ASC");
        $stmt->bind_param('s', $shareholderNo);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
            // Format Date for Frontend Display (m-d-Y)
            if(!empty($row['date_issued'])){
                $dt = DateTime::createFromFormat('Y-m-d', $row['date_issued']);
                if($dt) $row['date_issued'] = $dt->format('m-d-Y');
            }
            $row['is_printed'] = (int)($row['is_printed'] ?? 0); // Ensure numeric
            $list[] = $row;
        }
        echo json_encode(array("LIST" => $list));
    }

    public function markCertPrinted($data){
        $this->validateCSRF();
        try {
            $id = $data['certId'];
            $stmt = $this->conn->prepare("UPDATE tbl_sharecert_issuances SET is_printed = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if($stmt->execute()){
                echo json_encode(array("STATUS" => "SUCCESS"));
            } else {
                echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $stmt->error));
            }
            $stmt->close();
        } catch (Exception $e) {
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    // ==========================================
    //    NEW: MARK AS PAID (Generate Cert No)
    // ==========================================
    public function MarkAsPaid($data) {
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);
            
            $issuanceId = $data['issuanceId'];
            
            // 1. Get Issuance Details
            $stmt = $this->conn->prepare("SELECT shareholderNo, noofshare, payment_status, cert_no FROM tbl_sharecert_issuances WHERE id = ?");
            $stmt->bind_param("i", $issuanceId);
            $stmt->execute();
            $res = $stmt->get_result();
            $issuance = $res->fetch_assoc();
            $stmt->close();

            if (!$issuance) throw new Exception("Issuance record not found.");
            if ($issuance['payment_status'] === 'Paid' && !empty($issuance['cert_no'])) {
                throw new Exception("This issuance is already paid and has a certificate.");
            }

            // 2. Generate New Cert No
            $certData = $this->_generateCertNo();
            $newCertNo = $certData['certNo'];
            $actualNo = $certData['actualNo'];

            // 3. Update Issuance
            $updateStmt = $this->conn->prepare("UPDATE tbl_sharecert_issuances SET payment_status = 'Paid', cert_no = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newCertNo, $issuanceId);
            if (!$updateStmt->execute()) throw new Exception("Failed to update issuance.");
            $updateStmt->close();

            // 4. Increment Config Counter
            $this->updateCertNo($actualNo);

            // 5. Update Main Shareholder Info (Last Cert No) - Optional but good for consistency
            // Logic: Is this the latest issuance? Or just update it anyway?
            // Let's update it to reflect the *latest paid* cert no.
            $mainUpdate = $this->conn->prepare("UPDATE tbl_shareholder_info SET cert_no = ? WHERE shareholderNo = ?");
            $mainUpdate->bind_param("ss", $newCertNo, $issuance['shareholderNo']);
            $mainUpdate->execute();
            $mainUpdate->close();

            $this->conn->commit();
            
            $this->LogActivity($_SESSION['ID'] ?? null, "UPDATE", $this->logModuleId, "Marked Issuance #$issuanceId as Paid. Generated Cert #$newCertNo");
            echo json_encode(array("STATUS" => "SUCCESS", "MESSAGE" => "Payment confirmed. Certificate #$newCertNo generated."));

        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    // ==========================================
    //    SAVE SHAREHOLDER (Modified for Issuances)
    // ==========================================
    // ==========================================
    //    CONSOLIDATED UPSERT (SAVE/UPDATE)
    // ==========================================
    public function SaveInfo($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);
            $this->ValidateShareholderInputs($data, false);

            $fullname = $data['shareholderName'] ?? '';
            
            // CHECK DUPLICATE via Exact Name
            $check = $this->conn->prepare("SELECT shareholderNo, noofshare, amount_share FROM tbl_shareholder_info WHERE fullname = ?");
            $check->bind_param("s", $fullname);
            $check->execute();
            $chkRes = $check->get_result();

            if($chkRes->num_rows > 0){
                // Existing Found -> UPDATE_MERGE
                $existing = $chkRes->fetch_assoc();
                $this->_processMerge($data, $existing);
            } else {
                // NEW: Check for fuzzy duplicates ONLY if not an exact match (prevent creating nearly-identical variants)
                $this->CheckFuzzyDuplicateName($fullname);

                // Not Found -> INSERT
                $this->_processInsert($data);
            }
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
            $this->ValidateShareholderInputs($data, true);

            // NEW: Check for fuzzy duplicates (exclude current record)
            $shareID = $data['shareID'] ?? null;
            $fullname = $data['shareholderName'] ?? '';
            if ($shareID && $fullname) {
                $this->CheckFuzzyDuplicateName($fullname, $shareID);
            }

            // Manual Update (Overwrite)
            $this->_processOverwrite($data);
            
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    private function _processInsert($data) {
        // Generate ID
        $lastId = $this->conn->query("SELECT shareholderNo FROM tbl_shareholder_info ORDER BY id DESC LIMIT 1");
        $newID = "ISYN0000001";
        if($lastId->num_rows > 0){
            $row = $lastId->fetch_assoc();
            $lastNo = intval(substr($row["shareholderNo"], 4));
            $newID = "ISYN" . str_pad($lastNo + 1, 7, "0", STR_PAD_LEFT);
        }

        // Initial Save: NO Certificate Number yet (Until Paid)
        $cert_no = ''; 
        $actualNo = 0; // Don't increment yet

        // If user is NEW, we still generate an ID but no cert.

        $fullname = strip_tags($data['shareholderName']);
        $contact_number = strip_tags($data['contact_number']);
        $email = strip_tags($data['email']);
        $facebook_account = strip_tags($data['facebook_account']);
        $shareholder_type = strip_tags($data['shareholder_type']);
        $type = strip_tags($data['type']);
        $noofshare = $data['noofshare']; 
        
        // AUTO-CALCULATE AMOUNT
        $amount_share = floatval($noofshare) * 100;
        
        $president = strip_tags($data['president'] ?? '');
        $emp_resign = strip_tags($data['emp_resign'] ?? '');
        
        $Region = $data['Region'];
        $Province = $data['Province'];
        $CityTown = $data['CityTown'];
        $Barangay = $data['Barangay'];
        $street = strip_tags($data['street']);
        $Address = $this->_formatAddress($street, $Barangay, $CityTown, $Province, $Region);
        
        $asof = date("Y-m-d");

        $Address = $this->_formatAddress($street, $Barangay, $CityTown, $Province, $Region);
        
        $asof = date("Y-m-d");

        // Encrypt TIN
        $tin = trim(strip_tags($data['tin'] ?? ''));
        $tinEncrypted = Encryption::encrypt($tin);

        $stmt = $this->conn->prepare("INSERT INTO tbl_shareholder_info (shareholderNo, fullname, contact_number, email, facebook_account, shareholder_type, type, noofshare, amount_share, cert_no, OtherSignatories, emp_resign, dateEncoded, Region, Province, CityTown, Barangay, street, Address, TIN) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssssssssssssssss", $newID, $fullname, $contact_number, $email, $facebook_account, $shareholder_type, $type, $noofshare, $amount_share, $cert_no, $president, $emp_resign, $asof, $Region, $Province, $CityTown, $Barangay, $street, $Address, $tinEncrypted);
        
        if(!$stmt->execute()) throw new Exception("Insert Main Failed: " . $stmt->error);
        $stmt->close();

        // Insert Issuance (Unpaid, No Cert)
        $this->_insertIssuance($newID, '', $noofshare, $asof, 'Unpaid');
        
        $this->conn->commit();
        // $this->updateCertNo($actualNo); // REMOVED: Only update when Paid/Generated

        $this->LogActivity($_SESSION['ID'] ?? null, "INSERT", $this->logModuleId, "Added Shareholder: $fullname (Pending Payment)");
        echo json_encode(array("STATUS"=>"success", "MESSAGE"=>"Shareholder Successfully added"));
    }



    private function _processMerge($data, $existing) {
        $shNo = $existing['shareholderNo'];
        $currentShares = floatval($existing['noofshare']);
        
        $addedShares = floatval($data['noofshare']);
        $newTotalShares = $currentShares + $addedShares;
        $newTotalAmount = $newTotalShares * 100; // Fixed Calc
        $asof = date("Y-m-d");

        // Insert Issuance (Unpaid, No Cert)

        // Insert Issuance (Unpaid, No Cert)
        $this->_insertIssuance($shNo, '', $addedShares, $asof, 'Unpaid');
        // $this->updateCertNo($actualNo); // REMOVED: Only update when Paid

        // Prepare Update
        $fullname = $data['shareholderName']; // Should match
        $Address = $this->_formatAddress($data['street'], $data['Barangay'], $data['CityTown'], $data['Province'], $data['Region']);

        $fullname = $data['shareholderName']; // Should match
        $Address = $this->_formatAddress($data['street'], $data['Barangay'], $data['CityTown'], $data['Province'], $data['Region']);

        // Encrypt TIN
        $tin = trim(strip_tags($data['tin'] ?? ''));
        $tinEncrypted = Encryption::encrypt($tin);

        $updateSQL = "UPDATE tbl_shareholder_info SET noofshare = ?, amount_share = ?, contact_number = ?, email = ?, facebook_account = ?, shareholder_type = ?, type = ?, Region = ?, Province = ?, CityTown = ?, Barangay = ?, street = ?, Address = ?, cert_no = ?, TIN = ? WHERE shareholderNo = ?";
        $stmt = $this->conn->prepare($updateSQL);
        // Use empty cert_no for Pending
        $cert_no = '';

        // Bind Params
        $p1=$newTotalShares; $p2=$newTotalAmount; $p3=$data['contact_number']; $p4=$data['email']; $p5=$data['facebook_account'];
        $p6=$data['shareholder_type']; $p7=$data['type']; $p8=$data['Region']; $p9=$data['Province']; $p10=$data['CityTown'];
        // p14 is cert_no
        $p11=$data['Barangay']; $p12=$data['street']; $p13=$Address; $p14=$cert_no; $p15=$tinEncrypted; $p16=$shNo;
        
        $stmt->bind_param("ssssssssssssssss", $p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8, $p9, $p10, $p11, $p12, $p13, $p14, $p15, $p16);
        
        if(!$stmt->execute()) throw new Exception("Update Merge Failed: " . $stmt->error);
        $stmt->close();
        
        $this->conn->commit();
        $this->LogActivity($_SESSION['ID'] ?? null, "UPDATE", $this->logModuleId, "Updated Shareholder: $fullname (Added $addedShares shares, Pending Payment)");
        echo json_encode(array("STATUS"=>"SUCCESS", "MESSAGE"=>"Existing record updated. New shares added (Pending Payment)."));
    }

    private function _processOverwrite($data) {
        $shareID = $data['shareID']; // Primary Key ID
        $fullname = strip_tags($data['shareholderName']);
        
        // Detect Share Increase
        $stmtGet = $this->conn->prepare("SELECT noofshare FROM tbl_shareholder_info WHERE id = ?");
        $stmtGet->bind_param("i", $shareID);
        $stmtGet->execute();
        $resGet = $stmtGet->get_result();
        $currRow = $resGet->fetch_assoc();
        $currentShares = floatval($currRow['noofshare']);
        $stmtGet->close();

        // New Value
        $newShares = floatval($data['noofshare']);
        
        // If shares INCREASED, we treat the difference as a NEW Unpaid Issuance
        if ($newShares > $currentShares) {
            $diff = $newShares - $currentShares;
            $asof = date("Y-m-d");
            // Fetch ShareholderNo (needed for issuance)
            $shNo = $data['shareholderID']; // Should be passed from form
            
            // Insert Issuance
            $this->_insertIssuance($shNo, '', $diff, $asof, 'Unpaid');
        }

        $amount_share = $newShares * 100; // Force consistency

        $Address = $this->_formatAddress($data['street'], $data['Barangay'], $data['CityTown'], $data['Province'], $data['Region']);
        $asof = date("Y-m-d"); // Date Encoded update? Maybe keep original? 
        // Logic says we update profile info, so maybe dateEncoded updates too? 
        // Let's keep existing behavior (uses current date).

        // Encrypt TIN
        $tin = trim(strip_tags($data['tin'] ?? ''));
        $tinEncrypted = Encryption::encrypt($tin);

        // Re-prepare with cert_no
        $cert_no = $data['cert_no'];
        $stmt = $this->conn->prepare("UPDATE tbl_shareholder_info SET 
            fullname=?, contact_number=?, email=?, facebook_account=?, 
            shareholder_type=?, type=?, noofshare=?, amount_share=?, cert_no=?, 
            OtherSignatories=?, emp_resign=?, 
            Region=?, Province=?, CityTown=?, Barangay=?, Street=?, Address=?, TIN=?
            WHERE id = ?");

        // Note: Removed dateEncoded from update to preserve original entry date? 
        // Or should it update? The original code updated it. 
        // Let's keep it consistent: Update dateEncoded (asof) if that was the intent.
        // Actually, previous code: `dateEncoded=?,` ... `$asof`.
        // Let's restore that.

         $stmt = $this->conn->prepare("UPDATE tbl_shareholder_info SET 
            fullname=?, contact_number=?, email=?, facebook_account=?, 
            shareholder_type=?, type=?, noofshare=?, amount_share=?, cert_no=?, 
            OtherSignatories=?, emp_resign=?, dateEncoded=?,
            Region=?, Province=?, CityTown=?, Barangay=?, Street=?, Address=?, TIN=?
            WHERE id = ?");

        $stmt->bind_param('sssssssssssssssssssi', 
            $fullname, $data['contact_number'], $data['email'], $data['facebook_account'], 
            $data['shareholder_type'], $data['type'], $newShares, $amount_share, $cert_no, 
            $data['president'], $data['emp_resign'], $asof,
            $data['Region'], $data['Province'], $data['CityTown'], $data['Barangay'], $data['street'], $Address, $tinEncrypted,
            $shareID
        );

        if (!$stmt->execute()) throw new Exception("Update Overwrite Failed: " . $stmt->error);
        $stmt->close();

        $this->conn->commit();
        $this->LogActivity($_SESSION['ID'] ?? null, "UPDATE", $this->logModuleId, "Updated Shareholder Info: $fullname");
        echo json_encode(array("STATUS" => "success", "MESSAGE" => "Shareholder successfully updated"));
    }

    private function _insertIssuance($shNo, $certNo, $shares, $dateIssued, $paymentStatus='Active') { // Default to Active? No, confusing. Let's say 'Paid' or 'Unpaid'
        // But the previous code hardcoded 'Active' for status.
        // We added payment_status column.
        // Let's keep `status` as 'Active' (record validity) and use `payment_status`.
        
        $stmt = $this->conn->prepare("INSERT INTO tbl_sharecert_issuances (shareholderNo, cert_no, noofshare, date_issued, status, payment_status) VALUES (?, ?, ?, ?, 'Active', ?)");
        $stmt->bind_param("sssss", $shNo, $certNo, $shares, $dateIssued, $paymentStatus);
        if(!$stmt->execute()) throw new Exception("Issuance Insert Failed: " . $stmt->error);
        $stmt->close();
    }

    private function _formatAddress($street, $brgy, $city, $prov, $region) {
        $parts = array_filter([$street, $brgy, $city, $prov, $region]);
        $addr = strtoupper(implode(", ", $parts));
        return (strlen($addr) > 150) ? substr($addr, 0, 150) : $addr;
    }


    // ==========================================
    //    UPDATE CONFIG
    // ==========================================
    public function UpdateConfig($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);
            $requiredFields = ['signatory1Name', 'signatory1Desig', 'signatory2Name', 'signatory2Desig', 'signatorySub2Name', 'signatorySub2Desig', 'currentCertNo'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) throw new Exception("Missing required field: $field");
            }

            $sign1 = strip_tags($_POST['signatory1Name']); $sign1Desig = strip_tags($_POST['signatory1Desig']);
            $sign2 = strip_tags($_POST['signatory2Name']); $sign2Desig = strip_tags($_POST['signatory2Desig']);
            $signSub2 = strip_tags($_POST['signatorySub2Name']); $signSub2Desig = strip_tags($_POST['signatorySub2Desig']);
            $currentCertNo = strip_tags($_POST['currentCertNo']);
            
            $this->RunConfigUpdate('CERTIFICATENO', $currentCertNo);
            $this->RunConfigUpdate('SIGNATORIES_1', $sign1, $sign1Desig);
            $this->RunConfigUpdate('SIGNATORIES_2', $sign2, $sign2Desig);
            $this->RunConfigUpdate('SIGNATORIES_SUB_2', $signSub2, $signSub2Desig);

            // --- LOGGING ---
            $userId = $_SESSION['ID'] ?? null;
            $desc = "Updated Shareholder Signatories & Cert No Configuration";
            $this->LogActivity($userId, "UPDATE", $this->logModuleId, $desc);
            // ----------------

            $this->conn->commit();
            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Configuration successfully updated"));
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    private function RunConfigUpdate($name, $val, $subVal = null){
        if($subVal !== null) {
            $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value=?, SubValue=? WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = ?");
            $stmt->bind_param('sss', $val, $subVal, $name);
        } else {
            $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value=? WHERE ConfigOwner = 'SHAREHOLDER INFO' AND ConfigName = ?");
            $stmt->bind_param('ss', $val, $name);
        }
        $stmt->execute();
        $stmt->close();
    }

    public function ToSession($data){
        $_SESSION["SHNO"] = $data["shareholderNo"];
        $_SESSION["FORMAT"] = $data["format"];
        $_SESSION["CERT_ID"] = $data["certId"] ?? null;
        echo json_encode(array("STATUS" => "SUCCESS"));
    }

    // ==========================================
    //    CHECK BACKLOG (Unpaid Issuances)
    // ==========================================
    public function CheckBacklog($data) {
        $shareholderNo = trim($data['shareholderNo'] ?? '');
        if (empty($shareholderNo)) {
            echo json_encode(['HAS_BACKLOG' => false, 'COUNT' => 0]);
            return;
        }
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) as cnt FROM tbl_sharecert_issuances 
             WHERE shareholderNo = ? AND payment_status = 'Unpaid' AND status = 'Active'"
        );
        $stmt->bind_param('s', $shareholderNo);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = intval($row['cnt'] ?? 0);
        $stmt->close();
        echo json_encode(['HAS_BACKLOG' => $count > 0, 'COUNT' => $count]);
    }

    private function SelectQuery($string){
        $data = [];
        $stmt = $this->conn->prepare($string);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
        }
        return $data;
    }
}
?>