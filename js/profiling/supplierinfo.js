var SupplierInfoTbl, selectedSupplier = "None";
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

// 1. Load the table when the page starts
// 1. Load the table when the page starts
// Moved inside document.ready

$(document).ready(function(){
    // --- PAGINATION SETTING ---
    $.fn.DataTable.ext.pager.numbers_length = 7;

    // --- MODERN SEARCH BINDING ---
    let searchTimer = null;
    $("#supplierSearch").on("input", function () {
        const val = (this.value || "").trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if(SupplierInfoTbl){
                SupplierInfoTbl.search(val).draw();
            }
        }, 300);
    });

    LoadSupplierList();
    $('#supplierNo').prop('disabled', true);
    $('#supplierSince').datetimepicker({ timepicker: false, format: 'm/d/Y', maxDate: 0, scrollMonth: false, scrollInput: false, validateOnBlur: false }); // DatePicker Init
    LoadRegions();
    LoadValidPrefixes(); // Loads prefixes from DB for client-side validation

    // --- STRICT DATE INPUT HANDLING (Keyboard) ---
    $('#supplierSince').attr('maxlength', '10');
    
    $('#supplierSince').on('keydown', function(e) {
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
    $('#supplierSince').on('keyup', function(e) {
        if (e.keyCode !== 8) { // If NOT backspace
            var val = $(this).val();
            if (val.length === 2 || val.length === 5) {
                $(this).val(val + '/');
            }
        }
    });

    // 1. Format TIN
    // 1. Format TIN
    CommonValidation.bindTINFormatting('#tin');

    // 2. Force Uppercase
    $('input[type="text"]').on('input', function() {
        if(this.id !== 'email' && this.id !== 'facebookAccount'){
            let start = this.selectionStart;
            let end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        }
    });

    // 3. Print Report
    // 3. Report Modal Trigger
    $('#reportButton').off('click').on('click', function(){
        $('#reportFilterModal').modal('show');
    });

    // 4. Export PDF
    $('#printReportBtn').off('click').on('click', function(){
        var selectedType = $('#reportVatType').val() || 'ALL';
        var url = "../../routes/profiling/supplierinfo.route.php?action=PrintSupplierReport&filter=" + encodeURIComponent(selectedType);
        window.open(url, '_blank');
        $('#reportFilterModal').modal('hide');
    });

    // 5. Export Excel
    $('#exportCsvBtn').off('click').on('click', function(){
        var selectedType = $('#reportVatType').val() || 'ALL';
        var url = "../../routes/profiling/supplierinfo.route.php?action=PrintSupplierReportExcel&filter=" + encodeURIComponent(selectedType);
        window.open(url, '_blank');
        $('#reportFilterModal').modal('hide');
    });
});

// =========================================================
//  LOAD VALID PREFIXES (Mobile & Landline)
// =========================================================
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

// =========================================================
//  MOBILE NUMBER LOGIC (With Specific Error Message)
// =========================================================
// =========================================================
//  MOBILE NUMBER LOGIC (With Specific Error Message)
// =========================================================
$('#mobileNumber').on('input', function() {
    // 1. Formatting
    var digits = this.value.replace(/\D/g, '');
    
    // Allow empty field (USER REQUEST)
    if (digits.length === 0) {
        this.value = '';
        $(this).removeClass('is-invalid is-valid');
        $(this).next('.invalid-feedback').text('');
        return;
    }

    digits = '09' + digits.replace(/^0?9?/, ''); 
    digits = digits.slice(0, 11);
    this.value = digits;

    // 2. Validation
    var input = $(this);
    var value = input.val();
    var currentPrefix = value.substring(0, 4); 

    var isValid = VALID_MOBILE_PREFIXES.some(function(prefix) {
        return value.startsWith(prefix);
    });

    if (value.length >= 4) {
        if (isValid) {
            input.removeClass('is-invalid').addClass('is-valid');
            input.next('.invalid-feedback').text(''); 
        } else {
            input.removeClass('is-valid').addClass('is-invalid');
            input.next('.invalid-feedback').text('Invalid Network (' + currentPrefix + ')');
        }
    } else {
        input.removeClass('is-invalid is-valid');
        input.next('.invalid-feedback').text('');
    }
});

