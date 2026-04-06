<?php
$docRoot = $_SERVER['DOCUMENT_ROOT'];
include_once($docRoot . "/iSynApp-main/database/connection.php");

class Process extends Database
{
    public function LoadPage(){
        $AccountCodes = $this->SelectQuery("SELECT * FROM tbl_accountcodes");
        $Funds = $this->SelectQuery("SELECT DISTINCT Fund FROM tbl_banksetup");
        $Type = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_banksetup ORDER BY Type");

        echo json_encode(array(
            "ACCOUNTCODES" => $AccountCodes,
            "FUNDS" => $Funds,
            "TYPE" => $Type,
        ));
    }

    public function LoadBatchList(){
        $BatchCount = $this->SelectQuery("SELECT COUNT(DISTINCT BatchNo) AS BatchCount FROM tbl_othercv");
        $SavedList = $this->SelectQuery("SELECT DISTINCT BatchNo, payee, particular FROM tbl_othercv");

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

    public function GetBanks($data){
        $Banks = $this->SelectQuery("SELECT DISTINCT Bank FROM tbl_banksetup WHERE Type = '".$data["type"]."' AND Bank <> '-' ORDER BY Bank");

        echo json_encode(array(
            "BANKS" => $Banks,
        ));
    }

    public function LoadBankDetails($data){
        $BankInfo = $this->SelectQuery("SELECT * FROM tbl_banksetup WHERE Bank = '".$data["bank"]."'");

        echo json_encode(array(
            "BANKINFO" => $BankInfo,
        ));
    }

    public function DeleteSaveBatchNo($data){
        $stmt = $this->conn->prepare("DELETE FROM tbl_othercv WHERE BatchNo = '".$data["batchno"]."'");
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
        $batchdata = $this->SelectQuery("SELECT * FROM tbl_othercv WHERE BatchNo = '".$data["batchno"]."'");

        $status = "SUCCESS";
        // $message = "Transaction Batch No. " . $data["batchno"] . "Loaded.";

        echo json_encode(array(
            "STATUS" => $status,
            "BATCHNODATA" => $batchdata,
        ));
    }

    public function SaveCVEntry($data){

        if ($data["BATCHNO"] != "No") {
            $stmt = $this->conn->prepare("DELETE FROM tbl_othercv WHERE BatchNo = '".$data["BATCHNO"]."'");
            $stmt->execute();
            $stmt->close();
        }

        Reroll:
        $BatchNo = mt_rand(10000, 99999);

        $stmt = $this->conn->prepare("SELECT * FROM tbl_othercv WHERE BatchNo = '".$BatchNo."'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            goto Reroll;
        }

        $BranchInfo = $this->SelectQuery("SELECT * FROM tbl_configuration WHERE ConfigName = 'BRANCHNAME' AND ConfigOwner = 'BRANCH SETUP';");
        $BranchName = $BranchInfo[0]["Value"];

        $status = "";
        $message = "";

        $entries = json_decode($data["DATA"]);
        $tag = "-";
        $values = "";
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
            $checkAmount = str_replace(",", "", $data["CHECKAMOUNT"]);
            $amtwords = ucwords($this->numberTowords($checkAmount));
            
            $values .= ($i > 0) ? "," : "";
            $values .= "('".date("Y/m/d",strtotime($data["DATEPREPARED"]))."','".$BranchName."','".$data["FUND"]."','".$data["BANK"]."','bankcode','".$data["PAYEE"]."','".$data["CVNO"]."','".$data["CHECKNO"]."','".$data["PARTICULARS"]."','".date("m/d/Y",strtotime($data["DATEPREPARED"]))."','".$checkAmount."','".$amtwords."','".$accttitle."','".$entries[$i][4]."','".$sldrcr."','".$sldrcr1."','".$entries[$i][6]."','".$slno."','".$slname."','".$drother."','".$crother."','".$user."','".$clientno."','".$loanid."','entryno','".date("Y/m/d",strtotime($data["DATEPREPARED"]))."','".$program."','".$product."','".$tag."','".$BatchNo."','".$entries[$i][5]."','".$glno."','".$data["DISBURSEMENTTYPE"]."')";
        }

        // $qry = "INSERT INTO tbl_othercv (cdate, branch, fund, bank, bankcode, payee, cvno, checkno, particular, checkdate, amtothercv, amtwords, accttitle, acctno, sldrcr, sldrcr1, slyesno, slno, slname, drother, crother, preparedby, clientno, loanid, entryno, dateprepared, program, Product, Tag, BatchNo, DrCr, GLNo, DisbType) VALUES ".$values;

        $stmt = $this->conn->prepare("INSERT INTO tbl_othercv (cdate, branch, fund, bank, bankcode, payee, cvno, checkno, particular, checkdate, amtothercv, amtwords, accttitle, acctno, sldrcr, sldrcr1, slyesno, slno, slname, drother, crother, preparedby, clientno, loanid, entryno, dateprepared, program, Product, Tag, BatchNo, DrCr, GLNo, DisbType) VALUES ".$values);
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();

        if($result > 0){
            // $_SESSION["JVISPRINT"] = "YES";
            // $_SESSION["JVNO"] = $data["JVNO"];
            
            $message = "Transaction saved with Batch No. " . $BatchNo;
            $status = "SUCCESS";
        }

        
        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "BATCHNO" => $BatchNo,
            // "DATA" => $data,
            "CV" => $data["CVNO"],
            // "JV" => $jvnodata,
            // "ENTRIES" => $entries,
            // "VALUES" => $values,
            // "QRY" => $qry,
        ));
    }

    public function SaveToBooks($data){

        $bookpage = $this->GetBookPage("CDB",$data["BANK"],$data["DATEPREPARED"]);

        $cvnodata = $this->GetCVNo(array("Bank" => $data["BANK"]),"RETURN");
        $cvno = $cvnodata["CVNo"][0]["LastCV"];
        $checkno = $cvnodata["CVNo"][0]["NextCheck"];

        $status = "";
        $message = "";

        $stmt = $this->conn->prepare("SELECT * FROM tbl_othercv WHERE BatchNo = '".$data["BATCHNO"]."'");
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        if ($result->num_rows > 0) {
            $stmt = $this->conn->prepare("UPDATE tbl_othercv SET CheckNo = '".$checkno."', CVNo = '".$cvno."', CVDone = 'YES' WHERE BatchNo = '".$data["BATCHNO"]."'");
            $stmt->execute();                
            $stmt->close();

            $date = date("Y-m-d",strtotime($data["DATEPREPARED"]));

            $stmt = $this->conn->prepare("INSERT INTO tbl_books
            (ID, CDate, Branch, Fund, JVNo, Explanation, AcctTitle, AcctNo, SLDrCr, SLDrCr1, SLYesNo, SLNo, SLName, DrOther, CrOther, PreparedBy, CrDr, ClientNo, LoanID, postingstat, Program, Product, Tag, BookType, Payee, CheckNo, CVNo, ORNo, Bank, BookPage, GLNo) 
            SELECT 
            NULL, ?, branch, fund, '-', particular, accttitle, acctno, sldrcr, sldrcr1, slyesno, slno, slname, drother, crother, preparedby, DrCr, clientno, loanid, 'NO', program, Product, Tag, 'CDB', payee, ?, ?, '-', bank, ?, GLNo
            FROM tbl_othercv 
            WHERE BatchNo = '".$data["BATCHNO"]."'");
            $stmt->bind_param("ssss",$date,$checkno,$cvno,$bookpage);
            $stmt->execute();
            $result = $stmt->affected_rows;
            $stmt->close();

            if($result > 0){
                $nextcvno = floatval($cvno) + 1;
                $nextcheckno = floatval($checkno) + 1;
                $stmt = $this->conn->prepare("UPDATE tbl_banksetup SET LastCV = ?, NextCheck = ? WHERE Bank = ?");
                $stmt->bind_param("sss",$nextcvno,$nextcheckno,$data["BANK"]);
                $stmt->execute();
                $stmt->close();

                $_SESSION["BATCHNO"] = $data["BATCHNO"];
                $status = "SUCCESS";
                $message = "CV Done " . $data["BATCHNO"];
            }
        } else {
            $status = "ERROR";
            $message = "Batch No. " . $data["BATCHNO"] . ", doesn't exist.";
        }

        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "BOOKPAGE" => $bookpage,
            "CVNO" => $cvnodata,
            "batchno" => $data["BATCHNO"],
        ));
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

    private function GetCVNo($data){
        $CVNo = $this->SelectQuery("SELECT LastCV, NextCheck FROM tbl_banksetup WHERE Bank = '".$data["Bank"]."' LIMIT 1");
        
        return array(
            "CVNo" => $CVNo,
        );
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