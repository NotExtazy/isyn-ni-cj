

const floatingInput = document.getElementById('usernameField');
const emailError = document.getElementById('usernameError');

// Check if basePath is available when script loads
console.log('Login script loaded - basePath:', typeof basePath !== 'undefined' ? basePath : 'undefined');

floatingInput.addEventListener('input', function() {
    const emailAddress = this.value.trim();

    if (emailAddress === '') {
        emailError.textContent = 'Email Address is required';
    } else {
        emailError.textContent = '';
    }
});

const togglePasswordCheckbox = document.getElementById('togglePassword');
const passwordInput = document.getElementById('passwordField');

togglePasswordCheckbox.addEventListener('change', function() {
    const type = togglePasswordCheckbox.checked ? 'text' : 'password';
    passwordInput.setAttribute('type', type);

});

$('#loginForm').submit(function(event) {
    event.preventDefault();

    var formData = new FormData(this);
        formData.append('action', 'Login');

    if (formData.get("usernameField") === ""){
        Swal.fire ({
            icon: 'warning',
            title: 'Enter Username',
            confirmButtonColor: '#0d6efd',
        })
        return;
    } else if  (formData.get("passwordField") === "") {
        Swal.fire ({
            icon: 'warning',
            title: 'Enter Password',
            confirmButtonColor: '#0d6efd',
        })
        return;
    } else {
        // Show loading SweetAlert
        Swal.fire({
            icon: 'info',
            title: 'Logging in...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Direct AJAX call like login_simple.php
        $.ajax({
            url:"routes/auth.route.php",
            type:"POST",
            data: formData,
            dataType: 'JSON',
            processData: false,
            contentType: false,
            success:function(response){
                console.log('AJAX response STATUS:', response.STATUS);
                console.log('Redirecting to dashboard with basePath:', basePath);
                
                if(response.STATUS == 'SUCCESS') {
                    Swal.close();
                    console.log('Login successful, redirecting to dashboard');
                    
                    // Direct redirect to dashboard
                    window.location.href = basePath + '/dashboard';
                } else {
                    // Show error
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: response.MESSAGE,
                        confirmButtonColor: '#0d6efd'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Login failed. Please try again.',
                    confirmButtonColor: '#0d6efd'
                });
            }
        });
    }
});