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

if (!function_exists('upload_dir')) {
    /**
     * Thư mục VẬT LÝ để ghi file do người dùng tải lên.
     *
     * Vì sao tách riêng thành public/images/uploads/ thay vì ghi thẳng vào public/images/?
     * Trên Railway, mọi thứ nằm trong container đều bị dựng lại từ mã nguồn sau mỗi lần deploy —
     * file người dùng tải lên sẽ mất sạch. Muốn giữ lại thì phải gắn một ổ đĩa bền vững (volume)
     * vào đúng thư mục chứa chúng. Nhưng public/images/ lại đang chứa sẵn ảnh mẫu đi kèm mã nguồn,
     * mà gắn volume lên một thư mục đã có dữ liệu thì toàn bộ dữ liệu đó bị che mất.
     * Vì vậy ảnh tải lên được đẩy xuống thư mục con riêng để gắn volume vào đó:
     *   public/images/          <- ảnh mẫu, đi theo mã nguồn
     *   public/images/uploads/  <- ảnh người dùng tải lên, gắn volume ở đây
     */
    function upload_dir(string $subdir = ''): string
    {
        return public_path(rtrim('images/uploads/' . trim($subdir, '/'), '/'));
    }
}

if (!function_exists('upload_rel')) {
    /**
     * Giá trị đem lưu vào DB cho file vừa ghi bằng upload_dir(). Luôn tương đối so với
     * public/images/ để upload_url()/upload_path() dùng lại được mà không cần sửa gì.
     */
    function upload_rel(string $subdir, string $filename): string
    {
        return 'uploads/' . trim($subdir, '/') . '/' . $filename;
    }
}

if (!function_exists('avatar_url')) {
    /**
     * URL ảnh đại diện. Cột users.avatar có 3 dạng tuỳ nguồn:
     * - URL đầy đủ (đăng nhập Google): "https://lh3.googleusercontent.com/..." -> dùng nguyên
     * - Đường dẫn tương đối "uploads/avatars/1234_abc.jpg" (ảnh tải lên từ nay về sau)
     * - Tên file trần "1234_abc.jpeg" (dữ liệu cũ) -> nằm ở public/images/avatars/
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

        // Có dấu "/" nghĩa là đã kèm sẵn thư mục, chỉ cần ghép gốc images/
        return str_contains($avatar, '/')
            ? asset('images/' . $avatar)
            : asset('images/avatars/' . $avatar);
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

        return str_contains($avatar, '/')
            ? public_path('images/' . $avatar)
            : public_path('images/avatars/' . $avatar);
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
