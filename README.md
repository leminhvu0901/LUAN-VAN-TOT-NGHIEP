# Happy Tea — Đồ án tốt nghiệp

Website bán trà sữa (Laravel 13 + PHP 8.3), gồm trang khách hàng (đặt hàng online, giao hàng/nhận tại quầy, thanh toán VNPay/COD), trang quản trị (Admin) và trang bán hàng tại quầy cho Lễ tân (POS).

## Yêu cầu hệ thống

- PHP >= 8.3, kèm extension: `bcmath`, `gd`, `zip`
- Composer
- Node.js + npm
- MySQL (hoặc MariaDB tương thích)

## Cài đặt lần đầu

```bash
composer install
copy .env.example .env      # Windows (PowerShell/CMD). Trên Git Bash/Linux/Mac dùng: cp .env.example .env
php artisan key:generate
```

Mở `.env`, chỉnh phần kết nối DB cho đúng máy local (mặc định sẵn `DB_DATABASE=db_luanvantotnghiep`, `DB_USERNAME=root`, `DB_PASSWORD=` rỗng — sửa lại nếu MySQL local có mật khẩu khác). Tạo database trống cùng tên trước khi chạy migrate.

```bash
php artisan migrate --seed
npm install
```

## Chạy dự án (local)

Cách nhanh nhất — chạy đồng thời server PHP, queue worker, log viewer và Vite:

```bash
composer run dev
```

Hoặc chạy riêng từng phần nếu cần:

```bash
php artisan serve       # server Laravel — http://127.0.0.1:8000
php artisan queue:listen --tries=1 --timeout=0   # xử lý queue (gửi mail OTP, thông báo...)
npm run dev              # Vite — biên dịch CSS/JS, hot reload
```

## Tài khoản mặc định sau khi seed

Seeder (`database/seeders/DatabaseSeeder.php`, `AddAccountsSeeder.php`) tạo sẵn tài khoản admin/khách demo — xem trực tiếp các file này để lấy email/mật khẩu, tránh chép lặp thông tin đăng nhập ra nhiều nơi.

## Biến môi trường cần cấu hình thêm (tùy tính năng cần test)

Các key sau để trống mặc định trong `.env.example`, chỉ cần điền khi test đúng tính năng liên quan — thiếu thì các phần khác của web vẫn chạy bình thường:

| Biến | Dùng cho |
|---|---|
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Gửi email xác thực OTP (Gmail SMTP — dùng App Password, không dùng mật khẩu Gmail thường) |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Đăng nhập bằng Google (Laravel Socialite) |
| `GEOAPIFY_API_KEY` | Bản đồ + tính khoảng cách giao hàng lúc checkout |
| `OPENROUTE_SERVICE_API_KEY` | Tính route/khoảng cách (dự phòng cho Geoapify) |
| `VNPAY_TMN_CODE_SANDBOX`, `VNPAY_HASH_SECRET_SANDBOX` | Thanh toán online — phải tự đăng ký merchant sandbox tại sandbox.vnpayment.vn/merchantv2 |

## Chạy test

```bash
composer run test
```

## Quy ước code

- Giao diện chỉ dùng Tailwind CSS + CSS thuần, không dùng Bootstrap.
- CSS gộp vào file chung, không viết style inline.
