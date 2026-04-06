<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . "/../../database/connection.php");
require_once(__DIR__ . "/../../assets/tcpdf/tcpdf.php");

class Process extends Database
{
    // CONFIGURATION: Set to ID 2 (Customer Information)
    private $logModuleId = 2; 

    // ==========================================
    //    HELPER: CHECK DUPLICATES (SMART)
    // ==========================================
    // ==========================================
    //    HELPER: CHECK DUPLICATES (SMART)
    // ==========================================
    private function CheckDuplicate($table, $conditions, $excludeId = null) {
        $sql = "SELECT id FROM $table WHERE ";
        $params = [];
        $types = "";
        $whereClauses = [];

        foreach ($conditions as $col => $val) {
            if ($val === null || $val === '') { continue; }
            $whereClauses[] = "$col = ?";
            $params[] = $val;
            $types .= "s";
        }

        if (empty($whereClauses)) { return false; }

        $sql .= implode(" AND ", $whereClauses);

        // If updating, ignore the current row ID.
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
            $types .= "i";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    // =========================================================
    //  ADVANCED DUPLICATE CHECKS (Normalization + Fuzzy)
    // =========================================================
    
    // 1. CORPORATE CHECK
    private function CheckAdvancedDuplicateCorporate($companyName, $excludeId = null) {
        // Normalize: Uppercase + AlphaNumeric Only
        $normInput = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $companyName));
        if (strlen($normInput) < 3) return;

        // Fetch all companies
        $sql = "SELECT id, companyName FROM tbl_customer_profiles WHERE companyName != ''";
        if ($excludeId) { $sql .= " AND id != " . intval($excludeId); }
        
