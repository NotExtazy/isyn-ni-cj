<?php
include_once("../../../database/connection.php");
$db = new Database();
$conn = $db->conn;

$SIno    = trim($_POST['SIno']     ?? '');
$Serialno= $_POST['Serialno']      ?? '';   // Do NOT trim — '0' and '-' are valid values
$Product = $_POST['Product']       ?? '';   // Do NOT trim — DB stores with leading space

$response = ["error" => "Product not found"];

// Only SIno + Product are required; Serialno is optional for non-serialized items
if ($SIno !== '' && $Product !== '') {

    // Use TRIM() in SQL so leading/trailing whitespace in DB columns never breaks the match
    $sql    = "SELECT * FROM tbl_invlist WHERE TRIM(SIno) = ? AND TRIM(Product) = ?";
    $types  = "ss";
    $params = [trim($SIno), trim($Product)];   // trim the lookup values themselves

    // Include Serialno filter only when it was actually sent AND is not empty string
    // Note: '0' and '-' are valid Serialno values — use strict !== '' check, NOT empty()
    if ($Serialno !== '') {
        $sql   .= " AND TRIM(Serialno) = ?";
        $types .= "s";
        $params[] = trim($Serialno);
    }

    $sql .= " LIMIT 1";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Ensure lowercase 'product' key that the JS expects
            $row['product'] = $row['Product'];
            $response = $row;
        }
        $stmt->close();
    }
}

echo json_encode($response);
?>
