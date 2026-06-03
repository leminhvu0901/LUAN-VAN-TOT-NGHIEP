@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen pb-24 font-sans">
    <div class="max-w-md mx-auto bg-gray-50 min-h-screen relative">
        
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-4 bg-gray-50 sticky top-0 z-10">
            <a href="{{ url('/') }}" class="text-green-700 p-2">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-green-700">Đơn hàng của tôi</h1>
            <a href="{{ route('profile') }}" class="p-2 text-green-700">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>

        {{-- Tabs --}}
        <div class="flex items-center gap-2 px-4 py-2 overflow-x-auto hide-scrollbar sticky top-[68px] z-10 bg-gray-50 pb-4">
            <button class="bg-green-600 text-white font-semibold text-sm px-5 py-2.5 rounded-full whitespace-nowrap">Đang xử lý</button>
            <button class="text-gray-600 font-semibold text-sm px-5 py-2.5 rounded-full whitespace-nowrap">Đang giao</button>
            <button class="text-gray-600 font-semibold text-sm px-5 py-2.5 rounded-full whitespace-nowrap">Đã hoàn thành</button>
        </div>

        {{-- Order List --}}
        <div class="px-4 space-y-4 mt-2">
            
            {{-- Order Card 1 --}}
            <div class="bg-white rounded-2xl p-4 border border-green-200/60 shadow-sm">
                {{-- Header --}}
                <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-3">
                    <div>
                        <p class="font-bold text-gray-800 text-sm">#HD-88291</p>
                        <p class="text-gray-500 text-xs mt-0.5">12/10/2023 • 14:30</p>
                    </div>
                    <span class="bg-orange-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">ĐANG XỬ LÝ</span>
                </div>
                
                {{-- Content --}}
                <div class="flex gap-3 mb-3">
                    <div class="w-16 h-16 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0">
                        <img src="{{ asset('images/products/tra-dao-cam-sa.jpg') }}" onerror="this.src='https://images.unsplash.com/photo-1558857563-b37102e99757?auto=format&fit=crop&w=200&q=80'" alt="Product" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Trà Đào Cam Sả</h3>
                        <p class="text-gray-600 text-sm mt-0.5">x1 • Size L • Ít đường</p>
                        <p class="text-gray-600 text-sm">+ Cà phê sữa đá (x1)</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-between items-center border-t border-gray-100 pt-3">
                    <div>
                        <p class="text-gray-600 text-xs">Tổng cộng</p>
                        <p class="text-green-700 font-bold text-lg">85.000đ</p>
                    </div>
                    <button class="border-2 border-green-700 text-green-700 font-bold text-sm px-4 py-1.5 rounded-full hover:bg-green-50 transition">
                        Xem chi tiết
                    </button>
                </div>
            </div>

            {{-- Order Card 2 --}}
            <div class="bg-white rounded-2xl p-4 border border-green-200/60 shadow-sm">
                {{-- Header --}}
                <div class="flex justify-between items-start border-b border-gray-100 pb-3 mb-3">
                    <div>
                        <p class="font-bold text-gray-800 text-sm">#HD-88285</p>
                        <p class="text-gray-500 text-xs mt-0.5">11/10/2023 • 09:15</p>
                    </div>
                    <span class="bg-green-100 text-gray-700 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">ĐANG GIAO</span>
                </div>
                
                {{-- Content --}}
                <div class="flex gap-3 mb-3">
                    <div class="w-16 h-16 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0">
                        <img src="{{ asset('images/products/ca-phe-muoi.jpg') }}" onerror="this.src='https://images.unsplash.com/photo-1511920170033-f8396924c348?auto=format&fit=crop&w=200&q=80'" alt="Product" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Cà phê muối</h3>
                        <p class="text-gray-600 text-sm mt-0.5">x2 • Size M</p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-between items-center border-t border-gray-100 pt-3">
                    <div>
                        <p class="text-gray-600 text-xs">Tổng cộng</p>
                        <p class="text-green-700 font-bold text-lg">70.000đ</p>
                    </div>
                    <button class="border-2 border-green-700 text-green-700 font-bold text-sm px-4 py-1.5 rounded-full hover:bg-green-50 transition">
                        Xem chi tiết
                    </button>
                </div>
            </div>

        </div>
        
        {{-- Bottom Navigation --}}
        <div class="fixed bottom-0 left-0 w-full bg-gray-50 border-t border-gray-200 z-20 pb-safe">
            <div class="max-w-md mx-auto flex justify-between items-end px-6 py-2 pb-4">
                
                <a href="{{ url('/') }}" class="flex flex-col items-center gap-1 text-gray-700 w-12 hover:text-green-700 transition-colors">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-[11px] font-bold">Home</span>
                </a>
                
                <a href="{{ url('/products') }}" class="flex flex-col items-center gap-1 text-gray-700 w-12 hover:text-green-700 transition-colors">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                    </svg>
                    <span class="text-[11px] font-bold">Menu</span>
                </a>

                <a href="{{ route('orders') }}" class="flex flex-col items-center justify-center gap-1 bg-[#00a12e] text-[#0f3b1b] rounded-[24px] w-[72px] h-[60px] shadow-sm relative -top-1">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-[11px] font-bold">Orders</span>
                </a>

                <a href="{{ route('profile') }}" class="flex flex-col items-center gap-1 text-gray-700 w-12 hover:text-green-700 transition-colors">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-[11px] font-bold">Profile</span>
                </a>

            </div>
        </div>

    </div>
</div>

<style>
/* Hide scrollbar for Chrome, Safari and Opera */
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
/* Hide scrollbar for IE, Edge and Firefox */
.hide-scrollbar {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>
@endsection