// =========================================================
//  TELEPHONE NUMBER LOGIC (With Specific Error Message)
// =========================================================
$('#telNumber').on('input', function(e) {
    var input = $(this);
    var rawValue = input.val().replace(/\D/g, '').substring(0, 10); // Max 10 digits
    var formattedValue = "";
    var currentPrefix = "";

    // 1. Formatting & Prefix Extraction
    if (rawValue.startsWith('02')) {
        currentPrefix = "02";
        if (rawValue.length > 6) formattedValue = rawValue.replace(/(\d{2})(\d{4})(\d+)/, '$1-$2-$3');
        else if (rawValue.length > 2) formattedValue = rawValue.replace(/(\d{2})(\d+)/, '$1-$2');
        else formattedValue = rawValue;
    } else {
        currentPrefix = rawValue.substring(0, 3);
        if (rawValue.length > 7) formattedValue = rawValue.replace(/(\d{3})(\d{4})(\d+)/, '$1-$2-$3');
        else if (rawValue.length > 3) formattedValue = rawValue.replace(/(\d{3})(\d+)/, '$1-$2');
        else formattedValue = rawValue;
    }
    
    input.val(formattedValue);

    // 2. Validation Logic
    var isValid = VALID_LANDLINE_PREFIXES.some(function(validPrefix) {
        return currentPrefix === validPrefix;
    });

    // Check validation if enough digits for a prefix exist
    var minLen = (currentPrefix === '02') ? 2 : 3;

    if (rawValue.length >= minLen) {
        if (isValid) {
            input.removeClass('is-invalid').addClass('is-valid');
            input.next('.invalid-feedback').text(''); 
        } else {
            input.removeClass('is-valid').addClass('is-invalid');
            input.next('.invalid-feedback').text('Invalid Area Code (' + currentPrefix + ')');
        }
    } else {
        input.removeClass('is-valid is-invalid');
    }
});

// =========================================================
//  LOAD TABLE DATA
// =========================================================
function LoadSupplierList(){
    $.ajax({
        url:"../../routes/profiling/supplierinfo.route.php",
        type:"POST",
        data:{action:"LoadSupplierList"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#SupplierInfoTbl' ) ) {
                $('#SupplierInfoTbl').DataTable().clear();
                $('#SupplierInfoTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
            $("#SupplierInfoList").empty();
            if(response.LIST && response.LIST.length > 0){
                $.each(response.LIST,function(key,value){
                    
                    // --- Format Telephone ---
                    let rawTel = value["telephoneNumber"];
                    let formattedTel = rawTel; 

                    if (rawTel && rawTel.length >= 7) {
                        let clean = rawTel.replace(/\D/g, '');
                        if (clean.startsWith('02')) {
                            formattedTel = clean.replace(/(\d{2})(\d{4})(\d+)/, '$1-$2-$3');
                        } else {
                            formattedTel = clean.replace(/(\d{3})(\d{3,4})(\d+)/, '$1-$2-$3');
                        }
                    } else if (!rawTel || rawTel === 'N/A') {
                        formattedTel = 'N/A';
                    }

                    // --- Append Row ---
                    // --- Append Row (Split Mobile/Tel, Add SupplierSince) ---
                    var supplierSince = value["supplierSince"] ? value["supplierSince"] : "-";
                    
                    $("#SupplierInfoList").append(`
                        <tr>
                            <td>${escapeHtml(value["supplierNo"])}</td>
                            <td class="text-start" style="text-align: left !important"><span class="fw-bold text-dark">${escapeHtml(value["supplierName"])}</span><br><small class="text-muted">${escapeHtml(value["fullAddress"])}</small></td>
                            <td>${escapeHtml(value["tinNumber"])}</td>
                            <td>${escapeHtml(value["mobileNumber"])} / ${escapeHtml(formattedTel)}</td>
                            <td>${escapeHtml(value["dateEncoded"])}</td>
                            <td class="d-none">${escapeHtml(value["fullAddress"])}</td>
                        </tr>
                    `);
                });
            }

            // --- DATATABLE INITIALIZATION (UPDATED) ---
            SupplierInfoTbl = $('#SupplierInfoTbl').DataTable({
                scrollX: false,
                autoWidth: false,
                pageLength: 10,
                searching: true,
                ordering: true,         // Enable sorting arrows
                order: [[1, 'asc']],    // Default Sort: Column 1 (Supplier Name) A-Z
                lengthChange: true, // Enable page size selector
                lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]], // Page size options (5, 10, 25, 50, 100)
                info: false, // Hide "Showing X entries" text
                paging: true,
                responsive: true,
                columnDefs: [
                    { targets: 5, visible: false }, // Hide Address
                    { targets: [2, 3], orderable: false },
                    { targets: '_all', className: 'dt-center' } 
                ],
                language: { 
                    emptyTable: "No suppliers found",
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                }
            });
        }, 
    })
}

