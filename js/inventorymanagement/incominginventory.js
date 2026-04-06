var dataInvTbl, dataInvTblValue = "", itemTbl, SelectedFromList = "", SelectedFromDataInv = "", DataInvSINo = "";
var isUpdate = false;
var origSIno = "", origSerial = "", origProduct = "";
var editingItemRow = null;
var isSaving = false;

// Debugging disabled

// Always print a tiny load marker so we can confirm this file is the one running.
var BASE_PATH = window.BASE_PATH || '';
var ROUTE_URL = BASE_PATH + '/routes/inventorymanagement/incominginventory.route.php';
try { console.log("%c[incominginventory.js] loaded", "color:#0d6efd;font-weight:bold"); } catch(e) {}

$(document).ready(function() {
    var invRefreshTimer = null;
    
        
    // Custom DataTables filtering for Date Range
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            // Only apply to the Data Inventory Table
            if (settings.nTable.id !== 'dataInvTbl') {
                return true;
            }

            var min = $('#filterDateFrom').val();
            var max = $('#filterDateTo').val();
            // Purchase Date is at index 7 (0-based) based on the HTML table header order
            // 0:SI, 1:Serial, 2:Prod, 3:Supp, 4:Categ, 5:Type, 6:Branch, 7:PurchaseDate
            var dateStr = data[7] || ''; 
            
            if (!min && !max) return true;

            var date = new Date(dateStr);
            var minDate = min ? new Date(min) : null;
            var maxDate = max ? new Date(max) : null;

            // Invalid date in row -> exclude if filtering is active, or handle as needed
            if (isNaN(date.getTime())) return false;

            if (
                (!minDate || date >= minDate) &&
                (!maxDate || date <= maxDate)
            ) {
                return true;
            }
            return false;
        }
    );

    // Select2 removed - using standard dropdowns

    // Bind Change Events for Dependent Dropdowns
    $('#type').on('change', function() {
        LoadProdCateg($(this).val());
    });
    $('#category').on('change', function() {
        LoadProdName($(this).val());
    });
    $('#product').on('change', function() {
        LoadSupplier($(this).val());
        // Trigger auto-pricing lookup
        AutoFillPricing();
    });

    Initialize();
    InitializeDataTable(); // Initialize table immediately
    (function(){ try { var data = localStorage.getItem('incoming_itemTbl'); if (data) { var arr = JSON.parse(data); if (Array.isArray(arr)) { arr.forEach(function(entry){ itemTbl.row.add(entry).draw(false); }); } } } catch(e){ localStorage.removeItem('incoming_itemTbl'); } })();
    LoadDataInventory();
    // Lock all inputs initially
    $('#inventoryinForm input, #inventoryinForm select, #inventoryinForm textarea').prop('disabled', true);
    lockFormInputs(true);
    
    // Bind to our own Save reference to avoid global `Save()` name collisions with other modules
    window.IncomingInventorySave = Save;
    // Remove inline onclick handlers (they can call a different global Save() from other modules)
    try { $('#save, #updateBtn, #editSaveBtn').removeAttr('onclick'); } catch(e){}
    $('#updateBtn').off('click.incoming').on('click.incoming', function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        incomingDebugLog("updateBtn click handler fired", {disabled: $(this).prop('disabled'), hidden: $(this).prop('hidden')});
        if (DEBUG_INCOMING) { try { Swal.fire({toast:true,position:'top-end',timer:2000,showConfirmButton:false,icon:'info',title:'Update click detected'}); } catch(_e){} }
        window.IncomingInventorySave();
    });
    $('#save').off('click.incoming').on('click.incoming', function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        incomingDebugLog("save button click handler fired", {disabled: $(this).prop('disabled'), hidden: $(this).prop('hidden')});
        if (DEBUG_INCOMING) { try { Swal.fire({toast:true,position:'top-end',timer:2000,showConfirmButton:false,icon:'info',title:'Save click detected'}); } catch(_e){} }
        window.IncomingInventorySave();
    });
    $('#editSaveBtn').off('click.incoming').on('click.incoming', function(e){
        e.preventDefault();
        e.stopImmediatePropagation();
        incomingDebugLog("editSaveBtn click handler fired", {disabled: $(this).prop('disabled'), hidden: $(this).prop('hidden')});
        if (DEBUG_INCOMING) { try { Swal.fire({toast:true,position:'top-end',timer:2000,showConfirmButton:false,icon:'info',title:'Edit-Save click detected'}); } catch(_e){} }
        window.IncomingInventorySave();
    });

    $('#purchaseDate').on('change', function(){
        var val = $(this).val();
        if (val) {
            var d = new Date(val);
            var t = new Date();
            if (d > t) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Future date not allowed',
                });
                var date = t;
                $(this).val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
            }
        }
    });
    $('#dateEncoded').on('change', function(){
        var val = $(this).val();
        if (val) {
            var d = new Date(val);
            var t = new Date();
            if (d > t) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Future date not allowed',
                });
                var date = t;
                $(this).val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
            }
        }
    });

    $('#addToList').on('click', function() {
        console.log("Add to List clicked");
        if (!$.fn.DataTable.isDataTable('#itemTbl')) {
             console.log("Item table not initialized. Attempting to initialize...");
             InitializeDataTable();
        }
        
        if (!$.fn.DataTable.isDataTable('#itemTbl')) {
             Swal.fire("Error", "Item table initialization failed (Code: DT-INIT-FAIL). Please refresh the page.", "error");
             return;
        }
        
        // Ensure itemTbl variable is set correctly
        if (!itemTbl) {
            itemTbl = $('#itemTbl').DataTable();
        }
        
        console.log(DataInvSINo);
        let branch = $('#branch').val();
        let type = $('#type').val();
        let categ = $('#category').val();
        let product = $('#product').val();
        let supplier = $('#supplier').val();
        let supplierSI = $('#suppliersSI').val();
        let serialNo = $('#serialNo').val(); 
        let purchaseDate = $('#purchaseDate').val();
        let warranty = String($('#warranty').val()||'').toUpperCase();
        let imageName = $('#imageName').val();
        
        // Auto-calculate dateEncoded: Current date for new items
        var now = new Date();
        var mm = (now.getMonth() > 8) ? (now.getMonth() + 1) : ('0' + (now.getMonth() + 1));
        var dd = (now.getDate() > 9) ? now.getDate() : ('0' + now.getDate());
        var yyyy = now.getFullYear();
        let dateEncoded = mm + '/' + dd + '/' + yyyy;
        
        let dealerPrice = $('#dealersPrice').val();
        let srp = $('#srp').val();
        let quantity = $('#quantity').val();
        let totalPrice = $('#totalPrice').val();
        let totalSRP = $('#totalSRP').val();
        let mpi = $('#mpi').val();
        let totalMarkup = $('#totalmarkup').val();

        if (branch == null || type == null || categ == null || product == null || supplier == null || supplierSI == "" || serialNo == "" || purchaseDate == "" || warranty == "" || dateEncoded == "" || supplierSI == "" || dealerPrice == "" || srp == "" || quantity == "" || totalPrice == "" || totalSRP == "" || mpi == "" || totalMarkup == "") {
            Swal.fire({
                icon: 'warning',
                text: 'Please enter required details.',
            });
            return;
        }

        var rowData = [
            product,
            serialNo,
            warranty,
            dealerPrice,
            srp,
            quantity,
            totalPrice,
            totalSRP,
            mpi,
            totalMarkup,
            branch,
            type,
            categ,
            supplier,
            supplierSI,
            purchaseDate,
            imageName,
            dateEncoded,
        ];
        if (editingItemRow) {
            itemTbl.row(editingItemRow).data(rowData).draw(false);
            editingItemRow = null;
            // Reset UI after update
            Swal.fire({
                icon: 'success',
                title: 'Item Updated',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
            Cancel(); // Reset form and buttons
            return;
        } else {
            itemTbl.row.add(rowData).draw(false);
        }

        $('#suppliersSI').prop('disabled',false);
        try { localStorage.setItem('incoming_itemTbl', JSON.stringify(itemTbl.rows().data().toArray())); } catch(e) {}
    });

    $('#editBtn').on('click', function() {
        // If a record was selected from Data Inventory, Edit means "update that record".
        // If a record was selected from Item List (staging), Edit means "update that list item".
        
        isUpdate = (SelectedFromDataInv !== "");
        
        // Enable fields
        $('#branch').prop('disabled', false);
        $('#type').prop('disabled', false);
        $('#category').prop('disabled', false);
        $('#product').prop('disabled', false);
        $('#supplier').prop('disabled', false);
        $('#suppliersSI').prop('disabled', false);
        $('#serialNo').prop('disabled', false);   
        $('#purchaseDate').prop('disabled', false);
        $('#warranty').prop('disabled', false);
        $('#imageName').prop('disabled', false);
        $('#dateEncoded').prop('disabled', false);
        $('#dealersPrice').prop('disabled', false);
        $('#srp').prop('disabled', false);
        $('#quantity').prop('disabled', false);

        // Ensure all form inputs are enabled (except readonly ones)
        $('#inventoryinForm input:not([readonly]), #inventoryinForm select, #inventoryinForm textarea').prop('disabled', false);
        
        // Ensure dependent fields are properly enabled if parent is selected
        if ($('#category').val()) { $('#product').prop('disabled', false); }
        if ($('#product').val()) { $('#supplier').prop('disabled', false); }
        
        // Fix: Force reload of dropdowns to ensure all options are available
        var curType = $('#type').val();
        var curCat = $('#category').val();
        var curProd = $('#product').val();
        var curSupp = $('#supplier').val();
        
        if (curType) {
             LoadProdCateg(curType);
             $('#type').data('pendingCategory', curCat);
             $('#category').data('pendingProduct', curProd);
             $('#product').data('pendingSupplier', curSupp);
        } else if (curCat) {
             $('#category').data('pendingProduct', curProd);
             $('#product').data('pendingSupplier', curSupp);
             LoadProdName(curCat);
        }
        
        // Determine correct button state
        if (editingItemRow) {
            // Editing a Staging List Item
            $('#addToList').html('<i class="fa-solid fa-check"></i> Update List'); // Change text
            setButtonState('staging_edit_mode');
        } else {
            // Editing a DB Item
            setButtonState('edit_mode');
        }
    });

    // Safety: if Update is visible, keep it clickable after any edit
    $('#inventoryinForm').on('input change', 'input, select, textarea', function(){
        if (!$('#updateBtn').prop('hidden')) {
            $('#updateBtn').prop('disabled', false).removeAttr('disabled').css('pointer-events','auto');
        }
    });

    // --- Centralized Button State Management ---
    // states: 'new', 'edit', 'add_mode', 'initial'
    window.setButtonState = function(state) {
        console.log("Setting button state to:", state);
        
        // Helper to force show/hide
        const show = (sel) => $(sel).removeAttr('hidden').prop('disabled', false).show().css('display', 'inline-block');
        const hide = (sel) => $(sel).attr('hidden', 'hidden').prop('disabled', true).hide().css('display', 'none');

        if (state === 'initial') {
            show('#addNew');
            hide('#cancel');
            hide('#addToList');
            hide('#save');
            hide('#updateBtn');
            hide('#editBtn');
            hide('#editSaveBtn');
        } 
        else if (state === 'add_mode') {
            hide('#addNew');
            show('#cancel');
            show('#addToList');
            show('#save');
            hide('#updateBtn');
            hide('#editBtn');
            hide('#editSaveBtn');
        }
        else if (state === 'edit_mode') {
            hide('#addNew');
            show('#cancel');
            hide('#addToList');
            hide('#save');
            show('#updateBtn');
            hide('#editBtn'); // We are IN edit mode, so hide the "Edit" button itself if it was for triggering edit
            hide('#editSaveBtn');
        }
        else if (state === 'view_mode') {
             // Selected from Data Inventory
            hide('#addNew');
            show('#cancel');
            hide('#addToList');
            hide('#save');
            hide('#updateBtn');
            show('#editBtn');
            hide('#editSaveBtn');
        }
        else if (state === 'staging_selected') {
            // Selected from Item List (Staging)
            hide('#addNew');
            show('#cancel');
            hide('#addToList');
            show('#save');     // Save to DB
            hide('#updateBtn');
            show('#editBtn');  // Unlock to Edit in List
            hide('#editSaveBtn');
        }
        else if (state === 'staging_edit_mode') {
            // Editing the Item List entry (Unlocked)
            hide('#addNew');
            show('#cancel');
            show('#addToList'); // Acts as "Update List"
            hide('#save');
            hide('#updateBtn');
            hide('#editBtn');
            hide('#editSaveBtn');
        }
    };

    $('#addNew').on('click', function() {
        isUpdate = false;
        lockFormInputs(false);
        $('#branch').val('');
        $('#type').val('');
        
        // Reset dropdowns properly for Select2
        $('#category').empty().append('<option value="" disabled selected>Select</option>').trigger('change');
        $('#product').empty().append('<option value="" disabled selected>Select</option>').trigger('change');
        $('#supplier').empty().append('<option value="" disabled selected>Select</option>').trigger('change');
        
        $('#suppliersSI').prop('disabled', false).val('');
        $('#serialNo').prop('disabled', false).val('');
        $('#purchaseDate').prop('disabled', false);
        $('#warranty').prop('disabled', false).val('');
        $('#imageName').prop('disabled', false).val('');
        $('#dateEncoded').prop('disabled', false);
        $('#dealersPrice').prop('disabled', false).val('');
        $('#srp').prop('disabled', false).val('');
        $('#quantity').prop('disabled', false).val('');
        $('#totalPrice').prop('readonly', true).val('');
        $('#totalSRP').prop('readonly', true).val('');
        $('#mpi').prop('readonly', true).val('');
        $('#totalmarkup').prop('readonly', true).val('');
        
        setButtonState('add_mode');
        
        // Ensure all form inputs are enabled (except readonly ones)
        $('#inventoryinForm input:not([readonly]), #inventoryinForm select, #inventoryinForm textarea').prop('disabled', false);
        $('#suppliersSI').prop('disabled', false);

        // $("#CustomerInfoTbl tbody tr").removeClass("selected");
        var now = new Date();
        var mm = (now.getMonth() > 8) ? (now.getMonth() + 1) : ('0' + (now.getMonth() + 1));
        var dd = (now.getDate() > 9) ? now.getDate() : ('0' + now.getDate());
        var yyyy = now.getFullYear();
        var todayStr = mm + '/' + dd + '/' + yyyy;
        // $('#dateEncoded').val(todayStr); // No longer needed in UI
        $('#purchaseDate').val(todayStr);
        if ($("#branch option").filter(function(){ return $(this).text().toUpperCase() === "HEAD OFFICE"; }).length > 0) {
            $('#branch').val("HEAD OFFICE");
        }
        if ($("#type option").filter(function(){ return $(this).text().toUpperCase() === "WITH VAT"; }).length > 0) {
            $('#type').val("WITH VAT");
            // This will trigger the change event we bound earlier, calling LoadProdCateg
            $('#type').trigger('change'); 
        }
    });

    // Reliable row selection and form population using DataTables API
    $('#dataInvTbl tbody').off('click.rowselect').on('click.rowselect', 'tr', function(e){
        if (!$.fn.DataTable.isDataTable('#dataInvTbl')) return;
        
        // Clear Item List selection to avoid mixed states
        if (SelectedFromList) {
            SelectedFromList = "";
            editingItemRow = null;
            if ($.fn.DataTable.isDataTable('#itemTbl')) {
                $('#itemTbl').DataTable().rows('.selected').nodes().each((row) => { row.classList.remove('selected'); });
            }
            $("#DeleteFromListBtn").attr("disabled",true);
            // Hide Staging buttons
            $('#save').prop('hidden', true).attr('hidden','hidden');
            $('#addToList').prop('hidden', true).attr('hidden','hidden');
        }

        var dt = $('#dataInvTbl').DataTable();
        dt.rows('.selected').nodes().each((row) => { row.classList.remove('selected'); });
        $(this).addClass('selected');
        $("#DeleteFromDataInvBtn").attr("disabled", false);
        var d = dt.row(this).data() || [];
        if (!Array.isArray(d) || d.length === 0) {
            // Fallback to DOM cells if DataTables did not return data
            var cells = $(this).find('td');
            d = [];
            cells.each(function(){ d.push($(this).text().trim()); });
        }
        var d_SIno = d[0] || '', d_SerialNo = d[1] || '', d_Product = d[2] || '', d_Supplier = d[3] || '';
        var d_Category = d[4] || '', d_Type = d[5] || '', d_Branch = d[6] || '';
        
        // Use hidden columns for cleaner access if available
        // Note: DataTables data() usually returns raw array based on column index
        
        // ... (rest of variable assignments) ...
        var d_PurchaseDate = d[7] || '', d_Warranty = d[8] || '', d_DateEncoded = d[9] || '';
        var d_Quantity = d[10] || '', d_DealerPrice = d[11] || '', d_SRP = d[12] || '';
        var d_TotalPrice = d[13] || '', d_TotalSRP = d[14] || '', d_MPI = d[15] || '';
        var d_TotalMarkup = d[16] || '', d_ImgName = d[20] || '';

        itemTbl.clear().draw();
        itemTbl.row.add([
            d_Product, d_SerialNo, d_Warranty, d_DealerPrice, d_SRP, d_Quantity,
            d_TotalPrice, d_TotalSRP, d_MPI, d_TotalMarkup,
            d_Branch, d_Type, d_Category, d_Supplier, d_SIno,
            d_PurchaseDate, d_ImgName, d_DateEncoded
        ]).draw(false);
        // This row is a "view of selected inventory record", not a staging edit row.
        editingItemRow = null;

        $('#branch').val(d_Branch);
        $('#type').val(d_Type);
        
        // Handle Category Select2
        if (d_Category && $('#category option').filter(function(){ return $(this).text() === d_Category; }).length == 0) {
            $('#category').append('<option value="'+d_Category+'">'+d_Category+'</option>');
        }
        $('#category').data('pendingProduct', d_Product);
        $('#product').data('pendingSupplier', d_Supplier);
        $('#category').val(d_Category).trigger('change');
        
        // Product/Supplier will be handled by the trigger('change') + pending data logic
        // We do NOT need to manually set them here because LoadProdName/LoadSupplier are async
        // and will use the pending data to set the value once loaded.
        
        $('#suppliersSI').val(d_SIno);
        $('#serialNo').val(d_SerialNo);
        $('#purchaseDate').val(d_PurchaseDate);
        $('#warranty').val(d_Warranty);
        
        // Preserve original Date Encoded for existing records (hidden field or memory)
        // Since we removed it from UI, we store it in a data attribute on the form or a hidden input
        // But the request was to "remove it to the form" and "automatically set to when the product is encoded"
        // which usually means: New = NOW(), Edit = Original Value.
        // We'll store it in a variable for the Save/Update logic to pick up.
        if (d_DateEncoded) {
             $('#inventoryinForm').data('originalDateEncoded', d_DateEncoded);
        } else {
             $('#inventoryinForm').removeData('originalDateEncoded');
        }
        
        $('#dealersPrice').val(d_DealerPrice);
        $('#srp').val(d_SRP);
        $('#quantity').val(d_Quantity);
        $('#totalPrice').val(d_TotalPrice);
        $('#totalSRP').val(d_TotalSRP);
        $('#mpi').val(d_MPI);
        $('#totalmarkup').val(d_TotalMarkup);

        // Lock fields until Edit and show controls
        lockFormInputs(true);
        $('#inventoryinForm input, #inventoryinForm select, #inventoryinForm textarea').prop('disabled', true);

        isUpdate = true;
        
        setButtonState('view_mode');

        // Keys for update/delete
        origSIno = d_SIno; origSerial = d_SerialNo; origProduct = d_Product;
        dataInvTblValue = d; SelectedFromDataInv = this;
    });

    function lockFormInputs(lock){
        $('#branch').prop('disabled', lock);
        $('#type').prop('disabled', lock);
        $('#category').prop('disabled', lock);
        $('#product').prop('disabled', lock);
        $('#supplier').prop('disabled', lock);
    }

    // Removed duplicate document-level row click handler to improve performance

    $('#itemTbl tbody').on('click', 'tr',function(e){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $("#DeleteFromListBtn").attr("disabled",true);
            SelectedFromList = "";
            editingItemRow = null;
            Cancel(); // Reset form if deselected
        } else {
            // Clear Data Inv selection to avoid mixed states
            if (SelectedFromDataInv) {
                SelectedFromDataInv = "";
                isUpdate = false;
                if ($.fn.DataTable.isDataTable('#dataInvTbl')) {
                    $('#dataInvTbl').DataTable().rows('.selected').nodes().each((row) => { row.classList.remove('selected'); });
                }
                $("#DeleteFromDataInvBtn").attr("disabled",true);
                // Hide Data Inv buttons
                $('#updateBtn').prop('hidden', true).attr('hidden','hidden');
                $('#editSaveBtn').prop('hidden', true).attr('hidden','hidden');
            }

            itemTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            $("#DeleteFromListBtn").attr("disabled",false);
            SelectedFromList = this;
            editingItemRow = this;

            var entry = itemTbl.row(this).data() || [];
            var product = entry[0] || '';
            var serialNo = entry[1] || '';
            var warranty = entry[2] || '';
            var dealerPrice = entry[3] || '';
            var srp = entry[4] || '';
            var quantity = entry[5] || '';
            var totalPrice = entry[6] || '';
            var totalSRP = entry[7] || '';
            var mpi = entry[8] || '';
            var totalMarkup = entry[9] || '';
            var branch = entry[10] || '';
            var type = entry[11] || '';
            var categ = entry[12] || '';
            var supplier = entry[13] || '';
            var supplierSI = entry[14] || '';
            var purchaseDate = entry[15] || '';
           
            var dateEncoded = entry[16] || '';

            $('#branch').val(branch);
            $('#type').val(type);
            if ($('#category option[value="'+categ+'"]').length == 0) {
                $('#category').append('<option value="'+categ+'">'+categ+'</option>');
            }
            $('#category').data('pendingProduct', product);
            $('#product').data('pendingSupplier', supplier);
            $('#category').val(categ).trigger('change');
            if (product && $('#product option[value="'+product+'"]').length == 0) {
                $('#product').append('<option value="'+product+'">'+product+'</option>');
            }
            $('#product').val(product);
            
            if (supplier && $('#supplier option[value="'+supplier+'"]').length == 0) {
                $('#supplier').append('<option value="'+supplier+'">'+supplier+'</option>');
            }
            $('#supplier').val(supplier);
            
            $('#suppliersSI').val(supplierSI);
            $('#serialNo').val(serialNo);
            $('#purchaseDate').val(purchaseDate);
            $('#warranty').val(warranty);
            $('#dateEncoded').val(dateEncoded);
            $('#dealersPrice').val(dealerPrice);
            $('#srp').val(srp);
            $('#quantity').val(quantity);
            $('#totalPrice').val(totalPrice);
            $('#totalSRP').val(totalSRP);
            $('#mpi').val(mpi);
            $('#totalmarkup').val(totalMarkup);

            Compute();

            $('#inventoryinForm input:not([readonly]), #inventoryinForm select, #inventoryinForm textarea').prop('disabled', true);
            
            // Show Save, Edit, Cancel buttons as requested
            setButtonState('staging_selected');
        }
    });

    $('#searchDataInv').on('input', function() {
        var v = (this.value || '').trim();
        if ($.fn.DataTable.isDataTable('#dataInvTbl')) {
            $('#dataInvTbl').DataTable().search(v).draw();
        }
        if (invRefreshTimer) { clearInterval(invRefreshTimer); invRefreshTimer = null; }
        // Disable periodic auto-refresh to reduce lag; manual reload via actions
        // if (v === '') { LoadDataInventory(); }
    });

    $('#dealersPrice, #srp, #quantity').on('input', function() {
        var val = $(this).val();
        // Allow numbers, dots, and commas only
        var clean = val.replace(/[^0-9.,]/g, '');
        if (val !== clean) {
            $(this).val(clean);
        }
        Compute(); 
    });

    $('#dealersPrice, #srp, #quantity').on('blur', function() {
        formatInput(this);
        Compute();
    });

    $('#serialNo').on('input', function() {
        var val = $(this).val();
        // Allow numbers and dashes only
        var clean = val.replace(/[^0-9-]/g, '');
        if (val !== clean) {
            $(this).val(clean);
        }
    });

    $('#warranty').on('input', function() {
        var val = $(this).val();
        // Allow alphanumeric, spaces, and dashes
        var clean = val.replace(/[^a-zA-Z0-9\s-]/g, '');
        if (val !== clean) {
            $(this).val(clean);
        }
    });

});

