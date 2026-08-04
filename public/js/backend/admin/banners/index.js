/**
 * index.js - Xử lý danh sách banner ở trang quản trị (Admin Banners Control)
 * Lọc/tìm kiếm/phân trang nay là form GET/link thường (tải lại trang), không còn AJAX.
 * Các chức năng còn lại (sẽ chuyển tiếp ở giai đoạn sau):
 * - Xóa đơn lẻ hoặc xóa hàng loạt banner có hộp thoại xác nhận.
 * - Bật/Tắt trạng thái hoạt động trực tiếp ngoài danh sách bằng AJAX.
 * - fetchBanners() vẫn còn dùng nội bộ để nạp lại bảng sau khi xóa/toggle (chưa chuyển đổi).
 */
document.addEventListener('DOMContentLoaded', function () {
    // === PHẦN TỬ DOM ===
    const filterForm = document.getElementById('filter-form'); // Form bộ lọc đầu trang
    const tableContainer = document.getElementById('table-container'); // Vùng chứa bảng dữ liệu
    const btnClearFilter = document.getElementById('btn-clear-filter'); // Nút hủy toàn bộ bộ lọc

    /**
     * Hàm gọi AJAX lấy danh sách banner từ server dựa trên các bộ lọc
     * @param {string|null} urlStr - URL của trang cần tải (nếu chuyển trang), null để lấy theo form lọc hiện tại
     * @param {boolean} preserveScroll - true để giữ nguyên vị trí cuộn hiện tại của màn hình
     */
    function fetchBanners(urlStr = null, preserveScroll = false) {
        let url;
        if (urlStr) {
            url = new URL(urlStr);
        } else {
            url = new URL(filterForm.action);
            const formData = new FormData(filterForm);
            const searchParams = new URLSearchParams(formData);
            url.search = searchParams.toString();
        }

        // Cập nhật thanh địa chỉ URL của trình duyệt
        window.history.pushState({}, '', url);

        // Lấy container cuộn thực tế của layout (phân vùng #main-content-area hoặc thẻ html)
        const scrollContainer = document.getElementById('main-content-area') || document.documentElement;
        const tableWrapper = document.getElementById("banners-table-wrapper");

        // Lưu trữ vị trí cuộn chuột hiện tại
        const currentScrollTop = scrollContainer ? scrollContainer.scrollTop : window.scrollY;

        // Xác định xem có phải đang chuyển trang hay không
        const isPaging = (urlStr !== null);
        const shouldPreserve = isPaging || preserveScroll;
        const scrollFromBottom = (isPaging && tableWrapper && scrollContainer && scrollContainer.scrollTop > 100)
            ? (tableWrapper.scrollHeight - scrollContainer.scrollTop)
            : 0;

        // Đặt chiều cao tối thiểu tạm thời cho bảng để chống hiện tượng giật giật màn hình khi tải trang (Layout Shift)
        if (tableWrapper) {
            tableWrapper.style.minHeight = tableWrapper.offsetHeight + 'px';
        }

        // Hiển thị vòng xoay loading
        const loader = document.getElementById('table-loader');
        if (loader) {
            loader.classList.remove('hidden');
            loader.classList.add('flex');
        } else {
            tableContainer.style.opacity = '0.5';
            tableContainer.style.pointerEvents = 'none';
        }

        // Gửi request GET AJAX lên server nhận phản hồi JSON chứa HTML render sẵn
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                // Cập nhật các thẻ số liệu thống kê ở đầu trang quản lý banner
                if (data.total !== undefined) {
                    const totalStat = document.getElementById('total-banners-stat');
                    if (totalStat) totalStat.innerText = data.total;
                }
                if (data.active !== undefined) {
                    const activeStat = document.getElementById('active-banners-stat');
                    if (activeStat) activeStat.innerText = data.active;
                }
                if (data.inactive !== undefined) {
                    const inactiveStat = document.getElementById('inactive-banners-stat');
                    if (inactiveStat) inactiveStat.innerText = data.inactive;
                }
                if (data.upcoming !== undefined) {
                    const upcomingStat = document.getElementById('upcoming-banners-stat');
                    if (upcomingStat) upcomingStat.innerText = data.upcoming;
                }
                if (data.expired !== undefined) {
                    const expiredStat = document.getElementById('expired-banners-stat');
                    if (expiredStat) expiredStat.innerText = data.expired;
                }

                // Ghi đè HTML bảng dữ liệu mới nhận được vào view
                if (data.html) {
                    const tableWrapper = document.getElementById("banners-table-wrapper");
                    if (tableWrapper) {
                        tableWrapper.innerHTML = data.html;
                    } else {
                        tableContainer.innerHTML = data.html;
                    }
                    document.dispatchEvent(new Event("tableDataLoaded"));
                }

                // Hiện hoặc Ẩn nút Xóa lọc tùy theo có tham số lọc nào khác mặc định đang hoạt động
                if (btnClearFilter) {
                    const hasFilters = [...new URLSearchParams(url.search)].some(([key, val]) =>
                        (key === 'search' && val !== '') ||
                        (key === 'status' && val !== 'all') ||
                        (key === 'sort' && val !== 'order_asc')
                    );
                    btnClearFilter.style.display = hasFilters ? 'flex' : 'none';
                }

                // Phục hồi lại vị trí cuộn của người dùng sau khi nạp xong dữ liệu
                if (shouldPreserve) {
                    if (isPaging && scrollFromBottom > 0 && tableWrapper && scrollContainer) {
                        scrollContainer.scrollTop = tableWrapper.scrollHeight - scrollFromBottom;
                    } else if (scrollContainer) {
                        scrollContainer.scrollTop = currentScrollTop;
                    }
                } else {
                    if (scrollContainer) {
                        scrollContainer.scrollTop = 0;
                    } else {
                        window.scrollTo(0, 0);
                    }
                }
            })
            .catch(error => console.error('Lỗi khi tải dữ liệu banner:', error))
            .finally(() => {
                // Ẩn vòng xoay loading
                if (loader) {
                    loader.classList.add('hidden');
                    loader.classList.remove('flex');
                }
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';

                // Giải phóng thuộc tính chiều cao tối thiểu tạm thời của bảng
                if (tableWrapper) {
                    tableWrapper.style.minHeight = '';
                }

                // Đảm bảo cập nhật vị trí cuộn một lần nữa sau khi đã gỡ min-height
                if (shouldPreserve) {
                    if (isPaging && scrollFromBottom > 0 && tableWrapper && scrollContainer) {
                        scrollContainer.scrollTop = tableWrapper.scrollHeight - scrollFromBottom;
                    } else if (scrollContainer) {
                        scrollContainer.scrollTop = currentScrollTop;
                    }
                }
            });
    }

    // Lọc/tìm kiếm/phân trang: form GET và link "Xóa lọc" nay submit/điều hướng bình thường
    // (không còn JS chặn submit để gọi AJAX nữa) — xem nút "Lọc" trong filter-form.

    // Ủy quyền sự kiện click trên toàn bộ bảng dữ liệu
    if (tableContainer) {
        tableContainer.addEventListener('click', function (e) {
            // 1. Nhấn nút xóa một banner đơn lẻ
            const deleteBtn = e.target.closest('.delete-banner-btn');
            if (deleteBtn) {
                e.preventDefault();
                const id = deleteBtn.dataset.id;
                const url = deleteBtn.dataset.url;

                // Trích xuất tiêu đề banner để hiện trong modal xác nhận
                let title = 'banner này';
                const row = deleteBtn.closest('.select-row-tr');
                if (row) {
                    const titleEl = row.querySelector('.font-bold');
                    if (titleEl) title = titleEl.innerText.trim();
                }

                deleteBannerAjax(id, title, url);
                return;
            }

            // 2. Nhấn nút bật/tắt hiển thị trạng thái hoạt động nhanh của banner
            const toggleBtn = e.target.closest('.toggle-status-btn');
            if (toggleBtn) {
                e.preventDefault();
                const url = toggleBtn.dataset.url;
                toggleBannerStatusAjax(toggleBtn, url);
                return;
            }
        });
    }

    window.fetchBanners = fetchBanners;
});

