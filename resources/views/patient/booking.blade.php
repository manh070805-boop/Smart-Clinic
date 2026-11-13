<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-2">
                <span class="inline-block p-2 bg-emerald-100 text-emerald-700 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                </span>
                Đăng Ký Đặt Lịch Khám Bệnh Trực Tuyến (Booking Flow)
            </h2>
            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-sm font-semibold rounded-full">
                Hồ sơ: Đã xác thực CCCD
            </span>
        </div>
    </x-slot>

    @php
        $specialtiesJson = $specialties->map(fn ($sp) => [
            'specialty_id' => $sp->specialty_id,
            'specialty_name' => $sp->specialty_name,
            'description' => $sp->description,
            'symptoms' => $sp->symptoms->map(fn ($s) => $s->symptom_name)->values(),
            'doctors' => $sp->doctors->map(fn ($d) => [
                'doctor_id' => $d->doctor_id,
                'name' => $d->user?->name,
                'degree' => $d->degree,
                'experience_years' => $d->experience_years,
                'bio' => $d->bio,
                'clinic_name' => $d->clinic?->clinic_name,
            ])->values(),
        ])->values();
    @endphp

    <div class="py-8" x-data="{
        symptomsText: '',
        loading: false,
        errorMessage: '',
        analysisResult: null,
        selectedSpecialtyId: null,
        specialties: {{ Js::from($specialtiesJson) }},

        get selectedSpecialty() {
            return this.specialties.find(s => s.specialty_id === this.selectedSpecialtyId) || null;
        },

        fillSample(text) {
            this.symptomsText = text;
            this.errorMessage = '';
            this.analyzeSymptoms();
        },

        async analyzeSymptoms() {
            this.errorMessage = '';
            if (!this.symptomsText || this.symptomsText.trim().length < 3) {
                this.errorMessage = 'Vui lòng nhập mô tả triệu chứng của bạn (ít nhất 3 ký tự).';
                return;
            }

            this.loading = true;
            try {
                const response = await fetch('{{ route('patient.symptoms.analyze') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ symptoms_text: this.symptomsText })
                });

                const payload = await response.json();

                if (!response.ok) {
                    this.errorMessage = payload.message || 'Không thể phân tích triệu chứng lúc này.';
                    return;
                }

                this.analysisResult = payload.data;
                if (this.analysisResult?.recommended_specialty?.specialty_id) {
                    this.selectedSpecialtyId = this.analysisResult.recommended_specialty.specialty_id;
                }
            } catch (e) {
                this.errorMessage = 'Đã xảy ra lỗi kết nối khi phân tích triệu chứng. Bạn có thể tự chọn chuyên khoa bên dưới.';
            } finally {
                this.loading = false;
            }
        },

        chooseSpecialty(id) {
            this.selectedSpecialtyId = id;
            const el = document.getElementById('step-select-specialty');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }">
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
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-50 text-green-700 rounded-lg border border-green-200 text-sm font-medium">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1,1 0 00-1.414-1.414L9 10.586 7.707 9.293a1,1 0 00-1.414 1.414l2 2a1,1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        Đủ điều kiện đặt lịch theo SC03
                    </div>
                </div>
            </div>

            <!-- BƯỚC 1: TRỢ LÝ AI PHÂN TÍCH TRIỆU CHỨNG & GỢI Ý CHUYÊN KHOA (T3.1) -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-indigo-100">
                <div class="bg-gradient-to-r from-indigo-600 to-emerald-600 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Bước 1 (Tùy chọn): Trợ lý AI Phân tích Triệu chứng & Gợi ý Chuyên khoa</h3>
                            <p class="text-xs text-indigo-100">Mô tả cảm giác khó chịu của bạn bằng ngôn ngữ tự nhiên để hệ thống định hướng chuyên khoa phù hợp nhất</p>
                        </div>
                    </div>
                    <span class="hidden sm:inline-block px-2.5 py-1 bg-white/20 text-white text-xs font-semibold rounded-full">
                        AI & Smart Matcher
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label for="symptoms_text" class="block text-sm font-semibold text-gray-700 mb-1">
                            Mô tả triệu chứng hoặc tình trạng sức khỏe của bạn:
                        </label>
                        <textarea id="symptoms_text" x-model="symptomsText" rows="3"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            placeholder="Ví dụ: Tôi bị đau họng, ho có đờm và nghẹt mũi từ hôm qua..."></textarea>
                    </div>

                    <!-- Các mẫu câu triệu chứng thử nhanh -->
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="text-gray-500 font-medium">Mẫu thử nhanh:</span>
                        <button type="button"
                            @click="fillSample('Tôi bị đau họng, ho có đờm và nghẹt mũi 2 ngày nay')"
                            class="px-2.5 py-1 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 rounded-full border border-gray-200 transition">
                            Đau họng, ho có đờm, nghẹt mũi
                        </button>
                        <button type="button"
                            @click="fillSample('Tôi bị đau bụng âm ỉ, buồn nôn và sốt cao từ hôm qua')"
                            class="px-2.5 py-1 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 rounded-full border border-gray-200 transition">
                            Đau bụng, buồn nôn, sốt cao
                        </button>
                        <button type="button"
                            @click="fillSample('Da nổi mẩn đỏ, ngứa ngáy khắp người và có mụn nước')"
                            class="px-2.5 py-1 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 rounded-full border border-gray-200 transition">
                            Nổi mẩn đỏ, ngứa ngáy, mụn nước
                        </button>
                        <button type="button"
                            @click="fillSample('Tôi bị đau lưng, nhức mỏi khớp gối và tê bì chân tay')"
                            class="px-2.5 py-1 bg-gray-100 hover:bg-indigo-50 hover:text-indigo-700 text-gray-700 rounded-full border border-gray-200 transition">
                            Đau lưng, mỏi khớp gối, tê bì
                        </button>
                    </div>

                    <!-- Thông báo lỗi nếu có -->
                    <template x-if="errorMessage">
                        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-lg" x-text="errorMessage"></div>
                    </template>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-gray-100">
                        <p class="text-xs text-gray-500 italic">
                            * Bạn có thể bấm nút phân tích AI hoặc bỏ qua bước này để tự chọn chuyên khoa bên dưới.
                        </p>
                        <button type="button" @click="analyzeSymptoms()" :disabled="loading"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition cursor-pointer">
                            <svg x-show="!loading" class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <svg x-show="loading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="loading ? 'AI đang phân tích triệu chứng...' : '⚡ Bấm để AI Phân Tích & Gợi Ý Chuyên Khoa'"></span>
                        </button>
                    </div>

                    <!-- KẾT QUẢ PHÂN TÍCH TRIỆU CHỨNG -->
                    <template x-if="analysisResult">
                        <div class="mt-4 p-5 bg-indigo-50/70 rounded-xl border border-indigo-200 space-y-4">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-indigo-100 pb-3">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Kết quả định hướng chuyên khoa</span>
                                    <h4 class="text-lg font-bold text-gray-900 mt-0.5">
                                        Chuyên khoa đề xuất:
                                        <span class="text-emerald-700 underline decoration-emerald-400" x-text="analysisResult.recommended_specialty.specialty_name"></span>
                                    </h4>
                                </div>
                                <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-semibold rounded-full self-start md:self-center"
                                    x-text="analysisResult.analysis_mode === 'ai_llm' ? 'Phân tích bởi AI LLM' : 'Phân tích bởi Smart Matcher'">
                                </span>
                            </div>

                            <!-- Triệu chứng lâm sàng bóc tách được -->
                            <div>
                                <p class="text-xs font-semibold text-gray-700 mb-2">Triệu chứng lâm sàng nhận diện được:</p>
                                <template x-if="analysisResult.extracted_symptoms.length > 0">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="sym in analysisResult.extracted_symptoms" :key="sym.symptom_id">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-white text-indigo-800 border border-indigo-200 rounded-full text-xs font-semibold shadow-sm">
                                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                <span x-text="sym.symptom_name"></span>
                                                <span class="text-gray-400 font-normal" x-text="'(' + sym.specialty_name + ')'"></span>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="analysisResult.extracted_symptoms.length === 0">
                                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2 rounded-lg">
                                        Chưa nhận diện được triệu chứng đặc thù trong danh mục 19 triệu chứng chuẩn. Hệ thống gợi ý khám sàng lọc tại <strong>Nội tổng quát</strong>.
                                    </p>
                                </template>
                            </div>

                            <!-- Lý do gợi ý -->
                            <p class="text-sm text-gray-700 bg-white p-3 rounded-lg border border-gray-200"
                                x-text="analysisResult.recommended_specialty.confidence_reason"></p>

                            <!-- Cảnh báo y khoa -->
                            <p class="text-xs text-gray-500 italic" x-text="analysisResult.disclaimer"></p>

                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                <button type="button"
                                    @click="chooseSpecialty(analysisResult.recommended_specialty.specialty_id)"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                    Tiếp tục với chuyên khoa <span x-text="analysisResult.recommended_specialty.specialty_name"></span>
                                </button>
                                <button type="button"
                                    @click="document.getElementById('step-select-specialty').scrollIntoView({ behavior: 'smooth' })"
                                    class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-xs font-semibold rounded-lg transition">
                                    Tự chọn chuyên khoa khác
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- BƯỚC 2: CHỌN CHUYÊN KHOA & XEM BÁC SĨ PHỤ TRÁCH -->
            <div id="step-select-specialty" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Bước 2: Chọn Chuyên Khoa Khám Bệnh</h3>
                        <p class="text-sm text-gray-500">Bạn có thể giữ chuyên khoa do AI gợi ý hoặc chủ động bấm chọn bất kỳ chuyên khoa nào dưới đây</p>
                    </div>
                </div>

                <!-- Danh sách 4 chuyên khoa -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <template x-for="sp in specialties" :key="sp.specialty_id">
                        <div @click="selectedSpecialtyId = sp.specialty_id"
                            :class="selectedSpecialtyId === sp.specialty_id
                                ? 'border-emerald-500 bg-emerald-50/60 ring-2 ring-emerald-500'
                                : 'border-gray-200 hover:border-emerald-300 bg-white'"
                            class="cursor-pointer rounded-xl border p-4 transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-bold text-gray-900" x-text="sp.specialty_name"></h4>
                                    <template x-if="selectedSpecialtyId === sp.specialty_id">
                                        <span class="px-2 py-0.5 bg-emerald-600 text-white text-xs font-bold rounded-full">Đang chọn</span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-600 line-clamp-2" x-text="sp.description"></p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                                <span x-text="sp.doctors.length + ' Bác sĩ phụ trách'"></span>
                                <span class="text-emerald-600 font-semibold">Chọn khoa &rarr;</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Danh sách Bác sĩ thuộc chuyên khoa đang chọn -->
                <template x-if="selectedSpecialty">
                    <div class="pt-4 border-t border-gray-100 space-y-4">
                        <h4 class="font-bold text-gray-800 text-base">
                            Danh sách Bác sĩ chuyên khoa <span class="text-emerald-700" x-text="selectedSpecialty.specialty_name"></span>:
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="doc in selectedSpecialty.doctors" :key="doc.doctor_id">
                                <div class="p-4 rounded-xl border border-gray-200 bg-gray-50/50 flex items-start justify-between gap-4">
                                    <div>
                                        <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded mb-1"
                                            x-text="doc.degree"></span>
                                        <h5 class="font-bold text-gray-900 text-base" x-text="doc.name"></h5>
                                        <p class="text-xs text-gray-600 mt-1" x-text="doc.bio"></p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Kinh nghiệm: <strong x-text="doc.experience_years + ' năm'"></strong> |
                                            Cơ sở: <strong x-text="doc.clinic_name"></strong>
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
