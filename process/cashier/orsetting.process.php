<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function LoadORs(){
        $ORList = $this->SelectQuery("SELECT PONICK FROM TBL_PO ORDER BY PONICK");
        
        echo json_encode(array(
            "ORLIST" => $ORList,
        ));
    }

    public function GetORData($data){
        $name = $data["Name"];

        $stmt = $this->conn->prepare("SELECT * FROM TBL_ORSERIES WHERE NAME = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        $ordata = $row;
            
        echo json_encode(array(
            "ORDATA" => $ordata,
        ));
    }

    public function SaveSeries($data){

        $id = $data["id"];
        $name = $data["name"];
        $seriesStatus = $data["seriesStatus"];
        $from = $data["from"];
        $to = $data["to"];
        $nextor = $data["nextor"];
        $orsleft = $data["orsleft"];
        $Mode = $data["Mode"];
        $user = $_SESSION['USERNAME'];
        $date = date("m/d/Y", strtotime("now"));
        $type = "PO";

        if ($Mode == "ADD") {
            $stmt = $this->conn->prepare("INSERT INTO TBL_ORSERIES (NAME,ORFROM,ORTO,ORLEFT,NEXTOR,ISSUEDBY,DATEISSUED,ORTYPE,ORSTATUS) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssssss", $name,$from,$to,$orsleft,$nextor,$user,$date,$type,$seriesStatus);
        } else {
            $stmt = $this->conn->prepare("UPDATE TBL_ORSERIES SET ORFROM = ?, ORTO = ?, ORLEFT = ?, NEXTOR = ?, ISSUEDBY = ?, DATEISSUED = ?, ORTYPE = ?, ORSTATUS = ? WHERE NAME = ? AND ID = ?");
            $stmt->bind_param("ssssssssss", $from,$to,$orsleft,$nextor,$user,$date,$type,$seriesStatus,$name, $id);
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