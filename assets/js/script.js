// Add interactivity to buttons
document.addEventListener('DOMContentLoaded', function() {
    const signupButton = document.querySelector('.signup-button');
    const loginButton = document.querySelector('.login-button');
    const searchInput = document.querySelector('.search-input');

    // Sign Up button click handler
    if (signupButton) {
        signupButton.addEventListener('click', function() {
        alert('Sign Up clicked! This would redirect to a sign-up page.');
        // In a real application, you would redirect to signup.php
        // window.location.href = 'signup.php';
        });
    }

    // Login button click handler
    if (loginButton) {
        loginButton.addEventListener('click', function() {
        alert('Log In clicked! This would redirect to a login page.');
        // In a real application, you would redirect to login.php
        // window.location.href = 'login.php';
        });
    }

    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = this.value;
            alert('Searching for: ' + searchTerm);
            // In a real application, you would send this to search.php
            // window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
        }
        });

    // Add focus effect to search input
        searchInput.addEventListener('focus', function() {
        this.style.boxShadow = '0 0 10px rgba(111, 143, 111, 0.3)';
        });

        searchInput.addEventListener('blur', function() {
        this.style.boxShadow = 'none';
        });
    }
});






// Theme toggle script
document.addEventListener('DOMContentLoaded', function () {
                const themeSwitch = document.getElementById('themeSwitch');
                const body = document.body;
                if (!themeSwitch) return;

                if (localStorage.getItem('theme') === 'dark') {
                    body.classList.add('dark-mode');
                    themeSwitch.checked = true;
                }

                themeSwitch.addEventListener('change', function () {
                    if (this.checked) {
                        body.classList.add('dark-mode');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        body.classList.remove('dark-mode');
                        localStorage.setItem('theme', 'light');
                    }
                });
            });

// Login form handling script
    document.addEventListener('DOMContentLoaded', function () {
        const loginForm = document.querySelector('form[action="../backend/auth/login_process.php"]');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const loginEmailError = document.getElementById('login-email-error');
        const loginPasswordError = document.getElementById('login-password-error');
        const loginFormMessage = document.getElementById('form-message');
        const loginCsrfInput = document.getElementById('csrf_token');

        if (!loginForm || !emailInput || !passwordInput || !loginEmailError || !loginPasswordError || !loginFormMessage || !loginCsrfInput) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const message = params.get('message');
        const errorField = params.get('login_error');
        const successMessage = params.get('success');

        const navigationEntries = performance.getEntriesByType('navigation');
        const isReload = navigationEntries.length && navigationEntries[0].type === 'reload';
        if (isReload) {
            emailInput.value = '';
            passwordInput.value = '';
        }

        if (message) {
            if (errorField === 'email') {
                loginEmailError.textContent = message;
            } else if (errorField === 'password') {
                loginPasswordError.textContent = message;
                passwordInput.value = '';
            } else {
                loginFormMessage.textContent = message;
            }
        }

        if (errorField === 'password' && !successMessage) {
            passwordInput.value = '';
        }

        loginForm.addEventListener('submit', function (event) {
                loginEmailError.textContent = '';
                loginPasswordError.textContent = '';
                loginFormMessage.textContent = '';

                let valid = true;
                const emailValue = emailInput.value.trim();
                const passwordValue = passwordInput.value.trim();

                if (!emailValue) {
                    loginEmailError.textContent = 'Email is required.';
                    valid = false;
                }
                if (!passwordValue) {
                    loginPasswordError.textContent = 'Password is required.';
                    valid = false;
                }

                const csrfValue = loginCsrfInput.value.trim();
                if (!csrfValue) {
                    loginFormMessage.textContent = 'CSRF token is missing. Please refresh the page.';
                    valid = false;
                }

                if (!valid) {
                    event.preventDefault();
                }
            });

        if (successMessage) {
            loginFormMessage.textContent = successMessage;
            loginFormMessage.classList.remove('form-error');
            loginFormMessage.classList.add('form-success');
        }

        fetch('../backend/csrf_token.php', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load CSRF token');
            }
            return response.text();
        })
        .then(token => {
            if (!token || !token.trim()) {
                throw new Error('Empty CSRF token');
            }
            loginCsrfInput.value = token.trim();
        })
        .catch(error => {
            console.error('CSRF token error:', error);
            loginFormMessage.textContent = 'Unable to generate CSRF token. Please refresh the page.';
        });
    });

