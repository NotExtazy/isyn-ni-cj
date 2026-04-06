$(document).ready(function(){
    
    // --- INITIALIZATION ---
    LoadTreeNavigation(); 

    $("#newItemName, #newChoiceGeneral, #editChoiceValue, #editCustomerTypeName, #network_name").on("input", function() {
        // Enforce Uppercase AND Only Letters/Spaces
        // Allow spaces? "only letters" usually implies names which have spaces.
        // If strictly no spaces, remove \s. Assuming spaces allowed for "Customer Type" etc.
        let val = this.value.toUpperCase();
        this.value = val.replace(/[^A-Z\s]/g, '');
    });

    // --- SEARCH FUNCTIONALITY ---
    // --- SEARCH FUNCTIONALITY ---
    $("#treeSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        
        // 1. Reset: Show everything if empty
        if(value.length === 0) {
            $(".tree-item").show();
            $(".submodule-header").parent().show();
            $(".module-header").parent().show();
            
            // Instant Collapse (No Animation)
            $(".collapse").removeClass("show");
            
            // Reset Styles Manually (simulating hide.bs.collapse without animation)
            var $headers = $(".module-header, .submodule-header");
            $headers.removeClass("bg-primary text-white shadow-sm");
            $(".module-header").addClass("text-dark");
            $(".submodule-header").addClass("text-secondary");
            
            // Reset Icons
            $headers.find('.transition-icon-color').removeClass('text-white');
            $(".module-header .transition-icon-color").addClass('text-primary');
            $(".submodule-header .transition-icon-color").addClass('opacity-75');
            
            $headers.find('.transition-icon').removeClass('fa-rotate-180 text-white').addClass('text-muted opacity-50');

            return;
        }

        // 2. Hide everything initially
        $(".tree-item").hide();
        $(".submodule-header").parent().hide(); // Hide submodule wrappers
        $(".module-header").parent().hide();   // Hide module wrappers

        // 3. Find Matches
        var hasResults = false;
        $(".tree-item").each(function() {
            var $item = $(this);
            var text = $item.text().toLowerCase();
            
            if(text.indexOf(value) > -1) {
                // Show Item
                $item.show();
                hasResults = true;

                // Expand and Show Parents
                
                // Parent 1: The Item Container (collapse div)
                var $submoduleCollapse = $item.closest(".collapse");
                $submoduleCollapse.addClass("show"); // Force expand
                
                // Parent 2: The Submodule Wrapper (contains header + collapse)
                var $submoduleWrapper = $submoduleCollapse.parent().parent(); 
                // Note: Structure is div > div.collapse > div(items).  
                // Actually based on renderTree: 
                // Submodule Wrapper is the div with class "mb-1" inside the module collapse.
                // hierarchy is: ModuleCollapse > SubmoduleWrapper(mb-1) > SubmoduleHeader + SubmoduleCollapse
                
                $item.closest(".mb-1").show(); // Show Submodule Wrapper

                // Parent 3: The Module Collapse
                var $moduleCollapse = $item.closest(".mb-1").closest(".collapse");
                $moduleCollapse.addClass("show");

                // Parent 4: The Module Wrapper
                $moduleCollapse.parent().show(); // Show Module List Item
            }
        });

        // 4. Handle "No Results" (Optional)
        // if(!hasResults) ...
    });

    // --- TREE NAVIGATION (NEW) ---
    function LoadTreeNavigation(){
        $("#hierarchyNav").html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-primary"></div><div class="small text-muted mt-2">Loading structure...</div></div>');

        $.ajax({
            url:"../../routes/administrator/maintenance.route.php", type:"POST", data:{action:"GetHierarchy"}, dataType:"JSON",
            success:function(response){
                if(response.HIERARCHY && response.HIERARCHY.length > 0){
                    renderTree(response.HIERARCHY);
                } else { 
                    // FALLBACK MOCK DATA (For Design Review if DB is empty)
                    console.log("Database empty, using mock data.");
                    renderTree(getMockData());
                }
            },
            error: function(e){
                console.error("Tree Load Failed", e);
                // FALLBACK MOCK DATA ON ERROR
                renderTree(getMockData());
            }
        });
    }

    function getMockData(){
        return [
            { id_module: 1, module: "HUMAN RESOURCES", submodules: [
                { id_module: 10, module: "Profiling", items: [
                    { id_module: 101, module: "Civil Status" },
                    { id_module: 102, module: "Gender" },
                    { id_module: 103, module: "Religion" }
                ]},
                { id_module: 11, module: "Payroll", items: [] }
            ]},
            { id_module: 2, module: "SYSTEM SETTINGS", submodules: [
                { id_module: 20, module: "Configuration", items: [
                    { id_module: 201, module: "Valid Number Prefixes" }, // Special
                    { id_module: 202, module: "Customer Type" } // Special
                ]}
            ]}
        ];
    }

    function renderTree(data) {
        let html = '<div class="list-group list-group-flush" id="maintenanceAccordion">';
        
        // Icon Mapping based on pages.sidebar.php
        const iconMap = {
            "Profiling": "fa-address-card",
            "Cashier": "fa-cash-register",
            "Account Monitoring": "fa-user",
            "General Ledger": "fa-book-open",
            "Inventory Management": "fa-boxes-stacked",
            "Human Resources": "fa-briefcase", // Fallback/Assumed
            "System Settings": "fa-cogs"       // Fallback/Assumed
        };

        $.each(data, function(i, module){
            // Determine Icon (Case-insensitive check)
            let iconClass = "fa-layer-group"; // Default
            for (const [key, value] of Object.entries(iconMap)) {
                if (module.module.toLowerCase().includes(key.toLowerCase())) {
                    iconClass = value;
                    break;
                }
            }

            html += `
            <div class="list-group-item bg-transparent border-0 p-0 mb-2">
                <!-- Module Header -->
                <div class="d-flex align-items-center px-3 py-2 text-dark user-select-none module-header rounded" 
                     data-bs-toggle="collapse" data-bs-target="#mod_${module.id_module}" 
                     style="cursor:pointer; background: #f8f9fa;">
                    <i class="fa-solid ${iconClass} text-primary me-2 transition-icon-color"></i>
                    <span class="fw-bold small text-uppercase flex-grow-1">${module.module}</span>
                    <i class="fa-solid fa-chevron-down small transition-icon opacity-50"></i>
                </div>
                
                <!-- Submodules -->
                <div class="collapse" id="mod_${module.id_module}" data-bs-parent="#maintenanceAccordion">
                    <div class="ps-3 border-start py-1 ms-3" id="subgroup_${module.id_module}">
            `;
            
            if(module.submodules){
                $.each(module.submodules, function(j, sub){
                    html += `
                    <div class="mb-1">
                        <div class="d-flex align-items-center py-1 px-2 text-secondary user-select-none align-middle submodule-header rounded" 
                             data-bs-toggle="collapse" data-bs-target="#sub_${sub.id_module}"
                             style="cursor:pointer; transition: all 0.2s ease;">
                             <i class="fa-regular fa-folder me-2 small opacity-75 transition-icon-color"></i>
                             <span class="small fw-semibold msg-subject">${sub.module}</span>
                        </div>
                        
                        <div class="collapse ps-3 ms-1 border-start" id="sub_${sub.id_module}" data-bs-parent="#subgroup_${module.id_module}">
                            <div class="d-flex flex-column mt-1">
                    `;
                    
                    if(sub.items){
                        $.each(sub.items, function(k, item){
                            html += `
                            <button class="btn btn-sm text-start text-muted py-1 border-0 rounded-0 tree-item ps-2" 
                               data-id="${item.id_module}" 
                               onClick="ActivateSetting(${module.id_module}, ${sub.id_module}, ${item.id_module}, '${module.module}', '${sub.module}', '${item.module}'); return false;">
                                <span class="small">${item.module}</span>
                            </button>`;
                        });
                    }
                    
                    // New Item Placeholder
                    html += `
                        <button class="btn btn-sm text-start text-primary opacity-50 py-1 border-0 rounded-0 ps-2"
                           onClick="ShowCreateItem(${module.id_module}, ${sub.id_module}, '${sub.module}'); return false;">
                            <i class="fa fa-plus-circle me-1 small"></i> <span class="small">New...</span>
                        </button>
                    `;

                    html += `</div></div></div>`;
                });
            }
            html += `</div></div></div>`;
        });
        html += '</div>';
        $("#hierarchyNav").html(html);

        // --- EVENT LISTENERS FOR HIGHLIGHTING ---
        // When a module collapse is shown
        $('.collapse').on('show.bs.collapse', function (e) {
            e.stopPropagation(); 
            var header = $(this).prev('.module-header, .submodule-header');
            
            // Add Active Styles
            header.addClass('bg-primary text-white shadow-sm').removeClass('text-dark text-secondary');
            
            // Turn Icons White
            header.find('.transition-icon-color').removeClass('text-primary opacity-75').addClass('text-white');
            header.find('.transition-icon').addClass('fa-rotate-180 text-white').removeClass('text-muted opacity-50');
        });

        // When a module collapse is hidden
        $('.collapse').on('hide.bs.collapse', function (e) {
            e.stopPropagation();
            var header = $(this).prev('.module-header, .submodule-header');
            
            // Remove Active Styles
            header.removeClass('bg-primary text-white shadow-sm');
            if(header.hasClass('module-header')) header.addClass('text-dark');
            if(header.hasClass('submodule-header')) header.addClass('text-secondary');
            
            // Restore Icons to Blue/Gray
            if(header.hasClass('module-header')) {
                header.find('.transition-icon-color').addClass('text-primary').removeClass('text-white');
            } else {
                header.find('.transition-icon-color').addClass('opacity-75').removeClass('text-white');
            }
            
            header.find('.transition-icon').removeClass('fa-rotate-180 text-white').addClass('text-muted opacity-50');
        });
    }

    // --- GLOBAL HELPERS for ONCLICK ---
    window.ActivateSetting = function(modId, subId, itemId, modName, subName, itemName) {
        // Highlight
        $(".tree-item").removeClass("bg-primary text-white").addClass("text-muted");
        $(`.tree-item[data-id='${itemId}']`).removeClass("text-muted").addClass("bg-primary text-white");

        // Smooth Output Transition
        var $container = $("#editorConfigSection");
        $container.hide();

        // Context
        $("#pageTitle").text(itemName);
        $("#pageBreadcrumb").html(`<li class="breadcrumb-item">${modName}</li><li class="breadcrumb-item">${subName}</li><li class="breadcrumb-item active">${itemName}</li>`);
        
        // Update Context Header in Editor
        $("#editorContextName").text(itemName);

        // Hidden Inputs
        $("#editorModule").html(`<option value="${modId}" selected>${modName}</option>`);
        $("#editorSubmodule").html(`<option value="${subId}" selected>${subName}</option>`);
        $("#editorItem").html(`<option value="${itemId}" selected>${itemName}</option>`);
        $("#selectedModuleId").val(modId);
        $("#selectedSubmoduleId").val(subId);

        // UI Views
        $("#editorEmptyState").hide();
        $("#editorContainer").show();
        $("#createItemContainer").hide();
        $("#generalItemEditor").hide();
        $("#specialPrefixEditor").hide();
        $("#customerTypeFieldEditor").hide();

        // Router
        if (itemName === "Valid Number Prefixes") {
            $("#specialPrefixEditor").show();
            if(typeof LoadPrefixList === 'function') LoadPrefixList();
        } else if (itemName === "Customer Type") {
            $("#customerTypeFieldEditor").show(); 
            $("#generalItemEditor").show();
            // Pass true to indicate we need to check for field config on row click logic if needed
            LoadChoicesGeneral(itemId);
        } else {
            $("#generalItemEditor").show();
            $("#generalItemEditor").show();
            LoadChoicesGeneral(itemId);
        }
        
        // RESET/HIDE New Item Config Panel
        $('#newCustomerTypeFieldConfig').hide();
        $('#newTypeFieldsGrid').empty(); // Clear grid
        $('#newChoiceGeneral').val(''); // Clear input

        // Fade In Content
        $container.fadeIn(300);
    };

    window.ShowCreateItem = function(modId, subId, subName) {
        $("#pageTitle").text("Create New Setting");
        $("#pageBreadcrumb").html(`<li class="breadcrumb-item">Create in: ${subName}</li>`);
        $("#editorEmptyState").hide();
        $("#editorContainer").show();
        $("#generalItemEditor").hide();
        $("#specialPrefixEditor").hide();
        $("#customerTypeFieldEditor").hide();
        $("#createItemContainer").show();
        
        $("#editorSubmodule").val(subId).html(`<option value="${subId}" selected>${subName}</option>`);
        
        // Check if we are creating a Customer Type
        // We can check subName or explicitly pass a flag/ID if known.
        // Based on mock data/structure, Customer Type is under "Configuration" (submodule 20) -> Item 202.
        // But ShowCreateItem only gets modId, subId, subName.
        // So we are creating a NEW item under "Configuration".
        // Wait, "Customer Type" is an Item, not a Submodule?
        // Ah, the user adds OPTIONS to "Customer Type".
        // So "Customer Type" IS the ITEM.
        // The user is clicking "New..." under the ITEM "Customer Type"?
        // No, the tree structure shows "Customer Type" as an Item (202).
        // Clicking "New..." under a Submodule (e.g. Profiling) allows adding a new Item?
        // NO. "New..." is usually for adding a new CONFIGURATION OPTION (like a new Gender).
        // Let's check the onclick in renderTree:
        // onClick="ShowCreateItem(${module.id_module}, ${sub.id_module}, '${sub.module}'); return false;">
        // This adds a new Item to the Submodule.
        // BUT "Customer Type" is likely a list of choices.
        // The user wants to add a new "Customer Type" OPTION (e.g. "VIP", "Regular").
        // "Customer Type" is an Item (202). 
        // So the user should be selecting "Customer Type" and then clicking "Add to List" (btnSaveChoiceGeneral).
        // The "New..." button in the tree adds a NEW MAINTENANCE ITEM (e.g. adding "Religion" next to "Gender").
        // THAT IS NOT what the user is doing. The user is in the "Customer Type" view (ActivateSetting) and adding a choice.
        // The "creation of the new dropdown" refers to adding a new CHOICE to the "Customer Type" list.
        // So we need to check the "Input" event on #newChoiceGeneral, which I already have!
        // $('#newChoiceGeneral').on('input', function() ... inside the READY block.
        // The user says "configuration is also not present on creation".
        // This means the `.on('input')` handler is NOT firing or failing.
        // I will Verify the selectors and visibility.
        // Also, I will force check it on focus or just ensure the event is bound.
        
        // Let's look at the `$('#newChoiceGeneral').on('input', ...)` handler.
        // It checks: `if (itemName === 'Customer Type' && value.length > 0)`
        // `itemName` comes from `$('#editorItem option:selected').text();`
        // When ActivateSetting is called for "Customer Type", `#editorItem` should be set.
        
        // I'll ensure ShowCreateItem HIDES the field config, which is correct.
        // But the issue is likely in the "Add to List" flow (ActivateSetting -> Type Name -> Config appears).
        // I will add a check in ActivateSetting to reset/hide the new field config.
    };

    // --- GENERIC ACTIONS ---
    $("#btnCreateItem").on("click", function(){
        var submoduleId = $("#editorSubmodule").val();
        var name = ($("#newItemName").val() || "").trim();
        if(!submoduleId){ showToast("Select Submodule.", "warning"); return; }
        if(name === ""){ showToast("Enter name.", "warning"); return; }

        $.ajax({
            url:"../../routes/administrator/maintenance.route.php", type:"POST",
            data:{action:"CreateItem", parent: submoduleId, name: name}, dataType:"JSON",
            success:function(response){
                if(response.STATUS === "success"){
                    Swal.fire("Success", "Item created", "success");
                    $("#newItemName").val("");
                    LoadTreeNavigation(); // Reload tree
                } else { Swal.fire("Error", response.MESSAGE, "error"); }
            }
        });
    });

    $("#btnSaveChoiceGeneral").on("click", function(){
        var itemId = $("#editorItem").val();
        var choice = $("#newChoiceGeneral").val().trim();
        var module = $("#editorModule option:selected").text();
        var submodule = $("#editorSubmodule option:selected").text();
        var item = $("#editorItem option:selected").text();

        var item = $("#editorItem option:selected").text();
        
        // Strict check if empty (though regex input handler prevents invalid chars, empty is possible)
        if(choice === "") { showToast("Please enter a value", "warning"); return; }

        SaveChoice(module, submodule, item, choice, itemId, function(){
            $("#newChoiceGeneral").val("");
            LoadChoicesGeneral(itemId);
            showToast(`Option "${choice}" added successfully.`, 'success');
            
            // RESET AND HIDE INLINE CONFIGURATION
            $('#newCustomerTypeFieldConfig').hide();
            $('#newTypeFieldPanel').hide();
            $('#newConfigToggleText').text('Show Field Options');
            $('#newConfigToggleIcon').attr('class', 'fa-solid fa-chevron-down me-1');
            
            // Uncheck all boxes
            $('#newTypeFieldPanel input[type="checkbox"]').prop('checked', false);
            // Re-check default enabled fields if needed, or leave blank. 
            // Better to re-trigger the default state logic if possible, but clearing is fine for now.
             $('input[value="mobileNumber"], input[value="email"]').prop('checked', true); 
        });
    });
    
    // ==========================================
    //  CUSTOMER TYPE FIELD CONFIGURATION UI
    // ==========================================
    // Updated: Since we have a table now, we probably want to click a row to configure fields?
    // NOTE: For now, I'll allow clicking the ROW to trigger the special editor for Customer Type
    $(document).on('click', '#choicesTable tbody tr', function(e) {
        // Avoid triggering if clicked on buttons
        if($(e.target).closest('button').length) return;

            const itemName = $('#editorItem option:selected').text().trim();
            
            // Special handling for Customer Type row click
            if(itemName === 'Customer Type') {
                 // Get data from row
                 const selectedText = $(this).find('td:first').text().trim();
                 // Highlight row
                 $('#choicesTable tbody tr').removeClass('table-active');
                 $(this).addClass('table-active');
                 
                 // Show field editor in the create panel if desired, OR just open the modal.
                 // The original code showed the CREATE panel editor (`#customerTypeFieldEditor`).
                 // But wait, `#customerTypeFieldEditor` is from the OLD design (pre-modal).
                 // The new design uses `#fieldConfigModal`.
                 // So clicking the row should probably open the modal now?
                 // Or are we keeping the specific behavior for Customer Type?
                 
                 // User might expect clicking row to "select" it for some reason?
                 // For now, let's just highlight it. The button is the primary action.
                 
                 // If we want to support "Click row to edit" we can do:
                 // const id = $(this).attr('id').replace('choice-row-', '');
                 // openFieldConfigModal(id, selectedText);
            }
    });



    // ==========================================
    //  SAVE PREFIX BUTTON (Global Listener)
    // ==========================================
    $(document).on('click', '#btnSavePrefix', function(e) {
        e.preventDefault();
        var network = $('#prefix_network').val();
        var category = $('#prefix_type').val();
        var raw_codes = $('#prefix_code').val();

        if (!network || !category || !raw_codes) { Swal.fire("Error", "Fill all fields.", "error"); return; }

        var formData = new FormData();
        formData.append('action', 'SavePrefix');
        formData.append('network', network);
        formData.append('category', category);
        formData.append('prefix_code', raw_codes);

        $.ajax({
            url: "../../routes/administrator/maintenanceprefix.route.php", type: "POST", data: formData, contentType: false, processData: false, dataType: "JSON",
            success: function(response) {
                if (response.STATUS === "success") {
                    Swal.fire("Success", response.MESSAGE, "success");
                    $('#prefix_code').val(''); 
                    LoadPrefixList(); 
                    $('#prefixModal').modal('hide'); 
                } else { Swal.fire("Notice", response.MESSAGE, "info"); }
            }
        });
    });

}); // END READY

