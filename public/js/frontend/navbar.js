(function () {
    const $ = (id) => document.getElementById(id);

    /* ---- Dropdown ---- */
    const closeAll = () => {
        ['products-dropdown'].forEach((id) => {
            const el = $(id);
            if (!el) return;
            el.classList.remove('open');
            el.classList.add('hidden');
        });
        const chev = $('products-chevron');
        if (chev) chev.style.transform = '';
        const btn = $('products-btn');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    };

    const toggleDropdown = (btn, dropdown, chevron) => {
        if (!btn || !dropdown) return;
        const willOpen = dropdown.classList.contains('hidden');
        closeAll();
        if (willOpen) {
            dropdown.classList.remove('hidden');
            dropdown.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
            if (chevron) chevron.style.transform = 'rotate(180deg)';
        }
    };

    $('products-btn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleDropdown($('products-btn'), $('products-dropdown'), $('products-chevron'));
    });

    document.addEventListener('click', closeAll);
    ['products-dropdown'].forEach((id) => {
        $(id)?.addEventListener('click', (e) => e.stopPropagation());
    });

    /* ---- Hamburger (mobile) ---- */
    $('hamburger')?.addEventListener('click', () => {
        const menu  = $('mobile-menu');
        const ham   = $('ham-icon');
        const close = $('close-icon');
        if (!menu || !ham || !close) return;
        const isOpen = menu.classList.toggle('open');
        ham.classList.toggle('hidden', isOpen);
        close.classList.toggle('hidden', !isOpen);
    });

    /* ---- Wishlist drawer ---- */
    const openWishlist = () => {
        const drawer  = $('wishlist-drawer');
        const overlay = $('wishlist-overlay');
        if (!drawer || !overlay) return;
        overlay.style.display = 'block';
        requestAnimationFrame(() => {
            drawer.style.transform = 'translateX(0)';
            overlay.style.opacity  = '1';
        });
        document.body.style.overflow = 'hidden';
    };

    const closeWishlist = () => {
        const drawer  = $('wishlist-drawer');
        const overlay = $('wishlist-overlay');
        if (!drawer || !overlay) return;
        drawer.style.transform = 'translateX(100%)';
        overlay.style.opacity  = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 320);
        document.body.style.overflow = '';
    };

    $('wishlist-btn')?.addEventListener('click', openWishlist);
    $('wishlist-close')?.addEventListener('click', closeWishlist);
    $('wishlist-overlay')?.addEventListener('click', closeWishlist);

    /* ---- Cart drawer ---- */
    const openCart = () => {
        const drawer  = $('cart-drawer');
        const overlay = $('cart-overlay');
        if (!drawer || !overlay) return;
        if (typeof window.loadCart === 'function') window.loadCart();
        overlay.style.display = 'block';
        requestAnimationFrame(() => {
            drawer.style.transform = 'translateX(0)';
            overlay.style.opacity  = '1';
        });
        document.body.style.overflow = 'hidden';
    };

    const closeCart = () => {
        const drawer  = $('cart-drawer');
        const overlay = $('cart-overlay');
        if (!drawer || !overlay) return;
        drawer.style.transform = 'translateX(100%)';
        overlay.style.opacity  = '0';
        setTimeout(() => { overlay.style.display = 'none'; }, 320);
        document.body.style.overflow = '';
    };

    $('cart-btn')?.addEventListener('click', openCart);
    $('cart-close')?.addEventListener('click', closeCart);
    $('cart-overlay')?.addEventListener('click', closeCart);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { closeWishlist(); closeCart(); }
    });
})();
