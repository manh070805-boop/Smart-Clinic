<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-emerald-100 text-emerald-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </span>
                Quản Lý Lịch Làm Việc & Nghỉ Đột Xuất (UC_A03, UC_A04, LH16)
            </h2>
            <a href="{{ route('admin.dashboard') }}"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                &larr; Trang Quản Trị
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-semibold text-sm shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm shadow-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form tạo khung giờ khám mới (UC_A03) -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-900 mb-4">Công Bố Khung Giờ Khám Mới (UC_A03)</h3>
                <form method="POST" action="{{ route('admin.schedules.store') }}"
                    class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    @csrf
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Bác sĩ *</label>
                        <select name="doctor_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($doctors as $doc)
                                <option value="{{ $doc->doctor_id }}">
                                    {{ $doc->user?->name }} ({{ $doc->specialty?->specialty_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Cơ sở *</label>
                        <select name="clinic_id" required class="w-full rounded-lg border-gray-300 text-sm">
                            @foreach ($clinics as $cl)
                                <option value="{{ $cl->clinic_id }}">{{ $cl->clinic_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Ngày khám *</label>
                        <input type="date" name="work_date" required value="{{ now()->addDay()->toDateString() }}"
                            class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Giờ bắt đầu *</label>
                        <input type="time" name="start_time" required value="08:00"
                            class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Giờ kết thúc *</label>
                        <input type="time" name="end_time" required value="08:30"
                            class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-1">
                        <button type="submit"
                            class="w-full px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                            + Tạo
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bộ lọc danh sách ca làm việc -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <form method="GET" action="{{ route('admin.schedules.index') }}"
                    class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Ngày làm việc</label>
                        <input type="date" name="date" value="{{ $selectedDate }}"
                            class="w-full rounded-lg border-gray-300 text-sm">
                    </div>
                    <div class="md:col-span-5">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Bác sĩ</label>
                        <select name="doctor_id" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">-- Tất cả bác sĩ --</option>
                            @foreach ($doctors as $doc)
                                <option value="{{ $doc->doctor_id }}" @selected((int) $selectedDoctorId === (int) $doc->doctor_id)>
                                    {{ $doc->degree }} {{ $doc->user?->name }} ({{ $doc->specialty?->specialty_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Trạng thái ca</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="all" @selected($selectedStatus === 'all')>Tất cả</option>
                            <option value="available" @selected($selectedStatus === 'available')>Còn trống (Available)</option>
                            <option value="full" @selected($selectedStatus === 'full')>Đã kín (Full)</option>
                            <option value="cancelled" @selected($selectedStatus === 'cancelled')>Nghỉ đột xuất (Cancelled)</option>
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                            Lọc
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danh sách khung giờ & Nút Nghỉ đột xuất (LH16) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @if ($schedules->isEmpty())
                    <div class="p-10 text-center text-gray-500 text-sm">
                        Không có khung giờ khám nào khớp với bộ lọc hiện tại.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Ngày & Khung Giờ</th>
                                    <th class="px-4 py-3 text-left">Bác Sĩ & Chuyên Khoa</th>
                                    <th class="px-4 py-3 text-center">Lịch Đang Giữ Chỗ</th>
                                    <th class="px-4 py-3 text-center">Trạng Thái</th>
                                    <th class="px-4 py-3 text-right">Xử Lý Nghỉ Đột Xuất (LH16)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($schedules as $schedule)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-900">{{ $schedule->work_date?->format('d/m/Y') }}</div>
                                            <div class="font-mono text-xs text-indigo-700 font-bold">
                                                {{ substr((string) $schedule->start_time, 0, 5) }} – {{ substr((string) $schedule->end_time, 0, 5) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900">{{ $schedule->doctor?->user?->name }}</div>
                                            <div class="text-xs text-emerald-700">{{ $schedule->doctor?->specialty?->specialty_name }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono font-bold">
                                            {{ $schedule->appointments->count() }} / {{ $schedule->max_patients }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($schedule->is_day_off || $schedule->status === \App\Models\Schedule::STATUS_CANCELLED)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                                    Nghỉ đột xuất
                                                </span>
                                            @elseif ($schedule->status === \App\Models\Schedule::STATUS_FULL)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                    Đã kín chỗ
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                    Còn trống
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if (! $schedule->is_day_off && $schedule->status !== \App\Models\Schedule::STATUS_CANCELLED)
                                                <form method="POST"
                                                    action="{{ route('admin.schedules.day-off', $schedule) }}"
                                                    onsubmit="return confirm('Xác nhận đánh dấu ca này nghỉ đột xuất (LH16)? Hệ thống sẽ khóa nhận lịch mới và tự động gắn cờ cảnh báo cho các lịch Pending chuyển sang Lễ tân.');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                                        ⚠ Đánh dấu Nghỉ đột xuất
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Đã đóng ca</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
