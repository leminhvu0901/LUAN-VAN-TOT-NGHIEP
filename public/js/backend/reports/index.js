document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    const presetSelect = document.getElementById('preset-select');
    const dateFromContainer = document.getElementById('date-from-container');
    const dateToContainer = document.getElementById('date-to-container');
    const reportsLoader = document.getElementById('reports-loader');
    const contentWrapper = document.getElementById('reports-content-wrapper');
    const exportBtn = document.getElementById('export-btn');
    const btnClearFilter = document.getElementById('btn-clear-filter');

    let revenueChart = null;
    let statusChart = null;

    // 1. Khởi tạo Flatpickr cho khoảng ngày tùy chọn
    function initFlatpickr() {
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#date_from', {
                locale: 'vn',
                dateFormat: 'Y-m-d',
                allowInput: false,
                disableMobile: true
            });
            flatpickr('#date_to', {
                locale: 'vn',
                dateFormat: 'Y-m-d',
                allowInput: false,
                disableMobile: true
            });
        }
    }

    initFlatpickr();

    // 1b. Khởi tạo Custom Dropdown thay thế select gốc
    function initCustomPresetDropdown() {
        const dropdownWrapper = document.getElementById('custom-preset-dropdown');
        if (!dropdownWrapper || dropdownWrapper.dataset.dropdownInitialized === 'true') return;
        
        dropdownWrapper.dataset.dropdownInitialized = 'true';
        
        const btn = dropdownWrapper.querySelector('.reports-dropdown-btn');
        const menu = dropdownWrapper.querySelector('.reports-dropdown-menu');
        const items = dropdownWrapper.querySelectorAll('.dropdown-item');
        const arrow = btn.querySelector('.dropdown-arrow');
        const label = btn.querySelector('.selected-label');

        function updateDropdownUI() {
            const currentValue = presetSelect.value;
            let activeText = 'Mốc thời gian';
            
            items.forEach(item => {
                const itemValue = item.getAttribute('data-value');
                const check = item.querySelector('.select-check');
                if (itemValue === currentValue) {
                    item.classList.add('bg-emerald-50', 'text-emerald-600');
                    item.classList.remove('text-gray-700');
                    if (check) check.classList.remove('hidden');
                    activeText = item.querySelector('span').textContent.trim();
                } else {
                    item.classList.remove('bg-emerald-50', 'text-emerald-600');
                    item.classList.add('text-gray-700');
                    if (check) check.classList.add('hidden');
                }
            });
            
            label.textContent = activeText;
        }

        // Đồng bộ UI ban đầu
        updateDropdownUI();

        // Mở / Đóng menu
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = !menu.classList.contains('hidden');
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });

        function openDropdown() {
            menu.classList.remove('hidden');
            arrow.classList.add('rotate-180');
            
            // Tính toán hướng mở (lên trên hoặc xuống dưới)
            const rect = btn.getBoundingClientRect();
            const spaceBelow = window.innerHeight - rect.bottom;
            const menuHeight = 240; // max-height là 240px
            
            if (spaceBelow < menuHeight && rect.top > menuHeight) {
                menu.classList.add('open-up');
            } else {
                menu.classList.remove('open-up');
            }
        }

        function closeDropdown() {
            menu.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }

        // Chọn item
        items.forEach(item => {
            item.addEventListener('click', function (e) {
                e.stopPropagation();
                const newValue = this.getAttribute('data-value');
                presetSelect.value = newValue;
                
                // Đồng bộ UI
                updateDropdownUI();
                
                // Đóng dropdown
                closeDropdown();
                
                // Kích hoạt sự kiện thay đổi của select thật để chạy AJAX/ngày tháng
                presetSelect.dispatchEvent(new Event('change'));
            });
        });

        // Click bên ngoài thì đóng
        document.addEventListener('click', function () {
            closeDropdown();
        });

        // Nhấn ESC thì đóng
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        // Lắng nghe sự kiện change từ select gốc để đồng bộ UI (ví dụ khi nhấn reset bộ lọc)
        presetSelect.addEventListener('change', function() {
            updateDropdownUI();
        });
    }

    initCustomPresetDropdown();

    // 2. Khởi tạo Biểu đồ doanh thu (Line + Bar Chart)
    function initRevenueChart(data) {
        const ctx = document.getElementById('revenue-chart');
        if (!ctx) return;

        if (revenueChart) {
            revenueChart.destroy();
        }

        const isMobile = window.innerWidth < 640;

        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Doanh thu (VNĐ)',
                        data: data.revenue,
                        type: 'line',
                        borderColor: '#10b981', // emerald-500
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: isMobile ? 2 : 3,
                        pointBackgroundColor: '#10b981',
                        pointHoverRadius: 6,
                        tension: 0.35,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Số đơn hàng',
                        data: data.orders,
                        type: 'bar',
                        backgroundColor: 'rgba(59, 130, 246, 0.4)', // blue-500
                        hoverBackgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderRadius: 4,
                        barPercentage: 0.55,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: isMobile ? 'bottom' : 'top',
                        labels: {
                            font: { family: 'Inter', size: isMobile ? 10 : 12, weight: '500' },
                            color: '#475569',
                            boxWidth: isMobile ? 8 : 12,
                            padding: isMobile ? 6 : 10
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 13, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.yAxisID === 'y') {
                                    label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                                } else {
                                    label += context.raw + ' đơn';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: isMobile ? 9 : 11 },
                            color: '#64748b'
                        }
                    },
                    y: {
                        position: 'left',
                        type: 'linear',
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Inter', size: isMobile ? 9 : 11 },
                            color: '#64748b',
                            callback: function (value) {
                                if (value >= 1000000) {
                                    return (value / 1000000) + 'M';
                                }
                                if (value >= 1000) {
                                    return (value / 1000) + 'k';
                                }
                                return value;
                            }
                        }
                    },
                    y1: {
                        position: 'right',
                        type: 'linear',
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: isMobile ? 9 : 11 },
                            color: '#64748b',
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // 3. Khởi tạo Biểu đồ Trạng thái Đơn hàng (Doughnut Chart)
    function initStatusChart(data) {
        const ctx = document.getElementById('status-chart');
        if (!ctx) return;

        if (statusChart) {
            statusChart.destroy();
        }

        const total = data.counts.reduce((a, b) => a + b, 0);
        const isMobile = window.innerWidth < 640;

        statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.counts,
                    backgroundColor: [
                        '#f59e0b', // pending - amber
                        '#3b82f6', // confirmed - blue
                        '#06b6d4', // shipping - cyan
                        '#10b981', // completed - emerald
                        '#ef4444'  // cancelled - red
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: isMobile ? 'bottom' : 'right',
                        labels: {
                            font: { family: 'Inter', size: isMobile ? 9 : 11, weight: '500' },
                            color: '#475569',
                            boxWidth: isMobile ? 8 : 12,
                            padding: isMobile ? 6 : 8
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 12, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const val = context.raw;
                                const pct = total > 0 ? roundTo((val / total) * 100, 1) : 0;
                                return ` ${context.label}: ${val} đơn (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: isMobile ? '55%' : '65%'
            }
        });
    }

    function roundTo(num, decimals) {
        return +(Math.round(num + "e+" + decimals)  + "e-" + decimals);
    }

    // 4. Lắng nghe sự thay đổi của bộ lọc Preset
    if (presetSelect) {
        presetSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                dateFromContainer.classList.remove('hidden');
                dateToContainer.classList.remove('hidden');
            } else {
                dateFromContainer.classList.add('hidden');
                dateToContainer.classList.add('hidden');
                // Tự động submit khi chọn preset khác custom
                submitFilterAjax();
            }
        });
    }

    // 5. Gửi bộ lọc bằng AJAX
    function submitFilterAjax(e = null) {
        if (e) e.preventDefault();

        if (reportsLoader) {
            reportsLoader.classList.remove('hidden');
            reportsLoader.classList.add('flex');
        }

        const formData = new FormData(filterForm);
        const searchParams = new URLSearchParams(formData);
        const url = new URL(window.reportsConfig.indexUrl);
        url.search = searchParams.toString();

        // Đẩy URL lên thanh địa chỉ mà không reload trang
        window.history.pushState({}, '', url);

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(res => res.json())
            .then(data => {
                if (data.html) {
                    contentWrapper.innerHTML = data.html;
                }
                if (data.revenueChartData) {
                    initRevenueChart(data.revenueChartData);
                }
                if (data.orderStatusChartData) {
                    initStatusChart(data.orderStatusChartData);
                }
                initFlatpickr();
                initCustomPresetDropdown();

                // Cập nhật trạng thái hiển thị của nút Xóa lọc
                if (btnClearFilter && presetSelect) {
                    if (presetSelect.value !== '30_days') {
                        btnClearFilter.style.display = 'flex';
                    } else {
                        btnClearFilter.style.display = 'none';
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (window.AdminAlert) {
                    window.AdminAlert.error('Không thể cập nhật báo cáo dữ liệu. Vui lòng thử lại.', 'Lỗi tải dữ liệu');
                }
            })
            .finally(() => {
                if (reportsLoader) {
                    reportsLoader.classList.add('hidden');
                    reportsLoader.classList.remove('flex');
                }
            });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            submitFilterAjax(e);
        });
    }

    // 6. Xử lý xuất báo cáo
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            if (window.AdminAlert) {
                window.AdminAlert.confirm(
                    'Hệ thống đang chuẩn bị dữ liệu báo cáo để in hoặc tải xuống. Bạn có muốn tiếp tục?',
                    function () {
                        // Kích hoạt chức năng in của trình duyệt (Browser Print to PDF) làm phương thức xuất dự phòng
                        window.print();
                    },
                    'Xuất báo cáo thành PDF?'
                );
            } else {
                window.print();
            }
        });
    }

    // 7. Chạy khởi tạo ban đầu cho biểu đồ khi mở trang
    if (window.reportsConfig) {
        initRevenueChart(window.reportsConfig.revenueChartData);
        initStatusChart(window.reportsConfig.orderStatusChartData);
    }

    // 8. Lắng nghe thay đổi kích thước màn hình để vẽ lại chart (đáp ứng responsive legend)
    window.addEventListener('resize', function () {
        if (window.reportsConfig) {
            if (revenueChart && statusChart) {
                // Chỉ chạy vẽ lại để tránh giật lag quá nhiều khi resize liên tục
                // Sử dụng debounce đơn giản nếu cần, nhưng Chart.js vẽ lại khá nhanh
                initRevenueChart(window.reportsConfig.revenueChartData);
                initStatusChart(window.reportsConfig.orderStatusChartData);
            }
        }
    });
});
