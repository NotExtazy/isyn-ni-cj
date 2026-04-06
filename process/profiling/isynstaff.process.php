<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    // CONFIGURATION: Set to ID 18 (Isynergies Staff)
    private $logModuleId = 18; 

    // --- Load Choices from Maintenance ---
    public function LoadDropdowns(){
        $module = "Profiling";
        $submodule = "Isynergies Staff";

        $status = $this->GetChoices($module, $submodule, "Employee Status");
        $designation = $this->GetChoices($module, $submodule, "Designation");

        echo json_encode(array(
            "STATUS_OPTS" => $status,
            "DESIG_OPTS" => $designation
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

    public function LoadStaff(){
        $list = $this->SelectQuery("SELECT * FROM tbl_isynergies_info ORDER BY id_staff DESC");
        
        // Format dates for display (MM/DD/YYYY)
        foreach ($list as &$row) {
            // Handle birthdate
            if (!empty($row['birthdate']) && $row['birthdate'] != '00-00-0000' && $row['birthdate'] != '0000-00-00') {
                // Try Y-m-d first (MySQL Standard)
                $dateObj = DateTime::createFromFormat('Y-m-d', $row['birthdate']);
                if (!$dateObj) {
                    // Fallback to m-d-Y just in case of legacy data
                    $dateObj = DateTime::createFromFormat('m-d-Y', $row['birthdate']);
                }
                
                if ($dateObj) {
                    $row['birthdate'] = $dateObj->format('m/d/Y');
                } else {
                    $row['birthdate'] = '-';
                }
            } else {
                $row['birthdate'] = '-';
            }
            
            // Handle date_hired
            if (!empty($row['date_hired']) && $row['date_hired'] != '00-00-0000' && $row['date_hired'] != '0000-00-00') {
                // Try Y-m-d first
                $dateObj = DateTime::createFromFormat('Y-m-d', $row['date_hired']);
                if (!$dateObj) {
                     // Fallback
                    $dateObj = DateTime::createFromFormat('m-d-Y', $row['date_hired']);
                }

                if ($dateObj) {
                    $row['date_hired'] = $dateObj->format('m/d/Y');
                } else {
                    $row['date_hired'] = '-';
                }
            } else {
                $row['date_hired'] = '-';
            }
            
            // Handle dateEncoded (stored as Y-m-d in MySQL)
            if (!empty($row['dateEncoded']) && $row['dateEncoded'] != '0000-00-00') {
                $dateObj = DateTime::createFromFormat('Y-m-d', $row['dateEncoded']);
                if ($dateObj) {
                    $row['dateEncoded'] = $dateObj->format('m-d-Y');
                } else {
                    $row['dateEncoded'] = '-';
                }
            } else {
                $row['dateEncoded'] = '-';
            }
            
            // Decrypt government IDs for display
            $row['tin_num'] = Encryption::decrypt($row['tin_num']);
            $row['sss_num'] = Encryption::decrypt($row['sss_num']);
            $row['philhealth_num'] = Encryption::decrypt($row['philhealth_num']);
            $row['pag_ibig'] = Encryption::decrypt($row['pag_ibig']);
        }
        
        echo json_encode(array("LIST" => $list));
    }

    public function GetStaffInfo($data){
        $id = $data['employeeNo'];
        $stmt = $this->conn->prepare("SELECT * from tbl_isynergies_info WHERE employee_no = ?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Decrypt government IDs for editing
            $row['tin_num'] = Encryption::decrypt($row['tin_num']);
            $row['sss_num'] = Encryption::decrypt($row['sss_num']);
            $row['philhealth_num'] = Encryption::decrypt($row['philhealth_num']);
            $row['pag_ibig'] = Encryption::decrypt($row['pag_ibig']);
            
            // Format dates for display (MM/DD/YYYY)
            if (!empty($row['birthdate']) && $row['birthdate'] != '00-00-0000' && $row['birthdate'] != '0000-00-00') {
                $dateObj = DateTime::createFromFormat('Y-m-d', $row['birthdate']);
                if (!$dateObj) $dateObj = DateTime::createFromFormat('m-d-Y', $row['birthdate']); // Fallback

                if ($dateObj) {
                    $row['birthdate'] = $dateObj->format('m/d/Y');
                } else {
                    $row['birthdate'] = '';
                }
            } else {
                $row['birthdate'] = '';
            }
            
            if (!empty($row['date_hired']) && $row['date_hired'] != '00-00-0000' && $row['date_hired'] != '0000-00-00') {
                $dateObj = DateTime::createFromFormat('Y-m-d', $row['date_hired']);
                if (!$dateObj) $dateObj = DateTime::createFromFormat('m-d-Y', $row['date_hired']); // Fallback

                if ($dateObj) {
                    $row['date_hired'] = $dateObj->format('m/d/Y');
                } else {
                    $row['date_hired'] = '';
                }
            } else {
                $row['date_hired'] = '';
            }
            
            echo json_encode(array("INFO" => $row, "STATUS" => "LOADED"));
        } else {            
            echo json_encode(array("STATUS" => "EMPTY"));
        }
        $stmt->close();
    }

    // =========================================================
    //  NEW: DYNAMIC PREFIX CHECK (DB BASED)
    // =========================================================
    private function validatePrefixDB($fullNumber) {
        $prefix = substr($fullNumber, 0, 4); // Get 09XX
        
        $stmt = $this->conn->prepare("SELECT id FROM tbl_contactnum_prefixes WHERE prefix_code = ? AND status = 1 LIMIT 1");
        $stmt->bind_param('s', $prefix);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        
        $stmt->close();
        return $exists;
    }

    private function checkDuplicateID($column, $value, $excludeId = null) {
        if (empty($value)) return;
        
        $sql = "SELECT id_staff FROM tbl_isynergies_info WHERE $column = ?";
        if ($excludeId) {
            $sql .= " AND id_staff != ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        if ($excludeId) {
            $stmt->bind_param("si", $value, $excludeId);
        } else {
            $stmt->bind_param("s", $value);
        }
        
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        
        if ($exists) {
            // Map column names to readable names for the error message
            $niceNames = [
                'employee_no' => 'Employee Number',
                'tin_num' => 'TIN',
                'sss_num' => 'SSS Number',
                'philhealth_num' => 'PhilHealth Number',
                'pag_ibig' => 'Pag-IBIG Number'
            ];
            $name = $niceNames[$column] ?? $column;
            throw new Exception("$name '$value' is already in use by another record.");
        }
    }

    // =========================================================
    //  FUZZY DUPLICATE NAME CHECK (Levenshtein Distance)
    // =========================================================
    private function CheckFuzzyDuplicateName($firstName, $middleName, $lastName, $excludeId = null) {
        // Normalize: Combine names and remove special chars
        $normInput = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . $middleName . $lastName));
        if (strlen($normInput) < 3) return; // Skip very short names

        // Fetch all staff (exclude current if updating)
        $sql = "SELECT id_staff, first_name, middle_name, last_name FROM tbl_isynergies_info";
        if ($excludeId) {
            $sql .= " WHERE id_staff != " . intval($excludeId);
        }
        $result = $this->conn->query($sql);
        
        // Check if query was successful
        if (!$result) {
            throw new Exception("Database query failed: " . $this->conn->error);
        }
        
        while ($row = $result->fetch_assoc()) {
            $dbName = $row['first_name'] . $row['middle_name'] . $row['last_name'];
            $normDB = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $dbName));

            // Exact Match (Normalized) - Throw exception
            if ($normInput === $normDB) {
                throw new Exception("Staff member '{$row['first_name']} {$row['middle_name']} {$row['last_name']}' already exists (normalized match).");
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
                    throw new Exception("Similar Staff Name Found: '{$row['first_name']} {$row['middle_name']} {$row['last_name']}'. Please verify.");
                }
            }
        }
    }


    // =========================================================
    //  ENCRYPTED DUPLICATE CHECK (Decrypt & Compare)
    // =========================================================
    private function CheckEncryptedDuplicate($col, $inputValue, $excludeId = null) {
        if (empty($inputValue)) return false;

        $sql = "SELECT id_staff, $col FROM tbl_isynergies_info WHERE $col IS NOT NULL AND $col != ''";
        if ($excludeId) {
             $sql .= " AND id_staff != " . intval($excludeId);
        }

        $result = $this->conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $encrypted = $row[$col];
            try {
                $decrypted = trim(Encryption::decrypt($encrypted));
                if ($decrypted === $inputValue) {
                    return true;
                }
            } catch (Exception $e) {
                // Check plaintext fallback (legacy data)
                if ($encrypted === $inputValue) {
                    return true;
                }
                // Ignore decryption errors
            }
        }
        return false;
    }

    // =========================================================
    //  SAVE STAFF (With Logs)
    // =========================================================
    public function SaveInfo($data){
        $this->validateCSRF();
        try {
            if (!$this->conn) throw new Exception("Database connection failed.");
            $this->conn->autocommit(false);
            
            if (empty($data['employee_no']) || empty($data['first_name']) || empty($data['last_name'])) {
                throw new Exception("Missing required fields.");
            }

            $employee_no = trim(strip_tags($_POST['employee_no']));

            // 1. Check Duplicate Employee No
            $this->checkDuplicateID('employee_no', $employee_no);

            $first_name = strtoupper(trim(strip_tags($_POST['first_name'])));
            $middle_name = strtoupper(trim(strip_tags($_POST['middle_name'])));
            $last_name = strtoupper(trim(strip_tags($_POST['last_name'])));

            // NEW: Check for fuzzy duplicates (typos, special chars)
            $this->CheckFuzzyDuplicateName($first_name, $middle_name, $last_name);

            $contact_num = $_POST['contact_num'];

            // Mobile Validation
            $mobileDigits = preg_replace('/[^0-9]/', '', $contact_num);

            if (empty($mobileDigits)) throw new Exception("Contact Number is required.");

            $finalMobile = '';
            if (strlen($mobileDigits) === 9) {
                $finalMobile = '09' . $mobileDigits;
            } elseif (strlen($mobileDigits) === 11 && substr($mobileDigits, 0, 2) === '09') {
                $finalMobile = $mobileDigits;
            } else {
                throw new Exception("Invalid Contact Number. Must be 11 digits starting with 09.");
            }

            // CHECK PREFIX
            if (!$this->validatePrefixDB($finalMobile)) {
                $invalidPrefix = substr($finalMobile, 0, 4);
                throw new Exception("Invalid Network Prefix ($invalidPrefix). Please enter a valid PH mobile number.");
            }

            // Date Formatting (MM-DD-YYYY for DB storage)
            // Note: Text inputs now send MM/DD/YYYY format
            $birthdateInput = $_POST['birthdate'];
            $dateHiredInput = $_POST['date_hired'];
            
            // Parse MM/DD/YYYY format
            $birthdateObj = DateTime::createFromFormat('m/d/Y', $birthdateInput);
            $dateHiredObj = DateTime::createFromFormat('m/d/Y', $dateHiredInput);
            
            if (!$birthdateObj || !$dateHiredObj) {
                throw new Exception("Invalid date format. Use MM/DD/YYYY.");
            }
            
            $today = new DateTime();
            if ($birthdateObj > $today || $dateHiredObj > $today) {
                throw new Exception("Dates cannot be in the future.");
            }

            // Convert to Y-m-d for database (MySQL Standard)
            $birthdate = $birthdateObj->format('Y-m-d');
            $date_hired = $dateHiredObj->format('Y-m-d');
            
            $age = $_POST['age'] ?? '';
            $email_address = strip_tags($_POST['email_address']);
            $designation = $_POST['designation'];
            $employee_status = $_POST['employee_status'];
            
            // Capture Inputs (Raw/Formatted)
            $pag_ibig_raw = $_POST['pag_ibig'] ?? '';
            $tin_raw = $_POST['tin'] ?? '';
            $philhealth_raw = $_POST['philhealth'] ?? '';
            $sss_raw = $_POST['sss'] ?? '';

            // Clean for Validation (Digit Count)
            $pag_ibig_clean = preg_replace('/[^0-9]/', '', $pag_ibig_raw);
            $tin_clean = preg_replace('/[^0-9]/', '', $tin_raw);
            $philhealth_clean = preg_replace('/[^0-9]/', '', $philhealth_raw);
            $sss_clean = preg_replace('/[^0-9]/', '', $sss_raw);

            // Validation (Check digit length)
            if (strlen($tin_clean) !== 12) throw new Exception("TIN must be 12 digits.");
            if (strlen($sss_clean) !== 10) throw new Exception("SSS must be 10 digits.");
            if (strlen($philhealth_clean) !== 12) throw new Exception("PhilHealth must be 12 digits.");
            if (strlen($pag_ibig_clean) !== 12) throw new Exception("Pag-IBIG must be 12 digits.");

            // Prepare for Saving (Formatted, max 20 chars)
            $pag_ibig = substr(trim($pag_ibig_raw), 0, 20);
            $tin = substr(trim($tin_raw), 0, 20);
            $philhealth = substr(trim($philhealth_raw), 0, 20);
            $sss = substr(trim($sss_raw), 0, 20);

            // 2. Check Duplicate IDs (Using Formatted Value)
            // Use Encrypted Check for these fields
            if ($this->CheckEncryptedDuplicate('tin_num', $tin)) throw new Exception("TIN '$tin' is already in use.");
            if ($this->CheckEncryptedDuplicate('sss_num', $sss)) throw new Exception("SSS '$sss' is already in use.");
            if ($this->CheckEncryptedDuplicate('philhealth_num', $philhealth)) throw new Exception("PhilHealth '$philhealth' is already in use.");
            if ($this->CheckEncryptedDuplicate('pag_ibig', $pag_ibig)) throw new Exception("Pag-IBIG '$pag_ibig' is already in use.");
            
            $Region = $_POST['Region'];
            $Province = $_POST['Province'];
            $CityTown = $_POST['CityTown'];
            $Barangay = $_POST['Barangay'];
            $Street = strip_tags($_POST['Street']);
          
            // Address Truncation
            $address = substr($Street . ' ' . $Barangay . ' , ' . $CityTown . ', ' . $Province . ', ' . $Region, 0, 150);
            
            date_default_timezone_set('Asia/Manila');
            $dateEncoded = date("Y-m-d"); // MySQL standard date format
    
            // Encrypt government IDs before storing
            $tinEncrypted = Encryption::encrypt($tin);
            $sssEncrypted = Encryption::encrypt($sss);
            $philhealthEncrypted = Encryption::encrypt($philhealth);
            $pagibigEncrypted = Encryption::encrypt($pag_ibig);
    
            $sql = "INSERT INTO tbl_isynergies_info (
                employee_no, first_name, middle_name, last_name, birthdate, age, date_hired, 
                email_address, contact_num, employee_status, designation, pag_ibig, tin_num, 
                philhealth_num, sss_num, region, province, city, barangay, street, 
                full_address, dateEncoded
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $stmt1 = $this->conn->prepare($sql);
            if (!$stmt1) throw new Exception("Prepare failed: " . $this->conn->error);
            
            $stmt1->bind_param(
                'ssssssssssssssssssssss', 
                $employee_no, $first_name, $middle_name, $last_name, $birthdate, $age, $date_hired, 
                $email_address, $finalMobile, $employee_status, $designation, $pagibigEncrypted, $tinEncrypted, 
                $philhealthEncrypted, $sssEncrypted, $Region, $Province, $CityTown, $Barangay, $Street, 
                $address, $dateEncoded
            );

            if (!$stmt1->execute()) {
                throw new Exception("Execute failed: " . $stmt1->error);
            }

            // --- LOGGING ---
            $userId = $_SESSION['ID'] ?? null;
            $desc = "Added Staff: $first_name $last_name ($employee_no)";
            $this->LogActivity($userId, "INSERT", $this->logModuleId, $desc);
            // ----------------

            $this->conn->commit();
            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Staff Information Successfully added"));
            $stmt1->close();
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    // =========================================================
    //  UPDATE STAFF (With Logs)
    // =========================================================
    public function UpdateInfo($data){
        $this->validateCSRF();
        try {
            if (!$this->conn) throw new Exception("Database connection failed.");
            $this->conn->autocommit(false);

            if (empty($data['employee_no'])) throw new Exception("Missing Employee No.");

            $id_staff = $_POST['id_staff'];
            $employee_no = trim(strip_tags($_POST['employee_no'])); 
            
            // 1. Check Duplicate Employee No (Exclude Current)
            $this->checkDuplicateID('employee_no', $employee_no, $id_staff);

            $first_name = strtoupper(trim(strip_tags($_POST['first_name'])));
            $middle_name = strtoupper(trim(strip_tags($_POST['middle_name'])));
            $last_name = strtoupper(trim(strip_tags($_POST['last_name'])));

            // NEW: Check for fuzzy duplicates (exclude current record)
            $this->CheckFuzzyDuplicateName($first_name, $middle_name, $last_name, $id_staff);

            $contact_num = $_POST['contact_num'];

            // Mobile Validation
            $mobileDigits = preg_replace('/[^0-9]/', '', $contact_num);
            
            if (empty($mobileDigits)) throw new Exception("Contact Number is required.");

            $finalMobile = '';
            if (strlen($mobileDigits) === 9) {
                $finalMobile = '09' . $mobileDigits;
            } elseif (strlen($mobileDigits) === 11 && substr($mobileDigits, 0, 2) === '09') {
                $finalMobile = $mobileDigits;
            } else {
                throw new Exception("Invalid Contact Number. Must be 11 digits starting with 09.");
            }

            // CHECK PREFIX
            if (!$this->validatePrefixDB($finalMobile)) {
                $invalidPrefix = substr($finalMobile, 0, 4);
                throw new Exception("Invalid Network Prefix ($invalidPrefix). Please check the number.");
            }

            // Date Formatting (MM-DD-YYYY for DB storage)
            // Note: Text inputs now send MM/DD/YYYY format
            $birthdateInput = $_POST['birthdate'];
            $dateHiredInput = $_POST['date_hired'];

            // Parse MM/DD/YYYY format
            $birthdateObj = DateTime::createFromFormat('m/d/Y', $birthdateInput);
            $dateHiredObj = DateTime::createFromFormat('m/d/Y', $dateHiredInput);
            
            if (!$birthdateObj || !$dateHiredObj) {
                throw new Exception("Invalid date format. Use MM/DD/YYYY.");
            }
            
            $today = new DateTime();
            if ($birthdateObj > $today || $dateHiredObj > $today) {
                throw new Exception("Dates cannot be in the future.");
            }
            
            // Convert to Y-m-d for database (MySQL Standard)
            $birthdate = $birthdateObj->format('Y-m-d');
            $date_hired = $dateHiredObj->format('Y-m-d');

            $age = $_POST['age'] ?? '';
            $email_address = strip_tags($_POST['email_address']);
            $designation = $_POST['designation'];
            $employee_status = $_POST['employee_status'];
            
            // Capture Inputs (Raw/Formatted)
            $pag_ibig_raw = $_POST['pag_ibig'] ?? '';
            $tin_raw = $_POST['tin'] ?? '';
            $philhealth_raw = $_POST['philhealth'] ?? '';
            $sss_raw = $_POST['sss'] ?? '';

            // Clean for Validation
            $pag_ibig_clean = preg_replace('/[^0-9]/', '', $pag_ibig_raw);
            $tin_clean = preg_replace('/[^0-9]/', '', $tin_raw);
            $philhealth_clean = preg_replace('/[^0-9]/', '', $philhealth_raw);
            $sss_clean = preg_replace('/[^0-9]/', '', $sss_raw);

            // Validation
            if (strlen($tin_clean) !== 12) throw new Exception("TIN must be 12 digits.");
            if (strlen($sss_clean) !== 10) throw new Exception("SSS must be 10 digits.");
            if (strlen($philhealth_clean) !== 12) throw new Exception("PhilHealth must be 12 digits.");
            if (strlen($pag_ibig_clean) !== 12) throw new Exception("Pag-IBIG must be 12 digits.");

            // Prepare for Saving (Formatted)
            $pag_ibig = substr(trim($pag_ibig_raw), 0, 20);
            $tin = substr(trim($tin_raw), 0, 20);
            $philhealth = substr(trim($philhealth_raw), 0, 20);
            $sss = substr(trim($sss_raw), 0, 20);

            // 2. Check Duplicate IDs (Exclude Current)
            // Use Encrypted Check for these fields
            if ($this->CheckEncryptedDuplicate('tin_num', $tin, $id_staff)) throw new Exception("TIN '$tin' is already in use.");
            if ($this->CheckEncryptedDuplicate('sss_num', $sss, $id_staff)) throw new Exception("SSS '$sss' is already in use.");
            if ($this->CheckEncryptedDuplicate('philhealth_num', $philhealth, $id_staff)) throw new Exception("PhilHealth '$philhealth' is already in use.");
            if ($this->CheckEncryptedDuplicate('pag_ibig', $pag_ibig, $id_staff)) throw new Exception("Pag-IBIG '$pag_ibig' is already in use.");

            $Region = $_POST['Region'];
            $Province = $_POST['Province'];
            $CityTown = $_POST['CityTown'];
            $Barangay = $_POST['Barangay'];
            $Street = strip_tags($_POST['Street'] ?? '');
            
            // Address Truncation
            $address = substr($Street . ' ' . $Barangay . ' , ' . $CityTown . ', ' . $Province . ', ' . $Region, 0, 150);
            
            date_default_timezone_set('Asia/Manila');
            $asof = date("m-d-Y", strtotime("now"));
    
            // Encrypt government IDs before storing
            $tinEncrypted = Encryption::encrypt($tin);
            $sssEncrypted = Encryption::encrypt($sss);
            $philhealthEncrypted = Encryption::encrypt($philhealth);
            $pagibigEncrypted = Encryption::encrypt($pag_ibig);
    
            $sql = "UPDATE tbl_isynergies_info SET 
                employee_no=?, first_name=?, middle_name=?, last_name=?, birthdate=?, age=?, date_hired=?, 
                email_address=?, contact_num=?, employee_status=?, designation=?, pag_ibig=?, 
                tin_num=?, philhealth_num=?, sss_num=?, region=?, province=?, city=?, 
                barangay=?, street=?, full_address=? 
                WHERE id_staff = ?";

            $stmt1 = $this->conn->prepare($sql);
            if (!$stmt1) throw new Exception("Prepare failed: " . $this->conn->error);
            
            $stmt1->bind_param(
                'sssssssssssssssssssssi', 
                $employee_no, $first_name, $middle_name, $last_name, $birthdate, $age, $date_hired, 
                $email_address, $finalMobile, $employee_status, $designation, $pagibigEncrypted, 
                $tinEncrypted, $philhealthEncrypted, $sssEncrypted, $Region, $Province, $CityTown, $Barangay, $Street, 
                $address, $id_staff
            );
            
            if (!$stmt1->execute()) throw new Exception("Execute failed: " . $stmt1->error);
            
            // --- LOGGING ---
            $userId = $_SESSION['ID'] ?? null;
            $desc = "Updated Staff Info: $first_name $last_name";
            $this->LogActivity($userId, "UPDATE", $this->logModuleId, $desc);
            // ----------------

            $this->conn->commit();
            echo json_encode(array("STATUS" => "success", "MESSAGE" => "Staff Information Successfully updated"));
            
            $stmt1->close();
            $this->conn->autocommit(true);
        } catch (Exception $e) {
             $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));            
        }
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