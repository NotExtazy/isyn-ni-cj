var CustomerInfoTbl;
var VALID_MOBILE_PREFIXES = [];
var VALID_LANDLINE_PREFIXES = [];

// UI HELPERS
const UI = {
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
    // --- PAGINATION SETTING ---
    $.fn.DataTable.ext.pager.numbers_length = 7;

    // --- MODERN SEARCH BINDING ---
    let searchTimer = null;
    $("#customerSearch").on("input", function () {
        const val = (this.value || "").trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if(CustomerInfoTbl){
                CustomerInfoTbl.search(val).draw();
            }
        }, 300);
    });

    LoadCustomerList();
    LoadCustomerTypeOptions();
    LoadGenderOptions();
    LoadSuffixOptions();
    LoadValidPrefixes();
    LoadRegions(); 
    
    // --- INPUT LIMITS ---
    // Street limited to 50 characters (User Request)
    $('#street').attr('maxlength', '50');
    
    // Name fields limited to 50 characters
    $('#firstName, #middleName, #lastName').attr('maxlength', '50');

    // Standard limits for other fields
    $('#companyName, #productInfo, #email').attr('maxlength', '100');

    // --- DATETIME PICKER (Calendar Only) ---
    $('#birthdate, #clientSince').datetimepicker({
        timepicker: false,
        format: 'm/d/Y',
        maxDate: new Date(), 
        scrollMonth: false,
        scrollInput: false,
        validateOnBlur: false 
    });

    // --- STRICT DATE INPUT HANDLING (Keyboard) ---
    $('#birthdate, #clientSince').attr('maxlength', '10');
    
    $('#birthdate, #clientSince').on('keydown', function(e) {
        // Allow: backspace, delete, tab, escape, enter, ctrl+a, arrows
        if ($.inArray(e.keyCode, [46, 8, 9, 27, 13]) !== -1 ||
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) || 
            (e.keyCode >= 35 && e.keyCode <= 40)) {
                 return; 
        }
        // Block non-numbers (letters, symbols)
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });

    // Auto-Add Slashes Logic (MM/DD/YYYY)
    $('#birthdate, #clientSince').on('keyup', function(e) {
        if (e.keyCode !== 8) { // If NOT backspace
            var val = $(this).val();
            if (val.length === 2 || val.length === 5) {
                $(this).val(val + '/');
            }
        }
    });

    // --- UTILITIES (TIN Formatting & Uppercase) ---
    // --- UTILITIES (TIN Formatting & Uppercase) ---
    CommonValidation.bindTINFormatting('#tin');

    // Uppercase for text inputs
    $('body').on('input', 'input[type="text"]', function() {
        if(this.id !== 'birthdate' && this.id !== 'clientSince' && this.id !== 'email' && this.id !== 'tin'){
            let start = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, start);
        }
    });
});

function LoadValidPrefixes() {
    $.ajax({
        url: "../../routes/administrator/maintenanceprefix.route.php",
        type: "POST",
        data: { action: "GetValidPrefixes" },
        dataType: "JSON",
        success: function(response) {
            if (response) {
                VALID_MOBILE_PREFIXES = response.MOBILE_PREFIXES || [];
                VALID_LANDLINE_PREFIXES = response.LANDLINE_PREFIXES || [];
            }
        },
        error: function(e) { console.error("Failed to load prefixes", e); }
    });
}

// ==========================================
//    ADDRESS LOGIC 
// ==========================================
function LoadRegions() {
    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php", 
        type: "POST",
        data: { maintenance_action: "get_All" },
        dataType: "JSON",
        success: function(data) {
            $("#Region").empty().append('<option value="" selected>Select</option>');
            $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
            $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
            $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

            if(data.region){
                $.each(data.region, function(key, value) {
                    let val = value.Region || value.REGION || value;
                    $("#Region").append('<option value="' + val + '">' + val + '</option>');
                });
            }
        }
    });
}

