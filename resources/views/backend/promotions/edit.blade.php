@extends('backend.layouts.app')

@section('title', 'Chỉnh sửa Khuyến mãi')

@section('content')
<div class="promotions-page">
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('admin.promotions.index') }}"
            onclick="if(document.referrer.includes(window.location.host)) { event.preventDefault(); window.history.back(); }"
            class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Chỉnh sửa Khuyến mãi</h2>
            <p class="text-gray-500 text-sm mt-1">Cập nhật mã: <span class="font-bold font-mono text-emerald-600">{{ $promotion->code }}</span></p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 text-red-700 rounded-xl border border-red-200 text-sm font-medium">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.promotions.update', $promotion->id) }}" method="POST" id="promotion-form">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Cột trái: Thông tin cơ bản -->
            <div class="lg:col-span-2 space-y-5">
                <!-- Card: Thông tin chính -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 text-[20px]" style="font-variation-settings: 'FILL' 1;">local_offer</span>
                        Thông tin khuyến mãi
                    </h3>
                    <div class="space-y-5">
                        <!-- Mã khuyến mãi -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mã khuyến mãi (Code)</label>
                            <div class="flex gap-2">
                                <input type="text" name="code" id="promo-code"
                                    value="{{ old('code', $promotion->code) }}"
                                    maxlength="10"
                                    placeholder="Tối đa 10 ký tự"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm font-mono uppercase tracking-wider"
                                    oninput="this.value = this.value.toUpperCase()">
                                <button type="button" id="btn-gen-code"
                                    class="px-4 py-2.5 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition-colors text-sm font-semibold whitespace-nowrap flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[18px]">shuffle</span>
                                    Tự sinh
                                </button>
                            </div>
                        </div>

                        <!-- Loại khuyến mãi -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Loại khuyến mãi <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="type-selector">
                                <label class="flex items-center gap-3 border-2 {{ old('type', $promotion->type) == 'percent' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 bg-white' }} rounded-xl p-4 cursor-pointer transition-all hover:border-gray-300" id="type-percent-label">
                                    <input type="radio" name="type" value="percent" class="hidden" {{ old('type', $promotion->type) == 'percent' ? 'checked' : '' }}>
                                    <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center text-violet-600 flex-shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">percent</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-sm text-gray-800">Giảm theo %</p>
                                        <p class="text-xs text-gray-500">VD: Giảm 20% đơn hàng</p>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 border-2 {{ old('type', $promotion->type) == 'fixed' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-200 bg-white' }} rounded-xl p-4 cursor-pointer transition-all hover:border-gray-300" id="type-fixed-label">
                                    <input type="radio" name="type" value="fixed" class="hidden" {{ old('type', $promotion->type) == 'fixed' ? 'checked' : '' }}>
                                    <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                                        <span class="material-symbols-outlined text-[20px]">payments</span>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-sm text-gray-800">Giảm tiền cố định</p>
                                        <p class="text-xs text-gray-500">VD: Giảm 50.000đ</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Giá trị giảm -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">
                                    Giá trị giảm <span class="text-red-500">*</span>
                                    <span id="value-unit" class="font-normal text-gray-500">
                                        {{ old('type', $promotion->type) == 'percent' ? '(% tỷ lệ)' : '(VNĐ cố định)' }}
                                    </span>
                                </label>
                                @php
                                    $oldValue = old('value', $promotion->value);
                                    $oldType  = old('type', $promotion->type);
                                    $displayValue = ($oldType === 'fixed' && $oldValue)
                                        ? number_format($oldValue, 0, ',', '.')
                                        : $oldValue;
                                @endphp
                                <input type="text" id="display-value"
                                    value="{{ $displayValue }}"
                                    placeholder="VD: 20"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                                <input type="hidden" name="value" id="promo-value" value="{{ old('value', $promotion->value) }}">
                            </div>
                            <!-- Giảm tối đa (chỉ hiện khi type=percent) -->
                            <div id="max-discount-wrap" {{ old('type', $promotion->type) == 'fixed' ? 'style=display:none' : '' }}>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Giảm tối đa (VNĐ)</label>
                                <input type="text" id="display-max-discount"
                                    value="{{ old('max_discount_amount', $promotion->max_discount_amount) ? number_format(old('max_discount_amount', $promotion->max_discount_amount), 0, ',', '.') : '' }}"
                                    placeholder="Không giới hạn"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                                <input type="hidden" name="max_discount_amount" id="promo-max-discount" value="{{ old('max_discount_amount', $promotion->max_discount_amount) }}">
                            </div>
                        </div>

                        <!-- Mô tả -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả ngắn</label>
                            <input type="text" name="description" id="promo-description"
                                value="{{ old('description', $promotion->description) }}"
                                maxlength="100"
                                placeholder="VD: Giảm 20% cho đơn từ 100k"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                            <p class="text-xs text-gray-400 mt-1 flex justify-between"><span>Tối đa 100 ký tự.</span><span id="desc-count">{{ strlen(old('description', $promotion->description ?? '')) }}/100</span></p>
                        </div>
                    </div>
                </div>

                <!-- Card: Điều kiện áp dụng -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-500 text-[20px]" style="font-variation-settings: 'FILL' 1;">rule</span>
                        Điều kiện áp dụng
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Đơn hàng tối thiểu (VNĐ)</label>
                            <input type="text" id="display-min-order"
                                value="{{ old('min_order_amount', $promotion->min_order_amount) ? number_format(old('min_order_amount', $promotion->min_order_amount), 0, ',', '.') : '' }}"
                                placeholder="Không giới hạn"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                            <input type="hidden" name="min_order_amount" id="promo-min-order" value="{{ old('min_order_amount', $promotion->min_order_amount) }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Áp dụng cho hạng thành viên</label>
                            <select name="apply_for" class="custom-select-init w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm appearance-none bg-white" data-width-class="w-full">
                                <option value="all" {{ old('apply_for', $promotion->apply_for) == 'all' ? 'selected' : '' }}>Tất cả các hạng</option>
                                <option value="new" {{ old('apply_for', $promotion->apply_for) == 'new' ? 'selected' : '' }}>Mới (New)</option>
                                <option value="silver" {{ old('apply_for', $promotion->apply_for) == 'silver' ? 'selected' : '' }}>Bạc (Silver)</option>
                                <option value="gold" {{ old('apply_for', $promotion->apply_for) == 'gold' ? 'selected' : '' }}>Vàng (Gold)</option>
                                <option value="diamond" {{ old('apply_for', $promotion->apply_for) == 'diamond' ? 'selected' : '' }}>Kim cương (Diamond)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Giới hạn số lượt dùng</label>
                            <input type="number" name="usage_limit"
                                value="{{ old('usage_limit', $promotion->usage_limit) }}"
                                placeholder="Không giới hạn"
                                min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        </div>
                    </div>

                    <!-- Thông tin chỉ đọc -->
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 mb-3 uppercase tracking-wider">Thông tin sử dụng</p>
                        <div class="flex gap-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ $promotion->used_count ?? 0 }}</p>
                                <p class="text-xs text-gray-400">Lượt đã dùng</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ $promotion->usage_limit ?? '∞' }}</p>
                                <p class="text-xs text-gray-400">Tổng giới hạn</p>
                            </div>
                            @if($promotion->usage_limit && $promotion->used_count)
                            <div class="flex-1 flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    @php $pct = min(100, round(($promotion->used_count / $promotion->usage_limit) * 100)); @endphp
                                    <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 ml-2 whitespace-nowrap">{{ $pct }}%</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Thời gian & Trạng thái -->
            <div class="lg:col-span-1 space-y-5">
                <!-- Card: Thời gian -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-500 text-[20px]" style="font-variation-settings: 'FILL' 1;">calendar_month</span>
                        Thời gian áp dụng
                    </h3>
                    
                    <div class="mb-4 pb-4 border-b border-gray-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_recurring" id="is_recurring_cb" value="1" {{ old('is_recurring', $promotion->is_recurring) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                            <span class="text-sm font-semibold text-gray-700">Khuyến mãi lặp lại định kỳ</span>
                        </label>
                        <p class="text-[11px] text-gray-500 mt-1 ml-6">Sử dụng cho Giờ Vàng (vd: 7h-9h T2, T3 hàng tuần).</p>
                    </div>

                    <!-- Thời gian Cố định (Một lần) -->
                    <div id="fixed_time_wrapper" class="space-y-4 {{ old('is_recurring', $promotion->is_recurring) ? 'hidden' : '' }}">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ngày bắt đầu</label>
                            <input type="text" name="start_at" id="start_at"
                                value="{{ old('start_at', $promotion->start_at ? \Carbon\Carbon::parse($promotion->start_at)->format('Y-m-d H:i:s') : '') }}"
                                placeholder="Chọn ngày & giờ bắt đầu"
                                class="promotion-date-picker w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Ngày kết thúc</label>
                            <input type="text" name="end_at" id="end_at"
                                value="{{ old('end_at', $promotion->end_at ? \Carbon\Carbon::parse($promotion->end_at)->format('Y-m-d H:i:s') : '') }}"
                                placeholder="Chọn ngày & giờ kết thúc"
                                class="promotion-date-picker w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm bg-white cursor-pointer">
                            <p class="text-xs text-gray-400 mt-1">Để trống = không có ngày hết hạn.</p>
                        </div>
                    </div>

                    <!-- Thời gian Lặp lại -->
                    <div id="recurring_time_wrapper" class="space-y-4 {{ old('is_recurring', $promotion->is_recurring) ? '' : 'hidden' }}">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Lặp lại vào các ngày</label>
                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                @php 
                                    $days = [1=>'T2', 2=>'T3', 3=>'T4', 4=>'T5', 5=>'T6', 6=>'T7', 7=>'CN']; 
                                    $selectedDays = old('recurring_days', is_array($promotion->recurring_days) ? $promotion->recurring_days : []);
                                @endphp
                                @foreach($days as $val => $label)
                                <label class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" name="recurring_days[]" value="{{ $val }}" 
                                        {{ in_array($val, $selectedDays) ? 'checked' : '' }}
                                        class="w-3.5 h-3.5 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                    <span class="text-xs text-gray-700">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Từ giờ</label>
                                <input type="time" name="recurring_start_time" value="{{ old('recurring_start_time', $promotion->recurring_start_time ? \Carbon\Carbon::parse($promotion->recurring_start_time)->format('H:i') : '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Đến giờ</label>
                                <input type="time" name="recurring_end_time" value="{{ old('recurring_end_time', $promotion->recurring_end_time ? \Carbon\Carbon::parse($promotion->recurring_end_time)->format('H:i') : '') }}"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Trạng thái -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-4 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-500 text-[20px]" style="font-variation-settings: 'FILL' 1;">toggle_on</span>
                        Trạng thái
                    </h3>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <div class="relative">
                            <input type="checkbox" name="is_active" value="1"
                                id="toggle-active"
                                {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                            <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Kích hoạt</p>
                            <p class="text-xs text-gray-400">Khuyến mãi có thể được áp dụng</p>
                        </div>
                    </label>
                </div>

                <!-- Nút Lưu -->
                <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row gap-3 mt-4">
                    <button type="submit"
                        class="w-full sm:flex-1 px-6 py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 organic-shadow transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Cập nhật
                    </button>
                    <a href="{{ route('admin.promotions.index') }}"
                        onclick="if(document.referrer.includes(window.location.host)) { event.preventDefault(); window.history.back(); }"
                        class="w-full sm:flex-1 px-6 py-3 text-gray-600 font-semibold rounded-xl hover:bg-gray-100 transition-colors text-center border border-gray-200 flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">cancel</span>
                        Hủy
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/backend/promotions/form-common.js') }}"></script>
<script src="{{ asset('js/backend/promotions/edit.js') }}"></script>
@endpush
