<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\ReceptionistCheckInService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CheckInController extends Controller
{
    public function __construct(
        private readonly ReceptionistCheckInService $checkInService
    ) {
    }

    /**
     * Màn hình Bàn Tiếp Đón Lễ Tân: Tra cứu lịch hẹn theo ngày, Tiếp nhận, Xử lý vắng mặt & Theo dõi nghỉ đột xuất (UC_R01–UC_R04).
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:all,pending,checked_in,in_progress,completed,cancelled'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,doctor_id'],
            'tab' => ['nullable', 'string', 'in:daily,affected'],
        ]);

        $selectedDate = array_key_exists('date', $validated)
            ? (string) $validated['date']
            : Carbon::today()->toDateString();

        $search = $validated['search'] ?? '';
        $selectedStatus = $validated['status'] ?? 'all';
        $selectedDoctorId = $validated['doctor_id'] ?? null;
        $activeTab = $validated['tab'] ?? 'daily';

        $appointments = $this->checkInService->getAppointmentsForReceptionist([
            'date' => $selectedDate,
            'search' => $search,
            'status' => $selectedStatus,
            'doctor_id' => $selectedDoctorId,
        ]);

        $affectedAppointments = $this->checkInService->getAffectedByLeaveAppointments($search);
        $summary = $this->checkInService->getDailySummary($selectedDate !== '' ? $selectedDate : Carbon::today()->toDateString());
        $doctors = Doctor::with(['user', 'specialty'])->orderBy('doctor_id')->get();

        return view('receptionist.check-in', [
            'appointments' => $appointments,
            'affectedAppointments' => $affectedAppointments,
            'summary' => $summary,
            'doctors' => $doctors,
            'selectedDate' => $selectedDate,
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'selectedDoctorId' => $selectedDoctorId,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Tiếp nhận bệnh nhân tại quầy: Chuyển trạng thái pending -> checked_in & cấp số thứ tự queue_number (UC_R02, PQ07).
     */
    public function checkIn(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        try {
            $updated = $this->checkInService->checkIn($request->user(), $appointment);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Tiếp nhận bệnh nhân thành công.',
                'data' => $updated,
            ]);
        }

        return redirect()
            ->back()
            ->with(
                'success',
                "Đã tiếp nhận bệnh nhân {$updated->patient?->name} (Mã phiếu: {$updated->appointment_code}) — Số thứ tự hàng đợi: #{$updated->queue_number}."
            );
    }

    /**
     * Đánh dấu vắng mặt (No-show) khi đã quá giờ bắt đầu khám >= 15 phút (UC_R03, SC04 mục 4).
     */
    public function markNoShow(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        try {
            $updated = $this->checkInService->markNoShow($request->user(), $appointment);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã đánh dấu vắng mặt thành công.',
                'data' => $updated,
            ]);
        }

        return redirect()
            ->back()
            ->with(
                'success',
                "Đã đánh dấu vắng mặt (No-show) cho phiếu hẹn {$updated->appointment_code} và giải phóng khung giờ khám."
            );
    }
}
