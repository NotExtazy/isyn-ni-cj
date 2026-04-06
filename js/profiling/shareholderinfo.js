var shareholderTbl;
var VALID_MOBILE_PREFIXES = []; // Store allowed prefixes from DB

const ROUTES = {
    SHAREHOLDER: '../../routes/profiling/shareholderinfo.route.php',
    ADDRESS: '../../routes/administrator/maintenanceaddress.route.php'
};

// UI HELPERS
const UI = {
    showLoading: (selector, msg = 'Loading...') => {
        let el = $(selector);
        el.data('original-text', el.html());
        el.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${msg}`);
    },
    hideLoading: (selector) => {
        let el = $(selector);
        el.prop('disabled', false).html(el.data('original-text'));
    },
    blockUI: (msg = 'Processing...') => {
        Swal.fire({
            title: msg,
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
    },
    unblockUI: () => {
        Swal.close();
    },
    toast: (icon, title) => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        Toast.fire({ icon: icon, title: title });
    }
};

// ==========================================
//    INITIALIZATION
// ==========================================

$(document).ready(function(){
    LoadShareHolderNames();
    LoadDropdowns();
    LoadShareHolderList(""); 
    
    // --- LOAD REGIONS ON STARTUP ---
    LoadRegions(); 
    LoadValidPrefixes(); // <-- NEW: Load DB Prefixes
    // -------------------------------

    // Real-time search filter for DataTable
    let searchTimer = null;
    $("#shNames").on("input", function () {
        const val = (this.value || "").trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if(shareholderTbl){
                shareholderTbl.search(val).draw();
            }
        }, 300); // 300ms debounce
    });

    $("#street").on("input", function () {
        let val = (this.value || "").toUpperCase(); 
        val = val.replace(/[^A-Z0-9 .,\-\/#&']/g, "");
        val = val.replace(/\s{2,}/g, " ");
        this.value = val;
    });

    // Input Restrictions
    $("#shareholderName").on("input", function () {
        let v = (this.value || "").toUpperCase();
        v = v.replace(/[^A-Z .]/g, "").replace(/\s{2,}/g, " ");
        this.value = v;
    });

    // TIN Formatting
    CommonValidation.bindTINFormatting('#tin');

    $("#email").on("input", function () {
        this.value = this.value.replace(/[^a-zA-Z0-9@._+\-'&]/g, "");
    });

    $("#facebook_account").on("input", function () {
        this.value = (this.value || "").replace(/\s/g, "");
    });

    // =========================================================
    //  CONTACT NUMBER LOGIC (Dynamic Prefix Check)
    // =========================================================
    $('#contact_number').on('input', function() {
        // A. Formatting (Force 09)
        var val = this.value.replace(/\D/g, '');
        val = '09' + val.replace(/^0?9?/, ''); 
        if (val.length > 11) val = val.slice(0, 11);
        this.value = val;

        // B. Validation (Visual Feedback)
        var input = $(this);
        var currentPrefix = val.substring(0, 4);

        // Check if prefix exists in loaded array
        var isValid = VALID_MOBILE_PREFIXES.some(function(prefix) {
            return val.startsWith(prefix);
        });

        // Only validate if at least 4 digits are typed
        if (val.length >= 4) {
            if (isValid) {
                input.removeClass('is-invalid').addClass('is-valid');
                input.next('.invalid-feedback').text(''); 
            } else {
                input.removeClass('is-valid').addClass('is-invalid');
                input.next('.invalid-feedback').text('Invalid Network Prefix (' + currentPrefix + ')');
            }
        } else {
            // Reset if too short
            input.removeClass('is-invalid is-valid');
        }
    });

    // Delegated handler: dismiss backlog toast (dynamically injected element)
    $(document).on('click', '#backlog-toast-close', function() {
        $('#backlog-toast-container').remove();
    });
});


// ==========================================
//  LOAD PREFIXES FROM DB
// ==========================================
function LoadValidPrefixes() {
    $.ajax({
        url: "../../routes/administrator/maintenanceprefix.route.php",
        type: "POST",
        data: { action: "GetValidPrefixes" },
        dataType: "JSON",
        success: function(response) {
            if (response) {
                VALID_MOBILE_PREFIXES = response.MOBILE_PREFIXES || [];
                console.log("Valid mobile prefixes loaded:", VALID_MOBILE_PREFIXES.length);
            }
        },
        error: function(xhr, status, error){
            console.error("Error loading prefixes:", xhr);
        }
    });
}

// Auto-Search & Auto-Load
$('.searchName').typeahead({
    source: function(name, result){
        $.ajax({
            url: ROUTES.SHAREHOLDER,
            type:"POST",
            data:{action:"searchNames", name:name},
            dataType:"JSON",
            success:function(data){
                result($.map(data, function(item){ return item; }));
            }
        })
    },
    afterSelect: function(item) {
        // Trigger Auto-Load when an item is selected from the dropdown
        loadShareholderInfoByName(item);
    }
});

// Handle Manual Typing + Blur/Enter
$("#shareholderName").on("change", function() {
    let name = $(this).val();
    if(name && name.trim() !== "") {
        loadShareholderInfoByName(name);
    }
});

function loadShareholderInfoByName(name) {
    $.ajax({
        url: ROUTES.SHAREHOLDER,
        method: 'POST',
        data: { action: "getShareholderByName", fullname: name },
        dataType: 'JSON',
        success: function(response) {
            if(response.STATUS == "LOADED"){
                var INFO = response.INFO;

                // NOTIFY USER
                UI.toast('info', 'Existing Shareholder Found. Details auto-populated.');

                // Populate Form
                $('#shareID').val(INFO.id);
                $('#shareholderID').val(INFO.shareholderNo);
                $("#president").prop('checked', INFO.OtherSignatories === "Yes");
                
                $('#contact_number').val(INFO.contact_number);
                $('#email').val(INFO.email);
                $('#facebook_account').val(INFO.facebook_account);
                
                // TIN — decrypted by backend, populate here
                $('#tin').val(INFO.tin || '');
                
                // Set Dropdowns
                $('#shareholder_type').val(INFO.shareholder_type);
                $('#type').val(INFO.type);
                
                // CLEAR SHARES for new entry
                $('#noofshare').val('');
                $('#amount_share').val('');
                
                $("#emp_resign").prop('checked', INFO.emp_resign === "Yes");

                // ADDRESS POPULATION
                let strVal = INFO.Street || INFO.street || ""; 
                $('#street').val(strVal); 
                
                // Enable and Set Region First
                $('#Region').prop('disabled', false).val(INFO.Region);

                // Chain Loading
                LoadProvince(INFO.Region, INFO.Province)
                    .then(() => LoadCitytown(INFO.Province, INFO.CityTown))
                    .then(() => LoadBrgy(INFO.CityTown, INFO.Barangay))
                    .catch((err) => console.error("Address loading chain failed", err));

                // Handle Buttons
                $('#updateButton').hide();
                $('#submitButton').show();
                
                // Enable fields for editing
                $('#shareholderInfo input, #shareholderInfo select').prop('disabled', false);
                
                // DISABLE Auto-Generated / Calculated Fields
                $('#shareholderID').prop('disabled', true);
                $('#cert_no').prop('disabled', true); 
                $('#amount_share').prop('disabled', true);


                // ==========================================
                // BACKLOG CHECK: Run immediately on name-select
                // ==========================================
                $.ajax({
                    url: ROUTES.SHAREHOLDER,
                    method: 'POST',
                    data: { action: 'CheckBacklog', shareholderNo: INFO.shareholderNo },
                    dataType: 'JSON',
                    success: function(bl) {
                        // Remove any prior toast first
                        $('#backlog-toast-container').remove();
                        if (bl.HAS_BACKLOG) {
                            $('#shareholderInfo').data('hasBacklogs', true);
                            // Lock shares input and submit button
                            $('#noofshare').prop('disabled', true).attr('title', 'Locked: settle unpaid backlogs first');
                            $('#submitButton').prop('disabled', true);
                            // Plain div toast — no Bootstrap .toast transitions/fade, X via delegated handler
                            var $toast = $(
                                '<div id="backlog-toast-container" style="'
                                +   'position:fixed;top:16px;right:16px;z-index:99999;'
                                +   'min-width:320px;max-width:420px;'
                                +   'background:#dc3545;color:#fff;'
                                +   'border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.3);'
                                +   'display:flex;align-items:flex-start;gap:10px;padding:14px 12px;'
                                +   'font-size:.9rem;transition:none;'
                                + '">'
                                + '<i class="fa-solid fa-triangle-exclamation fa-lg" style="margin-top:2px;flex-shrink:0;"></i>'
                                + '<div style="flex:1;">'
                                +   '<strong>Backlog Detected:</strong> This shareholder has <strong>' + bl.COUNT + '</strong> '
                                +   'unpaid issuance(s). New shares cannot be submitted until all payments are settled. '
                                +   'Click <em>Generate Certificate</em> to view and settle them.'
                                + '</div>'
                                + '<button id="backlog-toast-close" type="button" aria-label="Close" style="'
                                +   'background:none;border:none;color:#fff;font-size:1.1rem;'
                                +   'cursor:pointer;padding:0 2px;flex-shrink:0;line-height:1;'
                                + '">&times;</button>'
                                + '</div>'
                            );
                            $('body').append($toast);
                        } else {
                            $('#shareholderInfo').data('hasBacklogs', false);
                            $('#noofshare').prop('disabled', false).removeAttr('title');
                            $('#submitButton').prop('disabled', false);
                        }
                    }
                });
                // ==========================================

            } else {
                // Not found -> Generate New ID
                gnrtSID();
            }
        }
    });
}

// ==========================================
// ==========================================
//    INTEGRATED ADDRESS LOGIC (CACHED)
// ==========================================

const AddressCache = {
    regions: null,
    provinces: {},
    citytowns: {},
    brgys: {}
};

function LoadRegions() {
    $("#Region").empty().append('<option value="" selected>Select</option>');
    // Force disable children
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    if (AddressCache.regions) {
        _populateDropdown("#Region", AddressCache.regions);
        return Promise.resolve(AddressCache.regions);
    }

    return $.ajax({
        url: ROUTES.ADDRESS, 
        type: "POST",
        data: { maintenance_action: "get_All" },
        dataType: "JSON",
        success: function(data) {
            let list = [];
            if(data.region){
                list = data.region.map(item => item.Region || item.REGION || item);
            }
            AddressCache.regions = list;
            _populateDropdown("#Region", list);
        },
        error: function(xhr) {
            console.error("Error loading regions:", xhr.responseText);
        }
    });
}

function LoadProvince(region, selected = null, isEditable = true) {
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    if (!region || region === "") return Promise.reject();

    if (AddressCache.provinces[region]) {
        _populateDropdown("#Province", AddressCache.provinces[region], selected);
        if(isEditable) $("#Province").prop('disabled', false); // Enable ONLY if editable
        return Promise.resolve(AddressCache.provinces[region]);
    }

    return $.ajax({
        url: ROUTES.ADDRESS,
        type: "POST",
        data: { maintenance_action: "get_province", region_selected: region },
        dataType: "JSON",
        success: function(data) {
            let list = [];
            let rawList = data.LIST ? data.LIST : [];
            list = rawList.map(item => item.Province || item.PROVINCE || item);
            
            AddressCache.provinces[region] = list;
            _populateDropdown("#Province", list, selected);
            
            // RESPECT EDITABLE FLAG
            if(isEditable) {
                 $("#Province").prop('disabled', false);
            }
        }
    });
}

function LoadCitytown(province, selected = null, isEditable = true) {
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    if (!province || province === "") return Promise.reject();

    if (AddressCache.citytowns[province]) {
        _populateDropdown("#CityTown", AddressCache.citytowns[province], selected);
        if(isEditable) $("#CityTown").prop('disabled', false);
        return Promise.resolve(AddressCache.citytowns[province]);
    }

    return $.ajax({
        url: ROUTES.ADDRESS,
        type: "POST",
        data: { maintenance_action: "get_citytown", province_selected: province },
        dataType: "JSON",
        success: function(data) {
            let list = [];
            let rawList = data.LIST ? data.LIST : [];
            list = rawList.map(item => item.CityTown || item.CITYTOWN || item.MUNICIPALITY || item);

            AddressCache.citytowns[province] = list;
            _populateDropdown("#CityTown", list, selected);

            if(isEditable) {
                $("#CityTown").prop('disabled', false);
            }
        }
    });
}

function LoadBrgy(citytown, selected = null, isEditable = true) {
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    if (!citytown || citytown === "") return Promise.reject();

    if (AddressCache.brgys[citytown]) {
        _populateDropdown("#Barangay", AddressCache.brgys[citytown], selected);
        if(isEditable) $("#Barangay").prop('disabled', false);
        return Promise.resolve(AddressCache.brgys[citytown]);
    }

    return $.ajax({
        url: ROUTES.ADDRESS,
        type: "POST",
        data: { maintenance_action: "get_brgy", citytown_selected: citytown },
        dataType: "JSON",
        success: function(data) {
            let list = [];
            let rawList = data.LIST ? data.LIST : [];
            list = rawList.map(item => item.Barangay || item.BARANGAY || item);

            AddressCache.brgys[citytown] = list;
            _populateDropdown("#Barangay", list, selected);

            if(isEditable) {
                $("#Barangay").prop('disabled', false);
            }
        }
    });
}

function _populateDropdown(selector, list, selected = null) {
    const el = $(selector);
    $.each(list, function(key, val) {
        el.append('<option value="' + val + '">' + val + '</option>');
    });
    if (selected) { el.val(selected); }
}

// Change Listeners
$("#Region").on("change", function() { LoadProvince($(this).val()); });
$("#Province").on("change", function() { LoadCitytown($(this).val()); });
$("#CityTown").on("change", function() { LoadBrgy($(this).val()); });


// ==========================================
//    DATA LOADING (Shareholder Specific)
// ==========================================



function LoadDropdowns(){
    $.ajax({
        url: ROUTES.SHAREHOLDER,
        type:"POST",
        data:{action:"LoadDropdowns"},
        dataType:"JSON",
        success:function(response){
            $("#shareholder_type").empty().append(`<option value="" selected>Select</option>`);
            if (response.SHTYPES) {
                $.each(response.SHTYPES,function(key,value){
                    $("#shareholder_type").append(`<option value="${value["choice_value"]}">${value["choice_value"]}</option>`);
                });
            }
            $("#type").empty().append(`<option value="" selected>Select</option>`);
            
            // Populate Modal Filter
            $("#modalReportFilter").empty()
                .append(`<option value="ALL" selected>All Shareholders</option>`)
                .append(`<option value="HIGHEST_SHARES">Highest to Lowest Shares</option>`)
                .append(`<option value="LOWEST_SHARES">Lowest to Highest Shares</option>`);

            if (response.TYPES) {
                $.each(response.TYPES,function(key,value){
                    $("#type").append(`<option value="${value["choice_value"]}">${value["choice_value"]}</option>`);
                    // Add types to report filter
                    $("#modalReportFilter").append(`<option value="${value["choice_value"]}">${value["choice_value"]}</option>`);
                });
            }
        }
    })
}

function LoadShareHolderNames(){
    $.ajax({
        url: ROUTES.SHAREHOLDER,
        type:"POST",
        data:{action:"LoadShareHolderNames"},
        dataType:"JSON",
        success:function(response){
            $("#shNamesList").empty();
            if(response.NAMES){
                $.each(response.NAMES,function(key,value){
                    if(value["fullname"]) $("#shNamesList").append(`<option value="${value["fullname"]}"></option>`);
                });
            }
        }
    })
}

function LoadShareHolderList(name){
    // Increase the number of pagination buttons shown
    $.fn.DataTable.ext.pager.numbers_length = 7; 

    $.ajax({
        url: ROUTES.SHAREHOLDER,
        type:"POST",
        data:{action:"LoadShareHolderList", name:name},
        dataType:"JSON",
        success:function(response){
            // 1. Destroy existing DataTable if it exists
            if ($.fn.DataTable.isDataTable('#shareholderTbl')) {
                $('#shareholderTbl').DataTable().clear().destroy();
            }

            // 2. Clear the table body
            $("#shareholderList").empty();
            
            // 3. Initialize DataTable with data directly
            shareholderTbl = $('#shareholderTbl').DataTable({
                data: response.LIST || [],
                columns: [
                    { data: 'shareholderNo' },
                    { data: 'fullname' },
                    { data: 'shareholder_type' },
                    { data: 'type' },
                    { data: 'noofshare' },
                    { data: 'dateEncoded' }
                ],
                pageLength: 10,
                searching: true,
                ordering: true,
                order: [[1, 'asc']], // Sort by Full Name
                lengthChange: true,
                lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                info: false,
                paging: true,
                responsive: false,
                columnDefs: [ 
                    { targets: 1, className: 'text-start' },
                    { targets: [2, 3, 4, 5], orderable: false }
                ],
                language: { 
                    emptyTable: "No shareholders found",
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                }
            });
        },
        error: function(xhr, status, error){
            console.error("Error loading shareholders:", xhr, status, error);
        }
    });
}
// ==========================================

$('#shareholderTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) classList.remove('selected');
    else {
        shareholderTbl.rows('.selected').nodes().each((row) => row.classList.remove('selected'));
        classList.add('selected');
    }

    var data = $('#shareholderTbl').DataTable().row(this).data();
    if(!data) return; 

    // Disable UI
    $('#shareholderInfo input, #shareholderInfo select').prop('disabled', true);
    $("#president, #emp_resign").prop('checked', false);

    // Buttons - FIX: Show Edit Button, Hide Update Button until Edit is clicked
    $("#printCert").prop('disabled', false);
    $('#addNew').show().prop('disabled', false); 
    
    $('#cancel').prop('hidden', false).prop('disabled', false);
    
    // THE FIX:
    $('#editButton').show().prop('disabled', false); 
    $('#updateButton').hide(); // Hide Update until Edit is clicked
    $('#submitButton').hide();

    var shareholderNo = data.shareholderNo || data[0]; // Fallback to index if needed, but prefer property 
    $('#shareholderID').val(shareholderNo);

    $.ajax({
        url: ROUTES.SHAREHOLDER,
        method: 'POST',
        data: { action: "getShareholderInfo", shareholderNo: shareholderNo },
        dataType: 'JSON',
        success: function(response) {
            if(response.STATUS == "LOADED"){
                var INFO = response.INFO;

                $('#shareID').val(INFO.id);
                $('#shareholderID').val(INFO.shareholderNo);
                $("#president").prop('checked', INFO.OtherSignatories === "Yes");
                $('#shareholderName').val(INFO.fullname);
                
                // Masked Contact
                $('#contact_number').val(INFO.contact_number);
                
                // TIN — decrypted by backend, populate here
                $('#tin').val(INFO.tin || '');
                
                $('#email').val(INFO.email);
                $('#facebook_account').val(INFO.facebook_account);
                setDropdownValue('#shareholder_type', INFO.shareholder_type);
                setDropdownValue('#type', INFO.type);
                $('#noofshare').val(INFO.noofshare);
                $('#amount_share').val(INFO.amount_share);
                $('#cert_no').val(INFO.cert_no);
                $("#emp_resign").prop('checked', INFO.emp_resign === "Yes");

                // =============================================
                // ADDRESS POPULATION (Fixed)
                // =============================================
                $('#street').val(INFO.Street); 
                $('#Region').val(INFO.Region);

                // Chain load with selections (DISABLE EDITING: isEditable = false)
                LoadProvince(INFO.Region, INFO.Province, false)
                    .then(() => LoadCitytown(INFO.Province, INFO.CityTown, false))
                    .then(() => LoadBrgy(INFO.CityTown, INFO.Barangay, false))
                    .catch((err) => console.error("Address loading chain failed", err));
                // =============================================

                // Remove legacy logic that auto-enabled fields
                // Fields should remain disabled until "Edit" is clicked.
            }
        }
    });
});

function enableEditFields(){
    $('#president').prop('disabled', false);
    $('#shareholderName').prop('disabled', false);
    $('#contact_number').prop('disabled', false);
    $('#tin').prop('disabled', false); // TIN must be editable
    $('#email').prop('disabled', false);
    $('#facebook_account').prop('disabled', false);
    $('#shareholder_type').prop('disabled', false);
    $('#type').prop('disabled', false);
    $('#noofshare').prop('disabled', false);
    $('#emp_resign').prop('disabled', false);
    
    // Enable Address Fields
    $('#Region, #Province, #CityTown, #Barangay, #street').prop('disabled', false);
    
    // Keep Locked
    $('#amount_share').prop('disabled', true); 
    $('#cert_no').prop('disabled', true); 
}

function setDropdownValue(selector, value) {
    if ($(selector + " option[value='" + value + "']").length > 0) {
        $(selector).val(value);
    } else {
        $(selector).append(new Option(value, value, true, true));
    }
}

// ==========================================
//    BUTTON ACTIONS
// ==========================================



$('#addNew').on('click', function() {
    // 1. Check for Backlogs
    if ($('#shareholderInfo').data('hasBacklogs') === true) {
        Swal.fire({
            icon: 'error',
            title: 'Action Restricted',
            text: 'This shareholder has unpaid backlogs. Please settle all payments before adding new shares.'
        });
        return;
    }

    // gnrtCertID(); // REMOVED: Generated on Backend upon Save
    $('#cert_no').val(''); // Placeholder
    gnrtSID();
    
    // CRITICAL: Clear Hidden ID to ensure INSERT instead of UPDATE
    $('#shareID').val(''); 
    
    $('#shareholderInfo input, #shareholderInfo select').prop('disabled', false);
    $('#amount_share').prop('disabled', true); 
    $('#cert_no').prop('disabled', true); 
    $('#shareholderID').prop('disabled', true); 
    
    // Set Mask
    $('#contact_number').val('09');

    // Specifically clear & disable child address fields on NEW
    $('#Region').val('');
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    $("#president, #emp_resign").prop('checked', false);
    $('#shareholderName, #email, #facebook_account, #noofshare, #amount_share').val('');
    $('#shareholder_type, #type').val('');
    $('#street').val(''); 
    
    // Clear Validation styling
    $('#shareholderInfo .is-invalid').removeClass('is-invalid');
    $('#shareholderInfo .is-valid').removeClass('is-valid');

    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#submitButton').show().prop('disabled', false);
    $("#printCert").prop('disabled', true);
    $('#updateButton').hide();
    $('#editButton').hide(); // Ensure Edit is hidden
    
    $("#shareholderTbl tbody tr").removeClass("selected");
});

$('#editButton').on('click', function(){
    enableEditFields();
    $('#shareholderInfo input, #shareholderInfo select').prop('disabled', false); // Enable all
    $('#amount_share').prop('disabled', true); // Keep calculated fields locked
    $('#cert_no').prop('disabled', true);
    $('#shareholderID').prop('disabled', true);

    $('#editButton').hide();
    $('#updateButton').show().prop('disabled', false);
    $('#submitButton').hide();
    
    // BACKLOG CHECK SPECIFIC TO ADDING SHARES
    if ($('#shareholderInfo').data('hasBacklogs') === true) {
        // We allow editing info, BUT we must disable share addition.
        // So we lock the "No of Shares" field.
        $('#noofshare').prop('disabled', true).attr('title', 'Locked due to unpaid backlogs');
        UI.toast('warning', 'Share addition disabled due to unpaid backlogs. You can only update profile info.');
    }
});

// ... (Edit Button and Cancel functions remain same) ...

function PrintReport(){
    let shareholderNo = $("#shareholderID").val();
    let shareholderName = $("#shareholderName").val();
    
    if(!shareholderNo || shareholderNo.trim() === ""){
        Swal.fire({ icon:"warning", text:"No shareholder selected/retrieved" });
        return;
    }

    // Dynamic Function to Load the Table Content
    const loadCertTable = () => {
        return $.ajax({
            url: ROUTES.SHAREHOLDER,
            type: "POST",
            data: { action: "getShareholderCertificates", shareholderNo: shareholderNo },
            dataType: "JSON"
        });
    };

    // Function to render the modal content
    const renderModal = (list) => {
        let html = `
            <div class="table-responsive">
                <input type="text" id="certSearch" class="form-control mb-2" placeholder="Search Certificate...">
                <table class="table table-bordered table-hover" id="certTable">
                    <thead class="thead-light" style="position: sticky; top: 0; background: white; z-index: 1;">
                        <tr>
                            <th>Date Issued</th>
                            <th>Cert No</th>
                            <th>Shares</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        let hasUnpaid = false;

        if(list && list.length > 0){
            list.forEach(item => {
                const isPrinted = item.is_printed == 1;
                const isPaid = item.payment_status === 'Paid';
                
                const certNoDisplay = isPaid ? (item.cert_no || 'Pending') : 'Unpaid';
                const statusBadge = isPaid 
                    ? '<span class="badge bg-success text-white">Paid</span>' 
                    : '<span class="badge bg-danger text-white">Unpaid</span>';
                
                let btn = '';
                
                if(!isPaid){
                    hasUnpaid = true;
                    // Pay Button
                    btn = `<button class="btn btn-warning btn-sm" onclick="MarkAsPaid('${item.id}')" title="Mark as Paid">
                            <i class="fa-solid fa-money-bill-wave"></i> Pay
                           </button>`;
                } else {
                    // Print Button
                    if(isPrinted){
                        btn = `<button class="btn btn-secondary btn-sm btn-print-cert" 
                                data-id="${item.id}" data-printed="true">
                                <i class="fas fa-print"></i> Reprint
                               </button>`;
                    } else {
                        btn = `<button class="btn btn-primary btn-sm btn-print-cert" 
                                data-id="${item.id}" data-printed="false">
                                <i class="fas fa-print"></i> Print
                               </button>`;
                    }
                }

                html += `<tr>
                            <td>${item.date_issued || '-'}</td>
                            <td>${certNoDisplay}</td>
                            <td>${item.noofshare}</td>
                            <td>${statusBadge}</td>
                            <td>${btn}</td>
                         </tr>`;
            });
            
            // Set Backlog Data
            if (hasUnpaid) {
                $('#shareholderInfo').data('hasBacklogs', true);
            } else {
                $('#shareholderInfo').data('hasBacklogs', false);
            }
        } else {
            html += `<tr><td colspan="5" class="text-center">No certificate issuances found.</td></tr>`;
            $('#shareholderInfo').data('hasBacklogs', false);
        }

        html += `</tbody></table></div>`;
        return html;
    };

    // Initial Load & Show
    loadCertTable().then(response => {
        const list = response.LIST || [];
        
        Swal.fire({
            title: `Certificates for ${shareholderName}`,
            html: renderModal(list),
            width: '800px',
            showCloseButton: true,
            showConfirmButton: false,
            didOpen: () => {
                // SEARCH LOGIC
                $(document).on('input', '#certSearch', function() {
                    let value = $(this).val().toLowerCase();
                    $("#certTable tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });

                // Attach Event Listeners to Buttons inside Swal
                $(document).off('click', '.btn-print-cert'); 
                $(document).on('click', '.btn-print-cert', function() {
                    let certId = $(this).data('id');
                    let isPrinted = $(this).data('printed');

                    const executePrint = () => {
                        window.open(`../../routes/profiling/shareholderinfo.route.php?type=PrintCertificate&filter=ALL&certId=${certId}&shno=${shareholderNo}`, '_blank');
                        
                        $.ajax({
                            url: ROUTES.SHAREHOLDER,
                            type: "POST",
                            data: { action: "markCertPrinted", certId: certId },
                            dataType: "JSON",
                            success: function(resp){
                                if(resp.STATUS === "SUCCESS"){
                                    loadCertTable().then(newResp => {
                                        Swal.getHtmlContainer().querySelector('.table-responsive').innerHTML = $(renderModal(newResp.LIST || [])).find('.table-responsive').html();
                                        // Re-apply search filter if exists? Or just clear it. 
                                        // Simple refresh is fine.
                                         Swal.getHtmlContainer().innerHTML = renderModal(newResp.LIST || []);
                                    });
                                }
                            }
                        });
                    };

                    if (isPrinted) {
                        Swal.fire({
                            title: 'Reprint Certificate?',
                            text: "This certificate has already been printed. Reprinting usually requires Admin Approval.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Yes, Reprint (Bypass)'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                executePrint();
                            }
                        });
                    } else {
                        executePrint();
                    }
                });
            }
        });
    });
}

$('#editButton').on('click', function() {
    enableEditFields();
    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#submitButton').hide();
    $('#updateButton').show().prop('disabled', false);
});

function Cancel() {
    $('#shareholderInfo')[0].reset();
    $('#shareholderInfo input, #shareholderInfo select').prop('disabled', true);
    
    // Clear Validations
    $('#contact_number').removeClass('is-valid is-invalid');

    // Re-lock address children
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    // Remove backlog toast and reset backlog state
    $('#backlog-toast-container').remove();
    $('#shareholderInfo').removeData('hasBacklogs');

    $('#cancel').prop('hidden', true).prop('disabled', true);
    $('#updateButton').hide();
    $('#submitButton').show().prop('disabled', true);
    $('#editButton').hide(); 
    $('#addNew').show().prop('disabled', false);
    $("#printCert").prop('disabled', true);
    
    $("#shareholderTbl tbody tr").removeClass("selected");
}

// On Load
$(document).ready(function(){
    // ... existing load ... 
    
    // Bind TIN Formatting
    if (typeof CommonValidation !== 'undefined') {
        CommonValidation.bindTINFormatting('#tin');
    }
});

// ... existing code ...

function validateShareholderForm() {
    let isValid = true;
    let firstErrorField = null;

    // 1. Required Fields Check
    let required = [
        { id: '#shareholderName', name: 'Shareholder Name' },
        { id: '#noofshare', name: 'No. of Shares' },
        { id: '#email', name: 'Email Address' },
        { id: '#shareholder_type', name: 'Shareholder Type' },
        { id: '#type', name: 'Type' },
        { id: '#Region', name: 'Region' },
        { id: '#Province', name: 'Province' },
        { id: '#CityTown', name: 'City/Town' },
        { id: '#Barangay', name: 'Barangay' },
        { id: '#street', name: 'Street Address' },
        { id: '#tin', name: 'Tax Identification No.' } // Added TIN
    ];

    for (let field of required) {
        const el = $(field.id);
        if (el.val() === null || el.val().trim() === "") {
            el.addClass('is-invalid');
            isValid = false;
            if (!firstErrorField) firstErrorField = el;
        } else {
            el.removeClass('is-invalid');
        }
    }

    // 1.1 TIN Format Validation
    const tinEl = $('#tin');
    if (tinEl.val().trim() !== "") {
        if (typeof CommonValidation !== 'undefined' && !CommonValidation.isValidTIN(tinEl.val())) {
             tinEl.addClass('is-invalid');
             isValid = false;
             UI.toast('warning', 'Invalid TIN Format. Must be 12 digits.');
             if (!firstErrorField) firstErrorField = tinEl;
        }
    }

    // 2. Contact Number Check (Dynamic)
    const contactEl = $("#contact_number");
    const contact = contactEl.val().trim();
    if (contact.length !== 11 || !contact.startsWith('09')) {
        contactEl.addClass('is-invalid');
        isValid = false;
        if (!firstErrorField) firstErrorField = contactEl;
    } else {
        // If it was marked invalid by the input event listener (prefix check), keep it invalid
        if (contactEl.hasClass('is-invalid')) {
            isValid = false;
            if (!firstErrorField) firstErrorField = contactEl;
        }
    }

    // 3. Email Regex
    const emailEl = $("#email");
    const email = emailEl.val().trim();
    const emailPattern = /^[a-zA-Z0-9._+\-'&]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailPattern.test(email)) {
        emailEl.addClass('is-invalid');
        isValid = false;
        if (!firstErrorField) firstErrorField = emailEl;
    } else {
        emailEl.removeClass('is-invalid');
    }

    if (!isValid) {
        // Show generic warning
        UI.toast('warning', 'Please fill in all required fields.');
        if(firstErrorField) firstErrorField.focus();
    }

    return isValid;  
}

$("#submitButton").on("click",function(){
    if (!validateShareholderForm()) return;
    
    $('#shareholderID, #amount_share, #cert_no').prop('disabled', false);

    var form = $('#shareholderInfo')[0];
    var formData = new FormData(form);
    formData.append('action', 'SaveInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Save New Shareholder?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save it!',
        preConfirm: function() {
            return $.ajax({
                url: ROUTES.SHAREHOLDER,
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'JSON'
            });
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value.STATUS == 'success' || result.value.STATUS == 'SUCCESS') {
                UI.toast("success", result.value.MESSAGE);
                LoadShareHolderList("");
                LoadShareHolderNames();
                Cancel(); // Reset form/modal
            } else {
                Swal.fire({ icon: "error", title: "Error", text: result.value.MESSAGE });
            }
        }
    });
});

