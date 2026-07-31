<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function orderPlaced(Order $order): void
    {
        $this->sendCustomerConfirmation($order);
        $this->sendAdminNewOrderAlert($order);
    }

    private function sendCustomerConfirmation(Order $order): void
    {
        if (Setting::getValue('order_confirmation_email_enabled', '1') != '1') return;

        $email = $order->user?->email ?: User::query()->whereKey($order->user_id)->value('email');
        if (!$email) return;

        $body = "Chào {$order->customer_name},\n\n"
            . "Đơn hàng {$order->order_code} của bạn đã được ghi nhận thành công.\n"
            . 'Tổng tiền: ' . number_format((float) $order->final_amount, 0, ',', '.') . "đ\n"
            . 'Phương thức thanh toán: ' . strtoupper($order->payment_method) . "\n"
            . "Địa chỉ giao: {$order->delivery_address}\n\n"
            . 'Cảm ơn bạn đã đặt hàng tại Happy Tea!';

        $this->send($email, 'Xác nhận đơn hàng ' . $order->order_code, $body);
    }

    private function sendAdminNewOrderAlert(Order $order): void
    {
        if (Setting::getValue('new_order_admin_notification_enabled', '1') != '1') return;

        $email = Setting::getValue('notification_email', 'admin@happytea.com');
        if (!$email) return;

        $body = "Có đơn hàng mới: {$order->order_code}\n"
            . "Khách hàng: {$order->customer_name} - {$order->customer_phone}\n"
            . 'Tổng tiền: ' . number_format((float) $order->final_amount, 0, ',', '.') . "đ\n"
            . 'Phương thức thanh toán: ' . strtoupper($order->payment_method) . "\n"
            . "Trạng thái thanh toán: {$order->payment_status}";

        $this->send($email, 'Đơn hàng mới ' . $order->order_code, $body);
    }

    private function send(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
            Log::error('NotificationService: failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
