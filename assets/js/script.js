/**
 * Themed alert/confirm (matches expenses UI). Native dialogs cannot be styled in CSS.
 */
(function () {
    function removeOverlay(overlay) {
        if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
    }

    window.appAlert = function appAlert(message) {
        return new Promise(function (resolve) {
            const overlay = document.createElement('div');
            overlay.className = 'app-dialog-overlay';
            overlay.setAttribute('role', 'alertdialog');
            overlay.setAttribute('aria-modal', 'true');

            const panel = document.createElement('div');
            panel.className = 'app-dialog';

            const head = document.createElement('div');
            head.className = 'app-dialog-header';
            const h2 = document.createElement('h2');
            h2.className = 'app-dialog-title';
            h2.textContent = 'Notice';
            head.appendChild(h2);

            const body = document.createElement('div');
            body.className = 'app-dialog-body';
            const p = document.createElement('p');
            p.className = 'app-dialog-message';
            p.textContent = String(message);
            body.appendChild(p);

            const foot = document.createElement('div');
            foot.className = 'app-dialog-footer';
            const ok = document.createElement('button');
            ok.type = 'button';
            ok.className = 'app-dialog-btn app-dialog-btn--primary';
            ok.textContent = 'OK';
            foot.appendChild(ok);

            panel.appendChild(head);
            panel.appendChild(body);
            panel.appendChild(foot);
            overlay.appendChild(panel);
            document.body.appendChild(overlay);

            function done() {
                document.removeEventListener('keydown', onKey);
                removeOverlay(overlay);
                resolve();
            }

            function onKey(e) {
                if (e.key === 'Escape') done();
            }

            ok.addEventListener('click', done);
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) done();
            });
            document.addEventListener('keydown', onKey);
            ok.focus();
        });
    };

    /**
     * @param {string} message
     * @param {{ okText?: string, cancelText?: string, title?: string }} [opts]
     * @returns {Promise<boolean>}
     */
    window.appConfirm = function appConfirm(message, opts) {
        opts = opts || {};
        return new Promise(function (resolve) {
            const overlay = document.createElement('div');
            overlay.className = 'app-dialog-overlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');

            const panel = document.createElement('div');
            panel.className = 'app-dialog';

            const head = document.createElement('div');
            head.className = 'app-dialog-header';
            const h2 = document.createElement('h2');
            h2.className = 'app-dialog-title';
            h2.textContent = opts.title || 'Confirm';
            head.appendChild(h2);

            const body = document.createElement('div');
            body.className = 'app-dialog-body';
            const p = document.createElement('p');
            p.className = 'app-dialog-message';
            p.textContent = String(message);
            body.appendChild(p);

            const foot = document.createElement('div');
            foot.className = 'app-dialog-footer';
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'app-dialog-btn app-dialog-btn--ghost';
            cancel.textContent = opts.cancelText || 'Cancel';
            const ok = document.createElement('button');
            ok.type = 'button';
            ok.className = 'app-dialog-btn app-dialog-btn--primary';
            ok.textContent = opts.okText || 'OK';
            foot.appendChild(cancel);
            foot.appendChild(ok);

            panel.appendChild(head);
            panel.appendChild(body);
            panel.appendChild(foot);
            overlay.appendChild(panel);
            document.body.appendChild(overlay);

            function finish(val) {
                document.removeEventListener('keydown', onKey);
                removeOverlay(overlay);
                resolve(val);
            }

            function onKey(e) {
                if (e.key === 'Escape') finish(false);
            }

            cancel.addEventListener('click', function () { finish(false); });
            ok.addEventListener('click', function () { finish(true); });
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) finish(false);
            });
            document.addEventListener('keydown', onKey);
            ok.focus();
        });
    };
})();

// Add interactivity to buttons
document.addEventListener('DOMContentLoaded', function() {
    const signupButton = document.querySelector('.signup-button');
    const loginButton  = document.querySelector('.login-button');
    const searchInput  = document.querySelector('.search-input');

    if (signupButton) {
        signupButton.addEventListener('click', function() {
            window.appAlert('Sign Up clicked! This would redirect to a sign-up page.');
        });
    }

    if (loginButton) {
        loginButton.addEventListener('click', function() {
            window.appAlert('Log In clicked! This would redirect to a login page.');
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value;
                window.appAlert('Searching for: ' + searchTerm);
            }
        });

        searchInput.addEventListener('focus', function() {
            this.style.boxShadow = '0 0 10px rgba(111, 143, 111, 0.3)';
        });

        searchInput.addEventListener('blur', function() {
            this.style.boxShadow = 'none';
        });
    }
});

