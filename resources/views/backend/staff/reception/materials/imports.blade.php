@extends('backend.layouts.app')

@section('title', 'Lịch sử Nhập Kho - Nhân viên')

@section('content')
    <div id="materials-imports-page" class="material-imports-page p-4 sm:p-6">
        <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <a href="{{ route('staff.reception.materials.index') }}"
                    onclick="smartGoBack(event)"
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

                            @if($import->remaining_quantity > 0)
                                <button type="button" title="Xuất dùng từ đúng lô này"
                                    class="js-consume-batch w-full py-2 bg-white border border-gray-200 text-gray-700 hover:text-amber-600 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-sm"
                                    data-id="{{ $import->id }}" data-action="{{ route('staff.reception.materials.imports.consume_batch', $import) }}"
                                    data-unit="{{ $material->unit }}" data-max="{{ $import->remaining_quantity }}">
                                    <span class="material-symbols-outlined text-[16px]">outbox</span> Xuất dùng
                                </button>
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
                    <table class="w-full table-fixed text-left border-collapse">
                        <thead class="bg-white text-xs uppercase text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-2 py-3 font-semibold w-[9%]">Mã lô</th>
                                <th class="px-2 py-3 font-semibold w-[10%]">Thời gian</th>
                                <th class="px-2 py-3 font-semibold text-right w-[8%]">SL đầu</th>
                                <th class="px-2 py-3 font-semibold text-right w-[10%]">Tổng tiền</th>
                                <th class="px-2 py-3 font-semibold text-right w-[10%]">Đơn giá/{{ $material->unit }}</th>
                                <th class="px-2 py-3 font-semibold text-right w-[7%]">Tồn lô</th>
                                <th class="px-2 py-3 font-semibold w-[9%]">Hạn SD</th>
                                <th class="px-2 py-3 font-semibold text-right w-[9%]">Còn lại</th>
                                <th class="px-2 py-3 font-semibold w-[22%]">Ghi chú</th>
                                <th class="px-2 py-3 font-semibold text-right w-[6%]">Xuất</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse($nhapKho as $import)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-2 py-3 font-bold text-blue-600 truncate">LOT-{{ $import->id }}</td>
                                    <td class="px-2 py-3 text-gray-500 text-xs leading-tight">
                                        {{ $import->created_at->format('d/m/Y') }}<br>
                                        <span class="text-gray-400">{{ $import->created_at->format('H:i') }}</span>
                                    </td>
                                    <td class="px-2 py-3 font-bold text-emerald-600 text-right truncate">+{{ number_format($import->quantity, 2, ',', '.') }}</td>
                                    <td class="px-2 py-3 font-bold text-gray-900 text-right truncate">{{ number_format($import->total_price, 0, ',', '.') }}đ</td>
                                    <td class="px-2 py-3 text-gray-600 text-right truncate">{{ $import->quantity != 0 ? number_format(abs($import->total_price / $import->quantity), 0, ',', '.') : 0 }}đ</td>
                                    <td class="px-2 py-3 text-gray-600 text-right font-semibold text-blue-600 truncate">{{ number_format($import->remaining_quantity, 2, ',', '.') }}</td>
                                    <td class="px-2 py-3 text-gray-600">
                                        @if($import->expiration_date)
                                            @php
                                                $daysDiffImport = now()->startOfDay()->diffInDays($import->expiration_date->startOfDay(), false);
                                            @endphp
                                            <span class="truncate block {{ $import->remaining_quantity == 0 ? 'text-gray-400' : ($daysDiffImport < 0 ? 'text-gray-500 line-through' : ($daysDiffImport <= 30 ? 'text-red-500 font-bold' : '')) }}">
                                                {{ $import->expiration_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            @php $daysDiffImport = null; @endphp
                                            -
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-right">
                                        @if($import->expiration_date)
                                            @if($import->remaining_quantity == 0)
                                                <span class="text-gray-400">-</span>
                                            @elseif($daysDiffImport < 0)
                                                <span class="text-xs text-red-500 font-bold bg-red-50 px-1.5 py-0.5 rounded whitespace-nowrap">Hết hạn</span>
                                            @else
                                                <span class="font-bold {{ $daysDiffImport <= 15 ? 'text-red-600' : 'text-emerald-600' }} whitespace-nowrap">{{ $daysDiffImport }} ngày</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 text-gray-500 truncate" title="{{ $import->note }}">{{ $import->note ?? '-' }}</td>
                                    <td class="px-2 py-3 text-right">
                                        @if($import->remaining_quantity > 0)
                                            <button type="button" title="Xuất dùng từ đúng lô này"
                                                class="js-consume-batch p-1 text-gray-400 hover:text-amber-600 transition-colors"
                                                data-id="{{ $import->id }}" data-action="{{ route('staff.reception.materials.imports.consume_batch', $import) }}"
                                                data-unit="{{ $material->unit }}" data-max="{{ $import->remaining_quantity }}">
                                                <span class="material-symbols-outlined">outbox</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-10 text-center text-gray-400">Chưa có dữ liệu nhập kho.</td>
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

    <div id="modal-consume-batch"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center {{ $errors->any() && old('_form_context') === 'consume-batch' ? '' : 'hidden' }} z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0 overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Xuất Kho Từ Lô <span id="consume-batch-id" class="text-blue-600">{{ old('_form_context') === 'consume-batch' ? 'LOT-' . str_pad((string) old('_lot_id'), 4, '0', STR_PAD_LEFT) : '' }}</span></h3>
                <button type="button" data-close-modal="modal-consume-batch" class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-consume-batch" method="POST" action="{{ old('_form_context') === 'consume-batch' ? old('_form_action') : '' }}" class="p-6">
                @csrf
                <input type="hidden" name="_form_context" value="consume-batch">
                <input type="hidden" name="_form_action" id="consume-form-action" value="{{ old('_form_action') }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng xuất (<span id="consume-batch-unit">{{ old('_form_context') === 'consume-batch' ? old('_unit') : '' }}</span>)</label>
                        <input type="number" step="1" min="1" id="consume-batch-quantity" name="quantity" required
                            value="{{ old('_form_context') === 'consume-batch' ? old('quantity') : '' }}"
                            max="{{ old('_form_context') === 'consume-batch' ? old('_max_quantity') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all">
                        <p class="text-xs text-gray-500 mt-1">Tồn kho của lô này: <span id="consume-batch-max" class="font-bold text-amber-600">{{ old('_form_context') === 'consume-batch' ? old('_max_quantity') : '' }}</span></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Lý do</label>
                        <input type="hidden" name="_max_quantity" id="consume-max-quantity" value="{{ old('_max_quantity') }}">
                        <input type="text" id="consume-reason" name="reason" required placeholder="VD: Hết ly tại quầy, lấy thêm để pha chế..."
                            data-max-length="255" data-field-label="Lý do" aria-describedby="consume-reason-error"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all"
                            value="{{ old('_form_context') === 'consume-batch' ? old('reason') : '' }}">
                        <p id="consume-reason-error" data-error-for="consume-reason" class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" data-close-modal="modal-consume-batch" class="w-full sm:w-auto text-center px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors border border-gray-200 sm:border-transparent">Hủy</button>
                    <button type="submit" class="w-full sm:w-auto text-center px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 transition-all">Xác nhận Xuất Kho</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/backend/staff/reception/materials/common.js') }}"></script>
        <script src="{{ asset('js/backend/staff/reception/materials/imports.js') }}"></script>
    @endpush
@endsection