// Signup form handling script
    document.addEventListener('DOMContentLoaded', function () {
        const signupForm = document.querySelector('form[action="../backend/auth/signup_process.php"]');
        const fullnameInput = document.getElementById('fullname');
        const signupEmailInput = document.getElementById('signup-email');
        const signupPasswordInput = document.getElementById('signup-password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const signupFullnameError = document.getElementById('signup-fullname-error');
        const signupEmailError = document.getElementById('signup-email-error');
        const signupPasswordError = document.getElementById('signup-password-error');
        const signupConfirmError = document.getElementById('signup-confirm-error');
        const signupFormMessage = document.getElementById('form-message');
        const signupCsrfInput = document.getElementById('csrf_token');

        if (!signupForm || !fullnameInput || !signupEmailInput || !signupPasswordInput || !confirmPasswordInput || !signupFullnameError || !signupEmailError || !signupPasswordError || !signupConfirmError || !signupFormMessage || !signupCsrfInput) {
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const message = params.get('message');
        const errorField = params.get('signup_error');
        const successMessage = params.get('success');
        const navigationEntries = performance.getEntriesByType('navigation');
        const isReload = navigationEntries.length && navigationEntries[0].type === 'reload';

        if (isReload) {
            fullnameInput.value = '';
            signupEmailInput.value = '';
            signupPasswordInput.value = '';
            confirmPasswordInput.value = '';
        }

        if (message) {
            if (errorField === 'fullname') {
                signupFullnameError.textContent = message;
            } else if (errorField === 'email') {
                signupEmailError.textContent = message;
            } else if (errorField === 'password') {
                signupPasswordError.textContent = message;
            } else if (errorField === 'confirm') {
                signupConfirmError.textContent = message;
            } else {
                signupFormMessage.textContent = message;
            }
        }

        signupForm.addEventListener('submit', function (event) {
                signupFullnameError.textContent = '';
                signupEmailError.textContent = '';
                signupPasswordError.textContent = '';
                signupConfirmError.textContent = '';
                signupFormMessage.textContent = '';

                let valid = true;
                const fullnameValue = fullnameInput.value.trim();
                const emailValue = signupEmailInput.value.trim();
                const passwordValue = signupPasswordInput.value.trim();
                const confirmValue = confirmPasswordInput.value.trim();

                if (!fullnameValue) {
                    signupFullnameError.textContent = 'Full name is required.';
                    valid = false;
                }
                if (!emailValue) {
                    signupEmailError.textContent = 'Email is required.';
                    valid = false;
                }
                if (!passwordValue) {
                    signupPasswordError.textContent = 'Password is required.';
                    valid = false;
                }
                if (!confirmValue) {
                    signupConfirmError.textContent = 'Please confirm your password.';
                    valid = false;
                }
                if (passwordValue && confirmValue && passwordValue !== confirmValue) {
                    signupConfirmError.textContent = 'Passwords do not match.';
                    valid = false;
                }

                const csrfValue = signupCsrfInput.value.trim();
                if (!csrfValue) {
                    signupFormMessage.textContent = 'CSRF token is missing. Please refresh the page.';
                    valid = false;
                }

                if (!valid) {
                    event.preventDefault();
                }
            });

        const loginButton = document.getElementById('login-btn');
        if (loginButton) {
            loginButton.addEventListener('click', function () {
                signupFullnameError.textContent = '';
                signupEmailError.textContent = '';
                signupPasswordError.textContent = '';
                signupConfirmError.textContent = '';
                signupFormMessage.textContent = '';

                let valid = true;
                const fullnameValue = fullnameInput.value.trim();
                const emailValue = signupEmailInput.value.trim();
                const passwordValue = signupPasswordInput.value.trim();
                const confirmValue = confirmPasswordInput.value.trim();
                const csrfValue = signupCsrfInput.value.trim();

                if (!fullnameValue) {
                    signupFullnameError.textContent = 'Full name is required.';
                    valid = false;
                }
                if (!emailValue) {
                    signupEmailError.textContent = 'Email is required.';
                    valid = false;
                }
                if (!passwordValue) {
                    signupPasswordError.textContent = 'Password is required.';
                    valid = false;
                }
                if (!confirmValue) {
                    signupConfirmError.textContent = 'Please confirm your password.';
                    valid = false;
                }
                if (passwordValue && confirmValue && passwordValue !== confirmValue) {
                    signupConfirmError.textContent = 'Passwords do not match.';
                    valid = false;
                }
                if (!csrfValue) {
                    signupFormMessage.textContent = 'CSRF token is missing. Please refresh the page.';
                    valid = false;
                }

                if (!valid) {
                    return;
                }

                signupForm.submit();
            });
        }

        if (successMessage) {
            signupFormMessage.textContent = successMessage;
            signupFormMessage.classList.remove('form-error');
            signupFormMessage.classList.add('form-success');
        }

        fetch('../backend/csrf_token.php', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load CSRF token');
            }
            return response.text();
        })
        .then(token => {
            if (!token || !token.trim()) {
                throw new Error('Empty CSRF token');
            }
            signupCsrfInput.value = token.trim();
        })
        .catch(error => {
            console.error('CSRF token error:', error);
            signupFormMessage.textContent = 'Unable to generate CSRF token. Please refresh the page.';
        });
    });


//Setting page scripts
        // Search functionality
        function handleSearch() {
            const searchValue = document.getElementById('searchInput').value;
            console.log('Searching for:', searchValue);
            alert('Search functionality: ' + searchValue);
        }

        // // Add enter key support for search
        // document.getElementById('searchInput').addEventListener('keypress', function(e) {
        //     if (e.key === 'Enter') {
        //         handleSearch();
        //     }
        // });

        // Notifications
        function handleNotifications() {
            alert('Opening notifications...');
        }

        // Profile
        function handleProfile() {
            alert('Opening profile...');
        }

        // Language change
        function handleLanguageChange() {
            const language = document.getElementById('languageSelect').value;
            console.log('Language changed to:', language);
            alert('Language changed to: ' + language);
        }

        // Mode change (Light/Dark)
        function handleModeChange() {
            const mode = document.getElementById('modeSelect').value;
            console.log('Mode changed to:', mode);

            if (mode === 'dark') {
                alert('Dark mode would be applied here. You can redirect to settingdark.php');
                // In a real application, you might redirect to a dark mode page:
                // window.location.href = 'settingdark.php';
            } else if (mode === 'auto') {
                alert('Auto mode selected');
            } else {
                alert('Light mode is currently active');
            }
        }

        // Change password
        function handleChangePassword() {
            alert('Redirecting to change password page...');
            // window.location.href = 'change-password.php';
        }

        // Toggle notification settings
        function toggleNotification(type) {
            console.log('Toggling notification:', type);
            alert('Toggle ' + type + ' notifications');
            // In a real application, you would send an AJAX request to update the setting
        }

        // Logout
        function handleLogout() {
            if (confirm('Are you sure you want to log out?')) {
                alert('Logging out...');
                window.location.href = 'logout.php';
            }
        }

