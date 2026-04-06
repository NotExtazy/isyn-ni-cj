<?php
/**
 * Loan Transaction Process Handler
 * Handles all loan transaction operations including new loans and renewals
 */

class LoanTransactionProcess {
    private $db;
    private $conn;
    
    public function __construct($database) {
        $this->db = $database;
        $this->conn = $database->conn;
    }
    
    /**
     * Get all clients for dropdown
     */
    public function getClients() {
        try {
            $query = "SELECT DISTINCT ClientNo, FullName 
                     FROM tbl_dynalists 
                     WHERE ClientNo IS NOT NULL AND ClientNo != '' 
                     ORDER BY FullName ASC";
            
            $result = $this->conn->query($query);
            $clients = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $clients[] = $row;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode($clients);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get client loans for display in table
     */
    public function getClientLoans($clientId) {
        try {
            $query = "SELECT LoanID, LoanAmount, Balance, DateRelease, DateMature, 
                            IFNULL(Interest, 0) as Interest,
                            IFNULL(CBU, 0) as CBU,
                            IFNULL(EF, 0) as EF,
                            IFNULL(MBA, 0) as MBA,
                            IFNULL(LoanStatus, 'ACTIVE') as LoanStatus
                     FROM tbl_loans 
                     WHERE ClientNo = ? 
                     ORDER BY DateRelease DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $clientId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $html = '';
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $statusClass = $this->getStatusClass($row['LoanStatus']);
                    $balance = number_format($row['Balance'], 2);
                    $loanAmount = number_format($row['LoanAmount'], 2);
                    
                    $html .= "<tr data-loan-id='{$row['LoanID']}' data-balance='{$row['Balance']}'>";
                    $html .= "<td>{$row['LoanID']}</td>";
                    $html .= "<td>₱{$loanAmount}</td>";
                    $html .= "<td>₱{$balance}</td>";
                    $html .= "<td>{$row['DateRelease']}</td>";
                    $html .= "<td>{$row['DateMature']}</td>";
                    $html .= "<td><span class='badge {$statusClass}'>{$row['LoanStatus']}</span></td>";
                    $html .= "</tr>";
                }
            } else {
                $html = "<tr><td colspan='6' class='text-center'>No loans found for this client</td></tr>";
            }
            
            echo $html;
            
        } catch (Exception $e) {
            echo "<tr><td colspan='6' class='text-center text-danger'>Error: {$e->getMessage()}</td></tr>";
        }
    }
    
