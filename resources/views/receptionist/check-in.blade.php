<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-amber-100 text-amber-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                        </path>
                    </svg>
                </span>
                Quầy Tiếp Nhận & Điểm Danh Bệnh Nhân
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('receptionist.dashboard') }}"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    &larr; Bàn Lễ Tân
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeTab: @js($activeTab) }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Thông báo thành công -->
            @if (session('success'))
                <div
                    class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center gap-3 shadow-sm">
                    <svg class="w-6 h-6 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Thông báo lỗi -->
            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl space-y-1 shadow-sm">
                    <div class="font-bold text-sm flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span>Không thể thực hiện thao tác:</span>
                    </div>
                    <ul class="list-disc list-inside text-sm pl-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Thống kê nhanh trong ngày -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Tổng lịch hẹn</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $summary['total'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-amber-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Chờ tiếp nhận</p>
                    <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $summary['pending'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-indigo-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Đã Check-in</p>
                    <p class="text-2xl font-extrabold text-indigo-700 mt-1">{{ $summary['checked_in'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-emerald-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Đang / Đã khám</p>
                    <p class="text-2xl font-extrabold text-emerald-700 mt-1">
                        {{ $summary['in_progress'] + $summary['completed'] }}
                    </p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-rose-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Đã hủy / Vắng</p>
                    <p class="text-2xl font-extrabold text-rose-700 mt-1">{{ $summary['cancelled'] }}</p>
                </div>
                <button type="button" @click="activeTab = 'affected'"
                    class="text-left bg-amber-50 hover:bg-amber-100 p-4 rounded-xl shadow-sm border border-amber-200 transition">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-800">Ca nghỉ đột xuất</p>
                    <p class="text-2xl font-extrabold text-amber-900 mt-1 flex items-center justify-between">
                        <span>{{ $affectedAppointments->count() }}</span>
                        <span class="text-xs font-semibold underline">Xem &rarr;</span>
                    </p>
                </button>
            </div>

            <!-- Thanh tìm kiếm & lọc (UC_R01) -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <form method="GET" action="{{ route('receptionist.checkin.index') }}"
                    class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <input type="hidden" name="tab" :value="activeTab">

                    <div class="md:col-span-4">
                        <label for="search" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">
                            Tìm nhanh (Mã phiếu hẹn / CCCD / SĐT / Họ tên)
                        </label>
                        <input type="text" id="search" name="search" value="{{ $search }}"
                            placeholder="VD: SC-2026..., 038204001234, 0912..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="date" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">
                            Ngày hẹn khám
                        </label>
                        <input type="date" id="date" name="date" value="{{ $selectedDate }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                    </div>

                    <div class="md:col-span-3">
                        <label for="doctor_id"
                            class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">
                            Bác sĩ phụ trách
                        </label>
                        <select id="doctor_id" name="doctor_id"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="">-- Tất cả bác sĩ --</option>
                            @foreach ($doctors as $doc)
                                <option value="{{ $doc->doctor_id }}" @selected((int) $selectedDoctorId === (int) $doc->doctor_id)>
                                    {{ $doc->degree }} {{ $doc->user?->name }} ({{ $doc->specialty?->specialty_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">
                            Trạng thái
                        </label>
                        <select id="status" name="status"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500">
                            <option value="all" @selected($selectedStatus === 'all')>Tất cả trạng thái</option>
                            <option value="pending" @selected($selectedStatus === 'pending')>Chờ khám (Pending)</option>
                            <option value="checked_in" @selected($selectedStatus === 'checked_in')>Đã tiếp nhận (Checked-in)</option>
                            <option value="in_progress" @selected($selectedStatus === 'in_progress')>Đang khám (In Progress)</option>
                            <option value="completed" @selected($selectedStatus === 'completed')>Đã hoàn thành (Completed)</option>
                            <option value="cancelled" @selected($selectedStatus === 'cancelled')>Đã hủy / Vắng mặt</option>
                        </select>
                    </div>

                    <div class="md:col-span-1 flex gap-2">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                            Lọc
                        </button>
                    </div>
                </form>

                <div class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                    <div class="flex items-center gap-2">
                        <span>Mốc thời gian máy chủ hiện tại:</span>
                        <strong class="font-mono text-gray-800">{{ now()->format('d/m/Y H:i') }}</strong>
                        <span>(Múi giờ: Asia/Ho_Chi_Minh)</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('receptionist.checkin.index', ['date' => now()->toDateString()]) }}"
                            class="text-amber-700 hover:underline font-semibold">Hôm nay</a>
                        <span>&bull;</span>
                        <a href="{{ route('receptionist.checkin.index', ['date' => '']) }}"
                            class="text-indigo-700 hover:underline font-semibold">Xem tất cả các ngày</a>
                    </div>
                </div>
            </div>

            <!-- Thanh chuyển Tab -->
            <div class="flex border-b border-gray-200 gap-2">
                <button type="button" @click="activeTab = 'daily'"
                    :class="activeTab === 'daily'
                        ? 'border-amber-600 text-amber-700 bg-white'
                        : 'border-transparent text-gray-500 hover:text-gray-700 bg-gray-50'"
                    class="px-5 py-3 font-bold text-sm border-b-2 rounded-t-xl transition flex items-center gap-2">
                    <span>Danh Sách Tiếp Nhận & Điểm Danh (UC_R01–UC_R03)</span>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800">
                        {{ $appointments->count() }}
                    </span>
                </button>

                <button type="button" @click="activeTab = 'affected'"
                    :class="activeTab === 'affected'
                        ? 'border-rose-600 text-rose-700 bg-white'
                        : 'border-transparent text-gray-500 hover:text-gray-700 bg-gray-50'"
                    class="px-5 py-3 font-bold text-sm border-b-2 rounded-t-xl transition flex items-center gap-2">
                    <span>Lịch Bị Ảnh Hưởng Do Bác Sĩ Nghỉ Đột Xuất (UC_R04, LH16)</span>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-rose-100 text-rose-800">
                        {{ $affectedAppointments->count() }}
                    </span>
                </button>
            </div>

            <!-- TAB 1: DANH SÁCH LỊCH HẸN TRONG NGÀY -->
            <div x-show="activeTab === 'daily'" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @if ($appointments->isEmpty())
                    <div class="p-12 text-center space-y-2">
                        <p class="text-gray-600 font-semibold">Không tìm thấy lịch hẹn nào khớp với bộ lọc hiện tại.</p>
                        <p class="text-xs text-gray-400">Bạn có thể đổi ngày hẹn hoặc bấm "Xem tất cả các ngày" để tra cứu rộng hơn.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Mã Phiếu / Giờ Khám</th>
                                    <th class="px-4 py-3 text-left">Bệnh Nhân (CCCD / SĐT)</th>
                                    <th class="px-4 py-3 text-left">Bác Sĩ & Chuyên Khoa</th>
                                    <th class="px-4 py-3 text-center">STT Hàng Đợi</th>
                                    <th class="px-4 py-3 text-center">Trạng Thái</th>
                                    <th class="px-4 py-3 text-right">Thao Tác Lễ Tân</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($appointments as $appointment)
                                    @php
                                        $isNoShowEligible = $appointment->isNoShowEligible();
                                        $noShowUnlockTime = $appointment->start_date_time->copy()->addMinutes(15)->format('H:i');
                                    @endphp
                                    <tr class="hover:bg-gray-50 transition">
                                        <!-- Mã phiếu & Giờ khám -->
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-mono font-bold text-indigo-700 text-sm">
                                                {{ $appointment->appointment_code }}
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1 font-medium">
                                                {{ $appointment->appointment_date?->format('d/m/Y') }}
                                                &bull;
                                                <span class="font-mono font-bold text-gray-900">
                                                    {{ substr((string) $appointment->start_time, 0, 5) }}
                                                </span>
                                            </div>
                                            @if ($appointment->is_affected_by_leave)
                                                <span
                                                    class="inline-block mt-1 px-2 py-0.5 bg-rose-100 text-rose-800 text-[11px] font-bold rounded">
                                                    ⚠ Bác sĩ nghỉ đột xuất (LH16)
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Thông tin Bệnh nhân -->
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-bold text-gray-900">{{ $appointment->patient?->name }}</div>
                                            <div class="text-xs text-gray-600 mt-0.5">
                                                CCCD: <span class="font-mono font-semibold text-gray-800">{{ $appointment->patient?->cccd ?? 'Chưa cập nhật' }}</span>
                                            </div>
                                            <div class="text-xs text-gray-600">
                                                SĐT: <span class="font-mono font-semibold text-gray-800">{{ $appointment->patient?->phone ?? 'Chưa cập nhật' }}</span>
                                            </div>
                                        </td>

                                        <!-- Bác sĩ & Chuyên khoa -->
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-semibold text-gray-900">
                                                {{ $appointment->doctor?->degree }} {{ $appointment->doctor?->user?->name }}
                                            </div>
                                            <div class="text-xs text-emerald-700 font-semibold mt-0.5">
                                                {{ $appointment->doctor?->specialty?->specialty_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $appointment->doctor?->clinic?->clinic_name }}
                                            </div>
                                        </td>

                                        <!-- Số thứ tự hàng đợi -->
                                        <td class="px-4 py-4 align-top text-center">
                                            @if ($appointment->queue_number)
                                                <span
                                                    class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 font-mono font-extrabold text-sm">
                                                    #{{ $appointment->queue_number }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Chưa cấp</span>
                                            @endif
                                        </td>

                                        <!-- Trạng thái -->
                                        <td class="px-4 py-4 align-top text-center">
                                            @if ($appointment->status === \App\Models\Appointment::STATUS_PENDING)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                    Chờ khám (Pending)
                                                </span>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_CHECKED_IN)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                                    Đã tiếp nhận (Checked-in)
                                                </span>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_IN_PROGRESS)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                                    Đang khám (In Progress)
                                                </span>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_COMPLETED)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                    Đã hoàn thành (Completed)
                                                </span>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_CANCELLED)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                                    Đã hủy (Cancelled)
                                                </span>
                                                @if ($appointment->cancel_reason)
                                                    <div class="text-[11px] text-rose-600 mt-1 max-w-[180px] mx-auto">
                                                        Lý do: {{ $appointment->cancel_reason }}
                                                    </div>
                                                @endif
                                            @endif
                                        </td>

                                        <!-- Thao tác Lễ tân -->
                                        <td class="px-4 py-4 align-top text-right">
                                            @if ($appointment->status === \App\Models\Appointment::STATUS_PENDING)
                                                <div class="flex flex-col sm:flex-row items-end sm:items-center justify-end gap-2">
                                                    @if (! $appointment->is_affected_by_leave && ! $appointment->schedule?->is_day_off)
                                                        <form method="POST"
                                                            action="{{ route('receptionist.appointments.check-in', $appointment) }}"
                                                            onsubmit="return confirm('Xác nhận tiếp nhận (Check-in) cho bệnh nhân {{ $appointment->patient?->name }} ({{ $appointment->appointment_code }})?');">
                                                            @csrf
                                                            <button type="submit"
                                                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                                                ✓ Tiếp nhận (Check-in)
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if ($isNoShowEligible)
                                                        <form method="POST"
                                                            action="{{ route('receptionist.appointments.no-show', $appointment) }}"
                                                            onsubmit="return confirm('Bệnh nhân đã trễ quá 15 phút. Xác nhận đánh dấu Vắng mặt (No-show) cho mã phiếu {{ $appointment->appointment_code }}?');">
                                                            @csrf
                                                            <button type="submit"
                                                                class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                                                ✕ Đánh dấu Vắng mặt
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span
                                                            title="Chỉ được đánh dấu vắng mặt khi quá giờ hẹn 15 phút (từ {{ $noShowUnlockTime }})"
                                                            class="px-2.5 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg border border-gray-200 cursor-not-allowed">
                                                            Vắng mặt (sau {{ $noShowUnlockTime }})
                                                        </span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">Đã xử lý</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- TAB 2: DANH SÁCH LỊCH HẸN BỊ ẢNH HƯỞNG DO BÁC SĨ NGHỈ ĐỘT XUẤT (UC_R04, LH16) -->
            <div x-show="activeTab === 'affected'" style="display: none;"
                class="bg-white rounded-xl shadow-sm border border-rose-200 overflow-hidden">
                <div class="p-4 bg-rose-50 border-b border-rose-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-rose-900">
                            Danh sách lịch hẹn bị ảnh hưởng do Bác sĩ nghỉ đột xuất (UC_R04, LH16)
                        </h3>
                        <p class="text-xs text-rose-700 mt-0.5">
                            Hệ thống giữ nguyên bản ghi lịch hẹn và gắn cờ cảnh báo để Lễ tân chủ động liên hệ bệnh nhân qua điện thoại hỗ trợ đổi/hủy lịch.
                        </p>
                    </div>
                </div>

                @if ($affectedAppointments->isEmpty())
                    <div class="p-12 text-center space-y-2">
                        <p class="text-gray-600 font-semibold">Hiện không có lịch hẹn nào bị ảnh hưởng bởi ca nghỉ đột xuất.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Mã Phiếu Hẹn</th>
                                    <th class="px-4 py-3 text-left">Bệnh Nhân Cần Liên Hệ</th>
                                    <th class="px-4 py-3 text-left">Số Điện Thoại & CCCD</th>
                                    <th class="px-4 py-3 text-left">Ca Khám Bị Ảnh Hưởng</th>
                                    <th class="px-4 py-3 text-center">Trạng Thái Hiện Tại</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($affectedAppointments as $affected)
                                    <tr class="hover:bg-rose-50/40 transition">
                                        <td class="px-4 py-4 font-mono font-bold text-rose-700">
                                            {{ $affected->appointment_code }}
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="font-bold text-gray-900">{{ $affected->patient?->name }}</div>
                                            <div class="text-xs text-gray-500">Email: {{ $affected->patient?->email }}</div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="font-mono font-bold text-indigo-700 text-sm">
                                                ☎ {{ $affected->patient?->phone ?? 'Chưa có SĐT' }}
                                            </div>
                                            <div class="text-xs text-gray-600 font-mono">
                                                CCCD: {{ $affected->patient?->cccd ?? 'Chưa có CCCD' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="font-semibold text-gray-900">
                                                {{ $affected->doctor?->degree }} {{ $affected->doctor?->user?->name }}
                                                ({{ $affected->doctor?->specialty?->specialty_name }})
                                            </div>
                                            <div class="text-xs text-rose-700 font-semibold mt-0.5">
                                                Ngày {{ $affected->appointment_date?->format('d/m/Y') }} — Giờ {{ substr((string) $affected->start_time, 0, 5) }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                                Cần liên hệ hỗ trợ ({{ $affected->status }})
                                            </span>
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
