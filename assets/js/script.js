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






// Global topbar search (no dropdown: filters on Expenses page only)
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('globalSearchInput') || document.querySelector('.topbar .search-box input');
    if (!input) return;

    const path = String(window.location.pathname || '').replace(/\\/g, '/');
    const isSearchPage =
        /\/pages\/expenses\.php$/i.test(path) || /expenses\.php$/i.test(path) ||
        /\/pages\/dashboard\.php$/i.test(path) || /dashboard\.php$/i.test(path) ||
        /\/pages\/wallet\.php$/i.test(path) || /wallet\.php$/i.test(path) ||
        /\/pages\/budget\.php$/i.test(path) || /budget\.php$/i.test(path);

    function norm(s) {
        return String(s || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function matchesQuery(haystack, query) {
        const q = norm(query);
        if (!q) return true;
        const text = norm(haystack);
        if (!text) return false;
        return q.split(' ').every(token => text.includes(token));
    }

    window.applyGlobalSearch = function (query) {
        if (!isSearchPage) return;
        const root = document.querySelector('.main-content') || document;
        const items = root.querySelectorAll('[data-search-text]');
        if (!items.length) return;

        const q = norm(query);
        items.forEach(el => {
            const hay = (el.getAttribute('data-search-text') || '') + ' ' + (el.textContent || '');
            el.classList.toggle('is-search-hidden', q ? !matchesQuery(hay, q) : false);
        });
    };

    const stored = sessionStorage.getItem('global_search_query') || '';
    if (stored) {
        input.value = stored;
        if (typeof window.applyGlobalSearch === 'function') window.applyGlobalSearch(stored);
    }

    input.addEventListener('input', function () {
        const q = this.value || '';
        sessionStorage.setItem('global_search_query', q);
        if (typeof window.applyGlobalSearch === 'function') window.applyGlobalSearch(q);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const q = input.value || '';
            sessionStorage.setItem('global_search_query', q);
            if (!isSearchPage && norm(q)) {
                window.location.href = '../pages/expenses.php';
            }
        }

        if (e.key === 'Escape') {
            input.value = '';
            sessionStorage.setItem('global_search_query', '');
            if (typeof window.applyGlobalSearch === 'function') window.applyGlobalSearch('');
        }
    });
});

// Theme toggle script
document.addEventListener('DOMContentLoaded', function () {
                const themeSwitch = document.getElementById('themeSwitch');
                const modeSelect = document.getElementById('modeSelect');
                const body = document.body;
                let autoThemeTimer = null;

                function resolveTheme(themePreference) {
                    if (themePreference === 'auto') {
                        const currentHour = new Date().getHours();
                        return currentHour >= 12 ? 'dark' : 'light';
                    }

                    return themePreference === 'dark' ? 'dark' : 'light';
                }

                function syncThemeControls(themePreference, resolvedTheme) {
                    if (themeSwitch) {
                        themeSwitch.checked = resolvedTheme === 'dark';
                    }

                    if (modeSelect) {
                        modeSelect.value = themePreference;
                    }
                }

                function applyTheme(themePreference) {
                    const storedPreference = themePreference === 'auto' ? 'auto' : (themePreference === 'dark' ? 'dark' : 'light');
                    const resolvedTheme = resolveTheme(storedPreference);

                    body.classList.toggle('dark-mode', resolvedTheme === 'dark');
                    localStorage.setItem('theme', storedPreference);
                    syncThemeControls(storedPreference, resolvedTheme);

                    if (autoThemeTimer) {
                        window.clearInterval(autoThemeTimer);
                        autoThemeTimer = null;
                    }

                    if (storedPreference === 'auto') {
                        autoThemeTimer = window.setInterval(function () {
                            const liveResolvedTheme = resolveTheme('auto');
                            body.classList.toggle('dark-mode', liveResolvedTheme === 'dark');
                            syncThemeControls('auto', liveResolvedTheme);
                        }, 60000);
                    }
                }

                applyTheme(localStorage.getItem('theme') || 'light');

                if (themeSwitch) {
                    themeSwitch.addEventListener('change', function () {
                        applyTheme(this.checked ? 'dark' : 'light');
                    });
                }

                if (modeSelect) {
                    modeSelect.addEventListener('change', function () {
                        applyTheme(this.value);
                    });
                }

                window.handleModeChange = function () {
                    if (modeSelect) {
                        applyTheme(modeSelect.value);
                    }
                };
            });

