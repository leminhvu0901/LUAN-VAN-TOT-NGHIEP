<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SettingController
{
    // Trang cấu hình thông số và thiết lập hệ thống
    public function index()
    {
        // Đọc toàn bộ cấu hình đã lưu trong DB thành mảng key => value
        $settings = [];
        try {
            if (Schema::hasTable('settings')) {
                $settingsList = Setting::all();
                foreach ($settingsList as $s) {
                    $settings[$s->key] = $s->value;
                }
            }
        } catch (\Exception $e) {
            // Bảng chưa tồn tại, mới cài đặt, chưa chạy migrate ->
        }

        // Giá trị mặc định cho lần đầu cài đặt hoặc key nào đó
        $defaults = [
            'store_name' => 'Happy Tea',
            'store_logo' => '/images/logo/black.png',
            'store_email' => 'admin@happytea.com',
            'store_phone' => '0123456789',
            'store_address' => '180 Cao Lỗ, Phường 4, Quận 8, TP. Hồ Chí Minh',
            'store_open_time' => '08:00',
            'store_close_time' => '22:00',
            'store_facebook_url' => 'https://facebook.com/happytea',
            'store_zalo_url' => 'https://zalo.me/happytea',
            'store_latitude' => '10.7380',
            'store_longitude' => '106.6778',

            'orders_enabled' => '1',
            'auto_cancel_unpaid_enabled' => '1',
            'auto_cancel_unpaid_minutes' => '30',

            'shipping_base_fee' => '15000',
            'shipping_fee_per_km' => '5000',
            'shipping_max_distance_km' => '15',
            'free_shipping_minimum' => '150000',
            'weather_surcharge_enabled' => '0',
            'weather_override' => 'auto',
            'weather_light_rain_percent' => '5',
            'weather_heavy_rain_percent' => '10',
            'weather_storm_percent' => '15',

            'cod_enabled' => '1',
            'vnpay_enabled' => '0',
            'payment_environment' => 'sandbox',

            'loyalty_enabled' => '1',
            'loyalty_money_per_point' => '10000',
            'loyalty_point_value' => '1',
            'loyalty_max_redeem_percent' => '100',
            'loyalty_min_points_to_redeem' => '10',

            'order_confirmation_email_enabled' => '1',
            'new_order_admin_notification_enabled' => '1',
            'low_stock_notification_enabled' => '1',
            'notification_email' => 'admin@happytea.com',
        ];

        // Key nào chưa có trong DB thì bù bằng giá trị mặc định ở trên
        foreach ($defaults as $key => $val) {
            if (!isset($settings[$key])) {
                $settings[$key] = Setting::getValue($key, $val);
            }
        }

        // Tự động cập nhật giá trị quy đổi điểm trong DB về 1 để
        if (Setting::getValue('loyalty_point_value') != 1) {
            Setting::setValue('loyalty_point_value', '1', 'loyalty', 'decimal');
            $settings['loyalty_point_value'] = '1';
        }

        // Chỉ kiểm tra ĐÃ có cấu hình VNPay sandbox hay chưa
        $vnpayEnvConfig = config("services.vnpay.sandbox", []);
        $paymentStatus = [
            'vnpay' => (!empty($vnpayEnvConfig['tmn_code']) && !empty($vnpayEnvConfig['hash_secret'])),
        ];

        // Quét logo ở 2 nơi: logo mẫu đi kèm mã nguồn
        $existingLogos = [];
        $allowedLogoExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $logoDirs = [
            public_path('images/logo') => '/images/logo/',
            upload_dir('logo') => '/images/uploads/logo/',
        ];
        foreach ($logoDirs as $logoDir => $urlPrefix) {
            if (!is_dir($logoDir)) {
                continue;
            }
            foreach (scandir($logoDir) as $file) {
                if ($file === '.' || $file === '..')
                    continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedLogoExts)) {
                    $existingLogos[] = $urlPrefix . $file;
                }
            }
        }

        return view('backend.admin.settings.index', compact('settings', 'paymentStatus', 'existingLogos'));
    }

    // Cập nhật giá trị các thiết lập hệ thống
    public function update(Request $request)
    {
        // Form cấu hình chia theo từng tab
        $section = $request->input('section');

        // Rule validate riêng cho từng tab, gán bên dưới theo switch($section)
        $rules = [];
        $messages = [
            'required' => 'Trường :attribute không được để trống.',
            'numeric' => 'Trường :attribute phải là một số hợp lệ.',
            'integer' => 'Trường :attribute phải là số nguyên.',
            'min' => 'Trường :attribute không được nhỏ hơn :min.',
            'max' => 'Trường :attribute không được lớn hơn :max.',
            'email' => 'Trường :attribute phải là định dạng email hợp lệ.',
            'url' => 'Trường :attribute phải là đường dẫn URL hợp lệ.',
            'mimes' => 'Trường :attribute chỉ chấp nhận định dạng: :values.',
            'in' => 'Trường :attribute lựa chọn không hợp lệ.',
            'date_format' => 'Trường :attribute định dạng thời gian H:i không hợp lệ.'
        ];

        // Danh sách key hợp lệ của từng tab, chỉ những key trong đúng $section mới được ghi vào DB
        $allowedKeys = [
            'store' => ['store_name', 'store_email', 'store_phone', 'store_address', 'store_open_time', 'store_close_time', 'store_facebook_url', 'store_zalo_url', 'store_latitude', 'store_longitude'],
            'orders' => ['orders_enabled', 'auto_cancel_unpaid_enabled', 'auto_cancel_unpaid_minutes'],
            'shipping' => ['shipping_base_fee', 'shipping_fee_per_km', 'shipping_max_distance_km', 'free_shipping_minimum', 'weather_surcharge_enabled', 'weather_override', 'weather_light_rain_percent', 'weather_heavy_rain_percent', 'weather_storm_percent'],
            'payment' => ['cod_enabled', 'vnpay_enabled', 'payment_environment'],
            'loyalty' => ['loyalty_enabled', 'loyalty_money_per_point', 'loyalty_point_value', 'loyalty_max_redeem_percent', 'loyalty_min_points_to_redeem'],
            'notifications' => ['order_confirmation_email_enabled', 'new_order_admin_notification_enabled', 'low_stock_notification_enabled', 'notification_email'],
        ];

        // Kiểu dữ liệu để Setting::setValue() ép kiểu đúng lúc
        $types = [
            'store_name' => 'string',
            'store_logo' => 'string',
            'store_email' => 'string',
            'store_phone' => 'string',
            'store_address' => 'string',
            'store_open_time' => 'string',
            'store_close_time' => 'string',
            'store_facebook_url' => 'string',
            'store_zalo_url' => 'string',
            'store_latitude' => 'decimal',
            'store_longitude' => 'decimal',

            'orders_enabled' => 'boolean',
            'auto_cancel_unpaid_enabled' => 'boolean',
            'auto_cancel_unpaid_minutes' => 'integer',

            'shipping_base_fee' => 'decimal',
            'shipping_fee_per_km' => 'decimal',
            'shipping_max_distance_km' => 'decimal',
            'free_shipping_minimum' => 'decimal',
            'weather_surcharge_enabled' => 'boolean',
            'weather_override' => 'string',
            'weather_light_rain_percent' => 'integer',
            'weather_heavy_rain_percent' => 'integer',
            'weather_storm_percent' => 'integer',

            'cod_enabled' => 'boolean',
            'vnpay_enabled' => 'boolean',
            'payment_environment' => 'string',

            'loyalty_enabled' => 'boolean',
            'loyalty_money_per_point' => 'decimal',
            'loyalty_point_value' => 'decimal',
            'loyalty_max_redeem_percent' => 'decimal',
            'loyalty_min_points_to_redeem' => 'integer',

            'order_confirmation_email_enabled' => 'boolean',
            'new_order_admin_notification_enabled' => 'boolean',
            'low_stock_notification_enabled' => 'boolean',
            'notification_email' => 'string',
        ];

        switch ($section) {
            case 'store':
                $rules = [
                    'store_name' => 'required|string|max:150',
                    'store_email' => 'required|email|max:255',
                    'store_phone' => 'required|string|max:20',
                    'store_address' => 'required|string',
                    'store_open_time' => 'nullable|date_format:H:i',
                    'store_close_time' => 'nullable|date_format:H:i',
                    'store_facebook_url' => 'nullable|url|max:255',
                    'store_zalo_url' => 'nullable|string|max:255',
                    'store_latitude' => 'required|numeric|between:-90,90',
                    'store_longitude' => 'required|numeric|between:-180,180',
                    'store_logo' => 'nullable|mimes:jpg,jpeg,png,webp,svg|max:2048',
                ];
                break;
            case 'orders':
                $rules = [
                    'orders_enabled' => 'required|in:0,1',
                    'auto_cancel_unpaid_enabled' => 'required|in:0,1',
                    'auto_cancel_unpaid_minutes' => 'required|integer|min:0',
                ];
                break;
            case 'shipping':
                $rules = [
                    'shipping_base_fee' => 'required|numeric|min:0',
                    'shipping_fee_per_km' => 'required|numeric|min:0',
                    'shipping_max_distance_km' => 'required|numeric|min:0',
                    'free_shipping_minimum' => 'required|numeric|min:0',
                    'weather_surcharge_enabled' => 'required|in:0,1',
                    // 'auto' = đọc thời tiết thật; các giá trị còn lại là ép cứng để trình diễn.
                    'weather_override' => 'required|in:auto,light_rain,heavy_rain,storm',
                    'weather_light_rain_percent' => 'required|integer|between:0,100',
                    'weather_heavy_rain_percent' => 'required|integer|between:0,100',
                    'weather_storm_percent' => 'required|integer|between:0,100',
                ];
                break;
            case 'payment':
                $rules = [
                    'cod_enabled' => 'required|in:0,1',
                    'vnpay_enabled' => 'required|in:0,1',
                    'payment_environment' => 'required|in:sandbox',
                ];
                break;
            case 'loyalty':
                $rules = [
                    'loyalty_enabled' => 'required|in:0,1',
                    'loyalty_money_per_point' => 'required|numeric|min:0',
                    'loyalty_point_value' => 'required|numeric|min:0',
                    'loyalty_max_redeem_percent' => 'required|numeric|between:0,100',
                    'loyalty_min_points_to_redeem' => 'required|integer|min:0',
                ];
                break;
            case 'notifications':
                $rules = [
                    'order_confirmation_email_enabled' => 'required|in:0,1',
                    'new_order_admin_notification_enabled' => 'required|in:0,1',
                    'low_stock_notification_enabled' => 'required|in:0,1',
                    'notification_email' => 'required|email|max:255',
                ];
                break;
            default:
                return back()->with('error', 'Phân mục cấu hình không hợp lệ.');
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('error_section', $section);
        }

        // Chặn sớm nếu bảng settings chưa tồn tại, tránh lỗi SQL
        if (!Schema::hasTable('settings')) {
            return back()->with('error', 'Bảng cấu hình chưa tồn tại. Vui lòng chạy php artisan migrate để tạo bảng settings trước.')->with('error_section', $section);
        }

        $fields = $allowedKeys[$section] ?? [];

        // Ghi từng key trong 1 transaction, nếu 1 key lỗi giữa
        DB::transaction(function () use ($fields, $section, $types, $request) {
            // Các trường giờ hoạt động: nếu bỏ trống thì giữ nguyên
            $timeFields = ['store_open_time', 'store_close_time'];

            foreach ($fields as $field) {
                $type = $types[$field] ?? 'string';
                $val = $request->input($field);

                // Nếu trường giờ bị để trống, giữ lại giá trị hiện tại
                if (in_array($field, $timeFields) && ($val === null || trim((string) $val) === '')) {
                    continue;
                }

                if ($type === 'boolean') {
                    $val = ($val == '1' || $val === 'true' || $val === true) ? '1' : '0';
                }
                Setting::setValue($field, $val, $section, $type);
            }
        });

        // Logo là file upload, không nằm trong $allowedKeys/$types nên xử lý tách riêng khỏi vòng lặp trên
        $logoPath = null;
        if ($section === 'store') {
            if ($request->hasFile('store_logo')) {
                $file = $request->file('store_logo');
                $extension = $file->getClientOriginalExtension();
                // Dùng UUID thay vì time(), chỉ chính xác tới giây - 2
                $fileName = 'logo_' . (string) Str::uuid() . '.' . $extension;

                // Giá trị lưu DB là đường dẫn tuyệt đối nên asset($shopLogo) ở các view dùng lại
                // được y nguyên, không cần qua upload_url().
                $file->move(upload_dir('logo'), $fileName);
                $logoPath = '/images/' . upload_rel('logo', $fileName);

                // Xóa file logo cũ, trừ các logo mẫu đi kèm mã nguồn
                $oldLogo = Setting::getValue('store_logo');
                $presetLogos = ['/images/logo/black.png', '/images/logo/black1.svg', '/images/logo/black2.png'];
                if ($oldLogo && !in_array($oldLogo, $presetLogos) && file_exists(public_path($oldLogo))) {
                    @unlink(public_path($oldLogo));
                }

                Setting::setValue('store_logo', $logoPath, 'store', 'string');
            }
        }

        return back()->with('success', 'Đã lưu cấu hình phân mục thành công!')->with('active_section', $section);
    }
}
