{{-- Chatbox trả lời nhanh (quick-reply, không AI, không lưu lịch sử) — dữ liệu do
     App\Http\View\Composers\QuickChatboxComposer cấp sẵn, render tĩnh lúc tải trang.
     JS (quick-chatbox.js) chỉ ẩn/hiện đúng "màn hình" tương ứng, không có fetch/AJAX nào. --}}
<div class="quick-chatbox" id="quick-chatbox" data-typing-delay="{{ config('quick_chat.typing_delay_ms') }}"
    data-greeting="{{ config('quick_chat.greeting') }}">
    <button type="button" class="quick-chatbox__toggle" id="quick-chatbox-toggle"
        aria-label="Mở hỗ trợ {{ config('quick_chat.header_title') }}" aria-expanded="false"
        aria-controls="quick-chatbox-panel">
        <svg class="quick-chatbox__toggle-icon-chat" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
        </svg>
        <svg class="quick-chatbox__toggle-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
    </button>

    <div class="quick-chatbox__panel" id="quick-chatbox-panel" role="dialog" aria-modal="false"
        aria-label="{{ config('quick_chat.header_title') }}" hidden>
        <div class="quick-chatbox__header">
            <span class="quick-chatbox__header-title">{{ config('quick_chat.header_title') }}</span>
            <button type="button" class="quick-chatbox__close" data-action="close"
                aria-label="{{ config('quick_chat.labels.close') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="quick-chatbox__body">
            <div class="quick-chatbox__messages" id="quick-chatbox-messages">
                <div class="quick-chatbox__message quick-chatbox__message--bot">{{ config('quick_chat.greeting') }}</div>
            </div>

            {{-- ===== MÀN HÌNH: MENU CHÍNH ===== --}}
            <div class="quick-chatbox__screen is-active" id="quick-chatbox-screen-menu">
                <div class="quick-chatbox__options">
                    @foreach(config('quick_chat.menu') as $item)
                        <button type="button" class="quick-chatbox__option"
                            data-topic="{{ $item['key'] }}">{{ $item['label'] }}</button>
                    @endforeach
                </div>
            </div>

            {{-- ===== SẢN PHẨM NỔI BẬT ===== --}}
            <div class="quick-chatbox__screen" id="quick-chatbox-screen-featured_products" hidden>
                @include('frontend.components.quick-chatbox-screen-nav')
                <div class="quick-chatbox__scroll-area">
                    @forelse($quickChatFeaturedProducts as $product)
                        <div class="quick-chatbox__product-card">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                class="quick-chatbox__product-card-img">
                            <div class="quick-chatbox__product-card-info">
                                <p class="quick-chatbox__product-card-name">{{ $product->name }}</p>
                                <p class="quick-chatbox__product-card-price">{{ number_format($product->base_price, 0, ',', '.') }}đ</p>
                                <a href="{{ route('product.show', $product->slug) }}"
                                    class="quick-chatbox__product-card-link">{{ config('quick_chat.labels.view_product') }}</a>
                            </div>
                        </div>
                    @empty
                        <p class="quick-chatbox__empty">Hiện chưa có sản phẩm để gợi ý.</p>
                    @endforelse
                </div>
            </div>

            {{-- ===== KHUYẾN MÃI HIỆN CÓ ===== --}}
            <div class="quick-chatbox__screen" id="quick-chatbox-screen-promotions" hidden>
                @include('frontend.components.quick-chatbox-screen-nav')
                <div class="quick-chatbox__scroll-area">
                    @forelse($quickChatPromotions as $promotion)
                        <div class="quick-chatbox__promotion-card">
                            <p class="quick-chatbox__promotion-card-code">{{ $promotion->code ?: 'Ưu đãi #' . $promotion->id }}</p>
                            @if($promotion->description)
                                <p class="quick-chatbox__promotion-card-desc">{{ $promotion->description }}</p>
                            @endif
                            <p class="quick-chatbox__promotion-card-value">
                                @if($promotion->type === 'percent')
                                    Giảm {{ rtrim(rtrim(number_format($promotion->value, 2, '.', ''), '0'), '.') }}%
                                @else
                                    Giảm {{ number_format($promotion->value, 0, ',', '.') }}đ
                                @endif
                                @if($promotion->max_discount_amount)
                                    (tối đa {{ number_format($promotion->max_discount_amount, 0, ',', '.') }}đ)
                                @endif
                            </p>
                            @if($promotion->min_order_amount)
                                <p class="quick-chatbox__promotion-card-condition">Đơn tối thiểu {{ number_format($promotion->min_order_amount, 0, ',', '.') }}đ</p>
                            @endif
                            @if($promotion->min_quantity)
                                <p class="quick-chatbox__promotion-card-condition">Tối thiểu {{ $promotion->min_quantity }} món</p>
                            @endif
                            @if($promotion->end_at)
                                <p class="quick-chatbox__promotion-card-expiry">Hạn dùng: {{ \Carbon\Carbon::parse($promotion->end_at)->format('d/m/Y') }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="quick-chatbox__empty">Hiện chưa có khuyến mãi nào đang áp dụng.</p>
                    @endforelse
                </div>
            </div>

            {{-- ===== THEO DÕI ĐƠN HÀNG ===== --}}
            <div class="quick-chatbox__screen" id="quick-chatbox-screen-order_tracking" hidden>
                @include('frontend.components.quick-chatbox-screen-nav')
                <div class="quick-chatbox__scroll-area">
                    @auth
                        <p class="quick-chatbox__answer-text">{{ config('quick_chat.answers.order_tracking_auth') }}</p>
                        <a href="{{ route('orders') }}"
                            class="quick-chatbox__action-btn">{{ config('quick_chat.labels.go_to_orders') }}</a>
                    @else
                        <p class="quick-chatbox__answer-text">{{ config('quick_chat.answers.order_tracking_guest') }}</p>
                        <button type="button" class="quick-chatbox__action-btn" data-action="open-login"
                            data-login-url="{{ route('login') }}">{{ config('quick_chat.labels.go_to_login') }}</button>
                    @endauth
                </div>
            </div>

            {{-- ===== PHƯƠNG THỨC THANH TOÁN ===== --}}
            <div class="quick-chatbox__screen" id="quick-chatbox-screen-payment_methods" hidden>
                @include('frontend.components.quick-chatbox-screen-nav')
                <div class="quick-chatbox__scroll-area">
                    <p class="quick-chatbox__answer-text">{{ config('quick_chat.answers.payment_methods') }}</p>
                    <ul class="quick-chatbox__answer-list">
                        @if($quickChatPaymentMethods['momo_enabled'])
                            <li>Ví điện tử MoMo</li>
                        @endif
                        @if($quickChatPaymentMethods['cod_enabled'])
                            <li>Tiền mặt khi nhận hàng (COD)</li>
                        @endif
                        <li>Tiền mặt hoặc MoMo (đơn tạo tại quầy)</li>
                    </ul>
                </div>
            </div>

            {{-- ===== GIAO HÀNG VÀ PHÍ VẬN CHUYỂN ===== --}}
            <div class="quick-chatbox__screen" id="quick-chatbox-screen-shipping" hidden>
                @include('frontend.components.quick-chatbox-screen-nav')
                <div class="quick-chatbox__scroll-area">
                    <p class="quick-chatbox__answer-text">{{ config('quick_chat.answers.shipping') }}</p>
                </div>
            </div>

            {{-- ===== GIỜ HOẠT ĐỘNG ===== --}}
            <div class="quick-chatbox__screen" id="quick-chatbox-screen-business_hours" hidden>
                @include('frontend.components.quick-chatbox-screen-nav')
                <div class="quick-chatbox__scroll-area">
                    <p class="quick-chatbox__answer-text">
                        Cửa hàng mở cửa hàng ngày từ {{ \Carbon\Carbon::parse($quickChatBusinessHours['open'])->format('H:i') }}
                        đến {{ \Carbon\Carbon::parse($quickChatBusinessHours['close'])->format('H:i') }}.
                    </p>
                </div>
            </div>

            {{-- ===== THÔNG TIN LIÊN HỆ ===== --}}
            <div class="quick-chatbox__screen" id="quick-chatbox-screen-contact" hidden>
                @include('frontend.components.quick-chatbox-screen-nav')
                <div class="quick-chatbox__scroll-area">
                    @if($quickChatContact['address'])
                        <p class="quick-chatbox__answer-text">{{ $quickChatContact['address'] }}</p>
                    @endif
                    <div class="quick-chatbox__contact-actions">
                        @if($quickChatContact['phone'])
                            <a href="tel:{{ $quickChatContact['phone'] }}"
                                class="quick-chatbox__action-btn">{{ config('quick_chat.labels.call_store') }} ({{ $quickChatContact['phone'] }})</a>
                        @endif
                        @if($quickChatContact['email'])
                            <a href="mailto:{{ $quickChatContact['email'] }}"
                                class="quick-chatbox__action-btn quick-chatbox__action-btn--secondary">{{ config('quick_chat.labels.send_email') }}</a>
                        @endif
                        @if($quickChatContact['map_url'])
                            <a href="{{ $quickChatContact['map_url'] }}" target="_blank" rel="noopener noreferrer"
                                class="quick-chatbox__action-btn quick-chatbox__action-btn--secondary">{{ config('quick_chat.labels.view_map') }}</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Khu vực hội thoại cho câu hỏi tự do — thay thế các screen tĩnh khi đang dùng, tránh
                 tranh chấp không gian với khung tin nhắn nhỏ phía trên. --}}
            <div class="quick-chatbox__answer-area" id="quick-chatbox-answer-area" hidden></div>
        </div>

        <form class="quick-chatbox__input-row" id="quick-chatbox-ask-form">
            <input type="text" id="quick-chatbox-ask-input" class="quick-chatbox__input" maxlength="300"
                placeholder="{{ config('quick_chat.freeform_placeholder') }}" autocomplete="off"
                aria-label="{{ config('quick_chat.freeform_placeholder') }}">
            <button type="submit" id="quick-chatbox-ask-send" class="quick-chatbox__send-btn" disabled
                aria-label="Gửi câu hỏi">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 9-18 9 4-9-4-9z" />
                </svg>
            </button>
        </form>

        <div class="quick-chatbox__footer">{{ config('quick_chat.fallback_notice') }}</div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('js/frontend/layout/quick-chatbox.js') }}?v={{ filemtime(public_path('js/frontend/layout/quick-chatbox.js')) }}"></script>
@endpush