// Sidebar navigation transition
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar');
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link[href]');

    if (!sidebar || !sidebarLinks.length) {
        return;
    }

    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            const href = link.getAttribute('href');

            if (!href || href === '#' || link.classList.contains('active')) {
                return;
            }

            const targetUrl = new URL(href, window.location.href);
            if (targetUrl.origin !== window.location.origin) {
                return;
            }

            event.preventDefault();
            sidebar.classList.add('sidebar-switching');
            link.classList.add('is-switching');

            window.setTimeout(function () {
                window.location.href = targetUrl.href;
            }, 180);
        });
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
        const signupUsernameInput = document.getElementById('signup-username');
        const signupEmailInput = document.getElementById('signup-email');
        const signupPasswordInput = document.getElementById('signup-password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const signupFullnameError = document.getElementById('signup-fullname-error');
        const signupUsernameError = document.getElementById('signup-username-error');
        const signupEmailError = document.getElementById('signup-email-error');
        const signupPasswordError = document.getElementById('signup-password-error');
        const signupConfirmError = document.getElementById('signup-confirm-error');
        const signupFormMessage = document.getElementById('form-message');
        const signupCsrfInput = document.getElementById('csrf_token');
        const signupActionInput = document.getElementById('signup_action');

        if (!signupForm || !fullnameInput || !signupUsernameInput || !signupEmailInput || !signupPasswordInput || !confirmPasswordInput || !signupFullnameError || !signupUsernameError || !signupEmailError || !signupPasswordError || !signupConfirmError || !signupFormMessage || !signupCsrfInput) {
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
            signupUsernameInput.value = '';
            signupEmailInput.value = '';
            signupPasswordInput.value = '';
            confirmPasswordInput.value = '';
        }

        if (message) {
            if (errorField === 'fullname') {
                signupFullnameError.textContent = message;
            } else if (errorField === 'username') {
                signupUsernameError.textContent = message;
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
                if (signupActionInput) {
                    signupActionInput.value = 'register_only';
                }
                signupFullnameError.textContent = '';
                signupUsernameError.textContent = '';
                signupEmailError.textContent = '';
                signupPasswordError.textContent = '';
                signupConfirmError.textContent = '';
                signupFormMessage.textContent = '';

                let valid = true;
                const fullnameValue = fullnameInput.value.trim();
                const usernameValue = signupUsernameInput.value.trim();
                const emailValue = signupEmailInput.value.trim();
                const passwordValue = signupPasswordInput.value.trim();
                const confirmValue = confirmPasswordInput.value.trim();

                if (!fullnameValue) {
                    signupFullnameError.textContent = 'Full name is required.';
                    valid = false;
                }
                if (!usernameValue) {
                    signupUsernameError.textContent = 'Username is required.';
                    valid = false;
                } else if (!/^[A-Za-z0-9_]{3,30}$/.test(usernameValue)) {
                    signupUsernameError.textContent = 'Use 3-30 letters, numbers, or underscores.';
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
                if (signupActionInput) {
                    signupActionInput.value = 'register_and_login';
                }

                signupFullnameError.textContent = '';
                signupUsernameError.textContent = '';
                signupEmailError.textContent = '';
                signupPasswordError.textContent = '';
                signupConfirmError.textContent = '';
                signupFormMessage.textContent = '';

                let valid = true;
                const fullnameValue = fullnameInput.value.trim();
                const usernameValue = signupUsernameInput.value.trim();
                const emailValue = signupEmailInput.value.trim();
                const passwordValue = signupPasswordInput.value.trim();
                const csrfValue = signupCsrfInput.value.trim();

                if (!fullnameValue) {
                    signupFullnameError.textContent = 'Full name is required.';
                    valid = false;
                }

                if (!usernameValue) {
                    signupUsernameError.textContent = 'Username is required.';
                    valid = false;
                } else if (!/^[A-Za-z0-9_]{3,30}$/.test(usernameValue)) {
                    signupUsernameError.textContent = 'Use 3-30 letters, numbers, or underscores.';
                    valid = false;
                }

                if (!emailValue) {
                    signupEmailError.textContent = 'Email is required.';
                    valid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailValue)) {
                    signupEmailError.textContent = 'Please enter a valid email address.';
                    valid = false;
                }

                if (!passwordValue) {
                    signupPasswordError.textContent = 'Password is required.';
                    valid = false;
                }

                if (!confirmPasswordInput.value.trim()) {
                    signupConfirmError.textContent = 'Please confirm your password.';
                    valid = false;
                } else if (passwordValue !== confirmPasswordInput.value.trim()) {
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

document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.getElementById('amountInput');
    const presetButtons = document.querySelectorAll('.preset-btn');
    const loadButton = document.getElementById('loadBtn');
    const fundingOptions = document.querySelectorAll('.funding-option');
    const selectedSourceLabel = document.getElementById('selectedSourceLabel');

    if (!amountInput || !loadButton || !presetButtons.length || !fundingOptions.length) {
        return;
    }

    function getFundingLabel(option) {
        const label = option ? option.querySelector('.funding-option-left span') : null;
        return label ? label.textContent.trim() : 'Visa debit card';
    }

    function syncFundingSelection() {
        fundingOptions.forEach(function (option) {
            const input = option.querySelector('input[type="radio"]');
            option.classList.toggle('is-selected', Boolean(input && input.checked));

            if (input && input.checked && selectedSourceLabel) {
                selectedSourceLabel.textContent = getFundingLabel(option);
            }
        });
    }

    presetButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            amountInput.value = button.dataset.amount || '';
            amountInput.focus();

            presetButtons.forEach(function (item) {
                item.classList.remove('is-active');
            });
            button.classList.add('is-active');
        });
    });

    amountInput.addEventListener('input', function () {
        presetButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.amount === amountInput.value);
        });
    });

    fundingOptions.forEach(function (option) {
        option.addEventListener('click', syncFundingSelection);
        const radio = option.querySelector('input[type="radio"]');
        if (radio) {
            radio.addEventListener('change', syncFundingSelection);
        }
    });

    loadButton.addEventListener('click', function (event) {
        const rawValue = amountInput.value.trim();
        const amount = Number(rawValue);

        if (!rawValue || Number.isNaN(amount) || amount <= 0) {
            event.preventDefault();
            alert('Please enter a valid amount to load.');
            amountInput.focus();
        }
    });

    syncFundingSelection();
});

