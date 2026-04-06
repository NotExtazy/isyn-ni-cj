$(document).ready(function() {

    // ─── Cached selectors ────────────────────────────────────────────────────
    var $split    = $('#prSplit');
    var $detailCol = $('#detailCol');
    var $returnList = $('#returnList');
    var $returnCount = $('#returnCount');
    var $returnEmptyRow = $('#returnEmptyRow');

    // Hidden storage for the currently-selected product's full details
    var currentProduct = {};

    // ─── Step helpers ────────────────────────────────────────────────────────
    function setStep(n) {
        for (var i = 1; i <= 3; i++) {
            var $s = $('#step-' + i);
            $s.removeClass('active done');
            if (i < n)  $s.addClass('done');
            if (i === n) $s.addClass('active');
        }
    }

    function updateReturnCount() {
        var n = $returnList.find('tr:not(#returnEmptyRow)').length;
        $returnCount.text(n);
        if (n > 0) {
            $returnEmptyRow.hide();
            setStep(3);
        } else {
            $returnEmptyRow.show();
        }
    }

    // ─── Tab switching ───────────────────────────────────────────────────────
    $(document).on('click', '.pr-tab-btn', function() {
        var tab = $(this).data('tab');
        $('.pr-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane-pr').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // ─── 0. Initialize Dropdowns ─────────────────────────────────────────────
    function initializeDropdowns() {
        $.ajax({
            url: "/process/inventorymanagement/purchasedreturn.process.php",
            type: "POST",
            data: { action: "Initialize" },
            dataType: "json",
            success: function(response) {
                var branchSelect = $('#isynBranch');
                branchSelect.empty().append('<option value="" selected disabled>Select Branch</option>');
                if (response.BRANCHES) {
                    $.each(response.BRANCHES, function(i, item) { branchSelect.append(new Option(item, item)); });
                }
                var typeSelect = $('#type');
                typeSelect.empty().append('<option value="" selected disabled>Select Type</option>');
                if (response.TYPES) {
                    $.each(response.TYPES, function(i, item) { typeSelect.append(new Option(item, item)); });
                }
                var categorySelect = $('#category');
                categorySelect.empty().append('<option value="" selected disabled>Select Category</option>');
                var printCategoryFilter = $('#printCategoryFilter');
                printCategoryFilter.empty().append('<option value="All">All Categories</option>');
                if (response.CATEGORIES) {
                    $.each(response.CATEGORIES, function(i, item) {
                        categorySelect.append(new Option(item, item));
                        printCategoryFilter.append(new Option(item, item));
                    });
                }
                var returnTypeSelect = $('#return-type');
                returnTypeSelect.empty().append('<option value="" selected disabled>Select Return Type</option>');
                if (response.RETURN_TYPES) {
                    $.each(response.RETURN_TYPES, function(i, item) { returnTypeSelect.append(new Option(item, item)); });
                }
            },
            error: function() { console.error("Failed to initialize dropdowns"); }
        });
    }
    initializeDropdowns();

    // ─── 1. Load Products ────────────────────────────────────────────────────
    function loadProducts() {
        var branch   = $('#isynBranch').val()  || '';
        var type     = $('#type').val()        || '';
        var category = $('#category').val()    || '';

        // Close detail panel while loading
        closeDetailPanel();

        $('#tableList').html(
            '<tr><td colspan="4" class="empty-state" style="padding:30px;">' +
            '<i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#3b82f6;margin-bottom:8px;display:block;"></i>' +
            '<p>Loading products...</p></td></tr>'
        );

        $.ajax({
            method: 'POST',
            url: '/pages/inventorymanagement/ajax-inventory/product-search.php',
            data: { branch: branch, type: type, category: category },
            dataType: 'json',
            success: function(response) {
                $('#tableList').empty();
                if (response.length > 0) {
                    $.each(response, function(index, item) {
                        var productName = (item.Product || '').trim();
                        var sinoPart    = item.SIno    ? '<span style="margin-right:8px;">SI: ' + item.SIno + '</span>' : '';
                        var serialPart  = (item.Serialno && item.Serialno !== '-' && item.Serialno !== '0') ? 'S/N: ' + item.Serialno : '';
                        var row = '<tr class="clickable-row"' +
                            ' data-product="' + encodeURIComponent(item.Product) + '"' +
                            ' data-sino="' + encodeURIComponent(item.SIno) + '"' +
                            ' data-serialno="' + encodeURIComponent(item.Serialno) + '"' +
                            ' data-quantity="' + item.Quantity + '">' +
                            '<td><div class="prod-name">' + productName + '</div>' +
                            '<div class="prod-meta">' + sinoPart + serialPart + '</div></td>' +
                            '<td class="SInoSelect">' + item.SIno + '</td>' +
                            '<td class="SerialnoSelected">' + item.Serialno + '</td>' +
                            '<td><span class="badge-qty">' + item.Quantity + '</span></td>' +
                            '</tr>';
                        $('#tableList').append(row);
                    });
                    setStep(2);
                } else {
                    $('#tableList').html(
                        '<tr><td colspan="4" class="empty-state" style="padding:30px;">' +
                        '<i class="fa-solid fa-box-open fa-2x" style="color:#cbd5e1;margin-bottom:8px;display:block;"></i>' +
                        '<p>No products found. Try different filters.</p></td></tr>'
                    );
                }
            },
            error: function(xhr, status, error) { console.error(error); }
        });
    }

    $('#search-btn').click(function() { loadProducts(); });
    loadProducts();

    // ─── 2. Row click → open detail panel (click again to close) ─────────────
    $(document).on('click', '.clickable-row', function() {
        var $row = $(this);

        // Toggle: clicking the already-selected row closes the panel
        if ($row.hasClass('selected')) {
            $row.removeClass('selected');
            closeDetailPanel();
            return;
        }

        $('.clickable-row').removeClass('selected');
        $row.addClass('selected');
        loadDetailPanel(this);
    });

    // Click outside the product list card → close panel & deselect
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#step2-card').length) {
            if ($split.hasClass('panel-open')) {
                $('.clickable-row').removeClass('selected');
                closeDetailPanel();
            }
        }
    });


    function loadDetailPanel(rowEl) {
        var $row     = $(rowEl);
        var SIno     = decodeURIComponent($row.data('sino'));
        var Serialno = decodeURIComponent($row.data('serialno'));
        var Product  = decodeURIComponent($row.data('product'));

        // Show loading state in panel
        openDetailPanel();
        $('#detailProductName').text('Loading…');

        $.ajax({
            type: 'POST',
            url: '/pages/inventorymanagement/ajax-inventory/product-summary.php',
            data: { SIno: SIno, Serialno: Serialno, Product: Product },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    Swal.fire('Error', data.error, 'error');
                    closeDetailPanel();
                    return;
                }
                currentProduct = data;
                var pname = (data.Product || data.product || '').trim();
                $('#detailProductName').text(pname);
                $('#detailSIno').text(data.SIno || '—');
                $('#detailSerial').text(data.Serialno || '—');
                $('#detailSupplier').text(data.Supplier || '—');
                $('#detailCategory').text(data.Category || '—');
                $('#detailType').text(data.Type || '—');
                $('#detailBranch').text(data.Branch || '—');
                $('#detailDealerPrice').text(data.DealerPrice ? '₱' + parseFloat(data.DealerPrice).toFixed(2) : '—');
                $('#detailSRP').text(data.SRP ? '₱' + parseFloat(data.SRP).toFixed(2) : '—');
                $('#detailMaxQty').text(data.Quantity || '—');
                $('#maxQuantityDisplay').val(data.Quantity || 1);
                $('#quantityDisplay').val(1).attr('max', data.Quantity);
            },
            error: function() {
                Swal.fire('Error', 'Error fetching product details', 'error');
                closeDetailPanel();
            }
        });
    }

    function openDetailPanel() {
        $split.addClass('panel-open');
    }
    function closeDetailPanel() {
        $split.removeClass('panel-open');
        currentProduct = {};
    }

    // ─── 3. Add to Return List ───────────────────────────────────────────────
    window.returnSingleItem = function() {
        var quantity   = parseInt($('#quantityDisplay').val(), 10) || 0;
        var maxQty     = parseInt($('#maxQuantityDisplay').val(), 10) || 0;
        var returntype = $('#return-type').val();
        var reason     = $('#returnReason').val().trim();

        if (!returntype) {
            Swal.fire('Warning', 'Please select a Return Type in the filter bar first.', 'warning');
            return;
        }
        if (!currentProduct.SIno) {
            Swal.fire('Warning', 'Please select a product from the list first.', 'warning');
            return;
        }
        if (quantity < 1 || quantity > maxQty) {
            Swal.fire('Warning', 'Quantity must be between 1 and ' + maxQty + '.', 'warning');
            return;
        }

        var product          = (currentProduct.Product || currentProduct.product || '').trim();
        var sino             = currentProduct.SIno    || '';
        var serialno         = currentProduct.Serialno|| '';
        var dealerPrice      = currentProduct.DealerPrice || 0;
        var srp              = currentProduct.SRP     || 0;
        var branch           = currentProduct.Branch  || '';
        var totalDealerPrice = parseFloat(dealerPrice) * quantity;
        var totalSRP         = parseFloat(srp)         * quantity;

        // Visible cols: Product | SIno | Serialno | Qty | ReturnType | Reason | [Action]
        // Hidden cols (for SaveReturn): dealerPrice | srp | branch | totalDP | totalSRP
        var newRow = `<tr>
            <td>${product}</td>
            <td>${sino}</td>
            <td>${serialno}</td>
            <td>${quantity}</td>
            <td>${returntype}</td>
            <td>${reason || '<em style="color:#94a3b8">—</em>'}</td>
            <td style="display:none">${dealerPrice}</td>
            <td style="display:none">${srp}</td>
            <td style="display:none">${branch}</td>
            <td style="display:none">${totalDealerPrice}</td>
            <td style="display:none">${totalSRP}</td>
            <td style="display:none">${reason}</td>
            <td><button class="btn-del remove-btn" title="Remove"><i class="fa fa-trash"></i></button></td>
        </tr>`;

        $returnList.append(newRow);
        updateReturnCount();
        $('#returnReason').val('');
        $('#quantityDisplay').val(1);

        Swal.fire({ icon: 'success', title: 'Added!', text: `"${product}" added to return list.`, timer: 1500, showConfirmButton: false });
    };

    // ─── 4. Remove row ───────────────────────────────────────────────────────
    $(document).on('click', '.remove-btn', function() {
        $(this).closest('tr').remove();
        updateReturnCount();
    });

    // ─── 5. Process Return ───────────────────────────────────────────────────
    window.archiveVisibleItems = function() {
        var tableRows = $('#returnList tr:not(#returnEmptyRow)');
        if (tableRows.length === 0) {
            Swal.fire('Warning', 'No items to return', 'warning');
            return;
        }
        var data = [];
        tableRows.each(function() {
            var cells = $(this).find('td');
            // Row structure after redesign:
            // 0:Product 1:SIno 2:Serialno 3:Qty 4:ReturnType 5:Reason(visible)
            // 6:dealerPrice(hidden) 7:srp(hidden) 8:branch(hidden)
            // 9:totalDP(hidden) 10:totalSRP(hidden) 11:reason(hidden copy) 12:Action
            data.push([
                cells.eq(0).text(),  // Product
                cells.eq(1).text(),  // SIno
                cells.eq(2).text(),  // Serialno
                cells.eq(3).text(),  // Qty
                cells.eq(4).text(),  // ReturnType (= Reason/return type for SaveReturn[4])
                cells.eq(6).text(),  // DealerPrice
                cells.eq(7).text(),  // SRP
                cells.eq(8).text(),  // Branch
                cells.eq(9).text(),  // TotalDP
                cells.eq(10).text(), // TotalSRP
                cells.eq(11).text()  // Reason text
            ]);
        });
        Swal.fire({
            title: 'Confirm Return',
            text: "Are you sure you want to process these returns?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1d4ed8',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, process it!'
        }).then((result) => {
            if (result.isConfirmed) {
                var formdata = new FormData();
                formdata.append("action", "SaveReturn");
                formdata.append("DATA", JSON.stringify(data));
                $.ajax({
                    url: "/process/inventorymanagement/purchasedreturn.process.php",
                    type: "POST",
                    data: formdata,
                    processData: false,
                    contentType: false,
                    dataType: 'JSON',
                    success: function(response) {
                        if (response.STATUS == 'success') {
                            // Show the processed transaction number then advance to next
                            $('#printBtn').show();
                            Swal.fire('Success', response.MESSAGE, 'success');

                            // Advance transaction ID to next number for subsequent returns
                            var lastNo  = response.TransactionNo || '';
                            var lastNum = parseInt(lastNo.replace('PRN', ''), 10) || 0;
                            var nextNo  = 'PRN' + String(lastNum + 1).padStart(6, '0');
                            $('#returnReceiptID').val(nextNo);

                            $returnList.find('tr:not(#returnEmptyRow)').remove();
                            updateReturnCount();
                            loadReturnedHistory();
                            closeDetailPanel();
                            // Switch to history tab automatically
                            $('.pr-tab-btn[data-tab="history"]').click();
                        } else {
                            Swal.fire('Error', response.MESSAGE, 'error');
                        }
                    },
                    error: function() { Swal.fire('Error', 'Failed to process return', 'error'); }
                });
            }
        });
    };

    // ─── 6. Load History ─────────────────────────────────────────────────────
    function loadReturnedHistory() {
        $.ajax({
            url: "/process/inventorymanagement/purchasedreturn.process.php",
            type: "POST",
            data: { action: "GetHistory" },
            dataType: "json",
            success: function(response) {
                $('#archivedList').empty();
                if (response.DATA && response.DATA.length > 0) {
                    $.each(response.DATA, function(i, item) {
                        // Use confirmed real DB columns only
                        var returnType = item.ReturnType || item.TransactionType || item.Reason || item.Type || '';
                        var badge = returnType
                            ? `<span class="badge-qty">${returnType}</span>`
                            : '<em style="color:#94a3b8">—</em>';
                        var row = `<tr>
                            <td style="font-size:.78rem;color:#64748b">${item.TransactionNo || '—'}</td>
                            <td>${item.Product || '—'}</td>
                            <td>${item.SIno || '—'}</td>
                            <td>${item.Serialno || '—'}</td>
                            <td>${item.Quantity || '—'}</td>
                            <td>${item.Branch || '—'}</td>
                            <td style="font-size:.78rem;color:#64748b">${item.DateAdded || '—'}</td>
                        </tr>`;
                        $('#archivedList').append(row);
                    });
                } else {
                    $('#archivedList').html(
                        '<tr><td colspan="7"><div class="empty-state">' +
                        '<i class="fa-solid fa-clock-rotate-left"></i>' +
                        '<p>No returned items found.</p></div></td></tr>'
                    );
                }
            },
            error: function() {
                $('#archivedList').html(
                    '<tr><td colspan="7"><div class="empty-state" style="color:#ef4444;">' +
                    '<i class="fa-solid fa-circle-exclamation"></i>' +
                    '<p>Error loading history.</p></div></td></tr>'
                );
            }
        });
    }
    loadReturnedHistory();

    // Initial state
    updateReturnCount();
});

// Print function
window.printData = function() {
    var transactionNo = $('#returnReceiptID').val();
    if (!transactionNo) {
        Swal.fire('Error', 'No Transaction Number found to print.', 'error');
        return;
    }
    window.open('/pages/inventorymanagement/print_return.php?transactionNo=' + transactionNo, '_blank');
};
