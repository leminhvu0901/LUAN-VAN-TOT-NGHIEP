/**
 * File Javascript điều khiển tương tác trên trang Chi tiết sản phẩm (Product Detail)
 * Quản lý tính toán giá động (kích cỡ, topping, số lượng), hiệu ứng chuyển ảnh gallery và gửi giỏ hàng.
 */
(function() {
    // Biến lưu trữ số lượng đặt mua sản phẩm hiện tại (mặc định ban đầu = 1)
    let qty = 1;
    
    // Tìm thẻ wrapper chính (.pd-wrapper) chứa thông tin sản phẩm do PHP truyền qua các thuộc tính data-*
    const wrapper = document.querySelector('.pd-wrapper');
    
    // Đọc giá tiền cơ bản của sản phẩm (đã gồm giảm giá nếu có, chưa tính phụ thu size/topping)
    let basePrice = wrapper ? (parseFloat(wrapper.getAttribute('data-base-price')) || 0) : 0;
    
    // Khoản tiền cộng thêm hoặc chênh lệch của kích cỡ (size) được chọn (mặc định = 0)
    let sizeAdj = 0; 
    
    // Tổng chi phí cộng thêm từ tất cả các Topping được khách hàng tích chọn (mặc định = 0)
    let toppingAdj = 0; 
    
    // ID của sản phẩm hiện tại để gửi lên server khi thêm vào giỏ hàng
    const productId = wrapper ? (parseInt(wrapper.getAttribute('data-product-id')) || 0) : 0;

    /**
     * Hàm tính toán và cập nhật lại đơn giá hiển thị trên giao diện.
     * Công thức: Đơn giá = Giá gốc + Phụ thu kích cỡ + Tổng tiền topping chọn thêm.
     * Lưu ý: Đơn giá này đại diện cho 1 sản phẩm để người dùng dễ đối chiếu.
     */
    function updatePrice() {
        const unitPrice = basePrice + sizeAdj + toppingAdj;
        // Định dạng số tiền theo chuẩn tiền tệ Việt Nam (vi-VN) và nối thêm ký tự 'đ'
        document.getElementById('pd-price').textContent = unitPrice.toLocaleString('vi-VN') + 'đ';
    }

    /**
     * Thay đổi số lượng đặt mua sản phẩm (khi click nút tăng (+) hoặc giảm (-))
     * @param {number} delta - Lượng thay đổi (thường là -1 hoặc 1)
     */
    window.changeQty = function(delta) {
        // Đảm bảo số lượng mua luôn tối thiểu là 1 sản phẩm
        qty = Math.max(1, qty + delta);
        // Cập nhật số lượng mới lên màn hình hiển thị
        document.getElementById('pd-qty-val').textContent = qty;
        updatePrice();
        
        // Tạo hiệu ứng giật nhẹ số lượng (bump animation) để phản hồi thị giác tốt hơn
        const el = document.getElementById('pd-qty-val');
        el.classList.remove('pd-qty__val--bump');
        void el.offsetWidth; // Buộc trình duyệt thực hiện reflow để reset lại CSS keyframe animation
        el.classList.add('pd-qty__val--bump');
    };

    /**
     * Xử lý khi người dùng chọn kích cỡ (Size) của sản phẩm
     * @param {HTMLElement} btn - Nút Size vừa được click
     */
    window.selectSize = function(btn) {
        // Xóa class active ở nút kích cỡ đang chọn trước đó và gắn active cho nút mới click
        document.querySelectorAll('#pd-sizes .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
        
        // Đọc giá trị phụ thu tương ứng của size vừa chọn từ thuộc tính data-price-adj
        sizeAdj = parseFloat(btn.getAttribute('data-price-adj')) || 0;
        // Tính lại giá tiền hiển thị mới
        updatePrice();
    };

    /**
     * Xử lý chọn các tùy chọn dạng nhãn (không phát sinh tiền) như Mức đường hoặc Mức đá
     * @param {HTMLElement} btn - Nút tùy chọn vừa được click
     * @param {string} groupId - ID của vùng chứa nhóm nút đó (ví dụ: 'pd-sugar', 'pd-ice')
     */
    window.selectOption = function(btn, groupId) {
        // Gỡ bỏ trạng thái active của nút cũ trong cùng nhóm và kích hoạt cho nút mới
        document.querySelectorAll('#' + groupId + ' .pd-chip').forEach(b => b.classList.remove('is-active'));
        btn.classList.add('is-active');
    };

    /**
     * Lắng nghe sự thay đổi trạng thái của các checkbox chọn Topping
     * @param {HTMLInputElement|null} inputEl - Checkbox topping vừa thay đổi trạng thái
     */
    window.handleToppingChange = function(inputEl = null) {
        // Thêm hoặc gỡ class .is-selected để đổi màu nền dòng topping được chọn
        if (inputEl) {
            const labelEl = inputEl.closest('.topping-item-label');
            labelEl.classList.toggle('is-selected', inputEl.checked);
        }
        
        // Reset lại tổng tiền phụ thu topping và danh sách tên các topping được chọn
        toppingAdj = 0;
        let selectedNames = [];
        
        // Lặp qua tất cả các checkbox topping đang được tích chọn
        document.querySelectorAll('.topping-checkbox:checked').forEach(cb => {
            // Cộng dồn giá tiền topping
            toppingAdj += parseFloat(cb.getAttribute('data-topping-price')) || 0;
            // Lưu lại tên của topping để hiển thị tóm tắt
            selectedNames.push(cb.getAttribute('data-topping-name'));
        });
        
        // Cập nhật lại giá hiển thị trên màn hình
        updatePrice();
        
        // Cập nhật nhãn hiển thị tóm tắt danh sách các topping đã chọn trên nút Dropdown
        const summary = document.getElementById('topping-summary');
        const btn = document.getElementById('toppingDropdown');
        if (selectedNames.length > 0) {
            // Hiển thị tên các topping phân tách bằng dấu phẩy
            summary.innerText = selectedNames.join(', ');
            summary.style.color = '#065f46'; // Đổi sang tông màu xanh lá đậm nổi bật
            btn.style.borderColor = '#10b981';
            btn.style.background = '#ecfdf5';
        } else {
            // Trả về nhãn mặc định khi không chọn topping nào
            summary.innerText = 'Chọn topping (không bắt buộc)...';
            summary.style.color = '#6b7280';
            btn.style.borderColor = '#e5e7eb';
            btn.style.background = '#ffffff';
        }
    };

    // Điều khiển hành vi mở/đóng và xoay mũi tên chỉ hướng của Dropdown Topping
    const toppingDropdownBtn = document.getElementById('toppingDropdown');
    if (toppingDropdownBtn) {
        const dropdownContainer = toppingDropdownBtn.closest('.dropdown');
        const dropdownMenu = dropdownContainer ? dropdownContainer.querySelector('.dropdown-menu') : null;
        const chevron = toppingDropdownBtn.querySelector('.dropdown-chevron');

        if (dropdownMenu) {
            // Bật/tắt hiển thị menu topping khi click vào nút dropdown chính
            toppingDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Ngăn sự kiện lan rộng lên document
                const isOpen = dropdownMenu.classList.contains('show');
                if (isOpen) {
                    dropdownMenu.classList.remove('show');
                    if (chevron) chevron.style.transform = 'rotate(0deg)'; // Xoay mũi tên về vị trí cũ
                } else {
                    dropdownMenu.classList.add('show');
                    if (chevron) chevron.style.transform = 'rotate(180deg)'; // Xoay mũi tên chỉ lên
                }
            });

            // Ngăn chặn sự kiện tự đóng dropdown khi người dùng click vào các phần tử bên trong menu topping
            dropdownMenu.addEventListener('click', (e) => {
                e.stopPropagation();
            });

            // Tự động ẩn menu chọn topping khi người dùng click ra vùng trống ngoài dropdown
            document.addEventListener('click', () => {
                dropdownMenu.classList.remove('show');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            });
        }
    }

    /**
     * Thu thập thông tin cấu hình sản phẩm đã chọn và gửi request thêm vào giỏ hàng
     */
    window.addToCartFromDetail = function() {
        const btn = document.getElementById('pd-add-cart');
        btn.disabled = true; // Vô hiệu hóa nút tạm thời để tránh spam gửi nhiều request liên tục
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Đang thêm...';

        // Lấy các nút tùy chọn đang được active để lấy giá trị tương ứng
        const activeSize = document.querySelector('#pd-sizes .pd-chip.is-active');
        const activeSugar = document.querySelector('#pd-sugar .pd-chip.is-active');
        const activeIce = document.querySelector('#pd-ice .pd-chip.is-active');
        const activeToppings = document.querySelectorAll('.topping-checkbox:checked');
        const toppingIds = Array.from(activeToppings).map(cb => cb.value); // Lấy danh sách ID các topping đã chọn

        // Đóng gói các lựa chọn chi tiết cấu hình sản phẩm
        const options = {
            size_name: activeSize ? activeSize.getAttribute('data-size-name') : null,
            sugar_level: activeSugar ? activeSugar.getAttribute('data-value') : null,
            ice_level: activeIce ? activeIce.getAttribute('data-value') : null,
            toppings: toppingIds
        };

        // Gọi hàm addToCart dùng chung của hệ thống (hàm này gửi AJAX POST lên endpoint giỏ hàng)
        addToCart(productId, qty, options);

        // Khôi phục lại trạng thái ban đầu của nút bấm sau khi thực hiện xong (khoảng 1.2s trì hoãn)
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Thêm vào giỏ hàng';
        }, 1200);
    };

    /**
     * Thay đổi ảnh lớn của sản phẩm khi click chọn các ảnh thumbnail nhỏ
     * @param {HTMLElement} thumb - Phần tử thumbnail được click
     * @param {string} url - Đường dẫn ảnh lớn mới
     */
    window.switchImage = function(thumb, url) {
        // Gỡ bỏ class active ở thumbnail đang hoạt động trước đó và gán cho thumbnail mới
        document.querySelectorAll('.pd-gallery__thumb').forEach(t => t.classList.remove('is-active'));
        thumb.classList.add('is-active');
        
        // Đổi đường dẫn ảnh lớn đi kèm hiệu ứng chuyển tiếp mờ mịn (fade)
        const mainImg = document.getElementById('pd-main-img');
        mainImg.style.opacity = '0'; // Ẩn mờ ảnh cũ
        setTimeout(() => { 
            mainImg.src = url; // Đổi đường dẫn nguồn ảnh mới
            mainImg.style.opacity = '1'; // Hiện rõ ảnh mới lên
        }, 180); // Chờ 180ms để khớp với hiệu ứng chuyển tiếp CSS transition opacity
    };

    // Chạy tính toán và hiển thị đơn giá lần đầu khi tải trang
    updatePrice();
})();