// =========================================================
//  TABLE ROW CLICK
// =========================================================
$('#SupplierInfoTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    
    if (classList.contains('selected')) {
        classList.remove('selected');
        Cancel();
    } else {
        SupplierInfoTbl.rows('.selected').nodes().each((row) => {
            row.classList.remove('selected');
        });
        classList.add('selected');
        
        var data = $('#SupplierInfoTbl').DataTable().row(this).data();
    
        $('#editButton').show().prop('disabled', false);
        $('#addNew').show().prop('disabled', true);
        $('#cancel').prop('hidden', false).prop('disabled', false);
        $('#updateButton').show().prop('disabled', true);
        $('#submitButton').hide();

        var supplierNo = data[0]; 

        $.ajax({
            url: '../../routes/profiling/supplierinfo.route.php',
            method: 'POST',
            data: { action: "GetSupplierInfo", supplierNo: supplierNo },
            dataType: 'JSON',
            success: function(response) {
                var INFO = response.INFO;
                selectedSupplier = INFO.id; 

                $('#supplierNo').val(INFO.supplierNo);
                $('#supplierName').val(INFO.supplierName);
                
                // Format TIN
                var rawTin = INFO.tinNumber || '';
                var formattedTin = rawTin;
                if (rawTin.length > 9) formattedTin = rawTin.replace(/(\d{3})(\d{3})(\d{3})(\d+)/, '$1-$2-$3-$4');
                else if (rawTin.length > 6) formattedTin = rawTin.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
                else if (rawTin.length > 3) formattedTin = rawTin.replace(/(\d{3})(\d+)/, '$1-$2');
                $('#tin').val(formattedTin);

                $('#email').val(INFO.email);
                
                // Mobile
                var rawMob = INFO.mobileNumber || '';
                var cleanMob = rawMob.replace(/\D/g, ''); 
                if(cleanMob.length === 10 && cleanMob.startsWith('9')) cleanMob = '0' + cleanMob;
                $('#mobileNumber').val(cleanMob);

                // Telephone
                var rawTel = INFO.telephoneNumber;
                if (rawTel && rawTel !== 'N/A') {
                    var cleanTel = rawTel.replace(/\D/g, ''); 
                    if (cleanTel.startsWith('02')) {
                        $('#telNumber').val(cleanTel.replace(/(\d{2})(\d{4})(\d+)/, '$1-$2-$3'));
                    } else {
                        $('#telNumber').val(cleanTel.replace(/(\d{3})(\d{4})(\d+)/, '$1-$2-$3'));
                    }
                } else {
                    $('#telNumber').val('');
                }

                $('#facebookAccount').val(INFO.facebookAccount);
                $('#supplierSince').val(INFO.supplierSince); // Populate Supplier Since
                $('#street').val(INFO.street);
                
                // ADDRESS LOADING (FORCE DISABLED AFTER LOAD)
                $('#Region').val(INFO.Region);
                LoadProvince(INFO.Region, INFO.Province); 
                LoadCitytown(INFO.Province, INFO.CityTown); 
                LoadBrgy(INFO.CityTown, INFO.Barangay);
                
                // CRITICAL: Disable all inputs initially when viewing
                $('#supplierInfo input, #supplierInfo select').prop('disabled', true);
                
                // Only enable the Edit button
                $('#editButton').prop('disabled', false);
            }
        });
    }
});

