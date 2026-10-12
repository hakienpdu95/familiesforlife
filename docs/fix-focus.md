Sửa lỗi viền focus bị "nháy" quanh các khối div/card khi thao tác filter và các tương tác khác (TomSelect, nút xoá ×, gõ tìm kiếm, Tabulator reload…), kèm chuẩn hoá biến màu DaisyUI 4 → 5. Stack:
  Tailwind 4 + DaisyUI 5 + TomSelect v2 + Tabulator.

  ## Nguyên nhân gốc (đã xác định ở project base)
  1. Các component field của DaisyUI 5 (.input, .select, .textarea, .file-input) có rule
     `&:focus, &:focus-within { outline: 2px solid var(--input-color); outline-offset: 2px; }`.
     Các class này thường gắn trên <div>/<label> bọc ngoài <input>. Khi focus di chuyển bên trong
     (bấm nút ×, chip biến mất, x-show ẩn phần tử đang focus, setValue…), :focus-within bật/tắt
     liên tục, nên quầng outline lệch 2px chớp quanh cả khối, trông như card đang nháy.
  2. TomSelect chép nguyên class của <select> (vd "select select-sm select-bordered w-full")
     lên .ts-wrapper, nên wrapper bị DaisyUI coi là .select và cũng ăn outline/box-shadow/isolation
     khi :focus-within.
  3. Có thể project đang có hack tắt toàn cục:
     `*:focus, *:focus-visible, *:focus-within { outline: none !important; outline-offset: 0 !important; }`
     Hack này che được triệu chứng nhưng tắt luôn viền focus bàn phím (hỏng a11y). Cần bỏ.

  ## Việc cần làm
  ### A. CSS chính của backend (vd resources/css/app.css, ở phần CSS KHÔNG nằm trong @layer, để thắng các layer của DaisyUI)
  1. Tìm và XOÁ rule tắt outline toàn cục kiểu `*:focus, *:focus-visible, *:focus-within { outline: none !important; ... }`.
  2. Thêm:
  ```css
  :focus:not(:focus-visible) {
      outline: none;
  }
  :is(.input, .select, .textarea, .file-input):is(:focus, :focus-within) {
      outline: none;
  }
  .ts-wrapper.select:focus,
  .ts-wrapper.select:focus-within {
      outline: none;
      isolation: auto;
      box-shadow: none;
  }
  ```
  3. Giữ nguyên các rule khác liên quan tới focus đã có (vd reset --input-color khi focus) nếu có.
  4. Nếu project có CSS frontend riêng dùng DaisyUI + TomSelect thì áp dụng tương tự.

  ### B. Chuyển biến màu DaisyUI 4 sang DaisyUI 5
  DaisyUI 5 không còn các biến --p, --pc, --b1, --b2, --b3, --bc, --s, --a, --n, --in, --su, --wa, --er…
  nên mọi `oklch(var(--x))` đều ra giá trị không hợp lệ và màu không ăn.
  Grep toàn bộ resources/ và Modules/ (*.php, *.blade.php, *.css, *.scss, *.js):
    grep -rnE "oklch\(var\(--|hsl\(var\(--" resources Modules
  Rồi thay theo bảng:
    --p → --color-primary        --pc → --color-primary-content
    --s → --color-secondary      --sc → --color-secondary-content
    --a → --color-accent         --ac → --color-accent-content
    --n → --color-neutral        --nc → --color-neutral-content
    --b1 → --color-base-100      --b2 → --color-base-200
    --b3 → --color-base-300      --bc → --color-base-content
    --in → --color-info          --su → --color-success
    --wa → --color-warning       --er → --color-error
  Quy tắc:
    oklch(var(--x))        → var(--color-…)
    oklch(var(--x) / .2)   → color-mix(in oklab, var(--color-…) 20%, transparent)
  Đặc biệt kiểm tra component theme TomSelect/Tabulator (vd resources/views/components/tabulator-theme.blade.php),
  các file scss của module, mixin dùng chung, và chuỗi style inline sinh ra trong JS.
  Sau khi thay, grep lại phải ra 0 kết quả.

  ## Ràng buộc
  - Không dùng Livewire. Không thêm code comment.
  - KHÔNG chạy `npm run build` (vite.config.js mặc định xoá cả public/build). Chỉ chạy
    `npm run build:backend` và `npm run build:frontend`.
  - Không chạy phpunit nếu phpunit.xml đang trỏ vào DB dùng chung.

  ## Kiểm chứng
  1. Cả 2 lệnh build chạy thành công.
  2. Trong CSS đã build (public/build/*/assets/*.css):
     - không còn `oklch(var(--`
     - có rule `:focus:not(:focus-visible)` và `:is(.input,.select,.textarea,.file-input):is(:focus,:focus-within){outline:none}`
     - không còn rule `:focus,:focus-visible,:focus-within{outline:none!important…}`
  3. Báo lại danh sách file đã sửa. Nhắc tôi kiểm tra trên trình duyệt ở một trang danh sách
     có filter TomSelect + Tabulator (vd dashboard/posts/articles): click chọn filter, xoá chip,
     gõ ô tìm kiếm thì không còn viền nháy; bấm Tab thì vẫn thấy viền focus.
  4. Nếu còn nháy ở chỗ nào, đưa tôi đoạn debug để chạy trong Console:
     document.addEventListener('focusin', e => console.log(e.target, getComputedStyle(e.target).outline));

  Một lưu ý: phần B dùng bảng ánh xạ đủ các biến của DaisyUI 4, kể cả các biến base này không dùng (--n, --sc…). Như vậy project nhân bản nào có dùng các biến đó cũng được sửa luôn.