function Initialize(){
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            $("#branch").empty().append(`<option value="" disabled selected>Select</option>`);
            var addedBranches = new Set();
            
            // Add "HEAD OFFICE" first if not already present in the loop later (or force it here)
            // But usually we just want unique values. 
            
            $.each(response.BRANCH,function(key,value){
                var b = (value["ItemName"] || '').trim();
                var bUpper = b.toUpperCase();
                if (b && !addedBranches.has(bUpper)) {
                    addedBranches.add(bUpper);
                    $("#branch").append(`
                        <option value="${b}">
                            ${b}
                        </option>
                    `);
                }
            });
            
            // Only add default HEAD OFFICE if it wasn't in the database response
            //if (!addedBranches.has("HEAD OFFICE")) {
                //$("#branch").append(`<option value="HEAD OFFICE">HEAD OFFICE</option>`);
            //}

            $("#type").empty().append(`<option value="" disabled selected>Select</option>`);
            var addedTypes = new Set();
            $.each(response.PRODTYPE,function(key,value){
                var t = (value["Type"] || '').trim();
                var tUpper = t.toUpperCase();
                if (t && !addedTypes.has(tUpper)) {
                    addedTypes.add(tUpper);
                    $("#type").append(`
                        <option value="${t}">
                            ${t}
                        </option>
                    `);
                }
            });
            if (!addedTypes.has("WITH VAT")) {
                $("#type").append(`<option value="WITH VAT">WITH VAT</option>`);
            }
            if (!addedTypes.has("NON-VAT")) {
                $("#type").append(`<option value="NON-VAT">NON-VAT</option>`);
            }

            var options = {
                value: new Date(),
                rtl: false,
                format: 'm/d/Y',
                timepicker: false,
                datepicker: true,
                startDate: false,
                closeOnDateSelect: false,
                closeOnTimeSelect: true,
                closeOnWithoutClick: true,
                closeOnInputClick: true,
                openOnFocus: true,
                mask: '99/99/9999',
                maxDate: new Date(),
            };

            $('#purchaseDate').datetimepicker(options);
            // Removed dateEncoded picker as requested
            
            // Initialize Date Filters for Data Inventory
            var filterOptions = {
                timepicker: false,
                datepicker: true,
                format: 'm/d/Y',
                closeOnDateSelect: true,
                scrollMonth: false,
                scrollInput: false
            };
            $('#filterDateFrom').datetimepicker(filterOptions);
            $('#filterDateTo').datetimepicker(filterOptions);
            
            // Trigger redraw on date change
            $('#filterDateFrom, #filterDateTo').on('change', function(){
                if ($.fn.DataTable.isDataTable('#dataInvTbl')) {
                    $('#dataInvTbl').DataTable().draw();
                }
            });

            $('#purchaseDate').on('change', function(){
                var val = $(this).val();
                if (val) {
                    var d = new Date(val);
                    var t = new Date();
                    if (d > t) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Future date not allowed',
                            text: 'Please select a valid date.',
                        });
                        var date = t;
                        $(this).val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
                    }
                }
            });
            $('#dateEncoded').on('change', function(){
                var val = $(this).val();
                if (val) {
                    var d = new Date(val);
                    var t = new Date();
                    if (d > t) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Future date not allowed',
                            text: 'Please select a valid date.',
                        });
                        var date = t;
                        $(this).val(((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' + date.getFullYear());
                    }
                }
            });

            var defaultType = $("#type option:not([disabled])").eq(0).val();
            if (defaultType) {
                $("#type").val(defaultType);
                LoadProdCateg(defaultType);
            }
            
            // Strictly disable all inputs initially to prevent editing before clicking New/Edit
            $('#inventoryinForm input, #inventoryinForm select, #inventoryinForm textarea').prop('disabled', true);
            // Explicitly disable Select2 dropdowns if they exist
            $('#category').prop('disabled', true);
            $('#product').prop('disabled', true);
            $('#supplier').prop('disabled', true);
        }, 
    })
}

