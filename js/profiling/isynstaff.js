var staffTbl;
var VALID_MOBILE_PREFIXES = []; // Store allowed prefixes

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

$(document).ready(function(){
    // --- PAGINATION SETTING ---
    $.fn.DataTable.ext.pager.numbers_length = 7;

    // --- MODERN SEARCH BINDING ---
    let searchTimer = null;
    $("#staffSearch").on("input", function () {
        const val = (this.value || "").trim();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            if(staffTbl){
                staffTbl.search(val).draw();
            }
        }, 300);
    });

    LoadStaff();
    LoadDropdowns(); 
    LoadRegions(); 
    LoadValidPrefixes(); // Load DB Prefixes

    // --- 1. RESTRICT NAME INPUTS ---
    $('#first_name, #middle_name, #last_name').on('input', function() {
        var start = this.selectionStart;
        var end = this.selectionEnd;
        this.value = this.value.toUpperCase().replace(/[^A-Z\s.-]/g, '');
        this.setSelectionRange(start, end);
    });

    // --- 2. RESTRICT DATES & AUTO-FORMAT ---
    var today = new Date().toISOString().split('T')[0];
    
    // Auto-format date inputs (MM/DD/YYYY)
    $('#birthdate, #date_hired').on('input', function() {
        var input = this.value.replace(/\D/g, ''); // Remove non-digits
        var formatted = '';
        
        if (input.length > 0) {
            formatted = input.substring(0, 2); // MM
        }
        if (input.length >= 3) {
            formatted += '/' + input.substring(2, 4); // DD
        }
        if (input.length >= 5) {
            formatted += '/' + input.substring(4, 8); // YYYY
        }
        
        this.value = formatted;
    });

    // --- 3. CONTACT NUMBER LOGIC (Dynamic Validation) ---
    $('#contact_num').on('input', function() {
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
            input.removeClass('is-invalid is-valid');
        }
    });

    // --- 4. GOV ID MASKING ---
    $('#pag_ibig').on('input', function(e) {
        // 0000-0000-0000 (12 digits -> 14 chars)
        var x = this.value.replace(/\D/g, '').substring(0, 12);
        var formatted = '';
        for (var i = 0; i < x.length; i++) {
            if (i === 4 || i === 8) formatted += '-';
            formatted += x[i];
        }
        this.value = formatted;
    });

    // TIN
    CommonValidation.bindTINFormatting('#tin');

    $('#philhealth').on('input', function(e) {
        // 00-000000000-0 (12 digits -> 14 chars)
        var x = this.value.replace(/\D/g, '').substring(0, 12);
        var formatted = '';
        for (var i = 0; i < x.length; i++) {
            if (i === 2 || i === 11) formatted += '-';
            formatted += x[i];
        }
        this.value = formatted;
    });

    $('#sss').on('input', function(e) {
        // 00-0000000-0 (10 digits -> 12 chars)
        var x = this.value.replace(/\D/g, '').substring(0, 10);
        var formatted = '';
        for (var i = 0; i < x.length; i++) {
            if (i === 2 || i === 9) formatted += '-';
            formatted += x[i];
        }
        this.value = formatted;
    });

    // --- 5. Auto-Calculate Age ---
    $('#birthdate').on('change blur', function() {
        var dateValue = this.value.trim();
        if (!dateValue) return;
        
        // Parse MM/DD/YYYY format
        var parts = dateValue.split('/');
        if (parts.length !== 3) return;
        
        var month = parseInt(parts[0], 10) - 1; // JS months are 0-indexed
        var day = parseInt(parts[1], 10);
        var year = parseInt(parts[2], 10);
        
        var birthdate = new Date(year, month, day);
        var today = new Date();
        var age = today.getFullYear() - birthdate.getFullYear();
        var m = today.getMonth() - birthdate.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
            age--;
        }
        $('#age').val(age);
    });
});

// ==========================================
//  HELPER: Date format handler for text inputs
// ==========================================
function convertToDateInputFormat(dateStr) {
    // Since we switched to type="text", we can display MM/DD/YYYY directly
    // Backend already returns dates as MM/DD/YYYY (e.g., "01/15/2023")
    if (!dateStr || dateStr === '-') return '';
    return dateStr; // Return as-is
}

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
            }
        },
        error: function(e) { console.error("Failed to load prefixes", e); }
    });
}