$("#updateButton").on("click",function(){
    if (!validateShareholderForm()) return;
    
    $('#shareID, #shareholderID, #amount_share, #cert_no').prop('disabled', false);
    
    var form = $('#shareholderInfo')[0];
    var formData = new FormData(form);
    formData.append('action', 'UpdateInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Update Shareholder?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, update it!',
        preConfirm: function() {
            return $.ajax({
                url: ROUTES.SHAREHOLDER,
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'JSON'
            });
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value.STATUS == 'success' || result.value.STATUS == 'SUCCESS') {
                UI.toast("success", result.value.MESSAGE);
                LoadShareHolderList("");
                Cancel();
            } else {
                Swal.fire({ icon: "error", title: "Error", text: result.value.MESSAGE });
            }
        }
    });
});

function gnrtCertID(){
    $.ajax({
        url: ROUTES.SHAREHOLDER,
        type: 'POST',
        data: {action:"gnrtCertID"},
        dataType: 'JSON',
        success: function(response) {
            $('#cert_no').val(response.certNo);
            $('#actualNo').val(response.actualNo);
        }
    });
}

function gnrtSID(){
    $.ajax({
        url: ROUTES.SHAREHOLDER,
        type: 'POST',
        data: {action:"gnrtSID"},
        dataType: 'JSON',
        success: function(response) {
            $('#shareholderID').val(response.shareNo);
        }
    });
}

