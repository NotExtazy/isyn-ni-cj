var AcashInfoTbl;
var EcpayTxnTbl;

// Add pagination variables for zero amount modal
var currentPage = 1;
var entriesPerPage = 5;
var allZeroAmountData = [];

$(document).ready(function() {
    LoadAcashInfo();
    LoadEcpayTransactions();
    
    // Load Custom and Raw tables
    LoadAcashInfoCustom();
    LoadAcashInfoRaw();
    LoadEcpayTransactionsCustom();
    LoadEcpayTransactionsRaw();
    LoadEcpayTransactionsEcpay();

    $('#ecpay-category-filter').change(function(){
        LoadEcpayTransactionsEcpay();
    });

    // Load dynamic filter values (Branches)
    LoadFilterValues();

    // Load Emails for Maintenance Tab
    LoadEmails();
    LoadBranches();
    
    // Reload emails when Maintenance tab is shown
    $('#maintenance-tab').on('shown.bs.tab', function (e) {
        LoadEmails();
        LoadBranches();
        LoadMaintenanceTable();
    });
    
    // Completely block zero amount modal from auto-opening
    var blockModal = true;
    
    // Override Bootstrap modal show for zero amount modal
    $(document).on('show.bs.modal', '#zeroAmountModal', function (e) {
        if (blockModal) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        LoadZeroAmountAcash();
    });
    
    // Allow modal to open only when button is clicked
    window.allowZeroAmountModal = function() {
        blockModal = false;
        LoadZeroAmountAcash();
        setTimeout(function() {
            blockModal = true;
        }, 100);
    };
    
    // Load zero amount data when modal is shown
    $('#zeroAmountModal').on('show.bs.modal', function (e) {
        LoadZeroAmountAcash();
    });
    $('#acash-info-tab').on('shown.bs.tab', function () {
        // Automatic check disabled to prevent modal from reopening after closing
        // Users can manually click the button to check for zero amounts
        // setTimeout(function() {
        //     CheckForZeroAmounts();
        // }, 1000);
    });

    // Remove the automatic click check to prevent aggressive modal appearance

    // Remove excess backdrops but keep one for normal modal behavior
    setInterval(function() {
        var backdrops = $('.modal-backdrop');
        if (backdrops.length > 1) {
            // Remove all but the first backdrop
            backdrops.slice(1).remove();
        }
        // Ensure body classes are correct
        if ($('#zeroAmountModal').hasClass('show')) {
            $('body').addClass('modal-open');
        } else {
            $('body').removeClass('modal-open');
        }
    }, 500);

    // Add manual close functionality for zero amount modal
    var modalClosedManually = false;
    
    // Emergency close handlers - multiple approaches
    $(document).on('click', '#zeroAmountModal .btn-close, #zeroAmountModal .btn-secondary, #zeroAmountModal [data-bs-dismiss="modal"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        modalClosedManually = true;
        
        // Force close modal with multiple methods
        var modalEl = document.getElementById('zeroAmountModal');
        
        // Method 1: Bootstrap Modal API
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
        
        // Method 2: jQuery modal
        $(modalEl).modal('hide');
        
        // Method 3: Direct DOM manipulation
        $(modalEl).removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
        
        // Clean up all backdrops
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
        
        console.log('Modal force-closed');
    });

    // ESC key to close modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#zeroAmountModal').hasClass('show')) {
            modalClosedManually = true;
            $('#zeroAmountModal .btn-close').click();
        }
    });

    // Ensure modal backdrop is cleaned up when modal is hidden
    $(document).on('hidden.bs.modal', '#zeroAmountModal', function () {
        modalClosedManually = true;
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
    });

    // COMPLETELY REMOVE ALL INPUT VALIDATION - NO INTERFERENCE
    console.log("All input validation removed - clean mode");
});
    
    // Add Maintenance Button Click
    $('#btn-add-maintenance').click(function() {
        var email = $('#email-search-dropdown').val();
        var branch = $('#branch-search-dropdown').val();
        var ownerName = $('#owner-name-input').val();
        
        if (!email || !branch) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Input',
                text: 'Please select both an Email and a Branch.'
            });
            return;
        }
        
        $.ajax({
            url: "../../routes/profiling/acashinfo.route.php",
            type: "POST",
            data: { 
                action: "SaveMaintenanceData",
                email: email,
                branch: branch,
                owner_name: ownerName
            },
            dataType: "JSON",
            success: function(response) {
                if (response.STATUS === "SUCCESS") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.MESSAGE,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    // Refresh the table and clear inputs
                    LoadMaintenanceTable();
                    LoadEmails(); // Refresh email list to remove the one just added
                    
                    // Reload all transaction tables to reflect the changes immediately
                    LoadAcashInfo(); 
                    LoadEcpayTransactions();
                    LoadAcashInfoCustom();
                    LoadAcashInfoRaw();
                    LoadEcpayTransactionsCustom();
                    LoadEcpayTransactionsRaw();
                    LoadEcpayTransactionsEcpay();

                    $('#email-search-dropdown').val('').trigger('change');
                    $('#branch-search-dropdown').val('').trigger('change');
                    $('#owner-name-input').val('');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.MESSAGE
                    });
                }
            },
            error: function(xhr) {
            console.error("SaveMaintenanceData error", xhr);
            Swal.fire({
                icon: 'error',
                title: 'System Error',
                text: 'Failed to save data. Check console for details.'
            });
        }
    });
});

// Row Click to Edit
$(document).on('click', '#maintenance-list tr', function() {
    // Get data from cells
    var email = $(this).find('td:eq(0)').text();
    var ownerName = $(this).find('td:eq(1)').text();
    var branch = $(this).find('td:eq(2)').text();
    
    // Only proceed if we have valid data (ignore "No mapped data found" row)
    if (!email || email === "No mapped data found") return;
    
    var emailDropdown = $('#email-search-dropdown');
    var branchDropdown = $('#branch-search-dropdown');
    
    // Set Email
    // Since mapped emails are removed from the list, we need to add this one back temporarily
    if (emailDropdown.find("option[value='" + email + "']").length) {
        emailDropdown.val(email).trigger('change');
    } else { 
        // Create a new option if it doesn't exist
        var newOption = new Option(email, email, true, true);
        emailDropdown.append(newOption).trigger('change');
    }
    
    // Set Branch
    branchDropdown.val(branch).trigger('change');
    
    // Set Owner Name
    $('#owner-name-input').val(ownerName);
});

// Initialize tables if not loaded via functions (fallback, though functions should handle it)
    // InitializeOtherTables(); // Disabled to prevent race condition with AJAX loads

    // Helper to show notification
    function showUploadNotification(message, type) {
        var notif = $("#upload-status-notification");
        var text = $("#upload-status-text");
        var spinner = $("#upload-spinner");
        var successIcon = $("#upload-success-icon");
        var errorIcon = $("#upload-error-icon");

        text.text(message);
        notif.removeClass("alert-info alert-success alert-danger");
        spinner.addClass("d-none");
        successIcon.addClass("d-none");
        errorIcon.addClass("d-none");

        if (type === 'loading') {
            notif.addClass("alert-info");
            spinner.removeClass("d-none");
            notif.fadeIn();
        } else if (type === 'success') {
            notif.addClass("alert-success");
            successIcon.removeClass("d-none");
            setTimeout(function() { notif.fadeOut(); }, 5000);
        } else if (type === 'error') {
            notif.addClass("alert-danger");
            errorIcon.removeClass("d-none");
            setTimeout(function() { notif.fadeOut(); }, 7000);
        }
    }

    // Unified Upload Handler
    $('#btn-upload-file').click(function(){
        var fileInput = $('#upload_file')[0];
        if (fileInput.files.length === 0) {
            showUploadNotification("Please select a file.", "error");
            return;
        }

        var file = fileInput.files[0];
        var fileName = file.name;
        var validExtensions = [".xlsx", ".csv"];
        var fileExtension = fileName.substring(fileName.lastIndexOf('.')).toLowerCase();

        if ($.inArray(fileExtension, validExtensions) == -1) {
            showUploadNotification("Invalid file type. Please upload only .xlsx or .csv files.", "error");
            return;
        }

        var category = $('#upload_category').val();
        var action = (category === 'Raw') ? 'UploadRaw' : 'UploadCustom';
        
        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('action', action);
        formData.append('category', category); // Pass category for custom handling

        // Close modal
        var modalEl = document.getElementById('uploadModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        $.ajax({
            url: "../../routes/profiling/acashinfo.route.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "JSON",
            beforeSend: function(){
                showUploadNotification("Uploading file...", "loading");
            },
            success: function(response){
                if(response.STATUS == "SUCCESS"){
                    showUploadNotification(response.MESSAGE || "Upload Successful", "success");
                    // Reload all tables to be safe
                    LoadAcashInfo(); 
                    LoadEcpayTransactions();
                    LoadAcashInfoCustom();
                    LoadAcashInfoRaw();
                    LoadEcpayTransactionsCustom();
                    LoadEcpayTransactionsRaw();
                    LoadEcpayTransactionsEcpay();
                } else {
                    showUploadNotification("Upload Failed: " + response.MESSAGE, "error");
                }
            },
            error: function(xhr){
                console.error("Upload error", xhr);
                var msg = "Server Error";
                if(xhr.responseJSON && xhr.responseJSON.MESSAGE){
                    msg = xhr.responseJSON.MESSAGE;
                } else if (xhr.statusText === "parsererror" || xhr.status === 200) {
                     var snippet = xhr.responseText ? xhr.responseText.substring(0, 100) : "Empty Response";
                     snippet = snippet.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                     msg += " (Invalid JSON: " + snippet + "...)";
                } else if (xhr.statusText) {
                     msg += " (" + xhr.status + " " + xhr.statusText + ")";
                }
                showUploadNotification("Upload Failed: " + msg, "error");
            }
        });
        
        // Reset input
        $('#upload_file').val('');
    });

    // Analytics Event Listeners - REMOVED

