<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-indigo-100 text-indigo-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                        </path>
                    </svg>
                </span>
                Quản Lý Danh Mục Y Tế (UC_A02)
            </h2>
            <a href="{{ route('admin.dashboard') }}"
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                &larr; Trang Quản Trị
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeTab: @js($activeTab) }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-semibold text-sm shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-sm shadow-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Tabs -->
            <div class="flex border-b border-gray-200 gap-2">
                <button type="button" @click="activeTab = 'specialties'"
                    :class="activeTab === 'specialties' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 bg-gray-50'"
                    class="px-5 py-3 font-bold text-sm border-b-2 rounded-t-xl transition">
                    Chuyên khoa ({{ $specialties->count() }})
                </button>
                <button type="button" @click="activeTab = 'doctors'"
                    :class="activeTab === 'doctors' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 bg-gray-50'"
                    class="px-5 py-3 font-bold text-sm border-b-2 rounded-t-xl transition">
                    Bác sĩ ({{ $doctors->count() }})
                </button>
                <button type="button" @click="activeTab = 'symptoms'"
                    :class="activeTab === 'symptoms' ? 'border-indigo-600 text-indigo-700 bg-white' : 'border-transparent text-gray-500 bg-gray-50'"
                    class="px-5 py-3 font-bold text-sm border-b-2 rounded-t-xl transition">
                    Triệu chứng AI ({{ $symptoms->count() }})
                </button>
            </div>

            <!-- TAB 1: CHUYÊN KHOA -->
            <div x-show="activeTab === 'specialties'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">Thêm Chuyên Khoa Mới</h3>
                    <form method="POST" action="{{ route('admin.catalog.specialties.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Tên chuyên khoa *</label>
                            <input type="text" name="specialty_name" required
                                placeholder="VD: Tim mạch"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Mô tả</label>
                            <textarea name="description" rows="3"
                                placeholder="Mô tả phạm vi khám chữa bệnh..."
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                        <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                            + Thêm Chuyên Khoa
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Chuyên khoa</th>
                                <th class="px-4 py-3 text-center">Bác sĩ</th>
                                <th class="px-4 py-3 text-center">Triệu chứng</th>
                                <th class="px-4 py-3 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($specialties as $specialty)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900">{{ $specialty->specialty_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $specialty->description }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $specialty->doctors_count }}</td>
                                    <td class="px-4 py-3 text-center">{{ $specialty->symptoms_count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.catalog.specialties.destroy', $specialty) }}"
                                            onsubmit="return confirm('Xóa chuyên khoa {{ $specialty->specialty_name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: BÁC SĨ -->
            <div x-show="activeTab === 'doctors'" style="display: none;" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">Thêm Tài Khoản & Hồ Sơ Bác Sĩ</h3>
                    <form method="POST" action="{{ route('admin.catalog.doctors.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Họ tên Bác sĩ *</label>
                            <input type="text" name="name" required placeholder="BS. Nguyễn Văn F"
                                class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Email đăng nhập *</label>
                            <input type="email" name="email" required placeholder="bs.nguyenvanf@smartclinic.vn"
                                class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Mật khẩu khởi tạo *</label>
                            <input type="password" name="password" required value="password"
                                class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Chuyên khoa *</label>
                                <select name="specialty_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                    @foreach ($specialties as $sp)
                                        <option value="{{ $sp->specialty_id }}">{{ $sp->specialty_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Cơ sở *</label>
                                <select name="clinic_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                    @foreach ($clinics as $cl)
                                        <option value="{{ $cl->clinic_id }}">{{ $cl->clinic_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Học vị</label>
                                <input type="text" name="degree" placeholder="Thạc sĩ Bác sĩ"
                                    class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Kinh nghiệm (năm)</label>
                                <input type="number" name="experience_years" min="0" value="5"
                                    class="w-full rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                            + Tạo Hồ Sơ Bác Sĩ
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Bác sĩ</th>
                                <th class="px-4 py-3 text-left">Chuyên khoa</th>
                                <th class="px-4 py-3 text-left">Cơ sở</th>
                                <th class="px-4 py-3 text-center">Kinh nghiệm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($doctors as $doc)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900">{{ $doc->degree }} {{ $doc->user?->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $doc->user?->email }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-emerald-700">{{ $doc->specialty?->specialty_name }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $doc->clinic?->clinic_name }}</td>
                                    <td class="px-4 py-3 text-center">{{ $doc->experience_years }} năm</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: TRIỆU CHỨNG -->
            <div x-show="activeTab === 'symptoms'" style="display: none;" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-4">Thêm Triệu Chứng Chuẩn</h3>
                    <form method="POST" action="{{ route('admin.catalog.symptoms.store') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Mã triệu chứng *</label>
                            <input type="text" name="symptom_code" required placeholder="VD: SYM_CHEST_PAIN"
                                class="w-full rounded-lg border-gray-300 text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Tên triệu chứng *</label>
                            <input type="text" name="symptom_name" required placeholder="VD: Đau tức ngực"
                                class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Chuyên khoa liên kết *</label>
                            <select name="specialty_id" required class="w-full rounded-lg border-gray-300 text-sm">
                                @foreach ($specialties as $sp)
                                    <option value="{{ $sp->specialty_id }}">{{ $sp->specialty_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition">
                            + Thêm Triệu Chứng
                        </button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-xs font-bold uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Mã</th>
                                <th class="px-4 py-3 text-left">Tên triệu chứng</th>
                                <th class="px-4 py-3 text-left">Chuyên khoa</th>
                                <th class="px-4 py-3 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($symptoms as $sym)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-gray-600">{{ $sym->symptom_code }}</td>
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $sym->symptom_name }}</td>
                                    <td class="px-4 py-3 text-emerald-700 font-semibold">{{ $sym->specialty?->specialty_name }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <form method="POST" action="{{ route('admin.catalog.symptoms.destroy', $sym) }}"
                                            onsubmit="return confirm('Xóa triệu chứng {{ $sym->symptom_name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
