#!/bin/bash
# deploy.sh — thuchocvn.vn production deploy
# Chạy thủ công: bash deploy.sh
# Chạy migrate (chỉ khi quản trị chủ động muốn): bash deploy.sh --with-migrations
set -euo pipefail

APP_DIR="/var/www/minhan"
PHP="/usr/bin/php8.5"
BRANCH="main"

cd "$APP_DIR"

# ── 0. Kéo code mới + re-exec ─────────────────────────────────
# QUAN TRỌNG: script này tự git reset đè lên chính file đang chạy. Nếu không
# re-exec, bash tiếp tục đọc phần còn lại từ file descriptor ĐÃ MỞ TRƯỚC khi
# git reset ghi đè — tức là chạy logic của LẦN DEPLOY TRƯỚC, không phải code
# vừa pull về (đã từng gây ra: SKIP_MIGRATIONS default không áp dụng đúng lần,
# bước reload PHP-FPM không chạy dù đã commit). Re-exec đảm bảo toàn bộ phần
# sau bước này luôn đọc từ file mới nhất trên đĩa.
if [ -z "${DEPLOY_REEXEC:-}" ]; then
    echo "[$(date '+%H:%M:%S')] [0/7] Pulling latest code..."
    [ -f "$APP_DIR/.env" ] && cp "$APP_DIR/.env" /tmp/.env.deploy.bak
    git fetch origin
    git reset --hard "origin/$BRANCH"
    [ -f /tmp/.env.deploy.bak ] && mv /tmp/.env.deploy.bak "$APP_DIR/.env"
    echo "[$(date '+%H:%M:%S')] ✓ Code updated → $(git log --oneline -1)"

    export DEPLOY_REEXEC=1
    exec bash "$APP_DIR/deploy.sh" "$@"
fi

# ── Flags ──────────────────────────────────────────────────────
# Mặc định KHÔNG chạy migrate trong deploy tự động — DB schema được quản trị
# chủ động kiểm soát qua `php artisan migration:generate --fresh` (local/staging)
# rồi áp dụng tay lên production. Tự động migrate từng làm vỡ deploy nhiều lần
# do migrations table lệch so với schema thực tế trên production.
#
# Dùng flag dòng lệnh (--with-migrations) thay vì biến môi trường SKIP_MIGRATIONS
# — biến môi trường có thể bị rò rỉ/đè bởi shell profile còn sót lại trên VPS,
# flag tường minh thì không bao giờ tự nhiên xuất hiện.
SKIP_MIGRATIONS=true
for arg in "$@"; do
    case "$arg" in
        --with-migrations) SKIP_MIGRATIONS=false ;;
    esac
done

log() { echo "[$(date '+%H:%M:%S')] $*"; }
ok()  { echo "[$(date '+%H:%M:%S')] ✓ $*"; }
err() { echo "[$(date '+%H:%M:%S')] ✗ $*" >&2; }

log "═══════════════════════════════════════"
log "  Deploy thuchocvn.vn — $(date '+%Y-%m-%d %H:%M:%S')"
log "  Branch: $BRANCH | Commit: $(git log --oneline -1) | Skip migrations: $SKIP_MIGRATIONS"
log "═══════════════════════════════════════"

# ── 1. PHP dependencies ────────────────────────────────────────
log "[1/7] Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet
ok "Composer done"

# ── 2. Frontend build ──────────────────────────────────────────
# KHÔNG tự build frontend ở đây. `npm run build` dùng vite.config.js mặc định
# (output public/build/), trong khi TOÀN BỘ view trong app dùng group
# 'build/backend' từ vite.config.backend.js (public/build/backend/). Vite mặc
# định emptyOutDir=true nên `npm run build` từng XOÁ SẠCH public/build/ — kéo
# theo public/build/backend/manifest.json biến mất dù không đổi JS/CSS nào.
# Quản trị tự build khi cần: npx vite build --config vite.config.backend.js
log "[2/7] Skipping frontend build (quản trị tự chạy: npx vite build --config vite.config.backend.js)"

# ── 3. Maintenance mode ────────────────────────────────────────
log "[3/7] Enabling maintenance mode..."
# Fix storage permissions trước khi artisan chạy
sudo /usr/local/bin/fix-minhan-build 2>/dev/null || true
# Xóa config cache cũ — bắt buộc trước migrate để artisan đọc đúng .env
$PHP artisan config:clear
$PHP artisan down --retry=10
trap '$PHP artisan up; err "Deploy failed — maintenance mode disabled"' ERR

# ── 4. Migration ────────────────────────────────────────────────
if [ "$SKIP_MIGRATIONS" = "true" ]; then
    log "[4/7] Skipping migrations (mặc định — dùng --with-migrations để bật)"
else
    log "[4/7] Running database migrations..."
    $PHP artisan migrate --force
    ok "Migrations done"
fi

# ── 5. Rebuild cache ────────────────────────────────────────────
log "[5/7] Rebuilding application cache..."
$PHP artisan config:clear  && $PHP artisan config:cache
$PHP artisan route:clear   && $PHP artisan route:cache
$PHP artisan view:clear    && $PHP artisan view:cache
$PHP artisan event:clear   && $PHP artisan event:cache
ok "Cache rebuilt"

# ── 6. Reload PHP-FPM (xóa opcache) ──────────────────────────────
log "[6/7] Reloading PHP-FPM..."
# Opcache giữ bytecode compiled view/class cũ trong RAM của các worker PHP-FPM
# đang chạy — view:cache ghi file mới trên đĩa nhưng FPM không tự đọc lại nếu
# opcache.validate_timestamps=0. Reload PHP-FPM để worker mới load code mới.
if sudo systemctl reload php8.5-fpm 2>/dev/null; then
    ok "PHP-FPM reloaded (opcache cleared)"
else
    err "Không reload được PHP-FPM — opcache có thể vẫn giữ code cũ! Cần cấu hình sudoers cho lệnh: systemctl reload php8.5-fpm"
fi

$PHP artisan up
trap - ERR   # bỏ trap sau khi up thành công

# ── 7. Khởi động lại workers ──────────────────────────────────────
log "[7/7] Restarting workers..."
sudo supervisorctl restart minhan-horizon  > /dev/null
sudo supervisorctl restart minhan-reverb   > /dev/null
ok "Horizon + Reverb restarted"

log ""
log "✅ Deploy hoàn tất — $(date '+%Y-%m-%d %H:%M:%S')"
log "   Commit: $(git log --oneline -1)"
