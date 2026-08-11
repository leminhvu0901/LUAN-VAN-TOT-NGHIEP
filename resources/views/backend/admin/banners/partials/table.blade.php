<div class="flex-1 flex flex-col min-h-0 w-full relative">
    @if($banners->count() > 0)
        @php
            $now = now();
        @endphp

        <!-- 1. GIAO DIỆN DESKTOP (Bảng) - Hiển thị từ màn hình lg (>= 1024px) -->
        <div class="hidden lg:block w-full overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-gray-100 bg-gray-50/70 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="py-3 px-4 w-12 text-center">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer js-select-all">
                                <div
                                    class="w-5 h-5 bg-white border border-gray-300 rounded-md peer-checked:bg-emerald-600 peer-checked:border-emerald-600 after:content-[''] after:absolute after:hidden peer-checked:after:block after:left-[7px] after:top-[3px] after:w-1.5 after:h-3 after:border-white after:border-r-2 after:border-b-2 after:rotate-45">
                                </div>
                            </label>
                        </th>
                        <th class="py-3 px-4">Ảnh</th>
                        <th class="py-3 px-4">Tiêu đề / Nhãn</th>
                        <th class="py-3 px-4 text-center w-20">Thứ tự</th>
                        <th class="py-3 px-4 text-center">Trạng thái</th>
                        <th class="py-3 px-4">Thời gian áp dụng</th>
                        <th class="py-3 px-4 text-center w-28">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100/60 bg-white text-sm text-gray-700">
                    @foreach($banners as $banner)
                        @php
                            $statusText = '';
                            $statusClass = '';

                            if (!$banner->is_active) {
                                $statusText = 'Đang ẩn';
                                $statusClass = 'bg-gray-100 text-gray-700 border-gray-200';
                            } elseif ($banner->start_at && $banner->start_at > $now) {
                                $statusText = 'Sắp diễn ra';
                                $statusClass = 'bg-blue-50 text-blue-700 border-blue-100';
                            } elseif ($banner->end_at && $banner->end_at < $now) {
                                $statusText = 'Đã hết hạn';
                                $statusClass = 'bg-red-50 text-red-700 border-red-100';
                            } else {
                                $statusText = 'Đang hiển thị';
                                $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                            }

                            // Dùng upload_url() dùng chung: xử lý đúng cả ảnh cũ ("banners/x.jpg") lẫn ảnh
                            // mới tải lên ("uploads/banners/x.jpg"), khỏi tự ghép đường dẫn rồi ghép sai.
                            $fullImageUrl = upload_url($banner->image_url);
                        @endphp
                        <tr id="banner-row-{{ $banner->id }}" class="hover:bg-gray-50/40 transition-colors select-row-tr" data-id="{{ $banner->id }}">
                            <!-- Checkbox -->
                            <td class="py-3 px-4 text-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="banner_ids[]" value="{{ $banner->id }}"
                                        class="sr-only peer row-checkbox">
                                    <div
                                        class="w-5 h-5 bg-white border border-gray-300 rounded-md peer-checked:bg-emerald-600 peer-checked:border-emerald-600 after:content-[''] after:absolute after:hidden peer-checked:after:block after:left-[7px] after:top-[3px] after:w-1.5 after:h-3 after:border-white after:border-r-2 after:border-b-2 after:rotate-45">
                                    </div>
                                </label>
                            </td>

                            <!-- Thumbnail -->
                            <td class="py-3 px-4">
                                <div
                                    class="w-20 h-11 rounded-lg overflow-hidden border border-gray-100 shadow-sm shrink-0 bg-gray-50">
                                    <img src="{{ $fullImageUrl }}" alt="Banner image" class="w-full h-full object-cover">
                                </div>
                            </td>

                            <!-- Tiêu đề / Nhãn -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-gray-900 truncate max-w-[200px]"
                                    title="{{ $banner->title ?? 'Không có tiêu đề' }}">
                                    {{ $banner->title ?? '---' }}
                                </div>
                                @if($banner->title_tag)
                                    <span
                                        class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-md">
                                        {{ $banner->title_tag }}
                                    </span>
                                @endif
                            </td>

                            <!-- Thứ tự -->
                            <td class="py-3 px-4 text-center font-semibold text-gray-800">
                                {{ $banner->display_order }}
                            </td>

                            <!-- Trạng thái -->
                            <td class="py-3 px-4 text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusClass }}">
                                    {{ $statusText }}
                                </span>
                            </td>

                            <!-- Thời gian -->
                            <td class="py-3 px-4 text-xs space-y-0.5 text-gray-500">
                                <div class="flex items-center gap-1">
                                    <span class="font-medium text-gray-400 w-8">Bắt đầu:</span>
                                    <span
                                        class="font-semibold">{{ $banner->start_at ? date('d/m/Y H:i', strtotime($banner->start_at)) : 'Ngay lập tức' }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="font-medium text-gray-400 w-8">Hết hạn:</span>
                                    <span
                                        class="font-semibold text-red-500">{{ $banner->end_at ? date('d/m/Y H:i', strtotime($banner->end_at)) : 'Vĩnh viễn' }}</span>
                                </div>
                            </td>

                            <!-- Thao tác -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex justify-center items-center gap-1.5">
                                    <!-- Bật/Tắt Trạng Thái -->
                                    <form method="POST" action="{{ route('admin.banners.toggle_status', $banner->id) }}">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors" title="Bật/Tắt hiển thị">
                                            <span class="material-symbols-outlined text-[16px] xl:text-[18px]">
                                                {{ $banner->is_active ? 'visibility' : 'visibility_off' }}
                                            </span>
                                        </button>
                                    </form>

                                    <!-- Chỉnh sửa -->
                                    <a href="{{ route('admin.banners.edit', $banner->id) }}"
                                        class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors"
                                        title="Sửa">
                                        <span class="material-symbols-outlined text-[16px] xl:text-[18px]">edit</span>
                                    </a>

                                    <!-- Xóa -->
                                    <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}"
                                        onsubmit="return confirm('Xóa banner này? Hành động này không thể hoàn tác.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors" title="Xóa">
                                            <span class="material-symbols-outlined text-[16px] xl:text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- 2. GIAO DIỆN MOBILE & TABLET (Cards) - Hiển thị trên màn hình < lg (< 1024px) -->
        <div class="lg:hidden flex flex-col gap-3 p-4">
            <!-- Checkbox chọn tất cả trên Mobile -->
            <div
                class="flex items-center justify-between bg-gray-50 p-3 rounded-xl border border-gray-200/60 shadow-sm mb-1">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Chọn tất cả</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer js-select-all">
                    <div
                        class="w-5 h-5 bg-white border border-gray-300 rounded-md peer-checked:bg-emerald-600 peer-checked:border-emerald-600 after:content-[''] after:absolute after:hidden peer-checked:after:block after:left-[7px] after:top-[3px] after:w-1.5 after:h-3 after:border-white after:border-r-2 after:border-b-2 after:rotate-45">
                    </div>
                </label>
            </div>

            @foreach($banners as $banner)
                @php
                    $statusText = '';
                    $statusClass = '';

                    if (!$banner->is_active) {
                        $statusText = 'Đang ẩn';
                        $statusClass = 'bg-gray-100 text-gray-700 border-gray-200';
                    } elseif ($banner->start_at && $banner->start_at > $now) {
                        $statusText = 'Sắp diễn ra';
                        $statusClass = 'bg-blue-50 text-blue-700 border-blue-100';
                    } elseif ($banner->end_at && $banner->end_at < $now) {
                        $statusText = 'Đã hết hạn';
                        $statusClass = 'bg-red-50 text-red-700 border-red-100';
                    } else {
                        $statusText = 'Đang hiển thị';
                        $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-100';
                    }

                    // Dùng upload_url() dùng chung: xử lý đúng cả ảnh cũ ("banners/x.jpg") lẫn ảnh
                    // mới tải lên ("uploads/banners/x.jpg"), khỏi tự ghép đường dẫn rồi ghép sai.
                    $fullImageUrl = upload_url($banner->image_url);
                @endphp
                {{-- Không đặt overflow-wrap/word-break ở thẻ card này: nó KẾ THỪA xuống 3 nút thao tác
                     bên dưới, làm min-content của nút bằng 0 nên nút flex-1 co lại và chữ bị bẻ giữa từ
                     ("Trạ/ng thá/i"). Chỉ cho phép bẻ từ ở đúng chỗ chữ do người dùng nhập (tiêu đề/nhãn). --}}
                <div id="banner-row-{{ $banner->id }}" class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col gap-3 relative select-row-tr"
                    data-id="{{ $banner->id }}">
                    <!-- Header Card: Checkbox và Trạng thái -->
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="banner_ids[]" value="{{ $banner->id }}"
                                class="sr-only peer row-checkbox">
                            <div
                                class="w-5 h-5 bg-white border border-gray-300 rounded-md peer-checked:bg-emerald-600 peer-checked:border-emerald-600 after:content-[''] after:absolute after:hidden peer-checked:after:block after:left-[7px] after:top-[3px] after:w-1.5 after:h-3 after:border-white after:border-r-2 after:border-b-2 after:rotate-45">
                            </div>
                        </label>

                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border {{ $statusClass }}">
                            {{ $statusText }}
                        </span>
                    </div>

                    <!-- Ảnh banner trên Mobile: Rộng 100%, tỷ lệ 16:9 -->
                    <div
                        class="w-full aspect-[16/9] rounded-xl overflow-hidden border border-gray-100 shadow-inner bg-gray-50 shrink-0">
                        <img src="{{ $fullImageUrl }}" alt="Banner image" class="w-full h-full object-cover">
                    </div>

                    <!-- Nội dung chữ -->
                    <div class="space-y-1.5">
                        <div class="flex items-start gap-2">
                            <span class="text-xs font-semibold text-gray-400 mt-0.5 uppercase tracking-wide shrink-0">Tiêu
                                đề:</span>
                            <h4 class="font-bold text-gray-900 text-sm leading-snug min-w-0 break-words">
                                {{ $banner->title ?? '---' }}
                            </h4>
                        </div>

                        @if($banner->title_tag)
                            <div class="flex items-start gap-2">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide shrink-0">Nhãn:</span>
                                <span
                                    class="px-2 py-0.5 text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-md min-w-0 break-words">
                                    {{ $banner->title_tag }}
                                </span>
                            </div>
                        @endif

                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide shrink-0">Thứ tự:</span>
                            <span class="font-bold text-gray-800 text-xs">{{ $banner->display_order }}</span>
                        </div>

                        <!-- Thời gian áp dụng -->
                        <div class="pt-2 border-t border-gray-50 space-y-1 text-xs text-gray-500">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-gray-400">Bắt đầu:</span>
                                <span
                                    class="font-semibold text-gray-700">{{ $banner->start_at ? date('d/m/Y H:i', strtotime($banner->start_at)) : 'Ngay lập tức' }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-gray-400">Hết hạn:</span>
                                <span
                                    class="font-semibold text-red-500">{{ $banner->end_at ? date('d/m/Y H:i', strtotime($banner->end_at)) : 'Vĩnh viễn' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Card: 3 nút chia đều. Màn hẹp xếp icon TRÊN chữ (flex-col) để nhãn đủ chỗ
                         nằm 1 dòng, từ sm trở lên mới xếp ngang - cùng cách đã dùng cho nút chọn cách
                         nhập địa chỉ ở trang thanh toán. whitespace-nowrap chặn hẳn việc bẻ giữa từ. --}}
                    <div class="flex items-stretch gap-2 pt-2 border-t border-gray-100 mt-1">
                        <!-- Bật/Tắt nhanh -->
                        <form method="POST" action="{{ route('admin.banners.toggle_status', $banner->id) }}" class="flex-1 min-w-0">
                            @csrf
                            <button type="submit"
                                class="w-full min-h-[44px] flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-2 sm:px-3 py-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-xl text-[11px] sm:text-xs font-bold leading-tight transition-all border border-emerald-100">
                                <span class="material-symbols-outlined text-[18px] shrink-0">
                                    {{ $banner->is_active ? 'visibility' : 'visibility_off' }}
                                </span>
                                <span class="whitespace-nowrap">Trạng thái</span>
                            </button>
                        </form>

                        <!-- Sửa -->
                        <a href="{{ route('admin.banners.edit', $banner->id) }}"
                            class="flex-1 min-w-0 min-h-[44px] flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-2 sm:px-3 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-xl text-[11px] sm:text-xs font-bold leading-tight transition-all border border-blue-100">
                            <span class="material-symbols-outlined text-[18px] shrink-0">edit</span>
                            <span class="whitespace-nowrap">Chỉnh sửa</span>
                        </a>

                        <!-- Xóa -->
                        <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}" class="flex-1 min-w-0"
                            onsubmit="return confirm('Xóa banner này? Hành động này không thể hoàn tác.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full min-h-[44px] flex flex-col sm:flex-row items-center justify-center gap-0.5 sm:gap-1.5 px-2 sm:px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 rounded-xl text-[11px] sm:text-xs font-bold leading-tight transition-all border border-red-100">
                                <span class="material-symbols-outlined text-[18px] shrink-0">delete</span>
                                <span class="whitespace-nowrap">Xóa</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

    @else
        <!-- Khi không có dữ liệu -->
        <div class="p-12 text-center bg-white rounded-b-2xl h-full flex flex-col justify-center">
            <div class="flex flex-col items-center gap-3 text-gray-400">
                <div
                    class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 shadow-inner">
                    <span class="material-symbols-outlined text-4xl text-gray-300">view_carousel</span>
                </div>
                <span class="font-medium text-gray-600 text-base">Không tìm thấy banner nào.</span>
                <p class="text-sm">Hãy thử thay đổi điều kiện lọc hoặc từ khóa tìm kiếm.</p>
            </div>
        </div>
    @endif
</div>

<!-- Phân trang bằng AJAX -->
@if($banners->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-2xl ajax-pagination mt-auto">
        {{ $banners->links('pagination::tailwind') }}
    </div>
@endif

<input type="hidden" id="total-banners-count" value="{{ $banners->total() }}">