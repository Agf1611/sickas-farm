# Panduan Deploy SICKAS FARM

Dokumen ini dipakai untuk menyiapkan aplikasi SICKAS FARM ke server staging atau produksi. Jangan jalankan deploy otomatis dari lokal; ikuti checklist dan command secara manual di server.

## 1. Kebutuhan Server

- PHP 8.2 atau lebih baru.
- Composer 2.
- MySQL 8 atau MariaDB 10.6 ke atas.
- Node.js LTS dan npm untuk build asset Vite.
- Web server Apache atau Nginx.
- SSL aktif untuk domain produksi.
- Akses terminal/SSH.
- Cron Laravel aktif untuk scheduler bila nanti dibutuhkan.
- Supervisor/systemd untuk queue worker bila `QUEUE_CONNECTION=database`.

Root web server harus diarahkan ke folder `public`, bukan root project.

Contoh struktur:

```text
/var/www/sickas-farm
|-- app
|-- bootstrap
|-- config
|-- database
|-- public   <- document root
|-- resources
|-- routes
|-- storage
`-- vendor
```

## 2. Ekstensi PHP Yang Diperlukan

Aktifkan ekstensi berikut di PHP server:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `gd`
- `intl`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql`
- `tokenizer`
- `xml`
- `zip`

Catatan:

- `intl` sudah tidak menjadi blocker tampilan karena formatter aplikasi sudah aman, tetapi tetap direkomendasikan aktif untuk kompatibilitas Laravel/Filament.
- `fileinfo`, `gd`, dan `zip` penting untuk upload gambar, PDF/Excel, dan pemrosesan file.

Cek ekstensi:

```bash
php -m
php -m | grep -E "intl|pdo_mysql|fileinfo|gd|zip"
```

## 3. Cara Upload Project Ke Server

Pilihan yang disarankan adalah Git:

```bash
cd /var/www
git clone <URL_REPOSITORY> sickas-farm
cd sickas-farm
```

Jika memakai upload ZIP:

```bash
cd /var/www
unzip sickas-farm.zip -d sickas-farm
cd sickas-farm
```

Jangan upload file `.env` lokal ke server. Buat `.env` server dari `.env.example`.

## 4. Setting File `.env`

Buat file `.env`:

```bash
cp .env.example .env
```

Minimal ubah nilai berikut:

```env
APP_NAME="SICKAS FARM"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

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

Jika staging belum memakai HTTPS, gunakan sementara:

```env
SESSION_SECURE_COOKIE=false
```

Setelah `.env` dibuat, generate app key:

```bash
php artisan key:generate
```

## 5. Install Dependency

Install dependency PHP:

```bash
composer install --no-dev --optimize-autoloader
```

Install dan build asset frontend:

```bash
npm ci
npm run build
```

Jika server produksi tidak mengizinkan Node.js, build asset di mesin CI/staging, lalu upload folder hasil build `public/build`.

## 6. Migrate Database

Pastikan database MySQL sudah dibuat dan user database punya izin yang cukup.

Jalankan migration:

```bash
php artisan migrate --force
```

Cek status:

```bash
php artisan migrate:status
```

Seeder produksi wajib dijalankan untuk membuat role, permission, dan admin awal:

```bash
php artisan db:seed --class=ProductionSeeder --force
```

Jangan jalankan `SickasFarmDemoSeeder` di produksi kecuali memang ingin mengisi data demo.

## 7. Membuat User Admin Awal

Cara utama adalah lewat `ProductionSeeder`. Sebelum menjalankan seeder, isi variabel berikut di `.env`:

```env
SICKAS_ADMIN_NAME="Admin SICKAS FARM"
SICKAS_ADMIN_EMAIL=admin@domain-anda.com
SICKAS_ADMIN_PASSWORD=password_sementara_yang_kuat
```

Lalu jalankan:

```bash
php artisan db:seed --class=ProductionSeeder --force
```

Seeder ini aman dijalankan ulang:

- Role dan permission dibuat atau disinkronkan tanpa duplikasi.
- User admin dicari berdasarkan email.
- Jika user belum ada, user dibuat dan diberi role `Super Admin`.
- Jika user sudah ada, password tidak diubah dan role `Super Admin` tetap dipastikan ada.

Jika `SICKAS_ADMIN_PASSWORD` dikosongkan, sistem akan membuat password sementara random dan menampilkannya di terminal saat seeder berjalan. Simpan password tersebut saat itu juga, lalu ganti setelah login pertama.

Alternatif manual jika ingin membuat user lewat command Filament:

```bash
php artisan make:filament-user
php artisan db:seed --class=SickasFarmRoleSeeder --force
```

Jika user admin pertama bukan email di `SICKAS_ADMIN_EMAIL` dan belum punya role, assign role Super Admin secara manual:

```bash
php artisan tinker
```

Lalu di tinker:

```php
$user = App\Models\User::where('email', 'admin@domain-anda.com')->firstOrFail();
$user->assignRole('Super Admin');
```

Keluar dari tinker:

```php
exit
```

Setelah itu login ke:

```text
https://domain-anda.com/admin
```

Setelah berhasil login:

1. Buka menu `Master Data > Pengguna & Role`.
2. Edit user admin.
3. Isi password baru yang kuat.
4. Simpan.
5. Hapus nilai `SICKAS_ADMIN_PASSWORD` dari `.env` jika sebelumnya diisi.
6. Jalankan `php artisan optimize:clear && php artisan config:cache`.

## 8. Storage Link Dan Upload File

Foto domba, nota, bukti pembelian, bukti penjualan, insiden, dan kondisi kandang disimpan di disk `public`, yaitu:

```text
storage/app/public
```

Buat symbolic link:

```bash
php artisan storage:link
```

Pastikan folder berikut writable oleh user web server:

```bash
storage
bootstrap/cache
```

Contoh Linux:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

Sesuaikan `www-data` dengan user web server, misalnya `nginx`, `apache`, atau user hosting.

## 9. Clear Dan Cache Config

Setelah semua konfigurasi benar:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:optimize
```

