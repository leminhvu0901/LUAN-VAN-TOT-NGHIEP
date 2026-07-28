# Thay đổi Database — Tính năng hoàn tiền tự động MoMo

Ngày: 2026-07-28
Migration: `database/migrations/2026_07_28_130000_add_refund_fields_to_orders_table.php`

## Bảng `orders` — 3 cột mới

| Cột | Kiểu | Nullable | Ghi chú |
|---|---|---|---|
| `refund_transaction_id` | `string(100)` | Có (unique) | Mã giao dịch **hoàn tiền** MoMo trả về — khác với `payment_transaction_id` (mã giao dịch **thanh toán** gốc, đã có sẵn từ trước). |
| `refunded_at` | `timestamp` | Có | Thời điểm hoàn tiền thành công. |
| `delivery_failure_type` | `string(20)` | Có | Phân loại lý do khi shipper báo giao thất bại: `damaged` / `customer_unreachable` / `other`. Chỉ `damaged` mới kích hoạt hoàn tiền tự động. |

Migration dùng `Schema::hasColumn` guard (idempotent — chạy lại không lỗi). Có `down()` để rollback (drop cả 3 cột).

## Không thay đổi

- Không tạo bảng mới.
- Không đổi cột nào đã có sẵn — kể cả `orders.payment_status` (enum `unpaid|partially_paid|paid|refunded`), giá trị `'refunded'` đã tồn tại từ trước, tính năng này chỉ mới BẮT ĐẦU sử dụng nó.
- Không sửa bất kỳ migration cũ nào.

## Thay đổi liên quan tầng model (không phải migration)

`app/Models/Order.php` — thêm `'refunded_at' => 'datetime'` vào `$casts`, cùng cách các cột timestamp khác (`paid_at`, `cod_settled_at`, ...) đang được cast.

## Trạng thái

Migration đã chạy (`php artisan migrate --force`) trên môi trường local. **Chưa push code lên git**, chưa deploy Railway.
