{{-- Bong bóng nổi "Giỏ hàng" + "Zalo" — xếp chồng ngay phía trên bong bóng chatbox (quick-chatbox),
     cùng góc dưới-phải, cùng phong cách hình tròn nổi (xem .quick-chatbox trong users.css). --}}
@php
    $floatingCartCount = 0;
    if (Auth::check()) {
        $floatingCart = \App\Models\Cart::query()->where('user_id', Auth::id())->first();
        if ($floatingCart) {
            $floatingCartCount = (int) \App\Models\CartItem::query()->where('cart_id', $floatingCart->id)->sum('quantity');
        }
    }
    $floatingZaloUrl = \App\Models\Setting::getValue('store_zalo_url', '#');
@endphp
<div class="floating-bubbles" id="floating-bubbles">
    <button type="button" id="floating-cart-btn" class="floating-bubble floating-bubble--cart"
        aria-label="Giỏ hàng của bạn" title="Giỏ hàng của bạn">
        <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 9m12-9l2 9m-9-4h4" />
        </svg>
        {{-- Luôn render sẵn (không @if) + toggle bằng class ẩn, giống hệt #cart-badge của navbar —
        để main.js (updateCartUI) tìm thấy và cập nhật số real-time mỗi khi thêm/xóa giỏ hàng qua AJAX,
        không cần tải lại trang. Nếu chỉ render khi >0 thì lúc giỏ đang trống sẽ không có phần tử nào
        để JS cập nhật khi khách thêm sản phẩm đầu tiên. --}}
        <span id="floating-cart-badge" class="floating-bubble__badge {{ $floatingCartCount > 0 ? '' : 'floating-bubble__badge--hidden' }}">{{ $floatingCartCount }}</span>
    </button>

    @if($floatingZaloUrl && $floatingZaloUrl !== '#')
        <a href="{{ $floatingZaloUrl }}" target="_blank" rel="noopener noreferrer"
            class="floating-bubble floating-bubble--zalo" aria-label="Nhắn tin qua Zalo" title="Nhắn tin qua Zalo">
            <img src="{{ asset('images/icons/zalo.png') }}" alt="Zalo" class="floating-bubble__zalo-icon">
        </a>
    @endif
</div>

@push('scripts')
    <script src="{{ asset('js/frontend/layout/floating-bubbles.js') }}?v={{ filemtime(public_path('js/frontend/layout/floating-bubbles.js')) }}"></script>
@endpush
