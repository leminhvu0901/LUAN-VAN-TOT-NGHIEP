/**
 * Submit các form đổi trạng thái đơn giao hàng (Nhận đơn, Giao thành công, Giao thất bại) qua fetch
 * thay vì form POST cổ điển — trước đây mỗi lần bấm đều tải lại cả trang, dễ bấm trùng lặp khi mạng
 * di động chập chờn (không rõ đã bấm trúng chưa) và không có phản hồi tức thì. Dùng chung cho cả danh
 * sách đơn (orders/index.blade.php) lẫn trang chi tiết (orders/show.blade.php).
 */
(function () {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Submit 1 form qua fetch. Thành công thì điều hướng thật tới redirect_url server trả về (đúng
     * đích như redirect() cổ điển trước đây — sang danh sách đúng tab). Lỗi thì hiện alert tại chỗ,
     * không tải lại trang, không mất trạng thái đang xem.
     */
    window.submitDeliveryActionForm = function (form) {
        const btn = form.querySelector('button[type="submit"]') || document.querySelector('#fail-reason-confirm');
        if (btn) btn.disabled = true;

        fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: new FormData(form),
        })
            .then(function (response) {
                return response.json().then(function (data) { return { status: response.status, data: data }; });
            })
            .then(function (result) {
                if (result.status >= 400) {
                    const errors = (result.data && result.data.errors) || {};
                    const firstError = Object.values(errors)[0];
                    const message = (firstError && firstError[0]) || (result.data && result.data.message) || 'Không thể xử lý, vui lòng thử lại.';
                    if (window.AdminAlert && window.AdminAlert.error) {
                        window.AdminAlert.error(message);
                    } else {
                        alert(message);
                    }
                    if (btn) btn.disabled = false;
                    return;
                }
                if (result.data && result.data.redirect_url) {
                    window.location.href = result.data.redirect_url;
                    return;
                }
                if (btn) btn.disabled = false;
            })
            .catch(function () {
                if (window.AdminAlert && window.AdminAlert.error) {
                    window.AdminAlert.error('Không thể kết nối máy chủ, vui lòng thử lại.');
                } else {
                    alert('Không thể kết nối máy chủ, vui lòng thử lại.');
                }
                if (btn) btn.disabled = false;
            });
    };

    document.addEventListener('DOMContentLoaded', function () {
        // "Nhận đơn & bắt đầu giao" / "Giao thành công" — form thường, submit trực tiếp.
        // Form của "Giao thất bại" (id="fail-form-...") KHÔNG chặn ở đây — nó ẩn (class="hidden") và
        // được fail-reason-modal.js gọi thẳng submitDeliveryActionForm() sau khi xác nhận lý do.
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.id && form.id.indexOf('fail-form-') === 0) return; // form ẩn, không submit qua sự kiện submit thường
            if (!/\/(ship|complete)$/.test(form.getAttribute('action') || '')) return;

            event.preventDefault();
            window.submitDeliveryActionForm(form);
        });
    });
})();