// =========================================================
//  BUTTON HANDLERS
// =========================================================
$('#editButton').on('click', function() {
    // Unlock Standard Inputs
    $('#supplierName').prop('disabled', false);
    $('#tin').prop('disabled', false);
    $('#email').prop('disabled', false);
    $('#mobileNumber').prop('disabled', false);
    $('#telNumber').prop('disabled', false);
    $('#telNumber').prop('disabled', false);
    $('#facebookAccount').prop('disabled', false);
    $('#supplierSince').prop('disabled', false); // Enable
    $('#street').prop('disabled', false);
    
    // ADDRESS LOGIC: Only enable child if parent has value
    $('#Region').prop('disabled', false);
    if ($('#Region').val()) $('#Province').prop('disabled', false);
    if ($('#Province').val()) $('#CityTown').prop('disabled', false);
    if ($('#CityTown').val()) $('#Barangay').prop('disabled', false);

    $('#editButton').hide();
    $('#updateButton').show().prop('disabled', false); 
    $('#submitButton').hide();
});

$('#addNew').on('click', function() {    
    $('#supplierInfo')[0].reset(); 
    $('#supplierNo').prop('disabled', true); 
    gnrtSupID(); 

    $('#supplierName').prop('disabled', false);
    $('#mobileNumber').prop('disabled', false).val('09'); 
    
    $('#email').prop('disabled', false);
    $('#Region').prop('disabled', false);
    
    // Force Disable Children
    $('#Province').prop('disabled', true).empty().append('<option value="" selected>Select</option>');
    $('#CityTown').prop('disabled', true).empty().append('<option value="" selected>Select</option>');
    $('#Barangay').prop('disabled', true).empty().append('<option value="" selected>Select</option>');

    $('#tin').prop('disabled', false);
    $('#street').prop('disabled', false);
    $('#telNumber').prop('disabled', false);
    $('#telNumber').prop('disabled', false);
    $('#facebookAccount').prop('disabled', false);
    $('#supplierSince').prop('disabled', false); // Enable


    $("#SupplierInfoTbl tbody tr").removeClass("selected");
    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#submitButton').show().prop('disabled', false);
    $('#editButton').prop('disabled', true);
    $('#updateButton').hide();
});

function Cancel() {
    selectedSupplier = "None";
    $('#supplierInfo')[0].reset(); 
    selectedSupplier = "None";
    $('#supplierInfo')[0].reset(); 
    $('#supplierInfo input, #supplierInfo select').prop('disabled', true);
    $('#supplierSince').prop('disabled', true); // Disable
    
    // Clear Validations
    $('#supplierInfo input, #supplierInfo select').removeClass('is-valid is-invalid');
    $('.invalid-feedback').hide();

    // Reset & Disable Address Children
    $('#Province, #CityTown, #Barangay').empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    
    $("#SupplierInfoTbl tbody tr").removeClass("selected");
    
    $('#cancel').prop('hidden', true).prop('disabled', true);
    $('#updateButton').hide();
    $('#submitButton').show().prop('disabled', true);
    $('#editButton').show().prop('disabled', true);
    $('#addNew').show().prop('disabled', false);
}

