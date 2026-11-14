<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentManagementService $managementService
    ) {}

    /**
     * Danh sách lịch sử đặt lịch & quản lý hủy/đổi lịch của bệnh nhân.
     */
    public function index(Request $request): View
    {
        $appointments = $this->managementService->getPatientAppointments($request->user());

        return view('patient.appointments', [
            'appointments' => $appointments,
        ]);
    }

    /**
     * API lấy danh sách khung giờ khả dụng cùng chuyên khoa để đổi lịch.
     */
    public function rescheduleSlots(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'work_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        try {
            $slots = $this->managementService->getAvailableRescheduleSlots(
                $request->user(),
                $appointment,
                $validated['work_date'] ?? null
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'data' => $slots,
        ]);
    }

    /**
     * Hủy lịch hẹn (LH07–LH11).
     */
    public function cancel(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy lịch.',
            'cancel_reason.min' => 'Lý do hủy phải có ít nhất 3 ký tự.',
        ]);

        try {
            $updated = $this->managementService->cancel(
                $request->user(),
                $appointment,
                $validated['cancel_reason']
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Hủy lịch hẹn thành công.',
                'data' => $updated,
            ]);
        }

        return redirect()
            ->route('patient.appointments.index')
            ->with('success', "Đã hủy lịch hẹn {$updated->appointment_code} và giải phóng khung giờ thành công.");
    }

    /**
     * Đổi lịch hẹn sang khung giờ mới cùng chuyên khoa (LH12–LH14).
     */
    public function reschedule(Request $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'new_schedule_id' => ['required', 'integer', 'exists:schedules,schedule_id'],
        ], [
            'new_schedule_id.required' => 'Vui lòng chọn khung giờ khám mới.',
            'new_schedule_id.exists' => 'Khung giờ khám mới không tồn tại.',
        ]);

        try {
            $updated = $this->managementService->reschedule(
                $request->user(),
                $appointment,
                (int) $validated['new_schedule_id']
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đổi lịch hẹn thành công.',
                'data' => $updated,
            ]);
        }

        return redirect()
            ->route('patient.appointments.index')
            ->with('success', "Đã đổi lịch hẹn {$updated->appointment_code} sang khung giờ mới thành công.");
    }
}
