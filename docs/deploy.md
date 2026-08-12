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
11. [Sự cố thực tế khi thêm site mới trên VPS (case study)](#11-sự-cố-thực-tế-khi-thêm-site-mới-trên-vps-case-study)
12. [Checklist tổng hợp — Thêm domain/project mới ổn định ngay từ đầu](#12-checklist-tổng-hợp--thêm-1-domainproject-mới-ổn-định-ngay-từ-đầu)

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

Không expose phpMyAdmin ra internet — chỉ truy cập qua SSH tunnel từ máy local, dùng **vhost Nginx riêng biệt, độc lập hoàn toàn** với `thuchocvn`/`quantri-thuchocvn`.

> ⚠️ **Bài học từ thực tế** — ĐỪNG thêm `location /phpmyadmin` vào vhost `thuchocvn`/`quantri-thuchocvn`, rất dễ gãy vì 2 lý do:
> 1. `location ~ \.php$` (regex, dùng cho Laravel) có độ ưu tiên cao hơn `location /phpmyadmin` (prefix match thường), trừ khi thêm modifier `^~` — nếu không, mọi request `.php` trong `/phpmyadmin` bị regex của Laravel "cướp" trước, PHP-FPM tìm sai đường dẫn (theo root Laravel) → 404.
> 2. Certbot tự sinh `return 404;` nằm **trần trong `server{}`** (không bọc trong `location`) ở block `listen 80` — directive này chạy ở pha *server rewrite*, **trước khi** Nginx chọn location, nên chặn *mọi* request tới port 80 (kể cả `/phpmyadmin`) bất kể bạn thêm location gì.
>
> → Cách bền hơn nhiều: tạo **1 vhost hoàn toàn tách biệt**, chỉ lắng nghe ở `127.0.0.1`, không đụng tới Certbot/Laravel.

### 9.1 Cài phpMyAdmin trên VPS

```bash
sudo apt install -y phpmyadmin
# Khi hỏi web server: nhấn Space để bỏ chọn tất cả → OK
# Khi hỏi dbconfig-common: Yes
```

### 9.2 Tạo vhost Nginx riêng cho phpMyAdmin

```bash
sudo nano /etc/nginx/sites-available/phpmyadmin-local
```

```nginx
server {
    listen 127.0.0.1:8082;
    server_name localhost;
    root /usr/share/phpmyadmin;
    index index.php;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

`listen 127.0.0.1:8082` đảm bảo port này **không** nghe trên IP public — chỉ truy cập được từ chính VPS (qua SSH tunnel), dù UFW có lỡ mở port cũng không expose ra ngoài.

```bash
sudo ln -s /etc/nginx/sites-available/phpmyadmin-local /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# Test ngay trên VPS trước khi mở tunnel — phải ra 200 OK
curl -sI http://127.0.0.1:8082/index.php
```

### 9.3 Mở SSH Tunnel từ máy local

**Mac / Linux / Windows Terminal:**

```bash
ssh -N -L 8888:127.0.0.1:8082 thuchoc@124.158.6.69 -p 2223
```

`-N` giữ tunnel chạy nền mà không mở shell tương tác — terminal đứng yên đúng nghĩa. Không dùng `-N` cũng không sao (SSH vẫn đăng nhập vào shell VPS, tunnel vẫn chạy song song) — chỉ cần **không đóng cửa sổ terminal**.

Mở trình duyệt: **`http://localhost:8888/`** — chú ý truy cập vào **gốc `/`**, không phải `/phpmyadmin`, vì vhost riêng này có `root` trỏ thẳng vào thư mục phpMyAdmin.

Khi xong: `Ctrl+C` để đóng tunnel.

**Windows — PuTTY:**

```
Session     → Host: 124.158.6.69 | Port: 2223
SSH → Tunnels → Source port: 8888 | Destination: 127.0.0.1:8082 → Add
→ Open → nhập password
→ Mở trình duyệt: http://localhost:8888/
```

### 9.4 Alias tiện dụng (đặt 1 lần, dùng mãi — trên từng máy: công ty, laptop cá nhân...)

Thêm vào `~/.bashrc` hoặc `~/.zshrc` trên máy **local**:

```bash
alias pma-tunnel="ssh -N -L 8888:127.0.0.1:8082 thuchoc@124.158.6.69 -p 2223"
```

```bash
source ~/.bashrc   # hoặc source ~/.zshrc
```

Từ sau chỉ cần:

```bash
pma-tunnel
# → mở http://localhost:8888/
```

SSH vốn không giới hạn theo IP nguồn (chỉ cần đúng key/password), nên cách này dùng được linh hoạt từ bất kỳ máy nào — không cần cấu hình lại firewall mỗi khi đổi mạng/IP.

### 9.5 Tài khoản MySQL đăng nhập phpMyAdmin

**Không dùng user production của app** (`thuchocvn`, `minhan`...) để đăng nhập phpMyAdmin quản trị chung — nếu dùng chung, lỡ app bị lỗi bảo mật (SQL injection...) thì kẻ tấn công có thể lợi dụng đúng quyền hạn đó. Giữ nguyên tắc: user app chỉ có quyền trong đúng DB của app.

Tạo riêng 1 user admin (toàn quyền server) chỉ dùng để quản trị qua phpMyAdmin:

```bash
sudo mysql -u root
```

```sql
CREATE USER 'pma_admin'@'localhost' IDENTIFIED BY 'MatKhauManhRieng';
GRANT ALL PRIVILEGES ON *.* TO 'pma_admin'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

Khi cần tạo database/user mới cho 1 project cụ thể (theo đúng pattern mục 3.3 — 1 DB + 1 user cùng tên, giới hạn trong đúng DB đó):

```sql
CREATE DATABASE ten_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ten_db'@'localhost' IDENTIFIED BY 'MatKhauRieng';
GRANT ALL PRIVILEGES ON ten_db.* TO 'ten_db'@'localhost';
FLUSH PRIVILEGES;
```

### 9.6 Xử lý sự cố thường gặp

**`bind [127.0.0.1]:8888: Address already in use`**

Port ở máy local đang bị 1 tunnel cũ (chưa đóng hẳn) chiếm:

```bash
lsof -i :8888          # Mac/Linux — xem tiến trình đang giữ port
kill $(lsof -t -i:8888)
```

Hoặc đơn giản đổi sang port local khác: `ssh -N -L 8889:127.0.0.1:8082 ...` rồi vào `http://localhost:8889/`.

**Truy cập `http://localhost:8888/` ra 404**

1. Kiểm tra vhost đã enable: `ls -la /etc/nginx/sites-enabled/ | grep phpmyadmin`
2. Test trực tiếp trên VPS trước khi nghi ngờ tunnel: `curl -sI http://127.0.0.1:8082/index.php` (phải ra `200 OK`)
3. Nếu VPS đã 200 OK nhưng trình duyệt vẫn 404 — thử cửa sổ ẩn danh (loại trừ cache từ lần thử URL/port khác trước đó), và kiểm tra đúng port trong lệnh tunnel khớp với port bạn gõ trên trình duyệt.

**phpMyAdmin đăng nhập được nhưng không có nút tạo database ("No privileges to create databases")**

User đang dùng bị giới hạn quyền theo từng DB (như `thuchocvn` chỉ có quyền trên `thuchocvn.*`) — đây là thiết kế đúng, không phải lỗi. Cần đăng nhập bằng `pma_admin` (mục 9.5) để có toàn quyền tạo database mới trên server.

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

## 11. Sự cố thực tế khi thêm site mới trên VPS (case study)

Đúc kết từ quá trình deploy thực tế 1 site mới (`familiesforlife`, domain `vigiadinh.vn`) lên cùng VPS đang chạy `thuchocvn`/`minhan`. Thư mục lấy tên theo đúng tên repo Git (`familiesforlife`) thay vì theo mẫu `devminhan`/`minhan` trong tài liệu — nên rất nhiều chỗ phải đổi tên thủ công thay vì copy nguyên xi, và phát sinh hàng loạt lỗi liên hoàn. Ghi lại đây để lần sau tra thẳng thay vì debug lại từ đầu.

### 11.1 Checklist: những chỗ PHẢI đổi tên khi copy `deploy.sh`/Supervisor từ site mẫu

| Chỗ cần đổi | File | Ví dụ (mẫu `minhan` → project mới `familiesforlife`) |
|---|---|---|
| `APP_DIR` | `deploy.sh` | `/var/www/familiesforlife` |
| Script fix permission gọi bằng `sudo` | `deploy.sh` | `/usr/local/bin/fix-familiesforlife-build` |
| Tên Supervisor program | `deploy.sh` + Supervisor conf + sudoers | `familiesforlife-horizon`, `familiesforlife-reverb` |
| Comment/log message | `deploy.sh` | đổi tên project trong log cho khỏi nhầm lẫn khi đọc output Actions |
| Reverb port | Supervisor conf + Nginx `location /app` | phải chọn port **chưa ai dùng** — xem bảng bên dưới |

**Bảng port Reverb/dịch vụ nội bộ đang chiếm trên VPS này** (cập nhật ngay khi thêm site mới, tránh giẫm port):

| Port | Dùng cho |
|---|---|
| 8080 | devminhan (thuchocvn.vn) — Reverb |
| 8081 | minhan (quantri.thuchocvn.vn) — Reverb |
| 8082 | phpMyAdmin (mục 9) — **không phải Reverb**, đừng gán trùng |
| 8083 | familiesforlife (vigiadinh.vn) — Reverb |

### 11.2 Supervisor báo `CANT_REREAD: Source contains parsing errors`

**Triệu chứng:** `sudo supervisorctl reread` báo lỗi parse ở dòng `command=...` (ví dụ trỏ vào `'--no-interaction\n'`).

**Nguyên nhân:** paste dòng `command=...` (dài) vào `nano` bị chèn xuống dòng giữa chừng — Supervisor bắt buộc mỗi directive nằm **trọn trên 1 dòng**.

**Fix:** ghi file bằng `tee` heredoc thay vì gõ/paste tay vào nano — không bao giờ bị gãy dòng:
```bash
sudo tee /etc/supervisor/conf.d/<ten-project>.conf > /dev/null << 'EOF'
[program:<ten-project>-horizon]
...
EOF
```
Luôn tạo **1 file `.conf` riêng cho mỗi project**, không gộp chung vào file site khác (`thuchocvn.conf`...) — dễ quản lý, tránh đụng cấu hình của nhau khi sửa/xoá sau này.

### 11.3 `Please provide a valid cache path` khi `composer install` (post-autoload-dump → `package:discover`)

**Triệu chứng:** lỗi từ `Illuminate\View\Compilers\Compiler.php line 75`, ngay sau `git clone` + `composer install` lần đầu trên VPS.

**Nguyên nhân:** `storage/framework/views` (và các thư mục con khác trong `storage/framework`) **không tồn tại** sau khi clone code (Laravel `.gitignore` bỏ qua nội dung các thư mục này). `config/view.php` mặc định dùng `realpath(storage_path('framework/views'))` — nếu thư mục không tồn tại, `realpath()` trả về `false` → Laravel báo "cache path không hợp lệ".

**Fix** — luôn chạy trước khi `composer install` lần đầu trên VPS mới (xem checklist đầy đủ ở 11.6):
```bash
cd /var/www/<project>
cp .env.example .env   # nếu chưa có .env — thiếu .env cũng góp phần gây lỗi này
mkdir -p storage/framework/{sessions,views,cache/data,testing}
mkdir -p storage/logs storage/app/private storage/app/public
```

### 11.4 Hàng loạt lỗi `Permission denied` liên hoàn (log, `.env`, `node_modules/.vite-temp`, `storage/app/private`...)

**Nguyên nhân gốc:** nhiều user Linux khác nhau (`deploy`, `www-data` do lệnh chạy qua `sudo`, user SSH thật đang gõ lệnh như `thuchoc`) từng đụng vào project ở các bước khác nhau trong lúc setup → ownership bị lẫn lộn, user đang thao tác thực tế không có quyền ghi vào đúng chỗ cần.

**Chẩn đoán nhanh:** `whoami` để biết đang chạy bằng user nào, rồi `ls -la` đúng file/thư mục báo lỗi để xem ai đang sở hữu — đừng đoán, luôn kiểm tra trước khi chown.

**Fix tổng thể 1 lần** (thay `<user>` bằng user SSH thật đang thao tác, ví dụ `thuchoc`):
```bash
sudo chown -R <user>:www-data /var/www/<project>
sudo find /var/www/<project> -type d -exec chmod 755 {} \;
sudo find /var/www/<project> -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/<project>/storage /var/www/<project>/bootstrap/cache
```

> ⚠️ **Bẫy quan trọng:** lệnh `chmod 644` áp cho toàn bộ file sẽ xoá luôn quyền thực thi (`+x`) của các binary như `node_modules/.bin/vite`, `vendor/bin/pint`, hay chính `deploy.sh` — gây lỗi `Permission denied` (khác hẳn ý nghĩa với lỗi "Permission denied" do thiếu quyền ghi) khi chạy chúng. Sau lệnh `chmod 644` recursive, LUÔN chạy thêm:
> ```bash
> chmod +x /var/www/<project>/node_modules/.bin/*
> chmod +x /var/www/<project>/vendor/bin/* 2>/dev/null || true
> chmod +x /var/www/<project>/deploy.sh
> ```

Để đỡ phải sửa lại nhiều lần về sau, nên **thêm user SSH thật vào group `www-data`** ngay từ đầu (cần đăng nhập SSH lại mới có hiệu lực):
```bash
sudo usermod -aG www-data <user>
```

> ⚠️ **Đừng giả định đã làm rồi — luôn kiểm tra lại bằng `id`/`groups`.** Case thực tế: gợi ý chạy `usermod -aG www-data thuchoc` được đưa ra nhưng không được thực thi ngay; nhiều bước sau đó (kể cả sau khi `chown`/`setfacl` đã đúng) `php artisan ...` chạy tay bởi `thuchoc` vẫn báo `Permission denied` — vì ACL/permission đã đúng cho group `www-data`, nhưng `id` cho thấy `thuchoc` chưa từng ở trong group đó. Sau khi `usermod`, group mới **chỉ áp dụng cho session SSH mới** — session đang mở vẫn chạy với danh sách group cũ; dùng `newgrp www-data` để có ngay group mới trong session hiện tại mà không cần đăng xuất, hoặc mở 1 cửa sổ SSH mới.

**Fix bền hơn — default ACL (khuyên dùng thay vì chown lặp lại):** `chown -R` ở trên chỉ sửa được ownership của file *đang có* — vài ngày sau, `www-data` (Horizon/PHP-FPM) hoặc `deploy` (CI) tạo file mới trong `storage`/`bootstrap/cache` lại mang owner/group khác, lỗi lặp lại y như cũ vì owner mặc định của file mới do **primary group của process tạo ra nó** quyết định, không tự kế thừa. `setfacl` với default ACL giải quyết dứt điểm: mọi file/thư mục **mới tạo sau này**, bất kể user nào tạo, đều tự động có quyền `rwx` cho group `www-data`:

```bash
sudo apt install -y acl   # nếu server chưa có lệnh setfacl

# Áp ngay cho file/thư mục đang có
sudo setfacl -R -m g:www-data:rwx \
  /var/www/<project>/storage /var/www/<project>/bootstrap/cache

# Default ACL — file/thư mục MỚI tạo trong này về sau tự kế thừa quyền trên,
# không phân biệt tạo bởi deploy (CI), www-data (Horizon/PHP-FPM), hay user SSH thật
sudo setfacl -R -d -m g:www-data:rwx \
  /var/www/<project>/storage /var/www/<project>/bootstrap/cache
```

Kiểm tra đã áp đúng — phải thấy dòng `default:group:www-data:rwx`:
```bash
getfacl /var/www/<project>/storage/logs
```

Làm 1 lần cho mỗi project mới là đủ, không cần lặp lại `chown -R` mỗi khi gặp lỗi permission trong `storage`/`bootstrap/cache` nữa.

### 11.5 `League\Flysystem\UnableToCreateDirectory ... storage/app/private`

Laravel 11+ mặc định cần thư mục `storage/app/private` (local disk mới) — nếu thiếu sau khi clone, thao tác filesystem (upload file...) sẽ lỗi này.

```bash
mkdir -p /var/www/<project>/storage/app/private /var/www/<project>/storage/app/public
sudo chown -R <user>:www-data /var/www/<project>/storage
sudo chmod -R 775 /var/www/<project>/storage
php8.5 artisan storage:link   # nếu dùng public disk
```

### 11.6 Checklist đầy đủ — setup lần đầu 1 project mới trên VPS (đúc kết 11.3–11.5)

```bash
cd /var/www/<project>

# 1. .env + toàn bộ thư mục storage/bootstrap cần thiết
cp .env.example .env
mkdir -p storage/framework/{sessions,views,cache/data,testing}
mkdir -p storage/logs storage/app/private storage/app/public
mkdir -p bootstrap/cache

# 2. Ownership + quyền — chạy bằng user SSH thật đang deploy
sudo chown -R $(whoami):www-data /var/www/<project>
sudo find /var/www/<project> -type d -exec chmod 755 {} \;
sudo find /var/www/<project> -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage bootstrap/cache
chmod +x node_modules/.bin/* vendor/bin/* deploy.sh 2>/dev/null || true

# 3. Cài đặt
composer install --no-dev --optimize-autoloader --no-interaction
php8.5 artisan key:generate
npx vite build --config vite.config.backend.js
npx vite build --config vite.config.frontend.js
php8.5 artisan migrate --force
php8.5 artisan storage:link
php8.5 artisan config:cache && php8.5 artisan route:cache
php8.5 artisan view:cache  && php8.5 artisan event:cache
```

### 11.7 Certbot: `cannot load certificate ... No such file` (chicken-and-egg)

**Triệu chứng:** chạy `sudo certbot --nginx -d domain -d www.domain` báo `nginx -t` fail vì thiếu file cert — do vhost đã có sẵn dòng `ssl_certificate` trỏ vào cert **chưa từng được cấp lần nào**.

**Fix** — tạm comment 4 dòng sau trong vhost trước khi chạy Certbot lần đầu cho domain đó:
```nginx
#listen 443 ssl;
#http2 on;
#ssl_certificate     /etc/letsencrypt/live/<domain>/fullchain.pem;
#ssl_certificate_key /etc/letsencrypt/live/<domain>/privkey.pem;
```
```bash
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d <domain> -d www.<domain> --email <email> --agree-tos --no-eff-email
```
Certbot tự bỏ comment và điền đúng path cert sau khi cấp thành công — không cần tự sửa lại tay.

### 11.8 Certbot: `conflicting server name` + redirect loop ("The page isn't redirecting properly")

**Triệu chứng:** `nginx -t` cảnh báo (không phải lỗi, vẫn "test is successful") `conflicting server name "domain" on 0.0.0.0:443, ignored`; sau đó trình duyệt báo lỗi redirect loop khi vào HTTPS.

**Nguyên nhân:** Certbot tự động chèn (đánh dấu `# managed by Certbot`) khối SSL vào **2 server block khác nhau** cùng khai báo `server_name` đó trên port 443 — 1 block là block HTTPS thật (có `root`, `location ~ \.php$`...), 1 block vốn chỉ định làm redirect (`return 301 https://...`) nhưng bị Certbot gắn nhầm thêm `listen 443 ssl;` vào. Vì dòng `return 301` đó nằm **trần trong `server{}`** (không bọc trong `location`) nên chạy vô điều kiện ở pha rewrite — kể cả khi request đã ở HTTPS rồi — gây tự redirect vào chính nó vô hạn.

**Fix:** sau mỗi lần chạy Certbot, luôn `cat -n` lại vhost, tìm và xoá hẳn **block dư/hỏng** đó (block chỉ có `return 301` + SSL block bị gắn nhầm vào). Chỉ giữ lại đúng 1 block HTTPS thật + 1 block port 80. Kiểm tra nhanh có bị dư block không:
```bash
sudo grep -c "listen 443" /etc/nginx/sites-available/<file>
```
Nếu ra > 1, chắc chắn có block dư cần dọn.

### 11.9 GitHub Actions — Auto Deploy cho familiesforlife (tag push)

Áp dụng đúng pattern tag-push ở mục 7 (đã chứng minh ổn định trên `devminhan`/`minhan`) cho repo `familiesforlife`, không dùng `workflow_dispatch` — chỉ deploy khi có ai chủ động gắn tag `v*`, giữ nguyên tắc "push `main` bao nhiêu cũng không đụng production".

**Khác biệt so với mục 7:**

- `deploy.sh` được thêm flag `--ref=<git-ref>` (mặc định `main`) thay vì hardcode branch. Workflow gọi `bash deploy.sh --ref=${{ github.ref_name }}` — tự động deploy đúng tag vừa push, không phải luôn kéo `main`.
- Vì `--ref` có thể là tag (không tồn tại dưới dạng `origin/<tag>` sau khi fetch), script tự kiểm tra: nếu `origin/$REF` tồn tại (trường hợp branch như `main`) thì dùng nó, không thì dùng `$REF` trực tiếp (trường hợp tag/commit) — `git fetch origin --tags --force` đảm bảo tag mới luôn có sẵn cục bộ trước khi reset.
- Nhờ vậy, **rollback thủ công cũng dùng đúng 1 lệnh nhất quán** thay vì checkout tay + chạy lại từng bước artisan như mục 8 mô tả:
  ```bash
  ssh deploy@vigiadinh.vn "cd /var/www/familiesforlife && bash deploy.sh --ref=v1.1.0"
  ```

**Setup secrets/deploy key:** làm đúng các bước 7.0–7.5 (PAT cho git push từ local, SSH key riêng cho GitHub Actions, thêm public key vào `authorized_keys` của user `deploy`, 3 secrets `VPS_HOST`/`VPS_USER`/`VPS_SSH_KEY`, tạo GitHub Environment `production`) — chỉ đổi tên repo/domain thành `familiesforlife`/`vigiadinh.vn`. Workflow file: `.github/workflows/deploy.yml` trong repo này.

### 11.9b Checklist thực tế — kích hoạt lần đầu CI/CD tag-push (đúc kết từ lần bật cho familiesforlife)

Lần đầu bật workflow ở 11.9 cho 1 site đã tồn tại (project không được setup CI/CD từ đầu, từng deploy tay bằng user khác) phát sinh 5 lỗi liên hoàn, đúng thứ tự gặp phải — tra thẳng theo đây thay vì debug lại:

1. **PAT thiếu scope `workflow`** — `git push` báo `refusing to allow a Personal Access Token to create or update workflow ... without workflow scope`. Fix: vào GitHub → token (classic) → tick thêm `workflow`, hoặc nếu dùng fine-grained token thì cấp quyền **Workflows: Read and write**. Xem lại mục 7.0.

2. **SSH port không phải 22 mặc định** — VPS có thể đã đổi sang port khác (ví dụ `2223`, xem mục 9.3). `Connection refused` ở port 22 → thử đúng port thật. **Workflow phải khai báo port này** — không hardcode `port: 22` trong `.github/workflows/deploy.yml`, dùng thêm secret `VPS_PORT` và `port: ${{ secrets.VPS_PORT }}`, nếu không GitHub Actions sẽ refused dù SSH tay đã chạy được.

3. **SSH hỏi password dù key đã thêm đúng vào `authorized_keys`** — nguyên nhân thường KHÔNG phải sai key/permission, mà do **test nhầm chiều**: chạy lệnh `ssh ... deploy@VPS` từ chính 1 session đang SSH sẵn vào VPS (loopback, user khác, private key không tồn tại ở đó) thay vì từ máy laptop thật — nơi giữ private key. Luôn `ssh -v` để đọc dòng `Offering public key` — nếu không thấy dòng này xuất hiện, khả năng cao đang chạy sai máy/sai user, không phải sai cấu hình key.

4. **`fatal: detected dubious ownership in repository`** khi `deploy.sh` chạy `git fetch/reset` bằng user `deploy` qua GitHub Actions (git ≥2.35 chặn user khác owner thao tác git). Fix 1 lần:
   ```bash
   sudo -u deploy git config --global --add safe.directory /var/www/<project>
   ```
   Nếu sau đó vẫn `error: cannot open '.git/FETCH_HEAD': Permission denied` — nghĩa là ownership thật của thư mục không phải `deploy` (do trước đó deploy tay bằng user khác), `safe.directory` chỉ tắt cảnh báo, không cấp quyền ghi. Phải chown thật:
   ```bash
   sudo chown -R deploy:www-data /var/www/<project>
   sudo chmod -R u+rwX,g+rwX /var/www/<project>
   ```
   Dùng `u+rwX,g+rwX` (thêm quyền) thay vì set cứng `644`/`755` để không xoá mất `+x` của `vendor/bin/*`, `node_modules/.bin/*`, `deploy.sh` (bẫy đã ghi ở mục 11.4).

5. **`sudo: a terminal is required to authenticate`** ở bước restart Horizon/Reverb hoặc reload PHP-FPM — SSH của GitHub Actions không có tty, nên bất kỳ lệnh `sudo` nào chưa được cấp `NOPASSWD` sẽ tự fail (không hiện prompt, script chết ngang do `set -e`). Fix — thêm đủ **mọi** lệnh sudo mà `deploy.sh` gọi vào sudoers (không chỉ 2 lệnh mẫu ở mục 6.2):
   ```bash
   sudo visudo -f /etc/sudoers.d/deploy-<project>
   ```
   ```
   deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart <project>-horizon
   deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart <project>-reverb
   deploy ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.5-fpm
   deploy ALL=(ALL) NOPASSWD: /usr/local/bin/fix-<project>-build
   ```

6. **`familiesforlife-horizon: ERROR (no such process)`** khi restart — Supervisor chưa từng có file `.conf` cho project (dễ quên nếu project được thêm CI/CD sau, không phải từ lúc setup ban đầu ở mục 6.1). Kiểm tra nhanh trước khi nghi ngờ gì khác:
   ```bash
   sudo supervisorctl status | grep <project>
   ```
   Nếu trống — tạo file theo mẫu mục 6.1 (đổi tên program + port Reverb theo bảng ở mục 11.1), rồi:
   ```bash
   sudo supervisorctl reread && sudo supervisorctl update
   ```
   Trạng thái `STARTING` ngay sau `update` là bình thường — đợi vài giây rồi `status` lại để xác nhận `RUNNING`.

> **Bài học chung:** khi 1 project được bật CI/CD *sau* khi đã tồn tại và từng deploy tay, luôn giả định 3 thứ chưa đồng bộ với `deploy` user: (a) ownership của source code, (b) sudoers cho các lệnh không tương tác, (c) Supervisor conf. Kiểm tra cả 3 trước khi push tag đầu tiên, đỡ phải debug từng lỗi một qua Actions log.

### 11.10 Certbot tự sinh bare `return 404;`/`return 301;` trong `server{}` — chặn location tự thêm sau này

Đã gặp lặp lại ở nhiều nơi (mục 9 — phpMyAdmin, và mục 11.8 ở trên): Certbot tự sinh khối port 80 kiểu:
```nginx
server {
    if ($host = www.domain) { return 301 https://$host$request_uri; }
    if ($host = domain)     { return 301 https://$host$request_uri; }
    listen 80;
    server_name domain www.domain;
    return 404; # managed by Certbot
}
```
`return 404;` này nằm **trần**, chạy ở pha *server rewrite* — **trước khi** Nginx chọn location. Nghĩa là bất kỳ `location` nào bạn thêm sau này vào đúng block đó đều bị chặn, luôn trả 404 dù URI có khớp location hay không.

**Fix:** bọc lại trong `location /`:
```nginx
    location / {
        return 404;
    }
```
rồi thêm các `location` khác cần dùng bên cạnh nó — lúc đó Nginx mới chọn location theo URI thay vì chặn hết ngay từ pha rewrite.

---

## 12. Checklist tổng hợp — Thêm 1 domain/project MỚI ổn định ngay từ đầu

Toàn bộ mục 11 là **case study xử lý phản ứng** (gặp lỗi → tra → fix) khi thêm `familiesforlife` — hầu hết lỗi đó đều lặp lại vì làm các bước không đúng thứ tự hoặc bỏ sót 1 bước. Checklist này gộp lại theo đúng thứ tự nên làm, để lần sau thêm domain/project mới lên **cùng VPS này** không phải debug lại từng lỗi một.

> Thay `<project>`/`<domain>`/`<port>` bằng tên thật. Trước khi bắt đầu, cập nhật bảng port ở mục 11.1 với port Reverb sẽ dùng — kiểm tra chưa ai chiếm.

### 12.1 Clone code — làm đúng ngay từ bước đầu, tránh cả saga ownership ở mục 11.4/11.9b#4

**Luôn clone bằng chính user `deploy`, không dùng user admin thật (`thuchoc`...) rồi chown lại sau:**
```bash
sudo mkdir -p /var/www/<project>
sudo chown deploy:www-data /var/www/<project>
sudo -u deploy git clone git@github.com:<org>/<project>.git /var/www/<project>
```
Nếu vì lý do gì đó đã clone bằng user khác — chown lại **ngay lập tức**, đừng để tới khi CI báo lỗi mới phát hiện:
```bash
sudo chown -R deploy:www-data /var/www/<project>
```

### 12.2 `.env` + storage/bootstrap dirs (mục 11.3) — làm trước `composer install`

```bash
cd /var/www/<project>
cp .env.example .env
mkdir -p storage/framework/{sessions,views,cache/data,testing}
mkdir -p storage/logs storage/app/private storage/app/public bootstrap/cache
```

### 12.3 Ownership + ACL bền vững — làm 1 lần, xong hẳn (mục 11.4)

```bash
sudo chown -R deploy:www-data /var/www/<project>
sudo find /var/www/<project> -type d -exec chmod 755 {} \;
sudo find /var/www/<project> -type f -exec chmod 644 {} \;
chmod +x /var/www/<project>/vendor/bin/* /var/www/<project>/node_modules/.bin/* /var/www/<project>/deploy.sh 2>/dev/null || true

sudo apt install -y acl   # nếu chưa có
sudo setfacl -R    -m g:www-data:rwx /var/www/<project>/storage /var/www/<project>/bootstrap/cache
sudo setfacl -R -d -m g:www-data:rwx /var/www/<project>/storage /var/www/<project>/bootstrap/cache
```

Thêm mọi user SSH thật sẽ chạy `artisan` tay vào group `www-data`, rồi **xác minh lại bằng `id`, không giả định**:
```bash
sudo usermod -aG www-data <user-ssh-thật>
```
Test ngay trong session hiện tại (không cần logout): `newgrp www-data` rồi `id` — phải thấy `www-data` trong danh sách group.

### 12.4 Cài đặt lần đầu

```bash
sudo -u deploy composer install --no-dev --optimize-autoloader --no-interaction
sudo -u deploy npm ci
npx vite build --config vite.config.backend.js    # + frontend.js nếu có
php8.5 artisan key:generate
php8.5 artisan migrate --force
php8.5 artisan storage:link
php8.5 artisan config:cache && php8.5 artisan route:cache
php8.5 artisan view:cache  && php8.5 artisan event:cache
```

### 12.5 Nginx + SSL (mục 4) — cẩn thận các bẫy Certbot đã ghi ở 11.7–11.10

Trỏ DNS trước, tạo vhost theo mẫu mục 4.2, comment tạm 4 dòng SSL trước lần cấp cert đầu (11.7), chạy Certbot, rồi **luôn `cat -n` lại vhost sau khi Certbot chạy** để phát hiện block dư/`return 404` trần chưa bọc `location` (11.8, 11.9/cũ→11.10 hiện tại).

### 12.6 Supervisor — tạo và xác nhận `RUNNING` TRƯỚC khi đụng tới CI/CD (mục 6.1, 11.2, 11.9b#6)

```bash
sudo tee /etc/supervisor/conf.d/<project>.conf > /dev/null << 'EOF'
[program:<project>-horizon]
process_name=%(program_name)s
command=/usr/bin/php8.5 /var/www/<project>/artisan horizon
directory=/var/www/<project>
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/<project>/storage/logs/horizon.log
stopwaitsecs=3600
stopsignal=SIGTERM

[program:<project>-reverb]
process_name=%(program_name)s
command=/usr/bin/php8.5 /var/www/<project>/artisan reverb:start --host=127.0.0.1 --port=<port> --no-interaction
directory=/var/www/<project>
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/<project>/storage/logs/reverb.log
EOF

sudo supervisorctl reread && sudo supervisorctl update
sleep 5 && sudo supervisorctl status | grep <project>   # phải RUNNING, không FATAL
```
Dùng `tee` heredoc — không gõ/paste tay vào `nano` (bẫy gãy dòng ở 11.2).

### 12.7 Sudoers NOPASSWD — cấp đủ TRƯỚC khi test deploy tự động (mục 6.2, 11.9b#5)

Liệt kê **đúng từng lệnh sudo mà `deploy.sh` thực sự gọi** (đọc lại script, không đoán):
```bash
sudo visudo -f /etc/sudoers.d/deploy-<project>
```
```
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart <project>-horizon
deploy ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart <project>-reverb
deploy ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.5-fpm
deploy ALL=(ALL) NOPASSWD: /usr/local/bin/fix-<project>-build
```
Lý do bắt buộc làm bước này: SSH của GitHub Actions không có tty, `sudo` thiếu `NOPASSWD` sẽ tự fail bằng `a terminal is required to authenticate` — không hiện prompt để nhập tay được.

### 12.8 `deploy.sh` — copy từ project mẫu (`familiesforlife` là bản mới nhất, có `--ref=`)

Checklist đổi tên khi copy (mục 11.1): `APP_DIR`, tên script fix-permission, tên Supervisor program, comment/log message, port Reverb trong `reverb:start`. Giữ nguyên pattern `--ref=<git-ref>` (branch/tag/commit, mặc định `main`) — cho phép cùng 1 script dùng cho cả deploy tự động (tag push) và deploy/rollback tay.

### 12.9 GitHub Actions — setup secrets & key TRƯỚC khi viết workflow (mục 7.0–7.3)

1. PAT (máy local) phải có scope **`workflow`**, không chỉ `repo` — thiếu là push `.github/workflows/*.yml` bị GitHub từ chối ngay (11.9b#1).
2. Tạo SSH key riêng cho Actions (không dùng chung với deploy key git pull):
   ```bash
   ssh-keygen -t ed25519 -C "github-actions@<domain>" -f ~/.ssh/gh_actions_<project> -N ""
   ```
3. Thêm public key vào `/home/deploy/.ssh/authorized_keys` trên VPS.
4. **Test SSH thủ công TỪ MÁY LOCAL (không phải từ 1 session đang SSH sẵn vào VPS)** trước khi động vào GitHub — nhầm chiều test là lỗi hay gặp nhất, dễ tưởng nhầm là sai key (11.9b#3):
   ```bash
   ssh -v -i ~/.ssh/gh_actions_<project> -p <ssh-port-thật> deploy@<VPS_IP> "echo ok"
   ```
   Xác nhận log có dòng `Offering public key` và `Authenticated ... using "publickey"`.
5. **Xác định đúng SSH port thật của VPS** — không phải lúc nào cũng là `22` (VPS này đang dùng port khác, xem mục 9.3). Nếu khác 22, phải có secret riêng cho port này (bước 6).
6. Thêm 4 secrets vào repo (Settings → Secrets and variables → Actions):

   | Secret | Giá trị |
   |---|---|
   | `VPS_HOST` | IP VPS |
   | `VPS_USER` | `deploy` |
   | `VPS_PORT` | port SSH thật (không hardcode `22` trong workflow) |
   | `VPS_SSH_KEY` | toàn bộ private key vừa tạo ở bước 2 |

7. Tạo GitHub Environment `production`.

### 12.10 Workflow file — trigger tag push, port lấy từ secret

```yaml
name: Deploy on Tag
on:
  push:
    tags: ['v*']
concurrency:
  group: production-deploy
  cancel-in-progress: false
jobs:
  deploy:
    name: "→ <domain> (${{ github.ref_name }})"
    runs-on: ubuntu-latest
    timeout-minutes: 20
    environment:
      name: production
      url: https://<domain>
    steps:
      - uses: appleboy/ssh-action@v1.2.0
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          port: ${{ secrets.VPS_PORT }}
          script_stop: true
          script: |
            cd /var/www/<project>
            bash deploy.sh --ref=${{ github.ref_name }}
```

### 12.11 `safe.directory` cho git (mục 11.9b#4) — chỉ cần nếu bước 12.1 không làm đúng từ đầu

Nếu đã clone bằng đúng user `deploy` ngay từ 12.1 và không có sudo/user khác đụng vào `.git` sau đó, bước này thường không cần. Nếu vẫn gặp `dubious ownership`:
```bash
sudo -u deploy git config --global --add safe.directory /var/www/<project>
```

### 12.12 Test lần đầu

```bash
git tag v1.0.0 -m "Kích hoạt CI/CD"
git push origin v1.0.0
```
Theo dõi tab **Actions**. Nếu fail, tra theo đúng thứ tự lỗi đã liệt kê ở 11.9b — thứ tự đó chính là thứ tự các bước 12.1–12.11 ở trên đã bỏ sót, làm đủ từ đầu thì thường qua thẳng không lỗi.

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