function InitializeDataTable() {
    // If already initialized, just set the variable and return
    if ($.fn.DataTable.isDataTable('#itemTbl')) {
        itemTbl = $('#itemTbl').DataTable();
        return;
    }
    
    itemTbl = $('#itemTbl').DataTable({
        destroy: true,
        searching:false,
        ordering:false,
        info:false,
        paging:false,
        lengthChange:false,
        scrollY: '230px',
        scrollX: true,  
        scrollCollapse: true,
        responsive:false,
        columnDefs: [
            { targets: [ 3,4,5,6,7,8,9 ], className: 'dt-right' },
            // { targets: [ 4,5,6,7,8,9,10,11,12 ], visible:false, searchable:false }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove the formatting to get integer data for summation
            var intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            ListTtlDealerPrice = api.column(3).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            ListTtlSRP = api.column(4).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
            
            ListTtlQty = api.column(5).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            ListTtlPrice = api.column(6).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            ListFnlTtlSRP = api.column(7).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            ListTtlMPI = api.column(8).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            ListFnlTtlMarkup = api.column(9).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

            // Update footer
            $(api.column(3).footer()).html(formatAmtVal(ListTtlDealerPrice));
            $(api.column(4).footer()).html(formatAmtVal(ListTtlSRP));
            $(api.column(5).footer()).html(formatAmtVal(ListTtlQty));
            $(api.column(6).footer()).html(formatAmtVal(ListTtlPrice));
            $(api.column(7).footer()).html(formatAmtVal(ListFnlTtlSRP));
            $(api.column(8).footer()).html(formatAmtVal(ListTtlMPI));
            $(api.column(9).footer()).html(formatAmtVal(ListFnlTtlMarkup));
        },
    });
}

