<?php
/**
 * Check Product Loan Eligibility API
 * 
 * This API endpoint checks if a product is eligible for loan
 * without requiring database columns.
 * 
 * Usage: GET /api/check_product_eligibility.php?product=PRODUCT_NAME
 */

header('Content-Type: application/json');

// Include the helper
require_once(__DIR__ . '/../includes/product_loan_helper.php');

// Get product name from request
$productName = $_GET['product'] ?? $_POST['product'] ?? '';

if (empty($productName)) {
    echo json_encode([
        'success' => false,
        'eligible' => false,
        'message' => 'Product name is required',
        'product' => ''
    ]);
    exit;
}

// Check eligibility
$validation = validateProductForLoan($productName);

// Return JSON response
echo json_encode([
    'success' => true,
    'eligible' => $validation['valid'],
    'message' => $validation['message'],
    'product' => $productName,
    'badge' => getProductEligibilityBadge($productName)
]);
?>
