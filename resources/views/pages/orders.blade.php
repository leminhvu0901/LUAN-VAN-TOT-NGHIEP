@extends('layouts.app')

@section('body_class', 'profile-body')

@push('scripts')
    <script src="{{ asset('js/orders.js') }}"></script>
@endpush

@section('content')
<div class="min-h-screen md:flex bg-background text-on-surface md:text-on-background font-body-md selection:bg-primary-container selection:text-on-primary-container relative pb-24 md:pb-0">
    
    <!-- MOBILE: TopAppBar -->
    <header class="md:hidden fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant flex items-center px-4 h-16 shadow-sm">
        <a href="{{ url()->previous() }}" class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-primary-container/10 active:scale-95 transition-transform">
            <span class="material-symbols-outlined text-primary">arrow_back</span>
        </a>
        <h1 class="ml-2 font-headline-md text-headline-md-mobile text-primary">Đơn hàng của tôi</h1>
    </header>

    <!-- DESKTOP: SideNavBar -->
    <aside class="hidden md:flex w-[280px] flex-shrink-0 bg-tertiary-fixed border-r border-outline-variant flex-col py-stack_lg">
        <div class="px-6 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-primary bg-white">
                    @if(Auth::user()->avatar)
                        <img alt="User profile avatar" class="w-full h-full object-cover" src="{{ asset('images/avatars/' . Auth::user()->avatar) }}">
                    @else
                        <img alt="User profile avatar" class="w-full h-full object-cover" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=006e01&color=fff">
                    @endif
                </div>
                <div>
                    <h3 class="font-label-md text-label-md text-on-surface">{{ Auth::user()->name }}</h3>
                    <p class="text-xs text-on-surface-variant">
                        @switch(Auth::user()->membership_level ?? 'new')
                            @case('silver') Thành viên hạng Bạc @break
                            @case('gold') Thành viên hạng Vàng @break
                            @case('diamond') Thành viên Kim Cương @break
                            @default Thành viên Mới
                        @endswitch
                    </p>
                </div>
            </div>
        </div>
        <nav class="flex-1">
            <a class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('profile') }}">
                <span class="material-symbols-outlined">person</span>
                Thông tin tài khoản
            </a>
            <a class="bg-surface-container-highest text-primary border-l-4 border-primary px-6 py-3 flex items-center gap-3 transition-all duration-150 font-label-md text-label-md" href="{{ route('orders') }}">
                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">shopping_bag</span>
                Đơn hàng của tôi
            </a>
            <a class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('profile') }}#address">
                <span class="material-symbols-outlined">location_on</span>
                Sổ địa chỉ
            </a>
            <a class="text-on-surface-variant hover:bg-surface-container-low px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('profile') }}#password">
                <span class="material-symbols-outlined">lock</span>
                Đổi mật khẩu
            </a>
            <a class="text-error hover:bg-error-container/20 px-6 py-3 flex items-center gap-3 transition-all duration-200 font-label-md text-label-md" href="{{ route('logout') }}">
                <span class="material-symbols-outlined">logout</span>
                Đăng xuất
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 pt-20 md:pt-stack_lg px-4 md:px-stack_lg pb-stack_lg max-w-md md:max-w-full mx-auto md:mx-0 w-full relative z-10">
        <div class="w-full">
            @if(session('success'))
            <div class="bg-secondary-container text-on-secondary-container border border-outline-variant px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                <span class="font-bold text-sm">{{ session('success') }}</span>
            </div>
            @endif
            
            <!-- DESKTOP: Heading -->
            <div class="hidden md:block mb-stack_lg">
                <h1 class="font-headline-lg text-headline-lg text-on-surface mb-2">Đơn hàng của tôi</h1>
                <p class="font-body-md text-body-md text-on-surface-variant">Xem lại lịch sử các đơn hàng bạn đã đặt</p>
            </div>
            
            <!-- Status Filter Bar (Unified) -->
            <div class="flex gap-2 md:gap-4 mb-6 md:mb-8 overflow-x-auto pb-2 hide-scrollbar sticky md:static top-16 z-10 bg-background/95 md:bg-transparent backdrop-blur-sm md:backdrop-blur-none pt-2 md:pt-0">
                <a href="{{ route('orders') }}" class="px-5 md:px-6 py-2 {{ !$status ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Tất cả</a>
                <a href="{{ route('orders', ['status' => 'pending']) }}" class="px-5 md:px-6 py-2 {{ $status == 'pending' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Chờ xác nhận</a>
                <a href="{{ route('orders', ['status' => 'confirmed']) }}" class="px-5 md:px-6 py-2 {{ $status == 'confirmed' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Đã xác nhận</a>
                <a href="{{ route('orders', ['status' => 'shipping']) }}" class="px-5 md:px-6 py-2 {{ $status == 'shipping' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Đang giao</a>
                <a href="{{ route('orders', ['status' => 'completed']) }}" class="px-5 md:px-6 py-2 {{ $status == 'completed' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Hoàn thành</a>
                <a href="{{ route('orders', ['status' => 'cancelled']) }}" class="px-5 md:px-6 py-2 {{ $status == 'cancelled' ? 'bg-primary text-white md:shadow-sm' : 'bg-white border border-outline-variant text-on-surface-variant md:hover:border-primary md:hover:text-primary' }} rounded-full font-label-md text-xs md:text-label-md whitespace-nowrap transition-all shadow-sm md:shadow-none">Đã hủy</a>
            </div>

            <!-- Orders Container (Unified) -->
            <div class="space-y-4 md:space-y-6">
                @forelse($orders as $order)
                <div class="bg-white rounded-2xl md:rounded-xl border border-outline-variant p-4 md:p-6 shadow-sm md:shadow-none md:order-card-hover transition-all duration-300">
                    
                    <!-- Card Header -->
                    <div class="flex justify-between items-start mb-3 md:mb-6 border-b border-gray-100 md:border-outline-variant pb-3 md:pb-4">
                        <div>
                            <div class="flex flex-col md:flex-row md:items-center gap-0.5 md:gap-3 mb-0 md:mb-1">
                                <span class="font-bold text-gray-800 md:text-primary text-sm md:text-base">#{{ $order->order_code ?? 'HPY-' . $order->id }}</span>
                                <div class="flex items-center gap-2 mt-0.5 md:mt-0">
                                    <span class="hidden md:inline-block w-1.5 h-1.5 rounded-full bg-outline-variant"></span>
                                    <span class="text-gray-500 md:text-on-surface-variant text-[11px] md:text-sm">{{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y • H:i') }}</span>
                                </div>
                            </div>
                            <div class="hidden md:flex items-center gap-2 mt-1">
                                <span class="material-symbols-outlined text-sm text-on-surface-variant">local_shipping</span>
                                <span class="text-sm text-on-surface-variant">
                                    @if($order->delivery_type == 'delivery')
                                        Giao hàng tận nơi
                                    @else
                                        Nhận tại cửa hàng
                                    @endif
                                </span>
                            </div>
                        </div>
                        
                        @switch($order->status)
                            @case('pending')
                                <span class="px-2.5 py-1 md:px-4 md:py-1.5 bg-yellow-100 text-yellow-800 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Chờ xác nhận</span>
                                @break
                            @case('confirmed')
                                <span class="px-2.5 py-1 md:px-4 md:py-1.5 bg-blue-100 text-blue-800 rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Đã xác nhận</span>
                                @break
                            @case('shipping')
                                <span class="px-2.5 py-1 md:px-4 md:py-1.5 bg-green-100 md:bg-primary-container/20 text-green-800 md:text-primary rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Đang giao</span>
                                @break
                            @case('completed')
                                <span class="px-2.5 py-1 md:px-4 md:py-1.5 bg-secondary-container text-on-secondary-container rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Hoàn thành</span>
                                @break
                            @case('cancelled')
                                <span class="px-2.5 py-1 md:px-4 md:py-1.5 bg-error-container text-on-error-container rounded-full text-[10px] md:text-xs font-bold uppercase tracking-wider">Đã hủy</span>
                                @break
                        @endswitch
                    </div>
                    
                    <!-- Card Body -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                        <!-- Product Info -->
                        <div class="flex items-center gap-3 md:gap-4 mb-3 md:mb-0 flex-1 min-w-0">
                            <!-- Mobile Image -->
                            <div class="md:hidden w-16 h-16 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0 border border-outline-variant">
                                @if($order->items->first())
                                    <img src="{{ asset('images/' . $order->items->first()->product_image) }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'" alt="Product" class="w-full h-full object-cover">
                                @else
                                    <img src="{{ asset('images/products/placeholder.jpg') }}" alt="Product" class="w-full h-full object-cover">
                                @endif
                            </div>
                            
                            <!-- Desktop Images -->
                            <div class="hidden md:flex -space-x-3 overflow-hidden">
                                @foreach($order->items->take(2) as $item)
                                    <img alt="{{ $item->product_name }}" class="inline-block h-16 w-16 rounded-lg ring-4 ring-white object-cover bg-surface-container-low border border-outline-variant" src="{{ asset('images/' . $item->product_image) }}" onerror="this.src='{{ asset('images/products/placeholder.jpg') }}'">
                                @endforeach
                                @if($order->items->count() > 2)
                                    <div class="h-16 w-16 rounded-lg ring-4 ring-white bg-surface-container-highest flex items-center justify-center text-xs font-bold text-primary border border-outline-variant">+{{ $order->items->count() - 2 }}</div>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 md:text-on-surface text-base truncate">
                                    {{ $order->items->first()->product_name ?? 'Sản phẩm đồ uống' }}
                                    <span class="hidden md:inline">
                                        @if($order->items->count() > 1)
                                            và {{ $order->items->count() - 1 }} sản phẩm khác
                                        @endif
                                    </span>
                                </h3>
                                <!-- Mobile variations -->
                                <p class="md:hidden text-gray-600 text-xs mt-0.5">
                                    @if($order->items->first())
                                        x{{ $order->items->first()->quantity }} • Size {{ $order->items->first()->size_name ?? 'M' }}
                                        @if($order->items->first()->sugar_level) • {{ $order->items->first()->sugar_level }} đường @endif
                                    @endif
                                </p>
                                @if($order->items->count() > 1)
                                    <p class="md:hidden text-gray-500 text-xs mt-1 font-medium">+ {{ $order->items->count() - 1 }} sản phẩm khác</p>
                                @endif
                                <!-- Desktop payment method -->
                                <p class="hidden md:block text-sm text-on-surface-variant">
                                    @if($order->payment_status == 'paid')
                                        Đã thanh toán trực tuyến
                                    @else
                                        Thanh toán khi nhận hàng (COD)
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Card Footer/Actions -->
                        <div class="flex justify-between md:justify-end items-center gap-2 md:gap-8 border-t border-gray-100 md:border-t-0 pt-3 md:pt-0 text-left md:text-right shrink-0">
                            <div>
                                <p class="text-[10px] md:text-xs text-gray-600 md:text-on-surface-variant uppercase font-semibold md:font-bold mb-0 md:mb-1 whitespace-nowrap">Tổng cộng</p>
                                <p class="text-base md:text-xl font-bold md:font-extrabold text-primary whitespace-nowrap">{{ number_format($order->final_amount, 0, ',', '.') }}đ</p>
                            </div>
                            <div class="flex gap-2 md:gap-3 shrink-0">
                                <button class="px-4 py-1.5 md:px-6 md:py-2.5 border border-primary text-primary font-bold text-xs md:text-base rounded-full md:rounded-lg hover:bg-primary/5 transition-all active:scale-95 whitespace-nowrap">Chi tiết</button>
                                <button class="hidden md:inline-block px-6 py-2.5 bg-primary text-white font-bold rounded-lg shadow-sm hover:shadow-md transition-all active:scale-95 whitespace-nowrap">Mua lại</button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <!-- Empty State Placeholder -->
                <div class="flex flex-col items-center justify-center py-20 text-center bg-white rounded-2xl md:rounded-xl border border-outline-variant p-6" id="empty-state">
                    <span class="material-symbols-outlined text-6xl text-outline-variant mb-4">shopping_cart_off</span>
                    <h3 class="text-lg md:text-xl font-bold text-on-background mb-2">Chưa có đơn hàng nào</h3>
                    <p class="text-sm md:text-base text-on-surface-variant mb-6">Có vẻ như bạn chưa đặt món đồ uống nào từ chúng tôi.</p>
                    <a href="{{ url('/products') }}" class="bg-primary text-white px-6 py-2.5 md:px-8 md:py-3 rounded-full font-bold shadow-sm md:shadow-md hover:shadow-lg transition-transform active:scale-95">Bắt đầu mua sắm</a>
                </div>
                @endforelse
            </div>

            <!-- Pagination (Unified) -->
            @if($orders->lastPage() > 1)
            <div class="mt-8 md:mt-12 flex justify-center items-center gap-2 pb-6 md:pb-0">
                @if($orders->onFirstPage())
                    <span class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant opacity-50 cursor-not-allowed">
                        <span class="material-symbols-outlined text-lg md:text-base">chevron_left</span>
                    </span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}" class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant hover:bg-surface-container-low active:bg-surface-container-low transition-all">
                        <span class="material-symbols-outlined text-lg md:text-base">chevron_left</span>
                    </a>
                @endif

                @foreach ($orders->getUrlRange(max(1, $orders->currentPage() - 1), min($orders->lastPage(), $orders->currentPage() + 1)) as $page => $url)
                    @if ($page == $orders->currentPage())
                        <span class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full bg-primary text-white text-sm md:text-base font-bold">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant text-sm md:text-base hover:bg-surface-container-low active:bg-surface-container-low transition-all">{{ $page }}</a>
                    @endif
                @endforeach

                @if($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}" class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant hover:bg-surface-container-low active:bg-surface-container-low transition-all">
                        <span class="material-symbols-outlined text-lg md:text-base">chevron_right</span>
                    </a>
                @else
                    <span class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center rounded-full border border-outline-variant text-on-surface-variant opacity-50 cursor-not-allowed">
                        <span class="material-symbols-outlined text-lg md:text-base">chevron_right</span>
                    </span>
                @endif
            </div>
            @endif

        </div>
    </main>

    <!-- MOBILE: BottomNavBar -->
    <nav class="md:hidden fixed bottom-0 left-0 w-full z-50 bg-surface rounded-t-xl border-t border-outline-variant shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex justify-around items-center px-2 py-3 pb-safe">
        <a href="{{ url('/') }}" class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined">home</span>
            <span class="font-label-md text-[11px] mt-1">Trang chủ</span>
        </a>
        <a href="{{ url('/products') }}" class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined">eco</span>
            <span class="font-label-md text-[11px] mt-1">Sản phẩm</span>
        </a>
        <!-- Active Tab: Đơn hàng -->
        <a href="{{ route('orders') }}" class="flex flex-col items-center justify-center bg-primary-container/10 text-primary-container rounded-xl px-4 py-1.5 active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
            <span class="font-label-md text-[12px] font-bold mt-0.5">Đơn hàng</span>
        </a>
        <a href="{{ route('profile') }}" class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary active:scale-90 transition-all duration-200">
            <span class="material-symbols-outlined">person</span>
            <span class="font-label-md text-[11px] mt-1">Tài khoản</span>
        </a>
    </nav>
</div>
@endsection