function getSortableDate(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('/');
    if (parts.length === 3) {
        return parts[2] + parts[0].padStart(2, '0') + parts[1].padStart(2, '0');
    }
    return '';
}

function LoadDataInventory(){
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadDataInventory"},
        dataType:"JSON",
        cache:false,
        beforeSend:function(){
            // no-op; we'll manage table instance in success
        },
        success:function(response){
            console.log("Inventory load response:", response);
            if (!response || !response.DATAINV) {
                console.error("Invalid response structure:", response);
                return;
            }

            if (response.DATAINVSINO && response.DATAINVSINO.length > 0) {
                DataInvSINo = response.DATAINVSINO[0]['SINo'];
            }

            var rows = [];
            var html = "";
            $.each(response.DATAINV,function(key,value){
                rows.push([
                    value["SIno"] || '',
                    value["Serialno"] || '',
                    value["Product"] || '',
                    value["Supplier"] || '',
                    value["Category"] || '',
                    value["Type"] || '',
                    value["Branch"] || '',
                    value["DatePurchase"] || '',
                    value["Warranty"] || '',
                    value["DateAdded"] || '',
                    value["Quantity"] || '',
                    value["DealerPrice"] || '',
                    value["SRP"] || '',
                    value["TotalPrice"] || '',
                    value["TotalSRP"] || '',
                    value["Markup"] || '',
                    value["TotalMarkup"] || '',
                    value["VatSales"] || '',
                    value["Vat"] || '',
                    value["AmountDue"] || '',
                    value["imgname"] || '',
                ]);
                html += `
                    <tr>
                        <td>${value["SIno"] || ''}</td>
                        <td>${value["Serialno"] || ''}</td>
                        <td>${value["Product"] || ''}</td>
                        <td>${value["Supplier"] || ''}</td>
                        <td>${value["Category"] || ''}</td>
                        <td>${value["Type"] || ''}</td>
                        <td>${value["Branch"] || ''}</td>
                        <td>${value["DatePurchase"] || ''}</td>
                        <td>${value["Warranty"] || ''}</td>
                        <td>${value["DateAdded"] || ''}</td>
                        <td>${value["Quantity"] || ''}</td>
                        <td>${value["DealerPrice"] || ''}</td>
                        <td>${value["SRP"] || ''}</td>
                        <td>${value["TotalPrice"] || ''}</td>
                        <td>${value["TotalSRP"] || ''}</td>
                        <td>${value["Markup"] || ''}</td>
                        <td>${value["TotalMarkup"] || ''}</td>
                        <td>${value["VatSales"] || ''}</td>
                        <td>${value["Vat"] || ''}</td>
                        <td>${value["AmountDue"] || ''}</td>
                        <td>${value["imgname"] || ''}</td>
                    </tr>
                `;
            });
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#dataInvTbl')) {
                var dt = $('#dataInvTbl').DataTable();
                dt.clear();
                dt.rows.add(rows).draw(false);
                dt.search('').draw(false);
                console.log("DataTable updated with " + rows.length + " rows");
                // Try to focus last saved/updated row after refresh
                try {
                    var k = JSON.parse(localStorage.getItem('incoming_last_saved_key') || '{}');
                    if (k && (k.serial || k.si || k.product)) {
                        focusRowByKeys(dt, k.serial, k.si, k.product);
                        localStorage.removeItem('incoming_last_saved_key');
                    }
                } catch(e){}
            } else {
                // Fallback: render without DataTables
                $("#dataInvList").html(html);
                if ($.fn.DataTable) {
                    dataInvTbl = $('#dataInvTbl').DataTable({
                        searching: true,
                        dom: 'lrtip',
                        ordering: true,
                        order: [[ 9, 'desc' ]],
                        info: true,
                        paging: true,
                        pageLength: 15,
                        deferRender: true,
                        stateSave: false,
                        lengthChange: false,
                        // scrollY: ($('#inventoryinForm').closest('.shadow').height() - 120) + 'px',
                        scrollX: true,
                        scrollCollapse: true,
                        responsive: false,
                        processing: true,
                        columnDefs: [
                            {
                                targets: [7, 9],
                                type: 'date',
                                render: function(data, type, row) {
                                    if (type === 'sort') {
                                        return getSortableDate(data);
                                    }
                                    return data;
                                }
                            }
                        ]
                    });
                    dataInvTbl.search('').draw(false);
                    try {
                        var k2 = JSON.parse(localStorage.getItem('incoming_last_saved_key') || '{}');
                        if (k2 && (k2.serial || k2.si || k2.product)) {
                            focusRowByKeys(dataInvTbl, k2.serial, k2.si, k2.product);
                            localStorage.removeItem('incoming_last_saved_key');
                        }
                    } catch(e){}
                }
            }

            // Removed print button toggling
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.log(xhr.responseText);
            Swal.fire({icon:'error',title:'Load failed',text:(xhr && xhr.responseText) ? xhr.responseText : (error || status)});
        } 
    })
}

