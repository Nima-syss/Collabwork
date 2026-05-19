document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const root = document.documentElement;
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    const card = document.querySelector('.qr-card');
    const qrRoot = document.getElementById('qrcode');
    const downloadBtn = document.getElementById('downloadBtn');

    if (!card || !qrRoot || typeof window.QRCode === 'undefined') {
        return;
    }

    function resolveTheme(themePreference) {
        if (themePreference === 'auto') {
            const currentHour = new Date().getHours();
            return currentHour >= 12 ? 'dark' : 'light';
        }

        return themePreference === 'dark' ? 'dark' : 'light';
    }

    function syncThemeUi(resolvedTheme) {
        if (themeIcon) {
            themeIcon.textContent = resolvedTheme === 'dark' ? '☀️' : '🌙';
        }
        if (themeToggleBtn) {
            themeToggleBtn.setAttribute('aria-label', resolvedTheme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
        }
    }

    function applyTheme(themePreference) {
        const storedPreference = themePreference === 'auto' ? 'auto' : (themePreference === 'dark' ? 'dark' : 'light');
        const resolvedTheme = resolveTheme(storedPreference);

        body.classList.toggle('dark-mode', resolvedTheme === 'dark');
        root.setAttribute('data-theme', resolvedTheme);
        localStorage.setItem('theme', storedPreference);
        syncThemeUi(resolvedTheme);
    }

    const qrText = (card.dataset.qrText || '').trim();

    function renderQr() {
        qrRoot.innerHTML = '';
        const size = Math.min(260, qrRoot.clientWidth || 260);

        new window.QRCode(qrRoot, {
            text: qrText || 'ewallet',
            width: size,
            height: size,
            colorDark: '#354037',
            colorLight: '#ffffff',
            correctLevel: window.QRCode.CorrectLevel.M
        });
    }

    function downloadQr() {
        const canvas = qrRoot.querySelector('canvas');
        const img = qrRoot.querySelector('img');
        const dataUrl = canvas ? canvas.toDataURL('image/png') : (img ? img.src : '');

        if (!dataUrl) {
            return;
        }

        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = 'ewallet-qr.png';
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    applyTheme(localStorage.getItem('theme') || 'light');
    renderQr();

    if (downloadBtn) {
        downloadBtn.addEventListener('click', downloadQr);
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function () {
            const currentPreference = localStorage.getItem('theme') || 'light';
            const currentResolvedTheme = resolveTheme(currentPreference);
            applyTheme(currentResolvedTheme === 'dark' ? 'light' : 'dark');
        });
    }
});

