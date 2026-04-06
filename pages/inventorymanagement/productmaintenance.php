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
                    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                }

                /* Card Styling */
                .custom-card {
                    background-color: var(--card-bg);
                    border-radius: var(--radius-lg);
                    box-shadow: var(--shadow-sm);
                    border: 1px solid rgba(0,0,0,0.02);
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    margin-bottom: 1.5rem;
                }

                .custom-card:hover {
                    box-shadow: var(--shadow-md);
                }

                .card-header-title {
                    color: var(--primary-color);
                    font-weight: 600;
                    font-size: 1.1rem;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-bottom: 1rem;
                }

                .section-title {
                    font-weight: 600;
                    color: var(--text-color);
                    margin-bottom: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                /* Form Elements */
                .form-label {
                    font-weight: 600;
                    font-size: 0.85rem;
                    color: var(--text-muted);
                    margin-bottom: 0.3rem;
                    text-transform: uppercase;
                }

                .form-control, .form-select {
                    border-radius: var(--radius-md);
                    border: 1px solid #dee2e6;
                    padding: 0.6rem 0.8rem;
                    font-size: 0.95rem;
                    transition: all 0.2s;
                }

                .form-control:focus, .form-select:focus {
                    border-color: var(--primary-color);
                    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
                }

                /* Table Styling */
                .table-container {
                    border-radius: var(--radius-md);
                    border: 1px solid var(--border-color);
                    background-color: var(--card-bg);
                    overflow: hidden;
                }

                /* DataTables Customization */
                .dataTables_wrapper .dataTables_length {
                    margin-bottom: 1rem;
                }

                .dataTables_wrapper .dataTables_length select {
                    border-radius: var(--radius-md);
                    border: 1px solid var(--border-color);
                    padding: 4px 8px;
                    margin: 0 5px;
                    outline: none;
                }

                .dataTables_wrapper .dataTables_filter {
                    margin-bottom: 1rem;
                }

                .dataTables_wrapper .dataTables_filter input {
                    border-radius: var(--radius-md);
                    border: 1px solid var(--border-color);
                    padding: 6px 12px;
                    margin-left: 10px;
                    outline: none;
                }

                .dataTables_wrapper .dataTables_paginate {
                    padding-top: 1rem;
                    display: flex;
                    justify-content: flex-end;
                    gap: 2px;
                }

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

                .table {
                    margin-bottom: 0;
                    width: 100% !important;
                }

                .table th {
                    background-color: #f8f9fa;
                    color: #495057;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 0.75rem;
                    letter-spacing: 0.5px;
                    padding: 12px 15px;
                    border-bottom: 2px solid var(--border-color);
                }

                .table td {
                    padding: 10px 15px;
                    vertical-align: middle;
                    border-bottom: 1px solid var(--border-color);
                    font-size: 0.9rem;
                }

                .table tbody tr:hover {
                    background-color: #f1f8ff;
                    cursor: pointer;
                }

                .table tbody tr.selected {
                    background-color: #e7f1ff;
                    border-left: 4px solid var(--primary-color);
                }

                /* Buttons */
                .btn {
                    border-radius: var(--radius-md);
                    padding: 0.6rem 1.2rem;
                    font-weight: 500;
                    transition: all 0.2s;
                }

                .btn-primary {
                    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2);
                }

                .btn-success {
                    box-shadow: 0 2px 4px rgba(25, 135, 84, 0.2);
                }

                .btn-danger {
                    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
                }

                /* Select2 Customization */
                .select2-container--default .select2-selection--single {
                    border: 1px solid #dee2e6 !important;
                    border-radius: var(--radius-md) !important;
                    height: 45px !important;
                    padding-top: 8px !important;
                }

                .select2-container--default .select2-selection--single .select2-selection__arrow {
                    height: 43px !important;
                }

                .select2-container--default .select2-selection--single .select2-selection__rendered {
                    line-height: 28px !important;
                    color: var(--text-color) !important;
                }

                .select2-container--default .select2-selection--single:focus {
                    border-color: var(--primary-color) !important;
                }
            </style>

            <div class="container-fluid mt-4">
                <div class="custom-card p-3 mb-4">
                    <div class="card-header-title">
                        <i class="fa-solid fa-boxes-stacked fs-4"></i> <span class="fs-5">Product Maintenance</span>
                    </div>
                </div>

                <div class="row">
                    <!-- Product List -->
                    <div class="col-md-7">
                        <div class="custom-card p-4">
                            <div class="section-title">
                                <i class="fa-solid fa-list text-primary"></i> Product List
                            </div>
                            <div class="table-container">
                                <table id="productTbl" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th style="width: 30%">Product</th>
                                            <th style="width: 25%">Supplier</th>
                                            <th style="width: 25%">Category</th>
                                            <th style="width: 20%">Type</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productList">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance Tools -->
                    <div class="col-md-5">
                        <div class="custom-card p-4">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="section-title mb-0">
                                    <i class="fa-solid fa-wrench text-primary"></i> Tools
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-success" type="button" id="addNew" onclick="AddNew()">
                                        <i class="fas fa-plus me-1"></i> New
                                    </button>
                                    <button class="btn btn-primary" type="button" id="editBtn" onclick="EditProduct()" disabled>
                                        <i class="fas fa-edit me-1"></i> Edit
                                    </button>
                                </div>
                            </div>
                            <hr class="my-4 opacity-10">
                            
                            <form method="POST" onsubmit="return false;">
                                <input type="hidden" name="id" id="ID">
                                <input type="hidden" id="refNo" name="refNo">
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="prodType" class="form-label">Product Type</label>
                                        <select class="form-select" name="prodType" id="prodType" disabled>
                                            <option value="" disabled selected></option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prodCateg" class="form-label">Product Category</label>
                                        <select class="form-select" name="prodCateg" id="prodCateg" disabled>
                                            <option value="" disabled selected></option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="supplier" class="form-label">Supplier</label>
                                        <select class="form-select" name="supplier" id="supplier" disabled>
                                            <option value="" disabled selected></option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label for="prodName" class="form-label">Product Name</label>
                                        <select class="form-select" name="prodName" id="prodName" disabled>
                                            <option value="" disabled selected></option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12 mt-5">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button class="btn btn-danger px-4" type="button" id="cancelBtn" onclick="Cancel();" disabled>
                                                <i class="fa-regular fa-circle-xmark me-1"></i> Cancel
                                            </button>
                                            <button class="btn btn-primary px-4" type="button" id="saveBtn" onclick="Save();" disabled>
                                                <i class="fa-solid fa-check-circle me-1"></i> Save Product
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <script src="../../assets/select2/js/select2.full.min.js"></script>
        <script src="../../js/inventorymanagement/productmaintenance.js?<?= time() ?>"></script>

    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>