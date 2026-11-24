<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Services\AdminScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly AdminScheduleService $scheduleService
    ) {}

    /**
     * Danh sách lịch làm việc của Bác sĩ & form tạo khung giờ mới (UC_A03, UC_A04).
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,doctor_id'],
            'status' => ['nullable', 'string', 'in:all,available,full,cancelled'],
        ]);

        $selectedDate = array_key_exists('date', $validated)
            ? (string) $validated['date']
            : Carbon::today()->toDateString();
        $selectedDoctorId = $validated['doctor_id'] ?? null;
        $selectedStatus = $validated['status'] ?? 'all';

        $query = Schedule::with([
            'doctor.user',
            'doctor.specialty',
            'clinic',
            'appointments' => function ($q) {
                $q->whereIn('status', [
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CHECKED_IN,
                    Appointment::STATUS_IN_PROGRESS,
                ]);
            },
        ]);

        if ($selectedDate !== '') {
            $query->whereDate('work_date', $selectedDate);
        }

        if ($selectedDoctorId) {
            $query->where('doctor_id', $selectedDoctorId);
        }

        if ($selectedStatus !== 'all') {
            $query->where('status', $selectedStatus);
        }

        $schedules = $query
            ->orderBy('work_date')
            ->orderBy('start_time')
            ->orderBy('doctor_id')
            ->limit(150)
            ->get();

        return view('admin.schedules', [
            'schedules' => $schedules,
            'doctors' => Doctor::with(['user', 'specialty', 'clinic'])->orderBy('doctor_id')->get(),
            'clinics' => Clinic::orderBy('clinic_id')->get(),
            'selectedDate' => $selectedDate,
            'selectedDoctorId' => $selectedDoctorId,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    /**
     * Công bố khung giờ khám mới cho bác sĩ (UC_A03).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,doctor_id'],
            'clinic_id' => ['required', 'integer', 'exists:clinics,clinic_id'],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'max_patients' => ['nullable', 'integer', 'min:1', 'max:10'],
        ], [
            'doctor_id.required' => 'Vui lòng chọn bác sĩ.',
            'clinic_id.required' => 'Vui lòng chọn cơ sở khám.',
            'work_date.required' => 'Vui lòng chọn ngày làm việc.',
            'start_time.required' => 'Vui lòng nhập giờ bắt đầu.',
            'end_time.required' => 'Vui lòng nhập giờ kết thúc.',
        ]);

        $schedule = $this->scheduleService->createSchedule($validated);

        return redirect()
            ->route('admin.schedules.index', ['date' => $schedule->work_date->format('Y-m-d')])
            ->with('success', "Đã tạo khung giờ khám {$validated['start_time']}–{$validated['end_time']} ngày {$schedule->work_date->format('d/m/Y')} thành công.");
    }

    /**
     * Đánh dấu ca/khung giờ nghỉ đột xuất & gắn cờ lịch hẹn bị ảnh hưởng cho Lễ tân (UC_A04, LH16).
     */
    public function markDayOff(Request $request, Schedule $schedule): RedirectResponse|JsonResponse
    {
        $result = $this->scheduleService->markDayOff($schedule);
        $affectedCount = $result['affected_count'];

        $message = $affectedCount > 0
            ? "Đã đóng ca nghỉ đột xuất (LH16) và tự động gắn cờ cảnh báo cho {$affectedCount} lịch hẹn Pending chuyển sang danh sách xử lý của Lễ tân."
            : 'Đã đóng ca nghỉ đột xuất (LH16). Không có lịch hẹn Pending nào bị ảnh hưởng.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }
}
