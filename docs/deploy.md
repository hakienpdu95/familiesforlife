# Hướng dẫn triển khai — thuchocvn.vn

> **Stack**: Laravel 13 · PHP 8.5 · MySQL 8 · Redis · Nginx · Supervisor · GitHub Actions

---

## Mục lục

1. [Kiến trúc hệ thống](#1-kiến-trúc-hệ-thống)
2. [Chuẩn bị server](#2-chuẩn-bị-server)
3. [Cài đặt stack](#3-cài-đặt-stack)
4. [Cấu hình Nginx + SSL](#4-cấu-hình-nginx--ssl)
5. [Deploy dự án lần đầu](#5-deploy-dự-án-lần-đầu)
6. [Dịch vụ nền — Supervisor + Cron](#6-dịch-vụ-nền--supervisor--cron)
7. [GitHub Actions — Auto Deploy](#7-github-actions--auto-deploy)
8. [Quy trình cập nhật hàng ngày](#8-quy-trình-cập-nhật-hàng-ngày)
9. [phpMyAdmin qua SSH Tunnel](#9-phpmyadmin-qua-ssh-tunnel)
10. [Xử lý sự cố](#10-xử-lý-sự-cố)

---

## 1. Kiến trúc hệ thống

```
Internet (:80/:443)
  └── Nginx
        ├── thuchocvn.vn          →  /var/www/devminhan/public  (trang chủ)
        │     └── PHP 8.5-FPM → Laravel 13
        │           ├── MySQL 8  (DB: thuchocvn)
        │           ├── Redis    (queue · cache · session)
        │           └── Reverb  :8080 (WebSocket)
        │
        └── quantri.thuchocvn.vn  →  /var/www/minhan/public    (quản trị)
              └── PHP 8.5-FPM → Laravel 13
                    ├── MySQL 8  (DB: minhan)
                    ├── Redis    (queue · cache · session)
                    └── Reverb  :8081 (WebSocket)

Background (Supervisor)
  ├── devminhan-horizon  · devminhan-reverb (:8080)
  └── minhan-horizon     · minhan-reverb    (:8081)

CI/CD
  GitHub tag v* → GitHub Actions → SSH → VPS → deploy.sh
```

| | devminhan | minhan |
|---|---|---|
| Domain | `thuchocvn.vn` | `quantri.thuchocvn.vn` |
| Thư mục | `/var/www/devminhan` | `/var/www/minhan` |
| Database | `thuchocvn` | `minhan` |
| Reverb port | `8080` | `8081` |

**Cổng mở ra ngoài:** `22` (SSH) · `80` (HTTP→redirect) · `443` (HTTPS+WSS)  
**Cổng nội bộ only:** `3306` (MySQL) · `6379` (Redis) · `8080` `8081` (Reverb)

---

## 2. Chuẩn bị server

> **Specs thực tế:** Ubuntu 26.04 LTS · 12 cores · 7.2GB RAM · 3.9GB Swap (có sẵn) · 19GB disk

### 2.1 Update hệ thống

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip software-properties-common
```

### 2.2 UFW Firewall

```bash
# SSH trước — bắt buộc không được bỏ
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
sudo ufw status verbose
```

> ⚠️ **Không mở port 8080, 3306, 6379 ra ngoài.**

### 2.3 Swap

> ✅ VPS đã có sẵn 3.9GB swap — **bỏ qua bước này**.

### 2.4 Tạo user deploy

```bash
sudo adduser deploy
sudo usermod -aG sudo deploy
sudo usermod -aG www-data deploy
```

---

## 3. Cài đặt stack - Dùng ubuntu 26.04

### 3.1 PHP 8.5

```bash
sudo apt update
sudo apt install -y \
  php8.5-fpm php8.5-cli \
  php8.5-mysql php8.5-redis \
  php8.5-curl php8.5-mbstring php8.5-xml \
  php8.5-zip php8.5-bcmath php8.5-intl \
  php8.5-gd php8.5-soap

# Nếu composer báo thiếu extension, cài bổ sung:
# sudo apt install -y php8.5-curl php8.5-xml php8.5-zip && sudo systemctl restart php8.5-fpm

# Nếu php8.5-cli chưa được cài kèm, cài thêm:
sudo apt install -y php8.5-cli

# opcache + tokenizer đã tích hợp sẵn trong php8.5-common (không cài riêng)
php8.5 -v
```

**OPcache** — Ubuntu 26 không tự tạo file ini, cần tạo thủ công:

```bash
sudo tee /etc/php/8.5/mods-available/opcache.ini << 'EOF'
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
EOF

# Enable cho cli + fpm
sudo phpenmod opcache

# Kiểm tra
php8.5 -m | grep -i opcache
```

**PHP-FPM pool** — `/etc/php/8.5/fpm/pool.d/www.conf` (tối ưu cho 12 cores · 7.2GB RAM):

```ini
pm = dynamic
pm.max_children      = 50
pm.start_servers     = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests      = 500
```

```bash
sudo systemctl restart php8.5-fpm
```

### 3.2 Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3.3 MySQL 8

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

```sql
-- Tạo database và user
CREATE DATABASE thuchocvn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'thuchocvn'@'localhost' IDENTIFIED BY 'Thuchocvn@2026!';
GRANT ALL PRIVILEGES ON thuchocvn.* TO 'thuchocvn'@'localhost';
FLUSH PRIVILEGES;
```

### 3.4 Redis

```bash
sudo apt install -y redis-server
```

Sửa `/etc/redis/redis.conf`:

```
bind 127.0.0.1
requirepass Thuchocvn@2026!
maxmemory 512mb
maxmemory-policy allkeys-lru
```

```bash
sudo systemctl enable redis-server && sudo systemctl restart redis-server
redis-cli -a 'Thuchocvn@2026!' ping   # → PONG
```

### 3.5 Node.js 22 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v   # v22.x
```

### 3.6 Google Chrome (Browsershot / PDF)

```bash
wget -q -O - https://dl.google.com/linux/linux_signing_key.pub \
  | sudo gpg --dearmor -o /usr/share/keyrings/google-chrome.gpg

echo "deb [arch=amd64 signed-by=/usr/share/keyrings/google-chrome.gpg] \
  http://dl.google.com/linux/chrome/deb/ stable main" \
  | sudo tee /etc/apt/sources.list.d/google-chrome.list

sudo apt update && sudo apt install -y google-chrome-stable
which google-chrome   # → /usr/bin/google-chrome
```

---

## 4. Cấu hình Nginx + SSL

### 4.1 Cài Nginx

```bash
sudo apt install -y nginx && sudo systemctl enable nginx
```

### 4.2 Config site — thuchocvn.vn (trang chủ)

```bash
sudo nano /etc/nginx/sites-available/thuchocvn
```

```nginx
server {
    listen 80;
    server_name thuchocvn.vn www.thuchocvn.vn;
    return 301 https://thuchocvn.vn$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name thuchocvn.vn www.thuchocvn.vn;
    root /var/www/devminhan/public;
    index index.php;
    charset utf-8;
    client_max_body_size 50M;

    ssl_certificate     /etc/letsencrypt/live/thuchocvn.vn/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/thuchocvn.vn/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript image/svg+xml;

    location ~* \.(js|css|png|jpg|ico|woff2|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_read_timeout 60s;
    }

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass   unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 120;
    }

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    location ~ /\.(env|ht|git) { deny all; }
}
```

### 4.3 Config site — quantri.thuchocvn.vn (quản trị)

```bash
sudo nano /etc/nginx/sites-available/quantri-thuchocvn
```

```nginx
server {
    listen 80;
    server_name quantri.thuchocvn.vn;
    return 301 https://quantri.thuchocvn.vn$request_uri;
}

server {
    listen 443 ssl;
    http2 on;
    server_name quantri.thuchocvn.vn;
    root /var/www/minhan/public;
    index index.php;
    charset utf-8;
    client_max_body_size 50M;

    ssl_certificate     /etc/letsencrypt/live/quantri.thuchocvn.vn/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/quantri.thuchocvn.vn/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript image/svg+xml;

    location ~* \.(js|css|png|jpg|ico|woff2|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location /app {
        proxy_pass http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_read_timeout 60s;
    }

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass   unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include        fastcgi_params;
        fastcgi_read_timeout 120;
    }

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    location ~ /\.(env|ht|git) { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/thuchocvn /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/quantri-thuchocvn /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

### 4.4 SSL Let's Encrypt

> ⚠️ Trỏ DNS trước khi chạy:
> - `thuchocvn.vn` → IP VPS
> - `www.thuchocvn.vn` → IP VPS
> - `quantri.thuchocvn.vn` → IP VPS

```bash
sudo apt install -y certbot python3-certbot-nginx

# Cấp SSL cho cả 3 domain 1 lần
sudo certbot --nginx \
  -d thuchocvn.vn -d www.thuchocvn.vn \
  -d quantri.thuchocvn.vn \
  --email hotro@thuchocvn.vn --agree-tos --no-eff-email

# Kiểm tra auto-renew
sudo certbot renew --dry-run
```

---

## 5. Deploy dự án lần đầu

### 5.1 SSH key cho GitHub (git pull trên VPS)

```bash
# Tạo deploy key cho user deploy
sudo -u deploy ssh-keygen -t ed25519 -C "deploy@thuchocvn.vn" \
  -f /home/deploy/.ssh/github_deploy -N ""

# Cấu hình SSH
sudo -u deploy bash -c 'cat >> /home/deploy/.ssh/config << EOF
Host github.com
  HostName github.com
  User git
  IdentityFile /home/deploy/.ssh/github_deploy
  StrictHostKeyChecking accept-new
EOF'

# In public key — thêm vào GitHub repo → Settings → Deploy keys
sudo cat /home/deploy/.ssh/github_deploy.pub

# Test
sudo -u deploy ssh -T git@github.com
```

### 5.2 Clone 2 repository

```bash
# Project trang chủ — thuchocvn.vn
sudo mkdir -p /var/www/devminhan
sudo chown deploy:www-data /var/www/devminhan
sudo -u deploy git clone git@github.com:hakienpdu95/devminhan.git /var/www/devminhan
sudo chmod +x /var/www/devminhan/deploy.sh

# Project quản trị — quantri.thuchocvn.vn
sudo mkdir -p /var/www/minhan
sudo chown deploy:www-data /var/www/minhan
sudo -u deploy git clone git@github.com:hakienpdu95/minhan.git /var/www/minhan
sudo chmod +x /var/www/minhan/deploy.sh
```

### 5.3 File .env — devminhan (thuchocvn.vn)

```bash
cp /var/www/devminhan/.env.example /var/www/devminhan/.env
nano /var/www/devminhan/.env
```

```env
APP_NAME=ThucHoc
APP_ENV=production
APP_DEBUG=false
APP_URL=https://thuchocvn.vn

DB_DATABASE=thuchocvn
DB_USERNAME=thuchocvn
DB_PASSWORD=Thuchocvn@2026!

REDIS_PASSWORD=Thuchocvn@2026!
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_DOMAIN=thuchocvn.vn

REVERB_HOST=thuchocvn.vn
REVERB_PORT=443
REVERB_SCHEME=https
VITE_REVERB_HOST=thuchocvn.vn
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

LARAVEL_PDF_CHROME_PATH=/usr/bin/google-chrome
LARAVEL_PDF_NODE_MODULES_PATH=/var/www/devminhan/node_modules
```

### 5.4 File .env — minhan (quantri.thuchocvn.vn)

```bash
cp /var/www/minhan/.env.example /var/www/minhan/.env
nano /var/www/minhan/.env
```

```env
APP_NAME=ThucHoc Admin
APP_ENV=production
APP_DEBUG=false
APP_URL=https://quantri.thuchocvn.vn

DB_DATABASE=minhan
DB_USERNAME=thuchocvn
DB_PASSWORD=Thuchocvn@2026!

REDIS_PASSWORD=Thuchocvn@2026!
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_DOMAIN=quantri.thuchocvn.vn

REVERB_HOST=quantri.thuchocvn.vn
REVERB_PORT=443
REVERB_SCHEME=https
VITE_REVERB_HOST=quantri.thuchocvn.vn
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

LARAVEL_PDF_CHROME_PATH=/usr/bin/google-chrome
LARAVEL_PDF_NODE_MODULES_PATH=/var/www/minhan/node_modules
```

> Tạo thêm database `minhan` nếu chưa có:
> ```sql
> CREATE DATABASE minhan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> GRANT ALL PRIVILEGES ON minhan.* TO 'thuchocvn'@'localhost';
> FLUSH PRIVILEGES;
> ```

### 5.5 Khởi tạo lần đầu — devminhan

```bash
cd /var/www/devminhan
sudo chown -R deploy:www-data /var/www/devminhan
sudo -u deploy composer install --no-dev --optimize-autoloader --no-interaction
sudo -u deploy npm ci && sudo -u deploy npm run build
php8.5 artisan key:generate
php8.5 artisan migrate --force
php8.5 artisan db:seed --force
php8.5 artisan storage:link
php8.5 artisan config:cache && php8.5 artisan route:cache
php8.5 artisan view:cache && php8.5 artisan event:cache
```

### 5.6 Khởi tạo lần đầu — minhan

```bash
cd /var/www/minhan
sudo chown -R deploy:www-data /var/www/minhan
sudo -u deploy composer install --no-dev --optimize-autoloader --no-interaction
sudo -u deploy npm ci && sudo -u deploy npm run build
php8.5 artisan key:generate
php8.5 artisan migrate --force
php8.5 artisan db:seed --force
php8.5 artisan storage:link
php8.5 artisan config:cache && php8.5 artisan route:cache
php8.5 artisan view:cache && php8.5 artisan event:cache
```

### 5.7 Phân quyền (cả 2 project)

```bash
for DIR in /var/www/devminhan /var/www/minhan; do
  sudo chown -R www-data:www-data $DIR/storage $DIR/bootstrap/cache
  sudo chmod -R 775 $DIR/storage $DIR/bootstrap/cache
done
```

---

## 6. Dịch vụ nền — Supervisor + Cron

### 6.1 Supervisor

```bash
sudo apt install -y supervisor
sudo nano /etc/supervisor/conf.d/thuchocvn.conf
```

```ini
; ── devminhan — thuchocvn.vn ─────────────────────────────────
[program:devminhan-horizon]
process_name=%(program_name)s
command=/usr/bin/php8.5 /var/www/devminhan/artisan horizon
directory=/var/www/devminhan
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/devminhan/storage/logs/horizon.log
stopwaitsecs=3600
stopsignal=SIGTERM

[program:devminhan-reverb]
process_name=%(program_name)s
command=/usr/bin/php8.5 /var/www/devminhan/artisan reverb:start --host=127.0.0.1 --port=8080 --no-interaction
directory=/var/www/devminhan
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/devminhan/storage/logs/reverb.log

; ── minhan — quantri.thuchocvn.vn ────────────────────────────
[program:minhan-horizon]
process_name=%(program_name)s
command=/usr/bin/php8.5 /var/www/minhan/artisan horizon
directory=/var/www/minhan
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/minhan/storage/logs/horizon.log
stopwaitsecs=3600
stopsignal=SIGTERM

[program:minhan-reverb]
process_name=%(program_name)s
command=/usr/bin/php8.5 /var/www/minhan/artisan reverb:start --host=127.0.0.1 --port=8081 --no-interaction
directory=/var/www/minhan
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/minhan/storage/logs/reverb.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start all
sudo supervisorctl status
```

### 6.2 Cấu hình sudo cho deploy.sh

```bash
sudo visudo -f /etc/sudoers.d/deploy-supervisor
```

```
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart devminhan-horizon
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart devminhan-reverb
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart minhan-horizon
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart minhan-reverb
```

### 6.3 Cron (Laravel Scheduler)

```bash
sudo crontab -u www-data -e
```

```
* * * * * cd /var/www/devminhan && /usr/bin/php8.5 artisan schedule:run >> /dev/null 2>&1
* * * * * cd /var/www/minhan && /usr/bin/php8.5 artisan schedule:run >> /dev/null 2>&1
```

---

## 7. GitHub Actions — Auto Deploy

Quy trình: **push git tag** → GitHub Actions trigger → SSH vào VPS → chạy `deploy.sh`.

### 7.0 Tạo Personal Access Token (PAT)

Token dùng để `git push` từ máy local lên GitHub — cần có scope `workflow` để push file `.github/workflows/`.

```
GitHub → Avatar → Settings
→ Developer settings → Personal access tokens → Tokens (classic)
→ Generate new token (classic)

  Note:       minhan deploy
  Expiration: 90 days

  Scope cần tick:
    ✅ repo     (toàn bộ — cho phép push code)
    ✅ workflow  (bắt buộc — cho phép push file workflow)

→ Generate token → Copy ngay (chỉ hiện 1 lần)
```

Cập nhật remote URL trên máy local (làm cho **từng repo**):

```bash
# repo minhan
git remote set-url origin https://kiendh:<TOKEN>@github.com/hakienpdu95/minhan.git

# repo devminhan
git remote set-url origin https://kiendh:<TOKEN>@github.com/hakienpdu95/devminhan.git
```

> Thay `<TOKEN>` bằng token vừa copy. Token lưu trong URL remote, không cần nhập lại.

```
Developer            GitHub                  VPS
    │                   │                     │
    ├── git push ──────►│                     │
    │                   ├── Actions trigger   │
    │                   ├── SSH connect ─────►│
    │                   │                     ├── git pull
    │                   │                     ├── composer install
    │                   │                     ├── npm build
    │                   │                     ├── artisan migrate
    │                   │                     ├── cache rebuild
    │                   │                     └── restart workers
    │                   │◄── log output ───────┤
    │◄── notify ────────┤                     │
```

### 7.1 Tạo SSH key cho GitHub Actions

Chạy trên **máy local** (không phải VPS):

```bash
# Tạo key pair riêng cho GitHub Actions
ssh-keygen -t ed25519 -C "github-actions@thuchocvn.vn" \
  -f ~/.ssh/gh_actions_minhan -N ""

# In ra để dùng ở bước tiếp theo
echo "=== PUBLIC KEY (thêm vào VPS) ==="
cat ~/.ssh/gh_actions_minhan.pub

echo "=== PRIVATE KEY (thêm vào GitHub Secrets) ==="
cat ~/.ssh/gh_actions_minhan
```

### 7.2 Thêm public key vào VPS

SSH vào VPS, chạy với user `deploy`:

```bash
# Thêm public key vào authorized_keys của user deploy
echo "ssh-ed25519 AAAA... github-actions@thuchocvn.vn" \
  | sudo tee -a /home/deploy/.ssh/authorized_keys

sudo chmod 600 /home/deploy/.ssh/authorized_keys
sudo chown deploy:deploy /home/deploy/.ssh/authorized_keys
```

> Đây là key **khác** với deploy key cho git pull. Hai key hai việc riêng.

### 7.3 Cấu hình GitHub Secrets

Mỗi repo cần 3 secrets giống nhau. Vào **GitHub repo → Settings → Secrets and variables → Actions**:

| Secret name | Giá trị |
|-------------|---------|
| `VPS_HOST` | IP VPS |
| `VPS_USER` | `deploy` |
| `VPS_SSH_KEY` | Nội dung file `~/.ssh/gh_actions_minhan` (toàn bộ private key) |

### 7.4 File workflow

> **Chiến lược:** Deploy chỉ xảy ra khi push **git tag** dạng `v*`.  
> Push lên `main` bao nhiêu lần cũng không ảnh hưởng server production.

**devminhan** — `.github/workflows/deploy.yml`:

```yaml
name: Deploy on Tag
on:
  push:
    tags:
      - 'v*'
concurrency:
  group: production-deploy
  cancel-in-progress: false
jobs:
  deploy:
    name: "→ thuchocvn.vn (${{ github.ref_name }})"
    runs-on: ubuntu-latest
    timeout-minutes: 20
    environment:
      name: production
      url: https://thuchocvn.vn
    steps:
      - name: "Deploy ${{ github.ref_name }} via SSH"
        uses: appleboy/ssh-action@v1.2.0
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          port: 22
          script_stop: true
          script: |
            cd /var/www/devminhan
            bash deploy.sh
```

**minhan** — `.github/workflows/deploy.yml`:

```yaml
name: Deploy on Tag
on:
  push:
    tags:
      - 'v*'
concurrency:
  group: production-deploy
  cancel-in-progress: false
jobs:
  deploy:
    name: "→ quantri.thuchocvn.vn (${{ github.ref_name }})"
    runs-on: ubuntu-latest
    timeout-minutes: 20
    environment:
      name: production
      url: https://quantri.thuchocvn.vn
    steps:
      - name: "Deploy ${{ github.ref_name }} via SSH"
        uses: appleboy/ssh-action@v1.2.0
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          port: 22
          script_stop: true
          script: |
            cd /var/www/minhan
            bash deploy.sh
```

### 7.5 Tạo GitHub Environment "production"

Vào **GitHub repo → Settings → Environments → New environment**:

- Tên: `production`
- (Tuỳ chọn) Bật **Required reviewers** nếu muốn có bước xác nhận trước khi deploy

### 7.6 Kiểm tra lần đầu

```bash
# Test xong trên local, push code bình thường
git push origin main     # không deploy gì cả

# Khi sẵn sàng đưa lên server
git tag v1.0.0 -m "Release đầu tiên"
git push origin v1.0.0   # → Actions tự chạy deploy
```

Kết quả mong đợi trong Actions log:

```
[10:23:01] ═══════════════════════════════════════
[10:23:01]   Deploy thuchocvn.vn — 2025-06-20 10:23:01
[10:23:01]   Branch: main | Skip migrations: false
[10:23:01] ═══════════════════════════════════════
[10:23:01] [1/7] Pulling latest code...
[10:23:03] ✓ Code updated → abc1234 cap nhat
[10:23:03] [2/7] Installing PHP dependencies...
...
[10:23:45] ✅ Deploy hoàn tất — 2025-06-20 10:23:45
```

---

## 8. Quy trình cập nhật hàng ngày

### Push code thường — KHÔNG deploy

```bash
# Làm việc bình thường, push bao nhiêu cũng được
git add .
git commit -m "feat: mô tả thay đổi"
git push origin main
# → server production không bị ảnh hưởng
```

### Deploy lên production — gắn tag khi sẵn sàng

```bash
# Khi test xong, muốn đưa lên server
git tag v1.2.0 -m "Thêm tính năng X, sửa lỗi Y"
git push origin v1.2.0
# → GitHub Actions deploy tự động
# → Theo dõi tại: GitHub repo → Actions tab
```

### Đặt tên tag theo quy tắc

```
v1.0.0  — release chính thức đầu tiên
v1.1.0  — thêm tính năng mới
v1.1.1  — sửa lỗi nhỏ
v1.2.0  — cập nhật lớn
```

### Deploy bỏ qua migration (hotfix khẩn)

```bash
# Chạy thẳng trên VPS — không cần tag
ssh deploy@thuchocvn.vn "cd /var/www/minhan && SKIP_MIGRATIONS=true bash deploy.sh"
```

### Rollback về version cũ

```bash
# Xem danh sách tag đã deploy
git tag -l --sort=-version:refname | head -10

# Deploy lại tag cũ — chỉ cần push tag đó lại
git push origin v1.1.0   # → Actions deploy v1.1.0 lên server

# Hoặc rollback thẳng trên VPS
ssh deploy@thuchocvn.vn

cd /var/www/minhan

# Quay về commit của tag cụ thể
git checkout v1.1.0

# Rollback migration nếu cần
/usr/bin/php8.5 artisan migrate:rollback --step=1

# Tắt maintenance nếu bị kẹt
/usr/bin/php8.5 artisan up

# Rebuild cache
/usr/bin/php8.5 artisan config:cache
/usr/bin/php8.5 artisan route:cache
```

---

## 9. phpMyAdmin qua SSH Tunnel

Không expose phpMyAdmin ra internet — chỉ truy cập qua SSH tunnel từ máy local.

### 9.1 Cài phpMyAdmin trên VPS

```bash
sudo apt install -y phpmyadmin
# Khi hỏi web server: nhấn Space để bỏ chọn tất cả → OK
# Khi hỏi dbconfig-common: Yes
```

Cấu hình Nginx — thêm vào trong `server` block của `/etc/nginx/sites-available/thuchocvn`:

```nginx
# phpMyAdmin — chỉ cho phép localhost (SSH tunnel), chặn internet
location /phpmyadmin {
    allow 127.0.0.1;
    deny all;

    root /usr/share/;
    index index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        include fastcgi_params;
    }
}
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 9.2 Mở SSH Tunnel từ máy local

**Mac / Linux / Windows Terminal:**

```bash
ssh -L 8888:localhost:80 thuchoc@124.158.6.69 -p 2223
```

Terminal sẽ đứng yên — đó là bình thường, tunnel đang chạy.  
Mở trình duyệt: **`http://localhost:8888/phpmyadmin`**  
Khi xong: `Ctrl+C` để đóng tunnel.

**Windows — PuTTY:**

```
Session     → Host: 124.158.6.69 | Port: 2223
SSH → Tunnels → Source port: 8888 | Destination: localhost:80 → Add
→ Open → nhập password
→ Mở trình duyệt: http://localhost:8888/phpmyadmin
```

### 9.3 Alias tiện dụng (đặt 1 lần, dùng mãi)

Thêm vào `~/.bashrc` hoặc `~/.zshrc` trên máy **local**:

```bash
alias tunnel-vps="ssh -L 8888:localhost:80 thuchoc@124.158.6.69 -p 2223"
```

```bash
source ~/.bashrc   # hoặc source ~/.zshrc
```

Từ sau chỉ cần:

```bash
tunnel-vps
# → mở http://localhost:8888/phpmyadmin
```

### 9.4 Tunnel chạy nền (không cần giữ terminal)

```bash
# Mở tunnel nền
ssh -f -N -L 8888:localhost:80 thuchoc@124.158.6.69 -p 2223

# Đóng tunnel khi xong
kill $(lsof -t -i:8888)
```

### 9.5 Luồng sử dụng hàng ngày

```
1. Terminal → gõ: tunnel-vps
2. Nhập password VPS
3. Chrome → http://localhost:8888/phpmyadmin
4. Làm việc xong → Ctrl+C
```

---

## 10. Xử lý sự cố

### Kiểm tra tổng thể

```bash
# Tất cả services
sudo systemctl status nginx php8.5-fpm mysql redis-server
sudo supervisorctl status

# Log Laravel
tail -f /var/www/minhan/storage/logs/laravel.log

# Log Horizon
tail -f /var/www/minhan/storage/logs/horizon.log

# Log Nginx
sudo tail -f /var/log/nginx/error.log
```

### Sự cố thường gặp

**GitHub Actions báo lỗi "Permission denied"**

```bash
# Kiểm tra authorized_keys
cat /home/deploy/.ssh/authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Test SSH thủ công
ssh -i ~/.ssh/gh_actions_minhan deploy@VPS_IP "echo ok"
```

**`npm run build` lỗi do hết RAM**

```bash
# Tăng Node.js memory limit trong deploy.sh
NODE_OPTIONS="--max-old-space-size=512" npm run build
```

**Horizon không nhận code mới sau deploy**

```bash
sudo supervisorctl restart minhan-horizon
/usr/bin/php8.5 artisan horizon:status
```

**WebSocket không kết nối**

```bash
# Kiểm tra Reverb đang chạy
sudo supervisorctl status minhan-reverb
# Kiểm tra Nginx proxy /app
curl -I https://thuchocvn.vn/app
# Xem log Reverb
tail -f /var/www/minhan/storage/logs/reverb.log
```

**Trang web hiện 502 Bad Gateway**

```bash
sudo systemctl status php8.5-fpm
sudo systemctl restart php8.5-fpm
```

**OPcache không cập nhật sau deploy**

```bash
# Thêm vào deploy.sh nếu cần
/usr/bin/php8.5 artisan opcache:clear
# Hoặc
sudo systemctl reload php8.5-fpm
```

### Backup database

```bash
# Thêm vào crontab — backup hàng ngày 3AM, giữ 7 ngày
sudo crontab -e
```

```
0 3 * * * mysqldump -u minhan -p'MK_DB_Manh@2025!' minhan \
  | gzip > /var/backups/minhan_$(date +\%Y\%m\%d).sql.gz \
  && find /var/backups/minhan_*.sql.gz -mtime +7 -delete
```

---

## Tham chiếu nhanh

| Lệnh | Mô tả |
|------|-------|
| `bash /var/www/minhan/deploy.sh` | Deploy thủ công |
| `sudo supervisorctl status` | Kiểm tra Horizon + Reverb |
| `sudo supervisorctl restart minhan-horizon` | Restart queue workers |
| `/usr/bin/php8.5 artisan horizon:status` | Trạng thái Horizon |
| `/usr/bin/php8.5 artisan about` | Kiểm tra toàn bộ config |
| `/usr/bin/php8.5 artisan queue:monitor` | Theo dõi queue |
| `redis-cli -a 'pass' ping` | Kiểm tra Redis |
| `sudo nginx -t` | Validate Nginx config |
| `sudo certbot renew --dry-run` | Test SSL auto-renew |

**URLs quan trọng sau khi deploy:**

- Production: `https://thuchocvn.vn`
- Horizon dashboard: `https://thuchocvn.vn/horizon` _(chỉ super-admin)_
- GitHub Actions: `https://github.com/hakienpdu95/minhan/actions`
