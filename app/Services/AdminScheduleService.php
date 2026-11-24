<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminScheduleService
{
    /**
     * Tạo ca làm việc / khung giờ khám mới cho bác sĩ (UC_A03).
     *
     * @param  array{doctor_id: int, clinic_id: int, work_date: string, start_time: string, end_time: string, max_patients?: int}  $data
     *
     * @throws ValidationException
     */
    public function createSchedule(array $data): Schedule
    {
        $startTime = strlen($data['start_time']) === 5 ? $data['start_time'].':00' : $data['start_time'];
        $endTime = strlen($data['end_time']) === 5 ? $data['end_time'].':00' : $data['end_time'];

        if ($endTime <= $startTime) {
            throw ValidationException::withMessages([
                'end_time' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu.',
            ]);
        }

        $exists = Schedule::where('doctor_id', $data['doctor_id'])
            ->whereDate('work_date', $data['work_date'])
            ->where('start_time', $startTime)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_time' => 'Bác sĩ đã có khung giờ làm việc trùng ngày và giờ bắt đầu này.',
            ]);
        }

        return Schedule::create([
            'doctor_id' => $data['doctor_id'],
            'clinic_id' => $data['clinic_id'],
            'work_date' => $data['work_date'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_patients' => $data['max_patients'] ?? 1,
            'status' => Schedule::STATUS_AVAILABLE,
            'is_day_off' => false,
        ]);
    }

    /**
     * Xử lý Bác sĩ nghỉ đột xuất (UC_A04, LH16):
     * - Khóa khung giờ (is_day_off = true, status = cancelled) để không nhận đặt lịch mới.
     * - Tự động gắn cờ is_affected_by_leave = true cho các lịch hẹn đang pending trong ca đó
     *   để chuyển sang danh sách cần hỗ trợ của Lễ tân (tuyệt đối không xóa lịch hẹn).
     *
     * @return array{schedule: Schedule, affected_count: int}
     *
     * @throws ValidationException
     */
    public function markDayOff(Schedule $schedule): array
    {
        return DB::transaction(function () use ($schedule) {
            $lockedSchedule = Schedule::where('schedule_id', $schedule->schedule_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSchedule->is_day_off && $lockedSchedule->status === Schedule::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'schedule' => 'Khung giờ này đã được đánh dấu nghỉ đột xuất trước đó.',
                ]);
            }

            $lockedSchedule->update([
                'is_day_off' => true,
                'status' => Schedule::STATUS_CANCELLED,
            ]);

            $affectedCount = Appointment::where('schedule_id', $lockedSchedule->schedule_id)
                ->where('status', Appointment::STATUS_PENDING)
                ->update([
                    'is_affected_by_leave' => true,
                ]);

            return [
                'schedule' => $lockedSchedule->fresh(['doctor.user', 'doctor.specialty', 'clinic']),
                'affected_count' => $affectedCount,
            ];
        });
    }
}
