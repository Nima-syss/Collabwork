document.addEventListener('DOMContentLoaded', function () {

    const API_URL = '../backend/api/expenses_api.php';

    // ── Category config — order matches DB ENUM ───────────────────────────
    const CATEGORIES = [
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

    function getCat(name) {
        return CATEGORIES.find(c => c.name === name) || { name, emoji: '📦', color: '#7c8a96' };
    }

    // ── DOM refs ──────────────────────────────────────────────────────────
    const addBtn            = document.getElementById('addBtn');
    const filterBtns        = document.querySelectorAll('.filter-btn');
    const transactionList   = document.querySelector('.transaction-list');
    const expensesTableBody = document.getElementById('expensesTableBody');
    const progressBar       = document.getElementById('progressBar');
    const progressText      = document.getElementById('progressText');
    const graphCanvas       = document.getElementById('expensesGraph');
    const graphEmpty        = document.getElementById('expensesGraphEmpty');

    // ── State ─────────────────────────────────────────────────────────────
    let currentFilter = 'daily';
    const globalQuery = (sessionStorage.getItem('global_search_query') || '').trim();
    if (globalQuery) {
        currentFilter = 'monthly';
        filterBtns.forEach(b => b.classList.remove('active'));
        const monthlyBtn = Array.from(filterBtns).find(b => b.textContent.trim().toLowerCase() === 'monthly');
        if (monthlyBtn) monthlyBtn.classList.add('active');
    }

    // ── Balance refresh ───────────────────────────────────────────────────
    async function refreshBalanceDisplay() {
        try {
            const res  = await fetch('../backend/api/get_balance.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data.balance !== undefined) {
                const formatted = 'NRP ' + Number(data.balance).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                document.querySelectorAll(
                    '.wallet-balance-value, .topbar-balance, [data-balance-display]'
                ).forEach(el => {
                    el.textContent = formatted;
                });
            }
        } catch (e) { /* silent — balance will update on next page load */ }
    }

    // ── API helpers ───────────────────────────────────────────────────────
    async function apiFetch(method, body) {
        const opts = {
            method,
            headers:     { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
        };
        if (body) opts.body = JSON.stringify(body);
        const url = API_URL + (method === 'GET' ? '?filter=' + currentFilter : '');
        const res = await fetch(url, opts);
        let data = null;
        try { data = await res.json(); } catch (e) { /* ignore */ }
        if (!res.ok) {
            const msg = data && data.error ? data.error : ('API error ' + res.status);
            // Return warning payloads instead of throwing
            if (data && data.warning && (data.budget_exceeded || data.no_budget_warning)) {
                return data;
            }
            throw new Error(msg);
        }
        return data;
    }

    // ── Load & render ─────────────────────────────────────────────────────
    async function loadExpenses() {
        if (transactionList) {
            transactionList.innerHTML = '<p class="exp-empty">Loading…</p>';
        }
        if (expensesTableBody) {
            expensesTableBody.innerHTML = '<tr><td colspan="6" class="expenses-table-empty">Loading…</td></tr>';
        }
        try {
            const data = await apiFetch('GET');
            renderList(data.expenses || []);
            renderTable(data.expenses || []);
            renderProgress(data.total_spent, data.total_budget);
            renderGraph(data.graph_points || data.expenses || []);

            const q = sessionStorage.getItem('global_search_query') || '';
            if (q && typeof window.applyGlobalSearch === 'function') {
                window.applyGlobalSearch(q);
            }
        } catch (e) {
            if (transactionList) {
                transactionList.innerHTML = '<p class="exp-empty exp-error">Failed to load expenses.</p>';
            }
            if (expensesTableBody) {
                expensesTableBody.innerHTML = '<tr><td colspan="6" class="expenses-table-empty">Failed to load expenses.</td></tr>';
            }
            if (graphEmpty) graphEmpty.hidden = false;
        }
    }

    function renderList(expenses) {
        if (!transactionList) return;

        if (!expenses.length) {
            transactionList.innerHTML = '<p class="exp-empty">No expenses for this period.</p>';
            return;
        }

        // Group by date
        const groups = {};
        expenses.forEach(exp => {
            const d = exp.expense_date;
            if (!groups[d]) groups[d] = [];
            groups[d].push(exp);
        });

        transactionList.innerHTML = '';

        Object.entries(groups).forEach(([date, items]) => {
            const header = document.createElement('div');
            header.className = 'exp-date-header';
            header.textContent = formatDate(date);
            transactionList.appendChild(header);

            items.forEach(exp => {
                const row = document.createElement('div');
                row.className = 'exp-row';
                row.dataset.searchText = [exp.expense_date || '', exp.category || '', exp.note || ''].join(' ');
                row.innerHTML =
                    '<div class="exp-meta">' +
                        '<div class="exp-cat">' + escHtml(exp.category) + '</div>' +
                    '</div>' +
                    '<div class="exp-right">' +
                        '<div class="exp-amount">- NRP ' + fmtNum(exp.amount) + '</div>' +
                    '</div>';
                transactionList.appendChild(row);
            });
        });
    }

    function renderTable(expenses) {
        if (!expensesTableBody) return;

        if (!expenses.length) {
            expensesTableBody.innerHTML = '<tr><td colspan="6" class="expenses-table-empty">No expenses for this period.</td></tr>';
            return;
        }

        const rows = expenses.map(exp => {
            const date       = String(exp.expense_date || '').slice(0, 10);
            const cat        = escHtml(exp.category || '');
            const note       = escHtml(exp.note || '');
            const amt        = fmtNum(exp.amount || 0);
            const id         = String(exp.id || '');
            const searchText = escHtml([date, exp.category || '', exp.note || ''].join(' '));
            return (
                '<tr data-expense-id="' + escHtml(id) + '" data-search-text="' + searchText + '">' +
                    '<td>' + escHtml(date) + '</td>' +
                    '<td>' + cat + '</td>' +
                    '<td>' + (note || '<span style="color:#999">—</span>') + '</td>' +
                    '<td>- NRP ' + escHtml(amt) + '</td>' +
                    '<td><button class="expenses-action-btn update" type="button" data-action="edit" data-id="' + escHtml(id) + '">Update</button></td>' +
                    '<td><button class="expenses-action-btn delete" type="button" data-action="delete" data-id="' + escHtml(id) + '">Delete</button></td>' +
                '</tr>'
            );
        });

        expensesTableBody.innerHTML = rows.join('');

        expensesTableBody.querySelectorAll('button[data-action="edit"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id  = btn.getAttribute('data-id');
                const exp = expenses.find(e => String(e.id) === String(id));
                if (exp) openModal('edit', exp);
            });
        });

        expensesTableBody.querySelectorAll('button[data-action="delete"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                deleteExpense(id);
            });
        });
    }

    function renderProgress(totalSpent, totalBudget) {
        if (!progressBar || !progressText) return;

        const spent  = Number(totalSpent  || 0);
        const budget = Number(totalBudget || 0);
        const pct    = budget > 0 ? (spent / budget) * 100 : 0;

        progressBar.style.width = Math.min(pct, 100).toFixed(1) + '%';

        if (spent > budget && budget > 0) {
            const over = spent - budget;
            progressText.innerHTML =
                '⚠️ Budget over the limit by <strong>NRP ' + fmtNum(over) + '</strong>';
            progressBar.style.background = '#ff4d4f';
        } else {
            progressText.textContent = 'Saving Progress ' + pct.toFixed(0) + '%';
            progressBar.style.background = pct >= 90 ? '#e55' : '#3e8a51';
        }
    }

    function renderGraph(series) {
        if (!graphCanvas) return;

        const ctx = graphCanvas.getContext('2d');
        if (!ctx) return;

        const points = Array.isArray(series) ? series : [];
        let labels = [];
        let values = [];

        if (points.length && Object.prototype.hasOwnProperty.call(points[0], 'label')) {
            labels = points.map(p => String(p.label || ''));
            values = points.map(p => Number(p.value || 0));
        } else {
            const totalsByDate = new Map();
            points.forEach(exp => {
                const date   = String(exp.expense_date || '').slice(0, 10);
                const amount = Number(exp.amount || 0);
                if (!date) return;
                totalsByDate.set(date, (totalsByDate.get(date) || 0) + (Number.isFinite(amount) ? amount : 0));
            });
            labels = Array.from(totalsByDate.keys()).sort();
            values = labels.map(d => totalsByDate.get(d) || 0);
        }

        values = values.map(v => (Number.isFinite(v) ? Math.max(0, v) : 0));

        if (!labels.length || values.every(v => v <= 0)) {
            if (graphEmpty) graphEmpty.hidden = false;
            ctx.clearRect(0, 0, graphCanvas.width || 0, graphCanvas.height || 0);
            return;
        }
        if (graphEmpty) graphEmpty.hidden = true;

        const dpr  = window.devicePixelRatio || 1;
        const cssW = graphCanvas.clientWidth  || 600;
        const cssH = graphCanvas.clientHeight || 220;
        graphCanvas.width  = Math.floor(cssW * dpr);
        graphCanvas.height = Math.floor(cssH * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const w         = cssW;
        const h         = cssH;
        const isDark    = document.body.classList.contains('dark-mode') || document.documentElement.getAttribute('data-theme') === 'dark';
        const gridColor = isDark ? 'rgba(232, 244, 236, 0.16)' : 'rgba(0,0,0,0.10)';
        const axisColor = isDark ? 'rgba(232, 244, 236, 0.88)' : 'rgba(0,0,0,0.70)';
        const lineColor = isDark ? '#7ee270' : '#0f5a13';
        const dotFill   = isDark ? '#f4fff4' : '#0e0e0e';

        ctx.clearRect(0, 0, w, h);

        const maxV = Math.max(...values);
        const yMax = niceCeil(maxV);

        const isMonthlySeries = labels.length === 12 && labels.every(l => /^\d{4}-\d{2}$/.test(String(l)));
        const padTop    = 34;
        const padRight  = 18;
        const padBottom = (labels.length > 6) ? (isMonthlySeries ? 92 : 64) : 34;

        ctx.font      = '600 12px system-ui';
        ctx.textAlign = 'left';
        const maxLabel = formatNrp(yMax);
        const labelW   = ctx.measureText(maxLabel).width;
        const padLeft  = Math.max(72, Math.min(130, Math.ceil(labelW + 24)));

        const chartX = padLeft;
        const chartY = padTop;
        const chartW = Math.max(10, w - padLeft - padRight);
        const chartH = Math.max(10, h - padTop - padBottom);

        ctx.fillStyle    = axisColor;
        ctx.font         = '800 14px system-ui';
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText(isMonthlySeries ? 'Monthly Totals (NRP)' : 'Totals (NRP)', w / 2, 8);

        ctx.strokeStyle  = gridColor;
        ctx.lineWidth    = 1;
        ctx.fillStyle    = axisColor;
        ctx.font         = '600 12px system-ui';
        ctx.textAlign    = 'right';
        ctx.textBaseline = 'middle';
        const ticks = 5;
        for (let i = 0; i < ticks; i++) {
            const t   = i / (ticks - 1);
            const y   = chartY + chartH - t * chartH;
            const val = yMax * t;
            ctx.beginPath();
            ctx.moveTo(chartX, y);
            ctx.lineTo(chartX + chartW, y);
            ctx.stroke();
            ctx.fillText(formatNrp(val), chartX - 12, y);
        }

        const n   = values.length;
        const pts = values.map((v, i) => {
            const x = (n === 1)
                ? (chartX + chartW / 2)
                : (chartX + (chartW * i) / (n - 1));
            const t = yMax > 0 ? (v / yMax) : 0;
            const y = chartY + chartH - (t * chartH);
            return { x, y, v, label: labels[i] };
        });

        ctx.strokeStyle = lineColor;
        ctx.lineWidth   = 2.5;
        ctx.lineJoin    = 'round';
        ctx.lineCap     = 'round';
        ctx.beginPath();
        pts.forEach((p, i) => {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.stroke();

        ctx.font         = '800 11px system-ui';
        ctx.textAlign    = 'center';
        ctx.textBaseline = 'bottom';
        pts.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
            ctx.fillStyle   = dotFill;
            ctx.fill();
            ctx.lineWidth   = 2;
            ctx.strokeStyle = lineColor;
            ctx.stroke();

            if (p.v > 0 && p.y < (chartY + chartH - 12)) {
                ctx.fillStyle  = axisColor;
                const minLabelY = chartY + 28;
                const labelY    = Math.max(minLabelY, p.y - 10);
                ctx.fillText(formatNrp(p.v), p.x, labelY);
            }
        });

        ctx.fillStyle    = axisColor;
        ctx.font         = (labels.length > 6) ? '700 10px system-ui' : '700 11px system-ui';
        ctx.textBaseline = 'alphabetic';
        const rotate = labels.length > 6;
        pts.forEach(p => {
            const label = formatPeriodLabel(p.label);
            if (!label) return;
            ctx.save();
            ctx.translate(p.x, chartY + chartH + (rotate ? (isMonthlySeries ? 46 : 34) : 22));
            if (rotate) ctx.rotate(isMonthlySeries ? (-Math.PI / 2) : (-Math.PI / 3));
            ctx.textAlign = rotate ? 'right' : 'center';
            ctx.fillText(label, 0, 0);
            ctx.restore();
        });
    }

    function formatPeriodLabel(raw) {
        const s = String(raw || '').trim();
        if (!s) return '';
        if (/^\d{4}-\d{2}$/.test(s)) {
            const d = new Date(s + '-01T00:00:00');
            return d.toLocaleDateString(undefined, { month: 'short' });
        }
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
            const d = new Date(s + 'T00:00:00');
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }
        return s;
    }

    function formatNrp(n) {
        const v    = Number(n || 0);
        const safe = Number.isFinite(v) ? v : 0;
        return 'NRP ' + safe.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function niceCeil(maxValue) {
        const v = Number(maxValue || 0);
        if (!Number.isFinite(v) || v <= 0) return 100;
        const exp    = Math.floor(Math.log10(v));
        const base   = Math.pow(10, exp);
        const frac   = v / base;
        let niceFrac = 1;
        if (frac <= 1)      niceFrac = 1;
        else if (frac <= 2) niceFrac = 2;
        else if (frac <= 5) niceFrac = 5;
        else                niceFrac = 10;
        return niceFrac * base;
    }

    // ── Modal ─────────────────────────────────────────────────────────────
    function buildCategoryOptions(selected) {
        return CATEGORIES.map(c =>
            '<option value="' + c.name + '"' + (c.name === selected ? ' selected' : '') + '>' +
                c.emoji + '  ' + c.name +
            '</option>'
        ).join('');
    }

    function openModal(mode, exp) {
        document.getElementById('expModal')?.remove();

        const isEdit = mode === 'edit' && exp;
        const modal  = document.createElement('div');
        modal.id     = 'expModal';
        modal.className = 'exp-modal-overlay';

        modal.innerHTML =
            '<div class="exp-modal">' +
                '<div class="exp-modal-header">' +
                    '<h3>' + (isEdit ? 'Edit Expense' : 'Add Expense') + '</h3>' +
                    '<button class="exp-modal-close" id="expClose">×</button>' +
                '</div>' +
                '<div class="exp-modal-body">' +
                    '<label class="exp-field">' +
                        '<span class="exp-label">Category</span>' +
                        '<div class="exp-select-wrap">' +
                            '<select id="expCat" class="exp-select">' +
                                buildCategoryOptions(isEdit ? exp.category : CATEGORIES[0].name) +
                            '</select>' +
                        '</div>' +
                    '</label>' +
                    '<div class="exp-badge-row" id="expBadgeRow"></div>' +
                    '<label class="exp-field">' +
                        '<span class="exp-label">Amount (NRP)</span>' +
                        '<input id="expAmt" class="exp-input" type="number" min="0.01" step="0.01"' +
                            ' placeholder="0.00" value="' + (isEdit ? exp.amount : '') + '" />' +
                    '</label>' +
                    '<label class="exp-field">' +
                        '<span class="exp-label">Note <span style="font-weight:400;color:var(--text-muted,#7a8f7e)">(optional)</span></span>' +
                        '<input id="expNote" class="exp-input" type="text" placeholder="e.g. Lunch at restaurant"' +
                            ' value="' + escHtml(isEdit ? (exp.note || '') : '') + '" />' +
                    '</label>' +
                    '<label class="exp-field">' +
                        '<span class="exp-label">Date</span>' +
                        '<input id="expDate" class="exp-input" type="date"' +
                            ' value="' + (isEdit ? exp.expense_date : today()) + '" />' +
                    '</label>' +
                    '<p id="expError" class="exp-modal-error" hidden></p>' +
                '</div>' +
                '<div class="exp-modal-footer">' +
                    '<button class="exp-modal-btn exp-cancel" id="expCancel">Cancel</button>' +
                    '<button class="exp-modal-btn exp-save"   id="expSave">Save</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(modal);
        updateBadge(modal);

        modal.querySelector('#expClose').addEventListener('click',  () => modal.remove());
        modal.querySelector('#expCancel').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
        modal.querySelector('#expCat').addEventListener('change', () => updateBadge(modal));

        modal.querySelector('#expSave').addEventListener('click', async () => {
            const errEl   = modal.querySelector('#expError');
            const payload = {
                category:     modal.querySelector('#expCat').value,
                amount:       parseFloat(modal.querySelector('#expAmt').value),
                note:         modal.querySelector('#expNote').value.trim(),
                expense_date: modal.querySelector('#expDate').value,
            };

            if (isNaN(payload.amount) || payload.amount <= 0) {
                errEl.textContent = 'Please enter a valid amount.';
                errEl.hidden = false;
                return;
            }
            errEl.hidden = true;

            try {
                let data;
                if (isEdit) { payload.id = exp.id; data = await apiFetch('PUT', payload); }
                else        { data = await apiFetch('POST', payload); }

                // ── No budget set warning ─────────────────────────────────
                if (data && data.warning && data.no_budget_warning) {
                    const confirmMsg =
                        'You have not set a category budget for ' + (data.month || 'this month') + '.\n' +
                        'Pick the Unbudgeted category for spending with no limit, or set limits on the Budget page.\n\n' +
                        'Do you still want to add this expense?';

                    const confirmed = await window.appConfirm(confirmMsg, { title: 'No budget set', okText: 'Add anyway' });

                    if (confirmed) {
                        payload.force_no_budget = true;
                        data = await apiFetch('POST', payload);

                        if (data && data.warning && data.budget_exceeded) {
                            const overConfirm = await window.appConfirm(
                                'Category: ' + data.category + '\n\n' +
                                'You are going NRP ' + data.over_by + ' over your NRP ' + data.limit + ' limit.\n\n' +
                                'Do you still want to add this expense?',
                                { title: 'Budget warning', okText: 'Yes, add it' }
                            );
                            if (overConfirm) {
                                payload.force_over_budget = true;
                                await apiFetch('POST', payload);
                                modal.remove();
                                await refreshBalanceDisplay();
                                loadExpenses();
                            } else {
                                errEl.textContent = 'Expense not added. Consider updating your budget for ' + data.category + ' first.';
                                errEl.hidden = false;
                            }
                            return;
                        }

                        modal.remove();
                        await refreshBalanceDisplay();
                        loadExpenses();
                    } else {
                        errEl.textContent = 'Expense not added. Set your budget first on the Budget page.';
                        errEl.hidden = false;
                    }
                    return;
                }

                // ── Budget exceeded warning ───────────────────────────────
                if (data && data.warning && data.budget_exceeded) {
                    const confirmMsg =
                        'Category: ' + data.category + '\n\n' +
                        'You are going NRP ' + data.over_by + ' over your NRP ' + data.limit + ' limit.\n\n' +
                        'Do you still want to add this expense?';

                    const confirmed = await window.appConfirm(confirmMsg, { title: 'Budget warning', okText: 'Yes, add it' });

                    if (confirmed) {
                        payload.force_over_budget = true;
                        if (isEdit) { payload.id = exp.id; data = await apiFetch('PUT', payload); }
                        else        { data = await apiFetch('POST', payload); }
                        modal.remove();
                        await refreshBalanceDisplay();
                        loadExpenses();
                    } else {
                        errEl.textContent = 'Expense not added. Consider updating your budget for ' + data.category + ' first.';
                        errEl.hidden = false;
                    }
                    return;
                }

                modal.remove();
                await refreshBalanceDisplay();
                loadExpenses();

                if (data && data.show_push_notification && data.push_message) {
                    window.appAlert(data.push_message);
                }

            } catch (err) {
                const msg = (err && err.message) ? err.message : 'Failed to save. Please try again.';
                errEl.textContent = msg;
                errEl.hidden = false;
            }
        });
    }

    function updateBadge(modal) {
        const selected = modal.querySelector('#expCat').value;
        const cat      = getCat(selected);
        const row      = modal.querySelector('#expBadgeRow');
        row.innerHTML  =
            '<span class="exp-badge" style="background:' + cat.color + '22;color:' + cat.color + ';border:1px solid ' + cat.color + '44;">' +
                cat.emoji + ' ' + cat.name +
            '</span>';
    }

    // ── Delete expense ────────────────────────────────────────────────────
    async function deleteExpense(id) {
        const ok = await window.appConfirm('Delete this expense?', { title: 'Delete expense', okText: 'Delete' });
        if (!ok) return;
        try {
            await apiFetch('DELETE', { id });
            await refreshBalanceDisplay();  // ← refund balance shown immediately
            loadExpenses();
        } catch (e) {
            await window.appAlert((e && e.message) ? e.message : 'Failed to delete. Please try again.');
        }
    }

    // ── Filter buttons ────────────────────────────────────────────────────
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentFilter = btn.textContent.trim().toLowerCase();
            loadExpenses();
        });
    });

    if (addBtn) addBtn.addEventListener('click', () => openModal('add'));

    // ── Utilities ─────────────────────────────────────────────────────────
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmtNum(n) {
        return Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function today() {
        return new Date().toISOString().slice(0, 10);
    }
    function formatDate(iso) {
        const d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }

    // ── Init ──────────────────────────────────────────────────────────────
    loadExpenses();
});