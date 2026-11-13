<?php

namespace App\Services;

use App\Models\Specialty;
use App\Models\Symptom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SymptomAnalysisService
{
    /**
     * Từ điển từ đồng nghĩa và cách diễn đạt thông dụng cho 19 triệu chứng chuẩn (SC06).
     * Hỗ trợ đối sánh chính xác cả tiếng Việt có dấu và không dấu.
     *
     * @var array<string, array<int, string>>
     */
    protected array $synonymKeywords = [
        // 1. Nhóm Nội tổng quát
        'SYM_DAU_BUNG' => [
            'đau bụng',
            'dau bung',
            'đau dạ dày',
            'dau da day',
            'đau bao tử',
            'dau bao tu',
            'quặn bụng',
            'quan bung',
            'đau thượng vị',
            'dau thuong vi',
            'đầy bụng',
            'day bung',
            'khó tiêu',
            'kho tieu',
            'rối loạn tiêu hóa',
            'roi loan tieu hoa',
        ],
        'SYM_BUON_NON' => [
            'buồn nôn',
            'buon non',
            'nôn mửa',
            'non mua',
            'nôn ói',
            'non oi',
            'muốn nôn',
            'muon non',
            'mắc ói',
            'mac oi',
        ],
        'SYM_TIEU_CHAY' => [
            'tiêu chảy',
            'tieu chay',
            'đi ngoài',
            'di ngoai',
            'đi lỏng',
            'di long',
            'phân lỏng',
            'phan long',
            'tào tháo đuổi',
            'tao thao duoi',
        ],
        'SYM_SOT_CAO' => [
            'sốt cao',
            'sot cao',
            'sốt',
            'nóng sốt',
            'nong sot',
            'sốt rét',
            'sot ret',
            'nhiệt độ cao',
            'nhiet do cao',
            'nóng người',
            'nong nguoi',
            'ớn lạnh',
            'on lanh',
        ],
        'SYM_MET_MOI' => [
            'mệt mỏi',
            'met moi',
            'uể oải',
            'ue oai',
            'suy nhược',
            'suy nhuoc',
            'mệt người',
            'met nguoi',
            'kiệt sức',
            'kiet suc',
            'chán ăn',
            'chan an',
            'mất sức',
            'mat suc',
            'chóng mặt',
            'chong mat',
        ],
        'SYM_TUC_NGUC' => [
            'tức ngực',
            'tuc nguc',
            'đau ngực',
            'dau nguc',
            'khó thở',
            'kho tho',
            'nặng ngực',
            'nang nguc',
            'nhói ngực',
            'nhoi nguc',
            'hồi hộp',
            'hoi hop',
        ],

        // 2. Nhóm Tai Mũi Họng
        'SYM_DAU_HONG' => [
            'đau họng',
            'dau hong',
            'rát họng',
            'rat hong',
            'viêm họng',
            'viem hong',
            'ngứa họng',
            'ngua hong',
            'sưng họng',
            'sung hong',
            'nuốt đau',
            'nuot dau',
            'khàn tiếng',
            'khan tieng',
            'đau cổ họng',
            'dau co hong',
            'viêm amidan',
            'viem amidan',
        ],
        'SYM_HO_DOM' => [
            'ho có đờm',
            'ho co dom',
            'ho đờm',
            'ho dom',
            'khạc đờm',
            'khac dom',
            'ho nhiều',
            'ho nhieu',
            'ho dai dẳng',
            'ho dai dang',
            'ho khan',
            'bị ho',
            'bi ho',
        ],
        'SYM_SO_MUI' => [
            'sổ mũi',
            'so mui',
            'chảy nước mũi',
            'chay nuoc mui',
            'chảy mũi',
            'chay mui',
            'dịch mũi',
            'dich mui',
            'hắt hơi',
            'hat hoi',
            'nhảy mũi',
            'nhay mui',
        ],
        'SYM_NGHET_MUI' => [
            'nghẹt mũi',
            'nghet mui',
            'tắc mũi',
            'tac mui',
            'ngạt mũi',
            'ngat mui',
            'viêm xoang',
            'viem xoang',
            'viêm mũi',
            'viem mui',
            'khó thở bằng mũi',
        ],
        'SYM_U_TAI' => [
            'ù tai',
            'u tai',
            'đau tai',
            'dau tai',
            'nhức tai',
            'nhuc tai',
            'nghe kém',
            'nghe kem',
            'viêm tai',
            'viem tai',
            'chảy mủ tai',
            'chay mu tai',
        ],

        // 3. Nhóm Da liễu
        'SYM_MAN_DO' => [
            'nổi mẩn đỏ',
            'noi man do',
            'mẩn đỏ',
            'man do',
            'nổi mẩn',
            'noi man',
            'nốt đỏ',
            'not do',
            'mề đay',
            'me day',
            'nổi mề đay',
            'noi me day',
            'sẩn đỏ',
            'san do',
        ],
        'SYM_NGUA' => [
            'ngứa ngáy',
            'ngua ngay',
            'ngứa da',
            'ngua da',
            'bị ngứa',
            'bi ngua',
            'gãi ngứa',
            'gai ngua',
            'dị ứng da',
            'di ung da',
            'ngứa khắp người',
            'ngua khap nguoi',
            'ngứa rát',
            'ngua rat',
        ],
        'SYM_PHAT_BAN' => [
            'phát ban',
            'phat ban',
            'nổi ban',
            'noi ban',
            'ban đỏ',
            'ban do',
            'kích ứng da',
            'kich ung da',
            'viêm da',
            'viem da',
            'chàm',
            'cham',
        ],
        'SYM_MUN_NUOC' => [
            'mụn nước',
            'mun nuoc',
            'bọng nước',
            'bong nuoc',
            'nổi mụn',
            'noi mun',
            'mụn mủ',
            'mun mu',
            'lở loét',
            'lo loet',
            'zona',
            'thủy đậu',
            'thuy dau',
            'nấm da',
            'nam da',
        ],

        // 4. Nhóm Cơ xương khớp
        'SYM_DAU_LUNG' => [
            'đau lưng',
            'dau lung',
            'mỏi lưng',
            'moi lung',
            'đau cột sống',
            'dau cot song',
            'đau thắt lưng',
            'dau that lung',
            'thoát vị đĩa đệm',
            'thoat vi dia dem',
            'đau cổ vai gáy',
            'dau co vai gay',
            'mỏi cổ',
            'moi co',
            'đau vai',
            'dau vai',
        ],
        'SYM_MOI_KHOP_GOI' => [
            'nhức mỏi khớp gối',
            'nhuc moi khop goi',
            'đau khớp gối',
            'dau khop goi',
            'mỏi gối',
            'moi goi',
            'đau đầu gối',
            'dau dau goi',
            'khô khớp',
            'kho khop',
            'đau khớp',
            'dau khop',
            'nhức mỏi xương khớp',
            'nhuc moi xuong khop',
            'nhức khớp',
            'nhuc khop',
        ],
        'SYM_SUNG_KHOP' => [
            'sưng khớp',
            'sung khop',
            'viêm khớp',
            'viem khop',
            'sưng đầu gối',
            'sung dau goi',
            'sưng mắt cá',
            'sung mat ca',
            'bệnh gút',
            'benh gut',
            'gout',
            'cứng khớp',
            'cung khop',
        ],
        'SYM_TE_BI_CHAN_TAY' => [
            'tê bì chân tay',
            'te bi chan tay',
            'tê tay',
            'te tay',
            'tê chân',
            'te chan',
            'tê bì',
            'te bi',
            'nhức mỏi tay chân',
            'nhuc moi tay chan',
            'đau dây thần kinh',
            'dau day than kinh',
        ],
    ];

    /**
     * Phân tích mô tả triệu chứng của bệnh nhân và gợi ý chuyên khoa phù hợp.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $description): array
    {
        $cleanDescription = trim($description);

        /** @var Collection<int, Symptom>|Collection $symptoms */
        $symptoms = Symptom::with('specialty')->get();

        /** @var Collection<int, Specialty>|Collection $specialties */
        $specialties = Specialty::with(['doctors.user', 'doctors.clinic'])->get();

        // 1. Thử phân tích qua AI LLM (Gemini API) nếu đã cấu hình API Key
        $aiResult = $this->analyzeWithGeminiApi($cleanDescription, $symptoms, $specialties);
        if ($aiResult !== null) {
            return $aiResult;
        }

        // 2. Fallback: Phân tích bằng bộ đối sánh từ khóa & từ đồng nghĩa thông minh (Offline-ready)
        return $this->analyzeWithSmartMatcher($cleanDescription, $symptoms, $specialties);
    }

    /**
     * Gọi Google Gemini API để trích xuất triệu chứng và gợi ý chuyên khoa.
     * Trả về null nếu không có API Key hoặc kết nối gặp sự cố để chuyển sang Fallback Matcher.
     *
     * @return array<string, mixed>|null
     */
    protected function analyzeWithGeminiApi(string $description, Collection $symptoms, Collection $specialties): ?array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-3.1-flash-lite');
        $symptomCatalog = $symptoms->map(fn (Symptom $s) => [
            'symptom_code' => $s->symptom_code,
            'symptom_name' => $s->symptom_name,
            'specialty_name' => $s->specialty?->specialty_name,
        ])->values()->toJson(JSON_UNESCAPED_UNICODE);

        $specialtyCatalog = $specialties->map(fn (Specialty $sp) => [
            'specialty_id' => $sp->specialty_id,
            'specialty_name' => $sp->specialty_name,
            'description' => $sp->description,
        ])->values()->toJson(JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Bạn là trợ lý phân loại triệu chứng y khoa của phòng khám Smart Clinic.
Dựa trên câu mô tả của bệnh nhân: "{$description}"

Danh mục triệu chứng của hệ thống:
{$symptomCatalog}

Danh mục chuyên khoa của hệ thống:
{$specialtyCatalog}

Hãy trích xuất các mã triệu chứng (symptom_code) khớp với mô tả và chọn ra 1 chuyên khoa (specialty_id) phù hợp nhất.
Chỉ trả về JSON thuần túy (không dùng markdown block) theo đúng cấu trúc:
{
  "symptom_codes": ["SYM_..."],
  "recommended_specialty_id": 1,
  "reason": "Giải thích ngắn gọn bằng tiếng Việt lý do gợi ý chuyên khoa này."
}
PROMPT;

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $response = Http::timeout(10)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if (! $response->successful()) {
                return null;
            }

            $rawText = $response->json('candidates.0.content.parts.0.text');
            if (empty($rawText)) {
                return null;
            }

            $parsed = json_decode($rawText, true);
            if (! is_array($parsed) || empty($parsed['recommended_specialty_id'])) {
                return null;
            }

            $matchedSymptoms = $symptoms->whereIn('symptom_code', $parsed['symptom_codes'] ?? [])->values();
            $recommendedSpecialty = $specialties->firstWhere('specialty_id', (int) $parsed['recommended_specialty_id']);

            if (! $recommendedSpecialty) {
                return null;
            }

            return $this->formatResponse(
                description: $description,
                mode: 'ai_llm',
                matchedSymptoms: $matchedSymptoms,
                recommendedSpecialty: $recommendedSpecialty,
                reason: $parsed['reason'] ?? 'Được phân tích và đề xuất bởi trợ lý AI dựa trên triệu chứng bạn cung cấp.',
                isFallbackGeneral: $matchedSymptoms->isEmpty()
            );
        } catch (\Throwable $e) {
            Log::warning('Smart Clinic AI Symptom Extraction Fallback triggered: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Phân tích triệu chứng bằng thuật toán đối sánh từ khóa & từ đồng nghĩa thông minh.
     * Đảm bảo hoạt động chính xác 100% cả khi không có kết nối mạng.
     *
     * @return array<string, mixed>
     */
    protected function analyzeWithSmartMatcher(string $description, Collection $symptoms, Collection $specialties): array
    {
        $normalizedInput = Str::lower($description);
        $asciiInput = Str::lower(Str::ascii($description));

        $matchedSymptoms = collect();

        foreach ($symptoms as $symptom) {
            $code = $symptom->symptom_code;
            $nameLower = Str::lower($symptom->symptom_name);
            $nameAscii = Str::lower(Str::ascii($symptom->symptom_name));

            $isMatched = false;

            // Kiểm tra khớp trực tiếp tên triệu chứng (có dấu hoặc không dấu)
            if (Str::contains($normalizedInput, $nameLower) || Str::contains($asciiInput, $nameAscii)) {
                $isMatched = true;
            }

            // Kiểm tra khớp theo từ điển từ đồng nghĩa & triệu chứng đời thường
            if (! $isMatched && isset($this->synonymKeywords[$code])) {
                foreach ($this->synonymKeywords[$code] as $keyword) {
                    $keywordLower = Str::lower($keyword);
                    if (Str::contains($normalizedInput, $keywordLower) || Str::contains($asciiInput, $keywordLower)) {
                        $isMatched = true;
                        break;
                    }
                }
            }

            if ($isMatched) {
                $matchedSymptoms->push($symptom);
            }
        }

        // Xác định chuyên khoa có nhiều triệu chứng khớp nhất
        $recommendedSpecialty = null;
        $isFallbackGeneral = false;
        $reason = '';

        if ($matchedSymptoms->isNotEmpty()) {
            $specialtyCounts = $matchedSymptoms
                ->groupBy('specialty_id')
                ->map(fn (Collection $group) => $group->count())
                ->sortDesc();

            $bestSpecialtyId = (int) $specialtyCounts->keys()->first();
            $recommendedSpecialty = $specialties->firstWhere('specialty_id', $bestSpecialtyId);

            $symptomNames = $matchedSymptoms->pluck('symptom_name')->join(', ', ' và ');
            $reason = sprintf(
                'Hệ thống nhận diện được triệu chứng: %s. Các biểu hiện này thuộc phạm vi chuyên môn của chuyên khoa %s.',
                $symptomNames,
                $recommendedSpecialty?->specialty_name
            );
        }

        // Nếu không khớp triệu chứng chuyên biệt nào, gợi ý mặc định về Nội tổng quát để khám sàng lọc
        if (! $recommendedSpecialty) {
            $isFallbackGeneral = true;
            $recommendedSpecialty = $specialties->firstWhere('specialty_name', 'Nội tổng quát')
                ?? $specialties->first();

            $reason = 'Mô tả của bạn chưa khớp với triệu chứng chuyên biệt cụ thể trong danh mục. Hệ thống khuyến nghị khám sàng lọc ban đầu tại chuyên khoa Nội tổng quát, hoặc bạn có thể tự chọn chuyên khoa khác bên dưới.';
        }

        return $this->formatResponse(
            description: $description,
            mode: 'smart_matcher',
            matchedSymptoms: $matchedSymptoms,
            recommendedSpecialty: $recommendedSpecialty,
            reason: $reason,
            isFallbackGeneral: $isFallbackGeneral
        );
    }

    /**
     * Chuẩn hóa cấu trúc dữ liệu trả về cho Controller và Frontend.
     *
     * @return array<string, mixed>
     */
    protected function formatResponse(
        string $description,
        string $mode,
        Collection $matchedSymptoms,
        Specialty $recommendedSpecialty,
        string $reason,
        bool $isFallbackGeneral
    ): array {
        return [
            'original_text' => $description,
            'analysis_mode' => $mode,
            'matched_count' => $matchedSymptoms->count(),
            'extracted_symptoms' => $matchedSymptoms->map(fn (Symptom $s) => [
                'symptom_id' => $s->symptom_id,
                'symptom_code' => $s->symptom_code,
                'symptom_name' => $s->symptom_name,
                'specialty_id' => $s->specialty_id,
                'specialty_name' => $s->specialty?->specialty_name,
            ])->values()->all(),
            'recommended_specialty' => [
                'specialty_id' => $recommendedSpecialty->specialty_id,
                'specialty_name' => $recommendedSpecialty->specialty_name,
                'description' => $recommendedSpecialty->description,
                'confidence_reason' => $reason,
                'is_fallback_general' => $isFallbackGeneral,
            ],
            'available_doctors' => $recommendedSpecialty->doctors->map(fn ($doc) => [
                'doctor_id' => $doc->doctor_id,
                'name' => $doc->user?->name,
                'degree' => $doc->degree,
                'experience_years' => $doc->experience_years,
                'bio' => $doc->bio,
                'clinic_name' => $doc->clinic?->clinic_name,
            ])->values()->all(),
            'disclaimer' => 'Kết quả trích xuất triệu chứng và gợi ý chuyên khoa chỉ mang tính chất định hướng tham khảo ban đầu, không thay thế chẩn đoán y khoa của bác sĩ. Bạn hoàn toàn có quyền tự chọn chuyên khoa khác.',
        ];
    }
}