// Global topbar search with dropdown
document.addEventListener('DOMContentLoaded', function () {
    const input          = document.getElementById('globalSearchInput') || document.querySelector('.topbar .search-box input');
    const dropdown       = document.getElementById('searchDropdown');
    const recentList     = document.getElementById('recentList');
    const recentSection  = document.getElementById('recentSection');
    const resultsList    = document.getElementById('resultsList');
    const resultsSection = document.getElementById('resultsSection');

    if (!input || !dropdown) return;

    const MAX_RECENT = 5;
    const RECENT_KEY = 'search_recent';

    const path = String(window.location.pathname || '').replace(/\\/g, '/');
    const isSearchPage =
        /\/pages\/expenses\.php$/i.test(path)   || /expenses\.php$/i.test(path)  ||
        /\/pages\/dashboard\.php$/i.test(path)  || /dashboard\.php$/i.test(path) ||
        /\/pages\/wallet\.php$/i.test(path)     || /wallet\.php$/i.test(path)    ||
        /\/pages\/budget\.php$/i.test(path)     || /budget\.php$/i.test(path);

    // ── Recent searches helpers ───────────────────────────────────────────
    function getRecent() {
        try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); }
        catch { return []; }
    }

    function saveRecent(q) {
        if (!q.trim()) return;
        let recent = getRecent().filter(r => r !== q.trim());
        recent.unshift(q.trim());
        recent = recent.slice(0, MAX_RECENT);
        localStorage.setItem(RECENT_KEY, JSON.stringify(recent));
    }

    function removeRecent(q) {
        const recent = getRecent().filter(r => r !== q);
        localStorage.setItem(RECENT_KEY, JSON.stringify(recent));
        renderDropdown(input.value);
    }

    // ── Escape helpers ────────────────────────────────────────────────────
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function escAttr(s) {
        return String(s).replace(/"/g, '&quot;');
    }

    function norm(s) {
        return String(s || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    // ── Render dropdown ───────────────────────────────────────────────────
    function renderDropdown(query) {
        const q = query.trim();

        // ── Recent searches (show when input is empty) ────────────────────
        const recent = getRecent();
        if (recentList && recentSection) {
            if (recent.length && !q) {
                recentSection.style.display = 'block';
                recentList.innerHTML = recent.map(r =>
                    '<div class="search-dropdown-item recent">' +
                        '<span style="cursor:pointer" data-recent="' + escAttr(r) + '">🕐 ' + escHtml(r) + '</span>' +
                        '<span class="remove-recent" data-remove="' + escAttr(r) + '">×</span>' +
                    '</div>'
                ).join('');

                recentList.querySelectorAll('[data-recent]').forEach(el => {
                    el.addEventListener('click', () => {
                        input.value = el.dataset.recent;
                        applySearch(el.dataset.recent);
                    });
                });

                recentList.querySelectorAll('[data-remove]').forEach(el => {
                    el.addEventListener('click', (e) => {
                        e.stopPropagation();
                        removeRecent(el.dataset.remove);
                    });
                });
            } else {
                recentSection.style.display = 'none';
            }
        }

        // ── Live results while typing ─────────────────────────────────────
        if (resultsList && resultsSection) {
            if (q) {
                resultsSection.style.display = 'block';
                const items   = document.querySelectorAll('[data-search-text]');
                const matched = [];

                items.forEach(el => {
                    const text = (el.getAttribute('data-search-text') || '') + ' ' + (el.textContent || '');
                    if (norm(text).includes(norm(q))) {
                        matched.push(el);
                    }
                });

                if (matched.length) {
                    resultsList.innerHTML = matched.slice(0, 5).map((el, i) =>
                        '<div class="search-dropdown-item result-item" data-index="' + i + '" style="cursor:pointer;">🔍 ' +
                            escHtml(el.getAttribute('data-search-text') || el.textContent.trim().slice(0, 60)) +
                        '</div>'
                    ).join('');

                    // Click result → scroll to and highlight it on the page
                    resultsList.querySelectorAll('.result-item').forEach((item, i) => {
                        item.addEventListener('click', () => {
                            const target = matched[i];
                            if (!target) return;

                            // Remove previous highlights
                            document.querySelectorAll('.search-highlight').forEach(el => {
                                el.classList.remove('search-highlight');
                            });

                            // Highlight and scroll to the item
                            target.classList.add('search-highlight');
                            target.scrollIntoView({ behavior: 'smooth', block: 'center' });

                            // Save to recent and close dropdown
                            saveRecent(q);
                            closeDropdown();

                            // Remove highlight after 3 seconds
                            setTimeout(() => {
                                target.classList.remove('search-highlight');
                            }, 3000);
                        });
                    });

                } else {
                    resultsList.innerHTML = '<div class="search-dropdown-empty">No results found for "' + escHtml(q) + '"</div>';
                }
            } else {
                resultsSection.style.display = 'none';
            }
        }
    }

    // ── Open / close ──────────────────────────────────────────────────────
    function openDropdown() {
        dropdown.classList.add('is-open');
        renderDropdown(input.value);
    }

    function closeDropdown() {
        dropdown.classList.remove('is-open');
    }

    function applySearch(q) {
        sessionStorage.setItem('global_search_query', q);
        if (typeof window.applyGlobalSearch === 'function') window.applyGlobalSearch(q);
        renderDropdown(q);
    }

    // ── Events ────────────────────────────────────────────────────────────
    input.addEventListener('focus', openDropdown);

    input.addEventListener('input', function () {
        const q = this.value || '';
        sessionStorage.setItem('global_search_query', q);
        if (typeof window.applyGlobalSearch === 'function') window.applyGlobalSearch(q);
        renderDropdown(q);
        openDropdown();
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const q = input.value.trim();
            if (q) saveRecent(q);
            sessionStorage.setItem('global_search_query', q);
            if (!isSearchPage && norm(q)) {
                window.location.href = '../pages/expenses.php';
            }
            closeDropdown();
        }

        if (e.key === 'Escape') {
            input.value = '';
            sessionStorage.setItem('global_search_query', '');
            if (typeof window.applyGlobalSearch === 'function') window.applyGlobalSearch('');
            closeDropdown();
        }
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
        const box = input.closest('.search-box');
        if (box && !box.contains(e.target)) {
            closeDropdown();
        }
    });

    // Restore stored query on page load
    const stored = sessionStorage.getItem('global_search_query') || '';
    if (stored) {
        input.value = stored;
        if (typeof window.applyGlobalSearch === 'function') window.applyGlobalSearch(stored);
    }

    // ── Global search filter ──────────────────────────────────────────────
    window.applyGlobalSearch = function (query) {
        if (!isSearchPage) return;
        const root  = document.querySelector('.main-content') || document;
        const items = root.querySelectorAll('[data-search-text]');
        if (!items.length) return;
        const q = norm(query);
        items.forEach(el => {
            const hay = (el.getAttribute('data-search-text') || '') + ' ' + (el.textContent || '');
            el.classList.toggle('is-search-hidden', q ? !norm(hay).includes(q) : false);
        });
    };
});