// =========================================================
//  VALIDATION LOGIC
// =========================================================
function ValidateSupplierFields() {
    let isValid = true;
    
    // Helper to show/clear error
    function showError(id, msg) {
        $(`#${id}`).addClass('is-invalid');
        $(`#${id}`).next('.invalid-feedback').text(msg).show();
        isValid = false;
    }
    function clearError(id) {
        $(`#${id}`).removeClass('is-invalid');
        $(`#${id}`).next('.invalid-feedback').hide();
    }

    // 1. Supplier Name
    if ($('#supplierName').val().trim() === "") showError('supplierName', "Required"); else clearError('supplierName');

    // 2. TIN
    if ($('#tin').val().trim() === "") showError('tin', "Required"); else clearError('tin');

    // 3. CONTACT NUMBER LOGIC (One or the other)
    let mobile = $('#mobileNumber').val().trim();
    let tel = $('#telNumber').val().trim();
    
    // FIX: Treat "09" as empty string for validation logic
    let hasMobile = (mobile !== "" && mobile !== "09");
    let hasTel = tel !== "";

    if (!hasMobile && !hasTel) {
        // Both empty -> Error on both
        showError('mobileNumber', "Mobile or Telephone is required.");
        showError('telNumber', "Mobile or Telephone is required.");
    } else {
        // At least one is present
        
        // 3a. Mobile Logic
        if (hasMobile) {
            if (!/^09\d{9}$/.test(mobile)) {
                showError('mobileNumber', "Must be 11 digits (09XXX).");
            } else {
                clearError('mobileNumber');
            }
        } else {
            // Mobile is empty (or just "09") but Tel exists -> Clear Mobile error
            // This effectively "Allows" the "09" to sit there if Tel is valid
            clearError('mobileNumber');
            $(`#mobileNumber`).next('.invalid-feedback').text(''); 
        }

        // 3b. Tel Logic
        if (hasTel) {
            if (tel.replace(/\D/g,'').length < 7) {
                showError('telNumber', "Invalid Telephone format.");
            } else {
                clearError('telNumber');
            }
        } else {
            // Tel is empty but Mobile exists -> Clear Tel error
            clearError('telNumber');
        }
    }

    // 4. Telephone (Optional in itself, handled above)
    // No extra check needed here unless we enforce specific length on frontend.

    // 5. Email (Optional? Input has pattern but not required attr in all cases? HTML has pattern)
    // HTML has `pattern` but validation requires manual check if we prevent submit.
    let email = $('#email').val().trim();
    let emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
    if (email !== "" && !emailPattern.test(email)) showError('email', "Invalid Email Address.");
    else clearError('email');

    // 6. Supplier Since (Optional -> Defaults to Current Date)
    // No validation needed as backend defaults it.
    clearError('supplierSince');

    // 7. Address
    if($('#Region').val() === "") showError('Region', "Required"); else clearError('Region');
    if($('#Province').val() === "") showError('Province', "Required"); else clearError('Province');
    if($('#CityTown').val() === "") showError('CityTown', "Required"); else clearError('CityTown');
    if($('#Barangay').val() === "") showError('Barangay', "Required"); else clearError('Barangay');
    
    if($('#street').val().trim() === "") showError('street', "Street is required."); else clearError('street');

    return isValid;
}

$("#submitButton").on("click",function(){
    if (!ValidateSupplierFields()) {
        UI.toast('warning', 'Please fill in all required fields.');
        return;
    }

    var form = $('#supplierInfo')[0];
    var formData = new FormData(form);
    var idValue = $('#supplierNo').val();
    formData.delete('supplierNo'); 
    formData.append('supplierNo', idValue); 
    formData.append('action', 'SaveInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Are you sure?', icon: 'question', text: 'Save New Supplier Information?', showCancelButton: true, confirmButtonText: 'Yes, proceed!',
        preConfirm: function() {
            return $.ajax({ url: "../../routes/profiling/supplierinfo.route.php", type: "POST", data: formData, processData: false, contentType: false, dataType: 'JSON' });
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value && result.value.STATUS == 'success') {
                UI.toast('success', result.value.MESSAGE);
                LoadSupplierList(); Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE || "Unknown Error");
            }
        }
    });
});


// =========================================================
//  REPORT GENERATION
// =========================================================
$("#exportCsvBtn").on("click", function(){
    let filter = 'ALL'; 
    window.open('../../routes/profiling/supplierinfo.route.php?action=PrintSupplierReportExcel&filter=' + filter, '_blank');
});

$("#printReportBtn").on("click", function(){
    let filter = 'ALL'; 
    window.open('../../routes/profiling/supplierinfo.route.php?action=PrintSupplierReport&filter=' + filter, '_blank');
});

