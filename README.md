# SICKAS FARM

SICKAS FARM adalah aplikasi manajemen peternakan berbasis Laravel dan Filament. Aplikasi ini dipakai untuk mengelola data ternak, kandang, pembelian, penimbangan, penjualan, biaya operasional, laporan, foto, QR code, export Excel, dan PDF.

Panel admin tersedia di:

```text
/admin
```

Jika membuka root domain (`/`), aplikasi langsung mengarahkan ke halaman login admin:

```text
/admin/login
```

## Syarat Menjalankan Aplikasi

### Server Produksi

- PHP 8.2 atau lebih baru.
- Composer 2.
- MySQL 8 atau MariaDB 10.6 ke atas.
- Node.js 20.19 atau lebih baru, disarankan Node.js 22 LTS.
- npm.
- Web server Apache atau Nginx.
- SSL aktif untuk production.
- Akses SSH ke server.
- GitHub repository untuk menyimpan source code.
- Cron Laravel jika scheduler dipakai.
- Supervisor atau systemd jika `QUEUE_CONNECTION=database`.

### Ekstensi PHP

Aktifkan ekstensi berikut:

```text
bcmath
ctype
curl
dom
fileinfo
gd
intl
json
mbstring
openssl
pdo
pdo_mysql
tokenizer
xml
zip
```

Root web server harus diarahkan ke folder `public`, bukan root project.

## Install Lokal

Clone repository:

```bash
git clone <URL_REPOSITORY> sickas-farm
cd sickas-farm
```

Install dependency:

```bash
composer install
npm install
```

Buat file environment:

```bash
cp .env.example .env
php artisan key:generate
```

Atur database di `.env`, lalu jalankan:

```bash
php artisan migrate
php artisan db:seed --class=ProductionSeeder
npm run build
```

Jalankan aplikasi lokal:

```bash
php artisan serve
npm run dev
```

Login ke `/admin` memakai user admin yang dibuat dari `ProductionSeeder`. Isi variabel ini di `.env` sebelum seed jika ingin menentukan akun admin sendiri:

```env
SICKAS_ADMIN_NAME="Admin SICKAS FARM"
SICKAS_ADMIN_EMAIL=admin@domain-anda.com
SICKAS_ADMIN_PASSWORD=password_yang_kuat
```

## Membuat Repository GitHub

Jika repository GitHub belum dibuat:

```bash
git add .
git commit -m "Initial SICKAS FARM project"
git branch -M main
git remote add origin https://github.com/<owner>/<repo>.git
git push -u origin main
```

Jangan commit file `.env`, `vendor`, `node_modules`, `public/build`, atau file upload di `storage`.

## Deploy Otomatis Dengan GitHub Actions

Repository ini sudah menyiapkan workflow:

```text
.github/workflows/deploy.yml
```

Workflow berjalan saat ada push ke branch `main` atau dijalankan manual dari tab GitHub Actions.
Workflow juga berjalan untuk branch `master`, sehingga repository lama yang belum memakai `main` tetap bisa langsung deploy.

### Secrets GitHub Yang Wajib Diisi

Buka GitHub repository, lalu masuk ke `Settings > Secrets and variables > Actions > New repository secret`.

Isi secret berikut:

```text
DEPLOY_HOST        IP/domain server
DEPLOY_USER        user SSH server
DEPLOY_PATH        path project di server, contoh /var/www/sickas-farm
SSH_PRIVATE_KEY    private key SSH untuk login ke server
```

Isi secret database berikut agar workflow bisa membuat `.env` otomatis pada deploy pertama:

```text
APP_URL            URL aplikasi, contoh https://domain-anda.com
DB_DATABASE        nama database
DB_USERNAME        username database
DB_PASSWORD        password database
```

Opsional:

```text
DEPLOY_PORT        port SSH, default 22
DB_CONNECTION      default mysql
DB_HOST            default 127.0.0.1
DB_PORT            default 3306
SICKAS_ADMIN_NAME  default Admin SICKAS FARM
SICKAS_ADMIN_EMAIL email admin awal
SICKAS_ADMIN_PASSWORD password admin awal; jika kosong, sistem membuat password random saat seeder
```

Server harus sudah memiliki folder target dan user `DEPLOY_USER` harus punya izin tulis ke folder tersebut.

### Persiapan Server Pertama Kali

Login ke server:

```bash
ssh user@server
```

Buat folder aplikasi:

```bash
sudo mkdir -p /var/www/sickas-farm
sudo chown -R user:www-data /var/www/sickas-farm
```

Pada deploy pertama, workflow akan mengirim file ke server. Jika `.env` belum ada, workflow akan berhenti setelah sync file dengan pesan:

```text
Files synced. Create .env on the server or set APP_URL, DB_DATABASE, DB_USERNAME, and DB_PASSWORD secrets, then rerun this workflow.
```

Jika secrets `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sudah diisi, workflow akan membuat `.env` otomatis. Jika ingin membuat manual, setelah file terkirim jalankan:

```bash
cd /var/www/sickas-farm
cp .env.example .env
php artisan key:generate
```

Edit `.env` production:

```env
APP_NAME="SICKAS FARM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sickas_farm
DB_USERNAME=sickas_user
DB_PASSWORD=password_database_yang_kuat

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
SESSION_SECURE_COOKIE=true

SICKAS_ADMIN_NAME="Admin SICKAS FARM"
SICKAS_ADMIN_EMAIL=admin@domain-anda.com
SICKAS_ADMIN_PASSWORD=password_sementara_yang_kuat
```

Jalankan setup awal:

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

Atau rerun workflow dari tab GitHub Actions agar migration, seeder, storage link, dan cache dijalankan otomatis.

Setelah admin berhasil login, ganti password admin dan hapus `SICKAS_ADMIN_PASSWORD` dari `.env`.

### Contoh Secrets Untuk aaPanel

Jika aaPanel membuat site di `/www/wwwroot/sickasfarm` dan domain memakai port khusus:

```text
DEPLOY_HOST=192.168.1.8
DEPLOY_USER=agf16
DEPLOY_PATH=/www/wwwroot/sickasfarm
DEPLOY_PORT=22
APP_URL=http://192.168.1.8:1216
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_aapanel
DB_USERNAME=user_database_aapanel
DB_PASSWORD=password_database_aapanel
SICKAS_ADMIN_EMAIL=admin@domain-anda.com
```

Di aaPanel, pastikan:

- Site root mengarah ke folder `public`.
- URL rewrite Laravel aktif:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Deploy Manual Dari Server

Jika ingin deploy manual tanpa GitHub Actions, gunakan panduan lengkap di:

```text
docs/deployment.md
```

## Verifikasi Setelah Deploy

Jalankan di server:

```bash
php artisan about
php artisan migrate:status
php artisan route:list
```

Buka:

```text
https://domain-anda.com/admin
```

Pastikan:

- Login admin berhasil.
- Dashboard terbuka.
- Menu master data, operasional, keuangan, dan laporan dapat diakses.
- Upload foto tampil melalui `/storage`.
- Export Excel/PDF berhasil.
- Tidak ada error baru di `storage/logs/laravel.log`.

## Perintah Penting

Clear cache:

```bash
php artisan optimize:clear
```

Rebuild cache production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

Queue worker:

```bash
php artisan queue:work --tries=3 --timeout=120
```

Test:

```bash
php artisan test
```
