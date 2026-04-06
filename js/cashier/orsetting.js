let Mode;

LoadInit();

function LoadInit (){
    $.ajax({
        url: "../../routes/cashier/orsetting.route.php",
        type: "POST",
        data: {action:"LoadORs"},
        dataType: "JSON",
        beforeSend: function() {
            $("#orList").empty();
            $("#orList").append("<tr><td class='text-center'>Loading..</td></tr>");
        },
        success: function(response) {

            $("#orList").empty();
            $.each(response.ORLIST,function(key,value){
                $("#orList").append(`
                    <tr>
                        <td>${value["PONICK"]}</td>
                    </tr>
                `);
            });

            orTbl = $('#orTbl').DataTable({
                scrollY: '250px',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                bFilter:false,
                info:true,
            });
        },
        error: function(err) {
            console.log(err)
        }
    });
}

$('#orTbl tbody').on('click', 'tr', function(e) {
    if(orTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $('#addButton').prop('disabled', true);
            $('#editButton').prop('disabled', true);
            $('#cancelButton').prop('disabled', true);
        } else {
            orTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            $('#addButton').prop('disabled', true);
            $('#editButton').prop('disabled', true);
            $('#cancelButton').prop('disabled', true);
            $('#saveButton').prop('disabled', true);
            $('#cancelButton').prop('disabled', true);

            let rowData = orTbl.row(this).data();
            let Name = rowData[0];

            $.ajax({
                url: "../../routes/cashier/orsetting.route.php",
                type: "POST",
                data: {action:"GetORData", Name:Name},
                dataType: "JSON",
                beforeSend: function() {
                    console.log('loading types...')
                },
                success: function(response) {
                    let data = response.ORDATA;

                    if (data){
                        $("#orID").prop('disabled', true).val(data["ID"]);
                        $("#orName").prop('disabled', true).val(Name);
                        $("#seriesStatus").prop('disabled', true).val(data["ORStatus"]);
                        $("#from").prop('disabled', true).val(data["ORFrom"]);
                        $("#to").prop('disabled', true).val(data["ORTo"]);
                        $("#nextOR").prop('disabled', true).val(data["NextOR"]);
                        $("#orsleft").prop('disabled', true).val(data["ORLeft"]);

                        $('#addButton').prop('disabled', true);
                        $('#editButton').prop('disabled', false);
                        $('#cancelButton').prop('disabled', true);
                        $('#saveButton').prop('disabled', true);
                        $('#cancelButton').prop('disabled', true);
                    } else {
                        $("#orID").prop('disabled', true).val("");
                        $("#orName").prop('disabled', true).val(Name);
                        $("#seriesStatus").prop('disabled', true).val("");
                        $("#from").prop('disabled', true).val("0");
                        $("#to").prop('disabled', true).val("0");
                        $("#nextOR").prop('disabled', true).val("0");
                        $("#orsleft").prop('disabled', true).val("0");

                        $('#addButton').prop('disabled', false);
                        $('#editButton').prop('disabled', true);
                        $('#cancelButton').prop('disabled', true);
                        $('#saveButton').prop('disabled', true);
                        $('#cancelButton').prop('disabled', true);
                    }
                },
                error: function(err) {
                    console.log(err)
                }
            });
        }
    }
});

function AddNewSeries (){
    Mode = "ADD";

    $("#seriesStatus").prop('disabled', false);
    $("#from").prop('disabled', false);
    $("#to").prop('disabled', false);
    $("#nextOR").prop('disabled', false);
    $("#orsleft").prop('disabled', false);

    $("#saveButton").prop('disabled', false);
    $("#cancelButton").prop('disabled', false);
}

function EditSeries (){
    Mode = "EDIT";

    $("#seriesStatus").prop('disabled', false);
    $("#from").prop('disabled', false);
    $("#to").prop('disabled', false);
    $("#nextOR").prop('disabled', false);
    $("#orsleft").prop('disabled', false);

    $("#saveButton").prop('disabled', false);
    $("#cancelButton").prop('disabled', false);
}

function Cancel (){
    Mode = "";

    $("#orID").val("");
    $("#orName").val("");
    $("#seriesStatus").prop('disabled', true).val("");
    $("#from").prop('disabled', true).val("");
    $("#to").prop('disabled', true).val("");
    $("#nextOR").prop('disabled', true).val("");
    $("#orsleft").prop('disabled', true).val("");
    
    $('#addButton').prop('disabled', true);
    $('#editButton').prop('disabled', true);
    $('#cancelButton').prop('disabled', true);
    $('#saveButton').prop('disabled', true);
    $('#cancelButton').prop('disabled', true);

    $('#orTbl tr').removeClass('selected');
}

