<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-emerald-100 text-emerald-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
                Đăng Ký Đặt Lịch Khám Bệnh Trực Tuyến (Booking Flow)
            </h2>
            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-sm font-semibold rounded-full">
                Hồ sơ: Đã xác thực CCCD
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Thẻ xác thực bệnh nhân -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Bệnh nhân: {{ Auth::user()->name }}</h3>
                        <p class="text-sm text-gray-600 mt-1">
                            CCCD: <span class="font-mono font-bold text-gray-800">{{ Auth::user()->cccd }}</span> |
                            Điện thoại: <span class="font-mono text-gray-800">{{ Auth::user()->phone }}</span> |
                            Ngày sinh: <span class="text-gray-800">{{ Auth::user()->dob?->format('d/m/Y') }}</span>
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-50 text-green-700 rounded-lg border border-green-200 text-sm font-medium">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1,1 0 00-1.414-1.414L9 10.586 7.707 9.293a1,1 0 00-1.414 1.414l2 2a1,1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Đủ điều kiện đặt lịch theo SC03
                    </div>
                </div>
            </div>

            <!-- Khối thông tin tiếp theo sẽ triển khai ở Phase 3 -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 text-center py-12">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h4 class="text-xl font-bold text-gray-900">Không Gian Đặt Lịch Sẵn Sàng!</h4>
                <p class="text-gray-600 mt-2 max-w-lg mx-auto">
                    Hồ sơ của bạn đã vượt qua bước kiểm tra điều kiện CCCD (kịch bản LH17). 
                    Giao diện trích xuất triệu chứng bằng AI và chọn bác sĩ/khung giờ sẽ được hoàn thiện ở Phase 3.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
