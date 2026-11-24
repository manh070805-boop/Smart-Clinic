<?php

use App\Http\Controllers\Doctor\ExaminationController;
use App\Http\Controllers\Patient\AppointmentController;
use App\Http\Controllers\Patient\BookingController;
use App\Http\Controllers\Patient\SymptomAnalysisController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Receptionist\CheckInController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Dispatcher trung tâm điều hướng sau khi đăng nhập theo vai trò
Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->isDoctor()) {
        return redirect()->route('doctor.dashboard');
    }
    if ($user->isReceptionist()) {
        return redirect()->route('receptionist.dashboard');
    }
    if ($user->isPatient()) {
        return redirect()->route('patient.dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Nhóm route Quản trị viên (Admin Portal)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboards.admin');
    })->name('dashboard');
});

// Nhóm route Bác sĩ (Doctor Portal)
Route::middleware(['auth', 'role:doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboards.doctor');
    })->name('dashboard');

    // Hàng đợi khám bệnh, xem triệu chứng AI & cập nhật tiến trình khám (T4.2)
    Route::get('/queue', [ExaminationController::class, 'index'])->name('queue.index');
    Route::post('/appointments/{appointment}/start', [ExaminationController::class, 'startExam'])->name('appointments.start');
    Route::post('/appointments/{appointment}/complete', [ExaminationController::class, 'completeExam'])->name('appointments.complete');
});

// Nhóm route Lễ tân (Receptionist Portal)
Route::middleware(['auth', 'role:receptionist'])->prefix('receptionist')->name('receptionist.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboards.receptionist');
    })->name('dashboard');

    // Tiếp nhận (Check-in), cấp số thứ tự, đánh dấu vắng mặt sau 15 phút & theo dõi nghỉ đột xuất (T4.1)
    Route::get('/check-in', [CheckInController::class, 'index'])->name('checkin.index');
    Route::post('/appointments/{appointment}/check-in', [CheckInController::class, 'checkIn'])->name('appointments.check-in');
    Route::post('/appointments/{appointment}/no-show', [CheckInController::class, 'markNoShow'])->name('appointments.no-show');
});

// Nhóm route Bệnh nhân (Patient Portal)
Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboards.patient');
    })->name('dashboard');

    // Quản lý lịch sử đặt lịch, hủy lịch & đổi lịch của Bệnh nhân (T3.3)
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/{appointment}/reschedule-slots', [AppointmentController::class, 'rescheduleSlots'])->name('appointments.reschedule-slots');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');

    // Luồng Đặt lịch khám: Bắt buộc hồ sơ phải đủ CCCD, SĐT, Ngày sinh (SC03, LH17)
    Route::middleware('profile.completed')->group(function () {
        Route::get('/booking', [BookingController::class, 'index'])->name('booking');
        Route::get('/booking/schedules', [BookingController::class, 'schedules'])->name('booking.schedules');
        Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');
        Route::get('/appointments/{appointment}/receipt', [BookingController::class, 'receipt'])->name('booking.receipt');

        // API Phân tích triệu chứng tự nhiên & Gợi ý chuyên khoa (T3.1)
        Route::post('/symptoms/analyze', SymptomAnalysisController::class)->name('symptoms.analyze');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
