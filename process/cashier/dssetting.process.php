<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function LoadFund(){
        $Fund = $this->SelectQuery("SELECT DISTINCT FUND FROM TBL_BANKSETUP WHERE FUND <> '-' ORDER BY FUND");

        $stmt = $this->conn->prepare("SELECT * FROM tbl_configuration WHERE ConfigName = 'DSPREFIX'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        $DSPrefix = $row["Value"];

        echo json_encode(array(
            "FUND" => $Fund,
            "DSPREFIX" => $DSPrefix,
        ));
    }

    public function LoadBank($data){
        $fund = $data["fund"];
        $defaultbank = "";
        $dsno = "";
        $status = "";

        $Bank = $this->SelectQuery("SELECT DISTINCT BANK FROM TBL_BANKSETUP WHERE BANK <> '-' AND FUND= '".$fund."' ORDER BY BANK");

        $stmt = $this->conn->prepare("SELECT * FROM TBL_DSNUMBERS WHERE FUND = ?");
        $stmt->bind_param("s", $fund);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $defaultbank = $row["DefaultBank"];
            $dsno = $row["DSNo"];
            $status = "MERON";
        } else {
            $defaultbank = "";
            $dsno = "";
            $status = "WALA";
        }

        echo json_encode(array(
            "BANK" => $Bank,
            "DEFAULTBANK" => $defaultbank,
            "DSNO" => $dsno,
            "STATUS" => $status,
        ));
    }

    public function SaveDSSetting($data){

        $fund = $data["fund"];
        $bank = $data["bank"];
        $lastDSNo = $data["lastDSNo"];
        $Mode = $data["Mode"];

        if ($Mode == "SET") {
            $stmt = $this->conn->prepare("INSERT INTO TBL_DSNUMBERS (FUND,DSNO,DEFAULTBANK) VALUES (?,?,?)");
            $stmt->bind_param("sss", $fund,$lastDSNo,$bank);
        } else {
            $stmt = $this->conn->prepare("UPDATE TBL_DSNUMBERS SET DSNO = ?, DEFAULTBANK = ? WHERE FUND = ?");
            $stmt->bind_param("sss", $lastDSNo,$bank,$fund);
        }

        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();
        
        $status = "";
        if($result > 0){
            $status = "SUCCESS";
        } else {
            $status = "ERROR";
        }

        echo json_encode(array(
            "STATUS" => $status,
            // "VALUES" => $values,
            // "QRY" => $qry,
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