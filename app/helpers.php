<?php

if (!function_exists('upload_url')) {
    // Trả về URL công khai cho file tải lên
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
    // Thư mục lưu trữ file tải lên trên máy chủ
    function upload_dir(string $subdir = ''): string
    {
        return public_path(rtrim('images/uploads/' . trim($subdir, '/'), '/'));
    }
}

if (!function_exists('upload_rel')) {
    // Đường dẫn tương đối lưu vào cơ sở dữ liệu
    function upload_rel(string $subdir, string $filename): string
    {
        return 'uploads/' . trim($subdir, '/') . '/' . $filename;
    }
}

if (!function_exists('avatar_url')) {
    // Trả về URL ảnh đại diện người dùng
    function avatar_url(?string $avatar): ?string
    {
        if (empty($avatar)) {
            return null;
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        return str_contains($avatar, '/')
            ? asset('images/' . $avatar)
            : asset('images/avatars/' . $avatar);
    }
}

if (!function_exists('avatar_path')) {
    // Đường dẫn file vật lý của ảnh đại diện trên máy chủ
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
    // Đường dẫn file vật lý trên máy chủ tương ứng giá trị trong DB
    function upload_path(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return public_path('images/' . $path);
    }
}

if (!function_exists('product_image_url')) {
    // Trả về URL hình ảnh sản phẩm
    function product_image_url(?string $image): string
    {
        if (empty($image)) {
            return asset('images/products/placeholder.jpg');
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        return asset('images/' . $image);
    }
}

if (!function_exists('setting')) {
    // Lấy giá trị cài đặt từ cơ sở dữ liệu
    function setting(string $key, $default = null)
    {
        return \App\Models\Setting::getValue($key, $default);
    }
}
