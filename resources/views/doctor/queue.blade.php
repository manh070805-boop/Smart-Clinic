<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-blue-100 text-blue-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                        </path>
                    </svg>
                </span>
                Hàng Đợi Khám Bệnh — {{ $doctor->degree }} {{ $doctor->user?->name }}
            </h2>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-full">
                    {{ $doctor->specialty?->specialty_name }} &bull; {{ $doctor->clinic?->clinic_name }}
                </span>
                <a href="{{ route('doctor.dashboard') }}"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    &larr; Không Gian Bác Sĩ
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        symptomModalOpen: false,
        selectedAppointment: null,
        openSymptomModal(data) {
            this.selectedAppointment = data;
            this.symptomModalOpen = true;
        }
    }">
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

            <!-- Thống kê nhanh hàng đợi của Bác sĩ -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Tổng lịch phân công</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $summary['total'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-indigo-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Sẵn sàng khám (Check-in)</p>
                    <p class="text-2xl font-extrabold text-indigo-700 mt-1">{{ $summary['checked_in'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-blue-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Đang khám</p>
                    <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $summary['in_progress'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-emerald-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Đã hoàn thành</p>
                    <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $summary['completed'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-amber-100">
                    <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Chờ Lễ tân Check-in</p>
                    <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $summary['pending'] }}</p>
                </div>
            </div>

            <!-- Thanh bộ lọc -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <form method="GET" action="{{ route('doctor.queue.index') }}"
                    class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-5">
                        <label for="search" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">
                            Tìm nhanh (Mã phiếu / Họ tên / CCCD / SĐT)
                        </label>
                        <input type="text" id="search" name="search" value="{{ $search }}"
                            placeholder="VD: SC-2026..., Nguyễn Văn..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-3">
                        <label for="date" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">
                            Ngày khám
                        </label>
                        <input type="date" id="date" name="date" value="{{ $selectedDate }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-3">
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">
                            Trạng thái tiến trình
                        </label>
                        <select id="status" name="status"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" @selected($selectedStatus === 'all')>Tất cả trạng thái</option>
                            <option value="checked_in" @selected($selectedStatus === 'checked_in')>Đã tiếp nhận (Checked-in)</option>
                            <option value="in_progress" @selected($selectedStatus === 'in_progress')>Đang khám (In Progress)</option>
                            <option value="completed" @selected($selectedStatus === 'completed')>Đã hoàn thành (Completed)</option>
                            <option value="pending" @selected($selectedStatus === 'pending')>Chờ Check-in (Pending)</option>
                            <option value="cancelled" @selected($selectedStatus === 'cancelled')>Đã hủy (Cancelled)</option>
                        </select>
                    </div>

                    <div class="md:col-span-1">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                            Lọc
                        </button>
                    </div>
                </form>

                <div class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                    <span>Quy tắc SC04: Chỉ gọi khám sau khi Lễ tân tiếp nhận (<strong>Checked-in &rarr; In Progress &rarr; Completed</strong>).</span>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('doctor.queue.index', ['date' => now()->toDateString()]) }}"
                            class="text-blue-700 hover:underline font-semibold">Hôm nay</a>
                        <span>&bull;</span>
                        <a href="{{ route('doctor.queue.index', ['date' => '']) }}"
                            class="text-indigo-700 hover:underline font-semibold">Xem tất cả các ngày</a>
                    </div>
                </div>
            </div>

            <!-- Danh sách hàng đợi khám của Bác sĩ -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                @if ($appointments->isEmpty())
                    <div class="p-12 text-center space-y-2">
                        <p class="text-gray-600 font-semibold">Không có bệnh nhân nào trong hàng đợi khám khớp với bộ lọc hiện tại.</p>
                        <p class="text-xs text-gray-400">Hãy chọn ngày khác hoặc bấm "Xem tất cả các ngày" để xem toàn bộ lịch được phân công cho bạn.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-bold uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-center">STT</th>
                                    <th class="px-4 py-3 text-left">Mã Phiếu / Giờ Hẹn</th>
                                    <th class="px-4 py-3 text-left">Thông Tin Bệnh Nhân</th>
                                    <th class="px-4 py-3 text-left">Triệu Chứng & Gợi Ý AI (UC_D03)</th>
                                    <th class="px-4 py-3 text-center">Trạng Thái</th>
                                    <th class="px-4 py-3 text-right">Cập Nhật Tiến Trình (UC_D02)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($appointments as $appointment)
                                    @php
                                        $aiData = $appointment->ai_extracted_data
                                            ? json_decode($appointment->ai_extracted_data, true)
                                            : null;
                                        $aiSymptoms = is_array($aiData['matched_symptoms'] ?? null)
                                            ? $aiData['matched_symptoms']
                                            : [];
                                        $aiSpecialty = $aiData['recommended_specialty']['specialty_name'] ?? ($aiData['recommended_specialty'] ?? null);
                                        $modalPayload = [
                                            'appointment_code' => $appointment->appointment_code,
                                            'patient_name' => $appointment->patient?->name,
                                            'patient_dob' => $appointment->patient?->dob?->format('d/m/Y') ?? 'Chưa cập nhật',
                                            'patient_phone' => $appointment->patient?->phone ?? 'Chưa cập nhật',
                                            'patient_cccd' => $appointment->patient?->cccd ?? 'Chưa cập nhật',
                                            'appointment_time' => ($appointment->appointment_date?->format('d/m/Y') ?? '') . ' — ' . substr((string) $appointment->start_time, 0, 5),
                                            'symptom_description' => $appointment->symptom_description ?: 'Bệnh nhân không khai báo mô tả triệu chứng trước khi đặt lịch.',
                                            'ai_symptoms' => $aiSymptoms,
                                            'ai_specialty' => is_string($aiSpecialty) ? $aiSpecialty : null,
                                            'ai_source' => $aiData['source'] ?? null,
                                        ];
                                    @endphp
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <!-- STT -->
                                        <td class="px-4 py-4 align-top text-center">
                                            @if ($appointment->queue_number)
                                                <span
                                                    class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-indigo-100 text-indigo-800 font-mono font-extrabold text-sm">
                                                    #{{ $appointment->queue_number }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 italic">—</span>
                                            @endif
                                        </td>

                                        <!-- Mã phiếu & giờ -->
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-mono font-bold text-blue-700">
                                                {{ $appointment->appointment_code }}
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1">
                                                {{ $appointment->appointment_date?->format('d/m/Y') }}
                                                &bull;
                                                <span class="font-mono font-bold text-gray-900">
                                                    {{ substr((string) $appointment->start_time, 0, 5) }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Bệnh nhân -->
                                        <td class="px-4 py-4 align-top">
                                            <div class="font-bold text-gray-900">{{ $appointment->patient?->name }}</div>
                                            <div class="text-xs text-gray-600 mt-0.5">
                                                Ngày sinh: {{ $appointment->patient?->dob?->format('d/m/Y') ?? '—' }}
                                            </div>
                                            <div class="text-xs text-gray-500 font-mono">
                                                SĐT: {{ $appointment->patient?->phone ?? '—' }}
                                            </div>
                                        </td>

                                        <!-- Triệu chứng & AI -->
                                        <td class="px-4 py-4 align-top max-w-xs">
                                            @if ($appointment->symptom_description)
                                                <p class="text-xs text-gray-800 line-clamp-2">
                                                    {{ $appointment->symptom_description }}
                                                </p>
                                            @else
                                                <p class="text-xs text-gray-400 italic">Chưa có mô tả triệu chứng</p>
                                            @endif

                                            @if (! empty($aiSymptoms))
                                                <div class="flex flex-wrap gap-1 mt-1.5">
                                                    @foreach (array_slice($aiSymptoms, 0, 3) as $sym)
                                                        <span class="px-2 py-0.5 text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">
                                                            {{ is_array($sym) ? ($sym['symptom_name'] ?? $sym['name'] ?? '') : $sym }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <button type="button"
                                                @click="openSymptomModal(@js($modalPayload))"
                                                class="mt-2 inline-flex items-center text-xs font-semibold text-blue-700 hover:underline">
                                                Xem chi tiết hồ sơ triệu chứng &rarr;
                                            </button>
                                        </td>

                                        <!-- Trạng thái -->
                                        <td class="px-4 py-4 align-top text-center">
                                            @if ($appointment->status === \App\Models\Appointment::STATUS_CHECKED_IN)
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
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_PENDING)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                                    Chờ Check-in (Pending)
                                                </span>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_CANCELLED)
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                                    Đã hủy (Cancelled)
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Thao tác tiến trình khám -->
                                        <td class="px-4 py-4 align-top text-right">
                                            @if ($appointment->status === \App\Models\Appointment::STATUS_CHECKED_IN)
                                                <form method="POST"
                                                    action="{{ route('doctor.appointments.start', $appointment) }}"
                                                    onsubmit="return confirm('Bắt đầu khám cho bệnh nhân {{ $appointment->patient?->name }} ({{ $appointment->appointment_code }})?');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                                        ▶ Bắt đầu khám
                                                    </button>
                                                </form>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_IN_PROGRESS)
                                                <form method="POST"
                                                    action="{{ route('doctor.appointments.complete', $appointment) }}"
                                                    onsubmit="return confirm('Xác nhận hoàn tất lượt khám cho bệnh nhân {{ $appointment->patient?->name }} ({{ $appointment->appointment_code }})?');">
                                                    @csrf
                                                    <button type="submit"
                                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                                        ✓ Hoàn tất khám
                                                    </button>
                                                </form>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_PENDING)
                                                <span
                                                    title="Theo quy định SC04, bệnh nhân cần được Lễ tân tiếp nhận (Check-in) trước khi Bác sĩ gọi khám."
                                                    class="inline-block px-2.5 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg border border-gray-200 cursor-not-allowed">
                                                    Chờ Lễ tân Check-in
                                                </span>
                                            @elseif ($appointment->status === \App\Models\Appointment::STATUS_COMPLETED)
                                                <span class="text-xs font-semibold text-emerald-700">✓ Đã hoàn tất</span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
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

        <!-- Modal Chi tiết Triệu chứng & Kết quả AI (UC_D03) -->
        <div x-show="symptomModalOpen" style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="symptomModalOpen = false">
            <div class="bg-white rounded-2xl shadow-xl max-w-xl w-full overflow-hidden"
                @click.away="symptomModalOpen = false">
                <div class="bg-blue-600 px-6 py-4 text-white flex items-center justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-blue-100 font-semibold">Hồ Sơ Triệu Chứng Bệnh Nhân (UC_D03)</p>
                        <h3 class="text-lg font-bold font-mono" x-text="selectedAppointment?.appointment_code"></h3>
                    </div>
                    <button type="button" @click="symptomModalOpen = false"
                        class="text-blue-100 hover:text-white text-xl font-bold">&times;</button>
                </div>

                <div class="p-6 space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div>
                            <p class="text-xs text-gray-500">Bệnh nhân</p>
                            <p class="font-bold text-gray-900" x-text="selectedAppointment?.patient_name"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Ngày / Giờ hẹn</p>
                            <p class="font-mono font-bold text-blue-700" x-text="selectedAppointment?.appointment_time"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Ngày sinh / SĐT</p>
                            <p class="text-gray-800" x-text="(selectedAppointment?.patient_dob || '') + ' • ' + (selectedAppointment?.patient_phone || '')"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Số CCCD</p>
                            <p class="font-mono text-gray-800" x-text="selectedAppointment?.patient_cccd"></p>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-1">Mô tả triệu chứng của bệnh nhân</p>
                        <div class="p-3.5 bg-amber-50/70 border border-amber-200 rounded-xl text-gray-800"
                            x-text="selectedAppointment?.symptom_description"></div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Kết quả trích xuất AI</p>
                        <template x-if="selectedAppointment?.ai_symptoms && selectedAppointment.ai_symptoms.length > 0">
                            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="(sym, idx) in selectedAppointment.ai_symptoms" :key="idx">
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-white text-emerald-800 border border-emerald-200 rounded-lg"
                                            x-text="typeof sym === 'object' ? (sym.symptom_name || sym.name || '') : sym"></span>
                                    </template>
                                </div>
                                <template x-if="selectedAppointment?.ai_specialty">
                                    <p class="text-xs text-emerald-900">
                                        Chuyên khoa gợi ý: <strong x-text="selectedAppointment.ai_specialty"></strong>
                                    </p>
                                </template>
                            </div>
                        </template>
                        <template x-if="!selectedAppointment?.ai_symptoms || selectedAppointment.ai_symptoms.length === 0">
                            <p class="text-xs text-gray-400 italic">Không có dữ liệu trích xuất AI cho phiếu hẹn này.</p>
                        </template>
                    </div>
                </div>

                <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" @click="symptomModalOpen = false"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-semibold rounded-lg transition">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
