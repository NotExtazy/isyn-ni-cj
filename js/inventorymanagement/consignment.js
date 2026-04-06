$(document).ready(function() {
    // Initialize Select2
    $("#branch").select2({ width: '100%' });
    $("#isynBranch").select2({ width: '100%' });
    $("#type").select2({ width: '100%' });
    $("#category").select2({ width: '100%' });
    $("#itemSelect").select2({ width: '100%' });
    $("#SInoSelect").select2({ width: '100%' });

    // Event Listeners
    $('#isynBranch').on('change', function() {
        filterType();
    });

    $('#type').on('change', function() {
        filterCategories();
    });

    $('input[name="inlineRadioOptions"]').on('change', function() {
        loadItemsForSelection();
    });

    $('#category').on('change', function() {
        loadItemsForSelection();
    });

    $('#itemSelect').on('change', function() {
        loadSINumbers();
    });

    $('#SInoSelect').on('change', function() {
        loadProductSummary();
    });

    // Load draft data from localStorage if exists
    loadDraftData();
});

// Function to save table data to localStorage
function saveDraftData() {
    const rows = $("#tableBody tr");
    const draft = [];
    rows.each(function() {
        const cells = $(this).find("td");
        draft.push({
            qty: cells.eq(0).text(),
            product: cells.eq(1).text(),
            dealersPrice: cells.eq(2).text(),
            totalPrice: cells.eq(3).text(),
            srp: cells.eq(4).text(),
            totalSRP: cells.eq(5).text(),
            markup: cells.eq(6).text(),
            vatsale: cells.eq(7).text(),
            vat: cells.eq(8).text(),
            amountDue: cells.eq(9).text(),
            stock: cells.eq(10).text(),
            isynBranch: cells.eq(11).text(),
            type: cells.eq(12).text(),
            category: cells.eq(13).text(),
            supplier_si: cells.eq(14).text(),
            SIno: cells.eq(15).text(),
            supplier: cells.eq(16).text(),
            date: cells.eq(17).text()
        });
    });
    localStorage.setItem('consignment_draft', JSON.stringify(draft));
}

// Function to load table data from localStorage
function loadDraftData() {
    const draft = localStorage.getItem('consignment_draft');
    if (draft) {
        const data = JSON.parse(draft);
        const tableBody = $("#tableBody");
        data.forEach(item => {
            const row = `
                <tr>
                    <td>${item.qty}</td>
                    <td>${item.product}</td>
                    <td>${item.dealersPrice}</td>
                    <td>${item.totalPrice}</td>
                    <td>${item.srp}</td>
                    <td>${item.totalSRP}</td>
                    <td>${item.markup}</td>
                    <td>${item.vatsale}</td>
                    <td>${item.vat}</td>
                    <td>${item.amountDue}</td>
                    <td>${item.stock}</td>
                    <td>${item.isynBranch}</td>
                    <td>${item.type}</td>
                    <td>${item.category}</td>
                    <td>${item.supplier_si}</td>
                    <td>${item.SIno}</td>
                    <td>${item.supplier}</td>
                    <td>${item.date}</td>
                </tr>
            `;
            tableBody.append(row);
        });
        if (data.length > 0) {
            $("#submit-btn, #cancel-btn").prop("disabled", false);
        }
    }
}

