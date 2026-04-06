<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>
<!doctype html>
<html lang="en" dir="ltr">
    <?php
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $pagesPos = strpos($scriptDir, '/pages');
        if ($pagesPos !== false) {
            $base = substr($scriptDir, 0, $pagesPos);
        } else {
            $base = rtrim($scriptDir, '/\\');
        }
        if ($base === '.' ) { $base = ''; }
        include(dirname(__DIR__, 2) . '/includes/pages.header.php');
    ?>

    <body class="  ">
        <!-- loader Start -->
        <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
        </div>
        <!-- Datetimepicker CSS -->
        <link rel="stylesheet" href="<?php echo $base; ?>/assets/datetimepicker/jquery.datetimepicker.css">
        <!-- Add Google Font -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <style>
            :root {
                --primary-color: #435ebe; /* Royal Blue */
                --secondary-color: #6c757d;
                --success-color: #198754;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
                --info-color: #0dcaf0;
                --background-color: #f2f7ff; /* Very Light Blue-Gray */
                --card-bg: #ffffff;
                --text-main: #25396f;
                --text-secondary: #7c8db5;
                --border-color: #eef2f6;
                --input-bg: #f8f9fa;
            }

            body {
                background-color: var(--background-color);
                font-family: 'Inter', sans-serif;
                color: var(--text-main);
            }

            /* Layout & Spacing */
            .main-container {
                padding: 2rem;
            }

            /* Cards */
            .card-modern {
                background: var(--card-bg);
                border: none;
                border-radius: 16px;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.03);
                margin-bottom: 1.5rem;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                height: 100%;
            }
            
            .card-header-modern {
                background: transparent;
                border-bottom: 1px solid var(--border-color);
                padding: 1.25rem 1.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .card-title-modern {
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--text-main);
                margin: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .card-body-modern {
                padding: 1.5rem;
            }

            /* Form Elements */
            .form-label {
                font-weight: 600;
                font-size: 0.85rem;
                color: var(--text-secondary);
                margin-bottom: 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .form-control, .form-select {
                background-color: var(--input-bg);
                border: 1px solid var(--border-color);
                border-radius: 8px;
                padding: 0.6rem 1rem;
                font-size: 0.95rem;
                font-weight: 500;
                color: var(--text-main);
                transition: all 0.2s ease;
            }
            
            .form-control:focus, .form-select:focus {
                background-color: #fff;
                border-color: var(--primary-color);
                box-shadow: 0 0 0 4px rgba(67, 94, 190, 0.1);
            }

            /* Buttons */
            .btn {
                border-radius: 8px;
                padding: 0.5rem 1rem;
                font-weight: 600;
                font-size: 0.9rem;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s;
                border: none;
            }
            
            .btn:active {
                transform: scale(0.98);
            }

            .btn-primary {
                background-color: var(--primary-color);
                box-shadow: 0 4px 12px rgba(67, 94, 190, 0.3);
            }
            
            .btn-warning {
                background-color: var(--warning-color);
                color: #fff;
                box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
            }

            .btn-danger {
                background-color: var(--danger-color);
                box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            }

            /* Tables */
            .table-responsive-custom {
                border-radius: 12px;
                overflow: hidden;
                border: 1px solid var(--border-color);
            }

            .table-custom {
                width: 100%;
                margin-bottom: 0;
                border-collapse: separate;
                border-spacing: 0;
            }

            .table-custom thead th {
                background-color: #f8f9fa;
                color: var(--text-secondary);
                font-weight: 700;
                text-transform: uppercase;
                font-size: 0.75rem;
                padding: 1rem;
                border-bottom: 2px solid var(--border-color);
                letter-spacing: 0.5px;
                white-space: nowrap;
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .table-custom tbody td {
                padding: 1rem;
                vertical-align: middle;
                border-bottom: 1px solid var(--border-color);
                color: var(--text-main);
                font-size: 0.9rem;
                background-color: #fff;
            }

            .table-custom tbody tr:hover td {
                background-color: #f1f5f9;
                cursor: pointer;
            }
            
            .table-custom tbody tr.selected td {
                background-color: #eef2ff !important;
                color: var(--primary-color);
                font-weight: 600;
            }

            /* Header Section */
            .page-header-container {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0;
            }
            
            .page-heading {
                font-size: 1.75rem;
                font-weight: 800;
                color: var(--text-main);
                letter-spacing: -0.5px;
                margin: 0;
            }
            
            .breadcrumb-modern {
                color: var(--text-secondary);
                font-size: 0.9rem;
                margin: 0;
            }
            
            /* Custom for Totals Table */
            #consignTable tr td {
                padding: 1rem;
                border-bottom: 1px solid var(--border-color);
            }
            #consignTable tr:last-child td {
                border-bottom: none;
            }
            #consignTable tr td:first-child {
                font-weight: 600;
                color: var(--text-secondary);
            }
            #consignTable tr td:last-child {
                font-weight: 700;
                color: var(--text-main);
                text-align: right;
                font-size: 1.1rem;
            }
        </style>

        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.sidebar.php');
            include(dirname(__DIR__, 2) . '/includes/pages.navbar.php');
        ?>

        <div class="container-fluid main-container">
            
            <!-- Page Header -->
            <div class="card-modern">
                <div class="card-body-modern py-3">
                    <div class="page-header-container">
                        <div>
                            <h1 class="page-heading mb-1">Cancel Consignment</h1>
                            <p class="breadcrumb-modern">Manage and cancel product consignments</p>
                        </div>
                        <div class="d-none d-md-block">
                            <i class="fa-solid fa-file-circle-xmark fa-3x text-secondary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Search Section -->
                <div class="col-lg-4">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-magnifying-glass text-primary"></i> Search Product</h5>
                        </div>
                        <div class="card-body-modern">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="isynBranch" class="form-label">ISYN Branch</label>
                                    <select id="isynBranch" name="isynBranch" class="form-select"></select>
                                </div>
                                <div class="col-12">
                                    <label for="type" class="form-label">Type</label>
                                    <select class="form-select" aria-label="type" required name="productType[]" id="type"></select>
                                </div>
                                <div class="col-12">
                                    <label for="category" class="form-label">Category</label>
                                    <select id="category" name="category" class="form-select"></select>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="button" class="btn btn-primary w-100" id="search-btn">
                                        <i class="fa-solid fa-search"></i> Search Records
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totals Section -->
                <div class="col-lg-8">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-calculator text-success"></i> Summary Totals</h5>
                        </div>
                        <div class="card-body-modern p-0">
                            <div class="table-responsive-custom border-0">
                                <table class="table table-custom" id="consignTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 60%">Description</th>
                                            <th style="width: 40%; text-align: right;">Amount / Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tableList">
                                        <tr>
                                            <td>Total DP</td>
                                            <td id="displayTotalDP">0</td>
                                        </tr>
                                        <tr>
                                            <td>Total Qty</td>
                                            <td id="displayTotalQty">0</td>
                                        </tr>
                                        <tr>
                                            <td>Total SRP</td>
                                            <td id="displayTotalSRP">0</td>
                                        </tr>
                                        <tr>
                                            <td>Total Markup</td>
                                            <td id="displayTotalMarkup">0</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product List Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card-modern">
                        <div class="card-header-modern">
                            <h5 class="card-title-modern"><i class="fa-solid fa-list text-info"></i> Product List</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-warning text-white btn-sm" id="removeButton" type="button" onclick="disableData()">
                                    <i class="fa-solid fa-xmark"></i> Remove Selected
                                </button>
                                <button class="btn btn-danger btn-sm" id="cancelButton" type="button" onclick="cancelConsignment()">
                                    <i class="fa-solid fa-trash-can"></i> Cancel Consignment
                                </button>
                            </div>
                        </div>
                        <div class="card-body-modern p-0">
                            <div class="table-responsive-custom" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-custom table-hover" id="list">
                                    <thead>
                                        <tr>
                                            <th style="width:12%">SI No.</th>
                                            <th style="width:15%">Serial No.</th>
                                            <th style="width:19%">Product</th>
                                            <th style="width:15%">Category</th>
                                            <th style="width:15%">Branch</th>
                                            <th style="width:12%">Quantity</th>
                                            <th style="width:12%">Dealer Price</th>
                                        </tr>
                                    </thead>
                                    <tbody id="searchResult">
                                        <!-- Rows will be populated here -->
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="fa-solid fa-box-open fa-3x mb-3 d-block opacity-25"></i>
                                                <span class="d-block">Use the search filter to display products</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
            include(dirname(__DIR__, 2) . '/includes/pages.footer.php');
        ?>

        <script>
            $(function(){
                $.ajax({
                    url: "<?php echo $base; ?>/routes/inventorymanagement/cancelconsignment.route.php",
                    type: "POST",
                    data: { action: "Initialize" },
                    dataType: "JSON",
                    success: function(res){
                        var $b = $("#isynBranch"), $t = $("#type"), $c = $("#category");
                        $b.html('<option value="" disabled selected>Select</option><option value="OVERALL">OVERALL</option>');
                        (res.branches||[]).forEach(function(v){ $b.append('<option value="'+v+'">'+v+'</option>'); });
                        $t.html('<option value="" disabled selected>Select</option><option value="OVERALL">OVERALL</option>');
                        (res.types||[]).forEach(function(v){ $t.append('<option value="'+v+'">'+v+'</option>'); });
                        $c.html('<option value="" disabled selected>Select</option><option value="OVERALL">OVERALL</option>');
                        (res.categories||[]).forEach(function(v){ $c.append('<option value="'+v+'">'+v+'</option>'); });
                    }
                });
                $("#isynBranch").on("change", function(){
                    var branch = $(this).val() || "";
                    $.ajax({
                        url: "<?php echo $base; ?>/routes/inventorymanagement/cancelconsignment.route.php",
                        type: "POST",
                        data: { action: "LoadTypes", branch: branch },
                        dataType: "JSON",
                        success: function(res){
                            var $t = $("#type");
                            $t.html('<option value="" disabled selected>Select</option><option value="OVERALL">OVERALL</option>');
                            (res.types||[]).forEach(function(v){ $t.append('<option value="'+v+'">'+v+'</option>'); });
                            $("#category").html('<option value="" disabled selected>Select</option><option value="OVERALL">OVERALL</option>');
                        }
                    });
                });
                $("#type").on("change", function(){
                    var branch = $("#isynBranch").val() || "";
                    var type = $(this).val() || "";
                    $.ajax({
                        url: "<?php echo $base; ?>/routes/inventorymanagement/cancelconsignment.route.php",
                        type: "POST",
                        data: { action: "LoadCategories", branch: branch, type: type },
                        dataType: "JSON",
                        success: function(res){
                            var $c = $("#category");
                            $c.html('<option value="" disabled selected>Select</option><option value="OVERALL">OVERALL</option>');
                            (res.categories||[]).forEach(function(v){ $c.append('<option value="'+v+'">'+v+'</option>'); });
                        }
                    });
                });
            });
        </script>

        <script>
            $(document).ready(function() {
                $('#search-btn').click(function() {
                    var branch = $('#isynBranch').val() || '';
                    var type = $('#type').val() || '';
                    var category = $('#category').val() || '';
                    $.ajax({
                        method: 'POST',
                        url: "<?php echo $base; ?>/routes/inventorymanagement/cancelconsignment.route.php",
                        data: {
                            action: 'SearchProducts',
                            branch: branch,
                            type: type,
                            category: category
                        },
                        dataType: 'json',
                        success: function(response) {
                            var items = response.items || [];
                            var totals = response.totals || {};
                            
                            // Optimization: Build HTML string first, then inject once
                            if (items.length > 0) {
                                var rowsHtml = items.map(function(item){
                                    return '<tr ' +
                                        'data-total-dp="' + (item.TotalPrice || 0) + '" ' +
                                        'data-qty="' + (item.Quantity || 0) + '" ' +
                                        'data-total-srp="' + (item.TotalSRP || 0) + '" ' +
                                        'data-total-markup="' + (item.TotalMarkup || 0) + '">' +
                                        '<td class="SInoSelect">' + (item.SIno || '') + '</td>' +
                                        '<td>' + (item.Serialno || '') + '</td>' +
                                        '<td>' + (item.Product || '') + '</td>' +
                                        '<td>' + (item.Category || '') + '</td>' +
                                        '<td>' + (item.Branch || '') + '</td>' +
                                        '<td>' + (item.Quantity || 0) + '</td>' +
                                        '<td>' + (item.DealerPrice || 0) + '</td>' +
                                        '</tr>';
                                }).join('');
                                
                                $('#searchResult').html(rowsHtml);
                                
                                $('#tableList').empty().append(
                                    '<tr><td>Total DP:</td><td id="displayTotalDP">'+(totals.totalDP||0)+'</td></tr>'+
                                    '<tr><td>Total Qty:</td><td id="displayTotalQty">'+(totals.totalQty||0)+'</td></tr>'+
                                    '<tr><td>Total SRP:</td><td id="displayTotalSRP">'+(totals.totalSRP||0)+'</td></tr>'+
                                    '<tr><td>Total Markup:</td><td id="displayTotalMarkup">'+(totals.totalMarkup||0)+'</td></tr>'
                                );
                            } else {
                                $('#searchResult').html('<tr><td colspan="7" class="text-center text-muted">No products found</td></tr>');
                                $('#tableList').empty().append(
                                    '<tr><td>Total DP:</td><td id="displayTotalDP">0</td></tr>'+
                                    '<tr><td>Total Qty:</td><td id="displayTotalQty">0</td></tr>'+
                                    '<tr><td>Total SRP:</td><td id="displayTotalSRP">0</td></tr>'+
                                    '<tr><td>Total Markup:</td><td id="displayTotalMarkup">0</td></tr>'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.onmouseenter = Swal.stopTimer;
                                    toast.onmouseleave = Swal.resumeTimer;
                                }
                            });
                            Toast.fire({
                                icon: 'error',
                                title: 'Product not found'
                            });
                        }
                    });
                });

        // Consolidate row selection logic
        // Use event delegation on 'tbody' to handle dynamically added rows efficiently
        $('#list tbody').on('click', 'tr', function(event) {
            // Toggle selection logic:
            // If user wants multi-select, we can toggle class. 
            // Based on previous user request ("remove selected row"), user might want multi-select.
            // But previous code forced single select by removing class from siblings.
            // Let's support multi-select for bulk cancellation which is more efficient.
            
            $(this).toggleClass('selected');
            
            // Optional: Scroll into view if needed, but usually clicking implies visibility
            // $(this).get(0).scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    //cancel consignment btn
    function cancelConsignment() {
        var $tbody = $("#list").find("tbody");
        var selectedRows = $tbody.find("tr.selected");
        if (selectedRows.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Please select item(s) to cancel' });
            return;
        }

        var data = [];
        var removeTotalDP = 0, removeTotalQty = 0, removeTotalSRP = 0, removeTotalMarkup = 0;

        selectedRows.each(function(){
            var $row = $(this);
            var $cells = $row.find("td");
            data.push({
                sino: $cells.eq(0).text() || '',
                serialno: $cells.eq(1).text() || '',
                product: $cells.eq(2).text() || '',
                category: $cells.eq(3).text() || '',
                branch: $cells.eq(4).text() || '',
                quantity: $cells.eq(5).text() || '',
                dealerprice: $cells.eq(6).text() || ''
            });
            // Sum up values to subtract later
            removeTotalDP += parseFloat($row.attr('data-total-dp') || 0);
            removeTotalQty += parseFloat($row.attr('data-qty') || 0);
            removeTotalSRP += parseFloat($row.attr('data-total-srp') || 0);
            removeTotalMarkup += parseFloat($row.attr('data-total-markup') || 0);
        });

        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to cancel " + selectedRows.length + " item(s). This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "<?php echo $base; ?>/routes/inventorymanagement/cancelconsignment.route.php",
                    type: "POST",
                    data: { action: "CancelConsignment", items: JSON.stringify(data) },
                    dataType: "JSON",
                    success: function(res){
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        });
                        if (res && res.STATUS === "success"){
                            Toast.fire({ icon: 'success', title: 'Cancelled successfully' });
                            
                            // Remove selected rows from UI
                            selectedRows.remove();
                            
                            // Update Totals
                            var currDP = parseFloat($('#displayTotalDP').text() || 0);
                            var currQty = parseFloat($('#displayTotalQty').text() || 0);
                            var currSRP = parseFloat($('#displayTotalSRP').text() || 0);
                            var currMarkup = parseFloat($('#displayTotalMarkup').text() || 0);

                            $('#displayTotalDP').text((currDP - removeTotalDP).toFixed(2));
                            $('#displayTotalQty').text((currQty - removeTotalQty));
                            $('#displayTotalSRP').text((currSRP - removeTotalSRP).toFixed(2));
                            $('#displayTotalMarkup').text((currMarkup - removeTotalMarkup).toFixed(2));

                        } else {
                            Toast.fire({ icon: 'error', title: (res && res.MESSAGE) ? res.MESSAGE : 'Error inserting data' });
                        }
                    },
                    error: function(xhr,status,error){
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.onmouseenter = Swal.stopTimer;
                                toast.onmouseleave = Swal.resumeTimer;
                            }
                        });
                        Toast.fire({ icon: 'error', title: 'Error inserting data' });
                    }
                });
            }
        });
    }
