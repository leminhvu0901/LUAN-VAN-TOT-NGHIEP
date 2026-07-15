document.addEventListener('DOMContentLoaded', function () {
    let operationalChart = null;

    function initOperationalChart(data) {
        const ctx = document.getElementById('operational-chart');
        if (!ctx) return;

        if (operationalChart) {
            operationalChart.destroy();
        }

        operationalChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Doanh thu hoàn thành (VNĐ)',
                        data: data.revenue,
                        borderColor: '#10b981', // emerald-500
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        borderWidth: 3,
                        pointBackgroundColor: '#10b981',
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Số đơn đặt hàng',
                        data: data.orders,
                        type: 'bar',
                        backgroundColor: 'rgba(59, 130, 246, 0.35)', // blue-500
                        hoverBackgroundColor: 'rgba(59, 130, 246, 0.55)',
                        borderRadius: 5,
                        barPercentage: 0.5,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { family: 'Inter', size: 12, weight: '500' },
                            color: '#475569'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { family: 'Inter', size: 13, weight: 'bold' },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                        cornerRadius: 10,
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
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b'
                        }
                    },
                    y: {
                        position: 'left',
                        type: 'linear',
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
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
                            font: { family: 'Inter', size: 11 },
                            color: '#64748b',
                            stepSize: 1,
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // Khởi chạy vẽ biểu đồ
    if (window.dashboardConfig && window.dashboardConfig.chartData) {
        initOperationalChart(window.dashboardConfig.chartData);
    }
});
