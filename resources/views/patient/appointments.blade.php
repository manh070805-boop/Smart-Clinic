<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-blue-100 text-blue-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </span>
                Lịch Sử & Quản Lý Lịch Hẹn Của Tôi
            </h2>
            <a href="{{ route('patient.booking') }}"
                class="inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                + Đặt Lịch Khám Mới
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        cancelModalOpen: false,
        cancelTarget: null,
        cancelReason: '',

        rescheduleModalOpen: false,
        rescheduleTarget: null,
        rescheduleSlots: [],
        loadingSlots: false,
        slotsError: '',
        selectedNewScheduleId: null,
        selectedRescheduleDate: '',
        selectedRescheduleDoctorId: null,
        fixedTimeSlots: [
            { start: '08:00', end: '08:30' },
            { start: '08:30', end: '09:00' },
            { start: '09:00', end: '09:30' },
            { start: '09:30', end: '10:00' },
            { start: '10:00', end: '10:30' },
            { start: '10:30', end: '11:00' },
            { start: '11:00', end: '11:30' },
            { start: '13:30', end: '14:00' },
            { start: '14:00', end: '14:30' },
            { start: '14:30', end: '15:00' },
            { start: '15:00', end: '15:30' },
            { start: '15:30', end: '16:00' },
            { start: '16:00', end: '16:30' },
            { start: '16:30', end: '17:00' },
        ],

        get availableRescheduleDates() {
            const seen = new Map();
            for (const s of this.rescheduleSlots) {
                if (!seen.has(s.work_date)) {
                    seen.set(s.work_date, { value: s.work_date, label: s.work_date_label });
                }
            }
            return Array.from(seen.values());
        },

        get doctorsForSelectedDate() {
            const seen = new Map();
            for (const s of this.rescheduleSlots) {
                if (s.work_date === this.selectedRescheduleDate && !seen.has(s.doctor_id)) {
                    seen.set(s.doctor_id, {
                        doctor_id: s.doctor_id,
                        name: (s.doctor_degree ? s.doctor_degree + ' ' : '') + s.doctor_name,
                        clinic_name: s.clinic_name,
                    });
                }
            }
            return Array.from(seen.values());
        },

        findSlotForTime(start, end) {
            return this.rescheduleSlots.find(s =>
                s.work_date === this.selectedRescheduleDate &&
                s.doctor_id === this.selectedRescheduleDoctorId &&
                s.start_time === start &&
                s.end_time === end
            ) || null;
        },

        selectRescheduleDate(dateValue) {
            this.selectedRescheduleDate = dateValue;
            this.selectedNewScheduleId = null;
            const docs = this.doctorsForSelectedDate;
            this.selectedRescheduleDoctorId = docs.length > 0 ? docs[0].doctor_id : null;
        },

        openCancelModal(appointment) {
            this.cancelTarget = appointment;
            this.cancelReason = '';
            this.cancelModalOpen = true;
        },

        async openRescheduleModal(appointment) {
            this.rescheduleTarget = appointment;
            this.rescheduleSlots = [];
            this.slotsError = '';
            this.selectedNewScheduleId = null;
            this.selectedRescheduleDate = '';
            this.selectedRescheduleDoctorId = null;
            this.rescheduleModalOpen = true;
            this.loadingSlots = true;

            try {
                const response = await fetch(appointment.reschedule_slots_url, {
                    headers: { 'Accept': 'application/json' }
                });
                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    this.slotsError = payload.message || 'Không thể tải danh sách khung giờ đổi lịch.';
                    return;
                }
                this.rescheduleSlots = payload.data || [];
                if (this.availableRescheduleDates.length > 0) {
                    this.selectRescheduleDate(this.availableRescheduleDates[0].value);
                }
            } catch (e) {
                this.slotsError = 'Lỗi kết nối khi tải danh sách khung giờ trống.';
            } finally {
                this.loadingSlots = false;
            }
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 shadow-sm text-sm font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 shadow-sm">
                    <div class="font-bold text-sm mb-1">Không thể thực hiện thao tác:</div>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Quy định hủy / đổi lịch -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-sm text-blue-900">
                <strong>Quy định Hủy / Đổi lịch (SC03):</strong>
                Bạn chỉ có thể hủy hoặc đổi sang khung giờ khác (cùng chuyên khoa) khi lịch hẹn đang ở trạng thái
                <strong>Chờ khám</strong> và thời điểm hiện tại cách giờ bắt đầu khám ít nhất <strong>2 giờ</strong>.
            </div>

            @if ($appointments->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center space-y-4">
                    <p class="text-gray-600">Bạn chưa có lịch hẹn khám bệnh nào.</p>
                    <a href="{{ route('patient.booking') }}"
                        class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition">
                        Đặt Lịch Khám Ngay
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($appointments as $appointment)
                        @php
                            $statusMeta = match ($appointment->status) {
                                \App\Models\Appointment::STATUS_PENDING => ['label' => 'Chờ khám', 'classes' => 'bg-amber-100 text-amber-800 border-amber-200'],
                                \App\Models\Appointment::STATUS_CHECKED_IN => ['label' => 'Đã tiếp nhận', 'classes' => 'bg-blue-100 text-blue-800 border-blue-200'],
                                \App\Models\Appointment::STATUS_IN_PROGRESS => ['label' => 'Đang khám', 'classes' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
                                \App\Models\Appointment::STATUS_COMPLETED => ['label' => 'Hoàn thành', 'classes' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                                \App\Models\Appointment::STATUS_CANCELLED => ['label' => 'Đã hủy', 'classes' => 'bg-red-100 text-red-800 border-red-200'],
                                default => ['label' => $appointment->status, 'classes' => 'bg-gray-100 text-gray-800 border-gray-200'],
                            };
                            $canModify = $appointment->canBeCancelledOrRescheduled();
                            $appointmentPayload = [
                                'appointment_id' => $appointment->appointment_id,
                                'appointment_code' => $appointment->appointment_code,
                                'specialty_name' => $appointment->doctor?->specialty?->specialty_name,
                                'doctor_name' => $appointment->doctor?->user?->name,
                                'date_time' => substr((string) $appointment->start_time, 0, 5) . ' - ' . $appointment->appointment_date?->format('d/m/Y'),
                                'cancel_url' => route('patient.appointments.cancel', $appointment),
                                'reschedule_url' => route('patient.appointments.reschedule', $appointment),
                                'reschedule_slots_url' => route('patient.appointments.reschedule-slots', $appointment),
                            ];
                        @endphp

                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <span class="font-mono font-bold text-base text-gray-900">
                                            {{ $appointment->appointment_code }}
                                        </span>
                                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full border {{ $statusMeta['classes'] }}">
                                            {{ $statusMeta['label'] }}
                                        </span>
                                        @if ($appointment->rescheduled_at)
                                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                                                Đã đổi lịch lúc {{ $appointment->rescheduled_at->format('H:i d/m/Y') }}
                                            </span>
                                        @endif
                                        @if ($appointment->is_affected_by_leave)
                                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 border border-orange-200">
                                                Bác sĩ nghỉ đột xuất — Cần hỗ trợ đổi lịch
                                            </span>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-1 text-sm text-gray-600">
                                        <div>
                                            Chuyên khoa: <strong class="text-gray-900">{{ $appointment->doctor?->specialty?->specialty_name }}</strong>
                                        </div>
                                        <div>
                                            Bác sĩ: <strong class="text-gray-900">{{ $appointment->doctor?->degree }} {{ $appointment->doctor?->user?->name }}</strong>
                                        </div>
                                        <div>
                                            Thời gian: <strong class="text-emerald-700">{{ substr((string) $appointment->start_time, 0, 5) }} | {{ $appointment->appointment_date?->format('d/m/Y') }}</strong>
                                        </div>
                                        <div>
                                            Cơ sở: <strong class="text-gray-900">{{ $appointment->doctor?->clinic?->clinic_name }}</strong>
                                        </div>
                                    </div>

                                    @if ($appointment->symptom_description)
                                        <p class="text-xs text-gray-500">
                                            Triệu chứng: {{ $appointment->symptom_description }}
                                        </p>
                                    @endif

                                    @if ($appointment->cancel_reason)
                                        <p class="text-xs text-red-600 font-medium">
                                            Lý do hủy: {{ $appointment->cancel_reason }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2 shrink-0">
                                    <a href="{{ route('patient.booking.receipt', $appointment) }}"
                                        class="px-3.5 py-2 rounded-lg border border-gray-200 hover:border-gray-300 text-gray-700 text-xs font-semibold transition">
                                        Xem Phiếu Hẹn
                                    </a>

                                    @if ($canModify)
                                        <button type="button"
                                            @click='openRescheduleModal(@json($appointmentPayload))'
                                            class="px-3.5 py-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-semibold transition">
                                            Đổi Lịch
                                        </button>
                                        <button type="button"
                                            @click='openCancelModal(@json($appointmentPayload))'
                                            class="px-3.5 py-2 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 text-xs font-semibold transition">
                                            Hủy Lịch
                                        </button>
                                    @elseif ($appointment->status === \App\Models\Appointment::STATUS_PENDING)
                                        <span class="px-3 py-2 rounded-lg bg-gray-100 text-gray-500 text-xs font-medium"
                                            title="Đã qua mốc 2 giờ trước giờ khám nên không thể hủy hoặc đổi trực tuyến">
                                            Khóa hủy/đổi (&lt; 2 giờ)
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Modal Hủy Lịch -->
        <div x-show="cancelModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="cancelModalOpen = false"
                class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-900">Xác Nhận Hủy Lịch Hẹn</h3>
                    <button type="button" @click="cancelModalOpen = false" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <template x-if="cancelTarget">
                    <form method="POST" :action="cancelTarget.cancel_url" class="space-y-4">
                        @csrf
                        <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700 space-y-1">
                            <div>Mã phiếu: <strong x-text="cancelTarget.appointment_code"></strong></div>
                            <div>Bác sĩ: <strong x-text="cancelTarget.doctor_name"></strong></div>
                            <div>Thời gian: <strong x-text="cancelTarget.date_time"></strong></div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Lý do hủy lịch <span class="text-red-500">*</span></label>
                            <textarea name="cancel_reason" x-model="cancelReason" rows="3" required minlength="3"
                                placeholder="Ví dụ: Bận việc đột xuất, muốn đặt lại ngày khác..."
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 text-sm"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="cancelModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-50">
                                Đóng
                            </button>
                            <button type="submit"
                                class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold">
                                Xác Nhận Hủy Lịch
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- Modal Đổi Lịch -->
        <div x-show="rescheduleModalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="rescheduleModalOpen = false"
                class="bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Đổi Sang Khung Giờ Mới</h3>
                        <p class="text-xs text-gray-500" x-show="rescheduleTarget">
                            Chỉ hiển thị khung giờ còn trống cùng chuyên khoa: <strong x-text="rescheduleTarget?.specialty_name"></strong>
                        </p>
                    </div>
                    <button type="button" @click="rescheduleModalOpen = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                </div>

                <template x-if="loadingSlots">
                    <div class="py-8 text-center text-sm text-gray-500">Đang tải danh sách khung giờ khả dụng...</div>
                </template>

                <template x-if="slotsError && !loadingSlots">
                    <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg" x-text="slotsError"></div>
                </template>

                <template x-if="!loadingSlots && !slotsError && rescheduleSlots.length === 0">
                    <div class="p-6 bg-gray-50 border border-gray-200 text-gray-600 text-sm rounded-lg text-center">
                        Hiện chưa có khung giờ trống khác cùng chuyên khoa trong 7 ngày tới.
                    </div>
                </template>

                <template x-if="!loadingSlots && rescheduleSlots.length > 0 && rescheduleTarget">
                    <form method="POST" :action="rescheduleTarget.reschedule_url" class="space-y-4">
                        @csrf
                        <input type="hidden" name="new_schedule_id" :value="selectedNewScheduleId">

                        <!-- Bước 1: Tự chọn ngày khám -->
                        <div class="space-y-2">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600">
                                    1. Chọn ngày khám
                                </label>
                                <input type="date"
                                    x-model="selectedRescheduleDate"
                                    @change="selectRescheduleDate($event.target.value)"
                                    :min="availableRescheduleDates[0]?.value"
                                    :max="availableRescheduleDates[availableRescheduleDates.length - 1]?.value"
                                    class="rounded-lg border-gray-300 text-xs font-medium shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="d in availableRescheduleDates" :key="d.value">
                                    <button type="button"
                                        @click="selectRescheduleDate(d.value)"
                                        :class="selectedRescheduleDate === d.value
                                            ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                            : 'bg-white text-gray-700 border-gray-200 hover:border-emerald-400'"
                                        class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition"
                                        x-text="d.label">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Bước 2: Chọn bác sĩ (nếu có) -->
                        <template x-if="doctorsForSelectedDate.length > 0">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600">
                                    2. Bác sĩ phụ trách
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="doc in doctorsForSelectedDate" :key="doc.doctor_id">
                                        <button type="button"
                                            @click="selectedRescheduleDoctorId = doc.doctor_id; selectedNewScheduleId = null"
                                            :class="selectedRescheduleDoctorId === doc.doctor_id
                                                ? 'bg-blue-50 text-blue-800 border-blue-500 ring-1 ring-blue-500'
                                                : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300'"
                                            class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition">
                                            <span x-text="doc.name"></span>
                                            <span class="text-gray-400 font-normal" x-text="'(' + doc.clinic_name + ')'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Không có lịch cho ngày đã chọn -->
                        <template x-if="doctorsForSelectedDate.length === 0">
                            <div class="p-4 bg-gray-50 border border-gray-200 text-gray-600 text-xs rounded-lg text-center">
                                Không có khung giờ trống trong ngày này. Vui lòng chọn ngày khác.
                            </div>
                        </template>

                        <!-- Bước 3: Lưới các khung giờ cố định -->
                        <template x-if="doctorsForSelectedDate.length > 0">
                            <div class="space-y-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600">
                                    3. Chọn khung giờ cố định
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                    <template x-for="ft in fixedTimeSlots" :key="ft.start">
                                        <button type="button"
                                            x-data="{ get slot() { return findSlotForTime(ft.start, ft.end); } }"
                                            :disabled="!slot"
                                            @click="slot && (selectedNewScheduleId = slot.schedule_id)"
                                            :class="{
                                                'border-emerald-600 bg-emerald-50 ring-2 ring-emerald-500 text-emerald-900': slot && selectedNewScheduleId === slot.schedule_id,
                                                'border-gray-200 bg-white hover:border-emerald-400 text-gray-800': slot && selectedNewScheduleId !== slot.schedule_id,
                                                'border-gray-100 bg-gray-100 text-gray-400 cursor-not-allowed opacity-70': !slot
                                            }"
                                            class="p-2.5 rounded-xl border text-center transition">
                                            <div class="font-bold text-xs" x-text="ft.start + ' - ' + ft.end"></div>
                                            <div class="text-[11px] mt-0.5"
                                                :class="slot ? 'text-emerald-700 font-medium' : 'text-gray-400'"
                                                x-text="slot ? 'Còn trống' : 'Không trống'">
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                            <button type="button" @click="rescheduleModalOpen = false"
                                class="px-4 py-2 rounded-lg border border-gray-200 text-gray-700 text-sm font-medium hover:bg-gray-50">
                                Đóng
                            </button>
                            <button type="submit" :disabled="!selectedNewScheduleId"
                                :class="selectedNewScheduleId ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
                                class="px-4 py-2 rounded-lg text-white text-sm font-semibold transition">
                                Xác Nhận Đổi Lịch
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