// Theme toggle script
document.addEventListener('DOMContentLoaded', function () {
    const themeSwitch = document.getElementById('themeSwitch');
    const modeSelect  = document.getElementById('modeSelect');
    const body        = document.body;
    let autoThemeTimer = null;

    function resolveTheme(themePreference) {
        if (themePreference === 'auto') {
            const currentHour = new Date().getHours();
            return currentHour >= 12 ? 'dark' : 'light';
        }
        return themePreference === 'dark' ? 'dark' : 'light';
    }

    function syncThemeControls(themePreference, resolvedTheme) {
        if (themeSwitch) themeSwitch.checked = resolvedTheme === 'dark';
        if (modeSelect)  modeSelect.value    = themePreference;
    }

    function applyTheme(themePreference) {
        const storedPreference = themePreference === 'auto' ? 'auto' : (themePreference === 'dark' ? 'dark' : 'light');
        const resolvedTheme    = resolveTheme(storedPreference);

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
        if (modeSelect) applyTheme(modeSelect.value);
    };
});

// Sidebar navigation transition
document.addEventListener('DOMContentLoaded', function () {
    const sidebar      = document.querySelector('.sidebar');
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link[href]');

    if (!sidebar || !sidebarLinks.length) return;

    sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function (event) {
            const href = link.getAttribute('href');

            if (!href || href === '#' || link.classList.contains('active')) return;

            const targetUrl = new URL(href, window.location.href);
            if (targetUrl.origin !== window.location.origin) return;

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
    const loginForm          = document.querySelector('form[action="../backend/auth/login_process.php"]');
    const emailInput         = document.getElementById('email');
    const passwordInput      = document.getElementById('password');
    const loginEmailError    = document.getElementById('login-email-error');
    const loginPasswordError = document.getElementById('login-password-error');
    const loginFormMessage   = document.getElementById('form-message');
    const loginCsrfInput     = document.getElementById('csrf_token');

    if (!loginForm || !emailInput || !passwordInput || !loginEmailError || !loginPasswordError || !loginFormMessage || !loginCsrfInput) return;

    const params         = new URLSearchParams(window.location.search);
    const message        = params.get('message');
    const errorField     = params.get('login_error');
    const successMessage = params.get('success');

    const navigationEntries = performance.getEntriesByType('navigation');
    const isReload = navigationEntries.length && navigationEntries[0].type === 'reload';
    if (isReload) {
        emailInput.value    = '';
        passwordInput.value = '';
    }

    if (message) {
        if (errorField === 'email')         loginEmailError.textContent    = message;
        else if (errorField === 'password') loginPasswordError.textContent = message;
        else                                loginFormMessage.textContent   = message;
    }

    if (errorField === 'password' && !successMessage) passwordInput.value = '';

    loginForm.addEventListener('submit', function (event) {
        loginEmailError.textContent    = '';
        loginPasswordError.textContent = '';
        loginFormMessage.textContent   = '';

        let valid = true;
        if (!emailInput.value.trim())     { loginEmailError.textContent    = 'Email is required.';    valid = false; }
        if (!passwordInput.value.trim())  { loginPasswordError.textContent = 'Password is required.'; valid = false; }
        if (!loginCsrfInput.value.trim()) { loginFormMessage.textContent   = 'CSRF token is missing. Please refresh the page.'; valid = false; }
        if (!valid) event.preventDefault();
    });

    if (successMessage) {
        loginFormMessage.textContent = successMessage;
        loginFormMessage.classList.remove('form-error');
        loginFormMessage.classList.add('form-success');
    }

    fetch('../backend/csrf_token.php', { method: 'GET', credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error('Failed'); return r.text(); })
        .then(token => {
            if (!token || !token.trim()) throw new Error('Empty');
            loginCsrfInput.value = token.trim();
        })
        .catch(error => {
            console.error('CSRF token error:', error);
            loginFormMessage.textContent = 'Unable to generate CSRF token. Please refresh the page.';
        });
});

// Signup form handling script
document.addEventListener('DOMContentLoaded', function () {
    const signupForm           = document.querySelector('form[action="../backend/auth/signup_process.php"]');
    const fullnameInput        = document.getElementById('fullname');
    const signupUsernameInput  = document.getElementById('signup-username');
    const signupEmailInput     = document.getElementById('signup-email');
    const signupPasswordInput  = document.getElementById('signup-password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const signupFullnameError  = document.getElementById('signup-fullname-error');
    const signupUsernameError  = document.getElementById('signup-username-error');
    const signupEmailError     = document.getElementById('signup-email-error');
    const signupPasswordError  = document.getElementById('signup-password-error');
    const signupConfirmError   = document.getElementById('signup-confirm-error');
    const signupFormMessage    = document.getElementById('form-message');
    const signupCsrfInput      = document.getElementById('csrf_token');
    const signupActionInput    = document.getElementById('signup_action');

    if (!signupForm || !fullnameInput || !signupUsernameInput || !signupEmailInput || !signupPasswordInput || !confirmPasswordInput || !signupFullnameError || !signupUsernameError || !signupEmailError || !signupPasswordError || !signupConfirmError || !signupFormMessage || !signupCsrfInput) return;

    const params         = new URLSearchParams(window.location.search);
    const message        = params.get('message');
    const errorField     = params.get('signup_error');
    const successMessage = params.get('success');

    const navigationEntries = performance.getEntriesByType('navigation');
    const isReload = navigationEntries.length && navigationEntries[0].type === 'reload';

    if (isReload) {
        fullnameInput.value        = '';
        signupUsernameInput.value  = '';
        signupEmailInput.value     = '';
        signupPasswordInput.value  = '';
        confirmPasswordInput.value = '';
    }

    if (message) {
        if (errorField === 'fullname')      signupFullnameError.textContent = message;
        else if (errorField === 'username') signupUsernameError.textContent = message;
        else if (errorField === 'email')    signupEmailError.textContent    = message;
        else if (errorField === 'password') signupPasswordError.textContent = message;
        else if (errorField === 'confirm')  signupConfirmError.textContent  = message;
        else                                signupFormMessage.textContent   = message;
    }

    signupForm.addEventListener('submit', function (event) {
        if (signupActionInput) signupActionInput.value = 'register_only';

        signupFullnameError.textContent  = '';
        signupUsernameError.textContent  = '';
        signupEmailError.textContent     = '';
        signupPasswordError.textContent  = '';
        signupConfirmError.textContent   = '';
        signupFormMessage.textContent    = '';

        let valid = true;
        if (!fullnameInput.value.trim())       { signupFullnameError.textContent  = 'Full name is required.'; valid = false; }
        if (!signupUsernameInput.value.trim())  { signupUsernameError.textContent  = 'Username is required.'; valid = false; }
        else if (!/^[A-Za-z0-9_]{3,30}$/.test(signupUsernameInput.value.trim())) { signupUsernameError.textContent = 'Use 3-30 letters, numbers, or underscores.'; valid = false; }
        if (!signupEmailInput.value.trim())     { signupEmailError.textContent     = 'Email is required.'; valid = false; }
        if (!signupPasswordInput.value.trim())  { signupPasswordError.textContent  = 'Password is required.'; valid = false; }
        if (!confirmPasswordInput.value.trim()) { signupConfirmError.textContent   = 'Please confirm your password.'; valid = false; }
        if (signupPasswordInput.value.trim() && confirmPasswordInput.value.trim() && signupPasswordInput.value.trim() !== confirmPasswordInput.value.trim()) {
            signupConfirmError.textContent = 'Passwords do not match.'; valid = false;
        }
        if (!signupCsrfInput.value.trim()) { signupFormMessage.textContent = 'CSRF token is missing. Please refresh the page.'; valid = false; }
        if (!valid) event.preventDefault();
    });

    const loginButton = document.getElementById('login-btn');
    if (loginButton) {
        loginButton.addEventListener('click', function () {
            if (signupActionInput) signupActionInput.value = 'register_and_login';

            signupFullnameError.textContent  = '';
            signupUsernameError.textContent  = '';
            signupEmailError.textContent     = '';
            signupPasswordError.textContent  = '';
            signupConfirmError.textContent   = '';
            signupFormMessage.textContent    = '';

            let valid = true;
            if (!fullnameInput.value.trim())       { signupFullnameError.textContent = 'Full name is required.'; valid = false; }
            if (!signupUsernameInput.value.trim())  { signupUsernameError.textContent = 'Username is required.'; valid = false; }
            else if (!/^[A-Za-z0-9_]{3,30}$/.test(signupUsernameInput.value.trim())) { signupUsernameError.textContent = 'Use 3-30 letters, numbers, or underscores.'; valid = false; }
            if (!signupEmailInput.value.trim())     { signupEmailError.textContent = 'Email is required.'; valid = false; }
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(signupEmailInput.value.trim())) { signupEmailError.textContent = 'Please enter a valid email address.'; valid = false; }
            if (!signupPasswordInput.value.trim())  { signupPasswordError.textContent = 'Password is required.'; valid = false; }
            if (!confirmPasswordInput.value.trim()) { signupConfirmError.textContent = 'Please confirm your password.'; valid = false; }
            else if (signupPasswordInput.value.trim() !== confirmPasswordInput.value.trim()) { signupConfirmError.textContent = 'Passwords do not match.'; valid = false; }
            if (!signupCsrfInput.value.trim()) { signupFormMessage.textContent = 'CSRF token is missing. Please refresh the page.'; valid = false; }

            if (!valid) return;
            signupForm.submit();
        });
    }

    if (successMessage) {
        signupFormMessage.textContent = successMessage;
        signupFormMessage.classList.remove('form-error');
        signupFormMessage.classList.add('form-success');
    }

    fetch('../backend/csrf_token.php', { method: 'GET', credentials: 'same-origin' })
        .then(r => { if (!r.ok) throw new Error('Failed'); return r.text(); })
        .then(token => {
            if (!token || !token.trim()) throw new Error('Empty');
            signupCsrfInput.value = token.trim();
        })
        .catch(error => {
            console.error('CSRF token error:', error);
            signupFormMessage.textContent = 'Unable to generate CSRF token. Please refresh the page.';
        });
});

// Setting page scripts
function handleSearch() {
    const searchValue = document.getElementById('searchInput').value;
    console.log('Searching for:', searchValue);
    window.appAlert('Search functionality: ' + searchValue);
}

function handleNotifications() { window.appAlert('Opening notifications...'); }
function handleProfile()       { window.appAlert('Opening profile...'); }
function handleChangePassword(){ window.appAlert('Redirecting to change password page...'); }

function handleLanguageChange() {
    const language = document.getElementById('languageSelect').value;
    console.log('Language changed to:', language);
    window.appAlert('Language changed to: ' + language);
}

function toggleNotification(type) {
    console.log('Toggling notification:', type);
    window.appAlert('Toggle ' + type + ' notifications');
}

function handleLogout() {
    window.appConfirm('Are you sure you want to log out?', { title: 'Log out', okText: 'Log out' }).then(function (ok) {
        if (ok) {
            localStorage.removeItem('theme');
            window.location.href = 'logout.php';
        }
    });
}

// Clear theme on any logout-btn click (covers admin sidebar plain <a> links too)
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.logout-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            localStorage.removeItem('theme');
        });
    });
});

