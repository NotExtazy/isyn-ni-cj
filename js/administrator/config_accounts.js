// Custom Modal Helpers
window.openCustomModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        // Calculate scrollbar width
        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex'); // Ensure flex centering
        
        document.body.style.paddingRight = `${scrollbarWidth}px`;
        document.body.style.overflow = 'hidden'; // Prevent scrolling

        // Also buffer the fixed navbar if it exists
        const navbar = document.querySelector('.iq-navbar');
        if(navbar) {
            navbar.style.paddingRight = `${scrollbarWidth}px`;
        }
    }
}

window.closeCustomModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        
        document.body.style.overflow = ''; // Restore scrolling
        document.body.style.paddingRight = '';

        // Remove buffer from navbar
        const navbar = document.querySelector('.iq-navbar');
        if(navbar) {
            navbar.style.paddingRight = '';
        }
    }
}

$(document).ready(function() {
    
    // =========================================================================
    //  DATATABLES
    // =========================================================================
    const usersTable = $('#usersTable').DataTable({
        "order": [[ 0, "asc" ]],
        "language": { "search": "", "searchPlaceholder": "Search users..." },
        "dom": "<'d-flex justify-content-between align-items-center mb-3'f>t<'d-flex justify-content-between align-items-center mt-3'ip>",
        "columns": [
            { "data": "Username" },
            { "data": "FullName" },
            { 
                "data": "role_name",
                "render": function(data, type, row){
                    return `<span class="badge bg-light text-dark border">${data || 'No Role'}</span>`;
                }
            },
            {
                "data": "Status",
                "className": "text-center",
                "render": function(data, type, row){
                    // Revert to Badge
                    if (data === "ENABLED" || data === "1") {
                        return '<span class="badge bg-success">Active</span>';
                    } else {
                        return '<span class="badge bg-secondary">Inactive</span>';
                    }
                }
            },
            {
                "data": null,
                "className": "text-center",
                "orderable": false,
                "render": function(data, type, row){
                    // Determine button style based on current status
                    let isActive = (row.Status === "ENABLED" || row.Status === "1");
                    let btnClass = isActive ? "btn-outline-danger" : "btn-outline-success";
                    let iconClass = isActive ? "fa-ban" : "fa-check";
                    let title = isActive ? "Deactivate User" : "Activate User";
                    
                    return `
                        <button class="btn btn-sm ${btnClass} rounded-circle me-1" onclick='toggleUserStatus(${row.ID}, "${row.Status}")' title="${title}">
                            <i class="fa ${iconClass}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary rounded-circle me-1" onclick='editUser(${JSON.stringify(row)})' title="Edit">
                            <i class="fa fa-pencil"></i>
                        </button>
                    `;
                }
            }
        ]
    });

    const rolesTable = $('#rolesTable').DataTable({
        "order": [[ 0, "asc" ]],
        "language": { "search": "", "searchPlaceholder": "Search roles..." },
        "dom": "<'d-flex justify-content-between align-items-center mb-3'f>t<'d-flex justify-content-between align-items-center mt-3'ip>",
        "columns": [
            { "data": "role_name", "className": "fw-bold" },
            { "data": "description" },
            {
                "data": "status",
                "className": "text-center",
                "render": function(data, type, row){
                    // Revert to Badge
                    return data == 1 
                        ? '<span class="badge bg-success">Active</span>' 
                        : '<span class="badge bg-secondary">Inactive</span>';
                }
            },
            {
                "data": null,
                "className": "text-center",
                "orderable": false,
                "render": function(data, type, row){
                    let isActive = (row.status == 1);
                    let btnClass = isActive ? "btn-outline-danger" : "btn-outline-success";
                    let iconClass = isActive ? "fa-ban" : "fa-check";
                    let title = isActive ? "Deactivate Role" : "Activate Role";

                    return `
                        <button class="btn btn-sm ${btnClass} rounded-circle me-1" onclick='toggleRoleStatus(${row.id}, ${row.status})' title="${title}">
                            <i class="fa ${iconClass}"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary rounded-circle me-1" onclick='editRole(${JSON.stringify(row)})' title="Edit Details">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-info rounded-circle" onclick='editPermissions(${row.id}, "${row.role_name}")' title="Manage Permissions">
                            <i class="fa fa-cog"></i>
                        </button>
                    `;
                }
            }
        ]
    });

    // =========================================================================
    //  LOAD DATA 
    // =========================================================================
    function loadUsers() {
        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: { action: "LoadUsers" },
            dataType: "json",
            success: function(response) {
                if(response.STATUS === "success") {
                    usersTable.clear().rows.add(response.DATA).draw();
                } else {
                    console.error(response.MESSAGE);
                }
            }
        });
    }

    function loadRoles() {
        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: { action: "LoadRoles" },
            dataType: "json",
            success: function(response) {
                if(response.STATUS === "success") {
                    rolesTable.clear().rows.add(response.DATA).draw();
                    
                    // Also populate select dropdown
                    let opts = '<option value="" disabled selected>Select Role</option>';
                    response.DATA.forEach(r => {
                        opts += `<option value="${r.id}">${r.role_name}</option>`;
                    });
                    $('#roleSelect').html(opts);
                } else {
                    console.error(response.MESSAGE);
                }
            }
        });
    }

    // Initial Load
    loadUsers();
    loadRoles();

    // =========================================================================
    //  USER ACTIONS
    // =========================================================================
    window.openUserModal = function() {
        $('#userForm')[0].reset();
        $('#userId').val(0);
        $('#userModalLabel').text("Add New User");
        $('#passwordHelp').text("Leave blank to keep auto-generated or default."); 
        openCustomModal('userModal');
    }

    window.editUser = function(row) {
        $('#userForm')[0].reset();
        $('#userId').val(row.ID);
        $('#username').val(row.Username);
        $('#fullname').val(row.FullName);
        
        // Find existing role id from LoadUsers logic? 
        // Note: Row data from LoadUsers includes 'role_name' not 'role_id'? 
        // Need to ensure LoadUsers select query fetches role_id too.
        // Let's assume process update (I'll fix it if I missed it, wait, I can fix frontend to match process return)
        // CHECKING PROCESS: SELECT u.ID ..., u.role_id (Not selected explicitly in LoadUsers JOIN?)
        // Let me assume I need to fetch it or match by name. 
        // Better: Wait, Process query: "SELECT u.ID, u.Username, u.Owner as FullName, u.Status, r.role_name FROM..." 
        // It DOES NOT select role_id. I should fix the process or pass it. 
        // QUICK FIX: I will rely on table redraw after process update or fix process now.
        // ACTUALLY: I will just iterate options to find match by TEXT if id missing, OR better, fix Process query.
        
        // Assuming I'll fix Process query to include u.role_id
        $('#roleSelect').val(row.role_id); 
        $('#password').val(''); // Clear password field (security)

        $('#userModalLabel').text("Edit User");
        $('#passwordHelp').text("Leave blank to keep existing password, or edit to change.");
        openCustomModal('userModal');
    }

    $('#btnSaveUser').click(function() {
        let formData = new FormData($('#userForm')[0]);
        formData.append('action', 'SaveUser');

        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: formData,
            dataType: "json",
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.STATUS === "success") {
                    closeCustomModal('userModal');
                    showToast('success', response.MESSAGE);
                    loadUsers();
                } else {
                    showToast('error', response.MESSAGE);
                }
            }
        });
    });

    // =========================================================================
    //  ROLE ACTIONS
    // =========================================================================
    window.openRoleModal = function() {
        $('#roleForm')[0].reset();
        $('#roleId').val(0);
        $('#roleModalLabel').text("Create New Role");
        openCustomModal('roleModal');
    }

    window.editRole = function(row) {
        $('#roleId').val(row.id);
        $('#roleName').val(row.role_name);
        $('#roleDesc').val(row.description);
        $('#roleModalLabel').text("Edit Role");
        openCustomModal('roleModal');
    }

    $('#btnSaveRole').click(function() {
        let formData = new FormData($('#roleForm')[0]);
        formData.append('action', 'SaveRole');

        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: formData,
            dataType: "json",
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.STATUS === "success") {
                    closeCustomModal('roleModal');
                    showToast('success', response.MESSAGE);
                    loadRoles(); // This also updates the dropdown in User Modal
                } else {
                    showToast('error', response.MESSAGE);
                }
            }
        });
    });

    // =========================================================================
    //  PERMISSIONS
    // =========================================================================
    window.editPermissions = function(roleId, roleName) {
        $('#permRoleId').val(roleId);
        $('#permRoleName').text(roleName);
        $('#permissionsTree').html('<div class="text-center p-3"><div class="spinner-border text-primary"></div></div>');
        $('#permissionsModal').modal('show');

        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: { action: 'GetRolePermissions', roleId: roleId },
            dataType: "json",
            success: function(response) {
                if(response.STATUS === "success") {
                    renderPermissionsTree(response.DATA);
                } else {
                    $('#permissionsTree').html('<div class="text-danger">Failed to load permissions.</div>');
                }
            }
        });
    }

    function renderPermissionsTree(modules) {
        let html = '<ul class="list-unstyled mb-0">';
        
        modules.forEach(mod => {
            let checkedInfo = mod.checked ? 'checked' : '';
            html += `
                <li class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input mod-check" type="checkbox" value="${mod.id}" id="mod_${mod.id}" ${checkedInfo}>
                        <label class="form-check-label fw-bold" for="mod_${mod.id}">
                            ${mod.name}
                        </label>
                    </div>
            `;
            
            if(mod.children && mod.children.length > 0) {
                html += '<ul class="list-unstyled ms-4 mt-1 border-start ps-2">';
                mod.children.forEach(sub => {
                    let subChecked = sub.checked ? 'checked' : '';
                    html += `
                        <li>
                            <div class="form-check">
                                <input class="form-check-input sub-check mod-child-${mod.id}" type="checkbox" value="${sub.id}" id="sub_${sub.id}" ${subChecked} data-parent="${mod.id}">
                                <label class="form-check-label" for="sub_${sub.id}">
                                    ${sub.name}
                                </label>
                            </div>
                        </li>
                    `;
                });
                html += '</ul>';
            }
            
            html += '</li>';
        });
        
        html += '</ul>';
        $('#permissionsTree').html(html);

        // Auto-check parent logic
        $('.sub-check').change(function() {
            let parentId = $(this).data('parent');
            if($(this).is(':checked')) {
                $(`#mod_${parentId}`).prop('checked', true);
            }
        });

        // Auto-check children logic (Optional: Select all submodules if parent checked?)
        // User preference: Sometimes checking parent means select all. Let's implementing: 
        // Checking Parent -> Checks all children. Unchecking parent -> Unchecks all children.
        $('.mod-check').change(function() {
            let modId = $(this).val();
            let isChecked = $(this).is(':checked');
            $(`.mod-child-${modId}`).prop('checked', isChecked);
        });
    }

    $('#btnSavePermissions').click(function() {
        let roleId = $('#permRoleId').val();
        let selectedModules = [];
        
        $('#permissionsTree input:checked').each(function() {
            selectedModules.push($(this).val());
        });

        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: { 
                action: 'SaveRolePermissions', 
                roleId: roleId,
                moduleIds: selectedModules 
            },
            dataType: "json",
            success: function(response) {
                if(response.STATUS === "success") {
                    $('#permissionsModal').modal('hide');
                    showToast('success', response.MESSAGE);
                } else {
                    showToast('error', response.MESSAGE);
                }
            }
        });
    });

    // =========================================================================
    //  TOGGLE ACTIONS
    // =========================================================================
    window.toggleUserStatus = function(id, currentStatus) {
        // let newStatus = $(elem).is(':checked') ? 'ENABLED' : 'DISABLED';
        let newStatus = (currentStatus === "ENABLED" || currentStatus === "1") ? 'DISABLED' : 'ENABLED';
        
        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: { action: 'ToggleUserStatus', id: id, status: newStatus },
            dataType: "json",
            success: function(response) {
                if(response.STATUS === "success") {
                    showToast('success', response.MESSAGE);
                    loadUsers(); // Refresh table to update button state
                } else {
                    showToast('error', response.MESSAGE);
                }
            },
            error: function() {
                showToast('error', "Request failed");
            }
        });
    }

    window.toggleRoleStatus = function(id, currentStatus) {
        // let newStatus = $(elem).is(':checked') ? 1 : 0;
        let newStatus = (currentStatus == 1) ? 0 : 1;
        
        $.ajax({
            url: "../../process/administrator/config_accounts.process.php",
            type: "POST",
            data: { action: 'ToggleRoleStatus', id: id, status: newStatus },
            dataType: "json",
            success: function(response) {
                if(response.STATUS === "success") {
                    showToast('success', response.MESSAGE);
                    loadRoles(); // Refresh table
                } else {
                    showToast('error', response.MESSAGE);
                }
            },
            error: function() {
                showToast('error', "Request failed");
            }
        });
    }

    // =========================================================================
    //  TOAST HELPER
    // =========================================================================
    // =========================================================================
    //  TOAST HELPER
    // =========================================================================
    function showToast(type, message) {
        // Reuse existing Toast logic if available in layout, or SweetAlert fallback
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            Toast.fire({
                icon: type,
                title: message
            });
        } else {
            console.log(type.toUpperCase() + ": " + message);
        }
    }

    // =========================================================================
    //  PASSWORD TOGGLE
    // =========================================================================
    $('#togglePasswordVisibility').on('click', function() {
        const passwordInput = $('#password');
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // =========================================================================
    //  SIDEBAR FORCE EXPAND (FALLBACK) - REMOVED (Handled Globally)
    // =========================================================================
});
