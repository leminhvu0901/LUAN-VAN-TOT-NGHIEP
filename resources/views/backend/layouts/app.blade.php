<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard - Happy Tea')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Be Vietnam Pro', 'sans-serif'],
                    "label-md": ["Be Vietnam Pro"], "body-lg": ["Be Vietnam Pro"], "display-lg": ["Be Vietnam Pro"],
                    "body-md": ["Be Vietnam Pro"], "label-sm": ["Be Vietnam Pro"], "headline-md": ["Be Vietnam Pro"],
                    "body-sm": ["Be Vietnam Pro"], "headline-lg": ["Be Vietnam Pro"], "headline-lg-mobile": ["Be Vietnam Pro"]
                },
                fontSize: {
                    "label-md": ["14px", {"lineHeight": "20px", "fontWeight": "600"}],
                    "body-lg": ["18px", {"lineHeight": "28px", "fontWeight": "400"}],
                    "display-lg": ["48px", {"lineHeight": "60px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                    "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "label-sm": ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                    "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                    "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                    "headline-lg-mobile": ["28px", {"lineHeight": "36px", "fontWeight": "700"}]
                },
                borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                spacing: {
                    "container-max": "1280px", "md": "16px", "gutter": "24px", "sm": "12px", "lg": "24px",
                    "margin-mobile": "16px", "base": "8px", "xs": "4px", "xl": "40px"
                },
                colors: {
                    success: '#10b981', 'success-light': '#d1fae5', warning: '#f59e0b', 'warning-light': '#fef3c7',
                    danger: '#ef4444', 'danger-light': '#fee2e2', info: '#3b82f6', 'info-light': '#dbeafe', dark: '#1f2937',
                    'gray-light': '#f3f4f6', 'sidebar-active': '#ecfdf5', 'sidebar-active-text': '#059669',
                    "surface-container-high": "#dfe8ff", "surface-container": "#e8eeff", "primary": "#006e01",
                    "on-background": "#111c2d", "tertiary-fixed-dim": "#c4c7ca", "on-secondary-container": "#596577",
                    "surface-bright": "#f9f9ff", "on-secondary": "#ffffff", "tertiary-container": "#929598",
                    "surface": "#f9f9ff", "on-secondary-fixed-variant": "#3c4859", "on-secondary-fixed": "#101c2c",
                    "secondary-container": "#d7e3f9", "on-surface": "#111c2d", "secondary-fixed-dim": "#bbc7dc",
                    "on-primary-fixed": "#002200", "primary-fixed-dim": "#56e245", "background": "#f9f9ff",
                    "primary-fixed": "#77ff62", "outline": "#6d7b67", "secondary": "#535f71",
                    "on-primary-fixed-variant": "#005301", "on-primary-container": "#003600", "surface-variant": "#d9e3fb",
                    "surface-tint": "#006e01", "surface-container-highest": "#d9e3fb", "tertiary-fixed": "#e0e3e6",
                    "outline-variant": "#bccbb4", "on-primary": "#ffffff", "error-container": "#ffdad6", "tertiary": "#5c5f61",
                    "surface-container-low": "#f0f3ff", "on-tertiary-fixed-variant": "#44474a", "on-tertiary-fixed": "#191c1e",
                    "on-surface-variant": "#3e4a38", "on-error": "#ffffff", "on-tertiary-container": "#2a2e30",
                    "surface-container-lowest": "#ffffff", "on-error-container": "#93000a", "on-tertiary": "#ffffff",
                    "surface-dim": "#d0daf2", "primary-container": "#0aad0a", "error": "#ba1a1a", "inverse-primary": "#56e245",
                    "inverse-on-surface": "#ecf0ff", "inverse-surface": "#273143", "secondary-fixed": "#d7e3f9"
                }
            }
        }
    };
    </script>

    <link rel="stylesheet" href="{{ asset('css/backend/admin.css') }}?v={{ filemtime(public_path('css/backend/admin.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    
    @stack('styles')
</head>

