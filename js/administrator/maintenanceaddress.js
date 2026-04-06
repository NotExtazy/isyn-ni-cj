var AddressTbl;

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

// 1. Initialize
$(document).ready(function() {
    // --- PAGINATION SETTING ---
    $.fn.DataTable.ext.pager.numbers_length = 7;

    LoadAddressList();
    
    // Custom Search Bar
    $('#addressSearch').on('keyup', function() {
        AddressTbl.search(this.value).draw();
    });
});

// 2. Load Table
function LoadAddressList(){
    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { action: "LoadAddressList" },
        dataType: "JSON",
        success: function(response){
            if ($.fn.DataTable.isDataTable('#AddressTbl')) {
                $('#AddressTbl').DataTable().destroy();
            }

            AddressTbl = $('#AddressTbl').DataTable({
                data: response.LIST, 
                columns: [
                    { data: 'Region', className: 'text-center' },
                    { data: 'Province', className: 'text-center' },
                    { data: 'CityTown', className: 'text-center' },
                    { data: 'Barangay', className: 'text-center' },
                    { 
                        data: 'status',
                        className: 'text-center',
                        render: function(data) {
                            if(data == 1) {
                                return '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fa fa-check-circle me-1"></i>Active</span>';
                            } else {
                                return '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa fa-times-circle me-1"></i>Inactive</span>';
                            }
                        }
                    },
                    {
                        data: null,
                        className: 'text-center',
                        render: function(data, type, row) {
                            var isActive = row.status == 1;
                            var checkedAttr = isActive ? 'checked="checked"' : '';
                            
                            return `<div class="bauble_box" onclick="event.stopPropagation();">
                                        <input class="bauble_input" id="bauble_check_${row.id_barangay}" type="checkbox" ${checkedAttr} onchange="ToggleStatus(${row.id_barangay}, ${row.status})">
                                        <label class="bauble_label" for="bauble_check_${row.id_barangay}">Toggle</label>
                                    </div>`;
                        }
                    }
                ],
                pageLength: 10,
                ordering: false,
                // lengthChange: true, // Default is true, so removing false enables it.
                searching: true,
                info: false,
                deferRender: true, 
                responsive: false,
                autoWidth: false,
                scrollX: false,
                language: { 
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                }
            });
        }
    });
}

// 3. REUSABLE FUNCTIONS TO FETCH DROPDOWNS
// These are separated so we can call them from "Change" events AND "Edit" events

function FetchProvinces(region, selectedValue = null) {
    return $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { action: "GetProvinces", filter: region },
        dataType: "JSON",
        success: function(response) {
            $('#Province').empty().append('<option value="" selected disabled>Select Province</option>');
            $.each(response.LIST, function(key, item) {
                $('#Province').append('<option value="'+item.Province+'">'+item.Province+'</option>');
            });
            $('#Province').prop('disabled', false);

            // If we have a value to set (during Edit), set it now
            if (selectedValue) {
                $('#Province').val(selectedValue);
            }
        }
    });
}

// 2. Toggle Status Function (No confirmation, direct action with 3D switch animation)
// 2. Toggle Status Function (In-place update with animation)
function ToggleStatus(id, currentStatus) {
    let newStatus = currentStatus == 1 ? 0 : 1;
    let actionText = newStatus == 1 ? "activated" : "deactivated";

    // Find the row to animate
    let $row = AddressTbl.rows().nodes().to$().filter(function() {
        return $(this).find('input[onchange*="ToggleStatus(' + id + ',"]').length > 0;
    });

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { action: "UpdateStatus", id: id, status: newStatus },
        dataType: "JSON",
        success: function(response) {
            if (response.STATUS === 'success') {
                UI.toast('success', `Address ${actionText} successfully`);
                
                if ($row.length > 0) {
                    // 1. Update Data (Silent Update for DataTables sorting/filtering)
                    // We directly update the data object so subsequent draws use the correct status
                    var rowData = AddressTbl.row($row).data();
                    rowData.status = newStatus;
                    // We do NOT call draw() to avoid resetting the DOM/Animation
                    
                    // 2. Update Badge UI (Animation)
                    var $statusCell = $row.find('td:eq(4)'); // 5th column is Status
                    var newBadge = (newStatus == 1) 
                        ? '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fa fa-check-circle me-1"></i>Active</span>'
                        : '<span class="badge bg-danger rounded-pill px-3 py-2"><i class="fa fa-times-circle me-1"></i>Inactive</span>';
                        
                    $statusCell.fadeOut(200, function() {
                        $(this).html(newBadge).fadeIn(200);
                    });
                    
                    // 3. Update Checkbox Attribute
                    // Update the onchange to reflect the NEW status (so next click toggles back)
                    var $checkbox = $row.find('input[type="checkbox"]');
                    $checkbox.attr('onchange', `ToggleStatus(${id}, ${newStatus})`);
                }
            } else {
                UI.toast('error', response.MESSAGE || 'Failed to update status');
                // Revert checkbox if failed
                if ($row.length > 0) {
                    $row.find('input[type="checkbox"]').prop('checked', currentStatus == 1);
                }
            }
        },
        error: function() {
            UI.toast('error', 'An error occurred while updating status');
            // Revert checkbox if failed
            if ($row.length > 0) {
                $row.find('input[type="checkbox"]').prop('checked', currentStatus == 1);
            }
        }
    });
}