/**
 * Gửi request DELETE AJAX xóa một banner và tạo hiệu ứng trượt mờ biến mất
 */
function deleteBannerAjax(id, title, url) {
    if (!window.AdminAlert) return;

    window.AdminAlert.confirm(
        `Bạn có chắc chắn muốn xóa banner "${title}" không? Hành động này không thể hoàn tác.`,
        function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value
                || '';

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const rows = document.querySelectorAll(`#banner-row-${id}`);

                        if (rows.length > 0) {
                            rows.forEach(row => {
                                // Tạo hiệu ứng CSS trượt ngang và ẩn mờ dần khi xóa
                                row.style.transition = 'all 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'translateX(20px)';
                                setTimeout(() => row.remove(), 300);
                            });

                            if (window.resetBannerSelection) window.resetBannerSelection();
                            window.AdminAlert.success(data.message || 'Xóa banner thành công!', 'Đã xóa!');
                        } else {
                            if (window.resetBannerSelection) window.resetBannerSelection();
                            if (typeof window.fetchBanners === 'function') {
                                window.fetchBanners(null, true);
                            }
                        }
                    } else {
                        window.AdminAlert.error(data.message, 'Lỗi');
                    }
                })
                .catch(() => {
                    window.AdminAlert.error('Có lỗi xảy ra khi xóa banner. Vui lòng thử lại.', 'Lỗi kết nối');
                });
        },
        'Xóa banner?'
    );
}

