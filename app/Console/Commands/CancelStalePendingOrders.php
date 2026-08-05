<?php

namespace App\Console\Commands;

use App\Services\OrderWorkflowService;
use Illuminate\Console\Command;

class CancelStalePendingOrders extends Command
{
    protected $signature = 'orders:cancel-stale-pending {--minutes=15 : Đơn chờ thanh toán VNPay quá số phút này sẽ bị hủy}';

    protected $description = 'Tự động hủy các đơn hàng còn "ờ thanh toán" VNPay bị treo quá lâu, giải phóng tồn kho đã trừ trước';

    public function handle(OrderWorkflowService $orderWorkflow): int
    {
        $minutes = (int) $this->option('minutes');
        $cancelled = $orderWorkflow->cancelStalePendingPayments($minutes);

        $this->info("Đã tự động hủy {$cancelled} đơn chờ thanh toán VNPay quá {$minutes} phút.");

        return self::SUCCESS;
    }
}
