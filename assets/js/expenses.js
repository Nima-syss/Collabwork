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
    ];

    function getCat(name) {
        return CATEGORIES.find(c => c.name === name) || { name, emoji: '📦', color: '#7c8a96' };
    }

    // ── DOM refs ──────────────────────────────────────────────────────────
    const addBtn          = document.getElementById('addBtn');
    const filterBtns      = document.querySelectorAll('.filter-btn');
    const transactionList = document.querySelector('.transaction-list');
    const expensesTableBody = document.getElementById('expensesTableBody');
    const progressBar     = document.getElementById('progressBar');
    const progressText    = document.getElementById('progressText');
    const graphCanvas     = document.getElementById('expensesGraph');
    const graphEmpty      = document.getElementById('expensesGraphEmpty');

    // ── State ─────────────────────────────────────────────────────────────
    let currentFilter = 'daily';
    const globalQuery = (sessionStorage.getItem('global_search_query') || '').trim();
    if (globalQuery) {
        currentFilter = 'monthly';
        filterBtns.forEach(b => b.classList.remove('active'));
        const monthlyBtn = Array.from(filterBtns).find(b => b.textContent.trim().toLowerCase() === 'monthly');
        if (monthlyBtn) monthlyBtn.classList.add('active');
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
            // Date header
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
            const date = String(exp.expense_date || '').slice(0, 10);
            const cat  = escHtml(exp.category || '');
            const note = escHtml(exp.note || '');
            const amt  = fmtNum(exp.amount || 0);
            const id   = String(exp.id || '');
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
                const id = btn.getAttribute('data-id');
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
        const pct = totalBudget > 0 ? Math.min(100, (totalSpent / totalBudget) * 100) : 0;
        progressBar.style.width    = pct.toFixed(1) + '%';
        progressText.textContent   = 'Saving Progress ' + pct.toFixed(0) + '%';
        progressBar.style.background = pct >= 90 ? '#e55' : '#3e8a51';
    }

    function renderGraph(series) {
        if (!graphCanvas) return;

        const ctx = graphCanvas.getContext('2d');
        if (!ctx) return;

        // Accept either backend `graph_points` [{label, value}] or raw expenses [{expense_date, amount}].
        const points = Array.isArray(series) ? series : [];
        let labels = [];
        let values = [];

        if (points.length && Object.prototype.hasOwnProperty.call(points[0], 'label')) {
            labels = points.map(p => String(p.label || ''));
            values = points.map(p => Number(p.value || 0));
        } else {
            const totalsByDate = new Map();
            points.forEach(exp => {
                const date = String(exp.expense_date || '').slice(0, 10);
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
            // Clear any previous drawing (best-effort, in case canvas has size already)
            ctx.clearRect(0, 0, graphCanvas.width || 0, graphCanvas.height || 0);
            return;
        }
        if (graphEmpty) graphEmpty.hidden = true;

        const dpr = window.devicePixelRatio || 1;
        const cssW = graphCanvas.clientWidth || 600;
        const cssH = graphCanvas.clientHeight || 220;
        graphCanvas.width = Math.floor(cssW * dpr);
        graphCanvas.height = Math.floor(cssH * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const w = cssW;
        const h = cssH;
        // The graph sits on a light panel even in dark mode, so always use dark text/grid for legibility.
        const gridColor = 'rgba(0,0,0,0.10)';
        const axisColor = 'rgba(0,0,0,0.70)';
        const lineColor = '#0f5a13';
        const dotFill   = '#0e0e0e';

        ctx.clearRect(0, 0, w, h);

        const maxV = Math.max(...values);
        const yMax = niceCeil(maxV);

        // Layout
        const isMonthlySeries = labels.length === 12 && labels.every(l => /^\d{4}-\d{2}$/.test(String(l)));
        const padTop = 34;
        const padRight = 18;
        const padBottom = (labels.length > 6) ? (isMonthlySeries ? 92 : 64) : 34;

        // Dynamic left padding so "NRP 999,999.99" never gets clipped.
        ctx.font = '600 12px system-ui';
        ctx.textAlign = 'left';
        const maxLabel = formatNrp(yMax);
        const labelW = ctx.measureText(maxLabel).width;
        const padLeft = Math.max(72, Math.min(130, Math.ceil(labelW + 24)));

        const chartX = padLeft;
        const chartY = padTop;
        const chartW = Math.max(10, w - padLeft - padRight);
        const chartH = Math.max(10, h - padTop - padBottom);

        // Title
        ctx.fillStyle = axisColor;
        ctx.font = '800 14px system-ui';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText(isMonthlySeries ? 'Monthly Totals (NRP)' : 'Totals (NRP)', w / 2, 8);

        // Grid + Y labels
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 1;
        ctx.fillStyle = axisColor;
        ctx.font = '600 12px system-ui';
        ctx.textAlign = 'right';
        ctx.textBaseline = 'middle';
        const ticks = 5;
        for (let i = 0; i < ticks; i++) {
            const t = i / (ticks - 1);
            const y = chartY + chartH - t * chartH;
            const val = yMax * t;
            ctx.beginPath();
            ctx.moveTo(chartX, y);
            ctx.lineTo(chartX + chartW, y);
            ctx.stroke();
            ctx.fillText(formatNrp(val), chartX - 12, y);
        }

        // Build x/y points
        const n = values.length;
        const pts = values.map((v, i) => {
            const x = (n === 1)
                ? (chartX + chartW / 2)
                : (chartX + (chartW * i) / (n - 1));
            const t = yMax > 0 ? (v / yMax) : 0;
            const y = chartY + chartH - (t * chartH);
            return { x, y, v, label: labels[i] };
        });

        // Line
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.beginPath();
        pts.forEach((p, i) => {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.stroke();

        // Points + value labels
        ctx.font = '800 11px system-ui';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'bottom';
        pts.forEach(p => {
            // dot
            ctx.beginPath();
            ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
            ctx.fillStyle = dotFill;
            ctx.fill();
            ctx.lineWidth = 2;
            ctx.strokeStyle = lineColor;
            ctx.stroke();

            // value (avoid clutter for zeros / baseline points)
            if (p.v > 0 && p.y < (chartY + chartH - 12)) {
                ctx.fillStyle = axisColor;
                const minLabelY = chartY + 28; // keep clear of the title area
                const labelY = Math.max(minLabelY, p.y - 10);
                ctx.fillText(formatNrp(p.v), p.x, labelY);
            }
        });

        // X labels
        ctx.fillStyle = axisColor;
        ctx.font = (labels.length > 6) ? '700 10px system-ui' : '700 11px system-ui';
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

    function roundRect(ctx, x, y, w, h, r) {
        const rr = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + rr, y);
        ctx.arcTo(x + w, y, x + w, y + h, rr);
        ctx.arcTo(x + w, y + h, x, y + h, rr);
        ctx.arcTo(x, y + h, x, y, rr);
        ctx.arcTo(x, y, x + w, y, rr);
        ctx.closePath();
    }

    function formatPeriodLabel(raw) {
        const s = String(raw || '').trim();
        if (!s) return '';
        // Month period: YYYY-MM
        if (/^\d{4}-\d{2}$/.test(s)) {
            const d = new Date(s + '-01T00:00:00');
            return d.toLocaleDateString(undefined, { month: 'short' });
        }
        // Date period: YYYY-MM-DD
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
            const d = new Date(s + 'T00:00:00');
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }
        return s;
    }

    function formatNrp(n) {
        const v = Number(n || 0);
        const safe = Number.isFinite(v) ? v : 0;
        return 'NRP ' + safe.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function niceCeil(maxValue) {
        const v = Number(maxValue || 0);
        if (!Number.isFinite(v) || v <= 0) return 100;
        const exp = Math.floor(Math.log10(v));
        const base = Math.pow(10, exp);
        const frac = v / base;
        let niceFrac = 1;
        if (frac <= 1) niceFrac = 1;
        else if (frac <= 2) niceFrac = 2;
        else if (frac <= 5) niceFrac = 5;
        else niceFrac = 10;
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
                    // Category
                    '<label class="exp-field">' +
                        '<span class="exp-label">Category</span>' +
                        '<div class="exp-select-wrap">' +
                            '<select id="expCat" class="exp-select">' +
                                buildCategoryOptions(isEdit ? exp.category : CATEGORIES[0].name) +
                            '</select>' +
                        '</div>' +
                    '</label>' +

                    // Category badges preview
                    '<div class="exp-badge-row" id="expBadgeRow"></div>' +

                    // Amount
                    '<label class="exp-field">' +
                        '<span class="exp-label">Amount (NRP)</span>' +
                        '<input id="expAmt" class="exp-input" type="number" min="0.01" step="0.01"' +
                            ' placeholder="0.00" value="' + (isEdit ? exp.amount : '') + '" />' +
                    '</label>' +

                    // Note
                    '<label class="exp-field">' +
                        '<span class="exp-label">Note <span style="font-weight:400;color:#aaa">(optional)</span></span>' +
                        '<input id="expNote" class="exp-input" type="text" placeholder="e.g. Lunch at restaurant"' +
                            ' value="' + escHtml(isEdit ? (exp.note || '') : '') + '" />' +
                    '</label>' +

                    // Date
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

        // Badge updates when category changes
        modal.querySelector('#expCat').addEventListener('change', () => updateBadge(modal));

        modal.querySelector('#expClose').addEventListener('click',  () => modal.remove());
        modal.querySelector('#expCancel').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });

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
                if (isEdit) { payload.id = exp.id; await apiFetch('PUT', payload); }
                else        { await apiFetch('POST', payload); }
                modal.remove();
                loadExpenses();
            } catch (err) {
                errEl.textContent = (err && err.message) ? err.message : 'Failed to save. Please try again.';
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

    async function deleteExpense(id) {
        if (!confirm('Delete this expense?')) return;
        try {
            await apiFetch('DELETE', { id });
            loadExpenses();
        } catch (e) {
            alert((e && e.message) ? e.message : 'Failed to delete. Please try again.');
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
