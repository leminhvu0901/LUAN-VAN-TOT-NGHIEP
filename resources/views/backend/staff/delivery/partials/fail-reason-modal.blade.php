<style>
    @keyframes fail-reason-shake {
        10%, 90% { transform: translateX(-1px); }
        20%, 80% { transform: translateX(2px); }
        30%, 50%, 70% { transform: translateX(-4px); }
        40%, 60% { transform: translateX(4px); }
    }
    .animate-shake { animation: fail-reason-shake 0.4s; }
    @media (prefers-reduced-motion: reduce) {
        .animate-shake { animation: none; }
    }
</style>
<div id="fail-reason-backdrop" class="hidden fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div id="fail-reason-panel" class="bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-3xl p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-gray-900 text-lg">Lý do giao thất bại</h3>
            <button type="button" id="fail-reason-close" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-2">
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="radio" name="fail-reason-type" value="damaged" class="mt-0.5"
                    data-default-text="Hàng bị hư hỏng/đổ vỡ trong quá trình vận chuyển.">
                <span>Hàng hư hỏng/đổ vỡ trong quá trình giao <span class="text-xs text-emerald-600">(sẽ tự động hoàn tiền nếu đơn đã thanh toán trực tuyến VNPay)</span></span>
            </label>
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="radio" name="fail-reason-type" value="customer_unreachable" class="mt-0.5" checked
                    data-default-text="Khách không nhận hàng / không liên lạc được.">
                <span>Khách không nhận hàng / không liên lạc được</span>
            </label>
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="radio" name="fail-reason-type" value="other" class="mt-0.5" data-default-text="">
                <span>Lý do khác</span>
            </label>
        </div>

        <p class="text-sm text-gray-500">Chọn lý do cụ thể ở "Lý do khác" thì cần tự nhập mô tả (tối thiểu 5 ký tự) — thông tin này sẽ được lễ tân/quản trị viên xem lại.</p>

        <textarea id="fail-reason-textarea" rows="3" maxlength="500" placeholder="Ví dụ: khách không nghe máy, không có ai nhận hàng..."
            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm resize-none focus:outline-none focus:ring-2 focus:ring-red-200"></textarea>
        <p id="fail-reason-hint" class="text-xs text-red-500 hidden">Vui lòng nhập ít nhất 5 ký tự.</p>

        <div class="grid grid-cols-2 gap-2 pt-1">
            <button type="button" id="fail-reason-cancel" class="min-h-[44px] rounded-xl border border-gray-200 text-gray-600 font-semibold text-sm">Hủy</button>
            <button type="button" id="fail-reason-confirm" class="min-h-[44px] rounded-xl bg-red-600 text-white font-bold text-sm">Xác nhận thất bại</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    "use strict";

    let currentForm = null;
    let userEditedReason = false; // true khi người dùng tự gõ tay -> không tự động ghi đè nội dung của họ nữa

    const backdrop = document.getElementById('fail-reason-backdrop');
    const textarea = document.getElementById('fail-reason-textarea');
    const hint = document.getElementById('fail-reason-hint');
    const reasonRadios = document.querySelectorAll('input[name="fail-reason-type"]');

    // Tự điền textarea theo lý do đã chọn
    function applyDefaultText(radio) {
        if (userEditedReason || !radio) return;
        textarea.value = radio.getAttribute('data-default-text') || '';
        hint.classList.add('hidden');
    }

    // Mở hộp thoại theo id truyền vào
    function openModal(form) {
        currentForm = form;
        textarea.value = '';
        userEditedReason = false;
        hint.classList.add('hidden');
        const defaultRadio = document.querySelector('input[name="fail-reason-type"][value="customer_unreachable"]');
        if (defaultRadio) defaultRadio.checked = true;
        applyDefaultText(defaultRadio);
        backdrop.classList.remove('hidden');
        setTimeout(() => textarea.focus(), 50);
    }

    // Đóng hộp thoại và dọn trạng thái đang giữ
    function closeModal() {
        backdrop.classList.add('hidden');
        currentForm = null;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-open-fail-modal]');
            if (!btn) return;

            const orderId = btn.getAttribute('data-open-fail-modal');
            const form = document.getElementById('fail-form-' + orderId);
            if (form) openModal(form);
        });

        // Đổi radio -> cập nhật lại textarea theo lý do mới chọn
        reasonRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                applyDefaultText(radio);
            });
        });

        // Người dùng tự gõ tay -> ngừng tự động điền đè khi đổi radio nữa
        textarea.addEventListener('input', function () {
            userEditedReason = true;
            hint.classList.add('hidden');
        });

        const cancelBtn = document.getElementById('fail-reason-cancel');
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        const closeBtn = document.getElementById('fail-reason-close');
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        if (backdrop) {
            backdrop.addEventListener('click', function (event) {
                if (event.target === backdrop) closeModal();
            });
        }

        const confirmBtn = document.getElementById('fail-reason-confirm');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                const reason = textarea.value.trim();

                if (reason.length < 5) {
                    hint.classList.remove('hidden');
                    textarea.classList.add('border-red-400', 'animate-shake');
                    setTimeout(() => textarea.classList.remove('animate-shake'), 400); // Rung nhẹ để không bị tưởng nút bị đơ
                    textarea.focus();
                    return;
                }
                textarea.classList.remove('border-red-400');

                if (!currentForm) return;

                // Giữ tham chiếu form ra biến riêng: closeModal() sẽ gán currentForm = null
                const form = currentForm;
                const selectedType = document.querySelector('input[name="fail-reason-type"]:checked');
                form.querySelector('input[name="reason"]').value = reason;
                form.querySelector('input[name="failure_type"]').value = selectedType ? selectedType.value : 'other';
                closeModal();
                form.submit();
            });
        }
    });
})();
</script>
@endpush