<body class="flex h-screen overflow-hidden antialiased">

    @include($sidebarView ?? 'backend.components.sidebar')

    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc]">
        @include('backend.components.topbar')

        <div id="main-content-area" class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
            <div id="main-content-inner" class="max-w-7xl mx-auto min-h-full flex flex-col">
                @yield('content')
            </div>
        </div>
    </main>

    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden lg:hidden transition-opacity">
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileCloseBtn = document.getElementById('mobile-close-btn');
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const collapseBtn = document.getElementById('desktop-collapse-btn');
        const collapseIcon = document.getElementById('collapse-icon');

        function toggleMobileSidebar() {
            if (sidebar) sidebar.classList.toggle('-translate-x-full');
            if (overlay) overlay.classList.toggle('hidden');
        }

        if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
        if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', toggleMobileSidebar);
        if (overlay) overlay.addEventListener('click', toggleMobileSidebar);

        if (collapseBtn && sidebar) {
            collapseBtn.addEventListener('click', function () {
                sidebar.classList.toggle('is-collapsed');
                sidebar.classList.toggle('w-64');
                sidebar.classList.toggle('w-[80px]');
                if (collapseIcon) {
                    collapseIcon.style.transform = sidebar.classList.contains('is-collapsed') ? 'rotate(180deg)' : 'rotate(0deg)';
                }
            });
        }

        if (typeof window.initCustomSelects === 'function') {
            window.initCustomSelects();
        }
    });

    window.initCustomSelects = function () {
        const selects = document.querySelectorAll('select.custom-select-init');
        selects.forEach(select => {
            if (select.dataset.customSelectInitialized) return;
            select.dataset.customSelectInitialized = "true";
            select.style.display = 'none';

            const container = document.createElement('div');
            container.className = 'custom-select-container relative shrink-0 z-10 ' + (select.getAttribute('data-width-class') || 'w-full sm:w-auto');

            select.parentNode.insertBefore(container, select);
            container.appendChild(select);

            let selectedOption = select.options[select.selectedIndex];

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'px-3 py-1.5 sm:py-2 border border-gray-200 rounded-lg bg-gray-50 text-sm font-medium text-gray-700 w-full flex items-center justify-between cursor-pointer transition-colors hover:border-emerald-300 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 min-h-[40px] sm:min-h-[38px]';
            button.innerHTML = `<span class="truncate pr-2 select-label-text">${selectedOption ? selectedOption.text : ''}</span><i class="fa-solid fa-chevron-down text-gray-400 text-xs shrink-0"></i>`;
            container.appendChild(button);

            const menu = document.createElement('div');
            menu.className = 'absolute top-[calc(100%+4px)] left-0 w-full min-w-[160px] bg-white border border-gray-200 rounded-lg organic-shadow hidden flex-col z-[100] max-h-[220px] overflow-y-auto py-1 shadow-lg';
            container.appendChild(menu);

            select.addEventListener('change', () => {
                let currentOpt = select.options[select.selectedIndex];
                if (currentOpt) {
                    button.querySelector('.select-label-text').textContent = currentOpt.text;
                    Array.from(menu.children).forEach(child => {
                        if (child.dataset.value === currentOpt.value) {
                            child.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 bg-emerald-50 text-emerald-700 font-semibold';
                        } else {
                            child.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 text-gray-700';
                        }
                    });
                }
            });

            Array.from(select.options).forEach(option => {
                const item = document.createElement('div');
                item.dataset.value = option.value;
                item.className = `px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 ${option.selected ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'text-gray-700'}`;
                item.textContent = option.text;

                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    select.value = option.value;
                    button.querySelector('.select-label-text').textContent = option.text;

                    Array.from(menu.children).forEach(child => {
                        child.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 text-gray-700';
                    });
                    item.className = 'px-3 py-2 text-sm cursor-pointer transition-colors hover:bg-emerald-50 hover:text-emerald-700 bg-emerald-50 text-emerald-700 font-semibold';

                    menu.classList.add('hidden');
                    menu.classList.remove('flex');
                    container.style.zIndex = '10';

                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                menu.appendChild(item);
            });

            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const isHidden = menu.classList.contains('hidden');
                document.querySelectorAll('.custom-select-options').forEach(m => {
                    m.classList.add('hidden');
                    m.classList.remove('flex');
                });
                document.querySelectorAll('.custom-select-container').forEach(c => {
                    c.style.zIndex = '10';
                });
                if (isHidden) {
                    menu.classList.remove('hidden');
                    menu.classList.add('flex');
                    container.style.zIndex = '50';
                }
            });
            menu.classList.add('custom-select-options');
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.custom-select-options').forEach(m => {
                m.classList.add('hidden');
                m.classList.remove('flex');
            });
            document.querySelectorAll('.custom-select-container').forEach(c => {
                c.style.zIndex = '10';
            });
        }, { once: false });
    };

    window.AdminAlert = {
        success: function (message, title = 'Thành công!') {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                return Toast.fire({ icon: 'success', title: message || title });
            }
        },
        error: function (message, title = 'Lỗi') {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true });
                return Toast.fire({ icon: 'error', title: title, html: message });
            }
        },
        confirm: function (message, confirmCallback, title = 'Xác nhận') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title, text: message, showCancelButton: true, confirmButtonText: 'Đồng ý', cancelButtonText: 'Hủy',
                    buttonsStyling: false, width: '340px', padding: '1.25rem', returnFocus: false,
                    customClass: {
                        popup: 'rounded-xl shadow-xl border border-gray-100 p-4', title: 'text-base font-bold text-gray-800 mb-1',
                        htmlContainer: 'text-xs text-gray-500 mb-4', actions: 'w-full flex gap-2 mt-1',
                        confirmButton: 'flex-1 px-4 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg text-xs hover:bg-emerald-700 transition-colors shadow-sm cursor-pointer',
                        cancelButton: 'flex-1 px-4 py-1.5 bg-white text-gray-700 font-semibold rounded-lg text-xs border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer'
                    }
                }).then((result) => {
                    if (result.isConfirmed && typeof confirmCallback === 'function') confirmCallback();
                });
            }
        },
        loading: function (message = 'Vui lòng đợi trong giây lát', title = 'Đang xử lý...') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title, html: message, allowOutsideClick: false, width: '280px', padding: '1rem', showConfirmButton: false, returnFocus: false,
                    customClass: { popup: 'rounded-xl shadow-xl border border-gray-100 p-4', title: 'text-sm font-bold text-gray-800 mb-1', htmlContainer: 'text-xs text-gray-500 mt-2' },
                    didOpen: () => { Swal.showLoading(); }
                });
            }
        },
        prompt: function (title, message, placeholder, confirmCallback, validatorMessage = 'Vui lòng nhập thông tin!', confirmText = 'Xác nhận', minLength = 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title, text: message, input: 'text', inputPlaceholder: placeholder, showCancelButton: true, confirmButtonText: confirmText, cancelButtonText: 'Hủy',
                    buttonsStyling: false, width: '340px', padding: '1.25rem', returnFocus: false,
                    customClass: {
                        popup: 'rounded-xl shadow-xl border border-gray-100 p-4', title: 'text-base font-bold text-gray-800 mb-1', htmlContainer: 'text-xs text-gray-500 mb-3',
                        input: 'w-full px-3 py-2 border border-gray-200 rounded-lg text-xs focus:border-emerald-500 transition-colors mb-3 shadow-none text-center focus:ring-0 focus:outline-none !outline-none font-normal',
                        validationMessage: 'text-xs text-red-500 bg-red-50 p-2.5 rounded-lg mb-3 border border-red-100 text-center w-full shadow-none mt-0 font-medium',
                        actions: 'w-full flex gap-2 mt-1',
                        confirmButton: 'flex-1 px-4 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg text-xs hover:bg-emerald-700 transition-colors shadow-sm cursor-pointer',
                        cancelButton: 'flex-1 px-4 py-1.5 bg-white text-gray-700 font-semibold rounded-lg text-xs border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm cursor-pointer'
                    },
                    inputValidator: (value) => {
                        if (!value) return validatorMessage;
                        if (minLength && value.trim().length < minLength) return `Vui lòng nhập ít nhất ${minLength} ký tự.`;
                    }
                }).then((result) => {
                    if (typeof confirmCallback === 'function') confirmCallback(result.value, result.isConfirmed);
                });
            } else {
                const reason = prompt(message);
                if (typeof confirmCallback === 'function') {
                    if (reason !== null && reason.trim() !== "") confirmCallback(reason.trim(), true); else confirmCallback(null, false);
                }
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal !== 'undefined') {
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            if (window.flashSuccessMessage) {
                Toast.fire({ icon: 'success', title: window.flashSuccessMessage });
            }
            if (window.flashErrorMessages) {
                let htmlContent = '<div class="text-left text-xs font-semibold space-y-0.5 mt-1">';
                window.flashErrorMessages.forEach(msg => { htmlContent += `<div>• ${msg}</div>`; });
                htmlContent += '</div>';
                Toast.fire({ icon: 'error', title: 'Có lỗi xảy ra!', html: htmlContent, timer: 5000 });
            }
        }
    });

    window.smartGoBack = function (event) {
        if (document.referrer.includes(window.location.host)) {
            event.preventDefault();
            window.history.back();
        }
    };

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) window.location.reload();
    });

    window.toggleFilterPanel = function (targetId) {
        const el = document.getElementById(targetId);
        if (!el) return;
        el.classList.toggle('hidden');
        el.classList.toggle('flex');
    };

    window.bulkDeselectAllRows = function (checkboxSelector, resetFnName) {
        document.querySelectorAll('.js-select-all, ' + checkboxSelector).forEach(function (el) {
            el.checked = false;
        });
        if (typeof window[resetFnName] === 'function') {
            window[resetFnName]();
        }
    };

    (function () {
        const KEY = 'bulk-sel:' + window.location.pathname;
        const CANDIDATES = ['.material-checkbox', '.product-checkbox', '.order-checkbox', '.row-checkbox'];

        function read() {
            try { return new Set(JSON.parse(sessionStorage.getItem(KEY) || '[]')); }
            catch (e) { return new Set(); }
        }

        function write(set) {
            try { sessionStorage.setItem(KEY, JSON.stringify(Array.from(set))); }
            catch (e) { }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('table-container');
            if (!container) return;

            const selector = CANDIDATES.find(function (s) { return container.querySelector(s); });
            if (!selector) return;

            const cls = selector.slice(1);

            function sync() {
                const set = read();
                const rowChecked = new Map();
                container.querySelectorAll(selector).forEach(function (cb) {
                    rowChecked.set(cb.value, (rowChecked.get(cb.value) || false) || cb.checked);
                });
                rowChecked.forEach(function (isChecked, value) {
                    if (isChecked) set.add(value);
                    else set.delete(value);
                });
                write(set);
            }

            const saved = read();
            if (saved.size > 0) {
                const byValue = new Map();
                container.querySelectorAll(selector).forEach(function (cb) {
                    if (!byValue.has(cb.value)) byValue.set(cb.value, []);
                    byValue.get(cb.value).push(cb);
                });

                const onPage = new Set(byValue.keys());
                const restored = [];
                saved.forEach(function (id) {
                    const group = byValue.get(id);
                    if (!group) return;
                    const target = group.find(function (cb) { return cb.offsetParent !== null; }) || group[0];
                    target.checked = true;
                    restored.push(target);
                });

                const sample = container.querySelector(selector);
                const ghostClass = sample ? sample.className : cls;

                saved.forEach(function (id) {
                    if (onPage.has(id)) return;
                    const ghost = document.createElement('input');
                    ghost.type = 'checkbox';
                    ghost.className = ghostClass;
                    ghost.value = id;
                    ghost.checked = true;
                    ghost.dataset.offPage = '1';
                    ghost.style.display = 'none';
                    container.appendChild(ghost);
                    restored.push(ghost);
                });

                setTimeout(function () {
                    restored.forEach(function (cb) {
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                }, 0);
            }

            container.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains(cls)) {
                    setTimeout(sync, 0);
                }
            });
            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList && e.target.classList.contains('js-select-all')) {
                    setTimeout(sync, 0);
                }
            });

            const deselectBtn = document.getElementById('bulk-deselect-btn');
            if (deselectBtn) deselectBtn.addEventListener('click', function () { setTimeout(sync, 0); });

            const form = document.getElementById('bulk-delete-form');
            if (form) {
                const nativeSubmit = form.submit.bind(form);
                form.submit = function () {
                    try { sessionStorage.removeItem(KEY); } catch (e) { }
                    return nativeSubmit();
                };
            }
        });
    })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    @include('backend.partials.flash_messages')

    @stack('scripts')
</body>

</html>