// Load money page
document.addEventListener('DOMContentLoaded', function () {
    const amountInput         = document.getElementById('amountInput');
    const presetButtons       = document.querySelectorAll('.preset-btn');
    const loadButton          = document.getElementById('loadBtn');
    const fundingOptions      = document.querySelectorAll('.funding-option');
    const selectedSourceLabel = document.getElementById('selectedSourceLabel');

    if (!amountInput || !loadButton || !presetButtons.length || !fundingOptions.length) return;

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
            presetButtons.forEach(function (item) { item.classList.remove('is-active'); });
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
        if (radio) radio.addEventListener('change', syncFundingSelection);
    });

    loadButton.addEventListener('click', function (event) {
        const rawValue = amountInput.value.trim();
        const amount   = Number(rawValue);
        if (!rawValue || Number.isNaN(amount) || amount <= 0) {
            event.preventDefault();
            window.appAlert('Please enter a valid amount to load.').then(function () { amountInput.focus(); });
        }
    });

    syncFundingSelection();
});

// Dashboard: show/hide balances per stat card on click; transaction amounts toggle per row on click
document.addEventListener('DOMContentLoaded', function () {
    const dashboard = document.querySelector('body.user-dashboard .dashboard');
    if (!dashboard) return;

    const statCards = dashboard.querySelectorAll('.stats-grid .stat-card');
    const txAmountEls = dashboard.querySelectorAll('.dashboard-tx-amount');

    function hasPair(el) {
        return el && el.dataset && el.dataset.visibleBalance !== undefined && el.dataset.hiddenBalance !== undefined;
    }

    /** @param {{ valueEl: HTMLElement, btn: HTMLElement, masked: boolean }} card */
    function applyStatCardMasked(card, masked) {
        card.masked = masked;
        const valueEl = card.valueEl;
        if (hasPair(valueEl)) {
            valueEl.textContent = masked ? valueEl.dataset.hiddenBalance : valueEl.dataset.visibleBalance;
        }
        const btn = card.btn;
        btn.setAttribute('aria-pressed', masked ? 'true' : 'false');
        const eyeImg = btn.querySelector('img');
        if (eyeImg) {
            eyeImg.src = masked
                ? '../assets/icons/mdi_eye-off.png'
                : '../assets/icons/mdi_eye.png';
            eyeImg.alt = masked ? 'Hidden — click to show amounts' : 'Visible — click to hide amounts';
        }
    }

    const statCardStates = [];
    statCards.forEach(function (cardRoot) {
        const valueEl = cardRoot.querySelector('.stat-value');
        const btn = cardRoot.querySelector('.balance-toggle-btn');
        if (!valueEl || !btn || !hasPair(valueEl)) return;
        const card = { valueEl: valueEl, btn: btn, masked: true };
        statCardStates.push(card);
        applyStatCardMasked(card, true);
        btn.addEventListener('click', function () {
            applyStatCardMasked(card, !card.masked);
        });
    });

    if (!statCardStates.length && !txAmountEls.length) return;

    txAmountEls.forEach(function (el) {
        if (!hasPair(el)) return;
        let masked = true;
        el.textContent = el.dataset.hiddenBalance;

        function applyTxMasked(next) {
            masked = next;
            el.textContent = masked ? el.dataset.hiddenBalance : el.dataset.visibleBalance;
        }

        el.setAttribute('role', 'button');
        el.setAttribute('tabindex', '0');
        el.setAttribute('title', 'Show or hide amount');
        el.addEventListener('click', function () {
            applyTxMasked(!masked);
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                applyTxMasked(!masked);
            }
        });
    });
});

