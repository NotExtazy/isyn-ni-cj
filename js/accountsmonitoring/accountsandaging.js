let SelectedClientNo = null, SelectedLoanID = null;
let SelectedSLClientNo = null, SelectedSLLoanID = null;
let agingTable = null;
let loanTable = null;

$(document).ready(function() {
    LoadList();
});

// ── Aging Tab ────────────────────────────────────────────────────────────────
function LoadList() {
    const order = $("#selectionOrder").val() || "name";
    
    // Destroy existing DataTable if it exists
    if (agingTable) {
        agingTable.destroy();
        agingTable = null;
    }
    
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: { action: "LoadList", order: order },
        dataType: "json",
        success: function (r) {
            let tbody = $("#tableBody").empty();
            
            // Check for error status
            if (r.STATUS === 'ERROR') {
                tbody.append(`<tr><td colspan="7" class="text-center text-danger py-3">Error: ${r.MESSAGE}</td></tr>`);
                console.error("LoadList Error:", r.MESSAGE);
                return;
            }
            
            if (!r.LIST || r.LIST.length === 0) {
                tbody.append(`<tr><td colspan="7" class="text-center text-muted py-3">No data available. Click "Update" to refresh aging data.</td></tr>`);
                return;
            }
            
            $.each(r.LIST, function (_, v) {
                tbody.append(`
                    <tr data-row='${JSON.stringify(v).replace(/'/g,"&#39;")}' style="cursor:pointer;">
                        <td>${v.FULLNAME ?? ''}</td>
                        <td>${v.ClientNo ?? ''}</td>
                        <td>${v.LoanID ?? ''}</td>
                        <td>${v.DATERELEASE ? formatDate(v.DATERELEASE) : ''}</td>
                        <td class="text-end">${formatAmt(v.LOANAMOUNT)}</td>
                        <td>${v.PRODUCT ?? ''}</td>
                        <td>${v.ADDITIONAL ?? ''}</td>
                    </tr>
                `);
            });
            
            // Initialize DataTable after data is loaded
            initializeAgingTable();
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.error("Response Status:", xhr.status);
            console.error("Response Text:", xhr.responseText);
            
            let tbody = $("#tableBody").empty();
            let errorMsg = "Error loading data";
            
            // Try to extract meaningful error from response
            if (xhr.responseText) {
                // Check if it's HTML
                if (xhr.responseText.trim().startsWith('<')) {
                    errorMsg = "Server returned HTML instead of JSON. Check PHP errors.";
                    // Try to extract error message from HTML
                    let match = xhr.responseText.match(/<b>(.+?)<\/b>/);
                    if (match) {
                        errorMsg += " Error: " + match[1];
                    }
                } else {
                    errorMsg = xhr.responseText.substring(0, 200);
                }
            }
            
            tbody.append(`<tr><td colspan="7" class="text-center text-danger py-3">${errorMsg}<br><small>Check browser console for details.</small></td></tr>`);
        }
    });
}

