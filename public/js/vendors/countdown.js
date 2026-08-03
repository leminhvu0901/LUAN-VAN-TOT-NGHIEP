// =========================================================================
// BỘ ĐẾM NGƯỢC THỜI GIAN (TIMER COUNTDOWN)
// =========================================================================

// Tìm tất cả các thẻ có thuộc tính data-countdown ngoài giao diện
document.querySelectorAll('[data-countdown]').forEach(function (element) {
	// Lấy mốc thời gian đích cần đếm ngược đến (ví dụ: "2026-12-31 23:59:59")
	var finalDate = element.getAttribute('data-countdown');

	function updateCountdown() {
		var now = new Date().getTime(); // Lấy thời gian hiện tại
		var distance = new Date(finalDate) - now; // Khoảng thời gian còn lại (mili-giây)

		// Nếu thời gian đã hết hạn
		if (distance <= 0) {
			clearInterval(interval); // Dừng bộ đếm lặp lại
			element.innerHTML = 'Hết thời gian'; // Hiển thị thông báo kết thúc
			return;
		}

		// Tính toán số ngày, giờ, phút, giây còn lại
		var days = Math.floor(distance / (1000 * 60 * 60 * 24));
		var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
		var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
		var seconds = Math.floor((distance % (1000 * 60)) / 1000);

		// Render cấu trúc giao diện hiển thị các ô số đếm ngược ra màn hình
		element.innerHTML =
			'<span class="countdown-section"><span class="countdown-amount hover-up">' +
			days +
			'</span><span class="countdown-period"> ngày </span></span>' +
			'<span class="countdown-section"><span class="countdown-amount hover-up">' +
			hours +
			'</span><span class="countdown-period"> giờ </span></span>' +
			'<span class="countdown-section"><span class="countdown-amount hover-up">' +
			minutes +
			'</span><span class="countdown-period"> phút </span></span>' +
			'<span class="countdown-section"><span class="countdown-amount hover-up">' +
			seconds +
			'</span><span class="countdown-period"> giây </span></span>';
	}

	updateCountdown(); // Chạy ngay lập tức một lần để tránh độ trễ hiển thị ban đầu
	var interval = setInterval(updateCountdown, 1000); // Lặp lại cập nhật mỗi giây (1000 mili-giây)
});