$("#updateButton").on("click",function(){
    if (!ValidateSupplierFields()) {
        UI.toast('warning', 'Please correct the highlighted fields.');
        return;
    }

    $('#supplierNo').prop('disabled', false);
    var form = $('#supplierInfo')[0];
    var formData = new FormData(form);
    $('#supplierNo').prop('disabled', true); 

    formData.append('action', 'UpdateInfo');
    formData.append('supplierID', selectedSupplier);
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Are you sure?', icon: 'question', text: 'Save Changes?', showCancelButton: true, confirmButtonText: 'Yes, update!',
        preConfirm: function() {
            return $.ajax({ url: "../../routes/profiling/supplierinfo.route.php", type: "POST", data: formData, processData: false, contentType: false, dataType: 'JSON' });
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value && result.value.STATUS == 'success') {
                UI.toast('success', result.value.MESSAGE);
                LoadSupplierList(); Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE || "Unknown Error");
            }
        }
    });
})

function gnrtSupID(){
    $.ajax({
        url: '../../routes/profiling/supplierinfo.route.php', type: 'POST', data: {action: "gnrtSupID"}, dataType: 'JSON',
        success: function(response) { $('#supplierNo').val(response.supNo); },
        error: function(xhr) { console.error("ID Gen Error:", xhr.responseText); }
    });
}

// =========================================================
//  ADDRESS LOGIC (FIXED)
// =========================================================
function LoadRegions() {
    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php", 
        type: "POST", 
        data: { maintenance_action: "get_All" }, 
        dataType: "JSON",
        success: function(data) {
            $("#Region").empty().append('<option value="" selected>Select</option>');
            if(data.region){
                $.each(data.region, function(key, v) { 
                    let val = v.Region || v.REGION || v;
                    $("#Region").append('<option value="' + val + '">' + val + '</option>'); 
                });
            }
        }
    });
}

function LoadProvince(r, s=null) {
    // RESET LOGIC: If region is empty (unselected), disable and clear children
    if(!r) {
        $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        return;
    }

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { maintenance_action: "get_province", region_selected: r },
        dataType: "JSON",
        success: function(data) {
            // Enable only if parent is editable
            let isEditable = !$('#Region').prop('disabled');
            $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', !isEditable);

            $.each(data.LIST || [], function(k, v) { 
                let val = v.Province || v.PROVINCE || v; 
                $("#Province").append('<option value="' + val + '">' + val + '</option>'); 
            });
            if(s) $("#Province").val(s);
        }
    });
}

function LoadCitytown(p, s=null) {
    if(!p) {
        $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        return;
    }

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { maintenance_action: "get_citytown", province_selected: p },
        dataType: "JSON",
        success: function(data) {
            let isEditable = !$('#Province').prop('disabled');
            $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', !isEditable);

            $.each(data.LIST || [], function(k, v) { 
                let val = v.CityTown || v.CITYTOWN || v.MUNICIPALITY || v; 
                $("#CityTown").append('<option value="' + val + '">' + val + '</option>'); 
            });
            if(s) $("#CityTown").val(s);
        }
    });
}

function LoadBrgy(c, s=null) {
    if(!c) {
        $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        return;
    }

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { maintenance_action: "get_brgy", citytown_selected: c },
        dataType: "JSON",
        success: function(data) {
            let isEditable = !$('#CityTown').prop('disabled');
            $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', !isEditable);

            $.each(data.LIST || [], function(k, v) { 
                let val = v.Barangay || v.BARANGAY || v; 
                $("#Barangay").append('<option value="' + val + '">' + val + '</option>'); 
            });
            if(s) $("#Barangay").val(s);
        }
    });
}

$("#Region").change(function(){ LoadProvince($(this).val()); });
$("#Province").change(function(){ LoadCitytown($(this).val()); });
            $("#CityTown").change(function(){ LoadBrgy($(this).val()); });


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
    var selectedType = 'ALL'; // Default since dropdown was removed

    if (format === 'excel') {
        var url = "../../routes/profiling/supplierinfo.route.php?action=PrintSupplierReportExcel&filter=" + encodeURIComponent(selectedType);
        window.open(url, '_blank');
    } else if (format === 'pdf') {
        var url = "../../routes/profiling/supplierinfo.route.php?action=PrintSupplierReport&filter=" + encodeURIComponent(selectedType);
        window.open(url, '_blank');
    }
    
    $('#reportFilterModal').modal('hide');
});
