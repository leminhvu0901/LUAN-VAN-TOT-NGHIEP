<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard - Happy Tea')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Thư viện Material Icons --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#059669', // Tailwind emerald-600
                        'primary-light': '#d1fae5',
                        success: '#10b981', // emerald-500
                        'success-light': '#d1fae5',
                        warning: '#f59e0b', // amber-500
                        'warning-light': '#fef3c7',
                        danger: '#ef4444', // red-500
                        'danger-light': '#fee2e2',
                        info: '#3b82f6', // blue-500
                        'info-light': '#dbeafe',
                        dark: '#1f2937',
                        'gray-light': '#f3f4f6',
                        'sidebar-active': '#eff6ff',
                        'sidebar-active-text': '#2563eb'
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
    @stack('styles')
</head>
<body class="flex h-screen overflow-hidden antialiased">
    
    {{-- Sidebar (Desktop & Mobile Drawer) --}}
    @include('admin.components.sidebar')

    {{-- Main Content --}}
    <main class="flex-1 flex flex-col h-screen overflow-hidden bg-[#f8fafc]">
        
        {{-- Mobile Header with Hamburger --}}
        <header class="lg:hidden flex items-center justify-between p-4 bg-white border-b border-gray-200">
            <div class="flex items-center gap-2 text-primary font-bold text-xl">
                <span class="material-symbols-outlined">local_cafe</span>
                Happy Tea
            </div>
            <button id="mobile-menu-btn" class="p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                <span class="material-symbols-outlined">menu</span>
            </button>
        </header>
        
        {{-- Content Area --}}
        <div class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto h-full flex flex-col">
                @yield('content')
            </div>
        </div>
    </main>

    {{-- Overlay cho mobile sidebar --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden lg:hidden transition-opacity"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                const isOpen = !sidebar.classList.contains('-translate-x-full');
                
                if (isOpen) {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                } else {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                }
            }

            if(mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });
    </script>
    @stack('scripts')
</body>
</html>
