# ĐẶC TẢ TRẠNG THÁI LỊCH HẸN (SC04)

## 1. Danh sách trạng thái (Status)
Một lịch hẹn trong Smart Clinic sẽ có 5 trạng thái chính:
1. **Pending (Chờ khám):** Lịch vừa được đặt thành công, bệnh nhân chưa đến phòng khám.
2. **Checked-in (Đã tiếp nhận):** Lễ tân xác nhận bệnh nhân đã có mặt tại phòng khám.
3. **In Progress (Đang khám):** Bác sĩ gọi bệnh nhân vào phòng và bắt đầu quá trình khám.
4. **Completed (Đã hoàn thành):** Bác sĩ kết thúc quá trình khám và ghi nhận kết quả.
5. **Cancelled (Đã hủy):** Lịch bị hủy hợp lệ bởi bệnh nhân, hoặc bị lễ tân đánh dấu vắng mặt.

*Lưu ý:* Cờ (flag) "Nghỉ đột xuất" hay "Đã đổi lịch" là thuộc tính đi kèm, không phải trạng thái chính của lịch hẹn.

## 2. Luồng chuyển trạng thái và Phân quyền
- **Mới đặt -> Pending:** Hệ thống tự động thiết lập khi bệnh nhân đặt lịch hợp lệ.
- **Pending -> Checked-in:** Lễ tân thực hiện.
- **Checked-in -> In Progress:** Bác sĩ được phân công thực hiện.
- **In Progress -> Completed:** Bác sĩ thực hiện.
- **Pending -> Cancelled:** Bệnh nhân tự thao tác (tuân thủ quy tắc hủy trước 2 giờ hoặc ngoại lệ ở SC03).
- **Pending -> Cancelled (Vắng mặt):** Lễ tân thực hiện (xem mục 4).

## 3. Quy tắc chặn sai thứ tự (Validation)
Hệ thống phải kiểm tra ở máy chủ và từ chối các thao tác chuyển trạng thái không hợp lệ sau:
- Không thể chuyển thẳng từ `Pending` sang `In Progress` hoặc `Completed` (bắt buộc qua bước lễ tân tiếp nhận).
- Không thể `Cancelled` (hủy) lịch khi đã ở trạng thái `Checked-in`, `In Progress` hoặc `Completed`.
- Không thể chuyển ngược từ `Completed` về `In Progress` hoặc `Checked-in`.
- Bác sĩ A không thể thao tác trên lịch hẹn của bệnh nhân đang được phân công cho Bác sĩ B.

## 4. Xử lý Vắng mặt (No Show)
- Lễ tân được quyền chuyển trạng thái từ `Pending` sang `Cancelled` (với lý do Vắng mặt) khi quá thời gian bắt đầu của khung giờ khám **15 phút** mà bệnh nhân chưa `Checked-in`.
- Ví dụ: Khung giờ khám là 08:00 - 08:30. Nếu đến 08:15 bệnh nhân chưa xác nhận tiếp nhận, lễ tân có thể đánh dấu vắng mặt để giải phóng lịch.