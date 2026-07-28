{{-- 1 thẻ đánh giá kiểu "compact" (dùng ở trang "Xem đánh giá", class Tailwind riêng). --}}
<div class="border-b border-gray-100 pb-6 last:border-0 last:pb-0">
    <div class="flex justify-between items-start mb-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-orange-100 text-orange-800 flex items-center justify-center font-bold text-sm overflow-hidden">
                @if($review->user_avatar)
                    @if(\Illuminate\Support\Str::startsWith($review->user_avatar, 'http'))
                        <img src="{{ $review->user_avatar }}" alt="{{ $review->user_name }}" class="w-full h-full object-cover" referrerpolicy="no-referrer">
                    @else
                        <img src="{{ asset('images/avatars/' . $review->user_avatar) }}" alt="{{ $review->user_name }}" class="w-full h-full object-cover">
                    @endif
                @else
                    {{ mb_substr($review->user_name, 0, 2) }}
                @endif
            </div>
            <div>
                <h4 class="font-bold text-sm text-gray-900">
                    {{ $review->user_name }}
                    @if(auth()->check() && auth()->id() === $review->user_id)
                        <span class="text-primary font-normal">(Bạn)</span>
                    @endif
                </h4>
                <div class="flex text-yellow-400 text-xs mt-0.5">
                    @for($i=1; $i<=5; $i++)
                        <span class="material-symbols-outlined text-[14px] {{ $i <= $review->rating ? 'material-filled' : '' }}">star</span>
                    @endfor
                </div>
                <span class="text-[11px] text-gray-400">{{ \Carbon\Carbon::parse($review->created_at)->translatedFormat('d \T\h\á\n\g m, Y') }}</span>
            </div>
        </div>
        <span class="inline-flex items-center gap-1 text-[10px] text-[#00a82d] bg-[#00a82d]/10 px-2.5 py-1 rounded-md font-bold uppercase">
            <span class="material-symbols-outlined text-[12px]">check_circle</span> ĐÃ MUA HÀNG
        </span>
    </div>

    @if($review->comment)
        <p class="text-sm text-gray-700 leading-relaxed">{{ $review->comment }}</p>
    @endif

    @if($review->image)
        @php
            $images = [];
            $decoded = json_decode($review->image, true);
            if (is_array($decoded)) { $images = $decoded; }
            else { $images = [$review->image]; }
        @endphp
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach($images as $img)
                <img src="{{ asset('images/' . $img) }}" class="w-20 h-20 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open(this.src, '_blank')">
            @endforeach
        </div>
    @endif
</div>
