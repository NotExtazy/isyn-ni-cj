<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function GenerateJournalReport ($data) {
        $purchaseSelect = $data['purchaseSelect'];
        $option = $data['option'];
        $from = $data['fromAsOf'];
        $to = $data['toAsOf'];
        $month = $data['month'];
        unset($_SESSION['purchaseSelect']);
        unset($_SESSION['option']);
        unset($_SESSION['fromAsOf']);
        unset($_SESSION['toAsOf']);
        unset($_SESSION['month']);
        $_SESSION['purchaseSelect'] = $purchaseSelect;
        $_SESSION['option'] = $option;
        $_SESSION['fromAsOf'] = $from;
        $_SESSION['toAsOf'] = $to;
        $_SESSION['month'] = $month;

        echo json_encode(array(
            // "DATAINVSESS" => $tableData,
            "PURCHASESELECT" => $purchaseSelect,
            "OPTION" => $option,
            "FROM" => $from,
            "TO" => $to,
            "MONTH" => $month,
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
