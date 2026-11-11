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
            <!-- Thẻ chào mừng & Thông tin quyền -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Xin chào, {{ Auth::user()->name }} 👋</h3>
                        <p class="text-gray-600 mt-1">Chào mừng bạn đến với bảng điều khiển trung tâm Smart Clinic. Bạn
                            có toàn quyền quản trị danh mục, người dùng và lịch công tác.</p>
                    </div>
                </div>
            </div>

            <!-- Khối chức năng quản trị -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Quản lý Danh mục -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-lg text-gray-900">Quản lý Danh Mục Y Tế</h4>
                    <p class="text-sm text-gray-600 mt-2">Cấu hình chuyên khoa, phòng khám và tập danh mục triệu chứng
                        phục vụ trích xuất AI.</p>
                </div>

                <!-- Quản lý Bác sĩ & Lịch làm việc -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div
                        class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-lg text-gray-900">Lịch Trực & Nghỉ Đột Xuất</h4>
                    <p class="text-sm text-gray-600 mt-2">Xếp ca khám cho bác sĩ, công bố khung giờ và xử lý cắm cờ
                        ngoại lệ nghỉ đột xuất (LH16).</p>
                </div>

                <!-- Người dùng & Phân quyền -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                    </div>
                    <h4 class="font-bold text-lg text-gray-900">Tài Khoản & Báo Cáo</h4>
                    <p class="text-sm text-gray-600 mt-2">Quản lý phân quyền 4 vai trò người dùng, kiểm toán thay đổi hệ
                        thống và thống kê tổng hợp.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>