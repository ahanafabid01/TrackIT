<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Sign Up - YourCompany</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo">
                <!-- Replace with your logo image or keep text -->
                YC
            </div>
            <div class="company-name">TRACKIT</div>
            <div class="company-tagline">Empowering Your Business</div>
        </div>

        <!-- Login Form -->
        <div class="form-container sign-in" id="loginForm">
            <h2>Sign in</h2>
            <p class="subtitle">Welcome back! Please sign in to continue</p>

            <div class="error-message" id="loginError"></div>
            <div class="success-message" id="loginSuccess"></div>

            <div class="social-login">
                <a href="google-auth.php" class="social-btn google">
                    <i class="fab fa-google"></i>
                    <span>Continue with Google</span>
                </a>
                <a href="facebook-auth.php" class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                    <span>Continue with Facebook</span>
                </a>
            </div>

            <div class="divider">
                <span>or</span>
            </div>

            <form id="loginFormElement" method="POST" action="login.php">
                <div class="input-group">
                    <label for="loginEmail">Email</label>
                    <input type="email" id="loginEmail" name="email" placeholder="Enter your email" required>
                </div>

                <div class="input-group">
                    <label for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('loginPassword')"></i>
                </div>

                <div class="forgot-password">
                    <a href="forgot-password.php">Forgot password?</a>
                </div>

                <button type="submit" class="submit-btn">Sign in</button>
            </form>

            <div class="switch-form">
                Don't have an account? <a onclick="switchForm('signup')">Sign up</a>
            </div>
        </div>

        <!-- Sign Up Form -->
        <div class="form-container sign-up" id="signupForm">
            <h2>Create account</h2>
            <p class="subtitle">Join us today! It only takes a minute</p>

            <div class="error-message" id="signupError"></div>
            <div class="success-message" id="signupSuccess"></div>

            <div class="social-login">
                <a href="google-auth.php" class="social-btn google">
                    <i class="fab fa-google"></i>
                    <span>Sign up with Google</span>
                </a>
                <a href="facebook-auth.php" class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i>
                    <span>Sign up with Facebook</span>
                </a>
            </div>

            <div class="divider">
                <span>or</span>
            </div>

            <form id="signupFormElement" method="POST" action="signup.php">
                <div class="input-group">
                    <label for="signupName">Full Name</label>
                    <input type="text" id="signupName" name="name" placeholder="Enter your full name" required>
                </div>

                <div class="input-group">
                    <label for="signupEmail">Email</label>
                    <input type="email" id="signupEmail" name="email" placeholder="Enter your email" required>
                </div>

                <div class="input-group">
                    <label for="signupPassword">Password</label>
                    <input type="password" id="signupPassword" name="password" placeholder="Create a password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('signupPassword')"></i>
                </div>

                <div class="input-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm your password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirmPassword')"></i>
                </div>

                <button type="submit" class="submit-btn">Create account</button>
            </form>

            <div class="switch-form">
                Already have an account? <a onclick="switchForm('login')">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        function switchForm(form) {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');

            if (form === 'signup') {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
            } else {
                signupForm.style.display = 'none';
                loginForm.style.display = 'block';
            }
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function showError(elementId, message) {
            const errorElement = document.getElementById(elementId);
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            setTimeout(() => {
                errorElement.style.display = 'none';
            }, 5000);
        }

        function showSuccess(elementId, message) {
            const successElement = document.getElementById(elementId);
            successElement.textContent = message;
            successElement.style.display = 'block';
            setTimeout(() => {
                successElement.style.display = 'none';
            }, 5000);
        }
    </script>
    <script src="auth.js"></script>
</body>
</html>