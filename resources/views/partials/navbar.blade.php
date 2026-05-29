<header>
    <!-- navbar -->
    <div class="border-b">

        {{-- row1 --}}
        <div class="pt-5">
            <div class="container">
                <div class="flex flex-wrap w-full items-center justify-between">
                    {{-- logo --}}
                    <div class="lg:w-1/6 md:w-1/2 w-2/5">
                        <a class="navbar-brand" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo/black1.svg') }}" class="site-logo"
                                alt="TailwindCSS eCommerce HTML Template" />
                        </a>
                    </div>
                    {{-- tìm kiếm sản phẩm --}}
                    <div class="lg:w-2/5 hidden lg:block">
                        <form action="#">
                            <div class="relative">
                                <label for="searchProducts" class="invisible hidden">Tìm</label>
                                <input
                                    class="border border-gray-300 text-gray-900 rounded-lg focus:shadow-[0_0_0_.25rem_rgba(10,173,10,.25)] focus:ring-green-600 focus:ring-0 focus:border-green-600 block p-2 px-3 disabled:opacity-50 disabled:pointer-events-none w-full text-base"
                                    type="search" placeholder="Tìm kiếm sản phẩm" id="searchProducts" />
                                <button class="absolute right-0 top-0 p-3" type="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-search"
                                        width="16" height="16" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                        <path d="M21 21l-6 -6" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                    {{-- địa chỉ --}}
                    <div class="lg:w-1/5 hidden lg:block">
                        <button type="button"
                            class="btn inline-flex items-center gap-x-2 bg-transparent text-gray-600 border-gray-300 disabled:opacity-50 disabled:pointer-events-none hover:text-white hover:bg-gray-700 hover:border-gray-700 active:bg-gray-700 active:border-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-300"
                            onclick="openGoogleMaps()">
                            <span class="flex items-center gap-1">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-map-pin"
                                        width="16" height="16" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                        <path
                                            d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z" />
                                    </svg>
                                </span>
                                <span>Địa chỉ</span>
                            </span>
                        </button>
                    </div>
                    {{-- sản phẩm yêu thích - tài khoản - giỏ hàng --}}
                    <div class="lg:w-1/5 text-end md:w-1/2 w-3/5">
                        <div class="flex gap-7 items-center justify-end">
                            {{-- sp yeu thich --}}
                            <div>
                                <a href="#!" class="relative" data-bs-toggle="offcanvas"
                                    data-bs-target="#offcanvasFavorites">
                                    <svg xmlns="" class="icon icon-tabler icon-tabler-heart" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path
                                            d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572">
                                        </path>
                                    </svg>
                                    <span
                                        class="absolute top-0 -mt-1 left-full rounded-full h-5 w-5 -ml-2 bg-green-600 text-white text-center font-semibold text-sm">
                                        5
                                        <span class="invisible">unread messages</span>
                                    </span>
                                </a>
                            </div>
                            {{-- tài khoản --}}
                            <div>
                                <a href="#!" class="text-gray-600" data-bs-toggle="modal"
                                    data-bs-target="#loginModal">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user"
                                        width="22" height="22" viewBox="0 0 24 24" stroke-width="2"
                                        stroke="currentColor" fill="none" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                                    </svg>
                                </a>
                            </div>

                            {{-- giỏ hàng --}}
                            <div>
                                <button type="button" class="text-gray-600 relative" data-bs-toggle="offcanvas"
                                    data-bs-target="#offcanvasRight" role="button" aria-controls="offcanvasRight">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-shopping-bag" width="24" height="24"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path
                                            d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z" />
                                        <path d="M9 11v-5a3 3 0 0 1 6 0v5" />
                                    </svg>
                                    <span id="cartCount"
                                        class="absolute top-0 -mt-1 left-full rounded-full h-5 w-5 -ml-3 bg-green-600 text-white text-center font-semibold text-sm">
                                        0
                                        <span class="invisible">sản phẩm trong giỏ hàng</span>
                                    </span>
                                </button>
                            </div>
                            {{-- menu khi màn hình nhỏ --}}
                            <div class="lg:hidden leading-none">
                                <!-- Button -->
                                <button class="collapsed" type="button" data-bs-toggle="offcanvas"
                                    data-bs-target="#navbar-default" aria-controls="navbar-default"
                                    aria-label="Toggle navigation">
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="icon icon-tabler icon-tabler-menu-2 text-gray-800" width="24"
                                        height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                        <path d="M4 6l16 0" />
                                        <path d="M4 12l16 0" />
                                        <path d="M4 18l16 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- row 2 --}}
        <nav class="navbar relative navbar-expand-lg lg:flex lg:flex-wrap items-center content-between text-black navbar-default"
            aria-label="Offcanvas navbar large">
            <div class="container max-w-7xl mx-auto w-full xl:px-4 lg:px-0">
                <div class="offcanvas offcanvas-left lg:visible" tabindex="-1" id="navbar-default">
                    {{-- logo + nút đóng --}}
                    <div class="offcanvas-header pb-1">
                        <a href="{{ url('/') }}"><img src="{{ asset('images/logo/black.png') }}"
                                alt="TailwindCSS eCommerce HTML Template" /></a>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="icon icon-tabler icon-tabler-x text-gray-700" width="24" height="24"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M18 6l-12 12" />
                                <path d="M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    {{-- menu bên dưới  --}}
                    <div class="offcanvas-body lg:flex lg:items-center">
                        {{-- timf --}}
                        <div class="block lg:hidden mb-4">
                            <form action="#">
                                <div class="relative">
                                    <label for="searhNavbar" class="invisible hidden">Tìm</label>
                                    <input
                                        class="border border-gray-300 text-gray-900 rounded-lg focus:shadow-[0_0_0_.25rem_rgba(10,173,10,.25)] focus:ring-green-600 focus:ring-0 focus:border-green-600 block p-2 px-3 disabled:opacity-50 disabled:pointer-events-none w-full text-base"
                                        type="search" placeholder="Tìm kiếm sản phẩm" id="searhNavbar" />
                                    <button class="absolute right-0 top-0 p-3" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="icon icon-tabler icon-tabler-search" width="16" height="16"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                            <path d="M21 21l-6 -6" />
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>
                        {{-- menu --}}
                        <div>
                            <ul class="navbar-nav lg:flex gap-3 lg:items-center">
                                {{-- home --}}
                                <div class="dropdown hidden lg:block">
                                    <button
                                        class="mr-4 btn inline-flex items-center gap-x-2 bg-green-600 text-white border-green-600 disabled:opacity-50 disabled:pointer-events-none hover:text-white hover:bg-green-700 hover:border-green-700 active:bg-green-700 active:border-green-700 focus:outline-none focus:ring-4 focus:ring-green-300"
                                        type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <span>
                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                class="icon icon-tabler icon-tabler-layout-grid" width="16"
                                                height="16" viewBox="0 0 24 24" stroke-width="2"
                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                <path
                                                    d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z">
                                                </path>
                                                <path
                                                    d="M14 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z">
                                                </path>
                                                <path
                                                    d="M4 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z">
                                                </path>
                                                <path
                                                    d="M14 14m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z">
                                                </path>
                                            </svg>
                                        </span>
                                        Tất cả sản phẩm
                                    </button>

                                </div>
                                <li class="nav-item dropdown w-full lg:w-auto">
                                    <a class="nav-link " href="{{ url('/') }}" role="button">Trang chủ</a>

                                </li>
                                {{-- Dropdown Menu --}}
                                <li class="nav-item dropdown w-full lg:w-auto">
                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">Danh sách sản phẩm</a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="/products?category=">
                                                🍹 Tất cả sản phẩm
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="/products?category=ca-phe">
                                                ☕ Cà phê

                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="/products?category=tra-sua">
                                                🧋 Trà sữa

                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="/products?category=tra-trai-cay">
                                                🍋 Trà trái cây

                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="/products?category=sua-chua">
                                                🍓 Sữa chua

                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="/products?category=do-uong-khac">
                                                🥤 Đồ uống khác

                                            </a>
                                        </li>
                                </li>
                            </ul>
                            </li>
                            {{-- Mega menu --}}
                            <li class="nav-item dropdown w-full lg:w-auto dropdown-fullwidth">
                                <a class="nav-link " href="#!">Bán chạy nhất
                                </a>

                            </li>

                            {{-- Dashboard --}}
                            <li class="nav-item">
                                <a class="nav-link" href="#!">Bảng điều khiển</a>
                            </li>


                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>
