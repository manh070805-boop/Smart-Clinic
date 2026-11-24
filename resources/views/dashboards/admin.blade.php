<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-indigo-100 text-indigo-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                </span>
                Không Gian Quản Trị Hệ Thống (Admin Portal)
            </h2>
            <span class="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-semibold rounded-full">
                Vai trò: Quản trị viên
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Thẻ chào mừng & Nút điều hướng nhanh -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Xin chào, {{ Auth::user()->name }} 👋</h3>
                    <p class="text-sm text-gray-600 mt-1">Bảng điều khiển trung tâm Smart Clinic — quản lý danh mục y tế, công bố lịch khám, xử lý nghỉ đột xuất và báo cáo thống kê.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.catalog.index') }}"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                        Quản lý Danh mục
                    </a>
                    <a href="{{ route('admin.schedules.index') }}"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                        Lịch trực & Nghỉ đột xuất
                    </a>
                </div>
            </div>

            <!-- Khối chức năng quản trị -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="{{ route('admin.catalog.index', ['tab' => 'specialties']) }}"
                    class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition block group">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-lg text-gray-900 group-hover:text-blue-600 transition">Danh Mục Y Tế (UC_A02)</h4>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">Mở &rarr;</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        {{ $stats['specialties_count'] }} Chuyên khoa &bull; {{ $stats['doctors_count'] }} Bác sĩ &bull; {{ $stats['symptoms_count'] }} Triệu chứng AI.
                    </p>
                </a>

                <a href="{{ route('admin.schedules.index') }}"
                    class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-emerald-500 hover:shadow-md transition block group">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-lg text-gray-900 group-hover:text-emerald-600 transition">Lịch Trực & Nghỉ Đột Xuất</h4>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700">Quản lý &rarr;</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">
                        Công bố khung giờ khám (`UC_A03`) và đóng ca nghỉ đột xuất gắn cờ `LH16` ({{ $stats['day_off_schedules_count'] }} ca đã đóng).
                    </p>
                </a>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-lg text-gray-900">Tỷ Lệ Vận Hành (UC_A05)</h4>
                    <p class="text-sm text-gray-600 mt-2">
                        Tỷ lệ đến khám: <strong class="text-emerald-700">{{ $stats['attendance_rate'] }}%</strong>
                        &bull;
                        Tỷ lệ hủy/vắng: <strong class="text-rose-700">{{ $stats['cancellation_rate'] }}%</strong>
                    </p>
                </div>
            </div>

            <!-- Thống kê chi tiết lịch hẹn theo trạng thái (UC_A05) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h4 class="font-bold text-lg text-gray-900">Thống Kê Lịch Hẹn Toàn Hệ Thống (UC_A05)</h4>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="p-4 rounded-xl bg-gray-50 border border-gray-200">
                        <p class="text-xs font-bold uppercase text-gray-500">Tổng lịch hẹn</p>
                        <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['total_appointments'] }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-amber-50 border border-amber-200">
                        <p class="text-xs font-bold uppercase text-amber-700">Chờ khám (Pending)</p>
                        <p class="text-2xl font-extrabold text-amber-800 mt-1">{{ $stats['pending'] }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-indigo-50 border border-indigo-200">
                        <p class="text-xs font-bold uppercase text-indigo-700">Đã Check-in</p>
                        <p class="text-2xl font-extrabold text-indigo-800 mt-1">{{ $stats['checked_in'] }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-blue-50 border border-blue-200">
                        <p class="text-xs font-bold uppercase text-blue-700">Đang khám</p>
                        <p class="text-2xl font-extrabold text-blue-800 mt-1">{{ $stats['in_progress'] }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                        <p class="text-xs font-bold uppercase text-emerald-700">Hoàn thành</p>
                        <p class="text-2xl font-extrabold text-emerald-800 mt-1">{{ $stats['completed'] }}</p>
                    </div>
                    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200">
                        <p class="text-xs font-bold uppercase text-rose-700">Hủy / Vắng ({{ $stats['no_show'] }} No-show)</p>
                        <p class="text-2xl font-extrabold text-rose-800 mt-1">{{ $stats['cancelled'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Thống kê theo chuyên khoa -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="font-bold text-lg text-gray-900">Phân Bổ Theo Chuyên Khoa</h4>
                    @if ($stats['affected_by_leave'] > 0)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                            ⚠ {{ $stats['affected_by_leave'] }} lịch Pending bị ảnh hưởng nghỉ đột xuất (LH16)
                        </span>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                            <tr>
                                <th class="px-6 py-3 text-left">Chuyên khoa</th>
                                <th class="px-6 py-3 text-center">Số bác sĩ</th>
                                <th class="px-6 py-3 text-center">Số triệu chứng AI</th>
                                <th class="px-6 py-3 text-center">Tổng lượt đặt lịch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($specialtyStats as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-semibold text-gray-900">{{ $item['specialty_name'] }}</td>
                                    <td class="px-6 py-3 text-center text-gray-700">{{ $item['doctors_count'] }}</td>
                                    <td class="px-6 py-3 text-center text-gray-700">{{ $item['symptoms_count'] }}</td>
                                    <td class="px-6 py-3 text-center font-bold text-indigo-700">{{ $item['appointments_count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
