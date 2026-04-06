var logTbl;

$(document).ready(function(){
    // 1. Load Dropdown Options (Modules & Users)
    LoadFilterOptions();

    // 2. Load Logs (Defaults to Today)
    LoadLogs();

    // 3. Button Events
    $("#btnFilter").on("click", function(){
        LoadLogs();
    });

    $("#btnRefresh").on("click", function(){
        // Reset filters to today
        var today = new Date().toISOString().split('T')[0];
        $('#filterStartDate').val(today);
        $('#filterEndDate').val(today);
        $('#filterModule').val('');
        $('#filterUser').val('');
        $('#filterAction').val('');
        LoadLogs();
    });
});

// ==========================================
//  LOAD LOGS FROM DB
// ==========================================
function LoadLogs(){
    var startDate = $('#filterStartDate').val();
    var endDate = $('#filterEndDate').val();
    var module = $('#filterModule').val();
    var user = $('#filterUser').val();
    var action = $('#filterAction').val();

    $.ajax({
        url: "../../routes/administrator/userlogs.route.php",
        type: "POST",
        data: {
            action: "LoadLogs",
            startDate: startDate,
            endDate: endDate,
            moduleId: module,
            userId: user,
            logAction: action
        },
        dataType: "JSON",
        beforeSend: function(){
            if ($.fn.DataTable.isDataTable('#logTable')) {
                $('#logTable').DataTable().clear().destroy();
            }
            $("#logList").html('<tr><td colspan="6" class="text-center">Loading logs...</td></tr>');
        },
        success: function(response){
            $("#logList").empty();
            if(response.LIST && response.LIST.length > 0){
                $.each(response.LIST, function(key, value){
                    
                    // --- UPDATED BADGE LOGIC (Soft Styles) ---
                    let badgeClass = 'badge-soft badge-default'; 
                    
                    if(value.action == 'INSERT')   badgeClass = 'badge-soft badge-insert';
                    if(value.action == 'UPDATE')   badgeClass = 'badge-soft badge-update';
                    if(value.action == 'DELETE')   badgeClass = 'badge-soft badge-delete';
                    if(value.action == 'LOGIN')    badgeClass = 'badge-soft badge-login';
                    
                    if(value.action == 'EXPORT' || value.action == 'GENERATE' || value.action == 'DOWNLOAD') {
                        badgeClass = 'badge-soft badge-export';
                    }

                    $("#logList").append(`
                        <tr>
                            <td style="white-space:nowrap;">${value.timestamp}</td>
                            <td>${value.username}</td>
                            <td>${value.module_name}</td>
                            <td><span class="badge ${badgeClass}">${value.action}</span></td>
                            <td class="text-start">${value.description}</td>
                            <td>${value.ip_address}</td>
                        </tr>
                    `);
                });
            }

            // Initialize DataTable
            logTbl = $('#logTable').DataTable({
                "data": null, 
                "destroy": true, 
                "pageLength": 10,
                "searching": true,
                "ordering": true,
                "order": [[0, 'desc']], 
                "lengthChange": true,
                "info": false, // Hide info as previous edit suggested
                "responsive": true,
                "dom": 't<"d-flex w-100 justify-content-end align-items-center px-4 py-3"p>', // Align pagination to END (Right)
                "language": { 
                    emptyTable: "No logs found for the selected criteria.",
                    paginate: {
                        previous: '<i class="fa-solid fa-chevron-left"></i>',
                        next: '<i class="fa-solid fa-chevron-right"></i>'
                    }
                },
                "columnDefs": [
                    { targets: '_all', className: 'text-center align-middle' },
                    { targets: 4, className: 'text-start text-wrap text-break', width: '38%' },
                    { targets: [1, 2, 3, 4, 5], orderable: false } // Disable sorting for all except Timestamp (0)
                ]
            });

            // Bind Custom Controls
            $('#customSearch').on('keyup', function() {
                logTbl.search(this.value).draw();
            });

            $('#customLength').on('change', function() {
                logTbl.page.len(this.value).draw();
            });
        },
        error: function(e){
            console.error("Error loading logs:", e);
            $("#logList").html('<tr><td colspan="6" class="text-danger text-center">Failed to load logs.</td></tr>');
        }
    });
}

// ==========================================
//  LOAD FILTER OPTIONS
// ==========================================
function LoadFilterOptions(){
    $.ajax({
        url: "../../routes/administrator/userlogs.route.php",
        type: "POST",
        data: { action: "GetFilterOptions" },
        dataType: "JSON",
        success: function(response){
            // Populate Module Dropdown
            if(response.MODULES){
                $.each(response.MODULES, function(key, val){
                    $('#filterModule').append(`<option value="${val.id_module}">${val.module}</option>`);
                });
            }
            // Populate User Dropdown
            if(response.USERS){
                $.each(response.USERS, function(key, val){
                    $('#filterUser').append(`<option value="${val.ID}">${val.Username}</option>`);
                });
            }
        }
    });
}