@extends('backend.layouts.app')

@section('title', 'Bảng điều khiển thương mại điện tử - Happy Tea')

@section('content')
<!-- PHẦN 1: TIÊU ĐỀ TRANG (Hiển thị lời chào và tiêu đề) -->
<div class="mb-8" data-purpose="page-title">
    <h1 class="text-2xl font-bold text-gray-900">Bảng điều khiển thương mại điện tử</h1>
    <p class="text-sm text-gray-500">Đây là những gì đang diễn ra tại doanh nghiệp của bạn hiện nay.</p>
</div>

<!-- PHẦN 2: THỐNG KÊ NHANH (3 THẺ THÔNG TIN TRẠNG THÁI Ở TRÊN CÙNG) -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" data-purpose="top-stats">
    <!-- Khối 1: Hiển thị số Đơn hàng mới đang chờ xác nhận -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
            <svg class="w-6 h-6" fill="currentColor" viewbox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"></path></svg>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-gray-900">{{ $pendingProcessingOrders }} đơn hàng mới</span>
            </div>
            <span class="text-xs text-gray-400">Đang chờ xử lý</span>
        </div>
    </div>
    
    <!-- Khối 2: Hiển thị số Đơn hàng đang được xử lý/giao hàng -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600">
            <svg class="w-6 h-6" fill="currentColor" viewbox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"></path></svg>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-gray-900">{{ $processingOrders }} đơn hàng</span>
            </div>
            <span class="text-xs text-gray-400">Đang giao hàng</span>
        </div>
    </div>
    
    <!-- Khối 3: Hiển thị số Sản phẩm đang bị ẩn khỏi hệ thống -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600">
            <svg class="w-6 h-6" fill="currentColor" viewbox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path></svg>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <span class="text-lg font-bold text-gray-900">{{ $hiddenProductsCount }} sản phẩm</span>
            </div>
            <span class="text-xs text-gray-400">Đang ẩn</span>
        </div>
    </div>
</div>

