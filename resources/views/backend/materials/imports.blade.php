@extends('backend.layouts.app')

@section('title', 'Lịch sử Nhập Kho')

@section('content')
    <div id="materials-imports-page" class="p-4 sm:p-6">
        <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                @php
                    $backUrl = url()->previous();
                    if ($backUrl == url()->current() || !str_contains($backUrl, 'admin/materials')) {
                        $backUrl = route('admin.materials.index');
                    }
                @endphp
                <a href="{{ $backUrl }}"
                    class="w-10 h-10 bg-white rounded-lg border border-gray-200 flex items-center justify-center flex-shrink-0 text-gray-500 hover:text-gray-900 transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 leading-tight">Chi tiết vật tư: {{ $material->name }}</h2>
                    <p class="text-gray-500 text-sm mt-1">Tồn kho hiện tại: <span
                            class="font-bold text-gray-900">{{ number_format($material->current_stock, 0, ',', '.') }}
                            {{ $material->unit }}</span> | Giá vốn TB:
                        {{ number_format($material->unit_price, 0, ',', '.') }}đ</p>
                </div>
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <button type="button" class="js-edit-material flex-1 md:flex-none justify-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-50 hover:text-emerald-600 transition-colors flex items-center gap-2 whitespace-nowrap"
                    data-action="{{ route('admin.materials.update', $material) }}"
                    data-name="{{ $material->name }}" data-unit="{{ $material->unit }}"
                    data-price="{{ $material->unit_price }}" data-has-imports="{{ $imports->isNotEmpty() ? 'true' : 'false' }}"
                    >
                    <span class="material-symbols-outlined text-[20px]">edit</span> Sửa thông tin
                </button>
                @if($activeLotsCount > 0)
                    <button type="button" disabled
                        title="Không thể xóa vì vật tư vẫn còn {{ $activeLotsCount }} lô hàng trong kho"
                        class="flex-1 md:flex-none justify-center px-4 py-2 bg-gray-100 border border-gray-200 text-gray-400 rounded-lg font-medium cursor-not-allowed flex items-center gap-2 whitespace-nowrap">
                        <span class="material-symbols-outlined text-[20px]">delete</span> Còn {{ $activeLotsCount }} lô hàng
                    </button>
                @else
                    <form action="{{ route('admin.materials.destroy', $material->id) }}" method="POST"
                        class="js-material-delete-form inline-block m-0 p-0 flex-1 md:flex-none">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full justify-center px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-50 hover:text-red-600 transition-colors flex items-center gap-2 whitespace-nowrap">
                            <span class="material-symbols-outlined text-[20px]">delete</span> Xóa vật tư
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="space-y-6">

            <!-- Phần 1: Biểu mẫu (Form) Tạo Phiếu Nhập Kho Mới -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Tạo phiếu nhập</h3>

                @if(session('success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded border border-emerald-200 text-sm font-medium">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-medium">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.materials.imports.store', $material->id) }}" method="POST" id="form-create-import">
                    @csrf
                    <input type="hidden" name="_form_context" value="import-create">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng nhập
                                ({{ $material->unit }})</label>
                            <input type="number" step="1" id="create-import-quantity" name="quantity" required min="1" max="99999999"
                                value="{{ old('quantity') }}" data-unit-price="{{ $material->unit_price }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tổng tiền thanh toán (VNĐ)</label>
                            <input type="text" id="formatted_total_price" required inputmode="numeric"
                                data-max-value="9999999999" data-max-message="Tổng tiền không được vượt quá 9.999.999.999 đồng."
                                data-number-message="Tổng tiền chỉ được nhập số."
                                aria-describedby="formatted-total-price-error"
                                value="{{ old('total_price') ? number_format((float) old('total_price'), 0, ',', '.') : '' }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                placeholder="0">
                            <p id="formatted-total-price-error" data-error-for="formatted_total_price"
                                class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                            <input type="hidden" name="total_price" id="total_price" value="{{ old('total_price') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hạn sử dụng (Tùy chọn)</label>
                            <input type="date" name="expiration_date" min="{{ now()->addDay()->format('Y-m-d') }}"
                                value="{{ old('expiration_date') }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none text-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (Tùy chọn)</label>
                            <input type="text" id="create-import-note" name="note" value="{{ old('note') }}"
                                data-max-length="255" data-field-label="Ghi chú"
                                aria-describedby="create-import-note-error"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                placeholder="VD: Nhập hàng từ NCC A...">
                            <p id="create-import-note-error" data-error-for="create-import-note"
                                class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">add_box</span> Lưu phiếu nhập
                        </button>
                    </div>
                </form>
            </div>



            <!-- Phần 2: Bảng Lịch sử Nhập kho & Thu hồi kho -->
            @php
                $nhapKho = $imports->where('quantity', '>', 0);
                $xuatHuy = $imports->where('quantity', '<', 0);
            @endphp

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
                <div class="p-5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900"><span
                            class="material-symbols-outlined align-middle mr-1 text-emerald-600">login</span>Lịch sử Nhập
                        kho</h3>
                    <span class="text-sm font-medium text-gray-500">{{ $nhapKho->count() }} phiếu nhập</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white text-xs uppercase text-gray-500 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap">Mã lô</th>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap">Thời gian</th>
                            <th class="px-4 py-4 font-semibold text-right whitespace-nowrap">SL ban đầu</th>
                            <th class="px-4 py-4 font-semibold text-right whitespace-nowrap">Tổng tiền</th>
                            <th class="px-4 py-4 font-semibold text-right">Đơn giá/{{ $material->unit }}</th>
                            <th class="px-4 py-4 font-semibold text-right whitespace-nowrap">Tồn lô</th>
                            <th class="px-4 py-4 font-semibold whitespace-nowrap">Hạn sử dụng</th>
                            <th class="px-4 py-4 font-semibold text-right whitespace-nowrap">Còn lại</th>
                            <th class="px-4 py-4 font-semibold">Ghi chú</th>
                            <th class="px-4 py-4 font-semibold text-right whitespace-nowrap">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($nhapKho as $import)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-4 py-4 font-bold text-blue-600 whitespace-nowrap">
                                    LOT-{{ $import->id }}</td>
                                <td class="px-4 py-4 text-gray-500 whitespace-nowrap">
                                    {{ $import->created_at->format('d/m/Y H:i') }}</td>
                                <td
                                    class="px-4 py-4 font-bold {{ $import->quantity > 0 ? 'text-emerald-600' : 'text-red-600' }} text-right">
                                    {{ $import->quantity > 0 ? '+' : '' }}{{ number_format($import->quantity, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 font-bold text-gray-900 text-right">
                                    {{ number_format($import->total_price, 0, ',', '.') }}đ</td>
                                <td class="px-4 py-4 text-gray-600 text-right">
                                    {{ $import->quantity != 0 ? number_format(abs($import->total_price / $import->quantity), 0, ',', '.') : 0 }}đ
                                </td>
                                <td
                                    class="px-4 py-4 text-gray-600 text-right font-semibold {{ $import->remaining_quantity > 0 ? 'text-blue-600' : '' }}">
                                    {{ number_format($import->remaining_quantity, 2, ',', '.') }}</td>
                                <td class="px-4 py-4 text-gray-600">
                                    @if($import->expiration_date)
                                        @php
                                            $daysDiffImport = now()->startOfDay()->diffInDays($import->expiration_date->startOfDay(), false);
                                        @endphp
                                        <span
                                            class="whitespace-nowrap {{ $import->remaining_quantity == 0 ? 'text-gray-400' : ($daysDiffImport < 0 ? 'text-gray-500 line-through' : ($daysDiffImport <= 30 ? 'text-red-500 font-bold' : '')) }}">
                                            {{ $import->expiration_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        @php $daysDiffImport = null; @endphp
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap">
                                    @if($import->expiration_date)
                                        @if($import->remaining_quantity == 0)
                                            <span class="text-gray-400">-</span>
                                        @elseif($daysDiffImport < 0)
                                            <span class="text-xs text-red-500 font-bold bg-red-50 px-2 py-1 rounded">Đã hết hạn</span>
                                        @else
                                            <span
                                                class="font-bold {{ $daysDiffImport <= 15 ? 'text-red-600' : 'text-emerald-600' }}">{{ $daysDiffImport }}
                                                ngày</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-gray-500 truncate max-w-[200px]">{{ $import->note ?? '-' }}</td>
                                <td class="px-4 py-4 text-right">
                                    @if($import->quantity > 0)
                                        <button type="button" title="Sửa thông tin phiếu nhập"
                                            class="js-edit-import p-1 text-gray-400 hover:text-blue-600 transition-colors mr-1"
                                            data-id="{{ $import->id }}" data-action="{{ route('admin.materials.imports.update', $import) }}"
                                            data-quantity="{{ $import->quantity }}" data-total-price="{{ $import->total_price }}"
                                            data-expiration-date="{{ $import->expiration_date ? $import->expiration_date->format('Y-m-d') : '' }}"
                                            data-note="{{ $import->note ?? '' }}"
                                            data-consumed="{{ max($import->quantity - $import->remaining_quantity, 0) }}"
                                            data-min-expiration-date="{{ $import->created_at->copy()->addDay()->format('Y-m-d') }}">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    @endif
                                    @if($import->remaining_quantity > 0)
                                        <button type="button" title="Hủy một phần hoặc toàn bộ lô này"
                                            class="js-dispose-batch p-1 text-gray-400 hover:text-red-600 transition-colors"
                                            data-id="{{ $import->id }}" data-action="{{ route('admin.materials.imports.dispose_batch', $import) }}"
                                            data-unit="{{ $material->unit }}" data-max="{{ $import->remaining_quantity }}">
                                            <span class="material-symbols-outlined">delete_sweep</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-10 text-center text-gray-400">
                                    Chưa có dữ liệu nhập kho.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900"><span
                            class="material-symbols-outlined align-middle mr-1 text-red-600">logout</span>Lịch sử Thu hồi
                        kho</h3>
                    <span class="text-sm font-medium text-gray-500">{{ $xuatHuy->count() }} phiếu thu hồi</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="bg-white text-xs uppercase text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-4 font-semibold">Mã GD</th>
                                <th class="px-4 py-4 font-semibold">Thời gian</th>
                                <th class="px-4 py-4 font-semibold text-right">Số lượng thu hồi</th>
                                <th class="px-4 py-4 font-semibold text-right">Giá trị thu hồi</th>
                                <th class="px-4 py-4 font-semibold">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($xuatHuy as $export)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-4 font-bold text-red-600">
                                        EXP-{{ str_pad($export->id, 4, '0', STR_PAD_LEFT) }}</td>
                                    <td class="px-4 py-4 text-gray-500 whitespace-nowrap">
                                        {{ $export->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 font-bold text-red-600 text-right">
                                        {{ number_format($export->quantity, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 font-bold text-gray-900 text-right">
                                        {{ number_format($export->total_price, 0, ',', '.') }}đ</td>
                                    <td class="px-4 py-4 text-gray-500">{{ $export->note ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-400">
                                        Chưa có dữ liệu thu hồi kho.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Phần 3: Hộp thoại (Modal) Xóa/Hủy một phần hoặc toàn bộ Lô hàng (Bị ẩn mặc định) -->
    <div id="modal-dispose-batch"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center {{ $errors->any() && old('_form_context') === 'dispose-batch' ? '' : 'hidden' }} z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0 overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Hủy Hàng Từ Lô <span id="dispose-batch-id"
                        class="text-blue-600">{{ old('_form_context') === 'dispose-batch' ? 'LOT-' . str_pad((string) old('_lot_id'), 4, '0', STR_PAD_LEFT) : '' }}</span></h3>
                <button type="button" data-close-modal="modal-dispose-batch"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-dispose-batch" method="POST" action="{{ old('_form_context') === 'dispose-batch' ? old('_form_action') : '' }}" class="p-6">
                @csrf
                <input type="hidden" name="_form_context" value="dispose-batch">
                <input type="hidden" name="_form_action" id="dispose-form-action" value="{{ old('_form_action') }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng hủy (<span
                                id="dispose-batch-unit">{{ old('_form_context') === 'dispose-batch' ? old('_unit') : '' }}</span>)</label>
                        <input type="number" step="1" min="1" id="dispose-batch-quantity" name="quantity" required
                            value="{{ old('_form_context') === 'dispose-batch' ? old('quantity') : '' }}"
                            max="{{ old('_form_context') === 'dispose-batch' ? old('_max_quantity') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all">
                        <p class="text-xs text-gray-500 mt-1">Tồn kho của lô này: <span id="dispose-batch-max"
                                class="font-bold text-red-600">{{ old('_form_context') === 'dispose-batch' ? old('_max_quantity') : '' }}</span></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Lý do</label>
                        <input type="hidden" name="_max_quantity" id="dispose-max-quantity" value="{{ old('_max_quantity') }}">
                        <input type="text" id="dispose-note" name="note" required placeholder="VD: Hàng hết hạn..."
                            data-max-length="255" data-field-label="Lý do" aria-describedby="dispose-note-error"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all"
                            value="{{ old('_form_context') === 'dispose-batch' ? old('note') : 'Hàng hết hạn' }}">
                        <p id="dispose-note-error" data-error-for="dispose-note"
                            class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-close-modal="modal-dispose-batch"
                        class="px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="submit"
                        class="px-5 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 organic-shadow transition-all">Xác
                        nhận Hủy Lô</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Phần 4: Hộp thoại (Modal) Sửa thông tin Vật tư cơ bản (Bị ẩn mặc định) -->
    <div id="modal-edit" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center {{ $errors->any() && old('_form_context') === 'material-edit' ? '' : 'hidden' }} z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0 overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Sửa Thông Tin Vật Tư</h3>
                <button type="button" data-close-modal="modal-edit"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-edit" method="POST" action="{{ old('_form_context') === 'material-edit' ? old('_form_action') : '' }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form_context" value="material-edit">
                <input type="hidden" name="_form_action" id="material-edit-form-action" value="{{ old('_form_action') }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên vật tư</label>
                        <input type="text" id="edit-name" name="name" required minlength="2"
                            data-max-length="50" data-field-label="Tên vật tư" aria-describedby="edit-name-error"
                            value="{{ old('_form_context') === 'material-edit' ? old('name') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                        <p id="edit-name-error" data-error-for="edit-name"
                            class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Đơn vị (Kg, Bao, Lốc, Cuộn,
                            Cái...)</label>
                        <input type="text" id="edit-unit" name="unit" required data-material-unit data-max-length="20"
                            data-field-label="Đơn vị" data-allowed-existing-value="{{ $material->unit }}" inputmode="text"
                            aria-describedby="edit-unit-error"
                            value="{{ old('_form_context') === 'material-edit' ? old('unit') : '' }}"
                            {{ $imports->isNotEmpty() ? 'readonly' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all {{ $imports->isNotEmpty() ? 'bg-gray-100' : '' }}">
                        <p id="edit-unit-error" data-error-for="edit-unit"
                            class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá vốn dự kiến (VNĐ / Đơn vị)</label>
                        <input type="text" id="edit-formatted-price" required inputmode="numeric"
                            data-max-value="999999999" data-max-message="Giá vốn dự kiến phải nhỏ hơn 1 tỷ đồng."
                            data-number-message="Giá vốn dự kiến chỉ được nhập số."
                            aria-describedby="edit-formatted-price-error"
                            value="{{ old('_form_context') === 'material-edit' && old('unit_price') !== null ? number_format((float) old('unit_price'), 0, ',', '.') : '' }}"
                            {{ $imports->isNotEmpty() ? 'readonly' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all {{ $imports->isNotEmpty() ? 'bg-gray-100' : '' }}">
                        <p id="edit-formatted-price-error" data-error-for="edit-formatted-price"
                            class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                        <input type="hidden" id="edit-price" name="unit_price" value="{{ old('_form_context') === 'material-edit' ? old('unit_price') : '' }}">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-close-modal="modal-edit"
                        class="px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="submit"
                        class="px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Cập
                        nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Sửa Phiếu Nhập -->
    <div id="modal-edit-import" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center {{ $errors->any() && old('_form_context') === 'import-edit' ? '' : 'hidden' }} z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0 overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Sửa Phiếu Nhập Lô <span id="edit-import-id-text" class="text-blue-600">{{ old('_form_context') === 'import-edit' ? 'LOT-' . old('_import_id') : '' }}</span></h3>
                <button type="button" data-close-modal="modal-edit-import"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-edit-import" method="POST" action="{{ old('_form_context') === 'import-edit' ? old('_form_action') : '' }}" class="p-6">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form_context" value="import-edit">
                <input type="hidden" name="_form_action" id="import-edit-form-action" value="{{ old('_form_action') }}">
                <input type="hidden" name="_min_quantity" id="import-edit-min-quantity" value="{{ old('_min_quantity') }}">
                <input type="hidden" name="_min_expiration_date" id="import-edit-min-expiration" value="{{ old('_min_expiration_date') }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng nhập ({{ $material->unit }})</label>
                        <input type="number" id="edit-import-quantity" name="quantity" required
                            min="{{ old('_form_context') === 'import-edit' ? max(1, (int) old('_min_quantity')) : 1 }}" max="99999999" step="1"
                            value="{{ old('_form_context') === 'import-edit' ? old('quantity') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tổng tiền thanh toán (VNĐ)</label>
                        <input type="number" id="edit-import-total-price" name="total_price" required min="1" max="9999999999.99" step="0.01"
                            value="{{ old('_form_context') === 'import-edit' ? old('total_price') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Hạn sử dụng (Tùy chọn)</label>
                        <input type="date" id="edit-import-expiration-date" name="expiration_date"
                            min="{{ old('_form_context') === 'import-edit' ? old('_min_expiration_date') : '' }}"
                            value="{{ old('_form_context') === 'import-edit' ? old('expiration_date') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Ghi chú (Tùy chọn)</label>
                        <input type="text" id="edit-import-note" name="note"
                            data-max-length="255" data-field-label="Ghi chú" aria-describedby="edit-import-note-error"
                            value="{{ old('_form_context') === 'import-edit' ? old('note') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                        <p id="edit-import-note-error" data-error-for="edit-import-note"
                            class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" data-close-modal="modal-edit-import"
                        class="px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="submit"
                        class="px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/backend/materials/common.js') }}"></script>
<script src="{{ asset('js/backend/materials/imports.js') }}"></script>
@endpush