$('#from').on('input', function() {
    updateCalculatedFields('#from','#nextOR','#to','#orsleft');
});

$('#to').on('input', function() {
    updateCalculatedFields('#from','#nextOR','#to','#orsleft');
});

$('#nextOR').on('input', function() {
    calculateOrLeftByNextOr('#from','#nextOR','#to','#orsleft');
});

function calculateOrLeftByNextOr (fromInput, nextORInput, toInput, leftInput){
    // Trigger the calculation of orLeft when nextOR changes
    var fromORValue = parseFloat($(fromInput).val());
    var toORValue = parseFloat($(toInput).val());
    var nextORValue = parseFloat($(nextORInput).val());

    // Calculate orLeft value
    // if (nextORValue < fromORValue){
    //     Swal.fire({
    //         icon: "warning",
    //         text: "OR Number is less than the series set",
    //     });
    // } else {
        var orLeftValue = toORValue - nextORValue + 1;
        orLeftValue = Math.max(orLeftValue, 0);
        $(leftInput).val(orLeftValue);
    // }
}

function updateCalculatedFields(fromInput, nextORInput, toInput, leftInput) {
    var fromORValue = parseFloat($(fromInput).val());
    if (isNaN(fromORValue)) {
        fromORValue = 0;
    }

    $(nextORInput).val(fromORValue);

    var toORValue = parseFloat($(toInput).val());
    if (isNaN(toORValue)) {
        toORValue = 0;
    }

    var nextORValue = parseFloat($(nextORInput).val());
    if (isNaN(nextORValue)) {
        nextORValue = 0;
    }

    // Calculate orLeft value
    var orLeftValue = toORValue - nextORValue + 1;
    orLeftValue = Math.max(orLeftValue, 0);

    // Update the input fields
    $(leftInput).val(orLeftValue);
}

function saveSeries () {
    let id =$("#orID").val();
    let orName =$("#orName").val();
    let seriesStatus =$("#seriesStatus").val();
    let from = $("#from").val();
    let to = $("#to").val();
    let nextor = $("#nextOR").val();
    let orsleft = $("#orsleft").val();

    if (from == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing From series.',
        })
        return;
    } else if (to == "") {
        Swal.fire({
            icon: 'warning',
            title: 'Missing To series',
        })
        return;
    } else if (seriesStatus == "" || seriesStatus == null) {
        Swal.fire({
            icon: 'warning',
            title: 'Select series status.',
        })
        return;
    } else if (orsleft <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid OR series',
        })
        return;
    } else if (nextor < from) {
        Swal.fire({
            icon: 'warning',
            title: 'OR number is less than From series set.',
        })
        return;
    } else {
        Swal.fire({
            icon: 'question',
            title: 'Save OR Setting?',
            showCancelButton: true,
            showLoaderOnConfirm: true,
            confirmButtonColor: '#435ebe',
            confirmButtonText: 'Yes, proceed!',
            // allowOutsideClick: false,
            preConfirm: function() {
                return $.ajax({
                    url: "../../routes/cashier/orsetting.route.php",
                    type: "POST",
                    data: {action:"SaveSeries", id:id,name:orName,from:from,to:to,nextor:nextor,orsleft:orsleft,Mode:Mode,seriesStatus:seriesStatus},
                    dataType: 'JSON',
                    beforeSend: function() {
                        console.log('Processing Request...')
                    },
                    success: function(response) {
                        if (response.STATUS == 'SUCCESS') {
                          console.log('Request Processed...')
                          resetform();
                        }
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });
            },
        }).then(function(result) {
            if (result.isConfirmed) {
                if (result.value.STATUS == 'SUCCESS') {
                    Swal.fire({
                      icon: "success",
                      text: "OR Setting Saved Successfully",
                  });
                } else if (result.value.STATUS != 'SUCCESS') {
                    Swal.fire({
                        icon: "warning",
                        text: "OR Setting Failed to Save",
                    });
                }
            }
        });
    }
 }

 function resetform (){
    Cancel();
}

function formatInput(input) {
    let cleanValue = input.value.replace(/[^0-9.,]/g, '');
    cleanValue = cleanValue.replace(/,/g, '');
    if (cleanValue !== '') {
        input.value = cleanValue;
    } else {
        input.value = '0';
    }
}