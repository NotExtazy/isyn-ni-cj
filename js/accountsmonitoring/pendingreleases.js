let SelectedClientNo = null, SelectedLoanID = null;
let allApplications = []; // Store all applications for filtering

// Document ready - initialize buttons
$(document).ready(function() {
    console.log("Page loaded - initializing...");
    // Start with Save Details disabled until client selected
    $("#saveDetailsBtn").prop("disabled", true);
    // Other buttons can start enabled
    $("#voucherBtn, #checkBtn, #lrsBtn").prop("disabled", false);
    console.log("Buttons initialized");
});

LoadPage();
LoadList();

// Search functionality for Application List
$(document).on("keyup", "#searchApplicationList", function() {
    let searchTerm = $(this).val().toLowerCase();
    let tbody = $("#checkVouchersTableBody").empty();
    
    let filtered = allApplications.filter(function(app) {
        let name = (app.FULLNAME || "").toLowerCase();
        let clientNo = (app.ClientNo || "").toLowerCase();
        return name.includes(searchTerm) || clientNo.includes(searchTerm);
    });
    
    if (filtered.length === 0) {
        tbody.append(`<tr><td colspan="8" class="text-center text-muted py-3">No matching applications found.</td></tr>`);
        return;
    }
    
    $.each(filtered, function(k, v) {
        let cvIcon = v.CVPrinted === 'YES' ? '<i class="fa-solid fa-check-circle text-success"></i>' : '<i class="fa-solid fa-times-circle text-danger"></i>';
        let checkIcon = v.CheckPrinted === 'YES' ? '<i class="fa-solid fa-check-circle text-success"></i>' : '<i class="fa-solid fa-times-circle text-danger"></i>';
        let statusBadge = v.ReleaseStatus === 'RELEASED' 
            ? '<span class="badge bg-success">Released</span>' 
            : '<span class="badge bg-warning">Pending</span>';
        
        tbody.append(`
            <tr data-clientno="${v.ClientNo}" data-loanid="${v.LoanID}" style="cursor:pointer;">
                <td>${v.FULLNAME}</td>
                <td>${v.ClientNo}</td>
                <td>${v.PROGRAM ?? '-'}</td>
                <td>${v.PRODUCT ?? '-'}</td>
                <td class="text-end">${formatAmt(v.LOANAMOUNT)}</td>
                <td class="text-center">${cvIcon}</td>
                <td class="text-center">${checkIcon}</td>
                <td class="text-center">${statusBadge}</td>
            </tr>
        `);
    });
});

function LoadPage() {
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
        type: "POST",
        data: { action: "LoadPage" },
        dataType: "JSON",
        success: function (r) {
            console.log("LoadPage response:", r);
            let sel = $("#inputReleaseType").empty()
                .append(`<option value="" disabled selected>SELECT RELEASE TYPE</option>`);
            if (r.TYPES && r.TYPES.length > 0) {
                $.each(r.TYPES, function (k, v) {
                    sel.append(`<option value="${v.Type}">${v.Type}</option>`);
                });
            } else {
                console.warn("No release types found in database");
            }
        },
        error: function(xhr, status, error) {
            console.error("LoadPage error:", status, error);
            console.error("Response:", xhr.responseText);
        }
    });
}

function LoadList() {
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
        type: "POST",
        data: { action: "LoadList" },
        dataType: "JSON",
        success: function (r) {
            console.log("LoadList response:", r);
            allApplications = r.LIST || []; // Store for filtering
            let tbody = $("#checkVouchersTableBody").empty();
            if (!r.LIST || r.LIST.length === 0) {
                tbody.append(`<tr><td colspan="8" class="text-center text-muted py-3">No releases found.</td></tr>`);
                return;
            }
            $.each(r.LIST, function (k, v) {
                let cvIcon = v.CVPrinted === 'YES' ? '<i class="fa-solid fa-check-circle text-success"></i>' : '<i class="fa-solid fa-times-circle text-danger"></i>';
                let checkIcon = v.CheckPrinted === 'YES' ? '<i class="fa-solid fa-check-circle text-success"></i>' : '<i class="fa-solid fa-times-circle text-danger"></i>';
                let statusBadge = v.ReleaseStatus === 'RELEASED' 
                    ? '<span class="badge bg-success">Released</span>' 
                    : '<span class="badge bg-warning">Pending</span>';
                
                tbody.append(`
                    <tr data-clientno="${v.ClientNo}" data-loanid="${v.LoanID}" style="cursor:pointer;">
                        <td>${v.FULLNAME}</td>
                        <td>${v.ClientNo}</td>
                        <td>${v.PROGRAM ?? '-'}</td>
                        <td>${v.PRODUCT ?? '-'}</td>
                        <td class="text-end">${formatAmt(v.LOANAMOUNT)}</td>
                        <td class="text-center">${cvIcon}</td>
                        <td class="text-center">${checkIcon}</td>
                        <td class="text-center">${statusBadge}</td>
                    </tr>
                `);
            });
        },
        error: function(xhr, status, error) {
            console.error("LoadList error:", status, error);
            console.error("Response:", xhr.responseText);
        }
    });
}

