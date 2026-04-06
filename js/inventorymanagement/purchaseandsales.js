var productTbl, State = "NONE";

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
};

$('#fromAsOf').datetimepicker(options);
$('#toAsOf').datetimepicker(options);
$('#month').datetimepicker(options);

function AsOf(option){
    if (option == "FromTo"){
        $('#fromAsOf').prop("disabled", false);
        $('#toAsOf').prop("disabled", false);
        $('#month').prop("disabled", true);
    } else if (option == "Monthly") {
        $('#fromAsOf').prop("disabled", true);
        $('#toAsOf').prop("disabled", true);
        $('#month').prop("disabled", false);
    } else {
        $('#fromAsOf').prop("disabled", true);
        $('#toAsOf').prop("disabled", true);
        $('#month').prop("disabled", true);
    }
}

function Search(){
    var purchaseSelect = $('#purchaseSelect').val();
    var option = "";
    
    if ($('#asof').is(':checked')){
        option = "asof";
    }
    var fromAsOf = $('#fromAsOf').val();
    var toAsOf = $('#toAsOf').val();
    
    if ($('#asofMonthly').is(':checked')){
        option = "month";
    }
    var month = $('#month').val();
    
    if (option == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Please select an option (As Of or As Of Monthly).',
        })
        return;
    }
    
    if (option == "asof"){
        if (fromAsOf == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Please select a From Date.',
            })
            return;
        }
        if (toAsOf == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Please select a To Date.',
            })
            return;
        }
        if (new Date(toAsOf) < new Date(fromAsOf)){
            Swal.fire({
                icon: 'warning',
                title: 'To Date must be greater than or equal to From Date.',
            })
            return;
        }
    }

    if (option == "month" && month == ""){
        Swal.fire({
            icon: 'warning',
            title: 'Please select a month date',
        })
        return;
    }

    $.ajax({
        url:"../../routes/inventorymanagement/purchaseandsales.route.php",
        type:"POST",
        data:{action:"GenerateJournalReport", purchaseSelect:purchaseSelect,option:option,fromAsOf:fromAsOf,toAsOf:toAsOf,month:month},
        dataType:"JSON",
        success:function(response){
            window.open("../../routes/inventorymanagement/purchaseandsales.route.php?type=PrintJournalReport");
        }
    })
}