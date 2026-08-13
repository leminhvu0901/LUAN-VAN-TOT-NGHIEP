{{-- Thẻ đánh giá chi tiết sản phẩm --}}
<div class="pd-review-item">
    {{-- Đánh giá của khách hàng --}}
    <div class="pd-review-avatar">
        @if($review->user_avatar)
            @if(\Illuminate\Support\Str::startsWith($review->user_avatar, 'http'))
                <img src="{{ $review->user_avatar }}" alt="{{ $review->user_name }}" class="pd-review-avatar-img" referrerpolicy="no-referrer">
            @else
                <img src="{{ avatar_url($review->user_avatar) }}" alt="{{ $review->user_name }}" class="pd-review-avatar-img">
            @endif
        @else
            <span class="pd-review-avatar__initial">{{ mb_substr($review->user_name, 0, 1) }}</span>
        @endif
    </div>
    <div class="pd-review-body">
        <div class="pd-review-header">
            <span class="pd-review-name">
                {{ $review->user_name }}
                @if(auth()->check() && auth()->id() === $review->user_id)
                    <span class="pd-review-you-tag">(Bạn)</span>
                @endif
            </span>
            <div class="pd-review-stars">
                @for($i=1;$i<=5;$i++)
                    <svg class="pd-star pd-star--sm {{ $i <= $review->rating ? 'pd-star--filled' : 'pd-star--empty' }}" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/></svg>
                @endfor
            </div>
            {{-- Thời gian bình luận: chuyển sang định dạng --}}
            <span class="pd-review-date">{{ \Carbon\Carbon::parse($review->created_at)->diffForHumans() }}</span>
        </div>
        @if($review->comment)
        <p class="pd-review-comment">{{ $review->comment }}</p>
        @endif

        {{-- Hiển thị các hình ảnh đính kèm bài đánh giá --}}
        @if($review->image)
        @php
            $images = [];
            $decoded = json_decode($review->image, true);
            if (is_array($decoded)) { $images = $decoded; }
            else { $images = [$review->image]; }
        @endphp
        <div class="pd-review-images mt-2 pd-review-images-wrap">
            @foreach($images as $img)
                <img src="{{ upload_url($img) }}" alt="Review Image" class="pd-review-image-thumb" onclick="window.open(this.src, '_blank')">
            @endforeach
        </div>
        @endif
    </div>
</div>
