<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\DoctorExaminationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ExaminationController extends Controller
{
    public function __construct(
        private readonly DoctorExaminationService $examinationService
    ) {}

    /**
     * Màn hình Hàng đợi Khám & Chi tiết Triệu chứng của Bác sĩ (UC_D01, UC_D03, PQ05).
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:all,checked_in,in_progress,completed,pending,cancelled'],
        ]);

        $selectedDate = array_key_exists('date', $validated)
            ? (string) $validated['date']
            : Carbon::today()->toDateString();

        $search = $validated['search'] ?? '';
        $selectedStatus = $validated['status'] ?? 'all';

        try {
            $doctor = $this->examinationService->resolveDoctorProfile($request->user());
            $appointments = $this->examinationService->getQueueAppointments($request->user(), [
                'date' => $selectedDate,
                'search' => $search,
                'status' => $selectedStatus,
            ]);
            $summary = $this->examinationService->getDailySummary(
                $request->user(),
                $selectedDate !== '' ? $selectedDate : Carbon::today()->toDateString()
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return view('doctor.queue', [
            'doctor' => $doctor,
            'appointments' => $appointments,
            'summary' => $summary,
            'selectedDate' => $selectedDate,
            'search' => $search,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    /**
     * Bắt đầu khám bệnh: Checked-in -> In Progress (UC_D02, SC04).
     */
    public function startExam(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        try {
            $updated = $this->examinationService->startExam($request->user(), $appointment);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã bắt đầu khám bệnh.',
                'data' => $updated,
            ]);
        }

        return redirect()
            ->back()
            ->with(
                'success',
                "Đã gọi bệnh nhân {$updated->patient?->name} (Mã phiếu: {$updated->appointment_code}) vào phòng — Trạng thái: Đang khám (In Progress)."
            );
    }

    /**
     * Hoàn tất lượt khám: In Progress -> Completed (UC_D02, SC04).
     */
    public function completeExam(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        try {
            $updated = $this->examinationService->completeExam($request->user(), $appointment);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã hoàn tất lượt khám.',
                'data' => $updated,
            ]);
        }

        return redirect()
            ->back()
            ->with(
                'success',
                "Đã hoàn tất lượt khám cho bệnh nhân {$updated->patient?->name} (Mã phiếu: {$updated->appointment_code})."
            );
    }
}
