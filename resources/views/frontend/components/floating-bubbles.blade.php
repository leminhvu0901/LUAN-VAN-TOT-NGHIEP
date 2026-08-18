{{-- Bong bóng nổi "Giỏ hàng" + "Zalo" --}}
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
        <i class="fa-solid fa-cart-shopping"></i>
        
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
<script>
// Điều khiển nút giỏ hàng nổi mở Cart Drawer và tránh đè bottom-nav mobile
(function () {
    const root = document.getElementById('floating-bubbles');
    if (!root) return;

    if (document.querySelector('.l-bottom-nav')) {
        root.classList.add('floating-bubbles--has-bottom-nav');
    }

    const cartBtn = document.getElementById('floating-cart-btn');
    if (cartBtn) {
        cartBtn.addEventListener('click', function () {
            const realCartBtn = document.getElementById('cart-btn');
            if (realCartBtn) realCartBtn.click();
        });
    }
})();
</script>
@endpush

