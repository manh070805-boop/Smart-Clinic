<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-blue-100 text-blue-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </span>
                Không Gian Bác Sĩ (Doctor Portal)
            </h2>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-semibold rounded-full">
                Vai trò: Bác sĩ điều trị
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Thông tin Bác sĩ -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Bác sĩ: {{ Auth::user()->name }}</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            Chuyên khoa: <span class="font-semibold text-blue-600">{{ Auth::user()->doctor?->specialty?->specialty_name ?? 'Chưa phân khoa' }}</span> |
                            Cơ sở: <span class="font-semibold text-gray-800">{{ Auth::user()->doctor?->clinic?->clinic_name ?? 'Smart Clinic Vinh' }}</span>
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-50 text-green-700 rounded-lg border border-green-200 text-sm font-medium">
                        <span class="w-2.5 h-2.5 bg-green-500 rounded-full animate-pulse"></span>
                        Ca trực hôm nay sẵn sàng
                    </div>
                </div>
            </div>

            <!-- Các tác vụ chính của Bác sĩ theo SC04 & SC07 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold">1</span>
                        Hàng Đợi Khám Bệnh Trong Ca (UC_D01)
                    </h4>
                    <p class="text-sm text-gray-600 mt-2">Xem danh sách bệnh nhân đã được Lễ tân tiếp nhận (<span class="font-semibold text-indigo-600">Checked-in</span>) theo số thứ tự để gọi vào phòng khám.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="font-bold text-lg text-gray-900 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm font-bold">2</span>
                        Tiến Trình & Hồ Sơ Triệu Chứng (UC_D02, UC_D03)
                    </h4>
                    <p class="text-sm text-gray-600 mt-2">Xem chi tiết triệu chứng bệnh nhân khai báo qua AI, cập nhật trạng thái <span class="font-semibold text-amber-600">In Progress</span> và hoàn thành lượt khám (<span class="font-semibold text-green-600">Completed</span>).</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
