@extends('backend.layouts.app')

@section('title', 'Lịch sử Nhập Kho')

@section('content')
    <div id="materials-imports-page" class="material-imports-page p-4 sm:p-6">
        <div class="mb-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <a href="{{ route('admin.materials.index') }}"
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

            <!-- Biểu mẫu Tạo Phiếu Nhập Kho Mới -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Tạo phiếu nhập</h3>

                @if(session('success'))
                    <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded border border-emerald-200 text-sm font-medium">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any() && !old('_form_context'))
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

            <!-- Bảng Lịch sử Nhập kho & Xuất kho -->
            @php
                $nhapKho = $imports->where('quantity', '>', 0);
                $xuatHuy = $imports->where('quantity', '<', 0);
            @endphp

            <div class="bg-transparent lg:bg-white lg:rounded-xl lg:border lg:border-gray-200 lg:shadow-sm lg:overflow-hidden mb-6">
                <div class="px-4 py-3 flex items-center justify-between lg:p-5 lg:border-b lg:border-gray-100 lg:bg-gray-50/50">
                    <h3 class="font-bold text-gray-900 flex items-center"><span
                            class="material-symbols-outlined align-middle mr-1.5 text-emerald-600">login</span>Lịch sử Nhập
                        kho</h3>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full lg:bg-transparent lg:p-0">{{ $nhapKho->count() }} phiếu nhập</span>
                </div>
                <!-- Giao diện Mobile -->
                <div class="block lg:hidden space-y-4 px-1 py-2">
                    @forelse($nhapKho as $import)
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-4 relative hover:shadow-md transition-shadow" id="import-card-{{ $import->id }}">
                            <!-- Header -->
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

                            <!-- Info Grid -->
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

                            <!-- Expiry date & remaining days -->
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

                            <!-- Note -->
                            @if($import->note)
                                <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 italic" style="overflow-wrap: anywhere; word-break: break-word;">
                                    Ghi chú: {{ $import->note }}
                                </div>
                            @endif

                            <!-- Action Buttons -->
                            <div class="flex gap-2.5 pt-1">
                                @if($import->quantity > 0)
                                    <button type="button" title="Sửa thông tin phiếu nhập"
                                        class="js-edit-import flex-1 py-2 bg-white border border-gray-200 text-gray-700 hover:text-blue-600 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-sm"
                                        data-id="{{ $import->id }}" data-action="{{ route('admin.materials.imports.update', $import) }}"
                                        data-quantity="{{ $import->quantity }}" data-total-price="{{ $import->total_price }}"
                                        data-expiration-date="{{ $import->expiration_date ? $import->expiration_date->format('Y-m-d') : '' }}"
                                        data-note="{{ $import->note ?? '' }}"
                                        data-consumed="{{ max($import->quantity - $import->remaining_quantity, 0) }}"
                                        data-min-expiration-date="{{ $import->created_at->copy()->addDay()->format('Y-m-d') }}">
                                        <span class="material-symbols-outlined text-[16px]">edit</span> Sửa
                                    </button>
                                @endif
                                @if($import->remaining_quantity > 0)
                                    <button type="button" title="Xuất dùng từ đúng lô này"
                                        class="js-consume-batch flex-1 py-2 bg-white border border-gray-200 text-gray-700 hover:text-amber-600 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-sm"
                                        data-id="{{ $import->id }}" data-action="{{ route('admin.materials.imports.consume_batch', $import) }}"
                                        data-unit="{{ $material->unit }}" data-max="{{ $import->remaining_quantity }}">
                                        <span class="material-symbols-outlined text-[16px]">outbox</span> Xuất
                                    </button>
                                    <button type="button" title="Hủy một phần hoặc toàn bộ lô này"
                                        class="js-dispose-batch flex-1 py-2 bg-white border border-gray-200 text-gray-700 hover:text-red-600 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-sm"
                                        data-id="{{ $import->id }}" data-action="{{ route('admin.materials.imports.dispose_batch', $import) }}"
                                        data-unit="{{ $material->unit }}" data-max="{{ $import->remaining_quantity }}">
                                        <span class="material-symbols-outlined text-[16px]">delete_sweep</span> Hủy lô
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center text-gray-400 flex flex-col items-center gap-2">
                            <span class="material-symbols-outlined text-3xl text-gray-300">inventory_2</span>
                            <span class="text-xs font-semibold">Chưa có dữ liệu nhập kho.</span>
                        </div>
                    @endforelse
                </div>

                <!-- Giao diện Desktop -->
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
                                        <button type="button" title="Xuất dùng từ đúng lô này"
                                            class="js-consume-batch p-1 text-gray-400 hover:text-amber-600 transition-colors mr-1"
                                            data-id="{{ $import->id }}" data-action="{{ route('admin.materials.imports.consume_batch', $import) }}"
                                            data-unit="{{ $material->unit }}" data-max="{{ $import->remaining_quantity }}">
                                            <span class="material-symbols-outlined">outbox</span>
                                        </button>
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

            <div class="bg-transparent lg:bg-white lg:rounded-xl lg:border lg:border-gray-200 lg:shadow-sm lg:overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between lg:p-5 lg:border-b lg:border-gray-100 lg:bg-gray-50/50">
                    <h3 class="font-bold text-gray-900 flex items-center"><span
                            class="material-symbols-outlined align-middle mr-1.5 text-red-600">logout</span>Lịch sử Xuất
                        kho</h3>
                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full lg:bg-transparent lg:p-0">{{ $xuatHuy->count() }} phiếu xuất</span>
                </div>
                <!-- Giao diện Mobile -->
                <div class="block lg:hidden space-y-4 px-1 py-2">
                    @forelse($xuatHuy as $export)
                        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-3.5 relative hover:shadow-md transition-shadow">
                            <!-- Header -->
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

                            <!-- Info Grid -->
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

                            <!-- Note -->
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

                <!-- Giao diện Desktop -->
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
                                        Chưa có dữ liệu xuất kho.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Hộp thoại Xóa/Hủy một phần hoặc toàn bộ Lô hàng -->
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
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" data-close-modal="modal-dispose-batch"
                        class="w-full sm:w-auto text-center px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors border border-gray-200 sm:border-transparent">Hủy</button>
                    <button type="submit"
                        class="w-full sm:w-auto text-center px-5 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 organic-shadow transition-all">Xác
                        nhận Hủy Lô</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal --}}
    <div id="modal-consume-batch"
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center {{ $errors->any() && old('_form_context') === 'consume-batch' ? '' : 'hidden' }} z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 sm:mx-0 overflow-hidden animate-fade-in-up">
            <div class="px-4 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-lg text-gray-900">Xuất Kho Từ Lô <span id="consume-batch-id"
                        class="text-blue-600">{{ old('_form_context') === 'consume-batch' ? 'LOT-' . str_pad((string) old('_lot_id'), 4, '0', STR_PAD_LEFT) : '' }}</span></h3>
                <button type="button" data-close-modal="modal-consume-batch"
                    class="text-gray-400 hover:text-gray-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form id="form-consume-batch" method="POST" action="{{ old('_form_context') === 'consume-batch' ? old('_form_action') : '' }}" class="p-6">
                @csrf
                <input type="hidden" name="_form_context" value="consume-batch">
                <input type="hidden" name="_form_action" id="consume-form-action" value="{{ old('_form_action') }}">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng xuất (<span
                                id="consume-batch-unit">{{ old('_form_context') === 'consume-batch' ? old('_unit') : '' }}</span>)</label>
                        <input type="number" step="1" min="1" id="consume-batch-quantity" name="quantity" required
                            value="{{ old('_form_context') === 'consume-batch' ? old('quantity') : '' }}"
                            max="{{ old('_form_context') === 'consume-batch' ? old('_max_quantity') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all">
                        <p class="text-xs text-gray-500 mt-1">Tồn kho của lô này: <span id="consume-batch-max"
                                class="font-bold text-amber-600">{{ old('_form_context') === 'consume-batch' ? old('_max_quantity') : '' }}</span></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Lý do</label>
                        <input type="hidden" name="_max_quantity" id="consume-max-quantity" value="{{ old('_max_quantity') }}">
                        <input type="text" id="consume-reason" name="reason" required placeholder="VD: Hết ly tại quầy, lấy thêm để pha chế..."
                            data-max-length="255" data-field-label="Lý do" aria-describedby="consume-reason-error"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all"
                            value="{{ old('_form_context') === 'consume-batch' ? old('reason') : '' }}">
                        <p id="consume-reason-error" data-error-for="consume-reason"
                            class="hidden mt-1 text-xs font-medium text-red-600" aria-live="polite"></p>
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" data-close-modal="modal-consume-batch"
                        class="w-full sm:w-auto text-center px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors border border-gray-200 sm:border-transparent">Hủy</button>
                    <button type="submit"
                        class="w-full sm:w-auto text-center px-5 py-2 bg-amber-600 text-white font-semibold rounded-xl hover:bg-amber-700 organic-shadow transition-all">Xác
                        nhận Xuất Kho</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hộp thoại Sửa thông tin Vật tư cơ bản -->
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
                            class="w-full px-4 py-2 border {{ $errors->has('name') && old('_form_context') === 'material-edit' ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500 focus:border-emerald-500' }} rounded-xl outline-none transition-all">
                        <p id="edit-name-error" data-error-for="edit-name"
                            class="{{ $errors->has('name') && old('_form_context') === 'material-edit' ? '' : 'hidden' }} mt-1 text-xs font-medium text-red-600" aria-live="polite">{{ $errors->has('name') && old('_form_context') === 'material-edit' ? $errors->first('name') : '' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Đơn vị (Kg, Bao, Lốc, Cuộn,
                            Cái...)</label>
                        <input type="text" id="edit-unit" name="unit" required data-material-unit data-max-length="20"
                            data-field-label="Đơn vị" data-allowed-existing-value="{{ $material->unit }}" inputmode="text"
                            aria-describedby="edit-unit-error"
                            value="{{ old('_form_context') === 'material-edit' ? old('unit') : '' }}"
                            {{ $imports->isNotEmpty() ? 'readonly' : '' }}
                            class="w-full px-4 py-2 border {{ $errors->has('unit') && old('_form_context') === 'material-edit' ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500 focus:border-emerald-500' }} rounded-xl outline-none transition-all {{ $imports->isNotEmpty() ? 'bg-gray-100' : '' }}">
                        <p id="edit-unit-error" data-error-for="edit-unit"
                            class="{{ $errors->has('unit') && old('_form_context') === 'material-edit' ? '' : 'hidden' }} mt-1 text-xs font-medium text-red-600" aria-live="polite">{{ $errors->has('unit') && old('_form_context') === 'material-edit' ? $errors->first('unit') : '' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Giá vốn dự kiến (VNĐ / Đơn vị)</label>
                        <input type="text" id="edit-formatted-price" required inputmode="numeric"
                            data-max-value="999999999" data-max-message="Giá vốn dự kiến phải nhỏ hơn 1 tỷ đồng."
                            data-number-message="Giá vốn dự kiến chỉ được nhập số."
                            aria-describedby="edit-formatted-price-error"
                            value="{{ old('_form_context') === 'material-edit' && old('unit_price') !== null ? number_format((float) old('unit_price'), 0, ',', '.') : '' }}"
                            {{ $imports->isNotEmpty() ? 'readonly' : '' }}
                            class="w-full px-4 py-2 border {{ $errors->has('unit_price') && old('_form_context') === 'material-edit' ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-emerald-500 focus:border-emerald-500' }} rounded-xl outline-none transition-all {{ $imports->isNotEmpty() ? 'bg-gray-100' : '' }}">
                        <p id="edit-formatted-price-error" data-error-for="edit-formatted-price"
                            class="{{ $errors->has('unit_price') && old('_form_context') === 'material-edit' ? '' : 'hidden' }} mt-1 text-xs font-medium text-red-600" aria-live="polite">{{ $errors->has('unit_price') && old('_form_context') === 'material-edit' ? $errors->first('unit_price') : '' }}</p>
                        <input type="hidden" id="edit-price" name="unit_price" value="{{ old('_form_context') === 'material-edit' ? old('unit_price') : '' }}">
                    </div>
                </div>
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" data-close-modal="modal-edit"
                        class="w-full sm:w-auto text-center px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors border border-gray-200 sm:border-transparent">Hủy</button>
                    <button type="submit"
                        class="w-full sm:w-auto text-center px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Cập
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
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng nhập ({{ $material->unit }}) </label>
                        <input type="number" id="edit-import-quantity" name="quantity" required
                            min="{{ old('_form_context') === 'import-edit' ? max(1, (int) old('_min_quantity')) : 1 }}" max="1000" step="1"
                            value="{{ old('_form_context') === 'import-edit' ? old('quantity') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                        <p id="edit-import-quantity-error" data-error-for="edit-import-quantity" class="hidden mt-1 text-xs font-medium text-red-600"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tổng tiền thanh toán (VNĐ)</label>
                        <input type="number" id="edit-import-total-price" name="total_price" required min="1" max="9999999999.99" step="0.01"
                            value="{{ old('_form_context') === 'import-edit' ? old('total_price') : '' }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Hạn sử dụng (Tùy chọn)</label>
                        <input type="text" id="edit-import-expiration-date" name="expiration_date"
                            value="{{ old('_form_context') === 'import-edit' ? old('expiration_date') : '' }}"
                            class="flatpickr-date w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all"
                            placeholder="Chọn ngày...">
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
                <div class="mt-6 flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" data-close-modal="modal-edit-import"
                        class="w-full sm:w-auto text-center px-5 py-2 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors border border-gray-200 sm:border-transparent">Hủy</button>
                    <button type="submit"
                        class="w-full sm:w-auto text-center px-5 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    "use strict";

    // Mở hộp thoại theo id truyền vào
    function openModal(id) {
        document.getElementById(id)?.classList.remove("hidden");
    }

    // Đóng hộp thoại và dọn trạng thái đang giữ
    function closeModal(id) {
        document.getElementById(id)?.classList.add("hidden");
    }

    // Tìm thẻ chứa dòng báo lỗi gắn với ô nhập đó
    function getFieldErrorElement(input) {
        if (!input?.id) return null;
        return document.querySelector(`[data-error-for="${input.id}"]`);
    }

    // Hiện/ẩn dòng lỗi đỏ ngay dưới ô nhập, không cần chờ submit
    function setFieldError(input, message = "", blockSubmission = true) {
        if (!input) return;

        const hasError = message !== "";
        const errorElement = getFieldErrorElement(input);

        input.setCustomValidity(blockSubmission ? message : "");
        input.setAttribute("aria-invalid", hasError ? "true" : "false");
        
        input.classList.toggle("border-red-500", hasError);
        input.classList.toggle("focus:border-red-500", hasError);
        input.classList.toggle("focus:ring-red-500", hasError);
        input.style.borderColor = hasError ? "#ef4444" : "";

        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.toggle("hidden", !hasError);
        }
    }

    // Dựng trước chuỗi SẼ thành sau khi chèn ký tự tại vị trí con trỏ, để biết có nên chặn hay không
    function getProposedValue(input, insertedText) {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        return `${input.value.slice(0, start)}${insertedText}${input.value.slice(end)}`;
    }

    // Bắt sự kiện beforeinput để chặn ký tự sai ngay TRƯỚC khi nó hiện ra, tránh hiện rồi mới xóa gây nhấp nháy
    function guardInsertedContent(input, getValidationMessage) {
        input.addEventListener("beforeinput", function (event) {
            if (
                event.isComposing ||
                typeof event.inputType !== "string" ||
                !event.inputType.startsWith("insert") ||
                typeof event.data !== "string"
            ) {
                return;
            }

            const message = getValidationMessage(getProposedValue(this, event.data));
            if (!message) return;

            event.preventDefault();
            setFieldError(this, message, false);
        });

        input.addEventListener("paste", function (event) {
            const pastedText = event.clipboardData?.getData("text");
            if (typeof pastedText !== "string") return;

            const message = getValidationMessage(getProposedValue(this, pastedText));
            if (!message) return;

            event.preventDefault();
            setFieldError(this, message, false);
        });
    }

    // Sinh câu thông báo lỗi tiếng Việt tương ứng với loại vi phạm của ô chữ
    function getTextValidationMessage(input, value) {
        const valueLength = Array.from(value).length;
        const maxLength = Number(input.dataset.maxLength);
        const fieldLabel = input.dataset.fieldLabel || "Nội dung";

        if (input.matches("[data-material-unit]") && value !== "") {
            const allowedExistingValue = input.dataset.allowedExistingValue;
            const isUnchangedExistingValue =
                allowedExistingValue !== undefined && value === allowedExistingValue;

            if (!isUnchangedExistingValue && /\p{N}/u.test(value)) {
                return "Đơn vị không được nhập số.";
            }

            if (
                !isUnchangedExistingValue &&
                !/^[\p{L}\p{M}\s.\/-]+$/u.test(value)
            ) {
                return "Đơn vị không được nhập ký tự đặc biệt.";
            }
        }

        if (Number.isFinite(maxLength) && valueLength > maxLength) {
            return `${fieldLabel} không được nhập quá ${maxLength} ký tự.`;
        }

        return "";
    }

    // Làm sạch chuỗi nhập vào; riêng ô đơn vị tính cho giữ nguyên giá trị cũ đã lưu
    function getSanitizedTextValue(input, value) {
        let sanitizedValue = value;
        const allowedExistingValue = input.dataset.allowedExistingValue;

        if (
            input.matches("[data-material-unit]") &&
            (allowedExistingValue === undefined || value !== allowedExistingValue)
        ) {
            sanitizedValue = sanitizedValue
                .replace(/\p{N}/gu, "")
                .replace(/[^\p{L}\p{M}\s.\/-]/gu, "");
        }

        const maxLength = Number(input.dataset.maxLength);
        if (Number.isFinite(maxLength)) {
            sanitizedValue = Array.from(sanitizedValue).slice(0, maxLength).join("");
        }

        return sanitizedValue;
    }

    // Kiểm tra giá trị hiện tại của ô chữ có hợp lệ không
    function validateTextInput(input) {
        if (!input) return true;

        const message = getTextValidationMessage(input, input.value);
        setFieldError(input, message);

        if (!message) input.dataset.lastValidValue = input.value;

        return message === "";
    }

    // Gắn toàn bộ xử lý kiểm tra ký tự vào một ô nhập chữ
    function bindTextValidation(root = document) {
        root.querySelectorAll("[data-max-length], [data-material-unit]").forEach((input) => {
            if (input.dataset.textValidationBound === "true") return;

            input.dataset.textValidationBound = "true";
            guardInsertedContent(input, (value) => getTextValidationMessage(input, value));

            // Chạy mỗi lần gõ vào ô chữ: làm sạch giá trị và cập nhật thông báo lỗi
            function handleTextInput(event) {
                if (event?.isComposing) return;

                const message = getTextValidationMessage(input, input.value);
                if (message) {
                    input.value = input.dataset.lastValidValue ?? "";
                    setFieldError(input, message, false);
                    return;
                }

                input.dataset.lastValidValue = input.value;
                setFieldError(input);
            }

            input.addEventListener("input", handleTextInput);
            input.addEventListener("compositionend", handleTextInput);

            const initialMessage = getTextValidationMessage(input, input.value);
            if (initialMessage) {
                input.value = getSanitizedTextValue(input, input.value);
                input.dataset.lastValidValue = input.value;
                setFieldError(input, initialMessage, false);
            } else {
                input.dataset.lastValidValue = input.value;
            }
        });
    }

    // Nạp giá trị có sẵn vào ô tiền khi mở form sửa
    function syncCurrencyValue(formattedInput, rawInput, value) {
        const numericValue = Number(value) || 0;
        rawInput.value = numericValue;
        formattedInput.value = new Intl.NumberFormat("vi-VN").format(numericValue);
        formattedInput.dataset.lastValidDigits = String(numericValue);
        setFieldError(formattedInput);
    }

    // Trả về thông báo lỗi cho ô tiền
    function getCurrencyValidation(input, value) {
        if (/[^\d.,\s]/u.test(value)) {
            return {
                digits: value.replace(/\D/g, ""),
                message: input.dataset.numberMessage || "Chỉ được nhập số.",
            };
        }

        const digits = value.replace(/\D/g, "");
        const maxValue = Number(input.dataset.maxValue);
        const exceedsMaximum =
            Number.isFinite(maxValue) && digits !== "" && Number(digits) > maxValue;

        return {
            digits,
            message: exceedsMaximum
                ? input.dataset.maxMessage || "Giá trị vượt quá giới hạn cho phép."
                : "",
        };
    }

    // Gắn xử lý tiền tệ vào ô nhập: dùng 2 input song song, ô hiện số đã format cho người xem và ô ẩn giữ số thô để gửi server
    function bindCurrencyInput(formattedInput, rawInput) {
        if (!formattedInput || !rawInput || formattedInput.dataset.currencyBound === "true") return;

        // Ghi giá trị vào cả 2 ô: ô thô nhận số nguyên, ô hiển thị nhận chuỗi đã chấm phân cách kiểu Việt Nam
        function setCurrencyValue(digits) {
            rawInput.value = digits;
            formattedInput.value =
                digits === "" ? "" : new Intl.NumberFormat("vi-VN").format(digits);
            formattedInput.dataset.lastValidDigits = digits;
        }

        // Chạy mỗi lần gõ vào ô tiền: lọc bỏ ký tự không phải số rồi format lại
        function handleCurrencyInput(event) {
            if (event?.isComposing) return;

            const validation = getCurrencyValidation(formattedInput, formattedInput.value);
            if (validation.message) {
                setCurrencyValue(formattedInput.dataset.lastValidDigits ?? "");
                setFieldError(formattedInput, validation.message, false);
                return;
            }

            setCurrencyValue(validation.digits);
            setFieldError(formattedInput);
        }

        formattedInput.dataset.currencyBound = "true";
        guardInsertedContent(
            formattedInput,
            (value) => getCurrencyValidation(formattedInput, value).message,
        );
        formattedInput.addEventListener("input", handleCurrencyInput);
        formattedInput.addEventListener("compositionend", handleCurrencyInput);

        const initialValidation = getCurrencyValidation(formattedInput, formattedInput.value);
        if (initialValidation.message) {
            setCurrencyValue("");
            setFieldError(formattedInput, initialValidation.message, false);
        } else {
            setCurrencyValue(initialValidation.digits);
        }
    }

    // Hỏi xác nhận trước khi thực hiện thao tác không hoàn tác được
    function confirmAction(title, text) {
        return new Promise(function (resolve) {
            window.AdminAlert.confirm(text, function () { resolve(true); }, title);
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        bindTextValidation();

        document.addEventListener("click", function (event) {
            const openButton = event.target.closest("[data-open-modal]");
            if (openButton) {
                openModal(openButton.dataset.openModal);
                return;
            }

            const closeButton = event.target.closest("[data-close-modal]");
            if (closeButton) {
                closeModal(closeButton.dataset.closeModal);
            }
        });
    });

    window.MaterialsCommon = {
        bindCurrencyInput,
        bindTextValidation,
        closeModal,
        confirmAction,
        openModal,
        setFieldError,
        syncCurrencyValue,
        validateTextInput,
    };
})();

document.addEventListener("DOMContentLoaded", function () {
    const page = document.getElementById("materials-imports-page");
    if (!page) return;

    const createImportQuantity = document.getElementById("create-import-quantity");
    const formattedTotalPrice = document.getElementById("formatted_total_price");
    const rawTotalPrice = document.getElementById("total_price");
    const createImportForm = document.getElementById("form-create-import");
    const editFormattedPrice = document.getElementById("edit-formatted-price");
    const editRawPrice = document.getElementById("edit-price");

    MaterialsCommon.bindCurrencyInput(formattedTotalPrice, rawTotalPrice);
    MaterialsCommon.bindCurrencyInput(editFormattedPrice, editRawPrice);

    if (typeof flatpickr !== 'undefined') {
        const createExpirationInput = document.getElementById("create-expiration-date");
        if (createExpirationInput) {
            flatpickr(createExpirationInput, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                locale: "vn",
                disableMobile: true,
                monthSelectorType: "static",
                minDate: createExpirationInput.dataset.minDate || ""
            });
        }

        const editExpirationInput = document.getElementById("edit-import-expiration-date");
        if (editExpirationInput) {
            flatpickr(editExpirationInput, {
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                locale: "vn",
                disableMobile: true,
                monthSelectorType: "static"
            });
        }
    }

    if (createImportQuantity && formattedTotalPrice && rawTotalPrice) {
        const unitPrice = Number(createImportQuantity.dataset.unitPrice) || 0;

        createImportQuantity.addEventListener("input", function () {
            const quantity = Number(this.value) || 0;
            
            if (quantity >= 1000) {
                MaterialsCommon.setFieldError(this, "Số lượng nhập phải bé hơn 1000.", false);
            } else {
                MaterialsCommon.setFieldError(this, "", false);
            }

            const total = Math.round(quantity * unitPrice);

            if (total > 0) {
                MaterialsCommon.syncCurrencyValue(formattedTotalPrice, rawTotalPrice, total);
            } else {
                rawTotalPrice.value = "";
                formattedTotalPrice.value = "";
                formattedTotalPrice.dataset.lastValidDigits = "";
                MaterialsCommon.setFieldError(formattedTotalPrice);
            }
        });
    }

    const editImportQuantity = document.getElementById("edit-import-quantity");
    if (editImportQuantity) {
        editImportQuantity.addEventListener("input", function () {
            const quantity = Number(this.value) || 0;
            if (quantity >= 1000) {
                MaterialsCommon.setFieldError(this, "Số lượng nhập phải bé hơn 1000.", false);
            } else {
                MaterialsCommon.setFieldError(this, "", false);
            }
        });
    }

    createImportForm?.addEventListener("submit", function (event) {
        const totalPrice = Number(rawTotalPrice?.value);
        if (Number.isFinite(totalPrice) && totalPrice > 0 && totalPrice <= 9999999999.99) return;

        event.preventDefault();
        MaterialsCommon.setFieldError(
            formattedTotalPrice,
            totalPrice > 9999999999.99
                ? "Tổng tiền thanh toán vượt quá giới hạn cho phép."
                : "Tổng tiền thanh toán phải lớn hơn 0.",
        );
        formattedTotalPrice?.focus();
    });

    page.addEventListener("click", function (event) {
        const editMaterialButton = event.target.closest(".js-edit-material");
        if (editMaterialButton) {
            const hasImports = editMaterialButton.dataset.hasImports === "true";
            const nameInput = document.getElementById("edit-name");
            const unitInput = document.getElementById("edit-unit");
            const form = document.getElementById("form-edit");
            const formAction = document.getElementById("material-edit-form-action");

            if (nameInput) {
                nameInput.value = editMaterialButton.dataset.name || "";
                MaterialsCommon.validateTextInput(nameInput);
            }
            
            if (unitInput) {
                unitInput.value = editMaterialButton.dataset.unit || "";
                unitInput.dataset.allowedExistingValue = editMaterialButton.dataset.unit || "";
                
                unitInput.readOnly = hasImports;
                unitInput.classList.toggle("bg-gray-100", hasImports);
                unitInput.title = hasImports
                    ? "Không thể đổi đơn vị khi vật tư đã có phiếu nhập."
                    : "";
                MaterialsCommon.validateTextInput(unitInput);
            }
            
            if (editFormattedPrice && editRawPrice) {
                MaterialsCommon.syncCurrencyValue(
                    editFormattedPrice,
                    editRawPrice,
                    editMaterialButton.dataset.price,
                );
                
                editFormattedPrice.readOnly = hasImports;
                editFormattedPrice.classList.toggle("bg-gray-100", hasImports);
                editFormattedPrice.title = hasImports
                    ? "Giá vốn được tính tự động từ các phiếu nhập."
                    : "";
            }
            
            if (form) form.action = editMaterialButton.dataset.action;
            if (formAction) formAction.value = editMaterialButton.dataset.action;
            MaterialsCommon.openModal("modal-edit");
            return;
        }

        const editImportButton = event.target.closest(".js-edit-import");
        if (editImportButton) {
            const quantityInput = document.getElementById("edit-import-quantity");
            const priceInput = document.getElementById("edit-import-total-price");
            const expirationInput = document.getElementById("edit-import-expiration-date");
            const noteInput = document.getElementById("edit-import-note");
            const idText = document.getElementById("edit-import-id-text");
            const form = document.getElementById("form-edit-import");
            const formAction = document.getElementById("import-edit-form-action");
            const minQuantity = document.getElementById("import-edit-min-quantity");
            const minExpiration = document.getElementById("import-edit-min-expiration");

            if (idText) idText.textContent = `LOT-${editImportButton.dataset.id}`;
            if (quantityInput) {
                quantityInput.value = editImportButton.dataset.quantity;
                quantityInput.min = Math.max(
                    1,
                    Math.ceil(Number(editImportButton.dataset.consumed) || 0),
                );
            }
            if (priceInput) priceInput.value = editImportButton.dataset.totalPrice;
            if (expirationInput) {
                if (expirationInput._flatpickr) {
                    expirationInput._flatpickr.setDate(editImportButton.dataset.expirationDate || "");
                    expirationInput._flatpickr.set('minDate', editImportButton.dataset.minExpirationDate || "");
                } else {
                    expirationInput.value = editImportButton.dataset.expirationDate || "";
                    expirationInput.min = editImportButton.dataset.minExpirationDate || "";
                }
            }
            if (noteInput) {
                noteInput.value = editImportButton.dataset.note || "";
                MaterialsCommon.validateTextInput(noteInput);
            }
            if (form) form.action = editImportButton.dataset.action;
            if (formAction) formAction.value = editImportButton.dataset.action;
            if (minQuantity) minQuantity.value = editImportButton.dataset.consumed || "0";
            if (minExpiration) {
                minExpiration.value = editImportButton.dataset.minExpirationDate || "";
            }
            MaterialsCommon.openModal("modal-edit-import");
            return;
        }

        const disposeButton = event.target.closest(".js-dispose-batch");
        if (disposeButton) {
            const quantityInput = document.getElementById("dispose-batch-quantity");
            const unitText = document.getElementById("dispose-batch-unit");
            const maxText = document.getElementById("dispose-batch-max");
            const idText = document.getElementById("dispose-batch-id");
            const form = document.getElementById("form-dispose-batch");
            const formAction = document.getElementById("dispose-form-action");
            const maxQuantity = document.getElementById("dispose-max-quantity");

            if (quantityInput) {
                quantityInput.value = "";
                quantityInput.max = disposeButton.dataset.max;
            }
            if (unitText) unitText.textContent = disposeButton.dataset.unit;
            if (maxText) maxText.textContent = disposeButton.dataset.max;
            if (idText) idText.textContent = `LOT-${String(disposeButton.dataset.id).padStart(4, "0")}`;
            if (form) form.action = disposeButton.dataset.action;
            if (formAction) formAction.value = disposeButton.dataset.action;
            if (maxQuantity) maxQuantity.value = disposeButton.dataset.max;
            MaterialsCommon.openModal("modal-dispose-batch");
        }

        const consumeButton = event.target.closest(".js-consume-batch");
        if (consumeButton) {
            const quantityInput = document.getElementById("consume-batch-quantity");
            const unitText = document.getElementById("consume-batch-unit");
            const maxText = document.getElementById("consume-batch-max");
            const idText = document.getElementById("consume-batch-id");
            const form = document.getElementById("form-consume-batch");
            const formAction = document.getElementById("consume-form-action");
            const maxQuantity = document.getElementById("consume-max-quantity");

            if (quantityInput) {
                quantityInput.value = "";
                quantityInput.max = consumeButton.dataset.max;
            }
            if (unitText) unitText.textContent = consumeButton.dataset.unit;
            if (maxText) maxText.textContent = consumeButton.dataset.max;
            if (idText) idText.textContent = `LOT-${String(consumeButton.dataset.id).padStart(4, "0")}`;
            if (form) form.action = consumeButton.dataset.action;
            if (formAction) formAction.value = consumeButton.dataset.action;
            if (maxQuantity) maxQuantity.value = consumeButton.dataset.max;
            MaterialsCommon.openModal("modal-consume-batch");
        }
    });

    page.addEventListener("submit", async function (event) {
        const deleteForm = event.target.closest(".js-material-delete-form");
        if (!deleteForm) return;

        event.preventDefault();
        const confirmed = await MaterialsCommon.confirmAction(
            "Xác nhận xóa vật tư?",
            "Vật tư này sẽ bị xóa vĩnh viễn khỏi hệ thống.",
        );
        if (confirmed) deleteForm.submit();
    });
});
</script>
@endpush

