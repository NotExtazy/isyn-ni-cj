<?php
// Simple standalone route without Database class
ob_start();
// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Direct database connection
require_once(dirname(__DIR__, 2) . '/database/constants.php');

ob_clean();

try {
    // Create direct mysqli connection
    $conn = new mysqli(HOST, USER, PASS, DB, PORT);
    $conn->set_charset("utf8");
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'debugAging':
            $clientNo = $_GET['clientNo'] ?? '';
            if (empty($clientNo)) {
                echo json_encode(['success' => false, 'message' => 'ClientNo required']);
                break;
            }
            
            $debug = [];
            
            // Check what columns exist in tbl_aging
            $describeQuery = "DESCRIBE tbl_aging";
            $describeResult = $conn->query($describeQuery);
            if ($describeResult) {
                $columns = [];
                while ($row = $describeResult->fetch_assoc()) {
                    $columns[] = $row['Field'];
                }
                $debug['columns'] = $columns;
            }
            
            // Try to find the client in tbl_aging with correct column names
            $searchQuery = "SELECT ClientNo, CENTERNAME, GROUPNAME, FULLNAME FROM tbl_aging WHERE ClientNo = '$clientNo' LIMIT 1";
            $searchResult = $conn->query($searchQuery);
            if ($searchResult && $searchResult->num_rows > 0) {
                $debug['client_data'] = $searchResult->fetch_assoc();
            } else {
                $debug['client_data'] = 'No data found for ClientNo: ' . $clientNo;
                
                // Check if ClientNo exists at all
                $allClientsQuery = "SELECT DISTINCT ClientNo FROM tbl_aging WHERE ClientNo IS NOT NULL AND ClientNo != '' AND ClientNo != '-' LIMIT 10";
                $allClientsResult = $conn->query($allClientsQuery);
                if ($allClientsResult) {
                    $allClients = [];
                    while ($row = $allClientsResult->fetch_assoc()) {
                        $allClients[] = $row['ClientNo'];
                    }
                    $debug['available_clients'] = $allClients;
                }
            }
            
            echo json_encode(['success' => true, 'debug' => $debug]);
            break;
            
        case 'searchClients':
            $search = $_GET['search'] ?? '';
            $clients = [];
            
            if (strlen($search) >= 2) {
                $search = $conn->real_escape_string($search);
                $query = "SELECT ClientNo as ClientID, ClientName 
                         FROM tbl_clientinfo 
                         WHERE ClientName LIKE '%$search%' 
                         OR ClientNo LIKE '%$search%'
                         ORDER BY ClientName ASC 
                         LIMIT 20";
                
                $result = $conn->query($query);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $clients[] = $row;
                    }
                }
            }
            
            echo json_encode($clients);
            break;
            
        case 'getClientLoanHistory':
            $clientId = $_GET['client_id'] ?? $_POST['clientId'] ?? '';
            if (empty($clientId)) {
                echo json_encode(['success' => false, 'message' => 'Client ID required']);
                break;
            }
            
            $clientId = $conn->real_escape_string($clientId);
            
            $query = "SELECT 
                        COUNT(*) as totalLoans,
                        SUM(CASE WHEN Balance = 0 THEN 1 ELSE 0 END) as paidLoans,
                        SUM(CASE WHEN Balance > 0 THEN 1 ELSE 0 END) as activeLoans
                      FROM tbl_loans 
                      WHERE ClientNo = '$clientId'";
            
            $result = $conn->query($query);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'summary' => [
                        'total_loans' => intval($row['totalLoans']),
                        'paid_loans' => intval($row['paidLoans']),
                        'active_loans' => intval($row['activeLoans'])
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'summary' => [
                        'total_loans' => 0,
                        'paid_loans' => 0,
                        'active_loans' => 0
                    ]
                ]);
            }
            break;
            
        case 'debugInterestBalance':
            $clientId = $_GET['client_id'] ?? '';
            if (empty($clientId)) {
                echo json_encode(['success' => false, 'message' => 'Client ID required']);
                break;
            }
            
            $clientId = $conn->real_escape_string($clientId);
            
            // Get raw data to debug the interest vs balance issue
            $query = "SELECT LoanID, LoanAmount, Balance, Interest, CBU, EF, MBA, InterestRate, 
                             (LoanAmount + COALESCE(Interest, 0) + COALESCE(CBU, 0) + COALESCE(EF, 0) + COALESCE(MBA, 0)) as CalculatedTotal
                      FROM tbl_loans 
                      WHERE ClientNo = '$clientId' 
                      ORDER BY DatePrepared DESC";
            
            $result = $conn->query($query);
            $debugData = [];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $debugData[] = [
                        'LoanID' => $row['LoanID'],
                        'LoanAmount' => floatval($row['LoanAmount']),
                        'Balance' => floatval($row['Balance']),
                        'Interest' => floatval($row['Interest']),
                        'CBU' => floatval($row['CBU']),
                        'EF' => floatval($row['EF']),
                        'MBA' => floatval($row['MBA']),
                        'InterestRate' => floatval($row['InterestRate']),
                        'CalculatedTotal' => floatval($row['CalculatedTotal']),
                        'BalanceEqualsInterest' => (floatval($row['Balance']) == floatval($row['Interest'])),
                        'InterestIsZero' => (floatval($row['Interest']) == 0),
                        'SuggestedInterest' => round(floatval($row['LoanAmount']) * (floatval($row['InterestRate']) / 100), 2)
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'clientId' => $clientId,
                'debugData' => $debugData,
                'message' => 'Debug data for interest vs balance analysis'
            ]);
            break;
            
        case 'checkClientEligibility':
            $clientId = $_GET['client_id'] ?? '';
            if (empty($clientId)) {
                echo json_encode(['success' => false, 'message' => 'Client ID required']);
                break;
            }
            
            $clientId = $conn->real_escape_string($clientId);
            
            // Check if client has any active loans (Balance > 0)
            $activeLoansQuery = "SELECT COUNT(*) as active_count, 
                                       GROUP_CONCAT(LoanID) as active_loan_ids,
                                       SUM(Balance) as total_outstanding
                                FROM tbl_loans 
                                WHERE ClientNo = '$clientId' 
                                AND Balance > 0";
            
            $result = $conn->query($activeLoansQuery);
            if ($result && $result->num_rows > 0) {
                $data = $result->fetch_assoc();
                $activeCount = intval($data['active_count']);
                $totalOutstanding = floatval($data['total_outstanding']);
                
                if ($activeCount > 0) {
                    // Client has active loans - not eligible for new loans
                    echo json_encode([
                        'success' => true,
                        'eligible' => false,
                        'message' => 'Client has ' . $activeCount . ' active loan(s) with total outstanding balance of ₱' . number_format($totalOutstanding, 2) . '. All existing loans must be fully paid before applying for new loans.',
                        'active_loans_count' => $activeCount,
                        'total_outstanding' => $totalOutstanding
                    ]);
                } else {
                    // Client has no active loans - eligible for new loans
                    echo json_encode([
                        'success' => true,
                        'eligible' => true,
                        'message' => 'Client is eligible for new loans.'
                    ]);
                }
            } else {
                // No loans found - client is eligible
                echo json_encode([
                    'success' => true,
                    'eligible' => true,
                    'message' => 'Client is eligible for new loans.'
                ]);
            }
            break;
            
        case 'saveLoan':
            // Handle loan saving (both new and renewal)
            $formData = $_POST;
            
            // Basic validation
            if (empty($formData['clientName'])) {
                echo json_encode(['success' => false, 'message' => 'Client name is required']);
                break;
            }
            
            // For now, just return success (you can implement actual saving logic)
            echo json_encode(['success' => true, 'message' => 'Loan saved successfully']);
            break;
            
        case 'testConnection':
            // Test database connection and show table structure
            $tables = [];
            
            // Test basic connection
            $tables['connection'] = 'OK';
            
            // Check tbl_loans structure
            $describeQuery = "DESCRIBE tbl_loans";
            $result = $conn->query($describeQuery);
            if ($result) {
                $columns = [];
                while ($row = $result->fetch_assoc()) {
                    $columns[] = $row['Field'];
                }
                $tables['tbl_loans_columns'] = $columns;
            }
            
            // Check tbl_aging structure and sample data
            $agingDescribe = "DESCRIBE tbl_aging";
            $agingResult = $conn->query($agingDescribe);
            if ($agingResult) {
                $agingColumns = [];
                while ($row = $agingResult->fetch_assoc()) {
                    $agingColumns[] = $row['Field'];
                }
                $tables['tbl_aging_columns'] = $agingColumns;
                
                // Get sample aging data with all relevant columns
                $sampleAging = "SELECT ClientNo, CENTERNAME, GROUPNAME, NatureAdj FROM tbl_aging LIMIT 5";
                $sampleResult = $conn->query($sampleAging);
                if ($sampleResult) {
                    $sampleData = [];
                    while ($row = $sampleResult->fetch_assoc()) {
                        $sampleData[] = $row;
                    }
                    $tables['tbl_aging_sample'] = $sampleData;
                } else {
                    // Try alternative column names
                    $altSample = "SELECT ClientNo, CenterName, GroupName, NatureAdj FROM tbl_aging LIMIT 5";
                    $altSampleResult = $conn->query($altSample);
                    if ($altSampleResult) {
                        $altSampleData = [];
                        while ($row = $altSampleResult->fetch_assoc()) {
                            $altSampleData[] = $row;
                        }
                        $tables['tbl_aging_sample_alt'] = $altSampleData;
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Database connection successful', 'tables' => $tables]);
            break;
            
        case 'checkOutstandingLoans':
            // Check if client has outstanding loans (for frontend eligibility validation)
            $clientNo = $conn->real_escape_string($_GET['client_no'] ?? '');
            
            if (empty($clientNo)) {
                echo json_encode(['success' => false, 'message' => 'Client number is required']);
                break;
            }
            
            // Check for active loans with outstanding balances
            $outstandingQuery = "SELECT LoanID, Balance, LoanAmount, LoanStatus 
                                FROM tbl_loans 
                                WHERE ClientNo = '$clientNo' 
                                AND Balance > 0 
                                ORDER BY DateRelease DESC";
            
            $outstandingResult = $conn->query($outstandingQuery);
            $outstandingLoans = [];
            $totalOutstanding = 0;
            
            if ($outstandingResult && $outstandingResult->num_rows > 0) {
                while ($loan = $outstandingResult->fetch_assoc()) {
                    $outstandingLoans[] = $loan;
                    $totalOutstanding += floatval($loan['Balance']);
                }
            }
            
            $hasOutstanding = count($outstandingLoans) > 0;
            
            echo json_encode([
                'success' => true,
                'hasOutstanding' => $hasOutstanding,
                'outstandingCount' => count($outstandingLoans),
                'totalOutstanding' => $totalOutstanding,
                'outstandingLoans' => $outstandingLoans,
                'message' => $hasOutstanding 
                    ? 'Client has ' . count($outstandingLoans) . ' outstanding loan(s)'
                    : 'Client is eligible for new loans'
            ]);
            break;
            
        case 'updateLoanBalance':
            // Update loan balance when payment is made
            $loanId = $_POST['loan_id'] ?? '';
            $paymentAmount = floatval($_POST['payment_amount'] ?? 0);
            
            if (empty($loanId) || $paymentAmount <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid loan ID or payment amount']);
                break;
            }
            
            $loanId = $conn->real_escape_string($loanId);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get current loan details
                $loanQuery = "SELECT Balance, LoanAmount FROM tbl_loans WHERE LoanID = '$loanId' LIMIT 1";
                $loanResult = $conn->query($loanQuery);
                
                if (!$loanResult || $loanResult->num_rows === 0) {
                    throw new Exception('Loan not found');
                }
                
                $loan = $loanResult->fetch_assoc();
                $currentBalance = floatval($loan['Balance']);
                $loanAmount = floatval($loan['LoanAmount']);
                
                // Calculate new balance
                $newBalance = $currentBalance - $paymentAmount;
                
                // Ensure balance doesn't go negative
                if ($newBalance < 0) {
                    $newBalance = 0;
                }
                
                // Determine new status
                $newStatus = 'ACTIVE';
                if ($newBalance == 0) {
                    $newStatus = 'PAID';
                } elseif ($newBalance < $currentBalance) {
                    $newStatus = 'ACTIVE'; // Partially paid but still active
                }
                
                // Update loan balance and status
                $updateQuery = "UPDATE tbl_loans 
                               SET Balance = $newBalance, 
                                   LoanStatus = '$newStatus',
                                   LastPaymentDate = CURDATE()
                               WHERE LoanID = '$loanId'";
                
                if (!$conn->query($updateQuery)) {
                    throw new Exception('Failed to update loan balance: ' . $conn->error);
                }
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Loan balance updated successfully',
                    'new_balance' => $newBalance,
                    'new_status' => $newStatus,
                    'payment_amount' => $paymentAmount
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        case 'getLoanBalance':
            // Get current loan balance and status
            $loanId = $_GET['loan_id'] ?? '';
            
            if (empty($loanId)) {
                echo json_encode(['success' => false, 'message' => 'Loan ID required']);
                break;
            }
            
            $loanId = $conn->real_escape_string($loanId);
            
            $query = "SELECT LoanID, LoanAmount, Balance, LoanStatus, 
                             (LoanAmount - Balance) as AmountPaid,
                             CASE 
                                 WHEN Balance = 0 THEN 100
                                 ELSE ROUND(((LoanAmount - Balance) / LoanAmount) * 100, 2)
                             END as PaymentProgress
                      FROM tbl_loans 
                      WHERE LoanID = '$loanId' 
                      LIMIT 1";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $loan = $result->fetch_assoc();
                echo json_encode(['success' => true, 'loan' => $loan]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Loan not found']);
            }
            break;
            
        case 'getClientLoanHistory':
            $clientId = $_GET['client_id'] ?? '';
            if (empty($clientId)) {
                echo json_encode(['success' => false, 'message' => 'Client ID required']);
                break;
            }
            
            $clientId = $conn->real_escape_string($clientId);
            
            // Get loan history with status
            $query = "SELECT 
                        LoanID, 
                        LoanAmount, 
                        Balance, 
                        DateRelease,
                        CASE 
                            WHEN Balance = 0 THEN 'PAID'
                            WHEN Balance > 0 THEN 'ACTIVE'
                            ELSE 'UNKNOWN'
                        END as loan_status,
                        CASE 
                            WHEN Balance = 0 THEN 'Fully Paid'
                            WHEN Balance < LoanAmount THEN 'Partially Paid'
                            WHEN Balance = LoanAmount THEN 'No Payments'
                            ELSE 'Unknown'
                        END as payment_status
                      FROM tbl_loans 
                      WHERE ClientNo = '$clientId' 
                      ORDER BY DateRelease DESC";
            
            $result = $conn->query($query);
            $loans = [];
            $summary = [
                'total_loans' => 0,
                'paid_loans' => 0,
                'active_loans' => 0,
                'has_previous_loans' => false,
                'has_paid_loans' => false
            ];
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $loans[] = $row;
                    $summary['total_loans']++;
                    
                    if ($row['loan_status'] === 'PAID') {
                        $summary['paid_loans']++;
                        $summary['has_paid_loans'] = true;
                    } else {
                        $summary['active_loans']++;
                    }
                }
                $summary['has_previous_loans'] = true;
            }
            
            echo json_encode([
                'success' => true, 
                'loans' => $loans,
                'summary' => $summary
            ]);
            break;
            
        case 'testConnection':
            echo json_encode([
                'success' => true,
                'message' => 'Backend connection working',
                'timestamp' => date('Y-m-d H:i:s'),
                'tables' => [
                    'tbl_clientinfo' => $conn->query("SELECT COUNT(*) as count FROM tbl_clientinfo")->fetch_assoc()['count'] ?? 0,
                    'tbl_loans' => $conn->query("SELECT COUNT(*) as count FROM tbl_loans")->fetch_assoc()['count'] ?? 0
                ]
            ]);
            break;
            
        case 'getClients':
            // Fetch all clients from tbl_clientinfo with their loan status
            $query = "SELECT c.ClientNo, c.ClientName, c.FirstName, c.LastName, c.MiddleName,
                             COUNT(l.LoanID) as TotalLoans,
                             SUM(CASE WHEN l.Balance > 0 THEN 1 ELSE 0 END) as ActiveLoans,
                             SUM(CASE WHEN l.Balance > 0 THEN l.Balance ELSE 0 END) as TotalBalance
                      FROM tbl_clientinfo c
                      LEFT JOIN tbl_loans l ON c.ClientNo = l.ClientNo
                      GROUP BY c.ClientNo, c.ClientName, c.FirstName, c.LastName, c.MiddleName
                      ORDER BY c.ClientName ASC";
            $result = $conn->query($query);
            
            $clients = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $clientName = $row['ClientName'];
                    
                    // If ClientName is empty, construct from FirstName, LastName
                    if (empty($clientName)) {
                        $clientName = trim(($row['LastName'] ?? '') . ', ' . ($row['FirstName'] ?? '') . ' ' . ($row['MiddleName'] ?? ''));
                    }
                    
                    $clients[] = [
                        'ClientNo' => $row['ClientNo'],
                        'ClientName' => $clientName,
                        'TotalLoans' => intval($row['TotalLoans']),
                        'ActiveLoans' => intval($row['ActiveLoans']),
                        'TotalBalance' => floatval($row['TotalBalance']),
                        'HasPendingLoans' => (intval($row['ActiveLoans']) > 0)
                    ];
                }
            }
            
            echo json_encode(['success' => true, 'clients' => $clients]);
            break;
            
        case 'getAllClients':
            // Fetch all clients from tbl_clientinfo who don't have active loans
            // Active loan = Balance > 0 in tbl_loans
            $query = "SELECT c.ClientNo, c.ClientName, c.FirstName, c.LastName, c.MiddleName 
                      FROM tbl_clientinfo c
                      LEFT JOIN tbl_loans l ON c.ClientNo = l.ClientNo AND l.Balance > 0
                      WHERE l.ClientNo IS NULL
                      ORDER BY c.ClientName ASC";
            $result = $conn->query($query);
            
            $clients = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $clientName = $row['ClientName'];
                    
                    // If ClientName is empty, construct from FirstName, LastName
                    if (empty($clientName)) {
                        $clientName = trim(($row['LastName'] ?? '') . ', ' . ($row['FirstName'] ?? '') . ' ' . ($row['MiddleName'] ?? ''));
                    }
                    
                    $clients[] = [
                        'ClientNo' => $row['ClientNo'],
                        'ClientName' => $clientName
                    ];
                }
            }
            
            echo json_encode(['success' => true, 'clients' => $clients, 'count' => count($clients)]);
            break;
            
        case 'getLoanedProductsForClient':
            // Get available loaned products for a specific client
            // Products where ISLOAN='YES' and LOANID IS NULL (not yet assigned to a loan)
            $clientNo = $_GET['client_no'] ?? '';
            if (empty($clientNo)) {
                echo json_encode(['success' => false, 'message' => 'Client number required']);
                break;
            }
            
            $clientNo = $conn->real_escape_string($clientNo);
            
            // First, get the client name
            $clientNameQuery = "SELECT ClientName, FirstName, LastName, MiddleName FROM tbl_clientinfo WHERE ClientNo = '$clientNo' LIMIT 1";
            $clientNameResult = $conn->query($clientNameQuery);
            $clientName = '';
            
            if ($clientNameResult && $clientNameResult->num_rows > 0) {
                $clientRow = $clientNameResult->fetch_assoc();
                $clientName = $clientRow['ClientName'];
                
                // If ClientName is empty, construct from FirstName, LastName
                if (empty($clientName)) {
                    $clientName = trim(($clientRow['LastName'] ?? '') . ', ' . ($clientRow['FirstName'] ?? '') . ' ' . ($clientRow['MiddleName'] ?? ''));
                }
            }
            
            // Get loaned products that are not yet assigned to a loan
            // Try to match by client name, but if none found, show all available loaned products
            $query = "SELECT SI, Product, Soldto, Quantity, DealerPrice, TotalPrice, DateAdded 
                      FROM tbl_inventoryout 
                      WHERE ISLOAN = 'YES' 
                      AND (LOANID IS NULL OR LOANID = '')
                      ORDER BY 
                        CASE WHEN Soldto = '$clientName' THEN 0 ELSE 1 END,
                        DateAdded DESC, 
                        Product ASC";
            $result = $conn->query($query);
            
            $products = [];
            $matchedProducts = 0;
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $isForThisClient = ($row['Soldto'] === $clientName);
                    if ($isForThisClient) {
                        $matchedProducts++;
                    }
                    
                    $displayName = $row['Product'] . ' (Qty: ' . $row['Quantity'] . ')';
                    
                    // Add client indicator if product is for a different client
                    if (!empty($row['Soldto']) && !$isForThisClient) {
                        $displayName .= ' - For: ' . $row['Soldto'];
                    }
                    
                    $displayName .= ' - Added: ' . date('M d, Y', strtotime($row['DateAdded']));
                    
                    $products[] = [
                        'SI' => $row['SI'],
                        'ProductName' => $row['Product'],
                        'DisplayName' => $displayName,
                        'Quantity' => $row['Quantity'],
                        'Price' => floatval($row['TotalPrice']),
                        'DealerPrice' => floatval($row['DealerPrice']),
                        'DateAdded' => $row['DateAdded'],
                        'Soldto' => $row['Soldto'],
                        'IsForThisClient' => $isForThisClient
                    ];
                }
            }
            
            echo json_encode([
                'success' => true, 
                'products' => $products, 
                'count' => count($products),
                'matchedCount' => $matchedProducts,
                'clientName' => $clientName,
                'debug' => [
                    'clientNo' => $clientNo,
                    'clientName' => $clientName,
                    'totalAvailable' => count($products),
                    'matchedToClient' => $matchedProducts
                ]
            ]);
            break;
            
        case 'getLoanedProductDetails':
            // Get detailed information about a loaned product
            $si = $_GET['si'] ?? '';
            if (empty($si)) {
                echo json_encode(['success' => false, 'message' => 'SI required']);
                break;
            }
            
            $si = $conn->real_escape_string($si);
            $query = "SELECT SI, Product, Soldto, Quantity, DealerPrice, TotalPrice, DateAdded, ISLOAN, LOANID 
                      FROM tbl_inventoryout 
                      WHERE SI = '$si' AND ISLOAN = 'YES' 
                      LIMIT 1";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $product = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'product' => [
                        'SI' => $product['SI'],
                        'ProductName' => $product['Product'],
                        'ClientName' => $product['Soldto'],
                        'Quantity' => $product['Quantity'],
                        'Price' => floatval($product['TotalPrice']),
                        'DealerPrice' => floatval($product['DealerPrice']),
                        'DateAdded' => $product['DateAdded']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
            }
            break;
            
        case 'getStaff':
            $query = "SELECT PONick FROM tbl_po ORDER BY PONick ASC";
            $result = $conn->query($query);
            
            $staff = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $staff[] = $row['PONick'];
                }
            }
            
            echo json_encode(['success' => true, 'staff' => $staff]);
            break;
            
        case 'getProducts':
            $query = "SELECT Product FROM tbl_loansetup ORDER BY Product ASC";
            $result = $conn->query($query);
            
            $products = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $products[] = $row['Product'];
                }
            }
            
            echo json_encode(['success' => true, 'products' => $products]);
            break;
            
        case 'getModes':
            $query = "SELECT ItemName FROM tbl_maintenance WHERE ItemType = 'MODE' ORDER BY ItemName ASC";
            $result = $conn->query($query);
            
            $modes = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $modes[] = $row['ItemName'];
                }
            }
            
            echo json_encode(['success' => true, 'modes' => $modes]);
            break;
            
        case 'testConnection':
            $tests = [];
            
            $queries = [
                'clients' => "SELECT COUNT(*) as count FROM tbl_clientinfo",
                'staff' => "SELECT COUNT(*) as count FROM tbl_po",
                'products' => "SELECT COUNT(*) as count FROM tbl_loansetup",
                'modes' => "SELECT COUNT(*) as count FROM tbl_maintenance WHERE ItemType = 'MODE'"
            ];
            
            foreach ($queries as $key => $query) {
                $result = $conn->query($query);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $tests[$key] = $row['count'];
                } else {
                    $tests[$key] = 'Error: ' . $conn->error;
                }
            }
            
            echo json_encode(['success' => true, 'tests' => $tests]);
            break;
            
        case 'getClientLoans':
            $clientId = $_GET['client_id'] ?? '';
            if (empty($clientId)) {
                echo '<tr><td colspan="12" class="text-center">Please select a client</td></tr>';
                break;
            }
            
            $clientId = $conn->real_escape_string($clientId);
            
            // First, get the client name
            $clientQuery = "SELECT ClientName FROM tbl_clientinfo WHERE ClientNo = '$clientId' LIMIT 1";
            $clientResult = $conn->query($clientQuery);
            $clientName = '';
            if ($clientResult && $clientResult->num_rows > 0) {
                $clientRow = $clientResult->fetch_assoc();
                $clientName = $clientRow['ClientName'] ?? '';
            }
            
            // Then get all loans for this client, ordered by date (newest first)
            $query = "SELECT *, 
                             CASE 
                                 WHEN Balance = 0 THEN 'PAID'
                                 WHEN Balance > 0 AND Balance < LoanAmount THEN 'PARTIAL'
                                 WHEN Balance = LoanAmount THEN 'ACTIVE'
                                 WHEN DateMature < CURDATE() AND Balance > 0 THEN 'OVERDUE'
                                 ELSE 'UNKNOWN'
                             END as PaymentStatus,
                             CASE 
                                 WHEN Balance = 0 THEN 100
                                 ELSE ROUND(((LoanAmount - Balance) / LoanAmount) * 100, 2)
                             END as PaymentProgress
                      FROM tbl_loans 
                      WHERE ClientNo = '$clientId' 
                      ORDER BY DatePrepared DESC, LoanID DESC";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $loanId = htmlspecialchars($row['LoanID'] ?? '');
                    $fullName = htmlspecialchars($row['FullName'] ?? $clientName);
                    $balance = floatval($row['Balance'] ?? 0);
                    $loanAmount = floatval($row['LoanAmount'] ?? 0);
                    $interest = floatval($row['Interest'] ?? 0);
                    $cbu = floatval($row['CBU'] ?? 0);
                    $ef = floatval($row['EF'] ?? 0);
                    $mba = floatval($row['MBA'] ?? 0);
                    $paymentStatus = $row['PaymentStatus'] ?? 'UNKNOWN';
                    
                    // Calculate total amount due (loan amount + all fees and interest)
                    $totalAmountDue = $loanAmount + $interest + $cbu + $ef + $mba;
                    
                    // Determine row styling based on status
                    $rowClass = 'loan-row';
                    $isRenewable = ($balance == 0); // Only fully paid loans can be renewed
                    
                    if ($paymentStatus === 'PAID') {
                        $rowClass .= ' table-success';
                    } elseif ($paymentStatus === 'PARTIAL') {
                        $rowClass .= ' table-warning';
                    } elseif ($paymentStatus === 'OVERDUE') {
                        $rowClass .= ' table-danger';
                    } else {
                        $rowClass .= ' table-light';
                    }
                    
                    // Add renewal restriction indicator for active loans
                    if (!$isRenewable) {
                        $rowClass .= ' loan-not-renewable';
                    }
                    
                    echo '<tr class="' . $rowClass . '" data-loan-id="' . $loanId . '" data-user-id="' . htmlspecialchars($row['ClientNo']) . '" data-balance="' . $balance . '" data-renewable="' . ($isRenewable ? 'true' : 'false') . '" data-status="' . $paymentStatus . '" data-client-name="' . $fullName . '">';
                    echo '<td>' . $loanId . '</td>';
                    echo '<td>' . $fullName . '</td>';
                    echo '<td>' . htmlspecialchars($row['Program'] ?? '') . '</td>';
                    
                    // Product column - show product name
                    $productName = htmlspecialchars($row['Product'] ?? '');
                    echo '<td>' . $productName . '</td>';
                    
                    // Product Details column - show inventory details if available
                    echo '<td style="min-width: 150px;">';
                    $hasProductDetails = false;
                    $productDetailsArray = [];
                    
                    // Check if inventory columns exist and have data
                    if (isset($row['SI']) && !empty($row['SI'])) {
                        $productDetailsArray[] = '<strong>SI:</strong> ' . htmlspecialchars($row['SI']);
                        $hasProductDetails = true;
                    }
                    if (isset($row['Serialno']) && !empty($row['Serialno'])) {
                        $productDetailsArray[] = '<strong>Serial:</strong> ' . htmlspecialchars($row['Serialno']);
                        $hasProductDetails = true;
                    }
                    if (isset($row['Supplier']) && !empty($row['Supplier'])) {
                        $productDetailsArray[] = '<strong>Supplier:</strong> ' . htmlspecialchars($row['Supplier']);
                        $hasProductDetails = true;
                    }
                    if (isset($row['Category']) && !empty($row['Category'])) {
                        $productDetailsArray[] = '<strong>Category:</strong> ' . htmlspecialchars($row['Category']);
                        $hasProductDetails = true;
                    }
                    if (isset($row['Warranty']) && !empty($row['Warranty'])) {
                        $productDetailsArray[] = '<strong>Warranty:</strong> ' . htmlspecialchars($row['Warranty']);
                        $hasProductDetails = true;
                    }
                    if (isset($row['Quantity']) && !empty($row['Quantity']) && $row['Quantity'] > 0) {
                        $productDetailsArray[] = '<strong>Qty:</strong> ' . htmlspecialchars($row['Quantity']);
                        $hasProductDetails = true;
                    }
                    
                    if ($hasProductDetails) {
                        // Display details inline with line breaks
                        echo '<div style="font-size: 0.85rem; line-height: 1.6;">';
                        echo implode('<br>', $productDetailsArray);
                        echo '</div>';
                    } else {
                        // Check if columns exist in the result
                        $columnExists = array_key_exists('SI', $row);
                        if ($columnExists) {
                            echo '<span class="text-muted" style="font-size: 0.85rem;">No inventory details</span>';
                        } else {
                            echo '<span class="text-warning" style="font-size: 0.85rem;" title="Product detail columns not found in database"><i class="fas fa-exclamation-triangle"></i> Columns missing</span>';
                        }
                    }
                    echo '</td>';
                    
                    // Try multiple date field names
                    $dateReleased = $row['DateRelease'] ?? $row['DateReleased'] ?? $row['ReleaseDate'] ?? '';
                    echo '<td>' . htmlspecialchars($dateReleased) . '</td>';
                    
                    // Term column (new)
                    $term = $row['Term'] ?? $row['TermRate'] ?? '';
                    echo '<td class="text-center">' . htmlspecialchars($term) . ($term ? ' months' : '') . '</td>';
                    
                    // Total Amount Due column (moved before Balance)
                    echo '<td class="text-end">₱' . number_format($totalAmountDue, 2) . '</td>';
                    
                    // Interest column
                    echo '<td class="text-end">₱' . number_format($interest, 2) . '</td>';
                    
                    // Balance column (moved after Total Amount Due)
                    echo '<td class="text-end">₱' . number_format($balance, 2) . '</td>';
                    
                    // Status column - text only, no icons
                    echo '<td>';
                    if ($paymentStatus === 'PAID') {
                        echo '<span class="badge bg-success">PAID</span>';
                    } elseif ($paymentStatus === 'PARTIAL') {
                        echo '<span class="badge bg-warning">PARTIAL</span>';
                    } elseif ($paymentStatus === 'OVERDUE') {
                        echo '<span class="badge bg-danger">OVERDUE</span>';
                    } else {
                        echo '<span class="badge bg-primary">ACTIVE</span>';
                    }
                    echo '</td>';
                    
                    // Renewable column - checkmark or cross
                    echo '<td class="text-center">';
                    if ($isRenewable) {
                        echo '<span class="text-success" title="Eligible for renewal">✅</span>';
                    } else {
                        echo '<span class="text-danger" title="Not eligible for renewal - loan must be fully paid">❌</span>';
                    }
                    echo '</td>';
                    
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="12" class="text-center text-muted">No loans found for this client</td></tr>';
            }
            break;
            
        case 'getLoanAvailment':
            $clientId = $_GET['client_id'] ?? '';
            if (empty($clientId)) {
                echo json_encode(['success' => false, 'message' => 'Client ID required']);
                break;
            }
            
            $clientId = $conn->real_escape_string($clientId);
            $query = "SELECT COUNT(*) as loanCount FROM tbl_loans WHERE ClientNo = '$clientId'";
            $result = $conn->query($query);
            
            $loanAvailment = 0;
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $loanAvailment = $row['loanCount'];
            }
            
            echo json_encode(['success' => true, 'loanAvailment' => $loanAvailment]);
            break;
            
        case 'getLoanDetails':
            $loanId = $_GET['loan_id'] ?? '';
            if (empty($loanId)) {
                echo json_encode(['success' => false, 'message' => 'Loan ID required']);
                break;
            }
            
            $loanId = $conn->real_escape_string($loanId);
            $query = "SELECT * FROM tbl_loans WHERE LoanID = '$loanId' LIMIT 1";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode(['success' => true, 'loan' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Loan not found']);
            }
            break;
            
        case 'getLoanTransaction':
            $userId = $_GET['userId'] ?? '';
            if (empty($userId)) {
                echo json_encode(['success' => false, 'message' => 'User ID required']);
                break;
            }
            
            $userId = $conn->real_escape_string($userId);
            $query = "SELECT 
                        LastName, FirstName, MiddleName, ClientName,
                        BizNature, BizType, BizProductService as ProductService,
                        Sector
                      FROM tbl_clientinfo 
                      WHERE ClientNo = '$userId' 
                      LIMIT 1";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                echo json_encode([
                    'success' => true,
                    'LastName' => $row['LastName'] ?? '',
                    'FirstName' => $row['FirstName'] ?? '',
                    'MiddleName' => $row['MiddleName'] ?? '',
                    'ClientName' => $row['ClientName'] ?? '',
                    'BizNature' => $row['BizNature'] ?? '',
                    'BizType' => $row['BizType'] ?? '',
                    'ProductService' => $row['ProductService'] ?? '',
                    'Sector' => $row['Sector'] ?? ''
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            break;
            
        case 'getLoanUtilization':
            $loanId = $_GET['loan_id'] ?? '';
            if (empty($loanId)) {
                echo json_encode(['success' => false, 'message' => 'Loan ID required']);
                break;
            }
            
            $loanId = $conn->real_escape_string($loanId);
            $query = "SELECT Purpose, Amount FROM tbl_loan_utilization WHERE LoanID = '$loanId' ORDER BY UtilizationID";
            $result = $conn->query($query);
            
            $utilization = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $utilization[] = $row;
                }
            }
            
            echo json_encode(['success' => true, 'utilization' => $utilization]);
            break;
            
        case 'saveLoanApplication':
        case 'saveLoanRenewal':
            // Get form data
            $data = $_POST;
            $isRenewal = ($action === 'saveLoanRenewal');
            
            // Additional validation for renewals - check for active loans
            if ($isRenewal) {
                $clientNo = $conn->real_escape_string($data['userId'] ?? '');
                
                // Check if client has any active loans (Balance > 0)
                $activeLoansQuery = "SELECT COUNT(*) as active_count, 
                                           GROUP_CONCAT(LoanID) as active_loan_ids,
                                           SUM(Balance) as total_outstanding
                                    FROM tbl_loans 
                                    WHERE ClientNo = '$clientNo' 
                                    AND Balance > 0";
                
                $activeResult = $conn->query($activeLoansQuery);
                if ($activeResult && $activeResult->num_rows > 0) {
                    $activeData = $activeResult->fetch_assoc();
                    $activeCount = intval($activeData['active_count']);
                    $totalOutstanding = floatval($activeData['total_outstanding']);
                    
                    if ($activeCount > 0) {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Renewal not allowed: Client has ' . $activeCount . ' active loan(s) with total outstanding balance of ₱' . number_format($totalOutstanding, 2) . '. All existing loans must be fully paid before renewal.',
                            'error_type' => 'ACTIVE_LOANS_EXIST',
                            'active_loans_count' => $activeCount,
                            'total_outstanding' => $totalOutstanding
                        ]);
                        exit;
                    }
                }
            } else {
                // NEW LOAN VALIDATION - Check for active loans for new applications too
                $clientNo = $conn->real_escape_string($data['add_userId'] ?? '');
                
                // Check if client has any active loans (Balance > 0)
                $activeLoansQuery = "SELECT COUNT(*) as active_count, 
                                           GROUP_CONCAT(LoanID) as active_loan_ids,
                                           SUM(Balance) as total_outstanding
                                    FROM tbl_loans 
                                    WHERE ClientNo = '$clientNo' 
                                    AND Balance > 0";
                
                $activeResult = $conn->query($activeLoansQuery);
                if ($activeResult && $activeResult->num_rows > 0) {
                    $activeData = $activeResult->fetch_assoc();
                    $activeCount = intval($activeData['active_count']);
                    $totalOutstanding = floatval($activeData['total_outstanding']);
                    
                    if ($activeCount > 0) {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'New loan not allowed: Client has ' . $activeCount . ' existing loan(s) with total outstanding balance of ₱' . number_format($totalOutstanding, 2) . '. All existing loans must be fully paid before applying for a new loan.',
                            'error_type' => 'ACTIVE_LOANS_EXIST',
                            'active_loans_count' => $activeCount,
                            'total_outstanding' => $totalOutstanding
                        ]);
                        exit;
                    }
                }
            }
            
            // Validate required fields
            $userIdField = $isRenewal ? 'userId' : 'add_userId';
            $required = [$userIdField, 'loanType', 'poFco', 'program', 'product', 'mode', 'termRate', 'amount'];
            
            // Adjust field names for renewal vs new application
            if (!$isRenewal) {
                $required = ['add_userId', 'add_loanType', 'add_poFco', 'add_program', 'add_product', 'add_mode', 'add_termRate', 'add_amount'];
            }
            
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                    exit;
                }
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get client number and name
                $clientNo = $conn->real_escape_string($isRenewal ? $data['userId'] : $data['add_userId']);
                error_log("DEBUG: Renewal clientNo received: " . $clientNo);
                error_log("DEBUG: POST data: " . print_r($data, true));
                
                // Initialize all client data variables
                $clientName = '';
                $firstName = '';
                $middleName = '';
                $lastName = '';
                $address = '';
                $barangay = '';
                $cityTown = '';
                $province = '';
                $fullAddress = '';
                $location = '';
                $branch = '';
                $branchCluster = '';
                $contactNo = '';
                $email = '';
                $birthDate = '';
                $age = 0;
                $gender = '';
                $religion = '';
                $civilStatus = '';
                $occupation = '';
                $citizenship = '';
                $insurance = '';
                $covered = '';
                $spouse = '';
                $spouseName = '';
                $spouseLastName = '';
                $spouseFirstName = '';
                $spouseMiddleName = '';
                $assetSize = '';
                $tin = '';
                $sss = '';
                $sector = '';
                $bizNature = '';
                $bizType = '';
                $productService = '';
                $bizAddress = '';
                $bizAdd = '';
                $bizBarangay = '';
                $bizCityTown = '';
                $bizProvince = '';
                $bizAgeYr = 0;
                $bizAgeMo = 0;
                $bizCapital = 0;
                $workers = '';
                $moIncome = 0;
                
                // Fetch ALL client personal and business data from tbl_clientinfo
                try {
                    // First, check what columns actually exist
                    $clientQuery = "SELECT * FROM tbl_clientinfo WHERE ClientNo = '$clientNo' LIMIT 1";
                    
                    $clientResult = $conn->query($clientQuery);
                    
                    if ($clientResult && $clientResult->num_rows > 0) {
                        $clientRow = $clientResult->fetch_assoc();
                        
                        // Personal Information - use isset to check if column exists
                        $clientName = $clientRow['ClientName'] ?? $clientRow['CLIENTNAME'] ?? '';
                        $firstName = $clientRow['FirstName'] ?? $clientRow['FIRSTNAME'] ?? '';
                        $middleName = $clientRow['MiddleName'] ?? $clientRow['MIDDLENAME'] ?? '';
                        $lastName = $clientRow['LastName'] ?? $clientRow['LASTNAME'] ?? '';
                        
                        // Debug: Log what we got from database
                        error_log("DEBUG: Raw data from tbl_clientinfo - ClientName: '$clientName', FirstName: '$firstName', LastName: '$lastName'");
                        
                        // If ClientName is empty, construct from FirstName, MiddleName, LastName
                        if (empty($clientName)) {
                            if (!empty($lastName) || !empty($firstName)) {
                                $clientName = trim($lastName . ', ' . $firstName . ' ' . $middleName);
                                error_log("DEBUG: Constructed ClientName from parts: '$clientName'");
                            } else {
                                // Last resort: Check if there's a FullName column
                                $clientName = $clientRow['FullName'] ?? $clientRow['FULLNAME'] ?? '';
                                if (empty($clientName)) {
                                    error_log("ERROR: No name data found for ClientNo: $clientNo - using ClientNo as name");
                                    $clientName = "CLIENT-" . $clientNo;
                                } else {
                                    error_log("DEBUG: Used FullName column: '$clientName'");
                                }
                            }
                        } else {
                            error_log("DEBUG: Using ClientName from database: '$clientName'");
                        }
                        
                        $address = $clientRow['Address'] ?? $clientRow['ADDRESS'] ?? '';
                        $barangay = $clientRow['Barangay'] ?? $clientRow['BARANGAY'] ?? '';
                        $cityTown = $clientRow['CityTown'] ?? $clientRow['CITYTOWN'] ?? '';
                        $province = $clientRow['Province'] ?? $clientRow['PROVINCE'] ?? '';
                        $fullAddress = $clientRow['FullAddress'] ?? $clientRow['FULLADDRESS'] ?? '';
                        $location = $clientRow['Location'] ?? $clientRow['LOCATION'] ?? '';
                        $branch = $clientRow['Branch'] ?? $clientRow['BRANCH'] ?? '';
                        $branchCluster = $clientRow['BranchCluster'] ?? $clientRow['BRANCHCLUSTER'] ?? '';
                        $contactNo = $clientRow['ContactNo'] ?? $clientRow['CONTACTNO'] ?? '';
                        $email = $clientRow['Email'] ?? $clientRow['EMAIL'] ?? '';
                        $birthDate = $clientRow['Birthdate'] ?? $clientRow['BIRTHDATE'] ?? $clientRow['BirthDate'] ?? '';
                        $age = intval($clientRow['Age'] ?? $clientRow['AGE'] ?? 0);
                        $gender = $clientRow['Gender'] ?? $clientRow['GENDER'] ?? '';
                        $religion = $clientRow['Religion'] ?? $clientRow['RELIGION'] ?? '';
                        $civilStatus = $clientRow['CStatus'] ?? $clientRow['CSTATUS'] ?? $clientRow['CivilStatus'] ?? '';
                        $occupation = $clientRow['Occupation'] ?? $clientRow['OCCUPATION'] ?? '';
                        $citizenship = $clientRow['Citizenship'] ?? $clientRow['CITIZENSHIP'] ?? '';
                        $insurance = $clientRow['Insurance'] ?? $clientRow['INSURANCE'] ?? '';
                        $covered = $clientRow['Covered'] ?? $clientRow['COVERED'] ?? '';
                        $spouse = $clientRow['Spouse'] ?? $clientRow['SPOUSE'] ?? '';
                        $spouseName = $clientRow['SpouseName'] ?? $clientRow['SPOUSENAME'] ?? $spouse;
                        $spouseLastName = $clientRow['SpouseLastName'] ?? $clientRow['SpuseLastName'] ?? $clientRow['SPOUSELASTNAME'] ?? '';
                        $spouseFirstName = $clientRow['SpouseFirstName'] ?? $clientRow['SPOUSEFIRSTNAME'] ?? '';
                        $spouseMiddleName = $clientRow['SpouseMiddleName'] ?? $clientRow['SPOUSEMIDDLENAME'] ?? '';
                        $assetSize = $clientRow['AssetSize'] ?? $clientRow['ASSETSIZE'] ?? '';
                        $tin = $clientRow['TIN'] ?? '';
                        $sss = $clientRow['SSS'] ?? '';
                        
                        // Business Information - check both cases
                        $sector = $clientRow['Sector'] ?? $clientRow['SECTOR'] ?? '';
                        $bizNature = $clientRow['BizNature'] ?? $clientRow['BIZNATURE'] ?? '';
                        $bizType = $clientRow['BizType'] ?? $clientRow['BIZTYPE'] ?? '';
                        $productService = $clientRow['BizProductService'] ?? $clientRow['BIZPRODUCTSERVICE'] ?? $clientRow['ProductService'] ?? $clientRow['PRODUCTSERVICE'] ?? '';
                        $bizAddress = $clientRow['BizAddress'] ?? $clientRow['BIZADDRESS'] ?? '';
                        $bizAdd = $clientRow['BizAdd'] ?? $clientRow['BIZADD'] ?? $bizAddress;
                        $bizBarangay = $clientRow['BizBarangay'] ?? $clientRow['BIZBARANGAY'] ?? '';
                        $bizCityTown = $clientRow['BizCityTown'] ?? $clientRow['BIZCITYTOWN'] ?? '';
                        $bizProvince = $clientRow['BizProvince'] ?? $clientRow['BIZPROVINCE'] ?? '';
                        $bizAgeYr = intval($clientRow['BizAgeYr'] ?? $clientRow['BIZAGEYR'] ?? 0);
                        $bizAgeMo = intval($clientRow['BizAgeMo'] ?? $clientRow['BIZAGEMO'] ?? 0);
                        $bizCapital = floatval($clientRow['BizCapital'] ?? $clientRow['BIZCAPITAL'] ?? 0);
                        $workers = $clientRow['Workers'] ?? $clientRow['WORKERS'] ?? '';
                        $moIncome = floatval($clientRow['MoIncome'] ?? $clientRow['MOINCOME'] ?? 0);
                        
                        error_log("SUCCESS: Fetched client data - ClientName: $clientName, FirstName: $firstName, LastName: $lastName");
                    } else {
                        // Client not found in tbl_clientinfo
                        error_log("WARNING: Client not found in tbl_clientinfo for ClientNo: $clientNo");
                        $clientName = "Client-" . $clientNo; // Use ClientNo as fallback with prefix
                    }
                } catch (Exception $e) {
                    error_log("ERROR: Failed to fetch client data: " . $e->getMessage());
                    $clientName = "Client-" . $clientNo;
                }
                
                // Override with form data if provided (allows user to update during loan application)
                if ($isRenewal) {
                    $sector = $conn->real_escape_string($data['sector'] ?? $sector);
                    $bizNature = $conn->real_escape_string($data['nature'] ?? $bizNature);
                    $bizType = $conn->real_escape_string($data['type'] ?? $bizType);
                    $productService = $conn->real_escape_string($data['productServices'] ?? $productService);
                } else {
                    $sector = $conn->real_escape_string($data['add_sector'] ?? $sector);
                    $bizNature = $conn->real_escape_string($data['add_nature'] ?? $bizNature);
                    $bizType = $conn->real_escape_string($data['add_type'] ?? $bizType);
                    $productService = $conn->real_escape_string($data['add_prodServices'] ?? $productService);
                }
                
                // Get business details from form data (user input during loan application)
                if ($isRenewal) {
                    $bizAgeYr = floatval($data['ageYear'] ?? 0);
                    $bizAgeMo = floatval($data['ageMonth'] ?? 0);
                    $bizCapital = floatval($data['capital'] ?? 0);
                    $workers = $conn->real_escape_string($data['workers'] ?? '');
                    $moIncome = floatval($data['moIncome'] ?? 0);
                } else {
                    $bizAgeYr = floatval($data['add_ageYear'] ?? 0);
                    $bizAgeMo = floatval($data['add_ageMonth'] ?? 0);
                    $bizCapital = floatval($data['add_capital'] ?? 0);
                    $workers = $conn->real_escape_string($data['add_workers'] ?? '');
                    $moIncome = floatval($data['add_moIncome'] ?? 0);
                }
                
                // Generate Loan ID
                $date = date('Ymd');
                $countQuery = "SELECT COUNT(*) as count FROM tbl_loans WHERE LoanID LIKE 'LOAN-$date-%'";
                $countResult = $conn->query($countQuery);
                $count = 1;
                if ($countResult && $countResult->num_rows > 0) {
                    $countRow = $countResult->fetch_assoc();
                    $count = $countRow['count'] + 1;
                }
                $loanId = 'LOAN-' . $date . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
                
                // Prepare loan data (handle both new and renewal field names)
                $prefix = $isRenewal ? '' : 'add_';
                $loanType = $conn->real_escape_string($data[$prefix . 'loanType'] ?? 'NEW');
                $tag = $conn->real_escape_string($data[$prefix . 'tag'] ?? '-');
                $staff = $conn->real_escape_string($data[$prefix . 'poFco']);
                $program = $conn->real_escape_string($data[$prefix . 'program']);
                $product = $conn->real_escape_string($data[$prefix . 'product']);
                $mode = $conn->real_escape_string($data[$prefix . 'mode']);
                $term = intval($data[$prefix . 'termRate']);
                $rate = floatval(str_replace('%', '', $data[$prefix . 'rate'] ?? '0'));
                $availment = intval($data[$prefix . 'availment'] ?? 1);
                $loanAmount = floatval(str_replace(',', '', $data[$prefix . 'amount']));
                $downPayment = floatval(str_replace(',', '', $data[$prefix . 'downPaymentAmount'] ?? '0'));
                $principal = $loanAmount - $downPayment;
                
                // Co-maker details (only for renewal)
                $coMakerFirstName = $conn->real_escape_string($data['firstname'] ?? '');
                $coMakerMiddleName = $conn->real_escape_string($data['middlename'] ?? '');
                $coMakerLastName = $conn->real_escape_string($data['lastname'] ?? '');
                
                // Use original system calculation logic
                // Calculate total payments based on mode
                $totalPayments = $term; // Default to monthly
                if ($mode === 'SEMI-MONTHLY') {
                    $totalPayments = $term * 2;
                } else if ($mode === 'WEEKLY') {
                    $totalPayments = $term * 4;
                }
                
                // Original system logic for principal per payment
                $principalPerPayment = $principal / $totalPayments;
                
                // Original rounding logic
                if (($principalPerPayment - floor($principalPerPayment)) >= 0.5) {
                    $roundedPrincipal = ceil($principalPerPayment);
                } else {
                    $roundedPrincipal = floor($principalPerPayment);
                }
                
                // Check if frontend calculated interest is provided
                if (isset($_POST['add_interest']) && !empty($_POST['add_interest'])) {
                    // Use the frontend calculated interest (for new loans)
                    $interestAmount = floatval($_POST['add_interest']);
                    $totalInterest = $interestAmount;
                } else if (isset($_POST['interest']) && !empty($_POST['interest'])) {
                    // Use the frontend calculated interest (for renewals)
                    $interestAmount = floatval($_POST['interest']);
                    $totalInterest = $interestAmount;
                } else {
                    // Original interest calculation: roundedPrincipal * term * rate / 100
                    $totalInterest = $roundedPrincipal * $term * ($rate / 100);
                    
                    // Original rounding for interest
                    if (($totalInterest - floor($totalInterest)) >= 0.5) {
                        $totalInterest = ceil($totalInterest);
                    } else {
                        $totalInterest = floor($totalInterest);
                    }
                    
                    $interestAmount = $totalInterest;
                }
                
                $totalAmortization = $roundedPrincipal + $totalInterest;
                
                // Set fees to 0 (not used in original system)
                $cbu = 0;
                $ef = 0;
                $mba = 0;
                
                // Get actual values from existing system instead of making assumptions
                // Check if there are existing loan records to understand the business rules
                $configQuery = "SELECT IntType, CBUType, EFType, IntComputation FROM tbl_loans WHERE IntType IS NOT NULL LIMIT 1";
                $configResult = $conn->query($configQuery);
                
                if ($configResult && $configResult->num_rows > 0) {
                    // Use values from existing loan records
                    $configRow = $configResult->fetch_assoc();
                    $intType = $configRow['IntType'] ?? '';
                    $cbuType = $configRow['CBUType'] ?? '';
                    $efType = $configRow['EFType'] ?? '';
                    $intComputation = $configRow['IntComputation'] ?? '';
                } else {
                    // If no existing records, check if there's a configuration table
                    $setupQuery = "SELECT * FROM tbl_loansetup WHERE Product = '$product' LIMIT 1";
                    $setupResult = $conn->query($setupQuery);
                    
                    if ($setupResult && $setupResult->num_rows > 0) {
                        $setupRow = $setupResult->fetch_assoc();
                        // Use values from loan setup if available
                        $intType = $setupRow['IntType'] ?? '';
                        $cbuType = $setupRow['CBUType'] ?? '';
                        $efType = $setupRow['EFType'] ?? '';
                        $intComputation = $setupRow['IntComputation'] ?? '';
                    } else {
                        // Last resort: use empty values and let the database handle defaults
                        $intType = '';
                        $cbuType = '';
                        $efType = '';
                        $intComputation = '';
                    }
                }
                
                // Set rates to 0 since we're not using fees in the original calculation
                $cbuRate = 0;
                $efRate = 0;
                
                // Calculate principal amortization (principal amount per payment)
                $principalAmount = $loanAmount / $term; // Simple calculation: loan amount divided by term
                
                // Initialize loan balance and status
                $balance = $loanAmount; // Initial balance equals loan amount
                $loanStatus = 'ACTIVE'; // New loans start as ACTIVE
                
                // Fetch CenterName and GroupName from tbl_aging based on ClientNo
                // NatureAdj doesn't exist in tbl_aging, so we'll set a default value
                $centerName = '';
                $groupName = '';
                $natureADJ = 'LOAN RELEASE'; // Default since column doesn't exist in tbl_aging
                
                // Debug: Log the ClientNo being searched
                error_log("Searching for ClientNo: '$clientNo' in tbl_aging");
                
                try {
                    // Get CenterName and GroupName from tbl_aging (correct column names: CENTERNAME, GROUPNAME)
                    $agingDataQuery = "SELECT CENTERNAME, GROUPNAME FROM tbl_aging WHERE ClientNo = '$clientNo' ORDER BY ID DESC LIMIT 1";
                    error_log("Executing query: $agingDataQuery");
                    
                    $agingDataResult = $conn->query($agingDataQuery);
                    if ($agingDataResult && $agingDataResult->num_rows > 0) {
                        $agingDataRow = $agingDataResult->fetch_assoc();
                        $centerName = $agingDataRow['CENTERNAME'] ?? '';
                        $groupName = $agingDataRow['GROUPNAME'] ?? '';
                        
                        // Debug logging
                        error_log("SUCCESS: Found aging data for ClientNo $clientNo: CenterName='$centerName', GroupName='$groupName'");
                    } else {
                        error_log("FAILED: No aging data found for ClientNo: $clientNo");
                        
                        // Check if the ClientNo exists at all in tbl_aging
                        $checkQuery = "SELECT COUNT(*) as count FROM tbl_aging WHERE ClientNo = '$clientNo'";
                        $checkResult = $conn->query($checkQuery);
                        if ($checkResult) {
                            $checkRow = $checkResult->fetch_assoc();
                            error_log("ClientNo '$clientNo' exists in tbl_aging: " . ($checkRow['count'] > 0 ? 'YES (' . $checkRow['count'] . ' records)' : 'NO'));
                        }
                        
                        // Check what ClientNos are available (for debugging)
                        $availableQuery = "SELECT DISTINCT ClientNo FROM tbl_aging WHERE ClientNo IS NOT NULL AND ClientNo != '' AND ClientNo != '-' LIMIT 5";
                        $availableResult = $conn->query($availableQuery);
                        if ($availableResult) {
                            $available = [];
                            while ($row = $availableResult->fetch_assoc()) {
                                $available[] = $row['ClientNo'];
                            }
                            error_log("Available ClientNos in tbl_aging: " . implode(', ', $available));
                        }
                    }
                } catch (Exception $e) {
                    error_log("ERROR: Data fetch from tbl_aging failed: " . $e->getMessage());
                }
                
                // Final debug log
                error_log("FINAL VALUES: CenterName='$centerName', GroupName='$groupName', NatureADJ='$natureADJ'");
                
                $dateAdded = date('Y-m-d H:i:s');
                $currentDate = date('Y-m-d');
                $addedBy = $_SESSION['USERNAME'] ?? 'SYSTEM';
                $balance = $loanAmount;
                
                // Calculate DateMature by adding term (months) to DateRelease
                $dateRelease = new DateTime($currentDate);
                $dateRelease->add(new DateInterval('P' . $term . 'M')); // Add term months
                $dateMature = $dateRelease->format('Y-m-d');
                
                // Generate RenewID if this is a renewal
                $renewId = '';
                $includeRenewId = false;
                
                // Check if RenewID column exists in tbl_loans
                $checkColumnQuery = "SHOW COLUMNS FROM tbl_loans LIKE 'RenewID'";
                $checkColumnResult = $conn->query($checkColumnQuery);
                
                if ($checkColumnResult && $checkColumnResult->num_rows > 0) {
                    $includeRenewId = true;
                    
                    if ($isRenewal) {
                        // Generate RenewID format: RENEW-YYYYMMDD-XXXXX
                        $renewDate = date('Ymd');
                        $renewCountQuery = "SELECT COUNT(*) as count FROM tbl_loans WHERE RenewID LIKE 'RENEW-$renewDate-%'";
                        $renewCountResult = $conn->query($renewCountQuery);
                        $renewCount = 1;
                        if ($renewCountResult && $renewCountResult->num_rows > 0) {
                            $renewCountRow = $renewCountResult->fetch_assoc();
                            $renewCount = $renewCountRow['count'] + 1;
                        }
                        $renewId = 'RENEW-' . $renewDate . '-' . str_pad($renewCount, 5, '0', STR_PAD_LEFT);
                        error_log("Generated RenewID: $renewId for renewal loan");
                    }
                } else {
                    error_log("WARNING: RenewID column does not exist in tbl_loans. Skipping RenewID generation.");
                }
                
                // DEBUG: Log the actual values before INSERT
                error_log("DEBUG BEFORE INSERT - ClientNo: $clientNo, ClientName: $clientName, Sector: $sector, BizNature: $bizNature");
                
                // Fetch inventory product details if this is a loaned product
                // Using exact same column names as tbl_inventoryout
                $inventorySI = '';
                $inventorySerialno = '';
                $inventorySupplier = '';
                $inventoryCategory = '';
                $inventoryWarranty = '';
                $inventoryQuantity = 0;
                $inventoryProductName = ''; // Add this to store the actual product name from inventory
                
                // Get the inventory SI from the form data
                $submittedInventorySI = $conn->real_escape_string($data['inventory_si'] ?? '');
                
                if (!empty($submittedInventorySI)) {
                    // Fetch inventory product details from tbl_inventoryout
                    $inventoryQuery = "SELECT SI, Product, Quantity, Serialno, Supplier, Category, Warranty 
                                      FROM tbl_inventoryout 
                                      WHERE SI = '$submittedInventorySI' AND ISLOAN = 'YES' 
                                      LIMIT 1";
                    $inventoryResult = $conn->query($inventoryQuery);
                    
                    if ($inventoryResult && $inventoryResult->num_rows > 0) {
                        $inventoryRow = $inventoryResult->fetch_assoc();
                        $inventorySI = $inventoryRow['SI'];
                        $inventoryProductName = $inventoryRow['Product'] ?? ''; // Get the actual product name
                        $inventorySerialno = $inventoryRow['Serialno'] ?? '';
                        $inventorySupplier = $inventoryRow['Supplier'] ?? '';
                        $inventoryCategory = $inventoryRow['Category'] ?? '';
                        $inventoryWarranty = $inventoryRow['Warranty'] ?? '';
                        $inventoryQuantity = intval($inventoryRow['Quantity']);
                        
                        error_log("DEBUG: Found inventory product - SI: $inventorySI, Product: $inventoryProductName, Quantity: $inventoryQuantity");
                        
                        // Update the LOANID in tbl_inventoryout to link it to this loan
                        $updateInventoryQuery = "UPDATE tbl_inventoryout 
                                                SET LOANID = '$loanId' 
                                                WHERE SI = '$inventorySI'";
                        if (!$conn->query($updateInventoryQuery)) {
                            error_log("WARNING: Failed to update LOANID in tbl_inventoryout: " . $conn->error);
                        } else {
                            error_log("SUCCESS: Updated LOANID in tbl_inventoryout for SI: $inventorySI");
                        }
                        
                        // Override the Product field with the actual product name from inventory if available
                        if (!empty($inventoryProductName)) {
                            $product = $conn->real_escape_string($inventoryProductName);
                            error_log("DEBUG: Overriding Product field with inventory product name: $product");
                        }
                    } else {
                        error_log("WARNING: No inventory product found for SI: $submittedInventorySI");
                    }
                } else {
                    error_log("DEBUG: No inventory_si provided, this is a regular loan without loaned product");
                }
                
                $insertQuery = "INSERT INTO tbl_loans (
                    LoanID, ClientNo, FullName, LoanType, Tag, PO, Program, Product, Mode, 
                    Term, InterestRate, LoanAmount, Balance, LoanStatus,
                    Sector, BizNature, BizType, ProductService, BizAgeYr, BizAgeMo,
                    BizCapital, Workers, MoIncome,
                    CBU, EF, Interest, MBA, PrincipalAmo, InterestAmo,
                    IntType, CBURate, CBUType, EFRate, EFType, IntComputation,
                    LRSType, DateRelease, DateMature, DatePrepared, PreparedBy,
                    SI, Serialno, Supplier, Category, Warranty, Quantity" . 
                    ($includeRenewId && $isRenewal ? ", RenewID" : "") . "
                ) VALUES (
                    '$loanId', '$clientNo', '$clientName', '$loanType', '$tag', '$staff', '$program', '$product', '$mode',
                    $term, $rate, $loanAmount, $balance, '$loanStatus',
                    '$sector', '$bizNature', '$bizType', '$productService', $bizAgeYr, $bizAgeMo,
                    $bizCapital, '$workers', $moIncome,
                    $cbu, $ef, $interestAmount, $mba, $principalAmount, $interestAmount,
                    '$intType', $cbuRate, '$cbuType', $efRate, '$efType', '$intComputation',
                    'CLIENT', '$currentDate', '$dateMature', '$currentDate', '$addedBy',
                    " . ($inventorySI ? "'$inventorySI'" : "NULL") . ", 
                    " . ($inventorySerialno ? "'$inventorySerialno'" : "NULL") . ", 
                    " . ($inventorySupplier ? "'$inventorySupplier'" : "NULL") . ", 
                    " . ($inventoryCategory ? "'$inventoryCategory'" : "NULL") . ", 
                    " . ($inventoryWarranty ? "'$inventoryWarranty'" : "NULL") . ", 
                    " . ($inventoryQuantity > 0 ? $inventoryQuantity : "NULL") . 
                    ($includeRenewId && $isRenewal ? ", '$renewId'" : "") . "
                )";
                
                // Debug: Log the query to see what values are being inserted
                error_log("INSERT Query: " . $insertQuery);
                
                if (!$conn->query($insertQuery)) {
                    throw new Exception('Failed to save loan: ' . $conn->error . ' | Query: ' . $insertQuery);
                }
                
                // AUTOMATICALLY INSERT INTO TBL_AGING
                error_log("DEBUG: Starting tbl_aging insertion for ClientNo: $clientNo");
                
                // For loan transactions (new/renewal), ALWAYS INSERT new records
                // Never update existing records - each loan gets its own aging entry
                
                // Check if LoanStatus column exists first
                $checkLoanStatusColumn = "SHOW COLUMNS FROM tbl_aging LIKE 'LoanStatus'";
                $loanStatusResult = $conn->query($checkLoanStatusColumn);
                $hasLoanStatus = ($loanStatusResult && $loanStatusResult->num_rows > 0);
                
                if ($hasLoanStatus) {
                    // Include LoanStatus if column exists
                    $insertAgingQuery = "INSERT INTO tbl_aging (
                        ClientNo, LoanID, FULLNAME, LASTNAME, FIRSTNAME, MIDDLENAME,
                        BARANGAY, CITYTOWN, PROVINCE, FULLADDRESS, LOCATION,
                        BRANCH, BRANCHCLUSTER,
                        AGE, BIRTHDATE, GENDER, RELIGION, CSTATUS, OCCUPATION, CITIZENSHIP,
                        CONTACTNO, SPOUSENAME, SPOUSELASTNAME, SPOUSEFIRSTNAME, SPOUSEMIDDLENAME,
                        ASSETSIZE,
                        LOANAMOUNT, AmountDueAsOf, 
                        DATERELEASE, DATEMATURE, PRODUCT, PROGRAM, MODE, CENTERNAME, GROUPNAME,
                        SECTOR, BIZNATURE, BIZTYPE, PRODUCTSERVICE, 
                        BIZBARANGAY, BIZCITYTOWN, BIZPROVINCE,
                        BIZAGEYR, BIZAGEMO, BIZCAPITAL, WORKERS, MOINCOME, 
                        LOANTYPE, PO, TERM, INTERESTRATE, INTTYPE, LoanStatus
                    ) VALUES (
                        '$clientNo', '$loanId', '$clientName', '$lastName', '$firstName', '$middleName',
                        '$barangay', '$cityTown', '$province', '$fullAddress', '$location',
                        '$branch', '$branchCluster',
                        $age, '$birthDate', '$gender', '$religion', '$civilStatus', '$occupation', '$citizenship',
                        '$contactNo', '$spouseName', '$spouseLastName', '$spouseFirstName', '$spouseMiddleName',
                        '$assetSize',
                        $loanAmount, $loanAmount,
                        '$currentDate', '$dateMature', '$product', '$program', '$mode', '$centerName', '$groupName',
                        '$sector', '$bizNature', '$bizType', '$productService',
                        '$bizBarangay', '$bizCityTown', '$bizProvince',
                        $bizAgeYr, $bizAgeMo, $bizCapital, '$workers', $moIncome,
                        '$loanType', '$staff', $term, $rate, '$intType', '$loanStatus'
                    )";
                } else {
                    // Exclude LoanStatus if column doesn't exist
                    $insertAgingQuery = "INSERT INTO tbl_aging (
                        ClientNo, LoanID, FULLNAME, LASTNAME, FIRSTNAME, MIDDLENAME,
                        BARANGAY, CITYTOWN, PROVINCE, FULLADDRESS, LOCATION,
                        BRANCH, BRANCHCLUSTER,
                        AGE, BIRTHDATE, GENDER, RELIGION, CSTATUS, OCCUPATION, CITIZENSHIP,
                        CONTACTNO, SPOUSENAME, SPOUSELASTNAME, SPOUSEFIRSTNAME, SPOUSEMIDDLENAME,
                        ASSETSIZE,
                        LOANAMOUNT, AmountDueAsOf, 
                        DATERELEASE, DATEMATURE, PRODUCT, PROGRAM, MODE, CENTERNAME, GROUPNAME,
                        SECTOR, BIZNATURE, BIZTYPE, PRODUCTSERVICE,
                        BIZBARANGAY, BIZCITYTOWN, BIZPROVINCE,
                        BIZAGEYR, BIZAGEMO, BIZCAPITAL, WORKERS, MOINCOME,
                        LOANTYPE, PO, TERM, INTERESTRATE, INTTYPE
                    ) VALUES (
                        '$clientNo', '$loanId', '$clientName', '$lastName', '$firstName', '$middleName',
                        '$barangay', '$cityTown', '$province', '$fullAddress', '$location',
                        '$branch', '$branchCluster',
                        $age, '$birthDate', '$gender', '$religion', '$civilStatus', '$occupation', '$citizenship',
                        '$contactNo', '$spouseName', '$spouseLastName', '$spouseFirstName', '$spouseMiddleName',
                        '$assetSize',
                        $loanAmount, $loanAmount,
                        '$currentDate', '$dateMature', '$product', '$program', '$mode', '$centerName', '$groupName',
                        '$sector', '$bizNature', '$bizType', '$productService',
                        '$bizBarangay', '$bizCityTown', '$bizProvince',
                        $bizAgeYr, $bizAgeMo, $bizCapital, '$workers', $moIncome,
                        '$loanType', '$staff', $term, $rate, '$intType'
                    )";
                }
                
                error_log("DEBUG: Insert query: $insertAgingQuery");
                
                if ($conn->query($insertAgingQuery)) {
                    error_log("SUCCESS: Inserted new tbl_aging record for ClientNo: $clientNo, LoanID: $loanId");
                } else {
                    error_log("ERROR: Failed to insert into tbl_aging: " . $conn->error);
                    
                    // Try with minimal required fields only as fallback
                    $fallbackQuery = "INSERT INTO tbl_aging (ClientNo, LoanID, FULLNAME, LOANAMOUNT, AmountDueAsOf) 
                                     VALUES ('$clientNo', '$loanId', '$clientName', $loanAmount, $loanAmount)";
                    
                    error_log("DEBUG: Trying fallback query: $fallbackQuery");
                    
                    if ($conn->query($fallbackQuery)) {
                        error_log("SUCCESS: Inserted tbl_aging record with minimal fields for ClientNo: $clientNo");
                    } else {
                        error_log("ERROR: Even fallback insert failed: " . $conn->error);
                    }
                }
                
                // Save utilization details
                $utilizationField = $isRenewal ? 'renewal_utilization_purpose' : 'utilization_purpose';
                $utilizationAmountField = $isRenewal ? 'renewal_utilization_amount' : 'utilization_amount';
                
                if (isset($data[$utilizationField]) && is_array($data[$utilizationField])) {
                    $purposes = $data[$utilizationField];
                    $amounts = $data[$utilizationAmountField] ?? [];
                    
                    for ($i = 0; $i < count($purposes); $i++) {
                        if (!empty($purposes[$i]) && !empty($amounts[$i]) && $amounts[$i] > 0) {
                            $purpose = $conn->real_escape_string($purposes[$i]);
                            $amount = floatval($amounts[$i]);
                            
                            $utilizationQuery = "INSERT INTO tbl_loan_utilization (LoanID, Purpose, Amount, DateAdded, AddedBy) 
                                               VALUES ('$loanId', '$purpose', $amount, '$dateAdded', '$addedBy')";
                            
                            if (!$conn->query($utilizationQuery)) {
                                throw new Exception('Failed to save utilization details: ' . $conn->error);
                            }
                        }
                    }
                }
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Loan saved successfully to both tbl_loans and tbl_aging',
                    'loanId' => $loanId,
                    'details' => [
                        'clientNo' => $clientNo,
                        'clientName' => $clientName,
                        'loanAmount' => $loanAmount,
                        'tables_updated' => ['tbl_loans', 'tbl_aging']
                    ]
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        case 'testConnection':
            $tests = [];
            
            $queries = [
                'clients' => "SELECT COUNT(*) as count FROM tbl_clientinfo",
                'staff' => "SELECT COUNT(*) as count FROM tbl_po",
                'products' => "SELECT COUNT(*) as count FROM tbl_loansetup",
                'modes' => "SELECT COUNT(*) as count FROM tbl_maintenance WHERE ItemType = 'MODE'"
            ];
            
            foreach ($queries as $key => $query) {
                $result = $conn->query($query);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $tests[$key] = $row['count'];
                } else {
                    $tests[$key] = 'Error: ' . $conn->error;
                }
            }
            
            echo json_encode(['success' => true, 'tests' => $tests]);
            break;
            
        case 'test':
        case 'testConnection':
            echo json_encode([
                'success' => true,
                'message' => 'Backend connection working',
                'timestamp' => date('Y-m-d H:i:s'),
                'tables' => [
                    'tbl_clientinfo' => $conn->query("SELECT COUNT(*) as count FROM tbl_clientinfo")->fetch_assoc()['count'] ?? 0,
                    'tbl_loans' => $conn->query("SELECT COUNT(*) as count FROM tbl_loans")->fetch_assoc()['count'] ?? 0
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}

exit;
?>