function LoadProdCateg (type){
    if (!type) return;
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadProdCateg", type:type},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            var target = $("#category");
            target.empty().append(`<option value="" disabled selected>Select</option>`);
            
            var addedCategs = new Set();
            $.each(response.PRODCATEG,function(key,value){
                var c = (value["Category"] || '').trim();
                var cUpper = c.toUpperCase();
                if (c && !addedCategs.has(cUpper)) {
                    addedCategs.add(cUpper);
                    target.append(`
                        <option value="${c}">
                            ${c}
                        </option>
                    `);
                }
            });
            var requiredCategories = ["Battery","Cable","Cartridge","Connector"];
            requiredCategories.forEach(function(c){
                if (!addedCategs.has(c.toUpperCase())) {
                    addedCategs.add(c.toUpperCase());
                    target.append(`<option value="${c}">${c}</option>`);
                }
            });
            
            // SECURITY FIX: Only enable if NOT in initial state (i.e., New or Edit is active)
            // We check if 'addNew' is hidden. If hidden -> we are in Add/Edit/View mode -> enable.
            // If visible -> we are in Initial mode -> disable.
            if ($('#addNew').prop('hidden')) {
                target.prop('disabled', false); 
            } else {
                target.prop('disabled', true);
            }
            
            // Trigger change to update Select2
            target.trigger('change');

            $("#product").empty().append(`<option value="" disabled selected>Select</option>`).trigger('change');
            $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`).trigger('change');
            
            // Restore Pending Category if exists
            var pendingCategory = $('#type').data('pendingCategory');
            var matched = false;
            
            if (pendingCategory) {
                var targetStr = String(pendingCategory).trim().toUpperCase();
                $("#category option").each(function(){
                    if (String($(this).val()).trim().toUpperCase() === targetStr) {
                        $("#category").val($(this).val()).trigger('change');
                        matched = true;
                        return false; 
                    }
                });
                
                if (matched) {
                    $('#type').removeData('pendingCategory');
                }
            }
            
            if (!matched && pendingCategory) {
                // If pending category exists but not in list, add it to preserve selection
                target.append($('<option>', {value: pendingCategory, text: pendingCategory}));
                target.val(pendingCategory).trigger('change');
                $('#type').removeData('pendingCategory');
            }
        }, 
    })
}

function LoadProdName (categ){
    if (!categ) {
        $("#product").empty().append(`<option value="" disabled selected>Select</option>`).trigger('change');
        $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`).trigger('change');
        return;
    }
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadProdName", categ:categ},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            var target = $("#product");
            target.empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.PRODUCT,function(key,value){
                target.append(
                    $('<option></option>')
                        .val(value["Product"])
                        .text(value["Product"])
                );
            });
            
            // Only enable product if category is enabled AND we are not in initial state
            if (!$('#category').prop('disabled') && $('#addNew').prop('hidden')) {
                target.prop('disabled', false);
            } else {
                target.prop('disabled', true);
            }

            $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`).trigger('change');
            
            var pendingProduct = $('#category').data('pendingProduct');
            var matched = false;
            
            if (pendingProduct) {
                // Robust comparison: Trim and case-insensitive check
                var targetStr = String(pendingProduct).trim().toUpperCase();
                
                $("#product option").each(function(){
                    if (String($(this).val()).trim().toUpperCase() === targetStr) {
                        $("#product").val($(this).val()).trigger('change');
                        matched = true;
                        return false; // break loop
                    }
                });
                
                if (matched) {
                    $('#category').removeData('pendingProduct');
                }
            }
            
            if (!matched) {
                if (pendingProduct) {
                    // Fix: If pending product exists but not in list, add it to preserve selection
                    // This prevents switching to the "first" product erroneously
                    target.append($('<option>', {value: pendingProduct, text: pendingProduct}));
                    target.val(pendingProduct).trigger('change');
                    $('#category').removeData('pendingProduct');
                } else {
                    var firstProduct = $("#product option:not([disabled])").eq(0).val();
                    if (firstProduct) {
                        $("#product").val(firstProduct).trigger('change');
                    }
                }
            }
        }, 
    })
}

function LoadSupplier (productname){
    if (!productname) {
         $("#supplier").empty().append(`<option value="" disabled selected>Select</option>`).trigger('change');
         return;
    }
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadSupplier", productname:productname},
        dataType:"JSON",
        beforeSend:function(){
        },
        success:function(response){
            var target = $("#supplier");
            target.empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.SUPPLIER,function(key,value){
                target.append(`
                    <option value="${value["Supplier"]}">
                        ${value["Supplier"]}
                    </option>
                `);
            });
            
            // Only enable supplier if product is enabled AND we are not in initial state
            if (!$('#product').prop('disabled') && $('#addNew').prop('hidden')) {
                target.prop('disabled', false);
            } else {
                target.prop('disabled', true);
            }

            $('#supplier').off('change.autofill').on('change.autofill', function(){
                AutoFillPricing();
                LoadSupplierSI();
            });
            
            var pendingSupplier = $('#product').data('pendingSupplier');
            var matched = false;
            
            if (pendingSupplier) {
                // Robust comparison: Trim and case-insensitive check
                var targetStr = String(pendingSupplier).trim().toUpperCase();
                
                $("#supplier option").each(function(){
                    if (String($(this).val()).trim().toUpperCase() === targetStr) {
                        $("#supplier").val($(this).val()).trigger('change');
                        matched = true;
                        return false; // break loop
                    }
                });
                
                if (matched) {
                    $('#product').removeData('pendingSupplier');
                }
            }
            
            if (!matched) {
                if (pendingSupplier) {
                    // Fix: If pending supplier exists but not in list, add it
                    target.append($('<option>', {value: pendingSupplier, text: pendingSupplier}));
                    target.val(pendingSupplier).trigger('change');
                    $('#product').removeData('pendingSupplier');
                } else {
                    var firstSupplier = $("#supplier option:not([disabled])").eq(0).val();
                    if (firstSupplier) {
                        $("#supplier").val(firstSupplier).trigger('change');
                    }
                }
            }
        }, 
    })
}

function LoadSupplierSI(){
    var product = $("#product").val();
    var supplier = $("#supplier").val();
    if (!product || !supplier) return;
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadSupplierSI", product:product, supplier:supplier},
        dataType:"JSON",
        success:function(response){
            $("#suppliersSI").empty().append(`<option value="" disabled selected>Select</option>`);
            $.each(response.SUPPLIERSI,function(key,value){
                $("#suppliersSI").append(`<option value="${value["SIno"]}">${value["SIno"]}</option>`);
            });
            var firstSI = $("#suppliersSI option:not([disabled])").eq(0).val();
            if (firstSI) {
                $("#suppliersSI").val(firstSI);
            }
            AutoFillPricing();
            Compute();
        }
    });
}

function AutoFillPricing(){
    var product = $("#product").val();
    var supplier = $("#supplier").val();
    if (!product || !supplier) return;
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"GetProductPricing", product:product, supplier:supplier},
        dataType:"JSON",
        success:function(response){
            var p = response.PRICING || {};
            var d = p.DealerPrice ? formatAmtVal(p.DealerPrice) : $("#dealersPrice").val();
            var s = p.SRP ? formatAmtVal(p.SRP) : $("#srp").val();
            if (!s || s.trim() === '') { s = d; }
            $("#dealersPrice").val(d);
            $("#srp").val(s);
            Compute();
        }
    });
}

function Compute() {
    try {
        var dealerPriceStr = $('#dealersPrice').val() || '0';
        var dealerPrice = parseFloat(dealerPriceStr.replace(/,/g, '')) || 0;
        
        var srpField = $('#srp');
        var srpVal = srpField.val();
        if (!srpVal || srpVal.trim() === '') {
            srpField.val(formatAmtVal(dealerPrice));
            srpVal = srpField.val();
        }
        var srp = parseFloat(srpVal.replace(/,/g, '')) || 0;
        
        var qtyStr = $('#quantity').val() || '0';
        var quantity = parseFloat(qtyStr.replace(/,/g, '')) || 0;

        if (quantity < 0) {
            Swal.fire({
                icon: 'warning',
                text: 'Negative amount is not allowed. Try Again!',
            });
            $('#quantity').val('');
            return;
        }

        var Total = dealerPrice * quantity;
        var TotalSRP = srp * quantity;
        $('#totalPrice').val(formatAmtVal(Total));
        $('#totalSRP').val(formatAmtVal(TotalSRP));
        var mpi  = srp - dealerPrice;
        $('#mpi').val(formatAmtVal(mpi));
        var totalMarkup = TotalSRP - Total; 
        $('#totalmarkup').val(formatAmtVal(totalMarkup));
    } catch (err) {
        console.error("Compute error:", err);
    }
}

// NOTE: Removed duplicate `#addToList` and `#addNew` click handlers (they were defined earlier
// inside `$(document).ready(...)` and could cause conflicting button enabled/hidden states).

