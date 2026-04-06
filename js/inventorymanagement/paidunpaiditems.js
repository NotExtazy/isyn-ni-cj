// Base path for AJAX requests
var BASE_PATH = window.BASE_PATH || '';
var ROUTE_URL = BASE_PATH + '/routes/inventorymanagement/paidunpaiditems.route.php';

$(function(){
  // Prevent future dates - REMOVED to allow older dates and future dates if necessary
  // var today = new Date().toISOString().split('T')[0];
  // $("#fromDate").attr("max", today);
  // $("#toDate").attr("max", today);

  // Set default dates if empty
  if(!$("#fromDate").val()) {
      // Default to current month start and end if empty
      // var today = new Date();
      // var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
      // var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
      
      // $("#fromDate").val(firstDay.toISOString().split('T')[0]);
      // $("#toDate").val(lastDay.toISOString().split('T')[0]);
  }

  $("#searchButton").on("click", function(){
    runPaidUnpaidSearch();
  });
  runPaidUnpaidSearch();
});

var summaryChart = null;

function fmt(n){
  var x = parseFloat(n||0);
  return x.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function renderSummaryBar(totals1, totals2){
  // Get labels from inputs
  var l1 = $("#fromDate").val() || "Year 1";
  var l2 = $("#toDate").val() || "Year 2";

  // Helper to safely parse float
  var safeFloat = function(v) {
      var f = parseFloat(v);
      return isNaN(f) ? 0 : f;
  };

  var series = [
    {
      name: l1,
      data: [
        safeFloat(totals1.TotalPrice),
        safeFloat(totals1.TotalSRP),
        safeFloat(totals1.TotalMarkup),
        safeFloat(totals1.TotalQty)
      ]
    },
    {
      name: l2,
      data: [
        safeFloat(totals2.TotalPrice),
        safeFloat(totals2.TotalSRP),
        safeFloat(totals2.TotalMarkup),
        safeFloat(totals2.TotalQty)
      ]
    }
  ];
  
  var options = {
    series: series,
    chart: { 
      type: 'bar', 
      height: 350, 
      toolbar: { show: false },
      fontFamily: 'Inter, sans-serif'
    },
    plotOptions: {
      bar: {
        borderRadius: 4,
        horizontal: false,
        columnWidth: '60%',
        dataLabels: {
          position: 'top', // top, center, bottom
        },
      }
    },
    dataLabels: {
      enabled: true,
      formatter: function (val, opts) {
          // Show value only if non-zero to avoid clutter
          if (val === 0) return "";
          // Check if it's quantity (index 3)
          if (opts && opts.dataPointIndex === 3) {
             return parseInt(val);
          }
          // For currency, maybe just show simplified number if space is tight
          // or nothing if it's too long. 
          // Let's show full formatted
          return fmt(val);
      },
      offsetY: -20,
      style: {
        fontSize: '10px',
        colors: ["#304758"]
      }
    },
    stroke: { show: true, width: 2, colors: ['transparent'] },
    xaxis: {
      categories: ['Total Price', 'Total SRP', 'Total Markup', 'Total Quantity'],
      labels: {
          style: {
              fontSize: '12px',
              fontWeight: 600
          }
      }
    },
    yaxis: {
      title: { text: 'Amount / Quantity' },
      labels: {
          formatter: function(val) {
              return fmt(val);
          }
      }
    },
    fill: { opacity: 1 },
    tooltip: {
      y: {
        formatter: function (val, opts) {
          // Check dataPointIndex to determine formatting
          // 0: Price, 1: SRP, 2: Markup, 3: Quantity
          if (opts && opts.dataPointIndex === 3) {
             return parseInt(val) + " pcs";
          }
          return "₱ " + fmt(val);
        }
      }
    },
    colors: ['#435ebe', '#198754'],
    grid: {
        borderColor: '#f1f1f1',
    }
  };

  var chartEl = document.querySelector("#summaryBar");
  if (!chartEl) {
      console.error("Summary Bar Container not found!");
      return;
  }
  
  // Clear any loading text
  $("#summaryBar").empty();

  // Ensure container has height
  chartEl.style.minHeight = "350px";

  if (summaryChart){
    summaryChart.updateOptions(options);
    summaryChart.updateSeries(series);
  } else {
    if (typeof ApexCharts !== 'undefined') {
        summaryChart = new ApexCharts(chartEl, options);
        summaryChart.render();
    } else {
        $("#summaryBar").html('<div class="text-danger text-center p-5">Error: Charts library not loaded.</div>');
    }
  }
}

function renderSlice(rowsHtmlArray, tbodySelector, page, pageSize, emptyColspan){
  var start = page * pageSize;
  var end = Math.min(start + pageSize, rowsHtmlArray.length);
  var slice = rowsHtmlArray.slice(start, end);
  $(tbodySelector).html(slice.length ? slice.join("") : `<tr><td colspan="${emptyColspan}" class="text-center text-muted">No results</td></tr>`);
}

function setupPagination(containerId, total, pageSize, onPageChange){
  var $c = $("#"+containerId);
  if (!$c.length){
    onPageChange(0);
    return;
  }
  var pages = Math.ceil(total / pageSize);
  if (pages <= 1){
    $c.html("");
    onPageChange(0);
    return;
  }
  var current = 0;
  function renderControls(){
    $c.html(`
      <div class="d-flex justify-content-end align-items-center gap-2 mt-2">
        <button class="btn btn-sm btn-outline-secondary" id="${containerId}-prev" ${current===0?'disabled':''}>Prev</button>
        <span class="small">Page ${current+1} of ${pages}</span>
        <div class="input-group input-group-sm" style="width: 150px;">
          <input type="number" min="1" max="${pages}" class="form-control" id="${containerId}-jump" placeholder="Page">
          <button class="btn btn-outline-secondary" id="${containerId}-go">Go</button>
        </div>
        <button class="btn btn-sm btn-outline-secondary" id="${containerId}-next" ${current>=pages-1?'disabled':''}>Next</button>
      </div>
    `);
    $("#"+containerId+"-prev").off("click").on("click", function(){
      if (current > 0){
        current--;
        onPageChange(current);
        renderControls();
      }
    });
    $("#"+containerId+"-next").off("click").on("click", function(){
      if (current < pages - 1){
        current++;
        onPageChange(current);
        renderControls();
      }
    });
    $("#"+containerId+"-go").off("click").on("click", function(){
      var v = parseInt($("#"+containerId+"-jump").val(), 10);
      if (!isNaN(v)){
        var target = Math.min(Math.max(v-1, 0), pages-1);
        if (target !== current){
          current = target;
          onPageChange(current);
          renderControls();
        }
      }
    });
    $("#"+containerId+"-jump").off("keydown").on("keydown", function(e){
      if (e.key === "Enter"){
        $("#"+containerId+"-go").click();
      }
    });
  }
  onPageChange(current);
  renderControls();
}

function runPaidUnpaidSearch(){
  var fromDate = $("#fromDate").val();
  var toDate = $("#toDate").val();
  var isConsign = $("#consignmentCheckbox").is(":checked") ? "Yes" : "No";
  var typeVal = $("#typeSelect").val(); // "1" paid, "2" unpaid
  var withSI = $("#flexCheckIndeterminate").is(":checked");
  
  if ((fromDate && !toDate) || (!fromDate && toDate)){
    Swal.fire({icon:'warning', title:'Complete the date range'});
    return;
  }
  
  $.ajax({
    url: ROUTE_URL,
    type: "POST",
    data: { action: "SearchPaidUnpaid", fromDate: fromDate, toDate: toDate, isConsign: isConsign, typeVal: typeVal, withSI: withSI ? "Yes" : "No" },
    dataType: "JSON",
    beforeSend: function(){
      $("#itemsTableBody").html(`<tr><td colspan="10" class="text-center text-muted">Loading...</td></tr>`);
      $("#clientListTbody").html("");
      $("#itemsPagination").html("");
      $("#clientPagination").html("");
      
      // Reset chart area
      if (summaryChart){ try { summaryChart.destroy(); } catch(e){} summaryChart = null; }
      $("#summaryBar").html('<div class="d-flex justify-content-center align-items-center h-100 text-muted">Loading Graph...</div>');
      
      $("#totalClientsCount").val("");
      $("#totalPayables").val("");
      $("#totalPriceLabel").text("0.00");
      $("#totalSRPLabel").text("0.00");
      $("#totalMarkupLabel").text("0.00");
      $("#totalQuantityLabel").text("0");
    },
    success: function(res){
      var items1 = res.items1 || [];
      var items2 = res.items2 || [];
      var totals1 = res.totals1 || {TotalPrice:0, TotalSRP:0, TotalMarkup:0, TotalQty:0};
      var totals2 = res.totals2 || {TotalPrice:0, TotalSRP:0, TotalMarkup:0, TotalQty:0};
      var clients1 = res.clients1 || [];
      var clients2 = res.clients2 || [];
      
      var y1 = $("#fromDate").val(); // YYYY
      var y2 = $("#toDate").val();   // YYYY
      
      // Update comparison labels
      $("#comparison-label-1").text(y1 || "Year 1");
      $("#comparison-label-2").text(y2 || "Year 2");
      
      // Update Column Headers
      $("#year1-label").text(y1 || "Year 1");
      $("#year2-label").text(y2 || "Year 2");
      
      // Update Client & Items Labels
      $("#client-label-1").text(y1 || "Year 1");
      $("#items-label-1").text(y1 || "Year 1");
      $("#client-label-2").text(y2 || "Year 2");
      $("#items-label-2").text(y2 || "Year 2");
      
      // Update Column 1
      $("#totalPriceLabel1").text(fmt(totals1.TotalPrice));
      $("#totalSRPLabel1").text(fmt(totals1.TotalSRP));
      $("#totalMarkupLabel1").text(fmt(totals1.TotalMarkup));
      $("#totalQuantityLabel1").text(parseInt(totals1.TotalQty));
      
      // Update Column 2
      $("#totalPriceLabel2").text(fmt(totals2.TotalPrice));
      $("#totalSRPLabel2").text(fmt(totals2.TotalSRP));
      $("#totalMarkupLabel2").text(fmt(totals2.TotalMarkup));
      $("#totalQuantityLabel2").text(parseInt(totals2.TotalQty));
      
      renderSummaryBar(totals1, totals2);

      // Render Table 1 (Items)
      var itemRows1 = items1.map(function(x){
        return `<tr class="item-row" data-si="${x.SI || ''}" data-date="${x.DateAdded || ''}" data-branch="${x.Branch || ''}">
          <td>${x.SI || "-"}</td>
          <td>${x.DateAdded || "-"}</td>
          <td>${x.Status || "-"}</td>
          <td>${x.Branch || "-"}</td>
          <td>${x.Product || "-"}</td>
          <td class="text-end">${fmt(x.DealerPrice)}</td>
          <td class="text-end">${fmt(x.TotalPrice)}</td>
          <td class="text-end">${fmt(x.VatSales)}</td>
          <td class="text-end">${fmt(x.TotalSRP)}</td>
          <td>${x.Type || "-"}</td>
        </tr>`;
      });
      setupPagination("itemsPagination1", itemRows1.length, 6, function(page){
        renderSlice(itemRows1, "#itemsTableBody1", page, 6, 10);
      });
      
      // Render Table 2 (Items)
      var itemRows2 = items2.map(function(x){
        return `<tr class="item-row" data-si="${x.SI || ''}" data-date="${x.DateAdded || ''}" data-branch="${x.Branch || ''}">
          <td>${x.SI || "-"}</td>
          <td>${x.DateAdded || "-"}</td>
          <td>${x.Status || "-"}</td>
          <td>${x.Branch || "-"}</td>
          <td>${x.Product || "-"}</td>
          <td class="text-end">${fmt(x.DealerPrice)}</td>
          <td class="text-end">${fmt(x.TotalPrice)}</td>
          <td class="text-end">${fmt(x.VatSales)}</td>
          <td class="text-end">${fmt(x.TotalSRP)}</td>
          <td>${x.Type || "-"}</td>
        </tr>`;
      });
      setupPagination("itemsPagination2", itemRows2.length, 6, function(page){
        renderSlice(itemRows2, "#itemsTableBody2", page, 6, 10);
      });
      
      // Render Client Table 1
      var clientCount1 = 0;
      var clientTotal1 = 0;
      var clientRows1 = clients1.map(function(c){
        clientCount1++;
        clientTotal1 += parseFloat(c.TotalPayables || 0);
        return `<tr class="client-row" data-customer="${c.Customer}">
          <td class="text-start">${c.Customer}</td>
          <td class="text-end">${parseInt(c.TotalQty || 0)}</td>
          <td class="text-end">${fmt(c.TotalPayables)}</td>
        </tr>`;
      });
      setupPagination("clientPagination1", clientRows1.length, 5, function(page){
        renderSlice(clientRows1, "#clientListTbody1", page, 5, 2);
      });
      $("#clientSearch1").off("input").on("input", function(){
        var q = ($(this).val() || "").toUpperCase();
        var filtered = clientRows1.filter(function(html){ return html.toUpperCase().indexOf(q) > -1; });
        setupPagination("clientPagination1", filtered.length, 5, function(page){
          renderSlice(filtered, "#clientListTbody1", page, 5, 2);
        });
      });
      $("#totalClientsCount1").val(clientCount1);
      $("#totalPayables1").val(fmt(clientTotal1));
      
      // Render Client Table 2
      var clientCount2 = 0;
      var clientTotal2 = 0;
      var clientRows2 = clients2.map(function(c){
        clientCount2++;
        clientTotal2 += parseFloat(c.TotalPayables || 0);
        return `<tr class="client-row" data-customer="${c.Customer}">
          <td class="text-start">${c.Customer}</td>
          <td class="text-end">${parseInt(c.TotalQty || 0)}</td>
          <td class="text-end">${fmt(c.TotalPayables)}</td>
        </tr>`;
      });
      setupPagination("clientPagination2", clientRows2.length, 5, function(page){
        renderSlice(clientRows2, "#clientListTbody2", page, 5, 2);
      });
      $("#clientSearch2").off("input").on("input", function(){
        var q = ($(this).val() || "").toUpperCase();
        var filtered = clientRows2.filter(function(html){ return html.toUpperCase().indexOf(q) > -1; });
        setupPagination("clientPagination2", filtered.length, 5, function(page){
          renderSlice(filtered, "#clientListTbody2", page, 5, 2);
        });
      });
      $("#totalClientsCount2").val(clientCount2);
      $("#totalPayables2").val(fmt(clientTotal2));

      // Client Click Handlers
      // Helper to fetch details
      function fetchClientDetails(customer, date) {
          var isConsign = $("#consignmentCheckbox").is(":checked") ? "Yes" : "No";
          var typeVal = $("#typeSelect").val();
          var withSI = $("#flexCheckIndeterminate").is(":checked") ? "Yes" : "No";
          
          // Note: Backend GetClientDetails expects fromDate/toDate for range.
          // But here we want a specific month.
          // We can pass the same month as both from/to to force the month filter logic we built.
          
          $.ajax({
            url: ROUTE_URL,
            type: "POST",
            data: { 
                action: "GetClientDetails", 
                customer: customer, 
                fromDate: date, 
                toDate: date, // Same date forces single month check
                isConsign: isConsign, 
                typeVal: typeVal, 
                withSI: withSI 
            },
            dataType: "JSON",
            beforeSend: function(){
              $("#clientDetailsBody").html('<div class="text-center text-muted">Loading...</div>');
            },
            success: function(res){
              var items = res.items || [];
              var total = res.total || 0;
              var header = `
                <div class="mb-3">
                  <div class="fw-bold">Customer: ${customer}</div>
                  <div class="text-muted small">Year: ${date}</div>
                  <div>Total Payables: ${fmt(total)}</div>
                </div>`;
              var table = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>SI</th><th>Date</th><th>Branch</th><th>Status</th><th>Product</th><th class="text-end">Quantity</th><th class="text-end">Amount Due</th><th class="text-end">Total Price</th><th class="text-end">Total SRP</th><th>Type</th></tr></thead><tbody>';
              items.forEach(function(x){
                table += `<tr>
                  <td>${x.SI || '-'}</td>
                  <td>${x.DateAdded || '-'}</td>
                  <td>${x.Branch || '-'}</td>
                  <td>${x.Status || '-'}</td>
                  <td>${x.Product || '-'}</td>
                  <td class="text-end">${fmt(x.Quantity)}</td>
                  <td class="text-end">${fmt(x.AmountDue)}</td>
                  <td class="text-end">${fmt(x.TotalPrice)}</td>
                  <td class="text-end">${fmt(x.TotalSRP)}</td>
                  <td>${x.Type || '-'}</td>
                </tr>`;
              });
              table += '</tbody></table></div>';
              $("#clientDetailsBody").html(header + table);
              var modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
              modal.show();
            },
            error: function(xhr, status, error){
              $("#clientDetailsBody").html(`<div class="text-center text-danger">Error: ${error}</div>`);
              var modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
              modal.show();
            }
          });
      }

      $(document).off("click", "#clientListTbody1 tr.client-row").on("click", "#clientListTbody1 tr.client-row", function(){
          var customer = $(this).data("customer");
          fetchClientDetails(customer, y1);
      });
      $(document).off("click", "#clientListTbody2 tr.client-row").on("click", "#clientListTbody2 tr.client-row", function(){
          var customer = $(this).data("customer");
          fetchClientDetails(customer, y2);
      });

      // Item Search Handlers (Client side filtering for tables)
      $("#tableSearch1").off("keyup").on("keyup", function(){
          var q = $(this).val().toUpperCase();
          var filtered = itemRows1.filter(function(html){ return html.toUpperCase().indexOf(q) > -1; });
          setupPagination("itemsPagination1", filtered.length, 6, function(page){
              renderSlice(filtered, "#itemsTableBody1", page, 6, 10);
          });
      });
      $("#tableSearch2").off("keyup").on("keyup", function(){
          var q = $(this).val().toUpperCase();
          var filtered = itemRows2.filter(function(html){ return html.toUpperCase().indexOf(q) > -1; });
          setupPagination("itemsPagination2", filtered.length, 6, function(page){
              renderSlice(filtered, "#itemsTableBody2", page, 6, 10);
          });
      });
    },
    error: function(xhr, status, error){
      $("#itemsTableBody1").html(`<tr><td colspan="10" class="text-center text-danger">Error: ${error}</td></tr>`);
      $("#itemsTableBody2").html(`<tr><td colspan="10" class="text-center text-danger">Error: ${error}</td></tr>`);
    }
  });
}