function LoadZeroAmountAcash(){
    // Show loading immediately
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadZeroAmountAcash"},
        dataType:"JSON",
        success:function(response){
            Swal.close();
            
            if(response.STATUS === "SUCCESS" && response.DATA && response.DATA.length > 0){
                // Store all data for pagination
                allZeroAmountData = response.DATA;
                currentPage = 1; // Reset to first page
                
                displayZeroAmountPage();
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No Missing Amounts',
                    text: 'All ACash entries have amounts assigned.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        },
        error: function(xhr) {
            Swal.close();
            console.error("LoadZeroAmountAcash error", xhr);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load entries without amounts.'
            });
        }
    });
}

function displayZeroAmountPage() {
    var tbody = $("#zero-amount-list");
    tbody.empty();
    
    // Calculate pagination
    var startIndex = (currentPage - 1) * entriesPerPage;
    var endIndex = Math.min(startIndex + entriesPerPage, allZeroAmountData.length);
    var currentPageData = allZeroAmountData.slice(startIndex, endIndex);
    
    // Display current page entries - COMPLETELY SIMPLE INPUTS
    currentPageData.forEach(function(item, index){
        var row = `
            <tr data-id="${item.ID}">
                <td>${item.CDate || ""}</td>
                <td>${item.Branch || ""}</td>
                <td>${item.Fund || ""}</td>
                <td>${item.AcctNo || ""}</td>
                <td>${item.Particulars || item.AcctTitle || ""}</td>
                <td>
                    <input type="text" 
                           value="${item.Amount || 0}" 
                           placeholder="0.00"
                           style="width: 120px; border: 1px solid #ccc; padding: 4px;"
                           class="simple-input"
                           data-row-id="${item.ID}"
                           onkeypress="return event.charCode >= 48 && event.charCode <= 57 || event.charCode === 46"
                           oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                </td>
            </tr>`;
        tbody.append(row);
    });
    
    // Show pagination info
    var totalPages = Math.ceil(allZeroAmountData.length / entriesPerPage);
    tbody.append(`
        <tr>
            <td colspan="6" class="text-center">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        <small>Showing ${startIndex + 1}-${endIndex} of ${allZeroAmountData.length} entries | Page ${currentPage} of ${totalPages}</small>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="goToPreviousPage()" ${currentPage === 1 ? 'disabled' : ''}>
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="goToNextPage()" ${currentPage === totalPages ? 'disabled' : ''}>
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </td>
        </tr>
    `);
    
    // Show modal immediately - no delays
    var modalEl = document.getElementById('zeroAmountModal');
    modalEl.style.display = 'block';
    modalEl.classList.add('show');
    document.body.classList.add('modal-open');
    
    // Add backdrop manually
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'custom-backdrop';
    document.body.appendChild(backdrop);
}

function goToPreviousPage() {
    if (currentPage > 1) {
        currentPage--;
        displayZeroAmountPage();
    }
}

function goToNextPage() {
    var totalPages = Math.ceil(allZeroAmountData.length / entriesPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        displayZeroAmountPage();
    }
}

function SubmitZeroAmounts(){
    var entries = [];
    $(".simple-input").each(function(){
        var id = parseInt($(this).attr("data-row-id"), 10);
        var inputVal = $(this).val();
        
        // Skip if no ID or truly empty input (allow 0 values)
        if (!id || inputVal === null || inputVal === undefined || inputVal === '') {
            return;
        }
        
        // Convert text to number, allowing decimal points
        var amount = parseFloat(inputVal.replace(/[^0-9.-]/g, ''));
        
        // Only add entries with valid positive amounts
        if (!isNaN(amount) && amount >= 0) {
            entries.push({id:id, amount:amount});
        }
    });
    
    if (entries.length === 0) {
        // Don't show warning - just close modal if user wants to
        console.log('No entries to update');
        closeSimpleModal();
        return;
    }
    
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"UpdateZeroAmounts", entries:entries},
        dataType:"JSON",
        success:function(response){
            if (response.STATUS === "SUCCESS") {
                Swal.fire({icon:'success', title:'Updated', timer:1200, showConfirmButton:false});
                LoadAcashInfo();
                LoadFilterValues();
                closeSimpleModal();
                
                // Reload data and stay on current page instead of resetting
                setTimeout(function() {
                    LoadZeroAmountAcashKeepPage();
                }, 500);
            } else {
                Swal.fire({icon:'error', title:'Error', text:response.MESSAGE || 'Update failed'});
            }
        },
        error: function(xhr) {
            console.error("SubmitZeroAmounts error", xhr);
            Swal.fire({icon:'error', title:'System Error', text: 'Failed to save amounts'});
        }
    });
}

function closeSimpleModal() {
    // Close modal manually
    var modalEl = document.getElementById('zeroAmountModal');
    modalEl.style.display = 'none';
    modalEl.classList.remove('show');
    document.body.classList.remove('modal-open');
    
    // Remove backdrop
    $('#custom-backdrop').remove();
}

function LoadZeroAmountAcashKeepPage() {
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadZeroAmountAcash"},
        dataType:"JSON",
        success:function(response){
            if(response.STATUS === "SUCCESS" && response.DATA && response.DATA.length > 0){
                // Store all data for pagination but DON'T reset current page
                allZeroAmountData = response.DATA;
                
                // Adjust current page if data changed (entries were removed)
                var totalPages = Math.ceil(allZeroAmountData.length / entriesPerPage);
                if (currentPage > totalPages) {
                    currentPage = totalPages; // Go to last page if current page no longer exists
                }
                
                displayZeroAmountPage();
            }
        },
        error: function(xhr) {
            console.error("LoadZeroAmountAcashKeepPage error", xhr);
        }
    });
}

function ResetZeroAmountModal() {
    modalClosedManually = false;
    
    // Force close modal
    var modalEl = document.getElementById('zeroAmountModal');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
        modal.hide();
    }
    
    // Remove all backdrops and restore body
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
    
    // Clear modal content
    $(modalEl).removeClass('show').css('display', 'none').attr('aria-hidden', 'true');
    
    console.log('Modal reset complete');
}

// Add reset button to page (you can call this from browser console)
window.resetModal = ResetZeroAmountModal;

// Add isolated input test to bypass all interference
window.testInput = function() {
    console.log('Testing input isolation...');
    
    // Remove all existing event handlers
    $(document).off('input', '.zero-amount-input');
    $(document).off('change', '.zero-amount-input');
    $(document).off('blur', '.zero-amount-input');
    $(document).off('paste', '.zero-amount-input');
    $(document).off('keydown', '.zero-amount-input');
    $(document).off('keyup', '.zero-amount-input');
    
    // Remove all attributes that might interfere
    $('.zero-amount-input').removeAttr('readonly disabled');
    
    console.log('All event handlers removed, inputs should work now');
};