<!-- Tài khoản  -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        đăng ký
        <div class="modal-content p-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-gray-800" id="userModalLabel">Đăng Ký</h3>

                <button type="button" class="login-modal-close" data-bs-dismiss="modal" aria-label="Đóng">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M18 6l-12 12" />
                        <path d="M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="fullName" class="mb-2 block text-gray-800">Họ và Tên</label>
                        <input type="text"
                            class="form-control border border-gray-300 text-gray-900 rounded-lg focus:shadow-[0_0_0_.25rem_rgba(10,173,10,.25)] focus:ring-green-600 focus:ring-0 focus:border-green-600 block p-2 px-3 disabled:opacity-50 disabled:pointer-events-none w-full text-base"
                            id="fullName" placeholder="Nhập tên của bạn" required />
                        <div class="invalid-feedback">Vui lòng nhập tên</div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="mb-2 block text-gray-800">Email hoặc Số điện thoại </label>
                        <input type="email"
                            class="form-control border border-gray-300 text-gray-900 rounded-lg focus:shadow-[0_0_0_.25rem_rgba(10,173,10,.25)] focus:ring-green-600 focus:ring-0 focus:border-green-600 block p-2 px-3 disabled:opacity-50 disabled:pointer-events-none w-full text-base"
                            id="email" placeholder="Nhập địa chỉ email hoặc số điện thoại."
                            autocomplete="email" required />
                        <div class="invalid-feedback">Vui lòng nhập email hoặc số điện thoại.</div>
                    </div>
                    <div class="mb-5">
                        <label for="password" class="mb-2 block text-gray-800">Mật khẩu</label>
                        <input type="password"
                            class="form-control border border-gray-300 text-gray-900 rounded-lg focus:shadow-[0_0_0_.25rem_rgba(10,173,10,.25)] focus:ring-green-600 focus:ring-0 focus:border-green-600 block p-2 px-3 disabled:opacity-50 disabled:pointer-events-none w-full text-base"
                            id="password" placeholder="Nhập mật khẩu" required />
                        <div class="invalid-feedback">Vui lòng nhập mật khẩu.</div>

                    </div>
                    <div class="mb-5">
                        <label for="password" class="mb-2 block text-gray-800"> Xác nhận mật khẩu</label>
                        <input type="password"
                            class="form-control border border-gray-300 text-gray-900 rounded-lg focus:shadow-[0_0_0_.25rem_rgba(10,173,10,.25)] focus:ring-green-600 focus:ring-0 focus:border-green-600 block p-2 px-3 disabled:opacity-50 disabled:pointer-events-none w-full text-base"
                            id="password" placeholder="Nhập mật khẩu xác nhận" required />
                        <div class="invalid-feedback">Vui lòng nhập mật khẩu xác nhận.</div>
                    </div>
                    <span class="block mt-1 text-sm text-gray-500">
                        Bằng việc đăng ký, bạn đồng ý với
                        <a href="#!" class="text-green-600">Điều khoản dịch vụ</a>
                        &
                        <a href="#!" class="text-green-600">Chính sách bảo mật</a>
                    </span>
            </div>

            <button type="submit"
                class="btn inline-flex items-center gap-x-2 bg-green-600 text-white border-green-600 disabled:opacity-50 disabled:pointer-events-none hover:text-white hover:bg-green-700 hover:border-green-700 active:bg-green-700 active:border-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 justify-center">
                Đăng ký
            </button>
            </form>
        </div>
        <div class="modal-footer flex border-0 justify-center mt-3">
            Đã có tài khoản?
            <a href="#!" class="text-green-600 ml-1" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng
                nhập</a>
        </div>
    </div>
