/**
 * Xử lý danh sách banner (Tìm kiếm, Lọc, Phân trang, Xóa, Bật/Tắt trạng thái) bằng AJAX
 */
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    const tableContainer = document.getElementById('table-container');
    const btnClearFilter = document.getElementById('btn-clear-filter');

    // Hàm gọi AJAX lấy danh sách banner
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

        // Cập nhật thanh địa chỉ trình duyệt
        window.history.pushState({}, '', url);

        // Lưu vị trí cuộn hiện tại (hỗ trợ cả main-content-area và window)
        const scrollContainer = document.getElementById('main-content-area') || document.documentElement;
        const tableWrapper = document.getElementById("banners-table-wrapper");

        // Lưu vị trí cuộn tuyệt đối để dùng khi xoá hoặc cập nhật trạng thái
        const currentScrollTop = scrollContainer ? scrollContainer.scrollTop : window.scrollY;

        // Chỉ giữ cố định vị trí nút ở đáy nếu chuyển trang (urlStr !== null) và đang cuộn xuống dưới
        const isPaging = (urlStr !== null);
        const shouldPreserve = isPaging || preserveScroll;
        const scrollFromBottom = (isPaging && tableWrapper && scrollContainer && scrollContainer.scrollTop > 100)
            ? (tableWrapper.scrollHeight - scrollContainer.scrollTop)
            : 0;

        // Giữ nguyên chiều cao hiện tại để tránh giật giao diện (layout shift)
        if (tableWrapper) {
            tableWrapper.style.minHeight = tableWrapper.offsetHeight + 'px';
        }

        const loader = document.getElementById('table-loader');
        if (loader) {
            loader.classList.remove('hidden');
            loader.classList.add('flex');
        } else {
            tableContainer.style.opacity = '0.5';
            tableContainer.style.pointerEvents = 'none';
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                // Cập nhật thống kê
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

                // Cập nhật nội dung bảng/card
                if (data.html) {
                    const tableWrapper = document.getElementById("banners-table-wrapper");
                    if (tableWrapper) {
                        tableWrapper.innerHTML = data.html;
                    } else {
                        tableContainer.innerHTML = data.html;
                    }
                    document.dispatchEvent(new Event("tableDataLoaded"));
                }

                // Hiện / Ẩn nút Xóa lọc
                if (btnClearFilter) {
                    const hasFilters = [...new URLSearchParams(url.search)].some(([key, val]) =>
                        (key === 'search' && val !== '') ||
                        (key === 'status' && val !== 'all') ||
                        (key === 'sort' && val !== 'order_asc')
                    );
                    btnClearFilter.style.display = hasFilters ? 'flex' : 'none';
                }

                // Phục hồi vị trí cuộn
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
                if (loader) {
                    loader.classList.add('hidden');
                    loader.classList.remove('flex');
                }
                tableContainer.style.opacity = '1';
                tableContainer.style.pointerEvents = 'auto';

                // Giải phóng min-height sau khi hoàn tất tải dữ liệu
                if (tableWrapper) {
                    tableWrapper.style.minHeight = '';
                }

                // Cập nhật lại vị trí cuộn sau khi gỡ min-height để tránh giật
                if (shouldPreserve) {
                    if (isPaging && scrollFromBottom > 0 && tableWrapper && scrollContainer) {
                        scrollContainer.scrollTop = tableWrapper.scrollHeight - scrollFromBottom;
                    } else if (scrollContainer) {
                        scrollContainer.scrollTop = currentScrollTop;
                    }
                }
            });
    }

    // Lắng nghe thay đổi dropdown lọc
    if (filterForm) {
        filterForm.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => fetchBanners());
        });

        // Tìm kiếm với debounce
        let timeout = null;
        const searchInput = filterForm.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(timeout);
                timeout = setTimeout(() => fetchBanners(), 400);
            });
        }
    }

    // Nút Xóa lọc
    if (btnClearFilter) {
        btnClearFilter.addEventListener('click', function (e) {
            e.preventDefault();
            filterForm.reset();
            filterForm.querySelectorAll('select').forEach(s => {
                if (s.name === 'sort') s.value = 'order_asc';
                else s.value = 'all';
            });
            const searchInput = filterForm.querySelector('input[name="search"]');
            if (searchInput) searchInput.value = '';
            fetchBanners();
        });
    }

    // Phân trang bằng AJAX & Các sự kiện trong bảng (sử dụng Event Delegation)
    if (tableContainer) {
        tableContainer.addEventListener('click', function (e) {
            // Phân trang
            const pageLink = e.target.closest('.ajax-pagination a');
            if (pageLink) {
                e.preventDefault();
                fetchBanners(pageLink.href);
                return;
            }

            // Nút xóa đơn lẻ
            const deleteBtn = e.target.closest('.delete-banner-btn');
            if (deleteBtn) {
                e.preventDefault();
                const id = deleteBtn.dataset.id;
                const url = deleteBtn.dataset.url;

                // Tìm tiêu đề banner
                let title = 'banner này';
                const row = deleteBtn.closest('.select-row-tr');
                if (row) {
                    const titleEl = row.querySelector('.font-bold');
                    if (titleEl) title = titleEl.innerText.trim();
                }

                deleteBannerAjax(id, title, url);
                return;
            }

            // Nút bật/tắt hiển thị trạng thái bằng AJAX
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
 * Bật/tắt hiển thị trạng thái banner bằng AJAX
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

                // Cập nhật lại toàn bộ bảng để lấy thông tin trạng thái / màu sắc badge chuẩn xác nhất
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
// Xử lý Chọn hàng loạt (Bulk Actions) cho Banner
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const tableContainer = document.getElementById('table-container');
    const selectedCountSpan = document.getElementById('selected-count');

    window.isGlobalSelectAllBanners = false;
    window.selectedBannerIds = new Set();

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
            if (e.target.classList.contains('js-select-all')) {
                const isChecked = e.target.checked;
                window.isGlobalSelectAllBanners = isChecked;

                // Đồng bộ checkbox tất cả (bao gồm cả mobile và desktop check-all)
                document.querySelectorAll('.js-select-all').forEach(el => el.checked = isChecked);

                if (!isChecked) {
                    window.selectedBannerIds.clear();
                }
                document.querySelectorAll('.row-checkbox').forEach(cb => {
                    cb.checked = isChecked;
                    if (isChecked) window.selectedBannerIds.add(cb.value);
                });
                updateBulkDeleteButton();
            } else if (e.target.classList.contains('row-checkbox')) {
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

    // Khôi phục trạng thái checkbox sau khi fetch AJAX bằng MutationObserver
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

    // Hành động xóa nhiều
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
