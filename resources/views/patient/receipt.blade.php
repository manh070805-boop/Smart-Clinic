<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-emerald-100 text-emerald-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </span>
                Phiếu Hẹn Khám Bệnh Điện Tử
            </h2>
            <a href="{{ route('patient.dashboard') }}"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                &larr; Về Trang Chủ
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <!-- Phiếu hẹn điện tử -->
            <div class="bg-white overflow-hidden shadow-md sm:rounded-2xl border border-emerald-100">
                <!-- Header phiếu -->
                <div
                    class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-emerald-100 font-semibold">Smart Clinic — Phiếu
                            Xác Nhận Đặt Lịch</p>
                        <h3 class="text-2xl font-extrabold font-mono tracking-wide mt-1">
                            {{ $appointment->appointment_code }}</h3>
                    </div>
                    <span
                        class="px-3.5 py-1.5 bg-white text-emerald-800 text-xs font-bold rounded-full uppercase tracking-wide self-start sm:self-center shadow-sm">
                        Trạng thái: Chờ tiếp nhận ({{ $appointment->status }})
                    </span>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Thông tin thời gian & bác sĩ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-gray-100">
                        <div class="space-y-2">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Thông Tin Lịch Khám</p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Cơ sở:</span>
                                <strong class="text-gray-900">{{ $appointment->doctor?->clinic?->clinic_name }}</strong>
                            </p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Chuyên khoa:</span>
                                <strong
                                    class="text-emerald-700">{{ $appointment->doctor?->specialty?->specialty_name }}</strong>
                            </p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Bác sĩ phụ trách:</span>
                                <strong class="text-gray-900">{{ $appointment->doctor?->user?->name }}</strong>
                                <span class="text-xs text-gray-500">({{ $appointment->doctor?->degree }})</span>
                            </p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Ngày & Giờ khám:</span>
                                <strong class="text-indigo-700 font-mono">
                                    {{ $appointment->appointment_date?->format('d/m/Y') }} —
                                    {{ substr($appointment->start_time, 0, 5) }}
                                </strong>
                            </p>
                        </div>

                        <div class="space-y-2">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Thông Tin Bệnh Nhân</p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Họ và tên:</span>
                                <strong class="text-gray-900">{{ $appointment->patient?->name }}</strong>
                            </p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Số CCCD:</span>
                                <strong class="font-mono text-gray-900">{{ $appointment->patient?->cccd }}</strong>
                            </p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Số điện thoại:</span>
                                <strong class="font-mono text-gray-900">{{ $appointment->patient?->phone }}</strong>
                            </p>
                            <p class="text-sm text-gray-700">
                                <span class="text-gray-500">Ngày sinh:</span>
                                <strong
                                    class="text-gray-900">{{ $appointment->patient?->dob?->format('d/m/Y') }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Triệu chứng đã khai báo -->
                    @if ($appointment->symptom_description)
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-2">
                            <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Triệu chứng đã khai báo</p>
                            <p class="text-sm text-gray-800">{{ $appointment->symptom_description }}</p>
                        </div>
                    @endif

                    <!-- Hướng dẫn khi đến khám -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800 space-y-1">
                        <p class="font-bold">Lưu ý quan trọng (Theo quy định SC03 / SC04):</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Vui lòng mang theo <strong>CCCD</strong> và đến quầy Lễ tân trước giờ hẹn 10–15 phút để
                                làm thủ tục tiếp nhận (Check-in).</li>
                            <li>Nếu đến muộn quá <strong>15 phút</strong> so với giờ hẹn, hệ thống có thể đánh dấu vắng
                                mặt (No-show).</li>
                            <li>Bạn có thể hủy hoặc đổi lịch trực tuyến miễn phí trước giờ khám tối thiểu <strong>2
                                    giờ</strong>.</li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <a href="{{ route('patient.booking') }}"
                            class="px-5 py-2.5 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-sm font-semibold rounded-xl transition">
                            + Đặt thêm lịch khám khác
                        </a>
                        <a href="{{ route('patient.dashboard') }}"
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                            Hoàn tất & Về Bảng Điều Khiển
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>