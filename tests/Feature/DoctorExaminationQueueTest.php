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

class DoctorExaminationQueueTest extends TestCase
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

    private function createSchedule(Doctor $doctor, string $workDate, string $startTime = '08:00:00'): Schedule
    {
        return Schedule::create([
            'doctor_id' => $doctor->doctor_id,
            'clinic_id' => $doctor->clinic_id,
            'work_date' => $workDate,
            'start_time' => $startTime,
            'end_time' => Carbon::parse($startTime)->addMinutes(30)->format('H:i:s'),
            'max_patients' => 1,
            'status' => Schedule::STATUS_FULL,
            'is_day_off' => false,
        ]);
    }

    private function createAppointment(
        User $patient,
        Doctor $doctor,
        Schedule $schedule,
        string $status,
        string $code,
        ?int $queueNumber = null,
        ?string $symptomDescription = 'Đau họng, ho khan kéo dài 3 ngày',
        ?array $aiExtractedData = null
    ): Appointment {
        return Appointment::create([
            'appointment_code' => $code,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->doctor_id,
            'schedule_id' => $schedule->schedule_id,
            'appointment_date' => Carbon::parse($schedule->work_date)->format('Y-m-d'),
            'start_time' => $schedule->start_time,
            'queue_number' => $queueNumber,
            'status' => $status,
            'symptom_description' => $symptomDescription,
            'ai_extracted_data' => $aiExtractedData ? json_encode($aiExtractedData, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function test_doctor_only_sees_own_assigned_appointments_and_symptom_details(): void
    {
        $doctorUserA = User::where('email', 'bs.nguyenvana@smartclinic.vn')->firstOrFail();
        $doctorUserB = User::where('email', 'bs.levanc@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();

        $doctorA = $doctorUserA->doctor;
        $doctorB = $doctorUserB->doctor;
        $today = Carbon::today()->toDateString();

        $scheduleA = $this->createSchedule($doctorA, $today, '08:00:00');
        $scheduleB = $this->createSchedule($doctorB, $today, '08:30:00');

        $apptA = $this->createAppointment(
            $patient,
            $doctorA,
            $scheduleA,
            Appointment::STATUS_CHECKED_IN,
            'SC-DOCA-0001',
            1,
            'Đau bụng âm ỉ vùng thượng vị',
            [
                'matched_symptoms' => [['symptom_name' => 'Đau bụng']],
                'recommended_specialty' => ['specialty_name' => 'Nội tổng quát'],
            ]
        );
        $apptB = $this->createAppointment(
            $patient,
            $doctorB,
            $scheduleB,
            Appointment::STATUS_CHECKED_IN,
            'SC-DOCB-0002',
            1,
            'Nghẹt mũi kéo dài'
        );

        $response = $this->actingAs($doctorUserA)->get(route('doctor.queue.index', ['date' => $today]));

        $response->assertStatus(200)
            ->assertSee($apptA->appointment_code)
            ->assertSee('Đau bụng âm ỉ vùng thượng vị')
            ->assertDontSee($apptB->appointment_code);
    }

    public function test_doctor_can_start_and_complete_checked_in_appointment_in_valid_order(): void
    {
        $doctorUser = User::where('email', 'bs.nguyenvana@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $today = Carbon::today()->toDateString();

        $schedule = $this->createSchedule($doctorUser->doctor, $today, '09:00:00');
        $appointment = $this->createAppointment(
            $patient,
            $doctorUser->doctor,
            $schedule,
            Appointment::STATUS_CHECKED_IN,
            'SC-FLOW-0001',
            1
        );

        // Checked-in -> In Progress
        $startRes = $this->actingAs($doctorUser)->post(route('doctor.appointments.start', $appointment));
        $startRes->assertSessionHasNoErrors();
        $this->assertEquals(Appointment::STATUS_IN_PROGRESS, $appointment->fresh()->status);

        // In Progress -> Completed
        $completeRes = $this->actingAs($doctorUser)->post(route('doctor.appointments.complete', $appointment));
        $completeRes->assertSessionHasNoErrors();
        $this->assertEquals(Appointment::STATUS_COMPLETED, $appointment->fresh()->status);
    }

    public function test_doctor_cannot_skip_check_in_or_complete_before_in_progress(): void
    {
        $doctorUser = User::where('email', 'bs.nguyenvana@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $today = Carbon::today()->toDateString();

        $s1 = $this->createSchedule($doctorUser->doctor, $today, '09:30:00');
        $s2 = $this->createSchedule($doctorUser->doctor, $today, '10:00:00');

        $pendingAppointment = $this->createAppointment(
            $patient,
            $doctorUser->doctor,
            $s1,
            Appointment::STATUS_PENDING,
            'SC-SKIP-0001'
        );

        $checkedInAppointment = $this->createAppointment(
            $patient,
            $doctorUser->doctor,
            $s2,
            Appointment::STATUS_CHECKED_IN,
            'SC-SKIP-0002',
            1
        );

        // Chặn Pending -> In Progress (SC04 mục 3)
        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.start', $pendingAppointment))
            ->assertSessionHasErrors('appointment');
        $this->assertEquals(Appointment::STATUS_PENDING, $pendingAppointment->fresh()->status);

        // Chặn Pending -> Completed
        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.complete', $pendingAppointment))
            ->assertSessionHasErrors('appointment');

        // Chặn Checked-in -> Completed (bỏ qua In Progress)
        $this->actingAs($doctorUser)
            ->post(route('doctor.appointments.complete', $checkedInAppointment))
            ->assertSessionHasErrors('appointment');
        $this->assertEquals(Appointment::STATUS_CHECKED_IN, $checkedInAppointment->fresh()->status);
    }

    public function test_doctor_cannot_start_or_complete_another_doctors_appointment(): void
    {
        $doctorUserA = User::where('email', 'bs.nguyenvana@smartclinic.vn')->firstOrFail();
        $doctorUserB = User::where('email', 'bs.levanc@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $today = Carbon::today()->toDateString();

        $scheduleB = $this->createSchedule($doctorUserB->doctor, $today, '10:30:00');
        $apptB = $this->createAppointment(
            $patient,
            $doctorUserB->doctor,
            $scheduleB,
            Appointment::STATUS_CHECKED_IN,
            'SC-PQ06-0001',
            1
        );

        // Bác sĩ A không thể bắt đầu khám lượt của Bác sĩ B (PQ06)
        $this->actingAs($doctorUserA)
            ->post(route('doctor.appointments.start', $apptB))
            ->assertStatus(403);

        // Cập nhật sang In Progress rồi test Bác sĩ A không thể hoàn tất lượt của Bác sĩ B (PQ06)
        $apptB->update(['status' => Appointment::STATUS_IN_PROGRESS]);
        $this->actingAs($doctorUserA)
            ->post(route('doctor.appointments.complete', $apptB))
            ->assertStatus(403);

        $this->assertEquals(Appointment::STATUS_IN_PROGRESS, $apptB->fresh()->status);
    }

    public function test_receptionist_and_patient_cannot_access_doctor_examination_routes(): void
    {
        $receptionist = User::where('email', 'receptionist@smartclinic.vn')->firstOrFail();
        $patient = User::where('email', 'patient1@smartclinic.vn')->firstOrFail();
        $doctorUser = User::where('email', 'bs.nguyenvana@smartclinic.vn')->firstOrFail();
        $today = Carbon::today()->toDateString();

        $schedule = $this->createSchedule($doctorUser->doctor, $today, '11:00:00');
        $appointment = $this->createAppointment(
            $patient,
            $doctorUser->doctor,
            $schedule,
            Appointment::STATUS_IN_PROGRESS,
            'SC-PQ08-0001',
            1
        );

        // Lễ tân không được truy cập hàng đợi bác sĩ hoặc hoàn tất khám (PQ08)
        $this->actingAs($receptionist)->get(route('doctor.queue.index'))->assertStatus(403);
        $this->actingAs($receptionist)->post(route('doctor.appointments.complete', $appointment))->assertStatus(403);

        // Bệnh nhân không được truy cập hàng đợi bác sĩ hoặc tự bắt đầu/hoàn tất khám
        $this->actingAs($patient)->get(route('doctor.queue.index'))->assertStatus(403);
        $this->actingAs($patient)->post(route('doctor.appointments.start', $appointment))->assertStatus(403);
    }
}
