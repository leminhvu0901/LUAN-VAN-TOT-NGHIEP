<div id="fail-reason-backdrop" class="hidden fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-0 sm:p-4">
    <div id="fail-reason-panel" class="bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-3xl p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-gray-900 text-lg">Lý do giao thất bại</h3>
            <button type="button" id="fail-reason-close" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <div class="space-y-2">
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="radio" name="fail-reason-type" value="damaged" class="mt-0.5">
                <span>Hàng hư hỏng/đổ vỡ trong quá trình giao <span class="text-xs text-emerald-600">(sẽ tự động hoàn tiền nếu đơn đã thanh toán trực tuyến MoMo/VNPay)</span></span>
            </label>
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="radio" name="fail-reason-type" value="customer_unreachable" class="mt-0.5" checked>
                <span>Khách không nhận hàng / không liên lạc được</span>
            </label>
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="radio" name="fail-reason-type" value="other" class="mt-0.5">
                <span>Lý do khác</span>
            </label>
        </div>

        <p class="text-sm text-gray-500">Vui lòng nhập lý do chi tiết (tối thiểu 5 ký tự) — thông tin này sẽ được lễ tân/quản trị viên xem lại.</p>

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
    <script src="{{ asset('js/backend/staff/delivery/order-actions.js') }}"></script>
    <script src="{{ asset('js/backend/staff/delivery/fail-reason-modal.js') }}"></script>
@endpush
