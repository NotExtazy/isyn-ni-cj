<?php
include_once("../../database/connection.php");

class Process extends Database
{
    public function Initialize(){
        $List = $this->SelectQuery("SELECT * FROM tbl_prodtype");
        $ProdType = $this->SelectQuery("SELECT module as Type FROM tbl_maintenance_module WHERE module_no = 171 AND module_type = 3 AND status = 1 ORDER BY module");
        $ProdCateg = $this->SelectQuery("SELECT module as Category FROM tbl_maintenance_module WHERE module_no = 172 AND module_type = 3 AND status = 1 ORDER BY module");
        $Supplier = $this->SelectQuery("SELECT module as supplierName FROM tbl_maintenance_module WHERE module_no = 173 AND module_type = 3 AND status = 1 ORDER BY module");
        $Product = $this->SelectQuery("SELECT module as Product FROM tbl_maintenance_module WHERE module_no = 174 AND module_type = 3 AND status = 1 ORDER BY module");

        echo json_encode(array( 
            "LIST" => $List,
            "PRODTYPE" => $ProdType,
            "PRODCATEG" => $ProdCateg,
            "SUPPLIER" => $Supplier,
            "PRODUCT" => $Product,
        ));
    }

    public function SaveProduct($data){
        $state = $data['state'];
        $refNo = $data['refNo'];
        $prodType = $data['prodType'];
        $prodCateg = $data['prodCateg'];
        $supplier = $data['supplier'];
        $product = $data['product'];

        if ($state == "ADD") {
            $stmt = $this->conn->prepare("SELECT * FROM tbl_prodtype WHERE Category = ? AND Type = ? AND Product = ? AND Supplier = ?");
            $stmt->bind_param('ssss', $prodCateg, $prodType, $product, $supplier);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            if ($result->num_rows > 0) {
                $stmt = $this->conn->prepare("INSERT INTO tbl_prodtype (Category, Type, Product, Supplier) VALUES (?,?,?,?)");
                $stmt->bind_param('ssss', $prodCateg, $prodType, $product, $supplier);
                $stmt->execute();
                $stmt->close();

                $status = "SUCCESS";
                $message = "Added new product information.";
            } else {
                $status = "ERROR";
                $message = "Product information already exist.";
            }


        } else if ($state == "EDIT") {
            $stmt = $this->conn->prepare("SELECT * FROM tbl_prodtype WHERE Category = ? AND Type = ? AND Product = ? AND Supplier = ?");
            $stmt->bind_param('ssss', $prodCateg, $prodType, $product, $supplier);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            if ($result->num_rows > 0) {
                $stmt = $this->conn->prepare("UPDATE tbl_prodtype SET Category = ?, Type = ?, Product = ?, Supplier = ? WHERE ID = ?");
                $stmt->bind_param('sssss', $prodCateg, $prodType, $product, $supplier, $refNo);
                $stmt->execute();
                $stmt->close();
    
                $status = "SUCCESS";
                $message = "Updated product information.";
            } else {
                $status = "ERROR";
                $message = "Product information already exist.";
            }
        } else {
            $status = "ERROR";
            $message = "Unknown Action. Please try again.";
        }

        echo json_encode(array( 
            "STATUS" => $status,
            "MESSAGE" => $message,
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
