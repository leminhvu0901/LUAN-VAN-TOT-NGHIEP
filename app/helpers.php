<?php

if (!function_exists('upload_url')) {
    /**
     * Trả về URL công khai cho 1 file do người dùng/quản trị tải lên (avatar, ảnh đánh giá, banner,
     * logo, ảnh sản phẩm...). Mọi đường dẫn lưu trong DB đều tương đối so với public/images/.
     */
    function upload_url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (
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
     * URL ảnh đại diện. Cột users.avatar có 2 dạng tuỳ nguồn:
     * - URL đầy đủ (đăng nhập Google): "https://lh3.googleusercontent.com/..." -> dùng nguyên
     * - Tên file trần "1234_abc.jpeg" (không có thư mục) -> nằm ở public/images/avatars/
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

        return public_path('images/avatars/' . $avatar);
    }
}

if (!function_exists('upload_path')) {
    /**
     * Đường dẫn VẬT LÝ trên ổ đĩa (public_path) tương ứng với 1 giá trị lưu trong DB - dùng khi cần
     * xoá file cũ (unlink/file_exists).
     */
    function upload_path(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return public_path('images/' . $path);
    }
}
