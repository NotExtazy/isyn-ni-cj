function logout(val) {
    if(val == "default"){
        var url = "routes/auth.route.php" 
    }else{
        var url = "../../routes/auth.route.php" 
    }

    Swal.fire({
        title: "Are you sure you want to logout?",
        icon: 'question',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        confirmButtonText: 'Logout',
        confirmButtonColor: "#435ebe",
        cancelButtonText: "Cancel",
        allowOutsideClick: false,
        customClass: {
            input: 'text-center',
        },
        preConfirm: () => {            
            return $.ajax({
                url: url,
                type:"POST",
                data: {action:"logout"},
                dataType: 'JSON',
                success:function(response){
                    if(response.STATUS != "LOGOUT_SUCCESS"){
                        Swal.showValidationMessage(
                            'Unable to logout, please reload.'
                        )
                    }
                }
            })
        },
    }).then((result) => {
        if (result.value.STATUS == "LOGOUT_SUCCESS") {
            window.location.href = basePath + "/login";
        }
    });
}