let searchTimeout  = null;
let form, tableContainer, loader;

// Tải dữ liệu bảng đơn hàng thông qua AJAX 
function loadTableData(url = null) {
    // Nếu không truyền URL, tự động xây dựng URL từ dữ liệu của form tìm kiếm hiện tại
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

// Xử lý debounce cho tìm kiếm trực tiếp (Live Search)
function handleLiveSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => loadTableData(), 500);
}


// Gắn sự kiện click vào các liên kết phân trang để gọi AJAX thay vì tải lại toàn bộ trang
function attachPaginationListeners() {
    const wrapper = tableContainer.querySelector('.overflow-x-auto');
    wrapper.querySelectorAll('.ajax-pagination a').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            loadTableData(this.href);
        });
    });
}

// Cập nhật đường link tải (export)
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


// Khởi tạo các sự kiện khi DOM đã được tải hoàn tất
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

    // -- Xử lý chuyển Tab bằng AJAX (Lọc đơn hàng theo trạng thái) --
    const tabLinks = document.querySelectorAll('.custom-scrollbar a');
    tabLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            // Ngăn chặn hành động tải lại trang mặc định của thẻ <a>
            e.preventDefault();

            // 1. Cập nhật giao diện (CSS): Gỡ bỏ style "đang chọn" (active) ở tất cả các tab
            tabLinks.forEach(t => {
                t.classList.remove('font-semibold', 'text-primary', 'border-primary', 'bg-emerald-50/30');
                t.classList.add('font-medium', 'text-gray-500', 'border-transparent');
            });
            // Thêm style "đang chọn" (active) vào tab vừa được click
            this.classList.remove('font-medium', 'text-gray-500', 'border-transparent');
            this.classList.add('font-semibold', 'text-primary', 'border-primary', 'bg-emerald-50/30');

            // 2. Đồng bộ trạng thái (status) vào trong form tìm kiếm hiện tại
            const url    = new URL(this.href);
            const status = url.searchParams.get('status') || '';
            let statusInput = form.querySelector('input[name="status"]');
            // Nếu form chưa có input này, tự động tạo mới một thẻ <input type="hidden"> và gắn vào form
            if (!statusInput) {
                statusInput      = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                form.appendChild(statusInput);
            }
            // Gán giá trị trạng thái (status) mới cho input ẩn
            statusInput.value = status;

            // 3. Gọi hàm loadTableData() để gửi request AJAX tải lại dữ liệu bảng kèm theo form (chứa filter + status mới)
            loadTableData();
        });
    });

    // Sync export link on page load
    updateExportLink(window.location.href);
});
