{{-- ===== COMPONENTS: FOOTER ===== --}}
<footer id="footer-custom">
    <div class="container mx-auto px-4 md:px-8">

        {{-- Grid trên: Brand + Các cột link --}}
        <div class="footer-grid-custom mb-8">

            {{-- Cột 1: Thương hiệu & Liên hệ --}}
            <div class="footer-col-1-custom flex flex-col gap-4">
                <a href="{{ url('/') }}" class="flex items-center gap-2 no-underline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                        stroke-width="2.5" stroke="#19b277" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <circle cx="6" cy="19" r="2"></circle>
                        <circle cx="17" cy="19" r="2"></circle>
                        <path d="M17 17h-11v-14h-2"></path>
                        <path d="M6 5l14 1l-1 7h-13"></path>
                    </svg>
                    <span class="text-2xl font-bold tracking-tight footer-logo-text select-none">Happy</span>
                </a>

                <ul class="flex flex-col gap-3 mt-2 list-none p-0">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 footer-icon-custom mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="text-[#475569] leading-relaxed">180 Cao Lỗ, Phường Chánh Hưng, Hồ Chí Minh 700000, Việt Nam</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 footer-icon-custom flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        <span class="text-[#475569]">Hotline: <a href="tel:12345678" class="font-bold footer-hotline-custom no-underline">1234 5678</a></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 footer-icon-custom flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="text-[#475569]">Email: <a href="mailto:adminhappy123@gmail.com" class="footer-link-custom">adminhappy123@gmail.com</a></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 footer-icon-custom flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[#475569]">Giờ mở cửa: 07:00 - 23:00</span>
                    </li>
                </ul>
            </div>

            {{-- Cột 2–5: Các nhóm link --}}
            <div class="footer-col-links-custom">

                {{-- Cột 2: Danh mục --}}
                <div class="footer-column flex flex-col">
                    <h6 class="footer-header footer-title-custom uppercase flex justify-between items-center w-full pb-2 border-b border-gray-200 md:border-none">
                        <span>DANH MỤC</span>
                        <span class="material-symbols-outlined footer-icon md:hidden">expand_more</span>
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
                        <span class="material-symbols-outlined footer-icon md:hidden">expand_more</span>
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
                        <span class="material-symbols-outlined footer-icon md:hidden">expand_more</span>
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
                        <span class="material-symbols-outlined footer-icon md:hidden">expand_more</span>
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
                    <div class="footer-payment-badge badge-momo" title="Thanh toán qua ví MoMo">MOMO</div>
                    <div class="footer-payment-badge badge-cod" title="Thanh toán khi nhận hàng (COD)">COD</div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <span class="text-[#94a3b8] text-xs font-bold tracking-wider uppercase">Kết nối với chúng tôi</span>
                <ul class="flex items-center gap-2.5 list-none p-0 m-0">
                    <li>
                        <a href="https://www.facebook.com/TuiTenVu204/" target="_blank" rel="noopener noreferrer" class="footer-social-icon no-underline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3" />
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="https://www.instagram.com/le.minhvu91" target="_blank" rel="noopener noreferrer" class="footer-social-icon no-underline">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M4 4m0 4a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z" />
                                <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                <path d="M16.5 7.5l0 .01" />
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-[#cbd5e1] opacity-40 my-6"></div>

        {{-- Copyright --}}
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-[#94a3b8] py-1">
            <p class="m-0 font-medium">&copy; {{ date('Y') }} Happy Tea. Tất cả các quyền được bảo lưu.</p>
        </div>

    </div>
</footer>

<script src="{{ asset('js/frontend/footer.js') }}"></script>
