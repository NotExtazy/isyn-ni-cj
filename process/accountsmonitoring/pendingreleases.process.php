<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
include_once($docRoot . "/iSynApp-main/database/connection.php");

class Process extends Database
{
    public function LoadPage() {
        $Types = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_banksetup WHERE Type <> '-' ORDER BY Type");
        $BranchInfo = $this->SelectQuery("SELECT Value FROM tbl_configuration WHERE ConfigName = 'BRANCHNAME' AND ConfigOwner = 'BRANCH SETUP' LIMIT 1");
        $BranchName = !empty($BranchInfo) ? $BranchInfo[0]["Value"] : "-";

        echo json_encode([
            "TYPES"      => $Types,
            "BRANCHNAME" => $BranchName,
        ]);
    }

    public function LoadList() {
        // Show all loans from tbl_loans with their release status
        // Join with tbl_clientinfo to get client name
        // ReleaseStatus is determined by whether BOTH CV and Check are printed
        $list = $this->SelectQuery("
            SELECT l.ClientNo, l.LoanID, 
                   CONCAT(c.LastName, ', ', c.FirstName, ' ', COALESCE(c.MiddleName, '')) as FULLNAME,
                   l.Program as PROGRAM, 
                   l.Product as PRODUCT, 
                   l.LoanAmount as LOANAMOUNT,
                   COALESCE(l.CVPrinted, 'NO') as CVPrinted,
                   COALESCE(l.CheckPrinted, 'NO') as CheckPrinted,
                   CASE 
                       WHEN COALESCE(l.CVPrinted, 'NO') = 'YES' 
                        AND COALESCE(l.CheckPrinted, 'NO') = 'YES' 
                       THEN 'RELEASED' 
                       ELSE 'PENDING' 
                   END as ReleaseStatus
            FROM tbl_loans l
            LEFT JOIN tbl_clientinfo c ON c.ClientNo = l.ClientNo
            ORDER BY c.LastName, c.FirstName ASC
        ");

        echo json_encode(["LIST" => $list]);
    }

    public function LoadClientDetails($data) {
        $clientno = $data["clientno"];
        $loanid   = $data["loanid"];

        // Get data from tbl_loans and join with tbl_clientinfo for client name
        $stmt = $this->conn->prepare("
            SELECT l.*, 
                   CONCAT(c.LastName, ', ', c.FirstName, ' ', COALESCE(c.MiddleName, '')) as FULLNAME,
                   COALESCE(l.CVPrinted, 'NO') as CVPrinted,
                   COALESCE(l.CheckPrinted, 'NO') as CheckPrinted
            FROM tbl_loans l
            LEFT JOIN tbl_clientinfo c ON c.ClientNo = l.ClientNo
            WHERE l.ClientNo = ? AND l.LoanID = ? 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        $row = $result->fetch_assoc();
        echo json_encode(["DETAILS" => $row]);
    }

    public function GetBanks($data) {
        $type = $data["type"];
        $Banks = $this->SelectQuery("SELECT DISTINCT Bank FROM tbl_banksetup WHERE Type = '$type' AND Bank <> '-' ORDER BY Bank");
        echo json_encode(["BANKS" => $Banks]);
    }

    public function GetFundTags($data) {
        $bank = $data["bank"];
        $clientno = $data["clientno"] ?? '';
        $loanid = $data["loanid"] ?? '';
        
        $info = $this->SelectQuery("SELECT Fund, LastCV, NextCheck FROM tbl_banksetup WHERE Bank = '$bank' LIMIT 1");
        
        // If clientno and loanid provided, temporarily store the CV and Check numbers in session
        // so they can be used for printing before saving
        if (!empty($clientno) && !empty($loanid) && !empty($info)) {
            $_SESSION['TEMP_CVNO'] = $info[0]["LastCV"];
            $_SESSION['TEMP_CHECKNO'] = $info[0]["NextCheck"];
            $_SESSION['TEMP_BANK'] = $bank;
            $_SESSION['TEMP_FUND'] = $info[0]["Fund"];
        }
        
        echo json_encode(["BANKINFO" => $info]);
    }

    public function GetVoucherEntries($data) {
        $clientno = $data["clientno"];
        $loanid   = $data["loanid"];

        // Get loan details from tbl_loans
        $stmt = $this->conn->prepare("
            SELECT l.*, 
                   CONCAT(c.LastName, ', ', c.FirstName, ' ', COALESCE(c.MiddleName, '')) as FULLNAME
            FROM tbl_loans l
            LEFT JOIN tbl_clientinfo c ON c.ClientNo = l.ClientNo
            WHERE l.ClientNo = ? AND l.LoanID = ? 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $cv = $result->fetch_assoc();

        if (!$cv) {
            echo json_encode(["ENTRIES" => [], "PARTICULARS" => ""]);
            return;
        }

        $loanamt  = floatval($cv["LoanAmount"]);
        $netamt   = floatval($cv["NetAmount"]);
        $interest = floatval($cv["Interest"]);
        $cbu      = floatval($cv["CBU"]);
        $ef       = floatval($cv["EF"]);
        $mba      = floatval($cv["MBA"]);
        
        // Get bank from funding details if available, otherwise use CASH
        $bank = "CASH";

        // If net amount is 0, use loan amount as net (cash release)
        if ($netamt <= 0) $netamt = $loanamt;

        $entries = [
            ["Account" => "LOANS RECEIVABLE", "AcctNo" => "11920", "SL" => $cv["FULLNAME"], "Debit" => $loanamt, "Credit" => 0],
            ["Account" => "CASH IN BANK",      "AcctNo" => "11130", "SL" => $bank,           "Debit" => 0,        "Credit" => $netamt],
        ];
        if ($interest > 0) $entries[] = ["Account" => "INTEREST INCOME", "AcctNo" => "21370", "SL" => "-", "Debit" => 0, "Credit" => $interest];
        if ($cbu > 0)      $entries[] = ["Account" => "CBU PAYABLE",     "AcctNo" => "31100", "SL" => "-", "Debit" => 0, "Credit" => $cbu];
        if ($ef > 0)       $entries[] = ["Account" => "ENTRANCE FEE",    "AcctNo" => "43400", "SL" => "-", "Debit" => 0, "Credit" => $ef];
        if ($mba > 0)      $entries[] = ["Account" => "MBA PAYABLE",     "AcctNo" => "21210", "SL" => "-", "Debit" => 0, "Credit" => $mba];

        $particulars = "LOAN RELEASE - " . $cv["FULLNAME"];

        echo json_encode(["ENTRIES" => $entries, "PARTICULARS" => $particulars]);
    }

    public function SaveFundingDetails($data) {
        $clientno = $data["clientno"];
        $loanid   = $data["loanid"];
        $type     = $data["type"];
        $bank     = $data["bank"];
        $fund     = $data["fund"];

        // Update tbl_aging with funding details
        $stmt = $this->conn->prepare("UPDATE tbl_aging SET RELEASETYPE = ?, BANK = ?, FUND = ? WHERE ClientNo = ? AND LoanID = ?");
        $stmt->bind_param("sssss", $type, $bank, $fund, $clientno, $loanid);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) {
            echo json_encode(["STATUS" => "SUCCESS", "MESSAGE" => "Funding details saved successfully."]);
        } else {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Failed to save funding details."]);
        }
    }

    public function SaveRelease($data) {
        $clientno = $data["clientno"];
        $loanid   = $data["loanid"];
        $type     = $data["type"];
        $bank     = $data["bank"];
        $fund     = $data["fund"];
        $user     = $_SESSION['USERNAME'];

        // First, update tbl_loans with funding details
        $updateLoansStmt = $this->conn->prepare("UPDATE tbl_loans SET Bank = ?, Fund = ?, ReleaseType = ? WHERE ClientNo = ? AND LoanID = ?");
        $updateLoansStmt->bind_param("sssss", $bank, $fund, $type, $clientno, $loanid);
        $updateLoansStmt->execute();
        $updateLoansStmt->close();

        // Also update tbl_aging with funding details (if it exists)
        $updateAgingStmt = $this->conn->prepare("UPDATE tbl_aging SET BANK = ?, FUND = ?, RELEASETYPE = ? WHERE ClientNo = ? AND LoanID = ?");
        $updateAgingStmt->bind_param("sssss", $bank, $fund, $type, $clientno, $loanid);
        $updateAgingStmt->execute();
        $updateAgingStmt->close();

        // Check if entries already exist in tbl_books
        $checkStmt = $this->conn->prepare("SELECT COUNT(*) as cnt FROM tbl_books WHERE BookType = 'CDB' AND ClientNo = ? AND LoanID = ?");
        $checkStmt->bind_param("ss", $clientno, $loanid);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkRow = $checkResult->fetch_assoc();
        $existingCount = $checkRow['cnt'];
        $checkStmt->close();

        // Get CV and Check numbers - use the ones from session if available (already displayed to user)
        // Otherwise get fresh ones from database
        if (!empty($_SESSION['TEMP_CVNO']) && !empty($_SESSION['TEMP_CHECKNO']) && 
            !empty($_SESSION['TEMP_BANK']) && $_SESSION['TEMP_BANK'] === $bank) {
            // Use the numbers that were already shown to the user
            $cvno = $_SESSION['TEMP_CVNO'];
            $checkno = $_SESSION['TEMP_CHECKNO'];
        } else {
            // Get fresh numbers from database
            $bankInfo = $this->SelectQuery("SELECT LastCV, NextCheck FROM tbl_banksetup WHERE Bank = '$bank' LIMIT 1");
            if (empty($bankInfo)) {
                echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Bank not found."]);
                return;
            }
            $cvno    = $bankInfo[0]["LastCV"];
            $checkno = $bankInfo[0]["NextCheck"];
        }

        // Get loan details from tbl_loans
        $stmt = $this->conn->prepare("
            SELECT l.*, 
                   CONCAT(c.LastName, ', ', c.FirstName, ' ', COALESCE(c.MiddleName, '')) as FULLNAME
            FROM tbl_loans l
            LEFT JOIN tbl_clientinfo c ON c.ClientNo = l.ClientNo
            WHERE l.ClientNo = ? AND l.LoanID = ? 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $cv = $result->fetch_assoc();

        if (!$cv) {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Loan record not found."]);
            return;
        }

        $BranchInfo = $this->SelectQuery("SELECT Value FROM tbl_configuration WHERE ConfigName = 'BRANCHNAME' AND ConfigOwner = 'BRANCH SETUP' LIMIT 1");
        $branch = !empty($BranchInfo) ? $BranchInfo[0]["Value"] : "-";

        $date        = date("Y-m-d");
        $bookpage    = $this->GetBookPage("CDB", $fund, $date);
        $loanamt     = floatval($cv["LoanAmount"]);
        $netamt      = floatval($cv["NetAmount"]);
        $interest    = floatval($cv["Interest"]);
        $cbu         = floatval($cv["CBU"]);
        $ef          = floatval($cv["EF"]);
        $mba         = floatval($cv["MBA"]);
        $fullname    = $cv["FULLNAME"];
        $program     = $cv["Program"] ?? "-";
        $product     = $cv["Product"] ?? "-";
        $tag         = ($cv["Tag"] && $cv["Tag"] != '-') ? $cv["Tag"] : "-";
        $particulars = "LOAN RELEASE - $fullname";

        if ($netamt <= 0) $netamt = $loanamt;

        $entries = [
            ["AcctTitle" => "LOANS RECEIVABLE", "AcctNo" => "11920", "DrOther" => $loanamt, "CrOther" => 0,      "CrDr" => "DEBIT",
             "SLYesNo" => "YES", "SLNo" => $clientno, "SLName" => $fullname, "SLDrCr" => $loanamt, "SLDrCr1" => $loanamt * -1, "GLNo" => "11920"],
            ["AcctTitle" => "CASH IN BANK",      "AcctNo" => "11130", "DrOther" => 0,        "CrOther" => $netamt, "CrDr" => "CREDIT",
             "SLYesNo" => "YES", "SLNo" => $bank, "SLName" => $bank, "SLDrCr" => $netamt * -1, "SLDrCr1" => $netamt, "GLNo" => "11130"],
        ];
        if ($interest > 0) $entries[] = ["AcctTitle" => "INTEREST INCOME", "AcctNo" => "21370", "DrOther" => 0, "CrOther" => $interest, "CrDr" => "CREDIT",
            "SLYesNo" => "NO", "SLNo" => "-", "SLName" => "", "SLDrCr" => 0, "SLDrCr1" => 0, "GLNo" => "21370"];
        if ($cbu > 0)      $entries[] = ["AcctTitle" => "CBU PAYABLE",     "AcctNo" => "31100", "DrOther" => 0, "CrOther" => $cbu,      "CrDr" => "CREDIT",
            "SLYesNo" => "NO", "SLNo" => "-", "SLName" => "", "SLDrCr" => 0, "SLDrCr1" => 0, "GLNo" => "31100"];
        if ($ef > 0)       $entries[] = ["AcctTitle" => "ENTRANCE FEE",    "AcctNo" => "43400", "DrOther" => 0, "CrOther" => $ef,       "CrDr" => "CREDIT",
            "SLYesNo" => "NO", "SLNo" => "-", "SLName" => "", "SLDrCr" => 0, "SLDrCr1" => 0, "GLNo" => "43400"];
        if ($mba > 0)      $entries[] = ["AcctTitle" => "MBA PAYABLE",     "AcctNo" => "21210", "DrOther" => 0, "CrOther" => $mba,      "CrDr" => "CREDIT",
            "SLYesNo" => "NO", "SLNo" => "-", "SLName" => "", "SLDrCr" => 0, "SLDrCr1" => 0, "GLNo" => "21210"];

        if ($existingCount > 0) {
            // UPDATE existing entries - delete old entries first
            $deleteStmt = $this->conn->prepare("DELETE FROM tbl_books WHERE BookType = 'CDB' AND ClientNo = ? AND LoanID = ?");
            $deleteStmt->bind_param("ss", $clientno, $loanid);
            $deleteStmt->execute();
            $deleteStmt->close();
        }

        // Insert new/updated entries to tbl_books
        $inserted = 0;
        $postingstat = "NO";
        $jvno = "-";
        $orno = "-";
        $booktype = "CDB";
        
        foreach ($entries as $e) {
            $stmt = $this->conn->prepare("INSERT INTO tbl_books
                (ID, CDate, Branch, Fund, JVNo, Explanation, AcctTitle, AcctNo, SLDrCr, SLDrCr1, SLYesNo, SLNo, SLName, DrOther, CrOther, PreparedBy, CrDr, ClientNo, LoanID, postingstat, Program, Product, Tag, BookType, Payee, CheckNo, CVNo, ORNo, Bank, BookPage, GLNo)
                VALUES (NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssddsssddssssssssssssssss",
                $date, $branch, $fund, $jvno, $particulars,
                $e["AcctTitle"], $e["AcctNo"],
                $e["SLDrCr"], $e["SLDrCr1"],
                $e["SLYesNo"], $e["SLNo"], $e["SLName"],
                $e["DrOther"], $e["CrOther"],
                $user, $e["CrDr"],
                $clientno, $loanid,
                $postingstat,
                $program, $product, $tag,
                $booktype, $fullname,
                $checkno, $cvno,
                $orno, $bank, $bookpage, $e["GLNo"]
            );
            $stmt->execute();
            $inserted += $stmt->affected_rows;
            $stmt->close();
        }

        if ($inserted > 0) {
            // Only increment CV and Check numbers if this is a NEW release (not an update)
            if ($existingCount == 0) {
                $nextcv    = floatval($cvno) + 1;
                $nextcheck = floatval($checkno) + 1;
                $stmt = $this->conn->prepare("UPDATE tbl_banksetup SET LastCV = ?, NextCheck = ? WHERE Bank = ?");
                $stmt->bind_param("sss", $nextcv, $nextcheck, $bank);
                $stmt->execute();
                $stmt->close();
            }

            // Clear temporary session variables after saving
            unset($_SESSION['TEMP_CVNO']);
            unset($_SESSION['TEMP_CHECKNO']);
            unset($_SESSION['TEMP_BANK']);
            unset($_SESSION['TEMP_FUND']);

            $action = $existingCount > 0 ? "updated" : "saved";
            echo json_encode(["STATUS" => "SUCCESS", "MESSAGE" => "Release $action successfully. CV No: $cvno, Check No: $checkno", "CVNO" => $cvno, "CHECKNO" => $checkno]);
        } else {
            echo json_encode(["STATUS" => "ERROR", "MESSAGE" => "Failed to save release entries."]);
        }
    }

    private function GetBookPage($type, $fund, $date) {
        $prefix = $type . "-";
        $stmt = $this->conn->prepare("SELECT BOOKTYPE, FUND, CDATE, BOOKPAGE FROM TBL_BOOKS USE INDEX(forBookPage) WHERE BOOKTYPE = ? AND FUND = ? AND BOOKPAGE <> '-' ORDER BY CAST(REPLACE(BOOKPAGE, ?, '') AS UNSIGNED) DESC LIMIT 1");
        $stmt->bind_param("sss", $type, $fund, $prefix);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastbookpage = trim(str_replace($prefix, "", $row["BOOKPAGE"]));
            $ddate = date("Y-m-d", strtotime($row["CDATE"]));
            return ($ddate == $date) ? $type . "-" . $lastbookpage : $type . "-" . (floatval($lastbookpage) + 1);
        }
        return $type . "-1";
    }
}