function calculateAmount() {
    var noOfShares = document.getElementById('noofshare').value;
    var amountOfShares = noOfShares * 100;
    document.getElementById('amount_share').value = amountOfShares;
}

$('#ConfigurationBtn').on('click', function() {
    $.ajax({
        url: ROUTES.SHAREHOLDER,
        method: 'POST',
        data: { action: "getShareholderConfig"},
        dataType: 'JSON',
        success: function(response) {
            if(response.certNo && response.certNo.length > 0) $('#currentCertNo').val(response.certNo[0].Value);
            if(response.SIGN1 && response.SIGN1.length > 0) {
                $('#signatory1Name').val(response.SIGN1[0].Value);
                $('#signatory1Desig').val(response.SIGN1[0].SubValue);
            }
            if(response.SIGN2 && response.SIGN2.length > 0) {
                $('#signatory2Name').val(response.SIGN2[0].Value);
                $('#signatory2Desig').val(response.SIGN2[0].SubValue);
            }
            if(response.SIGNSUB2 && response.SIGNSUB2.length > 0) {
                $('#signatorySub2Name').val(response.SIGNSUB2[0].Value);
                $('#signatorySub2Desig').val(response.SIGNSUB2[0].SubValue);
            }
            $("#configurationMDL").modal("show");
        }
    });
});

