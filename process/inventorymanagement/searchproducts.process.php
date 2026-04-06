<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $List = $this->SelectQuery("SELECT * FROM tbl_invlist ORDER BY Product ASC");

        echo json_encode(array( 
            "LIST" => $List,
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