function LoadProvince(region, selected = null) {
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    if (!region) return;

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { maintenance_action: "get_province", region_selected: region },
        dataType: "JSON",
        success: function(data) {
            let list = data.LIST ? data.LIST : [];
            $.each(list, function(key, value) {
                let val = value.Province || value.PROVINCE || value;
                $("#Province").append('<option value="' + val + '">' + val + '</option>');
            });

            if(!$('#Region').prop('disabled')) $("#Province").prop('disabled', false);
            if (selected) { $("#Province").val(selected); }
        }
    });
}

function LoadCitytown(province, selected = null) {
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    if (!province) return;

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { maintenance_action: "get_citytown", province_selected: province },
        dataType: "JSON",
        success: function(data) {
            let list = data.LIST ? data.LIST : [];
            $.each(list, function(key, value) {
                let val = value.CityTown || value.MUNICIPALITY || value.CITYTOWN || value;
                $("#CityTown").append('<option value="' + val + '">' + val + '</option>');
            });

            if(!$('#Province').prop('disabled')) $("#CityTown").prop('disabled', false);
            if (selected) { $("#CityTown").val(selected); }
        }
    });
}

function LoadBrgy(citytown, selected = null) {
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    if (!citytown) return;

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { maintenance_action: "get_brgy", citytown_selected: citytown },
        dataType: "JSON",
        success: function(data) {
            let list = data.LIST ? data.LIST : [];
            $.each(list, function(key, value) {
                let val = value.Barangay || value.BARANGAY || value;
                $("#Barangay").append('<option value="' + val + '">' + val + '</option>');
            });

            if(!$('#CityTown').prop('disabled')) $("#Barangay").prop('disabled', false);
            if (selected) { $("#Barangay").val(selected); }
        }
    });
}

$("#Region").on("change", function() { LoadProvince($(this).val()); });
$("#Province").on("change", function() { LoadCitytown($(this).val()); });
$("#CityTown").on("change", function() { LoadBrgy($(this).val()); });


// ==========================================
//    DROPDOWN LOADING
// ==========================================

function LoadCustomerTypeOptions(selected){
    $.ajax({
        url:"../../routes/profiling/customerinfo.route.php",
        type:"POST",
        data:{action:"LoadCustomerTypes"},
        dataType:"JSON",
        success:function(response){
            $("#customerType").empty().append('<option value="" disabled selected>Select</option>');
            $("#reportCustomerType").empty().append('<option value="ALL" selected>ALL</option>');
            if(response.TYPES){
                $.each(response.TYPES,function(key,value){
                    $("#customerType").append('<option value="'+value["choice_value"]+'">'+value["choice_value"]+'</option>');
                });
            }
            if(response.ALL_TYPES){
                $.each(response.ALL_TYPES,function(key,value){
                    $("#reportCustomerType").append('<option value="'+value["choice_value"]+'">'+value["choice_value"]+'</option>');
                });
            }
            if(selected){ $("#customerType").val(selected); }
        }
    });
}

function LoadGenderOptions(selected){
    $.ajax({
        url:"../../routes/profiling/customerinfo.route.php",
        type:"POST",
        data:{action:"LoadGenders"},
        dataType:"JSON",
        success:function(response){
            $("#gender").empty().append('<option value="" selected>Select</option>');
            if(response.GENDERS){
                $.each(response.GENDERS,function(key,value){
                    $("#gender").append('<option value="'+value["choice_value"]+'">'+value["choice_value"]+'</option>');
                });
            }
            if(selected){ $("#gender").val(selected); }
        }
    });
}

function LoadSuffixOptions(selected){
    $.ajax({
        url:"../../routes/profiling/customerinfo.route.php",
        type:"POST",
        data:{action:"LoadSuffixes"},
        dataType:"JSON",
        success:function(response){
            $("#suffix").empty().append('<option value="" selected>Select (Optional)</option>');
            if(response.SUFFIXES){
                $.each(response.SUFFIXES,function(key,value){
                    $("#suffix").append('<option value="'+value["choice_value"]+'">'+value["choice_value"]+'</option>');
                });
            }
            if(selected){ $("#suffix").val(selected); }
        }
    });
}


// ==========================================
//    DYNAMIC FIELD CONFIGURATION
// ==========================================

