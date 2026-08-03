/**
 * review.js - Xử lý chi tiết phần giao diện Đánh giá (Review) sản phẩm phía Frontend
 * Các tính năng bao gồm:
 * - Chấm điểm sao tương tác (Star Rating): hover đổi màu, click chọn điểm lưu vào thẻ input ẩn.
 * - Hỗ trợ khôi phục trạng thái sao khi tải lại trang hoặc khi sửa đánh giá cũ.
 * - Chuyển đổi qua lại giữa chế độ xem đánh giá và biểu mẫu chỉnh sửa (Edit Mode) bằng JS thuần không tải lại trang.
 * - Xem trước hình ảnh đánh giá (FileReader API), kiểm tra số lượng (tối đa 5 ảnh) và dung lượng file (tối đa 2MB).
 * - Nút xóa nhanh tất cả ảnh vừa chọn.
 * - Gửi dữ liệu đánh giá (bao gồm văn bản, số sao, và tệp ảnh đa phần tử) lên máy chủ bằng Fetch API + FormData.
 * - Xử lý thông báo lỗi chi tiết tại chỗ mà không làm mất dữ liệu người dùng nhập.
 */
document.addEventListener('DOMContentLoaded', function() {
    // === 1. XỬ LÝ CHẤM ĐIỂM SAO TƯƠNG TÁC (STAR RATING) ===
    const stars = document.querySelectorAll('#star-rating-container span'); // Danh sách các thẻ sao
    const ratingInput = document.getElementById('rating-input'); // Ô input ẩn lưu trữ số sao (1-5)

    // Khởi tạo số sao hiện tại: nếu sửa đánh giá cũ thì lấy giá trị đã lưu, nếu tạo mới mặc định là 0
    let currentRating = (ratingInput && ratingInput.value) ? parseInt(ratingInput.value, 10) : 0;

    // Đăng ký sự kiện tương tác cho từng ngôi sao
    stars.forEach(star => {
        // A. Sự kiện rê chuột vào (Hover): Highlight các ngôi sao từ 1 đến vị trí đang rê chuột
        star.addEventListener('mouseover', function() { 
            highlightStars(this.getAttribute('data-value')); 
        });

        // B. Sự kiện rê chuột ra (Mouseout): Khôi phục lại số lượng sao đã click chọn thực tế trước đó
        star.addEventListener('mouseout', function() { 
            highlightStars(currentRating); 
        });

        // C. Sự kiện click chọn: Ghi nhận số sao chính thức, cập nhật input ẩn và giữ nguyên highlight
        star.addEventListener('click', function() {
            currentRating = this.getAttribute('data-value');
            ratingInput.value = currentRating;
            highlightStars(currentRating);
            // Đổi class để đảm bảo màu vàng giữ lại
            stars.forEach(s => s.classList.replace('text-gray-300', 'text-yellow-400'));
        });
    });

    /**
     * Hàm tô màu/highlight các ngôi sao theo giá trị số truyền vào
     * @param {number} val - Số lượng sao cần tô màu (từ 1 đến 5)
     */
    function highlightStars(val) {
        stars.forEach(star => {
            // Nếu vị trí sao nhỏ hơn hoặc bằng giá trị cần highlight
            if (star.getAttribute('data-value') <= val) {
                // Đổ đầy màu cho icon Google Material Symbol (Fill = 1)
                star.style.fontVariationSettings = "'FILL' 1";
                if(currentRating == 0) {
                    star.classList.replace('text-gray-300', 'text-yellow-400');
                }
            } else {
                // Rỗng ruột icon (Fill = 0)
                star.style.fontVariationSettings = "'FILL' 0";
                if(currentRating == 0) {
                    star.classList.replace('text-yellow-400', 'text-gray-300');
                }
            }
        });
    }

    // === 2. CHUYỂN ĐỔI CHẾ ĐỘ XEM / SỬA ĐÁNH GIÁ (EDIT MODE TOGGLE) ===
    /**
     * Chuyển đổi ẩn/hiện giữa khối thông tin bài viết cũ và Form chỉnh sửa bằng cách thêm/bớt class 'hidden'
     * @param {boolean} showEdit - true nếu muốn mở form sửa, false nếu muốn quay lại chế độ xem
     */
    function toggleReviewEditMode(showEdit) {
        const viewMode = document.getElementById('review-view-mode');
        const editMode = document.getElementById('review-edit-mode');
        if (!viewMode || !editMode) return;
        viewMode.classList.toggle('hidden', showEdit);
        editMode.classList.toggle('hidden', !showEdit);
    }
    window.toggleReviewEditMode = toggleReviewEditMode; // Đưa ra toàn cục để gọi từ mã HTML

    // === 3. XỬ LÝ CHỌN VÀ XEM TRƯỚC HÌNH ẢNH ĐÁNH GIÁ (IMAGE PREVIEW) ===
    /**
     * Đọc danh sách file ảnh người dùng chọn, kiểm tra tính hợp lệ và render giao diện preview
     * @param {HTMLInputElement} input - Phần tử input file
     */
    function previewImages(input) {
        const previewContainer = document.getElementById('image-preview-container');
        previewContainer.innerHTML = ''; // Reset sạch vùng hiển thị cũ

        if (input.files && input.files.length > 0) {
            // A. Khống chế số lượng ảnh tối đa là 5
            if (input.files.length > 5) {
                const errorMsg = 'Chỉ được phép chọn tối đa 5 hình ảnh.';
                if (window.FrontendAlert) window.FrontendAlert.error(errorMsg); else alert(errorMsg);
                input.value = ''; // Reset input file
                return;
            }

            // B. Khống chế dung lượng từng file ảnh tối đa là 2MB
            let hasLargeFile = false;
            Array.from(input.files).forEach(file => { 
                if (file.size > 2 * 1024 * 1024) hasLargeFile = true; 
            });
            if (hasLargeFile) {
                const errorSizeMsg = 'Dung lượng mỗi hình ảnh không được vượt quá 2MB.';
                if (window.FrontendAlert) window.FrontendAlert.error(errorSizeMsg); else alert(errorSizeMsg);
                input.value = '';
                return;
            }

            // C. Đọc file bất đồng bộ bằng FileReader và thêm thẻ xem trước vào DOM
            Array.from(input.files).forEach((file) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200';
                    div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file); // Khởi chạy đọc tệp tin dưới dạng URL Base64
            });

            // D. Tạo nút "Xóa tất cả" (icon sọt rác nét đứt) ở cuối danh sách preview để xóa nhanh
            const clearDiv = document.createElement('div');
            clearDiv.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-gray-200 border-dashed flex items-center justify-center cursor-pointer hover:bg-red-50 text-error transition-colors';
            clearDiv.onclick = () => {
                input.value = ''; // Xóa file trong input
                previewContainer.innerHTML = ''; // Xóa sạch khung preview
            };
            clearDiv.innerHTML = '<span class="material-symbols-outlined text-2xl">delete</span>';
            previewContainer.appendChild(clearDiv);
        }
    }
    window.previewImages = previewImages; // Đưa ra toàn cục

    // === 4. GỬI ĐÁNH GIÁ ĐỒNG BỘ QUA FETCH API (AJAX FORM SUBMIT) ===
    const reviewForm = document.querySelector('form[action*="/review"]');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function (event) {
            event.preventDefault(); // Chặn hành vi tải lại trang mặc định của Form
            
            const btn = reviewForm.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true; // Disable nút bấm chống gửi trùng lặp

            // Sử dụng FormData đóng gói toàn bộ trường dữ liệu và tệp tin ảnh gửi đi
            fetch(reviewForm.action, {
                method: 'POST',
                headers: { 
                    Accept: 'application/json', 
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
                },
                body: new FormData(reviewForm),
            })
            .then(function (response) {
                // Nhận phản hồi dạng JSON kèm mã trạng thái HTTP status
                return response.json().then(function (data) { 
                    return { status: response.status, data: data }; 
                });
            })
            .then(function (result) {
                // A. Xử lý khi xảy ra lỗi dữ liệu hoặc lỗi Server (Status >= 400)
                if (result.status >= 400) {
                    const errors = (result.data && result.data.errors) || {};
                    const firstError = Object.values(errors)[0];
                    const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Không thể gửi đánh giá, vui lòng kiểm tra lại.';
                    
                    // Hiển thị thông báo lỗi nổi bọt (Toast)
                    if (window.FrontendAlert) {
                        window.FrontendAlert.error(message);
                    } else {
                        alert(message);
                    }
                    if (btn) btn.disabled = false; // Kích hoạt lại nút bấm
                    return;
                }
                
                // B. Xử lý khi gửi thành công -> Chuyển hướng người dùng về trang do server chỉ định
                if (result.data && result.data.redirect_url) {
                    window.location.href = result.data.redirect_url;
                    return;
                }
                if (btn) btn.disabled = false;
            })
            .catch(function (err) {
                console.error('Lỗi gửi đánh giá:', err);
                const networkError = 'Không thể kết nối máy chủ, vui lòng thử lại sau.';
                if (window.FrontendAlert) window.FrontendAlert.error(networkError); else alert(networkError);
                if (btn) btn.disabled = false;
            });
        });
    }
});
