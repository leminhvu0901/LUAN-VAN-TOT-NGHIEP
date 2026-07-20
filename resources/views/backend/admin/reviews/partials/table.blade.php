<!-- Giao diện Mobile (Card view) -->
<div class="block lg:hidden space-y-4 p-4">
    <div class="flex items-center justify-between mb-3 px-1">
        <label class="flex items-center gap-2 text-sm text-gray-700 font-bold cursor-pointer group">
            <input type="checkbox" id="selectAll-mobile" class="js-select-all rounded-md border-gray-300 text-amber-500 focus:ring-amber-400 w-5 h-5 transition-colors group-hover:border-amber-400">
            <span class="group-hover:text-amber-600 transition-colors">Chọn tất cả</span>
        </label>
        <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2.5 py-1 rounded-full">{{ count($reviews) }} đánh giá</span>
    </div>

    @forelse($reviews as $review)
        <div class="bg-white p-4 sm:p-5 rounded-2xl organic-shadow border border-gray-100 flex flex-col gap-3.5 relative group transition-all hover:shadow-md" id="review-card-{{ $review->id }}">
            <!-- Header: Avatar + Checkbox -->
            <div class="flex justify-between items-start">
                <div class="flex items-center gap-3 w-[calc(100%-2rem)]">
                    @php
                        if ($review->user->avatar) {
                            $avatarUrl = str_starts_with($review->user->avatar, 'http') ? $review->user->avatar : asset('images/avatars/' . $review->user->avatar);
                        } else {
                            $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($review->user->name).'&background=random';
                        }
                    @endphp
                    <img src="{{ $avatarUrl }}" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($review->user->name) }}&background=random'" alt="{{ $review->user->name }}" class="w-11 h-11 sm:w-12 sm:h-12 rounded-full object-cover border-2 border-white shadow-sm shrink-0">
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="text-[15px] sm:text-base font-bold text-gray-900 truncate" style="overflow-wrap: anywhere; word-break: break-word;">{{ $review->user->name }}</span>
                        <span class="text-[11px] sm:text-xs text-gray-500 truncate" style="overflow-wrap: anywhere; word-break: break-word;">{{ $review->user->email ?? '' }}</span>
                        <span class="text-[11px] sm:text-xs text-gray-400 mt-0.5 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">schedule</span>
                            {{ \Carbon\Carbon::parse($review->created_at)->format('d/m/Y H:i') }}
                        </span>
                    </div>
                </div>
                <input type="checkbox" class="row-checkbox rounded-md border-gray-300 text-amber-500 focus:ring-amber-400 cursor-pointer w-5 h-5 shrink-0 transition-colors" value="{{ $review->id }}">
            </div>

            <!-- Rating & Product -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between bg-amber-50/50 p-3 rounded-xl border border-amber-100/50 gap-2 sm:gap-4">
                <div class="flex flex-col gap-1 min-w-0 flex-1">
                    <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Sản phẩm</span>
                    <span class="text-sm font-semibold text-gray-800 line-clamp-1" style="overflow-wrap: anywhere; word-break: break-word;" title="{{ $review->product->name }}">{{ $review->product->name }}</span>
                </div>
                <div class="flex flex-col sm:items-end gap-1 shrink-0">
                    <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider hidden sm:block">Đánh giá</span>
                    <div class="flex items-center gap-1">
                        <div class="flex text-amber-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $review->rating)
                                    <span class="material-symbols-outlined text-[16px] icon-fill">star</span>
                                @else
                                    <span class="material-symbols-outlined text-[16px] text-gray-300 icon-fill">star</span>
                                @endif
                            @endfor
                        </div>
                        <span class="text-xs font-bold text-amber-600 ml-1 bg-amber-100 px-1.5 py-0.5 rounded">{{ $review->rating }}/5</span>
                    </div>
                </div>
            </div>

            <!-- Comment -->
            <div class="text-[13px] sm:text-sm text-gray-600 bg-gray-50/80 p-3.5 rounded-xl border border-gray-100/80 italic leading-relaxed" style="overflow-wrap: anywhere; word-break: break-word;">
                @if($review->comment)
                    "{{ $review->comment }}"
                @else
                    <span class="text-gray-400 not-italic flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px]">speaker_notes_off</span> Khách hàng không để lại nhận xét</span>
                @endif
            </div>

            <!-- Images Grid -->
            @if($review->image)
                @php $images = json_decode($review->image, true); @endphp
                @if(is_array($images) && count($images) > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-1">
                        @foreach($images as $img)
                            <a href="{{ asset('images/' . $img) }}" target="_blank" class="block w-full h-24 sm:h-28 rounded-lg overflow-hidden border border-gray-200/80 shadow-sm relative group">
                                <img src="{{ asset('images/' . $img) }}" alt="Review image" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif

            <hr class="border-gray-100 border-dashed my-1">

            <!-- Actions -->
            <div class="flex items-center justify-between pt-1">
                <div id="status-mobile-{{ $review->id }}">
                    @if($review->is_visible)
                        <button type="button" class="js-toggle-visibility inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg font-bold text-[11px] border border-emerald-100 hover:bg-emerald-100 transition-colors" data-id="{{ $review->id }}" data-url="{{ route('admin.reviews.toggle_visibility', $review->id) }}">
                            <span class="material-symbols-outlined text-[14px] icon-fill">visibility</span>
                            Đang hiển thị
                        </button>
                    @else
                        <button type="button" class="js-toggle-visibility inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-rose-50 text-rose-600 rounded-lg font-bold text-[11px] border border-rose-100 hover:bg-rose-100 transition-colors" data-id="{{ $review->id }}" data-url="{{ route('admin.reviews.toggle_visibility', $review->id) }}">
                            <span class="material-symbols-outlined text-[14px] icon-fill">visibility_off</span>
                            Đang ẩn
                        </button>
                    @endif
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('admin.reviews.edit', $review->id) }}"
                        class="px-3 py-2 sm:px-4 text-amber-600 bg-amber-50 hover:bg-amber-100 border border-amber-100 hover:border-amber-200 rounded-xl transition-colors text-xs font-bold flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">edit</span>
                        Sửa
                    </a>
                    <button type="button"
                        data-id="{{ $review->id }}"
                        class="js-delete-review px-3 py-2 sm:px-4 text-red-600 bg-red-50 hover:bg-red-100 border border-red-100 hover:border-red-200 rounded-xl transition-colors text-xs font-bold flex items-center gap-1 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">delete</span>
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white p-8 rounded-2xl organic-shadow border border-gray-100 text-center flex flex-col items-center">
            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 shadow-inner mb-3">
                <span class="material-symbols-outlined text-4xl text-gray-300">rate_review</span>
            </div>
            <p class="font-bold text-gray-600 text-sm">Không tìm thấy đánh giá nào</p>
            <p class="text-xs text-gray-400 mt-1">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
        </div>
    @endforelse