// Send money page
document.addEventListener('DOMContentLoaded', function () {
    const recipientInput       = document.getElementById('recipientInput');
    const amountInput          = document.getElementById('amountInput');
    const searchBtn            = document.getElementById('searchBtn');
    const summaryRecipient     = document.getElementById('summaryRecipient');
    const summaryAmount        = document.getElementById('summaryAmount');
    const sendMoneyForm        = document.getElementById('sendMoneyForm');
    const doneBtn              = document.getElementById('doneBtn');
    const balanceDisplay       = document.getElementById('balanceDisplay');
    const summaryBalanceAmount = document.getElementById('summaryBalanceAmount');
    const toggleSummaryBalance = document.getElementById('toggleSummaryBalance');

    if (!recipientInput || !amountInput || !searchBtn || !summaryRecipient || !summaryAmount || !sendMoneyForm || !doneBtn || !balanceDisplay || !summaryBalanceAmount || !toggleSummaryBalance) return;

    function syncTransferSummary() {
        summaryRecipient.textContent = recipientInput.value.trim();
        summaryAmount.textContent    = amountInput.value.trim() ? 'Rs ' + amountInput.value.trim() : '';
    }

    recipientInput.addEventListener('input', syncTransferSummary);
    amountInput.addEventListener('input', syncTransferSummary);

    searchBtn.addEventListener('click', function () {
        recipientInput.focus();
        syncTransferSummary();
    });

    sendMoneyForm.addEventListener('submit', function (event) {
        const recipient        = recipientInput.value.trim();
        const amount           = Number(amountInput.value.trim());
        const availableBalance = Number(balanceDisplay.textContent.replace(/,/g, ''));
        const currentUsername  = (sendMoneyForm.dataset.currentUsername || '').trim().toLowerCase();

        if (!recipient) {
            event.preventDefault();
            window.appAlert('Please enter a recipient.').then(function () { recipientInput.focus(); });
            return;
        }
        if (!/^[A-Za-z0-9_]{3,30}$/.test(recipient)) {
            event.preventDefault();
            window.appAlert('Please enter a valid recipient username.').then(function () { recipientInput.focus(); });
            return;
        }
        if (recipient.toLowerCase() === currentUsername) {
            event.preventDefault();
            window.appAlert('You cannot transfer money to your own account.').then(function () { recipientInput.focus(); });
            return;
        }
        if (!amount || Number.isNaN(amount) || amount <= 0) {
            event.preventDefault();
            window.appAlert('Please enter a valid amount.').then(function () { amountInput.focus(); });
            return;
        }
        if (amount > availableBalance) {
            event.preventDefault();
            window.appAlert('Insufficient balance for this transfer.').then(function () { amountInput.focus(); });
            return;
        }

        summaryRecipient.textContent = recipient;
        summaryAmount.textContent    = 'Rs ' + amount.toFixed(2);
    });

    function applySummaryBalanceHidden(hidden) {
        summaryBalanceAmount.textContent = hidden
            ? summaryBalanceAmount.dataset.hiddenBalance
            : summaryBalanceAmount.dataset.visibleBalance;
        toggleSummaryBalance.setAttribute('aria-pressed', hidden ? 'true' : 'false');
    }

    toggleSummaryBalance.addEventListener('click', function () {
        const isHidden   = toggleSummaryBalance.getAttribute('aria-pressed') === 'true';
        const shouldHide = !isHidden;
        applySummaryBalanceHidden(shouldHide);
    });

    syncTransferSummary();

    showPageToastFromQuery();
});

function createPageToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'page-toast page-toast--' + (type === 'error' ? 'error' : 'success');

    const title = document.createElement('div');
    title.className = 'page-toast-title';
    title.textContent = type === 'error' ? 'Transfer failed' : 'Success';

    const body = document.createElement('div');
    body.className = 'page-toast-message';
    body.textContent = String(message);

    toast.appendChild(title);
    toast.appendChild(body);

    toast.addEventListener('click', function () {
        toast.classList.remove('is-visible');
        window.setTimeout(() => toast.remove(), 250);
    });

    document.body.appendChild(toast);
    window.requestAnimationFrame(() => toast.classList.add('is-visible'));

    window.setTimeout(function () {
        toast.classList.remove('is-visible');
        window.setTimeout(() => toast.remove(), 250);
    }, 4000);
}

function showPageToastFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const message = params.get('message');
    const type = params.get('type') || 'success';
    if (!message) return;

    createPageToast(message, type);
    params.delete('message');
    params.delete('type');
    const newQuery = params.toString();
    const newUrl = window.location.pathname + (newQuery ? '?' + newQuery : '');
    window.history.replaceState(null, '', newUrl);
}

// Expense modal close / cancel buttons
document.addEventListener('DOMContentLoaded', function () {

    // Select modal
    const expenseModal = document.getElementById('expenseModal');

    // All buttons that should close modal
    const closeButtons = document.querySelectorAll(
        '.close-modal, .cancel-btn, .modal-close, .close-btn'
    );

    // Open buttons
    const openButtons = document.querySelectorAll(
        '.open-expense-modal, #openExpenseModal, #addExpenseBtn'
    );

    // Open modal
    openButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {

            if (!expenseModal) return;

            expenseModal.classList.add('show');
            expenseModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
    });

    // Close modal function
    function closeExpenseModal() {

        if (!expenseModal) return;

        expenseModal.classList.remove('show');
        expenseModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // X button + Cancel button
    closeButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closeExpenseModal();
        });
    });

    // Click outside modal closes it
    if (expenseModal) {
        expenseModal.addEventListener('click', function (e) {

            if (e.target === expenseModal) {
                closeExpenseModal();
            }
        });
    }

    // ESC key closes modal
    document.addEventListener('keydown', function (e) {

        if (e.key === 'Escape') {
            closeExpenseModal();
        }
    });
});

