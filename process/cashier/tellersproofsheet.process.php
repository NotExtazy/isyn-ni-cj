<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function LoadTotals($data){
        $cdate = date("Y-m-d",strtotime($data["cdate"]));
        $user = $_SESSION['USERNAME'];
        $systemtotals = 0;
        $checktotals = 0;
        $prevundeptotals = 0;

        // SystemTotals ==================================
        $stmt = $this->conn->prepare("SELECT SUM(DrOther) As Totals FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND GLNO = '11120' AND STATUS = 'UNDEPOSITED' AND DROTHER > 0 AND PREPAREDBY = ?");
        $stmt->bind_param("ss", $cdate,$user);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        $systemtotals = $row["Totals"] !== null ? $row["Totals"] : 0;

        // CheckTotals ==================================
        $stmt = $this->conn->prepare("SELECT SUM(DrOther) As TotalCheck FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') < STR_TO_DATE(?,'%Y-%m-%d') AND GLNO = '11120' AND PAYMENTTYPE = 'CHECK' AND STATUS = 'UNDEPOSITED' AND DROTHER > 0 AND PREPAREDBY = ?");
        $stmt->bind_param("ss", $cdate,$user);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        $checktotals = $row["TotalCheck"] !== null ? $row["TotalCheck"] : 0;


        // Previous Undep Transactions ==================================
        $stmt = $this->conn->prepare("SELECT SUM(DrOther) As TotalPrevUndep FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') < STR_TO_DATE(?,'%Y-%m-%d') AND GLNO = '11120' AND DROTHER > 0 AND PREPAREDBY = ? AND STATUS = 'UNDEPOSITED' AND PREVSTATUS = 'UNDEPOSITED'");
        $stmt->bind_param("ss", $cdate,$user);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        $row = $result->fetch_assoc();
        $prevundeptotals = $row["TotalPrevUndep"] !== null ? $row["TotalPrevUndep"] : 0;
        
        echo json_encode(array(
            "SYSTEMTOTALS" => $systemtotals,
            "CHECKTOTALS" => $checktotals,
            "PREVUNDEPTOTALS" => $prevundeptotals,
        ));
    }

    public function LoadScheduleA($data){
        $cdate = date("Y-m-d",strtotime($data["cdate"]));
        $user = $_SESSION['USERNAME'];

        $FUNDS = $this->SelectQuery("SELECT DISTINCT FUND FROM TBL_BANKSETUP ORDER BY FUND");

        // Total Collections
        $SchedATotalCollectionsData = [];
        foreach ($FUNDS as $fundRow) {
            $fund = $fundRow['FUND'];

            $totalCollectionsStmt = $this->conn->prepare("SELECT SUM(DrOther) AS myTOTALCOLLECTIONS FROM TBL_BOOKS WHERE BOOKTYPE='CRB' AND GLNO = '11120' AND FUND = ? AND STATUS='UNDEPOSITED' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND PREPAREDBY = ?");
            $totalCollectionsStmt->bind_param("sss", $fund,$cdate,$user);
            $totalCollectionsStmt->execute();
            $totalCollectionsResult = $totalCollectionsStmt->get_result();
            $totalCollectionsStmt->close();
        
            $vTotalCollections = 0;
            if ($totalCollectionsResult->num_rows > 0) {
                $row = $totalCollectionsResult->fetch_assoc();
                if (!is_null($row["myTOTALCOLLECTIONS"])) {
                    $vTotalCollections = $row["myTOTALCOLLECTIONS"];
                }
            }
        
            $SchedATotalCollectionsData[] = $vTotalCollections;
        }

        // Undep previous
        $SchedAUndepositedCollectionsPrev = [];

        foreach ($FUNDS as $fundRow) {
            $fund = $fundRow['FUND'];
        
            $undepPrevStmt = $this->conn->prepare("SELECT SUM(DrOther) AS mySUM FROM TBL_BOOKS WHERE BOOKTYPE='CRB' AND FUND = ? AND STR_TO_DATE(CDATE,'%Y-%m-%d') < STR_TO_DATE(?,'%Y-%m-%d') AND GLNO = '11120' AND DROTHER > 0 AND PREPAREDBY = ? AND PREVSTATUS='UNDEPOSITED'");
            $undepPrevStmt->bind_param("sss", $fund,$cdate,$user);
            $undepPrevStmt->execute();
            $undepPrevResult = $undepPrevStmt->get_result();
        
            $vUnderPrev = 0;
            if ($undepPrevResult->num_rows > 0) {
                $row = $undepPrevResult->fetch_assoc();
                if (!is_null($row["mySUM"])) {
                    $vUnderPrev = $row["mySUM"];
                }
            }
        
            $SchedAUndepositedCollectionsPrev[] = $vUnderPrev;
        }

        // Deposited
        $deposited = [];

        foreach ($FUNDS as $fundRow) {
            $fund = $fundRow['FUND'];
        
            // First Query
            $depositedStmt = $this->conn->prepare("SELECT SUM(DrOther) AS myDEPOSITED FROM TBL_BOOKS WHERE BOOKTYPE='CRB' AND FUND = ? AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND GLNO = '11120' AND DROTHER > 0 AND PREPAREDBY = ? AND STATUS = 'DEPOSITED'");
            $depositedStmt->bind_param("sss", $fund, $cdate, $user);
            $depositedStmt->execute();
            $depositedResult = $depositedStmt->get_result();
        
            $vdeposited = 0;
            if ($depositedResult->num_rows > 0) {
                $row = $depositedResult->fetch_assoc();
                if (!is_null($row["myDEPOSITED"])) {
                    $vdeposited = $row["myDEPOSITED"];
                }
            }
        
            $deposited[] = $vdeposited;
        }

        // Undep Day End
        $undepDay = array();

        foreach ($FUNDS as $fundRow) {
            $fund = $fundRow['FUND'];
        
            // First Query
            $undepDayStmt = $this->conn->prepare("SELECT SUM(DrOther) AS myUNDEPOSITED FROM TBL_BOOKS WHERE BOOKTYPE='CRB' AND GLNO = '11120' AND FUND = ? AND STATUS='UNDEPOSITED' AND STR_TO_DATE(CDATE,'%Y-%m-%d') <= STR_TO_DATE(?,'%Y-%m-%d') AND PREPAREDBY = ? AND PREVSTATUS='UNDEPOSITED'");
            $undepDayStmt->bind_param("sss", $fund, $cdate, $user);
            $undepDayStmt->execute();
            $undepDayResult = $undepDayStmt->get_result();
        
            $vundepDay = 0;
            if ($undepDayResult->num_rows > 0) {
                $row = $undepDayResult->fetch_assoc();
                if (!is_null($row["myUNDEPOSITED"])) {
                    $vundepDay = $row["myUNDEPOSITED"];
                }
            }
        
            $undepDay[] = $vundepDay;
        }


        echo json_encode(array(
            "FUNDS" => $FUNDS,
            "SCHEDATOTALCOLLECTIONDATA" => $SchedATotalCollectionsData,
            "SCHEDAUNDEPCOLLECTIONPREV" => $SchedAUndepositedCollectionsPrev,
            "SCHEDADEPOSITED" => $deposited,
            "SCHEDAUNDEPDAY" => $undepDay,
        ));
    }

    public function LoadScheduleB($data){
        $cdate = date("Y-m-d",strtotime($data["cdate"]));
        $user = $_SESSION['USERNAME'];
        $SchedBData = [];

        $stmt = $this->conn->prepare("SELECT * FROM TBL_BOOKS WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') < STR_TO_DATE(?,'%Y-%m-%d') AND GLNO = '11120' AND PAYMENTTYPE = 'CHECK' AND STATUS = 'UNDEPOSITED' AND DROTHER > 0 AND PREPAREDBY = ?");
        $stmt->bind_param("ss", $cdate,$user);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $SchedBData [] = $row;
        }

        echo json_encode(array(
            "SCHEDBDATA" => $SchedBData,
        ));
    }

    public function LoadScheduleC($data){
        $cdate = date("Y-m-d",strtotime($data["cdate"]));
        $user = $_SESSION['USERNAME'];
        $SchedCData = [];

        // $prevundepdeposited = 0;
        // $prevundep = 0;

        $stmt = $this->conn->prepare("SELECT * FROM TBL_BOOKS WHERE BOOKTYPE='CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') < STR_TO_DATE(?,'%Y-%m-%d') AND GLNO = '11120' AND DROTHER > 0 AND PREPAREDBY = ? AND PREVSTATUS='UNDEPOSITED'");
        $stmt->bind_param("ss", $cdate,$user);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        while ($row = $result->fetch_assoc()) {
            $SchedCData [] = $row;

            // if ($row["Status"] == "DEPOSITED") {
            //     $prevundepdeposited += $row["DrOther"];
            // } else if ($row["Status"] == "UNDEPOSITED"){
            //     $prevundep += $row["DrOther"];
            // }
        }

        echo json_encode(array(
            "SCHEDCDATA" => $SchedCData,
            // "PREVUNDEPDEPOSITED" => $prevundepdeposited,
            // "PREVUNDEP" => $prevundep,
        ));
    }

    public function LoadUndepToday($data){
        $cdate = date("Y-m-d",strtotime($data["cdate"]));
        $user = $_SESSION['USERNAME'];
        $UndepTodayData = [];

        $FUNDS = $this->SelectQuery("SELECT DISTINCT FUND FROM TBL_BANKSETUP ORDER BY FUND");

        foreach ($FUNDS as $fundRow) {
            $fund = $fundRow['FUND'];
            
            $stmt = $this->conn->prepare("SELECT Fund, Bank, PaymentType, DrOther FROM TBL_BOOKS WHERE GLNO = '11120' AND FUND = ? AND STATUS='UNDEPOSITED' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND PREPAREDBY = ?");
            $stmt->bind_param("sss", $fund,$cdate,$user);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            while ($row = $result->fetch_assoc()) {
                $UndepTodayData [] = $row;
    
            }
        }
        
        echo json_encode(array(
            "UNDEPTODAYDATA" => $UndepTodayData,
        ));
    }

    public function ToSession($data) {     
        $billcoins = $data;
        $TotalUndepPrev = $data['TotalUndepPrev'];
        $TotalCollections = $data['TotalCollections'];
        $TotalDeposit = $data['TotalDeposit'];
        $TotalUndepDayEnd = $data['TotalUndepDayEnd'];
        $scheduleA = json_decode($data['scheduleA']);
        $scheduleB = json_decode($data['scheduleB']);
        $scheduleC = json_decode($data['scheduleC']);
        $todayUndep = json_decode($data['todayUndep']);
        $psdate = date("Y-m-d",strtotime($data["SelectedDate"]));
        $user = $_SESSION['USERNAME'];
        
        unset($_SESSION['billcoins']);
        unset($_SESSION['TotalUndepPrev']);
        unset($_SESSION['TotalCollections']);
        unset($_SESSION['TotalDeposit']);
        unset($_SESSION['TotalUndepDayEnd']);
        unset($_SESSION['scheduleA']);
        unset($_SESSION['scheduleB']);
        unset($_SESSION['scheduleC']);
        unset($_SESSION['todayUndep']);
        unset($_SESSION['psdate']);

        $_SESSION['billcoins'] = $billcoins;
        $_SESSION['TotalUndepPrev'] = $TotalUndepPrev;
        $_SESSION['TotalCollections'] = $TotalCollections;
        $_SESSION['TotalDeposit'] = $TotalDeposit;
        $_SESSION['TotalUndepDayEnd'] = $TotalUndepDayEnd;
        $_SESSION['scheduleA'] = $scheduleA;
        $_SESSION['scheduleB'] = $scheduleB;
        $_SESSION['scheduleC'] = $scheduleC;
        $_SESSION['todayUndep'] = $todayUndep;
        $_SESSION['psdate'] = $psdate;

        $stat1 = "";

        $qry1 = "UPDATE TBL_BOOKS SET PREVSTATUS = 'UNDEPOSITED' WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE('".$psdate."','%Y-%m-%d') AND STATUS = 'UNDEPOSITED' AND PREPAREDBY = '".$user."'";

        $stmt = $this->conn->prepare("UPDATE TBL_BOOKS SET PREVSTATUS = 'UNDEPOSITED' WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') = STR_TO_DATE(?,'%Y-%m-%d') AND STATUS = 'UNDEPOSITED' AND PREPAREDBY = ?");
        $stmt->bind_param("ss",$psdate,$user);
        if ($stmt->execute()) {
            $stat1 = "oke";
        } else {
            $stat1 = "no";
        }
        $stmt->close();

        
        $stat2 = "";
        $qry2 = "UPDATE TBL_BOOKS SET PREVSTATUS = 'DEPOSITED' WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') < STR_TO_DATE('".$psdate."','%Y-%m-%d') AND STATUS = 'DEPOSITED' AND PREVSTATUS = 'UNDEPOSITED' AND PREPAREDBY = '".$user."'";

        $stmt = $this->conn->prepare("UPDATE TBL_BOOKS SET PREVSTATUS = 'DEPOSITED' WHERE BOOKTYPE = 'CRB' AND STR_TO_DATE(CDATE,'%Y-%m-%d') < STR_TO_DATE(?,'%Y-%m-%d') AND STATUS = 'DEPOSITED' AND PREVSTATUS = 'UNDEPOSITED' AND PREPAREDBY = ?");
        $stmt->bind_param("ss",$psdate,$user);
        if ($stmt->execute()) {
            $stat2 = "oke";
        } else {
            $stat2 = "no";
        }
        $stmt->close();

        echo json_encode(array(
            "STATUS" => 'PRINT_READY',
            "DATA" => $billcoins,
            "stat1" => $stat1,
            "qry1" => $qry1,
            "stat2" => $stat2,
            "qry2" => $qry2,
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