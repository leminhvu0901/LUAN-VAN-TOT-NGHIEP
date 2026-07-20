{{-- Thanh điều hướng dùng chung cho mọi "màn hình" con của chatbox (trừ menu chính) —
     luôn có nút Quay lại + Về menu chính, theo đúng yêu cầu mỗi màn hình phải có 2 nút này. --}}
<div class="quick-chatbox__screen-nav">
    <button type="button" class="quick-chatbox__nav-btn" data-action="back" data-back="menu">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
        </svg>
        {{ config('quick_chat.labels.back') }}
    </button>
    <button type="button" class="quick-chatbox__nav-btn" data-action="home">
        {{ config('quick_chat.labels.menu') }}
    </button>
</div>