/**
 * Gửi request POST AJAX thay đổi nhanh trạng thái hoạt động (is_active) của banner
 */
function toggleBannerStatusAjax(btn, url) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const currentScrollY = window.scrollY;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (window.AdminAlert) {
                    window.AdminAlert.success(data.message, 'Thành công');
                }

                // Cập nhật lại bảng dữ liệu AJAX để làm mới badge trạng thái và thống kê
                if (typeof window.fetchBanners === 'function') {
                    window.fetchBanners(null, true);
                }
            } else {
                if (window.AdminAlert) {
                    window.AdminAlert.error(data.message || 'Cập nhật trạng thái thất bại!');
                }
            }
        })
        .catch(() => {
            if (window.AdminAlert) {
                window.AdminAlert.error('Có lỗi xảy ra khi kết nối máy chủ.', 'Lỗi');
            }
        });
}

// ============================================================
// XỬ LÝ CHỌN NHIỀU BANNER ĐỂ THỰC THI XÓA HÀNG LOẠT (BULK ACTIONS)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const tableContainer = document.getElementById('table-container');
    const selectedCountSpan = document.getElementById('selected-count');

    window.isGlobalSelectAllBanners = false;
    window.selectedBannerIds = new Set(); // Set chứa danh sách ID được chọn

    /**
     * Cập nhật hiển thị số lượng và ẩn/hiện nút "Xóa nhiều"
     */
    function updateBulkDeleteButton() {
        let countText = window.selectedBannerIds.size;

        if (window.isGlobalSelectAllBanners) {
            const totalInput = document.getElementById('total-banners-count');
            if (totalInput) countText = totalInput.value;
        }

        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        const bulkDeselectBtn = document.getElementById('bulk-deselect-btn');

        if (countText > 0) {
            if (selectedCountSpan) selectedCountSpan.textContent = countText;
            if (bulkDeleteBtn) {
                bulkDeleteBtn.classList.remove('hidden');
                bulkDeleteBtn.classList.add('flex');
            }
            if (bulkDeselectBtn) {
                bulkDeselectBtn.classList.remove('hidden');
                bulkDeselectBtn.classList.add('flex');
            }
        } else {
            if (bulkDeleteBtn) {
                bulkDeleteBtn.classList.add('hidden');
                bulkDeleteBtn.classList.remove('flex');
            }
            if (bulkDeselectBtn) {
                bulkDeselectBtn.classList.add('hidden');
                bulkDeselectBtn.classList.remove('flex');
            }
        }
    }

    /**
     * Đặt lại trạng thái lựa chọn hàng loạt về ban đầu
     */
    window.resetBannerSelection = function () {
        window.isGlobalSelectAllBanners = false;
        if (window.selectedBannerIds) window.selectedBannerIds.clear();
        document.querySelectorAll('.js-select-all, .row-checkbox').forEach(el => el.checked = false);
        updateBulkDeleteButton();
    };

    window.deselectAllBanners = function () {
        window.resetBannerSelection();
    };

    if (tableContainer) {
        tableContainer.addEventListener('change', function (e) {
            // 1. Nhấn nút Checkbox chọn tất cả ở đầu bảng
            if (e.target.classList.contains('js-select-all')) {
                const isChecked = e.target.checked;
                window.isGlobalSelectAllBanners = isChecked;

                // Đồng bộ checkbox tất cả ở cả header bảng desktop và mobile
                document.querySelectorAll('.js-select-all').forEach(el => el.checked = isChecked);

                if (!isChecked) {
                    window.selectedBannerIds.clear();
                }
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                    if (isChecked) window.selectedBannerIds.add(cb.value);
                });
                updateBulkDeleteButton();
            } 
            // 2. Nhấn chọn/bỏ chọn checkbox của từng hàng banner đơn lẻ
            else if (e.target.classList.contains('row-checkbox')) {
                if (e.target.checked) {
                    window.selectedBannerIds.add(e.target.value);
                } else {
                    window.selectedBannerIds.delete(e.target.value);
                    window.isGlobalSelectAllBanners = false;
                }

                const allCheckboxes = document.querySelectorAll('.row-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
                const allChecked = allCheckboxes.length > 0 && checkedCheckboxes.length === allCheckboxes.length;
                document.querySelectorAll('.js-select-all').forEach(el => el.checked = allChecked);

                updateBulkDeleteButton();
            }
        });
    }

    // Đồng bộ lại trạng thái tích chọn checkbox sau khi nạp bảng bằng AJAX
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList') {
                if (window.isGlobalSelectAllBanners) {
                    document.querySelectorAll('.js-select-all').forEach(el => el.checked = true);
                    document.querySelectorAll('.row-checkbox').forEach(cb => {
                        cb.checked = true;
                        window.selectedBannerIds.add(cb.value);
                    });
                } else {
                    document.querySelectorAll('.row-checkbox').forEach(cb => {
                        if (window.selectedBannerIds.has(cb.value)) {
                            cb.checked = true;
                        }
                    });
                    const allCheckboxes = document.querySelectorAll('.row-checkbox');
                    if (allCheckboxes.length > 0) {
                        const allChecked = document.querySelectorAll('.row-checkbox:checked').length === allCheckboxes.length;
                        document.querySelectorAll('.js-select-all').forEach(el => el.checked = allChecked);
                    }
                }
                updateBulkDeleteButton();
            }
        });
    });

    if (tableContainer) {
        observer.observe(tableContainer, { childList: true, subtree: true });
    }

    // Hiển thị hộp thoại xác nhận trước khi thực hiện xóa nhiều
    window.submitBulkDeleteBanners = function () {
        if (window.selectedBannerIds.size === 0) return;

        let titleText = 'Xác nhận xóa nhiều?';
        let messageText = `Bạn chuẩn bị xóa ${window.selectedBannerIds.size} banner đã chọn.`;

        if (window.isGlobalSelectAllBanners) {
            titleText = 'Xác nhận xóa tất cả?';
            let countText = window.selectedBannerIds.size;
            const totalInput = document.getElementById('total-banners-count');
            if (totalInput) countText = totalInput.value;
            messageText = `Bạn chuẩn bị xóa TẤT CẢ ${countText} banner trùng khớp với bộ lọc.`;
        }

        window.AdminAlert.confirm(messageText, function () {
            executeBulkDelete(window.isGlobalSelectAllBanners);
        }, titleText);
    };

    const submitBtn = document.getElementById('bulk-delete-btn');
    if (submitBtn) {
        submitBtn.addEventListener('click', window.submitBulkDeleteBanners);
    }

    /**
     * Gửi request POST AJAX thực thi xóa hàng loạt banner
     * @param {boolean} isSelectAll - true để xóa toàn bộ bản ghi khớp bộ lọc ở mọi trang
     */
    function executeBulkDelete(isSelectAll) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value || '';

        const form = document.getElementById('bulk-delete-form');
        const deleteUrl = window.bannersRoutes.bulkDelete;

        let data = {};
        if (isSelectAll) {
            data.delete_all_pages = '1';
            const urlParams = new URLSearchParams(window.location.search);
            for (const [key, value] of urlParams.entries()) {
                if (key !== 'page' && value !== '') {
                    data[key] = value;
                }
            }
        } else {
            data.banner_ids = Array.from(window.selectedBannerIds);
        }

        fetch(deleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    window.AdminAlert.success(resData.message, 'Đã xóa!');
                    window.resetBannerSelection();
                    if (typeof window.fetchBanners === 'function') {
                        window.fetchBanners(null, true);
                    }
                } else {
                    window.AdminAlert.error(resData.message || 'Có lỗi xảy ra.');
                }
            })
            .catch(err => {
                console.error(err);
                window.AdminAlert.error('Lỗi kết nối khi xóa hàng loạt.');
            });
    }
});