// Row click — load client details
$(document).on("click", "#checkVouchersTableBody tr[data-clientno]", function () {
    $("#checkVouchersTableBody tr").removeClass("table-active");
    $(this).addClass("table-active");
    SelectedClientNo = $(this).data("clientno");
    SelectedLoanID   = $(this).data("loanid");

    console.log("Loading client details for:", SelectedClientNo, SelectedLoanID);

    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
        type: "POST",
        data: { action: "LoadClientDetails", clientno: SelectedClientNo, loanid: SelectedLoanID },
        dataType: "JSON",
        success: function (r) {
            console.log("LoadClientDetails response:", r);
            let d = r.DETAILS;
            if (!d) return;

            // Primary Details (tbl_loans column names)
            $("#programPenReleases").val(d.Program ?? "");
            $("#productPenReleases").val(d.Product ?? "");
            $("#staffPenReleases").val(d.PO ?? "");
            $("#modePenReleases").val(d.Mode ?? "");
            $("#termPenReleases").val(d.Term ?? "");
            $("#ratePenReleases").val(d.InterestRate ?? "");
            $("#computationPenReleases").val(d.IntComputation ?? "");
            $("#tagPenReleases").val(d.Tag ?? "");
            $("#chargesPenReleases").val("");

            // Totals
            $("#loanAmountPenReleases").val(formatAmt(d.LoanAmount));
            $("#interestPenReleases").val(formatAmt(d.Interest));
            $("#mbaPenReleases").val(formatAmt(d.MBA));
            $("#cbuPenReleases").val(formatAmt(d.CBU));
            $("#efPenReleases").val(formatAmt(d.EF));
            let net = parseFloat(d.NetAmount) > 0 ? d.NetAmount : d.LoanAmount;
            $("#netAmountPenReleases").val(formatAmt(net));

            // Amortization
            $("#principalAmort").val(d.PrincipalAmo ?? "");
            $("#interestAmort").val(d.InterestAmo ?? "");
            $("#mbaAmort").val(formatAmt(d.MBAAmo));
            $("#cbuAmort").val(formatAmt(d.CBUAmo));
            $("#efAmort").val(formatAmt(d.EFAmo));
            $("#totalAmort").val(d.TotalAmo ?? "");

            // Hidden
            $("#IDNum").val(d.ClientNo);
            $("#ClientId").val(d.ClientNo);

            // Enable release type dropdown
            $("#inputReleaseType").prop("disabled", false);

            // Check print status
            let cvPrinted = d.CVPrinted === 'YES';
            let checkPrinted = d.CheckPrinted === 'YES';

            // Enable all buttons by default
            $("#saveDetailsBtn").prop("disabled", false).removeAttr("title");
            $("#lrsBtn").prop("disabled", false).removeAttr("title");
            
            // Voucher button - disable if already printed
            if (cvPrinted) {
                $("#voucherBtn").prop("disabled", true).attr("title", "Voucher already printed");
            } else {
                $("#voucherBtn").prop("disabled", false).removeAttr("title");
            }
            
            // Check button - disable if already printed
            if (checkPrinted) {
                $("#checkBtn").prop("disabled", true).attr("title", "Check already printed");
            } else {
                $("#checkBtn").prop("disabled", false).removeAttr("title");
            }

            // Load voucher entries preview
            console.log("Loading voucher entries...");
            LoadVoucherEntries(SelectedClientNo, SelectedLoanID);
        },
        error: function(xhr, status, error) {
            console.error("LoadClientDetails error:", status, error);
            console.error("Response:", xhr.responseText);
        }
    });
});

