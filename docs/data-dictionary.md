# TỪ ĐIỂN DỮ LIỆU (DATA DICTIONARY) - SC08

Tài liệu này định nghĩa chi tiết các bảng, trường dữ liệu và ràng buộc trong cơ sở dữ liệu Smart Clinic.

## 1. Bảng `roles` (Vai trò)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã vai trò |
| `name` | VARCHAR(50) | NOT NULL, UNIQUE | Tên vai trò (Patient, Receptionist, Doctor, Admin) |

## 2. Bảng `users` (Người dùng)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã người dùng |
| `role_id` | BIGINT | FK -> roles(id) | Khóa ngoại chỉ định vai trò |
| `name` | VARCHAR(255) | NOT NULL | Họ và tên |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email đăng nhập |
| `password` | VARCHAR(255) | NOT NULL | Mật khẩu (đã mã hóa) |
| `cccd` | VARCHAR(20) | NOT NULL, UNIQUE | Số Căn cước công dân (Bắt buộc theo SC03) |
| `phone` | VARCHAR(20) | NOT NULL | Số điện thoại liên hệ |
| `dob` | DATE | NOT NULL | Ngày sinh |

## 3. Bảng `clinics` (Cơ sở khám bệnh)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã cơ sở |
| `name` | VARCHAR(255) | NOT NULL | Tên cơ sở (VD: Smart Clinic Vinh) |
| `address` | TEXT | NOT NULL | Địa chỉ |

## 4. Bảng `specialties` (Chuyên khoa)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã chuyên khoa |
| `name` | VARCHAR(255) | NOT NULL | Tên chuyên khoa |

## 5. Bảng `doctors` (Bác sĩ)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã hồ sơ bác sĩ |
| `user_id` | BIGINT | FK -> users(id) | Liên kết với tài khoản User |
| `specialty_id` | BIGINT | FK -> specialties(id)| Bác sĩ thuộc chuyên khoa nào |
| `clinic_id` | BIGINT | FK -> clinics(id) | Bác sĩ làm việc tại cơ sở nào |

## 6. Bảng `symptoms` (Triệu chứng)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã triệu chứng |
| `specialty_id` | BIGINT | FK -> specialties(id)| Triệu chứng này thuộc chuyên khoa nào |
| `name` | VARCHAR(255) | NOT NULL | Tên triệu chứng (VD: Đau họng, Sốt cao) |

## 7. Bảng `schedules` (Làm việc / Ca khám)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã lịch làm việc |
| `doctor_id` | BIGINT | FK -> doctors(id) | Lịch của bác sĩ nào |
| `work_date` | DATE | NOT NULL | Ngày làm việc |
| `start_time` | TIME | NOT NULL | Giờ bắt đầu ca |
| `end_time` | TIME | NOT NULL | Giờ kết thúc ca |
| `is_day_off` | BOOLEAN | DEFAULT 0 | Đánh dấu ngày nghỉ/ca nghỉ đột xuất |

## 8. Bảng `appointments` (Lịch hẹn)
| Cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT | PK, Auto Increment | Mã lịch hẹn |
| `patient_id` | BIGINT | FK -> users(id) | Bệnh nhân đặt lịch |
| `doctor_id` | BIGINT | FK -> doctors(id) | Bác sĩ phụ trách |
| `schedule_id` | BIGINT | FK -> schedules(id)| Thuộc ca làm việc nào |
| `appointment_date`| DATE | NOT NULL | Ngày khám |
| `start_time` | TIME | NOT NULL | Giờ bắt đầu khám |
| `status` | VARCHAR(50) | NOT NULL | Trạng thái (Pending, Checked-in, In Progress, Completed, Cancelled) |
| `ai_symptoms` | TEXT | NULL | Triệu chứng trích xuất từ AI (Nếu có) |

