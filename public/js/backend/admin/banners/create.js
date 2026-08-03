// =========================================================================
// XỬ LÝ CHỌN ẢNH & HIỂN THỊ XEM TRƯỚC CHO FORM BANNER (CREATE)
// =========================================================================

/**
 * Kích hoạt hộp thoại chọn file ảnh khi click vào vùng placeholder
 */
function triggerFileInput() {
    document.getElementById('image-input').click();
}

/**
 * Đọc file ảnh được chọn và hiển thị xem trước (preview) ra màn hình
 * @param {HTMLInputElement} input - Input chứa file ảnh được chọn
 */
function previewSelectedImage(input) {
    if (input.files && input.files[0]) {
        // Kiểm tra ràng buộc dung lượng ảnh tải lên tối đa 10MB
        if (input.files[0].size > 10 * 1024 * 1024) {
            if (window.AdminAlert) {
                window.AdminAlert.error('Dung lượng tệp ảnh không được vượt quá 10MB.', 'Ảnh quá lớn');
            } else {
                alert('Dung lượng tệp ảnh không được vượt quá 10MB.');
            }
            input.value = ''; // Reset input để xóa file bị lỗi
            return;
        }

        // Sử dụng FileReader để đọc dữ liệu file dưới dạng chuỗi Base64 (DataURL)
        var reader = new FileReader();
        reader.onload = function(e) {
            // Thay đổi thuộc tính src của thẻ img để hiển thị hình ảnh xem trước
            document.getElementById('image-preview').setAttribute('src', e.target.result);
            document.getElementById('image-preview-container').classList.remove('hidden');
            document.getElementById('upload-placeholder').classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * Loại bỏ ảnh xem trước hiện tại và đặt lại form chọn ảnh về ban đầu
 */
function removeImagePreview() {
    document.getElementById('image-input').value = ''; // Reset input file
    document.getElementById('image-preview').setAttribute('src', '#');
    document.getElementById('image-preview-container').classList.add('hidden');
    document.getElementById('upload-placeholder').classList.remove('hidden');
}

// Khởi tạo thư viện chọn ngày giờ Flatpickr cho các trường thời hạn hiển thị của banner
document.addEventListener('DOMContentLoaded', function() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".banner-date-picker", {
            locale: "vn", // Thiết lập ngôn ngữ tiếng Việt
            dateFormat: "Y-m-d H:i:S", // Định dạng ngày giờ: Năm-Tháng-Ngày Giờ:Phút:Giây
            enableTime: true, // Cho phép chọn thêm thời gian (giờ/phút)
            time_24hr: true, // Định dạng thời gian 24 giờ
            disableMobile: true, // Tắt hiển thị date picker mặc định của di động để đồng bộ giao diện
            monthSelectorType: "static" // Sử dụng danh sách tháng tĩnh
        });
    }
});
