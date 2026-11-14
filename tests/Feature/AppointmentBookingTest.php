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

class AppointmentBookingTest extends TestCase
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

    private function createSchedule(
        Doctor $doctor,
        string $workDate,
        string $startTime = '09:00:00',
        string $endTime = '09:30:00',
        string $status = Schedule::STATUS_AVAILABLE,
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

    public function test_patient_can_fetch_doctor_schedules_for_date(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        $doctor = Doctor::first();
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();

        $availableSlot = $this->createSchedule($doctor, $tomorrow, '08:00:00', '08:30:00');
        $dayOffSlot = $this->createSchedule($doctor, $tomorrow, '08:30:00', '09:00:00', Schedule::STATUS_CANCELLED, true);

        $response = $this->actingAs($patient)->getJson(route('patient.booking.schedules', [
            'doctor_id' => $doctor->doctor_id,
            'work_date' => $tomorrow,
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.schedule_id', $availableSlot->schedule_id)
            ->assertJsonPath('data.0.is_bookable', true)
            ->assertJsonPath('data.0.remaining_slots', 1)
            ->assertJsonPath('data.1.schedule_id', $dayOffSlot->schedule_id)
            ->assertJsonPath('data.1.is_bookable', false);
    }

    public function test_patient_can_book_available_slot_and_view_receipt(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        $doctor = Doctor::first();
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();
        $schedule = $this->createSchedule($doctor, $tomorrow, '09:00:00', '09:30:00');

        $response = $this->actingAs($patient)->post(route('patient.booking.store'), [
            'schedule_id' => $schedule->schedule_id,
            'symptom_description' => 'Đau họng, ho khan từ hôm qua',
            'ai_extracted_data' => json_encode([
                'recommended_specialty' => ['specialty_name' => 'Tai Mũi Họng'],
            ]),
        ]);

        $appointment = Appointment::where('patient_id', $patient->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertStringStartsWith('SC-', $appointment->appointment_code);
        $this->assertNull($appointment->queue_number);

        $response->assertRedirect(route('patient.booking.receipt', $appointment));

        $schedule->refresh();
        $this->assertEquals(Schedule::STATUS_FULL, $schedule->status);

        $receiptResponse = $this->actingAs($patient)->get(route('patient.booking.receipt', $appointment));
        $receiptResponse->assertStatus(200)
            ->assertSee($appointment->appointment_code)
            ->assertSee($patient->name);
    }

    public function test_booking_rejects_past_or_started_slot(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        $doctor = Doctor::first();
        $yesterday = Carbon::yesterday('Asia/Ho_Chi_Minh')->toDateString();
        $pastSchedule = $this->createSchedule($doctor, $yesterday, '08:00:00', '08:30:00');

        $response = $this->actingAs($patient)->postJson(route('patient.booking.store'), [
            'schedule_id' => $pastSchedule->schedule_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedule_id']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_rejects_day_off_or_cancelled_schedule(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        $doctor = Doctor::first();
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();
        $offSchedule = $this->createSchedule($doctor, $tomorrow, '10:00:00', '10:30:00', Schedule::STATUS_CANCELLED, true);

        $response = $this->actingAs($patient)->postJson(route('patient.booking.store'), [
            'schedule_id' => $offSchedule->schedule_id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedule_id']);
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_concurrent_booking_prevents_double_booking(): void
    {
        $patient1 = User::where('email', 'patient1@smartclinic.vn')->first();
        $patient2 = User::where('email', 'patient2@smartclinic.vn')->first();
        $patient2->update([
            'cccd' => '001200000099',
            'phone' => '0901234599',
            'dob' => '1995-05-05',
            'gender' => 'female',
            'address' => 'TP.HCM',
        ]);
        $doctor = Doctor::first();
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();
        $schedule = $this->createSchedule($doctor, $tomorrow, '09:30:00', '10:00:00');

        $firstResponse = $this->actingAs($patient1)->postJson(route('patient.booking.store'), [
            'schedule_id' => $schedule->schedule_id,
        ]);
        $firstResponse->assertStatus(201);

        $secondResponse = $this->actingAs($patient2)->postJson(route('patient.booking.store'), [
            'schedule_id' => $schedule->schedule_id,
        ]);
        $secondResponse->assertStatus(422)
            ->assertJsonValidationErrors(['schedule_id']);

        $this->assertEquals(1, Appointment::where('schedule_id', $schedule->schedule_id)->count());
    }

    public function test_repeat_booking_same_patient_and_slot_is_idempotent(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        $doctor = Doctor::first();
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();
        $schedule = $this->createSchedule($doctor, $tomorrow, '14:00:00', '14:30:00');

        $firstResponse = $this->actingAs($patient)->postJson(route('patient.booking.store'), [
            'schedule_id' => $schedule->schedule_id,
            'symptom_description' => 'Đau đầu nhẹ',
        ]);
        $firstResponse->assertStatus(201);
        $firstId = $firstResponse->json('data.appointment_id');

        $repeatResponse = $this->actingAs($patient)->postJson(route('patient.booking.store'), [
            'schedule_id' => $schedule->schedule_id,
            'symptom_description' => 'Đau đầu nhẹ',
        ]);
        $repeatResponse->assertStatus(200)
            ->assertJsonPath('data.appointment_id', $firstId);

        $this->assertEquals(1, Appointment::where('schedule_id', $schedule->schedule_id)->count());
    }

    public function test_patient_cannot_book_overlapping_slot_with_another_doctor(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        $doctors = Doctor::take(2)->get();
        $this->assertCount(2, $doctors);

        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();
        $slotDoctor1 = $this->createSchedule($doctors[0], $tomorrow, '15:00:00', '15:30:00');
        $slotDoctor2 = $this->createSchedule($doctors[1], $tomorrow, '15:00:00', '15:30:00');

        $this->actingAs($patient)->postJson(route('patient.booking.store'), [
            'schedule_id' => $slotDoctor1->schedule_id,
        ])->assertStatus(201);

        $overlapResponse = $this->actingAs($patient)->postJson(route('patient.booking.store'), [
            'schedule_id' => $slotDoctor2->schedule_id,
        ]);
        $overlapResponse->assertStatus(422)
            ->assertJsonValidationErrors(['schedule_id']);
    }

    public function test_patient_without_cccd_is_blocked_from_booking(): void
    {
        $patient = User::where('email', 'patient1@smartclinic.vn')->first();
        $patient->update(['cccd' => null]);

        $doctor = Doctor::first();
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();
        $schedule = $this->createSchedule($doctor, $tomorrow, '16:00:00', '16:30:00');

        $response = $this->actingAs($patient)->post(route('patient.booking.store'), [
            'schedule_id' => $schedule->schedule_id,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_patient_cannot_view_another_patients_receipt(): void
    {
        $patient1 = User::where('email', 'patient1@smartclinic.vn')->first();
        $patient2 = User::where('email', 'patient2@smartclinic.vn')->first();
        $patient2->update([
            'cccd' => '001200000098',
            'phone' => '0901234598',
            'dob' => '1996-06-06',
            'gender' => 'female',
            'address' => 'TP.HCM',
        ]);
        $doctor = Doctor::first();
        $tomorrow = Carbon::tomorrow('Asia/Ho_Chi_Minh')->toDateString();
        $schedule = $this->createSchedule($doctor, $tomorrow, '10:30:00', '11:00:00');

        $this->actingAs($patient1)->post(route('patient.booking.store'), [
            'schedule_id' => $schedule->schedule_id,
        ]);
        $appointment = Appointment::where('patient_id', $patient1->id)->firstOrFail();

        $this->actingAs($patient2)
            ->get(route('patient.booking.receipt', $appointment))
            ->assertStatus(403);
    }
}

