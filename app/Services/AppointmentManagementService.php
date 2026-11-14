<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentManagementService
{
    /**
     * Lấy danh sách lịch hẹn của bệnh nhân (mới nhất lên đầu).
     */
    public function getPatientAppointments(User $patient): Collection
    {
        return Appointment::with(['doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule'])
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->orderByDesc('appointment_id')
            ->get();
    }

    /**
     * Lấy danh sách khung giờ khả dụng để đổi lịch (cùng chuyên khoa, trong tương lai, còn chỗ).
     */
    public function getAvailableRescheduleSlots(User $patient, Appointment $appointment, ?string $workDate = null): Collection
    {
        if ((int) $appointment->patient_id !== (int) $patient->id) {
            throw new AuthorizationException('Bạn không có quyền xem lịch đổi cho phiếu hẹn này.');
        }

        $appointment->loadMissing('doctor.specialty');
        $specialtyId = $appointment->doctor?->specialty_id;

        if (! $specialtyId) {
            return collect();
        }

        $doctorIds = Doctor::where('specialty_id', $specialtyId)->pluck('doctor_id');
        $today = Carbon::today()->toDateString();

        $query = Schedule::with(['doctor.user', 'doctor.clinic'])
            ->whereIn('doctor_id', $doctorIds)
            ->where('schedule_id', '!=', $appointment->schedule_id)
            ->where('is_day_off', false)
            ->where('status', Schedule::STATUS_AVAILABLE);

        if ($workDate) {
            $query->whereDate('work_date', $workDate);
        } else {
            $query->whereDate('work_date', '>=', $today)
                ->whereDate('work_date', '<=', Carbon::today()->addDays(7)->toDateString());
        }

        return $query->orderBy('work_date')
            ->orderBy('start_time')
            ->get()
            ->filter(function (Schedule $schedule) {
                $startDateTime = Carbon::parse($schedule->work_date->format('Y-m-d').' '.$schedule->start_time);

                return $startDateTime->isFuture() && $schedule->isBookable();
            })
            ->values()
            ->map(function (Schedule $schedule) {
                return [
                    'schedule_id' => $schedule->schedule_id,
                    'doctor_id' => $schedule->doctor_id,
                    'doctor_name' => $schedule->doctor?->user?->name,
                    'doctor_degree' => $schedule->doctor?->degree,
                    'clinic_name' => $schedule->doctor?->clinic?->clinic_name,
                    'work_date' => $schedule->work_date->format('Y-m-d'),
                    'work_date_label' => $schedule->work_date->format('d/m/Y'),
                    'start_time' => substr((string) $schedule->start_time, 0, 5),
                    'end_time' => substr((string) $schedule->end_time, 0, 5),
                ];
            });
    }

    /**
     * Hủy lịch hẹn của bệnh nhân (LH07–LH11).
     */
    public function cancel(User $patient, Appointment $appointment, string $cancelReason): Appointment
    {
        return DB::transaction(function () use ($patient, $appointment, $cancelReason) {
            $lockedAppointment = Appointment::with(['schedule'])
                ->where('appointment_id', $appointment->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedAppointment->patient_id !== (int) $patient->id) {
                throw new AuthorizationException('Bạn không có quyền hủy lịch hẹn của người khác (LH10).');
            }

            if ($lockedAppointment->status !== Appointment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ có thể hủy lịch hẹn đang ở trạng thái Chờ khám (LH09).',
                ]);
            }

            if (! $lockedAppointment->canBeCancelledOrRescheduled()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ được phép hủy lịch trước giờ bắt đầu khám ít nhất 2 giờ (LH08).',
                ]);
            }

            $lockedAppointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancel_reason' => trim($cancelReason),
            ]);

            $this->releaseScheduleSlot($lockedAppointment->schedule_id);

            return $lockedAppointment->fresh(['doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);
        });
    }

    /**
     * Đổi lịch hẹn sang khung giờ mới cùng chuyên khoa trong 1 DB::transaction() (LH12–LH14).
     */
    public function reschedule(User $patient, Appointment $appointment, int $newScheduleId): Appointment
    {
        return DB::transaction(function () use ($patient, $appointment, $newScheduleId) {
            $lockedAppointment = Appointment::with(['doctor', 'schedule'])
                ->where('appointment_id', $appointment->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedAppointment->patient_id !== (int) $patient->id) {
                throw new AuthorizationException('Bạn không có quyền đổi lịch hẹn của người khác (LH10).');
            }

            if ($lockedAppointment->status !== Appointment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ có thể đổi lịch hẹn đang ở trạng thái Chờ khám.',
                ]);
            }

            if (! $lockedAppointment->canBeCancelledOrRescheduled()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ được phép đổi lịch trước giờ bắt đầu khám cũ ít nhất 2 giờ.',
                ]);
            }

            if ((int) $lockedAppointment->schedule_id === $newScheduleId) {
                throw ValidationException::withMessages([
                    'new_schedule_id' => 'Vui lòng chọn khung giờ mới khác với lịch hẹn hiện tại.',
                ]);
            }

            $newSchedule = Schedule::with('doctor')
                ->where('schedule_id', $newScheduleId)
                ->lockForUpdate()
                ->firstOrFail();

            // Kiểm tra cùng chuyên khoa
            if ((int) $newSchedule->doctor?->specialty_id !== (int) $lockedAppointment->doctor?->specialty_id) {
                throw ValidationException::withMessages([
                    'new_schedule_id' => 'Chỉ được phép đổi sang khung giờ mới thuộc cùng chuyên khoa.',
                ]);
            }

            // Kiểm tra ngày nghỉ / ca hủy (LH04)
            if ($newSchedule->is_day_off || $newSchedule->status === Schedule::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'new_schedule_id' => 'Khung giờ mới thuộc ngày nghỉ hoặc ca khám đã ngừng hoạt động.',
                ]);
            }

            // Kiểm tra khung giờ mới phải ở tương lai (LH03)
            $newStartDateTime = Carbon::parse($newSchedule->work_date->format('Y-m-d').' '.$newSchedule->start_time);
            if (! $newStartDateTime->isFuture()) {
                throw ValidationException::withMessages([
                    'new_schedule_id' => 'Không thể đổi sang khung giờ đã bắt đầu hoặc trong quá khứ.',
                ]);
            }

            // Kiểm tra sức chứa khung giờ mới (LH13)
            $activeNewCount = Appointment::where('schedule_id', $newSchedule->schedule_id)
                ->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CHECKED_IN,
                    Appointment::STATUS_IN_PROGRESS,
                ])
                ->lockForUpdate()
                ->count();

            if ($newSchedule->status === Schedule::STATUS_FULL || $activeNewCount >= $newSchedule->max_patients) {
                throw ValidationException::withMessages([
                    'new_schedule_id' => 'Khung giờ mới vừa hết chỗ. Lịch hẹn cũ của bạn vẫn được giữ nguyên (LH13).',
                ]);
            }

            // Kiểm tra bệnh nhân không bị trùng giờ với lịch hẹn khác (LH15)
            $hasOverlap = Appointment::where('patient_id', $patient->id)
                ->where('appointment_id', '!=', $lockedAppointment->appointment_id)
                ->whereDate('appointment_date', $newSchedule->work_date->format('Y-m-d'))
                ->where('start_time', $newSchedule->start_time)
                ->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CHECKED_IN,
                    Appointment::STATUS_IN_PROGRESS,
                ])
                ->exists();

            if ($hasOverlap) {
                throw ValidationException::withMessages([
                    'new_schedule_id' => 'Bạn đã có lịch hẹn khác vào khung giờ mới này (LH15).',
                ]);
            }

            $oldScheduleId = $lockedAppointment->schedule_id;

            // Cập nhật lịch hẹn sang khung giờ mới
            $lockedAppointment->update([
                'doctor_id' => $newSchedule->doctor_id,
                'schedule_id' => $newSchedule->schedule_id,
                'appointment_date' => $newSchedule->work_date->format('Y-m-d'),
                'start_time' => $newSchedule->start_time,
                'is_affected_by_leave' => false,
                'rescheduled_at' => Carbon::now(),
            ]);

            // Đánh dấu khung giờ mới là Full nếu đã đạt sức chứa
            if (($activeNewCount + 1) >= $newSchedule->max_patients) {
                $newSchedule->update(['status' => Schedule::STATUS_FULL]);
            }

            // Giải phóng chỗ khung giờ cũ sau khi đã giữ chắc khung giờ mới
            $this->releaseScheduleSlot($oldScheduleId);

            return $lockedAppointment->fresh(['doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);
        });
    }

    /**
     * Giải phóng lại slot về trạng thái available nếu không phải ngày nghỉ/ca hủy.
     */
    private function releaseScheduleSlot(?int $scheduleId): void
    {
        if (! $scheduleId) {
            return;
        }

        $schedule = Schedule::where('schedule_id', $scheduleId)
            ->lockForUpdate()
            ->first();

        if (! $schedule || $schedule->is_day_off || $schedule->status === Schedule::STATUS_CANCELLED) {
            return;
        }

        $activeCount = Appointment::where('schedule_id', $schedule->schedule_id)
            ->whereIn('status', [
                Appointment::STATUS_PENDING,
                Appointment::STATUS_CHECKED_IN,
                Appointment::STATUS_IN_PROGRESS,
            ])
            ->count();

        if ($activeCount < $schedule->max_patients && $schedule->status === Schedule::STATUS_FULL) {
            $schedule->update(['status' => Schedule::STATUS_AVAILABLE]);
        }
    }
}
