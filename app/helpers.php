<?php

if (!function_exists('upload_url')) {
    /**
     * Trả về URL công khai cho 1 file do người dùng/quản trị tải lên (avatar, ảnh đánh giá, banner,
     * logo, ảnh sản phẩm...).
     *
     * BỐI CẢNH: Railway (nơi deploy) tạo lại container HOÀN TOÀN MỚI từ code trên GitHub mỗi lần
     * deploy - file nào được ghi lên ổ đĩa lúc chạy (không nằm trong git) sẽ MẤT sau lần deploy kế
     * tiếp. Ảnh có sẵn (seed/demo) nằm trong public/images/... và được commit vào git nên luôn an
     * toàn. Ảnh tải lên MỚI giờ được ghi vào public/uploads/... - thư mục này gắn 1 Railway Volume
     * (ổ đĩa bền vững, sống sót qua các lần deploy) nên không bị mất nữa.
     *
     * Dữ liệu lưu trong DB có 2 dạng tuỳ thời điểm tạo:
     * - CŨ (trước khi có thay đổi này): đường dẫn trần như "reviews/xxx.jpg" -> vẫn nằm ở public/images/
     * - MỚI: đã có sẵn tiền tố "uploads/" như "uploads/reviews/xxx.jpg" -> nằm ở public/uploads/
     * Hàm này tự nhận diện tiền tố để trỏ đúng chỗ cho cả 2 trường hợp, không cần chạy migration dữ liệu.
     */
    function upload_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (
            str_starts_with($path, 'uploads/') ||
            str_starts_with($path, 'storage/') ||
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return asset($path);
        }

        return asset('images/' . $path);
    }
}

if (!function_exists('avatar_url')) {
    /**
     * URL ảnh đại diện. Cột users.avatar có 3 dạng tuỳ nguồn:
     * - URL đầy đủ (đăng nhập Google): "https://lh3.googleusercontent.com/..." -> dùng nguyên
     * - CŨ: chỉ TÊN FILE trần "1234_abc.jpeg" (không có thư mục) -> nằm ở public/images/avatars/
     * - MỚI: "uploads/avatars/1234_abc.jpeg" -> nằm ở public/uploads/ (Railway Volume, không mất khi deploy)
     * Trả về null nếu chưa có avatar để nơi gọi tự quyết định ảnh mặc định.
     */
    function avatar_url(?string $avatar): ?string
    {
        if (empty($avatar)) {
            return null;
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        if (str_starts_with($avatar, 'uploads/')) {
            return asset($avatar);
        }

        return asset('images/avatars/' . $avatar);
    }
}

if (!function_exists('avatar_path')) {
    /**
     * Đường dẫn VẬT LÝ của avatar để xoá file cũ. Trả về null với avatar là URL ngoài (Google) vì
     * không có file nào của mình để xoá.
     */
    function avatar_path(?string $avatar): ?string
    {
        if (empty($avatar) || str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return null;
        }

        if (str_starts_with($avatar, 'uploads/')) {
            return public_path($avatar);
        }

        return public_path('images/avatars/' . $avatar);
    }
}

if (!function_exists('upload_path')) {
    /**
     * Đường dẫn VẬT LÝ trên ổ đĩa (public_path) tương ứng với 1 giá trị lưu trong DB - dùng khi cần
     * xoá file cũ (unlink/file_exists). Cùng quy tắc nhận diện tiền tố với upload_url() ở trên.
     */
    function upload_path(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'uploads/')) {
            return public_path($path);
        }

        return public_path('images/' . $path);
    }
}
