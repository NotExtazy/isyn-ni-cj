<?php
    if (session_status() == PHP_SESSION_NONE) {        session_start();    }
    if (isset($_SESSION['EMPNO']) && isset($_SESSION['USERNAME']) && isset($_SESSION["AUTHENTICATED"]) && $_SESSION["AUTHENTICATED"] === true) {
?>
<!doctype html>
<html lang="en" dir="ltr">
    <?php
        include('../../includes/pages.header.php');
    ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <title>iSyn | Maintenance Module</title>
    <body class="  ">
        <div id="loading">
            <div class="loader simple-loader">
                <div class="loader-body"></div>
            </div>
        </div>
        <style>
            main { background-color: #EAEAF6; }
            th { 
                color: #090909; 
                position: sticky; 
                top: 0; 
                background-color: #f8f9fa; /* Matches standard bootstrap header */
                z-index: 10; 
                box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4); /* Optional: adds a nice shadow line */
            }
            .section-card { background-color: white; border-radius: 0.75rem; }
            .section-title { color: #0f172a; font-weight: 700; letter-spacing: -0.01em; }
            .section-subtitle { color: #334155; }
            .help-text { color: #64748b; font-size: .85rem; }
            .badge-soft { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: .75rem; line-height: 1; }
            .badge-optional { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
            .tree { min-height: 280px; }
            .tree ul { list-style: none; padding-left: 1rem; margin: 0; border-left: 1px dashed #e2e8f0; }
            .tree li { padding: 6px 0; position: relative; }
            .tree li::before { content: ""; position: absolute; left: -1rem; top: 0.9rem; width: 12px; height: 1px; background: #e2e8f0; }
            .tree .node { cursor: pointer; display: inline-flex; align-items: center; gap: 8px; padding: 6px 10px; border-radius: 8px; border: 1px solid #e2e8f0; background-color: #ffffff; color: #0f172a; transition: all .15s ease; }
            .tree .node:hover { border-color: #cbd5e1; background-color: #f8fafc; }
            .tree .node.selected { border-width: 2px; }
            .node-type { font-size: .7rem; padding: 2px 6px; border-radius: 999px; border: 1px solid #e2e8f0; color: #475569; background: #f8fafc; }
            .node[data-type="0"] .node-type { background: #f9fafb; border-color: #d1d5db; color: #111827; }
            .node[data-type="1"] .node-type { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
            .node[data-type="2"] .node-type { background: #fff7ed; border-color: #fed7aa; color: #ea580c; }
            .empty-state { display: none; border: 1px dashed #e2e8f0; border-radius: 8px; padding: 16px; color: #64748b; background: #fafafa; }
            .form-divider { height: 1px; background: #e5e7eb; margin: 8px 0 16px; }
            .actions { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
            .label-module { color: #111827; }
            .label-submodule { color: #b91c1c; }
            .label-item { color: #ea580c; }
            .node[data-type="0"] { color: #111827; border-color: #d1d5db; }
            .node[data-type="1"] { color: #b91c1c; border-color: #fecaca; }
            .node[data-type="2"] { color: #ea580c; border-color: #fed7aa; }
            .node.selected[data-type="0"] { background-color: #f3f4f6; border-color: #9ca3af; }
            .node.selected[data-type="1"] { background-color: #fef2f2; border-color: #fca5a5; }
            .node.selected[data-type="2"] { background-color: #fff7ed; border-color: #fdba74; }
            
            /* Inline Field Configuration Design */
            .customer-type-item {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                margin-bottom: 12px;
                background: white;
                transition: all 0.3s ease;
            }
            
            .customer-type-item.expanded {
                border-color: #3a57e8;
                box-shadow: 0 2px 8px rgba(58, 87, 232, 0.15);
            }
            
            .customer-type-header {
                padding: 12px 16px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
                user-select: none;
            }
            
            .customer-type-header:hover {
                background-color: #f8fafc;
            }
            
            .customer-type-name {
                font-weight: 500;
                color: #1e293b;
                flex: 1;
            }
            
            .field-config-toggle {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 0.75rem;
                color: #64748b;
                padding: 4px 8px;
                border-radius: 4px;
                background: #f1f5f9;
                margin-right: 8px;
                transition: all 0.2s ease;
            }
            
            .field-config-toggle:hover {
                background: #e2e8f0;
                color: #475569;
            }
            
            .field-config-toggle i {
                transition: transform 0.3s ease;
            }
            
            .customer-type-item.expanded .field-config-toggle i {
                transform: rotate(180deg);
            }
            
            .field-config-panel {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.4s ease, padding 0.4s ease;
                border-top: 1px solid transparent;
            }
            
            .customer-type-item.expanded .field-config-panel {
                max-height: 600px;
                padding: 16px;
                border-top-color: #e2e8f0;
                background: #f8fafc;
            }
            
           /* Combined Field Row Design */
            .field-config-row {
                display: flex;
                align-items: center;
                padding: 8px 12px;
                border-radius: 6px;
                background: white;
                margin-bottom: 8px;
                border: 1px solid #e2e8f0;
                transition: all 0.2s ease;
            }
            
            .field-config-row:hover {
                border-color: #cbd5e1;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            
            .field-label {
                flex: 1;
                font-size: 0.875rem;
                color: #334155;
                font-weight: 500;
            }
            
            .field-checkbox-group {
                display: flex;
                align-items: center;
                gap: 24px;
            }
            
            .field-checkbox {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .field-checkbox-label {
                font-size: 0.75rem;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.025em;
                font-weight: 600;
            }
            
            .field-checkbox.disabled {
                opacity: 0.4;
                pointer-events: none;
            }
            
            /* Animated SVG Checkbox Design */ 
            .checkbox-container {
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .checkbox-container input {
                display: none;
            }

            .checkbox-container svg {
                overflow: visible;
                flex-shrink: 0;
                width: 1.5em;
                height: 1.5em;
            }

            .checkbox-path {
                fill: none;
                stroke: #3a57e8;
                stroke-width: 6;
                stroke-linecap: round;
                stroke-linejoin: round;
                transition: stroke-dasharray 0.5s ease, stroke-dashoffset 0.5s ease;
                stroke-dasharray: 241 9999999;
                stroke-dashoffset: 0;
            }

            .checkbox-container input:checked ~ svg .checkbox-path {
                stroke-dasharray: 70.5096664428711 9999999;
                stroke-dashoffset: -262.2723388671875;
            }
            
            .field-config-save {
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid #e2e8f0;
                display: flex;
                justify-content: flex-end;
                gap: 8px;
            }

            /* FORCE SWEETALERT ABOVE BOOTSTRAP MODAL */
            .swal2-container {
                z-index: 10000 !important;
            }
        </style>
        <?php
            include('../../includes/pages.sidebar.php');
            include('../../includes/pages.navbar.php');
        ?>
        <div class="container-fluid py-3">
            <div class="row g-4" style="min-height: calc(100vh - 120px);">
                <!-- LEFT SIDEBAR: TREE NAVIGATION -->
                <div class="col-lg-3 col-md-4">
                    <div class="card shadow-sm border-0 h-100 rounded-3 overflow-hidden">
                        <div class="card-header bg-white border-bottom p-3">
                            <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-list-tree me-2"></i>Navigation</h6>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-0" id="treeSearch" placeholder="Search settings...">
                            </div>
                        </div>
                        <div class="card-body p-0 overflow-auto" id="hierarchyNav" style="max-height: calc(100vh - 220px);">
                            <!-- Tree will be injected here by JS -->
                            <div class="text-center text-muted mt-5">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <small class="d-block mt-2">Loading structure...</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT CONTENT: EDITOR AREA -->
                <div class="col-lg-9 col-md-8">
                    <div class="card shadow-sm border-0 h-100 rounded-3">
                        <div class="card-body p-4">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                                <div>
                                    <h4 class="fw-bold text-dark mb-1" id="pageTitle">Maintenance</h4>
                                    <nav aria-label="breadcrumb">
                                        <ol class="breadcrumb mb-0 small" id="pageBreadcrumb">
                                            <li class="breadcrumb-item text-muted"><i class="fa-solid fa-arrow-left me-1"></i>Select a setting from the sidebar</li>
                                        </ol>
                                    </nav>
                                </div>
                            </div>

                    <!-- EMPTY STATE -->
                    <div id="editorEmptyState" class="text-center py-5">
                        <img src="../../assets/images/maintenance-placeholder.svg" alt="" style="max-width: 200px; opacity: 0.5;" class="mb-3 d-none">
                        <i class="fa-solid fa-screwdriver-wrench fa-3x text-muted mb-3 opacity-25"></i>
                        <h5 class="text-secondary">No Setting Selected</h5>
                        <p class="text-muted small">Please select a module or item from the sidebar to begin configuration.</p>
                    </div>

                    <!-- ACTIVE EDITOR CONTAINER -->
                    <div id="editorContainer" style="display: none;">
                        
                        <!-- Configuration Card -->
                        <div class="card shadow-sm border-0">
                            <div class="card-body">
                                <div id="editorConfigSection">
                                    <div id="generalConfigContainer">
                                        <!-- Hidden Inputs mimicking the old structure -->
                                        <select id="editorModule" class="d-none"></select>
                                        <select id="editorSubmodule" class="d-none"></select>
                                        <select id="editorItem" class="d-none"></select>

                                        <!-- The Dynamic Content Areas -->
                                        <div id="createItemContainer" style="display:none;">
                                             <label class="help-text mb-1">Create New Setting Item</label>
                                             <div class="input-group">
                                                 <input type="text" class="form-control" id="newItemName" placeholder="e.g. New Dropdown">
                                                 <button class="btn btn-outline-primary" type="button" id="btnCreateItem">Create</button>
                                             </div>
                                        </div>

                                        <div id="generalItemEditor" style="display:none;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <h6 class="fw-bold text-primary mb-0" id="editorContextTitle">Manage Choices</h6>
                                                    <small class="text-muted">Add or update dropdown values for <span id="editorContextName" class="fw-bold text-dark">...</span></small>
                                                </div>
                                            </div>


                                            <!-- Add New Section -->
                                            <div class="card card-body bg-light border-0 mb-3" id="addNewCardContainer">
                                                <label class="small fw-bold text-uppercase text-muted mb-2">Add New Option</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white"><i class="fa fa-plus text-muted"></i></span>
                                                    <input type="text" class="form-control" id="newChoiceGeneral" placeholder="Type new value here (e.g. Single)...">
                                                    <button class="btn btn-primary" type="button" id="btnSaveChoiceGeneral">
                                                        Add to List
                                                    </button>
                                                </div>
                                                
                                                <!-- Field Configuration for Customer Type (appears after typing) -->
                                                <div id="newCustomerTypeFieldConfig" style="display: none; margin-top: 16px;">
                                                    <div class="alert alert-info bg-white border border-primary">
                                                        <div class="d-flex align-items-start gap-2">
                                                            <i class="fa-solid fa-sliders text-primary mt-1"></i>
                                                            <div class="flex-grow-1">
                                                                <h6 class="fw-bold mb-1 text-primary">⚙️ Configure Fields (Optional)</h6>
                                                                <p class="mb-2 small text-muted">Select which fields should be enabled and required for this customer type.</p>
                                                                <button class="btn btn-sm btn-outline-primary" type="button" onclick="toggleNewTypeFieldConfig()">
                                                                    <i class="fa-solid fa-chevron-down me-1" id="newConfigToggleIcon"></i>
                                                                    <span id="newConfigToggleText">Show Field Options</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Collapsible Field Configuration Panel -->
                                                    <div id="newTypeFieldPanel" style="display: none; margin-top: 12px;">
                                                        <div class="border rounded p-3 bg-white">
                                                            <div class="row g-2">
                                                                <!-- Quick Presets -->
                                                                <div class="col-12 mb-2">
                                                                    <small class="text-muted fw-bold d-block mb-2">
                                                                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Quick Presets:
                                                                    </small>
                                                                    <div class="btn-group btn-group-sm" role="group">
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="applyPreset('minimal')">
                                                                            <i class="fa-solid fa-user me-1"></i>Minimal
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="applyPreset('standard')">
                                                                            <i class="fa-solid fa-user-tie me-1"></i>Standard
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="applyPreset('company')">
                                                                            <i class="fa-solid fa-building me-1"></i>Company
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="applyPreset('complete')">
                                                                            <i class="fa-solid fa-address-card me-1"></i>Complete
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="applyPreset('clear')">
                                                                            <i class="fa-solid fa-eraser me-1"></i>Clear All
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                
                                                                <!-- Field Configuration Grid -->
                                                                <div class="col-12" id="newTypeFieldsGrid">
                                                                    <!-- Will be populated by JavaScript -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- List Table -->
                                            <div class="table-responsive border rounded">
                                                <table class="table table-hover align-middle mb-0" id="choicesTable">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 60%;">Display Value</th>
                                                            <th style="width: 20%;">Status</th>
                                                            <th style="width: 20%;" class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="generalChoiceList">
                                                        <!-- Choices injected via JS -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Reusing Special Editors -->
                                        
                                        <!-- Customer Type Field Configuration Modal -->
                                        <div class="modal fade" id="fieldConfigModal" tabindex="-1" aria-labelledby="fieldConfigModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title" id="fieldConfigModalLabel">
                                                            <i class="fa-solid fa-sliders me-2"></i>Configure Fields
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <!-- Editable Customer Type Name -->
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">
                                                                <i class="fa-solid fa-tag me-1"></i>Customer Type Name
                                                            </label>
                                                            <input type="text" class="form-control" id="editCustomerTypeName" placeholder="Enter customer type name">
                                                            <small class="text-muted">You can rename this customer type while configuring.</small>
                                                        </div>
                                                        
                                                        <div class="alert alert-info border-0">
                                                            <i class="fa-solid fa-info-circle me-2"></i>
                                                            Configure which fields are enabled and required for this customer type.
                                                        </div>
                                                        
                                                        <!-- Quick Presets -->
                                                        <div class="mb-3">
                                                            <label class="small fw-bold text-muted mb-2">
                                                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Quick Presets:
                                                            </label>
                                                            <div class="btn-group btn-group-sm w-100" role="group">
                                                                <button type="button" class="btn btn-outline-secondary" onclick="applyEditPreset('minimal')">
                                                                    <i class="fa-solid fa-user me-1"></i>Minimal
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" onclick="applyEditPreset('standard')">
                                                                    <i class="fa-solid fa-user-tie me-1"></i>Standard
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" onclick="applyEditPreset('company')">
                                                                    <i class="fa-solid fa-building me-1"></i>Company
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" onclick="applyEditPreset('complete')">
                                                                    <i class="fa-solid fa-address-card me-1"></i>Complete
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" onclick="applyEditPreset('clear')">
                                                                    <i class="fa-solid fa-eraser me-1"></i>Clear All
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        <hr>
                                                        
                                                        <!-- Field Configuration Grid -->
                                                        <div id="fieldConfigContainer">
                                                            <div id="editTypeFieldsGrid">
                                                                <!-- Will be populated by JavaScript -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fa-solid fa-times me-1"></i>Cancel
                                                        </button>
                                                        <button type="button" class="btn btn-primary" onclick="saveEditFieldConfig()">
                                                            <i class="fa-solid fa-save me-1"></i>Save Configuration
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                         
                                        <div id="specialPrefixEditor" style="display:none;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="fw-bold text-primary m-0"><i class="fa-solid fa-sim-card me-2"></i>Valid Number Prefixes</h6>
                                                <button class="btn btn-sm btn-primary" onclick="OpenPrefixModal()">Add New Prefix</button>
                                            </div>
                                            <!-- Prefix Table Structure -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-4">
                                                    <select id="filterNetwork" class="form-select form-select-sm">
                                                        <option value="">All Networks</option>
                                                        <option value="GLOBE">Globe / TM</option>
                                                        <option value="SMART">Smart / TNT</option>
                                                        <option value="DITO">DITO</option>
                                                        <option value="LANDLINE">Landline</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table id="PrefixTbl" class="table table-sm table-hover table-bordered w-100">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Category</th>
                                                            <th>Network</th>
                                                            <th>Prefix</th>
                                                            <th>Status</th>
                                                            <th class="text-center">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="selectedModuleId">
        <input type="hidden" id="selectedSubmoduleId">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">


        <div class="modal fade" id="prefixModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Valid Prefix</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="prefixForm">
                            <input type="hidden" id="prefix_id" name="id">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Select Category / Network</label>
                                <select class="form-select" id="prefix_network" name="network_name" required>
                                    <option value="" selected disabled>-- Choose One --</option>
                                    <optgroup label="Mobile Networks">
                                        <option value="GLOBE">Globe / TM / GOMO</option>
                                        <option value="SMART">Smart / TNT / Sun</option>
                                        <option value="DITO">DITO Telecommunity</option>
                                    </optgroup>
                                    <optgroup label="Landlines">
                                        <option value="LANDLINE">Fixed Line / Landline</option>
                                    </optgroup>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Prefix Code (First 4 Digits)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control fw-bold fs-5" id="prefix_code" name="prefix_code" 
                                           placeholder="Select Network First"  autocomplete="off" disabled>
                                </div>
                                <div id="prefixHelp" class="form-text text-primary"></div>
                            </div>

                            <input type="hidden" id="prefix_type" name="prefix_type">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="btnSavePrefix">Save Prefix</button>
                    </div>
                </div>
            </div>
        </div>

        <?php
            include('../../includes/pages.footer.php');
        ?>
        <script src="../../js/administrator/maintenance.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    </body>
</html>
<?php
  } else {
    echo '<script> window.location.href = "../../login.php"; </script>';
  }
?>