$("#updateConfigBtn").on("click",function(){
    var form = $('#configurationForm')[0];
    var formData = new FormData(form);
    formData.append('action', 'UpdateConfig');

    $.ajax({
        url: ROUTES.SHAREHOLDER,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'JSON',
        success: function(response) {
            if (response.STATUS == 'success') {
                Swal.fire("Success", response.MESSAGE, "success");
                $("#configurationMDL").modal("hide");
            } else {
                Swal.fire("Error", response.MESSAGE, "error");
            }
        }
    });
});

function PrintReport(){
    let shareholderNo = $("#shareholderID").val();
    let shareholderName = $("#shareholderName").val();
    
    if(!shareholderNo || shareholderNo.trim() === ""){
        Swal.fire({ icon:"warning", text:"No shareholder selected/retrieved" });
        return;
    }

    // Dynamic Function to Load the Table Content
    const loadCertTable = () => {
        return $.ajax({
            url: ROUTES.SHAREHOLDER,
            type: "POST",
            data: { action: "getShareholderCertificates", shareholderNo: shareholderNo },
            dataType: "JSON"
        });
    };

    // Render Function
    const renderModal = (list) => {
        let html = `
            <div class="table-responsive">
                <input type="text" id="certSearch" class="form-control mb-2" placeholder="Search Certificate...">
                <table class="table table-bordered table-hover" id="certTable">
                    <thead class="thead-light" style="position: sticky; top: 0; background: white; z-index: 1;">
                        <tr>
                            <th>Date Issued</th>
                            <th>Cert No</th>
                            <th>Shares</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        let hasUnpaid = false;

        if(list && list.length > 0){
            list.forEach(item => {
                const isPrinted = item.is_printed == 1;
                // Robust Payment Status Check
                const status = item.payment_status || 'Unpaid'; // Default to Unpaid if missing
                const isPaid = status === 'Paid';
                
                const certNoDisplay = isPaid ? (item.cert_no || 'Pending') : 'Unpaid';
                const statusBadge = isPaid 
                    ? '<span class="badge bg-success text-white">Paid</span>' 
                    : '<span class="badge bg-danger text-white">Unpaid</span>';
                
                let btn = '';
                
                // Button Logic
                if(!isPaid){
                    hasUnpaid = true;
                    btn = `<button class="btn btn-warning btn-sm" onclick="MarkAsPaid('${item.id}')" title="Mark as Paid">
                            <i class="fa-solid fa-money-bill-wave"></i> Pay
                           </button>`;
                } else {
                    if(isPrinted){
                        btn = `<button class="btn btn-secondary btn-sm btn-print-cert" 
                                data-id="${item.id}" data-printed="true">
                                <i class="fas fa-print"></i> Reprint
                               </button>`;
                    } else {
                        btn = `<button class="btn btn-primary btn-sm btn-print-cert" 
                                data-id="${item.id}" data-printed="false">
                                <i class="fas fa-print"></i> Print
                               </button>`;
                    }
                }

                html += `<tr>
                            <td>${item.date_issued || '-'}</td>
                            <td>${certNoDisplay}</td>
                            <td>${item.noofshare}</td>
                            <td>${statusBadge}</td>
                            <td>${btn}</td>
                         </tr>`;
            });
            
            // Set Backlog Data
            if (hasUnpaid) {
                $('#shareholderInfo').data('hasBacklogs', true);
            } else {
                $('#shareholderInfo').data('hasBacklogs', false);
            }
        } else {
            html += `<tr><td colspan="5" class="text-center">No certificate issuances found.</td></tr>`;
            $('#shareholderInfo').data('hasBacklogs', false);
        }

        html += `</tbody></table></div>`;
        return html;
    };

    // Initial Load & Show
    loadCertTable().then(response => {
        const list = response.LIST || [];
        console.log("Certificate Data Loaded:", list); // DEBUG LOG FOR USER

        Swal.fire({
            title: `Certificates for ${shareholderName}`,
            html: renderModal(list),
            width: '800px',
            showCloseButton: true,
            showConfirmButton: false,
            didOpen: () => {
                // SEARCH LOGIC
                $(document).on('input', '#certSearch', function() {
                    let value = $(this).val().toLowerCase();
                    $("#certTable tbody tr").filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                    });
                });

                // Attach Event Listeners to Buttons inside Swal
                $(document).off('click', '.btn-print-cert'); 
                $(document).on('click', '.btn-print-cert', function() {
                    let certId = $(this).data('id');
                    let isPrinted = $(this).data('printed');

                    const executePrint = () => {
                        window.open(`../../routes/profiling/shareholderinfo.route.php?type=PrintCertificate&filter=ALL&certId=${certId}&shno=${shareholderNo}`, '_blank');
                        
                        $.ajax({
                            url: ROUTES.SHAREHOLDER,
                            type: "POST",
                            data: { action: "markCertPrinted", certId: certId },
                            dataType: "JSON",
                            success: function(resp){
                                if(resp.STATUS === "SUCCESS"){
                                    loadCertTable().then(newResp => {
                                        Swal.getHtmlContainer().querySelector('.table-responsive').innerHTML = $(renderModal(newResp.LIST || [])).find('.table-responsive').html();
                                    });
                                }
                            }
                        });
                    };

                    if (isPrinted) {
                        Swal.fire({
                            title: 'Reprint Certificate?',
                            text: "This certificate has already been printed. Reprinting usually requires Admin Approval.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Yes, Reprint (Bypass)'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                executePrint();
                            }
                        });
                    } else {
                        executePrint();
                    }
                });
            }
        });
    });
}

// Global helper for SweetAlert click
window.selectCertToPrint = function(certId) {
    let shareholderNo = $("#shareholderID").val();
    showFormatSelection(shareholderNo, certId);
}

function showFormatSelection(shareholderNo, certId){
    Swal.fire({
        title: 'Select Format',
        html: `<select id="swal-select" class="swal2-input">
                <option value="10M">10M</option>
                <option value="4M">4M</option>
               </select>`,
        preConfirm: () => {
            return document.getElementById('swal-select').value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const format = result.value;
            $.ajax({
                url:"../../routes/profiling/shareholderinfo.route.php",
                type:"POST",
                data:{
                    action:"ToSession",
                    shareholderNo:shareholderNo, 
                    format:format,
                    certId: certId 
                },
                dataType:"JSON",
                success:function(response){
                    if(response.STATUS == "SUCCESS"){
                        window.open("../../routes/profiling/shareholderinfo.route.php?type=PrintCertificate", '_blank');
                    }
                }
            });
        }
    });
}

// ==========================================
//    REPORT MODAL HANDLERS
// ==========================================

$('#openReportModalBtn').on('click', function() {
    $('#SelectReportMDL').modal('show');
});

// Helper for card selection (globally accessible if onclick is used)
window.selectReportFormat = function(elem, type) {
    $('.report-option-card').removeClass('active');
    $(elem).addClass('active');
    $('#selectedReportFormat').val(type);
}

$('#generateReportConfirmBtn').on('click', function() {
    const type = $('#selectedReportFormat').val();
    const filter = $('#modalReportFilter').val();
    
    // Determine route type
    let urlType = (type === 'excel') ? 'ExportShareholderExcel' : 'PrintShareholderReport';
    
    // Execute
    window.open("../../routes/profiling/shareholderinfo.route.php?type=" + urlType + "&filter=" + filter, '_blank');
    $('#SelectReportMDL').modal('hide');
});

// NEW: Mark As Paid Function
function MarkAsPaid(issuanceId) {
    Swal.fire({
        title: 'Mark as Fully Paid?',
        text: "This will generate a Certificate Number and allow printing.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Mark as Paid!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: ROUTES.SHAREHOLDER,
                type: 'POST',
                data: { action: "MarkAsPaid", issuanceId: issuanceId, csrf_token: CSRF_TOKEN },
                dataType: 'JSON',
                success: function(response) {
                    if (response.STATUS === 'SUCCESS') {
                        Swal.fire('Paid!', response.MESSAGE, 'success');
                        // Refresh Modal
                        // We need to trigger the modal refresh. 
                        // Since `MarkAsPaid` is global, we can't easily access the internal `loadCertTable`.
                        // But we can close the modal and reopen it, or click the "Print Certificate" button again?
                        // Better: Close Swal and let user re-open checks. 
                        // Actually, if we are inside the Swal modal (Certificates), we should refresh it.
                        // But the Certificate List is a Swal itself!
                        // So we might need to close it.
                        
                        Swal.close(); // Close the "Paid!" success alert
                        // Re-open the certificate list
                        PrintReport(); 
                    } else {
                        Swal.fire('Error', response.MESSAGE, 'error');
                    }
                }
            });
        }
    });
}