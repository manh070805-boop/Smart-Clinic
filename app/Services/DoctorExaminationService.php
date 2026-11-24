<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorExaminationService
{
    /**
     * Lấy hồ sơ Bác sĩ gắn với tài khoản đang đăng nhập.
     *
     * @throws AuthorizationException
     */
    public function resolveDoctorProfile(User $user): Doctor
    {
        if (! $user->isDoctor() || ! $user->doctor) {
            throw new AuthorizationException('Chỉ Bác sĩ có hồ sơ chuyên môn hợp lệ mới được phép truy cập phân hệ khám bệnh.');
        }

        return $user->doctor->loadMissing(['specialty', 'clinic', 'user']);
    }

    /**
     * Lấy danh sách hàng đợi khám bệnh của chính Bác sĩ đang đăng nhập (UC_D01, PQ05).
     *
     * @param  array{date?: string|null, search?: string|null, status?: string|null}  $filters
     */
    public function getQueueAppointments(User $doctorUser, array $filters = []): Collection
    {
        $doctor = $this->resolveDoctorProfile($doctorUser);

        $date = array_key_exists('date', $filters)
            ? trim((string) $filters['date'])
            : Carbon::today()->toDateString();
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';

        $query = Appointment::with(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule'])
            ->where('doctor_id', $doctor->doctor_id);

        if ($date !== '') {
            $query->whereDate('appointment_date', $date);
        }

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_code', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('cccd', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderByRaw("CASE status
                WHEN 'in_progress' THEN 1
                WHEN 'checked_in' THEN 2
                WHEN 'pending' THEN 3
                WHEN 'completed' THEN 4
                WHEN 'cancelled' THEN 5
                ELSE 6
            END")
            ->orderByRaw('CASE WHEN queue_number IS NULL THEN 1 ELSE 0 END')
            ->orderBy('queue_number')
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->orderBy('appointment_id')
            ->get();
    }

    /**
     * Thống kê nhanh hàng đợi trong ngày của Bác sĩ.
     *
     * @return array{total: int, pending: int, checked_in: int, in_progress: int, completed: int, cancelled: int}
     */
    public function getDailySummary(User $doctorUser, string $date): array
    {
        $doctor = $this->resolveDoctorProfile($doctorUser);

        $appointments = Appointment::where('doctor_id', $doctor->doctor_id)
            ->whereDate('appointment_date', $date)
            ->get(['status']);

        return [
            'total' => $appointments->count(),
            'pending' => $appointments->where('status', Appointment::STATUS_PENDING)->count(),
            'checked_in' => $appointments->where('status', Appointment::STATUS_CHECKED_IN)->count(),
            'in_progress' => $appointments->where('status', Appointment::STATUS_IN_PROGRESS)->count(),
            'completed' => $appointments->where('status', Appointment::STATUS_COMPLETED)->count(),
            'cancelled' => $appointments->where('status', Appointment::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * Bắt đầu khám bệnh: chỉ cho phép chuyển từ checked_in -> in_progress (UC_D02, PQ05–PQ06, SC04 mục 3).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function startExam(User $actor, Appointment $appointment): Appointment
    {
        $doctor = $this->resolveDoctorProfile($actor);

        return DB::transaction(function () use ($doctor, $appointment) {
            $lockedAppointment = Appointment::with(['patient', 'doctor.user', 'doctor.specialty', 'schedule'])
                ->where('appointment_id', $appointment->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedAppointment->doctor_id !== (int) $doctor->doctor_id) {
                throw new AuthorizationException('Bạn không có quyền thao tác trên lượt khám của bác sĩ khác (PQ06).');
            }

            if ($lockedAppointment->status === Appointment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'appointment' => 'Không thể bắt đầu khám khi bệnh nhân chưa được Lễ tân tiếp nhận (Check-in) theo quy định SC04.',
                ]);
            }

            if ($lockedAppointment->status !== Appointment::STATUS_CHECKED_IN) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ có thể bắt đầu khám cho lịch hẹn đang ở trạng thái Đã tiếp nhận (Checked-in).',
                ]);
            }

            $lockedAppointment->update([
                'status' => Appointment::STATUS_IN_PROGRESS,
            ]);

            return $lockedAppointment->fresh(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);
        });
    }

    /**
     * Hoàn tất lượt khám: chỉ cho phép chuyển từ in_progress -> completed (UC_D02, PQ05–PQ06, SC04 mục 3).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function completeExam(User $actor, Appointment $appointment): Appointment
    {
        $doctor = $this->resolveDoctorProfile($actor);

        return DB::transaction(function () use ($doctor, $appointment) {
            $lockedAppointment = Appointment::with(['patient', 'doctor.user', 'doctor.specialty', 'schedule'])
                ->where('appointment_id', $appointment->appointment_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedAppointment->doctor_id !== (int) $doctor->doctor_id) {
                throw new AuthorizationException('Bạn không có quyền hoàn tất lượt khám của bác sĩ khác (PQ06).');
            }

            if ($lockedAppointment->status === Appointment::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'appointment' => 'Lượt khám này đã được hoàn thành trước đó và không thể thay đổi trạng thái (SC04).',
                ]);
            }

            if ($lockedAppointment->status !== Appointment::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages([
                    'appointment' => 'Chỉ có thể hoàn tất lượt khám đang ở trạng thái Đang khám (In Progress) theo quy định SC04.',
                ]);
            }

            $lockedAppointment->update([
                'status' => Appointment::STATUS_COMPLETED,
            ]);

            return $lockedAppointment->fresh(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);
        });
    }
}
