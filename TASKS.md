# 📋 KẾ HOẠCH & DANH SÁCH NHIỆM VỤ CHI TIẾT — SMART CLINIC

> **Mục đích tài liệu:** Đây là bảng điều khiển công việc (Task Backlog & Execution Plan) dùng chung để theo dõi tiến độ từ thời điểm hiện tại cho đến khi hoàn thiện đồ án.  
> **Cách sử dụng khi làm việc:** Trong các phiên làm việc tiếp theo, bạn chỉ cần yêu cầu theo mã nhiệm vụ (ví dụ: *"Triển khai nhiệm vụ T1.1"* hoặc *"Làm hết Phase 1"*). Sau khi hoàn thành nhiệm vụ nào, chúng ta sẽ đánh dấu `[x]` vào nhiệm vụ đó.

---

## 🔍 Hiện trạng xuất phát điểm (Baseline hiện tại)

### Đã hoàn thành:
- [x] Khởi tạo dự án Laravel + Laravel Breeze + Tailwind CSS + Alpine.js.
- [x] Hoàn thiện bộ tài liệu đặc tả nghiệp vụ trong thư mục [`docs/`](docs/):
  - Phân quyền RBAC ([`docs/permissions.md`](docs/permissions.md) — SC02)
  - Quy tắc đặt/hủy/đổi lịch ([`docs/appointment-rules.md`](docs/appointment-rules.md) — SC03)
  - Vòng đời trạng thái lịch hẹn ([`docs/appointment-statuses.md`](docs/appointment-statuses.md) — SC04)
  - Sơ đồ ERD ([`docs/erd.md`](docs/erd.md) — SC05)
  - Danh mục dữ liệu mô phỏng ([`docs/mock-categories.md`](docs/mock-categories.md) — SC06)
  - Use Cases & Quy trình nghiệp vụ ([`docs/use-cases.md`](docs/use-cases.md) — SC07)
  - Từ điển dữ liệu ([`docs/data-dictionary.md`](docs/data-dictionary.md) — SC08)
- [x] Tạo bước đầu 8 file Migration và 8 file Model khung.

### ⚠️ Các điểm lệch (Gap) trong code hiện tại cần xử lý ngay ở Phase 1:
1. **Bảng `users`** ([`0001_01_01_000000_create_users_table.php`](database/migrations/0001_01_01_000000_create_users_table.php)): Chưa có các cột bắt buộc theo đặc tả `SC03` & `SC08` (`role_id`, `cccd`, `phone`, `dob`).
2. **Bảng `appointments`** ([`2026_11_08_084803_create_appointments_table.php`](database/migrations/2026_11_08_084803_create_appointments_table.php)):
   - Cột `status` đang là `['pending', 'confirmed', 'completed', 'cancelled', 'rescheduled']` — **chưa khớp** với 5 trạng thái chuẩn của `SC04` (`Pending`, `Checked-in`, `In Progress`, `Completed`, `Cancelled`).
   - Thiếu `appointment_code` (mã phiếu hẹn duy nhất), `cancel_reason` (lý do hủy/vắng mặt), và cờ đánh dấu lịch bị ảnh hưởng khi bác sĩ nghỉ đột xuất.
3. **Các khóa chính (Primary Keys):** Các bảng `roles`, `clinics`, `specialties`, `doctors`, `symptoms`, `schedules`, `appointments` đang dùng khóa chính tên riêng (`role_id`, `clinic_id`, `specialty_id`, `doctor_id`, `symptom_id`, `schedule_id`, `appointment_id`), nhưng các file Model trong [`app/Models/`](app/Models/) hiện đang để trống (`//`), chưa khai báo `$primaryKey`, `$fillable` và Relationships.

---

## 🗺️ LỘ TRÌNH NHIỆM VỤ CHI TIẾT

---

### 📦 PHASE 1: Chuẩn hóa Database, Models & Dữ liệu Mô phỏng (Foundation)