// ==========================================
//  VALIDATION LOGIC
// ==========================================
function validateStaffForm() {
    let isValid = true;

    // Helper to show/clear error
    function showError(id, msg) {
        $(`${id}`).addClass('is-invalid');
        $(`${id}`).next('.invalid-feedback').text(msg).show();
        // Since some are in input-groups, we might need to target the feedback specifically if it's next to the input
        // But in our PHP structure, they are generally siblings. 
        // For input groups (IDs), the feedback is after the input, which is inside the group. 
        // Wait, in PHP: <div class="input-group"><input ...><div class="invalid-feedback"></div></div>
        // So .next() works.
        isValid = false;
    }
    function clearError(id) {
        $(`${id}`).removeClass('is-invalid');
        $(`${id}`).next('.invalid-feedback').hide();
    }

    // 1. Employee No
    if ($('#employee_no').val().trim() === "") showError('#employee_no', "Employee No. is required."); else clearError('#employee_no');

    // 2. Names
    if ($('#first_name').val().trim() === "") showError('#first_name', "First Name is required."); else clearError('#first_name');
    if ($('#last_name').val().trim() === "") showError('#last_name', "Last Name is required."); else clearError('#last_name');

    // 3. Employee Details
    if ($('#employee_status').val() === "") showError('#employee_status', "Please select a status."); else clearError('#employee_status');
    if ($('#designation').val() === "") showError('#designation', "Please select a designation."); else clearError('#designation');
    if ($('#date_hired').val() === "") showError('#date_hired', "Date Hired is required."); else clearError('#date_hired');
    
    // 4. Age/Birthdate
    if ($('#birthdate').val() === "") {
        showError('#birthdate', "Birthdate is required.");
    } else if ($('#age').val() <= 0) {
        showError('#birthdate', "Invalid Birthdate (Age cannot be 0 or negative).");
    } else {
        clearError('#birthdate');
    }

    // 5. Email
    let email = $('#email_address').val().trim();
    let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === "") showError('#email_address', "Email Address is required.");
    else if (!emailRegex.test(email)) showError('#email_address', "Invalid Email Address.");
    else clearError('#email_address');

    // 6. Contact Number
    let contact = $('#contact_num').val();
    if (contact.length !== 11) showError('#contact_num', "Contact number must be exactly 11 digits.");
    else if ($('#contact_num').hasClass('is-invalid') && !isValid) {
         // Should already be handled by the input listener but we re-verify
         // Actually the input listener handles dynamic validation. 
         // If it's invalid from listener, we shouldn't clear it.
         // But if we just check length here, we trust the listener for prefix.
         // Let's just check length and emptiness.
         // If the listener marked it invalid, we keep it.
         if(contact === "") showError('#contact_num', "Contact is required.");
    } else {
         clearError('#contact_num');
    }
    // Double check specific prefix error from listener? 
    // The listener adds class is-invalid. We should respect that.
    if($('#contact_num').hasClass('is-invalid')) isValid = false;

    // 7. Address
    if ($('#Region').val() === "") showError('#Region', "Required"); else clearError('#Region');
    if ($('#Province').val() === "") showError('#Province', "Required"); else clearError('#Province');
    if ($('#CityTown').val() === "") showError('#CityTown', "Required"); else clearError('#CityTown');
    if ($('#Barangay').val() === "") showError('#Barangay', "Required"); else clearError('#Barangay');
    if ($('#Street').val().trim() === "") showError('#Street', "Street is required."); else clearError('#Street');

    // 8. Gov IDs (Length Checks)
    // TIN (12 digits), SSS (10), PhilHealth (12), Pag-IBIG (12)
    // Note: The input masks might add dashes. We count digits.
    
    let tin = $('#tin').val().replace(/\D/g,'');
    if(tin === "") showError('#tin', "TIN is required.");
    else if(tin.length !== 12) {
         showError('#tin', "TIN must be 12 digits.");
    } else clearError('#tin');

    let sss = $('#sss').val().replace(/\D/g,'');
    if(sss === "") showError('#sss', "SSS is required.");
    else if(sss.length !== 10) showError('#sss', "SSS must be 10 digits.");
    else clearError('#sss');

    let ph = $('#philhealth').val().replace(/\D/g,'');
    if(ph === "") showError('#philhealth', "PhilHealth is required.");
    else if(ph.length !== 12) showError('#philhealth', "PhilHealth must be 12 digits.");
    else clearError('#philhealth');

    let pagibig = $('#pag_ibig').val().replace(/\D/g,'');
    if(pagibig === "") showError('#pag_ibig', "Pag-IBIG is required.");
    else if(pagibig.length !== 12) showError('#pag_ibig', "Pag-IBIG must be 12 digits.");
    else clearError('#pag_ibig');

    return isValid;
}

