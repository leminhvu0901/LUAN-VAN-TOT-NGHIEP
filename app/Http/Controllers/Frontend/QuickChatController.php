<?php

namespace App\Http\Controllers\Frontend;

use App\Services\QuickChatService;
use Illuminate\Http\Request;

// Controller mỏng cho chatbox trả lời nhanh — toàn bộ thuật toán nhận diện nằm ở QuickChatService.
// Chỉ đọc dữ liệu công khai, không nhận user_id từ request, không ghi log nội dung câu hỏi.
class QuickChatController
{
    public function ask(Request $request, QuickChatService $service)
    {
        $data = $request->validate([
            'question' => ['nullable', 'string', 'max:300'],
            'intent' => ['nullable', 'string', 'max:50'],
        ]);

        if (empty($data['question']) && empty($data['intent'])) {
            return response()->json([
                'intent' => null,
                'answer' => 'Vui lòng nhập câu hỏi.',
                'items' => [],
                'action_url' => null,
                'suggestions' => [],
            ]);
        }

        if (!empty($data['intent'])) {
            return response()->json($service->askByIntent($data['intent']));
        }

        return response()->json($service->ask($data['question']));
    }
}
