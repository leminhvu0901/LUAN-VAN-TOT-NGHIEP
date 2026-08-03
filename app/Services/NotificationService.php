<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

//"Dịch vụ gửi thông báo (Email)".
class NotificationService
{
    /**
     * public: Cho phép gọi từ bên ngoài.
     * orderPlaced(Order $order): Gửi toàn bộ thông báo liên quan khi một đơn hàng mới được đặt thành công.
     * - Tham số Order $order: Đối tượng đơn hàng vừa được tạo.
     */
    public function orderPlaced(Order $order): void
    {
        $this->sendCustomerConfirmation($order); // Gọi hàm gửi mail xác nhận cho khách hàng
        $this->sendAdminNewOrderAlert($order);   // Gọi hàm gửi mail thông báo cho Admin
    }

    /**
     * private: Chỉ dùng nội bộ trong class này.
     * sendCustomerConfirmation(Order $order): Gửi email xác nhận chi tiết đơn hàng cho khách hàng.
     * - Tham số Order $order: Đơn hàng cần xác nhận.
     */
    private function sendCustomerConfirmation(Order $order): void
    {
        // Kiểm tra cấu hình hệ thống: Nếu tính năng gửi email xác nhận bị tắt thì dừng lại không gửi
        if (Setting::getValue('order_confirmation_email_enabled', '1') != '1') {
            return;
        }

        // Lấy địa chỉ email của khách hàng: ưu tiên lấy từ quan hệ $order->user, nếu không có thì truy vấn trực tiếp từ bảng users
        $email = $order->user?->email ?: User::query()->whereKey($order->user_id)->value('email');
        if (!$email) {
            return; // Khách hàng không có email (hoặc là khách mua vãng lai không tài khoản), không gửi
        }

        // Soạn thảo nội dung email gửi cho khách
        $body = "Chào {$order->customer_name},\n\n"
            . "Đơn hàng {$order->order_code} của bạn đã được ghi nhận thành công.\n"
            . 'Tổng tiền: ' . number_format((float) $order->final_amount, 0, ',', '.') . "đ\n"
            . 'Phương thức thanh toán: ' . strtoupper($order->payment_method) . "\n"
            . "Địa chỉ giao: {$order->delivery_address}\n\n"
            . 'Cảm ơn bạn đã đặt hàng tại Happy Tea!';

        // Gọi hàm gửi mail
        $this->send($email, 'Xác nhận đơn hàng ' . $order->order_code, $body); // Gọi hàm gửi mail thô để thực thi gửi thư xác nhận
    }

    /**
     * private: Chỉ dùng nội bộ.
     * sendAdminNewOrderAlert(Order $order): Gửi email thông báo cho Admin/Quản lý khi có đơn hàng mới trên hệ thống.
     * - Tham số Order $order: Đơn hàng mới cần thông báo.
     */
    private function sendAdminNewOrderAlert(Order $order): void
    {
        // Kiểm tra cấu hình: Nếu tính năng thông báo admin bị tắt thì dừng
        if (Setting::getValue('new_order_admin_notification_enabled', '1') != '1') {
            return;
        }

        // Lấy email nhận thông báo của Admin từ cấu hình (mặc định là admin@happytea.com nếu chưa thiết lập)
        $email = Setting::getValue('notification_email', 'admin@happytea.com');
        if (!$email) {
            return;
        }

        // Soạn thảo nội dung thông báo
        $body = "Có đơn hàng mới: {$order->order_code}\n"
            . "Khách hàng: {$order->customer_name} - {$order->customer_phone}\n"
            . 'Tổng tiền: ' . number_format((float) $order->final_amount, 0, ',', '.') . "đ\n"
            . 'Phương thức thanh toán: ' . strtoupper($order->payment_method) . "\n"
            . "Trạng thái thanh toán: {$order->payment_status}";

        $this->send($email, 'Đơn hàng mới ' . $order->order_code, $body); // Gọi hàm gửi mail thô để thực thi gửi thư cảnh báo
    }

    /**
     * private: Chỉ dùng nội bộ.
     * send(string $to, string $subject, string $body): Thực hiện việc gửi email thô (raw email) sử dụng Mail driver của Laravel.
     * - Tham số string $to: Địa chỉ email người nhận.
     * - Tham số string $subject: Tiêu đề email.
     * - Tham số string $body: Nội dung email.
     */
    private function send(string $to, string $subject, string $body): void
    {
        try {
            // Sử dụng Mail::raw để gửi email dạng văn bản thuần túy (Plain Text)
            Mail::raw($body, function ($message) use ($to, $subject) { // Gọi API Mail::raw gửi thư qua cấu hình SMTP
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            // Bắt mọi lỗi xảy ra khi gửi mail (ví dụ: cấu hình SMTP sai, server mail bị chặn mạng)
            // Ghi lỗi vào log để Admin kiểm tra và xử lý, tránh việc gửi mail lỗi làm crash chương trình đặt hàng
            Log::error('NotificationService: failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