function Cancel(){
    console.log("Cancel() called"); 
    
    // --- 1. HIDE BUTTONS IMMEDIATELY (Before any potential errors) ---
    // Using window.setButtonState if available, else manual fallback
    if (typeof window.setButtonState === 'function') {
        window.setButtonState('initial');
    }
    // Redundant force-hide to be absolutely sure
    $('#cancel').hide().css('display', 'none');
    $('#addToList').hide().css('display', 'none');
    $('#addToList').html('<i class="fa-solid fa-plus"></i> Add to List'); // Reset text
    $('#save').hide().css('display', 'none');
    $('#updateBtn').hide().css('display', 'none');
    $('#editSaveBtn').hide().css('display', 'none');
    $('#editBtn').hide().css('display', 'none');
    $('#addNew').show().css('display', 'inline-block');

    // --- 2. RESET VARIABLES ---
    isUpdate = false;
    isSaving = false;
    editingItemRow = null;
    SelectedFromList = "";
    SelectedFromDataInv = "";
    $("#DeleteFromListBtn").attr("disabled",true);
    $("#DeleteFromDataInvBtn").attr("disabled",true);

    // --- 3. CLEAR FORM ---
    $('#branch').prop('disabled', true).val('');
    $('#type').prop('disabled', true).val('');
    
    // Clear Select2 Dropdowns properly
    $('#category').empty().append('<option value="" disabled selected>Select</option>').trigger('change').prop('disabled', true);
    $('#product').empty().append('<option value="" disabled selected>Select</option>').trigger('change').prop('disabled', true);
    $('#supplier').empty().append('<option value="" disabled selected>Select</option>').trigger('change').prop('disabled', true);
    
    $('#suppliersSI').prop('disabled', true).val('');
    $('#serialNo').prop('disabled', true).val('');
    $('#purchaseDate').prop('disabled', true).val('');
    $('#warranty').prop('disabled', true).val('');
    $('#imageName').prop('disabled', true).val('');
    $('#dateEncoded').prop('disabled', true).val('');
    $('#dealersPrice').prop('disabled', true).val('');
    $('#srp').prop('disabled', true).val('');
    $('#quantity').prop('disabled', true).val('');
    $('#totalPrice').prop('readonly', true).val('');
    $('#totalSRP').prop('readonly', true).val('');
    $('#mpi').prop('readonly', true).val('');
    $('#totalmarkup').prop('readonly', true).val('');
    
    // Disable all inputs again
    $('#inventoryinForm input, #inventoryinForm select, #inventoryinForm textarea').prop('disabled', true);
    
    // FIX: Removed lockFormInputs(true) call because it is not globally defined and causes a crash
    // The line above ($('#inventoryinForm input...').prop('disabled', true)) already covers it.
    
    // Reset DataTables Selection
    if ($.fn.DataTable.isDataTable('#dataInvTbl')) {
        $('#dataInvTbl').DataTable().rows('.selected').nodes().each((row) => { row.classList.remove('selected'); });
    }
    
    // $("#CustomerInfoTbl tbody tr").removeClass("selected");
    try { localStorage.removeItem('incoming_itemTbl'); } catch(e) {}
}

function DeleteFromDataInv(){
    if  (SelectedFromDataInv != "") {
        var SINo = dataInvTblValue[0];
        var SerialNo = dataInvTblValue[1];
        var Product = dataInvTblValue[2];
        Swal.fire({
            icon: 'warning',
            title: 'Warning!',
            text: 'This action will delete the selected data from the inventory. Are you sure you want to proceed?',
            preConfirm: function() {
                return $.ajax({
                    url: ROUTE_URL,
                    type: "POST",
                    data: {action:"DeleteFromDataInv", SINo:SINo, SerialNo:SerialNo, Product:Product},
                    dataType: 'JSON',
                    beforeSend: function() {
                        console.log('Processing Request...')
                    },
                    success: function(response) {
                    },
                });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'success') {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: result.value.MESSAGE,
                    });
                    LoadDataInventory();
                    Cancel();
                    SelectedFromDataInv = "";
                    $("#DeleteFromDataInvBtn").attr("disabled",true);

                } else if (result.value.STATUS != 'success') {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: result.value.MESSAGE,
                    });
                }                
            }
        });
    } else {
        SelectedFromDataInv = "";
        Swal.fire({
            icon: 'warning',
            title: 'Please select from the Data Inventory to delete',
        })
    }
}

function ClearFilter() {
    $('#filterDateFrom').val('');
    $('#filterDateTo').val('');
    $('#searchDataInv').val('');
    
    if ($.fn.DataTable.isDataTable('#dataInvTbl')) {
        var table = $('#dataInvTbl').DataTable();
        table.search('').draw();
    }
}


function DeleteFromList(){
    if  (SelectedFromList != "") {
        itemTbl.row(SelectedFromList).remove().draw(false);
        SelectedFromList = "";
        $("#DeleteFromListBtn").attr("disabled",true);
        try { localStorage.setItem('incoming_itemTbl', JSON.stringify(itemTbl.rows().data().toArray())); } catch(e) {}
    } else {
        SelectedFromList = "";
        Swal.fire({
            icon: 'warning',
            title: 'Please select from the Items to delete',
        })
    }
}

