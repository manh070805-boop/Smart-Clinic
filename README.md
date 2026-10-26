# Smart-Clinic 
Website quản lý đặt lịch khám và hỗ trợ định hướng chuyên khoa, mô phỏng hoạt động của một cơ sở khám bệnh

## 1. Vấn đề cần giải quyết 

- Bệnh nhân cần tra cứu lịch khám và đặt lịch thuận tiện
- Cơ sở khám bệnh cần quản lý lịch bác sĩ, phòng khám, lịch hẹn và phối hợp quy trình tiếp nhận, khám bệnh

- Hệ thống hỗ trợ chuyển mô tả triệu chứng bằng ngôn ngữ tự nhiên thành thông tin có cấu trúc để bệnh nhân kiểm tra, xác nhận và nhận gợi ý chuyên khoa 

## 2. Mục tiêu

- Hỗ trợ bệnh nhân xem lịch trống, đặt, hủy và đổi lịch khám
- Quản lý bác sĩ, chuyên khoa, phòng khám và lịch làm việc
- Kiểm soát trùng lịch, đặt đồng thời và xác nhận lặp
- Hỗ trợ lễ tân tiếp nhận và bác sĩ cập nhật tiến trình khám 
- Sử dụng AI để trích xuất thông tin từ mô tả triệu chứng 
- Gợi ý chuyên khoa bằng bộ quy tắc và hiển thị lý do 
- Cung cấp báo cáo cơ bản và lịch sử thay đổi quan trọng

## 3. Người sử dụng

| Vai trò | Nghiệp vụ chính dự kiến |
|---|---|
| Bệnh nhân | Quản lý hồ sơ, khai báo triệu chứng, xem lịch, đặt, hủy và đổi lịch |
| Lễ tân | Tra cứu lịch hẹn, xác nhận bệnh nhân đến và quản lý danh sách chờ |
| Bác sĩ | Xem bệnh nhân được phân công và cập nhật tiến trình khám |
| Quản trị viên | Quản lý tài khoản, phân quyền, danh mục, lịch làm việc và báo cáo |

Quyền chi tiết của từng vai trò được xác định trong tài liệu yêu cầu.

## 4. Phạm vi bản chính

### Tài khoản và phân quyền
- Đăng ký tài khoản bệnh nhân, đăng nhập và quản lý hồ sơ.
- Phân quyền cho bốn vai trò.

### Quản lý danh mục
- Chuyên khoa, bác sĩ, phòng khám và triệu chứng.

### Quản lý lịch làm việc
- Ca làm việc, khung giờ và ngày nghỉ.
- Kiểm tra trùng lịch bác sĩ và phòng khám.
- Hiển thị lịch còn trống.

### Quản lý lịch hẹn
- Đặt, xem, hủy và đổi lịch.
- Tạo phiếu hẹn.
- Kiểm soát đặt đồng thời và xác nhận lặp.

### Tiếp nhận và khám
- Lễ tân xác nhận bệnh nhân đến, quản lý danh sách chờ.
- Bác sĩ cập nhật tiến trình khám.

### AI và định hướng chuyên khoa
- Trích xuất thông tin từ mô tả triệu chứng.
- Cho bệnh nhân kiểm tra, sửa và xác nhận.
- Tính điểm chuyên khoa bằng bộ quy tắc.
- Hiển thị lý do gợi ý.
- Hỗ trợ khai báo thủ công khi AI không khả dụng.

### Quản trị
- Báo cáo cơ bản.
- Ghi lịch sử thay đổi quan trọng.

## 5. Luồng hỗ trợ định hướng

1. Bệnh nhân nhập mô tả triệu chứng.
2. AI trích xuất thông tin theo danh mục cho phép.
3. Bệnh nhân kiểm tra, sửa và xác nhận.
4. Bộ quy tắc xử lý dữ liệu đã xác nhận.
5. Hệ thống gợi ý chuyên khoa và hiển thị lý do.
6. Bệnh nhân chọn bác sĩ, khung giờ và đặt lịch.

AI đảm nhiệm trích xuất thông tin.
Bộ quy tắc đảm nhiệm tính điểm định hướng chuyên khoa.
Thông tin chưa được đề cập được để trống, không tự xem là phủ nhận.

## 6. Ngoài phạm vi bản chính

- Thanh toán.
- Đánh giá bác sĩ.
- Chatbot hỏi đáp.
- Quản lý nhiều cơ sở.
- Hồ sơ bệnh án chi tiết.

## 7. Công nghệ dự kiến sử dụng

- Ngôn ngữ chính: PHP.
- Framework: Laravel.
- Cơ sở dữ liệu: MySQL.
- Giao diện: Blade, Bootstrap và JavaScript.

## 8. Trạng thái dự án

Dự án đang trong giai đoạn xác định yêu cầu và phạm vi.
Các chức năng trên mô tả kế hoạch bản chính,
chưa phải danh sách chức năng đã hoàn thành.