function initializeAgingTable() {
    // Only initialize if there's data
    if ($("#tableBody tr[data-row]").length > 0) {
        agingTable = $("#myTable").DataTable({
            paging: true,
            pageLength: 10,
            searching: true, // Enable DataTable search
            ordering: true,
            info: true,
            autoWidth: false,
            dom: 'rtip', // Remove default search box (r=processing, t=table, i=info, p=pagination)
            language: {
                emptyTable: "No data available",
                info: "Showing _START_ to _END_ of _TOTAL_ accounts",
                infoEmpty: "Showing 0 to 0 of 0 accounts",
                infoFiltered: "(filtered from _MAX_ total accounts)",
                lengthMenu: "Show _MENU_ accounts",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });
    }
}

// Row click → fill account details
$(document).on("click", "#tableBody tr[data-row]", function () {
    $("#tableBody tr").removeClass("table-active");
    $(this).addClass("table-active");

    let v = JSON.parse($(this).attr("data-row").replace(/&#39;/g, "'"));
    SelectedClientNo = v.ClientNo;
    SelectedLoanID   = v.LoanID;

    // Primary Details
    $("#program").val(v.PROGRAM ?? "");
    $("#product").val(v.PRODUCT ?? "");
    $("#availment").val(v.LOANAVAILMENT ?? "");
    $("#date-release").val(v.DATERELEASE ? v.DATERELEASE.substring(0,10) : "");
    $("#date-mature").val(v.DATEMATURE ? v.DATEMATURE.substring(0,10) : "");
    $("#mode").val(v.MODE ?? "");
    $("#term").val(v.TERM ?? "");
    $("#PO").val(v.PO ?? "");
    $("#fund").val(v.FUND ?? "");
    $("#PNNo").val(v.PNNO ?? "");
    $("#tag").val(v.TAG ?? "");

    // Amounts
    $("#loan-amount").val(formatAmt(v.LOANAMOUNT));
    $("#interest-amount").val(formatAmt(v.INTEREST));
    $("#cbu").val(formatAmt(v.CBUFTL));
    $("#ef").val(formatAmt(v.EF));
    $("#mba").val(formatAmt(v.MBA));

    // Payments Made
    $("#principal-paid").val(formatAmt(v.AmountPaid));
    $("#interest-paid").val(formatAmt(v.InterestPaid));
    $("#cbu-paid").val(formatAmt(v.CBUPaid));
    $("#ef-paid").val(formatAmt(v.EFPaid));
    $("#mba-paid").val(formatAmt(v.MBAPaid));
    $("#penalties-paid").val(formatAmt(v.PenaltyPaid));

    // Status
    $("#principal-balance").val(formatAmt(v.Balance));

    // Date Modified
    $("#date-restructured").val(v.RESTRUCTUREDATE ?? "");
    $("#date-writtenOff").val(v.WRITEOFFDATE ?? "");
    $("#date-dropped").val(v.DATEDROPPED ?? "");

    // Dues
    $("#due-date").val(v.DueDate ? formatDate(v.DueDate) : "");
    $("#principal").val(formatAmt(v.AmountDue));
    $("#interest-due").val(formatAmt(v.InterestDue));
    $("#cbuDue").val(formatAmt(v.CBUDue));
    $("#efDue").val(formatAmt(v.EFDue));
    $("#mbaDue").val(formatAmt(v.MBADue));
    $("#penalty-due").val(formatAmt(v.PenaltyDue));

    // Arrears
    $("#one-thirty").val(formatAmt(v.DAYS130));
    $("#thirty-one-to-sixty").val(formatAmt(v.DAYS3160));
    $("#sixtyone-to-ninety").val(formatAmt(v.DAYS6190));
    $("#ninetyone-onetwenty").val(formatAmt(v.DAYS91120));
    $("#onetwentyone-onefifty").val(formatAmt(v.DAYS121150));
    $("#onefiftyone-oneeighty").val(formatAmt(v.DAYS151180));
    $("#over-oneeighty").val(formatAmt(v.DAYSOver180));
    $("#total-arrears").val(formatAmt(v.TotalArrears));
    $("#par").val(formatAmt(v.PAR));

    // Enable action buttons
    $("#editAccount, #removeAccount, #deleteAccount").prop("disabled", false);

    // Populate edit modal fields
    $("#editClientNo").val(v.ClientNo);
    $("#editLoanID").val(v.LoanID);
    $("#editClientNoDisplay").val(v.ClientNo);
    $("#editLoanIDDisplay").val(v.LoanID);
    $("#editFullname").val(v.FULLNAME ?? "");
    $("#editPNNO").val(v.PNNO ?? "");
    $("#editTag").val(v.TAG ?? "");
    $("#editPO").val(v.PO ?? "");
    $("#editFund").val(v.FUND ?? "");
    $("#editProgram").val(v.PROGRAM ?? "");
    $("#editProduct").val(v.PRODUCT ?? "");
    $("#editMode").val(v.MODE ?? "");
    $("#editTerm").val(v.TERM ?? "");
    $("#editInterestRate").val(v.INTERESTRATE ?? "");
    $("#editIntComputation").val(v.INTCOMPUTATION ?? "");
    $("#editDateRelease").val(v.DATERELEASE ? v.DATERELEASE.substring(0,10) : "");
    $("#editDateMature").val(v.DATEMATURE ? v.DATEMATURE.substring(0,10) : "");
    $("#editLoanAmount").val(v.LOANAMOUNT ?? "");
    $("#editInterest").val(v.INTEREST ?? "");
    $("#editCBU").val(v.CBUFTL ?? "");
    $("#editEF").val(v.EF ?? "");
    $("#editMBA").val(v.MBA ?? "");
    $("#editNetAmount").val(v.NETAMOUNT ?? "");
});

// Search filter - use DataTable's built-in search
$("#searchInput").on("input", function () {
    let q = $(this).val();
    
    if (agingTable) {
        // Use DataTable search if initialized
        agingTable.search(q).draw();
    } else {
        // Fallback to manual filtering if DataTable not initialized
        let qLower = q.toLowerCase();
        $("#tableBody tr[data-row]").each(function () {
            let text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(qLower));
        });
    }
});

// Sort order change
$("#selectionOrder").on("change", function () { LoadList(); });

// Print SL
$("#printSLBtn").on("click", function () {
    if (!SelectedSLClientNo || !SelectedSLLoanID) {
        Swal.fire({ icon: "warning", title: "Please select a loan first." });
        return;
    }
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: { action: "SetSession", clientno: SelectedSLClientNo, loanid: SelectedSLLoanID },
        dataType: "JSON",
        success: function () {
            window.open("/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php?type=SLReport", "_blank");
        }
    });
});