</div>
</div>

<!-- Shop Cart -->

<div class="offcanvas offcanvas-right" tabindex="-1" id="offcanvasRight" aria-labelledby="offcanvasRightLabel">
    <div class="offcanvas-header border-b">
        <div>
            <h5 id="offcanvasRightLabel">Giỏ hàng</h5>
            <span>Vị trí: 382480</span>
        </div>
        <button type="button" class="btn-close text-inherit" data-bs-dismiss="offcanvas" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x text-gray-700"
                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M18 6l-12 12" />
                <path d="M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div class="offcanvas-body p-4">
        <div>
            <!-- alert -->
            <div class="bg-red-500 bg-opacity-25 text-red-800 mb-3 rounded-lg p-4" role="alert">
                Bạn được giao hàng MIỄN PHÍ. Bắt đầu
                <a href="#!" class="alert-link">thanh toán ngay!</a>
            </div>
            <ul class="list-none">
                <!-- list group -->
                <li class="py-3 border-t">
                    <div class="flex items-center">
                        <div class="w-1/2 md:w-1/2 lg:w-3/5">
                            <div class="flex">
                                <img src="{{ asset('images/products/product-img-1.jpg') }}" alt="Ecommerce"
                                    class="w-16 h-16" />
                                <div class="ml-3">
                                    <!-- title -->
                                    <a href="#!" class="text-inherit">
                                        <h6>Haldiram's Sev Bhujia</h6>
                                    </a>
                                    <span><small class="text-gray-500">.98 / lb</small></span>
                                    <!-- text -->
                                    <div class="mt-2 small leading-none">
                                        <a href="#!" class="text-green-600 flex items-center">
                                            <span class="mr-1 align-text-bottom">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-trash" width="14"
                                                    height="14" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </span>
                                            <span class="text-gray-500 text-sm">Xóa</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- input group -->
                        <div class="w-1/3 md:w-1/4 lg:w-1/5">
                            <!-- input -->
                            <div class="input-group input-spinner rounded-lg flex justify-between items-center">
                                <input type="button" value="-"
                                    class="button-minus w-8 py-1 border-r cursor-pointer border-gray-300"
                                    data-field="quantity" />
                                <input type="number" step="1" max="10" value="1" name="quantity"
                                    class="quantity-field w-9 px-2 text-center h-7 border-0 bg-transparent" />
                                <input type="button" value="+"
                                    class="button-plus w-8 py-1 border-l cursor-pointer border-gray-300"
                                    data-field="quantity" />
                            </div>
                        </div>
                        <!-- price -->
                        <div class="w-1/5 text-center md:w-1/5">
                            <span class="font-bold text-gray-800">$5.00</span>
                        </div>
                    </div>
                </li>
                <!-- list group -->
                <li class="py-3 border-t">
                    <div class="flex items-center">
                        <div class="w-1/2 md:w-1/2 lg:w-3/5">
                            <div class="flex">
                                <img src="{{ asset('images/products/product-img-2.jpg') }}" alt="Ecommerce"
                                    class="w-16 h-16" />
                                <div class="ml-3">
                                    <a href="#!" class="text-inherit">
                                        <h6>NutriChoice Digestive</h6>
                                    </a>
                                    <span><small class="text-gray-500">250g</small></span>
                                    <!-- text -->
                                    <div class="mt-2 small leading-none">
                                        <a href="#!" class="text-green-600 flex items-center">
                                            <span class="mr-1 align-text-bottom">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-trash" width="14"
                                                    height="14" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </span>
                                            <span class="text-gray-500 text-sm">Xóa</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- input group -->
                        <div class="w-1/3 md:w-1/4 lg:w-1/5">
                            <!-- input -->
                            <div class="input-group input-spinner rounded-lg flex justify-between items-center">
                                <input type="button" value="-"
                                    class="button-minus w-8 py-1 border-r cursor-pointer border-gray-300"
                                    data-field="quantity" />
                                <input type="number" step="1" max="10" value="1" name="quantity"
                                    class="quantity-field w-9 px-2 text-center h-7 border-0 bg-transparent" />
                                <input type="button" value="+"
                                    class="button-plus w-8 py-1 border-l cursor-pointer border-gray-300"
                                    data-field="quantity" />
                            </div>
                        </div>
                        <!-- price -->
                        <div class="w-1/5 text-center md:w-1/5">
                            <span class="font-bold text-red-600">$20.00</span>
                            <div class="line-through text-gray-500 small">$26.00</div>
                        </div>
                    </div>
                </li>
                <!-- list group -->
                <li class="py-3 border-t">
                    <div class="flex items-center">
                        <div class="w-1/2 md:w-1/2 lg:w-3/5">
                            <div class="flex">
                                <img src="{{ asset('images/products/product-img-3.jpg') }}" alt="Ecommerce"
                                    class="w-16 h-16" />
                                <div class="ml-3">
                                    <!-- title -->
                                    <a href="#!" class="text-inherit">
                                        <h6>Cadbury 5 Star Chocolate</h6>
                                    </a>
                                    <span><small class="text-gray-500">1 kg</small></span>
                                    <!-- text -->
                                    <div class="mt-2 small leading-none">
                                        <a href="#!" class="text-green-600 flex items-center">
                                            <span class="mr-1 align-text-bottom">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-trash" width="14"
                                                    height="14" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </span>
                                            <span class="text-gray-500 text-sm">Xóa</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- input group -->
                        <div class="w-1/3 md:w-1/4 lg:w-1/5">
                            <!-- input -->
                            <div class="input-group input-spinner rounded-lg flex justify-between items-center">
                                <input type="button" value="-"
                                    class="button-minus w-8 py-1 border-r cursor-pointer border-gray-300"
                                    data-field="quantity" />
                                <input type="number" step="1" max="10" value="1" name="quantity"
                                    class="quantity-field w-9 px-2 text-center h-7 border-0 bg-transparent" />
                                <input type="button" value="+"
                                    class="button-plus w-8 py-1 border-l cursor-pointer border-gray-300"
                                    data-field="quantity" />
                            </div>
                        </div>
                        <!-- price -->
                        <div class="w-1/5 text-center md:w-1/5">
                            <span class="font-bold text-gray-800">$15.00</span>
                            <div class="line-through text-gray-500 small">$20.00</div>
                        </div>
                    </div>
                </li>
                <!-- list group -->
                <li class="py-3 border-t">
                    <div class="flex items-center">
                        <div class="w-1/2 md:w-1/2 lg:w-3/5">
                            <div class="flex">
                                <img src="{{ asset('images/products/product-img-4.jpg') }}" alt="Ecommerce"
                                    class="w-16 h-16" />
                                <div class="ml-3">
                                    <!-- title -->
                                    <!-- title -->
                                    <a href="#!" class="text-inherit">
                                        <h6>Onion Flavour Potato</h6>
                                    </a>
                                    <span><small class="text-gray-500">250g</small></span>
                                    <!-- text -->
                                    <div class="mt-2 small leading-none">
                                        <a href="#!" class="text-green-600 flex items-center">
                                            <span class="mr-1 align-text-bottom">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-trash" width="14"
                                                    height="14" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </span>
                                            <span class="text-gray-500 text-sm">Xóa</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- input group -->
                        <div class="w-1/3 md:w-1/4 lg:w-1/5">
                            <!-- input -->
                            <div class="input-group input-spinner rounded-lg flex justify-between items-center">
                                <input type="button" value="-"
                                    class="button-minus w-8 py-1 border-r cursor-pointer border-gray-300"
                                    data-field="quantity" />
                                <input type="number" step="1" max="10" value="1" name="quantity"
                                    class="quantity-field w-9 px-2 text-center h-7 border-0 bg-transparent" />
                                <input type="button" value="+"
                                    class="button-plus w-8 py-1 border-l cursor-pointer border-gray-300"
                                    data-field="quantity" />
                            </div>
                        </div>
                        <!-- price -->
                        <div class="w-1/5 text-center md:w-1/5">
                            <span class="font-bold text-gray-800">$15.00</span>
                            <div class="line-through text-gray-500 small">$20.00</div>
                        </div>
                    </div>
                </li>
                <!-- list group -->
                <li class="py-3 border-t border-b">
                    <div class="flex items-center">
                        <div class="w-1/2 md:w-1/2 lg:w-3/5">
                            <div class="flex">
                                <img src="{{ asset('images/products/product-img-5.jpg') }}" alt="Ecommerce"
                                    class="w-16 h-16" />
                                <div class="ml-3">
                                    <!-- title -->
                                    <a href="#!" class="text-inherit">
                                        <h6>Salted Instant Popcorn</h6>
                                    </a>
                                    <span><small class="text-gray-500">100g</small></span>
                                    <!-- text -->
                                    <div class="mt-2 small leading-none">
                                        <a href="#!" class="text-green-600 flex items-center">
                                            <span class="mr-1 align-text-bottom">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="icon icon-tabler icon-tabler-trash" width="14"
                                                    height="14" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor" fill="none" stroke-linecap="round"
                                                    stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                    <path d="M4 7l16 0" />
                                                    <path d="M10 11l0 6" />
                                                    <path d="M14 11l0 6" />
                                                    <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                                    <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                                                </svg>
                                            </span>
                                            <span class="text-gray-500 text-sm">Remove</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- input group -->
                        <div class="w-1/3 md:w-1/4 lg:w-1/5">
                            <!-- input -->
                            <div class="input-group input-spinner rounded-lg flex justify-between items-center">
                                <input type="button" value="-"
                                    class="button-minus w-8 py-1 border-r cursor-pointer border-gray-300"
                                    data-field="quantity" />
                                <input type="number" step="1" max="10" value="1" name="quantity"
                                    class="quantity-field w-9 px-2 text-center h-7 border-0 bg-transparent" />
                                <input type="button" value="+"
                                    class="button-plus w-8 py-1 border-l cursor-pointer border-gray-300"
                                    data-field="quantity" />
                            </div>
                        </div>
                        <!-- price -->
                        <div class="w-1/5 text-center md:w-1/5">
                            <span class="font-bold text-gray-800">$15.00</span>
                            <div class="line-through text-gray-500 small">$25.00</div>
                        </div>
                    </div>
                </li>
            </ul>
            <!-- btn -->
            <div class="flex justify-between mt-4">
                <a href="#!"
                    class="btn inline-flex items-center gap-x-2 bg-green-600 text-white border-green-600 disabled:opacity-50 disabled:pointer-events-none hover:text-white hover:bg-green-700 hover:border-green-700 active:bg-green-700 active:border-green-700 focus:outline-none focus:ring-4 focus:ring-green-300">
                    Tiếp tục mua sắm
                </a>
                <a href="#!"
                    class="btn inline-flex items-center gap-x-2 bg-gray-800 text-white border-gray-800 disabled:opacity-50 disabled:pointer-events-none hover:text-white hover:bg-gray-900 hover:border-gray-900 active:bg-gray-900 active:border-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300">
                    Cập nhật giỏ hàng
                </a>
            </div>
        </div>
    </div>
</div>

<!-- địa chỉ  -->
<script>
    function openGoogleMaps() {
        const dest = '180 Cao Lỗ, Phường, Chánh Hưng, Hồ Chí Minh 700000, Việt Nam';
        const url = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dest);
        window.open(url, '_blank');
    }
</script>

<!-- Favorites Offcanvas -->
<div class="offcanvas offcanvas-right" tabindex="-1" id="offcanvasFavorites"
    aria-labelledby="offcanvasFavoritesLabel">
    <div class="offcanvas-header border-b">
        <div>
            <h5 id="offcanvasFavoritesLabel">Sản phẩm yêu thích</h5>
        </div>
        <button type="button" class="btn-close text-inherit" data-bs-dismiss="offcanvas" aria-label="Close">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-x text-gray-700"
                width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M18 6l-12 12" />
                <path d="M6 6l12 12" />
            </svg>
        </button>
    </div>
    <div class="offcanvas-body p-4">
        <div id="favoritesList">
            <div class="text-center text-gray-500 py-6">
                Chưa có sản phẩm yêu thích.
            </div>
        </div>
    </div>
</div>
