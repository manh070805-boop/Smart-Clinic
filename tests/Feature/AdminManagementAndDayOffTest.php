<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Models\Symptom;
use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpecialtySeeder;
use Database\Seeders\SymptomSeeder;
use Database\Seeders\UserAndDoctorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminManagementAndDayOffTest extends TestCase
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

    public function test_admin_dashboard_displays_statistics_summary(): void
    {
        $admin = User::where('email', 'admin@smartclinic.vn')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200)
            ->assertSee('Không Gian Quản Trị Hệ Thống')
            ->assertSee('Thống Kê Lịch Hẹn Toàn Hệ Thống')
            ->assertSee('Nội tổng quát');
    }

    public function test_admin_can_create_and_delete_specialty_and_symptom_catalog(): void
    {
        $admin = User::where('email', 'admin@smartclinic.vn')->firstOrFail();

        // Tạo chuyên khoa mới (PQ10, UC_A02)
        $resSp = $this->actingAs($admin)->post(route('admin.catalog.specialties.store'), [
            'specialty_name' => 'Tim mạch',
            'description' => 'Khám và điều trị bệnh lý tim mạch',
        ]);
        $resSp->assertRedirect(route('admin.catalog.index', ['tab' => 'specialties']));
        $specialty = Specialty::where('specialty_name', 'Tim mạch')->firstOrFail();

        // Tạo triệu chứng mới gắn chuyên khoa đó
        $resSym = $this->actingAs($admin)->post(route('admin.catalog.symptoms.store'), [
            'symptom_code' => 'SYM_CHEST_PAIN',
            'symptom_name' => 'Đau tức ngực',
            'specialty_id' => $specialty->specialty_id,
        ]);
        $resSym->assertRedirect(route('admin.catalog.index', ['tab' => 'symptoms']));
        $symptom = Symptom::where('symptom_code', 'SYM_CHEST_PAIN')->firstOrFail();

        // Xóa triệu chứng & chuyên khoa vừa tạo
        $this->actingAs($admin)->delete(route('admin.catalog.symptoms.destroy', $symptom))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('symptoms', ['symptom_id' => $symptom->symptom_id]);

        $this->actingAs($admin)->delete(route('admin.catalog.specialties.destroy', $specialty))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('specialties', ['specialty_id' => $specialty->specialty_id]);
    }

    public function test_admin_can_create_doctor_profile(): void
    {
        $admin = User::where('email', 'admin@smartclinic.vn')->firstOrFail();
        $specialty = Specialty::firstOrFail();
        $clinic = Clinic::firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.catalog.doctors.store'), [
            'name' => 'BS. Nguyễn Văn F',
            'email' => 'bs.nguyenvanf@smartclinic.vn',
            'password' => 'password123',
            'specialty_id' => $specialty->specialty_id,
            'clinic_id' => $clinic->clinic_id,
            'degree' => 'Thạc sĩ Bác sĩ',
            'experience_years' => 6,
        ]);

        $response->assertRedirect(route('admin.catalog.index', ['tab' => 'doctors']));

        $newUser = User::where('email', 'bs.nguyenvanf@smartclinic.vn')->firstOrFail();
        $this->assertTrue($newUser->isDoctor());
        $this->assertNotNull($newUser->doctor);
        $this->assertEquals($specialty->specialty_id, $newUser->doctor->specialty_id);
    }

    public function test_admin_can_create_schedule_and_block_duplicate_time_slot(): void
    {
        $admin = User::where('email', 'admin@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = Carbon::tomorrow()->toDateString();

        $res1 = $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'doctor_id' => $doctor->doctor_id,
            'clinic_id' => $doctor->clinic_id,
            'work_date' => $workDate,
            'start_time' => '08:00',
            'end_time' => '08:30',
            'max_patients' => 1,
        ]);
        $res1->assertSessionHasNoErrors();
        $this->assertDatabaseHas('schedules', [
            'doctor_id' => $doctor->doctor_id,
            'start_time' => '08:00:00',
            'status' => Schedule::STATUS_AVAILABLE,
        ]);

        // Tạo trùng khung giờ phải bị chặn
        $res2 = $this->actingAs($admin)->post(route('admin.schedules.store'), [
            'doctor_id' => $doctor->doctor_id,
            'clinic_id' => $doctor->clinic_id,
            'work_date' => $workDate,
            'start_time' => '08:00',
            'end_time' => '08:30',
        ]);
        $res2->assertSessionHasErrors('start_time');
    }

    public function test_admin_marking_day_off_flags_pending_appointments_without_deleting_them(): void
    {
        $admin = User::where('email', 'admin@smartclinic.vn')->firstOrFail();
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = Carbon::tomorrow()->toDateString();

        $schedule = Schedule::create([
            'doctor_id' => $doctor->doctor_id,
            'clinic_id' => $doctor->clinic_id,
            'work_date' => $workDate,
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'max_patients' => 1,
            'status' => Schedule::STATUS_FULL,
            'is_day_off' => false,
        ]);

        $appointment = Appointment::create([
            'appointment_code' => 'SC-LH16-0001',
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->doctor_id,
            'schedule_id' => $schedule->schedule_id,
            'appointment_date' => $workDate,
            'start_time' => '09:00:00',
            'status' => Appointment::STATUS_PENDING,
            'is_affected_by_leave' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.schedules.day-off', $schedule));
        $response->assertSessionHasNoErrors();

        $schedule->refresh();
        $appointment->refresh();

        // Ca làm việc đã đóng nghỉ đột xuất và không thể đặt mới
        $this->assertTrue($schedule->is_day_off);
        $this->assertEquals(Schedule::STATUS_CANCELLED, $schedule->status);
        $this->assertFalse($schedule->isBookable());

        // Lịch hẹn Pending không bị xóa mà được tự động gắn cờ ảnh hưởng nghỉ đột xuất (LH16)
        $this->assertDatabaseHas('appointments', ['appointment_id' => $appointment->appointment_id]);
        $this->assertEquals(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertTrue($appointment->is_affected_by_leave);

        // Xuất hiện ngay trong danh sách theo dõi nghỉ đột xuất của Lễ tân (UC_R04)
        $this->actingAs($receptionist)
            ->get(route('receptionist.checkin.index', ['tab' => 'affected']))
            ->assertStatus(200)
            ->assertSee('SC-LH16-0001');
    }

    public function test_non_admin_cannot_access_admin_catalog_or_schedule_routes(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $doctorUser = User::where('email', 'bs.nguyenvana@smartclinic.vn')->firstOrFail();

        $this->actingAs($patient)->get(route('admin.catalog.index'))->assertStatus(403);
        $this->actingAs($receptionist)->get(route('admin.schedules.index'))->assertStatus(403);
        $this->actingAs($doctorUser)->post(route('admin.catalog.specialties.store'), [
            'specialty_name' => 'Unauthorized',
        ])->assertStatus(403);
    }
}
