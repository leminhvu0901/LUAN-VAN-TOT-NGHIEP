@extends('admin.layouts.app')

@section('title', 'Danh sách Đơn hàng - Admin')

@section('content')
<div class="flex flex-col gap-6 h-full pb-4">
    
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-1">Danh sách Đơn hàng</h1>
            <p class="text-sm text-gray-500">Theo dõi và quản lý các đơn đặt hàng từ hệ thống Happy Tea.</p>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <button onclick="alert('Tính năng Xuất báo cáo đang được phát triển!')" class="flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors flex-1 sm:flex-none">
                <span class="material-symbols-outlined text-[20px]">download</span>
                Xuất báo cáo
            </button>
            <a href="{{ route('admin.orders.create') }}" class="flex items-center justify-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-emerald-700 font-medium transition-colors shadow-sm shadow-emerald-200 flex-1 sm:flex-none whitespace-nowrap">
                <span class="material-symbols-outlined text-[20px]">add</span>
                Tạo đơn mới
            </a>
        </div>
    </div>

    {{-- Main Table Area --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col flex-1 overflow-hidden min-h-[600px]">
        
        {{-- Tabs --}}
        <div class="flex overflow-x-auto border-b border-gray-100 shrink-0 custom-scrollbar">
            @php
                $tabs = [
                    '' => 'Tất cả',
                    'pending' => 'Chờ xác nhận',
                    'confirmed' => 'Đã xác nhận',
                    'shipping' => 'Đang giao',
                    'completed' => 'Hoàn thành',
                    'cancelled' => 'Đã hủy',
                ];
            @endphp
            
            @foreach($tabs as $key => $label)
                @php
                    $isActive = ($currentStatus == $key) || ($key === '' && is_null($currentStatus));
                @endphp
                <a href="{{ route('admin.orders.index', $key ? ['status' => $key] : []) }}" 
                   class="px-6 py-4 text-sm whitespace-nowrap transition-colors border-b-2 {{ $isActive ? 'font-semibold text-primary border-primary bg-emerald-50/30' : 'font-medium text-gray-500 hover:text-gray-700 border-transparent hover:border-gray-300' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Filters & Search --}}
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 shrink-0">
            <form id="search-form" action="{{ route('admin.orders.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                
                <div class="flex-1 w-full">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tìm kiếm</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">search</span>
                        <input type="text" name="search" id="search-input" value="{{ request('search') }}" placeholder="Mã đơn, Tên, SĐT khách hàng..." 
                               class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>
                </div>

                <div class="w-full sm:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Từ ngày</label>
                    <input type="date" name="date_from" id="date-from-input" value="{{ request('date_from') }}" 
                           class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                <div class="w-full sm:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Đến ngày</label>
                    <input type="date" name="date_to" id="date-to-input" value="{{ request('date_to') }}" 
                           class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                </div>

                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="button" onclick="loadTableData()" class="flex-1 sm:flex-none px-4 py-2 bg-gray-800 text-white font-medium text-sm rounded-lg hover:bg-gray-900 transition-colors">
                        Lọc
                    </button>
                    @if(request('search') || request('date_from') || request('date_to'))
                        <a href="{{ route('admin.orders.index', request('status') ? ['status' => request('status')] : []) }}" class="flex items-center justify-center px-3 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors" title="Xóa bộ lọc">
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table Container for AJAX --}}
        <div id="table-container" class="flex-1 flex flex-col min-h-0 relative">
            <div id="table-loader" class="absolute inset-0 bg-white/60 z-20 hidden items-center justify-center">
                <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
            </div>
            
            <div class="flex-1 overflow-x-auto custom-scrollbar relative">
                @include('admin.orders.partials.table')
            </div>
        </div>
    </div>

    {{-- Bottom Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 shrink-0">
        
        {{-- Card 1 --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center mb-4 border border-blue-100">
                <span class="material-symbols-outlined text-blue-500">shopping_bag</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ĐƠN TRONG NGÀY</p>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $stats['today_orders']['value'] }}</h3>
            <div class="flex items-center text-xs font-medium text-success">
                <span class="material-symbols-outlined text-[14px]">trending_up</span>
                <span class="ml-1">{{ $stats['today_orders']['trend'] }}</span>
            </div>
        </div>

        {{-- Card 2 --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center mb-4 border border-emerald-100">
                <span class="material-symbols-outlined text-emerald-500">payments</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">DOANH THU NGÀY</p>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $stats['today_revenue']['value'] }}</h3>
            <div class="flex items-center text-xs font-medium text-success">
                <span class="material-symbols-outlined text-[14px]">trending_up</span>
                <span class="ml-1">{{ $stats['today_revenue']['trend'] }}</span>
            </div>
        </div>

        {{-- Card 3 --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center mb-4 border border-amber-100">
                <span class="material-symbols-outlined text-amber-500">assignment_late</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ĐANG CHỜ XỬ LÝ</p>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $stats['pending_orders']['value'] }}</h3>
            <div class="flex items-center text-xs font-medium text-gray-500">
                <span class="ml-1">{{ $stats['pending_orders']['trend'] }}</span>
            </div>
        </div>

        {{-- Card 4 --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center mb-4 border border-red-100">
                <span class="material-symbols-outlined text-red-500">cancel</span>
            </div>
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ĐƠN BỊ HỦY (THÁNG)</p>
            <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $stats['cancelled_orders']['value'] }}</h3>
            <div class="flex items-center text-xs font-medium text-danger">
                <span class="material-symbols-outlined text-[14px]">trending_down</span>
                <span class="ml-1">{{ $stats['cancelled_orders']['trend'] }}</span>
            </div>
        </div>

    </div>

</div>

{{-- Cancel Reason Modal --}}
<div id="cancel-modal" class="fixed inset-0 z-50 hidden bg-gray-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-red-50/30">
            <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-danger">cancel</span>
                Lý do hủy đơn hàng
            </h3>
            <button type="button" onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-5">
            <p class="text-sm text-gray-500 mb-3">Vui lòng nhập lý do hủy đơn hàng này để lưu vào lịch sử.</p>
            <textarea id="cancel-reason-input" rows="3" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-danger/20 focus:border-danger transition-all resize-none" placeholder="Ví dụ: Khách yêu cầu hủy, Hết món, Không liên lạc được..."></textarea>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeCancelModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Quay lại
            </button>
            <button type="button" onclick="submitCancelReason()" class="px-4 py-2 text-sm font-medium text-white bg-danger rounded-lg hover:bg-red-600 transition-colors shadow-sm shadow-red-200">
                Xác nhận hủy
            </button>
        </div>
    </div>
</div>

<script>
    // --- Cancel Modal Logic ---
    let currentOrderId = null;
    let currentFormId = null;
    let previousStatus = null;

    function handleStatusChange(selectElement, orderId, oldStatus) {
        if (selectElement.value === 'cancelled') {
            currentOrderId = orderId;
            currentFormId = 'form-status-' + orderId;
            previousStatus = oldStatus;
            document.getElementById('cancel-modal').classList.remove('hidden');
        } else {
            selectElement.closest('form').submit();
        }
    }

    function closeCancelModal() {
        document.getElementById('cancel-modal').classList.add('hidden');
        document.getElementById('cancel-reason-input').value = '';
        if (currentFormId) {
            const form = document.getElementById(currentFormId);
            if(form) form.querySelector('select').value = previousStatus;
        }
        currentOrderId = null;
        currentFormId = null;
    }

    function submitCancelReason() {
        const reason = document.getElementById('cancel-reason-input').value.trim();
        if (!reason) {
            alert('Vui lòng nhập lý do hủy đơn!');
            document.getElementById('cancel-reason-input').focus();
            return;
        }
        
        if (currentFormId && currentOrderId) {
            const form = document.getElementById(currentFormId);
            document.getElementById('reason-' + currentOrderId).value = reason;
            form.submit();
        }
    }

    // --- AJAX Live Search Logic ---
    let searchTimeout = null;
    const form = document.getElementById('search-form');
    const tableContainer = document.getElementById('table-container');
    const loader = document.getElementById('table-loader');

    function loadTableData(url = null) {
        if (!url) {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            url = form.action + '?' + params.toString();
        }

        // Update URL state (pushState)
        window.history.pushState({}, '', url);

        loader.classList.remove('hidden');
        loader.classList.add('flex');

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Because table-container contains both the loader and the overflow div,
            // we will replace the inner content of the overflow div.
            const wrapper = tableContainer.querySelector('.overflow-x-auto');
            wrapper.innerHTML = html;
            loader.classList.add('hidden');
            loader.classList.remove('flex');
            attachPaginationListeners();
        })
        .catch(err => {
            console.error(err);
            loader.classList.add('hidden');
            loader.classList.remove('flex');
        });
    }

    function handleLiveSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadTableData();
        }, 500); // Wait 500ms after user stops typing
    }

    document.getElementById('search-input').addEventListener('input', handleLiveSearch);
    document.getElementById('date-from-input').addEventListener('change', handleLiveSearch);
    document.getElementById('date-to-input').addEventListener('change', handleLiveSearch);

    // Prevent default form submission on enter
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        loadTableData();
    });

    // Delegate pagination clicks so they also run through AJAX
    function attachPaginationListeners() {
        const wrapper = tableContainer.querySelector('.overflow-x-auto');
        const links = wrapper.querySelectorAll('.ajax-pagination a');
        links.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadTableData(this.href);
            });
        });
    }

    // Initial attach
    attachPaginationListeners();

    // Make Tabs use AJAX
    const tabLinks = document.querySelectorAll('.custom-scrollbar a');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update Active Class visually immediately
            tabLinks.forEach(t => {
                t.classList.remove('font-semibold', 'text-primary', 'border-primary', 'bg-emerald-50/30');
                t.classList.add('font-medium', 'text-gray-500', 'border-transparent');
            });
            this.classList.remove('font-medium', 'text-gray-500', 'border-transparent');
            this.classList.add('font-semibold', 'text-primary', 'border-primary', 'bg-emerald-50/30');

            // Find status from URL
            const url = new URL(this.href);
            const status = url.searchParams.get('status') || '';
            
            // Update hidden status input
            let statusInput = form.querySelector('input[name="status"]');
            if (!statusInput) {
                statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                form.appendChild(statusInput);
            }
            statusInput.value = status;

            // Trigger Search
            loadTableData();
        });
    });

</script>

@endsection