// Global cache for field configurations
var FIELD_CONFIG_CACHE = {};

function GetFieldConfig(customerType) {
    if (!customerType) return;

    // Cache removed to ensure latest config is always applied
    // if (FIELD_CONFIG_CACHE[customerType]) { ... }

    // Show loading state (optional, or just disable fields while loading)
    // $('#customerinfo input, #customerinfo select').not('#customerNo, #customerType').prop('disabled', true);

    $.ajax({
        url: "../../routes/administrator/maintenance.route.php",
        type: "POST",
        data: { 
            action: "GetFieldConfig", 
            customerType: customerType 
        },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS === 'success') {
                // CONFIG may be null for new types with no restrictions set yet
                var config = response.CONFIG || null;
                // FIELD_CONFIG_CACHE[customerType] = config;
                ApplyFieldConfig(config);
            } else {
                console.error("Failed to load field config", response);
                // Fallback: enable all fields so form is usable
                ApplyFieldConfig(null);
            }
        },
        error: function(e) {
            console.error("Error loading field config", e);
            ApplyFieldConfig(null);
        }
    });
}

function ApplyFieldConfig(config) {
    // All fields that can be controlled by Customer Type configuration
    const allFields = [
        // Personal info
        'companyName', 'firstName', 'lastName', 'middleName', 'suffix',
        'birthdate', 'age', 'gender',
        // Contact
        'mobileNumber', 'email',
        // Address — now configurable (previously hardcoded always-enabled)
        // UPDATE: User requested Address/Product/ClientSince to be ALWAYS ENABLED.
        // So they are removed from this config list.
        'tin', 'mobileNumber', 'email'
    ];

    // 0. ALWAYS ENABLED FIELDS (Per User Request)
    // Run this FIRST to ensure they are active regardless of config state.
    const alwaysEnabled = ['street', 'Region', 'productInfo', 'clientSince'];
    alwaysEnabled.forEach(field => {
        $('#' + field).prop('disabled', false);
    });

    // 1. Reset all configurable fields to DISABLED first
    allFields.forEach(field => {
        let el = $('#' + field);
        el.prop('disabled', true);
        el.prop('required', false);
    });
    // Province/City/Barangay are always disabled until Region is chosen (cascade logic)
    $("#Province, #CityTown, #Barangay").empty()
        .append('<option value="" selected>Select</option>')
        .prop('disabled', true);

    // 2. If no config, enable everything (no restrictions set yet)
    if (!config) {
        allFields.forEach(field => $('#' + field).prop('disabled', false));
        $('#Region').prop('disabled', false);
        return;
    }

    // 3. Enable fields from config
    if (config.enabledFields) {
        config.enabledFields.forEach(field => {
            $('#' + field).prop('disabled', false);
        });
    }

    // 4. Set required fields from config
    if (config.requiredFields) {
        config.requiredFields.forEach(field => {
            $('#' + field).prop('required', true);
        });
    }
    // Note: Province, CityTown, Barangay are cascade-dependent on Region (handled separately)

    // Note: Province, CityTown, Barangay are cascade-dependent on Region (handled separately)
}

// Bind the change event
$('#customerType').on('change', function() {
    var type = $(this).val();
    if (type) {
        GetFieldConfig(type);
    } else {
        // If no type selected, disable everything except customer number and type
        $('#customerinfo input, #customerinfo select').not('#customerNo, #customerType, #addNew, #editButton, #submitButton, #cancel').prop('disabled', true);
    }
});


// ==========================================
//    INPUT RESTRICTIONS
// ==========================================

function GenerateCustomerNo(){
    $.ajax({
        url: "../../routes/profiling/customerinfo.route.php",
        type: "POST",
        data: {action: "GenerateCustomerNo"},
        dataType: "JSON",
        success: function(response){
            $('#customerNo').val(response.newCustomerNo);
        }
    });
}

