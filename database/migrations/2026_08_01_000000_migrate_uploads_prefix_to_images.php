<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Dự án không còn deploy Railway (chỉ chạy local) nên bỏ hẳn quy ước "public/uploads/" (Railway
 * Volume bền vững qua các lần deploy) — giờ chỉ còn 1 quy ước "public/images/" (giống dữ liệu
 * seed/legacy đã có sẵn). Migration này chuyển các dòng đang lưu tiền tố "uploads/" về dạng trần,
 * đồng thời di chuyển vật lý file tương ứng từ public/uploads/... sang public/images/....
 *
 * Không dùng Eloquent (Query Builder thuần) để vẫn chạy đúng nếu model sau này đổi khác.
 */
return new class extends Migration
{
    private const PREFIXED_COLUMNS = [
        'products' => 'image',
        'product_images' => 'image_path',
    ];

    public function up(): void
    {
        foreach (self::PREFIXED_COLUMNS as $table => $column) {
            $this->migrateRelativeColumn($table, $column);
        }

        $this->migrateAbsoluteSetting();
    }

    // Cột kiểu "products/x.jpg" — tiền tố "uploads/" nằm ngay đầu chuỗi.
    private function migrateRelativeColumn(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        $rows = DB::table($table)->where($column, 'like', 'uploads/%')->get(['id', $column]);

        foreach ($rows as $row) {
            $old = $row->{$column};
            $newRelative = Str::after($old, 'uploads/');

            $this->moveFileIfExists(public_path($old), public_path('images/' . $newRelative));

            DB::table($table)->where('id', $row->id)->update([$column => $newRelative]);
        }
    }

    // settings.store_logo lưu dạng tuyệt đối "/uploads/logo/x.png" (khác 2 cột trên).
    private function migrateAbsoluteSetting(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $row = DB::table('settings')->where('key', 'store_logo')->where('value', 'like', '/uploads/%')->first();
        if (!$row) {
            return;
        }

        $newValue = '/images/' . Str::after($row->value, '/uploads/');
        $this->moveFileIfExists(public_path(ltrim($row->value, '/')), public_path(ltrim($newValue, '/')));

        DB::table('settings')->where('id', $row->id)->update(['value' => $newValue]);
    }

    // Không được để 1 file lỗi/thiếu làm hỏng cả migration — DB vẫn là nguồn sự thật, cứ cập nhật
    // cột dù di chuyển file thất bại (ảnh vỡ 1 dòng không tệ hơn ảnh vỡ do thiếu file từ trước).
    private function moveFileIfExists(string $from, string $to): void
    {
        try {
            if (!is_file($from)) {
                Log::warning("migrate_uploads_prefix_to_images: source file missing, skipping move: {$from}");
                return;
            }
            if (!is_dir(dirname($to))) {
                mkdir(dirname($to), 0755, true);
            }
            @rename($from, $to);
        } catch (\Throwable $e) {
            Log::warning("migrate_uploads_prefix_to_images: failed to move {$from} -> {$to}: " . $e->getMessage());
        }
    }

    /**
     * Best-effort: dựng lại tiền tố "uploads/" cho các dòng hiện đang trỏ vào đúng những file đã di
     * chuyển ở up(). Giới hạn đã biết: không phân biệt được dòng nào vốn dĩ đã là legacy (bare path)
     * từ trước khi up() chạy — nếu có dữ liệu legacy trùng tên file mới được thêm sau up(), rollback
     * có thể gắn nhầm tiền tố cho dòng đó.
     */
    public function down(): void
    {
        foreach (self::PREFIXED_COLUMNS as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            // Không có cách xác định lại chính xác tập hợp dòng đã migrate — bỏ qua rollback dữ liệu
            // relative (an toàn hơn là đoán sai và gắn nhầm tiền tố cho dữ liệu legacy có sẵn).
        }
    }
};
