<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// "Dịch vụ gửi thông báo, Email".
class NotificationService
{
    // GỬI THÔNG BÁO
    public function orderPlaced(Order $order): void
    {
        $this->sendCustomerConfirmation($order);
        $this->sendAdminNewOrderAlert($order);
    }

    //Gửi email xác nhận chi tiết đơn hàng
    private function sendCustomerConfirmation(Order $order): void
    {
        if (Setting::getValue('order_confirmation_email_enabled', '1') != '1') {
            return;
        }
        $email = $order->user?->email ?: User::query()->whereKey($order->user_id)->value('email');
        if (!$email) {
            return;
        }
        $body = "Chào {$order->customer_name},\n\n"
            . "Đơn hàng {$order->order_code} của bạn đã được ghi nhận thành công.\n\n"
            . $this->buildOrderItemsSection($order);
        $body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . 'Tổng tiền: ' . number_format((float) $order->final_amount, 0, ',', '.') . "đ\n"
            . 'Phương thức thanh toán: ' . strtoupper($order->payment_method) . "\n"
            . "Địa chỉ giao: {$order->delivery_address}\n\n"
            . "Cảm ơn bạn đã đặt hàng tại Happy Tea! 🍵\n"
            . 'Chúng tôi sẽ chuẩn bị đơn ngay và liên hệ nếu cần thêm thông tin.';
        $this->send($email, 'Xác nhận đơn hàng ' . $order->order_code, $body);
    }

    //Gửi email yêu cầu Admin phê duyệt
    public function sendAdminApprovalRequest(Order $order): void
    {
        $email = Setting::getValue('notification_email', 'admin@happytea.com');
        if (!$email) {
            return;
        }

        $amount = number_format((float) $order->final_amount, 0, ',', '.');

        $body = "⚠️ YÊU CẦU PHÊ DUYỆT ĐƠN HÀNG GIÁ TRỊ LỚN\n\n"
            . "Đơn hàng {$order->order_code} có giá trị {$amount}đ cần Admin phê duyệt trước khi xử lý tiếp.\n\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "Khách hàng : {$order->customer_name}\n"
            . "Điện thoại : {$order->customer_phone}\n"
            . "Địa chỉ    : {$order->delivery_address}\n"
            . "Tổng tiền  : {$amount}đ\n"
            . "Thanh toán : " . strtoupper($order->payment_method) . "\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . $this->buildOrderItemsSection($order);

        $this->send($email, "[Cần duyệt] Đơn hàng {$order->order_code} — {$amount}đ", $body);
    }

    //Dựng đoạn nội dung liệt kê chi tiết
    private function buildOrderItemsSection(Order $order): string
    {
        $items = $order->items()->with('product')->get();

        $section = "------------------------------\n"
            . "🧋 CHI TIẾT SẢN PHẨM\n"
            . "------------------------------------\n";

        foreach ($items as $i => $item) {
            $productName = $item->product?->name ?? 'Sản phẩm';
            $details = [];
            if ($item->size_name)
                $details[] = 'Size ' . $item->size_name;
            if ($item->sugar_level !== null)
                $details[] = 'Đường ' . $item->sugar_level . '%';
            if ($item->ice_level)
                $details[] = 'Đá: ' . $item->ice_level;
            $toppings = collect($item->options ?? [])
                ->filter()
                ->implode(', ');

            $lineTotal = number_format((float) $item->unit_price * $item->quantity, 0, ',', '.');

            $section .= ($i + 1) . ". {$productName}\n";
            if (!empty($details)) {
                $section .= '   • ' . implode(' · ', $details) . "\n";
            }
            if ($toppings) {
                $section .= "   • Topping: {$toppings}\n";
            }
            $section .= "   • Số lượng  : {$item->quantity}\n"
                . '   • Đơn giá   : ' . number_format((float) $item->unit_price, 0, ',', '.') . "đ\n"
                . "   • Thành tiền: {$lineTotal}đ\n\n";
        }

        return $section;
    }

    //gửi email thông báo cho Admin
    private function sendAdminNewOrderAlert(Order $order): void
    {
        if (Setting::getValue('new_order_admin_notification_enabled', '1') != '1') {
            return;
        }
        $email = Setting::getValue('notification_email', 'admin@happytea.com');
        if (!$email) {
            return;
        }
        $body = "Có đơn hàng mới: {$order->order_code}\n"
            . "Khách hàng: {$order->customer_name} - {$order->customer_phone}\n"
            . 'Tổng tiền: ' . number_format((float) $order->final_amount, 0, ',', '.') . "đ\n"
            . 'Phương thức thanh toán: ' . strtoupper($order->payment_method) . "\n"
            . "Trạng thái thanh toán: {$order->payment_status}";

        $this->send($email, 'Đơn hàng mới ' . $order->order_code, $body); // Gọi hàm gửi mail thô để thực thi gửi thư cảnh báo
    }

    //Thực hiện việc gửi email
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
