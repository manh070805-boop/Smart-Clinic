\# SC03 — Quy định đặt, hủy và đổi lịch khám



\## 1. Mục đích và phạm vi



Quy định nghiệp vụ lịch hẹn tại một cơ sở khám bệnh,

làm đầu vào cho thiết kế dữ liệu, lập trình và kiểm thử.



Tài liệu tham chiếu:

\- README.md.

\- docs/permissions.md.



\## 2. Thuật ngữ



\- Khung giờ: khoảng thời gian khám thuộc lịch làm việc

&#x20; của một bác sĩ tại một phòng khám.

\- Lịch có hiệu lực chiếm chỗ: lịch đã đặt hợp lệ, chưa hủy,

&#x20; chưa kết thúc hoặc được xử lý theo quy định giải phóng chỗ.

\- Giờ bắt đầu khám: thời điểm bắt đầu của khung giờ đã chọn.



Tên trạng thái và các bước chuyển chi tiết được đặc tả ở SC04.



\## 3. Điều kiện đặt lịch



\- Bệnh nhân phải đăng nhập và đặt lịch cho bản thân.

\- Có đủ thông tin bắt buộc theo đặc tả hồ sơ.

\- Khung giờ đã được công bố và chưa bắt đầu.

\- Bác sĩ và phòng khám đang được sử dụng.

\- Khung giờ không thuộc ngày nghỉ hoặc ca ngừng hoạt động.

\- Khung giờ chưa có lịch hẹn có hiệu lực chiếm chỗ.

\- Không cho bệnh nhân đặt các lịch có thời gian khám chồng nhau.

&#x20; Đây là phương án đề xuất cần chốt cho bản demo.

\- Bệnh nhân có thể chọn chuyên khoa trực tiếp hoặc sử dụng

&#x20; chức năng hỗ trợ định hướng.

\- Không bắt buộc sử dụng AI để đặt lịch.



\## 4. Sức chứa và xác nhận đặt lịch



\- Một khung giờ nhận tối đa một lịch hẹn có hiệu lực chiếm chỗ.

\- Khi xác nhận, hệ thống kiểm tra lại điều kiện và chỗ trống

&#x20; tại phía máy chủ.

\- Hiển thị lịch trống trên giao diện không bảo đảm chỗ vẫn còn

&#x20; khi bệnh nhân xác nhận.

\- Đặt thành công phải tạo mã hẹn duy nhất và phiếu hẹn.

\- Phiếu thể hiện mã hẹn, bệnh nhân, bác sĩ, chuyên khoa,

&#x20; phòng khám, ngày và khung giờ.

\- Đặt thất bại phải thông báo rõ và không tạo lịch hẹn dở dang.



\## 5. Xác nhận lặp và đặt đồng thời



\### 5.1. Xác nhận lặp

\- Cùng một yêu cầu xác nhận được gửi lại không tạo thêm lịch.

\- Hệ thống trả lại kết quả của yêu cầu đã xử lý hoặc thông báo

&#x20; yêu cầu đang được xử lý.



\### 5.2. Đặt đồng thời

\- Hai bệnh nhân cùng đặt một khung giờ:

&#x20; tối đa một yêu cầu được xác nhận thành công.

\- Yêu cầu còn lại được thông báo khung giờ không còn trống.

\- Không chỉ dựa vào kiểm tra trên giao diện.

\- Tính nhất quán phải được bảo đảm tại phía máy chủ và MySQL.



\## 6. Hủy lịch



\### 6.1. Điều kiện

\- Bệnh nhân chỉ được hủy lịch của mình.

\- Lịch chưa được tiếp nhận, chưa khám và chưa kết thúc.

\- Lịch chưa bị hủy.

\- Còn đủ thời gian trước giờ bắt đầu khám.



\### 6.2. Thời hạn đề xuất

\- Bệnh nhân được hủy khi còn ít nhất 2 giờ trước giờ khám.

\- Đúng mốc 2 giờ: cho phép.

\- Còn dưới 2 giờ: từ chối hủy trực tuyến.



\### 6.3. Kết quả

\- Hủy hợp lệ giải phóng khung giờ.

\- Giữ bản ghi lịch hẹn và lịch sử hủy, không xóa cứng.

\- Ghi người thao tác, thời điểm và lý do nếu có.

\- Hủy không hợp lệ phải giữ nguyên lịch.



\## 7. Đổi lịch



\### 7.1. Điều kiện

\- Bệnh nhân chỉ được đổi lịch của mình.

\- Lịch cũ chưa tiếp nhận, chưa khám, chưa kết thúc hoặc hủy.

\- Còn đủ thời gian theo thời hạn đổi.

\- Khung giờ mới đáp ứng đầy đủ điều kiện đặt lịch.