// Print SOA
$("#printStatement").on("click", function () {
    if (!SelectedClientNo || !SelectedLoanID) {
        Swal.fire({ icon: "warning", title: "Please select a client first." });
        return;
    }
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: { action: "SetSession", clientno: SelectedClientNo, loanid: SelectedLoanID },
        dataType: "JSON",
        success: function () {
            window.open("/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php?type=SOAReport", "_blank");
        }
    });
});

// Update aging button
$("#updateBtn").on("click", function () {
    $(this).prop("disabled", true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Updating...');
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: { action: "UpdateAging" },
        dataType: "JSON",
        success: function (r) {
            if (r.STATUS === 'SUCCESS') {
                Swal.fire({ 
                    icon: "success", 
                    title: "Success", 
                    text: r.MESSAGE, 
                    timer: 2000, 
                    showConfirmButton: false 
                });
                LoadList();
            } else {
                Swal.fire({ 
                    icon: "error", 
                    title: "Error", 
                    text: r.MESSAGE 
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Update Error:", error);
            Swal.fire({ 
                icon: "error", 
                title: "Update Failed", 
                text: "Could not update aging data. Please check console for details." 
            });
        },
        complete: function () {
            $("#updateBtn").prop("disabled", false).html('<i class="fa-solid fa-refresh me-1"></i>Update');
        }
    });
});

function SaveEditAccount() {
    let clientno = $("#editClientNo").val();
    let loanid   = $("#editLoanID").val();
    if (!clientno || !loanid) return;

    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: {
            action:         "UpdateAccount",
            clientno:       clientno,
            loanid:         loanid,
            fullname:       $("#editFullname").val(),
            program:        $("#editProgram").val(),
            product:        $("#editProduct").val(),
            mode:           $("#editMode").val(),
            term:           $("#editTerm").val(),
            interestrate:   $("#editInterestRate").val(),
            intcomputation: $("#editIntComputation").val(),
            daterelease:    $("#editDateRelease").val(),
            datemature:     $("#editDateMature").val(),
            loanamount:     $("#editLoanAmount").val(),
            interest:       $("#editInterest").val(),
            cbu:            $("#editCBU").val(),
            ef:             $("#editEF").val(),
            mba:            $("#editMBA").val(),
            netamount:      $("#editNetAmount").val(),
            po:             $("#editPO").val(),
            tag:            $("#editTag").val(),
            pnno:           $("#editPNNO").val(),
            fund:           $("#editFund").val(),
        },
        dataType: "JSON",
        success: function (r) {
            if (r.STATUS === "SUCCESS") {
                bootstrap.Modal.getInstance(document.getElementById("editModal")).hide();
                Swal.fire({ icon: "success", title: r.MESSAGE, timer: 2000, showConfirmButton: false });
                LoadList();
            } else {
                Swal.fire({ icon: "error", title: r.MESSAGE });
            }
        }
    });
}

function clearAccountDetails() {
    SelectedClientNo = null; SelectedLoanID = null;
    $("input[readonly]").val("");
    $("#editAccount, #removeAccount, #deleteAccount").prop("disabled", true);
    $("#tableBody tr").removeClass("table-active");
}

// ── Subsidiary Ledgers Tab ───────────────────────────────────────────────────
$(document).on("click", "#subsidiary-ledgers", function () {
    LoadSLClientList();
});

function LoadSLClientList() {
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: { action: "LoadList", order: "name" },
        dataType: "JSON",
        success: function (r) {
            let sel = $("#selectName").empty().append(`<option value="all" selected>Show All</option>`);
            let seen = {};
            $.each(r.LIST, function (_, v) {
                if (!seen[v.ClientNo]) {
                    seen[v.ClientNo] = true;
                    sel.append(`<option value="${v.ClientNo}">${v.FULLNAME} (${v.ClientNo})</option>`);
                }
            });
            LoadSLLoans("all");
        }
    });
}

function LoadSLLoans(clientno) {
    if (clientno === "all") {
        // Show all loans from tbl_aging as proxy
        $.ajax({
            url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
            type: "POST",
            data: { action: "LoadList", order: "name" },
            dataType: "JSON",
            success: function (r) { renderLoanTable(r.LIST); }
        });
    } else {
        $.ajax({
            url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
            type: "POST",
            data: { action: "LoadSLList", clientno: clientno },
            dataType: "JSON",
            success: function (r) { renderLoanTable(r.LOANS); }
        });
    }
}

