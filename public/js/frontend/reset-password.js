/**
 * Hàm ẩn/hiện mật khẩu trong trường nhập liệu (input).
 * @param {string} id - ID của thẻ input cần thay đổi trạng thái hiển thị.
 */
function toggleVisibility(id) {
    // Lấy thẻ input dựa theo ID truyền vào
    const input = document.getElementById(id);
    
    // Nếu kiểu hiển thị hiện tại là 'password' (đang ẩn dưới dạng dấu chấm)
    if (input.type === 'password') {
        // Chuyển kiểu hiển thị sang 'text' (hiển thị rõ chữ mật khẩu)
        input.type = 'text';
    } else {
        // Nếu đang hiển thị rõ chữ, chuyển ngược lại về 'password' để ẩn đi
        input.type = 'password';
    }
}
