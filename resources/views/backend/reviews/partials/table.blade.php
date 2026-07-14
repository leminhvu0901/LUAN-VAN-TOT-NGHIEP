<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse whitespace-nowrap">
        <thead class="bg-gray-50/80 border-b border-gray-100 backdrop-blur-sm">
            <tr>
                <th class="w-10 px-4 py-4 text-center">
                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-amber-500 focus:ring-amber-400 cursor-pointer">
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
                                    <span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">star</span>
                                @else
                                    <span class="material-symbols-outlined text-[16px] text-gray-300" style="font-variation-settings: 'FILL' 0;">star</span>
                                @endif
                            @endfor
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1 max-w-[250px] whitespace-normal">
                            @if($review->comment)
                                <p class="text-sm text-gray-600 line-clamp-2" title="{{ $review->comment }}">{{ $review->comment }}</p>
                            @else
                                <span class="text-sm text-gray-400 italic">Không có nhận xét</span>
                            @endif
                            
                            @if($review->image)
                                @php $images = json_decode($review->image, true); @endphp
                                @if(is_array($images) && count($images) > 0)
                                    <div class="flex items-center gap-1 mt-1">
                                        <span class="material-symbols-outlined text-[14px] text-gray-400">image</span>
                                        <span class="text-xs text-gray-500">{{ count($images) }} hình ảnh</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div id="status-{{ $review->id }}">
                            @if($review->is_visible)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg font-semibold text-xs border border-emerald-100">
                                    <span class="material-symbols-outlined text-[16px]">visibility</span>
                                    Hiển thị
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-rose-50 text-rose-600 rounded-lg font-semibold text-xs border border-rose-100">
                                    <span class="material-symbols-outlined text-[16px]">visibility_off</span>
                                    Bị ẩn
                                </span>
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
                    <td colspan="6" class="px-6 py-16 text-center">
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
