<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\Symptom;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Màn hình Quản lý Danh mục Y tế: Chuyên khoa, Bác sĩ, Triệu chứng (UC_A02, PQ10).
     */
    public function index(Request $request): View
    {
        $activeTab = in_array($request->query('tab'), ['specialties', 'doctors', 'symptoms'], true)
            ? (string) $request->query('tab')
            : 'specialties';

        return view('admin.catalog', [
            'activeTab' => $activeTab,
            'specialties' => Specialty::withCount(['doctors', 'symptoms'])->orderBy('specialty_id')->get(),
            'doctors' => Doctor::with(['user', 'specialty', 'clinic'])->orderBy('doctor_id')->get(),
            'symptoms' => Symptom::with('specialty')->orderBy('symptom_id')->get(),
            'clinics' => Clinic::orderBy('clinic_id')->get(),
        ]);
    }

    /**
     * Thêm Chuyên khoa mới.
     */
    public function storeSpecialty(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'specialty_name' => ['required', 'string', 'max:100', 'unique:specialties,specialty_name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'specialty_name.required' => 'Vui lòng nhập tên chuyên khoa.',
            'specialty_name.unique' => 'Tên chuyên khoa đã tồn tại.',
        ]);

        Specialty::create($validated);

        return redirect()
            ->route('admin.catalog.index', ['tab' => 'specialties'])
            ->with('success', "Đã thêm chuyên khoa \"{$validated['specialty_name']}\" thành công.");
    }

    /**
     * Xóa Chuyên khoa (chặn nếu vẫn còn bác sĩ trực thuộc).
     */
    public function destroySpecialty(Specialty $specialty): RedirectResponse
    {
        if ($specialty->doctors()->exists()) {
            return redirect()
                ->route('admin.catalog.index', ['tab' => 'specialties'])
                ->withErrors(['specialty' => "Không thể xóa chuyên khoa \"{$specialty->specialty_name}\" vì đang có bác sĩ trực thuộc."]);
        }

        $name = $specialty->specialty_name;
        $specialty->delete();

        return redirect()
            ->route('admin.catalog.index', ['tab' => 'specialties'])
            ->with('success', "Đã xóa chuyên khoa \"{$name}\".");
    }

    /**
     * Thêm Triệu chứng mới gắn vào chuyên khoa (phục vụ AI Matcher).
     */
    public function storeSymptom(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'symptom_code' => ['required', 'string', 'max:50', 'unique:symptoms,symptom_code'],
            'symptom_name' => ['required', 'string', 'max:150'],
            'specialty_id' => ['required', 'integer', 'exists:specialties,specialty_id'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'symptom_code.required' => 'Vui lòng nhập mã triệu chứng.',
            'symptom_code.unique' => 'Mã triệu chứng đã tồn tại.',
            'symptom_name.required' => 'Vui lòng nhập tên triệu chứng.',
            'specialty_id.required' => 'Vui lòng chọn chuyên khoa liên kết.',
        ]);

        Symptom::create([
            'symptom_code' => strtoupper(trim($validated['symptom_code'])),
            'symptom_name' => trim($validated['symptom_name']),
            'specialty_id' => $validated['specialty_id'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.catalog.index', ['tab' => 'symptoms'])
            ->with('success', "Đã thêm triệu chứng \"{$validated['symptom_name']}\" thành công.");
    }

    /**
     * Xóa Triệu chứng.
     */
    public function destroySymptom(Symptom $symptom): RedirectResponse
    {
        $name = $symptom->symptom_name;
        $symptom->delete();

        return redirect()
            ->route('admin.catalog.index', ['tab' => 'symptoms'])
            ->with('success', "Đã xóa triệu chứng \"{$name}\".");
    }

    /**
     * Tạo mới tài khoản & hồ sơ chuyên môn Bác sĩ (UC_A01, UC_A02).
     */
    public function storeDoctor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'specialty_id' => ['required', 'integer', 'exists:specialties,specialty_id'],
            'clinic_id' => ['required', 'integer', 'exists:clinics,clinic_id'],
            'degree' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên bác sĩ.',
            'email.required' => 'Vui lòng nhập email đăng nhập.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu khởi tạo.',
        ]);

        DB::transaction(function () use ($validated) {
            $doctorRole = Role::where('role_name', Role::ROLE_DOCTOR)->firstOrFail();

            $user = User::create([
                'role_id' => $doctorRole->role_id,
                'name' => trim($validated['name']),
                'email' => trim($validated['email']),
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
            ]);

            Doctor::create([
                'user_id' => $user->id,
                'specialty_id' => $validated['specialty_id'],
                'clinic_id' => $validated['clinic_id'],
                'degree' => $validated['degree'] ?? 'Bác sĩ',
                'experience_years' => $validated['experience_years'] ?? 1,
                'bio' => $validated['bio'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.catalog.index', ['tab' => 'doctors'])
            ->with('success', "Đã tạo hồ sơ bác sĩ \"{$validated['name']}\" thành công.");
    }
}
