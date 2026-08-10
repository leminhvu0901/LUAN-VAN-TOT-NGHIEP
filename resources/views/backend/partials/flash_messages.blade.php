<!-- //Bắt các thông báo Flash Session từ Laravel Controller (thành công, lỗi nghiệp vụ,  -->
{{-- Kiểm tra nếu tồn tại bất kỳ thông báo thành công, lỗi đơn lẻ hoặc lỗi validate form từ Laravel --}}
@if(session('success') || session('error') || $errors->any())
    <script>
        {{-- 1. Trường hợp có thông báo THÀNH CÔNG từ Controller: redirect()->with('success', '...') --}}
        @if(session('success'))
            {{-- json_encode đảm bảo chuỗi ký tự (kể cả dấu ngoặc, nháy kép, unicode tiếng Việt) không bị lỗi cú pháp JS --}}
            window.flashSuccessMessage = {!! json_encode(session('success')) !!};
        @endif

        {{-- 2. Trường hợp có thông báo LỖI:
             - session('error'): Lỗi nghiệp vụ đơn lẻ từ Controller (VD: "Không thể xóa sản phẩm đang có đơn hàng")
             - $errors->all(): Mảng danh sách các lỗi validate từ Request/Validator (VD: "Tên sản phẩm là bắt buộc", "Giá phải là số dương")
             - Sử dụng array_merge để gộp cả 2 nguồn lỗi vào 1 mảng duy nhất truyền cho JavaScript
        --}}
        @if(session('error') || $errors->any())
            window.flashErrorMessages = {!! json_encode(array_merge(
                session('error') ? [session('error')] : [],
                $errors->all()
            )) !!};
        @endif
    </script>
@endif
