<!-- chan trang -->
<footer class="bg-gray-200 py-8" id="footer">
    <div class="container">
        <div class="flex flex-wrap md:gap-4 lg:gap-0 py-4 mb-6">
            {{-- Column 1 --}}
            <div class="footer-column w-full sm:w-1/2 lg:w-1/4 flex flex-col gap-4 mb-6 pr-4">
                <h6 class="footer-header">
                    <span>Danh mục</span>
                    <span class="material-symbols-outlined footer-icon">expand_more</span>
                </h6>
                <ul class="footer-links flex flex-col gap-2">
                    @php
                        $footerCategories = \Illuminate\Support\Facades\DB::table('categories')
                            ->where('is_active', 1)
                            ->orderBy('display_order')
                            ->get();
                        $currentCategories = (array) request('category', []);
                    @endphp
                    @foreach($footerCategories as $cat)
                        <li>
                            <a href="/products?category[]={{ $cat->id }}" 
                               class="inline-block hover:text-green-600 {{ in_array($cat->id, $currentCategories) ? 'text-green-600 font-semibold' : '' }}">
                                {{ $cat->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            
            {{-- Column 2 --}}
            <div class="footer-column w-full sm:w-1/2 lg:w-1/4 flex flex-col gap-4 mb-6 pr-4">
                <h6 class="footer-header">
                    <span>Về chúng tôi</span>
                    <span class="material-symbols-outlined footer-icon">expand_more</span>
                </h6>
                <ul class="footer-links flex flex-col gap-2">
                    <li><a href="#!" class="inline-block hover:text-green-600">Giới thiệu</a></li>
                </ul>
            </div>

            {{-- Column 3 --}}
            <div class="footer-column w-full sm:w-1/2 lg:w-1/4 flex flex-col gap-4 mb-6 pr-4">
                <h6 class="footer-header">
                    <span>Dành cho khách hàng</span>
                    <span class="material-symbols-outlined footer-icon">expand_more</span>
                </h6>
                <ul class="footer-links flex flex-col gap-2">
                    <li><a href="#!" class="inline-block hover:text-green-600">Thanh toán</a></li>
                    <li><a href="#!" class="inline-block hover:text-green-600">Giao hàng</a></li>
                    <li><a href="#!" class="inline-block hover:text-green-600">Đổi trả sản phẩm</a></li>
                    <li><a href="#!" class="inline-block">Thanh toán đơn hàng</a></li>
                </ul>
            </div>

            {{-- Column 4 --}}
            <div class="footer-column w-full sm:w-1/2 lg:w-1/4 flex flex-col gap-4 mb-6 pr-4">
                <h6 class="footer-header">
                    <span>Chương trình Happy</span>
                    <span class="material-symbols-outlined footer-icon">expand_more</span>
                </h6>
                <ul class="footer-links flex flex-col gap-2">
                    <li><a href="#!" class="inline-block hover:text-green-600">Chương trình Happy</a></li>
                    <li><a href="#!" class="inline-block hover:text-green-600">Thẻ quà tặng</a></li>
                    <li><a href="#!" class="inline-block hover:text-green-600">Khuyến mãi & mã giảm giá</a></li>
                    <li><a href="#!" class="inline-block hover:text-green-600">Quảng cáo Happy</a></li>
                    <li><a href="#!" class="inline-block hover:text-green-600">Tuyển dụng</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t py-4 border-gray-300">
            <div class="gap-y-4 flex flex-wrap items-center justify-center lg:justify-start">
                <div class="lg:w-2/5 lg:text-left text-center">
                    <div class="flex md:flex-row flex-col gap-3 md:gap-6 items-center">
                        <div class="text-gray-900">Đối tác thanh toán</div>
                        <ul class="flex items-center flex-row gap-4">
                            <li>
                                <a href="#!"><img src="{{ asset('images/payment/momo.svg') }}"
                                        alt="momo pay" /></a>
                            </li>

                        </ul>
                    </div>
                </div>

            </div>
        </div>
        <div class="border-t py-4 border-gray-300">
            <div class="flex flex-col md:flex-row items-center gap-3">

                <div class=" flex md:justify-end items-center">
                    <div class="flex flex-row gap-5 items-center">
                        <div class="text-gray-500">Theo dõi chúng tôi trên</div>
                        <ul class="flex items-center justify-end text-sm gap-1">
                            <li>
                                <a href="https://www.facebook.com/TuiTenVu204/"
                                    class="inline-flex justify-center items-center align-middle text-center select-none border font-normal whitespace-no-wrap rounded leading-normal no-underline h-8 w-8 border-gray-300 hover:border-green-600 hover:text-green-600 transition ease-in-out">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-brand-facebook" width="16" height="16"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3" />
                                    </svg>
                                </a>
                            </li>

                            <li>
                                <a href="https://www.instagram.com/le.minhvu91"
                                    class="inline-flex justify-center items-center align-middle text-center select-none border font-normal whitespace-no-wrap rounded leading-normal no-underline h-8 w-8 border-gray-300 hover:border-green-600 hover:text-green-600 transition ease-in-out">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-brand-instagram" width="16" height="16"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M4 4m0 4a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z" />
                                        <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                        <path d="M16.5 7.5l0 .01" />
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const headers = document.querySelectorAll('.footer-header');
    headers.forEach(header => {
        header.addEventListener('click', function() {
            if (window.innerWidth <= 640) {
                const column = this.closest('.footer-column');
                if (column) {
                    column.classList.toggle('open');
                }
            }
        });
    });
});
</script>

