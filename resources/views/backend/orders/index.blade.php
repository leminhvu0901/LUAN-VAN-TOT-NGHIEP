@extends('backend.layouts.app')

@section('title', 'Danh sách Đơn hàng - Admin')

@section('content')
    <div class="flex flex-col gap-6 h-full pb-4">

        {{-- Phần tiêu đề trang và nút chức năng xuất báo cáo --}}
        <div
            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm shrink-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Danh sách Đơn hàng</h1>
                <p class="text-sm text-gray-500">Theo dõi và quản lý các đơn đặt hàng từ hệ thống Happy Tea.</p>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <a href="{{ route('admin.orders.export', array_filter([
        'status' => request('status'),
        'search' => request('search'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
    ])) }}" id="export-btn"
                    class="flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors flex-1 sm:flex-none">
                    <span class="material-symbols-outlined text-[20px]">download</span>
                    Xuất báo cáo
                </a>
            </div>
        </div>

        {{-- Khu vực chính chứa các Tab trạng thái, Form bộ lọc và Bảng dữ liệu --}}
        <div
            class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col flex-1 overflow-hidden min-h-[600px]">

            {{-- Các Tab phân loại đơn hàng theo trạng thái --}}
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

            {{-- Form tìm kiếm và lọc dữ liệu (theo từ khóa và khoảng thời gian) --}}
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 shrink-0">
                <form id="search-form" action="{{ route('admin.orders.index') }}" method="GET"
                    class="flex flex-col sm:flex-row gap-4 items-end">
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif

                    <div class="flex-1 w-full">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tìm
                            kiếm</label>
                        <div class="relative">
                            <span
                                class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">search</span>
                            <input type="text" name="search" id="search-input" value="{{ request('search') }}"
                                placeholder="Mã đơn, Tên, SĐT khách hàng..."
                                class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                        </div>
                    </div>

                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Từ
                            ngày</label>
                        <input type="date" name="date_from" id="date-from-input" value="{{ request('date_from') }}"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>

                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Đến
                            ngày</label>
                        <input type="date" name="date_to" id="date-to-input" value="{{ request('date_to') }}"
                            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all">
                    </div>

                    <div class="flex gap-2 w-full sm:w-auto">
                        <button type="button" onclick="loadTableData()"
                            class="flex-1 sm:flex-none px-4 py-2 bg-gray-800 text-white font-medium text-sm rounded-lg hover:bg-gray-900 transition-colors">
                            Lọc
                        </button>
                        @if(request('search') || request('date_from') || request('date_to'))
                            <a href="{{ route('admin.orders.index', request('status') ? ['status' => request('status')] : []) }}"
                                class="flex items-center justify-center px-3 py-2 bg-white border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors"
                                title="Xóa bộ lọc">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- Khu vực chứa bảng dữ liệu. Nội dung bên trong div này sẽ được thay thế qua AJAX khi tìm kiếm/lọc --}}
            <div id="table-container" class="flex-1 flex flex-col min-h-0 relative">
                {{-- Biểu tượng Loading hiển thị lên khi đang gửi request AJAX --}}
                <div id="table-loader" class="absolute inset-0 bg-white/60 z-20 hidden items-center justify-center">
                    <div class="w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div class="flex-1 overflow-x-auto custom-scrollbar relative">
                    @include('backend.orders.table')
                </div>
            </div>
        </div>

        {{-- Khu vực các thẻ thống kê nhanh hiển thị ở cuối trang --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 shrink-0 pb-10">

            {{-- Card 1: Đơn trong ngày --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center mb-4 border border-blue-100">
                    <span class="material-symbols-outlined text-blue-500">shopping_bag</span>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ĐƠN TRONG NGÀY</p>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $stats['today_orders']['value'] }}</h3>
                <div
                    class="flex items-center text-xs font-medium {{ $stats['today_orders']['is_up'] ? 'text-success' : 'text-danger' }}">
                    <span
                        class="material-symbols-outlined text-[14px]">{{ $stats['today_orders']['is_up'] ? 'trending_up' : 'trending_down' }}</span>
                    <span class="ml-1">{{ $stats['today_orders']['trend'] }}</span>
                </div>
            </div>

            {{-- Card 2: Doanh thu ngày --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <div
                    class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center mb-4 border border-emerald-100">
                    <span class="material-symbols-outlined text-emerald-500">payments</span>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">DOANH THU NGÀY</p>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $stats['today_revenue']['value'] }}</h3>
                <div
                    class="flex items-center text-xs font-medium {{ $stats['today_revenue']['is_up'] ? 'text-success' : 'text-danger' }}">
                    <span
                        class="material-symbols-outlined text-[14px]">{{ $stats['today_revenue']['is_up'] ? 'trending_up' : 'trending_down' }}</span>
                    <span class="ml-1">{{ $stats['today_revenue']['trend'] }}</span>
                </div>
            </div>

            {{-- Card 3: Đang chờ xử lý --}}
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center mb-4 border border-amber-100">
                    <span class="material-symbols-outlined text-amber-500">assignment_late</span>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ĐANG CHỜ XỬ LÝ</p>
                <h3
                    class="text-2xl font-bold {{ $stats['pending_orders']['value'] > 0 ? 'text-amber-600' : 'text-gray-900' }} mb-2">
                    {{ $stats['pending_orders']['value'] }}
                </h3>
                <div
                    class="flex items-center text-xs font-medium {{ $stats['pending_orders']['value'] > 0 ? 'text-amber-500' : 'text-gray-400' }}">
                    @if($stats['pending_orders']['value'] > 0)
                        <span class="material-symbols-outlined text-[14px] mr-1">notification_important</span>
                    @endif
                    <span>{{ $stats['pending_orders']['trend'] }}</span>
                </div>
            </div>

            {{-- Card 4: Đơn bị hủy (tháng) --}}
            @php $cancelIsGood = $stats['cancelled_orders']['is_up']; @endphp
            <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
                <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center mb-4 border border-red-100">
                    <span class="material-symbols-outlined text-red-500">cancel</span>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ĐƠN BỊ HỦY (THÁNG)</p>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $stats['cancelled_orders']['value'] }}</h3>
                {{-- is_up = true nghĩa là ít hủy hơn tháng trước → màu xanh (tốt) --}}
                <div class="flex items-center text-xs font-medium {{ $cancelIsGood ? 'text-success' : 'text-danger' }}">
                    <span
                        class="material-symbols-outlined text-[14px]">{{ $cancelIsGood ? 'trending_down' : 'trending_up' }}</span>
                    <span class="ml-1">{{ $stats['cancelled_orders']['trend'] }}</span>
                </div>
            </div>

        </div>

    </div>



    @push('scripts')
        <script src="{{ asset('js/backend/orders-index.js') }}"></script>
    @endpush

@endsection