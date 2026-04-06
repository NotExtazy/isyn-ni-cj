// Base path for AJAX requests
var BASE_PATH = window.BASE_PATH || '';
var ROUTE_URL = BASE_PATH + '/routes/inventorymanagement/inventorybalancing.route.php';

$(document).ready(function() {
    Initialize();
    
    // Use .off() to remove any existing handlers before attaching a new one
    // to prevent multiple firings, and use direct click handler
    $("#recomputeButton").off('click').on("click", function(e) {
        e.preventDefault(); // Prevent default button behavior
        console.log("Recompute button clicked"); // Debug log
        
        // Add a loading state to the button
        let $btn = $(this);
        let originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Computing...');
        
        Initialize(function() {
            // Callback to reset button state
            $btn.prop('disabled', false).html(originalText);
            
            // Optional: Show a small toast/alert that it's updated
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            
            Toast.fire({
                icon: 'success',
                title: 'Inventory re-computed successfully'
            });
        });
    });
});

function Initialize(callback){
    console.log("Initialize called");
    $.ajax({
        url: ROUTE_URL,
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
            Clear();
        },
        success:function(response){
            console.log("Initialize success", response);
            let data = response;
            $("#lastInvEndTotalQty").val(formatAmtVal(data.QTYINVEND));
            $("#lastInvEndTotalDP").val(formatAmtVal(data.DPINVEND));

            $("#InvInTotalQty").val(formatAmtVal(data.QTYINVIN));
            $("#InvInTotalDP").val(formatAmtVal(data.DPINVIN));
            
            $("#transferProductReceivedTotalQty").val(formatAmtVal(data.QTYRECEIVED));
            $("#transferProductReceivedTotalDP").val(formatAmtVal(data.DPRECEIVED));
            
            $("#InvOutTotalQty").val(formatAmtVal(data.QTYINVOUT));
            $("#InvOutTotalDP").val(formatAmtVal(data.DPINVOUT));
            
            $("#transferProductTransferTotalQty").val(formatAmtVal(data.QTYTRANSFER));
            $("#transferProductTransferTotalDP").val(formatAmtVal(data.DPTRANSFER));

            $("#PurchaseReturnedTotalQty").val(formatAmtVal(data.QTYRETURNED));
            $("#PurchaseReturnedTotalDP").val(formatAmtVal(data.DPRETURNED));

            $("#EndOfMonthTotalQty").val(formatAmtVal(data.TOTALQTY));
            $("#EndOfMonthTotalDP").val(formatAmtVal(data.TOTALDP));
            
            $("#CurrentInvTotalQty").val(formatAmtVal(data.QTYINVCURR));
            $("#CurrentInvTotalDP").val(formatAmtVal(data.DPINVCURR));

            $("#DifferenceTotalQty").val(formatAmtVal(data.QTYINVDIF));
            $("#DifferenceTotalDP").val(formatAmtVal(data.DPINVDIF));

            if (response.QTYINVDIFSTAT == "Negative" || response.DPINVDIFSTAT == "Negative") {
                $('#diffLbl').addClass('Negative');
            } else {
                $('#diffLbl').removeClass('Negative');
            }

            if (response.QTYINVDIFSTAT == "Negative") {
                $('#DifferenceTotalQty').addClass('Negative');
            } else {
                $('#DifferenceTotalQty').removeClass('Negative');
            }

            if (response.DPINVDIFSTAT == "Negative") {
                $('#DifferenceTotalDP').addClass('Negative');
            } else {
                $('#DifferenceTotalDP').removeClass('Negative');
            }

            $("#ChangeDealerPrice").val(formatAmtVal(data.CHANGEDP));
            
            if (callback && typeof callback === 'function') {
                callback();
            }
        },
        error: function(xhr, status, error) {
            console.error("Initialize error", error);
            if (callback && typeof callback === 'function') {
                callback();
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to re-compute inventory. Please try again.'
            });
        }
    })
}

function Clear(){
    $("#lastInvEndTotalQty").val("");
    $("#lastInvEndTotalDP").val("");

    $("#InvInTotalQty").val("");
    $("#InvInTotalDP").val("");
    
    $("#transferProductReceivedTotalQty").val("");
    $("#transferProductReceivedTotalDP").val("");
    
    $("#InvOutTotalQty").val("");
    $("#InvOutTotalDP").val("");
    
    $("#transferProductTransferTotalQty").val("");
    $("#transferProductTransferTotalDP").val("");

    $("#PurchaseReturnedTotalQty").val("");
    $("#PurchaseReturnedTotalDP").val("");

    $("#EndOfMonthTotalQty").val("");
    $("#EndOfMonthTotalDP").val("");
    
    $("#CurrentInvTotalQty").val("");
    $("#CurrentInvTotalDP").val("");

    $("#DifferenceTotalQty").val("");
    $("#DifferenceTotalDP").val("");

    $("#ChangeDealerPrice").val("");
}

// =======================================================================================

function formatAmtVal(value) {
    if (value === undefined || value === null) {
        return '0.00';
    }
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
