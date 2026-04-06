<?php
/**
 * Loanable Products Manager
 * 
 * Simple UI to view and manage which products can be loaned
 * without requiring database changes
 */

session_start();
require_once(__DIR__ . '/../../includes/product_loan_helper.php');

// Check if user is logged in (adjust based on your auth system)
if (!isset($_SESSION['USERNAME'])) {
    header('Location: ../../login.php');
    exit;
}

// Get current configuration
$config = require(__DIR__ . '/../../config/loanable_products.php');
$loanableProducts = $config['products'];
$loanableCodes = $config['codes'];
$loanableCategories = $config['categories'];

// Get all products from database for comparison
require_once(__DIR__ . '/../../database/connection.php');
$db = new Database();
$allProducts = $db->SelectQuery("SELECT DISTINCT Product FROM tbl_loansetup ORDER BY Product ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loanable Products Manager</title>
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/fontawesome/css/all.min.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .badge-loanable { background: #28a745; }
        .badge-not-loanable { background: #6c757d; }
        .config-box { background: #f8f9fa; padding: 20px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #007bff; }
        .product-card { border: 1px solid #dee2e6; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .product-card.loanable { border-left: 4px solid #28a745; }
        .product-card.not-loanable { border-left: 4px solid #dc3545; }
        .instructions { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .code-block { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 14px; }
        .section { margin: 30px 0; }
        h2 { color: #495057; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-card { flex: 1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-card h3 { margin: 0; font-size: 36px; }
        .stat-card p { margin: 5px 0 0 0; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-cog"></i> Loanable Products Manager</h1>
            <a href="../../dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><?php echo count($allProducts); ?></h3>
                <p>Total Products</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?php echo count($loanableProducts); ?></h3>
                <p>Loanable Products</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><?php echo count($allProducts) - count($loanableProducts); ?></h3>
                <p>Not Loanable</p>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <h4><i class="fas fa-info-circle"></i> How to Manage Loanable Products</h4>
            <p>To add or remove products from the loanable list, edit the configuration file:</p>
            <p><strong>File Location:</strong> <code>config/loanable_products.php</code></p>
            <ol>
                <li>Open the file in a text editor</li>
                <li>Add or remove product names from the <code>$loanableProducts</code> array</li>
                <li>Save the file</li>
                <li>Refresh this page to see changes</li>
            </ol>
        </div>

        <!-- Current Configuration -->
        <div class="section">
            <h2><i class="fas fa-list"></i> Current Loanable Products Configuration</h2>
            <div class="config-box">
                <h5>Products Allowed for Loan:</h5>
                <div class="code-block">
<?php
echo '$loanableProducts = [' . "\n";
foreach ($loanableProducts as $product) {
    echo "    '" . htmlspecialchars($product) . "',\n";
}
echo '];';
?>
                </div>
            </div>
        </div>

        <!-- All Products Status -->
        <div class="section">
            <h2><i class="fas fa-th-list"></i> All Products Status</h2>
            <div class="row">
                <?php foreach ($allProducts as $product): 
                    $productName = $product['Product'];
                    $isLoanable = isProductLoanable($productName);
                    $cardClass = $isLoanable ? 'loanable' : 'not-loanable';
                    $badgeClass = $isLoanable ? 'badge-loanable' : 'badge-not-loanable';
                    $icon = $isLoanable ? 'fa-check-circle' : 'fa-times-circle';
                    $status = $isLoanable ? 'Loanable' : 'Not Loanable';
                ?>
                <div class="col-md-6">
                    <div class="product-card <?php echo $cardClass; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($productName); ?></h5>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <i class="fas <?php echo $icon; ?>"></i> <?php echo $status; ?>
                                </span>
                            </div>
                            <?php if ($isLoanable): ?>
                                <i class="fas fa-check-circle text-success" style="font-size: 24px;"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger" style="font-size: 24px;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Edit Guide -->
        <div class="section">
            <h2><i class="fas fa-edit"></i> Quick Edit Guide</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-plus"></i> To Add a Product</h5>
                        </div>
                        <div class="card-body">
                            <p>Open <code>config/loanable_products.php</code> and add:</p>
                            <div class="code-block">
$loanableProducts = [<br>
&nbsp;&nbsp;&nbsp;&nbsp;'REGULAR LOAN',<br>
&nbsp;&nbsp;&nbsp;&nbsp;'EMERGENCY LOAN',<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #50fa7b;">'NEW PRODUCT NAME',</span> // Add here<br>
];
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-minus"></i> To Remove a Product</h5>
                        </div>
                        <div class="card-body">
                            <p>Open <code>config/loanable_products.php</code> and delete the line:</p>
                            <div class="code-block">
$loanableProducts = [<br>
&nbsp;&nbsp;&nbsp;&nbsp;'REGULAR LOAN',<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #ff5555; text-decoration: line-through;">'PRODUCT TO REMOVE',</span> // Delete this<br>
&nbsp;&nbsp;&nbsp;&nbsp;'EMERGENCY LOAN',<br>
];
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Product -->
        <div class="section">
            <h2><i class="fas fa-vial"></i> Test Product Eligibility</h2>
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label for="testProduct">Enter Product Name:</label>
                        <input type="text" id="testProduct" class="form-control" placeholder="e.g., REGULAR LOAN">
                    </div>
                    <button onclick="testProduct()" class="btn btn-primary">
                        <i class="fas fa-check"></i> Test Eligibility
                    </button>
                    <div id="testResult" class="mt-3"></div>
                </div>
            </div>
        </div>

        <!-- File Location Info -->
        <div class="section">
            <h2><i class="fas fa-folder-open"></i> File Locations</h2>
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>File</th>
                        <th>Purpose</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fas fa-file-code"></i> Configuration</td>
                        <td>Define loanable products</td>
                        <td><code>config/loanable_products.php</code></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-code"></i> Helper Functions</td>
                        <td>Check eligibility functions</td>
                        <td><code>includes/product_loan_helper.php</code></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-code"></i> API Endpoint</td>
                        <td>AJAX eligibility check</td>
                        <td><code>api/check_product_eligibility.php</code></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-alt"></i> Documentation</td>
                        <td>Implementation guide</td>
                        <td><code>LOANABLE_PRODUCTS_IMPLEMENTATION.md</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../../assets/jquery/jquery.min.js"></script>
    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
        function testProduct() {
            const productName = document.getElementById('testProduct').value.trim();
            
            if (!productName) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Please enter a product name',
                    text: 'Enter a product name to test'
                });
                return;
            }
            
            $.ajax({
                url: '../../api/check_product_eligibility.php',
                type: 'GET',
                data: { product: productName },
                dataType: 'JSON',
                success: function(response) {
                    const resultDiv = document.getElementById('testResult');
                    
                    if (response.eligible) {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Product is Loanable!</h5>
                                <p><strong>${productName}</strong> can be used for loans.</p>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-times-circle"></i> Product is Not Loanable</h5>
                                <p><strong>${productName}</strong> cannot be used for loans.</p>
                                <p class="mb-0"><small>To make it loanable, add it to <code>config/loanable_products.php</code></small></p>
                            </div>
                        `;
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to check product eligibility'
                    });
                }
            });
        }
        
        // Allow Enter key to test
        document.getElementById('testProduct').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                testProduct();
            }
        });
    </script>
</body>
</html>