    /**
     * Check if client is eligible for new loan
     */
    public function checkClientEligibility($clientId) {
        try {
            // Check for outstanding loans (Balance > 0)
            $query = "SELECT COUNT(*) as outstanding_count 
                     FROM tbl_loans 
                     WHERE ClientNo = ? AND Balance > 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $clientId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $eligible = ($row['outstanding_count'] == 0);
            
            header('Content-Type: application/json');
            echo json_encode([
                'eligible' => $eligible,
                'outstanding_count' => $row['outstanding_count'],
                'message' => $eligible ? 'Client is eligible for new loan' : 'Client has outstanding loans'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Check for outstanding loans
     */
    public function checkOutstandingLoans($clientNo) {
        try {
            $query = "SELECT COUNT(*) as count FROM tbl_loans WHERE ClientNo = ? AND Balance > 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $clientNo);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            header('Content-Type: application/json');
            echo json_encode([
                'hasOutstanding' => ($row['count'] > 0),
                'count' => $row['count']
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get loan products from tbl_loansetup
     */
    public function getProducts() {
        try {
            $query = "SELECT DISTINCT Product FROM tbl_loansetup WHERE Product IS NOT NULL ORDER BY Product";
            $result = $this->conn->query($query);
            $products = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row['Product'];
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode($products);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get payment modes from tbl_maintenance
     */
    public function getModes() {
        try {
            $query = "SELECT DISTINCT Mode FROM tbl_maintenance WHERE Mode IS NOT NULL ORDER BY Mode";
            $result = $this->conn->query($query);
            $modes = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $modes[] = $row['Mode'];
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode($modes);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get staff members from tbl_po
     */
    public function getStaff() {
        try {
            $query = "SELECT DISTINCT POName FROM tbl_po WHERE POName IS NOT NULL ORDER BY POName";
            $result = $this->conn->query($query);
            $staff = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $staff[] = $row['POName'];
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode($staff);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get loan details for renewal
     */
    public function getLoanDetails($loanId) {
        try {
            $query = "SELECT * FROM tbl_loans WHERE LoanID = ? LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $loanId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $loan = $result->fetch_assoc();
                header('Content-Type: application/json');
                echo json_encode($loan);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Loan not found']);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get loan availment count for client
     */
    public function getLoanAvailment($clientId) {
        try {
            $query = "SELECT COUNT(*) as count FROM tbl_loans WHERE ClientNo = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $clientId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            header('Content-Type: application/json');
            echo json_encode(['count' => $row['count']]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get loan transaction details
     */
    public function getLoanTransaction($userId) {
        try {
            // This would typically get transaction details
            // For now, return empty array
            header('Content-Type: application/json');
            echo json_encode([]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Save new loan
     */
    public function saveLoan($data) {
        try {
            // Start transaction
            $this->conn->begin_transaction();
            
            // Map form field names to database field names
            $clientNo = $data['add_userId'] ?? $data['userId'] ?? $data['client_no'] ?? '';
            $loanAmount = floatval($data['add_amount'] ?? $data['amount'] ?? $data['loan_amount'] ?? 0);
            $product = $data['add_product'] ?? $data['product'] ?? '';
            $mode = $data['add_mode'] ?? $data['mode'] ?? '';
            $staff = $data['add_poFco'] ?? $data['poFco'] ?? $data['staff'] ?? '';
            $term = intval($data['add_termRate'] ?? $data['termRate'] ?? $data['term'] ?? 0);
            $interest = floatval($data['add_interest'] ?? $data['interest'] ?? 0);
            
            // Calculate dates
            $dateRelease = date('Y-m-d'); // Today
            $dateMature = date('Y-m-d', strtotime("+{$term} months")); // Add term months
            
            // Generate LoanID if not provided
            $loanId = $data['loan_id'] ?? $this->generateLoanID($clientNo);
            
            // Get fees (default to 0 if not provided)
            $cbu = floatval($data['add_cbu'] ?? $data['cbu'] ?? 0);
            $ef = floatval($data['add_ef'] ?? $data['ef'] ?? 0);
            $mba = floatval($data['add_mba'] ?? $data['mba'] ?? 0);
            
            // Insert into tbl_loans
            $query = "INSERT INTO tbl_loans (
                ClientNo, LoanID, LoanAmount, Balance, DateRelease, DateMature,
                Interest, CBU, EF, MBA, Product, Mode, Staff, LoanStatus, Term
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE', ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssddssddddsssi",
                $clientNo,
                $loanId,
                $loanAmount,
                $loanAmount, // Initial balance = loan amount
                $dateRelease,
                $dateMature,
                $interest,
                $cbu,
                $ef,
                $mba,
                $product,
                $mode,
                $staff,
                $term
            );
            
            $stmt->execute();
            $stmt->close();
            
            // Also insert into tbl_aging for immediate tracking
            $agingQuery = "INSERT INTO tbl_aging (
                ClientNo, LoanID, FULLNAME, LOANAMOUNT, AmountDueAsOf, 
                DATERELEASE, DATEMATURE, DueDate, INTEREST, CBUFTL, EF, MBA,
                PRODUCT, MODE, TERM, PROGRAM, PO
            ) SELECT 
                ?, ?, 
                CONCAT(COALESCE(LastName, ''), ', ', COALESCE(FirstName, ''), ' ', COALESCE(MiddleName, '')) as FULLNAME,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                COALESCE(Program, 'N/A'), 
                ?
            FROM tbl_clientinfo 
            WHERE ClientNo = ?";
            
            $agingStmt = $this->conn->prepare($agingQuery);
            $agingStmt->bind_param("ssddsssddddssiss",
                $clientNo,
                $loanId,
                $loanAmount,
                $loanAmount, // AmountDueAsOf = initial loan amount (balance)
                $dateRelease,
                $dateMature,
                $dateRelease, // DueDate = release date initially
                $interest,
                $cbu,
                $ef,
                $mba,
                $product,
                $mode,
                $term,
                $staff, // PO field
                $clientNo
            );
            
            $agingStmt->execute();
            $agingStmt->close();
            
            // Commit transaction
            $this->conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success', 
                'message' => 'Loan saved successfully',
                'loan_id' => $loanId,
                'date_release' => $dateRelease,
                'date_mature' => $dateMature
            ]);
            
        } catch (Exception $e) {
            $this->conn->rollback();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Generate unique Loan ID
     */
    private function generateLoanID($clientNo) {
        // Format: CLIENTNO-YYYYMMDD-SEQUENCE
        $date = date('Ymd');
        $prefix = $clientNo . '-' . $date;
        
        // Check for existing loans with same prefix
        $query = "SELECT LoanID FROM tbl_loans WHERE LoanID LIKE ? ORDER BY LoanID DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $searchPattern = $prefix . '%';
        $stmt->bind_param("s", $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastId = $row['LoanID'];
            // Extract sequence number and increment
            $parts = explode('-', $lastId);
            $sequence = isset($parts[2]) ? intval($parts[2]) + 1 : 1;
        } else {
            $sequence = 1;
        }
        
        return $prefix . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Save loan renewal
     */
    public function saveRenewal($data) {
        try {
            // Start transaction
            $this->conn->begin_transaction();
            
            // Insert new loan record
            $this->saveLoan($data);
            
            // Update old loan with renewal ID
            if (isset($data['old_loan_id'])) {
                $query = "UPDATE tbl_loans SET RenewID = ? WHERE LoanID = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ss", $data['loan_id'], $data['old_loan_id']);
                $stmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Loan renewed successfully']);
            
        } catch (Exception $e) {
            $this->conn->rollback();
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Helper: Get status CSS class
     */
    private function getStatusClass($status) {
        $classes = [
            'PAID' => 'bg-success',
            'PARTIAL' => 'bg-warning',
            'ACTIVE' => 'bg-primary',
            'OVERDUE' => 'bg-danger',
            'WRITEOFF' => 'bg-dark'
        ];
        
        return $classes[$status] ?? 'bg-secondary';
    }
}
?>
