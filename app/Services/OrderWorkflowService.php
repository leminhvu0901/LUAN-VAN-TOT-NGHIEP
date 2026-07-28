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

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly NotificationService $notifications,
    ) {}

    public function transition(Order $order, string $newStatus, ?string $cancelReason = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $cancelReason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($newStatus === $locked->status) return $locked;
            $transitions = $locked->delivery_type === 'pickup' ? self::PICKUP_TRANSITIONS : self::TRANSITIONS;
            if (!in_array($newStatus, $transitions[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Không thể chuyển từ {$locked->status} sang {$newStatus}."]);
            }

            if ($newStatus === 'cancelled') {
                if (mb_strlen(trim((string) $cancelReason)) < 5) {
                    throw ValidationException::withMessages(['cancel_reason' => 'Vui lòng nhập lý do hủy ít nhất 5 ký tự.']);
                }
                if ($locked->payment_status === 'paid') {
                    throw ValidationException::withMessages(['status' => 'Đơn đã thanh toán phải được hoàn tiền trước khi hủy.']);
                }
                $this->applyCancelCleanup($locked, $cancelReason);
            }

            if ($newStatus === 'completed') {
                if ($locked->payment_method === 'cod') {
                    $locked->payment_status = 'paid';
                    $locked->paid_at = $locked->paid_at ?: now();
                }
                if ($locked->payment_status !== 'paid') {
                    throw ValidationException::withMessages(['status' => 'Chỉ được hoàn thành đơn đã thanh toán.']);
                }
                $this->awardPointsOnce($locked);
            }

            $locked->status = $newStatus;
            $locked->save();
            return $locked->fresh();
        }, 3);
    }

    public function markPaid(Order $order, string $transactionId, float $amount): Order
    {
        $wasAlreadyPaid = false;

        $result = DB::transaction(function () use ($order, $transactionId, $amount, &$wasAlreadyPaid) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (abs((float) $locked->final_amount - $amount) > 0.01) {
                throw ValidationException::withMessages(['amount' => 'Số tiền thanh toán không khớp đơn hàng.']);
            }
            if ($locked->payment_status === 'paid') {
                $wasAlreadyPaid = true;
                return $locked;
            }
            $locked->forceFill([
                'payment_status' => 'paid',
                'payment_transaction_id' => $transactionId,
                'paid_at' => now(),
            ])->save();
            return $locked;
        }, 3);

        if (!$wasAlreadyPaid) {
            $this->notifications->orderPlaced($result);
        }

        return $result;
    }

    /**
     * Lễ tân/admin phân công nhân viên vận chuyển cho một đơn đã xác nhận.
     */
    public function assignDeliveryStaff(Order $order, int $deliveryStaffId, int $assignedByUserId): Order
    {
        return DB::transaction(function () use ($order, $deliveryStaffId, $assignedByUserId) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ có thể phân công giao hàng cho đơn đã xác nhận.',
                ]);
            }

            $deliveryStaff = User::query()->find($deliveryStaffId);
            if (!$deliveryStaff || $deliveryStaff->role !== 'staff' || $deliveryStaff->staff_type !== 'delivery' || !$deliveryStaff->is_active) {
                throw ValidationException::withMessages([
                    'delivery_staff_id' => 'Nhân viên giao hàng không hợp lệ hoặc đã bị khóa.',
                ]);
            }

            $locked->forceFill([
                'delivery_staff_id' => $deliveryStaff->id,
                'assigned_by' => $assignedByUserId,
                'assigned_at' => now(),
            ])->save();

            return $locked->fresh();
        }, 3);
    }

    /**
     * Nhân viên vận chuyển đánh dấu giao hàng thất bại. Theo quyết định nghiệp vụ đã duyệt:
     * đơn được hủy thẳng (status -> cancelled) NGAY CẢ KHI đã thanh toán trước (vd MoMo) —
     * cố ý bỏ qua rule "phải hoàn tiền trước khi hủy" trong transition() chỉ cho nhánh này.
     * $failureType ('damaged'|'customer_unreachable'|'other') chỉ dùng để audit ở đây — quyết định
     * có hoàn tiền hay không nằm ở Delivery\OrderController::fail() (chỉ 'damaged' mới gọi
     * markDeliveryFailedWithRefund() thay vì method này).
     */
    public function markDeliveryFailed(Order $order, string $reason, string $failureType): Order
    {
        return DB::transaction(function () use ($order, $reason, $failureType) {
            $locked = $this->lockShippingOrderAndValidate($order, $reason);
            $this->applyDeliveryFailedCleanup($locked, $reason, $failureType);
            $locked->save();
            return $locked->fresh();
        }, 3);
    }

    /**
     * Như markDeliveryFailed(), nhưng dùng cho case shipper báo "hàng hư hỏng/đổ vỡ" trên đơn MoMo đã
     * thanh toán VÀ hoàn tiền MoMo đã gọi thành công trước đó (transId đã có sẵn) — chỉ còn việc ghi
     * nhận hoàn tiền + hủy đơn nguyên tử trong cùng 1 transaction.
     */
    public function markDeliveryFailedWithRefund(Order $order, string $reason, string $failureType, string $refundTransactionId): Order
    {
        return DB::transaction(function () use ($order, $reason, $failureType, $refundTransactionId) {
            $locked = $this->lockShippingOrderAndValidate($order, $reason);

            if ($locked->payment_status === 'paid') {
                $locked->forceFill([
                    'payment_status' => 'refunded',
                    'refund_transaction_id' => $refundTransactionId,
                    'refunded_at' => now(),
                ]);
            }

            $this->applyDeliveryFailedCleanup($locked, $reason, $failureType);
            $locked->save();
            return $locked->fresh();
        }, 3);
    }

    /**
     * Hoàn tiền MoMo (transId đã được xác nhận thành công qua MomoController::requestRefund() trước
     * khi gọi vào đây) rồi hủy đơn trong cùng 1 transaction nguyên tử. Đối xứng với markPaid(): chỉ
     * nhận transactionId đã có sẵn, KHÔNG tự gọi MoMo (tránh giữ lock DB trong lúc chờ mạng).
     */
    public function refundAndCancel(Order $order, string $refundTransactionId, string $cancelReason): Order
    {
        return DB::transaction(function () use ($order, $refundTransactionId, $cancelReason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->payment_status === 'refunded') {
                // Đã hoàn tiền trước đó (double-submit/race) — coi như thành công, không xử lý lại.
                return $locked;
            }
            if ($locked->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Đơn hàng không ở trạng thái đã thanh toán.']);
            }
            if (!in_array($locked->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages(['status' => 'Chỉ có thể hoàn tiền cho đơn đang chờ xác nhận/đã xác nhận.']);
            }
            if (mb_strlen(trim($cancelReason)) < 5) {
                throw ValidationException::withMessages(['cancel_reason' => 'Vui lòng nhập lý do hủy ít nhất 5 ký tự.']);
            }

            $locked->forceFill([
                'payment_status' => 'refunded',
                'refund_transaction_id' => $refundTransactionId,
                'refunded_at' => now(),
            ]);

            $this->applyCancelCleanup($locked, $cancelReason);
            $locked->status = 'cancelled';
            $locked->save();

            return $locked->fresh();
        }, 3);
    }

    /**
     * Lễ tân/admin xác nhận ĐÃ NHẬN LẠI tiền mặt COD từ một đơn cụ thể mà nhân viên vận chuyển vừa
     * nộp quầy. Chỉ áp dụng cho đơn COD đã hoàn thành (tiền đã thực sự được thu từ khách).
     */
    public function settleCod(Order $order, int $settledByUserId): Order
    {
        return DB::transaction(function () use ($order, $settledByUserId) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($locked->payment_method !== 'cod' || $locked->status !== 'completed') {
                throw ValidationException::withMessages([
                    'cod' => 'Chỉ đơn COD đã hoàn thành mới cần đối soát.',
                ]);
            }

            if (!$locked->cod_settled_at) {
                $locked->forceFill(['cod_settled_at' => now(), 'cod_settled_by' => $settledByUserId])->save();
            }

            return $locked->fresh();
        }, 3);
    }

    /**
     * Xác nhận nộp quầy MỘT LẦN cho TOÀN BỘ đơn COD đã hoàn thành nhưng chưa đối soát của một nhân
     * viên vận chuyển — dùng khi shipper đưa đủ một cục tiền cho các đơn đã giao trong ca/ngày.
     * Trả về số đơn vừa được đánh dấu đã nộp.
     */
    public function settleAllCodForDeliveryStaff(int $deliveryStaffId, int $settledByUserId): int
    {
        return DB::transaction(function () use ($deliveryStaffId, $settledByUserId) {
            $orders = Order::query()
                ->where('delivery_staff_id', $deliveryStaffId)
                ->where('payment_method', 'cod')
                ->where('status', 'completed')
                ->whereNull('cod_settled_at')
                ->lockForUpdate()
                ->get();

            foreach ($orders as $order) {
                $order->forceFill(['cod_settled_at' => now(), 'cod_settled_by' => $settledByUserId])->save();
            }

            return $orders->count();
        }, 3);
    }

    /**
     * Tự động hủy các đơn "chờ thanh toán" MoMo bị treo quá lâu (khách/lễ tân mở cổng MoMo rồi bỏ
     * dở, không hủy tay). Quan trọng nhất với đơn tại quầy vì loại đơn này đã trừ tồn kho thật ngay
     * lúc tạo (OrderService::create -> reserveForOrder) — treo mãi sẽ khóa cứng tồn kho không ai
     * bán được. transition() sang 'cancelled' tự giải phóng tồn kho + hoàn used_count khuyến mãi.
     * Gọi cơ hội chủ nghĩa từ trang danh sách đơn (không cần cron) + có thể chạy qua lệnh Artisan
     * theo lịch để dọn cả khi không ai mở trang.
     */
    public function cancelStalePendingPayments(int $minutes = 15): int
    {
        $staleOrders = Order::query()
            ->where('payment_method', 'momo')
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->get();

        $cancelledCount = 0;
        foreach ($staleOrders as $order) {
            try {
                $this->transition($order, 'cancelled', 'Tự động hủy do quá thời gian chờ thanh toán MoMo.');
                $cancelledCount++;
            } catch (ValidationException $e) {
                // Đơn vừa được thanh toán/hủy đúng lúc job chạy (race condition) -> bỏ qua, không phải lỗi thật.
                continue;
            }
        }

        return $cancelledCount;
    }

    /**
     * Dọn dẹp dùng chung khi một đơn chuyển sang 'cancelled': giải phóng tồn kho, hoàn used_count
     * khuyến mãi, hoàn điểm tích lũy đã dùng, set cancel_reason. Tách từ transition() để refundAndCancel()
     * dùng lại y hệt — không đổi hành vi của transition().
     */
    private function applyCancelCleanup(Order $locked, string $cancelReason): void
    {
        $this->inventory->releaseForOrder($locked);
        if ($locked->promotion_id) {
            DB::table('promotions')->where('id', $locked->promotion_id)->where('used_count', '>', 0)->decrement('used_count');
        }
        // Hoàn lại điểm tích lũy đã dùng (nếu có) cho đúng khách đứng tên đơn — trước đây đơn
        // hủy không hoàn điểm, khiến khách bị trừ điểm oan cho đơn không thành.
        if ((int) $locked->points_redeemed > 0 && $locked->user_id) {
            User::query()->lockForUpdate()->where('id', $locked->user_id)
                ->increment('points', (int) $locked->points_redeemed);
        }
        $locked->cancel_reason = trim($cancelReason);
    }

    private function lockShippingOrderAndValidate(Order $order, string $reason): Order
    {
        $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

        if ($locked->status !== 'shipping') {
            throw ValidationException::withMessages([
                'status' => 'Chỉ đơn đang giao mới được đánh dấu giao thất bại.',
            ]);
        }

        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages([
                'delivery_failed_reason' => 'Vui lòng nhập lý do giao thất bại ít nhất 5 ký tự.',
            ]);
        }

        return $locked;
    }

    private function applyDeliveryFailedCleanup(Order $locked, string $reason, string $failureType): void
    {
        $this->inventory->releaseForOrder($locked);
        if ($locked->promotion_id) {
            DB::table('promotions')->where('id', $locked->promotion_id)->where('used_count', '>', 0)->decrement('used_count');
        }
        if ((int) $locked->points_redeemed > 0 && $locked->user_id) {
            User::query()->lockForUpdate()->where('id', $locked->user_id)
                ->increment('points', (int) $locked->points_redeemed);
        }

        $reason = trim($reason);
        $locked->forceFill([
            'status' => 'cancelled',
            'cancel_reason' => 'Giao hàng thất bại: ' . $reason,
            'delivery_failed_reason' => $reason,
            'delivery_failure_type' => $failureType,
            'delivery_failed_at' => now(),
        ]);
    }

    private function awardPointsOnce(Order $order): void
    {
        if (!$order->user_id || (int) $order->loyalty_points_awarded > 0) return;
        
        $loyaltyEnabled = (bool) \App\Models\Setting::getValue('loyalty_enabled', true);
        if (!$loyaltyEnabled) return;

        $moneyPerPoint = (float) \App\Models\Setting::getValue('loyalty_money_per_point', 10000);
        if ($moneyPerPoint <= 0) return;

        $points = (int) floor((float) $order->final_amount / $moneyPerPoint);
        if ($points <= 0) return;

        $user = User::query()->lockForUpdate()->find($order->user_id);
        if (!$user) return;
        $user->awardPoints($order->final_amount);
        $order->loyalty_points_awarded = $points;
    }
}
