<?php

use App\Http\Controllers\Patient\SymptomAnalysisController;
use App\Http\Controllers\ProfileController;
use App\Models\Specialty;
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
});

// Nhóm route Lễ tân (Receptionist Portal)
Route::middleware(['auth', 'role:receptionist'])->prefix('receptionist')->name('receptionist.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboards.receptionist');
    })->name('dashboard');
});

// Nhóm route Bệnh nhân (Patient Portal)
Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboards.patient');
    })->name('dashboard');

    // Luồng Đặt lịch khám: Bắt buộc hồ sơ phải đủ CCCD, SĐT, Ngày sinh (SC03, LH17)
    Route::middleware('profile.completed')->group(function () {
        Route::get('/booking', function () {
            return view('patient.booking', [
                'specialties' => Specialty::with(['doctors.user', 'doctors.clinic', 'symptoms'])->get(),
            ]);
        })->name('booking');

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
