<?php
// 1. Start Session to access User ID
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    // CONFIGURATION: Log ID
    private $logModuleId = 52; 

    public function LoadSupplierList(){
        $list = $this->SelectQuery("SELECT * FROM tbl_supplier_info ORDER BY id ASC");
        foreach ($list as &$row) {
            // Decrypt TIN for display
            $row['tinNumber'] = Encryption::decrypt($row['tinNumber']);
            
            if (!empty($row['dateEncoded']) && $row['dateEncoded'] != '0000-00-00') {
                $row['dateEncoded'] = date("m-d-Y", strtotime($row['dateEncoded']));
            } else {
                $row['dateEncoded'] = '-';
            }
        }
        echo json_encode(array("LIST" => $list));
    }

    public function GetSupplierInfo($data){
        $id = $data['supplierNo'];
        $stmt = $this->conn->prepare("SELECT * from tbl_supplier_info WHERE supplierNo = ?");
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Decrypt TIN for editing
            $row['tinNumber'] = Encryption::decrypt($row['tinNumber']);
            
            echo json_encode(array("INFO" => $row, "STATUS" => "LOADED"));
        } else {            
            echo json_encode(array("STATUS" => "EMPTY"));
        }
        $stmt->close();
    }

    public function gnrtSupID(){
        $stmt = $this->conn->prepare("SELECT supplierNo FROM tbl_supplier_info ORDER BY CAST(SUBSTRING(supplierNo, 5) AS UNSIGNED) DESC LIMIT 1;");
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_no = substr($row['supplierNo'], 4); 
            $next_number = intval($last_no) + 1;
            $supNo = 'SUPP' . str_pad($next_number, 7, "0", STR_PAD_LEFT);
        } else {
            $supNo = 'SUPP0000001'; 
        }
        $stmt->close();
        echo json_encode(array("supNo" => $supNo));
    }

    // =========================================================
    //  DYNAMIC PREFIX CHECK
    // =========================================================
    private function validatePrefixDB($fullNumber) {
        $len = 3; 
        if (substr($fullNumber, 0, 2) === '09') { $len = 4; } 
        elseif (substr($fullNumber, 0, 2) === '02') { $len = 2; }

        $prefix = substr($fullNumber, 0, $len);
        
        $stmt = $this->conn->prepare("SELECT id FROM tbl_contactnum_prefixes WHERE prefix_code = ? AND status = 1 LIMIT 1");
        $stmt->bind_param('s', $prefix);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    // =========================================================
    //  SAVE SUPPLIER
    // =========================================================
    public function SaveInfo($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);
            
            // 1. Basic Fields
            $requiredFields = ['supplierNo', 'supplierName'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) throw new Exception("Missing required field: $field");
            }

            $supplierNo = $data['supplierNo'];
            $supplierName = substr(strip_tags($data['supplierName']), 0, 100);

            // 2. Duplicate Check: Supplier Name (Advanced)
            $this->CheckAdvancedDuplicateName($supplierName);

            // 3. TIN Validation (Save formatted with dashes as requested)
            $tin = substr(trim($data['tin'] ?? ''), 0, 20); 

            // ENCRYPTED DUPLICATE CHECK
            if (!empty($tin)) {
                $sql = "SELECT tinNumber FROM tbl_supplier_info WHERE tinNumber IS NOT NULL AND tinNumber != ''";
                $result = $this->conn->query($sql);
                
                while ($row = $result->fetch_assoc()) {
                    $dbTinEncrypted = $row['tinNumber'];
                    try {
                        $dbTinDecrypted = trim(Encryption::decrypt($dbTinEncrypted));
                        if ($dbTinDecrypted === $tin) {
                            throw new Exception("TIN '$tin' is already registered.");
                        }
                    } catch (Exception $e) {
                         // Check plaintext fallback (legacy data)
                         if ($dbTinEncrypted === $tin) {
                             throw new Exception("TIN '$tin' is already registered.");
                         }
                         // Rethrow if it's our duplicate exception
                         if ($e->getMessage() === "TIN '$tin' is already registered.") throw $e;
                    }
                }
            }

            // 4. Email Validation
            $email = substr($data['email'] ?? '', 0, 50); 
            $stmtEmail = $this->conn->prepare("SELECT id FROM tbl_supplier_info WHERE email = ?");
            $stmtEmail->bind_param('s', $email);
            $stmtEmail->execute();
            if ($stmtEmail->get_result()->num_rows > 0) {
                throw new Exception("Email Address '$email' is already registered.");
            }
            $stmtEmail->close();

            // =========================================================
            //  CONTACT NUMBER LOGIC (Fixed for 'NA' Default)
            // =========================================================
            $rawMobile = $data['mobileNumber'] ?? '';
            $rawTel = $data['telNumber'] ?? '';

            // Clean inputs
            $mobileDigits = preg_replace('/[^0-9]/', '', $rawMobile);
            // ALLOW DASHES/PARENTHESES IN TELNUM: Do not strip, just trim and limit length
            $finalTel = substr(trim($rawTel), 0, 20); 

            // Treat "09" as empty (since it's the prefix)
            if ($mobileDigits === '09') { $mobileDigits = ''; }

            // LOGIC CHECK: If BOTH are empty -> STOP
            if (empty($mobileDigits) && empty($finalTel)) {
                throw new Exception("At least one contact number (Mobile or Telephone) is required.");
            }

            // DEFAULT TO 'NA' INSTEAD OF NULL TO FIX DB ERROR
            $finalMobile = 'NA';
            if (empty($finalTel)) $finalTel = 'NA';

            // --- A. PROCESS MOBILE (If Present) ---
            if (!empty($mobileDigits)) {
                if (strlen($mobileDigits) === 9) {
                    $finalMobile = '09' . $mobileDigits;
                } elseif (strlen($mobileDigits) === 11 && substr($mobileDigits, 0, 2) === '09') {
                    $finalMobile = $mobileDigits;
                } else {
                    throw new Exception("Invalid Mobile Format. Must be 11 digits starting with 09.");
                }

                if (!$this->validatePrefixDB($finalMobile)) {
                    throw new Exception("Invalid Mobile Network Prefix (" . substr($finalMobile, 0, 4) . ").");
                }

                $stmtMob = $this->conn->prepare("SELECT id FROM tbl_supplier_info WHERE mobileNumber = ?");
                $stmtMob->bind_param('s', $finalMobile);
                $stmtMob->execute();
                if ($stmtMob->get_result()->num_rows > 0) {
                    throw new Exception("Mobile Number '$finalMobile' is already registered.");
                }
                $stmtMob->close();
            }

            // --- B. PROCESS TELEPHONE (If Present) ---
            if ($finalTel !== 'NA') {
                // For Validation: Clean non-digits to check prefix/duplication if strict
                // strict check usually requires digits. 
                // However, user wants to SAVE dashes.
                // Duplicate check on 'telephoneNumber' column (which now has dashes) might fail if format varies.
                // We'll check exact match of the SAVED string for simplicity unless we want to normalize DB scan.
                // Given existing logic uses exact match on column:
                
                // Prefix Check: Extract digits
                $cleanTel = preg_replace('/[^0-9]/', '', $finalTel);
                if (!$this->validatePrefixDB($cleanTel)) {
                    throw new Exception("Invalid Telephone Area Code.");
                }

                $stmtTel = $this->conn->prepare("SELECT id FROM tbl_supplier_info WHERE telephoneNumber = ?");
                $stmtTel->bind_param('s', $finalTel);
                $stmtTel->execute();
                if ($stmtTel->get_result()->num_rows > 0) {
                    throw new Exception("Telephone Number is already registered.");
                }
                $stmtTel->close();
            }

            // 5. Address & Misc
            $fb = substr(strip_tags($data['facebookAccount'] ?? 'N/A'), 0, 100);
            $Region   = $data['Region'] ?? '';
            $Province = $data['Province'] ?? '';
            $CityTown = $data['CityTown'] ?? '';
            $Barangay = $data['Barangay'] ?? '';
            $street   = substr(trim(strip_tags($data['street'] ?? '')), 0, 50);

            if(empty($Region) || empty($Province) || empty($CityTown) || empty($Barangay) || empty($street)) {
                throw new Exception("Complete address is required.");
            }
            
            $fullAddress = substr("$street, $Barangay, $CityTown, $Province, $Region", 0, 150);
            $asof = date("Y-m-d"); 
            $supplierSince = !empty($data['supplierSince']) ? date("Y-m-d", strtotime($data['supplierSince'])) : date("Y-m-d");

            // Encrypt TIN before storing
            $tinEncrypted = Encryption::encrypt($tin);
            
            // Insert
            $stmt = $this->conn->prepare("INSERT INTO tbl_supplier_info (supplierNo, supplierName, tinNumber, email, mobileNumber, telephoneNumber, facebookAccount, Region, Province, CityTown, Barangay, street, fullAddress, dateEncoded, supplierSince) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            
            if (!$stmt) {
                throw new Exception("Database Prepare Error: " . $this->conn->error);
            }
            
            $stmt->bind_param('sssssssssssssss', $supplierNo, $supplierName, $tinEncrypted, $email, $finalMobile, $finalTel, $fb, $Region, $Province, $CityTown, $Barangay, $street, $fullAddress, $asof, $supplierSince);
            
            if ($stmt->execute()){
                $userId = $_SESSION['ID'] ?? null;
                $this->LogActivity($userId, "INSERT", $this->logModuleId, "Added Supplier: $supplierName");
                $this->conn->commit();
                echo json_encode(array("STATUS" => "success", "MESSAGE" => "Supplier Added Successfully"));
            } else {
                throw new Exception("Database Error: " . $stmt->error);
            }
            $stmt->close();
            $this->conn->autocommit(true);

        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    // =========================================================
    //  UPDATE SUPPLIER
    // =========================================================
    public function UpdateInfo($data){
        $this->validateCSRF();
        try {
            $this->conn->autocommit(false);
            $id = $data['supplierID'];
            
            if(empty($id)) throw new Exception("Supplier ID missing.");

            // 1. Validate Uniques (excluding current ID)
            $supplierName = substr($data['supplierName'], 0, 100);
            
            // Advanced Name Check
            $this->CheckAdvancedDuplicateName($supplierName, $id);

            $tin = substr(trim($data['tin']), 0, 20);
            
            // ENCRYPTED DUPLICATE CHECK (Exclude Current ID)
            if (!empty($tin)) {
                $sql = "SELECT id, tinNumber FROM tbl_supplier_info WHERE tinNumber IS NOT NULL AND tinNumber != '' AND id != " . intval($id);
                $result = $this->conn->query($sql);
                
                while ($row = $result->fetch_assoc()) {
                    $dbTinEncrypted = $row['tinNumber'];
                    try {
                        $dbTinDecrypted = trim(Encryption::decrypt($dbTinEncrypted));
                        if ($dbTinDecrypted === $tin) {
                            throw new Exception("TIN '$tin' is already registered by another supplier.");
                        }
                    } catch (Exception $e) {
                        // Check plaintext fallback (legacy data)
                        if ($dbTinEncrypted === $tin) {
                            throw new Exception("TIN '$tin' is already registered by another supplier.");
                        }
                        
                        // Rethrow if it's our duplicate exception
                        if ($e->getMessage() === "TIN '$tin' is already registered by another supplier.") {
                            throw $e;
                        }
                        // Ignore true decryption errors
                    }
                }
            }

            $email = substr($data['email'], 0, 50);
            if ($this->CheckDuplicate('tbl_supplier_info', ['email' => $email], $id)) {
                throw new Exception("Email is already taken.");
            }

            // =========================================================
            //  CONTACT NUMBER LOGIC (Update)
            // =========================================================
            $rawMobile = $data['mobileNumber'] ?? '';
            $rawTel = $data['telNumber'] ?? '';

            $mobileDigits = preg_replace('/[^0-9]/', '', $rawMobile);
            // Allow dashes
            $finalTel = substr(trim($rawTel), 0, 20);

            // FIX: Treat "09" as empty
            if ($mobileDigits === '09') { $mobileDigits = ''; }

            if (empty($mobileDigits) && empty($finalTel)) {
                throw new Exception("At least one contact number (Mobile or Telephone) is required.");
            }

            // DEFAULT TO 'NA'
            $finalMobile = 'NA';
            if(empty($finalTel)) $finalTel = 'NA';

            // A. Update Mobile (If present)
            if (!empty($mobileDigits)) {
                if (strlen($mobileDigits) === 9) $finalMobile = '09' . $mobileDigits;
                elseif (strlen($mobileDigits) === 11) $finalMobile = $mobileDigits;
                else throw new Exception("Invalid Mobile Format.");

                if (!$this->validatePrefixDB($finalMobile)) throw new Exception("Invalid Mobile Prefix.");

                if ($this->CheckDuplicate('tbl_supplier_info', ['mobileNumber' => $finalMobile], $id)) {
                    throw new Exception("Mobile Number is already taken.");
                }
            }

            // B. Update Tel (If present)
            if ($finalTel !== 'NA') {
                 // Prefix Check: Extract digits
                $cleanTel = preg_replace('/[^0-9]/', '', $finalTel);
                if (!$this->validatePrefixDB($cleanTel)) throw new Exception("Invalid Telephone Area Code.");

                if ($this->CheckDuplicate('tbl_supplier_info', ['telephoneNumber' => $finalTel], $id)) {
                    throw new Exception("Telephone Number is already taken.");
                }
            }

            // Address & Misc
            $fb = substr($data['facebookAccount'] ?? 'N/A', 0, 100);
            $Region = $data['Region'];
            $Province = $data['Province'];
            $City = $data['CityTown'];
            $Brgy = $data['Barangay'];
            $street = substr(trim($data['street']), 0, 50);
            
            $fullAddress = substr("$street, $Brgy, $City, $Province, $Region", 0, 150);
            $supplierSince = !empty($data['supplierSince']) ? date("Y-m-d", strtotime($data['supplierSince'])) : null;

            // Encrypt TIN before storing
            $tinEncrypted = Encryption::encrypt($tin);
            
            // Update Query
            $stmt = $this->conn->prepare("UPDATE tbl_supplier_info SET supplierName=?, tinNumber=?, email=?, mobileNumber=?, telephoneNumber=?, facebookAccount=?, Region=?, Province=?, CityTown=?, Barangay=?, street=?, fullAddress=?, supplierSince=? WHERE id=?");
            $stmt->bind_param('sssssssssssssi', $supplierName, $tinEncrypted, $email, $finalMobile, $finalTel, $fb, $Region, $Province, $City, $Brgy, $street, $fullAddress, $supplierSince, $id);
            
            if ($stmt->execute()){
                $userId = $_SESSION['ID'] ?? null;
                $this->LogActivity($userId, "UPDATE", $this->logModuleId, "Updated Supplier: $supplierName");
                $this->conn->commit();
                echo json_encode(array("STATUS" => "success", "MESSAGE" => "Supplier Updated"));
            } else {
                throw new Exception("SQL Error: " . $stmt->error);
            }
            $stmt->close();
            $this->conn->autocommit(true);

        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array("STATUS" => "ERROR", "MESSAGE" => $e->getMessage()));
        }
    }

    // =========================================================
    //  ADVANCED DUPLICATE CHECK (Normalization + Fuzzy)
    // =========================================================
    private function CheckAdvancedDuplicateName($inputName, $excludeId = null) {
        // 1. Normalize Input: Uppercase, Remove Non-Alphanumeric
        $normInput = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $inputName));
        
        if (strlen($normInput) < 3) return; // Skip too short names

        // 2. Fetch ALL Existing Names (Optimized: ID & Name only)
        // Note: For very large datasets (10k+), this should be optimized to SQL filtering first.
        $sql = "SELECT id, supplierName FROM tbl_supplier_info";
        if ($excludeId) {
            $sql .= " WHERE id != " . intval($excludeId);
        }
        
        $existing = $this->SelectQuery($sql);

        foreach ($existing as $row) {
            $dbName = $row['supplierName'];
            $normDB = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $dbName));

            // A. Exact Match (Normalized)
            if ($normInput === $normDB) {
                throw new Exception("Duplicate Supplier Name Detected: '$dbName' (Normalized match).");
            }

            // B. Fuzzy Match (Levenshtein)
            // Logic: Allow 1 error per ~5 characters, max 2 or 3 depending on length.
            $dist = levenshtein($normInput, $normDB);
            $len = strlen($normInput);
            
            // Threshold: 0 means exact matching (already caught), 1-2 means very close
            // Only apply fuzzy check if lengths are somewhat similar
            if (abs(strlen($normInput) - strlen($normDB)) <= 2) {
                 // Strict Threshold: 
                 // If len < 5, must be exact (dist=0). 
                 // If len 5-10, allow 1 edit.
                 // If len > 10, allow 2 edits.
                 $threshold = 0;
                 if ($len > 10) $threshold = 2;
                 elseif ($len > 4) $threshold = 1;

                 if ($dist > 0 && $dist <= $threshold) {
                     throw new Exception("Similar Supplier Name Found: '$dbName'. Please verify.");
                 }
            }
        }
    }

    // Helper to check duplicates excluding current ID
    private function CheckDuplicate($table, $conditions, $excludeId) {
        $sql = "SELECT id FROM $table WHERE ";
        $params = [];
        $types = "";
        $clauses = [];
        
        foreach($conditions as $col => $val) {
            $clauses[] = "$col = ?";
            $params[] = $val;
            $types .= "s";
        }
        $sql .= implode(" AND ", $clauses) . " AND id != ?";
        $params[] = $excludeId;
        $types .= "i";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = $res->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function SelectQuery($string){
        $data = [];
        $stmt = $this->conn->prepare($string);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close(); 
        return $data;
    }
}
?>