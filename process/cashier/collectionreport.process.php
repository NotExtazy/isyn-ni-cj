<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function LoadPOs(){
        $ORList = $this->SelectQuery("SELECT PONICK FROM TBL_PO ORDER BY PONICK");
        
        echo json_encode(array(
            "ENCODERLIST" => $ORList,
        ));
    }

    public function SearchTransactions($data){
        $cdate = date("Y-m-d",strtotime($data["cdate"]));
        $encodedBy = $data["encodedBy"];
        $from = $data["from"];
        $to = $data["to"];
        $qry = "";

        $principal = 0;
        $interest = 0;
        $cbu = 0;
        $penalty = 0;
        $mba = 0;

        $list = [];
        if ($encodedBy != "ALL") {
            $qry = "SELECT * FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE('".$cdate."','%Y-%m-%d') AND PO = '".$encodedBy."' AND ORNO >= '".$from."' AND ORNO <= '".$to."' ORDER BY CAST(ORNO AS UNSIGNED) ASC";

            $stmt = $this->conn->prepare("SELECT * FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND PO = ? AND ORNO >= ? AND ORNO <= ? ORDER BY CAST(ORNO AS UNSIGNED) ASC");
            $stmt->bind_param("ssss", $cdate,$encodedBy,$from,$to);
        } else {
            $qry = "SELECT * FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE('".$cdate."','%Y-%m-%d') AND ORNO >= '".$from."' AND ORNO <= '".$to."' ORDER BY CAST(ORNO AS UNSIGNED) ASC";

            $stmt = $this->conn->prepare("SELECT * FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND ORNO >= ? AND ORNO <= ? ORDER BY CAST(ORNO AS UNSIGNED) ASC");
            $stmt->bind_param("sss", $cdate,$from,$to);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            if ($row["Nature"] != "OTHER PAYMENT TYPES") {

                switch ($row["GLNo"]) {
                    case '11920':
                        $principal = $row["SLDrCr1"];
                        break;
                    case '21370':
                        $interest = $row["SLDrCr1"];
                        break;
                    case '11920':
                        $cbu = $row["SLDrCr1"];
                        break;
                    case '21370':
                        $penalty = $row["SLDrCr1"];
                        break;
                    case '11920':
                        $mba = $row["SLDrCr1"];
                        break;
                    case '11120':
                        $total = $row["DrOther"];
                        break;
                }

                $list[] = array(
                    "ORNO" => $row["ORNo"],
                    "PAYEE" => $row["Payee"],
                    "PRINCIPAL" => $principal,
                    "INTEREST" => $interest,
                    "CBU" => $cbu,
                    "PENALTY" => $penalty,
                    "MBA" => $mba,
                    "TOTAL" => $total,
                );
            } else {
                if ($row["GLNo"] == "11120") {
                    $list[] = array(
                        "ORNO" => $row["ORNo"],
                        "PAYEE" => $row["Payee"],
                        "PRINCIPAL" => $principal,
                        "INTEREST" => $interest,
                        "CBU" => $cbu,
                        "PENALTY" => $penalty,
                        "MBA" => $mba,
                        "TOTAL" => $row["DrOther"],
                    );
                }
            }
        }
            
        echo json_encode(array(
            "LIST" => $list,
            "QRY" => $qry,
        ));
    }

    public function ToSession($data) {
        $transactTbl = json_decode($data['transactTbl']);
        $cdate = $data['cdate'];
        $encodedby = $data['encodedby'];
        $from = $data['from'];
        $to = $data['to'];
        
        unset($_SESSION['CRDATA']);
        unset($_SESSION['CRDATE']);
        unset($_SESSION['CRENCODEDBY']);
        unset($_SESSION['CRFROM']);
        unset($_SESSION['CRTO']);

        $_SESSION['CRDATA'] = $transactTbl;
        $_SESSION['CRDATE'] = $cdate;
        $_SESSION['CRENCODEDBY'] = $encodedby;
        $_SESSION['CRFROM'] = $from;
        $_SESSION['CRTO'] = $to;

        echo json_encode(array(
            "STATUS" => 'PRINT READY',
            "DATA" => $_SESSION['CRDATA'],
        ));
    }
    
    private function SelectQuery($string){
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