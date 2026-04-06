// Base path for AJAX requests
var BASE_PATH = window.BASE_PATH || '';
var ROUTE_URL = BASE_PATH + '/routes/inventorymanagement/orderconfirmation.route.php';

$(function(){
  // Select2 removed - using standard dropdowns

  var SelectedFromTable = "";
  var table1;
  var categoriesByType = {};
  var selectBy = "product"; // Default value defined here
  var serialProductList = []; // Initialize empty array
  var productSINoList = []; // Initialize empty array

  InitializeOC();

  // Auto-capitalize all text inputs and textareas
  $(document).on('input', 'input[type="text"], textarea', function() {
    this.value = this.value.toUpperCase();
  });

  $("#isynBranch").on("change", function(){
    let type = $("#type").val();
    if (type) {
        LoadCategoryOC(type);
    } else {
        forBranchClearOC();
    }
  });

  $("#type").on("change", function(){
    LoadCategoryOC(this.value);
  });

  $("#category").on("change", function(){
    LoadSerialProductOC(this.value);
  });

  $("input[name='inlineRadioOptions']").on("change", function(){
    selectBy = $(this).val(); // 'product' or 'serial'
    populateItemSelect();
    clearSummary();
  });

  $("#itemSelect").on("change", function(){
    populateSINo($(this).val());
  });

  $("#SIno").on("change", function(){
    LoadProductSummaryOC($("#itemSelect").val());
  });

  $("#quantityInput").on("input", function(){
    autoCompute();
  });

  $("#editSRPtoggle").on("change", function(){
    var enabled = $(this).is(":checked");
    $("#editSRP").prop("disabled", !enabled);
    if (!enabled) $("#editSRP").val("");
    autoCompute();
  });

  $("#addButton").on("click", function(){
    addItem();
  });

  $("#submit-btn").on("click", function(){
    SubmitOC();
  });
  
  $("#searchInput").on("keypress", function(e){
    if (e.which === 13) { OCSearch(); }
  });

  function InitializeOC(){
    $.ajax({
      url: ROUTE_URL,
      type:"POST",
      data:{action:"Initialize"},
      dataType:"JSON",
      success:function(response){
        $("#isynBranch").empty().append(`<option value="" disabled selected>Select</option>`);
        $.each(response.ISYNBRANCH,function(_,value){
          var b = value["Branch"] || value["Stock"] || value["branch"] || value["stock"] || "";
          if (b) $("#isynBranch").append(`<option value="${b}">${b}</option>`);
        });

        $("#type").empty().append(`<option value="" disabled selected>Select</option>`);
        $.each(response.PRODTYPE,function(_,value){
          var t = value["Type"] || value["type"] || "";
          if (t) $("#type").append(`<option value="${t}">${t}</option>`);
        });

        categoriesByType = {};
        var rawCats = response.CATEGORIES || {};
        Object.keys(rawCats).forEach(function(k){
          var key = (k || "").toUpperCase();
          var arr = rawCats[k] || [];
          var uniq = {};
          var out = [];
          arr.forEach(function(v){
            var c = (typeof v === 'string') ? v : (v["Category"] || v["category"] || "");
            if (!c) return;
            var cu = c.toUpperCase();
            if (!uniq[cu]) { uniq[cu] = true; out.push(c); }
          });
          categoriesByType[key] = out;
        });

        if ($.fn.DataTable) {
          if (!table1 || !$.fn.DataTable.isDataTable('#table1')) {
            table1 = $('#table1').DataTable({
              searching:false, ordering:false, info:false, paging:false,
              scrollY: '230px', scrollX: true, scrollCollapse: true
            });
          }
        }
        $("#isynBranch").val("").trigger('change');
        $("#type").val("").trigger('change');
        OCSearch();
      }
    })
  }

  function addItem() {
    let branch = $("#isynBranch").val();
    let type = $("#type").val();
    let categ = $("#category").val();
    let selectedItem = $("#itemSelect").val();
    let siNo = $("#SIno").val();
    let qty = $("#quantityInput").val();
    
    let psProduct = $("#productDisplay").val();
    let psSerial = $("#serialNodisplay").val();
    let psSupplier = $("#supplierDisplay").val();
    let psSRP = $("#srpDisplay").val().replace(/,/g, '');
    let editSRP = $("#editSRP").val().replace(/,/g, '');
    let psWarranty = $("#warranty").val();

    let AvailableQty = $("#quantityDisplay").val();
    if (Number(qty) > Number(AvailableQty)) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        Toast.fire({
            icon: 'error',
            title: 'Not enough stock!'
        });
        return;
    }
    
    if (!branch || !type || !categ || !selectedItem || !siNo || !qty || parseFloat(qty) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Fields',
            text: 'Please fill in all required fields and ensure quantity is greater than 0.'
        });
        return;
    }

    let unitPrice = $("#editSRPtoggle").is(":checked") ? parseFloat(editSRP) : parseFloat(psSRP);
    let total = parseFloat(qty) * unitPrice;

    let displayProduct = psProduct;
    if (psSerial && psSerial !== "-") displayProduct += " (S/N: " + psSerial + ")";

    table1.row.add([
        displayProduct.toUpperCase(),
        qty,
        formatAmt(unitPrice),
        siNo.toUpperCase(),
        "0", // Vat placeholder
        "0", // VatSales placeholder
        psWarranty.toUpperCase(),
        new Date().toLocaleDateString(),
        psSerial.toUpperCase(),
        categ.toUpperCase(),
        type.toUpperCase(),
        branch.toUpperCase(),
        psSupplier.toUpperCase()
    ]).draw(false);

    $("#submit-btn").prop("disabled", false);
    
    // Clear product inputs
    $("#quantityInput").val("");
    $("#editSRP").val("").prop("disabled", true);
    $("#editSRPtoggle").prop("checked", false);
    clearSummary();
  }

  function SubmitOC(){
    var recipient = $("#recipient").val().toUpperCase();
    if (!recipient) {
        Swal.fire({ icon: 'warning', title: 'Recipient Required' });
        return;
    }

    let Data = table1.rows().data().toArray();
    let formdata = new FormData();
    formdata.append("action", "SubmitOC");
    formdata.append("DATA", JSON.stringify(Data));
    formdata.append("recipient", recipient);

    Swal.fire({
        title: 'Submit Order Confirmation?',
        showCancelButton: true,
        confirmButtonText: 'Submit',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return $.ajax({
                url: ROUTE_URL,
                type: "POST",
                data: formdata,
                processData: false, contentType: false, dataType: "JSON"
            }).then(response => {
                if (response.STATUS !== "success") throw new Error(response.MESSAGE);
                return response;
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({ icon: 'success', title: 'Success', text: result.value.MESSAGE });
            window.open(ROUTE_URL + "?type=PrintOC");
            location.reload();
        }
    });
  }

  $('#table1 tbody').on('click', 'tr', function () {
      if (table1.rows().count() !== 0) {
          if ($(this).hasClass('selected')) {
              $(this).removeClass('selected');
              $("#cancel-btn").prop("disabled", true);
              SelectedFromTable = "";
          } else {
              table1.$('tr.selected').removeClass('selected');
              $(this).addClass('selected');
              $("#cancel-btn").prop("disabled", false);
              SelectedFromTable = this;
          }
      }
  });

  window.cancelProduct = function() {
      if (SelectedFromTable !== "") {
          table1.row(SelectedFromTable).remove().draw(false);
          SelectedFromTable = "";
          $("#cancel-btn").prop("disabled", true);
          if (table1.rows().count() === 0) $("#submit-btn").prop("disabled", true);
      }
  }

  function forBranchClearOC(){
    $("#type").val("");
    $("#category").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#itemSelect").empty().append(`<option value="" disabled selected>Select</option>`);
    $("#SIno").empty().append(`<option value="" disabled selected>Select SI No</option>`);
    serialProductList = [];
    clearSummary();
  }

  function LoadCategoryOC(type){
    $("#category").empty().append(`<option value="" disabled selected>Select</option>`);
    var list = categoriesByType[(type || "").toUpperCase()] || categoriesByType[type] || [];
    $.each(list, function(_,value){
      var c = (typeof value === 'string') ? value : (value["Category"] || value["category"] || "");
      if (c) $("#category").append(`<option value="${c}">${c}</option>`);
    });
    if (list.length === 0) {
      var isynBranch = $("#isynBranch").val();
      $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"LoadCategory", isConsign:"No", type:type, isynBranch:isynBranch, consignBranch:""},
        dataType:"JSON",
        success:function(response){
          $("#category").empty().append(`<option value="" disabled selected>Select</option>`);
          $.each(response.CATEG, function(_,value){
            $("#category").append(`<option value="${value["Category"]}">${value["Category"]}</option>`);
          });
          if (!response.CATEG || response.CATEG.length === 0) {
            $("#category").empty().append(`<option value="" disabled selected>No categories found</option>`);
          }
          $("#category").val("").trigger('change');
        }
      })
    } else {
      $("#category").val("").trigger('change');
    }
  }

  function LoadSerialProductOC(category){
    var type = $("#type").val();
    var isynBranch = $("#isynBranch").val();
    $.ajax({
      url: ROUTE_URL,
      type:"POST",
      data:{action:"LoadSerialProduct", isConsign:"No", type:type, category:category, isynBranch:isynBranch, consignBranch:""},
      dataType:"JSON",
      success:function(response){
        serialProductList = response.SRKPRDT || [];
        productSINoList = response.PRDTSINO || [];
        populateItemSelect();
        $("#SIno").empty().append(`<option value="" disabled selected>Select SI No</option>`);
        $.each(productSINoList, function(_,value){
          if (value["SIno"]) { $("#SIno").append(`<option value="${value["SIno"]}">${value["SIno"]}</option>`); }
        });
        $("#SIno").val("").trigger('change');
        clearSummary();
      }
    })
  }

  function populateItemSelect(){
    $("#itemSelect").empty().append(`<option value="" disabled selected>Select</option>`);
    $.each(serialProductList, function(_,value){
        let val = (selectBy === "serial") ? value["Serialno"] : value["Product"];
        $("#itemSelect").append(`<option value="${val}">${val}</option>`);
    });
  }

  function populateSINo(selected){
    $("#SIno").empty().append(`<option value="" disabled selected>Select SI No</option>`);
    $.each(serialProductList, function(_,value){
      if(value["Serialno"] == selected || value["Product"] == selected){
        $("#SIno").append(`<option value="${value["SIno"]}">${value["SIno"]}</option>`);
      }
    });
  }

  function LoadProductSummaryOC(selected){
    var isynBranch = $("#isynBranch").val();
    var type = $("#type").val();
    var category = $("#category").val();
    var SINo = $("#SIno").val();
    $.ajax({
      url: ROUTE_URL,
      type:"POST",
      data: { action:"LoadProductSummary", isConsign:"No", selectBy:selectBy, type:type, category:category, serialProduct:selected, SINo:SINo, isynBranch:isynBranch },
      dataType:"JSON",
      success:function(res){
        var info = res.PSUMMARY || {};
        $("#supplierSIdisplay").val(info.SIno || "");
        $("#serialNodisplay").val(info.Serialno || "");
        $("#productDisplay").val(info.Product || "");
        $("#supplierDisplay").val(info.Supplier || "");
        $("#srpDisplay").val(formatAmt(info.SRP || 0));
        $("#quantityDisplay").val(info.Quantity || "");
        $("#delearsPriceDisplay").val(formatAmt(info.DealerPrice || 0));
        $("#totalPriceDisplay").val(formatAmt(info.TotalPrice || 0));
        $("#warranty").val(info.Warranty || "");
      }
    })
  }

  function autoCompute(){
    var qty = parseFloat($("#quantityInput").val() || 0);
    var srpVal = $("#editSRPtoggle").is(":checked") ? $("#editSRP").val() : $("#srpDisplay").val();
    var srp = parseFloat(srpVal.replace(/,/g,'') || 0);
    $("#totalPriceDisplay").val(formatAmt(qty * srp));
  }

  function formatAmt(value){
    return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  function clearSummary(){
    $("#supplierSIdisplay, #serialNodisplay, #productDisplay, #supplierDisplay, #srpDisplay, #quantityDisplay, #delearsPriceDisplay, #totalPriceDisplay, #warranty").val("");
  }

  // Search Logic
  var SelectedOCNoSearch = "";
  window.OCSearch = function() {
    let client = $("#searchInput").val();
    let fromdate = $("#fromDate").val();
    let todate = $("#toDate").val();
    $.ajax({
        url: ROUTE_URL,
        type: "POST",
        data: { action: "OCSearch", client: client, fromdate: fromdate, todate: todate },
        dataType: "JSON",
        beforeSend: function(){
          $("#ocSearchTableBody").html(`<tr><td colspan="3" class="text-center text-muted">Searching...</td></tr>`);
          SelectedOCNoSearch = "";
          $("#printOCBtn").prop("disabled", true);
        },
        success: function(response) {
            if (response.STATUS === "ERROR") {
              $("#ocSearchTableBody").html(`<tr><td colspan="3" class="text-center text-danger">${response.MESSAGE}</td></tr>`);
              return;
            }
            let html = "";
            $.each(response.OCLIST, function(_, v) {
                html += `<tr onclick="selectOC('${v.OCNo}', this)">
                    <td>${v.OCNo}</td>
                    <td>${v.NameTO}</td>
                    <td>${v.DatePrepared}</td>
                </tr>`;
            });
            $("#ocSearchTableBody").html(html || `<tr><td colspan="3" class="text-center text-muted">No results</td></tr>`);
        },
        error: function(xhr, status, error){
          $("#ocSearchTableBody").html(`<tr><td colspan="3" class="text-center text-danger">Error: ${error}</td></tr>`);
        }
    });
  }

  window.selectOC = function(no, row) {
      SelectedOCNoSearch = no;
      $("#printOCBtn").prop("disabled", false);
      $("#ocSearchTableBody tr").removeClass("table-active");
      if (row) $(row).addClass("table-active");
  }
  
  window.printSelectedOC = function() {
      if (!SelectedOCNoSearch) return;
      window.open(ROUTE_URL + "?type=PrintOC&no=" + SelectedOCNoSearch, "_blank");
  }
});
