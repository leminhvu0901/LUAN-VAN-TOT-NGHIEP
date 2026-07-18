document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const mobileBtn = document.getElementById('mobile-tab-selector-btn');
    const mobileMenu = document.getElementById('mobile-tab-dropdown-menu');

    // 1. Hàm chuyển đổi Tab cài đặt
    function switchTab(targetId) {
        // Lưu lại tab đang chọn vào localStorage và URL hash
        localStorage.setItem('active_settings_tab', targetId);
        if (history.pushState) {
            history.pushState(null, null, `#${targetId}`);
        } else {
            window.location.hash = targetId;
        }

        // Cập nhật class active cho nút tab (cả desktop)
        tabButtons.forEach(btn => {
            const btnTarget = btn.getAttribute('data-target');
            if (btnTarget === targetId) {
                btn.classList.add('bg-emerald-50', 'text-emerald-600', 'active-tab', 'border-emerald-200');
                btn.classList.remove('text-gray-500', 'hover:bg-gray-50');
            } else {
                btn.classList.remove('bg-emerald-50', 'text-emerald-600', 'active-tab', 'border-emerald-200');
                btn.classList.add('text-gray-500', 'hover:bg-gray-50');
            }
        });

        // Cập nhật nhãn và icon hiển thị trên Mobile Dropdown Selector
        const activeBtn = document.querySelector(`.tab-btn[data-target="${targetId}"]`);
        const mobileLabel = document.getElementById('mobile-tab-active-label');
        if (activeBtn && mobileLabel) {
            mobileLabel.innerHTML = activeBtn.innerHTML;
        }

        // Hiển thị pane tương ứng, ẩn các pane khác
        tabPanes.forEach(pane => {
            const paneId = pane.getAttribute('id');
            if (paneId === `section-${targetId}`) {
                pane.classList.remove('hidden');
            } else {
                pane.classList.add('hidden');
            }
        });

        // Cuộn tab button vào tầm nhìn
        if (activeBtn && activeBtn.scrollIntoView) {
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    }

    // Đăng ký sự kiện chuyển tab cho nút Desktop
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            switchTab(targetId);
        });
    });

    // 2. Khởi tạo dropdown di động (Mobile Dropdown Selector)
    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function () {
            mobileMenu.classList.add('hidden');
        });

        mobileMenu.querySelectorAll('.mobile-tab-option').forEach(opt => {
            opt.addEventListener('click', function () {
                const target = this.getAttribute('data-value');
                switchTab(target);
                mobileMenu.classList.add('hidden');
            });
        });
    }

    // 3. Khởi tạo tab ban đầu (Thứ tự ưu tiên: Backend Session -> URL Hash -> LocalStorage -> mặc định 'store')
    const settingsPage = document.querySelector('.settings-page');
    let initialSection = settingsPage ? settingsPage.dataset.activeSection : null;

    if (!initialSection && window.location.hash) {
        const hashVal = window.location.hash.substring(1);
        const validSections = ['store', 'orders', 'shipping', 'payment', 'loyalty', 'notifications'];
        if (validSections.includes(hashVal)) {
            initialSection = hashVal;
        }
    }

    if (!initialSection) {
        const storedTab = localStorage.getItem('active_settings_tab');
        const validSections = ['store', 'orders', 'shipping', 'payment', 'loyalty', 'notifications'];
        if (storedTab && validSections.includes(storedTab)) {
            initialSection = storedTab;
        }
    }

    // Kích hoạt tab ban đầu
    switchTab(initialSection || 'store');

    // 4. Xử lý các logic phụ thuộc trường (Dependencies)
    
    // Phụ thuộc 1: Tự động hủy đơn chưa thanh toán
    const autoCancelToggle = document.getElementById('auto_cancel_unpaid_enabled');
    const autoCancelMinutes = document.getElementById('auto_cancel_unpaid_minutes');
    if (autoCancelToggle && autoCancelMinutes) {
        const handleAutoCancelDependency = () => {
            const isEnabled = autoCancelToggle.checked;
            autoCancelMinutes.disabled = !isEnabled;
            const parentField = autoCancelMinutes.closest('.flex-col');
            if (parentField) {
                parentField.style.opacity = isEnabled ? '1' : '0.5';
                parentField.style.pointerEvents = isEnabled ? 'auto' : 'none';
            }
        };
        autoCancelToggle.addEventListener('change', handleAutoCancelDependency);
        handleAutoCancelDependency();
    }

    // Phụ thuộc 2: Phụ thu thời tiết xấu
    const weatherToggle = document.getElementById('weather_surcharge_enabled');
    const weatherInputs = [
        document.getElementById('weather_light_rain_percent'),
        document.getElementById('weather_heavy_rain_percent'),
        document.getElementById('weather_storm_percent')
    ];
    if (weatherToggle) {
        const handleWeatherDependency = () => {
            const isEnabled = weatherToggle.checked;
            weatherInputs.forEach(input => {
                if (input) {
                    input.disabled = !isEnabled;
                    const parent = input.closest('.flex-col');
                    if (parent) {
                        parent.style.opacity = isEnabled ? '1' : '0.5';
                        parent.style.pointerEvents = isEnabled ? 'auto' : 'none';
                    }
                }
            });
        };
        weatherToggle.addEventListener('change', handleWeatherDependency);
        handleWeatherDependency();
    }

    // 5. Cập nhật preview thời gian thực
    
    // Initialize Flatpickr for settings-time-picker
    const timePickers = document.querySelectorAll('.settings-time-picker');
    const openTimeInput = document.getElementById('store_open_time');
    const closeTimeInput = document.getElementById('store_close_time');
    const hoursPreview = document.getElementById('hours-preview-text');

    const updateHoursPreview = () => {
        if (!hoursPreview) return;
        const openVal = openTimeInput ? openTimeInput.value.trim() : '';
        const closeVal = closeTimeInput ? closeTimeInput.value.trim() : '';
        
        if (openVal && closeVal) {
            hoursPreview.textContent = `Cửa hàng hoạt động từ ${openVal} đến ${closeVal}`;
            hoursPreview.classList.remove('text-red-500');
            hoursPreview.classList.add('text-gray-400');
        } else {
            hoursPreview.textContent = `Vui lòng chọn đầy đủ giờ mở cửa và đóng cửa.`;
            hoursPreview.classList.add('text-red-500');
            hoursPreview.classList.remove('text-gray-400');
        }
    };

    if (timePickers.length && typeof flatpickr !== 'undefined') {
        timePickers.forEach(picker => {
            flatpickr(picker, {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 5,
                allowInput: true,
                disableMobile: true,
                locale: 'vn',
                onChange: function() {
                    updateHoursPreview();
                }
            });
        });
    }

    // Khởi tạo preview ban đầu
    updateHoursPreview();

    // Preview tích lũy điểm
    const loyaltyMoneyInput = document.getElementById('loyalty_money_per_point');
    const loyaltyIllustration = document.getElementById('loyalty-illustration-text');
    if (loyaltyMoneyInput && loyaltyIllustration) {
        const updateLoyaltyIllustration = () => {
            const value = parseFloat(loyaltyMoneyInput.value) || 0;
            const formatted = new Intl.NumberFormat('vi-VN').format(value);
            loyaltyIllustration.textContent = `Ví dụ: Chi tiêu ${formatted} VNĐ nhận được 1 điểm.`;
        };
        loyaltyMoneyInput.addEventListener('input', updateLoyaltyIllustration);
        updateLoyaltyIllustration();
    }

    // Segmented environment selector
    const envToggleBtns = document.querySelectorAll('.env-toggle-btn');
    const envHiddenInput = document.getElementById('payment_environment');
    if (envToggleBtns.length && envHiddenInput) {
        const updateEnvSelector = (val) => {
            envHiddenInput.value = val;
            envToggleBtns.forEach(btn => {
                const btnVal = btn.getAttribute('data-val');
                if (btnVal === val) {
                    btn.classList.add('bg-white', 'text-emerald-700', 'shadow-sm');
                    btn.classList.remove('text-gray-500', 'hover:bg-white/50');
                } else {
                    btn.classList.remove('bg-white', 'text-emerald-700', 'shadow-sm');
                    btn.classList.add('text-gray-500', 'hover:bg-white/50');
                }
            });
        };

        envToggleBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.getAttribute('data-val');
                updateEnvSelector(val);
            });
        });

        // Initialize
        updateEnvSelector(envHiddenInput.value);
    }

    // 6. Xử lý tải ảnh logo xem trước (Logo Upload Preview)
    const logoInput = document.getElementById('store_logo');
    const logoPreview = document.getElementById('logo-preview-img');
    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const maxSize = 2 * 1024 * 1024;
                if (file.size > maxSize) {
                    if (window.AdminAlert) {
                        window.AdminAlert.error('Kích thước logo không được vượt quá 2MB!', 'Tệp quá lớn');
                    } else {
                        alert('Kích thước logo không được vượt quá 2MB!');
                    }
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    logoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 7. Gửi các form cấu hình bằng AJAX
    function initAjaxForms() {
        document.querySelectorAll('.tab-pane form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Đang lưu...';
                }

                // Dọn dẹp các lớp lỗi cũ
                form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
                form.querySelectorAll('.field-error-msg').forEach(el => el.remove());

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    const data = result.data;
                    if (result.ok && data.success) {
                        window.AdminAlert.success(data.message, 'Thành công!');

                        if (data.store_logo && logoPreview) {
                            logoPreview.src = data.store_logo;
                        }

                        if (typeof data.momo_configured === 'boolean') {
                            const badge = document.getElementById('momo-config-badge');
                            if (badge) {
                                badge.textContent = data.momo_configured ? 'ĐÃ CẤU HÌNH' : 'CHƯA CẤU HÌNH';
                                badge.className = data.momo_configured 
                                    ? 'px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-lg text-[10px] font-bold'
                                    : 'px-2 py-0.5 bg-amber-100 text-amber-800 rounded-lg text-[10px] font-bold';
                            }
                        }
                    } else {
                        // Hiển thị alert lỗi
                        window.AdminAlert.error(data.message || 'Có lỗi xảy ra, vui lòng thử lại.', 'Lỗi!');
                    }
                })
                .catch(function (error) {
                    console.error(error);
                    window.AdminAlert.error('Không thể kết nối đến máy chủ.', 'Lỗi!');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                });
            });
        });
    }
    initAjaxForms();
});