</script>

<script>
    // Function to handle row selection and scrolling into view
    function selectRow(event) {
        $(event.currentTarget).addClass('selected');
        $(event.currentTarget).get(0).scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    // Function to remove the selected row
    function removeSelectedRow() {
        var selectedRow = $('#list tbody tr.selected');
        if (selectedRow.length > 0) {
            // Remove from totals before removing from DOM (similar logic to cancel)
             var removeTotalDP = 0, removeTotalQty = 0, removeTotalSRP = 0, removeTotalMarkup = 0;
             selectedRow.each(function(){
                removeTotalDP += parseFloat($(this).attr('data-total-dp') || 0);
                removeTotalQty += parseFloat($(this).attr('data-qty') || 0);
                removeTotalSRP += parseFloat($(this).attr('data-total-srp') || 0);
                removeTotalMarkup += parseFloat($(this).attr('data-total-markup') || 0);
             });

            selectedRow.remove();
            
             // Update Totals
            var currDP = parseFloat($('#displayTotalDP').text() || 0);
            var currQty = parseFloat($('#displayTotalQty').text() || 0);
            var currSRP = parseFloat($('#displayTotalSRP').text() || 0);
            var currMarkup = parseFloat($('#displayTotalMarkup').text() || 0);

            $('#displayTotalDP').text((currDP - removeTotalDP).toFixed(2));
            $('#displayTotalQty').text((currQty - removeTotalQty));
            $('#displayTotalSRP').text((currSRP - removeTotalSRP).toFixed(2));
            $('#displayTotalMarkup').text((currMarkup - removeTotalMarkup).toFixed(2));
            
        } else {
            alert('Please select a row to remove.');
        }
    }

    $(document).ready(function() {
        // Removed redundant event listeners that were defined in the second script block
        // $('#list tbody').on('click', 'tr', selectRow); -> Removed (consolidated above)
        $('#removeButton').click(removeSelectedRow);
    });
</script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>

