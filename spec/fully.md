Route: dashboard/[xxx]/create (và edit)

  Các TomSelect sau đang load toàn bộ data hệ thống, chưa lọc theo org:
  - #ts-[field1] — phụ thuộc org
  - #ts-[field2] — phụ thuộc org
  - #ts-[field3] — phụ thuộc org + branch (nếu có cascade 2 cấp)

  Áp dụng đúng pattern đã làm ở module Employee/Department/Branch:
  - orgLocked=true → PHP render, không đổi
  - orgLocked=false → select trống, load động qua API khi chọn org
  - Khi edit hoặc old() đã có giá trị → auto-fetch + restore selected value
  - Endpoint chưa có → tự kiểm tra route:list và tạo mới, trả về { id, text }
  - Sửa đủ: controller(s) + create.blade + edit.blade + [module]-form.js