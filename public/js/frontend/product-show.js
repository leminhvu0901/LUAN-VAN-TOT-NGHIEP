(function() {
    let qty = 1;
    let basePrice = window.productBasePrice || 0;
    let sizeAdj = 0;
    let toppingAdj = 0;
    const productId = window.productId || 0;

    function updatePrice() {
        const total = (basePrice + sizeAdj + toppingAdj) * qty;
        document.getElementById('pd-price').textContent = total.toLocaleString('vi-VN') + 'đ';
    }

    window.changeQty = function(delta) {
        qty = Math.max(1, qty + delta);
        document.getElementById('pd-qty-val').textContent = qty;
        updatePrice();
        const el = document.getElementById('pd-qty-val');
        el.classList.remove('pd-qty__val--bump');
        void el.offsetWidth;
        el.classList.add('pd-qty__val--bump');
    };

    window.selectSize = function(btn) {
        document.querySelectorAll('#pd-sizes .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        sizeAdj = parseFloat(btn.getAttribute('data-price-adj')) || 0;
        updatePrice();
    };

    window.selectOption = function(btn, groupId) {
        document.querySelectorAll('#' + groupId + ' .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
    };

    window.handleToppingChange = function(inputEl = null) {
        if (inputEl) {
            const labelEl = inputEl.closest('.topping-item-label');
            labelEl.classList.toggle('is-selected', inputEl.checked);
        }
        toppingAdj = 0;
        let selectedNames = [];
        document.querySelectorAll('.topping-checkbox:checked').forEach(cb => {
            toppingAdj += parseFloat(cb.getAttribute('data-topping-price')) || 0;
            selectedNames.push(cb.getAttribute('data-topping-name'));
        });
        updatePrice();
        const summary = document.getElementById('topping-summary');
        const btn = document.getElementById('toppingDropdown');
        if (selectedNames.length > 0) {
            summary.innerText = selectedNames.join(', ');
            summary.style.color = '#065f46';
            btn.style.borderColor = '#10b981';
            btn.style.background = '#ecfdf5';
        } else {
            summary.innerText = 'Chọn topping (không bắt buộc)...';
            summary.style.color = '#6b7280';
            btn.style.borderColor = '#e5e7eb';
            btn.style.background = '#ffffff';
        }
    };

    const toppingDropdownBtn = document.getElementById('toppingDropdown');
    if (toppingDropdownBtn) {
        const dropdownContainer = toppingDropdownBtn.closest('.dropdown');
        const dropdownMenu = dropdownContainer ? dropdownContainer.querySelector('.dropdown-menu') : null;
        const chevron = toppingDropdownBtn.querySelector('.dropdown-chevron');

        if (dropdownMenu) {
            toppingDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = dropdownMenu.classList.contains('show');
                if (isOpen) {
                    dropdownMenu.classList.remove('show');
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                } else {
                    dropdownMenu.classList.add('show');
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                }
            });

            // Prevent dropdown from closing when clicking inside the dropdown menu (auto-close: outside)
            dropdownMenu.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            // Close when clicking outside
            document.addEventListener('click', () => {
                dropdownMenu.classList.remove('show');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            });
        }
    }

    window.addToCartFromDetail = function() {
        const btn = document.getElementById('pd-add-cart');
        btn.disabled = true;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Đang thêm...';

        const activeSize = document.querySelector('#pd-sizes .pd-chip.is-active');
        const activeSugar = document.querySelector('#pd-sugar .pd-chip.is-active');
        const activeIce = document.querySelector('#pd-ice .pd-chip.is-active');
        const activeToppings = document.querySelectorAll('.topping-checkbox:checked');
        const toppingIds = Array.from(activeToppings).map(cb => cb.value);

        const options = {
            size_name: activeSize ? activeSize.getAttribute('data-size-name') : null,
            sugar_level: activeSugar ? activeSugar.getAttribute('data-value') : null,
            ice_level: activeIce ? activeIce.getAttribute('data-value') : null,
            toppings: toppingIds
        };

        addToCart(productId, qty, options);

        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Thêm vào giỏ hàng';
        }, 1200);
    };

    window.switchImage = function(thumb, url) {
        document.querySelectorAll('.pd-gallery__thumb').forEach(t => t.classList.remove('is-active'));
        thumb.classList.add('is-active');
        const mainImg = document.getElementById('pd-main-img');
        mainImg.style.opacity = '0';
        setTimeout(() => { mainImg.src = url; mainImg.style.opacity = '1'; }, 180);
    };

    updatePrice();
})();