// 1. NAME REGEX: Allows Letters, Spaces, Periods(.), Hyphens(-), Apostrophes(')
$('#firstName, #middleName, #lastName').on('input', function() {
    this.value = this.value.replace(/[^A-Za-z\s.\-']/g, '');
});

// 2. ADDRESS REGEX: Alphanumeric + Common Address Symbols (.,&'#-/ )
$('#companyName, #street').on('input', function() {
    this.value = this.value.replace(/[^A-Za-z0-9\s.,&'#\-\/]/g, '');
});

$('#mobileNumber').on('input', function() {
    var digits = this.value.replace(/\D/g, '');
    digits = '09' + digits.replace(/^0?9?/, '');
    digits = digits.slice(0, 11);
    this.value = digits;

    var input = $(this);
    var value = input.val();
    var isValid = VALID_MOBILE_PREFIXES.some(function(prefix) { return value.startsWith(prefix); });

    if (value.length >= 4) {
        if (isValid) input.removeClass('is-invalid').addClass('is-valid');
        else input.removeClass('is-valid').addClass('is-invalid');
    } else {
        input.removeClass('is-invalid is-valid');
    }
});

// ==========================================
//    MAIN TABLE & DATA LOADING
// ==========================================

function LoadCustomerList(){
    $.ajax({
        url: "../../routes/profiling/customerinfo.route.php",
        type: "POST",
        data: { action: "LoadCustomerList" },
        dataType: "JSON",
        beforeSend: function(){
            if ($.fn.DataTable.isDataTable('#CustomerInfoTbl')) {
                $('#CustomerInfoTbl').DataTable().clear().destroy();
            }
            $("#CustomerInfoList").html('<tr><td colspan="7">Loading...</td></tr>');
        },
        success: function(response){
            $("#CustomerInfoList").empty();
            if(response.CUSTOMERLIST && response.CUSTOMERLIST.length > 0){
                $.each(response.CUSTOMERLIST, function(key, value){
                    var name = value["Name"] || value["name"] || "";
                    var type = value["customerType"] || "";
                    var mobile = value["mobileNumber"] || "";
                    var email = value["email"] || "";
                    var id = value["clientNo"] || "";
                    var address = value["FullAddress"] || ""; 
                    var dateEncoded = value["dateEncoded"] ? value["dateEncoded"] : "-";

                    var row = '<tr>' +
                            '<td>' + escapeHtml(id) + '</td>' +
                            '<td class="text-start" style="text-align: left !important"><span class="fw-bold text-dark">' + escapeHtml(name) + '</span><br><small class="text-muted">' + escapeHtml(address) + '</small></td>' +
                            '<td><span class="fw-bold text-dark">' + escapeHtml(type) + '</span></td>' +
                            '<td>' + escapeHtml(mobile) + '</td>' +
                            '<td class="text-center">' + escapeHtml(email) + '</td>' +
                            '<td>' + escapeHtml(dateEncoded) + '</td>' +
                            '<td class="d-none">' + escapeHtml(address) + '</td>' +
                        '</tr>';
                    $("#CustomerInfoList").append(row);
                });
            }

            // Ensure table has proper classes before initializing
            $('#CustomerInfoTbl').addClass('table table-hover');

            CustomerInfoTbl = $('#CustomerInfoTbl').DataTable({
                scrollX: false, // Disable horizontal scroll
                autoWidth: false, // Allow columns to size automatically to container
                pageLength: 10,
                info: false, // Hide "Showing X entries" text
                "order": [],
                columnDefs: [
                    { targets: [2, 3, 4], orderable: false }
                ], 
                language: { 
                    emptyTable: "No customers found",
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                }
            });
        },
        error: function(xhr){
            console.error("AJAX Error:", xhr.responseText);
            $("#CustomerInfoList").empty();
            CustomerInfoTbl = $('#CustomerInfoTbl').DataTable();
        }
    });
}

// ==========================================
//    ROW CLICK (EDIT)
// ==========================================
$('#CustomerInfoTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
    } else {
        CustomerInfoTbl.rows('.selected').nodes().each((row) => {
            row.classList.remove('selected');
        });
        classList.add('selected');
    }

    var data = $('#CustomerInfoTbl').DataTable().row(this).data();
    if(!data) return;

    $('#customerinfo input, #customerinfo select').prop('disabled', true);
    $('#editButton').show().prop('disabled', false);
    $('#addNew').show().prop('disabled', true);
    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#updateButton').show().prop('disabled', true);
    $('#submitButton').hide();

    var clientNo = data[0];
    $('#customerNo').val(clientNo);

    $.ajax({
        url: '../../routes/profiling/customerinfo.route.php',
        method: 'POST',
        data: { action: "GetCustomerInfo", clientNo: clientNo },
        dataType: 'JSON',
        success: function(response) {
            if(response.STATUS == "LOADED"){
                var INFO = response.INFO;
                $('#customerID').val(INFO.id);
                $('#customerType').val(INFO.customerType);
                $('#customerNo').val(INFO.clientNo);
                
                // LOAD clientSince (Convert YYYY-MM-DD to MM/DD/YYYY)
                if(INFO.clientSince) {
                    var dEnc = new Date(INFO.clientSince);
                    var fDateEnc = ("0" + (dEnc.getMonth() + 1)).slice(-2) + "/" + ("0" + dEnc.getDate()).slice(-2) + "/" + dEnc.getFullYear();
                    $('#clientSince').val(fDateEnc);
                } else {
                    $('#clientSince').val('');
                }

                $('#firstName').val(INFO.firstName);
                $('#middleName').val(INFO.middleName);
                $('#lastName').val(INFO.lastName);
                $('#suffix').val(INFO.suffix || '');
                
                // LOAD Birthdate (Convert YYYY-MM-DD to MM/DD/YYYY)
                if(INFO.birthdate) {
                    var dBirth = new Date(INFO.birthdate);
                    var fBirth = ("0" + (dBirth.getMonth() + 1)).slice(-2) + "/" + ("0" + dBirth.getDate()).slice(-2) + "/" + dBirth.getFullYear();
                    $('#birthdate').val(fBirth);
                }

                $('#age').val(INFO.age);
                $('#gender').val(INFO.gender);
                $('#mobileNumber').val(INFO.mobileNumber);
                $('#companyName').val(INFO.companyName);
                $('#email').val(INFO.email);
                $('#tin').val(INFO.tinNumber);
                $('#productInfo').val(INFO.productInfo);
                $('#street').val(INFO.street);

                $('#Region').val(INFO.Region);
                LoadProvince(INFO.Region, INFO.Province); 
                LoadCitytown(INFO.Province, INFO.CityTown); 
                LoadBrgy(INFO.CityTown, INFO.Barangay);
            }
        }
    });
});


// ==========================================
//    BUTTON HANDLERS
// ==========================================

$('#addNew').on('click', function() {    
    $('#customerType').prop('disabled', false);
    $('#customerNo').prop('disabled', true).prop('readonly', true);
    GenerateCustomerNo(); 

    // SET TODAY'S DATE (MM/DD/YYYY) on #clientSince
    var now = new Date();
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    var today = month + "/" + day + "/" + now.getFullYear();
    
    $('#clientSince').val(today).prop('disabled', false); 

    // Remove explicit enabling of name/demographic fields here.
    // They will be handled by the ('#customerType').trigger('change') below.
    
    $('#email').prop('disabled', false);
    
    $('#Region').prop('disabled', false).val('');
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    $('#tin').prop('disabled', false);
    $('#street').prop('disabled', false);
    $('#productInfo').prop('disabled', false);
    
    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#submitButton').show().prop('disabled', false);
    
    $("#CustomerInfoTbl tbody tr").removeClass("selected");
    
    // Always trigger change to set initial state (disabled if empty, or specific fields if set)
    $('#customerType').trigger('change');
});

$('#editButton').on('click', function() {
    $('#customerinfo input, #customerinfo select').not('#customerNo').prop('disabled', false);
    $('#customerNo').prop('disabled', true); 
    
    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#submitButton').hide();
    $('#updateButton').show().prop('disabled', false);
    
    var selectedType = $('#customerType').val();
    if (selectedType) { $('#customerType').trigger('change'); }
});

function Cancel(){
    $('#customerinfo')[0].reset();
    $('#customerinfo input, #customerinfo select').prop('disabled', true);
    
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    $('#cancel').prop('hidden', true).prop('disabled', true);
    $('#updateButton').hide();
    $('#submitButton').show().prop('disabled', true);
    $('#editButton').show().prop('disabled', true);
    $('#addNew').show().prop('disabled', false);
    
    $("#CustomerInfoTbl tbody tr").removeClass("selected");
    $('#email').removeClass('email-invalid');
    $('#emailError').removeClass('show');
}


// ==========================================
//    VALIDATION LOGIC
// ==========================================

// ==========================================
//    VALIDATION LOGIC
// ==========================================

function ValidateCustomerFields(){
    var errorCount = 0;
    
    // --- Helper function to show errors ---
    function showError(elementId, message) {
        var el = $('#' + elementId);
        el.addClass('is-invalid'); // Bootstrap class for red border
        var errorEl = el.next('.invalid-feedback');
        
        // If error message div exists, update text
        if (errorEl.length) {
            errorEl.text(message).show();
        } else {
            // If doesn't exist, create it (fallback)
            el.after('<div class="invalid-feedback" style="display:block">' + message + '</div>');
        }
        errorCount++;
    }

    // --- Helper to clear errors ---
    function clearError(elementId) {
        var el = $('#' + elementId);
        el.removeClass('is-invalid');
        el.next('.invalid-feedback').hide();
    }

    // 1. Customer Type (Required)
    var customerType = $('#customerType').val();
    if (!customerType) { 
        showError('customerType', 'Please select a Customer Type.'); 
    } else { 
        clearError('customerType'); 
    }

    // 2. Customer No (Required)
    var customerNo = $('#customerNo').val().trim();
    if (customerNo === "") { 
        showError('customerNo', 'Customer No is required.'); 
    } else { 
        clearError('customerNo'); 
    }

    // 3. Name Validation (Dynamic based on Enabled/Disabled state)
    var firstName = $('#firstName').val().trim();
    var lastName = $('#lastName').val().trim();
    var companyName = $('#companyName').val().trim();
    var namePattern = /^[A-Za-z\s.\-']+$/; 

    // COMPANY NAME
    if (!$('#companyName').prop('disabled')) {
        // Check Required
        if ($('#companyName').prop('required') && companyName === "") {
            showError('companyName', 'Company Name is required.');
        } 
        // Check Format (if has value)
        else if (companyName !== "" && !/^[A-Za-z0-9\s.,&'#\-\/]+$/.test(companyName)) {
            showError('companyName', 'Invalid characters in Company Name.');
        } else {
            clearError('companyName');
        }
    } else {
        clearError('companyName');
    }

    // FIRST NAME
    if (!$('#firstName').prop('disabled')) {
        if ($('#firstName').prop('required') && firstName === "") {
            showError('firstName', 'First Name is required.');
        } else if (firstName !== "" && !namePattern.test(firstName)) {
            showError('firstName', 'Invalid characters in First Name.');
        } else {
            clearError('firstName');
        }
    } else {
        clearError('firstName');
    }

    // LAST NAME
    if (!$('#lastName').prop('disabled')) {
        if ($('#lastName').prop('required') && lastName === "") {
            showError('lastName', 'Last Name is required.');
        } else if (lastName !== "" && !namePattern.test(lastName)) {
            showError('lastName', 'Invalid characters in Last Name.');
        } else {
            clearError('lastName');
        }
    } else {
        clearError('lastName');
    }
    
    // MIDDLE NAME (Optional but check pattern if entered)
    if (!$('#middleName').prop('disabled') && $('#middleName').val().trim() !== "") {
        if (!namePattern.test($('#middleName').val().trim())) {
             showError('middleName', 'Invalid characters.');
        } else {
             clearError('middleName');
        }
    } else {
         clearError('middleName');
    }

    // ... (rest of validation)
    
    // 4. Contact Info
    var mobileNumber = $('#mobileNumber').val().trim();
    var email = $('#email').val().trim();
    // ...
    
    // ... [Inside ValidateCustomerFields - no changes needed for rest, moving to change handler] ...
    


    // 4. Contact Info
    var mobileNumber = $('#mobileNumber').val().trim();
    var email = $('#email').val().trim();

    // Mobile
    if (mobileNumber === "" || mobileNumber.length !== 11) {
        showError('mobileNumber', 'Mobile Number must be 11 digits.');
    } else {
        var isPrefixValid = VALID_MOBILE_PREFIXES.some(function(prefix) { return mobileNumber.startsWith(prefix); });
        if (!isPrefixValid) {
            showError('mobileNumber', 'Invalid Network Prefix.');
        } else {
            clearError('mobileNumber');
        }
    }

    // Email
    if (email === "" || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showError('email', 'Valid Email is required.');
        $('#emailError').addClass('show'); // Custom error div from HTML
    } else {
        clearError('email');
        $('#emailError').removeClass('show');
    }

    // 5. Address Fields (Required)
    var region = $('#Region').val();
    var province = $('#Province').val();
    var city = $('#CityTown').val();
    var brgy = $('#Barangay').val();
    var street = $('#street').val().trim();

    if(!region) { showError('Region', 'Region is required.'); } else { clearError('Region'); }
    if(!province) { showError('Province', 'Province is required.'); } else { clearError('Province'); }
    if(!city) { showError('CityTown', 'City/Town is required.'); } else { clearError('CityTown'); }
    if(!brgy) { showError('Barangay', 'Barangay is required.'); } else { clearError('Barangay'); }

    if (street === "") {
        showError('street', 'Street/House No. is required.');
    } else if (/[^A-Za-z0-9\s.,&'#\-\/]/.test(street)) {
        showError('street', 'Invalid characters in address.');
    } else {
        clearError('street');
    }

    // 6. Product Info (Required)
    var productInfo = $('#productInfo').val().trim();
    if(productInfo === "") {
        showError('productInfo', 'Product/Service info is required.');
    } else {
        clearError('productInfo');
    }

    // 7. TIN Logic
    var tin = $('#tin').val().trim();
    var tinExemptTypes = ['EXTERNAL CLIENT', 'OTHERS', 'OTHER CLIENT'];
    
    // If NOT exempt, TIN is required
    if (!tinExemptTypes.includes(customerType)) {
        if (tin === "") {
            showError('tin', 'TIN is required for this customer type.');
        } else if (tin.replace(/-/g, '').length !== 12) {
             showError('tin', 'TIN must be 12 digits.');
        } else {
            clearError('tin');
        }
    } else {
        // If exempt but user entered something, validate format
        if (tin !== "" && tin.replace(/-/g, '').length !== 12) {
            showError('tin', 'TIN must be 12 digits.');
        } else {
            clearError('tin');
        }
    }

    // 8. Date & Gender Logic
    var birthdate = $('#birthdate').val();
    var gender = $('#gender').val();
    var today = new Date();
    today.setHours(0,0,0,0);

    // Only validate if fields are enabled (meaning they are required for this type)
    if (!$('#birthdate').prop('disabled')) {
        if (!birthdate) {
            showError('birthdate', 'Birthdate is required.');
        } else {
            var inputDate = new Date(birthdate);
            if (inputDate > today) {
                showError('birthdate', 'Date cannot be in the future.');
            } else {
                clearError('birthdate'); 
            }
        }
    }

    if (!$('#gender').prop('disabled')) {
        if (!gender) {
            showError('gender', 'Gender is required.');
        } else {
            clearError('gender');
        }
    }

    // RETURN FALSE IF ANY ERRORS FOUND
    if (errorCount > 0) {
        UI.toast('warning', 'Please fill in all required fields.');
        return false;
    }

    return true;
}

// REMOVED HARDCODED CUSTOMER TYPE LOGIC
// The field enabling/disabling is now fully handled by GetFieldConfig() -> ApplyFieldConfig()
// triggered by the change handler at line ~387.

$("#birthdate").on("change",function(){
    var bdate = $(this).val();
    if(bdate){
        var bdateformat = new Date(bdate);
        var diff_ms =  Date.now() - bdateformat.getTime();
        var age_dt = new Date(diff_ms);
        var age = Math.abs(age_dt.getUTCFullYear() - 1970);
        $("#age").val(age);
    }
});


// ==========================================
//    SUBMISSION HANDLERS
// ==========================================

$("#submitButton").on("click",function(){
    if (!ValidateCustomerFields()) { return; }

    $('#age').prop('disabled', false);
    $('#customerNo').prop('disabled', false); 
    
    var form = $('#customerinfo')[0];
    var formData = new FormData(form);
    formData.append('action', 'SaveInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Are you sure?',
        icon: 'question',
        text: 'Save New Customer Information?',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        confirmButtonColor: '#435ebe',
        confirmButtonText: 'Yes, proceed!',
        preConfirm: function() {
            return $.ajax({
                url: "../../routes/profiling/customerinfo.route.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'JSON',
                success: function(response) { },
            });
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value.STATUS == 'success') {
                UI.toast('success', result.value.MESSAGE);
                LoadCustomerList();
                Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE);
            }
        }
    });
});

$("#updateButton").on("click",function(){
    $('#customerNo').prop('disabled', false);
    $('#age').prop('disabled', false);

    if (!ValidateCustomerFields()) { return; }
    
    var form = $('#customerinfo')[0];
    var formData = new FormData(form);
    formData.append('action', 'UpdateInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Are you sure?',
        icon: 'question',
        text: 'Save Changes?',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        confirmButtonColor: '#435ebe',
        confirmButtonText: 'Yes, proceed!',
        allowOutsideClick: false,
        preConfirm: function() {
            return $.ajax({
                url: "../../routes/profiling/customerinfo.route.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'JSON',
                success: function(response) { },
            });
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value.STATUS == 'success') {
                UI.toast('success', result.value.MESSAGE);
                LoadCustomerList();
                Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE);
            }
        }
    });
});

