var productTbl;

Initialize();

function Initialize(){
    $.ajax({
        url:"../../routes/inventorymanagement/searchproducts.route.php",
        type:"POST",
        data:{action:"Initialize"},
        dataType:"JSON",
        beforeSend:function(){
            if ( $.fn.DataTable.isDataTable( '#productTbl' ) ) {
                $('#productTbl').DataTable().clear();
                $('#productTbl').DataTable().destroy(); 
            }
        },
        success:function(response){
            $("#productList").empty();
            $.each(response.LIST,function(key,value){
                $("#productList").append(`
                    <tr>
                        <td>${value["Product"]}</td>
                        <td>${value["Category"]}</td>
                        <td>${value["Quantity"]}</td>
                        <td>${value["SRP"]}</td>
                        <td>${value["TotalSRP"]}</td>
                        <td>${value["DealerPrice"]}</td>
                        <td>${value["TotalPrice"]}</td>
                        <td>${value["Warranty"]}</td>
                    </tr>
                `);
            });

            productTbl = $('#productTbl').DataTable({
                // searching:true,
                // ordering:true,
                // info:true,
                // paging:false,
                // lengthChange:true,
                // scrollY: '445px',
                // scrollX: true,  
                // scrollCollapse: true,
                // responsive:false,
                columnDefs: [
                    { targets: [ 4,5,6,7 ], visible:false, searchable:false }
                ],
            });
        }
    })
}

$('#productTbl tbody').on('click', 'tr',function(e){
    if(productTbl.rows().count() !== 0){
        let classList = e.currentTarget.classList;
        if (classList.contains('selected')) {
            classList.remove('selected');
            $('#productName').val("");
            $('#category').val("");
            $('#warranty').val("");
            $('#quantity').val("");
            $('#dealerPrice').val("");
            $('#totalDP').val("");
            $('#totalSRP').val("");
            $('#SRP').val("");
        } else {
            productTbl.rows('.selected').nodes().each((row) => {
                row.classList.remove('selected');
            });
            classList.add('selected');
            var data = $('#productTbl').DataTable().row(this).data();

            $('#productName').val(data[0]);
            $('#category').val(data[1]);
            $('#warranty').val(data[7]);
            $('#quantity').val(data[2]);
            $('#dealerPrice').val(data[5]);
            $('#totalDP').val(data[6]);
            $('#totalSRP').val(data[4]);
            $('#SRP').val(data[3]);
        }
    }
});

// =======================================================================================

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