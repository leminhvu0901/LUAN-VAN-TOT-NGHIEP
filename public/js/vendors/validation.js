// =========================================================================
// XÁC THỰC LỖI BIỂU MẪU PHÍA CLIENT (FORM VALIDATION BY BOOTSTRAP CLASS)
// =========================================================================
(() => {
    'use strict'
  
    // Tìm tất cả các biểu mẫu (forms) cần áp dụng các lớp CSS kiểm tra lỗi
    const forms = document.querySelectorAll('.needs-validation')
  
    // Duyệt qua từng biểu mẫu và ngăn chặn hành động submit nếu chưa hợp lệ
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        // Nếu biểu mẫu chứa các trường chưa thỏa mãn điều kiện validate (vd: email sai định dạng, ô bắt buộc để trống...)
        if (!form.checkValidity()) {
          event.preventDefault() // Ngăn chặn tải lại trang hoặc gửi thông tin đi
          event.stopPropagation() // Ngăn chặn sự kiện nổi bọt lên các thành phần cha
        }
  
        // Thêm class 'was-validated' để trình duyệt tự kích hoạt các CSS báo đỏ lỗi hoặc báo xanh thành công
        form.classList.add('was-validated')
      }, false)
    })
  })()