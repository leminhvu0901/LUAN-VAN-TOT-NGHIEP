<div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
        <div {{$recentReviews ? '':''}}>
            <h3 class="font-bold text-gray-900 text-base">Đánh giá mới của khách</h3>
            <p class="text-xs text-gray-500 mt-0.5">Phản hồi và số lượng sao mới nhận được từ khách hàng.</p>
        </div>
        <a href="{{ route('admin.reviews.index') }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-bold transition-colors">Xem tất cả</a>
    </div>

    <div class="space-y-4">
        @forelse ($recentReviews as $review)
            <div class="p-3.5 bg-gray-50/50 hover:bg-gray-50 border border-gray-100 rounded-xl flex gap-3 transition-colors duration-150 relative">
                <!-- User Avatar / Initial Badge -->
                <div class="w-9 h-9 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center font-bold text-indigo-600 text-xs shrink-0 select-none overflow-hidden">
                    @if ($review->user && $review->user->avatar)
                        @php
                            $avatarUrl = avatar_url($review->user->avatar);
                        @endphp
                        <img src="{{ $avatarUrl }}" alt="{{ $review->user->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'; this.parentElement.innerText='{{ strtoupper(substr($review->user?->name ?? 'K', 0, 1)) }}'">
                    @else
                        {{ strtoupper(substr($review->user?->name ?? 'K', 0, 1)) }}
                    @endif
                </div>
                
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-xs font-bold text-gray-900">{{ $review->user?->name ?? 'Khách vãng lai' }}</span>
                            <span class="text-[10px] text-gray-400 font-medium">• {{ $review->created_at ? $review->created_at->diffForHumans() : 'Vừa xong' }}</span>
                        </div>
                        
                        <!-- Stars rating -->
                        <div class="flex text-amber-400 text-[13px] shrink-0">
                            @for ($i = 0; $i < 5; $i++)
                                @if ($i < $review->rating)
                                    ★
                                @else
                                    <span class="text-gray-200">★</span>
                                @endif
                            @endfor
                        </div>
                    </div>

                    @if ($review->product)
                        <div class="text-[11px] font-semibold text-blue-600 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">local_cafe</span>
                            <span>{{ $review->product->name }}</span>
                        </div>
                    @endif

                    <p class="text-xs text-gray-600 mt-1.5 line-clamp-2 leading-relaxed bg-white border border-gray-100/50 p-2 rounded-lg" title="{{ $review->comment }}">
                        {{ $review->comment ?: 'Khách hàng không để lại nhận xét.' }}
                    </p>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-400 text-sm">Chưa nhận được đánh giá nào.</div>
        @endforelse
    </div>
</div>
