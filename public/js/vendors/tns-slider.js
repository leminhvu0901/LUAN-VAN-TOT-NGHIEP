// =========================================================================
// CẤU HÌNH TINY SLIDER (TNS-SLIDER) CHO CHI TIẾT SẢN PHẨM & MODAL
// =========================================================================

// 1. Trình chiếu ảnh sản phẩm bên trong Popup Modal xem nhanh (Quick View Modal)
if (document.querySelectorAll('.productModal').length > 0) {
	var modalSlider = tns({
		container: '#productModal', // Vùng chứa chính của các slide ảnh
		items: 1, // Hiển thị duy nhất 1 ảnh tại một thời điểm
		startIndex: 0, // Bắt đầu từ ảnh đầu tiên (index 0)
		navContainer: '#productModalThumbnails', // Vùng chứa các ảnh nhỏ thumbnail điều hướng bên dưới
		navAsThumbnails: true, // Kích hoạt sử dụng ảnh nhỏ làm nút chuyển đổi slide ảnh lớn
		autoplay: false, // Tắt tự động chạy slide để khách tự vuốt/chuyển
		autoplayTimeout: 1500, // Thời gian chuyển tiếp (nếu bật autoplay) là 1.5 giây
		swipeAngle: false, // Tắt giới hạn góc vuốt để trải nghiệm cảm ứng mượt hơn
		speed: 1500, // Tốc độ trượt chuyển slide là 1.5 giây
		controls: false, // Ẩn hai nút điều hướng Trái/Phải mặc định (dùng thumbnail thay thế)
		autoplayButtonOutput: false, // Ẩn nút Dừng/Chạy tự động
		loop: false, // Tắt chế độ lặp vô tận (khi đến ảnh cuối cùng sẽ dừng)
	});
}

// 2. Trình chiếu ảnh sản phẩm tại trang chi tiết sản phẩm chính thức
if (document.querySelectorAll('.product').length > 1) {
	var productSlider = tns({
		container: '#product', // Vùng chứa chính của các slide ảnh chi tiết
		items: 1, // Hiển thị 1 ảnh sản phẩm lớn
		startIndex: 0, // Bắt đầu ở ảnh đầu tiên
		navContainer: '#productThumbnails', // Cấu hình thanh ảnh nhỏ thumbnail bên dưới
		navAsThumbnails: true, // Sử dụng ảnh nhỏ để nhấn chọn slide ảnh lớn
		autoplay: false, // Không tự động chạy ảnh
		autoplayTimeout: 1500,
		swipeAngle: false,
		speed: 1500,
		controls: false,
		autoplayButtonOutput: false,
	});
}
