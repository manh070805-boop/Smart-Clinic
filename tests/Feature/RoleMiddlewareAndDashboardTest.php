<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\ClinicSeeder::class);
        $this->seed(\Database\Seeders\SpecialtySeeder::class);
        $this->seed(\Database\Seeders\UserAndDoctorSeeder::class);
    }
    /**
     * Khách chưa đăng nhập truy cập dashboard sẽ bị chuyển hướng về login.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $responseAdmin = $this->get('/admin/dashboard');
        $responseAdmin->assertRedirect('/login');
    }

    /**
     * Admin đăng nhập vào /dashboard sẽ được phân luồng sang /admin/dashboard.
     */
    public function test_admin_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::where('email', 'admin@smartclinic.vn')->first();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertRedirect(route('admin.dashboard'));

        $dashboardResponse = $this->actingAs($admin)->get('/admin/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Không Gian Quản Trị Hệ Thống');
    }

    /**
     * Bác sĩ đăng nhập vào /dashboard sẽ được phân luồng sang /doctor/dashboard.
     */
    public function test_doctor_is_redirected_to_doctor_dashboard(): void
    {
        $doctor = User::where('email', 'bs.nguyenvana@smartclinic.vn')->first();

        $response = $this->actingAs($doctor)->get('/dashboard');
        $response->assertRedirect(route('doctor.dashboard'));

        $dashboardResponse = $this->actingAs($doctor)->get('/doctor/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Không Gian Bác Sĩ');
    }

    /**
     * Lễ tân đăng nhập vào /dashboard sẽ được phân luồng sang /receptionist/dashboard.
     */
    public function test_receptionist_is_redirected_to_receptionist_dashboard(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->first();

        $response = $this->actingAs($receptionist)->get('/dashboard');
        $response->assertRedirect(route('receptionist.dashboard'));

        $dashboardResponse = $this->actingAs($receptionist)->get('/receptionist/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Bàn Tiếp Đón Lễ Tân');
    }

    /**
     * Bệnh nhân đăng nhập vào /dashboard sẽ được phân luồng sang /patient/dashboard.
     */
    public function test_patient_is_redirected_to_patient_dashboard(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $response = $this->actingAs($patient)->get('/dashboard');
        $response->assertRedirect(route('patient.dashboard'));

        $dashboardResponse = $this->actingAs($patient)->get('/patient/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Cổng Dịch Vụ Bệnh Nhân');
    }

    /**
     * Bệnh nhân cố tình truy cập vào URL của Admin hoặc Bác sĩ sẽ bị chặn với mã 403.
     */
    public function test_patient_cannot_access_admin_or_doctor_dashboard(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();

        $responseAdmin = $this->actingAs($patient)->get('/admin/dashboard');
        $responseAdmin->assertStatus(403);

        $responseDoctor = $this->actingAs($patient)->get('/doctor/dashboard');
        $responseDoctor->assertStatus(403);
    }

    /**
     * Đăng ký tài khoản mới được tự động gán vai trò Patient.
     */
    public function test_new_user_registration_automatically_assigns_patient_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Bệnh Nhân Mới Test',
            'email' => 'newpatient@smartclinic.vn',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $newUser = User::where('email', 'newpatient@smartclinic.vn')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->isPatient());
        $this->assertEquals(Role::ROLE_PATIENT, $newUser->role->role_name);
    }
}
