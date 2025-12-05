Mẫu 1
@startuml
' Cấu hình giao diện
skinparam style strictuml
skinparam sequenceMessageAlign center

' --- KHAI BÁO ĐỐI TƯỢNG VỚI ICON CHUẨN ---
actor "Quản Trị Viên" as Admin

' Dùng từ khóa boundary để ra hình tròn có chữ T
boundary "Giao Diện\n(View)" as View

' Dùng từ khóa control để ra hình tròn có mũi tên
control "Hệ Thống\n(Controller)" as Ctrl

' Dùng từ khóa entity để ra hình tròn có gạch chân (Đại diện CSDL)
entity "CSDL\n(Entity)" as DB

' --- BẮT ĐẦU LUỒNG ---
Admin -> View: 1. Bấm icon "Sửa"
activate View

View -> Ctrl: 2. Lấy thông tin cũ
activate Ctrl

Ctrl -> DB: 3. SELECT * FROM baiviet...
activate DB

DB --> Ctrl: 4. Trả về dữ liệu
deactivate DB

Ctrl --> View: 5. Hiển thị form
deactivate Ctrl
deactivate View

' --- GIAI ĐOẠN 2: CẬP NHẬT ---
Admin -> View: 6. Sửa thông tin & Bấm "Cập nhật"
activate View

View -> Ctrl: 7. Gửi dữ liệu Update
activate Ctrl

' === KHUNG ALT RỘNG ===
alt Có thay đổi hình ảnh?

' Mẹo kéo dài khung
Admin -[hidden]> DB

' Cục xử lý nội bộ (Self-call)
Ctrl -> Ctrl: 8. Upload ảnh mới & Xóa cũ
activate Ctrl #DarkGray
deactivate Ctrl

else Không thay đổi

Admin -[hidden]> DB

' Cục xử lý nội bộ
Ctrl -> Ctrl: 8. Giữ nguyên ảnh cũ
activate Ctrl #DarkGray
deactivate Ctrl

end
' ======================

Ctrl -> DB: 9. UPDATE baiviet SET ...
activate DB
DB --> Ctrl: 10. Trả về kết quả (True)
deactivate DB

Ctrl --> View: 11. Thông báo thành công
deactivate Ctrl

View --> Admin: 12. Reload trang
deactivate View

@enduml


Mẫu 2:
@startuml
' Cấu hình giao diện chuẩn Style khóa luận
skinparam style strictuml
skinparam sequenceMessageAlign center

' --- KHAI BÁO CÁC ĐỐI TƯỢNG ---
actor "Nhân viên\nQuản trị" as Admin

' 1. View Trang chủ (Chỉ dùng để bấm menu)
boundary "Trang Chủ\n" as ViewHome

' 2. View Danh sách (Nơi hiển thị và tương tác chính)
boundary "Trang Danh Sách Nhân Sự\n" as ViewList

' 3. View Chi tiết (Đích đến)
boundary "Trang Chi Tiết\n" as ViewDetail

control "Hệ Thống\n(Ctrl_nhansu)" as Ctrl
entity "CSDL\n(nhansu)" as DB

' --- BẮT ĐẦU TỪ TRANG CHỦ ---
activate ViewHome
Admin -> ViewHome: Chọn menu "Quản lý nhân sự" -> "Danh sách nhân sự"

ViewHome -> Ctrl: Gửi yêu cầu chuyển trang (Lấy danh sách)
deactivate ViewHome
' Trang chủ xong nhiệm vụ

' --- HỆ THỐNG XỬ LÝ ---
activate Ctrl
Ctrl -> DB: SELECT * FROM NhanSu WHERE trangthai = 1
activate DB

DB --> Ctrl: Trả về kết quả truy vấn
deactivate DB

' --- HIỂN THỊ TẠI TRANG DANH SÁCH ---
alt Danh sách RỖNG
Ctrl -->> ViewList: Render View Danh Sách (Thông báo: Không có dữ liệu)
activate ViewList
ViewList --> Admin: Hiển thị bảng rỗng
else Danh sách CÓ dữ liệu
Ctrl -->> ViewList: Render View Danh Sách (Đổ dữ liệu vào bảng)
' ViewList đã được active

ViewList --> Admin: Hiển thị danh sách nhân sự
deactivate Ctrl

' --- TƯƠNG TÁC TẠI TRANG DANH SÁCH ---
Admin -> ViewList: Nhấn nút "Xem" tại dòng nhân sự
activate Ctrl

ViewList -> Ctrl: Gửi yêu cầu xem chi tiết (kèm tham số mans)
deactivate ViewList
' ViewList tạm ẩn

' Validate nội bộ
Ctrl -> Ctrl: Validate tham số 'mans' (Số, không rỗng)
activate Ctrl #DarkGray
deactivate Ctrl

alt Mã không hợp lệ (Sai định dạng)
' Quay về danh sách báo lỗi
Ctrl -->> ViewList: Redirect về View Danh Sách (Kèm thông báo lỗi Format)
activate ViewList
ViewList --> Admin: Hiển thị thông báo lỗi
deactivate ViewList
else Mã hợp lệ
' Truy vấn dữ liệu chi tiết
Ctrl -> DB: SELECT * FROM NhanSu WHERE mans = [giá trị]
activate DB
DB --> Ctrl: Trả về dữ liệu chi tiết
deactivate DB

alt Không tìm thấy bản ghi (Null)
' Quay về danh sách báo lỗi
Ctrl -->> ViewList: Redirect về View Danh Sách (Kèm thông báo lỗi Null)
activate ViewList
ViewList --> Admin: Hiển thị thông báo "Không tìm thấy"
deactivate ViewList
else Tìm thấy dữ liệu
' Chuyển sang trang chi tiết
Ctrl -->> ViewDetail: Render View Chi Tiết (Thông tin đầy đủ)
activate ViewDetail
ViewDetail --> Admin: Xem thông tin chi tiết
deactivate ViewDetail
end
end
end
deactivate Ctrl

@enduml

Mẫu activity
@startuml
' Cấu hình giao diện "Slim" - Nhỏ gọn tối đa
skinparam activity {
BackgroundColor White
BorderColor #333333
ArrowColor #333333
FontName Arial
FontSize 11
}

' BÍ KÍP THU NHỎ (như cũ)
skinparam maxMessageSize 100
skinparam padding 2
skinparam nodesep 10
skinparam ranksep 10

|Admin|
start
:Nhấn nút "Sửa"\ntại dòng nhân sự;

|Hệ thống|
:Nhận tham số 'mans'\ntừ URL & kiểm tra;

' Xử lý luồng thay thế 1.a/2.a (Sai ID)
if (ID tồn tại & hợp lệ?) then (Không)
:Báo lỗi "Không tìm thấy"\nvà chuyển về danh sách;
else (Có)
:Truy vấn CSDL lấy\nthông tin hiện tại;
:Hiển thị Form sửa\n(đã điền sẵn dữ liệu);

' Vòng lặp sửa chữa và validate (Luồng 3.a, 3.b)
repeat
|Admin|
:Sửa thông tin,\nchọn ảnh mới (nếu có)\nvà nhấn "Lưu";

|Hệ thống|
:Ảnh < 2MB hoặc đúng định dạng; ' Nếu lỗi thì quay lại bước nhập
  repeat while (Hợp lệ?) is (Không)
  -> Có;
  
  :Cập nhật thông tin\nmới vào CSDL;
  :Thông báo "Cập nhật\nnhân sự thành công!";
endif

' Kết thúc tại người dùng
    |Admin|
    :Nhận kết quả\n& Xem lại danh sách;

    stop

    @enduml