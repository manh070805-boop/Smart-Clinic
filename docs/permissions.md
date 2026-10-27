# SC02 — Đặc tả quyền truy cập Smart Clinic

## 1. Mục đích

Xác định quyền chức năng và phạm vi dữ liệu của bốn vai trò:
bệnh nhân, bác sĩ, lễ tân và quản trị viên.

Tài liệu làm đầu vào cho thiết kế use case, triển khai phân quyền
và kiểm thử truy cập.

## 2. Nguyên tắc chung

- Chức năng yêu cầu đăng nhập phải được kiểm tra phía máy chủ.
- Ẩn nút trên giao diện không thay thế kiểm tra quyền.
- Mỗi vai trò chỉ thực hiện các thao tác được cấp.
- Dữ liệu được truy cập phải nằm trong phạm vi cho phép.
- Chuyển trạng thái phải đúng quyền và đúng quy trình.
- Các thay đổi quan trọng phải được ghi lịch sử.
- Khách chưa đăng nhập không được truy cập dữ liệu cá nhân
  hoặc thực hiện nghiệp vụ của các vai trò.

## 3. Ma trận quyền


| Chức năng | Bệnh nhân | Lễ tân | Bác sĩ | Quản trị viên |
|---|---|---|---|---|
| Xem, cập nhật hồ sơ cá nhân | Có | Có | Có | Có |
| Quản lý tài khoản và vai trò | Không | Không | Không | Có |
| Quản lý chuyên khoa, triệu chứng | Không | Không | Không | Có |
| Quản lý hồ sơ chuyên môn bác sĩ, phòng khám | Không | Không | Không | Có |
| Công bố ca, khung giờ và ngày nghỉ | Không | Không | Không | Có |
| Xem lịch khám còn trống | Có | Có | Có | Có |
| Đặt lịch và tạo phiếu hẹn | Cho bản thân | Không | Không | Không |
| Xem lịch hẹn | Của bản thân | Phục vụ tiếp nhận | Được phân công | Phục vụ quản trị |
| Hủy hoặc đổi lịch | Có điều kiện | Chưa cấp | Không | Không |
| Xác nhận bệnh nhân đến | Không | Có | Không | Không |
| Quản lý danh sách chờ, xác nhận vắng mặt | Không | Có điều kiện | Không | Không |
| Xem danh sách bệnh nhân được phân công | Không | Không | Có | Không |
| Cập nhật tiến trình, hoàn tất khám | Không | Không | Có điều kiện | Không |
| Nhập, sửa và xác nhận bản khai triệu chứng | Của bản thân | Không | Không | Không |
| Nhận gợi ý chuyên khoa | Cho bản thân | Không | Không | Không |
| Xem báo cáo quản trị cơ bản | Không | Không | Không | Có |
| Xem nhật ký thay đổi toàn hệ thống | Không | Không | Không | Có |

Việc xem bản khai triệu chứng của bệnh nhân bởi nhân viên
chưa được cấp trong tài liệu này; cần chốt nếu có nhu cầu.

## 4. Giới hạn theo vai trò

### 4.1. Bệnh nhân

- Chỉ xem, cập nhật hồ sơ và xử lý lịch hẹn của bản thân.
- Không được đổi mã bệnh nhân trong yêu cầu để truy cập
  hồ sơ hoặc lịch hẹn của người khác.
- Chỉ được hủy, đổi lịch khi chưa tiếp nhận và còn trong
  thời hạn nghiệp vụ đã quy định.
- Chỉ được sửa, xác nhận bản khai của bản thân.
- Có thể bỏ qua AI để khai báo thủ công hoặc chọn chuyên khoa.
- Không được tự thay đổi trạng thái tiếp nhận hoặc khám.

### 4.2. Lễ tân

- Tra cứu thông tin cần thiết để tiếp nhận lịch hẹn tại cơ sở.
- Xác nhận bệnh nhân đến và quản lý danh sách chờ.
- Xác nhận vắng mặt theo điều kiện nghiệp vụ được chốt.
- Theo dõi danh sách lịch bị ảnh hưởng do nghỉ đột xuất.
- Không được cập nhật tiến trình chuyên môn hoặc hoàn tất khám.
- Không được tự thay đổi vai trò tài khoản.
- Cách lễ tân xử lý hủy/đổi lịch do nghỉ đột xuất cần được
  đặc tả và chốt riêng trước khi cấp quyền.

### 4.3. Bác sĩ

- Chỉ truy cập danh sách bệnh nhân và lượt khám được phân công.
- Chỉ cập nhật tiến trình, hoàn tất lượt khám do mình phụ trách.
- Không được sửa lượt khám của bác sĩ khác.
- Không được tự công bố lịch hoặc thay đổi phân công.
- Không được quản lý tài khoản, vai trò hoặc danh mục hệ thống.

### 4.4. Quản trị viên

- Quản lý tài khoản và phân quyền.
- Quản lý chuyên khoa, triệu chứng, bác sĩ và phòng khám.
- Công bố lịch làm việc, khung giờ và ngày nghỉ.
- Xem báo cáo cơ bản và nhật ký thay đổi quan trọng.
- Quyền quản trị không mặc nhiên thay thế nghiệp vụ
  xác nhận đến của lễ tân hoặc hoàn tất khám của bác sĩ.
- Quyền quản lý tài khoản không bao gồm đọc mật khẩu người dùng.

## 5. Tình huống kiểm tra quyền

| Mã | Tình huống | Kết quả mong đợi |
|---|---|---|
| PQ01 | Bệnh nhân A xem lịch hẹn của A | Cho phép |
| PQ02 | Bệnh nhân A đổi mã lịch để xem lịch của B | Từ chối |
| PQ03 | Bệnh nhân sửa hồ sơ của người khác | Từ chối |
| PQ04 | Bệnh nhân hủy lịch sau khi đã tiếp nhận | Từ chối |
| PQ05 | Bác sĩ xem lượt khám được phân công | Cho phép |
| PQ06 | Bác sĩ cập nhật lượt khám của bác sĩ khác | Từ chối |
| PQ07 | Lễ tân xác nhận đến cho lịch đủ điều kiện | Cho phép |
| PQ08 | Lễ tân gửi yêu cầu hoàn tất khám | Từ chối |
| PQ09 | Bệnh nhân truy cập chức năng quản lý tài khoản | Từ chối |
| PQ10 | Quản trị viên quản lý chuyên khoa | Cho phép |
| PQ11 | Khách chưa đăng nhập truy cập dữ liệu cá nhân | Từ chối |
| PQ12 | Gọi trực tiếp chức năng máy chủ để vượt quyền | Từ chối |
| PQ13 | Bệnh nhân sửa bản khai triệu chứng của người khác | Từ chối |

Đây là tình huống kiểm thử dự kiến.
Kết quả chạy thực tế sẽ được cập nhật khi có chức năng.

## 6. Các điểm cần chốt trước khi lập trình

- Thời hạn cụ thể cho phép bệnh nhân hủy và đổi lịch.
- Điều kiện xác nhận bệnh nhân vắng mặt.
- Quyền xử lý lịch bị ảnh hưởng bởi ca nghỉ đột xuất.
- Các trường hồ sơ mỗi vai trò được sửa.
- Nhu cầu và phạm vi nhân viên được xem bản khai triệu chứng.

## 7. Phạm vi nghiệm thu SC02

SC02 nghiệm thu tài liệu đặc tả quyền.
Việc triển khai và kiểm thử quyền trên ứng dụng thuộc
các nhiệm vụ lập trình và kiểm thử sau này.