\- Không chọn lại chính khung giờ hiện tại.



\### 7.2. Thời hạn và phạm vi đề xuất

\- Được đổi khi còn ít nhất 2 giờ trước giờ khám cũ.

\- Đúng mốc 2 giờ: cho phép.

\- Còn dưới 2 giờ: từ chối đổi trực tuyến.

\- Trong bản demo, chỉ đổi sang lịch cùng chuyên khoa;

&#x20; có thể đổi bác sĩ nếu bác sĩ mới thuộc chuyên khoa đó.



Các phương án trên cần xác nhận trước khi chốt.



\### 7.3. Tính nhất quán

\- Đổi lịch được xử lý trong một giao dịch.

\- Chỉ giải phóng lịch cũ khi đã bảo đảm nhận được lịch mới.

\- Nếu lịch mới hết chỗ hoặc phát sinh lỗi, lịch cũ vẫn giữ nguyên.

\- Ghi lịch sử để truy vết lịch trước và sau khi đổi.

\- Sau khi đổi thành công, phiếu hẹn phải phản ánh lịch hiện hành.



\## 8. Ngày nghỉ và nghỉ đột xuất



\- Quản trị viên quản lý, công bố thay đổi lịch làm việc.

\- Khung giờ ngừng hoạt động không nhận lịch mới.

\- Những lịch hẹn bị ảnh hưởng phải được đánh dấu

&#x20; và đưa vào danh sách lễ tân cần xử lý.

\- Không tự xóa lịch hẹn hoặc âm thầm đổi sang bác sĩ khác.

\- Lễ tân tra cứu danh sách và phối hợp liên hệ bệnh nhân.

\- Quyền hủy/đổi thay bệnh nhân chưa được cấp trong SC02.

&#x20; Nếu chọn cách xử lý này phải bổ sung và thống nhất quyền

&#x20; trước khi triển khai.



\## 9. Thời gian và nhật ký



\- Thời gian nghiệp vụ sử dụng múi giờ Asia/Ho\_Chi\_Minh.

\- Điều kiện thời hạn được tính theo thời gian máy chủ.

\- Đặt, hủy, đổi và xử lý nghỉ đột xuất phải lưu lịch sử phù hợp.

\- Chỉ cho chuyển trạng thái đúng quyền và đúng trình tự.

\- Đổi lịch hoặc hủy thất bại không làm mất lịch đang có hiệu lực.



\## 10. Tình huống kiểm tra dự kiến



| Mã | Tình huống | Kết quả mong đợi |

|---|---|---|

| LH01 | Bệnh nhân đăng nhập, đặt khung giờ hợp lệ | Thành công, có mã và phiếu hẹn |

| LH02 | Khách chưa đăng nhập xác nhận đặt | Từ chối |

| LH03 | Đặt khung giờ đã bắt đầu | Từ chối |

| LH04 | Đặt khung giờ thuộc ca nghỉ | Từ chối |

| LH05 | Hai người cùng đặt một chỗ | Chỉ một người thành công |

| LH06 | Gửi lại cùng yêu cầu đặt | Không tạo thêm lịch |

| LH07 | Hủy lịch đúng mốc 2 giờ | Cho phép theo phương án đề xuất |

| LH08 | Hủy lịch còn dưới 2 giờ | Từ chối theo phương án đề xuất |

| LH09 | Hủy lịch đã tiếp nhận | Từ chối |

| LH10 | Hủy lịch của người khác | Từ chối |

| LH11 | Hủy hợp lệ | Giải phóng chỗ, giữ lịch sử |

| LH12 | Đổi sang khung giờ hợp lệ | Thành công, cập nhật phiếu |

| LH13 | Khung giờ mới vừa hết chỗ khi đổi | Đổi thất bại, giữ lịch cũ |

| LH14 | Lỗi giữa quá trình đổi | Hoàn tác, giữ lịch cũ |

| LH15 | Bệnh nhân đặt hai lịch chồng thời gian | Từ chối theo phương án đề xuất |

| LH16 | Ca nghỉ đột xuất có lịch đã đặt | Đánh dấu, chuyển danh sách lễ tân xử lý |



Đây là tình huống dự kiến, chưa phải kết quả chạy ứng dụng.



\## 11. Các quyết định cần chốt



\- Thời hạn hủy: đề xuất ít nhất 2 giờ.

\- Thời hạn đổi: đề xuất ít nhất 2 giờ trước lịch cũ.

\- Giới hạn đổi: đề xuất trong cùng chuyên khoa.

\- Chặn các lịch chồng thời gian của cùng bệnh nhân.

\- Cách xử lý và quyền thao tác với lịch bị nghỉ đột xuất.

\- Các trường hồ sơ bắt buộc trước khi đặt.



