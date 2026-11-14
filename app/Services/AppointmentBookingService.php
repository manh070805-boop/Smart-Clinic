<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentBookingService
{
    /**
     * Lấy danh sách khung giờ làm việc của một bác sĩ theo ngày (dùng cho UI Bước 3).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getSchedulesForDoctorAndDate(int $doctorId, string $workDate): Collection
    {
        $schedules = Schedule::with([
            'appointments' => function ($query) {
                $query->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CHECKED_IN,
                    Appointment::STATUS_IN_PROGRESS,
                ]);
            }
        ])
            ->where('doctor_id', $doctorId)
            ->whereDate('work_date', $workDate)
            ->orderBy('start_time')
            ->get();

        return $schedules->map(function (Schedule $schedule) {
            $activeCount = $schedule->appointments->count();
            $remaining = max(0, $schedule->max_patients - $activeCount);
            $startDateTime = Carbon::parse($schedule->work_date->format('Y-m-d') . ' ' . $schedule->start_time);
            $isPast = !$startDateTime->isFuture();

            $reason = null;
            if ($schedule->is_day_off || $schedule->status === Schedule::STATUS_CANCELLED) {
                $reason = 'Bác sĩ nghỉ';
            } elseif ($isPast) {
                $reason = 'Đã qua giờ';
            } elseif ($schedule->status === Schedule::STATUS_FULL || $remaining <= 0) {
                $reason = 'Đã kín chỗ';
            }

            return [
                'schedule_id' => $schedule->schedule_id,
                'work_date' => $schedule->work_date->format('Y-m-d'),
                'start_time' => substr($schedule->start_time, 0, 5),
                'end_time' => substr($schedule->end_time, 0, 5),
                'status' => $schedule->status,
                'is_day_off' => $schedule->is_day_off,
                'is_bookable' => $schedule->isBookable() && $remaining > 0,
                'remaining_slots' => $remaining,
                'unavailable_reason' => $reason,
            ];
        })->values();
    }

    /**
     * Đặt lịch khám an toàn với DB Transaction & Lock For Update (SC03, LH01–LH06, LH15, LH17).
     *
     * @param  array<string, mixed>|null  $aiExtractedData
     * @return array{appointment: Appointment, is_duplicate_request: bool}
     *
     * @throws ValidationException
     */
    public function book(
        User $patient,
        int $scheduleId,
        ?string $symptomDescription = null,
        ?array $aiExtractedData = null
    ): array {
        if (!$patient->hasCompleteBookingProfile()) {
            throw ValidationException::withMessages([
                'profile' => 'Hồ sơ của bạn chưa đủ thông tin bắt buộc (CCCD, Số điện thoại, Ngày sinh) theo quy định SC03.',
            ]);
        }

        return DB::transaction(function () use ($patient, $scheduleId, $symptomDescription, $aiExtractedData) {
            // Khóa bản ghi khung giờ ở tầng DB để chống Race Condition (LH05)
            $schedule = Schedule::with(['doctor.specialty', 'clinic'])
                ->where('schedule_id', $scheduleId)
                ->lockForUpdate()
                ->first();

            if (!$schedule) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Khung giờ khám không tồn tại trong hệ thống.',
                ]);
            }

            // Chống gửi lặp yêu cầu (Idempotency - LH06):
            // Nếu chính bệnh nhân này đã đặt thành công đúng khung giờ này rồi -> trả về phiếu hẹn hiện có
            $existingSameSlot = Appointment::where('patient_id', $patient->id)
                ->where('schedule_id', $schedule->schedule_id)
                ->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CHECKED_IN,
                    Appointment::STATUS_IN_PROGRESS,
                ])
                ->first();

            if ($existingSameSlot) {
                return [
                    'appointment' => $existingSameSlot->load(['doctor.user', 'doctor.specialty', 'doctor.clinic', 'patient', 'schedule']),
                    'is_duplicate_request' => true,
                ];
            }

            // Kiểm tra ngày nghỉ / ca ngừng hoạt động (LH04)
            if ($schedule->is_day_off || $schedule->status === Schedule::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Khung giờ này thuộc ngày nghỉ hoặc ca khám đã ngừng hoạt động (LH04).',
                ]);
            }

            // Kiểm tra khung giờ đã bắt đầu hoặc trong quá khứ (LH03)
            $startDateTime = Carbon::parse($schedule->work_date->format('Y-m-d') . ' ' . $schedule->start_time);
            if (!$startDateTime->isFuture()) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Không thể đặt lịch cho khung giờ đã bắt đầu hoặc trong quá khứ (LH03).',
                ]);
            }

            // Kiểm tra sức chứa khung giờ sau khi đã khóa bản ghi (LH05)
            $activeCount = Appointment::where('schedule_id', $schedule->schedule_id)
                ->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CHECKED_IN,
                    Appointment::STATUS_IN_PROGRESS,
                ])
                ->lockForUpdate()
                ->count();

            if ($schedule->status === Schedule::STATUS_FULL || $activeCount >= $schedule->max_patients) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Rất tiếc, khung giờ này đã có người đặt hoặc đã đầy chỗ (LH05). Vui lòng chọn khung giờ khác.',
                ]);
            }

            // Kiểm tra bệnh nhân đã có lịch khám khác trùng ngày & giờ bắt đầu chưa (LH15)
            $hasOverlappingAppointment = Appointment::where('patient_id', $patient->id)
                ->whereDate('appointment_date', $schedule->work_date->format('Y-m-d'))
                ->where('start_time', $schedule->start_time)
                ->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CHECKED_IN,
                    Appointment::STATUS_IN_PROGRESS,
                ])
                ->exists();

            if ($hasOverlappingAppointment) {
                throw ValidationException::withMessages([
                    'schedule_id' => 'Bạn đã có một lịch hẹn khác vào cùng khung giờ này, không thể đặt trùng thời gian khám (LH15).',
                ]);
            }

            // Tạo mã phiếu hẹn duy nhất (VD: SC-20261113-A1B2)
            $appointmentCode = $this->generateUniqueAppointmentCode($schedule->work_date);

            $appointment = Appointment::create([
                'appointment_code' => $appointmentCode,
                'patient_id' => $patient->id,
                'doctor_id' => $schedule->doctor_id,
                'schedule_id' => $schedule->schedule_id,
                'appointment_date' => $schedule->work_date->format('Y-m-d'),
                'start_time' => $schedule->start_time,
                'status' => Appointment::STATUS_PENDING,
                'symptom_description' => $symptomDescription ? trim($symptomDescription) : null,
                'ai_extracted_data' => $aiExtractedData ? json_encode($aiExtractedData, JSON_UNESCAPED_UNICODE) : null,
            ]);

            // Cập nhật trạng thái khung giờ nếu đã đạt sức chứa tối đa
            if (($activeCount + 1) >= $schedule->max_patients) {
                $schedule->update(['status' => Schedule::STATUS_FULL]);
            }

            return [
                'appointment' => $appointment->load(['doctor.user', 'doctor.specialty', 'doctor.clinic', 'patient', 'schedule']),
                'is_duplicate_request' => false,
            ];
        });
    }

    /**
     * Sinh mã phiếu hẹn duy nhất theo định dạng SC-YYYYMMDD-XXXX.
     */
    protected function generateUniqueAppointmentCode(Carbon $workDate): string
    {
        $prefix = 'SC-' . $workDate->format('Ymd') . '-';

        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (Appointment::where('appointment_code', $code)->exists());

        return $code;
    }
}