function renderLoanTable(rows) {
    // Destroy existing DataTable if it exists
    if (loanTable) {
        loanTable.destroy();
        loanTable = null;
    }
    
    let tbody = $("#loanTable tbody").empty();
    
    if (!rows || rows.length === 0) {
        tbody.append(`<tr><td colspan="4" class="text-center text-muted py-3">No loans found.</td></tr>`);
        return;
    }
    
    $.each(rows, function (_, v) {
        let loanid  = v.LoanID   ?? v.LoanID   ?? '';
        let product = v.Product  ?? v.PRODUCT  ?? '';
        let drel    = v.DateRelease ?? v.DATERELEASE ?? '';
        let ltype   = v.LoanType ?? v.LOANAVAILMENT ?? '';
        let cno     = v.ClientNo ?? '';
        tbody.append(`
            <tr data-clientno="${cno}" data-loanid="${loanid}" style="cursor:pointer;">
                <td>${loanid}</td>
                <td>${product}</td>
                <td>${drel ? formatDate(drel) : ''}</td>
                <td>${ltype}</td>
            </tr>
        `);
    });
    
    // Initialize DataTable for loan table
    if ($("#loanTable tbody tr[data-loanid]").length > 0) {
        loanTable = $("#loanTable").DataTable({
            paging: true,
            pageLength: 10,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            language: {
                emptyTable: "No loans found",
                info: "Showing _START_ to _END_ of _TOTAL_ loans",
                infoEmpty: "Showing 0 to 0 of 0 loans",
                lengthMenu: "Show _MENU_ loans"
            }
        });
    }
}

// Loan row click → fill SL account details + load SL preview
$(document).on("click", "#loanTable tbody tr[data-loanid]", function () {
    $("#loanTable tbody tr").removeClass("table-active");
    $(this).addClass("table-active");

    let cno    = $(this).data("clientno");
    let loanid = $(this).data("loanid");
    SelectedSLClientNo = cno;
    SelectedSLLoanID   = loanid;

    // Fill account details from aging data
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: { action: "LoadList", order: "name" },
        dataType: "JSON",
        success: function (r) {
            let match = (r.LIST || []).find(x => x.ClientNo == cno && x.LoanID == loanid);
            if (match) {
                $("#clientDetails").val(match.ClientNo);
                $("#programDetails").val(match.PROGRAM ?? "");
                $("#productDetails").val(match.PRODUCT ?? "");
                $("#dateReleaseDetails").val(match.DATERELEASE ? formatDate(match.DATERELEASE) : "");
                $("#dateMatureDetails").val(match.DATEMATURE ? formatDate(match.DATEMATURE) : "");
                $("#loanAmountDetails").val(formatAmt(match.LOANAMOUNT));
                $("#interestDetails").val(formatAmt(match.INTEREST));
                $("#cbuDetails").val(formatAmt(match.CBUFTL));
                $("#pnnoDetails").val(match.PNNO ?? "");
                $("#poDetails").val(match.PO ?? "");
            }
        }
    });

    LoadSLPreview(cno, loanid);
});

function filterTableByClient() {
    let val = $("#selectName").val();
    LoadSLLoans(val);
}

function LoadSLPreview(clientno, loanid) {
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/accountsandaging.route.php",
        type: "POST",
        data: { action: "LoadSLPreview", clientno: clientno, loanid: loanid },
        dataType: "JSON",
        success: function (r) {
            let tbody = $("#slPreviewBody").empty();
            if (!r.SL || r.SL.length === 0) {
                tbody.append(`<tr><td colspan="9" class="text-center text-muted py-3">No transactions found.</td></tr>`);
                return;
            }
            let balance = 0;
            $.each(r.SL, function (_, v) {
                let ref = v.ORNo || v.CVNo || v.JVNo || '-';
                let dr  = parseFloat(v.DrOther) || 0;
                let cr  = parseFloat(v.CrOther) || 0;
                balance += dr - cr;
                tbody.append(`
                    <tr>
                        <td>${v.CDate ? formatDate(v.CDate) : ''}</td>
                        <td>${ref}</td>
                        <td>${v.AcctTitle ?? ''}</td>
                        <td class="text-end">${dr > 0 ? formatAmt(dr) : ''}</td>
                        <td class="text-end">${cr > 0 ? formatAmt(cr) : ''}</td>
                        <td class="text-end">${formatAmt(Math.abs(parseFloat(v.SLDrCr) || 0))}</td>
                        <td class="text-end">${formatAmt(balance)}</td>
                        <td>${v.BookType ?? ''}</td>
                        <td>${v.Explanation ?? ''}</td>
                    </tr>
                `);
            });
        }
    });
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function formatAmt(val) {
    let n = parseFloat(val);
    if (isNaN(n) || n === 0) return "";
    return n.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(d) {
    if (!d) return "";
    let dt = new Date(d);
    if (isNaN(dt)) return d;
    let m = String(dt.getMonth()+1).padStart(2,'0');
    let day = String(dt.getDate()).padStart(2,'0');
    return m + '-' + day + '-' + dt.getFullYear();
}

