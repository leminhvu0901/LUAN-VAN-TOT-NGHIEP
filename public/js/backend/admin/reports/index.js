document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filter-form');
    const presetSelect = document.getElementById('preset-select');
    const dateFromContainer = document.getElementById('date-from-container');
    const dateToContainer = document.getElementById('date-to-container');
    const exportBtn = document.getElementById('export-btn');

    let revenueChart = null;
    let statusChart = null;
    let channelChart = null;
    let channelOrdersChart = null;

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

    // 3b. Khởi tạo Biểu đồ Doanh thu theo kênh bán: Tại quầy / Đặt online (Doughnut Chart)
    function initChannelChart(data) {
        const ctx = document.getElementById('channel-chart');
        if (!ctx) return;

        if (channelChart) {
            channelChart.destroy();
        }

        const total = data.amounts.reduce((a, b) => a + b, 0);
        const isMobile = window.innerWidth < 640;

        channelChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.amounts,
                    backgroundColor: [
                        '#f97316', // Tại quầy - orange (khớp màu badge "Tại quầy" ở trang Khuyến mãi)
                        '#0ea5e9'  // Đặt online - sky (khớp màu badge "Giao hàng" ở trang Khuyến mãi)
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
                                const formatted = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
                                return ` ${context.label}: ${formatted} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: isMobile ? '55%' : '65%'
            }
        });
    }

    // 3c. Khởi tạo Biểu đồ Số lượng đơn theo kênh bán: Tại quầy / Đặt online (Doughnut Chart)
    function initChannelOrdersChart(data) {
        const ctx = document.getElementById('channel-orders-chart');
        if (!ctx) return;

        if (channelOrdersChart) {
            channelOrdersChart.destroy();
        }

        const total = data.counts.reduce((a, b) => a + b, 0);
        const isMobile = window.innerWidth < 640;

        channelOrdersChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.counts,
                    // Cùng màu với biểu đồ "Doanh thu theo kênh bán" — giữ nhất quán màu theo kênh
                    // (Tại quầy = cam, Đặt online = sky) xuyên suốt các biểu đồ trên trang.
                    backgroundColor: [
                        '#f97316', // Tại quầy - orange
                        '#0ea5e9'  // Đặt online - sky
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

    // 4. Bộ lọc Preset: đổi sang khoảng tùy chọn thì hiện thêm 2 ô ngày; các preset khác tự submit
    // form (điều hướng thật, tải lại trang) để áp dụng ngay — không cần bấm nút "Áp dụng".
    if (presetSelect) {
        presetSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                dateFromContainer.classList.remove('hidden');
                dateToContainer.classList.remove('hidden');
            } else {
                dateFromContainer.classList.add('hidden');
                dateToContainer.classList.add('hidden');
                filterForm.submit();
            }
        });
    }

    // 6. Xử lý xuất báo cáo ra file Excel (.xlsx)
    if (exportBtn) {
        // Lấy bộ lọc từ chính form thay vì đọc URL: form luôn là nguồn chuẩn, kể cả khi người dùng
        // vừa đổi giá trị nhưng chưa bấm "Áp dụng".
        function buildExportUrl() {
            const url = new URL(window.reportsConfig.exportUrl, window.location.origin);
            if (filterForm) {
                url.search = new URLSearchParams(new FormData(filterForm)).toString();
            }
            return url.toString();
        }

        function downloadReport() {
            // Điều hướng cả tab: server trả Content-Disposition: attachment nên trình duyệt tải file
            // về mà KHÔNG rời khỏi trang báo cáo đang xem.
            window.location.href = buildExportUrl();
        }

        exportBtn.addEventListener('click', function () {
            if (window.AdminAlert) {
                window.AdminAlert.confirm(
                    'Hệ thống sẽ tạo file Excel (.xlsx) theo đúng khoảng thời gian đang lọc. Bạn có muốn tải xuống?',
                    downloadReport,
                    'Xuất báo cáo ra Excel?'
                );
            } else {
                downloadReport();
            }
        });
    }

    // 7. Chạy khởi tạo ban đầu cho biểu đồ khi mở trang
    if (window.reportsConfig) {
        initRevenueChart(window.reportsConfig.revenueChartData);
        initStatusChart(window.reportsConfig.orderStatusChartData);
        initChannelChart(window.reportsConfig.channelRevenueChartData);
        initChannelOrdersChart(window.reportsConfig.channelOrdersChartData);
    }

    // 8. Lắng nghe thay đổi kích thước màn hình để vẽ lại chart (đáp ứng responsive legend)
    window.addEventListener('resize', function () {
        if (window.reportsConfig) {
            if (revenueChart && statusChart && channelChart && channelOrdersChart) {
                // Chỉ chạy vẽ lại để tránh giật lag quá nhiều khi resize liên tục
                // Sử dụng debounce đơn giản nếu cần, nhưng Chart.js vẽ lại khá nhanh
                initRevenueChart(window.reportsConfig.revenueChartData);
                initStatusChart(window.reportsConfig.orderStatusChartData);
                initChannelChart(window.reportsConfig.channelRevenueChartData);
                initChannelOrdersChart(window.reportsConfig.channelOrdersChartData);
            }
        }
    });
});
