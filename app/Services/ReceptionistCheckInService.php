<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceptionistCheckInService
{
    /**
     * Tra cứu danh sách lịch hẹn theo ngày và từ khóa tìm kiếm (Mã phiếu hẹn / CCCD / SĐT / Tên bệnh nhân) (UC_R01).
     *
     * @param  array{date?: string|null, search?: string|null, status?: string|null, doctor_id?: int|string|null}  $filters
     */
    public function getAppointmentsForReceptionist(array $filters = []): Collection
    {
        $date = $filters['date'] ?? Carbon::today()->toDateString();
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
        $doctorId = !empty($filters['doctor_id']) ? (int) $filters['doctor_id'] : null;

        $query = Appointment::with(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);

        if ($date !== '') {
            $query->whereDate('appointment_date', $date);
        }

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_code', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('cccd', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->orderByRaw('CASE WHEN queue_number IS NULL THEN 1 ELSE 0 END')
            ->orderBy('queue_number')
            ->orderBy('appointment_id')
            ->get();
    }

    /**
     * Lấy danh sách các lịch hẹn bị ảnh hưởng do bác sĩ nghỉ đột xuất (is_affected_by_leave = true) (UC_R04, LH16).
     */
    public function getAffectedByLeaveAppointments(?string $search = null): Collection
    {
        $search = $search ? trim($search) : '';

        $query = Appointment::with(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule'])
            ->where('is_affected_by_leave', true);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_code', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('cccd', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->orderBy('appointment_id')
            ->get();
    }

    /**
     * Thống kê nhanh số lượng lịch hẹn theo ngày phục vụ Bàn Lễ tân.
     *
     * @return array{total: int, pending: int, checked_in: int, in_progress: int, completed: int, cancelled: int, affected_by_leave: int}
     */
    public function getDailySummary(string $date): array
    {
        $dailyAppointments = Appointment::whereDate('appointment_date', $date)->get(['status']);

        $affectedCount = Appointment::where('is_affected_by_leave', true)
            ->where('status', Appointment::STATUS_PENDING)
            ->count();

        return [
            'total' => $dailyAppointments->count(),
            'pending' => $dailyAppointments->where('status', Appointment::STATUS_PENDING)->count(),
            'checked_in' => $dailyAppointments->where('status', Appointment::STATUS_CHECKED_IN)->count(),
            'in_progress' => $dailyAppointments->where('status', Appointment::STATUS_IN_PROGRESS)->count(),
            'completed' => $dailyAppointments->where('status', Appointment::STATUS_COMPLETED)->count(),
            'cancelled' => $dailyAppointments->where('status', Appointment::STATUS_CANCELLED)->count(),
            'affected_by_leave' => $affectedCount,
        ];
    }

    /**
     * Tiếp nhận bệnh nhân tại quầy (Check-in):
     * - Chuyển trạng thái từ pending -> checked_in (UC_R02, PQ07, SC04)
     * - Cấp số thứ tự hàng đợi (queue_number) tăng dần theo Bác sĩ trong ngày.
     */
    public function checkIn(User $actor, Appointment $appointment): Appointment
    {
        if (!$actor->isReceptionist()) {
            throw new AuthorizationException('Chỉ nhân viên Lễ tân mới có quyền tiếp nhận bệnh nhân (PQ07).');
        }

        return DB::transaction(function () use ($appointment) {
            $lockedAppointment = Appointment::with(['schedule', 'doctor.user', 'patient'])
                ->where('appointment_id', $appointment->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAppointment->status !== Appointment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ có thể tiếp nhận lịch hẹn đang ở trạng thái Chờ khám (Pending).',
                ]);
            }

            if (
                $lockedAppointment->is_affected_by_leave
                || $lockedAppointment->schedule?->is_day_off
                || $lockedAppointment->schedule?->status === Schedule::STATUS_CANCELLED
            ) {
                throw ValidationException::withMessages([
                    'appointment' => 'Lịch hẹn này thuộc ca khám bác sĩ nghỉ đột xuất, không thể tiếp nhận. Vui lòng hỗ trợ bệnh nhân đổi lịch (LH16).',
                ]);
            }

            $dateString = Carbon::parse($lockedAppointment->appointment_date)->format('Y-m-d');

            $maxQueueNumber = Appointment::whereDate('appointment_date', $dateString)
                ->where('doctor_id', $lockedAppointment->doctor_id)
                ->lockForUpdate()
                ->max('queue_number');

            $nextQueueNumber = ((int) $maxQueueNumber) + 1;

            $lockedAppointment->update([
                'status' => Appointment::STATUS_CHECKED_IN,
                'queue_number' => $nextQueueNumber,
            ]);

            return $lockedAppointment->fresh(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);
        });
    }

    /**
     * Đánh dấu vắng mặt (No-show) khi bệnh nhân đến muộn >= 15 phút so với giờ bắt đầu khám (UC_R03, SC04 mục 4):
     * - Chỉ cho phép khi trạng thái là pending và thời gian hiện tại >= start_time + 15 phút.
     * - Chuyển trạng thái sang cancelled với lý do 'Vắng mặt (No-show)'.
     * - Giải phóng lại khung giờ khám nếu khung giờ đang ở trạng thái full.
     */
    public function markNoShow(User $actor, Appointment $appointment): Appointment
    {
        if (!$actor->isReceptionist()) {
            throw new AuthorizationException('Chỉ nhân viên Lễ tân mới có quyền đánh dấu vắng mặt.');
        }

        return DB::transaction(function () use ($appointment) {
            $lockedAppointment = Appointment::with(['schedule', 'doctor.user', 'patient'])
                ->where('appointment_id', $appointment->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAppointment->status !== Appointment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ có thể đánh dấu vắng mặt cho lịch hẹn đang ở trạng thái Chờ khám (Pending).',
                ]);
            }

            if (!$lockedAppointment->isNoShowEligible()) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chưa đủ điều kiện đánh dấu vắng mặt. Chỉ được phép đánh dấu vắng mặt khi đã quá giờ bắt đầu khám từ 15 phút trở lên (SC04).',
                ]);
            }

            $lockedAppointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'cancel_reason' => 'Vắng mặt (No-show)',
            ]);

            $this->releaseScheduleSlot($lockedAppointment->schedule_id);

            return $lockedAppointment->fresh(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);
        });
    }

    /**
     * Giải phóng khung giờ khám về available khi lịch hẹn bị hủy do vắng mặt (SC04 mục 4).
     */
    private function releaseScheduleSlot(?int $scheduleId): void
    {
        if (!$scheduleId) {
            return;
        }

        $schedule = Schedule::where('schedule_id', $scheduleId)
            ->lockForUpdate()
            ->first();

        if (!$schedule || $schedule->is_day_off || $schedule->status === Schedule::STATUS_CANCELLED) {
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
