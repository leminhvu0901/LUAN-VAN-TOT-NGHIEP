@extends('backend.layouts.app')

@section('title', 'Lịch sử Nhập Kho - Nhân viên')

@section('content')
    <div id="materials-imports-page" class="material-imports-page p-4 sm:p-6">
        <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <a href="{{ route('staff.reception.materials.index') }}"
                    onclick="if(document.referrer.includes(window.location.host)) { event.preventDefault(); window.history.back(); }"
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

                <form action="{{ route('staff.reception.materials.imports.store', $material->id) }}" method="POST" id="form-create-import">
                    @csrf
                    <input type="hidden" name="_form_context" value="import-create">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng nhập
                                ({{ $material->unit }})</label>
                            <input type="number" step="1" id="create-import-quantity" name="quantity" required min="1" max="1000"
                                value="{{ old('quantity') }}" data-unit-price="{{ $material->unit_price }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none"
                                placeholder="0">
                            <p id="create-import-quantity-error" data-error-for="create-import-quantity" class="hidden mt-1 text-xs font-medium text-red-600"></p>
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
                            <input type="text" name="expiration_date" id="create-expiration-date" data-min-date="{{ now()->addDay()->format('Y-m-d') }}"
                                value="{{ old('expiration_date') }}"
                                class="flatpickr-date w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary outline-none text-gray-700"
                                placeholder="Chọn ngày...">
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

            <!-- Phần 1.5: Biểu mẫu Xuất Kho Sử Dụng (lấy hàng ra khỏi kho để dùng tại quầy, không qua đơn hàng) -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-1 border-b border-gray-100 pb-3">Xuất kho sử dụng</h3>
                <p class="text-xs text-gray-500 mt-2 mb-4">Dùng khi lấy vật tư ra khỏi kho để dùng trực tiếp tại quầy (VD: hết ly, lấy 1 lốc ly ra dùng).</p>

                <form action="{{ route('staff.reception.materials.consume', $material->id) }}" method="POST" id="form-consume-stock">
                    @csrf
                    <input type="hidden" name="_form_context" value="consume-stock">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng xuất
                                ({{ $material->unit }})</label>
                            <input type="number" step="0.01" name="quantity" required min="0.01" max="999.99"
                                value="{{ old('_form_context') === 'consume-stock' ? old('quantity') : '' }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none"
                                placeholder="0">
                        </div>
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lý do xuất kho</label>
                            <input type="text" name="reason" required maxlength="255"
                                value="{{ old('_form_context') === 'consume-stock' ? old('reason') : '' }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none"
                                placeholder="VD: Hết ly tại quầy, lấy thêm để pha chế">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                            class="w-full sm:w-auto px-6 py-2.5 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition-colors flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[20px]">outbox</span> Ghi nhận xuất kho
                        </button>
                    </div>
                </form>
            </div>

            <!-- Phần 2: Bảng Lịch sử Nhập kho & Xuất kho -->
            @php
                $nhapKho = $imports->where('quantity', '>', 0);
                $xuatHuy = $imports->where('quantity', '<', 0);
            @endphp

            <div class="bg-transparent lg:bg-white lg:rounded-xl lg:border lg:border-gray-200 lg:shadow-sm lg:overflow-hidden mb-6">
                <div class="px-4 py-3 flex items-center justify-between lg:p-5 lg:border-b lg:border-gray-100 lg:bg-gray-50/50">
                    <h3 class="font-bold text-gray-900 flex items-center"><span
                            class="material-symbols-outlined align-middle mr-1.5 text-emerald-600">login</span>Lịch sử Nhập kho</h3>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full lg:bg-transparent lg:p-0">{{ $nhapKho->count() }} phiếu nhập</span>
                </div>
                <!-- Giao diện Mobile (Card view) -->
                <div class="block lg:hidden space-y-4 px-1 py-2">
                    @forelse($nhapKho as $import)
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-4 relative hover:shadow-md transition-shadow" id="import-card-{{ $import->id }}">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <span class="text-sm font-extrabold text-gray-900">Lô: LOT-{{ $import->id }}</span>
                                </div>
                                <span class="text-xs text-gray-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                                    {{ $import->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="bg-gray-50/50 p-2.5 rounded-xl border border-gray-100/30">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">SL ban đầu</p>
                                    <p class="font-bold text-emerald-600 mt-0.5">+{{ number_format($import->quantity, 2, ',', '.') }} {{ $material->unit }}</p>
                                </div>
                                <div class="bg-gray-50/50 p-2.5 rounded-xl border border-gray-100/30">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Tồn lô hiện tại</p>
                                    <p class="font-bold text-blue-600 mt-0.5">{{ number_format($import->remaining_quantity, 2, ',', '.') }} {{ $material->unit }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Đơn giá</p>
                                    <p class="font-semibold text-gray-700 mt-0.5">{{ $import->quantity != 0 ? number_format(abs($import->total_price / $import->quantity), 0, ',', '.') : 0 }}đ/{{ $material->unit }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Tổng thanh toán</p>
                                    <p class="font-bold text-gray-900 mt-0.5">{{ number_format($import->total_price, 0, ',', '.') }}đ</p>
                                </div>
                            </div>

                            @if($import->expiration_date)
                                @php
                                    $daysDiffImport = now()->startOfDay()->diffInDays($import->expiration_date->startOfDay(), false);
                                @endphp
                                <div class="bg-amber-50/30 border border-amber-100/40 p-3 rounded-xl text-xs space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500 font-medium">Hạn sử dụng:</span>
                                        <span class="font-bold {{ $import->remaining_quantity == 0 ? 'text-gray-400' : ($daysDiffImport < 0 ? 'text-gray-400 line-through' : ($daysDiffImport <= 30 ? 'text-red-500 font-bold' : 'text-gray-700')) }}">
                                            {{ $import->expiration_date->format('d/m/Y') }}
                                        </span>
                                    </div>
                                    @if($import->remaining_quantity > 0)
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-500 font-medium">Trạng thái hạn:</span>
                                            @if($daysDiffImport < 0)
                                                <span class="text-[10px] text-red-500 font-extrabold bg-red-50 px-2 py-0.5 rounded-full">Đã hết hạn</span>
                                            @else
                                                <span class="font-bold px-2 py-0.5 rounded-full text-[11px] {{ $daysDiffImport <= 15 ? 'text-red-700 bg-red-50' : 'text-emerald-700 bg-emerald-50' }}">
                                                    Còn {{ $daysDiffImport }} ngày
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($import->note)
                                <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 italic" style="overflow-wrap: anywhere; word-break: break-word;">
                                    Ghi chú: {{ $import->note }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center text-gray-400 flex flex-col items-center gap-2">
                            <span class="material-symbols-outlined text-3xl text-gray-300">inventory_2</span>
                            <span class="text-xs font-semibold">Chưa có dữ liệu nhập kho.</span>
                        </div>
                    @endforelse
                </div>

                <!-- Giao diện Desktop (Table view) -->
                <div class="hidden lg:block overflow-x-auto">
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($nhapKho as $import)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-4 font-bold text-blue-600 whitespace-nowrap">LOT-{{ $import->id }}</td>
                                    <td class="px-4 py-4 text-gray-500 whitespace-nowrap">{{ $import->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 font-bold text-emerald-600 text-right">+{{ number_format($import->quantity, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 font-bold text-gray-900 text-right">{{ number_format($import->total_price, 0, ',', '.') }}đ</td>
                                    <td class="px-4 py-4 text-gray-600 text-right">{{ $import->quantity != 0 ? number_format(abs($import->total_price / $import->quantity), 0, ',', '.') : 0 }}đ</td>
                                    <td class="px-4 py-4 text-gray-600 text-right font-semibold text-blue-600">{{ number_format($import->remaining_quantity, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 text-gray-600">
                                        @if($import->expiration_date)
                                            @php
                                                $daysDiffImport = now()->startOfDay()->diffInDays($import->expiration_date->startOfDay(), false);
                                            @endphp
                                            <span class="whitespace-nowrap {{ $import->remaining_quantity == 0 ? 'text-gray-400' : ($daysDiffImport < 0 ? 'text-gray-500 line-through' : ($daysDiffImport <= 30 ? 'text-red-500 font-bold' : '')) }}">
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
                                                <span class="font-bold {{ $daysDiffImport <= 15 ? 'text-red-600' : 'text-emerald-600' }}">{{ $daysDiffImport }} ngày</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-gray-500 truncate max-w-[250px]">{{ $import->note ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-10 text-center text-gray-400">Chưa có dữ liệu nhập kho.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-transparent lg:bg-white lg:rounded-xl lg:border lg:border-gray-200 lg:shadow-sm lg:overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between lg:p-5 lg:border-b lg:border-gray-100 lg:bg-gray-50/50">
                    <h3 class="font-bold text-gray-900 flex items-center"><span
                            class="material-symbols-outlined align-middle mr-1.5 text-red-600">logout</span>Lịch sử Xuất kho</h3>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full lg:bg-transparent lg:p-0">{{ $xuatHuy->count() }} phiếu xuất</span>
                </div>
                <!-- Giao diện Mobile (Card view) -->
                <div class="block lg:hidden space-y-4 px-1 py-2">
                    @forelse($xuatHuy as $export)
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-3.5 relative hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-center border-b border-gray-100 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                    <span class="text-sm font-extrabold text-red-600">Mã GD: EXP-{{ str_pad($export->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <span class="text-xs text-gray-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                                    {{ $export->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div class="bg-red-50/30 p-2.5 rounded-xl border border-red-100/20">
                                    <p class="text-[10px] text-red-500 font-bold uppercase tracking-wider">Số lượng xuất</p>
                                    <p class="font-bold text-red-600 mt-0.5">{{ number_format($export->quantity, 2, ',', '.') }} {{ $material->unit }}</p>
                                </div>
                                <div class="bg-gray-50/50 p-2.5 rounded-xl border border-gray-100/30">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Giá trị xuất</p>
                                    <p class="font-bold text-gray-900 mt-0.5">{{ number_format($export->total_price, 0, ',', '.') }}đ</p>
                                </div>
                            </div>

                            @if($export->note)
                                <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 italic" style="overflow-wrap: anywhere; word-break: break-word;">
                                    Lý do: {{ $export->note }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center text-gray-400 flex flex-col items-center gap-2">
                            <span class="material-symbols-outlined text-3xl text-gray-300">inventory_2</span>
                            <span class="text-xs font-semibold">Chưa có dữ liệu xuất kho.</span>
                        </div>
                    @endforelse
                </div>

                <!-- Giao diện Desktop (Table view) -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="bg-white text-xs uppercase text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-4 font-semibold">Mã GD</th>
                                <th class="px-4 py-4 font-semibold">Thời gian</th>
                                <th class="px-4 py-4 font-semibold text-right">Số lượng xuất</th>
                                <th class="px-4 py-4 font-semibold text-right">Giá trị xuất</th>
                                <th class="px-4 py-4 font-semibold">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($xuatHuy as $export)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-4 font-bold text-red-600">EXP-{{ str_pad($export->id, 4, '0', STR_PAD_LEFT) }}</td>
                                    <td class="px-4 py-4 text-gray-500 whitespace-nowrap">{{ $export->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-4 font-bold text-red-600 text-right">{{ number_format($export->quantity, 2, ',', '.') }}</td>
                                    <td class="px-4 py-4 font-bold text-gray-900 text-right">{{ number_format($export->total_price, 0, ',', '.') }}đ</td>
                                    <td class="px-4 py-4 text-gray-500">{{ $export->note ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-gray-400">Chưa có dữ liệu xuất kho.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/backend/staff/reception/materials/common.js') }}"></script>
    @endpush
@endsection
