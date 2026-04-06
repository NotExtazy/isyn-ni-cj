<?php
$dbPath = $_SERVER['DOCUMENT_ROOT'] . '/iSynApp-main/database/connection.php';
include_once($dbPath);

class Process extends Database
{
    public function LoadList($data) {
        try {
            $order = ($data['order'] ?? 'name') === 'clientNo' ? 'ClientNo' : 'FULLNAME';
            
            // Check if table exists
            if (!$this->tableExists('tbl_aging')) {
                echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Table tbl_aging does not exist', 'LIST' => []]);
                return;
            }
            
            $rows = $this->SelectQuery("SELECT * FROM tbl_aging ORDER BY $order ASC");
            
            if ($rows === false || $rows === null) {
                echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Query failed', 'LIST' => []]);
                return;
            }
            
            echo json_encode(['STATUS' => 'SUCCESS', 'LIST' => $rows]);
        } catch (Exception $e) {
            echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => $e->getMessage(), 'LIST' => []]);
        }
    }

    public function LoadSLList($data) {
        $clientno = $data['clientno'] ?? '';
        $stmt = $this->conn->prepare("
            SELECT LoanID, Product, DateRelease, LoanType, Program, ClientNo, FullName,
                   DateMature, LoanAmount, Interest, CBU, PNNo, PO
            FROM tbl_loans
            WHERE ClientNo = ?
            ORDER BY DateRelease DESC
        ");
        $stmt->bind_param("s", $clientno);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        echo json_encode(['LOANS' => $rows]);
    }

    public function LoadSLPreview($data) {
        $clientno = $data['clientno'] ?? '';
        $loanid   = $data['loanid']   ?? '';
        $stmt = $this->conn->prepare("
            SELECT CDate, ORNo, CVNo, JVNo, BookType, AcctNo, AcctTitle,
                   DrOther, CrOther, SLDrCr, Explanation
            FROM tbl_books
            WHERE ClientNo = ? AND LoanID = ?
            ORDER BY CDate ASC, ID ASC
        ");
        $stmt->bind_param("ss", $clientno, $loanid);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        echo json_encode(['SL' => $rows]);
    }

    public function UpdateAccount($data) {
        $clientno      = $data['clientno']       ?? '';
        $loanid        = $data['loanid']         ?? '';
        $fullname      = $data['fullname']        ?? '';
        $program       = $data['program']         ?? '';
        $product       = $data['product']         ?? '';
        $mode          = $data['mode']            ?? '';
        $term          = $data['term']            ?? '';
        $interestrate  = $data['interestrate']    ?? '';
        $intcomp       = $data['intcomputation']  ?? '';
        $daterelease   = $data['daterelease']     ?? '';
        $datemature    = $data['datemature']      ?? '';
        $loanamount    = $data['loanamount']      ?? 0;
        $interest      = $data['interest']        ?? 0;
        $cbu           = $data['cbu']             ?? 0;
        $ef            = $data['ef']              ?? 0;
        $mba           = $data['mba']             ?? 0;
        $netamount     = $data['netamount']       ?? 0;
        $po            = $data['po']              ?? '';
        $tag           = $data['tag']             ?? '';
        $pnno          = $data['pnno']            ?? '';
        $fund          = $data['fund']            ?? '';

        $stmt = $this->conn->prepare("
            UPDATE tbl_aging SET
                FULLNAME = ?, PROGRAM = ?, PRODUCT = ?, MODE = ?, TERM = ?,
                INTERESTRATE = ?, INTCOMPUTATION = ?, DATERELEASE = ?, DATEMATURE = ?,
                LOANAMOUNT = ?, INTEREST = ?, CBUFTL = ?, EF = ?, MBA = ?, NETAMOUNT = ?,
                PO = ?, TAG = ?, PNNO = ?, FUND = ?
            WHERE ClientNo = ? AND LoanID = ?
        ");
        $stmt->bind_param(
            "sssssssssddddddssssss",
            $fullname, $program, $product, $mode, $term,
            $interestrate, $intcomp, $daterelease, $datemature,
            $loanamount, $interest, $cbu, $ef, $mba, $netamount,
            $po, $tag, $pnno, $fund,
            $clientno, $loanid
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected >= 0) {
            echo json_encode(['STATUS' => 'SUCCESS', 'MESSAGE' => 'Account updated successfully.']);
        } else {
            echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Failed to update account.']);
        }
    }

    public function UpdateAging() {
        try {
            // Check if stored procedure exists
            $spCheck = $this->conn->query("SHOW PROCEDURE STATUS WHERE Name = 'sp_UpdateAging'");
            
            if ($spCheck && $spCheck->num_rows > 0) {
                // Call stored procedure if it exists
                $result = $this->conn->query("CALL sp_UpdateAging()");
                if ($result !== false) {
                    echo json_encode(['STATUS' => 'SUCCESS', 'MESSAGE' => 'Aging updated successfully via stored procedure.']);
                } else {
                    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Stored procedure failed: ' . $this->conn->error]);
                }
            } else {
                // Fallback: Sync from tbl_loans to tbl_aging
                // This is a basic sync - you may need to customize based on your business logic
                
                // First, check if tbl_loans exists
                if (!$this->tableExists('tbl_loans')) {
                    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Source table tbl_loans does not exist']);
                    return;
                }
                
                // Sync data from tbl_loans to tbl_aging
                // This assumes tbl_aging should mirror active loans from tbl_loans
                $syncQuery = "
                    INSERT INTO tbl_aging (
                        ClientNo, LoanID, FULLNAME, PROGRAM, PRODUCT, MODE, TERM,
                        INTERESTRATE, INTCOMPUTATION, DATERELEASE, DATEMATURE,
                        LOANAMOUNT, INTEREST, CBUFTL, EF, MBA, NETAMOUNT,
                        PO, TAG, PNNO, FUND, LOANAVAILMENT, ADDITIONAL,
                        Balance, AmountDue, InterestDue, CBUDue, EFDue, MBAPaid,
                        AmountPaid, InterestPaid, CBUPaid, EFPaid, MBAPaid, PenaltyPaid
                    )
                    SELECT 
                        ClientNo, LoanID, FullName, Program, Product, Mode, Term,
                        InterestRate, IntComputation, DateRelease, DateMature,
                        LoanAmount, Interest, CBU, EF, MBA, NetAmount,
                        PO, Tag, PNNo, Fund, LoanType, '',
                        LoanAmount, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0
                    FROM tbl_loans
                    WHERE LoanStatus != 'PAID' OR LoanStatus IS NULL
                    ON DUPLICATE KEY UPDATE
                        FULLNAME = VALUES(FULLNAME),
                        LOANAMOUNT = VALUES(LOANAMOUNT),
                        Balance = VALUES(Balance)
                ";
                
                $result = $this->conn->query($syncQuery);
                
                if ($result !== false) {
                    $affected = $this->conn->affected_rows;
                    echo json_encode(['STATUS' => 'SUCCESS', 'MESSAGE' => "Aging data synced. $affected records updated."]);
                } else {
                    echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Sync failed: ' . $this->conn->error]);
                }
            }
        } catch (Exception $e) {
            echo json_encode(['STATUS' => 'ERROR', 'MESSAGE' => 'Update failed: ' . $e->getMessage()]);
        }
    }
}