// Release type change → load banks
$(document).on("change", "#inputReleaseType", function () {
    let type = $(this).val();
    if (!type) return;

    console.log("GetBanks request - Type:", type);

    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
        type: "POST",
        data: { action: "GetBanks", type: type },
        dataType: "JSON",
        success: function (r) {
            console.log("GetBanks response:", r);
            let sel = $("#inputBankAccount").empty()
                .append(`<option value="" disabled selected>SELECT BANK ACCOUNT</option>`);
            
            if (!r.BANKS || r.BANKS.length === 0) {
                console.warn("No banks found for type:", type);
                sel.append(`<option value="" disabled>No banks available</option>`);
                return;
            }
            
            $.each(r.BANKS, function (k, v) {
                sel.append(`<option value="${v.Bank}">${v.Bank}</option>`);
            });
            $("#inputFundTag").empty().append(`<option value="" disabled selected>SELECT FUND/TAG</option>`);
            $("#inputVoucher, #inputCheckNo").val("");
        },
        error: function(xhr, status, error) {
            console.error("GetBanks error:", status, error);
            console.error("Response:", xhr.responseText);
        }
    });
});

// Bank change → load fund/tag and CV/Check numbers
$(document).on("change", "#inputBankAccount", function () {
    let bank = $(this).val();
    if (!bank) return;

    console.log("GetFundTags request - Bank:", bank);

    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
        type: "POST",
        data: { 
            action: "GetFundTags", 
            bank: bank,
            clientno: SelectedClientNo,
            loanid: SelectedLoanID
        },
        dataType: "JSON",
        success: function (r) {
            console.log("GetFundTags response:", r);
            let sel = $("#inputFundTag").empty()
                .append(`<option value="" disabled selected>SELECT FUND/TAG</option>`);
            
            if (!r.BANKINFO || r.BANKINFO.length === 0) {
                console.warn("No fund/tag info found for bank:", bank);
                sel.append(`<option value="" disabled>No fund/tag available</option>`);
                return;
            }
            
            if (r.BANKINFO && r.BANKINFO.length > 0) {
                let info = r.BANKINFO[0];
                sel.append(`<option value="${info.Fund}" selected>${info.Fund}</option>`);
                $("#inputVoucher").val(info.LastCV ?? "");
                $("#inputCheckNo").val(info.NextCheck ?? "");
                
                // Store these values for printing
                console.log("CV and Check numbers ready for printing:", info.LastCV, info.NextCheck);
            }
        },
        error: function(xhr, status, error) {
            console.error("GetFundTags error:", status, error);
            console.error("Response:", xhr.responseText);
        }
    });
});

function LoadVoucherEntries(clientno, loanid) {
    console.log("LoadVoucherEntries called for:", clientno, loanid);
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
        type: "POST",
        data: { action: "GetVoucherEntries", clientno: clientno, loanid: loanid },
        dataType: "JSON",
        success: function (r) {
            console.log("GetVoucherEntries response:", r);
            $("#particulars").val(r.PARTICULARS ?? "");
            let tbody = $("#loanTableBody").empty();
            
            if (!r.ENTRIES || r.ENTRIES.length === 0) {
                console.warn("No voucher entries found");
                tbody.append(`<tr><td colspan="5" class="text-center text-muted">No entries available</td></tr>`);
                return;
            }
            
            $.each(r.ENTRIES, function (k, v) {
                tbody.append(`
                    <tr>
                        <td>${v.Account}</td>
                        <td>${v.AcctNo}</td>
                        <td>${v.SL}</td>
                        <td class="text-end">${v.Debit > 0 ? formatAmt(v.Debit) : ''}</td>
                        <td class="text-end">${v.Credit > 0 ? formatAmt(v.Credit) : ''}</td>
                    </tr>
                `);
            });
            console.log("Voucher entries loaded:", r.ENTRIES.length, "rows");
        },
        error: function(xhr, status, error) {
            console.error("GetVoucherEntries error:", status, error);
            console.error("Response:", xhr.responseText);
            $("#loanTableBody").empty().append(`<tr><td colspan="5" class="text-center text-danger">Error loading entries</td></tr>`);
        }
    });
}

