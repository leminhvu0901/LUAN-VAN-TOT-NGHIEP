{{-- Footer --}}
<footer id="footer-custom">
    <div class="container mx-auto px-4 md:px-8">

        {{-- Grid trên: Brand + Các cột link --}}
        <div class="footer-grid-custom mb-8">

            {{-- Cột 1: Thương hiệu & Liên hệ --}}
            <div class="footer-col-1-custom flex flex-col gap-4">
                @php
                    $shopLogo = \App\Models\Setting::getValue('store_logo', '/images/logo/black.png');
                    $shopName = \App\Models\Setting::getValue('store_name', 'Happy Tea');
                    $shopPhone = \App\Models\Setting::getValue('store_phone', '1234 5678');
                    $shopEmail = \App\Models\Setting::getValue('store_email', 'adminhappy123@gmail.com');
                    $shopAddress = \App\Models\Setting::getValue('store_address', '180 Cao Lỗ, Phường Chánh Hưng, Hồ Chí Minh 700000, Việt Nam');
                    $open = \App\Models\Setting::getValue('store_open_time', '08:00');
                    $close = \App\Models\Setting::getValue('store_close_time', '22:00');
                    $shopHours = "{$open} - {$close}";
                    $facebookUrl = \App\Models\Setting::getValue('store_facebook_url', '#');
                    $zaloUrl = \App\Models\Setting::getValue('store_zalo_url', '#');
                @endphp
                <a href="{{ url('/') }}" class="flex items-center gap-2 no-underline">
                    <img src="{{ asset($shopLogo) }}"
                         alt="{{ $shopName }}"
                         class="h-10 w-auto max-w-[150px] object-contain flex-shrink-0">
                </a>

                <ul class="flex flex-col gap-3 mt-2 list-none p-0">
                    <li class="flex items-start gap-3">
                        <i class="fa-solid fa-location-dot text-emerald-600 mt-1 flex-shrink-0 text-base"></i>
                        <span class="text-[#475569] leading-relaxed">{{ $shopAddress }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-phone text-emerald-600 flex-shrink-0 text-sm"></i>
                        <span class="text-[#475569]">Hotline: <a href="tel:{{ str_replace(' ', '', $shopPhone) }}" class="font-bold footer-hotline-custom no-underline">{{ $shopPhone }}</a></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-envelope text-emerald-600 flex-shrink-0 text-sm"></i>
                        <span class="text-[#475569]">Email: <a href="mailto:{{ $shopEmail }}" class="footer-link-custom">{{ $shopEmail }}</a></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fa-solid fa-clock text-emerald-600 flex-shrink-0 text-sm"></i>
                        <span class="text-[#475569]">Giờ mở cửa: {{ $shopHours }}</span>
                    </li>
                </ul>
            </div>

            {{-- Cột 2, 5: Các nhóm link --}}
            <div class="footer-col-links-custom">

                {{-- Cột 2: Danh mục --}}
                <div class="footer-column flex flex-col">
                    <h6 class="footer-header footer-title-custom uppercase flex justify-between items-center w-full pb-2 border-b border-gray-200 md:border-none">
                        <span>DANH MỤC</span>
                        <i class="fa-solid fa-chevron-down footer-icon md:hidden text-xs"></i>
                    </h6>
                    <ul class="footer-links flex flex-col gap-2 list-none p-0 mt-2">
                        @php
                            $footerCategories = \App\Models\Category::query()
                                ->where('is_active', 1)
                                ->orderBy('display_order')
                                ->get();
                            $currentCategories = (array) request('category', []);
                        @endphp
                        @foreach($footerCategories as $cat)
                            <li>
                                <a href="/products?category[]={{ $cat->id }}"
                                    class="footer-link-custom {{ in_array($cat->id, $currentCategories) ? 'footer-link-active' : '' }}">
                                    {{ $cat->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Cột 3: Về chúng tôi --}}
                <div class="footer-column flex flex-col">
                    <h6 class="footer-header footer-title-custom uppercase flex justify-between items-center w-full pb-2 border-b border-gray-200 md:border-none">
                        <span>VỀ CHÚNG TÔI</span>
                        <i class="fa-solid fa-chevron-down footer-icon md:hidden text-xs"></i>
                    </h6>
                    <ul class="footer-links flex flex-col gap-2 list-none p-0 mt-2">
                        <li><a href="#!" class="footer-link-custom">Giới thiệu</a></li>
                        <li><a href="#!" class="footer-link-custom">Hệ thống cửa hàng</a></li>
                        <li><a href="#!" class="footer-link-custom">Tin tức & Sự kiện</a></li>
                        <li><a href="#!" class="footer-link-custom">Tuyển dụng</a></li>
                    </ul>
                </div>

                {{-- Cột 4: Hỗ trợ --}}
                <div class="footer-column flex flex-col">
                    <h6 class="footer-header footer-title-custom uppercase flex justify-between items-center w-full pb-2 border-b border-gray-200 md:border-none">
                        <span>HỖ TRỢ KHÁCH HÀNG</span>
                        <i class="fa-solid fa-chevron-down footer-icon md:hidden text-xs"></i>
                    </h6>
                    <ul class="footer-links flex flex-col gap-2 list-none p-0 mt-2">
                        <li><a href="#!" class="footer-link-custom">Hướng dẫn mua hàng</a></li>
                        <li><a href="#!" class="footer-link-custom">Chính sách giao nhận</a></li>
                        <li><a href="#!" class="footer-link-custom">Chính sách đổi trả</a></li>
                        <li><a href="#!" class="footer-link-custom">Liên hệ & Góp ý</a></li>
                    </ul>
                </div>

                {{-- Cột 5: Điều khoản --}}
                <div class="footer-column flex flex-col">
                    <h6 class="footer-header footer-title-custom uppercase flex justify-between items-center w-full pb-2 border-b border-gray-200 md:border-none">
                        <span>ĐIỀU KHOẢN & CHÍNH SÁCH</span>
                        <i class="fa-solid fa-chevron-down footer-icon md:hidden text-xs"></i>
                    </h6>
                    <ul class="footer-links flex flex-col gap-2 list-none p-0 mt-2">
                        <li><a href="#!" class="footer-link-custom">Điều khoản dịch vụ</a></li>
                        <li><a href="#!" class="footer-link-custom">Chính sách bảo mật</a></li>
                        <li><a href="#!" class="footer-link-custom">Chính sách thành viên</a></li>
                    </ul>
                </div>

            </div>
        </div>

        <div class="border-t border-[#cbd5e1] opacity-40 my-6"></div>

        {{-- Đối tác thanh toán & Mạng xã hội --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 py-2">
            <div class="flex flex-wrap items-center gap-4 text-center md:text-left">
                <span class="text-[#94a3b8] text-xs font-bold tracking-wider uppercase">Đối tác thanh toán</span>
                <div class="flex items-center gap-2">

                    <div class="footer-payment-badge badge-vnpay" title="Thanh toán qua VNPay">VNPAY</div>
                    <div class="footer-payment-badge badge-cod" title="Thanh toán khi nhận hàng (COD)">COD</div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <span class="text-[#94a3b8] text-xs font-bold tracking-wider uppercase">Kết nối với chúng tôi</span>
                <ul class="flex items-center gap-2.5 list-none p-0 m-0">
                    @if($facebookUrl && $facebookUrl !== '#')
                    <li>
                        <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" class="footer-social-icon no-underline" title="Facebook">
                            <img src="{{ asset('images/icons/facebook.png') }}" alt="Facebook" class="w-4 h-4 object-contain">
                        </a>
                    </li>
                    @endif
                    @if($zaloUrl && $zaloUrl !== '#')
                    <li>
                        <a href="{{ $zaloUrl }}" target="_blank" rel="noopener noreferrer" class="footer-social-icon no-underline" title="Zalo">
                            <img src="{{ asset('images/icons/zalo.png') }}" alt="Zalo" class="w-4 h-4 object-contain">
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="border-t border-[#cbd5e1] opacity-40 my-6"></div>

        {{-- Bản quyền --}}
        <div class="text-center text-[#94a3b8] text-xs pb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="m-0 font-medium">&copy; {{ date('Y') }} {{ $shopName }}. Tất cả các quyền được bảo lưu.</p>
        </div>

    </div>
</footer>

<script>
// Accordion đóng/mở danh sách liên kết footer trên giao diện mobile, <=640px
document.addEventListener('DOMContentLoaded', function () {
    const headers = document.querySelectorAll('.footer-column h6');
    headers.forEach(header => {
        header.addEventListener('click', function () {
            if (window.innerWidth <= 640) {
                const column = this.closest('.footer-column');
                if (column) column.classList.toggle('open');
            }
        });
    });
});
</script>