// --- HELPER FUNCTIONS ---

function LoadChoicesGeneral(itemId){
    var itemType = $("#editorItem option:selected").text().trim();
    if(!itemType) { 
        // Fallback if not set yet (e.g. direct call), though ActivateSetting handles this.
        // We can't easily get text if not in DOM, but LoadChoicesGeneral is called AFTER ActivateSetting.
        // If empty, it might fail, but let's try.
    }

    $("#generalChoiceList").html('<tr><td colspan="3" class="text-center p-3 text-muted">Loading choices...</td></tr>');
    
    $.ajax({
        url:"../../routes/administrator/maintenance.route.php", type:"POST", 
        data:{action:"LoadChoices", item_id:itemId, itemType: itemType}, dataType:"JSON",
        success:function(response){
            var html = '';
            if(response.DATA && response.DATA.length > 0){
                $.each(response.DATA, function(i, c){
                    var statusBadge = c.status == 1 
                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Active</span>' 
                        : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill">Inactive</span>';
                    
                    var toggleBtn = c.status == 1
                        ? `<button class="btn btn-sm btn-outline-danger" title="Deactivate" onclick="ToggleChoiceStatus(${c.id}, 1); event.stopPropagation();"><i class="fa fa-power-off"></i></button>`
                        : `<button class="btn btn-sm btn-outline-success" title="Activate" onclick="ToggleChoiceStatus(${c.id}, 0); event.stopPropagation();"><i class="fa fa-power-off"></i></button>`;

                    // Escaping for safety - Use encoded URI component or simple replacement
                    var safeValue = c.choice_value.replace(/\\/g, "\\\\").replace(/"/g, '&quot;').replace(/'/g, "\\'");
                    
                    // Unified Action Buttons for ALL Items
                    // We use openFieldConfigModal for both Customer Type and others.
                    // The function will handle showing/hiding the config grid.
                    var actionButtons = `
                        <button class="btn btn-sm btn-info" onclick="openFieldConfigModal(${c.id}, '${safeValue}'); event.stopPropagation();" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        ${toggleBtn}
                    `;

                    var rowClass = (itemId == $("#editorItem").val() && i === 0 && $('#editorItem option:selected').text() === 'Customer Type') ? 'table-active' : '';
                    
                    html += `<tr id="choice-row-${c.id}" class="${rowClass}" style="cursor: pointer;">
                        <td class="fw-bold text-dark">${c.choice_value}</td>
                        <td class="status-cell">${statusBadge}</td>
                        <td class="text-center actions-cell">
                            <div class="btn-group btn-group-sm">
                                ${actionButtons}
                            </div>
                        </td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="3" class="text-center p-4 text-muted"><i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-25"></i>No items found. Add one above.</td></tr>';
            }
            $("#generalChoiceList").html(html);
        }
    });
}

    // --- UPDATE CHOICE ---
    window.PromptUpdateChoice = function(id, currentValue) {
        Swal.fire({
            title: 'Update Option',
            input: 'text',
            inputValue: currentValue,
            inputAttributes: {
                maxlength: "20",
                autocapitalize: 'characters'
            },
            customClass: {
                input: 'text-uppercase'
            },
            scrollbarPadding: false,
            showCancelButton: true,
            confirmButtonText: 'Update',
            showLoaderOnConfirm: true,
            didOpen: () => {
                const input = Swal.getInput();
                input.oninput = function() {
                    let start = this.selectionStart;
                    let end = this.selectionEnd;
                    
                    // Force Uppercase and Remove Invalid Chars (Keep only A-Z and Space)
                    this.value = this.value.toUpperCase().replace(/[^A-Z\s]/g, '');
                    
                    this.setSelectionRange(start, end);
                };
            },
            preConfirm: (newValue) => {
                const rawValue = newValue.trim(); // Ensure trim handles whitespace
                if (!rawValue || rawValue === "") {
                    Swal.showValidationMessage('Please enter a value');
                    return false;
                }
                return $.ajax({
                    url: "../../routes/administrator/maintenance.route.php",
                    type: "POST",
                    data: { action: "UpdateChoice", id: id, choice: rawValue },
                    dataType: "JSON"
                }).then(response => {
                    if (response.STATUS !== 'success') {
                        throw new Error(response.MESSAGE);
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                // Ensure Toast has top-end position to not overlap with any modals if present
                showToast('Option updated successfully', 'success');
                
                // IN-PLACE UPDATE (No Reload)
                // We need to know the ID and the New Value.
                // The result from Swal is the isConfirmed bool, not the value.
                // But preConfirm returns the AJAX response. 
                // Wait, Swal.fire().then(result) -> result.value IS the return from preConfirm (the AJAX response).
                // So result.value.DATA might contain the saved data if backend returns it?
                // OR we can just use the value from the input?
                // Actually, preConfirm returns the response object.
                // But we don't have access to the 'newValue' variable here in .then() scope easily unless we parse it from result.value or something.
                // Easier way: Store it in a variable or just trust the input?
                // Ah, result.value IS the response from ajax. If ajax returns the updated choice object, we can use it.
                // If not, we can grab it from Swal.getInput().value
                
                var newValue = Swal.getInput().value.trim().toUpperCase(); // Ensure we emulate the input processing
                updateChoiceRowUI(id, newValue);
            }
        });
    };

    /* 
    // OLD DELETE FUNCTION - Kept for reference but button removed
    window.DeleteChoiceItem = function(choiceId) {
        ...
    } 
    */

function SaveChoice(module, submodule, item, choice, itemId, callback){
    $.ajax({
        url:"../../routes/administrator/maintenance.route.php", type:"POST",
        data:{ action:"SaveChoice", module: module, submodule: submodule, item: item, choice: choice, item_id: itemId }, dataType:"JSON",
        success:function(response){
            if(response.STATUS === 'success'){ callback(); } else { 
                // Use Toast for errors (e.g. Duplicates) instead of Modal
                showToast(response.MESSAGE, 'error'); 
            }
        }
    });
}

function ArchiveChoice(id, newStatus, callback){
    $.ajax({
        url:"../../routes/administrator/maintenance.route.php", type:"POST", data:{action:"ArchiveChoice", id:id, status:newStatus}, dataType:"JSON",
        success:function(response){ callback(); }
    });
}

function DeleteChoice(id, callback){
    $.ajax({
        url:"../../routes/administrator/maintenance.route.php", type:"POST", data:{action:"DeleteChoice", id:id}, dataType:"JSON",
        success:function(response){ callback(); }
    });
}

function LoadFieldConfiguration(customerType) {
    console.log('[FieldConfig] Loading config for:', customerType);
    $.ajax({
        url: '../../routes/administrator/maintenance.route.php', type: 'POST',
        data: { action: 'GetFieldConfig', customerType: customerType }, dataType: 'JSON',
        success: function(response) {
            console.log('[FieldConfig] Response:', response);

            if (response.STATUS === 'success' && response.CONFIG) {
                const enabledFields  = response.CONFIG.enabledFields  || [];
                const requiredFields = response.CONFIG.requiredFields || [];
                console.log('[FieldConfig] Applying — enabled:', enabledFields, 'required:', requiredFields);

                // Mark enabled fields and unlock their Required column
                enabledFields.forEach(f => {
                    $('#enabled-edit-' + f).prop('checked', true);
                    handleEditFieldEnabledChange(f); // visually unlocks Required for this row
                });

                // Mark required fields (only if already enabled)
                requiredFields.forEach(f => {
                    if ($('#enabled-edit-' + f).is(':checked')) {
                        $('#required-edit-' + f).prop('checked', true);
                    }
                });
            } else {
                console.log('[FieldConfig] No saved config yet — all fields start unchecked');
            }
        },
        error: function(xhr, status, err) {
            console.error('[FieldConfig] AJAX error:', status, err);
        }
    });
}

// --- PREFIX TABLE STUFF ---
var prefixTable;
function LoadPrefixList() {
    $.ajax({
        url: "../../routes/administrator/maintenanceprefix.route.php", type: "POST", data: { action: "LoadPrefixList" }, dataType: "JSON",
        success: function(response) {
            if ($.fn.DataTable.isDataTable('#PrefixTbl')) { $('#PrefixTbl').DataTable().destroy(); }
            var html = "";
            if (response.LIST && response.LIST.length > 0) {
                $.each(response.LIST, function(i, row) {
                     let statusBadge = row.status == 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                     html += `<tr>
                        <td>${row.prefix_type}</td>
                        <td>${row.network_name}</td>
                        <td class="fw-bold">${row.prefix_code}</td>
                        <td>${statusBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="EditPrefix(${row.id})" title="Edit"><i class="fa fa-edit"></i></button>
                            <button class="btn btn-sm ${ row.status == 1 ? 'btn-outline-danger' : 'btn-outline-success'}" onclick="TogglePrefixStatus(${row.id}, ${row.status})" title="Toggle Status">
                                <i class="fa fa-${ row.status == 1 ? 'ban' : 'check'}"></i>
                            </button>
                        </td></tr>`;
                });
            } else {
                html = '<tr><td colspan="5" class="text-center text-muted p-3">No prefixes found.</td></tr>';
            }
            $("#PrefixTbl tbody").html(html);
            prefixTable = $('#PrefixTbl').DataTable({ 
                pageLength: 25,
                order: [[0, 'asc'], [2, 'asc']]
            });
        }
    });
}

function OpenPrefixModal() {
    $('#prefixForm')[0].reset();
    $('#prefix_id').val('');
    $('#prefixModal').modal('show');
}

function EditPrefix(id) {
    $.ajax({
        url: "../../routes/administrator/maintenanceprefix.route.php", type: "POST", data: { action: "GetPrefixInfo", id: id }, dataType: "JSON",
        success: function(response) {
            if (response.STATUS === 'success') {
                $('#prefix_id').val(response.INFO.id);
                $('#prefix_network').val(response.INFO.network_name);
                $('#prefix_type').val(response.INFO.prefix_type);
                $('#prefix_code').val(response.INFO.prefix_code);
                $('#prefixModal').modal('show');
            }
        }
    });
}

function TogglePrefixStatus(id, currentStatus) {
    var newStatus = (currentStatus == 1) ? 0 : 1;
    $.ajax({
        url: "../../routes/administrator/maintenanceprefix.route.php", type: "POST",
        data: { action: "UpdateStatus", id: id, status: newStatus }, dataType: "JSON",
        success: function(response) {
            if (response.STATUS === 'success') {
                showToast('Status updated!', 'success');
                LoadPrefixList();
            } else {
                showToast(response.MESSAGE || 'Failed to update status.', 'error');
            }
        }
    });
}

// (Other Prefix helpers like Filter/Input logic can be added here if critical, shortened for now)

// --- GLOBAL HELPERS (MOVED OUTSIDE READY) ---

function showToast(message, type = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    
    let icon = type;
    if (type === 'primary' || type === 'secondary') icon = 'info';
    
    Toast.fire({
        icon: icon,
        title: message
    });
}

window.ToggleChoiceStatus = function(choiceId, currentStatus) {
    var newStatus = (currentStatus == 1) ? 0 : 1;
    var statusText = (newStatus == 1) ? "Activated" : "Deactivated";
    var type = (newStatus == 1) ? "success" : "secondary";

    // Optimistic UI Update or Wait for AJAX? User asked for transition.
    // Let's wait for AJAX to be sure, but show loading state if needed.
    
    ArchiveChoice(choiceId, newStatus, function(){ 
        // SUCCESS CALLBACK
        showToast(`Item ${statusText}`, type);
        
        // IN-PLACE UPDATE (No Reload)
        var $row = $(`#choice-row-${choiceId}`);
        if($row.length > 0) {
            // 1. Update Badge
            var newBadge = (newStatus == 1) 
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1 rounded-pill">Active</span>'
                : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1 rounded-pill">Inactive</span>';
            
            var $cell = $row.find('.status-cell');
            $cell.fadeOut(200, function() {
                $(this).html(newBadge).fadeIn(200);
            });

            // 2. Update Toggle Button
            var $btnGroup = $row.find('.actions-cell .btn-group');
            // Find the toggle button - it's the one with onclick containing ToggleChoiceStatus
            // We can reconstruct the button or just update attributes. Reconstructing is safer.
            
            // We need to know if it's Customer Type (to keep Configure button).
            // Actually, we can just find the specfic toggle button.
            var $toggleBtn = $btnGroup.find('button[onclick*="ToggleChoiceStatus"]');
            
            // Determine new button HTML/Attributes without replacing the whole group (to keep other buttons)
            if (newStatus == 1) {
                // Now Active -> Button should be Deactivate (Danger)
                $toggleBtn.removeClass('btn-outline-success').addClass('btn-outline-danger');
                $toggleBtn.attr('title', 'Deactivate');
                $toggleBtn.attr('onclick', `ToggleChoiceStatus(${choiceId}, 1); event.stopPropagation();`);
                $toggleBtn.html('<i class="fa fa-power-off"></i>');
            } else {
                // Now Inactive -> Button should be Activate (Success)
                $toggleBtn.removeClass('btn-outline-danger').addClass('btn-outline-success');
                $toggleBtn.attr('title', 'Activate');
                $toggleBtn.attr('onclick', `ToggleChoiceStatus(${choiceId}, 0); event.stopPropagation();`);
                $toggleBtn.html('<i class="fa fa-power-off"></i>');
            }
        } else {
            // Fallback if row not found
            LoadChoicesGeneral($("#editorItem").val()); 
        }
    });
};

window.DeleteChoiceItem = function(choiceId) {
    Swal.fire({
        title: 'Delete?', text: "Cannot be undone.", icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            DeleteChoice(choiceId, function(){ LoadChoicesGeneral($("#editorItem").val()); });
        }
    });
};

// ====================================================================
// CUSTOMER TYPE FIELD CONFIGURATION
// ====================================================================

// Show field configuration panel when typing new customer type
$('#newChoiceGeneral').on('input', function() {
    // We need to match the item name exactly or using the improved check
    const itemName = $('#editorItem option:selected').text().trim();
    const value = $(this).val().trim();
    
    // Debug
    console.log('New Choice Input:', { itemName, length: value.length });
    
    // Check for Customer Type (trimmed)
    if (itemName === 'Customer Type' && value.length > 0) {
        // Show panel
        $('#newCustomerTypeFieldConfig').slideDown(300);
        
        // Generate grid if empty
        // We should always regenerate or check if empty. 
        // If we previously generated it, we might want to keep it?
        // But if we switched items, we should have cleared it.
        // Let's regenerate if empty.
        if ($('#newTypeFieldsGrid').children().length === 0) {
            console.log('Generating New Type Field Grid...');
            generateNewTypeFieldGrid();
        }
    } else {
        $('#newCustomerTypeFieldConfig').slideUp(300);
        $('#newTypeFieldPanel').slideUp(300); // Also hide the inner panel if it was toggled? 
        // Actually newCustomerTypeFieldConfig IS the container for the "Show Options" toggle?
        // No, looking at HTML (inferred), there's a wrapper.
    }
});

// Toggle field configuration panel
window.toggleNewTypeFieldConfig = function() {
    const $panel = $('#newTypeFieldPanel');
    const $icon = $('#newConfigToggleIcon');
    const $text = $('#newConfigToggleText');
    
    if ($panel.is(':visible')) {
        $panel.slideUp(300);
        $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        $text.text('Show Field Options');
    } else {
        $panel.slideDown(300);
        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        $text.text('Hide Field Options');
    }
};

// Generate field configuration grid for new customer type
function generateNewTypeFieldGrid() {
    const fields = [
        { value: 'companyName', label: 'Company Name' },
        { value: 'firstName', label: 'First Name' },
        { value: 'lastName', label: 'Last Name' },
        { value: 'middleName', label: 'Middle Name' },
        { value: 'suffix', label: 'Suffix' },
        { value: 'birthdate', label: 'Birthdate' },
        { value: 'age', label: 'Age' },
        { value: 'gender', label: 'Gender' },
        { value: 'mobileNumber', label: 'Mobile Number' },
        { value: 'email', label: 'Email' },
        { value: 'tin', label: 'TIN' }
    ];
    
    let html = '';
    fields.forEach(field => {
        html += `
        <div class="field-config-row">
            <span class="field-label">${field.label}</span>
            <div class="field-checkbox-group">
                <div class="field-checkbox field-enabled-new-${field.value}">
                    <label class="checkbox-container">
                        <input type="checkbox" 
                               id="enabled-new-${field.value}" 
                               value="${field.value}"
                               class="new-field-enabled"
                               onchange="handleNewFieldEnabledChange('${field.value}')">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="checkbox-path"></path>
                        </svg>
                    </label>
                    <span class="field-checkbox-label">Enabled</span>
                </div>
                <div class="field-checkbox field-required-new-${field.value}">
                    <label class="checkbox-container">
                        <input type="checkbox" 
                               id="required-new-${field.value}" 
                               value="${field.value}"
                               class="new-field-required">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="checkbox-path"></path>
                        </svg>
                    </label>
                    <span class="field-checkbox-label">Required</span>
                </div>
            </div>
        </div>
        `;
    });
    
    $('#newTypeFieldsGrid').html(html);
    
    // Initialize state: Disable all required checkboxes where enabled is unchecked
    // (Though they start unchecked/enabled by default in html string above, we should enforce the logic)
    // Actually, in the HTML loop above, we didn't check anything by default, so everything is unchecked.
    // The previous user edit removed 'disabled' attribute from the HTML string.
    // We need to re-add it OR run the logic.
    // Let's run the logic for all fields to be safe and consistent.
    fields.forEach(field => {
        handleNewFieldEnabledChange(field.value);
    });
}

// Handle enabled checkbox change for new customer type
window.handleNewFieldEnabledChange = function(fieldValue) {
    const enabledChecked = $(`#enabled-new-${fieldValue}`).is(':checked');
    const $requiredContainer = $(`.field-required-new-${fieldValue}`);
    
    if (!enabledChecked) {
        $(`#required-new-${fieldValue}`).prop('checked', false);
        $requiredContainer.addClass('disabled');
    } else {
        $requiredContainer.removeClass('disabled');
    }
};

// Apply preset configurations
window.applyPreset = function(presetType) {
    const presets = {
        minimal: {
            enabled: ['firstName', 'lastName', 'mobileNumber', 'email'],
            required: ['firstName', 'lastName', 'mobileNumber']
        },
        standard: {
            enabled: ['firstName', 'lastName', 'middleName', 'gender', 'mobileNumber', 'email'],
            required: ['firstName', 'lastName', 'mobileNumber', 'email']
        },
        company: {
            enabled: ['companyName', 'email', 'tin'],
            required: ['companyName', 'email', 'tin']
        },
        complete: {
            enabled: ['companyName', 'firstName', 'lastName', 'middleName', 'suffix', 'birthdate', 'age', 'gender', 'mobileNumber', 'email', 'tin'],
            required: ['firstName', 'lastName', 'mobileNumber', 'email']
        },
        clear: {
            enabled: [],
            required: []
        }
    };
    
    const preset = presets[presetType];
    if (!preset) return;
    
    // Clear all first
    $('.new-field-enabled').prop('checked', false);
    $('.new-field-required').prop('checked', false);
    $('.field-checkbox[class*="field-required-new-"]').addClass('disabled');
    
    // Apply preset
    preset.enabled.forEach(field => {
        $(`#enabled-new-${field}`).prop('checked', true);
        $(`.field-required-new-${field}`).removeClass('disabled');
    });
    
    preset.required.forEach(field => {
        $(`#required-new-${field}`).prop('checked', true);
    });
    
    // Toast alert
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    
    const messages = {
        minimal: 'Essential fields only',
        standard: 'Common business fields',
        company: 'Company/corporate fields',
        complete: 'All available fields',
        clear: 'All fields cleared'
    };
    
    Toast.fire({
        icon: 'success',
        title: 'Preset Applied!',
        text: messages[presetType]
    });
};

// MODAL-BASED FIELD CONFIGURATION FOR EXISTING CUSTOMER TYPES

let currentEditingTypeId = null;
let currentEditingTypeName = '';

// Open field configuration modal (Generic for all items)
window.openFieldConfigModal = function(typeId, typeName) {
    currentEditingTypeId = typeId;
    currentEditingTypeName = typeName;
    
    // Set Name
    $('#editCustomerTypeName').val(typeName);

    // Always show field configuration (button only exists on Customer Type rows)
    $('#fieldConfigContainer').show();
    $('#editModalTitle').text('Configure: ' + typeName);

    // Build fresh grid (all unchecked initially)
    generateEditTypeFieldGrid();

    const modalEl = document.getElementById('fieldConfigModal');
    const modal = new bootstrap.Modal(modalEl, {
        backdrop: 'static',
        keyboard: false
    });

    // Load saved config AFTER the modal is fully visible so that the
    // custom SVG checkbox CSS animations fire on visible elements
    modalEl.addEventListener('shown.bs.modal', function onShown() {
        modalEl.removeEventListener('shown.bs.modal', onShown);
        LoadFieldConfiguration(typeName);
    });

    modal.show();
};

// Generate field grid for editing
function generateEditTypeFieldGrid() {
    const fields = [
        { value: 'companyName', label: 'Company Name' },
        { value: 'firstName', label: 'First Name' },
        { value: 'lastName', label: 'Last Name' },
        { value: 'middleName', label: 'Middle Name' },
        { value: 'suffix', label: 'Suffix' },
        { value: 'birthdate', label: 'Birthdate' },
        { value: 'age', label: 'Age' },
        { value: 'gender', label: 'Gender' },
        { value: 'mobileNumber', label: 'Mobile Number' },
        { value: 'email', label: 'Email' },
        { value: 'tin', label: 'TIN' }
    ];
    
    let html = '';
    fields.forEach(field => {
        html += `
        <div class="field-config-row mb-2">
            <span class="field-label">${field.label}</span>
            <div class="field-checkbox-group">
                <div class="field-checkbox field-enabled-edit-${field.value}">
                    <label class="checkbox-container">
                        <input type="checkbox" 
                               id="enabled-edit-${field.value}" 
                               value="${field.value}"
                               class="edit-field-enabled"
                               onchange="handleEditFieldEnabledChange('${field.value}')">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="checkbox-path"></path>
                        </svg>
                    </label>
                    <span class="field-checkbox-label">Enabled</span>
                </div>
                <div class="field-checkbox field-required-edit-${field.value}">
                    <label class="checkbox-container">
                        <input type="checkbox" 
                               id="required-edit-${field.value}" 
                               value="${field.value}"
                               class="edit-field-required">
                        <svg viewBox="0 0 64 64" height="2em" width="2em">
                            <path d="M 0 16 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 16 L 32 48 L 64 16 V 8 A 8 8 90 0 0 56 0 H 8 A 8 8 90 0 0 0 8 V 56 A 8 8 90 0 0 8 64 H 56 A 8 8 90 0 0 64 56 V 16" pathLength="575.0541381835938" class="checkbox-path"></path>
                        </svg>
                    </label>
                    <span class="field-checkbox-label">Required</span>
                </div>
            </div>
        </div>
        `;
    });
    
    $('#editTypeFieldsGrid').html(html);
    
    // Initialize state for Edit Grid
    // We need to ensure that for every field, if it's not checked, the required box is disabled.
    // However, this function is called BEFORE data is populated (checked).
    // So initially, everything is unchecked.
    // We should enforce the disabled state for all unchecked items.
    fields.forEach(field => {
        // We can't just call handleEditFieldEnabledChange because it relies on the DOM state
        // creating the grid puts them in unchecked state.
        // So we just need to ensure the required boxes are disabled.
        // But wait, if we are loading saved config, checks will be applied later.
        // The checking logic (in openFieldConfig/LoadFieldConfig) needs to trigger the update.
        
        // precise fix: set them to disabled initially in the HTML loop?
        // OR, just run the handler here which will see they are unchecked and disable required.
        handleEditFieldEnabledChange(field.value);
    });
}

// Handle enabled checkbox change in edit modal
window.handleEditFieldEnabledChange = function(fieldValue) {
    const enabledChecked = $(`#enabled-edit-${fieldValue}`).is(':checked');
    const $requiredContainer = $(`.field-required-edit-${fieldValue}`);
    
    if (!enabledChecked) {
        $(`#required-edit-${fieldValue}`).prop('checked', false);
        $requiredContainer.addClass('disabled');
    } else {
        $requiredContainer.removeClass('disabled');
    }
};

// Apply preset in edit modal
window.applyEditPreset = function(presetType) {
    const presets = {
        minimal: {
            enabled: ['firstName', 'lastName', 'mobileNumber', 'email'],
            required: ['firstName', 'lastName', 'mobileNumber']
        },
        standard: {
            enabled: ['firstName', 'lastName', 'middleName', 'gender', 'mobileNumber', 'email'],
            required: ['firstName', 'lastName', 'mobileNumber', 'email']
        },
        company: {
            enabled: ['companyName', 'email', 'tin'],
            required: ['companyName', 'email', 'tin']
        },
        complete: {
            enabled: ['companyName', 'firstName', 'lastName', 'middleName', 'suffix', 'birthdate', 'age', 'gender', 'mobileNumber', 'email', 'tin'],
            required: ['firstName', 'lastName', 'mobileNumber', 'email']
        },
        clear: {
            enabled: [],
            required: []
        }
    };
    
    const preset = presets[presetType];
    if (!preset) return;
    
    // Clear all first
    $('.edit-field-enabled').prop('checked', false);
    $('.edit-field-required').prop('checked', false);
    $('.field-checkbox[class*="field-required-edit-"]').addClass('disabled');
    
    // Apply preset
    preset.enabled.forEach(field => {
        $(`#enabled-edit-${field}`).prop('checked', true);
        $(`.field-required-edit-${field}`).removeClass('disabled');
    });
    
    preset.required.forEach(field => {
        $(`#required-edit-${field}`).prop('checked', true);
    });
    
    // Toast alert
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    
    const messages = {
        minimal: 'Essential fields only',
        standard: 'Common business fields',
        company: 'Company/corporate fields',
        complete: 'All available fields',
        clear: 'All fields cleared'
    };
    
    Toast.fire({
        icon: 'success',
        title: 'Preset Applied!',
        text: messages[presetType]
    });
};

// Save field configuration / Edit Item
window.saveEditFieldConfig = function() {
    // DEBUG: Alert check as requested
    // alert("Save button clicked! Processing...");

    const enabledFields = [];
    const requiredFields = [];
    const updatedName = $('#editCustomerTypeName').val().trim();
    
    // Validate name
    if (!updatedName) {
        showToast('Please enter a name', 'error');
        return;
    }
    
    // Only collect fields if the container is visible (Customer Type)
    const isConfigMode = $('#fieldConfigContainer').is(':visible');
    
    if (isConfigMode) {
        $('.edit-field-enabled:checked').each(function() {
            enabledFields.push($(this).val());
        });
        
        $('.edit-field-required:checked').each(function() {
            requiredFields.push($(this).val());
        });
    }
    
    const requests = [];

    // 1. Rename Request (Always run this if name changed)
    if (updatedName !== currentEditingTypeName) {
        // Force uppercase for consistency with other inputs
        // (The input has text-uppercase class but we force it here too just in case)
        // Actually, let's keep it as is, or uppercase? Previous PromptUpdateChoice forced uppercase.
        // Let's force uppercase to be safe if that's the convention.
        // const finalName = updatedName.toUpperCase(); 
        // User request didn't specify, but maintenance usually implies uppercase for codes/types.
        // Let's trust the input value for now to support mixed case if needed.
        
        requests.push($.ajax({
            url: "../../routes/administrator/maintenance.route.php", type: "POST",
            data: { action: "UpdateChoice", id: currentEditingTypeId, choice: updatedName },
            dataType: "JSON"
        }));
    }

    // 2. Config Save Request (Only if Customer Type)
    if (isConfigMode) {
        console.log('[SaveConfig] Sending:', { id: currentEditingTypeId, name: updatedName, enabled: enabledFields });
        requests.push($.ajax({
            url: '../../routes/administrator/maintenance.route.php', type: 'POST',
            data: {
                action: 'SaveFieldConfig', 
                id: currentEditingTypeId, // Send ID for robust update
                customerType: updatedName, 
                enabledFields: JSON.stringify(enabledFields), 
                requiredFields: JSON.stringify(requiredFields),
                csrf_token: $('input[name="csrf_token"]').val()
            }, dataType: 'JSON'
        }));
    }

    // Execute requests
    if (requests.length === 0) {
        // Nothing changed
        var modalEl = document.getElementById('fieldConfigModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if(modal) modal.hide();
        return;
    }

    Promise.all(requests).then(responses => {
        // Check if any response failed
        const failed = responses.find(r => r.STATUS !== 'success');
        if (failed) {
            showToast(failed.MESSAGE || 'An error occurred while saving', 'error');
        } else {
            const successMsg = responses[0].MESSAGE || `Configuration saved successfully!`;
            showToast(successMsg, 'success');
            console.log('[SaveConfig] Success:', responses);
            
            // Close modal
            var modalEl = document.getElementById('fieldConfigModal');
            var modal = bootstrap.Modal.getInstance(modalEl);
            if(modal) modal.hide();
            
            // IN-PLACE UPDATE (No Reload)
            if (updatedName !== currentEditingTypeName) {
                updateChoiceRowUI(currentEditingTypeId, updatedName);
            }
        }
    }).catch(error => {
        showToast('Request failed. Please try again.', 'error');
        console.error(error);
    });
};

// HELPER: Update Row UI In-Place (Transition & Attribute Update)
function updateChoiceRowUI(id, newName) {
    var $row = $(`#choice-row-${id}`);
    if ($row.length === 0) return;

    // 1. Update Name Text with Fade
    var $nameCell = $row.find('td:eq(0)');
    // Only animate if text changed
    if ($nameCell.text() !== newName) {
        $nameCell.fadeOut(200, function() {
            $(this).text(newName).fadeIn(200);
        });
    }

    // 2. Update Safe Value for Buttons
    var safeValue = newName.replace(/\\/g, "\\\\").replace(/"/g, '&quot;').replace(/'/g, "\\'");

    // 3. Update "Configure/Edit" Button
    var $configBtn = $row.find('button[onclick*="openFieldConfigModal"]');
    if ($configBtn.length > 0) {
        $configBtn.attr('onclick', `openFieldConfigModal(${id}, '${safeValue}'); event.stopPropagation();`);
    }

    // 4. Update "Edit" Button (Old check, can remove if fully migrated, but keeping for safety)
    /*
    var $editBtn = $row.find('button[onclick*="PromptUpdateChoice"]');
    if ($editBtn.length > 0) {
        $editBtn.attr('onclick', `PromptUpdateChoice(${id}, '${safeValue}'); event.stopPropagation();`);
    }
    */
}