<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - TwinTalk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="/css/login.css">
</head>

<body>
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>

    <div class="auth-container" data-aos="fade-up" data-aos-duration="800">
        <!-- Brand Section -->
        <div class="auth-brand">
            <div class="brand-content">
                <div class="brand-logo" onclick="window.location.href='/'">
                    <i class="fas fa-bolt"></i> TwinTalk
                </div>
                <h2 class="brand-tagline">Connect Instantly</h2>
                <p class="brand-description">Real-time, one-on-one chats made simple. TwinTalk connects you instantly with the people who matter most.</p>
                
                <div class="brand-features">
                    <div class="brand-feature">
                        <div class="feature-icon"><i class="fas fa-comments"></i></div>
                        <span>Real-time messaging</span>
                    </div>
                    <div class="brand-feature">
                        <div class="feature-icon"><i class="fas fa-user-check"></i></div>
                        <span>Online status indicators</span>
                    </div>
                    <div class="brand-feature">
                        <div class="feature-icon"><i class="fas fa-lock"></i></div>
                        <span>Private conversations</span>
                    </div>
                </div>

                <div class="app-preview">
                    <img src="/assets/chat-app-ui.png" alt="TwinTalk App Preview" class="app-preview-image">
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="auth-form-container">
            <a href="/" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Home</span>
            </a>

            <div class="form-tabs">
                <button id="loginTab" class="tab-button active">Login</button>
                <button id="registerTab" class="tab-button">Register</button>
            </div>

            <div id="loginForm" class="auth-form active">
                <div class="form-header">
                    <h1 class="form-title">Welcome Back</h1>
                    <p class="form-subtitle">Sign in to continue your conversations</p>
                </div>

                <form>
                    <div class="form-group">
                        <label class="form-label" for="loginEmail">Email Address</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-envelope form-input-icon"></i>
                            <input type="email" id="loginEmail" name="email" class="form-input" placeholder="you@example.com" required>
                        </div>
                        <div id="loginEmailError" class="error-message">Please enter a valid email address</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="loginPassword">Password</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock form-input-icon"></i>
                            <input type="password" id="loginPassword" name="password" class="form-input" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="loginPasswordError" class="error-message">Please enter your password</div>
                    </div>

                    <div class="form-extras">
                        <div class="remember-me">
                            <input type="checkbox" id="rememberMe" class="form-checkbox">
                            <label for="rememberMe" class="form-checkbox-label">Remember me</label>
                        </div>
                        <a href="/forgot-password" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" id="loginSubmit" class="form-submit">Sign In</button>

                    <div class="form-alternate">
                        <span>Don't have an account?</span>
                        <button type="button" class="switch-form" data-target="registerForm">Register now</button>
                    </div>
                </form>
            </div>

            <div id="registerForm" class="auth-form">
                <div class="form-header">
                    <h1 class="form-title">Create Account</h1>
                    <p class="form-subtitle">Join TwinTalk for simple one-on-one chats</p>
                </div>

                <form>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="firstName">First Name</label>
                            <div class="form-input-wrapper">
                                <i class="fas fa-user form-input-icon"></i>
                                <input type="text" id="firstName" name="firstName" class="form-input" placeholder="Your first name" required>
                            </div>
                            <div id="firstNameError" class="error-message">Please enter your first name</div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="lastName">Last Name</label>
                            <div class="form-input-wrapper">
                                <i class="fas fa-user form-input-icon"></i>
                                <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Your last name" required>
                            </div>
                            <div id="lastNameError" class="error-message">Please enter your last name</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerEmail">Email Address</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-envelope form-input-icon"></i>
                            <input type="email" id="registerEmail" name="email" class="form-input" placeholder="you@example.com" required>
                        </div>
                        <div id="registerEmailError" class="error-message">Please enter a valid email address</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-phone form-input-icon"></i>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="+1 (555) 000-0000" required>
                        </div>
                        <div id="phoneError" class="error-message">Please enter a valid phone number</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="registerPassword">Password</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock form-input-icon"></i>
                            <input type="password" id="registerPassword" name="password" class="form-input" placeholder="Create a strong password" required>
                            <button type="button" class="password-toggle" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div id="passwordStrengthMeter" class="password-strength-meter"></div>
                        </div>
                        <div id="registerPasswordError" class="error-message">Password doesn't meet requirements</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">Confirm Password</label>
                        <div class="form-input-wrapper">
                            <i class="fas fa-lock form-input-icon"></i>
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Repeat your password" required>
                            <button type="button" class="password-toggle" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="confirmPasswordError" class="error-message">Passwords do not match</div>
                    </div>

                    <div class="form-agreement">
                        <input type="checkbox" id="agreement" class="form-checkbox" required>
                        <div>
                            <label for="agreement" class="form-checkbox-label">
                                I agree to the <a href="#" class="form-link">Terms of Service</a> and <a href="#" class="form-link">Privacy Policy</a>
                            </label>
                            <div id="agreementError" class="error-message">You must agree to the terms</div>
                        </div>
                    </div>

                    <button type="submit" id="registerSubmit" class="form-submit">Create Account</button>

                    <div class="form-alternate">
                        <span>Already have an account?</span>
                        <button type="button" class="switch-form" data-target="loginForm">Sign in</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="passwordGuidelines" class="guidelines">
        <div class="guidelines-title">Password Requirements</div>
        <ul>
            <li id="lengthReq">At least 8 characters</li>
            <li id="uppercaseReq">At least one uppercase letter</li>
            <li id="lowercaseReq">At least one lowercase letter</li>
            <li id="numberReq">At least one number</li>
            <li id="specialReq">At least one special character</li>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="/js/login.js"></script>
</body>
</html>