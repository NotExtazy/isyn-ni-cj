<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>

<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('../../includes/pages.header.php');
    ?>
      <link rel="stylesheet" href="../../assets/datetimepicker/jquery.datetimepicker.css">
      <link rel="stylesheet" href="../../assets/select2/css/select2.min.css">

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- loader END -->

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

    <style>
        :root {
            --primary-color: #0d6efd;
            --bg-color: #f4f6f9;
            --card-bg: #ffffff;
            --text-color: #333;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --radius-md: 10px;
            --radius-lg: 15px;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        .custom-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: none;
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .section-title {
            font-weight: 700;
            color: var(--text-color);
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .form-label {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }

        .form-control[readonly] {
            background-color: #f8f9fa;
            border-color: #eee;
            color: #555;
            font-weight: 500;
        }

        /* Table Styling */
        .table-container {
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 12px 15px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.03);
        }

        .table tr.selected {
            background-color: rgba(13, 110, 253, 0.1) !important;
            color: var(--primary-color) !important;
            font-weight: 600;
        }

        /* DataTables Customization */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.3rem 0.6rem !important;
            margin-left: 2px !important;
            border-radius: 6px !important;
            border: 1px solid var(--border-color) !important;
            background: #fff !important;
            color: var(--text-color) !important;
            font-size: 0.85rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #f8f9fa !important;
            border-color: #dee2e6 !important;
            color: var(--primary-color) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Button Styling */
        .btn {
            border-radius: var(--radius-md);
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary { background-color: var(--primary-color); border: none; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2); }
        
        .btn-success { background-color: #198754; border: none; }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(25, 135, 84, 0.2); }

        .btn-danger { background-color: #dc3545; border: none; }
        .btn-danger:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2); }

        /* Select2 Styling */
        .select2-container--default .select2-selection--single {
            border: 1px solid var(--border-color) !important;
            border-radius: var(--radius-md) !important;
            height: 42px !important;
            padding: 6px 12px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
        }
    </style>

    <div class="container-fluid mt-4">
        <div class="custom-card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="mb-0 fw-bold text-primary">
                    <i class="fa-solid fa-arrows-rotate me-2"></i>Change of Product SRP/DP
                </h4>
            </div>
        </div>

        <!-- Search Product and Current Inventory -->
        <div class="row">
            <div class="col-md-12">
                <div class="custom-card p-4">
                    <div class="section-title">
                        <i class="fa-solid fa-magnifying-glass"></i> Search Product & Current Inventory
                    </div>
                    
                    <!-- Search Filters -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Update Type</label>
                            <select id="updateType" name="updateType" class="form-select" onchange="GenTableHeader(this.value);">
                                <option value="" selected disabled>Select</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Isyn Branch</label>
                            <select id="isynBranch" name="isynBranch" class="form-select">
                                <option value="" selected disabled>Select</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="type" onchange="LoadCategory(this.value);">
                                <option value="" selected disabled>Select</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select id="category" name="category" class="form-select">
                                <option value="" selected disabled>Select</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" id="searchInventory" class="btn btn-primary" onclick="SearchInventory();">
                                <i class="fa-solid fa-magnifying-glass"></i> Search Inventory
                            </button>
                        </div>
                    </div>

                    <hr class="my-4 opacity-50">

                    <!-- Inventory Table -->
                    <div class="table-container">
                        <table id="inventoryTbl" class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th style="width: 30%">Product</th>
                                    <th style="width: 15%">SI No.</th>
                                    <th style="width: 15%">Serial No.</th>
                                    <th style="width: 10%">Quantity</th>
                                    <th style="width: 15%">Dealer Price</th>
                                    <th style="width: 15%">SRP</th>
                                    <th>Supplier</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Branch</th>
                                </tr>
                            </thead>
                            <tbody id="inventoryList"></tbody>
                        </table>
                    </div>

                    <div class="row mt-4 align-items-end g-3">
                        <div class="col-md-3">
                            <label class="form-label">New Price</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">₱</span>
                                <input type="text" id="newPrice" class="form-control border-start-0 ps-0" placeholder="0.00" onchange="formatInput(this)" disabled>
                            </div>
                        </div>
                        <div class="col-md-9 text-end">
                            <button type="button" id="cancelBtn" class="btn btn-light border" onclick="CancelData();" disabled>
                                <i class="fa-solid fa-ban"></i> Cancel
                            </button>
                            <button type="button" id="addProduct" class="btn btn-success" onclick="AddProductData();" disabled>
                                <i class="fa-solid fa-plus"></i> Add to List
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row: Product Details and Product List -->
        <div class="row">
            <div class="col-md-4">
                <div class="custom-card p-4">
                    <div class="section-title">
                        <i class="fa-solid fa-circle-info"></i> Product Details
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">SI No</label>
                            <input type="text" id="sinoDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Serial No</label>
                            <input type="text" id="serialNoDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Product Name</label>
                            <input type="text" id="productDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Dealer Price</label>
                            <input type="text" id="dealerspriceDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Current SRP</label>
                            <input type="text" id="srpDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Supplier</label>
                            <input type="text" id="supplierDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Category</label>
                            <input type="text" id="categoryDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Type</label>
                            <input type="text" id="typeDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Branch</label>
                            <input type="text" id="branchDisplay" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Quantity</label>
                            <input type="number" id="quantityDisplay" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="custom-card p-4">
                    <div class="section-title">
                        <i class="fa-solid fa-list-check"></i> Pending Updates List
                    </div>
                    <div class="table-container">
                        <table id="productTbl" class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th style="width: 30%">Product</th>
                                    <th style="width: 15%">SI No.</th>
                                    <th style="width: 15%">Serial No.</th>
                                    <th style="width: 10%">Qty</th>
                                    <th style="width: 15%">Old Price</th>
                                    <th style="width: 15%">New Price</th>
                                    <th>Supplier</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Branch</th>
                                    <th>Other Price</th>
                                </tr>
                            </thead>
                            <tbody id="productList"></tbody>
                        </table>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-danger" id="removeButton" onclick="RemoveProduct();" disabled>
                            <i class="fa-regular fa-circle-xmark"></i> Remove Selected
                        </button>
                        <button type="button" class="btn btn-primary" onclick="UpdateProduct();">
                            <i class="fa-solid fa-check-double"></i> Apply Updates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
        
        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/datetimepicker/jquery.datetimepicker.full.js"></script>
        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/inventorymanagement/changeproductsrpdp.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>