<?php

namespace Database\Seeders;

use App\Models\Banner;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $statuses = [
            ['is_active' => 1, 'start_at' => null, 'end_at' => null],
            ['is_active' => 1, 'start_at' => $now->copy()->subDays(5)->toDateTimeString(), 'end_at' => $now->copy()->addDays(30)->toDateTimeString()],
            ['is_active' => 0, 'start_at' => null, 'end_at' => null],
            ['is_active' => 1, 'start_at' => $now->copy()->addDays(3)->toDateTimeString(), 'end_at' => $now->copy()->addDays(20)->toDateTimeString()],
            ['is_active' => 1, 'start_at' => $now->copy()->subDays(30)->toDateTimeString(), 'end_at' => $now->copy()->subDays(2)->toDateTimeString()],
        ];

        $titles = [
            'Banner Khuyen Mai Mua He',
            'Flash Sale Cuoi Tuan',
            'Uu Dai Dac Biet Thang 7',
            'Combo Tiet Kiem Hoc Sinh',
            'Sinh Nhat Tra Sua Giam 50%',
            'Thu Hai Vui Ve - Mua 1 Tang 1',
            'Chuong Trinh Tich Diem Thanh Vien',
            'Khai Truong Chi Nhanh Moi',
            'Ket Hop Hoan Hao - Tra va Banh',
            'Deal Ngon Moi Ngay',
            'Gioi Thieu Ban Be - Nhan Qua',
            'Ngay Cuoi Thang Dai Giam Gia',
            'Mua He Ruc Ro - Tra Trai Cay Moi',
            'Happy Hour 2-4 Chieu',
            'Banner Tuyen Dung Nhan Vien',
            'Su Kien Ky Niem 3 Nam',
            'Voucher Sinh Nhat - Mien Phi 1 Ly',
            'Chuong Trinh Loyalty VIP',
            'He 2026 - Sam Deal Ngay',
            'Topping Mien Phi Thu 4',
            'App Moi - Giam 20K Don Dau',
            'Uu Dai Giao Hang - Freeship 5KM',
            'Le Hoi Am Nhac - Tra Sua Dong Gia',
            'Check-in Nhan Ngay Qua Tang',
            'Tra Sua Moi Ra Mat - Thu Ngay',
            'Khuyen Mai Valentine 2026',
            'Tet Trung Thu - Combo Dac Biet',
            'Ngay Phu Nu Viet Nam 20-10',
            'Black Friday Dai Ha Gia',
            'Mung Nam Moi - Uu Dai Lon',
        ];

        foreach ($titles as $i => $title) {
            $s = $statuses[$i % count($statuses)];

            Banner::create([
                'title'         => $title,
                'title_tag'     => 'Seed Test ' . ($i + 1),
                'image_url'     => '',
                'link_url'      => null,
                'is_active'     => $s['is_active'],
                'display_order' => $i + 1,
                'start_at'      => $s['start_at'],
                'end_at'        => $s['end_at'],
            ]);
        }

        $this->command->info('Inserted 30 sample banners.');
    }
}
