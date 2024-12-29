# Hướng Dẫn Cài Đặt Dự Án

## Yêu cầu hệ thống

- PHP phiên bản >= 7.4
- Laravel >= 8.0
- Composer
- FFmpeg
- MySQL hoặc hệ quản trị cơ sở dữ liệu tương thích
- Máy chủ web như Apache hoặc Nginx

---

## Các bước cài đặt

### 1. Cấu hình file `.env`

- Mở file `.env` trong thư mục gốc của dự án.
- Chỉnh sửa các thông tin cần thiết:
  - `APP_URL`: Đường dẫn URL của ứng dụng.
  - `DB_DATABASE`: Tên cơ sở dữ liệu.
  - `DB_USERNAME`: Tên đăng nhập cơ sở dữ liệu.
  - `DB_PASSWORD`: Mật khẩu cơ sở dữ liệu.

### 2. Chạy lệnh migrate

- Mở terminal và điều hướng tới thư mục gốc của dự án.
- Thực hiện lệnh sau để khởi tạo bảng cơ sở dữ liệu:
  ```bash
  php artisan migrate
  ```

### 3. Chạy lệnh install

- Chạy lệnh install để cài đặt vendor:

```bash
  composer install
```

### 4. Cài đặt tài khoản admin

- Chạy lệnh sau để cài đặt tài khoản admin:

```bash
  php artisan admin:install
```

* Nếu thấy thông báo:

`Database seeding completed successfully.`

là quá trình cài đặt thành công. Tài khoản mặc định:

* Tên đăng nhập: `admin`
* Mật khẩu: `admin`
