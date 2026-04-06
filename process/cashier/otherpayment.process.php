<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function LoadPage(){
        $ORSeries = $this->SelectQuery("SELECT DISTINCT Name FROM tbl_orseries ORDER BY Name");
        $AccountCodes = $this->SelectQuery("SELECT * FROM tbl_accountcodes");
        $Funds = $this->SelectQuery("SELECT DISTINCT Fund FROM tbl_banksetup ORDER BY Fund ASC");
        $ClientType = $this->SelectQuery("SELECT DISTINCT Type FROM tbl_clientlist ORDER BY Type");

        echo json_encode(array(
            "ORSERIES" => $ORSeries,
            "ACCOUNTCODES" => $AccountCodes,
            "FUNDS" => $Funds,
            "CLIENTTYPE" => $ClientType,
        ));
    }

    public function LoadClientName($data){
        $clientType = $data['clientType'];
        $clientName = [];

        $qry = "SELECT DISTINCT Name FROM tbl_clientlist WHERE type = '$clientType' ORDER BY NAME";
        $stmt = $this->conn->prepare($qry);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clientName[] = $row;
            }
        }

        echo json_encode(array( 
            "CLIENTNAMELIST" => $clientName,
        ));
    }

    public function LoadClientNameInfo($data){
        $clientName = $data['clientName'];
        $clientInfo = "";

        $stmt = $this->conn->prepare("SELECT Name, tin_no, FullAddress FROM tbl_clientlist WHERE Name = ? LIMIT 1");
        $stmt->bind_param('s', $clientName);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $clientInfo = $row;
            }
        }

        echo json_encode(array( 
            "CLIENTINFO" => $clientInfo,
        ));
    }

    public function GetBank($data){
        $fund = $data['fund'];
        $banklist = [];

        $stmt = $this->conn->prepare("SELECT Bank FROM tbl_banksetup WHERE Fund = ? AND Bank <> '-' ORDER BY Bank ASC");
        $stmt->bind_param('s', $fund);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $banklist[] = $row;
            }
        }

        echo json_encode(array( 
            "BANKLIST" => $banklist,
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

    public function SaveOtherPayment($data){
        $orprint = "NO";
        $transactDate = date("Y-m-d",strtotime($data["TRANSACTIONDATE"]));
        $payee = $data["PAYEE"];
        $payeeTin = $data["PAYEETIN"];
        $payeeAddress = $data["PAYEEADDRESS"];
        $particulars = $data["PARTICULARS"];
        $fund = $data["FUND"];
        $bank = $data["BANK"];
        $tag = $data["TAG"];
        $paymentType = $data["PAYMENTTYPE"];

        $checkNo = $data["CHECKNO"];
        $bankName = $data["BANKNAME"];
        $bankBranch = $data["BANKBRANCH"];

        $orseries = $data["ORSERIES"];
        $ortype = ($data["ORSERIES"] == "CASHIER") ? "CASHIER" : "PO";
        $orno = $data["SERIESNO"];
        $seriesLeftNo = $data["SERIESLEFTNO"];
        $nontax = $data["NONTAX"];

        $BranchInfo = $this->SelectQuery("SELECT * FROM tbl_configuration WHERE ConfigName = 'BRANCHNAME' AND ConfigOwner = 'BRANCH SETUP';");
        $BranchName = $BranchInfo[0]["Value"];

        $bookpage = $this->GetBookPage("CRB",$fund,$transactDate);

        $status = "";
        $message = "";

        $entries = json_decode($data["DATA"]);
        $values = "";
        $user = $_SESSION['USERNAME'];
        for ($i=0; $i < count($entries); $i++) { 
            $accttitle = (substr($entries[$i][0],0,6) == "&emsp;" ? "     ".str_replace("&emsp;","",$entries[$i][0]) : $entries[$i][0]);
            $acctno = $entries[$i][4];

            $sldrcr = str_replace(",", "", $entries[$i][1]);
            if (strpos($sldrcr, '(') !== false || strpos($sldrcr, ')') !== false) {
                $sldrcr = str_replace(['(', ')'], '', $sldrcr);
                $sldrcr = "-" . $sldrcr;
            }
            $sldrcr1 = str_replace(",", "", ($entries[$i][12] == "GL") ? "0.00" : ($sldrcr == 0 ? "0.00" : number_format(str_replace(",", "", $sldrcr) * -1, 2, '.', '')));
            
            $crdr = $entries[$i][5];
            $slyesno = $entries[$i][6];
            $drother = ($entries[$i][2]=="") ? "0.00" : str_replace(",", "", $entries[$i][2]);
            $crother = ($entries[$i][3]=="") ? "0.00" : str_replace(",", "", $entries[$i][3]);
            $loanid = ($entries[$i][8]=="") ? "-" : $entries[$i][8];
            $slno = ($entries[$i][12]=="GL") ? "-" : $entries[$i][4];
            $slname = ($entries[$i][7]=="-") ? "" : $entries[$i][7];
            $clientno = ($entries[$i][12]=="GL") ? "-" : $entries[$i][4];
            $program = ($entries[$i][9]=="") ? "-" : $entries[$i][9];
            $product = ($entries[$i][10]=="") ? "-" : $entries[$i][10];
            $glno = ($entries[$i][12]=="GL") ? $entries[$i][4] : $entries[$i][11];

            $nature = "OTHER PAYMENT TYPES";
            $bookType = "CRB";
            $depStat = "UNDEPOSITED";

            $postingstat = "NO";
            $jvno = "-";
            $cvno = "-";

            $values .= ($i > 0) ? "," : "";
            $values .= "('".$transactDate."','".$BranchName."','".$fund."','".$particulars."','".$accttitle."','".$acctno."','".$sldrcr."','".$sldrcr1."','".$slyesno."','".$slno."','".$slname."','".$drother."','".$crother."','".$user."','".$crdr."','".$clientno."','".$loanid."','".$nature."','".$program."','".$product."','".$tag."','".$bookType."','".$payee."','".$checkNo."','".$orno."','".$bank."','".$bookpage."','".$glno."','".$orseries."','".$bankBranch."','".$bankName."','".$ortype."','".$depStat."','".$paymentType."','".$postingstat."','".$jvno."','".$cvno."')";
        }

        // $qry = "INSERT INTO tbl_books (CDate, Branch, Fund, Explanation, AcctTitle, AcctNo, SLDrCr, SLDrCr1, SLYesNo, SLNo, SLName, DrOther, CrOther, PreparedBy, CrDr, ClientNo, LoanID, Nature, Program, Product, Tag, BookType, Payee, CheckNo, ORNo, Bank, BookPage, GLNo, PO, BankBranch, BankName, ORType, Status, PaymentType) VALUES ".$values;

        $stmt = $this->conn->prepare("INSERT INTO tbl_books (CDate, Branch, Fund, Explanation, AcctTitle, AcctNo, SLDrCr, SLDrCr1, SLYesNo, SLNo, SLName, DrOther, CrOther, PreparedBy, CrDr, ClientNo, LoanID, Nature, Program, Product, Tag, BookType, Payee, CheckNo, ORNo, Bank, BookPage, GLNo, PO, BankBranch, BankName, ORType, Status, PaymentType, postingstat, JVNo, CVNo) VALUES ".$values);
        $stmt->execute();
        $result = $stmt->affected_rows;
        $stmt->close();

        if($result > 0){
            // $_SESSION["ORPRINT"] = "YES";
            $_SESSION["ORNO"] = $orno;
            $_SESSION["ORDATE"] = $transactDate;
            $_SESSION["ORFUND"] = $fund;
            $_SESSION["ORSERIES"] = $orseries;
            $_SESSION["ORPYTIN"] = $payeeTin;
            $_SESSION["ORPYADD"] = $payeeAddress;
            $_SESSION["NONTAX"] = $nontax;
            
            $status = "SUCCESS";
            $message = "Transaction Saved";
            $orprint = "YES";

            $orno = floatval($orno) + 1;
            $orleft = floatval($seriesLeftNo) - 1;

            $stmt = $this->conn->prepare("UPDATE tbl_orseries SET NextOR = ?, ORLeft = ? WHERE Name = ?");
            $stmt->bind_param("sss",$orno,$orleft,$orseries);
            $stmt->execute();
            $stmt->close();
        }
        
        echo json_encode(array(
            "STATUS" => $status,
            "MESSAGE" => $message,
            "ORPRINT" => $orprint,
            // "VALUES" => $values,
            // "QRY" => $qry,
        ));
    }

    public function GetORNo($data,$return){
        $name = $data['name'];
        $orno = "";
        $orleft = "";
        $orstatus = "";

        $stmt = $this->conn->prepare("SELECT ORLeft, NextOR, ORStatus, Name FROM tbl_orseries WHERE Name = ?");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $orno = $row['NextOR'];
            $orleft = $row['ORLeft'];
            $orstatus = $row['ORStatus'];
        }

        if($return == "RETURN"){
            return array(
                "ORNO" => $orno,
                "ORLEFT" => $orleft,
                "ORSTATUS" => $orstatus,
            );
        }else{
            echo json_encode(array(
                "ORNO" => $orno,
                "ORLEFT" => $orleft,
                "ORSTATUS" => $orstatus,
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