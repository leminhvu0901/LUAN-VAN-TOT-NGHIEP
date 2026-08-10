<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['shipping', 'cancelled'],
        'shipping' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];


    // Đơn tại quầy (pickup) không có bước giao hàng — khách nhận trực tiếp nên bỏ qua "shipping",
    // xác nhận xong là hoàn thành luôn. Giữ 'shipping' => ['completed', 'cancelled'] để tương thích
    // ngược cho các đơn pickup cũ (nếu có) đã lỡ ở trạng thái này từ trước khi đổi luồng.
    private const PICKUP_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'shipping' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    // Khởi tạo dịch vụ xử lý quy trình đơn hàng
    public function __construct(private readonly NotificationService $notifications) {}

    // Chuyển trạng thái đơn hàng và xử lý các điều kiện nghiệp vụ đi kèm
    public function transition(Order $order, string $newStatus, ?string $cancelReason = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $cancelReason) {
            // Khóa dòng dữ liệu đơn hàng để tránh xung đột ghi đè
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Nếu trạng thái mới trùng trạng thái cũ thì trả về luôn
            if ($newStatus === $locked->status) {
                return $locked;
            }

            // Chọn sơ đồ chuyển trạng thái dựa trên loại nhận hàng
            if ($locked->delivery_type === 'pickup') {
                $transitions = self::PICKUP_TRANSITIONS;
            } else {
                $transitions = self::TRANSITIONS;
            }

            // Kiểm tra trạng thái mới có hợp lệ trong sơ đồ không
            if (!in_array($newStatus, $transitions[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Không thể chuyển từ {$locked->status} sang {$newStatus}."]);
            }

            // Đơn tiền mặt tại quầy phải thu tiền trước khi xác nhận đơn hàng
            if ($newStatus === 'confirmed' && $locked->payment_method === 'cash' && $locked->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Cần xác nhận đã thu tiền mặt trước khi xác nhận đơn hàng.']);
            }

            // Xử lý khi đơn hàng bị hủy
            if ($newStatus === 'cancelled') {
                if (mb_strlen(trim((string) $cancelReason)) < 5) {
                    throw ValidationException::withMessages(['cancel_reason' => 'Vui lòng nhập lý do hủy ít nhất 5 ký tự.']);
                }
                if ($locked->payment_status === 'paid') {
                    throw ValidationException::withMessages(['status' => 'Đơn đã thanh toán phải được hoàn tiền trước khi hủy.']);
                }
                $this->applyCancelCleanup($locked, $cancelReason); // Gọi hàm nội bộ dọn dẹp khuyến mãi và hoàn điểm khi hủy đơn
            }

            // Xử lý khi đơn hàng hoàn thành
            if ($newStatus === 'completed') {
                if ($locked->payment_method === 'cod') {
                    $locked->payment_status = 'paid';
                    if (!$locked->paid_at) {
                        $locked->paid_at = now();
                    }
                }
                if ($locked->payment_status !== 'paid') {
                    throw ValidationException::withMessages(['status' => 'Chỉ được hoàn thành đơn đã thanh toán.']);
                }
                $this->awardPointsOnce($locked); // Gọi hàm nội bộ tích lũy điểm thưởng thành viên
                $locked->completed_at = now(); // Ghi nhận thời điểm hoàn thành để thống kê số đơn giao theo ngày/tuần/tháng/năm
            }

            $locked->status = $newStatus;
            $locked->save();
            return $locked->fresh();
        }, 3);
    }

    // Đánh dấu đơn hàng đã thanh toán thành công 
    public function markPaid(Order $order, string $transactionId, float $amount, ?\Carbon\Carbon $paidAtOverride = null): Order
    {
        $wasAlreadyPaid = false;

        $result = DB::transaction(function () use ($order, $transactionId, $amount, $paidAtOverride, &$wasAlreadyPaid) {
            // Khóa đơn hàng để đối soát thông tin thanh toán
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Kiểm tra số tiền thanh toán có khớp với hóa đơn không
            if (abs((float) $locked->final_amount - $amount) > 0.01) {
                throw ValidationException::withMessages(['amount' => 'Số tiền thanh toán không khớp đơn hàng.']);
            }

            // Nếu đơn hàng đã được ghi nhận thanh toán trước đó
            if ($locked->payment_status === 'paid') {
                $wasAlreadyPaid = true;
                return $locked;
            }

            // Cập nhật thông tin thanh toán thành công
            if ($paidAtOverride) {
                $paidAt = $paidAtOverride;
            } else {
                $paidAt = now();
            }

            $locked->forceFill([
                'payment_status' => 'paid',
                'payment_transaction_id' => $transactionId,
                'paid_at' => $paidAt,
            ])->save();
            return $locked;
        }, 3);

        // Gửi thông báo đặt hàng thành công nếu thanh toán lần đầu
        if (!$wasAlreadyPaid) {
            $this->notifications->orderPlaced($result); // Gửi email xác nhận đơn hàng mới cho khách và quản lý
        }

        return $result;
    }

    // Phân công nhân viên vận chuyển cho đơn hàng đã xác nhận
    public function assignDeliveryStaff(Order $order, int $deliveryStaffId, int $assignedByUserId): Order
    {
        return DB::transaction(function () use ($order, $deliveryStaffId, $assignedByUserId) {
            // Khóa đơn hàng để cập nhật thông tin người vận chuyển
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Chỉ phân công khi đơn đang ở trạng thái đã xác nhận
            if ($locked->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ có thể phân công giao hàng cho đơn đã xác nhận.',
                ]);
            }

            // Tìm và kiểm tra nhân viên vận chuyển có hợp lệ không
            $deliveryStaff = User::query()->find($deliveryStaffId);
            if (!$deliveryStaff || $deliveryStaff->role !== 'staff' || $deliveryStaff->staff_type !== 'delivery' || !$deliveryStaff->is_active) {
                throw ValidationException::withMessages([
                    'delivery_staff_id' => 'Nhân viên giao hàng không hợp lệ hoặc đã bị khóa.',
                ]);
            }

            // Lưu thông tin người giao hàng được gán
            $locked->forceFill([
                'delivery_staff_id' => $deliveryStaff->id,
                'assigned_by' => $assignedByUserId,
                'assigned_at' => now(),
            ])->save();

            return $locked->fresh();
        }, 3);
    }

    // Ghi nhận đơn hàng giao thất bại và tiến hành hủy đơn
    public function markDeliveryFailed(Order $order, string $reason, string $failureType): Order
    {
        return DB::transaction(function () use ($order, $reason, $failureType) {
            // Xác thực và khóa đơn hàng đang giao
            $locked = $this->lockShippingOrderAndValidate($order, $reason); // Xác thực tính hợp lệ trạng thái đang giao hàng

            // Xử lý các nghiệp vụ hoàn trả và hủy đơn
            $this->applyDeliveryFailedCleanup($locked, $reason, $failureType); // Hoàn nguyên tồn kho, điểm thưởng và hủy khuyến mãi
            $locked->save();
            return $locked->fresh();
        }, 3);
    }

    // Giao thất bại và ghi nhận thông tin hoàn tiền MoMo/VnPay
    public function markDeliveryFailedWithRefund(Order $order, string $reason, string $failureType, string $refundTransactionId): Order
    {
        return DB::transaction(function () use ($order, $reason, $failureType, $refundTransactionId) {
            // Xác thực và khóa đơn hàng đang giao
            $locked = $this->lockShippingOrderAndValidate($order, $reason); // Khóa và xác thực đơn giao hàng

            // Cập nhật trạng thái tiền đã hoàn nếu trước đó đã thanh toán
            if ($locked->payment_status === 'paid') {
                $locked->forceFill([
                    'payment_status' => 'refunded',
                    'refund_transaction_id' => $refundTransactionId,
                    'refunded_at' => now(),
                ]);
            }

            // Dọn dẹp khuyến mãi và hủy đơn hàng
            $this->applyDeliveryFailedCleanup($locked, $reason, $failureType); // Thực hiện hoàn nguyên khuyến mãi cho đơn giao thất bại
            $locked->save();
            return $locked->fresh();
        }, 3);
    }

    // Hoàn tiền online và hủy đơn hàng trong cùng một giao dịch
    public function refundAndCancel(Order $order, string $refundTransactionId, string $cancelReason): Order
    {
        return DB::transaction(function () use ($order, $refundTransactionId, $cancelReason) {
            // Khóa dòng dữ liệu để đối soát hoàn tiền
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Nếu đơn đã được hoàn tiền trước đó thì bỏ qua
            if ($locked->payment_status === 'refunded') {
                return $locked;
            }

            // Chỉ hoàn tiền cho những đơn đã thanh toán thành công
            if ($locked->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Đơn hàng không ở trạng thái đã thanh toán.']);
            }

            // Chỉ cho phép hủy đơn hoàn tiền khi đơn chưa đi giao
            if (!in_array($locked->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages(['status' => 'Chỉ có thể hoàn tiền cho đơn đang chờ xác nhận/đã xác nhận.']);
            }

            // Kiểm tra lý do hủy
            if (mb_strlen(trim($cancelReason)) < 5) {
                throw ValidationException::withMessages(['cancel_reason' => 'Vui lòng nhập lý do hủy ít nhất 5 ký tự.']);
            }

            // Cập nhật trạng thái đã hoàn tiền
            $locked->forceFill([
                'payment_status' => 'refunded',
                'refund_transaction_id' => $refundTransactionId,
                'refunded_at' => now(),
            ]);

            // Hoàn lại khuyến mãi/điểm tích lũy và chuyển trạng thái hủy
            $this->applyCancelCleanup($locked, $cancelReason); // Hoàn trả điểm, lượt dùng mã cho khách
            $locked->status = 'cancelled';
            $locked->save();

            return $locked->fresh();
        }, 3);
    }

    // Xác nhận đã thu tiền mặt COD từ shipper cho đơn hàng cụ thể
    public function settleCod(Order $order, int $settledByUserId): Order
    {
        return DB::transaction(function () use ($order, $settledByUserId) {
            // Khóa đơn hàng để đối soát tiền mặt
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // Chỉ áp dụng đối soát cho đơn COD đã hoàn thành giao hàng
            if ($locked->payment_method !== 'cod' || $locked->status !== 'completed') {
                throw ValidationException::withMessages([
                    'cod' => 'Chỉ đơn COD đã hoàn thành mới cần đối soát.',
                ]);
            }

            // Ghi nhận thời gian đối soát và người thực hiện đối soát
            if (!$locked->cod_settled_at) {
                $locked->forceFill(['cod_settled_at' => now(), 'cod_settled_by' => $settledByUserId])->save();
            }

            return $locked->fresh();
        }, 3);
    }

    // Đối soát tiền mặt toàn bộ đơn hàng COD chưa quyết toán của nhân viên giao hàng
    public function settleAllCodForDeliveryStaff(int $deliveryStaffId, int $settledByUserId): int
    {
        return DB::transaction(function () use ($deliveryStaffId, $settledByUserId) {
            // Tìm danh sách tất cả các đơn COD hoàn thành chưa đối soát của shipper
            $orders = Order::query()
                ->where('delivery_staff_id', $deliveryStaffId)
                ->where('payment_method', 'cod')
                ->where('status', 'completed')
                ->whereNull('cod_settled_at')
                ->lockForUpdate()
                ->get();

            // Đánh dấu đối soát cho từng đơn hàng
            foreach ($orders as $order) {
                $order->forceFill(['cod_settled_at' => now(), 'cod_settled_by' => $settledByUserId])->save();
            }

            return $orders->count();
        }, 3);
    }

    // Tự động hủy các đơn hàng online bị treo quá lâu không thanh toán
    public function cancelStalePendingPayments(int $minutes = 15): int
    {
        // Lấy danh sách các đơn VNPay quá hạn chưa thanh toán
        $staleOrders = Order::query()
            ->where('payment_method', 'vnpay')
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->get();

        $cancelledCount = 0;
        // Thực hiện hủy từng đơn hàng
        foreach ($staleOrders as $order) {
            try {
                $this->transition($order, 'cancelled', 'Tự động hủy do quá thời gian chờ thanh toán.'); // Chuyển trạng thái đơn sang Đã hủy
                $cancelledCount++;
            } catch (ValidationException $e) {
                continue;
            }
        }

        return $cancelledCount;
    }

    // Hoàn lại lượt dùng khuyến mãi và điểm tích lũy của khách hàng khi hủy đơn
    private function applyCancelCleanup(Order $locked, string $cancelReason): void
    {
        // Hoàn trả lượt dùng mã khuyến mãi nếu đơn có áp dụng
        if ($locked->promotion_id) {
            DB::table('promotions')->where('id', $locked->promotion_id)->where('used_count', '>', 0)->decrement('used_count'); // Trừ số lượt đã dùng của mã
        }

        // Hoàn trả điểm tích lũy khách đã tiêu dùng cho đơn hàng này
        if ((int) $locked->points_redeemed > 0 && $locked->user_id) {
            User::query()->lockForUpdate()->where('id', $locked->user_id) // Khóa dòng user
                ->increment('points', (int) $locked->points_redeemed); // Cộng lại điểm tích lũy của khách
        }
        $locked->cancel_reason = trim($cancelReason);
    }

    // Khóa đơn hàng đang giao và kiểm tra tính hợp lệ trước khi shipper báo lỗi
    private function lockShippingOrderAndValidate(Order $order, string $reason): Order
    {
        $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

        // Chỉ cho phép báo lỗi đối với đơn hàng đang trong trạng thái giao
        if ($locked->status !== 'shipping') {
            throw ValidationException::withMessages([
                'status' => 'Chỉ đơn đang giao mới được đánh dấu giao thất bại.',
            ]);
        }

        // Kiểm tra lý do giao thất bại
        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages([
                'delivery_failed_reason' => 'Vui lòng nhập lý do giao thất bại ít nhất 5 ký tự.',
            ]);
        }

        return $locked;
    }

    // Cập nhật thông tin giao hàng thất bại và hoàn trả ưu đãi cho đơn hàng
    private function applyDeliveryFailedCleanup(Order $locked, string $reason, string $failureType): void
    {
        // Hoàn lại lượt dùng mã khuyến mãi
        if ($locked->promotion_id) {
            DB::table('promotions')->where('id', $locked->promotion_id)->where('used_count', '>', 0)->decrement('used_count');
        }

        // Hoàn lại điểm tích lũy
        if ((int) $locked->points_redeemed > 0 && $locked->user_id) {
            User::query()->lockForUpdate()->where('id', $locked->user_id)
                ->increment('points', (int) $locked->points_redeemed);
        }

        $reason = trim($reason);
        // Lưu thông tin giao hàng thất bại chi tiết
        $locked->forceFill([
            'status' => 'cancelled',
            'cancel_reason' => 'Giao hàng thất bại: ' . $reason,
            'delivery_failed_reason' => $reason,
            'delivery_failure_type' => $failureType,
            'delivery_failed_at' => now(),
        ]);
    }

    // Tự động cộng điểm tích lũy thành viên dựa trên giá trị đơn hàng hoàn thành
    private function awardPointsOnce(Order $order): void
    {
        // Không cộng điểm nếu đơn không có tài khoản thành viên hoặc đã được cộng rồi
        if (!$order->user_id || (int) $order->loyalty_points_awarded > 0) {
            return;
        }

        // Kiểm tra tính năng tích điểm có đang được kích hoạt không
        $loyaltyEnabled = (bool) \App\Models\Setting::getValue('loyalty_enabled', true);
        if (!$loyaltyEnabled) {
            return;
        }

        // Lấy hạn mức quy đổi điểm tích lũy từ cấu hình hệ thống
        $moneyPerPoint = (float) \App\Models\Setting::getValue('loyalty_money_per_point', 10000);
        if ($moneyPerPoint <= 0) {
            return;
        }

        // Tính số điểm tích lũy nhận được
        $points = (int) floor((float) $order->final_amount / $moneyPerPoint);
        if ($points <= 0) {
            return;
        }

        // Tiến hành cộng điểm vào tài khoản thành viên
        $user = User::query()->lockForUpdate()->find($order->user_id); // Khóa dòng user trong DB
        if (!$user) {
            return;
        }
        $user->awardPoints($order->final_amount); // Gọi phương thức awardPoints của User model để thực thi cộng điểm
        $order->loyalty_points_awarded = $points;
    }
}
