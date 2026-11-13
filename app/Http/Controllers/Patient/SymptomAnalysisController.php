<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\SymptomAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SymptomAnalysisController extends Controller
{
    /**
     * Phân tích mô tả triệu chứng của bệnh nhân và trả về gợi ý chuyên khoa (T3.1).
     */
    public function __invoke(Request $request, SymptomAnalysisService $analysisService): JsonResponse
    {
        $validated = $request->validate(
            [
                'symptoms_text' => ['required', 'string', 'min:3', 'max:1000'],
            ],
            [
                'symptoms_text.required' => 'Vui lòng nhập mô tả triệu chứng hoặc biểu hiện sức khỏe của bạn.',
                'symptoms_text.min' => 'Mô tả triệu chứng phải có ít nhất 3 ký tự.',
                'symptoms_text.max' => 'Mô tả triệu chứng không được vượt quá 1000 ký tự.',
            ]
        );

        $result = $analysisService->analyze($validated['symptoms_text']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
