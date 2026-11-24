<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Specialty;
use App\Models\Symptom;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Bảng điều khiển Quản trị viên kèm báo cáo thống kê tổng hợp (UC_A05).
     */
    public function index(): View
    {
        $allAppointments = Appointment::with('doctor.specialty')->get();
        $total = $allAppointments->count();

        $pending = $allAppointments->where('status', Appointment::STATUS_PENDING)->count();
        $checkedIn = $allAppointments->where('status', Appointment::STATUS_CHECKED_IN)->count();
        $inProgress = $allAppointments->where('status', Appointment::STATUS_IN_PROGRESS)->count();
        $completed = $allAppointments->where('status', Appointment::STATUS_COMPLETED)->count();
        $cancelled = $allAppointments->where('status', Appointment::STATUS_CANCELLED)->count();
        $noShow = $allAppointments
            ->where('status', Appointment::STATUS_CANCELLED)
            ->filter(fn (Appointment $a) => str_contains((string) $a->cancel_reason, 'Vắng mặt'))
            ->count();
        $affectedByLeave = $allAppointments
            ->where('is_affected_by_leave', true)
            ->where('status', Appointment::STATUS_PENDING)
            ->count();

        $attendedCount = $checkedIn + $inProgress + $completed;
        $attendanceRate = $total > 0 ? round(($attendedCount / $total) * 100, 1) : 0.0;
        $cancellationRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : 0.0;

        $specialtyStats = Specialty::withCount(['doctors', 'symptoms'])
            ->get()
            ->map(function (Specialty $specialty) use ($allAppointments) {
                $count = $allAppointments->filter(
                    fn (Appointment $a) => (int) $a->doctor?->specialty_id === (int) $specialty->specialty_id
                )->count();

                return [
                    'specialty_name' => $specialty->specialty_name,
                    'doctors_count' => $specialty->doctors_count,
                    'symptoms_count' => $specialty->symptoms_count,
                    'appointments_count' => $count,
                ];
            });

        return view('dashboards.admin', [
            'stats' => [
                'total_appointments' => $total,
                'pending' => $pending,
                'checked_in' => $checkedIn,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'no_show' => $noShow,
                'affected_by_leave' => $affectedByLeave,
                'attendance_rate' => $attendanceRate,
                'cancellation_rate' => $cancellationRate,
                'specialties_count' => Specialty::count(),
                'doctors_count' => Doctor::count(),
                'symptoms_count' => Symptom::count(),
                'day_off_schedules_count' => Schedule::where('is_day_off', true)->count(),
            ],
            'specialtyStats' => $specialtyStats,
        ]);
    }
}