<!-- PHẦN 3: KHU VỰC BIỂU ĐỒ CHÍNH VÀ CÁC CHỈ SỐ DOANH THU -->
<div class="grid grid-cols-12 gap-6 mb-8">
    <!-- Cột bên trái (Chiếm 8/12 không gian): Biểu đồ Cột hiển thị Tổng doanh thu -->
    <div class="col-span-12 lg:col-span-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col" data-purpose="revenue-chart-card">
        <div class="flex flex-col sm:flex-row justify-between items-start mb-6 gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-bold text-gray-900">Tổng doanh số</h3>
                    <span id="stat-revenue-trend" class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statsData['monthly']['revenue']['trend']['bg'] }} {{ $statsData['monthly']['revenue']['trend']['color'] }}">{{ $statsData['monthly']['revenue']['trend']['text'] }}</span>
                </div>
                <p class="text-sm text-gray-400 mt-1">Đã nhận được thanh toán qua tất cả các kênh.</p>
                <div class="mt-2 flex items-baseline gap-1">
                    <h2 class="text-2xl font-black text-blue-600" id="stat-revenue-value">{{ $statsData['monthly']['revenue']['value'] }}</h2>
                    <span class="text-xs text-gray-400">trong <span id="stat-revenue-period">Tháng này</span></span>
                </div>
            </div>
            <div class="relative flex flex-wrap items-center gap-2" id="chart-toggles">
                <button data-type="weekly" class="px-3 py-1 text-sm bg-primary/10 text-primary font-bold rounded-md transition-colors">Tuần này</button>
                <button data-type="monthly" class="px-3 py-1 text-sm text-gray-500 hover:bg-gray-100 rounded-md transition-colors">Tháng này</button>
                <button data-type="yearly" class="px-3 py-1 text-sm text-gray-500 hover:bg-gray-100 rounded-md transition-colors">Năm nay</button>
            </div>
        </div>
        
        <div class="flex-1 w-full flex items-end justify-between relative mt-4 min-h-[250px]" id="revenue-chart" data-chart="{{ json_encode($chartData) }}">
             <!-- Khu vực vẽ cột biểu đồ -->
            <div class="w-full h-full flex items-end justify-between gap-1 md:gap-4" id="chart-bars-container"></div>
        </div>
        <div class="flex justify-between w-full mt-4 text-xs text-gray-400" id="chart-y-axis">
            <!-- Khu vực vẽ cột biểu đồ -->
        </div>
    </div>
    
    <!-- Cột bên phải (Chiếm 4/12 không gian): Các thẻ chỉ số phụ (Tổng đơn, Khách mới, Chi phí, Lợi nhuận) -->
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
        <!-- Total Orders -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700">Tổng số đơn hàng 
                        <span id="stat-orders-trend" class="ml-1 text-[10px] {{ $statsData['monthly']['orders']['trend']['bg'] }} {{ $statsData['monthly']['orders']['trend']['color'] }} px-1.5 py-0.5 rounded-full">{{ $statsData['monthly']['orders']['trend']['text'] }}</span>
                    </h4>
                    <p class="text-[10px] text-gray-400" id="stat-orders-period">Tháng này</p>
                </div>
                <span id="stat-orders-value" class="text-lg font-bold text-gray-900">{{ $statsData['monthly']['orders']['value'] }}</span>
            </div>
            
            <div id="small-orders-chart" class="flex items-end justify-between gap-1 h-16 mt-auto w-full px-2">
                <!--  Khu vực vẽ biểu đồ Tổng số đơn -->
            </div>
        </div>
        
        <!-- Thẻ 2: Khách hàng mới đăng ký -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700">Khách hàng mới 
                        <span id="stat-customers-trend" class="ml-1 text-[10px] {{ $statsData['monthly']['customers']['trend']['bg'] }} {{ $statsData['monthly']['customers']['trend']['color'] }} px-1.5 py-0.5 rounded-full">{{ $statsData['monthly']['customers']['trend']['text'] }}</span>
                    </h4>
                    <p class="text-[10px] text-gray-400" id="stat-customers-period">Tháng này</p>
                </div>
                <span id="stat-customers-value" class="text-lg font-bold text-gray-900">{{ $statsData['monthly']['customers']['value'] }}</span>
            </div>
            <div id="small-customers-chart" class="flex items-end justify-between gap-1 h-16 mt-auto w-full px-2">
                <!-- Khu vực vẽ biểu đồ Số lượng khách -->
            </div>
        </div>
        
        <!-- Thẻ 3: Tổng chi phí nhập vật tư -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700">Chi phí vật tư 
                        <span id="stat-expenses-trend" class="ml-1 text-[10px] {{ $statsData['monthly']['expenses']['trend']['bg'] }} {{ $statsData['monthly']['expenses']['trend']['color'] }} px-1.5 py-0.5 rounded-full">{{ $statsData['monthly']['expenses']['trend']['text'] }}</span>
                    </h4>
                    <p class="text-[10px] text-gray-400" id="stat-expenses-period">Tháng này</p>
                </div>
                <span id="stat-expenses-value" class="text-lg font-bold text-red-600">{{ $statsData['monthly']['expenses']['value'] }}</span>
            </div>
        </div>
        
        <!-- Thẻ 4: Lợi nhuận (Doanh thu - Chi phí) -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700">Lợi nhuận gộp 
                        <span id="stat-profit-trend" class="ml-1 text-[10px] {{ $statsData['monthly']['profit']['trend']['bg'] }} {{ $statsData['monthly']['profit']['trend']['color'] }} px-1.5 py-0.5 rounded-full">{{ $statsData['monthly']['profit']['trend']['text'] }}</span>
                    </h4>
                    <p class="text-[10px] text-gray-400" id="stat-profit-period">Tháng này</p>
                </div>
                <span id="stat-profit-value" class="text-lg font-bold text-emerald-600">{{ $statsData['monthly']['profit']['value'] }}</span>
            </div>
        </div>
        
        <!-- Kho giấu dữ liệu thống kê (Tuần/Tháng/Năm) để Javascript lấy ra dùng -->
        <div id="stat-cards-container" data-stats="{{ json_encode($statsData) }}" class="hidden"></div>
    </div>
</div>