// ==========================================
//  BUTTONS
// ==========================================

$('#exportCsvBtn').on('click', function(){
    const filter = 'ALL'; 
    window.location.href = `../../routes/profiling/isynstaff.route.php?action=PrintStaffReportExcel&filter=${filter}`;
});

$('#printReportBtn').on('click', function(){
    const filter = 'ALL';
    window.location.href = `../../routes/profiling/isynstaff.route.php?action=PrintStaffReport&filter=${filter}`;
});

$('#addNew').on('click', function() {    
    $('#staffForm input, #staffForm select').prop('disabled', false);
    $('#age').prop('disabled', true);
    $('#employee_no').prop('disabled', false); 
    
    $('#contact_num').val('09'); 
    
    $('#Region').val('');
    
    // RESET ADDRESS CHILDREN
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#submitButton').show().prop('disabled', false);
    $('#updateButton').hide();
    $('#editButton').prop('disabled', true);
    
    $("#staffTbl tbody tr").removeClass("selected");
});

$('#editButton').on('click', function() {
    // Enable Standard Fields
    $('#staffForm input, #staffForm select').prop('disabled', false);
    $('#employee_no').prop('disabled', true);
    $('#age').prop('disabled', true);

    // ADDRESS CASCADING ENABLE LOGIC
    // Only enable child dropdowns if their parent has a value
    if($('#Region').val()) $("#Province").prop('disabled', false);
    else $("#Province").prop('disabled', true);

    if($('#Province').val()) $("#CityTown").prop('disabled', false);
    else $("#CityTown").prop('disabled', true);

    if($('#CityTown').val()) $("#Barangay").prop('disabled', false);
    else $("#Barangay").prop('disabled', true);

    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#submitButton').hide();
    $('#updateButton').show().prop('disabled', false);
    $('#editButton').hide();
});

function Cancel(){
    $('#staffForm')[0].reset();
    $('#staffForm input, #staffForm select').prop('disabled', true);
    
    $('#contact_num').removeClass('is-valid is-invalid');

    // RESET & DISABLE ADDRESS CHILDREN
    $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
    $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);

    $('#cancel').prop('hidden', true).prop('disabled', true);
    $('#updateButton').hide();
    $('#submitButton').show().prop('disabled', true);
    $('#editButton').show().prop('disabled', true);
    $('#addNew').show().prop('disabled', false);
    $("#staffTbl tbody tr").removeClass("selected");
}

// ==========================================
//  SUBMIT / UPDATE HANDLERS
// ==========================================
$("#submitButton").on("click",function(){
    if (!validateStaffForm()) {
        UI.toast('warning', 'Please fill in all required fields.');
        return;
    } 

    var form = $('#staffForm')[0];
    var formData = new FormData(form);
    formData.append('age', $('#age').val()); // Ensure Age is sent
    formData.append('action', 'SaveInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Are you sure?', icon: 'question', text: 'Save New Staff Information?', showCancelButton: true, confirmButtonColor: '#435ebe', confirmButtonText: 'Yes, proceed!',
        preConfirm: function() {
            return $.ajax({
                url: "../../routes/profiling/isynstaff.route.php", type: "POST", data: formData, processData: false, contentType: false, dataType: 'JSON'
            });
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value.STATUS == 'success') {
                UI.toast('success', result.value.MESSAGE);
                LoadStaff(); Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE);
            }
        }
    });
})

