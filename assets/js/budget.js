// assets/js/budget.js
// Requires: categories.js loaded before this file

document.addEventListener('DOMContentLoaded', function () {
    const budgetList            = document.getElementById('budgetList');
    const openBtn               = document.getElementById('openBudgetModal');
    const overlay               = document.getElementById('budgetModalOverlay');
    const closeBtn              = document.getElementById('closeBudgetModal');
    const cancelBtn             = document.getElementById('cancelBudgetBtn');
    const deleteBtn             = document.getElementById('deleteBudgetBtn');
    const form                  = document.getElementById('budgetForm');
    const categorySelect        = document.getElementById('budgetCategory');
    const limitInput            = document.getElementById('budgetLimit');
    const usedInput             = document.getElementById('budgetUsed');
    const editingCategoryInput  = document.getElementById('editingCategory');
    const pieCanvas             = document.getElementById('budgetPie');
    const legendRoot            = document.getElementById('budgetLegend');
    const totalBudgetValue      = document.getElementById('totalBudgetValue');
    const remainingBalanceValue = document.getElementById('remainingBalanceValue');
    const remainingBudgetValue  = document.getElementById('remainingBudgetValue');
    const overallUsedBar        = document.getElementById('overallUsedBar');
    const prevMonthBtn          = document.getElementById('prevMonth');
    const nextMonthBtn          = document.getElementById('nextMonth');
    const currentMonthLabel     = document.getElementById('currentMonthLabel');

    if (!budgetList || !openBtn || !overlay || !form || !categorySelect) return;

    const API_URL = '../backend/api/budget_api.php';

    // ── Month state ────────────────────────────────────────────────────────
    const now = new Date();
    let activeYear  = now.getFullYear();
    let activeMonth = now.getMonth() + 1; // 1-based

    function activeMonthString() {
        return activeYear + '-' + String(activeMonth).padStart(2, '0');
    }

    function updateMonthLabel() {
        if (!currentMonthLabel) return;
        const d = new Date(activeYear, activeMonth - 1, 1);
        currentMonthLabel.textContent = d.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        // Disable next button if we're already at current month
        const isCurrentMonth = activeYear === now.getFullYear() && activeMonth === now.getMonth() + 1;
        if (nextMonthBtn) nextMonthBtn.disabled = isCurrentMonth;
    }

    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', function () {
            activeMonth--;
            if (activeMonth < 1) { activeMonth = 12; activeYear--; }
            updateMonthLabel();
            render();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', function () {
            const isCurrentMonth = activeYear === now.getFullYear() && activeMonth === now.getMonth() + 1;
            if (isCurrentMonth) return;
            activeMonth++;
            if (activeMonth > 12) { activeMonth = 1; activeYear++; }
            updateMonthLabel();
            render();
        });
    }

    updateMonthLabel();
    const CATEGORIES =
        (typeof EXPENSE_CATEGORIES !== 'undefined' && Array.isArray(EXPENSE_CATEGORIES) && EXPENSE_CATEGORIES.length)
            ? EXPENSE_CATEGORIES
            : [
                { name: 'Foods',               emoji: '🍲', color: '#ff6b6b' },
                { name: 'Transportation',      emoji: '🚌', color: '#5f95ff' },
                { name: 'Housing',             emoji: '🏠', color: '#ffb020' },
                { name: 'Shopping',            emoji: '🛍️', color: '#ff6fb2' },
                { name: 'Health and Wellness', emoji: '🩺', color: '#2aa64a' },
                { name: 'Education',           emoji: '📚', color: '#8b6bff' },
                { name: 'Entertainment',       emoji: '🎮', color: '#31c4d6' },
                { name: 'Others',              emoji: '📦', color: '#7c8a96' },
                { name: 'Unbudgeted',          emoji: '⚠️', color: '#ff9800' },
            ];

    let cachedBudgets = [];

    function money(n, digits = 2) {
        const v = Number(n || 0);
        const safe = Number.isFinite(v) ? v : 0;
        return 'NRP ' + safe.toLocaleString(undefined, { minimumFractionDigits: digits, maximumFractionDigits: digits });
    }

    function clampPercent(v) {
        return Number.isFinite(v) ? Math.max(0, Math.min(100, v)) : 0;
    }

    function getWalletBalance() {
        if (!remainingBalanceValue) return null;
        const raw = remainingBalanceValue.dataset ? remainingBalanceValue.dataset.walletBalance : null;
        const n = Number(raw);
        return Number.isFinite(n) ? n : null;
    }

    function getCat(name) {
        return CATEGORIES.find(c => c.name === name) || { name, emoji: '🏷️', color: '#7c8a96' };
    }

    function normalizeBudget(item) {
        const limit = Number(item.monthly_limit ?? item.limit ?? 0);
        const used  = Number(item.used ?? 0);

        return {
            category:   String(item.category || '').trim(),
            limit:      Number.isFinite(limit) ? Math.max(0, limit) : 0,
            used:       Number.isFinite(used)  ? Math.max(0, used)  : 0,
            created_at: item.created_at ?? null,   // created timestamp from backend, if available
            updated_at: item.updated_at ?? null,   // updated timestamp from backend, if available
        };
    }

    async function apiFetch(method, body) {
        let url = API_URL;
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
        };
        if (method === 'GET') {
            url = API_URL + '?month=' + encodeURIComponent(activeMonthString());
        } else if (body) {
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        let data = null;
        try { data = await res.json(); } catch (e) { /* ignore */ }
        if (!res.ok) {
            const msg = data && data.error ? data.error : ('API error ' + res.status);
            throw new Error(msg);
        }
        return data;
    }

    async function loadBudgets() {
        const data = await apiFetch('GET');
        const budgets = Array.isArray(data.budgets) ? data.budgets.map(normalizeBudget) : [];
        return budgets;
    }

    async function saveBudget(category, limit, used) {
        await apiFetch('POST', { category, limit, used, month: activeMonthString() });
    }

    async function removeBudget(category) {
        await apiFetch('DELETE', { category, month: activeMonthString() });
    }

    function closeModal() {
        overlay.hidden = true;
        document.body.style.overflow = '';
    }

    function openModal(mode, categoryName, budgets) {
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';

        const existing = Array.isArray(budgets) ? budgets : [];
        const isEditing = mode === 'edit' && categoryName;

        function fillOptions(names) {
            categorySelect.innerHTML = '';
            names.forEach(name => {
                const cat = getCat(name);
                const opt = document.createElement('option');
                opt.value = cat.name;
                opt.textContent = (cat.emoji ? (cat.emoji + '  ') : '') + cat.name;
                categorySelect.appendChild(opt);
            });
        }

        function syncFromSelected() {
            const selected = String(categorySelect.value || '').trim();
            editingCategoryInput.value = selected;
            const current = existing.find(b => b.category === selected);
            if (deleteBtn) deleteBtn.style.display = current ? 'inline-flex' : 'none';
            const isUnb = selected === 'Unbudgeted';
            limitInput.readOnly = isUnb;
            limitInput.value = isUnb ? '0' : (current ? String(current.limit) : '');
            usedInput.value  = current ? String(current.used) : '0';
        }

        fillOptions(CATEGORIES.map(c => c.name));

        if (isEditing) {
            categorySelect.value = categoryName;
        }

        syncFromSelected();

        categorySelect.onchange = syncFromSelected;

        const onKeyDown = function (e) {
            if (e.key === 'Escape') closeModal();
        };

        document.addEventListener('keydown', onKeyDown, { once: true });
    }

    function drawPieChart(displayBudgets) {
        if (!pieCanvas || !legendRoot) return;
        const ctx = pieCanvas.getContext('2d');
        if (!ctx) return;

        const values = (Array.isArray(displayBudgets) ? displayBudgets : []).map(b => {
            const used = Number(b.used) || 0;
            const limit = Number(b.limit) || 0;
            return { category: b.category, value: Math.max(0, Math.min(used, limit || used)) };
        }).filter(s => s.value > 0);

        legendRoot.innerHTML = '';

        const w = pieCanvas.width || 320;
        const h = pieCanvas.height || 320;
        ctx.clearRect(0, 0, w, h);

        if (!values.length) {
            ctx.fillStyle = 'rgba(0,0,0,0.55)';
            ctx.font = '700 13px system-ui';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('No data', w / 2, h / 2);
            return;
        }

        const total = values.reduce((s, v) => s + v.value, 0);
        const cx = w / 2;
        const cy = h / 2;
        const r = Math.min(w, h) * 0.40;

        let angle = -Math.PI / 2;
        values.forEach(slice => {
            const cat = getCat(slice.category);
            const frac = total > 0 ? slice.value / total : 0;
            const next = angle + frac * (Math.PI * 2);

            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, angle, next);
            ctx.closePath();
            ctx.fillStyle = cat.color;
            ctx.fill();

            angle = next;
        });

        // hole
        ctx.beginPath();
        ctx.arc(cx, cy, r * 0.58, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();

        // center text
        ctx.fillStyle = 'rgba(0,0,0,0.75)';
        ctx.font = '900 13px system-ui';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('Used', cx, cy - 8);
        ctx.font = '900 14px system-ui';
        ctx.fillText(money(total).replace('NRP ', ''), cx, cy + 12);

        // legend
        values
            .slice()
            .sort((a, b) => b.value - a.value)
            .forEach(slice => {
                const cat = getCat(slice.category);
                const row = document.createElement('div');
                row.className = 'budget-legend-item';
                row.innerHTML =
                    '<div class="budget-legend-left">' +
                        '<span class="budget-legend-swatch" style="background:' + cat.color + '"></span>' +
                        '<span class="budget-legend-name">' + (cat.emoji ? (cat.emoji + ' ') : '') + slice.category + '</span>' +
                    '</div>' +
                    '<div class="budget-legend-value">' + money(slice.value).replace('NRP ', '') + '</div>';
                legendRoot.appendChild(row);
            });
    }

    async function render() {
        budgetList.innerHTML = '<div class="chart-placeholder">Loading…</div>';

        let budgets;

        try {
            budgets = await loadBudgets();
        } catch (e) {
            budgetList.innerHTML = '<div class="chart-placeholder">Failed to load budgets.</div>';
            return;
        }

        budgets.sort((a, b) => a.category.localeCompare(b.category));
        cachedBudgets = budgets.slice();

        const byCategory = new Map(budgets.map(b => [b.category, b]));
        const displayBudgets = CATEGORIES.map(c => byCategory.get(c.name) || { category: c.name, limit: 0, used: 0 });

        const totalLimit = displayBudgets.reduce((s, b) => s + (b.category === 'Unbudgeted' ? 0 : (Number(b.limit) || 0)), 0);
        const totalUsed = displayBudgets.reduce((s, b) => s + (b.category === 'Unbudgeted' ? 0 : (Number(b.used) || 0)), 0);
        const remaining = Math.max(0, totalLimit - totalUsed);
        const percentUsed = totalLimit > 0 ? (totalUsed / totalLimit) * 100 : 0;

        if (totalBudgetValue) totalBudgetValue.textContent = money(totalLimit, 2);
        if (remainingBudgetValue) remainingBudgetValue.textContent = money(remaining, 2);

        const walletBal = getWalletBalance();
        if (walletBal !== null && remainingBalanceValue) {
            remainingBalanceValue.textContent = money(walletBal, 2);
        }

        if (overallUsedBar) overallUsedBar.style.width = clampPercent(percentUsed) + '%';

        budgetList.innerHTML = '';
        displayBudgets.forEach(budget => {
            const cat = getCat(budget.category);
            const isUnb = budget.category === 'Unbudgeted';
            const hasLimit = !isUnb && (budget.limit || 0) > 0;
            const used = Number(budget.used || 0);
            const overAmount = hasLimit ? Math.max(0, used - budget.limit) : 0;
            const rem = hasLimit ? Math.max(0, budget.limit - used) : 0;
            const itemPercent = hasLimit ? (used / budget.limit) * 100 : 0;
            const statusText = isUnb
                ? 'No limit'
                : (!hasLimit ? 'Not set' : (overAmount > 0 ? 'Over limit' : (itemPercent >= 100 ? 'Limit reached' : 'On track')));
            const limitText = isUnb
                ? 'No cap'
                : (hasLimit ? money(budget.limit).replace('NRP ', '') : '—');
            const remText = isUnb
                ? ('Tracked this month: ' + money(used).replace('NRP ', ''))
                : (hasLimit
                ? (overAmount > 0 ? 'Budget is over the limit' : (money(rem).replace('NRP ', '') + ' remaining'))
                : 'Set a limit to track spending');
            const barColor = hasLimit ? cat.color : '#b7b7b7';

            const item = document.createElement('div');
            item.className = 'budget-item';
            item.dataset.searchText = [budget.category, statusText, remText].join(' ');
            item.innerHTML =
                '<div class="budget-item-top">' +
                    '<div class="budget-item-left">' +
                        '<span class="budget-pill" style="background:' + cat.color + '22;color:' + cat.color + '">' +
                            (cat.emoji ? (cat.emoji + ' ') : '') + budget.category +
                        '</span>' +
                        '<span class="budget-status">' + statusText + '</span>' +
                    '</div>' +
                    '<div class="budget-item-right">' +
                        '<div class="budget-amount"><small>' + money(used).replace('NRP ', '') + '</small> / ' + limitText + '</div>' +
                        '<button class="budget-edit-btn" type="button" aria-label="Edit">✎</button>' +
                    '</div>' +
                '</div>' +
                '<div class="budget-item-bottom">' +
                    '<div class="budget-progress"><span style="width:' + clampPercent(itemPercent) + '%;background:' + barColor + '"></span></div>' +
                    '<div class="budget-mini-text">' + remText + '</div>' +
                    '<div class="budget-mini-text" style="color:rgba(0,0,0,0.40);font-size:11px;">' +
                    (budget.updated_at ? '🕐 Last updated: ' + new Date(budget.updated_at).toLocaleString() : '') +
                    '</div>' +
                '</div>';

            item.querySelector('.budget-edit-btn').addEventListener('click', () => openModal('edit', budget.category, cachedBudgets));
            budgetList.appendChild(item);
        });

        drawPieChart(displayBudgets);

        const q = sessionStorage.getItem('global_search_query') || '';
        if (q && typeof window.applyGlobalSearch === 'function') {
            window.applyGlobalSearch(q);
        }
    }

    // Modal events

    openBtn.addEventListener('click', function () {
        openModal('manage', '', cachedBudgets);
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) {
            closeModal();
        }
    });

    if (deleteBtn) {
        deleteBtn.addEventListener('click', async function () {
            const cat = String(editingCategoryInput.value || categorySelect.value || '').trim();
            if (!cat) return;
            const ok = await window.appConfirm('Delete budget for ' + cat + '?', { title: 'Delete budget', okText: 'Delete' });
            if (!ok) return;
            try {
                await removeBudget(cat);
                closeModal();
                render();
            } catch (e) {
                await window.appAlert((e && e.message) ? e.message : 'Failed to delete.');
            }
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const category = String(categorySelect.value || '').trim();
        let limit = Number(limitInput.value || 0);
        const used = Number(usedInput.value || 0);

        if (!category) return;
        if (category === 'Unbudgeted') {
            limit = 0;
        }
        if (!Number.isFinite(limit) || limit < 0 || !Number.isFinite(used) || used < 0) {
            await window.appAlert('Limit and used must be non-negative numbers.');
            return;
        }

        try {
            await saveBudget(category, limit, used);
            closeModal();
            render();
        } catch (err) {
            await window.appAlert((err && err.message) ? err.message : 'Failed to save.');
        }
    });

    render();
});

