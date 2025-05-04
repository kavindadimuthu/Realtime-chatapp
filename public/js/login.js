document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS animation library
    AOS.init({
        once: true,
        offset: 100,
        easing: 'ease-out-cubic'
    });

    // Form tab switching
    const loginTab = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    loginTab.addEventListener('click', () => {
        activateTab(loginTab, registerTab);
        showForm(loginForm, registerForm);
    });

    registerTab.addEventListener('click', () => {
        activateTab(registerTab, loginTab);
        showForm(registerForm, loginForm);
    });

    // Switch form links
    const switchFormLinks = document.querySelectorAll('.switch-form');
    switchFormLinks.forEach(link => {
        link.addEventListener('click', () => {
            const targetForm = link.getAttribute('data-target');
            if (targetForm === 'loginForm') {
                activateTab(loginTab, registerTab);
                showForm(loginForm, registerForm);
            } else {
                activateTab(registerTab, loginTab);
                showForm(registerForm, loginForm);
            }
        });
    });

    function activateTab(activeTab, inactiveTab) {
        activeTab.classList.add('active');
        inactiveTab.classList.remove('active');
    }

    function showForm(activeForm, inactiveForm) {
        activeForm.classList.add('active');
        inactiveForm.classList.remove('active');
    }

    // Password visibility toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Password guidelines popup
    const registerPassword = document.getElementById('registerPassword');
    const passwordGuidelines = document.getElementById('passwordGuidelines');
    const loginPassword = document.getElementById('loginPassword');

    registerPassword.addEventListener('focus', () => {
        positionGuidelines(registerPassword);
        passwordGuidelines.classList.add('show');
    });

    registerPassword.addEventListener('blur', () => {
        passwordGuidelines.classList.remove('show');
    });

    // Position the guidelines popup relative to the password field
    function positionGuidelines(element) {
        const rect = element.getBoundingClientRect();
        const guideRect = passwordGuidelines.getBoundingClientRect();
        
        // Check if we should position above or below
        if (window.innerHeight - rect.bottom < guideRect.height + 10) {
            passwordGuidelines.style.top = (rect.top - guideRect.height - 10) + 'px';
        } else {
            passwordGuidelines.style.top = (rect.bottom + 10) + 'px';
        }
    }

    // Login form validation
    const loginFormElement = document.querySelector('#loginForm form');
    const loginSubmit = document.getElementById('loginSubmit');
    const loginEmailField = document.getElementById('loginEmail');
    const loginPasswordField = document.getElementById('loginPassword');
    const loginEmailError = document.getElementById('loginEmailError');
    const loginPasswordError = document.getElementById('loginPasswordError');

    const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

    loginFormElement.addEventListener('submit', async function(e) {
        e.preventDefault();

        let isValid = true;
        
        // Validate email
        if (!emailPattern.test(loginEmailField.value.trim())) {
            loginEmailField.classList.add('error');
            loginEmailError.style.display = 'block';
            isValid = false;
        } else {
            loginEmailField.classList.remove('error');
            loginEmailField.classList.add('valid');
            loginEmailError.style.display = 'none';
        }

        // Validate password
        if (loginPasswordField.value.trim().length < 1) {
            loginPasswordField.classList.add('error');
            loginPasswordError.style.display = 'block';
            isValid = false;
        } else {
            loginPasswordField.classList.remove('error');
            loginPasswordField.classList.add('valid');
            loginPasswordError.style.display = 'none';
        }

        if (isValid) {
            // Show loading state
            loginSubmit.disabled = true;
            const originalText = loginSubmit.textContent;
            loginSubmit.textContent = 'Signing in...';

            try {
                // Create data object for submission
                const formData = {
                    email: loginEmailField.value.trim(),
                    password: loginPasswordField.value
                };

                // Send API request for login
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                
                if (!response.ok) {
                    // Handle different error status codes
                    if (response.status === 401) {
                        throw new Error('Invalid email or password');
                    } 
                    if (response.status === 403) {
                        throw new Error('Account is inactive or suspended');
                    }
                    
                    throw new Error(data.message || data.error || 'Login failed');
                }
                
                // Success handling
                if (data.token) {
                    // Store token if needed (depends on your auth implementation)
                    localStorage.setItem('authToken', data.token);
                }
                
                // Redirect to chat page on success
                window.location.href = '/chat';
            } catch (error) {
                // Display error message
                displayLoginError(error.message);
                
                // Reset button
                loginSubmit.disabled = false;
                loginSubmit.textContent = originalText;
            }
        }
    });

    // Display login error message
    function displayLoginError(message) {
        // First check if we need to create a general error message element
        let generalErrorElement = document.getElementById('generalLoginError');
        
        if (!generalErrorElement) {
            generalErrorElement = document.createElement('div');
            generalErrorElement.id = 'generalLoginError';
            generalErrorElement.className = 'alert alert-danger mt-3';
            generalErrorElement.role = 'alert';
            
            // Insert before the submit button
            const formContent = loginFormElement;
            const submitButtonParent = loginSubmit.parentElement;
            formContent.insertBefore(generalErrorElement, submitButtonParent);
        }
        
        generalErrorElement.textContent = message;
        generalErrorElement.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            generalErrorElement.style.display = 'none';
        }, 5000);
    }

    // Registration form validation
    const registerFormElement = document.querySelector('#registerForm form');
    const registerSubmit = document.getElementById('registerSubmit');
    const firstNameField = document.getElementById('firstName');
    const lastNameField = document.getElementById('lastName');
    const registerEmailField = document.getElementById('registerEmail');
    const phoneField = document.getElementById('phone');
    const registerPasswordField = document.getElementById('registerPassword');
    const confirmPasswordField = document.getElementById('confirmPassword');
    const agreementCheckbox = document.getElementById('agreement');

    // Password strength validation
    const lengthReq = document.getElementById('lengthReq');
    const uppercaseReq = document.getElementById('uppercaseReq');
    const lowercaseReq = document.getElementById('lowercaseReq');
    const numberReq = document.getElementById('numberReq');
    const specialReq = document.getElementById('specialReq');
    const passwordStrengthMeter = document.getElementById('passwordStrengthMeter');

    const namePattern = /^[A-Za-z\s'-]{2,50}$/;
    const phonePattern = /^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,3}[-\s\.]?[0-9]{3,4}[-\s\.]?[0-9]{3,4}$/;
    
    // Password validation patterns
    const lengthPattern = /.{8,}/;
    const uppercasePattern = /[A-Z]/;
    const lowercasePattern = /[a-z]/;
    const numberPattern = /[0-9]/;
    const specialPattern = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/;

    // Password strength validation
    registerPasswordField.addEventListener('input', function() {
        const value = this.value;
        
        // Check individual requirements
        const hasLength = lengthPattern.test(value);
        const hasUppercase = uppercasePattern.test(value);
        const hasLowercase = lowercasePattern.test(value);
        const hasNumber = numberPattern.test(value);
        const hasSpecial = specialPattern.test(value);
        
        // Update visual indicators
        updateRequirement(lengthReq, hasLength);
        updateRequirement(uppercaseReq, hasUppercase);
        updateRequirement(lowercaseReq, hasLowercase);
        updateRequirement(numberReq, hasNumber);
        updateRequirement(specialReq, hasSpecial);
        
        // Calculate password strength (0-5)
        const strength = [hasLength, hasUppercase, hasLowercase, hasNumber, hasSpecial].filter(Boolean).length;
        
        // Update strength meter
        passwordStrengthMeter.style.width = `${strength * 20}%`;
        
        if (strength === 0) {
            passwordStrengthMeter.style.backgroundColor = 'var(--gray-300)';
        } else if (strength < 3) {
            passwordStrengthMeter.style.backgroundColor = 'var(--danger)'; // Red - weak
        } else if (strength < 5) {
            passwordStrengthMeter.style.backgroundColor = 'var(--warning)'; // Orange - medium
        } else {
            passwordStrengthMeter.style.backgroundColor = 'var(--success)'; // Green - strong
        }
    });

    function updateRequirement(element, isValid) {
        if (isValid) {
            element.classList.add('valid');
        } else {
            element.classList.remove('valid');
        }
    }

    // Check password match
    confirmPasswordField.addEventListener('input', function() {
        const confirmPasswordError = document.getElementById('confirmPasswordError');
        if (this.value !== registerPasswordField.value) {
            this.classList.add('error');
            this.classList.remove('valid');
            confirmPasswordError.style.display = 'block';
        } else {
            this.classList.remove('error');
            this.classList.add('valid');
            confirmPasswordError.style.display = 'none';
        }
    });

    registerFormElement.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        let isValid = true;
        
        // Validate first name
        if (!namePattern.test(firstNameField.value.trim())) {
            firstNameField.classList.add('error');
            document.getElementById('firstNameError').style.display = 'block';
            isValid = false;
        } else {
            firstNameField.classList.remove('error');
            firstNameField.classList.add('valid');
            document.getElementById('firstNameError').style.display = 'none';
        }
        
        // Validate last name
        if (!namePattern.test(lastNameField.value.trim())) {
            lastNameField.classList.add('error');
            document.getElementById('lastNameError').style.display = 'block';
            isValid = false;
        } else {
            lastNameField.classList.remove('error');
            lastNameField.classList.add('valid');
            document.getElementById('lastNameError').style.display = 'none';
        }
        
        // Validate email
        if (!emailPattern.test(registerEmailField.value.trim())) {
            registerEmailField.classList.add('error');
            document.getElementById('registerEmailError').style.display = 'block';
            isValid = false;
        } else {
            registerEmailField.classList.remove('error');
            registerEmailField.classList.add('valid');
            document.getElementById('registerEmailError').style.display = 'none';
        }
        
        // Validate phone
        if (!phonePattern.test(phoneField.value.trim())) {
            phoneField.classList.add('error');
            document.getElementById('phoneError').style.display = 'block';
            isValid = false;
        } else {
            phoneField.classList.remove('error');
            phoneField.classList.add('valid');
            document.getElementById('phoneError').style.display = 'none';
        }
        
        // Validate password
        const passwordValue = registerPasswordField.value;
        const hasLength = lengthPattern.test(passwordValue);
        const hasUppercase = uppercasePattern.test(passwordValue);
        const hasLowercase = lowercasePattern.test(passwordValue);
        const hasNumber = numberPattern.test(passwordValue);
        const hasSpecial = specialPattern.test(passwordValue);
        
        if (!hasLength || !hasUppercase || !hasLowercase || !hasNumber || !hasSpecial) {
            registerPasswordField.classList.add('error');
            document.getElementById('registerPasswordError').style.display = 'block';
            isValid = false;
        } else {
            registerPasswordField.classList.remove('error');
            registerPasswordField.classList.add('valid');
            document.getElementById('registerPasswordError').style.display = 'none';
        }
        
        // Validate confirm password
        if (confirmPasswordField.value !== registerPasswordField.value) {
            confirmPasswordField.classList.add('error');
            document.getElementById('confirmPasswordError').style.display = 'block';
            isValid = false;
        } else {
            confirmPasswordField.classList.remove('error');
            confirmPasswordField.classList.add('valid');
            document.getElementById('confirmPasswordError').style.display = 'none';
        }
        
        // Validate agreement
        if (!agreementCheckbox.checked) {
            document.getElementById('agreementError').style.display = 'block';
            isValid = false;
        } else {
            document.getElementById('agreementError').style.display = 'none';
        }
        
        if (isValid) {
            // Show loading state
            registerSubmit.disabled = true;
            const originalText = registerSubmit.textContent;
            registerSubmit.textContent = 'Creating account...';
            
            try {
                // Create data object for registration
                const formData = {
                    firstName: firstNameField.value.trim(),
                    lastName: lastNameField.value.trim(),
                    email: registerEmailField.value.trim(),
                    phone: phoneField.value.trim(),
                    password: registerPasswordField.value
                };
                
                // Send API request for registration
                const response = await fetch('/api/auth/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    // Handle error response
                    throw new Error(data.message || data.error || 'Registration failed');
                }
                
                // Success handling - redirect to login with success message
                window.location.href = '/login?message=' + encodeURIComponent('Account created successfully! Please sign in.');
            } catch (error) {
                // Display error message
                displayRegisterError(error.message);
                
                // Reset button
                registerSubmit.disabled = false;
                registerSubmit.textContent = originalText;
            }
        }
    });
    
    // Display register error message
    function displayRegisterError(message) {
        // First check if we need to create a general error message element
        let generalErrorElement = document.getElementById('generalRegisterError');
        
        if (!generalErrorElement) {
            generalErrorElement = document.createElement('div');
            generalErrorElement.id = 'generalRegisterError';
            generalErrorElement.className = 'alert alert-danger mt-3';
            generalErrorElement.role = 'alert';
            
            // Insert before the submit button
            const formContent = registerFormElement;
            const submitButtonParent = registerSubmit.parentElement;
            formContent.insertBefore(generalErrorElement, submitButtonParent);
        }
        
        generalErrorElement.textContent = message;
        generalErrorElement.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            generalErrorElement.style.display = 'none';
        }, 5000);
    }

    // Display success message from URL params if present
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    
    if (message) {
        const successElement = document.createElement('div');
        successElement.className = 'alert alert-success';
        successElement.style.marginBottom = '20px';
        successElement.style.padding = '10px 15px';
        successElement.style.borderRadius = '8px';
        successElement.style.backgroundColor = '#d1e7dd';
        successElement.style.color = '#0f5132';
        successElement.textContent = message;
        
        const formContainer = document.querySelector('.auth-form-container');
        formContainer.insertBefore(successElement, formContainer.firstChild);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            successElement.style.opacity = '0';
            successElement.style.transition = 'opacity 1s ease';
            setTimeout(() => {
                successElement.remove();
            }, 1000);
        }, 5000);
    }
});