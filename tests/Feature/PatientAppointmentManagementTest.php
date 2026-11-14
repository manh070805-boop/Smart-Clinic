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

class PatientAppointmentManagementTest extends TestCase
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
        string $startTime = '09:00:00',
        string $endTime = '09:30:00',
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
        string $code = 'SC-20261115-0001'
    ): Appointment {
        return Appointment::create([
            'appointment_code' => $code,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->doctor_id,
            'schedule_id' => $schedule->schedule_id,
            'appointment_date' => Carbon::parse($schedule->work_date)->format('Y-m-d'),
            'start_time' => $schedule->start_time,
            'status' => $status,
            'symptom_description' => 'Đau đầu nhẹ',
        ]);
    }

    public function test_patient_can_view_own_appointments_only(): void
    {
        $patient1 = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $patient2 = User::where('email', 'patient2@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $tomorrow = Carbon::tomorrow()->toDateString();

        $s1 = $this->createSchedule($doctor, $tomorrow, '08:00:00', '08:30:00');
        $s2 = $this->createSchedule($doctor, $tomorrow, '08:30:00', '09:00:00');

        $a1 = $this->createAppointment($patient1, $doctor, $s1, Appointment::STATUS_PENDING, 'SC-OWN-0001');
        $a2 = $this->createAppointment($patient2, $doctor, $s2, Appointment::STATUS_PENDING, 'SC-OTHER-0002');

        $response = $this->actingAs($patient1)->get(route('patient.appointments.index'));

        $response->assertStatus(200)
            ->assertSee($a1->appointment_code)
            ->assertDontSee($a2->appointment_code);
    }

    public function test_patient_can_cancel_appointment_at_least_two_hours_before_start(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 08:00:00'));

        $schedule = $this->createSchedule($doctor, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $appointment = $this->createAppointment($patient, $doctor, $schedule);

        $response = $this->actingAs($patient)->post(route('patient.appointments.cancel', $appointment), [
            'cancel_reason' => 'Bận công việc đột xuất',
        ]);

        $response->assertRedirect(route('patient.appointments.index'));

        $appointment->refresh();
        $schedule->refresh();

        $this->assertEquals(Appointment::STATUS_CANCELLED, $appointment->status);
        $this->assertEquals('Bận công việc đột xuất', $appointment->cancel_reason);
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $schedule->status);
    }

    public function test_patient_cannot_cancel_appointment_under_two_hours_before_start(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 08:01:00'));

        $schedule = $this->createSchedule($doctor, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $appointment = $this->createAppointment($patient, $doctor, $schedule);

        $response = $this->actingAs($patient)->postJson(route('patient.appointments.cancel', $appointment), [
            'cancel_reason' => 'Muốn hủy sát giờ',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment']);

        $appointment->refresh();
        $schedule->refresh();
        $this->assertEquals(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertEquals(Schedule::STATUS_FULL, $schedule->status);
    }

    public function test_patient_cannot_cancel_checked_in_appointment(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:00:00'));

        $schedule = $this->createSchedule($doctor, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $appointment = $this->createAppointment($patient, $doctor, $schedule, Appointment::STATUS_CHECKED_IN);

        $response = $this->actingAs($patient)->postJson(route('patient.appointments.cancel', $appointment), [
            'cancel_reason' => 'Hủy sau khi đã tiếp nhận',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment']);
        $this->assertEquals(Appointment::STATUS_CHECKED_IN, $appointment->fresh()->status);
    }

    public function test_patient_cannot_cancel_or_reschedule_another_patients_appointment(): void
    {
        $patient1 = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $patient2 = User::where('email', 'patient2@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:00:00'));

        $schedule = $this->createSchedule($doctor, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $newSchedule = $this->createSchedule($doctor, $workDate, '14:00:00', '14:30:00', Schedule::STATUS_AVAILABLE);
        $appointment = $this->createAppointment($patient1, $doctor, $schedule);

        $this->actingAs($patient2)
            ->postJson(route('patient.appointments.cancel', $appointment), ['cancel_reason' => 'Hủy hộ'])
            ->assertStatus(403);

        $this->actingAs($patient2)
            ->postJson(route('patient.appointments.reschedule', $appointment), ['new_schedule_id' => $newSchedule->schedule_id])
            ->assertStatus(403);
    }

    public function test_patient_can_reschedule_to_valid_same_specialty_slot(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctorsSameSpecialty = Doctor::whereHas('specialty', fn ($q) => $q->where('specialty_name', 'Nội tổng quát'))->get();
        $this->assertGreaterThanOrEqual(2, $doctorsSameSpecialty->count());

        $doctor1 = $doctorsSameSpecialty[0];
        $doctor2 = $doctorsSameSpecialty[1];
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:00:00'));

        $oldSchedule = $this->createSchedule($doctor1, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $newSchedule = $this->createSchedule($doctor2, $workDate, '15:00:00', '15:30:00', Schedule::STATUS_AVAILABLE);
        $appointment = $this->createAppointment($patient, $doctor1, $oldSchedule);

        $response = $this->actingAs($patient)->post(route('patient.appointments.reschedule', $appointment), [
            'new_schedule_id' => $newSchedule->schedule_id,
        ]);

        $response->assertRedirect(route('patient.appointments.index'));

        $appointment->refresh();
        $oldSchedule->refresh();
        $newSchedule->refresh();

        $this->assertEquals($newSchedule->schedule_id, $appointment->schedule_id);
        $this->assertEquals($doctor2->doctor_id, $appointment->doctor_id);
        $this->assertEquals('15:00:00', $appointment->start_time);
        $this->assertNotNull($appointment->rescheduled_at);
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $oldSchedule->status);
        $this->assertEquals(Schedule::STATUS_FULL, $newSchedule->status);
    }

    public function test_reschedule_fails_if_new_slot_is_full_and_keeps_old_appointment(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:00:00'));

        $oldSchedule = $this->createSchedule($doctor, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $fullNewSchedule = $this->createSchedule($doctor, $workDate, '15:00:00', '15:30:00', Schedule::STATUS_FULL);
        $appointment = $this->createAppointment($patient, $doctor, $oldSchedule);

        $response = $this->actingAs($patient)->postJson(route('patient.appointments.reschedule', $appointment), [
            'new_schedule_id' => $fullNewSchedule->schedule_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_schedule_id']);

        $appointment->refresh();
        $oldSchedule->refresh();
        $this->assertEquals($oldSchedule->schedule_id, $appointment->schedule_id);
        $this->assertEquals(Schedule::STATUS_FULL, $oldSchedule->status);
    }

    public function test_reschedule_rejects_different_specialty_slot(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctorNoi = Doctor::whereHas('specialty', fn ($q) => $q->where('specialty_name', 'Nội tổng quát'))->firstOrFail();
        $doctorTmh = Doctor::whereHas('specialty', fn ($q) => $q->where('specialty_name', 'Tai Mũi Họng'))->firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:00:00'));

        $oldSchedule = $this->createSchedule($doctorNoi, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $diffSpecialtySchedule = $this->createSchedule($doctorTmh, $workDate, '15:00:00', '15:30:00', Schedule::STATUS_AVAILABLE);
        $appointment = $this->createAppointment($patient, $doctorNoi, $oldSchedule);

        $response = $this->actingAs($patient)->postJson(route('patient.appointments.reschedule', $appointment), [
            'new_schedule_id' => $diffSpecialtySchedule->schedule_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_schedule_id']);

        $this->assertEquals($oldSchedule->schedule_id, $appointment->fresh()->schedule_id);
    }

    public function test_reschedule_rejects_under_two_hours_before_old_appointment(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 08:30:00'));

        $oldSchedule = $this->createSchedule($doctor, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $newSchedule = $this->createSchedule($doctor, $workDate, '15:00:00', '15:30:00', Schedule::STATUS_AVAILABLE);
        $appointment = $this->createAppointment($patient, $doctor, $oldSchedule);

        $response = $this->actingAs($patient)->postJson(route('patient.appointments.reschedule', $appointment), [
            'new_schedule_id' => $newSchedule->schedule_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['appointment']);

        $this->assertEquals($oldSchedule->schedule_id, $appointment->fresh()->schedule_id);
    }

    public function test_reschedule_rolls_back_on_mid_transaction_failure_and_keeps_old_appointment(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctor = Doctor::firstOrFail();
        $workDate = '2026-11-20';

        Carbon::setTestNow(Carbon::parse('2026-11-20 07:00:00'));

        $oldSchedule = $this->createSchedule($doctor, $workDate, '10:00:00', '10:30:00', Schedule::STATUS_FULL);
        $newSchedule = $this->createSchedule($doctor, $workDate, '15:00:00', '15:30:00', Schedule::STATUS_AVAILABLE);
        $appointment = $this->createAppointment($patient, $doctor, $oldSchedule);

        Schedule::updating(function () {
            throw new \RuntimeException('Simulated DB failure mid-reschedule');
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($patient)
                ->postJson(route('patient.appointments.reschedule', $appointment), [
                    'new_schedule_id' => $newSchedule->schedule_id,
                ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Simulated DB failure mid-reschedule', $e->getMessage());
        } finally {
            Schedule::flushEventListeners();
        }

        $this->assertEquals($oldSchedule->schedule_id, $appointment->fresh()->schedule_id);
        $this->assertEquals(Schedule::STATUS_FULL, $oldSchedule->fresh()->status);
        $this->assertEquals(Schedule::STATUS_AVAILABLE, $newSchedule->fresh()->status);
    }
}
