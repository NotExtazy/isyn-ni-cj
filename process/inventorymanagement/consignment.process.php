<?php
include_once("../../database/connection.php");

class Process extends Database
{
    // 1. Initialize Dropdowns (Replaces inline PHP in consignment.php)
    public function Initialize() {
        $data = [
            'branches' => [],
            'isynBranches' => [],
            'types' => [],
            'categoriesWithVAT' => [],
            'categoriesNonVAT' => []
        ];

        // Branches
        $res = $this->conn->query("SELECT DISTINCT ItemName FROM tbl_maintenance WHERE ItemType='BRANCH' ORDER BY ItemName ASC");
        while ($row = $res->fetch_assoc()) $data['branches'][] = $row['ItemName'];

        // ISYN Branches
        $res = $this->conn->query("SELECT DISTINCT Branch FROM tbl_invlist ORDER BY Branch ASC");
        while ($row = $res->fetch_assoc()) $data['isynBranches'][] = $row['Branch'];

        // Types
        $res = $this->conn->query("SELECT DISTINCT Type FROM tbl_invlist ORDER BY Type ASC");
        while ($row = $res->fetch_assoc()) $data['types'][] = $row['Type'];

        // Categories
        $res = $this->conn->query("SELECT DISTINCT Category, UPPER(Type) as Type FROM tbl_invlist ORDER BY Category ASC");
        while ($row = $res->fetch_assoc()) {
            if ($row['Type'] == 'WITH VAT') $data['categoriesWithVAT'][] = $row['Category'];
            if ($row['Type'] == 'NON-VAT') $data['categoriesNonVAT'][] = $row['Category'];
        }

        echo json_encode($data);
    }

    // 2. Fetch Items for Selection (Replaces fetch_items.php)
    public function FetchItems($post) {
        $options = [];
        $type = $post['type'] ?? '';
        $category = $post['category'] ?? '';
        
        // Scenario A: Fetch SI Numbers based on selected Product/Serial
        if (isset($post['selectedOption'])) {
            $selectedOption = $post['selectedOption'];
            // Check if it matches Serial or Product
            $sql = "SELECT DISTINCT SIno FROM tbl_invlist WHERE (Product = ? OR Serialno = ?) AND Category = ? AND Type = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssss", $selectedOption, $selectedOption, $category, $type);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $options[] = $row['SIno'];
            }
            echo json_encode(['SIno' => $options]);
        } 
        // Scenario B: Fetch Products or Serials based on Category/Type
        else {
            $selectField = (isset($post['inputType']) && $post['inputType'] == 'serial') ? 'Serialno' : 'Product';
            $sql = "SELECT DISTINCT $selectField FROM tbl_invlist WHERE Category = ? AND Type = ? ORDER BY $selectField ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $category, $type);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $options[] = $row[$selectField];
            }
            echo json_encode(['options' => $options]);
        }
    }

    // 3. Load Product Summary (Replaces consignment-product-summary.php)
    public function LoadProductSummary($post) {
        $SIno = trim($post['SIno']);
        $Serialno = trim($post['Serialno'] ?? '');
        $Product = trim($post['Product'] ?? '');

        $sql = "SELECT * FROM tbl_invlist WHERE TRIM(SIno) = ?";
        $params = [$SIno];
        $types = "s";

        if (!empty($Product)) {
            $sql .= " AND TRIM(Product) = ?";
            $params[] = $Product;
            $types .= "s";
        } elseif (!empty($Serialno)) {
            $sql .= " AND TRIM(Serialno) = ?";
            $params[] = $Serialno;
            $types .= "s";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode([
                'Serialno' => $row['Serialno'],
                'SIno' => $row['SIno'],
                'product' => $row['Product'],
                'Supplier' => $row['Supplier'],
                'SRP' => $row['SRP'],
                'Quantity' => $row['Quantity'],
                'DealerPrice' => $row['DealerPrice'],
                'TotalPrice' => $row['TotalPrice']
            ]);
        } else {
            echo json_encode(['error' => 'Product not found']);
        }
    }

    // 4. Save Consignment (Replaces save-consignment.php)
    public function SaveConsignment($data) {
        try {
            $this->conn->autocommit(false);
            
            $user = $_SESSION['USERNAME'] ?? 'SYSTEM';
            $dateAdded = date('m/d/Y');
            
            $stmt = $this->conn->prepare("INSERT INTO tbl_invconsignin 
                (Quantity, Product, DealerPrice, TotalPrice, SRP, TotalSRP, TotalMarkup, VatSales, Vat, AmountDue, Stock, Branch, Type, Category, Supplier_SI, SIno, Supplier, DateAdded, User) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($data as $item) {
                // Sanitize and format numbers
                $qty = $item['quantity'];
                $dp = str_replace(',', '', $item['dealersPrice']);
                $tp = str_replace(',', '', $item['totalPrice']);
                $srp = str_replace(',', '', $item['srp']);
                $tsrp = str_replace(',', '', $item['totalSRP']);
                $mk = str_replace(',', '', $item['markup']);
                $vs = str_replace(',', '', $item['vatsale']);
                $vt = str_replace(',', '', $item['vat']);
                $ad = str_replace(',', '', $item['amountDue']);
                
                $stmt->bind_param("sssssssssssssssssss", 
                    $qty, $item['product'], $dp, $tp, $srp, $tsrp, $mk, $vs, $vt, $ad, 
                    $item['stock'], $item['isynBranch'], $item['type'], $item['category'], 
                    $item['supplier_si'], $item['SIno'], $item['supplier'], $dateAdded, $user
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Error saving item: " . $stmt->error);
                }
            }

            $this->conn->commit();
            echo json_encode(['success' => true, 'message' => 'Consignment saved successfully!']);
        } catch (Exception $e) {
            $this->conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>