$('#reportButton').on('click', function() {
    $('#reportFilterModal').modal('show');
});

$('#reportCustomerType').on('change', function() {
    var selectedType = $(this).val();
    if (selectedType === 'ALL') {
        CustomerInfoTbl.column(2).search('').draw();
    } else {
        CustomerInfoTbl.column(2).search('^' + selectedType + '$', true, false).draw();
    }
});

// Export PDF
$('#exportPdfBtn').off('click').on('click', function(){
    var selectedType = $('#reportCustomerType').val();
    var url = "../../routes/profiling/customerinfo.route.php?action=PrintCustomerReport&filter=" + encodeURIComponent(selectedType);
    window.open(url, '_blank');
});

// Export XML (Excel)
$('#exportCsvBtn').off('click').on('click', function(){
    var selectedType = $('#reportCustomerType').val();
    var url = "../../routes/profiling/customerinfo.route.php?action=PrintCustomerReportExcel&filter=" + encodeURIComponent(selectedType);
    window.open(url, '_blank');
});


// ==========================================
//    MODERN REPORT MODAL HANDLERS
// ==========================================

// 1. Option Card Selection
$(document).on('click', '.report-option-card', function() {
    $('.report-option-card').removeClass('active');
    $(this).addClass('active');
});

// 2. Generate Button Click
$('#confirmReportBtn').on('click', function() {
    var selectedCard = $('.report-option-card.active');
    
    if (selectedCard.length === 0) {
        UI.toast('warning', 'Please select a report format (PDF or Excel).');
        return;
    }

    var format = selectedCard.data('format');
    var selectedType = $('#reportCustomerType').val();

    if (format === 'excel') {
        var url = "../../routes/profiling/customerinfo.route.php?action=PrintCustomerReportExcel&filter=" + encodeURIComponent(selectedType);
        window.open(url, '_blank');
    } else if (format === 'pdf') {
        var url = "../../routes/profiling/customerinfo.route.php?action=PrintCustomerReport&filter=" + encodeURIComponent(selectedType);
        window.open(url, '_blank');
    }
    
    $('#reportFilterModal').modal('hide');
});