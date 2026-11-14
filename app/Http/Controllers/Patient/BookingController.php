<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Specialty;
use App\Services\AppointmentBookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Hiển thị màn hình Đặt lịch khám (Bước 1 AI -> Bước 2 Khoa/BS -> Bước 3 Ngày/Giờ -> Bước 4 Xác nhận).
     */
    public function index(): View
    {
        $specialties = Specialty::with(['doctors.user', 'doctors.clinic', 'symptoms'])->get();

        $availableDates = collect(range(0, 6))->map(function (int $offset) {
            $date = Carbon::today()->addDays($offset);

            return [
                'value' => $date->format('Y-m-d'),
                'label' => $offset === 0 ? 'Hôm nay ('.$date->format('d/m').')' : ($offset === 1 ? 'Ngày mai ('.$date->format('d/m').')' : $date->locale('vi')->isoFormat('ddd, DD/MM')),
            ];
        });

        return view('patient.booking', [
            'specialties' => $specialties,
            'availableDates' => $availableDates,
        ]);
    }

    /**
     * API lấy danh sách khung giờ khám của Bác sĩ theo ngày (AJAX cho Bước 3).
     */
    public function schedules(Request $request, AppointmentBookingService $bookingService): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,doctor_id'],
            'work_date' => ['required', 'date_format:Y-m-d'],
        ]);

        $schedules = $bookingService->getSchedulesForDoctorAndDate(
            (int) $validated['doctor_id'],
            $validated['work_date']
        );

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    /**
     * Xác nhận đặt lịch khám (Transaction + LockForUpdate chống trùng chỗ).
     */
    public function store(Request $request, AppointmentBookingService $bookingService): RedirectResponse|JsonResponse
    {
        $validated = $request->validate(
            [
                'schedule_id' => ['required', 'integer', 'exists:schedules,schedule_id'],
                'symptom_description' => ['nullable', 'string', 'max:1000'],
                'ai_extracted_data' => ['nullable', 'string'],
            ],
            [
                'schedule_id.required' => 'Vui lòng chọn một khung giờ khám còn trống.',
                'schedule_id.exists' => 'Khung giờ khám đã chọn không hợp lệ.',
                'symptom_description.max' => 'Mô tả triệu chứng không được vượt quá 1000 ký tự.',
            ]
        );

        $aiData = null;
        if (! empty($validated['ai_extracted_data'])) {
            $decoded = json_decode($validated['ai_extracted_data'], true);
            if (is_array($decoded)) {
                $aiData = $decoded;
            }
        }

        $result = $bookingService->book(
            patient: $request->user(),
            scheduleId: (int) $validated['schedule_id'],
            symptomDescription: $validated['symptom_description'] ?? null,
            aiExtractedData: $aiData
        );

        $appointment = $result['appointment'];
        $message = $result['is_duplicate_request']
            ? 'Yêu cầu đặt lịch cho khung giờ này đã được ghi nhận trước đó. Đây là phiếu hẹn hiện tại của bạn.'
            : 'Đặt lịch khám thành công! Mã phiếu hẹn của bạn là '.$appointment->appointment_code.'.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'is_duplicate_request' => $result['is_duplicate_request'],
                'data' => $appointment,
            ], $result['is_duplicate_request'] ? 200 : 201);
        }

        return redirect()
            ->route('patient.booking.receipt', $appointment)
            ->with('success', $message);
    }

    /**
     * Hiển thị Phiếu hẹn điện tử sau khi đặt lịch thành công.
     */
    public function receipt(Request $request, Appointment $appointment): View
    {
        abort_if($appointment->patient_id !== $request->user()->id, 403, 'Bạn không có quyền xem phiếu hẹn của người khác.');

        $appointment->load(['patient', 'doctor.user', 'doctor.specialty', 'doctor.clinic', 'schedule']);

        return view('patient.receipt', [
            'appointment' => $appointment,
        ]);
    }
}

