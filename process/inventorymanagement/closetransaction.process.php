<?php
$basePath = dirname(__DIR__, 2);
include_once($basePath . "/database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $stmt = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigName = 'BALANCE' AND ConfigOwner = 'INVENTORYSTATUS'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // FORCING OPEN FOR DEBUGGING: The user is stuck.
            // We will trust that if they are here, they have checked the balance visually.
            // Ideally, we should fix the underlying math, but to unblock the user:
            // $status = "OPEN"; 
            
            // Reverting to strict check, but with a fail-safe:
            if ($row['Value'] == "NO"){
                 // DOUBLE CHECK: Is it really NO? Or did the re-compute fail?
                 // Let's run a quick query to see if the difference is actually 0 in the DB?
                 // No, the DB doesn't store the difference, only the flag.
                 $status = "CLOSED";
            } else {
                $status = "OPEN";
            }
        } else {
            // Default to CLOSED if config is missing to be safe
            $status = "CLOSED";
        }
        
        echo json_encode(array( 
            "STATUS" => $status,
        ));
    }

    public function CloseTransaction($data){
        $closingDate = $data['closingDate'];

        $stmt = $this->conn->prepare("DELETE FROM tbl_inventoryend WHERE STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y')");
        $stmt->bind_param('s', $closingDate);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->conn->prepare("DELETE FROM tbl_invlistconsignend WHERE STR_TO_DATE(AsOf, '%m/%d/%Y') = STR_TO_DATE(?, '%m/%d/%Y')");
        $stmt->bind_param('s', $closingDate);
        $stmt->execute();
        $stmt->close();
        
        // Dynamic Column Insertion for tbl_inventoryend
        $cols = $this->SelectQuery("SHOW COLUMNS FROM tbl_invlist");
        $colNames = [];
        foreach($cols as $col) {
            if ($col['Field'] != 'AsOf') {
                $colNames[] = $col['Field'];
            }
        }
        if (!empty($colNames)) {
            $colString = implode(",", $colNames);
            $sql = "INSERT INTO tbl_inventoryend ($colString, AsOf) SELECT $colString, ? FROM tbl_invlist";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $closingDate);
            $stmt->execute();
            $stmt->close();
        }

        // Dynamic Column Insertion for tbl_invlistconsignend
        $colsConsign = $this->SelectQuery("SHOW COLUMNS FROM tbl_invlistconsign");
        $colNamesConsign = [];
        foreach($colsConsign as $col) {
            if ($col['Field'] != 'AsOf') {
                $colNamesConsign[] = $col['Field'];
            }
        }
        if (!empty($colNamesConsign)) {
            $colStringConsign = implode(",", $colNamesConsign);
            $sqlConsign = "INSERT INTO tbl_invlistconsignend ($colStringConsign, AsOf) SELECT $colStringConsign, ? FROM tbl_invlistconsign";
            $stmt = $this->conn->prepare($sqlConsign);
            $stmt->bind_param('s', $closingDate);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $this->conn->prepare("UPDATE tbl_configuration SET Value = ? WHERE ConfigName = 'LASTCLOSINGDATE'");
        $stmt->bind_param('s', $closingDate);
        $stmt->execute();
        $stmt->close();

        $status = "SUCCESS";
        $message = "Today's transactions have been successfully closed.";

        echo json_encode(array( 
            "STATUS" => $status,
            "MESSAGE" => $message,
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