$("#updateButton").on("click",function(){
    $('#employee_no').prop('disabled', false);
    // REMOVED: $('#age').prop('disabled', false); -> Keep Age Disabled
    
    if (!validateStaffForm()) {
        UI.toast('warning', 'Please correct the highlighted fields.');
        return;
    } 

    var form = $('#staffForm')[0];
    var formData = new FormData(form);
    // Manually append disabled fields if needed
    formData.append('age', $('#age').val()); 
    formData.append('action', 'UpdateInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Are you sure?', icon: 'question', text: 'Save Changes?', showCancelButton: true, confirmButtonColor: '#435ebe', confirmButtonText: 'Yes, proceed!',
        preConfirm: function() {
            return $.ajax({
                url: "../../routes/profiling/isynstaff.route.php", type: "POST", data: formData, processData: false, contentType: false, dataType: 'JSON'
            });
        },
    }).then(function(result) {
        if (result.isConfirmed) {
            if (result.value.STATUS == 'success') {
                UI.toast('success', result.value.MESSAGE);
                LoadStaff(); Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE);
            }
        }
    });
});

// Formatters for Edit Mode Load
function formatTIN(val) { return val ? val.replace(/(\d{3})(\d{3})(\d{3})(\d{3})/, '$1-$2-$3-$4') : ''; }
function formatSSS(val) { return val ? val.replace(/(\d{2})(\d{7})(\d{1})/, '$1-$2-$3') : ''; }
function formatPhilHealth(val) { return val ? val.replace(/(\d{2})(\d{9})(\d{1})/, '$1-$2-$3') : ''; }
function formatPagIBIG(val) { return val ? val.replace(/(\d{4})(\d{4})(\d{4})/, '$1-$2-$3') : ''; }

// ==========================================
//  DATA LOADING
// ==========================================
function LoadStaff(){
    $.ajax({
        url:"../../routes/profiling/isynstaff.route.php",
        type:"POST",
        data:{action:"LoadStaff"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#staffTbl' ) ) {
                $('#staffTbl').DataTable().clear().destroy();
            }
        },
        success:function(response){
            $("#staffList").empty();
            if(response.LIST){
                $.each(response.LIST,function(key,value){
                    let display_name = value["last_name"] + ", " + value["first_name"];
                    if(value["middle_name"]) display_name += " " + value["middle_name"];
                    
                    let dateEncoded = value["dateEncoded"] ? value["dateEncoded"] : "-";

                    $("#staffList").append(`
                        <tr>
                            <td>${escapeHtml(value["employee_no"])}</td>
                            <td class="text-start" style="text-align: left !important">${escapeHtml(display_name)}</td>
                            <td>${escapeHtml(value["employee_status"])}</td>
                            <td>${escapeHtml(value["designation"])}</td>
                            <td>${escapeHtml(dateEncoded)}</td>
                        </tr>
                    `);
                });
            }

            staffTbl = $('#staffTbl').DataTable({
                pageLength: 10,
                searching: true,
                ordering: true,
                order: [[1, 'asc']], // <--- UPDATED: Default Sort by Column 1 (Name) A-Z
                lengthChange: true, // Enable page size selector
                lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]], // Page size options (5, 10, 25, 50, 100)
                info: false, // Hide "Showing X entries" text
                paging: true,
                responsive: true,
                columnDefs: [ 
                    { targets: [0, 2, 3, 4], className: 'text-center' },
                    { targets: 1, className: 'text-start' },
                    { targets: 2, orderable: false }
                ],
                language: { 
                    emptyTable: "No staff found",
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                }
            });
        }, 
    })
}

function LoadDropdowns(){
    $.ajax({
        url: "../../routes/profiling/isynstaff.route.php",
        type: "POST",
        data: {action: "LoadDropdowns"},
        dataType: "JSON",
        success: function(response){
            $("#employee_status").empty().append('<option value="" selected>Select Employee Status</option>');
            if(response.STATUS_OPTS){
                $.each(response.STATUS_OPTS, function(key, value){
                    $("#employee_status").append(`<option value="${value.choice_value}">${value.choice_value}</option>`);
                });
            }

            $("#designation").empty().append('<option value="" selected>Select Designation</option>');
            if(response.DESIG_OPTS){
                $.each(response.DESIG_OPTS, function(key, value){
                    $("#designation").append(`<option value="${value.choice_value}">${value.choice_value}</option>`);
                });
            }
        }
    });
}

function setDropdownValue(selector, value) {
    if(!value) return;
    if ($(selector + " option[value='" + value + "']").length > 0) {
        $(selector).val(value);
    } else {
        $(selector).append(new Option(value, value, true, true));
    }
}

