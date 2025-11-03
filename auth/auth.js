document.addEventListener('DOMContentLoaded', function() {
    // Handle Login Form Submission
    document.getElementById('loginFormElement').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('loginSuccess', data.message);
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                showError('loginError', data.message);
            }
        })
        .catch(error => {
            showError('loginError', 'An error occurred. Please try again.');
            console.error('Error:', error);
        });
    });

    // Handle Signup Form Submission
    document.getElementById('signupFormElement').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            showError('signupError', 'Passwords do not match!');
            return;
        }

        const formData = new FormData(this);
        
        fetch('signup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('signupSuccess', data.message);
                setTimeout(() => {
                    switchForm('login');
                }, 1500);
            } else {
                showError('signupError', data.message);
            }
        })
        .catch(error => {
            showError('signupError', 'An error occurred. Please try again.');
            console.error('Error:', error);
        });
    });
});