</div>

<!-- Giao diện Desktop (Table view) -->
<div class="hidden lg:block overflow-x-auto">
    <table class="w-full text-left border-collapse whitespace-nowrap">
        <thead class="bg-gray-50/80 border-b border-gray-100 backdrop-blur-sm">
            <tr>
                <th class="w-10 px-4 py-4 text-center">
                    <input type="checkbox" id="selectAll" class="js-select-all rounded border-gray-300 text-amber-500 focus:ring-amber-400 cursor-pointer">
                </th>
                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Khách hàng</th>
                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Sản phẩm</th>
                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Đánh giá</th>
                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Nội dung</th>
                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Trạng thái</th>
                <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider text-center">Hành động</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($reviews as $review)
                <tr class="hover:bg-amber-50/30 transition-colors duration-200 group" id="review-row-{{ $review->id }}">
                    <td class="px-4 py-4 text-center">
                        <input type="checkbox" class="row-checkbox rounded border-gray-300 text-amber-500 focus:ring-amber-400 cursor-pointer" value="{{ $review->id }}">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            @php
                                if ($review->user->avatar) {
                                    $avatarUrl = str_starts_with($review->user->avatar, 'http') ? $review->user->avatar : asset('images/avatars/' . $review->user->avatar);
                                } else {
                                    $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($review->user->name).'&background=random';
                                }
                            @endphp
                            <img src="{{ $avatarUrl }}" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($review->user->name) }}&background=random'" alt="{{ $review->user->name }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 shadow-sm">
                            <div class="flex flex-col min-w-0">
                                <span class="text-sm font-bold text-gray-900 truncate">{{ $review->user->name }}</span>
                                <span class="text-xs text-gray-500 truncate">{{ \Carbon\Carbon::parse($review->created_at)->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-500 text-[18px]">local_cafe</span>
                            <span class="text-sm font-semibold text-gray-700 truncate max-w-[200px]" title="{{ $review->product->name }}">{{ $review->product->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center text-amber-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $review->rating)
                                    <span class="material-symbols-outlined text-[16px] icon-fill">star</span>
                                @else
                                    <span class="material-symbols-outlined text-[16px] text-gray-300 icon-fill">star</span>
                                @endif
                            @endfor
                            <span class="text-xs font-bold text-amber-600 ml-1.5 bg-amber-100 px-1.5 py-0.5 rounded">{{ $review->rating }}/5</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1.5 max-w-[280px] whitespace-normal">
                            @if($review->comment)
                                <p class="text-sm text-gray-600 line-clamp-2" title="{{ $review->comment }}">{{ $review->comment }}</p>
                            @else
                                <span class="text-sm text-gray-400 italic">Không có nhận xét</span>
                            @endif
                            
                            @if($review->image)
                                @php $images = json_decode($review->image, true); @endphp
                                @if(is_array($images) && count($images) > 0)
                                    <div class="flex items-center gap-1.5 mt-1">
                                        <div class="flex -space-x-2 overflow-hidden">
                                            @foreach(array_slice($images, 0, 3) as $img)
                                                <img class="inline-block h-6 w-6 rounded-md ring-2 ring-white object-cover border border-gray-200" src="{{ asset('images/' . $img) }}" alt="img">
                                            @endforeach
                                        </div>
                                        <span class="text-[11px] font-medium text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">{{ count($images) }} ảnh</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div id="status-{{ $review->id }}">
                            @if($review->is_visible)
                                <button type="button" class="js-toggle-visibility inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg font-bold text-xs border border-emerald-100 hover:bg-emerald-100 transition-colors" data-id="{{ $review->id }}" data-url="{{ route('admin.reviews.toggle_visibility', $review->id) }}">
                                    <span class="material-symbols-outlined text-[16px] icon-fill">visibility</span>
                                    Hiển thị
                                </button>
                            @else
                                <button type="button" class="js-toggle-visibility inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-rose-50 text-rose-600 rounded-lg font-bold text-xs border border-rose-100 hover:bg-rose-100 transition-colors" data-id="{{ $review->id }}" data-url="{{ route('admin.reviews.toggle_visibility', $review->id) }}">
                                    <span class="material-symbols-outlined text-[16px] icon-fill">visibility_off</span>
                                    Bị ẩn
                                </button>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        <div class="flex justify-center gap-1.5">
                            <a href="{{ route('admin.reviews.edit', $review->id) }}"
                                class="p-1.5 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors" title="Sửa đánh giá">
                                <span class="material-symbols-outlined text-[18px]">edit</span>
                            </a>
                            <button type="button"
                                data-id="{{ $review->id }}"
                                class="js-delete-review p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors" title="Xóa đánh giá">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3 text-gray-400">
                            <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center border border-gray-100 shadow-inner">
                                <span class="material-symbols-outlined text-4xl text-gray-300">rate_review</span>
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-gray-600 text-base">Không tìm thấy đánh giá nào</p>
                                <p class="text-sm">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($reviews->hasPages())
<div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 pagination-container rounded-b-2xl">
    {{ $reviews->links() }}
</div>
@endif
