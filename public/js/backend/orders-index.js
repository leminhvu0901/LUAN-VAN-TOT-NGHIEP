/**
 * orders-index.js
 * JS cho trang Admin > Đơn hàng (index)
 * Bao gồm: Cancel Modal, AJAX search/filter, Tab AJAX, Pagination AJAX, Export link sync
 */

// ─── Cancel Modal Logic ────────────────────────────────────────────────────
let currentOrderId = null;
let currentFormId  = null;
let previousStatus = null;

function handleStatusChange(selectElement, orderId, oldStatus) {
    if (selectElement.value === 'cancelled') {
        currentOrderId = orderId;
        currentFormId  = 'form-status-' + orderId;
        previousStatus = oldStatus;
        document.getElementById('cancel-modal').classList.remove('hidden');
    } else {
        selectElement.closest('form').submit();
    }
}

function closeCancelModal() {
    document.getElementById('cancel-modal').classList.add('hidden');
    document.getElementById('cancel-reason-input').value = '';
    if (currentFormId) {
        const form = document.getElementById(currentFormId);
        if (form) form.querySelector('select').value = previousStatus;
    }
    currentOrderId = null;
    currentFormId  = null;
}

function submitCancelReason() {
    const reason = document.getElementById('cancel-reason-input').value.trim();
    if (!reason) {
        alert('Vui lòng nhập lý do hủy đơn!');
        document.getElementById('cancel-reason-input').focus();
        return;
    }
    if (currentFormId && currentOrderId) {
        const form = document.getElementById(currentFormId);
        document.getElementById('reason-' + currentOrderId).value = reason;
        form.submit();
    }
}

// ─── AJAX Live Search & Filter ─────────────────────────────────────────────
let searchTimeout  = null;
let form, tableContainer, loader;

function loadTableData(url = null) {
    if (!url) {
        const formData = new FormData(form);
        const params   = new URLSearchParams(formData);
        url = form.action + '?' + params.toString();
    }

    // Cập nhật URL trên thanh địa chỉ
    window.history.pushState({}, '', url);

    // Đồng bộ href nút Xuất báo cáo
    updateExportLink(url);

    loader.classList.remove('hidden');
    loader.classList.add('flex');

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept'           : 'text/html'
        }
    })
    .then(res  => res.text())
    .then(html => {
        const wrapper   = tableContainer.querySelector('.overflow-x-auto');
        wrapper.innerHTML = html;
        loader.classList.add('hidden');
        loader.classList.remove('flex');
        attachPaginationListeners();
    })
    .catch(err => {
        console.error(err);
        loader.classList.add('hidden');
        loader.classList.remove('flex');
    });
}

function handleLiveSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadTableData(), 500);
}

// ─── Pagination AJAX ────────────────────────────────────────────────────────
function attachPaginationListeners() {
    const wrapper = tableContainer.querySelector('.overflow-x-auto');
    wrapper.querySelectorAll('.ajax-pagination a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            loadTableData(this.href);
        });
    });
}

// ─── Export link sync ──────────────────────────────────────────────────────
function updateExportLink(currentUrl) {
    try {
        const parsed = new URL(currentUrl, window.location.origin);
        parsed.pathname = parsed.pathname.replace(/\/orders(\/?)?$/, '/orders/export');
        if (!parsed.pathname.includes('/export')) {
            parsed.pathname = parsed.pathname.replace(/\/$/, '') + '/export';
        }
        const btn = document.getElementById('export-btn');
        if (btn) btn.href = parsed.toString();
    } catch (e) { /* ignore */ }
}

// ─── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    form           = document.getElementById('search-form');
    tableContainer = document.getElementById('table-container');
    loader         = document.getElementById('table-loader');

    // Input events
    document.getElementById('search-input').addEventListener('input', handleLiveSearch);
    document.getElementById('date-from-input').addEventListener('change', handleLiveSearch);
    document.getElementById('date-to-input').addEventListener('change', handleLiveSearch);

    // Prevent native form submit (Enter key)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        loadTableData();
    });

    // Initial pagination attach
    attachPaginationListeners();

    // Tab AJAX
    const tabLinks = document.querySelectorAll('.custom-scrollbar a');
    tabLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Visual active state
            tabLinks.forEach(t => {
                t.classList.remove('font-semibold', 'text-primary', 'border-primary', 'bg-emerald-50/30');
                t.classList.add('font-medium', 'text-gray-500', 'border-transparent');
            });
            this.classList.remove('font-medium', 'text-gray-500', 'border-transparent');
            this.classList.add('font-semibold', 'text-primary', 'border-primary', 'bg-emerald-50/30');

            // Sync hidden status input
            const url    = new URL(this.href);
            const status = url.searchParams.get('status') || '';
            let statusInput = form.querySelector('input[name="status"]');
            if (!statusInput) {
                statusInput      = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                form.appendChild(statusInput);
            }
            statusInput.value = status;

            loadTableData();
        });
    });

    // Sync export link on page load
    updateExportLink(window.location.href);
});