// Auto-run test when modal is shown
$(document).on('shown.bs.modal', '#zeroAmountModal', function () {
    setTimeout(function() {
        testInput();
    }, 500);
});

// Direct amount update functions for console use
window.updateAmount = function(rowId, amount) {
    console.log('Updating amount for row', rowId, 'to', amount);
    $(`tr[data-id="${rowId}"] .zero-amount-input`).val(amount);
    console.log('Amount updated. Current value:', $(`tr[data-id="${rowId}"] .zero-amount-input`).val());
};

window.updateAllAmounts = function(amount) {
    console.log('Updating all amounts to', amount);
    $('.zero-amount-input').val(amount);
    console.log('All amounts updated');
};

window.showAllInputs = function() {
    console.log('Current input values:');
    $('.zero-amount-input').each(function(index) {
        var rowId = $(this).closest('tr').attr('data-id');
        var value = $(this).val();
        console.log(`Row ${rowId}: ${value}`);
    });
};

window.saveCurrentAmounts = function() {
    console.log('Saving current amounts...');
    SubmitZeroAmounts();
};

function CheckForZeroAmounts(){
    // Completely disabled to prevent modal from auto-opening
    // Users must manually click the button to check for zero amounts
    return;
    
    // Don't check if modal is already visible or was manually closed
    if ($('#zeroAmountModal').hasClass('show') || modalClosedManually) {
        return;
    }
    
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadZeroAmountAcash"},
        dataType:"JSON",
        success:function(response){
            if(response.STATUS === "SUCCESS" && response.DATA && response.DATA.length > 0){
                // Only show modal if there are entries without amounts
                // and the modal isn't already visible
                if (!$('#zeroAmountModal').hasClass('show')) {
                    LoadZeroAmountAcash();
                }
            }
            // If no entries without amounts, do nothing (don't show modal)
        },
        error: function(xhr) {
            console.error("CheckForZeroAmounts error", xhr);
        }
    });
}
function LoadAcashInfoCustom(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadAcashInfo", type:"Custom", limit: 5000},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#AcashInfoTblCustom' ) ) {
                $('#AcashInfoTblCustom').DataTable().clear();
                $('#AcashInfoTblCustom').DataTable().destroy();
            }
        },
        success:function(response){
            $("#AcashInfoListCustom").empty();
            if(response && response.ACASHINFO){
                $.each(response.ACASHINFO,function(key,value){
                    $("#AcashInfoListCustom").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["Fund"] || ""}</td>
                            <td>${value["AcctNo"] || value["Identity"] || ""}</td>
                            <td>${value["AcctTitle"] || value["OwnerName"] || ""}</td>
                            <td>${value["Amount"] || "0.00"}</td>
                        </tr>
                    `);
                });
            }
            InitializeTable('#AcashInfoTblCustom');
        }
    });
}

function LoadAcashInfoRaw(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadAcashInfo", type:"Raw", limit: 5000},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#AcashInfoTblRaw' ) ) {
                $('#AcashInfoTblRaw').DataTable().clear();
                $('#AcashInfoTblRaw').DataTable().destroy();
            }
        },
        success:function(response){
            $("#AcashInfoListRaw").empty();
            if(response && response.ACASHINFO){
                $.each(response.ACASHINFO,function(key,value){
                    $("#AcashInfoListRaw").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["Fund"] || ""}</td>
                            <td>${value["AcctNo"] || value["Identity"] || ""}</td>
                            <td>${value["AcctTitle"] || value["OwnerName"] || ""}</td>
                            <td>${value["Amount"] || "0.00"}</td>
                        </tr>
                    `);
                });
            }
            InitializeTable('#AcashInfoTblRaw');
        }
    });
}

function LoadEcpayTransactionsCustom(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadEcpayTransactions", type:"Custom", limit: 5000},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#EcpayTxnTblCustom' ) ) {
                $('#EcpayTxnTblCustom').DataTable().clear();
                $('#EcpayTxnTblCustom').DataTable().destroy();
            }
        },
        success:function(response){
            $("#EcpayTxnListCustom").empty();
            if(response && response.ECPAYTXNS){
                $.each(response.ECPAYTXNS,function(key,value){
                     var amount = value["DrOther"] || value["CrOther"] || "";
                    $("#EcpayTxnListCustom").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["Identity"] || ""}</td>
                            <td>${value["OwnerName"] || ""}</td>
                            <td>${amount}</td>
                        </tr>
                    `);
                });
            }
            InitializeTable('#EcpayTxnTblCustom');
        }
    });
}

function LoadEcpayTransactionsRaw(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadEcpayTransactions", type:"Raw", limit: 5000},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#EcpayTxnTblRaw' ) ) {
                $('#EcpayTxnTblRaw').DataTable().clear();
                $('#EcpayTxnTblRaw').DataTable().destroy();
            }
        },
        success:function(response){
            $("#EcpayTxnListRaw").empty();
            if(response && response.ECPAYTXNS){
                $.each(response.ECPAYTXNS,function(key,value){
                     var amount = value["DrOther"] || value["CrOther"] || "";
                    $("#EcpayTxnListRaw").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["Identity"] || ""}</td>
                            <td>${value["OwnerName"] || ""}</td>
                            <td>${amount}</td>
                        </tr>
                    `);
                });
            }
            InitializeTable('#EcpayTxnTblRaw');
        }
    });
}