function Save(){
    console.log("Save clicked");
    incomingDebugLog("Save() clicked", {
        isSaving: isSaving,
        isUpdate: isUpdate,
        SelectedFromDataInv: (SelectedFromDataInv !== ""),
        orig: {si: origSIno, serial: origSerial, product: origProduct}
    });
    if (isSaving) {
        // Avoid "dead button" feel if something previously failed to reset state
        try { Swal.fire({icon:'info', title:'Please wait', text:'A save/update is still being processed.'}); } catch(e){}
        return;
    }
    isSaving = true;
    $('#save, #updateBtn, #editSaveBtn').prop('disabled', true);
    // Watchdog: if server hangs, unblock UI so you can retry and we can see logs.
    try {
        if (window._incomingSaveWatchdog) { clearTimeout(window._incomingSaveWatchdog); }
        window._incomingSaveWatchdog = setTimeout(function(){
            if (isSaving) {
                incomingDebugLog("WATCHDOG fired (still saving after 60s) - unlocking UI");
                isSaving = false;
                try { $('#save, #updateBtn, #editSaveBtn').prop('disabled', false); } catch(e){}
                try { Swal.fire({icon:'warning', title:'Request timeout', text:'No server response. Open DevTools → Network/Console for details.'}); } catch(e){}
            }
        }, 60000);
    } catch(e){}

    try {
        if (!$.fn.DataTable.isDataTable('#itemTbl')) {
             console.log("Item table not initialized. Attempting to initialize...");
             InitializeDataTable();
        }

        if (!$.fn.DataTable.isDataTable('#itemTbl')) {
             Swal.fire("Error", "Item table initialization failed (Code: DT-SAVE-FAIL). Please refresh.", "error");
             isSaving = false;
             $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
             return;
        }

        if (!itemTbl) {
            itemTbl = $('#itemTbl').DataTable();
        }

    // Save the currently edited item from itemTbl directly to DB
    if (editingItemRow) {
        let branch = String($('#branch').val()||'').toUpperCase();
        let type = String($('#type').val()||'').toUpperCase();
        let categ = String($('#category').val()||'').toUpperCase();
        let product = String($('#product').val()||'').toUpperCase();
        let supplier = String($('#supplier').val()||'').toUpperCase();
        let supplierSI = $('#suppliersSI').val();
        let serialNo = String($('#serialNo').val()||'').toUpperCase();
        let purchaseDate = $('#purchaseDate').val();
        let warranty = String($('#warranty').val()||'').toUpperCase();
        let imageName = $('#imageName').val();
        
        // Auto-calculate dateEncoded: Current date for new items
        var now = new Date();
        var mm = (now.getMonth() > 8) ? (now.getMonth() + 1) : ('0' + (now.getMonth() + 1));
        var dd = (now.getDate() > 9) ? now.getDate() : ('0' + now.getDate());
        var yyyy = now.getFullYear();
        let dateEncoded = mm + '/' + dd + '/' + yyyy;
        
        let dealerPrice = $('#dealersPrice').val();
        let srp = $('#srp').val();
        let quantity = $('#quantity').val();
        let totalPrice = $('#totalPrice').val();
        let totalSRP = $('#totalSRP').val();
        let mpi = $('#mpi').val();
        let totalMarkup = $('#totalmarkup').val();

        if (branch == null || type == null || categ == null || product == null || supplier == null || supplierSI == "" || serialNo == "" || purchaseDate == "" || warranty == "" || dateEncoded == "" || dealerPrice == "" || srp == "" || quantity == "") {
            Swal.fire({ icon:'warning', text:'Please enter required details.' });
            isSaving = false;
            $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
            return;
        }

        // Prevent duplicate by checking existing rows
        try {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#dataInvTbl')) {
                var dt = $('#dataInvTbl').DataTable();
                var exists = false;
                dt.rows().every(function(){
                    var r = this.data();
                    var dsi = String(r[0]||'').trim().toUpperCase();
                    var dserial = String(r[1]||'').trim().toUpperCase();
                    var dprod = String(r[2]||'').trim().toUpperCase();
                    if ((supplierSI && dsi === String(supplierSI).toUpperCase()) &&
                        (serialNo && dserial === serialNo) &&
                        (product && dprod === product)) { exists = true; return false; }
                });
                if (exists) {
                    isSaving = false;
                    $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                    Swal.fire({icon:'warning',title:'Duplicate',text:'This item already exists in inventory.'});
                    return;
                }
            }
        } catch(e){}

        let formData = new FormData();
        formData.append("action", "SaveSingle");
        formData.append("branch", branch);
        formData.append("type", type);
        formData.append("categ", categ);
        formData.append("product", product);
        formData.append("supplier", supplier);
        formData.append("supplierSI", supplierSI);
        formData.append("serialNo", serialNo);
        formData.append("purchaseDate", purchaseDate);
        formData.append("warranty", warranty);
        formData.append("dateEncoded", dateEncoded);
        formData.append("dealerPrice", dealerPrice);
        formData.append("srp", srp);
        formData.append("quantity", quantity);
        formData.append("totalPrice", totalPrice);
        formData.append("totalSRP", totalSRP);
        formData.append("mpi", mpi);
        formData.append("totalMarkup", totalMarkup);
        let fileInput = $('#imageName')[0];
        if (fileInput && fileInput.files.length > 0) {
            formData.append("imageName", fileInput.files[0]);
        }

        Swal.fire({
            icon: 'info',
            title: 'Save this item to inventory?',
            showCancelButton: true,
            confirmButtonText: 'Yes, save',
            preConfirm: function() {
                return $.ajax({
                    url: ROUTE_URL,
                    type: "POST",
                    data: formData,
                    dataType: 'JSON',
                    processData: false,
                    contentType: false,
                    error: function(xhr, status, error) {
                        var msg = (xhr && xhr.responseText) ? xhr.responseText : (error || status);
                        Swal.showValidationMessage(`Request failed: ${msg}`);
                    },
                }).then(function(resp){ return resp; });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'success') {
                    Swal.fire("Success", result.value.MESSAGE, "success");
                    // Remove saved item from staging list
                    itemTbl.row(editingItemRow).remove().draw(false);
                    try { localStorage.setItem('incoming_itemTbl', JSON.stringify(itemTbl.rows().data().toArray())); } catch(e) {}
                    editingItemRow = null;
                    var savedSINo = supplierSI;
                    var savedSerial = serialNo;
                    var savedProduct = product;
                    LoadDataInventory();
                    setTimeout(function(){
                        try {
                            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#dataInvTbl')) {
                                var dt = $('#dataInvTbl').DataTable();
                                // Persist keys so LoadDataInventory can focus after full refresh
                                localStorage.setItem('incoming_last_saved_key', JSON.stringify({serial: savedSerial, si: savedSINo, product: savedProduct}));
                                // Try immediate focus without waiting a second refresh
                                focusRowByKeys(dt, savedSerial, savedSINo, savedProduct);
                            }
                        } catch(e){}
                        Cancel();
                    }, 400);
                    isSaving = false;
                } else {
                    Swal.fire("Error", result.value.MESSAGE, "error");
                    isSaving = false;
                    $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                }                
            } else {
                isSaving = false;
                $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
            }
        });
        return;
    }

    if (isUpdate) {
        let branch = String($('#branch').val()||'').toUpperCase();
        let type = String($('#type').val()||'').toUpperCase();
        let categ = String($('#category').val()||'').toUpperCase();
        let product = String($('#product').val()||'').toUpperCase();
        let supplier = String($('#supplier').val()||'').toUpperCase();
        let supplierSI = $('#suppliersSI').val();
        let serialNo = String($('#serialNo').val()||'').toUpperCase();
        let purchaseDate = $('#purchaseDate').val();
        let warranty = String($('#warranty').val()||'').toUpperCase();
        let imageName = $('#imageName').val();
        
        // Use preserved Date Encoded if available (from database), otherwise fallback to current date (though update usually implies existing)
        let dateEncoded = $('#inventoryinForm').data('originalDateEncoded');
        if (!dateEncoded) {
             // Fallback if missing
            var now = new Date();
            var mm = (now.getMonth() > 8) ? (now.getMonth() + 1) : ('0' + (now.getMonth() + 1));
            var dd = (now.getDate() > 9) ? now.getDate() : ('0' + now.getDate());
            var yyyy = now.getFullYear();
            dateEncoded = mm + '/' + dd + '/' + yyyy;
        }
        
        let dealerPrice = $('#dealersPrice').val();
        let srp = $('#srp').val();
        let quantity = $('#quantity').val();
        let totalPrice = $('#totalPrice').val();
        let totalSRP = $('#totalSRP').val();
        let mpi = $('#mpi').val();
        let totalMarkup = $('#totalmarkup').val();

        // Prevent duplicate by checking existing rows (exclude the row currently being updated)
        try {
            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#dataInvTbl')) {
                var dt = $('#dataInvTbl').DataTable();
                var exists = false;
                var origSI = String(origSIno || '').trim().toUpperCase();
                var origSer = String(origSerial || '').trim().toUpperCase();
                var origProd = String(origProduct || '').trim().toUpperCase();
                dt.rows().every(function(){
                    var r = this.data();
                    var dsi = String(r[0]||'').trim().toUpperCase();
                    var dserial = String(r[1]||'').trim().toUpperCase();
                    var dprod = String(r[2]||'').trim().toUpperCase();
                    // Skip the current record (otherwise it always "duplicates" itself)
                    if (origSI && origSer && origProd &&
                        dsi === origSI && dserial === origSer && dprod === origProd) {
                        return;
                    }
                    if ((supplierSI && dsi === String(supplierSI).toUpperCase()) &&
                        (serialNo && dserial === serialNo) &&
                        (product && dprod === product)) { exists = true; return false; }
                });
                if (exists) {
                    isSaving = false;
                    $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                    Swal.fire({icon:'warning',title:'Duplicate',text:'This item already exists in inventory.'});
                    return;
                }
            }
        } catch(e){}

        let formData = new FormData();
        formData.append("action", "UpdateInventory");
        formData.append("branch", branch);
        formData.append("type", type);
        formData.append("categ", categ);
        formData.append("product", product);
        formData.append("supplier", supplier);
        formData.append("supplierSI", supplierSI);
        formData.append("serialNo", serialNo);
        formData.append("purchaseDate", purchaseDate);
        formData.append("warranty", warranty);
        formData.append("dateEncoded", dateEncoded);
        formData.append("dealerPrice", dealerPrice);
        formData.append("srp", srp);
        formData.append("quantity", quantity);
        formData.append("totalPrice", totalPrice);
        formData.append("totalSRP", totalSRP);
        formData.append("mpi", mpi);
        formData.append("totalMarkup", totalMarkup);
        formData.append("origSIno", origSIno);
        formData.append("origSerial", origSerial);
        formData.append("origProduct", origProduct);

        let fileInput = $('#imageName')[0];
        if (fileInput.files.length > 0) {
            formData.append("imageName", fileInput.files[0]);
        }

        Swal.fire({
            icon: 'info',
            title: 'Update product details?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonText: 'Yes, update!',
            preConfirm: function() {
                incomingDebugLog("UpdateInventory preConfirm()", {
                    supplierSI: supplierSI, serialNo: serialNo, product: product,
                    origSIno: origSIno, origSerial: origSerial, origProduct: origProduct
                });
                return $.ajax({
                    url: ROUTE_URL,
                    type: "POST",
                    data: formData,
                    dataType: 'JSON',
                    processData: false,
                    contentType: false,
                    timeout: 20000,
                    error: function(xhr, status, error) {
                        var msg = (xhr && xhr.responseText) ? xhr.responseText : (error || status);
                        incomingDebugLog("UpdateInventory ajax error", {
                            status: status,
                            error: error,
                            http: xhr ? xhr.status : 0,
                            resp: (xhr && xhr.responseText) ? String(xhr.responseText).slice(0, 500) : ""
                        });
                        Swal.showValidationMessage(`Request failed: ${msg}`);
                    },
                }).then(function(resp){
                    incomingDebugLog("UpdateInventory ajax success", resp);
                    try { if (window._incomingSaveWatchdog) { clearTimeout(window._incomingSaveWatchdog); } } catch(e){}
                    return resp;
                });
            },
        }).then(function(result) {
            incomingDebugLog("UpdateInventory swal result", (result && result.value) ? result.value : result);
            if (result.isConfirmed) {
                if (result.value.STATUS == 'success') {
                    Swal.fire("Success", result.value.MESSAGE, "success");
                    // Persist keys so `LoadDataInventory()` can focus the updated row after refresh
                    try { localStorage.setItem('incoming_last_saved_key', JSON.stringify({serial: serialNo, si: supplierSI, product: product})); } catch(e){}
                    // Refresh the "original keys" so repeated updates (without reselect) still work
                    origSIno = supplierSI;
                    origSerial = serialNo;
                    origProduct = product;
                    // Reset saving state BEFORE doing anything else that might throw
                    isSaving = false;
                    $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                    try { if (window._incomingSaveWatchdog) { clearTimeout(window._incomingSaveWatchdog); } } catch(e){}
                    LoadDataInventory();
                    Cancel();
                } else {
                    Swal.fire("Error", result.value.MESSAGE, "error");
                    isSaving = false;
                    $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                    try { if (window._incomingSaveWatchdog) { clearTimeout(window._incomingSaveWatchdog); } } catch(e){}
                }                
            } else {
                isSaving = false;
                $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                try { if (window._incomingSaveWatchdog) { clearTimeout(window._incomingSaveWatchdog); } } catch(e){}
            }
        });
        return;
    }

    if(itemTbl.rows().count() === 0){
        // When Item List is empty, this will encode the details in Particulars
        let branch = String($('#branch').val()||'').toUpperCase();
        let type = String($('#type').val()||'').toUpperCase();
        let categ = String($('#category').val()||'').toUpperCase();
        let product = String($('#product').val()||'').toUpperCase();
        let supplier = String($('#supplier').val()||'').toUpperCase();
        let supplierSI = $('#suppliersSI').val();
        let serialNo = String($('#serialNo').val()||'').toUpperCase();
        let purchaseDate = $('#purchaseDate').val();
        let warranty = String($('#warranty').val()||'').toUpperCase();
        let imageName = $('#imageName').val();
        
        // Auto-calculate dateEncoded: Current date for new items
        var now = new Date();
        var mm = (now.getMonth() > 8) ? (now.getMonth() + 1) : ('0' + (now.getMonth() + 1));
        var dd = (now.getDate() > 9) ? now.getDate() : ('0' + now.getDate());
        var yyyy = now.getFullYear();
        let dateEncoded = mm + '/' + dd + '/' + yyyy;
        
        let dealerPrice = $('#dealersPrice').val();
        let srp = $('#srp').val();
        let quantity = $('#quantity').val();
        let totalPrice = $('#totalPrice').val();
        let totalSRP = $('#totalSRP').val();
        let mpi = $('#mpi').val();
        let totalMarkup = $('#totalmarkup').val();

        if (branch == null || type == null || categ == null || product == null || supplier == null || supplierSI == "" || serialNo == "" || purchaseDate == "" || warranty == "" || dateEncoded == "" || supplierSI == "" || dealerPrice == "" || srp == "" || quantity == "") {
            Swal.fire({
                icon: 'warning',
                text: 'Please enter required details.',
            });
            isSaving = false;
            $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
            return;
        }

        let formData = new FormData();
        formData.append("action", "SaveSingle");
        formData.append("branch", branch);
        formData.append("type", type);
        formData.append("categ", categ);
        formData.append("product", product);
        formData.append("supplier", supplier);
        formData.append("supplierSI", supplierSI);
        formData.append("serialNo", serialNo);
        formData.append("purchaseDate", purchaseDate);
        formData.append("warranty", warranty);
        formData.append("dateEncoded", dateEncoded);
        formData.append("dealerPrice", dealerPrice);
        formData.append("srp", srp);
        formData.append("quantity", quantity);
        formData.append("totalPrice", totalPrice);
        formData.append("totalSRP", totalSRP);
        formData.append("mpi", mpi);
        formData.append("totalMarkup", totalMarkup);

        let fileInput = $('#imageName')[0];
        if (fileInput.files.length > 0) {
            formData.append("imageName", fileInput.files[0]);
        }

        Swal.fire({
            icon: 'info',
            title: 'Ready to save the product details?',
            showCancelButton: true, // Add cancel button for better UX
            preConfirm: function() {
                return $.ajax({
                    url: ROUTE_URL,
                    type: "POST",
                    // data: {action:"SaveSingle", branch:branch,type:type,categ:categ,product:product,supplier:supplier,supplierSI:supplierSI,serialNo:serialNo,purchaseDate:purchaseDate,warranty:warranty,imageName:imageName,dateEncoded:dateEncoded,dealerPrice:dealerPrice,srp:srp,quantity:quantity,totalPrice:totalPrice,totalSRP:totalSRP,mpi:mpi,totalMarkup:totalMarkup},
                    // dataType: 'JSON',
                    data: formData,
                    dataType: 'JSON',
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        console.log('Processing Request...')
                    },
                    success: function(response) {
                        return response;
                    },
                    error: function(xhr, status, error) {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    }
                });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                var response = result.value;
                if (response && response.STATUS == 'success') {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.MESSAGE,
                    });
                    // Persist keys and reload from DB to guarantee server-computed fields (VAT, VatSales, AmountDue)
                    try { localStorage.setItem('incoming_last_saved_key', JSON.stringify({serial: serialNo, si: supplierSI, product: product})); } catch(e){}
                    LoadDataInventory();
                    Cancel();
                    isSaving = false;
                } else if (response && response.STATUS != 'success') {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: response.MESSAGE || "Unknown error occurred",
                    });
                    isSaving = false;
                    $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                }                
            } else {
                isSaving = false;
                $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
            }
        });

    } else {
        // When Item List is not empty, this will encode the Item List and skip what's left in Particulars
        let Data = itemTbl.rows().data().toArray();
    
        let formdata = new FormData();
        formdata.append("action","SaveAll");
        formdata.append("DATA",JSON.stringify(Data));

        Swal.fire({
            icon: 'question',
            title: 'Ready to save all product details?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Yes, proceed!',
            // allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: ROUTE_URL,
                    type: "POST",
                    data: formdata,
                    processData: false,
                    contentType: false,
                    dataType: 'JSON',
                    beforeSend: function() {
                        console.log('Processing Request...')
                    },
                    success: function(response) {
                        // Return response to be handled in .then()
                        return response; 
                    },
                    error: function(xhr, status, error) {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    }
                });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                // Check if the result.value contains the response from AJAX
                var response = result.value;
                if (response && response.STATUS == 'success') {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.MESSAGE,
                    });
                    LoadDataInventory();
                    Cancel();
                    itemTbl.clear().draw(false);
                    isSaving = false;
                } else if (response && response.STATUS != 'success') {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: response.MESSAGE || "Unknown error occurred",
                    });
                    isSaving = false;
                    $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                } else {
                     // Fallback for cases where response might not be structured as expected
                     // or if the AJAX failed silently but didn't trigger error callback
                     console.log("Unexpected response:", response);
                     isSaving = false;
                     $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
                }               
            } else {
                isSaving = false;
                $('#save, #updateBtn, #editSaveBtn').prop('disabled', false);
            }
        });

     }
    } catch (err) {
        console.error("Save() crashed:", err);
        isSaving = false;
        try { $('#save, #updateBtn, #editSaveBtn').prop('disabled', false); } catch(e){}
        try { Swal.fire({icon:'error', title:'Update failed', text:(err && err.message) ? err.message : 'Unexpected error'}); } catch(e){}
        return;
    }
}