Jika ada perubahan `.env`, jalankan ulang:

```bash
php artisan optimize:clear
php artisan config:cache
```

## 10. Queue Worker

Karena `.env.example` memakai:

```env
QUEUE_CONNECTION=database
```

Jalankan queue worker di production agar job background tidak menumpuk:

```bash
php artisan queue:work --tries=3 --timeout=120
```

Contoh Supervisor:

```ini
[program:sickas-farm-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sickas-farm/artisan queue:work --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/sickas-farm/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart sickas-farm-worker:*
```

Jika server kecil dan belum memakai worker, bisa sementara pakai:

```env
QUEUE_CONNECTION=sync
```

Namun untuk produksi jangka panjang, `database` plus worker lebih disarankan.

## 11. Cek Aplikasi Setelah Deploy

Jalankan command berikut:

```bash
php artisan about
php artisan migrate:status
php artisan route:list
php artisan test
```

Buka di browser:

```text
https://domain-anda.com/admin
```

Checklist browser:

- Login admin berhasil.
- Dashboard terbuka.
- Menu Master Data terbuka.
- Menu Operasional Domba terbuka.
- Menu Keuangan terbuka.
- Menu Laporan terbuka.
- Create/edit data Kandang berhasil.
- Upload foto bisa dibuka dari detail data.
- Export Excel/PDF bisa diunduh.
- Format Rupiah, kg, dan tanggal tampil benar.
- Tidak ada error 500.

Cek log:

```bash
tail -n 100 storage/logs/laravel.log
```

## 12. Troubleshooting Umum

### Halaman 500 Setelah Deploy

Jalankan:

```bash
php artisan optimize:clear
tail -n 100 storage/logs/laravel.log
```

Cek `.env`:

- `APP_KEY` sudah ada.
- `APP_DEBUG=false`.
- `APP_URL` sesuai domain.
- Database MySQL benar.
- Folder `storage` dan `bootstrap/cache` writable.

### Login Gagal Atau Session Sering Keluar

Cek:

```env
APP_URL=https://domain-anda.com
SESSION_DRIVER=database
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
```

Jika belum HTTPS, set sementara:

```env
SESSION_SECURE_COOKIE=false
```

Lalu:

```bash
php artisan optimize:clear
php artisan config:cache
```

### Foto Tidak Muncul

Cek storage link:

```bash
php artisan storage:link
ls -la public/storage
```

Cek permission:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Export Excel/PDF Gagal

Cek ekstensi PHP:

```bash
php -m | grep -E "zip|gd|dom|xml|mbstring|fileinfo"
```

Cek permission folder:

```bash
storage/framework
storage/app
storage/logs
```

### Error Database

Cek koneksi:

```bash
php artisan migrate:status
```

Pastikan database, username, password, host, dan port MySQL di `.env` sesuai.

### Perubahan `.env` Tidak Terbaca

Jalankan:

```bash
php artisan optimize:clear
php artisan config:cache
```

### Role Atau Menu Tidak Sesuai

Jalankan ulang seeder role:

```bash
php artisan db:seed --class=ProductionSeeder --force
php artisan permission:cache-reset
```

Jika command `permission:cache-reset` tidak tersedia:

```bash
php artisan optimize:clear
```

## 13. Checklist Deploy Singkat

- [ ] Server memenuhi kebutuhan PHP, MySQL, Composer, Node.js, dan ekstensi PHP.
- [ ] Document root web server mengarah ke folder `public`.
- [ ] Project sudah diupload atau di-clone.
- [ ] `.env` sudah dibuat dari `.env.example`.
- [ ] `APP_ENV=production`.
- [ ] `APP_DEBUG=false`.
- [ ] `APP_URL` sesuai domain.
- [ ] Database MySQL, user, dan password sudah benar.
- [ ] `composer install --no-dev --optimize-autoloader` berhasil.
- [ ] `npm ci && npm run build` berhasil.
- [ ] `php artisan key:generate` sudah dijalankan.
- [ ] `php artisan migrate --force` berhasil.
- [ ] `php artisan db:seed --class=ProductionSeeder --force` berhasil.
- [ ] User admin awal sudah dibuat dan diberi role `Super Admin`.
- [ ] `php artisan storage:link` berhasil.
- [ ] Folder `storage` dan `bootstrap/cache` writable.
- [ ] Queue worker aktif jika memakai `QUEUE_CONNECTION=database`.
- [ ] `php artisan optimize:clear`, `config:cache`, `route:cache`, `view:cache`, dan `filament:optimize` berhasil.
- [ ] Login `/admin` berhasil.
- [ ] Semua menu utama terbuka tanpa error 500.
- [ ] Upload foto dan export laporan diuji.
- [ ] Log Laravel tidak menunjukkan error baru.
