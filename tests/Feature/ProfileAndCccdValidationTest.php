<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpecialtySeeder;
use Database\Seeders\UserAndDoctorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAndCccdValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(ClinicSeeder::class);
        $this->seed(SpecialtySeeder::class);
        $this->seed(UserAndDoctorSeeder::class);
    }

    /**
     * Màn hình Profile hiển thị đầy đủ các trường CCCD, SĐT, Ngày sinh.
     */
    public function test_profile_page_is_displayed_with_cccd_fields(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Số Căn cước công dân (CCCD)');
        $response->assertSee('Số điện thoại liên hệ');
        $response->assertSee('Ngày sinh');
        $response->assertSee('Hồ sơ đã đạt chuẩn đặt lịch');
    }

    /**
     * Người dùng cập nhật thành công CCCD, Số điện thoại và Ngày sinh.
     */
    public function test_user_can_update_profile_with_valid_cccd_phone_dob(): void
    {
        $patient = User::where('email', 'patient2@smartclinic.vn')->first();
        $this->assertNull($patient->cccd);

        $response = $this->actingAs($patient)->patch('/profile', [
            'name' => 'Trần Thị Bệnh Nhân 2 Đã Đủ CCCD',
            'email' => 'patient2@smartclinic.vn',
            'cccd' => '038090005678',
            'phone' => '0988776655',
            'dob' => '1998-08-20',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile');

        $patient->refresh();
        $this->assertEquals('038090005678', $patient->cccd);
        $this->assertEquals('0988776655', $patient->phone);
        $this->assertEquals('1998-08-20', $patient->dob->format('Y-m-d'));
        $this->assertTrue($patient->hasCompleteBookingProfile());
    }

    /**
     * Validate: CCCD phải có đúng 12 chữ số.
     */
    public function test_cccd_must_be_exactly_12_digits(): void
    {
        $patient = User::where('email', 'patient2@smartclinic.vn')->first();

        // Thử với 10 số (thiếu)
        $responseShort = $this->actingAs($patient)->patch('/profile', [
            'name' => 'Test Patient',
            'email' => 'patient2@smartclinic.vn',
            'cccd' => '1234567890',
        ]);
        $responseShort->assertSessionHasErrors('cccd');

        // Thử với ký tự chữ
        $responseAlpha = $this->actingAs($patient)->patch('/profile', [
            'name' => 'Test Patient',
            'email' => 'patient2@smartclinic.vn',
            'cccd' => '03809000123A',
        ]);
        $responseAlpha->assertSessionHasErrors('cccd');
    }

    /**
     * Validate: CCCD phải là duy nhất, không được trùng người khác.
     */
    public function test_cccd_must_be_unique(): void
    {
        $patient1 = User::where('email', 'patient1@smartclinic.vn')->first();
        $patient2 = User::where('email', 'patient2@smartclinic.vn')->first();

        // Bệnh nhân 2 cố tình điền lại CCCD của Bệnh nhân 1
        $response = $this->actingAs($patient2)->patch('/profile', [
            'name' => 'Patient 2 Fake CCCD',
            'email' => 'patient2@smartclinic.vn',
            'cccd' => $patient1->cccd,
        ]);

        $response->assertSessionHasErrors('cccd');
    }

    /**
     * Validate: Ngày sinh phải là ngày trong quá khứ.
     */
    public function test_dob_must_be_in_the_past(): void
    {
        $patient = User::where('email', 'patient2@smartclinic.vn')->first();

        $tomorrow = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($patient)->patch('/profile', [
            'name' => 'Test Patient',
            'email' => 'patient2@smartclinic.vn',
            'dob' => $tomorrow,
        ]);

        $response->assertSessionHasErrors('dob');
    }

    /**
     * Kịch bản LH17: Bệnh nhân chưa có CCCD khi bấm Đặt lịch bị chặn lại và chuyển về Profile.
     */
    public function test_patient_without_cccd_is_redirected_to_profile_with_warning(): void
    {
        $patientWithoutCccd = User::where('email', 'patient2@smartclinic.vn')->first();
        $this->assertFalse($patientWithoutCccd->hasCompleteBookingProfile());

        $response = $this->actingAs($patientWithoutCccd)->get('/patient/booking');
        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('warning');

        // Khi redirect về profile thì thấy thông báo cảnh báo
        $profileResponse = $this->actingAs($patientWithoutCccd)->get('/profile');
        $profileResponse->assertSee('Chưa đủ điều kiện đặt lịch khám');
    }

    /**
     * Bệnh nhân đã có đủ CCCD và thông tin định danh được phép vào trang Đặt lịch khám.
     */
    public function test_patient_with_complete_profile_can_access_booking(): void
    {
        $patientWithCccd = User::where('email', 'patient1@smartclinic.vn')->first();
        $this->assertTrue($patientWithCccd->hasCompleteBookingProfile());

        $response = $this->actingAs($patientWithCccd)->get('/patient/booking');
        $response->assertStatus(200);
        $response->assertSee('Đăng Ký Đặt Lịch Khám Bệnh Trực Tuyến');
        $response->assertSee($patientWithCccd->cccd);
    }
}

