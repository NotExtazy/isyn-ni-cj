<?php
// Suppress all output before JSON
ob_start();

// Suppress PHP warnings/notices
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

$basePath = dirname(__DIR__, 2);
include_once($basePath . "/database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $isynBranch = $this->SelectQuery("SELECT DISTINCT Stock FROM tbl_invlist ORDER BY Stock");
        $branch = $this->SelectQuery("SELECT DISTINCT ItemName FROM tbl_maintenance WHERE ItemType='BRANCH'");
        $prodType = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_prodtype ORDER BY Type");
        $customertype = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_clientlist ORDER BY Type");
        $sicount = $this->SelectQuery("SELECT * FROM tbl_sinumber");

        $orseries = $this->SelectQuery("SELECT NAME FROM TBL_ORSERIES ORDER BY NAME");

        // Clear any output buffer before sending JSON
        ob_clean();
        
        echo json_encode(array( 
            "ISYNBRANCH" => $isynBranch,
            "PRODTYPE" => $prodType,
            "CUSTOMERTYPE" => $customertype,
            "SICOUNT" => $sicount,
            "ORSERIES" => $orseries,
        ));
    }

    public function BuildReportTable($data){
        $tblHeader = [];
        $listviewname = $data['listViewName'];
        $listname = $data['ListName'];
        $value = $listname == "" ? $listviewname : $listviewname . "-" . $listname;
        $stmt = $this->conn->prepare("SELECT * FROM TBL_DYNALISTS WHERE ListViewName = ? ORDER BY CAST(ColumnPos AS UNSIGNED) ASC");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tblHeader[] = $row;
            }
        }

        echo json_encode(array( 
            "TBLHEADER" => $tblHeader,
        ));
    }
    
    public function LoadTransactClientName($data){        
        $accounts = [];
        $sTransType = "";

        $type = $data['transactType'];
        
        // Use tbl_loans as primary source for most accurate data
        // Fall back to tbl_aging for writeoffs
        $table = $type == "WRITEOFF" ? "TBL_WRITEOFF" : "tbl_loans";

        $tblHeader = [];
        $listviewname = $data['listViewName'];

        $value = $type == "" ? $listviewname : $listviewname . "-" . $type;
        $stmt = $this->conn->prepare("SELECT * FROM TBL_DYNALISTS WHERE ListViewName = ? ORDER BY CAST(ColumnPos AS UNSIGNED) ASC");
        $stmt->bind_param('s', $value);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $tblHeader[] = $row;
            }
        }

        // Enhanced query with comprehensive loan status calculation
        switch ($type) {
            case "CENTER":
                if ($type == "WRITEOFF") {
                    $sql = "SELECT DISTINCT CENTERNAME,
                                   'WRITEOFF' as LOAN_STATUS,
                                   BALANCE, LOANAMOUNT, FULLNAME
                            FROM TBL_WRITEOFF 
                            WHERE CENTERNAME <> '' 
                            ORDER BY CENTERNAME ASC";
                } else {
                    // Get center data from tbl_aging - GROUP BY center to avoid duplicates
                    $sql = "SELECT 
                                a.CENTERNAME,
                                COUNT(DISTINCT a.ClientNo) as CLIENT_COUNT,
                                COUNT(a.LoanID) as LOAN_COUNT,
                                SUM(a.Balance) as TOTAL_BALANCE,
                                SUM(a.LOANAMOUNT) as TOTAL_LOAN_AMOUNT,
                                GROUP_CONCAT(DISTINCT a.FULLNAME ORDER BY a.FULLNAME SEPARATOR ', ') as CLIENT_NAMES,
                                CASE 
                                    WHEN SUM(a.Balance) = 0 THEN 'PAID'
                                    WHEN SUM(a.Balance) > 0 AND SUM(a.Balance) < SUM(a.TOTALAMO) THEN 'PARTIAL'
                                    WHEN SUM(a.Balance) >= SUM(a.TOTALAMO) THEN 'ACTIVE'
                                    ELSE 'ACTIVE'
                                END as LOAN_STATUS
                            FROM tbl_aging a
                            WHERE a.CENTERNAME IS NOT NULL AND a.CENTERNAME <> '' 
                            GROUP BY a.CENTERNAME
                            ORDER BY a.CENTERNAME ASC";
                }
                $sTransType = "MULTIPLE";
                break;

            case "GROUP":
                if ($type == "WRITEOFF") {
                    $sql = "SELECT DISTINCT CENTERNAME, GROUPNAME,
                                   'WRITEOFF' as LOAN_STATUS,
                                   BALANCE, LOANAMOUNT, FULLNAME
                            FROM TBL_WRITEOFF 
                            WHERE GROUPNAME <> '' 
                            ORDER BY GROUPNAME ASC";
                } else {
                    // Get group data from tbl_aging - GROUP BY center and group to avoid duplicates
                    $sql = "SELECT 
                                a.CENTERNAME,
                                a.GROUPNAME,
                                COUNT(DISTINCT a.ClientNo) as CLIENT_COUNT,
                                COUNT(a.LoanID) as LOAN_COUNT,
                                SUM(a.Balance) as TOTAL_BALANCE,
                                SUM(a.LOANAMOUNT) as TOTAL_LOAN_AMOUNT,
                                GROUP_CONCAT(DISTINCT a.FULLNAME ORDER BY a.FULLNAME SEPARATOR ', ') as CLIENT_NAMES,
                                CASE 
                                    WHEN SUM(a.Balance) = 0 THEN 'PAID'
                                    WHEN SUM(a.Balance) > 0 AND SUM(a.Balance) < SUM(a.TOTALAMO) THEN 'PARTIAL'
                                    WHEN SUM(a.Balance) >= SUM(a.TOTALAMO) THEN 'ACTIVE'
                                    ELSE 'ACTIVE'
                                END as LOAN_STATUS
                            FROM tbl_aging a
                            WHERE a.GROUPNAME IS NOT NULL AND a.GROUPNAME <> '' 
                            GROUP BY a.CENTERNAME, a.GROUPNAME
                            ORDER BY a.CENTERNAME ASC, a.GROUPNAME ASC";
                }
                $sTransType = "MULTIPLE";
                break;

            case "WRITEOFF":
                $sql = "SELECT DISTINCT CENTERNAME, GROUPNAME,
                               'WRITEOFF' as LOAN_STATUS,
                               BALANCE, LOANAMOUNT, FULLNAME
                        FROM TBL_WRITEOFF 
                        WHERE GROUPNAME <> '' 
                        ORDER BY GROUPNAME ASC";
                $sTransType = "MULTIPLE";
                break;

            default: // INDIVIDUAL
                // Get ALL loan data from tbl_loans (source of truth) with last payment info
                $sql = "SELECT 
                               -- Core loan information for client list
                               l.LoanID,
                               l.ClientNo,
                               l.FullName as FULLNAME,
                               l.Program as PROGRAM,
                               l.Mode as MODE,
                               l.Product,
                               l.LoanAmount as LOANAMOUNT,
                               l.Balance,
                               l.Interest as INTEREST,
                               l.CBU,
                               l.EF,
                               l.MBA,
                               l.InterestRate as INTERESTRATE,
                               l.Term as TERM,
                               l.PO,
                               l.DateRelease,
                               l.DateMature,
                               l.LoanStatus,
                               l.checkprinted,
                               -- Get last payment date for next due calculation
                               (SELECT MAX(TransactDate) FROM tbl_loanspayment WHERE LoanID = l.LoanID) as LastPaymentDate,
                               -- Enhanced loan status calculation
                               CASE 
                                   WHEN l.Balance = 0 THEN 'PAID'
                                   WHEN l.Balance > 0 AND l.Balance < l.LoanAmount THEN 'PARTIAL'
                                   WHEN l.Balance >= l.LoanAmount THEN 'ACTIVE'
                                   WHEN l.DateMature < CURDATE() AND l.Balance > 0 THEN 'OVERDUE'
                                   ELSE 'UNKNOWN'
                               END as LOAN_STATUS,
                               -- Calculated fields for payment processing
                               (l.LoanAmount + COALESCE(l.Interest, 0) + COALESCE(l.CBU, 0) + COALESCE(l.EF, 0) + COALESCE(l.MBA, 0)) as TOTAL_AMOUNT_DUE,
                               l.Balance as REMAINING_BALANCE
                        FROM tbl_loans l
                        ORDER BY l.FullName ASC, l.DateRelease DESC";
                $sTransType = "SINGLE";
                break;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $accounts[] = $row;
            }
        }

        // Clear any output buffer before sending JSON
        ob_clean();
        
        echo json_encode(array(
            "TBLHEADER" => $tblHeader,
            "ACCOUNTS" => $accounts,
            "TRANSTYPE" => $sTransType,
            "DATA_SOURCE" => $table,
            "QUERY_TYPE" => $type,
            // "DEBUG_SQL" => $sql, // Uncomment for debugging
        ));
    }

    public function LoadAccountDetails($data){
        $acctDetails = [];
        $filter = $data['filter'];
        $transtype = $data['transtype'];
        
        try {
            // Use enhanced query similar to LoadTransactClientName for consistent data
            if ($transtype == "WRITEOFF") {
                $table = "TBL_WRITEOFF";
                $sql = "SELECT * FROM $table WHERE $filter ORDER BY FULLNAME ASC";
            } else {
                // Parse different filter types for different transaction types
                if (preg_match("/CLIENTNO = '([^']+)'/", $filter, $clientMatches)) {
                    // INDIVIDUAL transaction - get specific client loan details
                    $clientNo = $clientMatches[1];
                    
                    // Get complete loan details directly from tbl_loans - include Product and DateRelease for form
                    $sql = "SELECT 
                                   -- All loan information directly from tbl_loans (for Primary Details form)
                                   l.LoanID,
                                   l.ClientNo,
                                   l.FullName as FULLNAME,
                                   l.Product as PRODUCT,
                                   l.Program as PROGRAM,
                                   l.Mode as MODE,
                                   l.PO,
                                   l.LoanAmount as LOANAMOUNT,
                                   l.Balance,
                                   l.Interest,
                                   l.CBU,
                                   l.EF,
                                   l.MBA,
                                   l.InterestRate,
                                   l.Term,
                                   l.DateRelease,
                                   l.DateMature,
                                   -- Payment calculation fields
                                   l.Balance as AmountDueAsOf,
                                   l.Interest as InterestDueAsOf,
                                   0 as PenaltyDue,
                                   l.CBU as CBUDueAsOf,
                                   l.MBA as MBADueAsOf,
                                   0 as TotalArrears,
                                   -- Fund information (use default if not available)
                                   COALESCE(
                                       (SELECT DISTINCT FUND FROM TBL_BANKSETUP LIMIT 1),
                                       'GENERAL FUND'
                                   ) as FUND,
                                   -- Status information
                                   CASE 
                                       WHEN l.Balance = 0 THEN 'PAID'
                                       WHEN l.Balance > 0 AND l.Balance < (l.LoanAmount + COALESCE(l.Interest, 0) + COALESCE(l.CBU, 0) + COALESCE(l.EF, 0) + COALESCE(l.MBA, 0)) THEN 'PARTIAL'
                                       WHEN l.Balance >= (l.LoanAmount + COALESCE(l.Interest, 0) + COALESCE(l.CBU, 0) + COALESCE(l.EF, 0) + COALESCE(l.MBA, 0)) THEN 'ACTIVE'
                                       WHEN l.DateMature < CURDATE() AND l.Balance > 0 THEN 'OVERDUE'
                                       ELSE 'UNKNOWN'
                                   END as LOAN_STATUS
                            FROM tbl_loans l
                            WHERE l.ClientNo = '$clientNo'";
                    
                    // Add LoanID filter if present
                    if (preg_match("/LOANID = '([^']+)'/", $filter, $loanMatches)) {
                        $loanId = $loanMatches[1];
                        $sql .= " AND l.LoanID = '$loanId'";
                    }
                    
                    $sql .= " ORDER BY l.DateRelease DESC";
                    
                } elseif (preg_match("/CENTERNAME = '([^']+)'/", $filter, $centerMatches)) {
                    // CENTER or GROUP transaction - get all loans for the center/group
                    $centerName = $centerMatches[1];
                    
                    // Check if this is a GROUP filter (has both CENTERNAME and GROUPNAME)
                    if (preg_match("/GROUPNAME = '([^']+)'/", $filter, $groupMatches)) {
                        $groupName = $groupMatches[1];
                        
                        // GROUP transaction - get all loans for specific center and group
                        $sql = "SELECT 
                                       l.LoanID,
                                       l.ClientNo,
                                       l.FullName as FULLNAME,
                                       l.Product as PRODUCT,
                                       l.Program as PROGRAM,
                                       l.Mode as MODE,
                                       l.PO,
                                       l.LoanAmount as LOANAMOUNT,
                                       l.Balance,
                                       l.Interest,
                                       l.CBU,
                                       l.EF,
                                       l.MBA,
                                       l.InterestRate,
                                       l.Term,
                                       l.DateRelease,
                                       l.DateMature,
                                       -- Payment calculation fields
                                       l.Balance as AmountDueAsOf,
                                       l.Interest as InterestDueAsOf,
                                       0 as PenaltyDue,
                                       l.CBU as CBUDueAsOf,
                                       l.MBA as MBADueAsOf,
                                       0 as TotalArrears,
                                       -- Fund information
                                       COALESCE(
                                           (SELECT DISTINCT FUND FROM TBL_BANKSETUP LIMIT 1),
                                           'GENERAL FUND'
                                       ) as FUND,
                                       -- Status information
                                       CASE 
                                           WHEN l.Balance = 0 THEN 'PAID'
                                           WHEN l.Balance > 0 AND l.Balance < (l.LoanAmount + COALESCE(l.Interest, 0) + COALESCE(l.CBU, 0) + COALESCE(l.EF, 0) + COALESCE(l.MBA, 0)) THEN 'PARTIAL'
                                           WHEN l.Balance >= (l.LoanAmount + COALESCE(l.Interest, 0) + COALESCE(l.CBU, 0) + COALESCE(l.EF, 0) + COALESCE(l.MBA, 0)) THEN 'ACTIVE'
                                           WHEN l.DateMature < CURDATE() AND l.Balance > 0 THEN 'OVERDUE'
                                           ELSE 'UNKNOWN'
                                       END as LOAN_STATUS
                                FROM tbl_loans l
                                WHERE l.ClientNo IN (
                                    SELECT DISTINCT ClientNo 
                                    FROM tbl_aging 
                                    WHERE CENTERNAME = '$centerName' 
                                      AND GROUPNAME = '$groupName'
                                )
                                  AND l.Balance > 0
                                ORDER BY l.FullName ASC, l.DateRelease DESC";
                    } else {
                        // CENTER transaction - get all loans for the center
                        $sql = "SELECT 
                                       l.LoanID,
                                       l.ClientNo,
                                       l.FullName as FULLNAME,
                                       l.Product as PRODUCT,
                                       l.Program as PROGRAM,
                                       l.Mode as MODE,
                                       l.PO,
                                       l.LoanAmount as LOANAMOUNT,
                                       l.Balance,
                                       l.Interest,
                                       l.CBU,
                                       l.EF,
                                       l.MBA,
                                       l.InterestRate,
                                       l.Term,
                                       l.DateRelease,
                                       l.DateMature,
                                       -- Payment calculation fields
                                       l.Balance as AmountDueAsOf,
                                       l.Interest as InterestDueAsOf,
                                       0 as PenaltyDue,
                                       l.CBU as CBUDueAsOf,
                                       l.MBA as MBADueAsOf,
                                       0 as TotalArrears,
                                       -- Fund information
                                       COALESCE(
                                           (SELECT DISTINCT FUND FROM TBL_BANKSETUP LIMIT 1),
                                           'GENERAL FUND'
                                       ) as FUND,
                                       -- Status information
                                       CASE 
                                           WHEN l.Balance = 0 THEN 'PAID'
                                           WHEN l.Balance > 0 AND l.Balance < (l.LoanAmount + COALESCE(l.Interest, 0) + COALESCE(l.CBU, 0) + COALESCE(l.EF, 0) + COALESCE(l.MBA, 0)) THEN 'PARTIAL'
                                           WHEN l.Balance >= (l.LoanAmount + COALESCE(l.Interest, 0) + COALESCE(l.CBU, 0) + COALESCE(l.EF, 0) + COALESCE(l.MBA, 0)) THEN 'ACTIVE'
                                           WHEN l.DateMature < CURDATE() AND l.Balance > 0 THEN 'OVERDUE'
                                           ELSE 'UNKNOWN'
                                       END as LOAN_STATUS
                                FROM tbl_loans l
                                WHERE l.ClientNo IN (
                                    SELECT DISTINCT ClientNo 
                                    FROM tbl_aging 
                                    WHERE CENTERNAME = '$centerName'
                                )
                                  AND l.Balance > 0
                                ORDER BY l.FullName ASC, l.DateRelease DESC";
                    }
                } else {
                    // Fallback to original query if filter format is unexpected
                    $sql = "SELECT * FROM TBL_AGING WHERE $filter ORDER BY FULLNAME ASC";
                }
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $acctDetails[] = $row;
                }
            }

            echo json_encode(array( 
                "ACCTDETAILS" => $acctDetails,
                "STATUS" => "SUCCESS",
                "MESSAGE" => "Account details loaded successfully"
            ));
            
        } catch (Exception $e) {
            // Return error as JSON instead of letting PHP show the error
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => "Failed to load account details: " . $e->getMessage(),
                "ACCTDETAILS" => []
            ));
        }
    }

    public function LoadORSeries($data){
        $seriesdata = 0;
        $sql = "SELECT NEXTOR,ORLEFT,ORSTATUS FROM TBL_ORSERIES WHERE NAME = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $data["SeriesName"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $seriesdata = $row;
        }

        echo json_encode(array( 
            "SERIESDATA" => $seriesdata,
        ));
    }

    public function LoadDepositoryBank($data){
        $bank = [];
        $sql = "SELECT BANK FROM TBL_BANKSETUP WHERE FUND = ? AND BANK <> '-' ORDER BY BANK";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $data["Fund"]);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $bank[] = $row;
            }
        }

        echo json_encode(array( 
            "BANK" => $bank,
        ));
    }

    public function LoadClientType(){
        $clienttype = [];
        
        try {
            // Use the same approach as Initialize() function which works
            $result = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_clientlist ORDER BY Type");
            
            if ($result && count($result) > 0) {
                foreach ($result as $row) {
                    $clienttype[] = ["TYPE" => $row["Type"]];
                }
            }
            
            // If no client types found, provide default ones
            if (empty($clienttype)) {
                $clienttype = [
                    ["TYPE" => "INDIVIDUAL"],
                    ["TYPE" => "CORPORATION"],
                    ["TYPE" => "PARTNERSHIP"],
                    ["TYPE" => "COOPERATIVE"]
                ];
            }
            
        } catch (Exception $e) {
            // If there's any error, provide default client types
            error_log("LoadClientType error: " . $e->getMessage());
            $clienttype = [
                ["TYPE" => "INDIVIDUAL"],
                ["TYPE" => "CORPORATION"],
                ["TYPE" => "PARTNERSHIP"],
                ["TYPE" => "COOPERATIVE"]
            ];
        }

        echo json_encode(array( 
            "CLIENTTYPE" => $clienttype,
        ));
    }

    public function LoadClientName($data){
        $qry = "";
        $column = "";
        $type = $data['transactType'];
        $table = $type == "WRITEOFF" ? "TBL_WRITEOFF" : "TBL_AGING";
        $clientType = $data['clientType'];

        $clientName = [];

        if ($clientType == "CUSTOMER") {
            $column = "FULLNAME";
            $qry = "SELECT DISTINCT $column FROM $table ORDER BY $column";
        } else {
            $column = "NAME";
            $qry = "SELECT DISTINCT NAME FROM tbl_clientlist WHERE type = '$clientType' ORDER BY $column";
        }
        $stmt = $this->conn->prepare($qry);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clientName[] = [ "NAME" => $row[$column]];
            }
        }

        echo json_encode(array( 
            "CLIENTNAMELIST" => $clientName,
        ));
    }

    public function GetClientInfo($data){
        $qry = "";
        $column = "";
        $type = $data['transactType'];
        $table = $type == "WRITEOFF" ? "TBL_WRITEOFF" : "TBL_AGING";
        $clientType = $data['clientType'];
        $name = $data['name'];

        $clientName = [];

        if ($clientType == "CUSTOMER") {
            $qry = "SELECT FULLADDRESS FROM $table WHERE FULLNAME = '$name'";
        } else {
            $qry = "SELECT * FROM tbl_clientlist WHERE type = '$clientType' AND name = '$name'";
        }
        $stmt = $this->conn->prepare($qry);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clientName[] = $row;
            }
        }

        echo json_encode(array( 
            "CLIENTINFOLIST" => $clientName,
        ));
    }
    public function SaveTransaction($data){
        // Clear any previous output to ensure clean JSON response
        ob_clean();
        
        // EMERGENCY DEBUG: Log the ACTUAL data being received
        error_log("=== REAL PAYMENT DATA DEBUG ===");
        error_log("Raw data received: " . print_r($data, true));
        if (isset($data['paymentData'])) {
            error_log("Payment Data JSON: " . $data['paymentData']);
            $paymentDataDecoded = json_decode($data['paymentData'], true);
            error_log("Payment Data Decoded: " . print_r($paymentDataDecoded, true));
            if (isset($paymentDataDecoded[0])) {
                $firstPayment = $paymentDataDecoded[0];
                error_log("First Payment Array: " . print_r($firstPayment, true));
                error_log("Array indices: [0]=" . ($firstPayment[0] ?? 'NULL') . " [1]=" . ($firstPayment[1] ?? 'NULL') . " [2]=" . ($firstPayment[2] ?? 'NULL') . " [3]=" . ($firstPayment[3] ?? 'NULL') . " [4]=" . ($firstPayment[4] ?? 'NULL'));
            }
        }
        error_log("=== END DEBUG ===");
        
        // Add error handling to prevent PHP errors from breaking JSON
        try {
            // Validate required data first
            if (!isset($data['paymentData']) || empty($data['paymentData'])) {
                echo json_encode(array(
                    "STATUS" => "ERROR",
                    "MESSAGE" => "No payment data received"
                ));
                return;
            }
            
            $paymentData = json_decode($data['paymentData'], true);
            if (!$paymentData || !is_array($paymentData) || count($paymentData) === 0) {
                echo json_encode(array(
                    "STATUS" => "ERROR",
                    "MESSAGE" => "Invalid or empty payment data",
                    "DEBUG" => array(
                        "received_data" => $data['paymentData'] ?? 'not set',
                        "json_decode_result" => $paymentData,
                        "json_last_error" => json_last_error_msg()
                    )
                ));
                return;
            }
            
            // Log successful data parsing for debugging
            error_log("SaveTransaction: Processing " . count($paymentData) . " payment records");
            error_log("SaveTransaction: Payment data structure: " . print_r($paymentData[0] ?? [], true));
            
            $this->conn->begin_transaction();

            // Extract data
            $clientType = $data['clientType'];
            $clientName = $data['clientName'];
            $clientAddress = $data['clientAddress'];
            $clientTIN = $data['clientTIN'];
            $particulars = $data['particulars'];
            $paymentType = $data['paymentType'];
            // Handle check-related fields based on payment type
            if ($paymentType === 'CASH') {
                // For cash payments, use special values to indicate "not applicable"
                $checkDate = '0000-00-00'; // MySQL date field - use default date for cash
                $checkNo = '-';
                $bankName = '-';
                $bankBranch = '-';
            } else {
                // For check payments, use actual values or defaults
                $checkDate = (!empty($data['checkDate']) && $data['checkDate'] !== '') ? $data['checkDate'] : '0000-00-00';
                $checkNo = (!empty($data['checkNo']) && $data['checkNo'] !== '') ? $data['checkNo'] : '-';
                $bankName = (!empty($data['bankName']) && $data['bankName'] !== '') ? $data['bankName'] : '-';
                $bankBranch = (!empty($data['bankBranch']) && $data['bankBranch'] !== '') ? $data['bankBranch'] : '-';
            }
            $orFrom = $data['orFrom'];
            $orno = $data['orno'];
            $orLeft = $data['orLeft'] ?? null; // Handle ORLeft field
            $depositorybank = $data['depositorybank'];
            $transactType = $data['transactType'];
            $paymentData = json_decode($data['paymentData'], true);

            $username = $_SESSION['USERNAME'] ?? '';
            $transactDate = date('Y-m-d H:i:s');

            // SIMPLE: Process each payment with proper breakdown
            foreach ($paymentData as $payment) {
                // Validate payment data structure
                if (!is_array($payment) || count($payment) < 8) {
                    error_log("ERROR: Invalid payment data structure: " . print_r($payment, true));
                    continue;
                }
                
                $clientName = $payment[0] ?? '';
                $paymentAmount = floatval($payment[1] ?? 0); // THE ACTUAL PAYMENT AMOUNT
                $principal = floatval($payment[2] ?? 0);     // Principal breakdown
                $interest = floatval($payment[3] ?? 0);      // Interest breakdown
                $penalty = floatval($payment[4] ?? 0);       // Penalty breakdown
                $clientNo = $payment[5] ?? '';
                $loanId = $payment[6] ?? '';
                $fund = $payment[7] ?? 'GENERAL';
                
                // Calculate Total from breakdown (Principal + Interest + Penalty)
                $total = $principal + $interest + $penalty;
                
                // Validate required fields
                if (empty($clientNo) || empty($loanId) || $paymentAmount <= 0) {
                    error_log("ERROR: Missing required payment data - ClientNo: $clientNo, LoanID: $loanId, Payment: $paymentAmount");
                    continue;
                }

                error_log("PROCESSING: ClientName: $clientName, Payment: $paymentAmount, Principal: $principal, Interest: $interest, Penalty: $penalty, Total: $total, ClientNo: $clientNo, LoanID: $loanId, Fund: $fund");

                // Insert into tbl_loanspayment - with proper breakdown
                $sql = "INSERT INTO tbl_loanspayment (
                    TransactDate, Username, ClientType, ClientName, ClientAddress, ClientTIN,
                    Particulars, PaymentType, CheckDate, CheckNo, BankName, BankBranch,
                    ORFrom, ORNo, ORLeft, DepositoryBank, ClientNo, LoanID, Fund,
                    Principal, Interest, Penalty, Total, Payment
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $this->conn->prepare($sql);
                if (!$stmt) {
                    error_log("ERROR: Failed to prepare statement: " . $this->conn->error);
                    continue;
                }
                
                $stmt->bind_param('ssssssssssssssissssddddd',
                    $transactDate, $username, $clientType, $clientName, $clientAddress, $clientTIN,
                    $particulars, $paymentType, $checkDate, $checkNo, $bankName, $bankBranch,
                    $orFrom, $orno, $orLeft, $depositorybank, $clientNo, $loanId, $fund,
                    $principal, $interest, $penalty, $total, $paymentAmount // Payment = actual amount paid as double, not string
                );
                
                if (!$stmt->execute()) {
                    error_log("ERROR: Failed to execute statement: " . $stmt->error);
                    $stmt->close();
                    continue;
                }
                $stmt->close();

                // Update balance - use PAYMENT AMOUNT for balance calculation
                if ($paymentAmount > 0) {
                    try {
                        $this->updateBalanceSimple($loanId, $paymentAmount);
                    } catch (Exception $e) {
                        error_log("ERROR: Failed to update balance for LoanID $loanId: " . $e->getMessage());
                    }
                }
            }

            // Update OR Series (keep existing logic)
            $sql = "UPDATE TBL_ORSERIES SET NEXTOR = NEXTOR + 1, ORLEFT = ORLEFT - 1 WHERE NAME = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $orFrom);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();

            // Prepare receipt data
            $receiptData = array(
                "ORNO" => $orno,
                "TRANSACTION_DATE" => $transactDate,
                "CLIENT_NAME" => $clientName,
                "CLIENT_ADDRESS" => $clientAddress,
                "CLIENT_TIN" => $clientTIN,
                "PAYMENT_TYPE" => $paymentType,
                "CHECK_DATE" => $checkDate,
                "CHECK_NO" => $checkNo,
                "BANK_NAME" => $bankName,
                "BANK_BRANCH" => $bankBranch,
                "PARTICULARS" => $particulars,
                "PAYMENTS" => array(),
                "TOTAL_AMOUNT" => 0
            );

            // Add payment details for receipt
            $totalAmount = 0;
            foreach ($paymentData as $payment) {
                // Correct array indices: [clientName, paymentAmount, principal, interest, penalty, clientNo, loanId, fund]
                $paymentAmount = floatval($payment[1] ?? 0);  // Actual payment amount
                $principal = floatval($payment[2] ?? 0);      // Principal breakdown
                $interest = floatval($payment[3] ?? 0);       // Interest breakdown  
                $penalty = floatval($payment[4] ?? 0);        // Penalty breakdown
                $total = $principal + $interest + $penalty;   // Calculate total from breakdown
                $totalAmount += $paymentAmount; // Use payment amount for total, not breakdown total

                $receiptData["PAYMENTS"][] = array(
                    "CLIENT_NAME" => $payment[0],
                    "LOAN_ID" => $payment[6],
                    "PAYMENT_AMOUNT" => $paymentAmount,  // Add actual payment amount
                    "PRINCIPAL" => $principal,
                    "INTEREST" => $interest,
                    "PENALTY" => $penalty,
                    "TOTAL" => $total  // This is the breakdown total
                );
            }
            $receiptData["TOTAL_AMOUNT"] = $totalAmount;

            echo json_encode(array(
                "STATUS" => "SUCCESS",
                "MESSAGE" => "Payment processed successfully. Loan balances updated based on principal payments only.",
                "RECEIPT_DATA" => $receiptData,
                "TOTAL_PAYMENTS" => count($paymentData)
            ));

        } catch (Exception $e) {
            $this->conn->rollback();
            
            // Log the error for debugging
            error_log("SaveTransaction error: " . $e->getMessage());
            error_log("SaveTransaction trace: " . $e->getTraceAsString());
            
            // Clear any output and return clean JSON error
            ob_clean();
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => "Failed to save transaction. Please check the form data and try again.",
                "DEBUG" => array(
                    "error" => $e->getMessage(),
                    "line" => $e->getLine(),
                    "file" => basename($e->getFile())
                )
            ));
        } catch (Error $e) {
            // Handle PHP fatal errors
            if ($this->conn) {
                $this->conn->rollback();
            }
            
            error_log("SaveTransaction fatal error: " . $e->getMessage());
            
            ob_clean();
            echo json_encode(array(
                "STATUS" => "ERROR", 
                "MESSAGE" => "System error occurred. Please contact support.",
                "DEBUG" => array(
                    "error" => $e->getMessage(),
                    "line" => $e->getLine(),
                    "file" => basename($e->getFile())
                )
            ));
        }
    }
    public function UpdateLoanAmounts($data) {
        try {
            // Validate required data
            if (!isset($data['loanId']) || !isset($data['principal']) || !isset($data['interest']) || !isset($data['penalty'])) {
                $response = array("success" => false, "message" => "Missing required loan amount data");
                echo json_encode($response);
                return;
            }

            $loanId = $data['loanId'];
            $principal = floatval($data['principal']);
            $interest = floatval($data['interest']);
            $penalty = floatval($data['penalty']);
            $total = $principal + $interest + $penalty;

            // Start transaction
            $this->conn->begin_transaction();

            try {
                // Get ClientNo for aging table update
                $clientQuery = "SELECT ClientNo FROM tbl_loans WHERE LOANID = ?";
                $clientStmt = $this->conn->prepare($clientQuery);
                $clientStmt->bind_param("s", $loanId);
                $clientStmt->execute();
                $clientResult = $clientStmt->get_result();

                if ($clientResult->num_rows === 0) {
                    throw new Exception("No loan found with ID: $loanId");
                }

                $loanData = $clientResult->fetch_assoc();
                $clientNo = $loanData['ClientNo'];
                $clientStmt->close();

                // Update tbl_loans
                $updateLoansQuery = "UPDATE tbl_loans SET 
                                    LoanAmount = ?, 
                                    Interest = ?, 
                                    Penalty = ?
                                    WHERE LOANID = ?";

                $loansStmt = $this->conn->prepare($updateLoansQuery);
                $loansStmt->bind_param("ddds", $principal, $interest, $penalty, $loanId);
                $loansStmt->execute();
                $loansAffectedRows = $loansStmt->affected_rows;
                $loansStmt->close();

                // Update tbl_aging if record exists
                $agingAffectedRows = 0;
                $checkAgingQuery = "SELECT LOANAMOUNT, BALANCE FROM tbl_aging WHERE ClientNo = ? AND LoanID = ? LIMIT 1";
                $checkAgingStmt = $this->conn->prepare($checkAgingQuery);
                $checkAgingStmt->bind_param('ss', $clientNo, $loanId);
                $checkAgingStmt->execute();
                $agingResult = $checkAgingStmt->get_result();

                if ($agingResult->num_rows > 0) {
                    $agingData = $agingResult->fetch_assoc();
                    $currentAgingBalance = floatval($agingData['BALANCE']);
                    $newTotalAmountDue = $total;

                    $updateAgingQuery = "UPDATE tbl_aging SET 
                                        LOANAMOUNT = ?,
                                        BALANCE = ?,
                                        LOANSTATUS = CASE 
                                            WHEN ? = 0 THEN 'PAID'
                                            ELSE 'ACTIVE'
                                        END
                                        WHERE ClientNo = ? AND LoanID = ?";

                    $agingStmt = $this->conn->prepare($updateAgingQuery);
                    $agingStmt->bind_param("dddss", $newTotalAmountDue, $currentAgingBalance, $currentAgingBalance, $clientNo, $loanId);
                    $agingStmt->execute();
                    $agingAffectedRows = $agingStmt->affected_rows;
                    $agingStmt->close();
                }
                $checkAgingStmt->close();

                // Commit transaction
                $this->conn->commit();

                $response = array(
                    "success" => true, 
                    "message" => "Loan amounts updated successfully",
                    "data" => array(
                        "loanId" => $loanId,
                        "principal" => $principal,
                        "interest" => $interest,
                        "penalty" => $penalty,
                        "total" => $total,
                        "loansAffectedRows" => $loansAffectedRows,
                        "agingAffectedRows" => $agingAffectedRows
                    )
                );
                echo json_encode($response);

            } catch (Exception $e) {
                $this->conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            $response = array("success" => false, "message" => "Error: " . $e->getMessage());
            echo json_encode($response);
        }
    }

    // SIMPLE balance update method - just basic math
    private function updateBalanceSimple($loanId, $paymentAmount) {
        error_log("=== UPDATE BALANCE SIMPLE START ===");
        error_log("LoanID: $loanId");
        error_log("Payment Amount Received: $paymentAmount");
        
        // Get current balance
        $query = "SELECT LoanAmount, Balance FROM tbl_loans WHERE LoanID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $loan = $result->fetch_assoc();
            $loanAmount = floatval($loan['LoanAmount']);
            $currentBalance = floatval($loan['Balance']);
            
            error_log("Loan Amount from DB: $loanAmount");
            error_log("Current Balance from DB: $currentBalance");
            
            // If balance is 0, set to loan amount
            if ($currentBalance == 0) {
                $currentBalance = $loanAmount;
                error_log("Balance was 0, set to Loan Amount: $currentBalance");
            }
            
            // SIMPLE MATH: Balance = Balance - Payment
            $newBalance = $currentBalance - $paymentAmount;
            error_log("CALCULATION: $currentBalance - $paymentAmount = $newBalance");
            
            if ($newBalance < 0) {
                error_log("New balance was negative ($newBalance), setting to 0");
                $newBalance = 0;
            }
            
            $status = ($newBalance == 0) ? 'PAID' : 'ACTIVE';
            error_log("New Status: $status");
            
            // Update tbl_loans
            $updateQuery = "UPDATE tbl_loans SET Balance = ?, LoanStatus = ? WHERE LoanID = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bind_param('dss', $newBalance, $status, $loanId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Update tbl_aging
            $agingQuery = "UPDATE tbl_aging SET AmountDue = ?, LoanStatus = ? WHERE LoanID = ?";
            $agingStmt = $this->conn->prepare($agingQuery);
            $agingStmt->bind_param('dss', $newBalance, $status, $loanId);
            $agingStmt->execute();
            $agingStmt->close();
            
            error_log("=== UPDATE COMPLETE: New Balance = $newBalance, Status = $status ===");
        } else {
            error_log("ERROR: Loan not found for LoanID: $loanId");
        }
        $stmt->close();
    }

    // Helper function to update loan balance in tbl_loans - ACTUAL PAYMENT AMOUNT VERSION
    private function updateLoanBalance($loanId, $paymentAmount) {
        error_log("PAYMENT: updateLoanBalance - LoanID: $loanId, Payment: $paymentAmount");
        
        // Get current loan data
        $query = "SELECT LoanAmount, Balance FROM tbl_loans WHERE LoanID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $loan = $result->fetch_assoc();
            $loanAmount = floatval($loan['LoanAmount']);
            $currentBalance = floatval($loan['Balance']);
            
            // If balance is 0 or null, set it to loan amount first (initial state)
            if ($currentBalance == 0) {
                $currentBalance = $loanAmount;
            }
            
            // CORRECT MATH: New Balance = Current Balance - Payment Amount
            $newBalance = $currentBalance - $paymentAmount;
            
            // Don't let balance go negative
            if ($newBalance < 0) {
                $newBalance = 0;
            }
            
            // Status: PAID only if balance is 0, otherwise ACTIVE
            $status = ($newBalance == 0) ? 'PAID' : 'ACTIVE';
            
            error_log("PAYMENT CALCULATION: LoanAmount: $loanAmount, Current Balance: $currentBalance, Payment: $paymentAmount, New Balance: $newBalance, Status: $status");
            
            // Update the loan
            $updateQuery = "UPDATE tbl_loans SET Balance = ?, LoanStatus = ? WHERE LoanID = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bind_param('dss', $newBalance, $status, $loanId);
            
            if ($updateStmt->execute()) {
                error_log("SUCCESS: Updated LoanID $loanId - Balance: $newBalance, Status: $status");
                
                // Verify the update by checking the new balance
                $verifyQuery = "SELECT Balance, LoanStatus FROM tbl_loans WHERE LoanID = ?";
                $verifyStmt = $this->conn->prepare($verifyQuery);
                $verifyStmt->bind_param('s', $loanId);
                $verifyStmt->execute();
                $verifyResult = $verifyStmt->get_result();
                
                if ($verifyResult && $verifyResult->num_rows > 0) {
                    $verifyData = $verifyResult->fetch_assoc();
                    $actualBalance = floatval($verifyData['Balance']);
                    $actualStatus = $verifyData['LoanStatus'];
                    
                    error_log("VERIFY: After update - ActualBalance: $actualBalance, ActualStatus: $actualStatus");
                    
                    if (abs($actualBalance - $newBalance) > 0.01) {
                        error_log("ERROR: Balance verification failed! Expected: $newBalance, Actual: $actualBalance");
                    } else {
                        error_log("SUCCESS: Balance verification passed");
                    }
                } else {
                    error_log("ERROR: Could not verify balance update - loan not found");
                }
                $verifyStmt->close();
            } else {
                error_log("ERROR: Failed to update tbl_loans balance for LoanID: $loanId - " . $updateStmt->error);
            }
            $updateStmt->close();
        } else {
            error_log("ERROR: No loan record found for LoanID: $loanId. Skipping balance update.");
        }
        $stmt->close();
    }

    // Helper function to update balance in tbl_aging for portfolio sync
    // Helper function to update balance in tbl_aging for portfolio sync - ACTUAL PAYMENT AMOUNT VERSION
    private function updateAgingBalance($clientNo, $loanId, $paymentAmount) {
        error_log("PAYMENT: updateAgingBalance - ClientNo: $clientNo, LoanID: $loanId, Payment: $paymentAmount");
        
        // Get current aging data
        $query = "SELECT AmountDue, LoanAmount FROM tbl_aging WHERE ClientNo = ? AND LoanID = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $clientNo, $loanId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $aging = $result->fetch_assoc();
            $currentAmountDue = floatval($aging['AmountDue']);
            $loanAmount = floatval($aging['LoanAmount']);
            
            // If amount due is 0 or null, set it to loan amount first (initial state)
            if ($currentAmountDue == 0) {
                $currentAmountDue = $loanAmount;
            }
            
            // CORRECT MATH: New Amount Due = Current Amount Due - Payment Amount
            $newAmountDue = $currentAmountDue - $paymentAmount;
            
            // Don't let amount due go negative
            if ($newAmountDue < 0) {
                $newAmountDue = 0;
            }
            
            // Status: PAID only if amount due is 0, otherwise ACTIVE
            $status = ($newAmountDue == 0) ? 'PAID' : 'ACTIVE';
            
            error_log("PAYMENT CALCULATION: LoanAmount: $loanAmount, Current AmountDue: $currentAmountDue, Payment: $paymentAmount, New AmountDue: $newAmountDue, Status: $status");
            
            // Update aging table
            $updateQuery = "UPDATE tbl_aging SET AmountDue = ?, LoanStatus = ? WHERE ClientNo = ? AND LoanID = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bind_param('dsss', $newAmountDue, $status, $clientNo, $loanId);
            $updateStmt->execute();
            $updateStmt->close();
            
            error_log("SUCCESS: Updated aging ClientNo $clientNo, LoanID $loanId - AmountDue: $newAmountDue, Status: $status");
        } else {
            error_log("ERROR: No aging record found for ClientNo: $clientNo, LoanID: $loanId");
        }
        $stmt->close();
    }

    // New function to sync tbl_aging with tbl_loans data
    public function SyncAgingData($data = null) {
        try {
            $syncCount = 0;
            
            // Get all active loans from tbl_loans
            $loansQuery = "SELECT l.LoanID, l.ClientNo, l.FullName, l.LoanAmount, l.Balance, 
                                 l.DateRelease, l.Product, l.Program, l.Mode, l.LoanStatus,
                                 COALESCE(a.CENTERNAME, '') as CENTERNAME,
                                 COALESCE(a.GROUPNAME, '') as GROUPNAME
                          FROM tbl_loans l
                          LEFT JOIN tbl_aging a ON l.ClientNo = a.ClientNo";
            
            $loansResult = $this->conn->query($loansQuery);
            
            if ($loansResult && $loansResult->num_rows > 0) {
                while ($loan = $loansResult->fetch_assoc()) {
                    // Check if record exists in tbl_aging
                    $checkQuery = "SELECT COUNT(*) as exists_count FROM tbl_aging WHERE ClientNo = ?";
                    $checkStmt = $this->conn->prepare($checkQuery);
                    $checkStmt->bind_param('s', $loan['ClientNo']);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    $exists = $checkResult->fetch_assoc()['exists_count'] > 0;
                    $checkStmt->close();
                    
                    if ($exists) {
                        // Update existing record
                        $updateQuery = "UPDATE tbl_aging 
                                       SET FULLNAME = ?, 
                                           LOANAMOUNT = ?, 
                                           BALANCE = ?, 
                                           DATERELEASE = ?, 
                                           PRODUCT = ?, 
                                           PROGRAM = ?, 
                                           MODE = ?, 
                                           LOANSTATUS = ?
                                       WHERE ClientNo = ?";
                        $updateStmt = $this->conn->prepare($updateQuery);
                        $updateStmt->bind_param('sddssssss', 
                            $loan['FullName'], $loan['LoanAmount'], $loan['Balance'],
                            $loan['DateRelease'], $loan['Product'], $loan['Program'], 
                            $loan['Mode'], $loan['LoanStatus'], $loan['ClientNo']
                        );
                        $updateStmt->execute();
                        $updateStmt->close();
                    } else {
                        // Insert new record
                        $insertQuery = "INSERT INTO tbl_aging 
                                       (ClientNo, CENTERNAME, GROUPNAME, FULLNAME, LOANAMOUNT, 
                                        BALANCE, DATERELEASE, PRODUCT, PROGRAM, MODE, LOANSTATUS)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $insertStmt = $this->conn->prepare($insertQuery);
                        $insertStmt->bind_param('ssssddsssss',
                            $loan['ClientNo'], $loan['CENTERNAME'], $loan['GROUPNAME'],
                            $loan['FullName'], $loan['LoanAmount'], $loan['Balance'],
                            $loan['DateRelease'], $loan['Product'], $loan['Program'],
                            $loan['Mode'], $loan['LoanStatus']
                        );
                        $insertStmt->execute();
                        $insertStmt->close();
                    }
                    $syncCount++;
                }
            }
            
            echo json_encode(array(
                "STATUS" => "SUCCESS",
                "MESSAGE" => "Aging data synchronized successfully",
                "RECORDS_SYNCED" => $syncCount
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => "Failed to sync aging data: " . $e->getMessage()
            ));
        }
    }

    // New function to check and fix missing column data
    public function CheckMissingData($data = null) {
        try {
            $issues = [];
            
            // Check for missing Product data
            $productCheck = "SELECT COUNT(*) as missing_count FROM tbl_loans 
                            WHERE Balance > 0 AND (Product IS NULL OR Product = '' OR Product = '-')";
            $result = $this->conn->query($productCheck);
            if ($result) {
                $count = $result->fetch_assoc()['missing_count'];
                if ($count > 0) {
                    $issues[] = "Missing Product data in $count loans";
                }
            }
            
            // Check for missing LoanID data
            $loanIdCheck = "SELECT COUNT(*) as missing_count FROM tbl_loans 
                           WHERE Balance > 0 AND (LoanID IS NULL OR LoanID = '' OR LoanID = '-')";
            $result = $this->conn->query($loanIdCheck);
            if ($result) {
                $count = $result->fetch_assoc()['missing_count'];
                if ($count > 0) {
                    $issues[] = "Missing LoanID data in $count loans";
                }
            }
            
            // Check for missing DateRelease data
            $dateCheck = "SELECT COUNT(*) as missing_count FROM tbl_loans 
                         WHERE Balance > 0 AND (DateRelease IS NULL OR DateRelease = '' OR DateRelease = '0000-00-00')";
            $result = $this->conn->query($dateCheck);
            if ($result) {
                $count = $result->fetch_assoc()['missing_count'];
                if ($count > 0) {
                    $issues[] = "Missing DateRelease data in $count loans";
                }
            }
            
            // Get sample of problematic records
            $sampleQuery = "SELECT LoanID, ClientNo, Product, DateRelease, Program, Mode 
                           FROM tbl_loans 
                           WHERE Balance > 0 
                             AND (Product IS NULL OR Product = '' OR Product = '-'
                                  OR LoanID IS NULL OR LoanID = '' OR LoanID = '-'
                                  OR DateRelease IS NULL OR DateRelease = '' OR DateRelease = '0000-00-00')
                           LIMIT 10";
            $sampleResult = $this->conn->query($sampleQuery);
            $samples = [];
            if ($sampleResult && $sampleResult->num_rows > 0) {
                while ($row = $sampleResult->fetch_assoc()) {
                    $samples[] = $row;
                }
            }
            
            echo json_encode(array(
                "STATUS" => "SUCCESS",
                "ISSUES" => $issues,
                "SAMPLE_RECORDS" => $samples,
                "TOTAL_ISSUES" => count($issues)
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => "Failed to check missing data: " . $e->getMessage()
            ));
        }
    }


    public function LoadCategory($data){
        $categ = [];
        $isConsign = $data['isConsign'];
        $type = $data['type'];
        $isynBranch = $data['isynBranch'];
        $consignBranch = $data['consignBranch'];

        if ($isConsign === "Yes"){
            $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlistconsign WHERE Type = ? AND Stock = ? AND Branch = ? ORDER BY Category");
            $stmt->bind_param('sss', $type, $consignBranch, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categ[] = $row;
                }
            }
        } else {
            $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist WHERE Type = ? AND Branch = ? ORDER BY Category");
            $stmt->bind_param('ss', $type, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $categ[] = $row;
                }
            }
        }

        echo json_encode(array( 
            "CATEG" => $categ,
        ));
    }

    public function LoadSerialProduct($data){
        $serialproduct = [];
        $productSINo = [];
        $isConsign = $data['isConsign'];
        $type = $data['type'];
        $category = $data['category'];
        $isynBranch = $data['isynBranch'];
        $consignBranch = $data['consignBranch'];

        if ($isConsign === "Yes"){
            $stmt = $this->conn->prepare("SELECT SIno, Serialno, Product FROM tbl_invlistconsign WHERE Category = ? AND Type = ? AND Stock = ? AND Branch = ?");
            $stmt->bind_param('ssss', $category, $type, $consignBranch, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $serialproduct[] = $row;
                }
            }

            $stmt2 = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_invlistconsign WHERE Category = ? AND Type = ? AND Stock = ? AND Branch = ?");
            $stmt2->bind_param('ssss', $category, $type, $consignBranch, $isynBranch);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    $productSINo[] = $row;
                }
            }
        } else {
            $stmt = $this->conn->prepare("SELECT SIno, Serialno, Product FROM tbl_invlist WHERE Category = ? AND Type = ? AND Branch = ?");
            $stmt->bind_param('sss', $category, $type, $isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $serialproduct[] = $row;
                }
            }

            $stmt2 = $this->conn->prepare("SELECT DISTINCT SIno FROM tbl_invlist WHERE Category = ? AND Type = ? AND Branch = ?");
            $stmt2->bind_param('sss', $category, $type, $isynBranch);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {
                    $productSINo[] = $row;
                }
            }
        }

        echo json_encode(array( 
            "SRKPRDT" => $serialproduct,
            "PRDTSINO" => $productSINo,
        ));
    }

    public function LoadProductSummary($data){
        $branch = $data["isConsign"] == "No" ? $data["isynBranch"] : $data["consignBranch"];
        $table = $data["isConsign"] == "No" ? "tbl_invlist" : "tbl_invlistconsign";
        $selectBy = $data["selectBy"];
        $type = $data["type"];
        $category = $data["category"];
        $serialProduct = $data["serialProduct"];
        $SINo = $data["SINo"];
        // $isynBranch = $data["isynBranch"];
        // $consignBranch = $data["consignBranch"];
        $productSummary = "";

        if ($selectBy == "serial"){
            $stmt = $this->conn->prepare("SELECT * FROM ".$table." WHERE Type = ? AND Category = ? AND SIno = ? AND stock = ? AND Serialno = ?");
            $stmt->bind_param('sssss', $type, $category, $SINo, $branch, $serialProduct);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $productSummary = $row;
            }
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM ".$table." WHERE Type = ? AND Category = ? AND SIno = ? AND stock = ? AND Product = ?");
            $stmt->bind_param('sssss', $type, $category, $SINo, $branch, $serialProduct);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $productSummary = $row;
            }

        }

        echo json_encode(array(
            "PSUMMARY" => $productSummary,
        ));
    }
    
    public function LoadCustomerName($data){
        $customerName = [];

        $customerType = $data['customerType'];
        $stmt = $this->conn->prepare("SELECT DISTINCT Name FROM tbl_clientlist WHERE Type = ? ORDER BY Name");
        $stmt->bind_param('s', $customerType);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $customerName[] = $row;
            }
        }
        $stmt->close();
        
        echo json_encode(array(
            "CUSTOMERNAMELIST" => $customerName,
        ));
    }

    public function LoadCustomerNameInfo($data){
        $customerInfo = [];

        $customerName = $data['customerName'];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_clientlist WHERE Name = ?");
        $stmt->bind_param('s', $customerName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $customerInfo[] = $row;
        }
        $stmt->close();
        
        echo json_encode(array(
            "CUSTOMERINFO" => $customerInfo,
        ));
    }

    public function AddToItems($data){

        $ConsignQuant = 0;
        $needQty = 0;
        $actualQty = 0;

        $isConsigment = $data['isConsignment'];
        $isynBranch = $data['isynBranch'];
        $consignBranch = $data['consignBranch'];
        $type = $data['type'];
        $categ = $data['categ'];
        $serialProduct = $data['serialProduct'];
        $SINo = $data['SINo'];
        $supplierSI = $data['supplierSI'];
        $serialNo = $data['serialNo'];
        $productName = $data['productName'];
        $supplierName = $data['supplierName'];
        $psSRP = str_replace(",", "", $data['psSRP']);
        $psQty = $data['psQty'];
        $psDealerPrice = str_replace(",", "", $data['psDealerPrice']);
        $psTotalPrice = str_replace(",", "", $data['psTotalPrice']);
        $customerType = $data['customerType'];
        $customerName = $data['customerName'];
        $staffLoan = $data['staffLoan'];
        $branchUsed = $data['branchUsed'];
        $mfiUsed = $data['mfiUsed'];
        $tin = $data['tin'];
        $address = $data['address'];
        $status = $data['status'];
        $srpMS = str_replace(",", "", $data['srpMS']);
        $qtyMS = $data['qtyMS'];
        $vatMS = str_replace(",", "", $data['vatMS']);
        $totalCostMS = str_replace(",", "", $data['totalCostMS']);
        $addDiscount = $data['addDiscount'];
        $discInterest = $data['discInterest'];
        $discAmtMS = str_replace(",", "", $data['discAmtMS']);
        $newSRPMS = str_replace(",", "", $data['newSRPMS']);
        $totalDiscountMS = str_replace(",", "", $data['totalDiscountMS']);
        
        $ClientType = "";
        $Area = $data['Area'];
        $Department = "";
        
        $mark = $data['mark'];
        $Tmark = $data['Tmark'];
        $warranty = $data['Warranty'];

        $consignList = [];

        date_default_timezone_set('Asia/Manila');
        $dateAdded = date("d/m/Y", strtotime("now"));

        $user = $_SESSION['USERNAME'];

        $stmt = $this->conn->prepare("SELECT SUM(QUANTITY) AS forQuantity FROM tbl_transaction WHERE serialno = ? AND supplier = ? AND product = ? AND CATEGORY = ? AND SupplierSI = ?");
        $stmt->bind_param('sssss', $serialNo, $supplierName,$productName,$categ,$supplierSI);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $InQuant = $row['forQuantity'];
        } else {
            $InQuant = 0;
        }
        $stmt->close();
        
        if ($isConsigment != "Yes"){
            $stmt = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE Serialno = ? AND Supplier = ? AND Product = ? AND Category = ? AND SIno = ? AND Branch = ?");
            $stmt->bind_param('ssssss', $serialNo, $supplierName,$productName,$categ,$supplierSI,$isynBranch);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()){
                    $ConsignQuant += $row['Quantity'];
                    $consignList[] = $row;
                }

                if ($qtyMS > ($psQty - $ConsignQuant)){
                    $needQty = $qtyMS - ($psQty - $ConsignQuant);
                    $actualQty = $psQty - $ConsignQuant;

                    $transactstatus = "WARNING";
                    $message = "Available stock (".($psQty - $ConsignQuant).") is lower than your inputted quantity. Get more stock from the branch?";

                    echo json_encode(array(
                        "STATUS" => $transactstatus,
                        "MESSAGE" => $message,
                        "ConsignList" => $consignList,
                        "ConsignQuant" => $ConsignQuant,
                        "needQty" => $needQty,
                        "actualQty" => $actualQty,
                    ));
                    return;
                    
                }
            }
            $stmt->close();
        }

        $QuantVariance = $psQty - $InQuant;

        if ($QuantVariance < 0){
            $transactstatus = "FAILED";
            $message = "The available stock quantity is lower than the quantity you entered.";
        } else {
            $vat = 0;
            $vatSales = 0;
            $amountDue = 0;
            
            if ($addDiscount == "Yes"){
                if ($type == "WITH VAT"){
                    $vat = round((floatval($newSRPMS) / 1.12) * 0.12, 2) * floatval($psQty);
                    $vatSales = floatval($totalDiscountMS) - $vat;
                    $amountDue = $totalDiscountMS;
                } else {
                    $vat = 0;
                    $vatSales = $totalDiscountMS;
                    $amountDue = $totalDiscountMS;
                }
            } else {
                if ($type == "WITH VAT"){
                    $vat = round((floatval($srpMS) / 1.12) * 0.12, 2) * floatval($psQty);
                    $vatSales = floatval($totalCostMS) - $vat;
                    $amountDue = $totalCostMS;
                } else {
                    $vat = 0;
                    $vatSales = $totalCostMS;
                    $amountDue = $totalCostMS;
                }
            }
            
            if ($customerType == "OTHER CLIENT"){
                $ClientType = "EXTERNAL";
                $Area = "-";
                $Department = "WALK IN CLIENT";
            } else if ($customerType == "EXTERNAL CLIENT"){
                $ClientType = "EXTERNAL";
                $Area = "-";
                $Department = "WALK IN CLIENT";
            } else if ($customerType == "STAFF"){
                $ClientType = "EXTERNAL";
                $Area = "-";
                if ($staffLoan == "Yes"){
                    $Department = "ISYN LOAN";
                } else {
                    $Department = "ASKI EMPLOYEE";
                }
            } else if ($customerType == "MFI BRANCHES"){
                if ($branchUsed == "Yes"){
                    $ClientType = "INTERNAL";
                    $Area = "";
                    $Department = "BRANCH USED";
                } else if ($mfiUsed == "Yes") {
                    $ClientType = "EXTERNAL";
                    $Area = "";
                    $Department = "MFI CLIENT";
                }
            } else if ($customerType == "DEPARTMENT"){
                $ClientType = "INTERNAL";
                $Area = "-";
                $Department = "AGC HO";
            } else if ($customerType == "BUSINESS UNIT"){
                $ClientType = "INTERNAL";
                $Area = "-";
                if ($customerName == "ISYNERGIES INC"){
                    $Department = "ISYNERGIES INC";
                } else {
                    $Department = "BUSINESS UNIT";
                }
            } else if ($customerType == "MFI HO"){
                $ClientType = "INTERNAL";
                $Area = "-";
                $Department = "MFI HO";
            } else {
                $ClientType = "-";
                $Area = "-";
                $Department = "-";
            }
            
            // $stmt1 = $this->conn->prepare("INSERT INTO tbl_transaction (SupplierSI, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, VAT, AmountDue, DateAdded, User, Soldto, TIN, Address, Status, Stock, Branch, itemConsign, myClient, Area, Department, DiscProduct, DiscInterest, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Warranty) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            // $stmt1->bind_param('ssssssssssssssssssssssssssssssssss', $supplierSI, $serialNo, $productName, $supplierName, $categ, $type, $qtyMS, $psDealerPrice, $vatMS, $srpMS, $totalCostMS, $mark, $Tmark, $vatSales, $vat, $amountDue, $dateAdded, $user, $customerName, $tin, $address, $status, $consignBranch, $isynBranch, $isConsigment, $ClientType, $Area, $Department, $addDiscount, $discInterest, $discAmtMS, $newSRPMS, $totalDiscountMS, $warranty);
            // $stmt1->execute();
            // $stmt1->close();

            $transactstatus = "SUCCESS";
            $message = "Successfully added.";            
        }
        
        echo json_encode(array(
            "STATUS" => $transactstatus,
            "MESSAGE" => $message,
            "ConsignQuant" => $ConsignQuant,
            "needQty" => $needQty,
            "actualQty" => $actualQty,
            // "vat" => $vat,
            // "discountMS" => $totalDiscountMS,
            // "vatsales" => $vatSales,
            // "amountdue" => $amountDue,
        ));
    }

    // ===================================================================================
    public function LoadTransaction(){
        $transactlist = [];
        $user = $_SESSION['USERNAME'];
        $stmt = $this->conn->prepare("SELECT * FROM tbl_transaction WHERE User = ?");
        $stmt->bind_param('s', $user);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $transactlist[] = $row;
            }
        }

        echo json_encode(array( 
            "TRANSACTIONLIST" => $transactlist,
        ));
    }

    public function DeleteFromItems($data){
        
        $SINo = $data["SINo"];
        $SerialNo = $data["SerialNo"];
        $Product = $data["Product"];
        $Product = html_entity_decode($Product, ENT_QUOTES, 'UTF-8');        
        
        $stmt = $this->conn->prepare("DELETE FROM tbl_transaction WHERE SupplierSI = ? AND TRIM(Serialno) = ? AND Product = ?");
        $stmt->bind_param('sss', $SINo,$SerialNo,$Product);
        $stmt->execute();
        $result1 = $stmt->affected_rows;
        $stmt->close();

        if ($result1 === 0) {
            $status = "error";
            $message = "Failed to deleted transaction [".$SINo." | ".$SerialNo." | ".$Product."].";
        } else {
            $status = "success";
            $message = "Deleted transaction [".$SINo." | ".$SerialNo." | ".$Product."].";
        }

        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "DATA" => $data,
        ));
    }

    public function SearchTransmittal($data){
        $transactlist = [];

        $dateFrom = $data['dateFrom'];
        $dateTo = $data['dateTo'];        

        $stmt = $this->conn->prepare("SELECT DISTINCT TransmittalNO, NameTO, DatePrepared FROM tbl_transmittal WHERE STR_TO_DATE(DatePrepared,'%m/%d/%Y') >= STR_TO_DATE(?,'%m/%d/%Y') AND STR_TO_DATE(DatePrepared,'%m/%d/%Y') <= STR_TO_DATE(?,'%m/%d/%Y') ORDER BY TransmittalNO, str_to_date(DatePrepared,'%m/%d/%Y') DESC");
        $stmt->bind_param('ss', $dateFrom, $dateTo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $transactlist[] = $row;
            }
        }

        echo json_encode(array( 
            "TRANSACTIONLIST" => $transactlist,
        ));
    }

    public function SubmitInvOut($data){
        try {
            $this->conn->autocommit(false);

            date_default_timezone_set('Asia/Manila');
            $AsOf = date("m/d/Y", strtotime("now"));

            $user = $_SESSION['USERNAME'];
            $SalesInvoice = "";
            $stmt = $this->conn->prepare("SELECT SIcount FROM TBL_SINUMBER WHERE user = ?");
            $stmt->bind_param('s', $user);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $SalesInvoice = $row['SIcount'];
            }
            $stmt->close();

            $NewSalesInvoice = $SalesInvoice + 1;
    
            $stmt1 = $this->conn->prepare("UPDATE tbl_transaction SET SI = ? WHERE User = ?");
            $stmt1->bind_param('ss', $SalesInvoice,$user);
            $stmt1->execute();
            $stmt1->close();
            
            $stmt2 = $this->conn->prepare("UPDATE tbl_sinumber SET SIcount = ? WHERE User = ?");
            $stmt2->bind_param('ss', $NewSalesInvoice,$user);
            $stmt2->execute();
            $stmt2->close();

            $currentQuantity = 0;
            $DealerPrice = 0;
            $ProdSRP = 0;

            $Totalprice = 0;
            $TSRP = 0;
            $MyMark = 0;
            $MyTMark = 0;

            $MyVat = 0;
            $MySalesVat = 0;

            $SerialNo = "";
            $SI = "";
            $ProductName = "";
            $MyQuantity = "";
            $TotalSales = 0;
            $MyCategory = "";
            $ItemConsign = "";
            $isynBranch = "";
            $askiBranch = "";
            $Type = "";

            $SalesNoVat = 0;
            $SalesWithVat = 0;
            $AmountDue = 0;
            $VAT = 0.12;

            $SIRef = "-";
            $DateAdded = "-";

            $stmt3 = $this->conn->prepare("SELECT * FROM tbl_transaction WHERE User = ?");
            $stmt3->bind_param('s', $user);
            $stmt3->execute();
            $result3 = $stmt3->get_result();
            if ($result3->num_rows > 0) {
                while ($row3 = $result3->fetch_assoc()) {
                    $SerialNo = $row3['Serialno'];
                    $SI = $row3['SupplierSI'];
                    $ProductName = $row3['Product'];
                    $MyQuantity = $row3['Quantity'];
                    $Type = $row3['Type'];

                    if ($row3['DiscProduct'] == "Yes") {
                        $TotalSales = $row3['DiscNewTotalSRP'];
                    } else {
                        $TotalSales = $row3['TotalSRP'];
                    }
                    
                    $MyCategory = $row3['Category'];
                    $ItemConsign = $row3['itemConsign']  ;
                    $isynBranch = $row3['Branch'];
                    $askiBranch = $row3['Stock'];

                    if ($ItemConsign == "Yes") {
                        $stmt4 = $this->conn->prepare("SELECT * FROM tbl_invlistconsign WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                        $stmt4->bind_param('ssssss', $SI, $SerialNo, $ProductName, $MyCategory, $askiBranch, $isynBranch);
                        $stmt4->execute();
                        $result4 = $stmt4->get_result();
                        if ($result4->num_rows > 0) {
                            $row4 = $result4->fetch_assoc();
                            $currentQuantity = $row4["Quantity"];
                            $DealerPrice = $row4["DealerPrice"];
                            $ProdSRP = $row4["SRP"];

                            $rcQuantity = $currentQuantity - $MyQuantity;

                            if ($rcQuantity == 0){
                                // $stmt = $this->conn->prepare("INSERT INTO tbl_invlistconsignhistory SELECT * FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                                // $stmt->bind_param('ssssss', $SerialNo,$SI,$ProductName,$MyCategory,$askiBranch,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();

                                // $stmt = $this->conn->prepare("DELETE FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                                // $stmt->bind_param('ssssss', $SerialNo,$SI,$ProductName,$MyCategory,$askiBranch,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();
                            } else {

                                $Totalprice = floatval($DealerPrice * $rcQuantity);
                                $TSRP = floatval($ProdSRP * $rcQuantity);
                                $MyMark = floatval($ProdSRP * $rcQuantity);
                                $MyTMark = round($MyMark - $Totalprice, 2);

                                if ($Type == "WITH VAT") {
                                    $MyVat = round(((floatval($DealerPrice) / 1.12) * 0.12), 2) * floatval($rcQuantity);
                                    $MySalesVat = floatval($Totalprice) - $MyVat;
                                } else {
                                    $MyVat = 0;
                                    $MySalesVat = $Totalprice;
                                }

                                // $stmt = $this->conn->prepare("UPDATE tbl_invlistconsign SET QUANTITY = ?, TOTALPRICE = ?, TOTALSRP =?, TOTALMARKUP = ?, VATSALES = ?, VAT = ?, AMOUNTDUE = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND STOCK = ? AND BRANCH = ?");
                                // $stmt->bind_param('sssssssssssss', $rcQuantity,$Totalprice,$TSRP,$MyTMark,$MySalesVat,$MyVat,$Totalprice,$SerialNo,$SI,$ProductName,$MyCategory,$askiBranch,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();
                            }                            
                        }
                        $stmt4->close();

                        // ==================
                        $stmt5 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                        $stmt5->bind_param('sssss', $SI, $SerialNo, $ProductName, $MyCategory, $isynBranch);
                        $stmt5->execute();
                        $result5 = $stmt5->get_result();
                        if ($result5->num_rows > 0) {
                            $row5 = $result5->fetch_assoc();
                            $currentQuantity = $row5["Quantity"];
                            $DealerPrice = $row5["DealerPrice"];
                            $ProdSRP = $row5["SRP"];

                            $rcQuantity = $currentQuantity - $MyQuantity;

                            if ($rcQuantity == 0){
                                // $stmt = $this->conn->prepare("INSERT INTO tbl_prodhistory SELECT * FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                // $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();

                                // $stmt = $this->conn->prepare("DELETE FROM tbl_invlist WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                // $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();
                            } else {
                                $Totalprice = floatval($DealerPrice * $rcQuantity);
                                $TSRP = floatval($ProdSRP * $rcQuantity);
                                $MyMark = floatval($ProdSRP * $rcQuantity);
                                $MyTMark = round($MyMark - $Totalprice, 2);

                                if ($Type == "WITH VAT") {
                                    $MyVat = round(((floatval($DealerPrice) / 1.12) * 0.12), 2) * floatval($rcQuantity);
                                    $MySalesVat = floatval($Totalprice) - $MyVat;
                                } else {
                                    $MyVat = 0;
                                    $MySalesVat = $Totalprice;
                                }

                                // $stmt = $this->conn->prepare("UPDATE tbl_invlist SET QUANTITY = ?, TOTALPRICE = ?, TOTALSRP =?, TOTALMARKUP = ?, VATSALES = ?, VAT = ?, AMOUNTDUE = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                // $stmt->bind_param('ssssssssssss', $rcQuantity,$Totalprice,$TSRP,$MyTMark,$MySalesVat,$MyVat,$Totalprice,$SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();
                            }                            
                        }
                        $stmt5->close();
                    } else {
                        $stmt6 = $this->conn->prepare("SELECT * FROM tbl_invlist WHERE SINO = ? AND SERIALNO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                        $stmt6->bind_param('sssss', $SI, $SerialNo, $ProductName, $MyCategory, $isynBranch);
                        $stmt6->execute();
                        $result6 = $stmt6->get_result();
                        if ($result6->num_rows > 0) {
                            $row6 = $result6->fetch_assoc();
                            $currentQuantity = $row6["Quantity"];
                            $DealerPrice = $row6["DealerPrice"];
                            $ProdSRP = $row6["SRP"];

                            $rcQuantity = $currentQuantity - $MyQuantity;

                            if ($rcQuantity == 0){
                                // $stmt = $this->conn->prepare("INSERT INTO tbl_prodhistory SELECT * FROM tbl_invlistconsign WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                // $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();

                                // $stmt = $this->conn->prepare("DELETE FROM tbl_invlist WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                // $stmt->bind_param('sssss', $SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();
                            } else {
                                $Totalprice = floatval($DealerPrice * $rcQuantity);
                                $TSRP = floatval($ProdSRP * $rcQuantity);
                                $MyMark = floatval($ProdSRP * $rcQuantity);
                                $MyTMark = round($MyMark - $Totalprice, 2);

                                if ($Type == "WITH VAT") {
                                    $MyVat = round(((floatval($DealerPrice) / 1.12) * 0.12), 2) * floatval($rcQuantity);
                                    $MySalesVat = floatval($Totalprice) - $MyVat;
                                } else {
                                    $MyVat = 0;
                                    $MySalesVat = $Totalprice;
                                }

                                // $stmt = $this->conn->prepare("UPDATE tbl_invlist SET QUANTITY = ?, TOTALPRICE = ?, TOTALSRP =?, TOTALMARKUP = ?, VATSALES = ?, VAT = ?, AMOUNTDUE = ? WHERE SERIALNO = ? AND SINO = ? AND PRODUCT = ? AND CATEGORY = ? AND BRANCH = ?");
                                // $stmt->bind_param('ssssssssssss', $rcQuantity,$Totalprice,$TSRP,$MyTMark,$MySalesVat,$MyVat,$Totalprice,$SerialNo,$SI,$ProductName,$MyCategory,$isynBranch);
                                // $stmt->execute();
                                // $stmt->close();
                            }                            
                        }
                        $stmt6->close();
                    }

                    $SalesNoVat += $TotalSales / 1.12;
                    $SalesWithVat = round($SalesNoVat * $VAT, 2);
                    $AmountDue = round($SalesNoVat + $SalesWithVat, 2);
                }

                $stmt7 = $this->conn->prepare("SELECT DateAdded, SI, Soldto, SUM(AmountDue) as forTotal, SUM(VatSAles) as forSales, SUM(VAT) as forVAT, TIN, Address, Status, Branch FROM tbl_transaction GROUP BY SI, User, Branch;");
                $stmt7->execute();
                $result7 = $stmt7->get_result();
                if ($result7->num_rows > 0) {
                    while ($row7 = $result7->fetch_assoc()) {
                        $SIRef = $row7['SI'];
                        $DateAdded = $row7['DateAdded'];
                        $Total = $row7['forTotal'];
                        $SoldTo = $row7['Soldto'];
                        $Tin = $row7['TIN'];
                        $Address = $row7['Address'];
                        $isynBranch = $row7['Branch'];

                        $sSales = round(floatval($Total) / 1.12, 2);
                        $sVAT = round((floatval($Total) / 1.12) * 0.12, 2);

                        // $stmt = $this->conn->prepare("INSERT INTO tbl_salesjournal (DateSold,Reference,Customer,GrossSales,VAT,NetSales,TIN,Address,Stock) VALUES (?,?,?,?,?,?,?,?,?)");
                        // $stmt->bind_param('sssssssss', $DateAdded,$SIRef,$SoldTo,$Total,$sVAT,$sSales,$Tin,$Address,$isynBranch);
                        // $stmt->execute();
                        // $stmt->close();
                    }

                    // $stmt = $this->conn->prepare("UPDATE tbl_transaction SET VatSales = TotalPrice, VAT = 0, AmountDue = TotalPrice WHERE Type = 'NON-VAT' AND User = ?");
                    // $stmt->bind_param('s', $user);
                    // $stmt->execute();
                    // $stmt->close();

                    // $stmt = $this->conn->prepare("UPDATE tbl_transaction SET VatSales = ((DealerPrice * Quantity)-(round(((DealerPrice/1.12)*0.12),2) * Quantity)),VAT = (round(((DealerPrice/1.12)*0.12),2) * Quantity), AmountDue=TotalPrice WHERE Type = 'WITH VAT' AND User = ?");
                    // $stmt->bind_param('s', $user);
                    // $stmt->execute();
                    // $stmt->close();

                    // $stmt = $this->conn->prepare("INSERT INTO tbl_inventoryout (SI, SupplierSI, Batchno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, VAT, AmountDue, DateAdded, User, Soldto, TIN, Address, Status, Stock, Branch, itemConsign, myClient, Area, Department, DiscProduct, DiscInterest, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Warranty, imgname) SELECT SI, SupplierSI, Batchno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, VAT, AmountDue, DateAdded, User, Soldto, TIN, Address, Status, Stock, Branch, itemConsign, myClient, Area, Department, DiscProduct, DiscInterest, DiscAmount, DiscNewSRP, DiscNewTotalSRP, Warranty, imgname FROM tbl_transaction WHERE User = ?");
                    // $stmt->bind_param('s', $user);
                    // $stmt->execute();
                    // $stmt->close();

                    // $stmt = $this->conn->prepare("DELETE FROM tbl_transaction WHERE User = ?");
                    // $stmt->bind_param('s', $user);
                    // $stmt->execute();
                    // $stmt->close();                    
                }
                $stmt7->close();
            }
            $stmt3->close();
            
            $status = "success";
            $message = "Product details were saved successfully.";

            $tableData = json_decode($data['DATA']);
            unset($_SESSION['tableData']);
            unset($_SESSION['SalesNoVAT']);
            unset($_SESSION['SalesWithVAT']);
            unset($_SESSION['SIRef']);
            unset($_SESSION['DateAdded']);
            $_SESSION['tableData'] = $tableData;
            $_SESSION['SalesNoVAT'] = $SalesNoVat;
            $_SESSION['SalesWithVAT'] = $SalesWithVat;
            $_SESSION['SIRef'] = $SIRef;
            $_SESSION['DateAdded'] = $DateAdded;

            $this->conn->commit();
            
            echo json_encode(array(
                "STATUS" => $status,
                "MESSAGE" => $message,
                // "NOVAT" => $SalesNoVat,
                // "YESVAT" => $SalesWithVat,
                // "TTLSLS" => $TotalSales,
            ));
            $this->conn->autocommit(true);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array(
                "STATUS" => "ERROR",
                "MESSAGE" => $e->getMessage()
            ));
        }
    }

    public function PrintSalesInvoice ($data) {
        $products = [];
        date_default_timezone_set('Asia/Manila');
        $AsOf = date("m/d/Y", strtotime("now"));
        $ProdPend = "NO";

        $stmt = $this->conn->prepare("SELECT Quantity, DateAdded, SIno, Supplier, SUM(TotalPrice) AS forTotal, SUM(Vat) AS forVAT, SUM(VatSales) AS forSVat, Stock, Branch, User FROM tbl_inventoryin WHERE ProdPend = 'YES' AND STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y') GROUP BY SIno, Supplier, Stock, Branch, DateAdded, User");
        $stmt->bind_param('s', $AsOf);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;

                $purchaseDate = $row['DateAdded'];
                $SINo = $row['SIno'];
                $supplier = $row['Supplier'];
                $total = $row['forTotal'];
                $vat = $row['forVAT'];
                $svat = $row['forSVat'];
                $stock = $row['Stock'];
                $branch = $row['Branch'];

                $stmt1 = $this->conn->prepare("INSERT INTO tbl_purchasejournal (DatePurchase, Reference, Supplier, GrossPurchase, InputVAT, NetPurchase, Stock, Branch) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt1->bind_param('ssssssss', $purchaseDate, $SINo, $supplier, $total, $vat, $svat, $stock, $branch);
                $stmt1->execute();

                $stmt2 = $this->conn->prepare("SELECT tinNumber, fullAddress FROM tbl_supplier_info WHERE supplierName = ?");
                $stmt2->bind_param('s', $supplier);
                $stmt2->execute();
                $result2 = $stmt2->get_result();
                if ($result2->num_rows > 0) {
                    $row2 = $result2->fetch_assoc();
                    $tin = $row2['tinNumber'];
                    $address = $row2['fullAddress'];
                    
                    $stmt3 = $this->conn->prepare("UPDATE tbl_purchasejournal SET TIN = ?,  Address = ? WHERE Supplier = ? AND TIN = '-' AND Address = '-'");
                    $stmt3->bind_param('sss', $tin, $address, $supplier);
                    $stmt3->execute();
                }
            }
        }

        $stmt3 = $this->conn->prepare("INSERT INTO tbl_invlist (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ?, Stock, Branch, Warranty, imgname FROM tbl_inventoryin WHERE ProdPend = 'YES' AND STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y')");
        $stmt3->bind_param('ss', $ProdPend, $AsOf);
        $stmt3->execute();

        $stmt3 = $this->conn->prepare("UPDATE tbl_inventoryin SET ProdPend = 'NO' WHERE ProdPend = 'YES'");
        $stmt3->execute();

        $tableData = json_decode($data['DATA']);
        unset($_SESSION['tableData']);
        $_SESSION['tableData'] = $tableData;

        echo json_encode(array(
            "PRODS" => $products,
            "DATAINVSESS" => $tableData,
        ));
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