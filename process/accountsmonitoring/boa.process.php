<?php
include_once("../../database/connection.php");

class Process extends Database
{    
    public function Initialize(){
        $BookTypes = $this->SelectQuery("SELECT DISTINCT BookType FROM tbl_books WHERE BookType <> '-' ORDER BY BookType;");
        $Funds = $this->SelectQuery("SELECT DISTINCT Fund FROM tbl_banksetup");

        echo json_encode(array(
            "BOOKTYPES" => $BookTypes,
            "FUNDS" => $Funds,
        ));
    }

    public function LoadDataRows($data){
        $BookType = $data["bookType"];

        $DataRows = $this->SelectQuery("SELECT * FROM TBL_DATAROWS WHERE ROWCLASS = '".$BookType."' ORDER BY ROWNAME");

        echo json_encode(array(
            "DATAROWS" => $DataRows,
        ));
    }

    public function SearchBooks($data){
        $fromDate = $data["fromDate"]; 
        $toDate = $data["toDate"];
        $BookType = $data["bookType"];
        $fund = $data["fund"];

        $squery = "SELECT * FROM tbl_books WHERE BookType = '$BookType' AND ";

        $DateField = "STR_TO_DATE(CDate,'%Y-%m-%d')";
        $From = "STR_TO_DATE('" . date("Y-m-d",strtotime($fromDate)) . "','%Y-%m-%d')";
        $To = "STR_TO_DATE('" . date("Y-m-d",strtotime($toDate)) . "','%Y-%m-%d')";
        $squery = $squery . $DateField . " >= " . $From . " AND " . $DateField . " <= " . $To;
        if ($fund != "CONSOLIDATED") {
            $squery = $squery . " AND Fund = '$fund'";
        }

        switch ($BookType) {
            case 'CRB':
                $SortColumn = "ORNO";
                break;
            case 'CDB':
                $SortColumn = "CVNO";
                break;
            case 'GJ':
                $SortColumn = "JVNO";
                break;
            default:
                # code...
                break;
        }

        $GroupQuery = str_replace("*", "ACCTTITLE, SUM(DROTHER) as TOTALDR, SUM(CROTHER) as TOTALCR",$squery);
        $GroupQuery = $GroupQuery . " AND LEFT(ACCTTITLE,1) <> ' ' GROUP BY ACCTTITLE";

        $stmt = $this->conn->prepare($GroupQuery);
        $stmt->execute();
        $GroupResult = $stmt->get_result();
        $stmt->close();

        $stmt = $this->conn->prepare($squery . " ORDER BY CDate, CAST(" . $SortColumn . " AS UNSIGNED) ASC");
        $stmt->execute();
        $QueryResult = $stmt->get_result(); 
        $stmt->close();

        $QueryData = [];
        $GroupData = [];
        $status = "SUCCESS";

        if ($GroupResult->num_rows > 0) {
            while ($GroupRow = $GroupResult->fetch_assoc()) {
                $GroupData[] = array("AcctTitle" => $GroupRow["ACCTTITLE"],"Debit" => $GroupRow["TOTALDR"],"Credit" => $GroupRow["TOTALCR"]);
            }
        } else {
            $status = "Nothing to fetch.";
        }

        while ($QueryRow = $QueryResult->fetch_assoc()) {
            $QueryData[] = $QueryRow;
        }

        echo json_encode(array(
            "STATUS" => $status,
            "QueryData" => $QueryData,
            "GroupData" => $GroupData,
        ));
    }

    public function SearchTransaction($data){
        $SelectQuery = "";
        $AcctName = "AcctTitle";
        $DrColumn = "DrOther";
        $CrColumn = "CrOther";

        $ReferenceNo = $data["ReferenceNo"];
        $CDate = $data["CDate"];
        $Fund = $data["Fund"];

        switch ($data["SelectedBooks"]) {
            case 'GJ':               
                $SelectQuery = "SELECT * FROM tbl_books WHERE JVNo = '" . $ReferenceNo . "' AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE('".$CDate."','%Y-%m-%d') AND Fund = '".$Fund."'";

                $_SESSION["JVISPRINT"] = "YES";
                $_SESSION["JVDATE"] = $CDate;
                $_SESSION["JVFUND"] = $Fund;
                $_SESSION["JVNO"] = $ReferenceNo;
                break;
            case 'CRB':
                $SelectQuery = "SELECT * FROM tbl_books WHERE ORNo = '" . $ReferenceNo . "' AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE('".$CDate."','%Y-%m-%d') AND Fund = '".$Fund."'";               

                $_SESSION["ORNO"] = $ReferenceNo;
                $_SESSION["ORDATE"] = $CDate;
                $_SESSION["ORFUND"] = $Fund;
                break;
            case 'CDB':
                $SelectQuery = "SELECT * FROM tbl_books WHERE CVNo = '" . $ReferenceNo . "' AND STR_TO_DATE(CDate,'%Y-%m-%d') = STR_TO_DATE('".$CDate."','%Y-%m-%d') AND Fund = '".$Fund."'";

                $_SESSION["CVISPRINT"] = "YES";
                $_SESSION["CVDATE"] = $CDate;
                $_SESSION["CVNO"] = $ReferenceNo;
                $_SESSION["CVFUND"] = $Fund;
                $_SESSION["REPRINT"] = "YES";
                break;
            default:
                # code...
                break;
        }

        $DataResult = [];
        $stmt = $this->conn->prepare($SelectQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $DataResult[] = array("AcctTitle" => $row[$AcctName], "SLDrCr" => $row["SLDrCr"], "Debit" => $row[$DrColumn], "Credit" => $row[$CrColumn]);
        }
        
        echo json_encode(array(
            "DataResult" => $DataResult,
        ));
    }

    public function PrintDetailedBOA($data){
        $_SESSION["PRINTFROMDATE"] = $data["fromDate"];
        $_SESSION["PRINTTODATE"] = $data["toDate"];
        $_SESSION["PRINTBOOKTYPE"] = $data["bookType"];
        $_SESSION["PRINTFUND"] = $data["fund"];
        echo json_encode(array(
            "READYPRINT" => "YES",
            "TYPE" => $_SESSION["PRINTBOOKTYPE"]
        ));
    }

    public function PrintSummaryBOA($data){
        $_SESSION["PRINTFROMDATE"] = $data["fromdate"];
        $_SESSION["PRINTTODATE"] = $data["todate"];
        $_SESSION["PRINTBOOKTYPE"] = $data["booktype"];
        $_SESSION["PRINTFUND"] = $data["fund"];
        echo json_encode(array(
            "READYPRINT" => "YES",
            "TYPE" => $_SESSION["PRINTBOOKTYPE"]
        ));
    }

    // ======================
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

    private function removeSpecialCharacters($string) {
        $pattern = '/[^a-zA-Z0-9]/';
        $replacement = '';
        $cleanString = strtolower(preg_replace($pattern, $replacement, $string));
        return $cleanString;
    }

    private function FillRow($data){
        $arr = [];
        while ($row = $data->fetch_assoc()) {
            $arr[] = $row;
        }
        return $arr;
    }

    function getLastDateOfPreviousMonth($forDate) {
        // Convert string date to DateTime object
        $date = new DateTime($forDate);
        
        // Subtract one month
        $date->modify('-1 month');
    
        // Get the last day of the previous month
        $lastDay = $date->format('t'); // 't' gives the number of days in the month
    
        // Construct the final date (last day of the previous month)
        return $date->format("Y-m") . "-" . $lastDay;
    }
}