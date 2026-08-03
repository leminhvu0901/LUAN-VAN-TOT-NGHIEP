/**
 * form-common.js - Xử lý logic dùng chung cho form Thêm/Sửa Sản phẩm
 * Các tính năng bao gồm:
 * - Quản lý thêm/xóa dòng kích thước (size) và giá điều chỉnh tương ứng.
 * - Xem trước ảnh tải lên (Ảnh đại diện sản phẩm & Bộ sưu tập ảnh phụ).
 * - Tự động định dạng giá bán thành định dạng hiển thị tiền tệ VNĐ có phân tách dấu chấm.
 * - Xóa ảnh phụ bất đồng bộ (AJAX) khỏi thư viện ảnh sản phẩm.
 * - Khởi tạo thư viện lựa chọn thông minh Choices.js cho danh mục sản phẩm.
 */
(function () {
    // Giới hạn dung lượng ảnh tải lên tối đa là 2MB
    const maxImageBytes = 2 * 1024 * 1024;

    /**
     * Hiển thị thông báo lỗi sử dụng SweetAlert2 nếu có, hoặc dùng alert của trình duyệt
     * @param {string} message - Nội dung thông báo lỗi
     */
    function showError(message) {
        if (window.Swal) {
            Swal.fire({ 
                icon: 'error', 
                title: 'Thông báo', 
                text: message, 
                confirmButtonText: 'Đóng' 
            });
        } else {
            window.alert(message);
        }
    }

    /**
     * Tạo một hàng giao diện nhập kích thước mới (Size)
     * @returns {HTMLDivElement} - Đối tượng DOM chứa các ô nhập tên size, giá cộng thêm và nút xóa
     */
    function createSizeRow() {
        const row = document.createElement('div');
        row.className = 'product-size-row grid grid-cols-[1fr_1fr_40px] gap-2';
        row.innerHTML = 
            '<input name="size_names[]" maxlength="50" placeholder="Tên size" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
            '<input name="size_price_adjustments[]" type="number" min="0" max="50000000" step="1000" value="0" placeholder="Giá cộng thêm" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
            '<button type="button" class="js-remove-size w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg" title="Xóa kích thước">' +
                '<span class="material-symbols-outlined">delete</span>' +
            '</button>';
        return row;
    }

    /**
     * Khởi tạo quản lý danh sách kích cỡ sản phẩm (Size)
     */
    function initSizes() {
        const container = document.getElementById('product-sizes');
        const addButton = document.getElementById('add-product-size');
        if (!container || !addButton) return;

        // Xử lý thêm hàng kích thước mới khi click nút (giới hạn tối đa 10 dòng)
        addButton.addEventListener('click', function () {
            if (container.querySelectorAll('.product-size-row').length < 10) {
                container.appendChild(createSizeRow());
            }
        });

        // Xử lý xóa hàng kích thước bằng cơ chế ủy quyền sự kiện (event delegation)
        container.addEventListener('click', function (event) {
            const button = event.target.closest('.js-remove-size');
            if (!button) return;
            const rows = container.querySelectorAll('.product-size-row');
            
            // Nếu chỉ còn 1 dòng duy nhất, không cho xóa mà chỉ reset trắng ô nhập
            if (rows.length === 1) {
                rows[0].querySelectorAll('input').forEach((input) => {
                    input.value = input.type === 'number' ? '0' : '';
                });
            } else {
                button.closest('.product-size-row').remove();
            }
        });
    }

    /**
     * Khởi tạo tính năng tải lên hình ảnh và xem trước (Preview) ảnh
     */
    function initImages() {
        const trigger = document.querySelector('.js-image-upload-trigger');
        const input = document.getElementById('image-upload');
        const gallery = document.getElementById('gallery-input');

        // Xử lý click vào vùng chọn ảnh đại diện để kích hoạt input file ẩn
        if (trigger && input) {
            trigger.addEventListener('click', (event) => { 
                if (event.target !== input) input.click(); 
            });

            // Lắng nghe thay đổi khi chọn file ảnh đại diện
            input.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                // Validate dung lượng ảnh đại diện tối đa 2MB
                if (file.size > maxImageBytes) { 
                    showError('Ảnh chính không được vượt quá 2MB.'); 
                    this.value = ''; 
                    return; 
                }

                // Đọc file ảnh dưới dạng DataURL để gán vào thẻ xem trước src img
                const reader = new FileReader();
                reader.onload = (event) => {
                    const preview = document.getElementById('image-preview');
                    preview.src = event.target.result;
                    preview.classList.remove('hidden');
                    document.getElementById('image-placeholder')?.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            });
        }

        // Lắng nghe thay đổi khi tải lên các ảnh phụ (Gallery)
        if (gallery) {
            gallery.addEventListener('change', function () {
                const files = Array.from(this.files || []);

                // Kiểm tra ràng buộc tối đa 5 hình ảnh phụ và dung lượng mỗi ảnh
                if (document.querySelectorAll('.gallery-item').length + files.length > 5 || 
                    files.some((file) => file.size > maxImageBytes)) {
                    showError('Tối đa 5 ảnh phụ và mỗi ảnh không được vượt quá 2MB.');
                    this.value = '';
                    return;
                }

                // Render xem trước các ảnh phụ mới chọn
                const container = document.getElementById('gallery-preview-container');
                container.innerHTML = '';
                files.forEach((file) => {
                    const reader = new FileReader();
                    reader.onload = (event) => {
                        const image = document.createElement('img');
                        image.src = event.target.result;
                        image.className = 'w-16 h-16 object-cover rounded-lg border border-gray-200';
                        container.appendChild(image);
                    };
                    reader.readAsDataURL(file);
                });
            });
        }
    }

    /**
     * Khởi tạo định dạng giá bán thời gian thực (Real-time formatting)
     */
    function initPrice() {
        const display = document.getElementById('display_price');
        const raw = document.getElementById('raw_price');
        if (!display || !raw) return;

        // Khi người dùng gõ vào ô nhập hiển thị
        display.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, ''); // Loại bỏ toàn bộ ký tự không phải là số

            // Ràng buộc giá bán tối đa là 50 triệu VNĐ
            if (Number(value) > 50000000) { 
                value = '50000000'; 
                showError('Giá bán không được vượt quá 50.000.000 VNĐ.'); 
            }

            raw.value = value; // Lưu giá trị thuần số vào input ẩn để gửi về server
            this.value = value ? new Intl.NumberFormat('vi-VN').format(value) : ''; // Định dạng hiển thị dấu chấm kiểu vi-VN
        });
    }

    /**
     * Khởi tạo tính năng xóa ảnh phụ trong thư viện ảnh bằng AJAX
     */
    function initGalleryDelete() {
        document.addEventListener('click', async function (event) {
            const button = event.target.closest('.js-delete-gallery-image');
            if (!button) return;

            // Hiển thị hộp thoại xác nhận xóa ảnh
            const confirmed = window.Swal
                ? (await Swal.fire({ 
                    icon: 'warning', 
                    title: 'Xóa ảnh này?', 
                    showCancelButton: true, 
                    confirmButtonText: 'Xóa', 
                    cancelButtonText: 'Hủy' 
                  })).isConfirmed
                : window.confirm('Xóa ảnh này?');

            if (!confirmed) return;

            // Thực thi gửi request DELETE bất đồng bộ (AJAX) để xóa ảnh phụ
            const response = await fetch('/admin/products/gallery/' + button.dataset.galleryImageId, {
                method: 'DELETE', 
                headers: { 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 
                    Accept: 'application/json' 
                },
            });

            // Nếu thành công thì xóa thẻ chứa ảnh khỏi giao diện người dùng
            if (response.ok) {
                document.getElementById('gallery-item-' + button.dataset.galleryImageId)?.remove();
            } else {
                showError('Không thể xóa ảnh.');
            }
        });
    }

    /**
     * Khởi tạo ô lựa chọn danh mục sản phẩm nâng cao (Choices.js) có thanh tìm kiếm
     */
    function initCategorySelect() {
        const categorySelects = document.querySelectorAll('.product-category-select');
        if (categorySelects.length > 0 && typeof Choices !== 'undefined') {
            categorySelects.forEach(select => {
                if (!select.dataset.choicesInitialized) {
                    new Choices(select, {
                        searchEnabled: true,
                        shouldSort: false,
                        itemSelectText: '',
                        searchPlaceholderValue: 'Tìm danh mục...',
                        noResultsText: 'Không tìm thấy danh mục',
                        noChoicesText: 'Không còn lựa chọn',
                    });
                    select.dataset.choicesInitialized = 'true';
                }
            });
        }
    }

    // Đăng xuất đối tượng toàn cục ProductForm chứa hàm khởi chạy
    window.ProductForm = { 
        init: function () { 
            initSizes(); 
            initImages(); 
            initPrice(); 
            initGalleryDelete(); 
            initCategorySelect(); 
        } 
    };
})();
