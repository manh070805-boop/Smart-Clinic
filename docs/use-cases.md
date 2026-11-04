# THIẾT KẾ USE CASE VÀ QUY TRÌNH NGHIỆP VỤ (SC07)

## 1. Danh sách Use Case theo Vai trò

### 1.1. Bệnh nhân (Patient)
- UC_P01: Đăng ký và Đăng nhập.
- UC_P02: Quản lý hồ sơ cá nhân (Bắt buộc cập nhật CCCD trước khi đặt lịch).
- UC_P03: Tra cứu chuyên khoa, bác sĩ, lịch trống.
- UC_P04: Đặt lịch khám (Có tùy chọn dùng AI trích xuất triệu chứng).
- UC_P05: Hủy lịch khám (Điều kiện: Trước 2 giờ).
- UC_P06: Đổi lịch khám (Điều kiện: Trước 2 giờ, cùng chuyên khoa).
- UC_P07: Xem lịch sử khám bệnh và phiếu hẹn.

### 1.2. Lễ tân (Receptionist)
- UC_R01: Tra cứu danh sách lịch hẹn trong ngày.
- UC_R02: Tiếp nhận bệnh nhân (`Pending` -> `Checked-in`).
- UC_R03: Đánh dấu vắng mặt (`Pending` -> `Cancelled`) khi bệnh nhân đến muộn quá 15 phút.
- UC_R04: Hỗ trợ liên hệ bệnh nhân khi có ngoại lệ (Bác sĩ nghỉ đột xuất).
*(Lưu ý: Lễ tân không tự ý hủy/đổi lịch thay bệnh nhân trong điều kiện bình thường).*

### 1.3. Bác sĩ (Doctor)
- UC_D01: Xem danh sách bệnh nhân được phân công khám trong ca.
- UC_D02: Cập nhật tiến trình khám (`Checked-in` -> `In Progress` -> `Completed`).
- UC_D03: Xem chi tiết triệu chứng bệnh nhân đã khai báo (sau khi tiếp nhận).

### 1.4. Quản trị viên (Admin)
- UC_A01: Quản lý tài khoản và phân quyền.
- UC_A02: Quản lý danh mục (Chuyên khoa, Phòng khám, Triệu chứng).
- UC_A03: Quản lý lịch làm việc của bác sĩ (Thiết lập ca, khung giờ, ngày nghỉ).
- UC_A04: Xử lý ngoại lệ bác sĩ nghỉ đột xuất (Đóng ca, cắm cờ ảnh hưởng).
- UC_A05: Xem báo cáo thống kê cơ bản.

---

## 2. Quy trình nghiệp vụ cốt lõi

### 2.1. Luồng Đặt lịch khám (Booking Flow)
1. **Bước 1:** Bệnh nhân đăng nhập và vào màn hình Đặt lịch. (Hệ thống kiểm tra đã có CCCD chưa, nếu chưa yêu cầu cập nhật).
2. **Bước 2 (Tùy chọn):** Bệnh nhân nhập mô tả triệu chứng -> Hệ thống gọi AI trích xuất -> Gợi ý chuyên khoa. Bệnh nhân có thể đồng ý hoặc tự chọn chuyên khoa khác.
3. **Bước 3:** Bệnh nhân chọn Bác sĩ, Ngày khám và Khung giờ còn trống.
4. **Bước 4:** Bệnh nhân xác nhận đặt lịch.
5. **Bước 5:** Hệ thống kiểm tra điều kiện (Không trùng lịch, khung giờ chưa bắt đầu, bác sĩ không nghỉ đột xuất).
6. **Bước 6:** Đặt lịch thành công, tạo mã phiếu hẹn, trạng thái lịch là `Pending`.

### 2.2. Luồng Tiếp nhận và Khám bệnh (Check-in & Examination Flow)
1. **Bước 1:** Bệnh nhân đến cơ sở y tế vào ngày hẹn và gặp Lễ tân.
2. **Bước 2:** Lễ tân kiểm tra mã phiếu hẹn/CCCD.
    - Nếu quá giờ 15 phút: Lễ tân đánh dấu `Cancelled (Vắng mặt)`.
    - Nếu hợp lệ: Lễ tân bấm tiếp nhận, trạng thái chuyển thành `Checked-in`.
3. **Bước 3:** Bệnh nhân ngồi chờ tại khu vực phòng khám. Bác sĩ xem danh sách hàng đợi trên hệ thống.
4. **Bước 4:** Bác sĩ gọi bệnh nhân vào phòng, bấm bắt đầu khám -> trạng thái chuyển `In Progress`.
5. **Bước 5:** Bác sĩ khám xong, ghi nhận kết quả và bấm hoàn thành -> trạng thái chuyển `Completed`.