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

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly NotificationService $notifications,
    ) {}

    public function transition(Order $order, string $newStatus, ?string $cancelReason = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $cancelReason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($newStatus === $locked->status) return $locked;
            if (!in_array($newStatus, self::TRANSITIONS[$locked->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => "Không thể chuyển từ {$locked->status} sang {$newStatus}."]);
            }

            if ($newStatus === 'cancelled') {
                if (mb_strlen(trim((string) $cancelReason)) < 5) {
                    throw ValidationException::withMessages(['cancel_reason' => 'Vui lòng nhập lý do hủy ít nhất 5 ký tự.']);
                }
                if ($locked->payment_status === 'paid') {
                    throw ValidationException::withMessages(['status' => 'Đơn đã thanh toán phải được hoàn tiền trước khi hủy.']);
                }
                $this->inventory->releaseForOrder($locked);
                if ($locked->promotion_id) {
                    DB::table('promotions')->where('id', $locked->promotion_id)->where('used_count', '>', 0)->decrement('used_count');
                }
                $locked->cancel_reason = trim($cancelReason);
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
