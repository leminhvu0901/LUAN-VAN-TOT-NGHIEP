<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Tạo tài khoản ảo phục vụ đo hiệu năng đăng nhập (load test).
 *
 * Cách chạy (chỉ chạy trên máy local, KHÔNG chạy trên server thật):
 *   php artisan db:seed --class=LoadTestUserSeeder
 *
 * Tài khoản sinh ra: loadtest1@test.local ... loadtest1000@test.local
 * Mật khẩu chung:    loadtest123
 *
 * Xoá sau khi test xong:
 *   php artisan tinker --execute="DB::table('users')->where('email','like','loadtest%@test.local')->delete();"
 */
class LoadTestUserSeeder extends Seeder
{
    private const TOTAL = 1000;
    private const PASSWORD = 'loadtest123';
    private const EMAIL_SUFFIX = '@test.local';

    public function run(): void
    {
        // Chặn chạy nhầm trên server thật - seeder này đổ 1000 tài khoản rác vào bảng users
        if (app()->environment('production')) {
            $this->command->error('Seeder này chỉ dùng ở môi trường local. Đã hủy.');
            return;
        }

        // Băm mật khẩu ĐÚNG MỘT LẦN rồi dùng lại cho mọi tài khoản. Bcrypt cố tình chạy chậm
        // (khoảng 0.2-0.4 giây/lần với BCRYPT_ROUNDS=12), băm 1000 lần sẽ mất vài phút vô ích.
        $hash = Hash::make(self::PASSWORD);
        $now = now();

        $rows = [];
        for ($i = 1; $i <= self::TOTAL; $i++) {
            $rows[] = [
                'name' => 'Load Test ' . $i,
                'email' => 'loadtest' . $i . self::EMAIL_SUFFIX,
                'password' => $hash,
                'role' => 'customer',
                'membership_level' => 'new',
                'points' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Chèn theo lô 200 dòng cho nhẹ bộ nhớ; upsert để chạy lại nhiều lần không bị trùng email
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('users')->upsert($chunk, ['email'], ['name', 'password', 'is_active', 'updated_at']);
        }

        $this->command->info(sprintf(
            'Đã tạo %d tài khoản: loadtest1%s ... loadtest%d%s (mật khẩu: %s)',
            self::TOTAL,
            self::EMAIL_SUFFIX,
            self::TOTAL,
            self::EMAIL_SUFFIX,
            self::PASSWORD
        ));
    }
}
