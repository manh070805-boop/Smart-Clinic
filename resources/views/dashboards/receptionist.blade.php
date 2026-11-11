<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-amber-100 text-amber-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </span>
                Bàn Tiếp Đón Lễ Tân (Receptionist Desk)
            </h2>
            <span class="px-3 py-1 bg-amber-100 text-amber-800 text-sm font-semibold rounded-full">
                Vai trò: Lễ tân tiếp đón
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Thẻ chào mừng -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                <h3 class="text-xl font-bold text-gray-900">Nhân viên tiếp đón: {{ Auth::user()->name }}</h3>
                <p class="text-sm text-gray-600 mt-1">Quản lý danh sách lịch hẹn trong ngày, xác nhận có mặt tại quầy và xử lý các trường hợp quá hạn/vắng mặt.</p>
            </div>

            <!-- Nhiệm vụ Lễ tân theo SC04, SC07 -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center font-bold mb-3">1</div>
                    <h4 class="font-bold text-gray-900">Tiếp Nhận Bệnh Nhân (UC_R02)</h4>
                    <p class="text-sm text-gray-600 mt-2">Đối chiếu CCCD/Mã phiếu hẹn, chuyển trạng thái <span class="font-semibold text-blue-600">Pending</span> sang <span class="font-semibold text-indigo-600">Checked-in</span> và phát số thứ tự.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="w-10 h-10 bg-rose-100 text-rose-600 rounded-lg flex items-center justify-center font-bold mb-3">2</div>
                    <h4 class="font-bold text-gray-900">Xử Lý Vắng Mặt (UC_R03)</h4>
                    <p class="text-sm text-gray-600 mt-2">Kích hoạt quyền đánh dấu <span class="font-semibold text-rose-600">Cancelled (Vắng mặt)</span> khi bệnh nhân trễ quá 15 phút so với giờ bắt đầu khám theo quy định SC04.</p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center font-bold mb-3">3</div>
                    <h4 class="font-bold text-gray-900">Nghỉ Đột Xuất (UC_R04, LH16)</h4>
                    <p class="text-sm text-gray-600 mt-2">Theo dõi danh sách các lịch hẹn bị ảnh hưởng khi bác sĩ báo nghỉ đột xuất để hỗ trợ gọi điện liên hệ bệnh nhân.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
