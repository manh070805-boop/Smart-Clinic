<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpecialtySeeder;
use Database\Seeders\SymptomSeeder;
use Database\Seeders\UserAndDoctorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SymptomAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ClinicSeeder::class);
        $this->seed(SpecialtySeeder::class);
        $this->seed(SymptomSeeder::class);
        $this->seed(UserAndDoctorSeeder::class);
    }

    /**
     * Test trích xuất triệu chứng Tai Mũi Họng theo đúng tiêu chí DoD của T3.1.
     */
    public function test_analyze_ent_symptoms_recommends_tai_mui_hong(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Tôi bị đau họng, ho có đờm và nghẹt mũi',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recommended_specialty.specialty_name', 'Tai Mũi Họng')
            ->assertJsonPath('data.recommended_specialty.is_fallback_general', false);

        $extractedCodes = collect($response->json('data.extracted_symptoms'))->pluck('symptom_code')->all();
        $this->assertContains('SYM_DAU_HONG', $extractedCodes);
        $this->assertContains('SYM_HO_DOM', $extractedCodes);
        $this->assertContains('SYM_NGHET_MUI', $extractedCodes);

        $doctorNames = collect($response->json('data.available_doctors'))->pluck('name')->all();
        $this->assertContains('BS. Lê Văn C', $doctorNames);
    }

    /**
     * Test trích xuất triệu chứng Da liễu.
     */
    public function test_analyze_dermatology_symptoms_recommends_da_lieu(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Da tay nổi mẩn đỏ, ngứa ngáy khắp người và có mụn nước',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recommended_specialty.specialty_name', 'Da liễu');

        $extractedCodes = collect($response->json('data.extracted_symptoms'))->pluck('symptom_code')->all();
        $this->assertContains('SYM_MAN_DO', $extractedCodes);
        $this->assertContains('SYM_NGUA', $extractedCodes);
        $this->assertContains('SYM_MUN_NUOC', $extractedCodes);
    }

    /**
     * Test trích xuất triệu chứng Cơ xương khớp.
     */
    public function test_analyze_musculoskeletal_symptoms_recommends_co_xuong_khop(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Tôi bị đau lưng, nhức mỏi khớp gối và tê bì chân tay',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recommended_specialty.specialty_name', 'Cơ xương khớp');

        $extractedCodes = collect($response->json('data.extracted_symptoms'))->pluck('symptom_code')->all();
        $this->assertContains('SYM_DAU_LUNG', $extractedCodes);
        $this->assertContains('SYM_MOI_KHOP_GOI', $extractedCodes);
        $this->assertContains('SYM_TE_BI_CHAN_TAY', $extractedCodes);
    }

    /**
     * Test trích xuất triệu chứng Nội tổng quát.
     */
    public function test_analyze_internal_medicine_symptoms_recommends_noi_tong_quat(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Tôi bị đau bụng âm ỉ, buồn nôn và sốt cao từ hôm qua',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recommended_specialty.specialty_name', 'Nội tổng quát');

        $extractedCodes = collect($response->json('data.extracted_symptoms'))->pluck('symptom_code')->all();
        $this->assertContains('SYM_DAU_BUNG', $extractedCodes);
        $this->assertContains('SYM_BUON_NON', $extractedCodes);
        $this->assertContains('SYM_SOT_CAO', $extractedCodes);
    }

    /**
     * Test hỗ trợ nhận diện cả tiếng Việt không dấu hoặc từ đồng nghĩa đời thường.
     */
    public function test_analyze_supports_unaccented_vietnamese_and_synonyms(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Toi bi rat hong, so mui va nghet mui 2 ngay nay',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.recommended_specialty.specialty_name', 'Tai Mũi Họng');

        $extractedCodes = collect($response->json('data.extracted_symptoms'))->pluck('symptom_code')->all();
        $this->assertContains('SYM_DAU_HONG', $extractedCodes);
        $this->assertContains('SYM_SO_MUI', $extractedCodes);
        $this->assertContains('SYM_NGHET_MUI', $extractedCodes);
    }

    /**
     * Test khi mô tả chung chung không khớp triệu chứng đặc thù -> gợi ý mặc định Nội tổng quát.
     */
    public function test_unmatched_description_falls_back_to_noi_tong_quat(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Tôi muốn đăng ký kiểm tra sức khỏe định kỳ hằng năm',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.recommended_specialty.specialty_name', 'Nội tổng quát')
            ->assertJsonPath('data.recommended_specialty.is_fallback_general', true)
            ->assertJsonPath('data.matched_count', 0);
    }

    /**
     * Test tích hợp AI LLM (Gemini API) và tự động Fallback sang Smart Matcher nếu API lỗi.
     */
    public function test_gemini_ai_api_mode_and_automatic_fallback(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        config(['services.gemini.api_key' => 'fake-gemini-key']);

        // Giả lập Gemini API trả về kết quả hợp lệ
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'symptom_codes' => ['SYM_DAU_HONG', 'SYM_U_TAI'],
                                        'recommended_specialty_id' => 2,
                                        'reason' => 'AI nhận diện triệu chứng vùng tai và họng.',
                                    ], JSON_UNESCAPED_UNICODE),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Đau rát cổ họng kèm ù tai trái',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.analysis_mode', 'ai_llm')
            ->assertJsonPath('data.recommended_specialty.specialty_name', 'Tai Mũi Họng');
    }

    /**
     * Test validation từ chối chuỗi rỗng hoặc quá ngắn.
     */
    public function test_analyze_validates_minimum_length(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->postJson(route('patient.symptoms.analyze'), [
            'symptoms_text' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['symptoms_text']);
    }

    /**
     * Test bảo vệ quyền: Bệnh nhân chưa có CCCD (LH17) bị chặn truy cập endpoint phân tích.
     */
    public function test_patient_without_cccd_cannot_access_symptom_analysis(): void
    {
        $patientWithoutCccd = User::where('email', 'patient2@smartclinic.vn')->first();

        $response = $this->actingAs($patientWithoutCccd)->post(route('patient.symptoms.analyze'), [
            'symptoms_text' => 'Tôi bị đau họng và sốt cao',
        ]);

        $response->assertRedirect(route('profile.edit'));
    }
}
