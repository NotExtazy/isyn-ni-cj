<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
        // Enforce RBAC
        require_once('../../includes/permissions.php');
        $permissions = new Permissions();
        
        $_SESSION['parent_module'] = 'Administrator';
        $_SESSION['current_module'] = 'Config Accounts';
        
        // Dynamic check based on current URL
        if (!$permissions->checkAccessByUrl($_SERVER['PHP_SELF'])) {
            header("Location: ../../dashboard");
            exit;
        }
?>
<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('../../includes/pages.header.php');
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <title>iSyn | Config Accounts</title>
    <body class="  ">
        <div id="loading">
            <div class="loader simple-loader">
                <div class="loader-body"></div>
            </div>
        </div>

        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>

        <style>
            .nav-tabs .nav-link {
                color: #6c757d;
                font-weight: 600;
                border: none;
                border-bottom: 2px solid transparent;
                padding-bottom: 10px;
                transition: color 0.3s, border-color 0.3s;
            }
            .nav-tabs .nav-link.active {
                color: #0d6efd;
                border-bottom: 2px solid #0d6efd;
                background-color: transparent;
            }
            .premium-card {
                background: white;
                border: none;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                transition: transform 0.2s;
            }
            .table-premium thead th {
                background-color: #f8f9fa;
                color: #6c757d;
                font-weight: 700;
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.5px;
                border-bottom: 1px solid #e9ecef;
            }
        </style>

        <div class="container-fluid content-inner pb-0">
            <div class="row">
                <div class="col-sm-12">
                    <div class="premium-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="fw-bold text-primary mb-1">Config Accounts</h4>
                                <p class="text-muted mb-0 small">Manage System Users and Role-Based Access Control</p>
                            </div>
                        </div>

                        <!-- TABS -->
                        <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users-panel" type="button" role="tab">User Management</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="roles-tab" data-bs-toggle="tab" data-bs-target="#roles-panel" type="button" role="tab">Role Management</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="configTabContent">
                            
                            <!-- USERS PANEL -->
                            <div class="tab-pane fade show active" id="users-panel" role="tabpanel">
                                <div class="d-flex justify-content-end mb-3">
                                    <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openUserModal()">
                                        <i class="fa fa-plus me-1"></i> Add User
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table id="usersTable" class="table table-premium table-hover align-middle w-100">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Role</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ROLES PANEL -->
                            <div class="tab-pane fade" id="roles-panel" role="tabpanel">
                                <div class="d-flex justify-content-end mb-3">
                                    <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="openRoleModal()">
                                        <i class="fa fa-plus me-1"></i> Add Role
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table id="rolesTable" class="table table-premium table-hover align-middle w-100">
                                        <thead>
                                            <tr>
                                                <th>Role Name</th>
                                                <th>Description</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data via AJAX -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Custom Modal Design Tokens */
            :root {
                --color-brand: #3a57e8;
                --color-brand-medium: #c7d2fe; /* Light purple/blue focus ring */
                --color-brand-strong: #2c42b5;
                --color-heading: #111827;
                --color-body: #6b7280;
                --bg-soft: #ffffff;
                --bg-medium: #f9fafb;
                --border-default: #e5e7eb;
                --rounded-base: 0.5rem;
            }

            /* Utility Classes to match User Snippet */
            .bg-brand { background-color: var(--color-brand) !important; }
            .bg-brand-strong { background-color: var(--color-brand-strong) !important; }
            .bg-neutral-primary-soft { background-color: var(--bg-soft); }
            .bg-neutral-secondary-medium { background-color: var(--bg-medium); }
            .hover\:bg-brand-strong:hover { background-color: var(--color-brand-strong) !important; }
            .hover\:bg-neutral-tertiary:hover { background-color: #f3f4f6; }
            
            .text-heading { color: var(--color-heading); }
            .text-body { color: var(--color-body); }
            
            .border-default { border-color: var(--border-default) !important; }
            .border-default-medium { border-color: var(--border-default) !important; }
            
            .rounded-base { border-radius: var(--rounded-base) !important; }
            
            .focus\:ring-brand-medium:focus { 
                box-shadow: 0 0 0 4px var(--color-brand-medium); 
                border-color: var(--color-brand);
            }
            
            .box-border { box-sizing: border-box; }
            .cursor-pointer { cursor: pointer; }
            
            
            /* Custom Modal Layout */
            .custom-modal-backdrop {
                background-color: rgba(0, 0, 0, 0.5);
                display: block !important; /* Override flex */
                padding-bottom: 60px; /* Bottom spacing for scrolling */
            }
            
            .custom-modal-backdrop > div {
                margin-left: auto;
                margin-right: auto;
            }

            /* Helper for hidden state */
            .hidden { display: none !important; }
            
            .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); display: grid; }
            .col-span-2 { grid-column: span 2 / span 2; }
            .sm\:col-span-1 { grid-column: span 1 / span 1; }
            .gap-4 { gap: 1rem; }

            /* Tailwind Emulation for Layout */
            .fixed { position: fixed !important; }
            .relative { position: relative !important; }
            .top-0 { top: 0 !important; }
            .right-0 { right: 0 !important; }
            .bottom-0 { bottom: 0 !important; }
            .left-0 { left: 0 !important; }
            .z-50 { z-index: 50 !important; }
            .w-full { width: 100% !important; }
            .max-w-md { max-width: 28rem !important; }
            .max-w-2xl { max-width: 42rem !important; }
            .h-full { height: 100% !important; }
            .max-h-full { max-height: 100% !important; }
            .flex { display: flex !important; }
            .items-center { align-items: center !important; }
            .justify-center { justify-content: center !important; }
            .justify-between { justify-content: space-between !important; }
            .overflow-y-auto { overflow-y: auto !important; }
            .overflow-x-hidden { overflow-x: hidden !important; }
            .space-x-4 > :not([hidden]) ~ :not([hidden]) { --tw-space-x-reverse: 0; margin-right: calc(1rem * var(--tw-space-x-reverse)); margin-left: calc(1rem * (1 - var(--tw-space-x-reverse))); }
            
            /* Basic resets for Tailwind-like behavior */
            .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; }
            .shadow-xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; }
        </style>

        <!-- ==================== USER MODAL (NEW DESIGN) ==================== -->
        <div id="userModal" tabindex="-1" aria-hidden="true" class="custom-modal-backdrop hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full" style="z-index: 1055;">
            <div class="relative p-3 w-full max-w-md max-h-full" style="margin-top: 80px;">
                <!-- Modal content -->
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-3 bg-white">
                    <!-- Modal header -->
                    <div class="flex items-center justify-content-between border-b border-default pb-3 mb-3">
                        <h3 class="text-lg font-medium text-heading fw-bold fs-5" id="userModalLabel">
                            Add New User
                        </h3>
                        <button type="button" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center border-0" onclick="closeCustomModal('userModal')">
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <form id="userForm">
                        <input type="hidden" id="userId" name="userId" value="0">
                        <div class="grid gap-3 grid-cols-2 py-0">
                            <div class="col-span-2">
                                <label for="username" class="block mb-2 text-sm font-medium text-heading">Username</label>
                                <input type="text" name="username" id="username" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body form-control" placeholder="Enter username" required="" maxlength="50">
                            </div>
                            <div class="col-span-2">
                                <label for="fullname" class="block mb-2 text-sm font-medium text-heading">Full Name</label>
                                <input type="text" name="fullname" id="fullname" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body form-control" placeholder="Enter full name" required="" maxlength="100">
                            </div>
                            <div class="col-span-2">
                                <div class="position-relative">
                                    <input type="password" name="password" id="password" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body form-control" placeholder="••••••••" style="padding-right: 2.5rem;">
                                    <button type="button" id="togglePasswordVisibility" class="btn btn-link position-absolute text-muted" style="top: 50%; right: 10px; transform: translateY(-50%); text-decoration: none; padding: 0;">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-body" id="passwordHelp">Leave blank to keep existing password.</p>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="roleSelect" class="block mb-2 text-sm font-medium text-heading">Role</label>
                                <select id="roleSelect" name="roleSelect" class="block w-full px-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand px-3 py-2.5 shadow-xs placeholder:text-body form-select">
                                    <option selected="">Select role</option>
                                    <!-- Options via AJAX -->
                                </select>
                            </div>
                            <!-- 
                            <div class="col-span-2 sm:col-span-1">
                                <label for="userStatus" class="block mb-2 text-sm font-medium text-heading">Status</label>
                                <select id="userStatus" name="userStatus" class="block w-full px-3 py-2.5 bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand px-3 py-2.5 shadow-xs placeholder:text-body form-select">
                                     <option value="ENABLED">Active</option>
                                     <option value="DISABLED">Inactive</option>
                                </select>
                            </div> 
                            -->
                        </div>
                        <div class="flex items-center space-x-4 border-t border-default pt-3 mt-3 d-flex gap-2">
                            <button type="button" id="btnSaveUser" class="inline-flex items-center text-white bg-brand hover:bg-brand-strong box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none btn btn-primary border-0">
                                <svg class="w-4 h-4 me-1.5 -ms-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/></svg>
                                Save User
                            </button>
                            <button type="button" onclick="closeCustomModal('userModal')" class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none btn btn-light border">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div> 

        <!-- ==================== ROLE MODAL (NEW DESIGN) ==================== -->
        <div id="roleModal" tabindex="-1" aria-hidden="true" class="custom-modal-backdrop hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full" style="z-index: 1055;">
            <div class="relative p-3 w-full max-w-md max-h-full" style="margin-top: 40px;">
                <!-- Modal content -->
                <div class="relative bg-neutral-primary-soft border border-default rounded-base shadow-sm p-3 bg-white">
                    <!-- Modal header -->
                    <div class="flex items-center justify-content-between border-b border-default pb-3 mb-3">
                        <h3 class="text-lg font-medium text-heading fw-bold fs-5" id="roleModalLabel">
                            Create New Role
                        </h3>
                        <button type="button" class="text-body bg-transparent hover:bg-neutral-tertiary hover:text-heading rounded-base text-sm w-9 h-9 ms-auto inline-flex justify-center items-center border-0" onclick="closeCustomModal('roleModal')">
                            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/></svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <form id="roleForm">
                        <input type="hidden" id="roleId" name="roleId" value="0">
                        <div class="grid gap-3 grid-cols-2 py-0">
                            <div class="col-span-2">
                                <label for="roleName" class="block mb-2 text-sm font-medium text-heading">Role Name</label>
                                <input type="text" name="roleName" id="roleName" class="bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full px-3 py-2.5 shadow-xs placeholder:text-body form-control" placeholder="e.g. Administrator" required="" maxlength="50">
                            </div>
                            <div class="col-span-2">
                                <label for="roleDesc" class="block mb-2 text-sm font-medium text-heading">Description</label>
                                <textarea id="roleDesc" name="roleDesc" rows="3" class="block bg-neutral-secondary-medium border border-default-medium text-heading text-sm rounded-base focus:ring-brand focus:border-brand block w-full p-3.5 shadow-xs placeholder:text-body form-control" placeholder="Describe the role's responsibilities" maxlength="255"></textarea>                    
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 border-t border-default pt-3 mt-3 d-flex gap-2">
                            <button type="button" id="btnSaveRole" class="inline-flex items-center text-white bg-brand hover:bg-brand-strong box-border border border-transparent focus:ring-4 focus:ring-brand-medium shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none btn btn-primary border-0">
                                <svg class="w-4 h-4 me-1.5 -ms-0.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5"/></svg>
                                Save Role
                            </button>
                            <button type="button" onclick="closeCustomModal('roleModal')" class="text-body bg-neutral-secondary-medium box-border border border-default-medium hover:bg-neutral-tertiary-medium hover:text-heading focus:ring-4 focus:ring-neutral-tertiary shadow-xs font-medium leading-5 rounded-base text-sm px-4 py-2.5 focus:outline-none btn btn-light border">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==================== PERMISSIONS MODAL ==================== -->
        <div class="modal fade" id="permissionsModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold text-primary" id="permModalLabel">Manage Permissions</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">Select the modules that <strong><span id="permRoleName" class="text-dark"></span></strong> can access.</p>
                        <input type="hidden" id="permRoleId" value="0">
                        
                        <!-- Tree View Container -->
                        <div id="permissionsTree" class="border rounded p-3 bg-light" style="max-height: 400px; overflow-y: auto;">
                            <!-- Tree Content via JS -->
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary rounded-pill px-4" id="btnSavePermissions">Update Permissions</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>

        <!-- Scripts -->
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
        <script src="../../js/administrator/config_accounts.js?v=<?= time() ?>"></script>

    </body>
</html>
<?php
    } else {
        echo '<script> window.location.href = "../../login"; </script>';
    }
?>