function PrintSupplierSalesInvoice(){ return; }

/**
 * Focus and highlight a row in DataTables by keys (serial, si, product).
 * Jumps to the correct page and scrolls the row into view.
 */
function focusRowByKeys(dt, serial, si, product){
    try {
        var pageInfo = dt.page.info();
        var len = pageInfo.length || 50;
        var targetIndex = -1;
        dt.rows().every(function(rowIdx){
            var data = this.data();
            // data array indices follow DATAINV order:
            // 0:SIno, 1:Serialno, 2:Product
            var dsi = String(data[0]||'').trim();
            var dserial = String(data[1]||'').trim();
            var dprod = String(data[2]||'').trim();
            
            // Loose comparison to handle potential casing/whitespace issues
            if ((serial && dserial.toUpperCase() === String(serial).toUpperCase()) &&
                (si && dsi.toUpperCase() === String(si).toUpperCase()) &&
                (product && dprod.toUpperCase() === String(product).toUpperCase())) {
                targetIndex = rowIdx;
                return false; // break
            }
        });
        if (targetIndex >= 0) {
            var pageIdx = Math.floor(targetIndex / len);
            dt.page(pageIdx).draw(false);
            var node = dt.row(targetIndex).node();
            // Clear previous selections
            dt.rows('.selected').nodes().each((row) => { row.classList.remove('selected'); });
            $(node).addClass('selected');
            if (node && node.scrollIntoView) {
                node.scrollIntoView({behavior:'smooth', block:'center'});
            }
            return true;
        } else {
            // Fallback to search to narrow visible rows
            dt.search(serial || si || product || '').draw(false);
            return false;
        }
    } catch(e){
        return false;
    }
}
function formatAmtVal(value) {
    // Remove any characters that are not digits, commas, or periods
    let cleanValue = value.toString().replace(/[^0-9.,]/g, '');
    // Remove commas for formatting purposes
    cleanValue = cleanValue.replace(/,/g, '');
    if (cleanValue !== '') {
        // Parse the cleaned value to a float and ensure two decimal places
        let formattedValue = parseFloat(cleanValue).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        return formattedValue; // Return the formatted value
    }    
    return '0.00'; // Return an empty string if input is invalid or empty
}

function formatInput(input) {
    // Get the value from the input field and remove invalid characters
    let cleanValue = input.value.replace(/[^0-9.,-]/g, '');

    // Check for negative values
    if (cleanValue.includes('-')) {
        // Show SweetAlert message
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Negative amounts are not allowed.',
            confirmButtonText: 'OK'
        });

        // Reset the input field
        input.value = '0.00';
        return;
    }

    // Remove commas for numeric processing
    cleanValue = cleanValue.replace(/,/g, '');

    if (cleanValue !== '') {
        // Parse the cleaned value to a float and ensure two decimal places
        let formattedValue = parseFloat(cleanValue).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        // Set the formatted value back to the input field
        input.value = formattedValue;
    } else {
        input.value = '0.00'; // If empty or invalid, set input to empty
    }
}
