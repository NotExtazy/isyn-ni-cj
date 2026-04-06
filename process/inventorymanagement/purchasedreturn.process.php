<?php
include_once("../../database/connection.php");

class Process extends Database
{
    // Initialize dropdowns
    public function Initialize(){
        $response = [];

        // 1. Branches
        // Fetch from tbl_invlist as tbl_maintenance does not have BRANCH items yet
        $branches = [];
        $stmt = $this->conn->prepare("SELECT DISTINCT Branch FROM tbl_invlist ORDER BY Branch");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()) {
                if (!empty($row['Branch'])) {
                    $branches[] = $row['Branch'];
                }
            }
            $stmt->close();
        }
        $response['BRANCHES'] = $branches;

        // 2. Types — sourced from tbl_invlist so values match what product-search.php filters
        $types = [];
        $stmt = $this->conn->prepare("SELECT DISTINCT Type FROM tbl_invlist WHERE Type IS NOT NULL AND Type != '' ORDER BY Type");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()) {
                $types[] = $row['Type'];
            }
            $stmt->close();
        }
        $response['TYPES'] = $types;

        // 3. Categories — sourced from tbl_invlist so values match what product-search.php filters
        $categories = [];
        $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlist WHERE Category IS NOT NULL AND Category != '' ORDER BY Category");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()) {
                $categories[] = $row['Category'];
            }
            $stmt->close();
        }
        $response['CATEGORIES'] = $categories;

        // 4. Return Types (Hardcoded for now as they are business logic, unless table exists)
        $response['RETURN_TYPES'] = ['RETURNED', 'REFUND'];

        echo json_encode($response);
    }

    public function SaveReturn($data){
        try {
            $this->conn->autocommit(false);

            $entries = json_decode($data["DATA"]);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON data: " . json_last_error_msg());
            }

            if (empty($entries)) {
                throw new Exception("No data to save.");
            }

            $user = $_SESSION['USERNAME'];
            date_default_timezone_set('Asia/Manila');
            $DateReturned = date("m/d/Y H:i:s");

            // Generate Transaction No
            $sql = "SELECT MAX(CAST(SUBSTRING(TransactionNo, 4) AS UNSIGNED)) as max_num FROM tbl_purchasereturned WHERE TransactionNo LIKE 'PRN%'";
            $result = $this->conn->query($sql);
            $transactionNo = 'PRN000001';
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $nextNum = ($row['max_num'] ?? 0) + 1;
                $transactionNo = 'PRN' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
            }

            // Confirmed columns only — Reason column name TBD from debug_table.php
            $stmt = $this->conn->prepare(
                "INSERT INTO tbl_purchasereturned 
                 (TransactionNo, SIno, Serialno, Product, Branch, Quantity, DateAdded, User) 
                 VALUES (?,?,?,?,?,?,?,?)"
            );

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            foreach ($entries as $entry) {
                // JS sends: [Product, SIno, Serialno, Qty, ReturnType, DealerPrice, SRP, Branch, TotalDP, TotalSRP, ReasonText]
                $product  = $entry[0];
                $sino     = $entry[1];
                $serialno = $entry[2];
                $quantity = (int) $entry[3]; // cast to int
                $branch   = $entry[7];

                // All strings except quantity (i) — order: TransactionNo,SIno,Serialno,Product,Branch,Quantity,DateAdded,User
                $stmt->bind_param('ssssssss',
                    $transactionNo, $sino, $serialno, $product,
                    $branch, $quantity, $DateReturned, $user
                );

                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert $product: " . $stmt->error);
                }
            }


            $stmt->close();

            $this->conn->commit();
            $this->conn->autocommit(true);

            echo json_encode(array(
                "STATUS"        => "success",
                "MESSAGE"       => "Return transaction $transactionNo processed successfully.",
                "TransactionNo" => $transactionNo
            ));

        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(array(
                "STATUS"  => "ERROR",
                "MESSAGE" => $e->getMessage()
            ));
        }
    }


    public function GetHistory() {
        // Fetch all returned transactions, ordered by DateAdded descending
        $stmt = $this->conn->prepare("SELECT * FROM tbl_purchasereturned ORDER BY DateAdded DESC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            echo json_encode(['STATUS' => 'success', 'DATA' => $data]);
            $stmt->close();
        } else {
            echo json_encode(['STATUS' => 'error', 'MESSAGE' => 'Failed to fetch history']);
        }
    }
}

// Handler
if (isset($_POST['action'])) {
    $process = new Process();
    $action = $_POST['action'];

    if ($action == 'SaveReturn') {
        $process->SaveReturn($_POST);
    } elseif ($action == 'Initialize') {
        $process->Initialize();
    } elseif ($action == 'GetHistory') {
        $process->GetHistory();
    }
}
?>