<!-- PHẦN 4: HAI BIỂU ĐỒ TRÒN (DONUT CHART) THỐNG KÊ TỶ LỆ Ở BÊN DƯỚI -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Biểu đồ 1: Tỷ lệ Phương thức thanh toán (Tiền mặt vs MoMo) -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
        <div>
            <h4 class="text-sm font-semibold text-gray-700">Phương thức thanh toán</h4>
            <p class="text-[10px] text-gray-400 mb-4">Tất cả thời gian</p>
        </div>
        <div class="flex items-center justify-around mt-4">
            <div class="relative w-24 h-24 shrink-0">
                <svg class="w-full h-full -rotate-90" viewbox="0 0 36 36">
                    <path class="text-gray-100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="4"></path>
                    <path class="text-blue-500" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="{{ $paymentStats['cod']['percent'] }}, 100" stroke-linecap="round" stroke-width="4"></path>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center font-bold text-gray-800">{{ $paymentStats['cod']['percent'] }}%</div>
            </div>
            <div class="space-y-2 text-[10px] flex-1 pl-6">
                <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 shrink-0"></span> Tiền mặt (COD) <span class="ml-auto font-semibold">{{ $paymentStats['cod']['count'] }} đơn</span></div>
                <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-gray-200 shrink-0"></span> Ví điện tử (MoMo) <span class="ml-auto font-semibold">{{ $paymentStats['momo']['count'] }} đơn</span></div>
            </div>
        </div>
    </div>
    
    <!-- Biểu đồ 2: Tỷ lệ Kênh đặt hàng (Giao tận nơi vs Lấy tại quán) -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
        <div>
            <h4 class="text-sm font-semibold text-gray-700">Kênh đặt hàng</h4>
            <p class="text-[10px] text-gray-400 mb-4">Tất cả thời gian</p>
        </div>
        <div class="flex items-center justify-around mt-4">
            <div class="relative w-24 h-24 shrink-0">
                <svg class="w-full h-full -rotate-90" viewbox="0 0 36 36">
                    <path class="text-gray-100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="4"></path>
                    <path class="text-blue-500" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="{{ $deliveryStats['delivery']['percent'] }}, 100" stroke-linecap="round" stroke-width="4"></path>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center font-bold text-gray-800">{{ $deliveryStats['delivery']['percent'] }}%</div>
            </div>
            <div class="space-y-2 text-[10px] flex-1 pl-6">
                <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-blue-500 shrink-0"></span> Giao hàng tận nơi <span class="ml-auto font-semibold">{{ $deliveryStats['delivery']['count'] }} đơn</span></div>
                <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-gray-200 shrink-0"></span> Lấy tại quán <span class="ml-auto font-semibold">{{ $deliveryStats['pickup']['count'] }} đơn</span></div>
            </div>
        </div>
    </div>
</div>

<!-- PHẦN 5: BẢNG DANH SÁCH 5 ĐÁNH GIÁ (REVIEW) MỚI NHẤT CỦA KHÁCH HÀNG -->
<section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8" id="recent-reviews-section">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="p-6 border-b border-gray-50 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Đánh giá mới nhất</h3>
            <p class="text-sm text-gray-400">Đã nhận được thanh toán qua tất cả các kênh.</p>
        </div>

    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[900px]">
            <thead class="bg-gray-50 text-[10px] uppercase text-gray-500 font-bold border-b border-gray-100">
                <tr>
                    <th class="px-4 py-3">Sản phẩm</th>
                    <th class="px-4 py-3">Khách hàng</th>
                    <th class="px-4 py-3">Đánh giá</th>
                    <th class="px-4 py-3">Nội dung</th>
                    <th class="px-4 py-3">Thời gian</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm" id="reviews-tbody">
                @forelse($latestReviews as $review)
                <tr class="hover:bg-gray-50 transition-colors group" id="review-row-{{ $review->id }}">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 shrink-0 bg-white rounded border border-gray-100 overflow-hidden flex items-center justify-center p-1">
                                @if($review->product)
                                    <img src="{{ asset('images/' . $review->product->image) }}" class="w-full h-full object-contain" onerror="this.src='https://via.placeholder.com/150'">
                                @else
                                    <span class="material-symbols-outlined text-gray-400">inventory_2</span>
                                @endif
                            </div>
                            @if($review->product)
                                <a href="{{ route('product.show', $review->product->slug) }}" target="_blank" class="text-xs text-blue-500 hover:text-blue-700 font-medium max-w-[200px] line-clamp-2 hover:underline">{{ $review->product->name }}</a>
                            @else
                                <span class="text-xs text-blue-500 font-medium max-w-[200px] line-clamp-2">Sản phẩm đã xóa</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-[10px] font-bold text-blue-600">
                                {{ strtoupper(substr($review->user?->name ?? '?', 0, 1)) }}
                            </div>
                            <span class="text-xs text-gray-700 whitespace-nowrap font-medium">{{ $review->user?->name ?? 'Khách' }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex text-amber-400 text-xs shrink-0">
                            @for($i = 0; $i < $review->rating; $i++) <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg> @endfor
                            @for($i = $review->rating; $i < 5; $i++) <svg class="w-3.5 h-3.5 text-gray-300 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg> @endfor
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <p class="text-xs text-gray-600 line-clamp-2 max-w-[250px]" title="{{ $review->comment }}">{{ $review->comment }}</p>
                    </td>
                    <td class="px-4 py-4 text-xs font-medium text-gray-700 whitespace-nowrap">
                        {{ $review->created_at ? $review->created_at->diffForHumans() : 'Không rõ' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">Chưa có đánh giá nào</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-4 border-t border-gray-50 flex justify-center">
        <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1 transition-colors">
            Xem tất cả đánh giá
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </a>
    </div>
</section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/backend/dashboard.js') }}"></script>
@endpush