// Function to load existing consigned items from database (Optional: removed from auto-load as per request)
function loadConsignedItems() {
    var basePath = typeof ajaxBasePath !== 'undefined' ? ajaxBasePath : './ajax-inventory/';
    $.ajax({
        type: 'GET',
        url: basePath + 'fetch_consigned_items.php',
        dataType: 'json',
        success: function(response) {
            var tableBody = $("#tableBody");
            tableBody.empty();
            if (response && response.length > 0) {
                response.forEach(function(item) {
                    var row = `
                        <tr class="consigned-row" style="background-color: #f8f9fa;">
                            <td>${item.Quantity}</td>
                            <td>${item.Product}</td>
                            <td>${parseFloat(item.DealerPrice || 0).toFixed(2)}</td>
                            <td>${parseFloat(item.TotalPrice || 0).toFixed(2)}</td>
                            <td>${parseFloat(item.SRP || 0).toFixed(2)}</td>
                            <td>${parseFloat(item.TotalSRP || 0).toFixed(2)}</td>
                            <td>${parseFloat(item.TotalMarkup || 0).toFixed(2)}</td>
                            <td>${parseFloat(item.VatSales || 0).toFixed(2)}</td>
                            <td>${parseFloat(item.Vat || 0).toFixed(2)}</td>
                            <td>${parseFloat(item.AmountDue || 0).toFixed(2)}</td>
                            <td>${item.Stock || ''}</td>
                            <td>${item.Branch || ''}</td>
                            <td>${item.Type || ''}</td>
                            <td>${item.Category || ''}</td>
                            <td>${item.Serialno || ''}</td>
                            <td>${item.SIno || ''}</td>
                            <td>${item.Supplier || ''}</td>
                            <td>${item.DateAdded || ''}</td>
                        </tr>
                    `;
                    tableBody.append(row);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching consigned items:', error);
        }
    });
}

// Function to filter types based on selected branch
function filterType() {
    var branch = $('#isynBranch').val();
    console.log("Branch selected:", branch);

    // We can fetch types dynamically via AJAX or use the pre-passed PHP array
    // For now, let's assume we use the global variable defined in PHP
    if (typeof allTypes !== 'undefined') {
        var typeSelect = $('#type');
        typeSelect.empty().append('<option value="" selected disabled>Select</option>');
        
        if (branch) {
            allTypes.forEach(function(type) {
                typeSelect.append(new Option(type, type));
            });
        }
        typeSelect.trigger('change');
    }
}

// Function to filter categories based on selected type
function filterCategories() {
    var type = $('#type').val();
    console.log("Type selected:", type);

    if (type) {
        var categoriesSelect = $('#category');
        categoriesSelect.empty().append('<option value="" selected disabled>Select</option>');

        var list = [];
        if (type.toUpperCase() === "WITH VAT") {
            list = typeof categoriesWithVAT !== 'undefined' ? categoriesWithVAT : [];
        } else if (type.toUpperCase() === "NON-VAT") {
            list = typeof categoriesNonVAT !== 'undefined' ? categoriesNonVAT : [];
        }

        list.forEach(function(category) {
            categoriesSelect.append(new Option(category, category));
        });
        categoriesSelect.trigger('change');
    }
}

// Function to load items (Product Name or Serial No)
function loadItemsForSelection() {
    var type = $('input[name="inlineRadioOptions"]:checked').val();
    var category = $('#category').val();
    var selectElement = $('#itemSelect');
    var basePath = typeof ajaxBasePath !== 'undefined' ? ajaxBasePath : './ajax-inventory/';

    console.log('Load items:', { type: type, category: category });

    selectElement.empty().append('<option selected disabled>Select</option>');

    if (type && category) {
        $.ajax({
            type: 'POST',
            url: basePath + 'fetch_items.php',
            data: { type: type, category: category },
            dataType: 'json',
            success: function(response) {
                console.log('Fetch items response:', response);
                $.each(response.options, function(index, option) {
                    selectElement.append(new Option(option, option));
                });
                selectElement.trigger('change');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching items:', error);
                selectElement.append('<option selected disabled>No items found</option>');
            }
        });
    }
}

// Function to load SI numbers
function loadSINumbers() {
    var selectedOption = $('#itemSelect').val();
    var category = $('#category').val();
    var type = $('input[name="inlineRadioOptions"]:checked').val();
    var siSelectElement = $('#SInoSelect');
    var basePath = typeof ajaxBasePath !== 'undefined' ? ajaxBasePath : './ajax-inventory/';

    console.log('Attempting to load SI numbers for:', { selectedOption: selectedOption, category: category, type: type });

    siSelectElement.empty().append('<option selected disabled>Select</option>');

    if (selectedOption && selectedOption !== "Select" && category && type) {
        $.ajax({
            type: 'POST',
            url: basePath + 'fetch_items.php',
            data: {
                selectedOption: selectedOption,
                category: category,
                type: type
            },
            dataType: 'json',
            success: function(response) {
                console.log('SI numbers response received:', response);
                if (response.SIno && response.SIno.length > 0) {
                    $.each(response.SIno, function(index, option) {
                        siSelectElement.append(new Option(option, option));
                    });
                    
                    if (response.SIno.length === 1) {
                        siSelectElement.val(response.SIno[0]).trigger('change');
                    } else {
                        siSelectElement.trigger('change');
                    }
                } else {
                    console.log('No SI numbers found in response');
                    siSelectElement.append('<option selected disabled>No SI No. available</option>');
                    siSelectElement.trigger('change');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error fetching SI numbers:', error, xhr.responseText);
                siSelectElement.append('<option selected disabled>Error loading SI No.</option>');
                siSelectElement.trigger('change');
            }
        });
    } else {
        console.log('Skipping SI load: missing parameters or "Select" chosen');
    }
}

// Function to load product summary
function loadProductSummary() {
    var selectedSIno = $('#SInoSelect').val();
    var selectedProduct = "";
    var selectedSerialno = "";
    var basePath = typeof ajaxBasePath !== 'undefined' ? ajaxBasePath : './ajax-inventory/';
    
    if ($("#inlineRadio1").is(":checked")) {
        selectedProduct = $("#itemSelect").val();
    } else if ($("#inlineRadio2").is(":checked")) {
        selectedSerialno = $("#itemSelect").val();
    }

    if (selectedSIno) {
        $.ajax({
            type: 'POST',
            url: basePath + 'consignment-product-summary.php',
            data: {
                SIno: selectedSIno,
                Serialno: selectedSerialno,
                Product: selectedProduct
            },
            dataType: 'json',
            success: function(productSummary) {
                console.log('Product summary response:', productSummary);
                if (productSummary.error) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Product Not Found',
                        text: productSummary.error,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    updateProductSummaryFields(productSummary);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching product summary:', error);
            }
        });
    }
}

function updateProductSummaryFields(productSummary) {
    $('#supplier_si').val(productSummary.Serialno || '');
    $('#SIno').val(productSummary.SIno || '');
    $('#product').val(productSummary.product || '');
    $('#supplier').val(productSummary.Supplier || '');
    $('#srp').val(productSummary.SRP || '');
    $('#quantity').val(productSummary.Quantity || '');
    $('#dealers_price').val(productSummary.DealerPrice || '');
    $('#total_price').val(productSummary.TotalPrice || '');
}

function addToTable() {
    const quantityInput = $("#quantityInput").val();
    const availableQty = parseFloat($("#quantity").val()) || 0;
    const product = $("#product").val();
    
    if (!quantityInput || !product) {
        Swal.fire({ icon: 'warning', title: 'Missing Information', text: 'Please fill in quantity and select a product' });
        return;
    }

    if (parseFloat(quantityInput) > availableQty) {
        Swal.fire({ icon: 'error', title: 'Invalid quantity', text: 'Requested quantity exceeds available stock' });
        return;
    }

    const dealersPrice = parseFloat($("#dealers_price").val()) || 0;
    const srp = parseFloat($("#srp").val()) || 0;
    const qty = parseFloat(quantityInput);
    
    const totalPrice = qty * dealersPrice;
    const totalSRP = qty * srp;
    const markup = totalSRP - totalPrice;
    
    let vatsale = 0;
    let vat = 0;
    const type = $("#type").val() || "";
    
    if (type.toUpperCase() === 'WITH VAT') {
        vatsale = totalPrice / 1.12;
        vat = vatsale * 0.12;
    } else {
        vatsale = totalPrice;
        vat = 0;
    }

    const dateAdded = new Date();
    const formattedDate = (dateAdded.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                          dateAdded.getDate().toString().padStart(2, '0') + '/' + 
                          dateAdded.getFullYear();

    const row = `
        <tr>
            <td>${qty}</td>
            <td>${product}</td>
            <td>${dealersPrice.toFixed(2)}</td>
            <td>${totalPrice.toFixed(2)}</td>
            <td>${srp.toFixed(2)}</td>
            <td>${totalSRP.toFixed(2)}</td>
            <td>${markup.toFixed(2)}</td>
            <td>${vatsale.toFixed(2)}</td>
            <td>${vat.toFixed(2)}</td>
            <td>${totalPrice.toFixed(2)}</td>
            <td>${$('#branch').val()}</td>
            <td>${$('#isynBranch').val()}</td>
            <td>${type}</td>
            <td>${$('#category').val()}</td>
            <td>${$('#supplier_si').val()}</td>
            <td>${$('#SIno').val()}</td>
            <td>${$('#supplier').val()}</td>
            <td>${formattedDate}</td>
        </tr>
    `;

    $("#tableBody").append(row);
    saveDraftData(); // Save to localStorage
    $("#particulars")[0].reset();
    $("#summary")[0].reset();
    $('#isynBranch, #type, #category, #itemSelect, #SInoSelect').val(null).trigger('change');
    
    $("#submit-btn, #cancel-btn").prop("disabled", false);
}

function saveDataFromTable() {
    const rows = $("#tableBody tr");
    if (rows.length === 0) return;

    const data = [];
    rows.each(function() {
        const cells = $(this).find("td");
        data.push({
            quantity: cells.eq(0).text(),
            product: cells.eq(1).text(),
            dealersPrice: cells.eq(2).text(),
            totalPrice: cells.eq(3).text(),
            srp: cells.eq(4).text(),
            totalSRP: cells.eq(5).text(),
            markup: cells.eq(6).text(),
            vatsale: cells.eq(7).text(),
            vat: cells.eq(8).text(),
            amountDue: cells.eq(9).text(),
            stock: cells.eq(10).text(),
            branch: cells.eq(11).text(),
            type: cells.eq(12).text(),
            category: cells.eq(13).text(),
            supplierSI: cells.eq(14).text(),
            SIno: cells.eq(15).text(),
            supplier: cells.eq(16).text(),
            dateAdded: cells.eq(17).text()
        });
    });

    Swal.fire({
        title: 'Saving...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        type: "POST",
        url: (typeof ajaxBasePath !== 'undefined' ? ajaxBasePath : './ajax-inventory/') + "save-consignment.php",
        data: JSON.stringify(data),
        contentType: "application/json",
        success: function(response) {
            console.log('Server response:', response);
            Swal.close();
            let res;
            try {
                res = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error('JSON Parse Error:', e, response);
                Swal.fire({ icon: 'error', title: 'Response Error', text: 'Invalid response from server. Check console for details.' });
                return;
            }

            if (res.success) {
                Swal.fire({ icon: 'success', title: 'Success', text: res.message });
                $("#tableBody").empty();
                localStorage.removeItem('consignment_draft'); // Clear draft
                $("#submit-btn, #cancel-btn").prop("disabled", true);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Unknown error occurred' });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error, xhr.responseText);
            Swal.close();
            Swal.fire({ icon: 'error', title: 'Submission Failed', text: 'Network error or server-side failure. Details: ' + error });
        }
    });
}

function cancelProduct() {
    $("#tableBody").empty();
    localStorage.removeItem('consignment_draft'); // Clear draft
    $("#particulars")[0].reset();
    $("#summary")[0].reset();
    $('#isynBranch, #type, #category, #itemSelect, #SInoSelect').val(null).trigger('change');
    $("#submit-btn, #cancel-btn").prop("disabled", true);
}