// ── Notification panel ──────────────────────────────────────────────────
(function () {
    var btn       = document.getElementById('notifBtn');
    var panel     = document.getElementById('notifPanel');
    var badge     = document.getElementById('notifBadge');
    var list      = document.getElementById('notifList');
    var empty     = document.getElementById('notifEmpty');
    var markAllBtn= document.getElementById('notifMarkAll');

    if (!btn || !panel) return;

    var ICONS = {
        money_received:  '💰',
        money_sent:      '📤',
        money_loaded:    '🏦',
        expense_added:   '🧾',
        budget_exceeded: '🚨',
        budget_warning:  '⚠️',
    };

    function timeAgo(dateStr) {
        var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60)   return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    function renderNotifications(data) {
        var items = data.notifications || [];
        var unread = data.unread || 0;

        // Badge
        if (unread > 0) {
            badge.textContent = unread > 99 ? '99+' : unread;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }

        // List
        // Remove old items (keep empty placeholder)
        var existing = list.querySelectorAll('.notif-item');
        existing.forEach(function (el) { el.remove(); });

        if (items.length === 0) {
            empty.style.display = 'flex';
            return;
        }
        empty.style.display = 'none';

        items.forEach(function (n) {
            var item = document.createElement('div');
            item.className = 'notif-item' + (n.is_read ? '' : ' unread');
            item.dataset.id = n.id;

            var icon = document.createElement('div');
            icon.className = 'notif-icon type-' + n.type;
            icon.textContent = ICONS[n.type] || '🔔';

            var content = document.createElement('div');
            content.className = 'notif-content';

            var title = document.createElement('div');
            title.className = 'notif-title';
            title.textContent = n.title;

            var body = document.createElement('div');
            body.className = 'notif-body';
            body.textContent = n.body;

            content.appendChild(title);
            content.appendChild(body);

            var time = document.createElement('div');
            time.className = 'notif-time';
            time.textContent = timeAgo(n.created_at);

            var dot = document.createElement('div');
            dot.className = 'notif-unread-dot';
            dot.style.display = n.is_read ? 'none' : 'block';

            item.appendChild(icon);
            item.appendChild(content);
            item.appendChild(time);
            item.appendChild(dot);

            item.addEventListener('click', function () {
                if (!n.is_read) {
                    markRead(n.id, item, dot);
                }
            });

            list.insertBefore(item, empty);
        });
    }

    function fetchNotifications() {
        fetch('../backend/api/notifications_api.php')
            .then(function (r) { return r.json(); })
            .then(function (data) { renderNotifications(data); })
            .catch(function () {});
    }

    function markRead(id, itemEl, dotEl) {
        fetch('../backend/api/notifications_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', id: id })
        }).then(function () {
            itemEl.classList.remove('unread');
            if (dotEl) dotEl.style.display = 'none';
            updateBadgeFromDom();
        }).catch(function () {});
    }

    function updateBadgeFromDom() {
        var unread = list.querySelectorAll('.notif-item.unread').length;
        if (unread > 0) {
            badge.textContent = unread > 99 ? '99+' : unread;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    markAllBtn.addEventListener('click', function () {
        fetch('../backend/api/notifications_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        }).then(function () {
            list.querySelectorAll('.notif-item').forEach(function (el) {
                el.classList.remove('unread');
                var dot = el.querySelector('.notif-unread-dot');
                if (dot) dot.style.display = 'none';
            });
            badge.style.display = 'none';
        }).catch(function () {});
    });

    // Toggle panel
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = panel.classList.contains('is-open');
        panel.classList.toggle('is-open', !isOpen);
        btn.setAttribute('aria-expanded', String(!isOpen));
        if (!isOpen) fetchNotifications();
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
        if (!panel.contains(e.target) && e.target !== btn) {
            panel.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    // Initial fetch + poll every 60s
    fetchNotifications();
    setInterval(fetchNotifications, 60000);
})();
