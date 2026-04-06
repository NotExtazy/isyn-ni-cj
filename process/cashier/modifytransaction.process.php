<?php
include_once(__DIR__ . "/../../database/connection.php");

class Process extends Database
{
    public function LoadORTypes($data){
        $sql = "SELECT DISTINCT ORType FROM TBL_BOOKS WHERE BookType='CRB' AND ORType IS NOT NULL AND ORType != '' ORDER BY ORType";
        $result = $this->conn->query($sql);
        $ORTypes = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ORTypes[] = $row;
            }
        } else {
            // Return error info for debugging
            echo json_encode(["ORTYPES" => [], "ERROR" => $this->conn->error, "SQL" => $sql]);
            return;
        }
        
        echo json_encode(array(
            "ORTYPES" => $ORTypes,
        ));
    }

    public function LoadTransactions($data){
        $ortype = $data["type"];
        $cdate = date("Y-m-d",strtotime($data["SelectedDate"]));
        $rows = [];

        // Check if PrevStatus column exists; if not, omit that filter
        $colCheck = $this->conn->query("SHOW COLUMNS FROM TBL_BOOKS LIKE 'PrevStatus'");
        $hasStatus = ($colCheck && $colCheck->num_rows > 0);

        $sql = "SELECT DISTINCT ORNo, Payee, ClientNo, LoanID, Nature, Fund, CDate FROM TBL_BOOKS WHERE BookType = 'CRB' AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND ORType = ?";
        if ($hasStatus) { $sql .= " AND (PrevStatus IS NULL OR (PrevStatus != 'ARCHIVED' AND PrevStatus != 'CANCELLED'))"; }
        $sql .= " ORDER BY Payee";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $cdate, $ortype);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
            
        echo json_encode(array(
            "ORLIST" => $rows,
        ));
    }

    public function GetORData($data){
        $orno = $data["orno"];
        $cdate = $data["cdate"];
        $data = [];

        $fund = "-";
        $po = "-";
        $nature = "-";

        $principal = 0;
        $interest = 0;
        $cbu = 0;
        $penalty = 0;
        $mba = 0;
        $total = 0;

        $qry = "SELECT * FROM TBL_BOOKS WHERE ORNo = '".$orno."' AND BookType = 'CRB' AND CDate = '".$cdate."'";

        $stmt = $this->conn->prepare("SELECT * FROM TBL_BOOKS WHERE ORNo = ? AND BookType = 'CRB' AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d')");
        $stmt->bind_param("ss", $orno, $cdate);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;

            $fund = $row["Fund"];
            $po = $row["PO"];
            $nature = $row["Nature"];

            switch ($row["GLNo"]) {
                case '11920':
                    $principal = $row["SLDrCr1"];
                    break;
                case '21370':
                    $interest = $row["SLDrCr1"];
                    break;
                case '31100':
                $cbu = $row["SLDrCr1"];
                break;
            case '43400':
                $penalty = $row["SLDrCr1"];
                break;
            case '21210':
                $mba = $row["SLDrCr1"];
                break;
                case '11120':
                    $total = $row["DrOther"];
                    break;
            }
        }
            
        echo json_encode(array(
            "ORDATA" => $data,
            "QRY" => $qry,
            "FUND" => $fund,
            "PO" => $po,
            "NATURE" => $nature,
            "PRINCIPAL" => $principal,
            "INTEREST" => $interest,
            "CBU" => $cbu,
            "PENALTY" => $penalty,
            "MBA" => $mba,
            "TOTAL" => $total
        ));
    }

    public function CancelTransaction($data){
        $orno = $data["orno"];
        $fund = $data["fund"];
        $po = $data["po"];
        $nature = $data["nature"];
        $clientno = $data["clientno"];
        $loanid = $data["loanid"];
        $cdate = $data["cdate"];
        
        if ($nature == "LOAN AMORTIZATION") {
            $stmt = $this->conn->prepare("UPDATE TBL_BOOKS SET PrevStatus = 'CANCELLED', Payee = 'CANCELLED', DrOther = '0', CrOther = '0', SLDrCr = '0', SLDrCr1 = '0', ClientNo='-', LoanID='-', Explanation = CONCAT(Explanation, ' (CANCELLED)') WHERE ORNo = ? AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND ClientNo = ? AND LoanID = ?");
            $stmt->bind_param("ssss", $orno, $cdate, $clientno, $loanid);
        } else {
            $stmt = $this->conn->prepare("UPDATE TBL_BOOKS SET PrevStatus = 'CANCELLED', Payee = 'CANCELLED', DrOther = '0', CrOther = '0', SLDrCr = '0', SLDrCr1 = '0', ClientNo='-', LoanID='-', Explanation = CONCAT(Explanation, ' (CANCELLED)') WHERE ORNo = ? AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ? AND PO = ?");
            $stmt->bind_param("ssss", $orno, $cdate, $fund, $po);
        }

        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();
        
        echo json_encode(["STATUS" => $result > 0 ? "SUCCESS" : "ERROR"]);
    }

    public function ArchiveTransaction($data){
        $orno  = $data["orno"];
        $cdate = $data["cdate"];
        $fund  = $data["fund"];
        $po    = $data["po"];
        
        $stmt = $this->conn->prepare("UPDATE TBL_BOOKS SET PrevStatus = 'ARCHIVED' WHERE ORNo = ? AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND Fund = ? AND PO = ? AND BookType = 'CRB'");
        $stmt->bind_param("ssss", $orno, $cdate, $fund, $po);
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();
        
        echo json_encode(["STATUS" => $result > 0 ? "SUCCESS" : "ERROR"]);
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