document.addEventListener('DOMContentLoaded', function () {
    const pairs = [
        { valueId: 'totalBalanceValue', btnId: 'balanceToggleBtn' },
        { valueId: 'totalSpendingValue', btnId: 'spendingToggleBtn' },
        { valueId: 'moneySavedValue', btnId: 'savedToggleBtn' },
    ];

    pairs.forEach(pair => {
        const valueEl = document.getElementById(pair.valueId);
        const btn = document.getElementById(pair.btnId);
        if (!valueEl || !btn) return;
        if (!valueEl.dataset || typeof valueEl.dataset.visibleBalance === 'undefined' || typeof valueEl.dataset.hiddenBalance === 'undefined') return;

        btn.addEventListener('click', function () {
            const isHidden = btn.getAttribute('aria-pressed') === 'true';
            const shouldHide = !isHidden;

            valueEl.textContent = shouldHide ? valueEl.dataset.hiddenBalance : valueEl.dataset.visibleBalance;
            btn.setAttribute('aria-pressed', shouldHide ? 'true' : 'false');
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const recipientInput = document.getElementById('recipientInput');
    const amountInput = document.getElementById('amountInput');
    const searchBtn = document.getElementById('searchBtn');
    const summaryRecipient = document.getElementById('summaryRecipient');
    const summaryAmount = document.getElementById('summaryAmount');
    const sendMoneyForm = document.getElementById('sendMoneyForm');
    const doneBtn = document.getElementById('doneBtn');
    const balanceDisplay = document.getElementById('balanceDisplay');
    const summaryBalanceAmount = document.getElementById('summaryBalanceAmount');
    const toggleSummaryBalance = document.getElementById('toggleSummaryBalance');

    if (!recipientInput || !amountInput || !searchBtn || !summaryRecipient || !summaryAmount || !sendMoneyForm || !doneBtn || !balanceDisplay || !summaryBalanceAmount || !toggleSummaryBalance) {
        return;
    }

    function syncTransferSummary() {
        summaryRecipient.textContent = recipientInput.value.trim();
        summaryAmount.textContent = amountInput.value.trim() ? 'Rs ' + amountInput.value.trim() : '';
    }

    recipientInput.addEventListener('input', syncTransferSummary);
    amountInput.addEventListener('input', syncTransferSummary);

    searchBtn.addEventListener('click', function () {
        recipientInput.focus();
        syncTransferSummary();
    });

    sendMoneyForm.addEventListener('submit', function (event) {
        const recipient = recipientInput.value.trim();
        const amount = Number(amountInput.value.trim());
        const availableBalance = Number(balanceDisplay.textContent.replace(/,/g, ''));
        const currentUsername = (sendMoneyForm.dataset.currentUsername || '').trim().toLowerCase();

        if (!recipient) {
            event.preventDefault();
            alert('Please enter a recipient.');
            recipientInput.focus();
            return;
        }

        if (!/^[A-Za-z0-9_]{3,30}$/.test(recipient)) {
            event.preventDefault();
            alert('Please enter a valid recipient username.');
            recipientInput.focus();
            return;
        }

        if (recipient.toLowerCase() === currentUsername) {
            event.preventDefault();
            alert('You cannot transfer money to your own account.');
            recipientInput.focus();
            return;
        }

        if (!amount || Number.isNaN(amount) || amount <= 0) {
            event.preventDefault();
            alert('Please enter a valid amount.');
            amountInput.focus();
            return;
        }

        if (amount > availableBalance) {
            event.preventDefault();
            alert('Insufficient balance for this transfer.');
            amountInput.focus();
            return;
        }

        summaryRecipient.textContent = recipient;
        summaryAmount.textContent = 'Rs ' + amount.toFixed(2);
    });

    toggleSummaryBalance.addEventListener('click', function () {
        const isHidden = toggleSummaryBalance.getAttribute('aria-pressed') === 'true';
        const shouldHide = !isHidden;

        summaryBalanceAmount.textContent = shouldHide
            ? summaryBalanceAmount.dataset.hiddenBalance
            : summaryBalanceAmount.dataset.visibleBalance;
        toggleSummaryBalance.setAttribute('aria-pressed', shouldHide ? 'true' : 'false');
    });

    syncTransferSummary();
});
