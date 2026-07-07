/**
 * File Javascript điều khiển tương tác trên trang Chi tiết sản phẩm (Product Detail)
 * Quản lý tính toán giá động (kích cỡ, topping, số lượng), hiệu ứng chuyển ảnh gallery và gửi giỏ hàng.
 */
(function() {
    // Biến lưu trữ trạng thái hiện tại của sản phẩm
    let qty = 1; // Số lượng đặt mua (mặc định = 1)
    
    // Tìm thẻ wrapper chính để đọc thông tin sản phẩm do PHP gắn vào data attributes
    const wrapper = document.querySelector('.pd-wrapper');
    
    // Giá tiền cơ bản của sản phẩm (chưa cộng size, topping)
    let basePrice = wrapper ? (parseFloat(wrapper.getAttribute('data-base-price')) || 0) : 0;
    
    // Số tiền chênh lệch của kích cỡ (size) được chọn
    let sizeAdj = 0; 
    
    // Tổng số tiền cộng thêm từ tất cả các Topping được tích chọn
    let toppingAdj = 0; 
    
    // ID của sản phẩm hiện tại
    const productId = wrapper ? (parseInt(wrapper.getAttribute('data-product-id')) || 0) : 0;

    /**
     * Hàm tính toán và cập nhật giá hiển thị trên giao diện theo công thức:
     * Tổng tiền = (Giá gốc + Giá size chênh lệch + Tổng giá topping) * Số lượng
     */
    function updatePrice() {
        const total = (basePrice + sizeAdj + toppingAdj) * qty;
        // Định dạng số tiền theo tiêu chuẩn Việt Nam (Ví dụ: 50.000đ)
        document.getElementById('pd-price').textContent = total.toLocaleString('vi-VN') + 'đ';
    }

    /**
     * Thay đổi số lượng đặt mua sản phẩm (Tăng/Giảm)
     * @param {number} delta - Giá trị thay đổi (ví dụ: -1 để giảm, 1 để tăng)
     */
    window.changeQty = function(delta) {
        // Đảm bảo số lượng luôn lớn hơn hoặc bằng 1
        qty = Math.max(1, qty + delta);
        document.getElementById('pd-qty-val').textContent = qty;
        updatePrice();
        
        // Tạo hiệu ứng phóng to nhẹ (bump animation) khi số lượng thay đổi
        const el = document.getElementById('pd-qty-val');
        el.classList.remove('pd-qty__val--bump');
        void el.offsetWidth; // Trigger reflow để reset animation
        el.classList.add('pd-qty__val--bump');
    };

    /**
     * Xử lý khi người dùng chọn kích cỡ (Size) sản phẩm
     * @param {HTMLElement} btn - Nút size vừa nhấp chọn
     */
    window.selectSize = function(btn) {
        // Gỡ bỏ class active của nút size cũ và gán cho nút mới
        document.querySelectorAll('#pd-sizes .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        
        // Đọc giá chênh lệch của size vừa chọn từ data-price-adj
        sizeAdj = parseFloat(btn.getAttribute('data-price-adj')) || 0;
        updatePrice();
    };

    /**
     * Xử lý chọn các tùy chọn chung có dạng nhãn (như mức đường, mức đá)
     * @param {HTMLElement} btn - Nút vừa nhấp chọn
     * @param {string} groupId - ID của vùng chứa nhóm nút đó
     */
    window.selectOption = function(btn, groupId) {
        document.querySelectorAll('#' + groupId + ' .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
    };

    /**
     * Lắng nghe sự thay đổi của checkbox chọn Topping
     * @param {HTMLInputElement|null} inputEl - Checkbox topping vừa thay đổi trạng thái
     */
    window.handleToppingChange = function(inputEl = null) {
        // Tô màu nổi bật dòng topping nếu checkbox được tích chọn
        if (inputEl) {
            const labelEl = inputEl.closest('.topping-item-label');
            labelEl.classList.toggle('is-selected', inputEl.checked);
        }
        
        // Reset lại giá trị phụ thu topping và mảng tên các topping đã chọn
        toppingAdj = 0;
        let selectedNames = [];
        
        // Duyệt qua toàn bộ các checkbox topping đang được chọn để tính tổng tiền và gom tên
        document.querySelectorAll('.topping-checkbox:checked').forEach(cb => {
            toppingAdj += parseFloat(cb.getAttribute('data-topping-price')) || 0;
            selectedNames.push(cb.getAttribute('data-topping-name'));
        });
        updatePrice();
        
        // Cập nhật nhãn hiển thị danh sách topping đã chọn trên thanh dropdown
        const summary = document.getElementById('topping-summary');
        const btn = document.getElementById('toppingDropdown');
        if (selectedNames.length > 0) {
            summary.innerText = selectedNames.join(', ');
            summary.style.color = '#065f46'; // Màu xanh đậm nổi bật
            btn.style.borderColor = '#10b981';
            btn.style.background = '#ecfdf5';
        } else {
            summary.innerText = 'Chọn topping (không bắt buộc)...';
            summary.style.color = '#6b7280';
            btn.style.borderColor = '#e5e7eb';
            btn.style.background = '#ffffff';
        }
    };

    // Điều khiển hành vi mở/đóng và xoay mũi tên của Dropdown Topping
    const toppingDropdownBtn = document.getElementById('toppingDropdown');
    if (toppingDropdownBtn) {
        const dropdownContainer = toppingDropdownBtn.closest('.dropdown');
        const dropdownMenu = dropdownContainer ? dropdownContainer.querySelector('.dropdown-menu') : null;
        const chevron = toppingDropdownBtn.querySelector('.dropdown-chevron');

        if (dropdownMenu) {
            // Lắng nghe sự kiện click vào nút Dropdown để bật/tắt bảng danh sách
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

            // Ngăn chặn sự kiện tự đóng dropdown khi nhấp chuột bên trong menu chọn topping
            dropdownMenu.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            // Tự động đóng dropdown chọn topping khi người dùng click ra vùng trống bất kỳ bên ngoài
            document.addEventListener('click', () => {
                dropdownMenu.classList.remove('show');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            });
        }
    }

    /**
     * Thu thập thông số đã chọn và thực hiện thêm sản phẩm vào giỏ hàng
     */
    window.addToCartFromDetail = function() {
        const btn = document.getElementById('pd-add-cart');
        btn.disabled = true; // Vô hiệu hóa nút tạm thời để tránh spam click
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Đang thêm...';

        // Lấy các giá trị thuộc tính (size, mức đường, mức đá, danh sách topping ID) đang được active
        const activeSize = document.querySelector('#pd-sizes .pd-chip.is-active');
        const activeSugar = document.querySelector('#pd-sugar .pd-chip.is-active');
        const activeIce = document.querySelector('#pd-ice .pd-chip.is-active');
        const activeToppings = document.querySelectorAll('.topping-checkbox:checked');
        const toppingIds = Array.from(activeToppings).map(cb => cb.value);

        // Đóng gói các tùy chọn
        const options = {
            size_name: activeSize ? activeSize.getAttribute('data-size-name') : null,
            sugar_level: activeSugar ? activeSugar.getAttribute('data-value') : null,
            ice_level: activeIce ? activeIce.getAttribute('data-value') : null,
            toppings: toppingIds
        };

        // Gọi hàm addToCart dùng chung của hệ thống (thực hiện gửi request AJAX lên server)
        addToCart(productId, qty, options);

        // Reset lại trạng thái nút bấm sau 1.2 giây
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Thêm vào giỏ hàng';
        }, 1200);
    };

    /**
     * Thay đổi ảnh hiển thị lớn khi click vào các ảnh thumbnail nhỏ
     * @param {HTMLElement} thumb - Phần tử thumbnail được click
     * @param {string} url - Đường dẫn ảnh lớn mới
     */
    window.switchImage = function(thumb, url) {
        // Gỡ active của thumbnail cũ và kích hoạt cho thumbnail mới
        document.querySelectorAll('.pd-gallery__thumb').forEach(t => t.classList.remove('is-active'));
        thumb.classList.add('is-active');
        
        // Thực hiện đổi ảnh lớn đi kèm hiệu ứng làm mờ mịn (fade-out / fade-in)
        const mainImg = document.getElementById('pd-main-img');
        mainImg.style.opacity = '0';
        setTimeout(() => { 
            mainImg.src = url; 
            mainImg.style.opacity = '1'; 
        }, 180);
    };

    // Chạy cập nhật tính toán giá tiền lần đầu khi tải trang thành công
    updatePrice();
})();
