@extends('backend.layouts.app')

@section('title', 'Chỉnh sửa Khuyến mãi')

@section('content')
<div class="promotions-page">
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-4">
        <a href="{{ route('admin.promotions.index') }}"
            onclick="smartGoBack(event)"
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
                        <span class="material-symbols-outlined text-emerald-600 text-[20px] icon-fill">local_offer</span>
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

                        <!-- Loại khuyến mãi + Giá trị giảm: CHỈ có ý nghĩa -->
                        <div id="money-discount-fields" class="{{ old('scope', $promotion->scope) === 'combo' ? 'hidden' : '' }}">
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
                            <!-- Giảm tối đa -->
                            <div id="max-discount-wrap" {{ old('type', $promotion->type) == 'fixed' ? 'style=display:none' : '' }}>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Giảm tối đa (VNĐ)</label>
                                <input type="text" id="display-max-discount"
                                    value="{{ old('max_discount_amount', $promotion->max_discount_amount) ? number_format(old('max_discount_amount', $promotion->max_discount_amount), 0, ',', '.') : '' }}"
                                    placeholder="Không giới hạn"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                                <input type="hidden" name="max_discount_amount" id="promo-max-discount" value="{{ old('max_discount_amount', $promotion->max_discount_amount) }}">
                            </div>
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

                <!-- Card: Phạm vi áp dụng -->
                @include('backend.admin.promotions.partials.scope-fields', [
                    'products' => $products,
                    'categories' => $categories,
                    'selectedScope' => old('scope', $promotion->scope),
                    'selectedProductIds' => old('product_ids', $promotion->products->pluck('id')->all()),
                    'selectedCategoryIds' => old('category_ids', $promotion->categories->pluck('id')->all()),
                    'combo' => $promotion->combo,
                    'comboItems' => $promotion->comboItems,
                ])

                <!-- Card: Điều kiện áp dụng -->
                <div class="bg-white rounded-2xl organic-shadow border border-gray-100 p-6">
                    <h3 class="text-base font-bold text-gray-800 mb-5 pb-3 border-b border-gray-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-500 text-[20px] icon-fill">rule</span>
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
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Số lượng món tối thiểu</label>
                            <input type="number" name="min_quantity"
                                value="{{ old('min_quantity', $promotion->min_quantity) }}"
                                placeholder="Không giới hạn"
                                min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                            <p class="text-xs text-gray-400 mt-1">Vd: 2 = khách phải mua từ 2 món trở lên mới dùng được mã. Để trống = không giới hạn.</p>
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
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Kênh áp dụng</label>
                            <select name="applies_to" class="custom-select-init w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm appearance-none bg-white" data-width-class="w-full">
                                <option value="all" {{ old('applies_to', $promotion->applies_to) == 'all' ? 'selected' : '' }}>Tất cả (tại quầy + giao hàng)</option>
                                <option value="pickup" {{ old('applies_to', $promotion->applies_to) == 'pickup' ? 'selected' : '' }}>Chỉ đơn tại quầy</option>
                                <option value="delivery" {{ old('applies_to', $promotion->applies_to) == 'delivery' ? 'selected' : '' }}>Chỉ đơn giao hàng</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Giới hạn số lượt dùng (toàn hệ thống)</label>
                            <input type="number" name="usage_limit"
                                value="{{ old('usage_limit', $promotion->usage_limit) }}"
                                placeholder="Không giới hạn"
                                min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Giới hạn lượt dùng / 1 tài khoản</label>
                            <input type="number" name="usage_limit_per_user"
                                value="{{ old('usage_limit_per_user', $promotion->usage_limit_per_user) }}"
                                placeholder="Không giới hạn"
                                min="1"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all text-sm">
                            <p class="text-xs text-gray-400 mt-1">Mặc định 1 = mỗi tài khoản chỉ dùng được 1 lần. Để trống = không giới hạn.</p>
                        </div>
                    </div>

                    {{-- Toggle: Yêu cầu nhân viên xác nhận --}}
                    <div class="mt-5 pt-4 border-t border-gray-100">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <div class="relative mt-0.5 flex-shrink-0">
                                <input type="checkbox" name="requires_staff_verification" id="requires_staff_verification"
                                    value="1"
                                    {{ old('requires_staff_verification', $promotion->requires_staff_verification) ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-rose-500 transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-5"></div>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-700 group-hover:text-rose-600 transition-colors">Yêu cầu nhân viên xác nhận trước khi áp dụng</p>
                                <p class="text-xs text-gray-400 mt-0.5">Dùng cho mã <strong>Sinh viên</strong>, <strong>Sinh nhật</strong>... — mã sẽ <strong class="text-rose-600">không tự động áp</strong>. Chỉ lễ tân mới nhập tay sau khi khách đã báo và được xác nhận.</p>
                            </div>
                        </label>
                    </div>

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
                        <span class="material-symbols-outlined text-blue-500 text-[20px] icon-fill">calendar_month</span>
                        Thời gian áp dụng
                    </h3>
                    
                    <div class="mb-4 pb-4 border-b border-gray-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_recurring" id="is_recurring_cb" value="1" {{ old('is_recurring', $promotion->is_recurring) ? 'checked' : '' }} class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                            <span class="text-sm font-semibold text-gray-700">Khuyến mãi lặp lại định kỳ</span>
                        </label>
                        <p class="text-[11px] text-gray-500 mt-1 ml-6">Sử dụng cho Giờ Vàng (vd: 7h-9h T2, T3 hàng tuần).</p>
                    </div>

                    <!-- Thời gian Cố định -->
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
                        <span class="material-symbols-outlined text-emerald-500 text-[20px] icon-fill">toggle_on</span>
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
                        onclick="smartGoBack(event)"
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typePercentLabel = document.getElementById('type-percent-label');
    const typeFixedLabel = document.getElementById('type-fixed-label');
    const maxDiscountWrap = document.getElementById('max-discount-wrap');
    const valueUnitLabel = document.getElementById('value-unit');

    const displayValue = document.getElementById('display-value');
    const displayMaxDiscount = document.getElementById('display-max-discount');
    const displayMinOrder = document.getElementById('display-min-order');

    const hiddenValue = document.getElementById('promo-value');
    const hiddenMaxDiscount = document.getElementById('promo-max-discount');
    const hiddenMinOrder = document.getElementById('promo-min-order');

    const btnGenCode = document.getElementById('btn-gen-code');
    const promoCodeInput = document.getElementById('promo-code');
    const descriptionInput = document.getElementById('promo-description');
    const descCount = document.getElementById('desc-count');

    // Định dạng ô nhập tiền theo kiểu Việt Nam
    function bindVNDInput(displayEl, hiddenEl) {
        if (!displayEl || !hiddenEl) return;

        displayEl.addEventListener('input', function () {
            let raw = this.value.replace(/[^\d]/g, '');
            const num = parseInt(raw, 10);
            hiddenEl.value = isNaN(num) ? '' : num;

            const pos = this.selectionStart;
            const oldLen = this.value.length;
            this.value = raw ? parseInt(raw).toLocaleString('vi-VN') : '';
            const newLen = this.value.length;
            this.setSelectionRange(pos + (newLen - oldLen), pos + (newLen - oldLen));
        });

        displayEl.addEventListener('focus', function () {
            if (hiddenEl.value) {
                this.value = hiddenEl.value;
            }
            this.select();
        });

        displayEl.addEventListener('blur', function () {
            if (hiddenEl.value) {
                this.value = parseInt(hiddenEl.value).toLocaleString('vi-VN');
            } else {
                this.value = '';
            }
        });
    }

    bindVNDInput(displayMaxDiscount, hiddenMaxDiscount);
    bindVNDInput(displayMinOrder, hiddenMinOrder);

    // Đọc kiểu giảm giá đang được chọn
    function getCurrentType() {
        const checked = document.querySelector('input[name="type"]:checked');
        return checked ? checked.value : 'percent';
    }

    // Đổi giao diện form theo kiểu giảm giá đang chọn
    function updateTypeUI(selectedType) {
        const isPercent = (selectedType === 'percent');

        if (typePercentLabel) {
            typePercentLabel.classList.toggle('border-emerald-500', isPercent);
            typePercentLabel.classList.toggle('bg-emerald-50', isPercent);
            typePercentLabel.classList.toggle('border-gray-200', !isPercent);
            typePercentLabel.classList.toggle('bg-white', !isPercent);
        }
        if (typeFixedLabel) {
            typeFixedLabel.classList.toggle('border-emerald-500', !isPercent);
            typeFixedLabel.classList.toggle('bg-emerald-50', !isPercent);
            typeFixedLabel.classList.toggle('border-gray-200', isPercent);
            typeFixedLabel.classList.toggle('bg-white', isPercent);
        }

        if (maxDiscountWrap) {
            maxDiscountWrap.style.display = isPercent ? '' : 'none';
        }

        if (valueUnitLabel) {
            valueUnitLabel.textContent = isPercent ? '(% tỷ lệ)' : '(VNĐ cố định)';
        }

        if (displayValue && hiddenValue) {
            const raw = hiddenValue.value;
            if (isPercent) {
                displayValue.value = raw || '';
                displayValue.placeholder = 'VD: 20';
                displayValue.onblur = null;
                displayValue.onfocus = null;
                displayValue.oninput = function () {
                    let v = this.value.replace(/[^\d.]/g, '');
                    hiddenValue.value = v;
                    this.value = v;
                };
            } else {
                displayValue.value = raw ? parseInt(raw).toLocaleString('vi-VN') : '';
                displayValue.placeholder = 'VD: 50.000';
                bindVNDInput(displayValue, hiddenValue);
            }
        }
    }

    const initialType = getCurrentType();
    updateTypeUI(initialType);

    if (initialType === 'percent' && displayValue && hiddenValue) {
        displayValue.oninput = function () {
            let v = this.value.replace(/[^\d.]/g, '');
            hiddenValue.value = v;
            this.value = v;
        };
    }

    if (typePercentLabel) {
        typePercentLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateTypeUI('percent');
        });
    }
    if (typeFixedLabel) {
        typeFixedLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateTypeUI('fixed');
        });
    }

    const scopeOptions = document.querySelectorAll('.scope-option');
    const scopeProductFields = document.getElementById('scope-product-fields');
    const scopeCategoryFields = document.getElementById('scope-category-fields');
    const scopeComboFields = document.getElementById('scope-combo-fields');
    const moneyDiscountFields = document.getElementById('money-discount-fields');

    // Hiện/ẩn các khu vực của form theo phạm vi áp dụng
    function updateScopeUI(selectedScope) {
        scopeOptions.forEach(function (option) {
            const isActive = option.dataset.scope === selectedScope;
            option.classList.toggle('border-emerald-500', isActive);
            option.classList.toggle('bg-emerald-50', isActive);
            option.classList.toggle('border-gray-200', !isActive);
            option.classList.toggle('bg-white', !isActive);
        });

        if (scopeProductFields) scopeProductFields.classList.toggle('hidden', selectedScope !== 'product');
        if (scopeCategoryFields) scopeCategoryFields.classList.toggle('hidden', selectedScope !== 'category');
        if (scopeComboFields) scopeComboFields.classList.toggle('hidden', selectedScope !== 'combo');
        if (moneyDiscountFields) moneyDiscountFields.classList.toggle('hidden', selectedScope === 'combo');
    }

    // Đọc phạm vi áp dụng đang được chọn
    function getCurrentScope() {
        const checked = document.querySelector('input[name="scope"]:checked');
        return checked ? checked.value : 'order';
    }

    if (scopeOptions.length > 0) {
        scopeOptions.forEach(function (option) {
            option.addEventListener('click', function () {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
                updateScopeUI(this.dataset.scope);
            });
        });
        updateScopeUI(getCurrentScope());
    }

    const productSearch = document.getElementById('product-search');
    if (productSearch) {
        productSearch.addEventListener('input', function () {
            const keyword = this.value.trim().toLowerCase();
            document.querySelectorAll('.product-option').forEach(function (row) {
                const matched = !keyword || (row.dataset.name || '').includes(keyword);
                row.classList.toggle('hidden', !matched);
            });
        });
    }

    if (btnGenCode && promoCodeInput) {
        btnGenCode.addEventListener('click', function () {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let suffix = '';
            for (let i = 0; i < 6; i++) {
                suffix += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            const code = 'KM' + suffix;
            promoCodeInput.value = code;

            promoCodeInput.style.borderColor = '#10b981';
            promoCodeInput.style.boxShadow = '0 0 0 3px rgba(16,185,129,0.2)';
            setTimeout(() => {
                promoCodeInput.style.borderColor = '';
                promoCodeInput.style.boxShadow = '';
            }, 1500);
        });
    }

    if (descriptionInput && descCount) {
        // Đếm số ký tự còn lại cho ô mô tả
        const updateCount = () => {
            const len = descriptionInput.value.length;
            descCount.textContent = len + '/100';
            descCount.style.color = len >= 90 ? '#ef4444' : (len >= 75 ? '#f59e0b' : '');
        };
        descriptionInput.addEventListener('input', updateCount);
        updateCount();
    }

    const form = document.getElementById('promotion-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (getCurrentScope() === 'combo') {
                if (!validateComboBeforeSubmit()) {
                    e.preventDefault();
                }
                return;
            }

            const selectedType = getCurrentType();
            const rawValue = hiddenValue ? parseFloat(hiddenValue.value) : NaN;

            if (isNaN(rawValue) || rawValue <= 0) {
                e.preventDefault();
                if (displayValue) {
                    displayValue.focus();
                    displayValue.style.borderColor = '#ef4444';
                    setTimeout(() => displayValue.style.borderColor = '', 2000);
                }
                alert('Giá trị giảm phải lớn hơn 0.');
                return;
            }

            if (selectedType === 'percent' && rawValue > 100) {
                e.preventDefault();
                if (displayValue) {
                    displayValue.focus();
                    displayValue.style.borderColor = '#ef4444';
                    setTimeout(() => displayValue.style.borderColor = '', 2000);
                }
                alert('Tỷ lệ giảm giá không thể vượt quá 100%.');
                return;
            }
        });
    }

    // Khởi tạo khu vực khai báo tổ hợp món của combo
    function initComboItems() {
        const container = document.getElementById('combo-items');
        const addButton = document.getElementById('add-combo-item');
        if (!container || !addButton) return;

        // Sinh một dòng chọn sản phẩm + số lượng trong tổ hợp combo
        function createComboItemRow() {
            const row = document.createElement('div');
            row.className = 'combo-item-row grid grid-cols-1 sm:grid-cols-[1fr_120px_40px] gap-2';
            const options = Array.from(document.querySelectorAll('#combo-items select')[0]?.options || [])
                .map((opt) => '<option value="' + opt.value + '">' + opt.textContent + '</option>').join('');
            row.innerHTML =
                '<select name="combo_product_ids[]" class="custom-select-init w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" data-width-class="w-full">' + options + '</select>' +
                '<input name="combo_quantities[]" type="number" min="1" value="1" placeholder="SL" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">' +
                '<button type="button" class="js-remove-combo-item w-10 h-10 text-red-500 hover:bg-red-50 rounded-lg" title="Xóa sản phẩm"><span class="material-symbols-outlined">delete</span></button>';
            return row;
        }

        addButton.addEventListener('click', function () {
            if (container.querySelectorAll('.combo-item-row').length < 20) {
                container.appendChild(createComboItemRow());
                if (typeof window.initCustomSelects === 'function') window.initCustomSelects();
            }
        });

        container.addEventListener('click', function (event) {
            const button = event.target.closest('.js-remove-combo-item');
            if (!button) return;
            const rows = container.querySelectorAll('.combo-item-row');
            if (rows.length === 1) {
                rows[0].querySelectorAll('input[type="number"]').forEach((input) => input.value = '1');
            } else {
                button.closest('.combo-item-row').remove();
            }
        });
    }
    initComboItems();

    const comboHasDiscount = document.getElementById('combo_has_discount');
    const comboHasGift = document.getElementById('combo_has_gift');
    const comboDiscountFields = document.getElementById('combo-discount-fields');
    const comboGiftFields = document.getElementById('combo-gift-fields');
    const comboDiscountTypePercentLabel = document.getElementById('combo-discount-type-percent-label');
    const comboDiscountTypeFixedLabel = document.getElementById('combo-discount-type-fixed-label');
    const comboDiscountValueInput = document.getElementById('combo-discount-value');
    const comboDiscountValueUnit = document.getElementById('combo-discount-value-unit');
    const comboMaxDiscountWrap = document.getElementById('combo-max-discount-wrap');

    // Hiện/ẩn khu vực chọn quà tặng kèm của combo
    function updateComboRewardUI() {
        if (comboDiscountFields && comboHasDiscount) comboDiscountFields.classList.toggle('hidden', !comboHasDiscount.checked);
        if (comboGiftFields && comboHasGift) comboGiftFields.classList.toggle('hidden', !comboHasGift.checked);
    }

    // Đọc kiểu giảm giá riêng của combo đang chọn
    function getComboDiscountType() {
        const checked = document.querySelector('input[name="discount_type"]:checked');
        return checked ? checked.value : 'percent';
    }

    // Đổi giao diện theo kiểu giảm giá riêng của combo
    function updateComboDiscountTypeUI(selectedType) {
        const isPercent = selectedType === 'percent';
        if (comboDiscountTypePercentLabel) {
            comboDiscountTypePercentLabel.classList.toggle('border-emerald-500', isPercent);
            comboDiscountTypePercentLabel.classList.toggle('bg-emerald-50', isPercent);
            comboDiscountTypePercentLabel.classList.toggle('border-gray-200', !isPercent);
            comboDiscountTypePercentLabel.classList.toggle('bg-white', !isPercent);
        }
        if (comboDiscountTypeFixedLabel) {
            comboDiscountTypeFixedLabel.classList.toggle('border-emerald-500', !isPercent);
            comboDiscountTypeFixedLabel.classList.toggle('bg-emerald-50', !isPercent);
            comboDiscountTypeFixedLabel.classList.toggle('border-gray-200', isPercent);
            comboDiscountTypeFixedLabel.classList.toggle('bg-white', isPercent);
        }
        if (comboMaxDiscountWrap) comboMaxDiscountWrap.classList.toggle('hidden', !isPercent);
        if (comboDiscountValueUnit) comboDiscountValueUnit.textContent = isPercent ? '(% tỷ lệ)' : '(VNĐ cố định)';
        if (comboDiscountValueInput) {
            comboDiscountValueInput.max = isPercent ? '100' : '';
            comboDiscountValueInput.step = isPercent ? '1' : '1000';
            comboDiscountValueInput.placeholder = isPercent ? 'VD: 15' : 'VD: 10000';
        }
    }

    if (comboHasDiscount) comboHasDiscount.addEventListener('change', updateComboRewardUI);
    if (comboHasGift) comboHasGift.addEventListener('change', updateComboRewardUI);
    updateComboRewardUI();

    if (comboDiscountTypePercentLabel) {
        comboDiscountTypePercentLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateComboDiscountTypeUI('percent');
        });
    }
    if (comboDiscountTypeFixedLabel) {
        comboDiscountTypeFixedLabel.addEventListener('click', function () {
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateComboDiscountTypeUI('fixed');
        });
    }
    updateComboDiscountTypeUI(getComboDiscountType());

    // Kiểm tra combo ngay tại trình duyệt trước khi gửi, chặn sớm cho khỏi mất công tải lại trang
    function validateComboBeforeSubmit() {
        const productSelects = Array.from(document.querySelectorAll('#combo-items select[name="combo_product_ids[]"]'));
        const hasProduct = productSelects.some((sel) => sel.value);
        
        if (!hasProduct) {
            alert('Vui lòng chọn ít nhất 1 sản phẩm cho combo.');
            return false;
        }

        if (!(comboHasDiscount && comboHasDiscount.checked) && !(comboHasGift && comboHasGift.checked)) {
            alert('Combo phải có ít nhất giảm giá hoặc tặng quà.');
            return false;
        }

        if (comboHasDiscount && comboHasDiscount.checked) {
            const rawValue = comboDiscountValueInput ? parseFloat(comboDiscountValueInput.value) : NaN;
            if (isNaN(rawValue) || rawValue <= 0) {
                alert('Giá trị giảm giá combo phải lớn hơn 0.');
                return false;
            }
        }

        if (comboHasGift && comboHasGift.checked) {
            const giftProduct = document.querySelector('#combo-gift-fields select[name="gift_product_id"]');
            const giftQty = document.querySelector('#combo-gift-fields input[name="gift_quantity"]');
            if (!giftProduct || !giftProduct.value) {
                alert('Vui lòng chọn sản phẩm tặng cho combo.');
                return false;
            }
            if (!giftQty || !giftQty.value || parseInt(giftQty.value, 10) <= 0) {
                alert('Vui lòng nhập số lượng tặng hợp lệ cho combo.');
                return false;
            }
        }

        return true;
    }

    const isRecurringCb = document.getElementById('is_recurring_cb');
    const fixedTimeWrapper = document.getElementById('fixed_time_wrapper');
    const recurringTimeWrapper = document.getElementById('recurring_time_wrapper');

    if (isRecurringCb && fixedTimeWrapper && recurringTimeWrapper) {
        isRecurringCb.addEventListener('change', function () {
            if (this.checked) {
                fixedTimeWrapper.classList.add('hidden');
                recurringTimeWrapper.classList.remove('hidden');
            } else {
                fixedTimeWrapper.classList.remove('hidden');
                recurringTimeWrapper.classList.add('hidden');
            }
        });
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr(".promotion-date-picker", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            altInput: true,
            altFormat: "d/m/Y H:i",
            locale: "vn",
            disableMobile: true,
            time_24hr: true
        });
    }
});
</script>
@endpush

