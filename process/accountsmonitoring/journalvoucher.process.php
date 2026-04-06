<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function LoadPage(){
        $NatureAdjustment = $this->SelectQuery("SELECT * FROM tbl_natureadjustments");
        $AccountCodes = $this->SelectQuery("SELECT * FROM tbl_accountcodes");
        $Funds = $this->SelectQuery("SELECT DISTINCT Fund FROM tbl_banksetup");

        echo json_encode(array(
            "NATUREADJUSTMENT" => $NatureAdjustment,
            "ACCOUNTCODES" => $AccountCodes,
            "FUNDS" => $Funds,
        ));
    }

    public function LoadBatchList(){
        $BatchCount = $this->SelectQuery("SELECT COUNT(DISTINCT BatchNo) AS BatchCount FROM tbl_jventries");
        $SavedList = $this->SelectQuery("SELECT DISTINCT BatchNo, explanation FROM tbl_jventries");

        echo json_encode(array(
            "BATCHCOUNT" => $BatchCount,
            "SAVEDLIST" => $SavedList,
        ));
    }

    public function LoadSLTypes($data){
        $SLTypes = $this->SelectQuery("SELECT DISTINCT slname FROM tbl_allowedsls WHERE AccountCode = '".$data["acctno"]."'");

        echo json_encode(array(
            "SLTYPES" => $SLTypes,
        ));
    }

    public function LoadSL($data){
        $field1 = "";
        $field2 = "";
        $field3 = "";
        $table = "";
        $others = FALSE;
        $sltype = $data["sltype"];

        switch ($sltype) {
            case 'CLIENT':
                $field1 = "FULLNAME";
                $field2 = "CLIENTNO";
                $field3 = "LOANID";
                $table = "TBL_LOANS";
                break;
            case 'BANK':
                $field1 = "BANK";
                $field2 = "ID";
                $table = "TBL_BANKSETUP";
                break;
            case 'FUND':
                $field1 = "FUND";
                $field2 = "ID";
                $table = "TBL_BANKSETUP";
                break;
            case 'EMPLOYEE':
                $field1 = "FULLNAME";
                $field2 = "EMPNO";
                $table = "TBL_EMPLOYEES";
                break;
            default:
                $field1 = "SUBNAME";
                $field2 = "SUBCODE";
                $table = "TBL_SUBSIDIARYCODES";
                $others = TRUE;
                break;
        }

        if($sltype == "CLIENT"){
            $SQL = "SELECT CLIENTNO,LOANID,FULLNAME,PRODUCT,PROGRAM,DATERELEASE FROM ".$table." ORDER BY ".$field1;
        }else{
            if($others == false){
                $SQL = "SELECT ".$field1.",".$field2." FROM ".$table." WHERE ".$field1." <> '-' ORDER BY ".$field1;
            }else{
                $SQL = "SELECT ".$field1.",".$field2." FROM ".$table." WHERE CATEGORY = '".$sltype."' ORDER BY ".$field1;
            }
        }

        $SLNames = $this->SelectQuery($SQL);

        echo json_encode(array(
            "SLNAMES" => $SLNames,
            "FIELD1" => $field1,
            "FIELD2" => $field2,
        ));
    }

    public function LoadSLFromSubtype($data){
        $table = "";

        switch ($data["subtype"]) {
            case 'CURRENT':
                $table = "TBL_LOANS";
                break;
            case 'OLD':
                $table = "TBL_LOANHISTORY";
                break;
            case 'WRITEOFF':
                $table = "TBL_WRITEOFFLOANS";
                break;
            default:
                break;
        }

        $SLNames = $this->SelectQuery("SELECT CLIENTNO,LOANID,FULLNAME,PRODUCT,PROGRAM,DATERELEASE FROM ".$table." ORDER BY CLIENTNO");

        echo json_encode(array(
            "SLNAMES" => $SLNames,
        ));
    }

    public function DeleteSaveBatchNo($data){
        $stmt = $this->conn->prepare("DELETE FROM tbl_jventries WHERE BatchNo = '".$data["batchno"]."'");
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();

        if($result > 0){
            $status = "SUCCESS";
            $message = "Deleted Transaction Batch No. " . $data["batchno"];
        }

        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
        ));
    }

    public function LoadSaveBatchNo($data){
        $batchdata = $this->SelectQuery("SELECT * FROM tbl_jventries WHERE BatchNo = '".$data["batchno"]."'");

        $status = "SUCCESS";
        // $message = "Transaction Batch No. " . $data["batchno"] . "Loaded.";

        echo json_encode(array(
            "STATUS" => $status,
            "BATCHNODATA" => $batchdata,
        ));
    }

    public function SaveJVEntry($data){

        if ($data["BATCHNO"] != "No") {
            $stmt = $this->conn->prepare("DELETE FROM tbl_jventries WHERE BatchNo = '".$data["BATCHNO"]."'");
            $stmt->execute();
            $stmt->close();
        }

        Reroll:
        $BatchNo = mt_rand(10000, 99999);

        $stmt = $this->conn->prepare("SELECT * FROM tbl_jventries WHERE BatchNo = '".$BatchNo."'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            goto Reroll;
        }

        $BranchInfo = $this->SelectQuery("SELECT * FROM tbl_configuration WHERE ConfigName = 'BRANCHNAME' AND ConfigOwner = 'BRANCH SETUP';");
        $BranchName = $BranchInfo[0]["Value"];

        // $jvnodata = $this->GetJVNo(array("fund" => $data["FUND"]),"RETURN");
        // $jvnodata["JVNo"][0]["JVNo"]

        $status = "";
        $message = "";

        $entries = json_decode($data["DATA"]);
        $tag = "-";
        $values = "";
        $prevamount = 0;
        $user = $_SESSION['USERNAME'];
        for ($i=0; $i < count($entries); $i++) { 
            $accttitle = (substr($entries[$i][0],0,6) == "&emsp;" ? "     ".str_replace("&emsp;","",$entries[$i][0]) : $entries[$i][0]);

            $sldrcr = str_replace(",", "", $entries[$i][1]);
            $sldrcr1 = str_replace(",", "", ($entries[$i][12] == "GL") ? "0.00" : ($entries[$i][1] == 0 ? "0.00" : number_format(str_replace(",", "", $entries[$i][1]) * -1, 2, '.', '')));

            $drother = ($entries[$i][2]=="") ? "0.00" : str_replace(",", "", $entries[$i][2]);
            $crother = ($entries[$i][3]=="") ? "0.00" : str_replace(",", "", $entries[$i][3]);
            $loanid = ($entries[$i][8]=="") ? "-" : $entries[$i][8];
            $slno = ($entries[$i][12]=="GL") ? "-" : $entries[$i][4];
            $slname = ($entries[$i][7]=="-") ? "" : $entries[$i][7];
            $clientno = ($entries[$i][12]=="GL") ? "-" : $entries[$i][4];
            $program = ($entries[$i][9]=="") ? "-" : $entries[$i][9];
            $product = ($entries[$i][10]=="") ? "-" : $entries[$i][10];
            $glno = ($entries[$i][12]=="GL") ? $entries[$i][4] : $entries[$i][11];
            
            $values .= ($i > 0) ? "," : "";
            $values .= "('".date("Y-m-d",strtotime($data["DATEPREPARED"]))."','".$BranchName."','".$data["FUND"]."','".$data["JVNO"]."','".$data["VOUCHEREXPLANATION"]."','".$accttitle."','".$entries[$i][4]."','".$sldrcr."','".$sldrcr1."','".$entries[$i][6]."','".$slno."','".$slname."','".$drother."','".$crother."','".$user."','".$clientno."','".$loanid."','".date("Y-m-d",strtotime($data["DATEPREPARED"]))."','".$program."','".$product."','".$tag."','".$BatchNo."','".$entries[$i][5]."','".$glno."','".$data["NATUREADJUSTMENT"]."')";
        }

        // $qry = "INSERT INTO tbl_jventries (cdate, branch, fund, jvno, explanation, accttitle, acctno, sldrcr, sldrcr1, slyesno, slno, slname, drother, crother, preparedby, clientno, loanid, dateprepared, program, Product, Tag, BatchNo, DrCr, GLNo, Nature) VALUES ".$values;

        $stmt = $this->conn->prepare("INSERT INTO tbl_jventries (cdate, branch, fund, jvno, explanation, accttitle, acctno, sldrcr, sldrcr1, slyesno, slno, slname, drother, crother, preparedby, clientno, loanid, dateprepared, program, Product, Tag, BatchNo, DrCr, GLNo, Nature) VALUES".$values);
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();

        if($result > 0){
            // $nextjvno = floatval($jvnodata["JVNo"][0]["JVNo"]) + 1;
            // $stmt = $this->conn->prepare("UPDATE tbl_banksetup SET JVNo = ? WHERE Fund = ?");
            // $stmt->bind_param("ss",$nextjvno,$data["FUND"]);
            // $stmt->execute();
            // $stmt->close();

            $_SESSION["JVISPRINT"] = "YES";
            $_SESSION["JVDATE"] = $data["DATEPREPARED"];
            $_SESSION["JVFUND"] = $data["FUND"];
            $_SESSION["JVNO"] = $data["JVNO"];

            $status = "SUCCESS";
            $message = "Transaction saved with Batch No. " . $BatchNo;
        }
        
        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "BATCHNO" => $BatchNo,
            // "DATA" => $data,
            "JV" => $data["JVNO"],
            // "JV" => $jvnodata,
            // "ENTRIES" => $entries,
            // "VALUES" => $values,
            // "QRY" => $qry,
        ));
    }

    public function SaveToBooks($data){

        $bookpage = $this->GetBookPage("GJ",$data["FUND"],$data["DATEPREPARED"]);

        $jvnodata = $this->GetJVNo(array("fund" => $data["FUND"]),"RETURN");
        $jvno = $jvnodata["JVNo"][0]["JVNo"];

        $status = "";
        $message = "";

        $stmt = $this->conn->prepare("INSERT INTO tbl_books
        (ID, CDate, Branch, Fund, JVNo, Explanation, AcctTitle, AcctNo, SLDrCr, SLDrCr1, SLYesNo, SLNo, SLName, DrOther, CrOther, PreparedBy, CrDr, ClientNo, LoanID, Nature, postingstat, Program, Product, Tag, BookType, CVNo, ORNo, BookPage, GLNo) 
        SELECT 
        NULL, cdate, branch, fund, '".$jvno."', explanation, accttitle, acctno, sldrcr, sldrcr1, slyesno, slno, slname, drother, crother, preparedby, DrCr, clientno, loanid, Nature, 'NO' ,program, Product, Tag, 'GJ', '-','-', '".$bookpage."', GLNo 
        FROM tbl_jventries 
        WHERE BatchNo = '".$data["BATCHNO"]."'");
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();

        if ($data["BATCHNO"] != "No") {
            $stmt = $this->conn->prepare("DELETE FROM tbl_jventries WHERE BatchNo = '".$data["BATCHNO"]."'");
            $stmt->execute();
            $stmt->close();
        }

        if($result > 0){
            $nextjvno = floatval($jvno) + 1;
            $stmt = $this->conn->prepare("UPDATE tbl_banksetup SET JVNo = ? WHERE Fund = ?");
            $stmt->bind_param("ss",$nextjvno,$data["FUND"]);
            $stmt->execute();
            $stmt->close();

            $_SESSION["JVNO"] = $jvno;

            $status = "SUCCESS";
            $message = "Transaction Completed";
        }

        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "BOOKPAGE" => $bookpage,
            "JVNO" => $jvno,
            "batchno" => $data["BATCHNO"],
        ));
    }

    public function GetJVNo($data,$return){
        $JVNo = $this->SelectQuery("SELECT JVNo FROM tbl_banksetup WHERE Fund = '".$data["fund"]."' LIMIT 1");

        if($return == "RETURN"){
            return array(
                "JVNo" => $JVNo,
            );
        }else{
            echo json_encode(array(
                "JVNo" => $JVNo,
            ));
        }
    }

    private function GetBookPage($type,$fund,$date){
        $lastbookpage = "";
        $newbookpage = "";

        $stmt = $this->conn->prepare("SELECT DISTINCT BOOKTYPE,FUND,CDATE,BOOKPAGE FROM TBL_BOOKS USE INDEX(forBookPage) WHERE BOOKTYPE = ? AND FUND = ? AND BOOKPAGE <> '-' ORDER BY CAST(REPLACE(BOOKPAGE,'".$type."-','') AS UNSIGNED) DESC LIMIT 1");
        $stmt->bind_param("ss",$type,$fund);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if($result->num_rows > 0){
            while ($row = $result->fetch_assoc()) {
                $lastbookpage = trim(str_replace($type."-","",$row["BOOKPAGE"]));
                $ddate = date("Y-m-d",strtotime($row["CDATE"]));
                $newbookpage = ($ddate == $date) ? $type."-".$lastbookpage : $type."-".(floatval($lastbookpage) + 1);
            }
        }else{
            $newbookpage = $type."-1";
        }

        return $newbookpage;
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