#### [x] `T1.1` — Đồng bộ Schema Migration & Cấu hình Múi giờ
- **Mục tiêu:** Đảm bảo cấu trúc bảng trong MySQL khớp 100% với đặc tả `SC03`, `SC04`, `SC08` và cấu hình múi giờ chuẩn Việt Nam.
- **File tác động:**
  - [`config/app.php`](config/app.php): Đặt `'timezone' => 'Asia/Ho_Chi_Minh'`.
  - [`database/migrations/0001_01_01_000000_create_users_table.php`](database/migrations/0001_01_01_000000_create_users_table.php) & [`database/migrations/2026_11_10_000001_add_role_and_profile_fields_to_users_table.php`](database/migrations/2026_11_10_000001_add_role_and_profile_fields_to_users_table.php):
    - Thêm `role_id` (FK -> `roles.role_id`), `cccd` (string 20, unique, nullable khi mới đăng ký nhưng bắt buộc trước khi đặt lịch), `phone` (string 20, nullable), `dob` (date, nullable).
  - [`database/migrations/2026_11_08_084757_create_doctors_table.php`](database/migrations/2026_11_08_084757_create_doctors_table.php):
    - Thêm `clinic_id` (FK -> `clinics.clinic_id`) theo đúng `SC08`.
  - [`database/migrations/2026_11_08_084802_create_schedules_table.php`](database/migrations/2026_11_08_084802_create_schedules_table.php):
    - Bổ sung cờ `is_day_off` (boolean, default `false`) để hỗ trợ nghiệp vụ bác sĩ nghỉ đột xuất.
  - [`database/migrations/2026_11_08_084803_create_appointments_table.php`](database/migrations/2026_11_08_084803_create_appointments_table.php):
    - Thêm `appointment_code` (string, unique), `doctor_id` (FK -> `doctors`), `appointment_date`, `start_time`.
    - Chuẩn hóa `status` theo `SC04`: `pending`, `checked_in`, `in_progress`, `completed`, `cancelled`.
    - Thêm `cancel_reason` (nullable), `is_affected_by_leave` (boolean, default `false`), `rescheduled_at` (timestamp, nullable).
- **Tiêu chí hoàn thành (DoD):** Chạy `php artisan migrate:fresh` thành công, không lỗi khóa ngoại.

#### [x] `T1.2` — Hoàn thiện 8 Eloquent Models & Relationships
- **Mục tiêu:** Khai báo đầy đủ `$primaryKey`, `$fillable`, `$casts`, các hằng số trạng thái (Status Constants/Enums) và quan hệ Eloquent.
- **File tác động:**
  - [`app/Models/Role.php`](app/Models/Role.php): `$primaryKey = 'role_id'`, quan hệ `users()`.
  - [`app/Models/User.php`](app/Models/User.php): Quan hệ `role()`, `doctor()`, `appointments()`; thêm các hàm helper: `isAdmin()`, `isDoctor()`, `isReceptionist()`, `isPatient()`, `hasCompleteBookingProfile()`.
  - [`app/Models/Clinic.php`](app/Models/Clinic.php): `$primaryKey = 'clinic_id'`, quan hệ `doctors()`, `schedules()`.
  - [`app/Models/Specialty.php`](app/Models/Specialty.php): `$primaryKey = 'specialty_id'`, quan hệ `doctors()`, `symptoms()`.
  - [`app/Models/Doctor.php`](app/Models/Doctor.php): `$primaryKey = 'doctor_id'`, quan hệ `user()`, `specialty()`, `clinic()`, `schedules()`, `appointments()`.
  - [`app/Models/Symptom.php`](app/Models/Symptom.php): `$primaryKey = 'symptom_id'`, quan hệ `specialty()`.
  - [`app/Models/Schedule.php`](app/Models/Schedule.php): `$primaryKey = 'schedule_id'`, quan hệ `doctor()`, `clinic()`, `appointments()`, helper kiểm tra còn chỗ trống (`isBookable()`).
  - [`app/Models/Appointment.php`](app/Models/Appointment.php): `$primaryKey = 'appointment_id'`, quan hệ `patient()`, `schedule()`, helper kiểm tra điều kiện hủy/đổi trước 2 giờ (`canBeCancelledOrRescheduled()`), kiểm tra vắng mặt sau 15 phút (`isNoShowEligible()`).
