// =========================================================================
// HIỆU ỨNG THU PHÓNG ẢNH CHI TIẾT SẢN PHẨM (IMAGE MOUSE HOVER ZOOM)
// =========================================================================

/**
 * Hàm phóng to ảnh dựa trên vị trí di chuột của khách hàng
 * @param {Event} e - Sự kiện di chuột (Mousemove hoặc Touch)
 */
function zoom(e) {
	var zoomer = e.currentTarget; // Phần tử bọc ngoài chứa ảnh nền cần phóng to
	
	// Xác định tọa độ X của chuột trong vùng chứa (hỗ trợ cả chạm màn hình cảm ứng)
	e.offsetX ? (offsetX = e.offsetX) : (offsetX = e.touches[0].pageX);
	
	// Xác định tọa độ Y của chuột trong vùng chứa
	e.offsetY ? (offsetY = e.offsetY) : (offsetX = e.touches[0].pageX);
	
	// Tính toán tỷ lệ phần trăm vị trí chuột so với chiều ngang và chiều dọc của khung
	x = (offsetX / zoomer.offsetWidth) * 100;
	y = (offsetY / zoomer.offsetHeight) * 100;
	
	// Thay đổi tâm điểm định vị của hình nền theo vị trí chuột để tạo cảm giác di chuyển ống kính zoom
	zoomer.style.backgroundPosition = x + '% ' + y + '%';
}
