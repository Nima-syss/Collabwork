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
            if (modeSelect     && data.theme)    modeSelect.value     = data.theme;

            notifStates.email   = !!data.notif_email;
            notifStates.push    = !!data.notif_push;
            notifStates.expense = !!data.notif_expense;

            applyTheme(data.theme || 'light');
            updateNotifButtonStyles();
        } catch (e) {
            console.error('Could not load settings:', e);
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
        const map = { email: 'Email Notifications', push: 'Push Notifications', expense: 'Expense Alerts' };
        document.querySelectorAll('.settings-option').forEach(btn => {
            const key = Object.keys(map).find(k => btn.textContent.trim() === map[k]);
            if (key) {
                btn.style.background = notifStates[key] ? '#3e8a51' : '';
                btn.style.color      = notifStates[key] ? '#fff'     : '';
            }
        });
    }

    // ── Init ──────────────────────────────────────────────────────────────
    loadSettings();
});