- **Tiêu chí hoàn thành (DoD):** Truy vấn thử các quan hệ qua `php artisan tinker` hoạt động chính xác.

#### [x] `T1.3` — Xây dựng bộ Seeder Dữ liệu Mô phỏng (`SC06`)
- **Mục tiêu:** Tạo sẵn toàn bộ dữ liệu danh mục và tài khoản mẫu theo đúng [`docs/mock-categories.md`](docs/mock-categories.md) để sẵn sàng demo và kiểm thử.
- **File tác động:**
  - [`database/seeders/DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php) và các Seeder thành phần:
    - **4 Vai trò:** `Patient`, `Receptionist`, `Doctor`, `Admin`.
    - **1 Cơ sở khám:** `Smart Clinic Vinh` (TP. Vinh, Nghệ An).
    - **4 Chuyên khoa:** Nội tổng quát, Tai Mũi Họng, Da liễu, Cơ xương khớp.
    - **5 Bác sĩ mẫu:** BS. Nguyễn Văn A, BS. Trần Thị B, BS. Lê Văn C, BS. Phạm Thị D, BS. Hoàng Văn E.
    - **19 Triệu chứng chuẩn:** Gắn đúng vào 4 chuyên khoa (Đau bụng, sốt cao, đau họng, nổi mẩn đỏ, đau lưng,...).
    - **Tài khoản mẫu:**
      - `admin@smartclinic.vn` (Quản trị viên)
      - `receptionist@smartclinic.vn` (Lễ tân)
      - 5 tài khoản Bác sĩ
      - 2 tài khoản Bệnh nhân (1 tài khoản đã đủ CCCD, 1 tài khoản thiếu CCCD để test chặn đặt lịch `LH17`).
    - **Lịch làm việc (`schedules`):** Tạo 420 khung giờ khám (12 khung giờ/ngày * 7 ngày * 5 bác sĩ).
- **Tiêu chí hoàn thành (DoD):** Chạy `php artisan migrate:fresh --seed` nạp đầy đủ dữ liệu mẫu.

---

### 🔐 PHASE 2: Phân quyền RBAC, Xác thực & Hồ sơ Cá nhân

#### [ ] `T2.1` — Xây dựng Middleware Phân quyền (`RoleMiddleware`) & Điều hướng theo Vai trò
- **Mục tiêu:** Thực thi ma trận quyền `SC02` ở phía máy chủ (Server-side enforcement). Không dựa vào việc ẩn nút trên giao diện.
- **File tác động:**
  - Tạo `app/Http/Middleware/RoleMiddleware.php`: Kiểm tra vai trò người dùng đang đăng nhập (`role:patient`, `role:receptionist`, `role:doctor`, `role:admin`), trả về `403 Forbidden` nếu sai quyền.
  - Đăng ký alias middleware trong [`bootstrap/app.php`](bootstrap/app.php).
  - Cập nhật điều hướng tại `/dashboard` trong [`routes/web.php`](routes/web.php) để chuyển người dùng về đúng không gian làm việc của vai trò tương ứng.
- **Tiêu chí hoàn thành (DoD):** Đăng nhập từng tài khoản mẫu đều vào đúng trang của vai trò đó; truy cập trái phép đường dẫn của vai trò khác bị chặn với mã `403`.

#### [ ] `T2.2` — Quản lý Hồ sơ Bệnh nhân & Ràng buộc CCCD (`UC_P02`, `LH17`)
- **Mục tiêu:** Cho phép người dùng cập nhật `name`, `dob`, `phone`, `cccd` và kiểm tra bắt buộc đủ 4 trường này trước khi cho phép đặt lịch.
- **File tác động:**
  - [`app/Http/Requests/ProfileUpdateRequest.php`](app/Http/Requests/ProfileUpdateRequest.php): Thêm rule validate `cccd` (12 chữ số, unique), `phone` (10-11 số), `dob` (ngày sinh hợp lệ trong quá khứ).
  - [`resources/views/profile/partials/update-profile-information-form.blade.php`](resources/views/profile/partials/update-profile-information-form.blade.php): Bổ sung ô nhập CCCD, SĐT, Ngày sinh.
- **Tiêu chí hoàn thành (DoD):** Bệnh nhân chưa có CCCD khi bấm vào "Đặt lịch khám" sẽ được chuyển hướng về trang Hồ sơ kèm thông báo yêu cầu bổ sung thông tin (`LH17`).

---

### 📅 PHASE 3: Nghiệp vụ Đặt lịch, AI Gợi ý Chuyên khoa & Hủy/Đổi lịch (Patient Flow)

#### [ ] `T3.1` — Xây dựng Dịch vụ Gợi ý Chuyên khoa từ Triệu chứng (`AI & Rule Matcher`)
- **Mục tiêu:** Cho phép bệnh nhân nhập mô tả triệu chứng tự nhiên -> trích xuất các triệu chứng khớp với danh mục 15 triệu chứng -> gợi ý chuyên khoa phù hợp nhất.
- **File tác động:**
  - Tạo `app/Services/SymptomAnalysisService.php`:
    - Hỗ trợ trích xuất qua AI API (Gemini/OpenAI nếu có API Key trong `.env`) kết hợp cơ chế đối sánh từ khóa thông minh (Fallback Rule-based Matcher) để luôn hoạt động ổn định khi demo offline.
    - Trả về: Danh sách triệu chứng nhận diện được + Chuyên khoa khuyến nghị + Danh sách bác sĩ thuộc chuyên khoa đó.
  - Tạo API/Endpoint nội bộ `POST /patient/symptoms/analyze` để giao diện gọi bất đồng bộ (AJAX/Alpine.js).
- **Tiêu chí hoàn thành (DoD):** Nhập câu mô tả *"Tôi bị đau họng, ho có đờm và nghẹt mũi"* -> Hệ thống trích xuất đúng triệu chứng và gợi ý chuyên khoa **Tai Mũi Họng**; bệnh nhân vẫn có quyền bỏ qua AI để tự chọn chuyên khoa.

#### [ ] `T3.2` — Xây dựng Service & Luồng Đặt lịch Khám (`UC_P03`, `UC_P04`, `LH01`–`LH06`, `LH15`)
- **Mục tiêu:** Bệnh nhân tra cứu lịch trống, đặt lịch an toàn với Database Transaction và chống xung đột (Race Condition).
- **File tác động:**
  - Tạo `app/Services/AppointmentBookingService.php`:
    - Sử dụng `DB::transaction()` và `Schedule::where(...)->lockForUpdate()` để đảm bảo tại một thời điểm chỉ 1 bệnh nhân đặt được khung giờ đó (`LH05`).
    - Kiểm tra khung giờ chưa bắt đầu (`LH03`), không thuộc ngày nghỉ (`LH04`), và bệnh nhân không có lịch hẹn khác trùng khung giờ (`LH15`).
    - Chống gửi lặp yêu cầu (Idempotency / kiểm tra lịch vừa tạo `LH06`).
    - Sinh mã phiếu hẹn duy nhất (ví dụ: `SC-20261110-0001`), trạng thái `pending`.
  - Tạo `app/Http/Controllers/Patient/BookingController.php` & các màn hình Blade:
    - Màn hình tra cứu & đặt lịch (có tích hợp bước nhập triệu chứng AI).
    - Màn hình hiển thị Phiếu hẹn điện tử sau khi đặt thành công.
- **Tiêu chí hoàn thành (DoD):** Đặt lịch thành công tạo phiếu hẹn, cập nhật trạng thái slot; chặn toàn bộ các ca không hợp lệ (`LH01`–`LH06`, `LH15`, `LH17`).

#### [ ] `T3.3` — Chức năng Xem lịch sử, Hủy lịch & Đổi lịch của Bệnh nhân (`UC_P05`, `UC_P06`, `LH07`–`LH14`)
- **Mục tiêu:** Bệnh nhân quản lý lịch hẹn của chính mình, hủy hoặc đổi lịch đúng quy định thời hạn 2 giờ.
- **File tác động:**
  - Tạo `app/Http/Controllers/Patient/AppointmentController.php`:
    - Chỉ cho phép truy cập lịch hẹn của chính bệnh nhân đang đăng nhập (`PQ01`, `PQ02`, `LH10`).
    - **Hủy lịch (`cancel`):** Chỉ cho phép khi trạng thái là `pending` (`PQ04`, `LH09`) và thời gian hiện tại cách giờ bắt đầu khám `>= 2 giờ` (`LH07`, `LH08`). Giải phóng chỗ của khung giờ cũ và lưu lý do hủy (`LH11`).
    - **Đổi lịch (`reschedule`):** Chỉ cho phép khi cách giờ khám cũ `>= 2 giờ`, khung giờ mới thuộc **cùng chuyên khoa** và còn trống. Thực hiện trong 1 `DB::transaction()` — nếu khung giờ mới hết chỗ hoặc lỗi thì giữ nguyên lịch cũ (`LH12`–`LH14`).
- **Tiêu chí hoàn thành (DoD):** Hủy/đổi lịch hoạt động đúng mốc 2 giờ; đổi lịch thất bại không làm mất lịch cũ.

---

### 🏥 PHASE 4: Nghiệp vụ Lễ tân, Bác sĩ & Quản trị viên (Staff & Admin Flows)

#### [ ] `T4.1` — Phân hệ Lễ tân: Tiếp nhận (`Check-in`) & Xử lý Vắng mặt (`UC_R01`–`UC_R04`)
- **Mục tiêu:** Lễ tân quản lý danh sách hẹn trong ngày, xác nhận bệnh nhân đến hoặc đánh dấu vắng mặt sau 15 phút.
- **File tác động:**
  - Tạo `app/Http/Controllers/Receptionist/CheckInController.php` & giao diện Lễ tân:
    - Tra cứu lịch hẹn theo ngày, tìm nhanh theo Mã phiếu hẹn / Số CCCD / SĐT.
    - **Tiếp nhận (`checkIn`):** Chuyển từ `pending` -> `checked_in`, cấp số thứ tự hàng đợi (`queue_number`) (`PQ07`).
    - **Đánh dấu vắng mặt (`markNoShow`):** Chỉ cho phép khi trạng thái là `pending` và thời gian hiện tại đã qua giờ bắt đầu khung giờ khám **>= 15 phút** (theo `SC04` mục 4) -> chuyển sang `cancelled` với lý do `Vắng mặt (No-show)`.
    - Tab theo dõi danh sách các lịch hẹn bị ảnh hưởng do bác sĩ nghỉ đột xuất (`is_affected_by_leave = true`) để liên hệ hỗ trợ bệnh nhân (`UC_R04`, `LH16`).
- **Tiêu chí hoàn thành (DoD):** Lễ tân tiếp nhận đúng quy trình, không thể đánh dấu vắng mặt khi chưa quá 15 phút.

#### [ ] `T4.2` — Phân hệ Bác sĩ: Hàng đợi Khám & Cập nhật Tiến trình (`UC_D01`–`UC_D03`, `PQ05`–`PQ06`)
- **Mục tiêu:** Bác sĩ xem danh sách bệnh nhân của riêng mình trong ca và điều khiển quá trình khám.
- **File tác động:**
  - Tạo `app/Http/Controllers/Doctor/ExaminationController.php` & giao diện Bác sĩ:
    - Chỉ hiển thị lịch hẹn thuộc các ca khám của chính bác sĩ đang đăng nhập (`PQ05`, chặn `PQ06`).
    - Xem chi tiết triệu chứng bệnh nhân đã khai báo và kết quả trích xuất AI (`UC_D03`).
    - **Bắt đầu khám (`startExam`):** Chỉ cho phép chuyển từ `checked_in` -> `in_progress` (chặn nhảy cóc từ `pending` sang `in_progress` theo `SC04` mục 3).
    - **Hoàn tất khám (`completeExam`):** Chuyển từ `in_progress` -> `completed`.
- **Tiêu chí hoàn thành (DoD):** Luồng chuyển trạng thái của bác sĩ tuân thủ chặt chẽ `checked_in` -> `in_progress` -> `completed`.

#### [ ] `T4.3` — Phân hệ Quản trị viên: Quản lý Danh mục, Lịch làm việc & Nghỉ đột xuất (`UC_A01`–`UC_A05`, `LH16`)
- **Mục tiêu:** Admin quản lý toàn bộ dữ liệu nền, công bố lịch khám và xử lý tình huống bác sĩ báo nghỉ đột xuất.
- **File tác động:**
  - Tạo các Controller trong `app/Http/Controllers/Admin/`:
    - `DashboardController.php`: Thống kê tổng số lịch hẹn theo trạng thái, theo chuyên khoa, tỷ lệ đến khám / hủy / vắng mặt (`UC_A05`).
    - `CatalogController.php` (hoặc tách riêng `SpecialtyController`, `DoctorController`, `SymptomController`): Quản lý danh mục chuyên khoa, bác sĩ, triệu chứng (`UC_A02`).
    - `ScheduleController.php`: Tạo ca làm việc/khung giờ khám cho bác sĩ (`UC_A03`).
    - **Xử lý Bác sĩ nghỉ đột xuất (`markDayOff` - `UC_A04`, `LH16`):** Khi Admin đánh dấu một ca/khung giờ là nghỉ đột xuất (`is_day_off = true`, `status = cancelled`), hệ thống khóa không nhận lịch mới và tự động gắn cờ `is_affected_by_leave = true` cho các lịch hẹn `pending` đang nằm trong ca đó để chuyển sang danh sách cần xử lý của Lễ tân (tuyệt đối không tự ý xóa lịch hẹn).
- **Tiêu chí hoàn thành (DoD):** Admin tạo được lịch khám, báo nghỉ đột xuất tự động gắn cờ cảnh báo cho lễ tân mà không làm mất dữ liệu lịch hẹn.

---

### 🧪 PHASE 5: Kiểm thử Tự động (Automated Testing) & Nghiệm thu Đồ án

#### [ ] `T5.1` — Viết Feature Tests cho Phân quyền RBAC (`PQ01` – `PQ07`)
- **Mục tiêu:** Chứng minh bằng тест tự động rằng mọi giới hạn phân quyền trong [`docs/permissions.md`](docs/permissions.md) đều được bảo vệ ở tầng Server.
- **File tác động:** `tests/Feature/PermissionsTest.php`
  - Kiểm tra đầy đủ các kịch bản `PQ01` đến `PQ07` (Bệnh nhân A không xem/sửa/hủy được lịch của Bệnh nhân B; Bác sĩ A không cập nhật được lượt khám của Bác sĩ B; Lễ tân không thể bấm hoàn tất khám thay Bác sĩ).

#### [ ] `T5.2` — Viết Feature Tests cho Quy tắc Lịch hẹn & Trạng thái (`LH01` – `LH17` & `SC04`)
- **Mục tiêu:** Đảm bảo toàn bộ quy tắc đặt, hủy, đổi lịch và chuyển trạng thái trong [`docs/appointment-rules.md`](docs/appointment-rules.md) & [`docs/appointment-statuses.md`](docs/appointment-statuses.md) vượt qua kiểm thử.
- **File tác động:** `tests/Feature/AppointmentRulesTest.php`
  - Test đặt lịch thiếu CCCD bị chặn (`LH17`).
  - Test hủy/đổi lịch >= 2 giờ thành công và < 2 giờ bị từ chối (`LH07`, `LH08`).
  - Test không cho phép nhảy cóc trạng thái (`Pending` -> `In Progress` bị từ chối).
  - Test đổi lịch thất bại giữ nguyên lịch cũ (`LH13`, `LH14`).
  - Test ca nghỉ đột xuất đánh dấu lịch bị ảnh hưởng (`LH16`).

#### [ ] `T5.3` — Rà soát Giao diện, Chuẩn hóa Code & Nghiệm thu
- **Mục tiêu:** Đảm bảo giao diện đồng bộ, dễ sử dụng khi bảo vệ đồ án và code sạch theo chuẩn PSR-12.
- **Công việc:**
  - Chạy `./vendor/bin/pint` để format code chuẩn Laravel.
  - Chạy `php artisan test` đảm bảo 100% test xanh (PASS).
  - Kiểm tra lại kịch bản Demo đầu-cuối (End-to-End Demo Script) từ lúc Bệnh nhân khai báo triệu chứng -> Đặt lịch -> Lễ tân Check-in -> Bác sĩ Khám hoàn tất.

---

## 🚀 BẢNG THEO DÕI NHANH (QUICK TRACKER)

| Mã | Nhiệm vụ | Giai đoạn | Ưu tiên | Trạng thái |
|---|---|---|---|---|
| **T1.1** | Đồng bộ Schema Migration (`users`, `doctors`, `schedules`, `appointments`) & Timezone | Phase 1 | 🔴 Cao nhất | ✅ Hoàn thành |
| **T1.2** | Hoàn thiện 8 Eloquent Models, `$primaryKey`, Relationships & Helpers | Phase 1 | 🔴 Cao nhất | ✅ Hoàn thành |
| **T1.3** | Viết Seeders dữ liệu mô phỏng chuẩn `SC06` (4 khoa, 5 BS, 15 triệu chứng, Lịch khám) | Phase 1 | 🔴 Cao nhất | ✅ Hoàn thành |
| **T2.1** | Middleware Phân quyền RBAC 4 vai trò & Điều hướng Dashboard | Phase 2 | 🟠 Cao | ⏳ Chờ thực hiện |
| **T2.2** | Cập nhật Hồ sơ cá nhân & Chặn đặt lịch khi thiếu CCCD (`LH17`) | Phase 2 | 🟠 Cao | ⏳ Chờ thực hiện |
| **T3.1** | Service AI Trích xuất triệu chứng & Gợi ý chuyên khoa | Phase 3 | 🟠 Cao | ⏳ Chờ thực hiện |
| **T3.2** | Luồng Đặt lịch khám (Transaction chống trùng chỗ `LH05` & Tạo phiếu hẹn) | Phase 3 | 🟠 Cao | ⏳ Chờ thực hiện |
| **T3.3** | Xem lịch sử, Hủy lịch & Đổi lịch cho Bệnh nhân (Mốc 2 giờ `LH07`–`LH14`) | Phase 3 | 🟡 Trung bình | ⏳ Chờ thực hiện |
| **T4.1** | Phân hệ Lễ tân: Tiếp nhận (`Checked-in`) & Đánh dấu Vắng mặt sau 15p | Phase 4 | 🟠 Cao | ⏳ Chờ thực hiện |
| **T4.2** | Phân hệ Bác sĩ: Hàng đợi khám & Cập nhật `In Progress` -> `Completed` | Phase 4 | 🟠 Cao | ⏳ Chờ thực hiện |
| **T4.3** | Phân hệ Admin: Quản lý danh mục, lịch làm việc & Nghỉ đột xuất (`LH16`) | Phase 4 | 🟡 Trung bình | ⏳ Chờ thực hiện |
| **T5.1** | Feature Tests Phân quyền RBAC (`PQ01`–`PQ07`) | Phase 5 | 🟢 Chuẩn bị nghiệm thu | ⏳ Chờ thực hiện |
| **T5.2** | Feature Tests Quy tắc Lịch hẹn & Trạng thái (`LH01`–`LH17`) | Phase 5 | 🟢 Chuẩn bị nghiệm thu | ⏳ Chờ thực hiện |
| **T5.3** | Format code (`Pint`), Kiểm thử toàn trình & Kịch bản Demo bảo vệ đồ án | Phase 5 | 🟢 Chuẩn bị nghiệm thu | ⏳ Chờ thực hiện |