function LoadEcpayTransactionsEcpay(){
    var category = $('#ecpay-category-filter').val();
    var table = $('#EcpayTxnTblEcpay');
    var thead = table.find('thead tr');
    var tbody = $("#EcpayTxnListEcpay");

    if (!category) {
        if ($.fn.DataTable.isDataTable('#EcpayTxnTblEcpay')) {
            table.DataTable().clear().draw();
        } else {
             tbody.empty();
        }
        return;
    }

    // Define headers based on category
    var headers = [];
    if (category === 'LOADS') {
        headers = ['Date', 'Branch', 'User', 'Telco', 'Variant', 'Mobile No', 'Trace No', 'Amount Transacted', 'Amount Deducted', 'Commission', 'Convenience Fee', 'Total', 'Status', 'month', 'year'];
    } else if (category === 'PAYBILLS') {
        headers = ['Date', 'Branch', 'User', 'Biller', 'Account No', 'Reference No', 'Amount Transacted', 'Amount Deducted', 'Commission', 'Convenience Fee', 'Total', 'Status', 'month', 'year'];
    } else if (category === 'SERVICES') {
        headers = ['Date', 'Branch', 'User', 'Service', 'AccountNo', 'Reference', 'ServiceRef', 'Amount Transacted', 'Amount Deducted', 'Commission', 'Service Charge', 'Total', 'Status', 'month', 'year'];
    }

    // Reset Table
    if ( $.fn.DataTable.isDataTable( '#EcpayTxnTblEcpay' ) ) {
        table.DataTable().clear();
        table.DataTable().destroy();
    }
    
    // Completely Rebuild Table Structure to avoid column count mismatch
    table.empty();
    
    var theadHtml = '<thead><tr>';
    headers.forEach(h => { theadHtml += `<th>${h}</th>`; });
    theadHtml += '</tr></thead>';
    
    table.append(theadHtml);
    table.append('<tbody id="EcpayTxnListEcpay"></tbody>');
    
    var tbody = $("#EcpayTxnListEcpay"); // Re-select tbody after rebuild

    var limit = (category === 'PAYBILLS') ? 10 : 5000;

    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadEcpayTransactions", type:"ECPAY", limit: limit, category: category},
        dataType:"JSON",
        success:function(response){
            tbody.empty();
            if(response && response.ECPAYTXNS){
                $.each(response.ECPAYTXNS,function(key,value){
                    var row = '<tr>';
                    // Map values based on category
                    if (category === 'LOADS') {
                 // Format Date to m/d/Y H:i if possible
                 var displayDate = value["CDate"] || "";
                 if (displayDate) {
                     var d = new Date(displayDate);
                     if (!isNaN(d.getTime())) {
                         var mo = d.getMonth() + 1;
                         var da = d.getDate();
                         var yr = d.getFullYear();
                         var hr = d.getHours();
                         var mn = d.getMinutes();
                         mn = mn < 10 ? '0' + mn : mn;
                         // Handle 24h format from backend to what user likely wants (24h or 12h? Screenshot shows 20:49 which is 24h)
                         displayDate = mo + '/' + da + '/' + yr + ' ' + hr + ':' + mn;
                     }
                 }
                 row += `<td>${displayDate}</td>`;
                 row += `<td>${value["Branch"] || ""}</td>`;
                         row += `<td>${value["User"] || ""}</td>`;
                         row += `<td>${value["Telco"] || ""}</td>`;
                         row += `<td>${value["Variant"] || ""}</td>`;
                         row += `<td>${value["MobileNo"] || ""}</td>`;
                         row += `<td>${value["TraceNo"] || ""}</td>`;
                         row += `<td>${value["DrOther"] || "0.00"}</td>`; // Amount Transacted
                         row += `<td>${value["CrOther"] || "0.00"}</td>`; // Amount Deducted
                         row += `<td>${value["Commission"] || "0.00"}</td>`;
                         row += `<td>${value["ConvenienceFee"] || "0.00"}</td>`;
                         row += `<td>${value["TransactionTotal"] || "0.00"}</td>`;
                         row += `<td>${value["Status"] || ""}</td>`;
                         row += `<td>${value["TransactionMonth"] || ""}</td>`;
                         row += `<td>${value["TransactionYear"] || ""}</td>`;
                    } else if (category === 'PAYBILLS') {
                         row += `<td>${value["CDate"] || ""}</td>`;
                         row += `<td>${value["Branch"] || ""}</td>`;
                         row += `<td>${value["User"] || ""}</td>`;
                         row += `<td>${value["Biller"] || ""}</td>`;
                         row += `<td>${value["AccountNo"] || ""}</td>`;
                         row += `<td>${value["ReferenceNo"] || ""}</td>`;
                         row += `<td>${value["DrOther"] || "0.00"}</td>`;
                         row += `<td>${value["CrOther"] || "0.00"}</td>`;
                         row += `<td>${value["Commission"] || "0.00"}</td>`;
                         row += `<td>${value["ConvenienceFee"] || "0.00"}</td>`;
                         row += `<td>${value["TransactionTotal"] || "0.00"}</td>`;
                         row += `<td>${value["Status"] || ""}</td>`;
                         row += `<td>${value["TransactionMonth"] || ""}</td>`;
                         row += `<td>${value["TransactionYear"] || ""}</td>`;
                    } else if (category === 'SERVICES') {
                         row += `<td>${value["CDate"] || ""}</td>`;
                         row += `<td>${value["Branch"] || ""}</td>`;
                         row += `<td>${value["User"] || ""}</td>`;
                         row += `<td>${value["Service"] || ""}</td>`;
                         row += `<td>${value["AccountNo"] || ""}</td>`;
                         row += `<td>${value["ReferenceNo"] || ""}</td>`;
                         row += `<td>${value["ServiceRef"] || ""}</td>`;
                         row += `<td>${value["DrOther"] || "0.00"}</td>`;
                         row += `<td>${value["CrOther"] || "0.00"}</td>`;
                         row += `<td>${value["Commission"] || "0.00"}</td>`;
                         row += `<td>${value["ServiceCharge"] || "0.00"}</td>`;
                         row += `<td>${value["TransactionTotal"] || "0.00"}</td>`;
                         row += `<td>${value["Status"] || ""}</td>`;
                         row += `<td>${value["TransactionMonth"] || ""}</td>`;
                         row += `<td>${value["TransactionYear"] || ""}</td>`;
                    }
                    row += '</tr>';
                    tbody.append(row);
                });
            }
            InitializeTable('#EcpayTxnTblEcpay');
        }
    });
}

