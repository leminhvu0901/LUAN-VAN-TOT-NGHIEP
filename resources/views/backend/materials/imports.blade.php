@extends('backend.layouts.app')

@section('title', 'Lịch sử Nhập Kho')

@section('content')
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.materials.index') }}"
                    class="w-10 h-10 bg-white rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:text-gray-900 transition-colors">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Chi tiết vật tư: {{ $material->name }}</h2>
                    <p class="text-gray-500 text-sm mt-1">Tồn kho hiện tại: <span
                            class="font-bold text-gray-900">{{ number_format($material->current_stock, 0, ',', '.') }}
                            {{ $material->unit }}</span> | Giá vốn TB:
                        {{ number_format($material->unit_price, 0, ',', '.') }}đ</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    onclick="editMaterial({{ $material->id }}, '{{ $material->name }}', '{{ $material->unit }}', {{ $material->unit_price }})"
                    class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-50 hover:text-emerald-600 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">edit</span> Sửa thông tin
                </button>
                <form action="{{ route('admin.materials.destroy', $material->id) }}" method="POST"
                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ vật tư này?');"
                    class="inline-block m-0 p-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-50 hover:text-red-600 transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">delete</span> Xóa vật tư
                    </button>
                </form>
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

                <form action="{{ route('admin.materials.imports.store', $material->id) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng nhập
                                ({{ $material->unit }})</label>
                            <input type="number" step="1" name="quantity" required min="1"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tổng tiền thanh toán (VNĐ)</label>
                            <input type="text" id="formatted_total_price" required
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                placeholder="0">
                            <input type="hidden" name="total_price" id="total_price" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hạn sử dụng (Tùy chọn)</label>
                            <input type="date" name="expiration_date" min="{{ date('Y-m-d') }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none text-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (Tùy chọn)</label>
                            <input type="text" name="note"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                placeholder="VD: Nhập hàng từ NCC A...">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                            class="px-6 py-2.5 bg-primary text-white rounded-lg font-bold hover:bg-primary-dark transition-colors flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">add_box</span> Lưu phiếu nhập
                        </button>
                    </div>
                </form>
            </div>

            <script>
                const unitPrice = {{ $material->unit_price }};
                const qtyInput = document.querySelector('input[name="quantity"]');
                const formattedTotalInput = document.getElementById('formatted_total_price');
                const rawTotalInput = document.getElementById('total_price');

                // Auto-calculate total price when quantity changes
                qtyInput.addEventListener('input', function () {
                    const qty = parseFloat(this.value) || 0;
                    const total = Math.round(qty * unitPrice);

                    rawTotalInput.value = total;
                    formattedTotalInput.value = total > 0 ? new Intl.NumberFormat('vi-VN').format(total) : '';
                });

                // Format VND when user manually edits total price
                formattedTotalInput.addEventListener('input', function (e) {
                    let value = this.value.replace(/\D/g, ''); // Remove non-digit characters
                    rawTotalInput.value = value; // Set raw value

                    if (value !== '') {
                        this.value = new Intl.NumberFormat('vi-VN').format(value); // Format display
                    } else {
                        this.value = '';
                    }
                });
            </script>

            <!-- Phần 2: Bảng Lịch sử Nhập kho & Xuất/Hủy kho -->
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
                            <th class="px-4 py-4 font-semibold text-right whitespace-nowrap">Số lượng</th>
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
                                            onclick="editImport({{ $import->id }}, {{ $import->quantity }}, {{ $import->total_price }}, '{{ $import->expiration_date ? $import->expiration_date->format('Y-m-d') : '' }}', '{{ addslashes($import->note ?? '') }}')"
                                            class="p-1 text-gray-400 hover:text-blue-600 transition-colors mr-1">
                                            <span class="material-symbols-outlined">edit</span>
                                        </button>
                                    @endif
                                    @if($import->remaining_quantity > 0)
                                        <button type="button" title="Hủy một phần hoặc toàn bộ lô này"
                                            onclick="disposeBatch({{ $import->id }}, '{{ $material->unit }}', {{ $import->remaining_quantity }})"
                                            class="p-1 text-gray-400 hover:text-red-600 transition-colors">
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
                            class="material-symbols-outlined align-middle mr-1 text-red-600">logout</span>Lịch sử Xuất / Hủy
                        kho</h3>
                    <span class="text-sm font-medium text-gray-500">{{ $xuatHuy->count() }} phiếu xuất/hủy</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="bg-white text-xs uppercase text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-4 font-semibold">Mã GD</th>
                                <th class="px-4 py-4 font-semibold">Thời gian</th>
                                <th class="px-4 py-4 font-semibold text-right">Số lượng xuất/hủy</th>
                                <th class="px-4 py-4 font-semibold text-right">Giá trị xuất/hủy</th>
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
                                        Chưa có dữ liệu xuất/hủy kho.
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
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Hủy Hàng Từ Lô <span id="dispose-batch-id"
                        class="text-blue-600"></span></h3>
                <button onclick="document.getElementById('modal-dispose-batch').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-dispose-batch" method="POST" class="p-6">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng hủy (<span
                                id="dispose-batch-unit"></span>)</label>
                        <input type="number" step="1" min="1" id="dispose-batch-quantity" name="quantity" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all">
                        <p class="text-xs text-gray-500 mt-1">Tồn kho của lô này: <span id="dispose-batch-max"
                                class="font-bold text-red-600"></span></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Lý do</label>
                        <input type="text" name="note" required placeholder="VD: Hàng hết hạn..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all"
                            value="Hàng hết hạn">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-dispose-batch').classList.add('hidden')"
                        class="px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="submit"
                        class="px-5 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 organic-shadow transition-all">Xác
                        nhận Hủy Lô</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Phần 4: Hộp thoại (Modal) Sửa thông tin Vật tư cơ bản (Bị ẩn mặc định) -->
    <div id="modal-edit" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Sửa Thông Tin Vật Tư</h3>
                <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-edit" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên vật tư</label>
                        <input type="text" id="edit-name" name="name" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Đơn vị (Kg, Bao, Lốc, Cuộn,
                            Cái...)</label>
                        <input type="text" id="edit-unit" name="unit" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá vốn dự kiến (VNĐ / Đơn vị)</label>
                        <input type="number" id="edit-price" name="unit_price" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                        class="px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="submit"
                        class="px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Cập
                        nhật</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Sửa Phiếu Nhập -->
    <div id="modal-edit-import" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Sửa Phiếu Nhập Lô <span id="edit-import-id-text" class="text-blue-600"></span></h3>
                <button onclick="document.getElementById('modal-edit-import').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-edit-import" method="POST" class="p-6">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng nhập ({{ $material->unit }})</label>
                        <input type="number" id="edit-import-quantity" name="quantity" required min="1" step="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tổng tiền thanh toán (VNĐ)</label>
                        <input type="number" id="edit-import-total-price" name="total_price" required min="0" step="1"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Hạn sử dụng (Tùy chọn)</label>
                        <input type="date" id="edit-import-expiration-date" name="expiration_date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Ghi chú (Tùy chọn)</label>
                        <input type="text" id="edit-import-note" name="note"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-edit-import').classList.add('hidden')"
                        class="px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors">Hủy</button>
                    <button type="submit"
                        class="px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script src="{{ asset('js/backend/materials/common.js') }}"></script>
<script src="{{ asset('js/backend/materials/index.js') }}"></script>
@endpush