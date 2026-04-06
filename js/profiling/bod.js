var bodTbl;
var committeeTbl;

var options = {
    rtl: false,
    format: 'm/d/Y',
    timepicker: false,
    datepicker: true,
    closeOnDateSelect: true,
    closeOnTimeSelect: true,
    mask: '99/99/9999',
};

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



$('.date').datetimepicker(options);

// Strict Date Validation
$(document).on('change', '.date', function() {
    var input = $(this);
    var dateVal = input.val();
    if (!dateVal) return;

    // Regex for MM/DD/YYYY or MM-DD-YYYY
    var regex = /^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/;
    var match = dateVal.match(regex);

    if (!match) {
        UI.toast('warning', 'Invalid date format. Please use MM/DD/YYYY.');
        input.val('');
        return;
    }

    var month = parseInt(match[1], 10);
    var day = parseInt(match[2], 10);
    var year = parseInt(match[3], 10);

    if (month < 1 || month > 12) {
        UI.toast('warning', 'Invalid month. Value must be between 1 and 12.');
        input.val('');
        return;
    }

    var monthDays = new Date(year, month, 0).getDate();
    if (day < 1 || day > monthDays) {
        UI.toast('warning', 'Invalid day. Value must be valid for the selected month.');
        input.val('');
        return;
    }
});



Initialize();

function Initialize(){
    $.ajax({
        url:"../../routes/profiling/bod.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        success:function(response){
            $("#slctYearBODModal").empty().append("<option value='All' selected>All Years</option>");
            $("#slctYearCmmttModal").empty().append("<option value='All' selected>All Years</option>");

            if(response.YRLIST){
                $.each(response.YRLIST,function(key,value){
                    $("#slctYearBODModal").append(`<option value="${value["Year"]}">${value["Year"]}</option>`);
                    $("#slctYearCmmttModal").append(`<option value="${value["Year"]}">${value["Year"]}</option>`);
                });
            }

            LoadBODList("All");
            LoadCMMTTList("All");
            LoadSelectOptions();
            LoadShareholderNames();
        }, 
    })
}

function LoadShareholderNames(){
    $('#shareholderName').typeahead({
        source: function(query, result){
            $.ajax({
                url: "../../routes/profiling/shareholderinfo.route.php",
                type:"POST",
                data:{action:"searchNames", name:query},
                dataType:"JSON",
                success:function(data){
                    result($.map(data, function(item){ return item; }));
                }
            })
        },
        minLength: 2,  // Only trigger after 2 characters
        autoSelect: false,  // Don't force selection from list
        items: 10  // Limit suggestions
    });
}

function LoadSelectOptions(){
    $.ajax({
        url:"../../routes/profiling/bod.route.php",
        type:"POST",
        data:{action:"LoadSelectOptions"},
        dataType:"JSON",
        success:function(response){
            $("#BODdesignation").empty().append("<option value='' selected>SELECT</option>");
            $.each(response.DESIGNATIONS,function(key,value){
                $("#BODdesignation").append(`<option value="${value["designation"]}">${value["designation"]}</option>`);
            });

            $("#committeeType").empty().append("<option value='' selected>SELECT</option>");
            $.each(response.COMMITTEETYPES,function(key,value){
                $("#committeeType").append(`<option value="${value["committeeType"]}">${value["committeeType"]}</option>`);
            });

            $("#specializedposition").empty().append("<option value='' selected>SELECT</option>");
            $.each(response.SPECIALIZEDPOSITIONS,function(key,value){
                $("#specializedposition").append(`<option value="${value["specializedposition"]}">${value["specializedposition"]}</option>`);
            });
        }
    })
}

$(document).on('change', '#BODdesignation', function() {
    var designation = $(this).val();
    if (designation && designation.trim().toUpperCase() === 'COMMITTEE') {
        $('#committeeType').prop('disabled', false);
    } else {
        $('#committeeType').prop('disabled', true).val('');
    }
});

