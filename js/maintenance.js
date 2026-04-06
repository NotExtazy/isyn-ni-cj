// MAINTENANCE RETRIEVING
$(window).ready(function(){
    $.ajax({
        url:"../../routes/maintenance.route.php",
        type:"POST",
        data:{maintenance_action:"get_All"},
        dataType:"JSON",
        success:function(data){
            //$("#studno").val(data.curr_empno);
            // window.value = data.curr_empno;
            // compensation = data.compensation;

            $.each(data.region, function(key,value){
                $("#Region").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.category, function(key,value){
                $("#prodCategory").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.customertype, function(key,value){
                $("#customerType").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.gender, function(key,value){
                $("#gender").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.shareholdertype, function(key,value){
                $("#shareholder_type").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.shareholdercat, function(key,value){
                $("#type").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.employeestatus, function(key,value){
                $("#employee_status").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.designation, function(key,value){
                $("#designation").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.boddesig, function(key,value){
                $("#BODdesignation").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.committee, function(key,value){
                $("#committeeType").append("<option value='"+value+"'>"+value+"</option>");
            })
            $.each(data.specialdesig, function(key,value){
                $("#specializedposition").append("<option value='"+value+"'>"+value+"</option>");
            })
        }
    })

    $("#Region").on("change",function(){
        var region_selected = $(this).val();
        if(region_selected != ''){
            $.ajax({
                url:"../../routes/maintenance.route.php",
                type:"POST",
                data:{maintenance_action:"get_province",region_selected:region_selected},
                dataType:"JSON",
                success:function(data){
                    $("#Province").empty();
                    $("#CityTown").empty();
                    $("#Barangay").empty();
                    $("#Province").append("<option value='' disabled selected>-</option>");
                    $.each(data, function(key,value){
                        $("#Province").append("<option value='"+value+"'>"+value+"</option>");
                    })
                }
            })
        }
    })

    $("#Province").on("change",function(){
        var province_selected = $(this).val();
        if(province_selected != ''){
            $.ajax({
                url:"../../routes/maintenance.route.php",
                type:"POST",
                data:{maintenance_action:"get_citytown",province_selected:province_selected},
                dataType:"JSON",
                success:function(data){
                    $("#CityTown").empty();
                    $("#Barangay").empty();
                    $("#CityTown").append("<option value='' disabled selected>-</option>");
                    $.each(data, function(key,value){
                        $("#CityTown").append("<option value='"+value+"'>"+value+"</option>");
                    })
                }
            })
        }
    })

    $("#CityTown").on("change",function(){
        var citytown_selected = $(this).val();
        if(citytown_selected != ''){
            $.ajax({
                url:"../../routes/maintenance.route.php",
                type:"POST",
                data:{maintenance_action:"get_brgy",citytown_selected:citytown_selected},
                dataType:"JSON",
                success:function(data){
                    $("#Barangay").empty();
                    $("#Barangay").append("<option value='' disabled selected>-</option>");
                    $.each(data, function(key,value){
                        $("#Barangay").append("<option value='"+value+"'>"+value+"</option>");
                    })
                }
            })
        }
    })  
})

function LoadProvince(region,value){
    $.ajax({
        url:"../../routes/maintenance.route.php",
        type:"POST",
        data:{maintenance_action:"get_province",region_selected:region},
        dataType:"JSON",
        success:function(data){
            $("#Province").empty();
            $("#Province").append("<option value='' disabled selected>-</option>");
            $.each(data, function(key,value){
                $("#Province").append("<option value='"+value+"'>"+value+"</option>");
            })
            $("#Province").val(value);
        }
    })
}

function LoadCitytown(province,value){
    $.ajax({
        url:"../../routes/maintenance.route.php",
        type:"POST",
        data:{maintenance_action:"get_citytown",province_selected:province},
        dataType:"JSON",
        success:function(data){
            $("#CityTown").empty();
            $("#CityTown").append("<option value='' disabled selected>-</option>");
            $.each(data, function(key,value){
                $("#CityTown").append("<option value='"+value+"'>"+value+"</option>");
            })
            $("#CityTown").val(value);
        }
    })
}

function LoadBrgy(citytown,value){
    $.ajax({
        url:"../../routes/maintenance.route.php",
        type:"POST",
        data:{maintenance_action:"get_brgy",citytown_selected:citytown},
        dataType:"JSON",
        success:function(data){
            $("#Barangay").empty();
            $("#Barangay").append("<option value='' disabled selected>-</option>");
            $.each(data, function(key,value){
                $("#Barangay").append("<option value='"+value+"'>"+value+"</option>");
            })
            $("#Barangay").val(value);
        }
    })
}