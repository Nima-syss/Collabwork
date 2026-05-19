document.addEventListener('DOMContentLoaded', function () {

    const API_URL = '../backend/api/settings_api.php';

    // ── DOM refs ──────────────────────────────────────────────────────────
    const languageSelect = document.getElementById('languageSelect');
    const modeSelect     = document.getElementById('modeSelect');

    // Notification buttons — track state locally, save on change
    const notifStates = { email: false, push: false, expense: false };

    // ── Load settings from server ─────────────────────────────────────────
    async function loadSettings() {
        try {
            const res  = await fetch(API_URL, { credentials: 'same-origin' });
            const data = await res.json();

            if (languageSelect && data.language) languageSelect.value = data.language;
            const storedPreference = localStorage.getItem('theme');
            const activeTheme = storedPreference || data.theme || 'light';

            if (modeSelect) modeSelect.value = activeTheme;

            notifStates.email   = !!data.notif_email;
            notifStates.push    = !!data.notif_push;
            notifStates.expense = !!data.notif_expense;

            applyTheme(activeTheme);
            updateNotifButtonStyles();
        } catch (e) {
            console.error('Could not load settings:', e);
            const fallbackTheme = localStorage.getItem('theme') || 'light';
            if (modeSelect) modeSelect.value = fallbackTheme;
            applyTheme(fallbackTheme);
        }
    }

    // ── Save settings to server ───────────────────────────────────────────
    async function saveSettings() {
        const payload = {
            language:      languageSelect?.value || 'en-US',
            theme:         modeSelect?.value     || 'light',
            notif_email:   notifStates.email,
            notif_push:    notifStates.push,
            notif_expense: notifStates.expense,
        };
        try {
            await fetch(API_URL, {
                method:      'POST',
                headers:     { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body:        JSON.stringify(payload),
            });
        } catch (e) {
            console.error('Could not save settings:', e);
        }
    }

    // ── Theme ─────────────────────────────────────────────────────────────
    function applyTheme(preference) {
        const resolved = preference === 'auto'
            ? (new Date().getHours() >= 12 ? 'dark' : 'light')
            : preference;

        document.body.classList.toggle('dark-mode', resolved === 'dark');
        document.documentElement.setAttribute('data-theme', resolved);
        localStorage.setItem('theme', preference); // keep local copy for other pages
    }

    // ── Exposed globals (called from setting.php inline onchange) ─────────
    window.handleLanguageChange = function () {
        saveSettings();
    };

    window.handleModeChange = function () {
        applyTheme(modeSelect?.value || 'light');
        saveSettings();
    };

    window.toggleNotification = function (type) {
        if (!(type in notifStates)) return;
        notifStates[type] = !notifStates[type];
        updateNotifButtonStyles();
        saveSettings();
    };

    function updateNotifButtonStyles() {
        document.querySelectorAll('.settings-option[data-notif]').forEach(function (btn) {
            const key = btn.getAttribute('data-notif');
            if (!key || !(key in notifStates)) return;
            btn.classList.toggle('is-on', notifStates[key]);
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────
    loadSettings();

    // ── Change Password Modal ─────────────────────────────────────────────
    (function () {
        var modal      = document.getElementById('changePasswordModal');
        var openBtn    = document.getElementById('openChangePasswordModal');
        var closeBtn   = document.getElementById('closeChangePasswordModal');
        var cancelBtn  = document.getElementById('cancelChangePassword');
        var submitBtn  = document.getElementById('submitChangePassword');
        var msgBox     = document.getElementById('cpMessage');

        if (!modal) return; // guard: modal not in DOM

        function openModal() {
            clearForm();
            modal.style.display = 'flex';
            document.getElementById('cpCurrentPassword').focus();
        }

        function closeModal() {
            modal.style.display = 'none';
            clearForm();
        }

        function clearForm() {
            document.getElementById('cpCurrentPassword').value = '';
            document.getElementById('cpNewPassword').value     = '';
            document.getElementById('cpConfirmPassword').value = '';
            hideMessage();
            // Reset eye icons back to hidden
            modal.querySelectorAll('.cp-eye-btn').forEach(function (btn) {
                var targetId = btn.getAttribute('data-target');
                var input = document.getElementById(targetId);
                if (input) input.type = 'password';
                var icon = btn.querySelector('.cp-eye-icon');
                if (icon) icon.src = '../assets/icons/mdi_eye-off.png';
            });
        }

        function showMessage(text, isError) {
            msgBox.textContent = text;
            msgBox.className   = 'cp-message ' + (isError ? 'cp-message-error' : 'cp-message-success');
            msgBox.style.display = 'block';
        }

        function hideMessage() {
            msgBox.style.display = 'none';
            msgBox.textContent   = '';
            msgBox.className     = 'cp-message';
        }

        // Open / close wiring
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });

        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);

        // Click outside modal to close
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        // Escape key to close
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
        });

        // Toggle password visibility (reuses existing eye icons from the project)
        modal.querySelectorAll('.cp-eye-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var input    = document.getElementById(targetId);
                var icon     = btn.querySelector('.cp-eye-icon');
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) icon.src = '../assets/icons/mdi_eye.png';
                } else {
                    input.type = 'password';
                    if (icon) icon.src = '../assets/icons/mdi_eye-off.png';
                }
            });
        });

        // Submit
        submitBtn.addEventListener('click', async function () {
            hideMessage();

            var current  = document.getElementById('cpCurrentPassword').value;
            var newPass  = document.getElementById('cpNewPassword').value;
            var confirm  = document.getElementById('cpConfirmPassword').value;

            // Client-side pre-validation (server validates again)
            if (!current || !newPass || !confirm) {
                showMessage('All fields are required.', true);
                return;
            }
            if (newPass !== confirm) {
                showMessage('New passwords do not match.', true);
                return;
            }
            if (newPass.length < 4) {
                showMessage('New password must be at least 4 characters.', true);
                return;
            }

            submitBtn.disabled   = true;
            submitBtn.textContent = 'Saving…';

            try {
                var res  = await fetch('../backend/api/change_password_api.php', {
                    method:      'POST',
                    headers:     { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body:        JSON.stringify({
                        current_password: current,
                        new_password:     newPass,
                        confirm_password: confirm,
                    }),
                });
                var data = await res.json();

                if (data.success) {
                    showMessage(data.message || 'Password changed successfully.', false);
                    // Clear fields after success, keep modal open briefly so user sees confirmation
                    document.getElementById('cpCurrentPassword').value = '';
                    document.getElementById('cpNewPassword').value     = '';
                    document.getElementById('cpConfirmPassword').value = '';
                    setTimeout(closeModal, 2000);
                } else {
                    showMessage(data.error || 'Something went wrong.', true);
                }
            } catch (err) {
                showMessage('Network error. Please try again.', true);
            } finally {
                submitBtn.disabled    = false;
                submitBtn.textContent = 'Save Password';
            }
        });
    }());
    // ── End Change Password Modal ─────────────────────────────────────────
});