function SaveDetails() {
    if (!SelectedClientNo) {
        Swal.fire({ icon: "warning", title: "Please select a client first." });
        return;
    }

    let type = $("#inputReleaseType").val();
    let bank = $("#inputBankAccount").val();
    let fund = $("#inputFundTag").val();

    if (!type || !bank || !fund) {
        Swal.fire({ icon: "warning", title: "Please complete Funding Details." });
        return;
    }

    Swal.fire({
        icon: "question",
        title: "Save Release?",
        text: "This will save/update funding details and post entries to the books.",
        showCancelButton: true,
        confirmButtonText: "Yes, save",
        confirmButtonColor: "#2563eb",
    }).then(function (result) {
        if (result.isConfirmed) {
            $.ajax({
                url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
                type: "POST",
                data: { action: "SaveRelease", clientno: SelectedClientNo, loanid: SelectedLoanID, type: type, bank: bank, fund: fund },
                dataType: "JSON",
                success: function (r) {
                    console.log("SaveRelease response:", r);
                    if (r.STATUS == "SUCCESS") {
                        Swal.fire({ icon: "success", title: r.MESSAGE, timer: 2000, showConfirmButton: false });
                        LoadList();
                        clearForm();
                    } else {
                        Swal.fire({ icon: "error", title: r.MESSAGE });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("SaveRelease error:", status, error);
                    console.error("Response:", xhr.responseText);
                    Swal.fire({ icon: "error", title: "Error saving release", text: xhr.responseText });
                }
            });
        }
    });
}

function clearForm() {
    console.log("Clearing form...");
    SelectedClientNo = null;
    SelectedLoanID   = null;
    let fields = ["programPenReleases","productPenReleases","staffPenReleases","modePenReleases",
        "termPenReleases","ratePenReleases","computationPenReleases","tagPenReleases","chargesPenReleases",
        "loanAmountPenReleases","interestPenReleases","mbaPenReleases","cbuPenReleases","efPenReleases",
        "netAmountPenReleases","principalAmort","interestAmort","mbaAmort","cbuAmort","efAmort","totalAmort",
        "particulars","IDNum","ClientId","inputVoucher","inputCheckNo"];
    fields.forEach(function (f) { $("#" + f).val(""); });
    $("#inputReleaseType, #inputBankAccount, #inputFundTag").val("").trigger("change");
    $("#loanTableBody").empty();
    $("#checkVouchersTableBody tr").removeClass("table-active");
    
    // Disable all buttons after clearing
    $("#saveDetailsBtn").prop("disabled", true).removeAttr("title");
    $("#voucherBtn").prop("disabled", true).removeAttr("title");
    $("#checkBtn").prop("disabled", true).removeAttr("title");
    $("#lrsBtn").prop("disabled", true).removeAttr("title");
}

// ── Print helpers ────────────────────────────────────────────────────────────
function setSessionThenOpen(type) {
    if (!SelectedClientNo || !SelectedLoanID) {
        Swal.fire({ icon: "warning", title: "Please select a client first." });
        return;
    }
    $.ajax({
        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
        type: "POST",
        data: { action: "SetSession", clientno: SelectedClientNo, loanid: SelectedLoanID },
        dataType: "JSON",
        success: function () {
            window.open("/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php?type=" + type, "_blank");
            
            // Wait a moment for the print to process, then refresh
            setTimeout(function() {
                // Refresh the application list to show updated print status
                LoadList();
                
                // Reload the client details to update button states
                if (SelectedClientNo && SelectedLoanID) {
                    $.ajax({
                        url: "/iSynApp-main/routes/accountsmonitoring/pendingreleases.route.php",
                        type: "POST",
                        data: { action: "LoadClientDetails", clientno: SelectedClientNo, loanid: SelectedLoanID },
                        dataType: "JSON",
                        success: function (r) {
                            let d = r.DETAILS;
                            if (!d) return;
                            
                            // Check print status
                            let cvPrinted = d.CVPrinted === 'YES';
                            let checkPrinted = d.CheckPrinted === 'YES';
                            
                            // Enable all buttons by default
                            $("#saveDetailsBtn").prop("disabled", false).removeAttr("title");
                            $("#lrsBtn").prop("disabled", false).removeAttr("title");
                            
                            // Voucher button - disable if already printed
                            if (cvPrinted) {
                                $("#voucherBtn").prop("disabled", true).attr("title", "Voucher already printed");
                            } else {
                                $("#voucherBtn").prop("disabled", false).removeAttr("title");
                            }
                            
                            // Check button - disable if already printed
                            if (checkPrinted) {
                                $("#checkBtn").prop("disabled", true).attr("title", "Check already printed");
                            } else {
                                $("#checkBtn").prop("disabled", false).removeAttr("title");
                            }
                        }
                    });
                }
            }, 1500); // Wait 1.5 seconds for print to complete
        }
    });
}

function PrintVoucher() { 
    setSessionThenOpen("VoucherReport"); 
}

function PrintCheck() { 
    setSessionThenOpen("CheckReport"); 
}

function PrintLRS() { 
    setSessionThenOpen("LRSReport"); 
}

function formatAmt(val) {
    let n = parseFloat(val);
    if (isNaN(n)) return "";
    return n.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
