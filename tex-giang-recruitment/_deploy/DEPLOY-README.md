# HƯỚNG DẪN DEPLOY — Tính năng Khối tuyển dụng

Ngày đóng gói: 2026-11-20
Dành cho: production `tuyendun6ae4` (14 tin, 11 bộ phận)

Cấu trúc đích: **Nhà máy → Khối → Bộ phận tuyển dụng → Việc làm**
Khối cố định 2 giá trị: `office` = Văn phòng, `enterprise` = Xí nghiệp

---

## THỨ TỰ BẮT BUỘC

```
1. SAO LƯU DATABASE
2. Import migration SQL        ← PHẢI TRƯỚC
3. Upload code                 ← SAU
4. Xoá app/Models/Department.php
5. Kiểm tra
```

**Vì sao phải đúng thứ tự:** code mới gọi `SELECT ... c.block`. Nếu upload code
trước khi có cột, 2 chỗ sẽ lỗi 500:

| Chỗ | Hàm gọi | Triệu chứng |
|---|---|---|
| `/admin/jobs` | `Job::countBlockMismatch()` | 500 |
| Form thêm/sửa tin | `Category::withJobCounts()` | 500 |

Lưu ý: website ngoài (`/`, `/jobs`) **vẫn chạy bình thường** vì không đụng cột
`block` — nên triệu chứng sẽ là "web ổn mà admin lỗi", dễ chẩn đoán nhầm.

---

## BƯỚC 2 — Import migration

File: `database/migrations/2026-11-19-khoi-tuyen-dung-PRODUCTION.sql`

**Cách làm (phpMyAdmin):**
1. Chọn **đúng database** ở cột trái (bắt buộc — câu kiểm tra dùng `DATABASE()`)
2. Tab **Import** → Choose File → chọn file .sql → **Go**

**Hoặc bằng dòng lệnh:**
```bash
mysql -u USER -p TEN_DATABASE < 2026-11-19-khoi-tuyen-dung-PRODUCTION.sql
```

**Kết quả in ra — kiểm tra 3 bảng:**

| Bảng | Kỳ vọng |
|---|---|
| [1] Tin theo Khối | `enterprise = 13`, `office = 1` |
| [2] Bộ phận theo Khối | `enterprise = 9`, `office = 2` |
| [3] Tin lệch Khối | **0 dòng** |

Nếu bảng [3] có dòng → chạy lại file một lần nữa (idempotent, an toàn).

File này **không DROP gì**, chỉ `ADD COLUMN` + `UPDATE`.

---

## BƯỚC 3 — Upload code

Copy **toàn bộ nội dung** thư mục `_deploy/` vào thư mục gốc dự án trên hosting,
**giữ nguyên cấu trúc thư mục** (đè file cũ).

```
_deploy/app/...      →  <gốc dự án>/app/...
_deploy/public/...   →  <gốc dự án>/public/...
```

23 file, gồm **5 file MỚI** (trước đó chưa có trên hosting):

| File mới | Vai trò |
|---|---|
| `app/Views/partials/public/jobs-fragment.php` | fragment AJAX cho bộ lọc |
| `app/Views/partials/public/jobs-results.php` | vùng kết quả |
| `app/Views/partials/public/jobs-results-data.php` | tính chips/URL dùng chung |
| `app/Views/partials/public/jobs-filter-fields.php` | các ô lọc |
| `public/assets/js/admin-job-block.js` | phụ thuộc Khối → Bộ phận |

> **Thiếu 4 file `jobs-*.php` là `/jobs` sập ngay** — chúng là file mới, không
> thể đè lên file cũ vì trước đó không tồn tại.

---

## BƯỚC 4 — Xoá file không còn dùng

```
app/Models/Department.php    ← XOÁ
```

File này thuộc cấu trúc 4 cấp cũ (đã bỏ). Không còn ai gọi, nhưng để lại gây nhầm.

---

## BƯỚC 5 — Kiểm tra

**Ngoài website** — mở `/jobs`:
- Bộ lọc nằm **ngang** một hàng: Từ khoá · Địa điểm · Khối · Bộ phận tuyển dụng · nút Áp dụng
- **Không còn** ô "Mức lương"
- Ô **Bộ phận tuyển dụng** chỉ hiện **7 bộ phận có tin**, **không kèm số lượng**:
  Sản xuất may mặc, Xưởng in, Phòng cắt, May mẫu, Kỹ thuật may, Bảo vệ, Kỹ thuật
- Số vị trí hiển thị: **14**
- Chọn Khối → danh sách bộ phận lọc theo; chọn lại "Tất cả" → bỏ được lọc
- Lọc/sắp xếp/phân trang **không nhảy về đầu trang**

**Trong admin** — `/admin/jobs`:
- Có cột **Khối** trên bảng tin
- **Không** có banner đỏ cảnh báo lệch Khối
- Form thêm/sửa tin có dropdown **Khối**, và Bộ phận lọc theo Khối
- `/admin/categories?type=category` có cột Khối và ô chọn Khối khi sửa bộ phận

---

## NẾU CÓ LỖI

| Lỗi | Nguyên nhân | Xử lý |
|---|---|---|
| `/admin/jobs` 500, log ghi `Unknown column 'c.block'` | Chưa chạy migration, hoặc chạy nhầm database | Chạy lại bước 2 |
| `/jobs` 500, log ghi thiếu view `jobs-results` | Thiếu 4 file `jobs-*.php` mới | Upload lại bước 3 |
| Import báo `Duplicate column name 'block'` | Cột đã tồn tại (đã migrate rồi) | Bỏ qua, không sao |
| Bộ phận vẫn hiện số lượng `(7)` | Cache trình duyệt | Ctrl + F5 |
| Bộ phận hiện cả bộ phận 0 tin | Chưa upload `app/Models/Category.php` | Upload lại bước 3 |

---

## SAU KHI DEPLOY XONG

Có thể xoá thư mục `_deploy/` khỏi máy local và khỏi hosting (nếu có upload).

Migration `2026-11-19-khoi-tuyen-dung.sql` (bản **local**, không có hậu tố
`-PRODUCTION`) **KHÔNG dùng cho production** — bản đó để gỡ cấu trúc
`departments`/`enterprises` mà production chưa từng có.
