// Bong bóng nổi "Giỏ hàng" + "Zalo" — chỉ 2 việc:
// 1. Bấm bong bóng giỏ hàng -> mở ĐÚNG cart drawer có sẵn (giả lập click nút #cart-btn thật trên
//    navbar, tái dùng nguyên logic openCart() đã có trong navbar.js thay vì viết lại).
// 2. Đẩy cả cụm lên tránh đè thanh bottom-nav trên mobile.
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
