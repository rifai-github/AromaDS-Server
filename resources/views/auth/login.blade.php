<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Aroma ERP System</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
        }

        .login-container {
            background: white;
            width: 100%;
            display: flex;
            min-height: 100vh;
        }

        .login-form {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-image {
            flex: 1;
            background: linear-gradient(135deg, #14ADD6 0%, #384295 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            position: relative;
        }

        .login-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0;
        }

        .logo-section {
            text-align: left;
            margin-bottom: 40px;
        }

        .logo-section img {
            height: 80px;
            margin-bottom: 20px;
        }

        .welcome-text {
            text-align: left;
            margin-bottom: 40px;
        }

        .welcome-text h1 {
            color: #666;
            font-size: 18px;
            font-weight: 400;
            margin-bottom: 10px;
        }

        .welcome-text p {
            color: #214589;
            font-size: 32px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: #214589;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .input-container {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #14ADD6;
            background: white;
            box-shadow: 0 0 0 3px rgba(20, 173, 214, 0.1);
        }

        .form-control.error {
            border-color: #EF4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
            background: #fff;
        }

        .form-control.success {
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            background: #fff;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 18px;
        }

        .form-control:focus + .input-icon {
            color: #14ADD6;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9CA3AF;
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #14ADD6;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #14ADD6 0%, #384295 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(20, 173, 214, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            color: #EF4444;
            font-size: 12px;
            font-weight: 500;
            margin-top: 8px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #14ADD6;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #14ADD6;
            text-decoration: none;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 15px;
            color: #214589;
            font-weight: 500;
        }

        .checkbox-container input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            height: 20px;
            width: 20px;
            background-color: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-radius: 4px;
            margin-right: 12px;
            position: relative;
            transition: all 0.3s ease;
        }

        .checkbox-container:hover input ~ .checkmark {
            border-color: #14ADD6;
        }

        .checkbox-container input:checked ~ .checkmark {
            background-color: #14ADD6;
            border-color: #14ADD6;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }

        .checkbox-container .checkmark:after {
            left: 6px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-image {
                display: none;
            }
            
            .login-form {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div class="logo-section">
                <img src="{{ asset('theme/assets/images/logo.svg') }}" alt="Aroma ERP">
            </div>
            
            <div class="welcome-text">
                <h1>Welcome Back</h1>
                <p>Please Sign In</p>
            </div>

            <div class="alert alert-danger" id="error-alert"></div>
            <div class="alert alert-success" id="success-alert"></div>

            <form id="login-form">
                <div class="form-group">
                    <label for="email">Email or Username</label>
                    <div class="input-container">
                        <input type="text" id="email" name="email" class="form-control" placeholder="Masukkan email atau username" required>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    <div class="error-message" id="email-error">Email or Username is required</div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-container">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Tampilkan/Sembunyikan password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="password-error">Password Is Required</div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <div class="remember-me">
                        <label class="checkbox-container">
                            <input type="checkbox" id="remember" name="remember">
                            <span class="checkmark"></span>
                            Remember Me
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="login-btn">
                    <span id="login-text">Login</span>
                    <div class="loading" id="login-loading">
                        <div class="spinner"></div>
                    </div>
                </button>
            </form>
        </div>

        <div class="login-image">
            <img src="{{ asset('theme/assets/images/login2.png') }}" alt="Login Background">
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // AJAX setup
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Password toggle
            $('#passwordToggle').on('click', function() {
                const input = $('#password');
                const icon = $(this).find('i');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                if (type === 'text') {
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            let emailTouched = false;
            let passwordTouched = false;

            function validateEmail(show = false) {
                const value = $('#email').val().trim();
                if (value === '') {
                    if (show) {
                        $('#email').removeClass('success').addClass('error');
                        $('#email-error').addClass('show');
                    } else {
                        $('#email').removeClass('error success');
                        $('#email-error').removeClass('show');
                    }
                    return false;
                }
                // Remove email validation since we now accept username too
                $('#email').removeClass('error').addClass('success');
                $('#email-error').removeClass('show');
                return true;
            }

            function validatePassword(show = false) {
                const value = $('#password').val().trim();
                if (value === '') {
                    if (show) {
                        $('#password').removeClass('success').addClass('error');
                        $('#password-error').addClass('show');
                    } else {
                        $('#password').removeClass('error success');
                        $('#password-error').removeClass('show');
                    }
                    return false;
                }
                $('#password').removeClass('error').addClass('success');
                $('#password-error').removeClass('show');
                return true;
            }

            function updateSubmitButton() {
                const ok = $('#email').val().trim() !== '' && $('#password').val().trim() !== '';
                $('#login-btn').prop('disabled', !ok);
            }

            $('#email').on('input', function() {
                validateEmail(false);
                updateSubmitButton();
            }).on('blur', function() {
                emailTouched = true;
                validateEmail(true);
            });

            $('#password').on('input', function() {
                validatePassword(false);
                updateSubmitButton();
            }).on('blur', function() {
                passwordTouched = true;
                validatePassword(true);
            });

            // Login form submission
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                
                const email = $('#email').val();
                const password = $('#password').val();

                // Client-side validation
                emailTouched = true; passwordTouched = true;
                const valid = validateEmail(true) && validatePassword(true);
                if (!valid) {
                    return;
                }
                
                // Show loading
                $('#login-text').hide();
                $('#login-loading').show();
                $('#login-btn').prop('disabled', true);
                
                // Hide alerts
                $('.alert').hide();
                
                // Get remember me value
                const remember = $('#remember').is(':checked');
                
                // Make AJAX request
                $.ajax({
                    url: '{{ route("login.post") }}',
                    method: 'POST',
                    data: {
                        email: email,
                        password: password,
                        remember: remember
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#success-alert').text(response.message).show();
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMessage = '';
                            for (let field in errors) {
                                errorMessage += errors[field][0] + '\n';
                            }
                            $('#error-alert').text(errorMessage).show();
                        } else if (xhr.status === 401) {
                            $('#error-alert').text(xhr.responseJSON.message).show();
                        } else {
                            $('#error-alert').text('Terjadi kesalahan. Silakan coba lagi.').show();
                        }
                        
                        // Hide loading
                        $('#login-text').show();
                        $('#login-loading').hide();
                        $('#login-btn').prop('disabled', false);
                    }
                });
            });

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);

            // Initialize: no errors shown, button disabled until both fields filled
            $('#login-btn').prop('disabled', true);
        });
    </script>
</body>
</html>
