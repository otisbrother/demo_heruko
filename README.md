# 🏥 HỆ THỐNG QUẢN LÝ BỆNH VIỆN

## 📋 TỔNG QUAN HỆ THỐNG

Hệ thống quản lý bệnh viện được xây dựng trên nền tảng Laravel với 3 vai trò chính: **Admin**, **Bác sĩ** và **Bệnh nhân**. Mỗi vai trò có các chức năng và quyền hạn riêng biệt.

## 👥 CÁC VAI TRÒ VÀ QUYỀN HẠN

### 🔧 ADMIN (Quản trị viên)
**Quyền hạn:**
- Quản lý toàn bộ hệ thống
- Thống kê tổng quan
- Duyệt hồ sơ BHYT
- Quản lý bệnh nhân, bác sĩ, khoa
- Quản lý thuốc, đơn thuốc
- Quản lý nhập/xuất viện
- Xử lý hóa đơn

### 👨‍⚕️ BÁC SĨ
**Quyền hạn:**
- Khám bệnh và ghi hồ sơ y tế
- Kê đơn thuốc
- Quản lý lịch khám
- Quản lý nhập/xuất viện
- Xem thống kê cá nhân

### 👤 BỆNH NHÂN
**Quyền hạn:**
- Đặt lịch hẹn khám
- Xem hồ sơ y tế cá nhân
- Xem đơn thuốc
- Thanh toán hóa đơn
- Đăng ký BHYT
- Quản lý thông tin cá nhân

---



## 🔐 BẢO MẬT VÀ PHÂN QUYỀN

### **Hệ Thống Xác Thực**
- **Admin**: Sử dụng Laravel Breeze với role 'admin'
- **Bác sĩ**: Sử dụng guard 'doctor' riêng biệt
- **Bệnh nhân**: Sử dụng session-based authentication

### **Phân Quyền Truy Cập**
- Mỗi vai trò chỉ có thể truy cập các chức năng được phân quyền
- Middleware kiểm tra quyền truy cập trước khi cho phép thực hiện chức năng
- Session timeout cho bệnh nhân (10 phút)

---

## 📱 GIAO DIỆN NGƯỜI DÙNG

### **Responsive Design**
- Giao diện thân thiện với mobile
- Sử dụng Tailwind CSS
- Components tái sử dụng

### **Thông Báo Real-time**
- Thông báo lịch hẹn mới cho Admin
- Thông báo duyệt BHYT cho bệnh nhân
- Cập nhật trạng thái real-time

---

## 🛠️ CÔNG NGHỆ SỬ DỤNG

- **Backend**: Laravel 10.x
- **Frontend**: Blade Templates + Tailwind CSS
- **Database**: MySQL
- **Authentication**: Laravel Breeze + Custom Guards
- **File Upload**: Laravel Storage
- **Real-time**: AJAX + JavaScript

---

## 📈 TÍNH NĂNG NỔI BẬT

1. **Quản lý đa vai trò** với phân quyền chi tiết
2. **Hệ thống đặt lịch hẹn** thông minh
3. **Quản lý BHYT** với workflow duyệt
4. **Hệ thống thanh toán** tích hợp
5. **Thống kê và báo cáo** chi tiết
6. **Quản lý nhập/xuất viện** toàn diện
7. **Giao diện responsive** thân thiện
8. **Thông báo real-time** cho người dùng

---

## 🎯 MỤC TIÊU HỆ THỐNG

- **Tối ưu hóa quy trình** khám chữa bệnh
- **Giảm thiểu thời gian chờ** của bệnh nhân
- **Tăng hiệu quả quản lý** cho bệnh viện
- **Cải thiện trải nghiệm** người dùng
- **Đảm bảo tính bảo mật** và an toàn dữ liệu
