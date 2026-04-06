<?php
require_once('../../../database/connection.php');

header('Content-Type: application/json');

$db = new Database();
$conn = $db->conn;

$branch = $_POST['branch'] ?? '';
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';

// Build dynamic query
$sql = "SELECT Product, SIno, Serialno, Quantity FROM tbl_invlist WHERE 1=1";
$types = "";
$params = [];

if (!empty($branch)) {
    $sql .= " AND Branch = ?";
    $types .= "s";
    $params[] = $branch;
}

if (!empty($type)) {
    $sql .= " AND Type = ?";
    $types .= "s";
    $params[] = $type;
}

if (!empty($category)) {
    $sql .= " AND Category = ?";
    $types .= "s";
    $params[] = $category;
}

$sql .= " ORDER BY Product ASC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'Product' => $row['Product'],
            'SIno' => $row['SIno'],
            'Serialno' => $row['Serialno'],
            'Quantity' => $row['Quantity']
        ];
    }
    
    echo json_encode($data);
    $stmt->close();
} else {
    echo json_encode(['error' => 'Database error']);
}
?>