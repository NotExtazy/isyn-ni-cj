<?php
$basePath = dirname(__DIR__, 2);
include_once($basePath . "/database/connection.php");
class CancelConsignmentProcess extends Database {
    public function Initialize(){
        $branches = [];
        $types = [];
        $categories = [];
        $stmt = $this->conn->prepare("SELECT DISTINCT Branch FROM tbl_invlistconsign ORDER BY Branch");
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){ $branches[] = $row['Branch']; }
        $stmt->close();
        $stmt = $this->conn->prepare("SELECT DISTINCT Type FROM tbl_invlistconsign ORDER BY Type");
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){ $types[] = $row['Type']; }
        $stmt->close();
        $stmt = $this->conn->prepare("SELECT DISTINCT Category FROM tbl_invlistconsign ORDER BY Category");
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){ $categories[] = $row['Category']; }
        $stmt->close();
        echo json_encode(["branches"=>$branches,"types"=>$types,"categories"=>$categories]);
    }
    public function LoadTypes($data){
        $branch = isset($data['branch']) ? trim($data['branch']) : '';
        $types = [];
        if ($branch === '' || strtoupper($branch) === 'OVERALL'){
            $stmt = $this->conn->prepare("SELECT DISTINCT Type FROM tbl_invlistconsign ORDER BY Type");
        } else {
            $stmt = $this->conn->prepare("SELECT DISTINCT Type FROM tbl_invlistconsign WHERE Branch = ? ORDER BY Type");
            $stmt->bind_param('s', $branch);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){ $types[] = $row['Type']; }
        $stmt->close();
        echo json_encode(["types"=>$types]);
    }
    public function LoadCategories($data){
        $branch = isset($data['branch']) ? trim($data['branch']) : '';
        $type = isset($data['type']) ? trim($data['type']) : '';
        $categories = [];
        $sql = "SELECT DISTINCT Category FROM tbl_invlistconsign";
        $where = [];
        $params = [];
        $typesStr = '';
        if ($branch !== '' && strtoupper($branch) !== 'OVERALL'){ $where[] = "Branch = ?"; $typesStr .= 's'; $params[] = $branch; }
        if ($type !== '' && strtoupper($type) !== 'OVERALL'){ $where[] = "Type = ?"; $typesStr .= 's'; $params[] = $type; }
        if (count($where) > 0){ $sql .= " WHERE ".implode(" AND ", $where); }
        $sql .= " ORDER BY Category";
        $stmt = $this->conn->prepare($sql);
        if ($typesStr !== ''){
            $refs = [];
            $refs[] = &$typesStr;
            foreach ($params as $k => $v){ $refs[] = &$params[$k]; }
            call_user_func_array([$stmt,'bind_param'], $refs);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()){ $categories[] = $row['Category']; }
        $stmt->close();
        echo json_encode(["categories"=>$categories]);
    }
    public function SearchProducts($data){
        $branch = isset($data['branch']) ? trim($data['branch']) : '';
        $type = isset($data['type']) ? trim($data['type']) : '';
        $category = isset($data['category']) ? trim($data['category']) : '';
        if (strtoupper($branch) === 'OVERALL') $branch = '';
        if (strtoupper($type) === 'OVERALL') $type = '';
        if (strtoupper($category) === 'OVERALL') $category = '';
        $items = [];
        $sql = "SELECT SIno, Serialno, Product, Category, Branch, Quantity, DealerPrice, TotalPrice, TotalSRP, TotalMarkup FROM tbl_invlistconsign";
        $where = [];
        $params = [];
        $typesStr = '';
        if ($branch !== ''){ $where[] = "Branch = ?"; $typesStr .= 's'; $params[] = $branch; }
        if ($type !== ''){ $where[] = "Type = ?"; $typesStr .= 's'; $params[] = $type; }
        if ($category !== ''){ $where[] = "Category = ?"; $typesStr .= 's'; $params[] = $category; }
        if (count($where) > 0){ $sql .= " WHERE ".implode(" AND ", $where); }
        $sql .= " ORDER BY Product, Serialno";
        $stmt = $this->conn->prepare($sql);
        if ($typesStr !== ''){
            $refs = [];
            $refs[] = &$typesStr;
            foreach ($params as $k => $v){ $refs[] = &$params[$k]; }
            call_user_func_array([$stmt,'bind_param'], $refs);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $totDP = 0.0; $totQty = 0.0; $totSRP = 0.0; $totMarkup = 0.0;
        while($row = $res->fetch_assoc()){
            $items[] = [
                "SIno"=>$row["SIno"],
                "Serialno"=>$row["Serialno"],
                "Product"=>$row["Product"],
                "Category"=>$row["Category"],
                "Branch"=>$row["Branch"],
                "Quantity"=>$row["Quantity"],
                "DealerPrice"=>$row["DealerPrice"],
                "TotalPrice"=>$row["TotalPrice"],
                "TotalSRP"=>$row["TotalSRP"],
                "TotalMarkup"=>$row["TotalMarkup"],
            ];
            $totDP += floatval($row["TotalPrice"] ?? 0);
            $totQty += floatval($row["Quantity"] ?? 0);
            $totSRP += floatval($row["TotalSRP"] ?? 0);
            $totMarkup += floatval($row["TotalMarkup"] ?? 0);
        }
        $stmt->close();
        echo json_encode([
            "items"=>$items,
            "totals"=>[
                "totalDP"=>$totDP,
                "totalQty"=>$totQty,
                "totalSRP"=>$totSRP,
                "totalMarkup"=>$totMarkup
            ]
        ]);
    }
    public function CancelConsignment($data){
        $items = json_decode($data['items'] ?? '[]', true);
        if (!is_array($items) || count($items) === 0){
            echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>"No items selected"]);
            return;
        }
        $this->conn->autocommit(false);
        $moved = 0; $deleted = 0;
        try {
            foreach ($items as $it){
                $sino = trim($it['sino'] ?? '');
                $serial = trim($it['serialno'] ?? '');
                $product = trim($it['product'] ?? '');
                $category = trim($it['category'] ?? '');
                $branch = trim($it['branch'] ?? '');
                if ($sino === '' || $product === '' || $category === ''){
                    continue;
                }
                $stock = '';
                $stmt0 = $this->conn->prepare("SELECT Stock FROM tbl_invlistconsign WHERE SIno = ? AND Serialno = ? AND Product = ? AND Category = ? AND Branch = ? LIMIT 1");
                $stmt0->bind_param('sssss', $sino, $serial, $product, $category, $branch);
                $stmt0->execute();
                $res0 = $stmt0->get_result();
                if ($row0 = $res0->fetch_assoc()){ $stock = $row0['Stock'] ?? ''; }
                $stmt0->close();
                $stmtIns = $this->conn->prepare("INSERT INTO tbl_invlistconsignhistory (SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname) SELECT SIno, Serialno, Product, Supplier, Category, Type, Quantity, DealerPrice, TotalPrice, SRP, TotalSRP, Markup, TotalMarkup, VatSales, Vat, AmountDue, DateAdded, DatePurchase, User, AsOf, ProdPend, Stock, Branch, Warranty, imgname FROM tbl_invlistconsign WHERE SIno = ? AND Serialno = ? AND Product = ? AND Category = ? AND Branch = ?".($stock!==''?" AND Stock = ?":""));
                if ($stock !== ''){
                    $stmtIns->bind_param('ssssss', $sino, $serial, $product, $category, $branch, $stock);
                } else {
                    $stmtIns->bind_param('sssss', $sino, $serial, $product, $category, $branch);
                }
                $stmtIns->execute();
                $moved += max(0, intval($stmtIns->affected_rows));
                $stmtIns->close();
                $stmtDel = $this->conn->prepare("DELETE FROM tbl_invlistconsign WHERE SIno = ? AND Serialno = ? AND Product = ? AND Category = ? AND Branch = ?".($stock!==''?" AND Stock = ?":""));
                if ($stock !== ''){
                    $stmtDel->bind_param('ssssss', $sino, $serial, $product, $category, $branch, $stock);
                } else {
                    $stmtDel->bind_param('sssss', $sino, $serial, $product, $category, $branch);
                }
                $stmtDel->execute();
                $deleted += max(0, intval($stmtDel->affected_rows));
                $stmtDel->close();
            }
            $this->conn->commit();
            $this->conn->autocommit(true);
            echo json_encode(["STATUS"=>"success","MESSAGE"=>"Cancelled consignment items","MOVED"=>$moved,"DELETED"=>$deleted]);
        } catch (\Throwable $e){
            $this->conn->rollback();
            $this->conn->autocommit(true);
            echo json_encode(["STATUS"=>"ERROR","MESSAGE"=>$e->getMessage()]);
        }
    }
}
