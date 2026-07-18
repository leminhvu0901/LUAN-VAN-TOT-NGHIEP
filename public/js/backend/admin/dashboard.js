// Chờ sự kiện DOMContentLoaded để đảm bảo giao diện HTML đã tải xong trước khi chạy JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Biến lưu trữ đối tượng biểu đồ hiện tại (được khởi tạo bằng null)
    let operationalChart = null;

    /**
     * Hàm khởi tạo và vẽ biểu đồ hoạt động
     * @param {Object} data - Dữ liệu truyền từ backend sang (gồm nhãn, doanh thu và đơn hàng)
     */
    function initOperationalChart(data) {
        // Lấy thẻ canvas HTML phục vụ việc vẽ biểu đồ Chart.js
        const ctx = document.getElementById('operational-chart');
        if (!ctx) return; // Nếu không tìm thấy phần tử canvas, thoát khỏi hàm

        // Nếu biểu đồ đã tồn tại từ trước, gọi hàm destroy() để hủy biểu đồ cũ
        // Điều này giúp tránh lỗi đè lặp dữ liệu hoặc hiển thị nhấp nháy khi cập nhật dữ liệu mới
        if (operationalChart) {
            operationalChart.destroy();
        }

        // Tạo instance biểu đồ mới thông qua Chart.js
        operationalChart = new Chart(ctx, {
            type: 'line', // Kiểu biểu đồ mặc định chính là đường (line chart)
            data: {
                labels: data.labels, // Các nhãn trên trục hoành X (ví dụ: ngày, tháng)
                datasets: [
                    {
                        // Bộ dữ liệu 1: Doanh thu hoàn thành (Đồ thị dạng đường - Line)
                        label: 'Doanh thu hoàn thành (VNĐ)',
                        data: data.revenue,
                        borderColor: '#10b981', // Màu đường viền (emerald-500)
                        backgroundColor: 'rgba(16, 185, 129, 0.08)', // Màu nền mờ dưới đường vẽ
                        borderWidth: 3, // Độ dày của đường vẽ
                        pointBackgroundColor: '#10b981', // Màu của các điểm mốc trên đường
                        pointHoverRadius: 6, // Kích thước điểm mốc khi rê chuột (hover) vào
                        tension: 0.3, // Độ bo cong của đường (0 là đường thẳng gấp khúc)
                        fill: true, // Cho phép tô màu vùng phía dưới đường vẽ
                        yAxisID: 'y' // Áp dụng thang đo của trục tung bên trái (trục y)
                    },
                    {
                        // Bộ dữ liệu 2: Số đơn đặt hàng (Đồ thị dạng cột - Bar)
                        label: 'Số đơn đặt hàng',
                        data: data.orders,
                        type: 'bar', // Định nghĩa kiểu riêng cho bộ dữ liệu này là dạng cột (Bar chart)
                        backgroundColor: 'rgba(59, 130, 246, 0.35)', // Màu nền của cột (màu xanh blue-500 mờ)
                        hoverBackgroundColor: 'rgba(59, 130, 246, 0.55)', // Màu nền của cột khi rê chuột vào
                        borderRadius: 5, // Độ bo góc cho đầu cột
                        barPercentage: 0.5, // Chiều rộng tương đối của cột so với khoảng cách cột
                        yAxisID: 'y1' // Áp dụng thang đo của trục tung bên phải (trục y1)
                    }
                ]
            },
            options: {
                responsive: true, // Tự động co giãn theo kích thước màn hình
                maintainAspectRatio: false, // Bỏ qua tỉ lệ khung hình cố định để dễ căn chỉnh chiều cao qua CSS
                plugins: {
                    // Cấu hình ghi chú/chú thích của các bộ dữ liệu
                    legend: {
                        position: 'top', // Vị trí hiển thị ở trên cùng biểu đồ
                        labels: {
                            font: { family: 'Inter', size: 12, weight: '500' }, // Cỡ chữ và font chữ
                            color: '#475569' // Màu chữ của chú thích (slate-600)
                        }
                    },
                    // Cấu hình khung thông tin chi tiết khi rê chuột vào biểu đồ (Tooltip)
                    tooltip: {
                        backgroundColor: '#0f172a', // Màu nền tooltip đen đậm (slate-900)
                        titleFont: { family: 'Inter', size: 13, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            // Hàm tuỳ biến hiển thị nội dung giá trị trong Tooltip
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                // Định dạng hiển thị tiền tệ VNĐ cho trục y (doanh thu)
                                if (context.dataset.yAxisID === 'y') {
                                    label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                                } else {
                                    // Định dạng thêm chữ "đơn" cho trục y1 (đơn hàng)
                                    label += context.raw + ' đơn';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    // Cấu hình cho trục hoành X
                    x: {
                        grid: { display: false }, // Ẩn đường lưới dọc
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b' // Màu chữ các nhãn trên trục X
                        }
                    },
                    // Cấu hình cho trục tung bên trái Y (dành cho Doanh thu)
                    y: {
                        position: 'left', // Nằm bên trái
                        type: 'linear',
                        grid: { color: '#f1f5f9' }, // Đường lưới ngang mờ (slate-100)
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b',
                            // Hàm định dạng rút gọn giá trị tiền tệ trên trục tung
                            // Ví dụ: 1.000.000 -> 1M, 5.000 -> 5k để tránh trục tung bị quá dài
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
                    // Cấu hình cho trục tung bên phải Y1 (dành cho Đơn hàng)
                    y1: {
                        position: 'right', // Nằm bên phải
                        type: 'linear',
                        grid: { display: false }, // Không hiện đường lưới ngang để tránh rối mắt
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b',
                            stepSize: 1, // Khoảng cách tăng tiến là 1 đơn vị
                            precision: 0 // Đảm bảo không hiển thị số thập phân cho đơn hàng
                        }
                    }
                }
            }
        });
    }

    // Kiểm tra cấu hình và dữ liệu từ backend truyền sang để khởi chạy vẽ biểu đồ
    // (Được đặt trong biến toàn cục window.dashboardConfig được render từ view Laravel Blade)
    if (window.dashboardConfig && window.dashboardConfig.chartData) {
        initOperationalChart(window.dashboardConfig.chartData);
    }
});

