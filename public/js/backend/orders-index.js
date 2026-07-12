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

    loader.classList.remove('hidden');
    loader.classList.add('flex');

    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept'           : 'application/json'
        }   
    })
    .then(res  => {
        const contentType = res.headers.get("content-type");
        if (contentType && contentType.indexOf("application/json") !== -1) {
            return res.json();
        } else {
            return res.text().then(text => { return { table_html: text }; });
        }
    })
    .then(data => {
        if (data.table_html) {
            const wrapper   = tableContainer.querySelector('.overflow-x-auto');
            wrapper.innerHTML = data.table_html;
            attachPaginationListeners();
        }
        if (data.stats_html) {
            const statsContainer = document.getElementById('stats-container');
            if (statsContainer) statsContainer.innerHTML = data.stats_html;
        }
        loader.classList.add('hidden');
        loader.classList.remove('flex');
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

// Cập nhật đường link tải (export) - function removed as export button is removed

// Khởi tạo các sự kiện khi DOM đã được tải hoàn tất
document.addEventListener('DOMContentLoaded', function () {
    form           = document.getElementById('search-form');
    tableContainer = document.getElementById('table-container');
    loader         = document.getElementById('table-loader');

    // Input events
    document.getElementById('search-input').addEventListener('input', handleLiveSearch);
    document.getElementById('date-from-input').addEventListener('change', handleLiveSearch);
    document.getElementById('date-to-input').addEventListener('change', handleLiveSearch);
    
    // Select events for auto-filtering
    const statusSelect = form.querySelector('select[name="status"]');
    if (statusSelect) statusSelect.addEventListener('change', handleLiveSearch);
    
    const sortSelect = form.querySelector('select[name="sort"]');
    if (sortSelect) sortSelect.addEventListener('change', handleLiveSearch);

    // Prevent native form submit (Enter key)
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        loadTableData();
    });

    // Initial pagination attach
    attachPaginationListeners();
});