        $result = $this->conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $dbName = $row['companyName'];
            $normDB = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $dbName));

            // Exact Match (Normalized)
            if ($normInput === $normDB) {
                throw new Exception("Company Name '$dbName' already exists (Normalized match).");
            }

            // Fuzzy (Levenshtein)
            $dist = levenshtein($normInput, $normDB);
            $len = strlen($normInput);
            
            if (abs(strlen($normInput) - strlen($normDB)) <= 2) {
                 $threshold = 0;
                 if ($len > 10) $threshold = 2;
                 elseif ($len > 4) $threshold = 1;

                 if ($dist > 0 && $dist <= $threshold) {
                     throw new Exception("Similar Company Name Found: '$dbName'. Please verify.");
                 }
            }
        }
    }

    // 2. INDIVIDUAL CHECK (First + Last)
    private function CheckAdvancedDuplicateIndividual($firstName, $lastName, $excludeId = null) {
        // Normalize Input: "LAST""FIRST"
        $normInput = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $lastName . $firstName));
        if (strlen($normInput) < 3) return;

        // Fetch all individuals
        // Optimization: We could filter by soundex or partial match in SQL first for speed.
        // For now, fetching all individuals (firstName/lastName not empty)
        $sql = "SELECT id, firstName, lastName FROM tbl_customer_profiles WHERE companyName = ''";
        if ($excludeId) { $sql .= " AND id != " . intval($excludeId); }

        $result = $this->conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $dbFirst = $row['firstName'];
            $dbLast = $row['lastName'];
            
            // Normalize DB: "LAST""FIRST"
            $normDB = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $dbLast . $dbFirst));

            // Exact Match
            if ($normInput === $normDB) {
                throw new Exception("Customer '$dbFirst $dbLast' already exists.");
            }

            // Fuzzy Match
            $dist = levenshtein($normInput, $normDB);
            $len = strlen($normInput);

            if (abs(strlen($normInput) - strlen($normDB)) <= 2) {
                 $threshold = 0;
                 if ($len > 10) $threshold = 2;
                 elseif ($len > 4) $threshold = 1;

                 if ($dist > 0 && $dist <= $threshold) {
                     throw new Exception("Similar Customer Name Found: '$dbFirst $dbLast'. Please verify.");
                 }
            }
        }
    }

    // ==========================================
    //    SAVE INFO (New Customer)
    // ==========================================
    public function SaveInfo($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);
            
            // 1. Basic Required Fields
            $requiredFields = ['customerType', 'customerNo', 'mobileNumber', 'email', 'Region', 'Province', 'CityTown', 'Barangay', 'productInfo'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // 2. Assign Variables & Apply Backend Limits
            $customerType = $data['customerType'];
            $customerNo = $data['customerNo'];
            
            // Limit Names to 50 chars
            $firstName = substr(trim(strip_tags($data['firstName'] ?? '')), 0, 50);
            $lastName = substr(trim(strip_tags($data['lastName'] ?? '')), 0, 50);
            $middleName = substr(trim(strip_tags($data['middleName'] ?? '')), 0, 50);
            
            $suffix = trim(strip_tags($data['suffix'] ?? ''));
            
            // Limit Company/Product to 255
            $companyName = substr(trim(strip_tags($data['companyName'] ?? '')), 0, 100);
            $productInfo = substr(strip_tags($data['productInfo']), 0, 100);
            
            // Limit Email to 100
            $email = substr($data['email'], 0, 100);
            
            $tin = trim($data['tin'] ?? '' );
            
            // Handle Birthdate (Convert MM/DD/YYYY to YYYY-MM-DD if needed)
            $birthdate = $data['birthdate'] ?? '';
            if(!empty($birthdate)){ $birthdate = date("Y-m-d", strtotime($birthdate)); }

            $age = $data['age'] ?? '';
            $gender = $data['gender'] ?? '';
            $mobileNumber = $data['mobileNumber'];
            $Region = $data['Region']; 
            $Province = $data['Province'];
            $CityTown = $data['CityTown'];
            $Barangay = $data['Barangay'];
            
            // Limit Street to 50
            $street = substr(strip_tags($data['street']), 0, 50);

            // 3. TIN REQUIREMENT LOGIC
            $tinExemptTypes = ['EXTERNAL CLIENT', 'OTHERS', 'OTHER CLIENT'];
            if (!in_array($customerType, $tinExemptTypes)) {
                if (empty($tin)) { throw new Exception("TIN is required for " . $customerType . "."); }
            }

            // 4. DUPLICATE CHECK LOGIC
            $customer = "";
            $fullname = "";

            if ($firstName == "" && $lastName == "") {
                // --- CORPORATE / COMPANY LOGIC ---
                $customer = $companyName;
                if (empty($companyName)) throw new Exception("Company Name is required.");

                // [A] MAIN VERIFICATION: Check TIN First (Decrypt all TINs to compare)
                if (!empty($tin)) {
                    $sql = "SELECT tinNumber FROM tbl_customer_profiles WHERE tinNumber IS NOT NULL AND tinNumber != ''";
                    $result = $this->conn->query($sql);
                    
                    while ($row = $result->fetch_assoc()) {
                        $dbTinEncrypted = $row['tinNumber'];
                        try {
                            $dbTinDecrypted = Encryption::decrypt($dbTinEncrypted);
                            if ($dbTinDecrypted === $tin) {
                                throw new Exception("Duplicate Company: The TIN '$tin' is already registered.");
                            }
                        } catch (Exception $e) {
                            // Re-throw if it's our duplicate exception
                            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                                throw $e;
                            }
                            // Otherwise, check plaintext (might be old unencrypted data)
                            if ($dbTinEncrypted === $tin) {
                                throw new Exception("Duplicate Company: The TIN '$tin' is already registered.");
                            }
                        }
                    }
                }
                
                // [B] SECONDARY VERIFICATION: Advanced Name Check
                $this->CheckAdvancedDuplicateCorporate($companyName);

            } else {
                // --- INDIVIDUAL LOGIC ---
                $fullname = trim($lastName . ' ' . $firstName . ' ' . $middleName . ' ' . $suffix);
                $customer = $fullname;

                // [VALIDATION] Birthdate & Gender required for Individuals
                if (empty($data['birthdate'])) throw new Exception("Birthdate is required for individual customers.");
                if (empty($data['gender'])) throw new Exception("Gender is required for individual customers.");

                // [A] Check Name (Advanced)
                $this->CheckAdvancedDuplicateIndividual($firstName, $lastName);

                // [B] Check TIN (Decrypt all TINs to compare)
                if (!empty($tin)) {
                    $sql = "SELECT tinNumber FROM tbl_customer_profiles WHERE tinNumber IS NOT NULL AND tinNumber != ''";
                    $result = $this->conn->query($sql);
                    
                    while ($row = $result->fetch_assoc()) {
                        $dbTinEncrypted = $row['tinNumber'];
                        try {
                            $dbTinDecrypted = Encryption::decrypt($dbTinEncrypted);
                            if ($dbTinDecrypted === $tin) {
                                throw new Exception("TIN Number '$tin' is already registered to another individual.");
                            }
                        } catch (Exception $e) {
                            // Re-throw if it's our duplicate exception
                            if (strpos($e->getMessage(), 'TIN Number') !== false || strpos($e->getMessage(), 'already registered') !== false) {
                                throw $e;
                            }
                            // Otherwise, check plaintext (might be old unencrypted data)
                            if ($dbTinEncrypted === $tin) {
                                throw new Exception("TIN Number '$tin' is already registered to another individual.");
                            }
                        }
                    }
                }
            }

            // 5. DATE LOGIC
            date_default_timezone_set('Asia/Manila');
            
            // A. clientSince (User Input)
            if (!empty($data['clientSince'])) {
                $clientSince = date("Y-m-d", strtotime($data['clientSince']));
            } else {
                $clientSince = date("Y-m-d"); 
            }

            // B. dateEncoded (System Audit - Always Today)
            $dateEncoded = date("Y-m-d"); 

            // 6. Database Save
            $address = substr($street . ' ' . $Barangay . ' , ' . $CityTown . ', ' . $Province . ', ' . $Region, 0, 150);

            // Encrypt TIN before storing
            $tinEncrypted = Encryption::encrypt($tin);

            $stmt1 = $this->conn->prepare("INSERT INTO tbl_customer_profiles (clientNo, firstName, middleName, lastName, suffix, birthdate, age, gender, mobileNumber, companyName, email, tinNumber, Region, Province, CityTown, Barangay, street, productInfo, customerType, Name, FullAddress, clientSince, dateEncoded) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            
            if (!$stmt1) throw new Exception("Database Prepare Error: " . $this->conn->error);

            $stmt1->bind_param('sssssssssssssssssssssss', $customerNo, $firstName, $middleName, $lastName, $suffix, $birthdate, $age, $gender, $mobileNumber, $companyName, $email, $tinEncrypted,$Region, $Province, $CityTown, $Barangay, $street, $productInfo, $customerType, $customer, $address, $clientSince, $dateEncoded);
            
            if (!$stmt1->execute()) {
                if ($this->conn->errno == 1062) throw new Exception("Duplicate Entry Detected.");
                throw new Exception("Save Failed: " . $stmt1->error);
            }
            
            $userId = $_SESSION['ID'] ?? null;
            $desc = "Added Customer: $customer (Client Since: $clientSince)"; 
            $this->LogActivity($userId, "INSERT", $this->logModuleId, $desc);

            $this->conn->commit();
            $stmt1->close();
            $this->conn->autocommit(true);

            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Client Information Successfully added"));

        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    // ==========================================
    //    UPDATE INFO (Returning Customer)
    // ==========================================
    public function UpdateInfo($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);

            $requiredFields = ['customerType', 'customerNo', 'mobileNumber', 'email', 'Region', 'Province', 'CityTown', 'Barangay', 'productInfo'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) { throw new Exception("Missing required field: $field"); }
            }

            $customerID = $data['customerID'] ?? ''; 
            $customerType = $data['customerType'];
            $customerNo = $data['customerNo'];
            
            // Limit Names to 50 chars
            $firstName = substr(trim($data['firstName'] ?? ''), 0, 50);
            $lastName = substr(trim($data['lastName'] ?? ''), 0, 50);
            $middleName = substr(trim($data['middleName'] ?? ''), 0, 50);
            
            $suffix = trim($data['suffix'] ?? '');
            
            // Limit Company/Product to 100
            $companyName = substr(trim($data['companyName'] ?? ''), 0, 100);
            $productInfo = substr($data['productInfo'], 0, 100);
            
            // Limit Email to 100
            $email = substr($data['email'], 0, 100);
            
            $tin = trim($data['tin'] ?? '');
            
            // Handle Birthdate
            $birthdate = $data['birthdate'] ?? '';
            if(!empty($birthdate)){ $birthdate = date("Y-m-d", strtotime($birthdate)); }

            $age = $data['age'] ?? '';
            $gender = $data['gender'] ?? '';
            $mobileNumber = $data['mobileNumber'];
            $Region = $data['Region']; 
            $Province = $data['Province'];
            $CityTown = $data['CityTown'];
            $Barangay = $data['Barangay'];
            
            // Limit Street to 50
            $street = substr($data['street'] ?? '', 0, 50);

            // 3. TIN REQUIREMENT LOGIC
            $tinExemptTypes = ['EXTERNAL CLIENT', 'OTHERS', 'OTHER CLIENT'];
            if (!in_array($customerType, $tinExemptTypes)) {
                if (empty($tin)) { throw new Exception("TIN is required for " . $customerType . "."); }
            }

            // 4. DUPLICATE CHECK LOGIC (WITH EXCLUSION)
            $customer = "";
            $fullname = "";

            if ($firstName == "" && $lastName == "") {
                // --- CORPORATE UPDATE ---
                $customer = $companyName;
                if (empty($companyName)) throw new Exception("Company Name is required.");

                if (!empty($tin)) {
                    $sql = "SELECT tinNumber, id FROM tbl_customer_profiles WHERE tinNumber IS NOT NULL AND tinNumber != '' AND id != ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param('i', $customerID);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $dbTinEncrypted = $row['tinNumber'];
                        try {
                            $dbTinDecrypted = Encryption::decrypt($dbTinEncrypted);
                            if ($dbTinDecrypted === $tin) {
                                throw new Exception("Duplicate Company: The TIN '$tin' is already used by another client.");
                            }
                        } catch (Exception $e) {
                            // Re-throw if it's our duplicate exception
                            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                                throw $e;
                            }
                            // Otherwise, check plaintext (might be old unencrypted data)
                            if ($dbTinEncrypted === $tin) {
                                throw new Exception("Duplicate Company: The TIN '$tin' is already used by another client.");
                            }
                        }
                    }
                    $stmt->close();
                }
                
                // Advanced Check
                $this->CheckAdvancedDuplicateCorporate($companyName, $customerID);

            } else {
                // --- INDIVIDUAL UPDATE ---
                $fullname = trim($firstName . ' ' . $middleName . '. ' . $lastName . ' ' . $suffix);
                $customer = $fullname;

                // [VALIDATION] Birthdate & Gender required for Individuals
                if (empty($birthdate)) throw new Exception("Birthdate is required for individual customers.");
                if (empty($gender)) throw new Exception("Gender is required for individual customers.");

                // Advanced Check
                $this->CheckAdvancedDuplicateIndividual($firstName, $lastName, $customerID);

                if (!empty($tin)) {
                    $sql = "SELECT tinNumber, id FROM tbl_customer_profiles WHERE tinNumber IS NOT NULL AND tinNumber != '' AND id != ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bind_param('i', $customerID);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $dbTinEncrypted = $row['tinNumber'];
                        try {
                            $dbTinDecrypted = Encryption::decrypt($dbTinEncrypted);
                            if ($dbTinDecrypted === $tin) {
                                throw new Exception("TIN Number '$tin' is already used by another individual.");
                            }
                        } catch (Exception $e) {
                            // Re-throw if it's our duplicate exception
                            if (strpos($e->getMessage(), 'TIN Number') !== false || strpos($e->getMessage(), 'already used') !== false) {
                                throw $e;
                            }
                            // Otherwise, check plaintext (might be old unencrypted data)
                            if ($dbTinEncrypted === $tin) {
                                throw new Exception("TIN Number '$tin' is already used by another individual.");
                            }
                        }
                    }
                    $stmt->close();
                }
            }

            // 5. DATE LOGIC
            date_default_timezone_set('Asia/Manila');
            
            // A. clientSince (Allow update if user changed it)
            if (!empty($data['clientSince'])) {
                $clientSince = date("Y-m-d", strtotime($data['clientSince']));
            } else {
                $clientSince = date("Y-m-d"); 
            }
            // B. dateEncoded -> WE DO NOT UPDATE THIS

            // 6. Database Update
            $address = substr($street . ' ' . $Barangay . ' , ' . $CityTown . ', ' . $Province . ', ' . $Region, 0, 150);
            
            // Encrypt TIN before storing
            $tinEncrypted = Encryption::encrypt($tin);
            
            // Update clientSince = ?, remove dateEncoded
            $stmt1 = $this->conn->prepare("UPDATE tbl_customer_profiles SET firstName = ?, middleName = ?, lastName = ?, suffix = ?, birthdate = ?, age = ?, gender = ?, mobileNumber = ?, companyName = ?, email = ?, tinNumber = ?, Region = ?, Province = ?, CityTown = ?, Barangay = ?, street = ?, productInfo = ?, customerType = ?, Name = ?, FullAddress = ?, clientSince = ? WHERE id = ?");
            
            if (!$stmt1) throw new Exception("Database Prepare Error: " . $this->conn->error);

            $stmt1->bind_param('sssssssssssssssssssssi', $firstName, $middleName, $lastName, $suffix, $birthdate, $age, $gender, $mobileNumber, $companyName, $email, $tinEncrypted, $Region, $Province, $CityTown, $Barangay, $street, $productInfo, $customerType, $customer, $address, $clientSince, $customerID);
            
            if (!$stmt1->execute()) {
                 throw new Exception("Update Failed: " . $stmt1->error);
            }

            $userId = $_SESSION['ID'] ?? null;
            $desc = "Updated Customer Info: $customer";
            $this->LogActivity($userId, "UPDATE", $this->logModuleId, $desc);

            $this->conn->commit();
            $stmt1->close();
            $this->conn->autocommit(true);

            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Client Information Successfully updated"));

        } catch (Exception $e) {
             $this->conn->rollback();
             echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));        
        }
    }

    // ... [KEEP GetMaintenanceChoices, LoadCustomerTypes, LoadGenders, LoadSuffixes, LoadCustomerList, GetCustomerInfo, GenerateCustomerNo FUNCTIONS HERE] ...
    private function GetMaintenanceChoices($module, $submodule, $item, $keyName){
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
        $stmt->bind_param("sss", $module, $submodule, $item);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while($row = $res->fetch_assoc()){ $list[] = $row; }
        $stmt->close();
        echo json_encode(array($keyName => $list));
    }

    public function LoadCustomerTypes(){
        $sql = "SELECT t4.module as choice_value 
                FROM tbl_maintenance_module t4
                JOIN tbl_maintenance_module t3 ON t4.module_no = t3.id_module
                JOIN tbl_maintenance_module t2 ON t3.module_no = t2.id_module
                JOIN tbl_maintenance_module t1 ON t2.module_no = t1.id_module
                WHERE t1.module_type = 0 AND t1.module = 'Profiling'
                AND t2.module_type = 1 AND t2.module = 'Customer Information'
                AND t3.module_type = 2 AND t3.module = 'Customer Type'
                AND t4.module_type = 3 AND t4.status = 1
                ORDER BY t4.module ASC";
        $list = [];
        $res = $this->conn->query($sql);
        while($row = $res->fetch_assoc()) { $list[] = $row; }

        echo json_encode(array("TYPES" => $list, "ALL_TYPES" => $list));
    }

    public function LoadGenders(){ $this->GetMaintenanceChoices("Profiling", "Customer Information", "Gender", "GENDERS"); }
    public function LoadSuffixes(){ $this->GetMaintenanceChoices("Profiling", "Customer Information", "Suffix", "SUFFIXES"); }
    
    public function LoadCustomerList(){
        header('Content-Type: application/json');
        $sql = "SELECT * FROM tbl_customer_profiles ORDER BY Name ASC"; 
        $result = $this->conn->query($sql);
        $customerlist = [];
        if ($result) { 
            while ($row = $result->fetch_assoc()) { 
                // Decrypt TIN for display
                try {
                    $row['tinNumber'] = !empty($row['tinNumber']) ? Encryption::decrypt($row['tinNumber']) : '';
                } catch (Exception $e) {
                    error_log("TIN Decryption Error in LoadCustomerList: " . $e->getMessage());
                    $row['tinNumber'] = '';
                }
                
                if(!empty($row['dateEncoded'])){
                    $row['dateEncoded'] = date("m-d-Y", strtotime($row['dateEncoded']));
                }
                $customerlist[] = $row; 
            } 
        }
        echo json_encode(array("CUSTOMERLIST" => $customerlist));
    }

    public function GetCustomerInfo($data){
        $client_no = $data['clientNo'];
        $stmt = $this->conn->prepare("SELECT * from tbl_customer_profiles WHERE clientNo = ?");
        $stmt->bind_param('s', $client_no);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Decrypt TIN for editing
            try {
                $row['tinNumber'] = !empty($row['tinNumber']) ? Encryption::decrypt($row['tinNumber']) : '';
            } catch (Exception $e) {
                error_log("TIN Decryption Error in GetCustomerInfo: " . $e->getMessage());
                $row['tinNumber'] = '';
            }
            
            echo json_encode(array("INFO" => $row, "STATUS" => "LOADED"));
        } else { echo json_encode(array("STATUS" => "EMPTY")); }
    }

    public function GenerateCustomerNo(){
        $stmt = $this->conn->prepare("SELECT clientNo FROM tbl_customer_profiles ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $new_id = "CUST000001";
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_id = $row['clientNo'];
            if (preg_match('/^CUST(\d+)$/', $last_id, $matches)) {
                $number = intval($matches[1]); 
                $number++; 
                $new_id = "CUST" . str_pad($number, 6, "0", STR_PAD_LEFT);
            }
        }
        echo json_encode(array("newCustomerNo" => $new_id));
    }
}
?>