function LoadBODList(Year){
    $.ajax({
        url:"../../routes/profiling/bod.route.php",
        type:"POST",
        data:{action:"LoadBODList", Year:Year},
        dataType:"JSON",
        success:function(response){
            if ($.fn.DataTable.isDataTable('#bodTbl')) {
                $('#bodTbl').DataTable().destroy();
            }
            
            var bodlist = "";
            if(response.BODLIST && response.BODLIST.length > 0){
               $.each(response.BODLIST, function(key, value){
                   bodlist += '<tr>'+
                                 '<td style="display:none;">'+escapeHtml(value.id)+'</td>'+
                                 '<td class="text-start" style="text-align: left !important;">'+escapeHtml(value.fullname)+'</td>'+
                                 '<td>'+escapeHtml(value.designation)+'</td>'+
                                 '<td>'+escapeHtml(value.fromdate)+'</td>'+
                                 '<td>'+escapeHtml(value.toDate)+'</td>'+
                                 '<td>'+escapeHtml(value.dateEncoded)+'</td>'+
                              '</tr>';
                });
            }
            $("#bodList").html(bodlist);

            bodTbl = $('#bodTbl').DataTable({
                "columnDefs": [{ "targets": 1, "className": "text-start" }],
                "pageLength": 10,
                "lengthMenu": [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                "searching": false,
                "ordering": false,
                "lengthChange": true, // Enabled
                "info": false, 
                "paging": true,
                "responsive": true,
                "language": {
                    "emptyTable": "No board members found for this year",
                    "paginate": {
                        "previous": '<i class="fa-solid fa-chevron-left"></i>',
                        "next": '<i class="fa-solid fa-chevron-right"></i>'
                    }
                }
            });
        }
    })
}

function LoadCMMTTList(Year){
    $.ajax({
        url:"../../routes/profiling/bod.route.php",
        type:"POST",
        data:{action:"LoadCMMTTList", Year:Year},
        dataType:"JSON",
        success:function(response){
            if ($.fn.DataTable.isDataTable('#committeeTbl')) {
                $('#committeeTbl').DataTable().destroy();
            }

            var committeelist = "";
            if(response.CMMTTLIST && response.CMMTTLIST.length > 0){
               $.each(response.CMMTTLIST, function(key, value){
                   committeelist += '<tr>'+
                                    '<td style="display:none;">'+escapeHtml(value.id)+'</td>'+
                                    '<td class="text-start" style="text-align: left !important;">'+escapeHtml(value.fullname)+'</td>'+
                                    '<td>'+escapeHtml(value.designation)+'</td>'+
                                    '<td style="white-space: normal !important; word-wrap: break-word; max-width: 150px;">'+escapeHtml(value.committeeType)+'</td>'+
                                    '<td>'+escapeHtml(value.specializedposition)+'</td>'+
                                    '<td>'+escapeHtml(value.fromdate.replace(/\//g, '-'))+'</td>'+
                                    '<td>'+escapeHtml(value.toDate.replace(/\//g, '-'))+'</td>'+
                                '<td>'+escapeHtml(value.dateEncoded.replace(/\//g, '-'))+'</td>'+
                              '</tr>';
               });
            }
            $("#committeeList").html(committeelist);

            committeeTbl = $('#committeeTbl').DataTable({
                "columnDefs": [{ "targets": 1, "className": "text-start" }],
                "pageLength": 10,
                "lengthMenu": [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                "searching": false,
                "ordering": false,
                "lengthChange": true, // Enabled
                "info": false, 
                "paging": true,
                "responsive": true,
                "language": {
                    "emptyTable": "No committee members found for this year",
                    "paginate": {
                        "previous": '<i class="fa-solid fa-chevron-left"></i>',
                        "next": '<i class="fa-solid fa-chevron-right"></i>'
                    }
                }
            });
        }
    })
}

$('#bodTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
    } else {
        if(bodTbl) bodTbl.rows('.selected').nodes().each((row) => {
            row.classList.remove('selected');
        });
        classList.add('selected');
    }

    var data = $('#bodTbl').DataTable().row(this).data();
    if(data){
        var id = data[0];
        $('#boardID').val(id);
        setInfo();
        getInfo(id)
    }
});

$('#committeeTbl tbody').on('click', 'tr',function(e){
    let classList = e.currentTarget.classList;
    if (classList.contains('selected')) {
        classList.remove('selected');
    } else {
        if(committeeTbl) committeeTbl.rows('.selected').nodes().each((row) => {
            row.classList.remove('selected');
        });
        classList.add('selected');
    }

    var data = $('#committeeTbl').DataTable().row(this).data();
    if(data){
        var id = data[0];
        $('#boardID').val(id);
        setInfo();
        getInfo(id)
    }
});

function setInfo(){
    $('#shareholderName').prop('disabled', true).val('');
    $('#BODdesignation').prop('disabled', true).val('');
    $('#committeeType').prop('disabled', true).val('');
    $('#specializedposition').prop('disabled', true).val('');
    $('#fromdate').prop('disabled', true).val('');
    $('#toDate').prop('disabled', true).val('');

    $('#editButton').show().prop('disabled', false);
    $('#addNew').show().prop('disabled', true);
    $('#cancel').prop('hidden', false).prop('disabled', false);
    $('#updateButton').show().prop('disabled', true);
    $('#submitButton').hide();
}

function getInfo(id){
    $.ajax({
        url: '../../routes/profiling/bod.route.php',
        method: 'POST',
        data: { action: "getBODInfo", id: id },
        dataType: 'JSON',
        success: function(response) {
            if(response.STATUS == "LOADED"){
                var INFO = response.INFO;
        
                $('#boardID').val(INFO.id);
                var fullname = INFO.fullname || (INFO.firstname + " " + (INFO.middlename ? INFO.middlename + " " : "") + INFO.lastname);
                $('#shareholderName').val(fullname);
                
                $('#BODdesignation').val(INFO.designation);
                
                if (INFO.designation && INFO.designation.trim().toUpperCase() === 'COMMITTEE') {
                     $('#committeeType').prop('disabled', false).val(INFO.committeeType);
                } else {
                     $('#committeeType').prop('disabled', true).val('');
                }

                $('#specializedposition').val(INFO.specializedposition);
                $('#fromdate').val(INFO.fromdate);
                $('#toDate').val(INFO.toDate);
            }
        },
        error: function(error) {
            console.error('Error fetching info:', error);
        }
    });
}

$('#addNew').on('click', function() {
    $('#bodForm').removeClass('was-validated'); // Reset validation
    $('#boardID').val("");
    $('#shareholderName').val("").prop('disabled', false);
    $('#BODdesignation').val("").prop('disabled', false);
    $('#committeeType').val("").prop('disabled', true);
    $('#specializedposition').val("").prop('disabled', false);
    $('#fromdate').val("").prop('disabled', false);
    $('#toDate').val("").prop('disabled', false);
    
    $('#addNew').show().prop('disabled', true);
    $('#cancel').prop('hidden', false);
    $('#cancel').prop('disabled', false);
    $('#submitButton').show();
    $('#submitButton').prop('disabled', false);
    
    $("#bodTbl tbody tr").removeClass("selected");
    $("#committeeTbl tbody tr").removeClass("selected");
});

$('#editButton').on('click', function() {
    $('#bodForm .is-invalid').removeClass('is-invalid'); // Reset error classes
    $('#bodForm .is-valid').removeClass('is-valid');     // Reset valid classes
    $('#bodForm').removeClass('was-validated');          // Just in case
    $('#shareholderName').prop('disabled', false);
    $('#BODdesignation').prop('disabled', false);
    
    var designation = $('#BODdesignation').val();
    if (designation && designation.trim().toUpperCase() === 'COMMITTEE') {
        $('#committeeType').prop('disabled', false);
    } else {
        $('#committeeType').prop('disabled', true);
    }

    $('#specializedposition').prop('disabled', false);
    $('#fromdate').prop('disabled', false);
    $('#toDate').prop('disabled', false);
    
    $('#cancel').prop('hidden', false);
    $('#cancel').prop('disabled', false);
    $('#submitButton').hide();
    $('#updateButton').show();
    $('#updateButton').prop('disabled', false);
});

function Cancel() {
    $('#bodForm .is-invalid').removeClass('is-invalid'); // Reset error classes
    $('#bodForm .is-valid').removeClass('is-valid');     // Reset valid classes (if any)
    $('#bodForm').removeClass('was-validated');          // Just in case
    $('#shareholderName').prop('disabled', true).val('');
    $('#BODdesignation').prop('disabled', true).val('');
    $('#committeeType').prop('disabled', true).val('');
    $('#specializedposition').prop('disabled', true).val('');
    $('#fromdate').prop('disabled', true).val('');
    $('#toDate').prop('disabled', true).val('');
    
    $('#cancel').prop('hidden', true).prop('disabled', true);
    $('#updateButton').hide();
    $('#submitButton').show().prop('disabled', true);
    $('#editButton').show().prop('disabled', true);
    $('#addNew').show().prop('disabled', false);
    
    $("#bodTbl tbody tr").removeClass("selected");
    $("#committeeTbl tbody tr").removeClass("selected");
}

// Remove validation error on proper input
$('#bodForm input, #bodForm select').on('input change', function() {
    if (this.checkValidity()) {
        $(this).removeClass('is-invalid'); // Remove red box
        $(this).removeClass('is-valid');   // Ensure no green check (though not used)
    }
});



$("#submitButton").on("click",function(e){
    e.preventDefault(); // Prevent default form submission
    var form = $('#bodForm')[0];
    
    // Manual Validation Check
    if (form.checkValidity() === false) {
        e.stopPropagation();
        
        // Apply is-invalid class manually & REMOVE is-valid
        $(form).find(':input').each(function() {
            $(this).removeClass('is-valid'); // Force remove green check
            if (!this.checkValidity()) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        UI.toast('warning', 'Please fill in all required fields.'); // Use Toast
        return;
    }
    // Clear any residual invalid classes
    $('#bodForm .is-invalid').removeClass('is-invalid');
    $('#bodForm .is-valid').removeClass('is-valid');

    var formData = new FormData(form);
    formData.append('action', 'SaveInfo');
    formData.append('csrf_token', CSRF_TOKEN);

    Swal.fire({
        title: 'Are you sure?',
        icon: 'question',
        text: 'Save New Board of Director?',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        confirmButtonColor: '#435ebe',  
        confirmButtonText: 'Yes, proceed!',
        preConfirm: function() {
            return $.ajax({
                url: "../../routes/profiling/bod.route.php",
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
                UI.toast('success', result.value.MESSAGE); // Success Toast
                LoadBODList("All"); // Refresh with All
                LoadCMMTTList("All");
                Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE);
            }
        }
    });
})

function escapeHtml(text) {
  if (text == null) return "";
  return text
      .toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}

// ==========================================
// REPORT MODAL HANDLERS - NEW
// ==========================================

// BOD Report Modal
// BOD Report Modal
$('#openBODReportModalBtn').on('click', function() {
    // Default to All
    $('#slctYearBODModal').val("All");
    
    // Reset selection
    $('#SelectBODReportMDL .report-option-card').removeClass('active');
    $('#SelectBODReportMDL').modal('show');
});

// Committee Report Modal
// Committee Report Modal
$('#openCommitteeReportModalBtn').on('click', function() {
    // Default to All
    $('#slctYearCmmttModal').val("All");

    // Reset selection
    $('#SelectCommitteeReportMDL .report-option-card').removeClass('active');
    $('#SelectCommitteeReportMDL').modal('show');
});

// Report Option Card Selection
$(document).on('click', '.report-option-card', function() {
    $(this).closest('.modal').find('.report-option-card').removeClass('active');
    $(this).addClass('active');
});

// Confirm BOD Report Generation
$('#confirmBODReportBtn').on('click', function() {
    var format = $('#SelectBODReportMDL .report-option-card.active').data('format');
    if (!format) {
        UI.toast('warning', 'Please select a report format (PDF or Excel).');
        return;
    }
    
    var year = $('#slctYearBODModal').val();
    GenerateBODReport(year, format);
    $('#SelectBODReportMDL').modal('hide');
});

// Confirm Committee Report Generation
$('#confirmCommitteeReportBtn').on('click', function() {
    var format = $('#SelectCommitteeReportMDL .report-option-card.active').data('format');
    if (!format) {
        UI.toast('warning', 'Please select a report format (PDF or Excel).');
        return;
    }
    
    var year = $('#slctYearCmmttModal').val();
    GenerateCommitteeReport(year, format);
    $('#SelectCommitteeReportMDL').modal('hide');
});

function GenerateBODReport(year, format) {
    $.ajax({
        url: "../../routes/profiling/bod.route.php",
        type: "POST",
        data: { action: "GenerateBODReport", Year: year, format: format },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS == "SUCCESS") {
                window.open('../../routes/profiling/bod.route.php?type=PrintBODReport&format=' + format, '_blank');
            } else {
                Swal.fire({ 
                    icon: "error", 
                    title: "Error", 
                    text: response.MESSAGE || "Failed to generate report." 
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error generating report:", error);
            Swal.fire({ 
                icon: "error", 
                title: "Error", 
                text: "Failed to generate report. Please try again." 
            });
        }
    });
}

function GenerateCommitteeReport(year, format) {
    $.ajax({
        url: "../../routes/profiling/bod.route.php",
        type: "POST",
        data: { action: "GenerateCommitteeReport", Year: year, format: format },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS == "SUCCESS") {
                window.open('../../routes/profiling/bod.route.php?type=PrintCommitteeReport&format=' + format, '_blank');
            } else {
                Swal.fire({ 
                    icon: "error", 
                    title: "Error", 
                    text: response.MESSAGE || "Failed to generate report." 
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error generating report:", error);
            Swal.fire({ 
                icon: "error", 
                title: "Error", 
                text: "Failed to generate report. Please try again." 
            });
        }
    });
}

$("#updateButton").on("click",function(e){
    e.preventDefault(); // Stop Reload
    var form = $('#bodForm')[0];
    
    // Manual Validation Check
    if (form.checkValidity() === false) {
        e.stopPropagation();
        
        // Apply is-invalid class manually & REMOVE is-valid
        $(form).find(':input').each(function() {
            $(this).removeClass('is-valid'); // Force remove green check
            if (!this.checkValidity()) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        UI.toast('warning', 'Please fill in all required fields.'); // Use Toast
        return;
    }
    // Clear any residual invalid classes
    $('#bodForm .is-invalid').removeClass('is-invalid');
    $('#bodForm .is-valid').removeClass('is-valid');

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
        preConfirm: function() {
            return $.ajax({
                url: "../../routes/profiling/bod.route.php",
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
                UI.toast('success', result.value.MESSAGE); // Success Toast
                LoadBODList("All");
                LoadCMMTTList("All");
                Cancel();
            } else {
                UI.toast('error', result.value.MESSAGE);
            }
        }
    });
})