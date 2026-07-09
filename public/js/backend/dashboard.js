window.addEventListener('DOMContentLoaded', () => {
    // 1. LẤY CÁC THẺ HTML CẦN THIẾT TRÊN GIAO DIỆN
    const chartContainer = document.getElementById('revenue-chart');
    if (!chartContainer) return; 

    // Lấy chuỗi dữ liệu JSON ẩn trong HTML và chuyển lại thành Object (Mảng) của Javascript
    const chartData = JSON.parse(chartContainer.getAttribute('data-chart'));
    const yAxisContainer = document.getElementById('chart-y-axis'); // Trục X của biểu đồ lớn
    const barsContainer = document.getElementById('chart-bars-container'); // Khu vực vẽ cột biểu đồ
    const toggles = document.querySelectorAll('#chart-toggles button'); // 3 Nút Tuần/Tháng/Năm
    
    // Tương tự, lấy dữ liệu JSON của các Thẻ chỉ số phụ
    const statsContainer = document.getElementById('stat-cards-container');
    const statsData = statsContainer ? JSON.parse(statsContainer.getAttribute('data-stats')) : null;

    function formatCurrency(num) {
        if (num >= 1000000000) return (num / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
        if (num >= 1000000) return (num / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
        return num.toString();
    }

    // 2. HÀM CẬP NHẬT CÁC THẺ CHỈ SỐ PHỤ
    // Truyền tham số type ('weekly', 'monthly', 'yearly') để cập nhật số liệu tương ứng
    function renderStats(type) {
        if (!statsData) return;
        const data = statsData[type];
        if (!data) return;

        // Vòng lặp duyệt qua 5 loại chỉ số
        ['revenue', 'orders', 'customers', 'expenses', 'profit'].forEach(key => {
            const valueEl = document.getElementById(`stat-${key}-value`);
            const trendEl = document.getElementById(`stat-${key}-trend`);
            
            // Cập nhật con số hiển thị
            if (valueEl) valueEl.textContent = data[key].value;
            
            // Cập nhật phần trăm tăng giảm và đổi màu (xanh/đỏ)
            if (trendEl) {
                trendEl.textContent = data[key].trend.text;
                trendEl.className = `ml-1 text-[10px] px-1.5 py-0.5 rounded-full ${data[key].trend.bg} ${data[key].trend.color}`;
            }
            
            // Cập nhật chữ nhỏ bên dưới (Tuần này, Tháng này, Năm nay)
            const periodEl = document.getElementById(`stat-${key}-period`);
            if (periodEl) {
                const textMap = { 'weekly': 'Tuần này', 'monthly': 'Tháng này', 'yearly': 'Năm nay' };
                periodEl.textContent = textMap[type] || 'Kỳ hiện tại';
            }
        });
    }

    // 3. HÀM VẼ BIỂU ĐỒ CỘT
    // Dùng Javascript tạo ra các thẻ Div (đại diện cho các cột) và nhét vào màn hình
    function renderChart(type) {
        const dataObj = chartData[type];
        if (!dataObj) return;
        
        const labels = dataObj.labels;
        const data = dataObj.revenue;

        let maxVal = Math.max(...data);
        if (maxVal === 0) maxVal = 10000000; // Default 10M if no data

        // Xóa sạch các cột biểu đồ cũ trên màn hình trước khi bắt đầu vẽ cái mới
        barsContainer.innerHTML = '';

        // A. VẼ BIỂU ĐỒ CỘT DOANH THU (Biểu đồ lớn nhất ở giữa màn hình)
        // Dùng vòng lặp chạy qua từng mốc thời gian (từng ngày hoặc từng tháng)
        labels.forEach((label, index) => {
            const val = data[index];
            const percentage = Math.max(maxVal > 0 ? (val / maxVal) * 95 : 0, 2);

            const barWrapper = document.createElement('div');
            barWrapper.className = 'group relative flex-1 flex flex-col items-center justify-end h-full';

            const bar = document.createElement('div');
            bar.className = 'w-full max-w-[40px] bg-blue-500 group-hover:bg-blue-400 transition-all duration-1000 rounded-t-md relative';
            bar.style.height = '0%';

            const tooltip = document.createElement('div');
            tooltip.className = 'absolute -top-10 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] py-1.5 px-2.5 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20';
            tooltip.textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
            
            const tooltipArrow = document.createElement('div');
            tooltipArrow.className = 'absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900';
            tooltip.appendChild(tooltipArrow);

            bar.appendChild(tooltip);
            barWrapper.appendChild(bar);
            barsContainer.appendChild(barWrapper);

            setTimeout(() => {
                bar.style.transitionTimingFunction = 'cubic-bezier(0.34, 1.56, 0.64, 1)';
                bar.style.height = `${percentage}%`;
            }, 50 * index);
        });

        // B. VẼ BIỂU ĐỒ CỘT MINI CHO TỔNG SỐ ĐƠN HÀNG (Nằm trong thẻ phụ bên phải)
        const ordersChart = document.getElementById('small-orders-chart');
        if (ordersChart && dataObj.orders) {
            ordersChart.innerHTML = '';
            const ordersData = dataObj.orders;
            const maxOrders = Math.max(...ordersData, 1);
            ordersData.forEach((val, i) => {
                const h = Math.max((val / maxOrders) * 100, 5); // min 5%
                
                const barWrapper = document.createElement('div');
                barWrapper.className = 'group relative flex-1 flex flex-col items-center justify-end h-full cursor-pointer';

                const div = document.createElement('div');
                div.className = `w-full max-w-2 rounded-t transition-all duration-500 ${i % 2 === 0 ? 'bg-blue-400' : 'bg-blue-500'} group-hover:brightness-90 relative`;
                div.style.height = '0%';
                
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20';
                tooltip.textContent = `${val} đơn`;
                
                const tooltipArrow = document.createElement('div');
                tooltipArrow.className = 'absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900';
                tooltip.appendChild(tooltipArrow);

                barWrapper.appendChild(tooltip);
                barWrapper.appendChild(div);
                ordersChart.appendChild(barWrapper);
                
                setTimeout(() => { div.style.height = `${h}%`; }, 50 * i);
            });
        }

        // C. VẼ BIỂU ĐỒ CỘT MINI CHO SỐ LƯỢNG KHÁCH HÀNG (Nằm trong thẻ phụ bên phải)
        const customersChart = document.getElementById('small-customers-chart');
        if (customersChart && dataObj.customers) {
            customersChart.innerHTML = '';
            const custData = dataObj.customers;
            const maxCust = Math.max(...custData, 1);
            custData.forEach((val, i) => {
                const h = Math.max((val / maxCust) * 100, 5); // min 5%
                
                const barWrapper = document.createElement('div');
                barWrapper.className = 'group relative flex-1 flex flex-col items-center justify-end h-full cursor-pointer';

                const div = document.createElement('div');
                div.className = `w-full max-w-2 rounded-t transition-all duration-500 ${i % 2 === 0 ? 'bg-blue-400' : 'bg-blue-500'} group-hover:brightness-90 relative`;
                div.style.height = '0%';
                
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute bottom-full mb-1 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-20';
                tooltip.textContent = `${val} khách`;
                
                const tooltipArrow = document.createElement('div');
                tooltipArrow.className = 'absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900';
                tooltip.appendChild(tooltipArrow);

                barWrapper.appendChild(tooltip);
                barWrapper.appendChild(div);
                customersChart.appendChild(barWrapper);
                
                setTimeout(() => { div.style.height = `${h}%`; }, 50 * i);
            });
        }

        // D. IN NHÃN TRỤC X (Ví dụ in ra chữ: T2, T3... hoặc Th 1, Th 2...) ở dưới đáy biểu đồ lớn
        if (yAxisContainer) {
            yAxisContainer.innerHTML = labels.map(label => `<span class="flex-1 text-center truncate px-1">${label}</span>`).join('');
        }

        // E. ĐỔI MÀU NÚT BẤM (Nút nào đang được ấn thì bôi đậm màu xanh, nút khác đổi thành màu xám nhạt)
        toggles.forEach(btn => {
            if (btn.getAttribute('data-type') === type) {
                btn.className = 'px-3 py-1 text-sm bg-primary/10 text-primary font-bold rounded-md transition-colors';
            } else {
                btn.className = 'px-3 py-1 text-sm text-gray-500 hover:bg-gray-100 rounded-md transition-colors';
            }
        });
    }

    // Gán sự kiện Click cho 3 nút (Tuần này, Tháng này, Năm nay)
    toggles.forEach(btn => {
        btn.addEventListener('click', () => {
            renderChart(btn.getAttribute('data-type'));
            renderStats(btn.getAttribute('data-type'));
        });
    });

    // Gọi hàm chạy lần đầu tiên khi vừa load trang (Mặc định hiển thị dữ liệu Tháng)
    renderChart('monthly');
    renderStats('monthly');
});
