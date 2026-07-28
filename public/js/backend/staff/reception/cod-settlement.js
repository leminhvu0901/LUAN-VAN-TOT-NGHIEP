/**
 * Trang Đối soát tiền COD — "Nộp tất cả" và "Xác nhận đã nộp" submit qua fetch thay vì form POST cổ
 * điển (trước đây mỗi lần xác nhận đều tải lại cả trang). Vẫn giữ nguyên bước hỏi xác nhận trước khi
 * gửi (ưu tiên AdminAlert.confirm() — toast đẹp đã nạp sẵn ở layout dùng chung — native confirm() chỉ
 * dùng khi AdminAlert vì lý do gì đó chưa nạp được).
 */
(function () {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function showMessage(message, type) {
        if (window.AdminAlert && type === 'error' && window.AdminAlert.error) {
            window.AdminAlert.error(message);
        } else if (window.AdminAlert && type !== 'error' && window.AdminAlert.success) {
            window.AdminAlert.success(message);
        } else {
            alert(message);
        }
    }

    function submitSettleForm(form) {
        const btn = form.querySelector('button[type="submit"]');
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
                    showMessage((result.data && result.data.message) || 'Không thể xử lý, vui lòng thử lại.', 'error');
                    if (btn) btn.disabled = false;
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                showMessage('Không thể kết nối máy chủ, vui lòng thử lại.', 'error');
                if (btn) btn.disabled = false;
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-confirm-message]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                const message = form.dataset.confirmMessage;

                if (window.AdminAlert && window.AdminAlert.confirm) {
                    window.AdminAlert.confirm(message, function () { submitSettleForm(form); }, 'Xác nhận');
                } else if (confirm(message)) {
                    submitSettleForm(form);
                }
            });
        });
    });
})();
