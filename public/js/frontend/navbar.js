(function () {
    // Hàm rút gọn document.getElementById để viết code ngắn gọn hơn
    const $ = (id) => document.getElementById(id);

    /* ====================================================================
       1. NGĂN KÉO YÊU THÍCH (WISHLIST DRAWER)
       Được gọi bởi nút #wishlist-btn (Xuất hiện đầu tiên trên Navbar)
       ==================================================================== */
    const openWishlist = () => {
        const drawer = $('wishlist-drawer');
        const overlay = $('wishlist-overlay'); // Lớp phủ màu đen phía sau
        if (!drawer || !overlay) return;

        // Hiện lớp phủ
        overlay.style.display = 'block';

        // Dùng requestAnimationFrame để trình duyệt render mượt mà hiệu ứng trượt CSS
        requestAnimationFrame(() => {
            drawer.style.transform = 'translateX(0)'; // Kéo ngăn kéo vào trong màn hình
            overlay.style.opacity = '1'; // Làm mờ màn hình nền phía sau
        });

        // Khóa thanh cuộn của trang web (body) để user không cuộn trang được khi ngăn kéo đang mở
        document.body.style.overflow = 'hidden';
    };

    const closeWishlist = () => {
        
        const drawer = $('wishlist-drawer');
        const overlay = $('wishlist-overlay');
        if (!drawer || !overlay) return;

        // Trượt ngăn kéo giấu ra ngoài lề phải (100%)
        drawer.style.transform = 'translateX(100%)';
        overlay.style.opacity = '0'; // Xóa lớp mờ

        // Đợi 320ms cho CSS Transition trượt xong rồi mới ẩn hẳn lớp phủ
        setTimeout(() => { overlay.style.display = 'none'; }, 320);

        // Mở khóa cuộn trang web
        document.body.style.overflow = '';
    };

    // Gắn sự kiện click cho các nút: Nút mở, Nút đóng (dấu X) và Bấm ra ngoài (overlay) để đóng
    $('wishlist-btn')?.addEventListener('click', openWishlist);
    $('wishlist-close')?.addEventListener('click', closeWishlist);
    $('wishlist-overlay')?.addEventListener('click', closeWishlist);


    /* ====================================================================
       2. NGĂN KÉO GIỎ HÀNG (CART DRAWER)
       Được gọi bởi nút #cart-btn (Xuất hiện thứ hai trên Navbar)
       ==================================================================== */
    const openCart = () => {
        const drawer = $('cart-drawer');
        const overlay = $('cart-overlay');
        if (!drawer || !overlay) return;

        // QUAN TRỌNG: Gọi hàm loadCart (đã viết bên main.js) để fetch dữ liệu từ Backend trước khi mở
        if (typeof window.loadCart === 'function') window.loadCart();

        overlay.style.display = 'block';
        requestAnimationFrame(() => {
            drawer.style.transform = 'translateX(0)';
            overlay.style.opacity = '1';
        });
        document.body.style.overflow = 'hidden';
    };

    const closeCart = () => {
        const drawer = $('cart-drawer');
        const overlay = $('cart-overlay');
        if (!drawer || !overlay) return;
        drawer.style.transform = 'translateX(100%)';
        overlay.style.opacity = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 320);
        document.body.style.overflow = '';
    };

    $('cart-btn')?.addEventListener('click', openCart);
    $('cart-close')?.addEventListener('click', closeCart);
    $('cart-overlay')?.addEventListener('click', closeCart);


    /* ====================================================================
       3. MENU HAMBURGER (MOBILE MENU)
       Được gọi bởi nút #hamburger (Xuất hiện cuối cùng, góc phải trên Mobile)
       ==================================================================== */
    $('hamburger')?.addEventListener('click', () => {
        const menu = $('mobile-menu'); // Khung menu thả xuống
        const search = $('mobile-search-bar'); // Thanh tìm kiếm trên mobile
        const ham = $('ham-icon'); // Icon 3 gạch ngang
        const close = $('close-icon'); // Icon dấu X

        if (!menu || !ham || !close) return; // Nếu thiếu các thành phần này thì dừng lại

        // Bật/tắt class 'open' cho menu. Trả về true nếu vừa được mở, false nếu đóng
        const isOpen = menu.classList.toggle('open');

        if (search) search.classList.toggle('open', isOpen);

        // Ẩn icon 3 gạch khi menu đang mở, và ngược lại
        ham.classList.toggle('hidden', isOpen);
        // Hiện icon X khi menu đang mở, và ngược lại
        close.classList.toggle('hidden', !isOpen);
    });


    /* ====================================================================
       4. SỰ KIỆN TOÀN CỤC (GLOBAL EVENTS)
       ==================================================================== */
    // Bắt sự kiện phím tắt trên bàn phím: Bấm phím "ESC" để đóng các ngăn kéo nhanh chóng
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeWishlist(); closeCart(); }
    });
})();
