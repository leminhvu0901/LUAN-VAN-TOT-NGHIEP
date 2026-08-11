@extends('backend.layouts.app')

@section('title', 'Chỉnh sửa Banner')

@section('content')
<div class="banners-page p-4 sm:p-6 space-y-6 max-w-4xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.banners.index') }}"
            onclick="smartGoBack(event)"
            class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-gray-500 hover:bg-gray-50 organic-shadow transition-colors">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Chỉnh sửa banner</h2>
            <p class="text-gray-500 text-sm mt-1">Cập nhật thông tin chi tiết của banner.</p>
        </div>
    </div>

    @if($errors->any())
        @push('scripts')
        <script>
            window.flashErrorMessages = {!! json_encode($errors->all()) !!};
            window.flashErrorTitle = 'Thông báo';
        </script>
        @endpush
    @endif

    <form action="{{ route('admin.banners.update', $banner->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl organic-shadow p-6 sm:p-8 border border-gray-100">
        @csrf
        @method('PUT')
        
        <div class="space-y-6">
            <!-- Grid 2 cột trên Desktop, 1 cột trên Mobile -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tiêu đề banner -->
                <div>
                    <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">
                        Tiêu đề banner
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title', $banner->title) }}" maxlength="100"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        placeholder="VD: Chào hè rực rỡ - Đồng giá 29K">
                </div>

                <!-- Nhãn banner (title_tag) -->
                <div>
                    <label for="title_tag" class="block text-sm font-semibold text-gray-700 mb-2">
                        Nhãn phụ (Tag)
                    </label>
                    <input type="text" name="title_tag" id="title_tag" value="{{ old('title_tag', $banner->title_tag) }}" maxlength="50"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        placeholder="VD: Khuyến mãi đặc biệt, Sản phẩm mới...">
                </div>
            </div>

            <!-- Upload ảnh banner (Có preview và tỷ lệ banner) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Ảnh banner
                </label>
                <div class="space-y-4">
                    @php
                        // Dùng upload_url() dùng chung: nó xử lý đúng cả ảnh cũ ("banners/x.jpg")
                        // lẫn ảnh mới tải lên ("uploads/banners/x.jpg"), khỏi tự ghép đường dẫn.
                        $fullImageUrl = upload_url($banner->image_url);
                    @endphp

                    <!-- Preview Container -->
                    <div id="image-preview-container" class="{{ $banner->image_url ? '' : 'hidden' }} w-full aspect-[2.3/1] sm:aspect-[4.9/1] rounded-2xl overflow-hidden border-2 border-dashed border-gray-200 relative group bg-gray-50">
                        <img id="image-preview" src="{{ $banner->image_url ? $fullImageUrl : '#' }}" alt="Preview" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <button type="button" onclick="triggerFileInput();" class="px-4 py-2 bg-white text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-100 transition-all flex items-center gap-1.5 shadow">
                                <span class="material-symbols-outlined text-[18px]">cached</span> Thay đổi
                            </button>
                        </div>
                    </div>

                    <!-- Upload Input Box -->
                    <div id="upload-placeholder" onclick="triggerFileInput();" class="{{ $banner->image_url ? 'hidden' : '' }} w-full h-32 border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center gap-2 hover:border-emerald-500 hover:bg-emerald-50/10 cursor-pointer transition-all">
                        <span class="material-symbols-outlined text-4xl text-gray-400">cloud_upload</span>
                        <span class="text-sm font-semibold text-gray-600">Nhấp để tải lên ảnh banner</span>
                        <span class="text-xs text-gray-400">Định dạng JPEG, PNG, WEBP tối đa 10MB (Tỷ lệ gợi ý 5.7:1)</span>
                    </div>

                    <input type="file" name="image" id="image-input" class="hidden" accept="image/*" onchange="previewSelectedImage(this);">
                </div>
            </div>



            <!-- Grid 3 cột trên Desktop cho Thứ tự, Start_at, End_at -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Thứ tự hiển thị -->
                <div>
                    <label for="display_order" class="block text-sm font-semibold text-gray-700 mb-2">
                        Thứ tự hiển thị
                    </label>
                    <input type="number" name="display_order" id="display_order" value="{{ old('display_order', $banner->display_order) }}" min="0"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                        placeholder="VD: 1, 2, 3...">
                </div>

                <!-- Ngày bắt đầu -->
                <div>
                    <label for="start_at" class="block text-sm font-semibold text-gray-700 mb-2">
                        Ngày bắt đầu áp dụng
                    </label>
                    <div class="relative">
                        <input type="text" name="start_at" id="start_at" value="{{ old('start_at', $banner->start_at) }}"
                            class="banner-date-picker w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white"
                            placeholder="Chọn ngày giờ bắt đầu">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">calendar_month</span>
                    </div>
                </div>

                <!-- Ngày kết thúc -->
                <div>
                    <label for="end_at" class="block text-sm font-semibold text-gray-700 mb-2">
                        Ngày kết thúc áp dụng
                    </label>
                    <div class="relative">
                        <input type="text" name="end_at" id="end_at" value="{{ old('end_at', $banner->end_at) }}"
                            class="banner-date-picker w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors bg-white"
                            placeholder="Chọn ngày giờ kết thúc">
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">calendar_month</span>
                    </div>
                </div>
            </div>

            <!-- Trạng thái hiển thị (is_active) -->
            <div class="flex items-center gap-3 pt-2">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" class="sr-only peer" {{ $banner->is_active ? 'checked' : '' }} value="1">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
                <span class="text-sm font-semibold text-gray-700">Kích hoạt hiển thị banner này</span>
            </div>

        </div>

        <!-- Hủy và Lưu (Xếp dọc trên mobile, ngang trên desktop) -->
        <div class="mt-8 flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100">
            <a href="{{ route('admin.banners.index') }}" 
                onclick="smartGoBack(event)"
                class="w-full sm:w-auto text-center px-6 py-2.5 rounded-xl border border-gray-200 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                Hủy bỏ
            </a>
            <button type="submit" 
                class="w-full sm:w-auto text-center px-6 py-2.5 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors organic-shadow">
                Cập nhật banner
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// =========================================================================
// XỬ LÝ CHỌN ẢNH & HIỂN THỊ XEM TRƯỚC CHO FORM BANNER (EDIT)
// =========================================================================

// Bấm vào khung ảnh thì mở hộp thoại chọn file (thẻ input file gốc bị ẩn cho đẹp)
function triggerFileInput() {
    document.getElementById('image-input').click();
}

// Hiện ảnh xem trước ngay sau khi chọn file, chưa cần tải lên máy chủ
function previewSelectedImage(input) {
    if (input.files && input.files[0]) {
        if (input.files[0].size > 10 * 1024 * 1024) {
            if (window.AdminAlert) {
                window.AdminAlert.error('Dung lượng tệp ảnh không được vượt quá 10MB.', 'Ảnh quá lớn');
            } else {
                alert('Dung lượng tệp ảnh không được vượt quá 10MB.');
            }
            input.value = '';
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').setAttribute('src', e.target.result);
            document.getElementById('image-preview-container').classList.remove('hidden');
            document.getElementById('upload-placeholder').classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".banner-date-picker", {
            locale: "vn",
            dateFormat: "Y-m-d H:i:S",
            enableTime: true,
            time_24hr: true,
            disableMobile: true,
            monthSelectorType: "static"
        });
    }
});
</script>
@endpush

