<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-emerald-100 text-emerald-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </span>
                Cổng Dịch Vụ Bệnh Nhân (Patient Portal)
            </h2>
            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-sm font-semibold rounded-full">
                Vai trò: Bệnh nhân
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Thẻ chào mừng & Trạng thái hồ sơ CCCD -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Xin chào, {{ Auth::user()->name }} 👋</h3>
                        <p class="text-sm text-gray-600 mt-1">Chào mừng bạn đến với Cổng y tế thông minh Smart Clinic Vinh.</p>
                    </div>

                    @if(Auth::user()->hasCompleteBookingProfile())
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 text-emerald-700 rounded-lg border border-emerald-200 text-sm font-medium">
                            <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1,1 0 00-1.414-1.414L9 10.586 7.707 9.293a1,1 0 00-1.414 1.414l2 2a1,1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span>Hồ sơ đã đủ điều kiện đặt lịch (CCCD: {{ Auth::user()->cccd }})</span>
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-lg border border-amber-200 text-sm font-medium">
                            <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            <span>Hồ sơ chưa có CCCD — Cần cập nhật để đặt lịch (SC03)</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Khối chức năng chính dành cho Bệnh nhân -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Đặt lịch khám -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-emerald-300 transition">
                    <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    </div>
                    <h4 class="font-bold text-lg text-gray-900">Đặt Lịch Khám (UC_P04)</h4>
                    <p class="text-sm text-gray-600 mt-2">Khai báo triệu chứng để AI hỗ trợ gợi ý chuyên khoa hoặc tự chọn bác sĩ và khung giờ khám còn trống.</p>
                </div>

                <!-- Phiếu hẹn & Lịch sử -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-300 transition">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <h4 class="font-bold text-lg text-gray-900">Phiếu Hẹn Của Tôi (UC_P07)</h4>
                    <p class="text-sm text-gray-600 mt-2">Xem mã phiếu hẹn điện tử, trạng thái tiếp nhận và kết quả khám bệnh đã thực hiện.</p>
                </div>

                <!-- Hủy / Đổi lịch -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-amber-300 transition">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </div>
                    <h4 class="font-bold text-lg text-gray-900">Hủy & Đổi Lịch Khám (SC03)</h4>
                    <p class="text-sm text-gray-600 mt-2">Đổi sang khung giờ khác cùng chuyên khoa hoặc hủy lịch trực tuyến nếu còn trước giờ khám tối thiểu 2 giờ.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