function LoadAcashInfo(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadAcashInfo", limit: 5000},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#AcashInfoTbl' ) ) {
                $('#AcashInfoTbl').DataTable().clear();
                $('#AcashInfoTbl').DataTable().destroy();
            }
        },
        success:function(response){
            $("#AcashInfoList").empty();
            if(response && response.ACASHINFO){
                $.each(response.ACASHINFO,function(key,value){
                    $("#AcashInfoList").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["Fund"] || ""}</td>
                            <td>${value["AcctNo"] || ""}</td>
                            <td>${value["AcctTitle"] || value["Payee"] || ""}</td>
                            <td>${value["Amount"] || "0.00"}</td>
                        </tr>
                    `);
                });
            }

            if ($.fn.DataTable) {
                if ($.fn.DataTable.isDataTable('#AcashInfoTbl')) {
                    $('#AcashInfoTbl').DataTable().destroy();
                }
                AcashInfoTbl = $('#AcashInfoTbl').DataTable({
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    lengthChange: false,
                    info: true,
                    paging: true,
                    responsive: true,
                });
            }
        },
        error:function(xhr){
            console.error("LoadAcashInfo error", xhr);
        }
    });
}

function LoadEcpayTransactions(){
    $.ajax({
        url:"../../routes/profiling/acashinfo.route.php",
        type:"POST",
        data:{action:"LoadEcpayTransactions", limit: 5000},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable && $.fn.DataTable.isDataTable( '#EcpayTxnTbl' ) ) {
                $('#EcpayTxnTbl').DataTable().clear();
                $('#EcpayTxnTbl').DataTable().destroy();
            }
        },
        success:function(response){
            $("#EcpayTxnList").empty();
            if(response && response.ECPAYTXNS){
                $.each(response.ECPAYTXNS,function(key,value){
                    var amount = "";
                    if (value["DrOther"] && value["DrOther"] !== "0" && value["DrOther"] !== 0) {
                        amount = value["DrOther"];
                    } else if (value["CrOther"] && value["CrOther"] !== "0" && value["CrOther"] !== 0) {
                        amount = value["CrOther"];
                    }
                    $("#EcpayTxnList").append(`
                        <tr>
                            <td>${value["CDate"] || ""}</td>
                            <td>${value["Branch"] || ""}</td>
                            <td>${value["AcctNo"] || ""}</td>
                            <td>${value["Payee"] || ""}</td>
                            <td>${amount}</td>
                        </tr>
                    `);
                });
            }

            if ($.fn.DataTable) {
                if ($.fn.DataTable.isDataTable('#EcpayTxnTbl')) {
                    $('#EcpayTxnTbl').DataTable().destroy();
                }
                EcpayTxnTbl = $('#EcpayTxnTbl').DataTable({
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    lengthChange: false,
                    info: true,
                    paging: true,
                    responsive: true,
                });
            }
        },
        error:function(xhr){
            console.error("LoadEcpayTransactions error", xhr);
        }
    });
}

function InitializeOtherTables(){
    var ids = ['#AcashInfoTblCustom', '#AcashInfoTblRaw', '#EcpayTxnTblCustom', '#EcpayTxnTblRaw'];
    if ($.fn.DataTable) {
        $.each(ids, function(index, id) {
            if (!$.fn.DataTable.isDataTable(id)) {
                $(id).DataTable({
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    lengthChange: false,
                    info: false,
                    paging: true,
                    responsive: true,
                });
            }
        });
    }
}

function InitializeTable(id) {
    if ($.fn.DataTable) {
        if (!$.fn.DataTable.isDataTable(id)) {
            $(id).DataTable({
                pageLength: 10,
                searching: true,
                ordering: true,
                lengthChange: false,
                info: true,
                paging: true,
                responsive: true,
            });
        }
    }
}

function PrintAcashInfo() {
    var type = "Main";
    if ($('#acash-custom-tab').hasClass('active')) {
        type = "Custom";
    } else if ($('#acash-raw-tab').hasClass('active')) {
        type = "Raw";
    }
    
    window.open('../../routes/profiling/acashinfo.route.php?action=PrintAcashReport&type=' + type, '_blank');
}

function PrintEcpayTxn() {
    var type = "Main";
    if ($('#ecpay-custom-tab').hasClass('active')) {
        type = "Custom";
    } else if ($('#ecpay-raw-tab').hasClass('active')) {
        type = "Raw";
    } else if ($('#ecpay-ecpay-tab').hasClass('active')) {
        type = "ECPAY";
    }
    
    window.open('../../routes/profiling/acashinfo.route.php?action=PrintEcpayReport&type=' + type, '_blank');
}

function LoadYearComparison(source) {
    console.log('LoadYearComparison called with source:', source);
    
    var monthsSelect = $('#' + source + '-year-comparison-months');
    var selectedMonths = monthsSelect.val() || [];
    console.log('Selected months:', selectedMonths);
    
    // Get user-selected years
    var year1 = $('#' + source + '-year1-select').val();
    var year2 = $('#' + source + '-year2-select').val();
    var year3 = $('#' + source + '-year3-select').val();
    console.log('Selected years:', {year1, year2, year3});
    
    var years = [];
    if (year1) years.push(year1);
    if (year2) years.push(year2);
    if (year3) years.push(year3);
    
    console.log('Years array:', years);
    
    if (years.length === 0) {
        console.log('No years selected, returning');
        return;
    }
    
    // Load real data from backend
    $.ajax({
        url: "../../routes/profiling/acashinfo.route.php",
        type: "POST",
        data: { 
            action: "LoadYearlyData",
            source: source,
            years: years,
            months: selectedMonths
        },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS === "SUCCESS" && response.DATA) {
                console.log('Real data loaded:', response.DATA);
                
                // Process the data to match expected format
                var allData = response.DATA.map(function(item) {
                    return {
                        year: item.year,
                        commission: parseFloat(item.commission).toFixed(2),
                        amount: parseFloat(item.amount).toFixed(2),
                        count: item.count,
                        monthlyData: item.monthlyData || []
                    };
                });
                
                console.log('Processed allData:', allData);
                
                // Render line graph
                RenderHistoricalChart(source, allData, selectedMonths);
                
                // Populate comparison table
                PopulateComparisonTable(source, allData, selectedMonths);
            } else {
                console.error('Error loading yearly data:', response);
                // Fallback to mock data if backend fails
                LoadYearComparisonFallback(source, selectedMonths, years);
            }
        },
        error: function(xhr) {
            console.error('AJAX error loading yearly data:', {
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                url: this.url,
                requestPayload: this.data
            });
            // Fallback to mock data if AJAX fails
            LoadYearComparisonFallback(source, selectedMonths, years);
        }
    });
}

// Fallback function using mock data
function LoadYearComparisonFallback(source, selectedMonths, years) {
    console.log('Using fallback mock data');
    
    // Sort years to ensure lowest year is first
    years.sort(function(a, b) { return parseInt(a) - parseInt(b); });
    
    // Generate data for all selected years
    var allData = [];
    years.forEach(function(year) {
        var mockData = GenerateMockData(source, year, selectedMonths);
        allData.push({
            year: year,
            commission: (parseFloat(mockData.totals.amount) * 0.05).toFixed(2), // 5% commission
            amount: parseFloat(mockData.totals.amount).toFixed(2),
            count: mockData.totals.count,
            monthlyData: mockData.data
        });
    });
    
    console.log('Generated allData:', allData);
    
    // Render line graph
    RenderHistoricalChart(source, allData, selectedMonths);
    
    // Populate comparison table
    PopulateComparisonTable(source, allData, selectedMonths);
}

function RenderHistoricalChart(source, allData, selectedMonths) {
    console.log('RenderHistoricalChart called with:', {source, allData, selectedMonths});
    
    var chartId = source + '-historical-chart';
    console.log('Chart ID:', chartId);
    
    var ctx = document.getElementById(chartId);
    console.log('Chart element found:', ctx);
    
    if (!ctx) {
        console.error('Chart element not found:', chartId);
        return;
    }
    
    // Debug: Log the raw data structure
    console.log('Raw allData structure:', JSON.stringify(allData, null, 2));
    
    // Normalize selectedMonths to integers
    const normalizedMonths = selectedMonths.map(m => parseInt(m, 10));
    console.log('Normalized selectedMonths:', normalizedMonths);
    
    // Prepare chart data
    var datasets = [];
    var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    // Determine which months to include
    var allMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    var monthsToGenerateDataFor = normalizedMonths.length > 0 ? normalizedMonths : allMonths;
    
    // Generate labels based on selected months
    var labels = [];
    monthsToGenerateDataFor.forEach(function(month) {
        labels.push(monthNames[month - 1]);
    });
    
    console.log('Generated labels:', labels);
    console.log('Months to generate data for:', monthsToGenerateDataFor);
    
    // Colors for different years
    var yearColors = ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6f42c1', '#6610f2', '#20c997', '#fd7e14'];
    
    allData.forEach(function(yearData, yearIndex) {
        console.log('Processing yearData for', yearData.year, ':', JSON.stringify(yearData, null, 2));
        
        var color = yearColors[yearIndex % yearColors.length];
        var monthlyAmounts = [];
        var monthlyCommissions = [];
        
        // Generate data only for the months we want to show
        monthsToGenerateDataFor.forEach(function(month) {
            var monthData = yearData.monthlyData && yearData.monthlyData.find(function(item) {
                // Normalize both month values to integers for comparison
                const itemMonth = parseInt(item.month, 10);
                const targetMonth = parseInt(month, 10);
                return itemMonth === targetMonth;
            });
            var amount = monthData ? parseFloat(monthData.amount) : 0;
            // Calculate commission as 5% of amount if not provided by backend
            var commission = monthData && monthData.commission ? parseFloat(monthData.commission) : (amount * 0.05);
            monthlyAmounts.push(amount);
            monthlyCommissions.push(commission);
            console.log('Month', month, 'data:', monthData, 'Amount:', amount, 'Commission:', commission, 'Has commission field:', monthData ? !!monthData.commission : false);
        });
        
        console.log('Monthly amounts for', yearData.year, ':', monthlyAmounts);
        console.log('Monthly commissions for', yearData.year, ':', monthlyCommissions);
        
        // Add amount dataset for this year
        datasets.push({
            label: yearData.year + ' - Amount',
            data: monthlyAmounts,
            borderColor: color,
            backgroundColor: color + '20',
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.1
        });
        
        // Add commission dataset for this year
        datasets.push({
            label: yearData.year + ' - Commission',
            data: monthlyCommissions,
            borderColor: color + 'CC', // Lighter version of the same color
            backgroundColor: color + '10',
            borderWidth: 2,
            borderDash: [5, 5], // Dashed line to distinguish from amount
            pointRadius: 3,
            pointHoverRadius: 5,
            tension: 0.1
        });
    });
    
    console.log('Chart datasets:', datasets);
    
    // Destroy existing chart
    if (window[chartId + 'Chart']) {
        console.log('Destroying existing chart');
        window[chartId + 'Chart'].destroy();
    }
    
    // Create new chart
    console.log('Creating new chart...');
    window[chartId + 'Chart'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            spanGaps: true, // Important: Connect lines across null values
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var dataset = context.dataset;
                            var label = dataset.label || '';
                            var value = context.parsed.y;
                            if (value !== null && value !== undefined) {
                                return label + ': ₱' + value.toLocaleString();
                            }
                            return null;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    console.log('Chart created successfully');
}

function PopulateComparisonTable(source, allData, selectedMonths) {
    var tbodyId = source + '-comparison-tbody';
    var $tbody = $('#' + tbodyId);
    $tbody.empty();
    
    if (allData.length === 0) {
        $tbody.append('<tr><td colspan="4" class="text-center text-muted"><em>No data available</em></td></tr>');
        return;
    }
    
    var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    // Table header with specific blue colors from image
    var $tableHead = $('#' + source + '-comparison-table thead tr');
    $tableHead.html(
        '<th style="width: 12%; text-align: center; background: #ffffff; color: #4a5568; font-weight: 600; padding: 16px 8px; border: 1px solid #e2e8f0; border-radius: 8px 0 0 0;">Year</th>' +
        '<th style="width: 26%; text-align: center; background: #e0f2f7; color: #4a5568; font-weight: 600; padding: 16px 8px; border: 1px solid #e2e8f0;">Commission</th>' +
        '<th style="width: 26%; text-align: center; background: #b3e0f2; color: #4a5568; font-weight: 600; padding: 16px 8px; border: 1px solid #e2e8f0;">Amount of Transaction</th>' +
        '<th style="width: 36%; text-align: center; background: #cbe0f2; color: #4a5568; font-weight: 600; padding: 16px 8px; border: 1px solid #e2e8f0; border-radius: 0 8px 0 0;">No. of Transaction</th>'
    );
    
    // Add data rows for each year
    allData.forEach(function(yearData, yearIndex) {
        var yearColor = yearIndex % 2 === 0 ? '#f8fafc' : '#ffffff';
        
        // Main row with year and data
        var mainRow = '<tr style="background-color: ' + yearColor + '; border-bottom: 1px solid #e2e8f0;">' +
            '<td style="font-weight: 600; text-align: center; vertical-align: top; padding: 20px 8px; font-size: 1.05em; color: #4a5568; border-right: 1px solid #e2e8f0;">' + 
            yearData.year + '</td>';
        
        // Commission column with light blue theme
        var commissionContent = '';
        if (yearData.monthlyData && yearData.monthlyData.length > 0) {
            commissionContent = '<div style="background: #f0f9ff; border-radius: 8px; padding: 16px; margin: 8px; box-shadow: 0 1px 3px rgba(14, 165, 233, 0.1); border: 1px solid #e0f2f7;">';
            
            // Monthly data in a clean grid
            commissionContent += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">';
            yearData.monthlyData.forEach(function(monthData) {
                var monthName = monthNames[monthData.month - 1] || 'Month ' + monthData.month;
                var commission = monthData.commission ? parseFloat(monthData.commission) : (parseFloat(monthData.amount || 0) * 0.05);
                
                commissionContent += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: white; border-radius: 4px; border: 1px solid #e0f2f7;">' +
                    '<span style="font-weight: 500; color: #64748b; font-size: 0.85em;">' + monthName + '</span>' +
                    '<span style="font-weight: 600; color: #0ea5e9; font-size: 0.9em;">₱' + commission.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>' +
                    '</div>';
            });
            commissionContent += '</div>';
            
            // Total section
            commissionContent += '<div style="background: #0ea5e9; color: white; padding: 12px; border-radius: 6px; text-align: center; font-weight: 600; font-size: 1em; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.2);">' +
                'Total: ₱' + parseFloat(yearData.commission).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                '</div>';
            commissionContent += '</div>';
        } else {
            commissionContent = '<div style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 8px; text-align: center; border: 1px solid #e2e8f0;">' +
                '<div style="color: #64748b; margin-bottom: 12px; font-size: 0.9em;">No monthly data available</div>' +
                '<div style="background: #0ea5e9; color: white; padding: 12px; border-radius: 6px; font-weight: 600;">Total: ₱' + 
                parseFloat(yearData.commission).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div></div>';
        }
        
        // Amount of Transaction column with medium blue theme
        var amountContent = '';
        if (yearData.monthlyData && yearData.monthlyData.length > 0) {
            amountContent = '<div style="background: #f0f9ff; border-radius: 8px; padding: 16px; margin: 8px; box-shadow: 0 1px 3px rgba(59, 130, 246, 0.1); border: 1px solid #b3e0f2;">';
            
            // Monthly data in a clean grid
            amountContent += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">';
            yearData.monthlyData.forEach(function(monthData) {
                var monthName = monthNames[monthData.month - 1] || 'Month ' + monthData.month;
                var amount = parseFloat(monthData.amount) || 0;
                
                amountContent += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: white; border-radius: 4px; border: 1px solid #b3e0f2;">' +
                    '<span style="font-weight: 500; color: #64748b; font-size: 0.85em;">' + monthName + '</span>' +
                    '<span style="font-weight: 600; color: #3b82f6; font-size: 0.9em;">₱' + amount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span>' +
                    '</div>';
            });
            amountContent += '</div>';
            
            // Total section
            amountContent += '<div style="background: #3b82f6; color: white; padding: 12px; border-radius: 6px; text-align: center; font-weight: 600; font-size: 1em; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);">' +
                'Total: ₱' + parseFloat(yearData.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                '</div>';
            amountContent += '</div>';
        } else {
            amountContent = '<div style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 8px; text-align: center; border: 1px solid #e2e8f0;">' +
                '<div style="color: #64748b; margin-bottom: 12px; font-size: 0.9em;">No monthly data available</div>' +
                '<div style="background: #3b82f6; color: white; padding: 12px; border-radius: 6px; font-weight: 600;">Total: ₱' + 
                parseFloat(yearData.amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div></div>';
        }
        
        // No. of Transaction column with light-medium blue theme
        var transactionContent = '';
        if (yearData.monthlyData && yearData.monthlyData.length > 0) {
            transactionContent = '<div style="background: #f0f9ff; border-radius: 8px; padding: 16px; margin: 8px; box-shadow: 0 1px 3px rgba(99, 102, 241, 0.1); border: 1px solid #cbe0f2;">';
            
            // Monthly data in a clean grid
            transactionContent += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">';
            yearData.monthlyData.forEach(function(monthData) {
                var monthName = monthNames[monthData.month - 1] || 'Month ' + monthData.month;
                var count = parseInt(monthData.count) || 0;
                
                transactionContent += '<div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: white; border-radius: 4px; border: 1px solid #cbe0f2;">' +
                    '<span style="font-weight: 500; color: #64748b; font-size: 0.85em;">' + monthName + '</span>' +
                    '<span style="font-weight: 600; color: #6366f1; font-size: 0.9em;">' + count.toLocaleString() + '</span>' +
                    '</div>';
            });
            transactionContent += '</div>';
            
            // Total section
            transactionContent += '<div style="background: #6366f1; color: white; padding: 12px; border-radius: 6px; text-align: center; font-weight: 600; font-size: 1em; box-shadow: 0 2px 4px rgba(99, 102, 241, 0.2);">' +
                'Total: ' + (yearData.count || 0).toLocaleString() +
                '</div>';
            transactionContent += '</div>';
        } else {
            transactionContent = '<div style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 8px; text-align: center; border: 1px solid #e2e8f0;">' +
                '<div style="color: #64748b; margin-bottom: 12px; font-size: 0.9em;">No monthly data available</div>' +
                '<div style="background: #6366f1; color: white; padding: 12px; border-radius: 6px; font-weight: 600;">Total: ' + 
                (yearData.count || 0).toLocaleString() + '</div></div>';
        }
        
        // Add the complete row
        mainRow += '<td style="padding: 8px; vertical-align: top;">' + commissionContent + '</td>';
        mainRow += '<td style="padding: 8px; vertical-align: top;">' + amountContent + '</td>';
        mainRow += '<td style="padding: 8px; vertical-align: top;">' + transactionContent + '</td>';
        mainRow += '</tr>';
        
        $tbody.append(mainRow);
    });
}

function LoadYearData(source, year, months, yearNum) {
    // Create mock data since backend doesn't exist yet
    var mockData = GenerateMockData(source, year, months);
    
    // Simulate async operation
    setTimeout(function() {
        RenderYearComparison(mockData, source, yearNum, year);
    }, 300 + (yearNum * 100)); // Stagger the loading times
}

function GenerateMockData(source, year, months) {
    var data = [];
    var totalAmount = 0;
    var totalCount = 0;
    
    // Generate data for selected months or all months
    var monthsToGenerate = months.length > 0 ? months : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    
    monthsToGenerate.forEach(function(month) {
        var baseAmount = source === 'acash' ? 50000 : 30000;
        var baseCount = source === 'acash' ? 50 : 30;
        
        // Add some randomness
        var amount = baseAmount + Math.random() * 40000 - 20000;
        var count = Math.floor(baseCount + Math.random() * 40 - 20);
        
        if (amount < 0) amount = 0;
        if (count < 0) count = 0;
        
        data.push({
            month: month,
            amount: amount.toFixed(2),
            count: count
        });
        
        totalAmount += amount;
        totalCount += count;
    });
    
    return {
        data: data,
        totals: {
            amount: totalAmount.toFixed(2),
            count: totalCount
        }
    };
}

function RenderYearComparison(data, source, yearNum, year) {
    // Update year label
    $('#' + source + '-year' + yearNum + ' .card-header h6').text(year);
    
    // Update statistics
    var totalAmount = data.totals ? data.totals.amount : 0;
    var totalCount = data.totals ? data.totals.count : 0;
    
    $('#' + source + '-year' + yearNum + '-amount').text('₱' + parseFloat(totalAmount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#' + source + '-year' + yearNum + '-count').text(totalCount);
    
    // Render mini chart
    var chartId = source + '-year' + yearNum + '-chart';
    
    // Prepare chart data
    var chartData = [];
    var labels = [];
    
    if (data.data && data.data.length > 0) {
        // Group data by month
        var monthlyData = {};
        data.data.forEach(function(item) {
            var month = parseInt(item.month) || 1;
            if (!monthlyData[month]) {
                monthlyData[month] = {amount: 0, count: 0};
            }
            monthlyData[month].amount += parseFloat(item.amount) || 0;
            monthlyData[month].count += parseInt(item.count) || 0;
        });
        
        // Create chart data for all months
        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        for (var i = 1; i <= 12; i++) {
            labels.push(monthNames[i-1]);
            chartData.push(monthlyData[i] ? monthlyData[i].amount : 0);
        }
    } else {
        // Empty data
        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        for (var i = 0; i < 12; i++) {
            labels.push(monthNames[i]);
            chartData.push(0);
        }
    }
    
    // Destroy existing chart if it exists
    if (window[chartId + 'Chart']) {
        window[chartId + 'Chart'].destroy();
    }
    
    // Create new chart
    var ctx = document.getElementById(chartId);
    if (ctx) {
        window[chartId + 'Chart'] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monthly Amount',
                    data: chartData,
                    borderColor: source === 'acash' ? '#0d6efd' : '#198754',
                    backgroundColor: source === 'acash' ? 'rgba(13, 110, 253, 0.1)' : 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
}

// Initialize year comparison on page load - years are now in HTML directly
$(document).ready(function() {
    console.log("Year comparison page loaded - years are available in HTML");
    
    // Auto-load default year comparison data for the last 2 years
    setTimeout(function() {
        // Set default years (current year and previous year)
        var currentYear = new Date().getFullYear();
        var previousYear = currentYear - 1;
        
        // Set the dropdown values
        $('#acash-year1-select').val(currentYear.toString());
        $('#acash-year2-select').val(previousYear.toString());
        
        // Load the comparison data
        LoadYearComparison('acash');
    }, 1000); // Delay to ensure page is fully loaded
});

// Analytics Logic

function LoadSpecificAnalytics(source, chartId, listId) {
    var filterId = source === 'acash' ? '#acash-analytics-filter' : '#ecpay-analytics-filter';
    var metricId = source === 'acash' ? '#acash-analytics-metric' : '#ecpay-analytics-metric';
    var yearId = source === 'acash' ? '#acash-chart-year' : '#ecpay-chart-year';
    var monthId = source === 'acash' ? '#acash-chart-month' : '#ecpay-chart-month';

    var selectedFilter = $(filterId).val();
    var metric = $(metricId).val();
    var chartType = 'pie'; // Always use pie chart
    var year = $(yearId).val();
    var month = $(monthId).val();

    var requestType = '';
    var requestFilterType = '';
    var requestFilterValue = '';
    
    var knownCategories = ['HEADOFFICE', 'ISYNERGIES, INC', 'EXTERNAL CLIENT', 'MFI BRANCHES', 'STAFF', 'BUSINESS UNIT', 'OTHERS', 'INDIVIDUAL', 'LOADS', 'PAYBILLS', 'SERVICES'];

    if (selectedFilter === 'Overview') {
        // Show all branches
        requestType = 'branch';
        requestFilterType = '';
        requestFilterValue = '';
    } else if (knownCategories.includes(selectedFilter)) {
        // Show individuals within the selected category
        requestType = 'individual'; 
        requestFilterType = 'category';
        requestFilterValue = selectedFilter;
    } else {
        // Show individuals within the selected branch
        requestType = 'individual'; 
        requestFilterType = 'branch';
        requestFilterValue = selectedFilter;
    }

    $.ajax({
        url: "../../process/analytics/analytics.process.php",
        type: "GET",
        data: {
            action: "get_top_performers",
            type: requestType,
            filter_type: requestFilterType,
            filter_value: requestFilterValue,
            metric: metric,
            source: source,
            limit: 1000, // Show all in graph
            year: year,
            month: month
        },
        dataType: "JSON",
        success: function(response) {
            RenderAnalyticsChart(response, metric, source, chartId);
            RenderAnalyticsList(response.slice(0, 5), metric, listId);
        },
        error: function(xhr) {
            console.error(source + " Analytics Load Error", xhr);
        }
    });
}

function LoadFilterValues() {
    // Load ACash Filters
    $.ajax({
        url: "../../process/analytics/analytics.process.php",
        type: "GET",
        data: { action: "get_filters", source: "acash" },
        dataType: "JSON",
        success: function(response) {
            populateFilterDropdown('#acash-analytics-filter', response);
        }
    });

    // Load ECpay Filters
    $.ajax({
        url: "../../process/analytics/analytics.process.php",
        type: "GET",
        data: { action: "get_filters", source: "ecpay" },
        dataType: "JSON",
        success: function(response) {
            populateFilterDropdown('#ecpay-analytics-filter', response);
        }
    });
}

function populateFilterDropdown(selector, data) {
    var $dropdown = $(selector);
    $dropdown.empty();
    $dropdown.append('<option value="">Overview (All Branches)</option>');
    
    if (data.categories && data.categories.length > 0) {
        $.each(data.categories, function(index, cat) {
            $dropdown.append(`<option value="${cat}">${cat}</option>`);
        });
    }
    
    if (data.branches && data.branches.length > 0) {
        var branchGroup = '<optgroup label="Branches">';
        $.each(data.branches, function(index, branch) {
            branchGroup += `<option value="${branch}">${branch}</option>`;
        });
        branchGroup += '</optgroup>';
        $dropdown.append(branchGroup);
    }
}

function RenderAnalyticsChart(data, metric, source, containerId) {
    console.log('RenderAnalyticsChart called with:', { data: data, metric: metric, source: source, containerId: containerId });
    console.log('Data length:', data.length);
    
    var categories = [];
    var seriesData = [];
    var seriesData2 = [];
    
    $.each(data, function(index, item) {
        var categoryName = item.Name || "Unknown";
        // Replace dash with HEADOFFICE
        if (categoryName === "-") {
            categoryName = "HEADOFFICE";
        }
        categories.push(categoryName);
        
        if (metric === 'all') {
            seriesData.push(parseFloat(item.TotalAmount) || 0);
            seriesData2.push(parseInt(item.TransactionCount) || 0);
        } else {
            seriesData.push(metric === 'amount' ? parseFloat(item.TotalAmount) || 0 : parseInt(item.TransactionCount) || 0);
        }
    });

    console.log('Processed categories:', categories);
    console.log('Series data:', seriesData);
    console.log('Series data2:', seriesData2);

    var sourceName = source === 'acash' ? 'ACash ' : (source === 'ecpay' ? 'ECPay ' : '');
    var titleText = sourceName + (metric === 'amount' ? 'Transaction Amount' : (metric === 'count' ? 'Transaction Count' : 'Transaction Amount & Count'));

    // Always use pie chart
    var pieData = metric === 'amount' || metric === 'all' ? seriesData : seriesData2;
    
    console.log('Final pie data:', pieData);
    console.log('Pie data length:', pieData.length);
    
    var total = pieData.reduce((a, b) => a + b, 0);
    console.log('Calculated total:', total);
    
    var options = {
        series: pieData,
        chart: {
            type: 'pie',
            height: 350,
            fontFamily: 'inherit',
            toolbar: { show: false }
        },
        labels: categories,
        colors: ['#3a57e8', '#f16a1b', '#00d4aa', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f7b731', '#5f27cd', '#00d2d3', '#ff9ff3'],
        title: {
            text: titleText,
            align: 'left',
            style: {
                fontSize: '16px',
                fontWeight: '600',
                color: '#333'
            }
        },
        legend: {
            position: 'right',
            horizontalAlign: 'center',
            offsetY: 0,
            fontSize: '12px',
            markers: {
                width: 12,
                height: 12,
                strokeWidth: 0,
                strokeColor: '#fff',
                fillColors: undefined,
                radius: 12,
                customHTML: undefined,
                onClick: undefined,
                offsetX: 0,
                offsetY: 0
            },
            itemMargin: {
                horizontal: 5,
                vertical: 5
            }
        },
        tooltip: {
            theme: 'light',
            y: {
                formatter: function (val) {
                    if (metric === 'amount' || metric === 'all') {
                        return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(val);
                    }
                    return val + " txns";
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        plotOptions: {
            pie: {
                expandOnClick: false,
                dataLabels: {
                    minAngleToShowLabel: 10,
                    offset: 0
                }
            }
        }
    };

    if (document.querySelector(containerId)) {
        document.querySelector(containerId).innerHTML = "";
        if (ApexCharts) {
            var chart = new ApexCharts(document.querySelector(containerId), options);
            chart.render();
        }
    }
}

function RenderAnalyticsList(data, metric, containerId) {
    var list = $(containerId);
    list.empty();
    
    if (data.length === 0) {
        list.append('<li class="list-group-item text-center p-2">No data available</li>');
        return;
    }

    // Calculate total for percentage calculation
    var total = 0;
    if (metric === 'all') {
        total = data.reduce((sum, item) => sum + (parseFloat(item.TotalAmount) || 0), 0);
    } else if (metric === 'amount') {
        total = data.reduce((sum, item) => sum + (parseFloat(item.TotalAmount) || 0), 0);
    } else {
        total = data.reduce((sum, item) => sum + (parseInt(item.TransactionCount) || 0), 0);
    }

    $.each(data, function(index, item) {
        var itemName = item.Name || "Unknown";
        // Replace dash with HEADOFFICE
        if (itemName === "-") {
            itemName = "HEADOFFICE";
        }
        
        var valueHtml = "";
        
        if (metric === 'all') {
             var amount = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(item.TotalAmount);
             valueHtml = `
                <div class="d-flex flex-column align-items-end">
                    <span class="badge bg-primary rounded-pill mb-1" style="font-size: 12px;">${amount}</span>
                    <span class="badge bg-secondary rounded-pill" style="font-size: 12px;">${item.TransactionCount} txns</span>
                </div>`;
        } else {
             var value = metric === 'amount' 
                ? new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(item.TotalAmount)
                : item.TransactionCount + " txns";
             valueHtml = `<span class="badge bg-primary rounded-pill" style="font-size: 12px;">${value}</span>`;
        }
            
        list.append(`
            <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                <div class="d-flex align-items-center text-truncate" style="max-width: 120px;">
                    <span class="me-1" style="font-size: 13px; font-weight: bold;">${index + 1}.</span>
                    <span class="text-truncate" style="font-size: 13px;" title="${itemName}">${itemName}</span>
                </div>
                ${valueHtml}
            </li>
        `);
    });
}

$(document).ready(function() {
    
    // Initial Load for Active Tab (Default: Acash Info)
    if ($('#acash-info-tab').hasClass('active')) {
        LoadSpecificAnalytics('acash', '#acash-analytics-chart', '#acash-analytics-list');
    } else if ($('#ecpay-transaction-tab').hasClass('active')) {
        LoadSpecificAnalytics('ecpay', '#ecpay-analytics-chart', '#ecpay-analytics-list');
    }
    
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (e.target.id === 'acash-info-tab') {
             LoadSpecificAnalytics('acash', '#acash-analytics-chart', '#acash-analytics-list');
        } else if (e.target.id === 'ecpay-transaction-tab') {
             LoadSpecificAnalytics('ecpay', '#ecpay-analytics-chart', '#ecpay-analytics-list');
        }
    });
});

function LoadEmails() {
    $.ajax({
        url: "../../routes/profiling/acashinfo.route.php",
        type: "POST",
        data: { action: "LoadEmails" },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS === "SUCCESS") {
                var dropdown = $("#email-search-dropdown");
                var currentVal = dropdown.val();
                
                dropdown.empty().append('<option value="">Select Email...</option>');
                
                if (response.EMAILS && response.EMAILS.length > 0) {
                    $.each(response.EMAILS, function(index, email) {
                        dropdown.append(`<option value="${email}">${email}</option>`);
                    });
                }
                
                if ($.fn.select2) {
                    dropdown.select2({
                        placeholder: "Select Email...",
                        allowClear: true,
                        dropdownParent: $('#maintenance'),
                        width: '100%'
                    });
                }
                
                if (currentVal) {
                    dropdown.val(currentVal).trigger('change');
                }
            }
        },
        error: function(xhr) {
            console.error("LoadEmails error", xhr);
        }
    });
}

function LoadBranches() {
    $.ajax({
        url: "../../routes/profiling/acashinfo.route.php",
        type: "POST",
        data: { action: "LoadBranches" },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS === "SUCCESS") {
                var dropdown = $("#branch-search-dropdown");
                var currentVal = dropdown.val();
                
                dropdown.empty().append('<option value="">Select Branch...</option>');
                
                // Add fixed options as requested
                var fixedOptions = [
                    'HEADOFFICE', 
                    'INDIVIDUAL', 
                    'EXTERNAL CLIENT', 
                    'OTHERS',
                    'MFI BRANCHES', 
                    'BUSINESS UNIT'
                ];
                
                $.each(fixedOptions, function(index, opt) {
                    dropdown.append(`<option value="${opt}">${opt}</option>`);
                });
                
                if ($.fn.select2) {
                    dropdown.select2({
                        placeholder: "Select Branch...",
                        allowClear: true,
                        dropdownParent: $('#maintenance'),
                        width: '100%'
                    });
                }
                
                if (currentVal) {
                    dropdown.val(currentVal).trigger('change');
                }
            }
        },
        error: function(xhr) {
            console.error("LoadBranches error", xhr);
        }
    });
}

function LoadMaintenanceTable() {
    console.log("Loading maintenance table...");
    $.ajax({
        url: "../../routes/profiling/acashinfo.route.php",
        type: "POST",
        data: { action: "LoadMaintenanceData" },
        dataType: "JSON",
        success: function(response) {
            console.log("Maintenance response:", response);
            if (response.STATUS === "SUCCESS") {
                var tbody = $("#maintenance-list");
                tbody.empty();
                
                console.log("Data length:", response.DATA ? response.DATA.length : 0);
                
                if (response.DATA && response.DATA.length > 0) {
                    $.each(response.DATA, function(index, item) {
                        // Combine all years from both ACASH and ECPAY counts
                        var allYears = new Set();
                        Object.keys(item.acash_year_counts || {}).forEach(function(year) {
                            allYears.add(year);
                        });
                        Object.keys(item.ecpay_year_counts || {}).forEach(function(year) {
                            allYears.add(year);
                        });
                        
                        // Sort years in descending order
                        var sortedYears = Array.from(allYears).sort(function(a, b) {
                            return b - a;
                        });
                        
                        // Generate year totals HTML
                        var yearTotalsHtml = '';
                        if (sortedYears.length > 0) {
                            yearTotalsHtml = '<div class="year-totals mt-2">';
                            sortedYears.forEach(function(year) {
                                var acashCount = item.acash_year_counts[year] || 0;
                                var ecpayCount = item.ecpay_year_counts[year] || 0;
                                var totalCount = acashCount + ecpayCount;
                                
                                yearTotalsHtml += '<small class="badge bg-secondary me-1">' + year + ': ' + totalCount + '</small>';
                                if (acashCount > 0 && ecpayCount > 0) {
                                    yearTotalsHtml += '<small class="badge bg-info me-2">(' + acashCount + ' ACASH, ' + ecpayCount + ' ECPAY)</small>';
                                } else if (acashCount > 0) {
                                    yearTotalsHtml += '<small class="badge bg-success me-2">(' + acashCount + ' ACASH)</small>';
                                } else if (ecpayCount > 0) {
                                    yearTotalsHtml += '<small class="badge bg-warning me-2">(' + ecpayCount + ' ECPAY)</small>';
                                }
                            });
                            yearTotalsHtml += '</div>';
                        }
                        
                        tbody.append(`
                            <tr>
                                <td>${item.email}</td>
                                <td>${item.full_name || ''}</td>
                                <td>${item.branch_name}</td>
                            </tr>
                        `);
                    });
                } else {
                    tbody.append('<tr><td colspan="3" class="text-center">No mapped data found</td></tr>');
                }
            }
        },
        error: function(xhr) {
            console.error("LoadMaintenanceTable error", xhr);
            console.error("Response Text:", xhr.responseText);
            console.error("Status:", xhr.status);
            console.error("Status Text:", xhr.statusText);
        }
    });
}