$('#staffTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
        Cancel();
    } else {
        staffTbl.rows('.selected').nodes().each((row) => {
            row.classList.remove('selected');
        });
        classList.add('selected');
    }

    var data = $('#staffTbl').DataTable().row(this).data();
    if(!data) return;

    $('#staffForm input, #staffForm select').prop('disabled', true);
    $('#editButton').show().prop('disabled', false);
    $('#addNew').show().prop('disabled', true);
    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#updateButton').show().prop('disabled', true);
    $('#submitButton').hide();

    var employeeNo = data[0];
    $('#employee_no').val(employeeNo);

    $.ajax({
        url: '../../routes/profiling/isynstaff.route.php',
        method: 'POST',
        data: { action: "GetStaffInfo", employeeNo: employeeNo },
        dataType: 'JSON',
        success: function(response) {
            if(response.STATUS == "LOADED"){
                var INFO = response.INFO;

                $('#id_staff').val(INFO.id_staff);
                $('#employee_no').val(INFO.employee_no);
                $('#date_hired').val(convertToDateInputFormat(INFO.date_hired));
                $('#first_name').val(INFO.first_name);
                $('#middle_name').val(INFO.middle_name);
                $('#last_name').val(INFO.last_name);
                $('#birthdate').val(convertToDateInputFormat(INFO.birthdate));
                $('#age').val(INFO.age);
                $('#email_address').val(INFO.email_address);
                
                var rawContact = INFO.contact_num || '';
                var cleanContact = rawContact.replace(/\D/g, ''); 
                if(cleanContact.length === 10 && cleanContact.startsWith('9')) cleanContact = '0' + cleanContact;
                $('#contact_num').val(cleanContact);
                
                $('#pag_ibig').val(formatPagIBIG(INFO.pag_ibig));
                $('#tin').val(formatTIN(INFO.tin_num));
                $('#philhealth').val(formatPhilHealth(INFO.philhealth_num));
                $('#sss').val(formatSSS(INFO.sss_num));
                
                $('#Street').val(INFO.street);
                
                setDropdownValue('#employee_status', INFO.employee_status);
                setDropdownValue('#designation', INFO.designation);
                
                $('#Region').val(INFO.region); 
                
                LoadProvince(INFO.region, INFO.province); 
                LoadCitytown(INFO.province, INFO.city); 
                LoadBrgy(INFO.city, INFO.barangay);

                // FORCE DISABLE ALL AFTER LOAD (Waiting for Edit click)
                $('#staffForm input, #staffForm select').prop('disabled', true);
                $('#editButton').prop('disabled', false);
            }
        }
    });
});

// ==========================================
//  ADDRESS LOGIC (FIXED)
// ==========================================
function LoadRegions() {
    $.ajax({ url: "../../routes/administrator/maintenanceaddress.route.php", type: "POST", data: { maintenance_action: "get_All" }, dataType: "JSON",
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
    // RESET: If Region is empty (unselected), clear and disable everything below it
    if(!r) {
        $("#Province").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        $("#CityTown").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        $("#Barangay").empty().append('<option value="" selected>Select</option>').prop('disabled', true);
        return;
    }

    $.ajax({ url: "../../routes/administrator/maintenanceaddress.route.php", type: "POST", data: { maintenance_action: "get_province", region_selected: r }, dataType: "JSON",
        success: function(data) {
            // Only enable if the parent (Region) is enabled/editable
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

    $.ajax({ url: "../../routes/administrator/maintenanceaddress.route.php", type: "POST", data: { maintenance_action: "get_citytown", province_selected: p }, dataType: "JSON",
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

    $.ajax({ url: "../../routes/administrator/maintenanceaddress.route.php", type: "POST", data: { maintenance_action: "get_brgy", citytown_selected: c }, dataType: "JSON",
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

$("#Region").change(function() { LoadProvince($(this).val()); });
$("#Province").change(function() { LoadCitytown($(this).val()); });
$("#CityTown").change(function() { LoadBrgy($(this).val()); });


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
    var filter = 'ALL';

    if (format === 'excel') {
        window.open(`../../routes/profiling/isynstaff.route.php?action=PrintStaffReportExcel&filter=${filter}`, '_blank');
    } else if (format === 'pdf') {
        window.open(`../../routes/profiling/isynstaff.route.php?action=PrintStaffReport&filter=${filter}`, '_blank');
    }
    
    $('#reportFilterModal').modal('hide');
});