function FetchCities(province, selectedValue = null) {
    return $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: { action: "GetCities", filter: province },
        dataType: "JSON",
        success: function(response) {
            $('#CityTown').empty().append('<option value="" selected disabled>Select City/Town</option>');
            $.each(response.LIST, function(key, item) {
                $('#CityTown').append('<option value="'+item.CityTown+'">'+item.CityTown+'</option>');
            });
            $('#CityTown').prop('disabled', false);

            // If we have a value to set (during Edit), set it now
            if (selectedValue) {
                $('#CityTown').val(selectedValue);
            }
        }
    });
}


// 4. DROPDOWN CHANGE EVENTS (For Manual Selection)
$('#Region').on('change', function() {
    var region = $(this).val();
    $('#Province').empty().append('<option value="" selected disabled>Loading...</option>');
    $('#CityTown').empty().append('<option value="" selected disabled>Select Province First</option>');
    
    FetchProvinces(region);
});

$('#Province').on('change', function() {
    var province = $(this).val();
    $('#CityTown').empty().append('<option value="" selected disabled>Loading...</option>');
    
    FetchCities(province);
});


// 5. EDIT LOGIC (The most important fix)
$('#AddressTbl tbody').on('click', 'tr', function() {
    if ($(this).hasClass('selected')) {
        $(this).removeClass('selected');
        Cancel();
    } else {
        AddressTbl.$('tr.selected').removeClass('selected');
        $(this).addClass('selected');

        var data = AddressTbl.row(this).data();
        var id = data.id_barangay; 

        $('#editButton').prop('disabled', false);
        $('#addNew').prop('disabled', true);
        $('#cancel').prop('hidden', false).prop('disabled', false);
        
        // Fetch Record Details
        $.ajax({
            url: "../../routes/administrator/maintenanceaddress.route.php",
            type: "POST",
            data: { action: "GetAddressInfo", id_barangay: id },
            dataType: "JSON",
            success: function(response){
                if(response.STATUS === "LOADED"){
                    var INFO = response.INFO;

                    // 1. Set ID and Barangay (Simple text inputs)
                    $('#id_barangay').val(INFO.id_barangay);
                    $('#Barangay').val(INFO.Barangay);

                    // 2. Set Region (It's already loaded in HTML)
                    $('#Region').val(INFO.Region);

                    // 3. TRIGGER THE CASCADE for Province -> Then City
                    // We use the Promise returned by FetchProvinces to wait for it to finish
                    // before trying to fetch Cities.
                    
                    FetchProvinces(INFO.Region, INFO.Province).then(function() {
                        // After Provinces are loaded and set, fetch Cities
                        FetchCities(INFO.Province, INFO.CityTown).then(function() {
                            // Finally, lock fields since we are in "View/Selected" mode
                             $('.form-control, .form-select').prop('disabled', true);
                        });
                    });

                    $('#updateButton').hide();
                    $('#submitButton').hide();
                }
            }
        });
    }
});

// 6. Buttons Logic
$('#addNew').on('click', function() {
    $('#addressForm')[0].reset();
    $('#id_barangay').val('');
    
    // Reset Dropdowns to default
    $('#Province').empty().append('<option value="" selected disabled>Select Region First</option>');
    $('#CityTown').empty().append('<option value="" selected disabled>Select Province First</option>');

    $('.form-control, .form-select').prop('disabled', false);
    
    $('#addNew').prop('disabled', true);
    $('#editButton').prop('disabled', true);
    $('#submitButton').show().prop('disabled', false);
    $('#updateButton').hide();
    $('#cancel').prop('hidden', false).prop('disabled', false);
    
    AddressTbl.$('tr.selected').removeClass('selected');
});

$('#editButton').on('click', function() {
    $('.form-control, .form-select').prop('disabled', false);
    $('#editButton').hide();
    $('#updateButton').show().prop('disabled', false);
    $('#submitButton').hide();
});

// 7. Submit (No confirmation, direct action)
$('#submitButton').on('click', function() {
    var form = $('#addressForm')[0];
    if (form.checkValidity() === false) {
        form.classList.add('was-validated');
        return;
    }

    var formData = new FormData(form);
    formData.append('action', 'SaveInfo');

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "JSON",
        success: function(response){
            if(response.STATUS === 'success'){
                UI.toast('success', response.MESSAGE || 'Address added successfully');
                LoadAddressList();
                Cancel();
            } else {
                UI.toast('error', response.MESSAGE || 'Failed to add address');
            }
        },
        error: function() {
            UI.toast('error', 'An error occurred while adding address');
        }
    });
});

// 8. Update (No confirmation, direct action)
$('#updateButton').on('click', function() {
    var form = $('#addressForm')[0];
    var formData = new FormData(form);
    formData.append('action', 'UpdateInfo');

    $.ajax({
        url: "../../routes/administrator/maintenanceaddress.route.php",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "JSON",
        success: function(response){
            if(response.STATUS === 'success'){
                UI.toast('success', response.MESSAGE || 'Address updated successfully');
                LoadAddressList();
                Cancel();
            } else {
                UI.toast('error', response.MESSAGE || 'Failed to update address');
            }
        },
        error: function() {
            UI.toast('error', 'An error occurred while updating address');
        }
    });
});

function Cancel(){
    $('#addressForm')[0].reset();
    $('#id_barangay').val('');
    
    $('#Province').empty().append('<option value="" selected disabled>Select Region First</option>');
    $('#CityTown').empty().append('<option value="" selected disabled>Select Province First</option>');

    $('.form-control, .form-select').prop('disabled', true);
    
    $('#addNew').prop('disabled', false).show();
    $('#editButton').prop('disabled', true).show();
    $('#submitButton').hide();
    $('#updateButton').hide();
    $('#cancel').prop('hidden', true);
    
    AddressTbl.$('tr.selected').removeClass('selected');
    $('#addressForm').removeClass('was-validated');
}