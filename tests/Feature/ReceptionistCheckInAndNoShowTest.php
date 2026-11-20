<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpecialtySeeder;
use Database\Seeders\UserAndDoctorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReceptionistCheckInAndNoShowTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createSchedule(
        Doctor $doctor,
        string $workDate,
        string $startTime = '08:00:00',
        string $endTime = '08:30:00',
        string $status = Schedule::STATUS_FULL,
        bool $isDayOff = false
    ): Schedule {
        return Schedule::create([
            'doctor_id' => $doctor->doctor_id,
            'clinic_id' => $doctor->clinic_id,
            'work_date' => $workDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_patients' => 1,
            'status' => $status,
            'is_day_off' => $isDayOff,
        ]);
    }

    private function createAppointment(
        User $patient,
        Doctor $doctor,
        Schedule $schedule,
        string $status = Appointment::STATUS_PENDING,
        string $code = 'SC-20261118-0001',
        bool $isAffectedByLeave = false
    ): Appointment {
        return Appointment::create([
            'appointment_code' => $code,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->doctor_id,
            'schedule_id' => $schedule->schedule_id,
            'appointment_date' => Carbon::parse($schedule->work_date)->format('Y-m-d'),
            'start_time' => $schedule->start_time,
            'status' => $status,
            'symptom_description' => 'Đau họng, sốt nhẹ',
            'is_affected_by_leave' => $isAffectedByLeave,
        ]);
    }

    public function test_receptionist_can_view_daily_appointments_and_search_by_code_cccd_or_phone(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient1 = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $patient2 = User::where('email', 'patient2@smartclinic.vn')->firstOrFail();
        $patient2->update(['cccd' => '040099887766', 'phone' => '0988776655']);

        $doctor = Doctor::firstOrFail();
        $today = Carbon::today()->toDateString();

        $s1 = $this->createSchedule($doctor, $today, '08:00:00', '08:30:00');
        $s2 = $this->createSchedule($doctor, $today, '08:30:00', '09:00:00');

        $a1 = $this->createAppointment($patient1, $doctor, $s1, Appointment::STATUS_PENDING, 'SC-FIND-0001');
        $a2 = $this->createAppointment($patient2, $doctor, $s2, Appointment::STATUS_PENDING, 'SC-FIND-0002');

        // Xem toàn bộ trong ngày
        $response = $this->actingAs($receptionist)->get(route('receptionist.checkin.index', ['date' => $today]));
        $response->assertStatus(200)
            ->assertSee('SC-FIND-0001')
            ->assertSee('SC-FIND-0002');

        // Tìm theo CCCD của patient2
        $searchCccd = $this->actingAs($receptionist)->get(route('receptionist.checkin.index', [
            'date' => $today,
            'search' => '040099887766',
        ]));
        $searchCccd->assertStatus(200)
            ->assertSee($a2->appointment_code)
            ->assertDontSee($a1->appointment_code);

        // Tìm theo mã phiếu hẹn của patient1
        $searchCode = $this->actingAs($receptionist)->get(route('receptionist.checkin.index', [
            'date' => $today,
            'search' => 'SC-FIND-0001',
        ]));
        $searchCode->assertStatus(200)
            ->assertSee($a1->appointment_code)
            ->assertDontSee($a2->appointment_code);
    }

    public function test_receptionist_can_check_in_pending_appointment_and_assign_incremental_queue_number(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient1 = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $patient2 = User::where('email', 'patient2@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $today = Carbon::today()->toDateString();

        $s1 = $this->createSchedule($doctor, $today, '08:00:00', '08:30:00');
        $s2 = $this->createSchedule($doctor, $today, '08:30:00', '09:00:00');

        $a1 = $this->createAppointment($patient1, $doctor, $s1, Appointment::STATUS_PENDING, 'SC-CHK-0001');
        $a2 = $this->createAppointment($patient2, $doctor, $s2, Appointment::STATUS_PENDING, 'SC-CHK-0002');

        $res1 = $this->actingAs($receptionist)->post(route('receptionist.appointments.check-in', $a1));
        $res1->assertSessionHasNoErrors();

        $res2 = $this->actingAs($receptionist)->post(route('receptionist.appointments.check-in', $a2));
        $res2->assertSessionHasNoErrors();

        $a1->refresh();
        $a2->refresh();

        $this->assertEquals(Appointment::STATUS_CHECKED_IN, $a1->status);
        $this->assertEquals(1, $a1->queue_number);

        $this->assertEquals(Appointment::STATUS_CHECKED_IN, $a2->status);
        $this->assertEquals(2, $a2->queue_number);
    }

    public function test_receptionist_cannot_check_in_non_pending_or_affected_by_leave_appointment(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $today = Carbon::today()->toDateString();

        $s1 = $this->createSchedule($doctor, $today, '09:00:00', '09:30:00');
        $affectedAppointment = $this->createAppointment(
            $patient,
            $doctor,
            $s1,
            Appointment::STATUS_PENDING,
            'SC-LEAVE-0001',
            true
        );

        $response = $this->actingAs($receptionist)->post(route('receptionist.appointments.check-in', $affectedAppointment));
        $response->assertSessionHasErrors('appointment');

        $affectedAppointment->refresh();
        $this->assertEquals(Appointment::STATUS_PENDING, $affectedAppointment->status);
        $this->assertNull($affectedAppointment->queue_number);
    }

    public function test_receptionist_can_mark_no_show_when_at_least_15_minutes_past_start_time(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-18';

        // Khung giờ khám 08:00, thời điểm hiện tại là đúng 08:15 (đủ 15 phút)
        Carbon::setTestNow(Carbon::parse('2026-11-18 08:15:00'));

        $schedule = $this->createSchedule($doctor, $workDate, '08:00:00', '08:30:00', Schedule::STATUS_FULL);
        $appointment = $this->createAppointment($patient, $doctor, $schedule, Appointment::STATUS_PENDING, 'SC-NOSHOW-0001');

        $response = $this->actingAs($receptionist)->post(route('receptionist.appointments.no-show', $appointment));
        $response->assertSessionHasNoErrors();

        $appointment->refresh();
        $schedule->refresh();

        $this->assertEquals(Appointment::STATUS_CANCELLED, $appointment->status);
        $this->assertEquals('Vắng mặt (No-show)', $appointment->cancel_reason);
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $schedule->status);
    }

    public function test_receptionist_cannot_mark_no_show_before_15_minutes_past_start_time(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-18';

        // Khung giờ khám 08:00, thời điểm hiện tại mới là 08:14 (chưa đủ 15 phút)
        Carbon::setTestNow(Carbon::parse('2026-11-18 08:14:00'));

        $schedule = $this->createSchedule($doctor, $workDate, '08:00:00', '08:30:00', Schedule::STATUS_FULL);
        $appointment = $this->createAppointment($patient, $doctor, $schedule, Appointment::STATUS_PENDING, 'SC-EARLY-0001');

        $response = $this->actingAs($receptionist)->post(route('receptionist.appointments.no-show', $appointment));
        $response->assertSessionHasErrors('appointment');

        $appointment->refresh();
        $schedule->refresh();

        $this->assertEquals(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertNull($appointment->cancel_reason);
        $this->assertEquals(Schedule::STATUS_FULL, $schedule->status);
    }

    public function test_receptionist_can_view_affected_by_leave_appointments_list(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $tomorrow = Carbon::tomorrow()->toDateString();

        $schedule = $this->createSchedule($doctor, $tomorrow, '10:00:00', '10:30:00', Schedule::STATUS_CANCELLED, true);
        $affected = $this->createAppointment(
            $patient,
            $doctor,
            $schedule,
            Appointment::STATUS_PENDING,
            'SC-AFFECTED-9999',
            true
        );

        $response = $this->actingAs($receptionist)->get(route('receptionist.checkin.index', ['tab' => 'affected']));

        $response->assertStatus(200)
            ->assertSee($affected->appointment_code)
            ->assertSee($patient->phone);
    }

    public function test_non_receptionist_cannot_access_receptionist_check_in_routes(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctorUser = User::where('email', 'bs.nguyenvana@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $today = Carbon::today()->toDateString();

        $schedule = $this->createSchedule($doctor, $today, '08:00:00', '08:30:00');
        $appointment = $this->createAppointment($patient, $doctor, $schedule, Appointment::STATUS_PENDING, 'SC-SEC-0001');

        $this->actingAs($patient)->get(route('receptionist.checkin.index'))->assertStatus(403);
        $this->actingAs($patient)->post(route('receptionist.appointments.check-in', $appointment))->assertStatus(403);
        $this->actingAs($doctorUser)->post(route('receptionist.appointments.no-show', $appointment))->assertStatus(403);
    }
}
