# PWA Builder Package (`cmdimu/pwa-builder`)

Package generator dan manajemen **Progressive Web App (PWA)** lengkap dan otomatis untuk **Laravel**, **CodeIgniter 4 (CI4)**, dan **CodeIgniter 3 (CI3)**.

Package ini memungkinkan website PHP Anda diubah menjadi aplikasi Progressive Web App (bisa di-install di Android, iOS, Windows, dan MacOS) dengan **satu kali upload logo**, konfigurasi instan melalui Web Dashboard, dan integrasi view hanya dengan 2 baris kode.

---

## 🚀 Fitur Utama

- **Multi-Framework**: Mendukung penuh **Laravel (Auto-discovery & Blade Directives)**, **CodeIgniter 4**, dan **CodeIgniter 3**.
- **Auto Route Registration**: Rute dashboard, API, manifest, dan service worker otomatis terdaftar tanpa konfigurasi rumit.
- **Sekali Upload Logo, Semua Icon Ter-generate Otomatis**:
  - Cukup unggah 1 logo master (PNG/JPEG/WebP).
  - Mengenerate seluruh ukuran icon standar PWA: `72x72`, `96x96`, `128x128`, `144x144`, `152x152`, `192x192`, `384x384`, `512x512`.
  - **Maskable Icon Generator**: Otomatis membuat icon *adaptive maskable* (512x512) dengan *safe-zone 80%* dan background padding sesuai standar Google PWA.
  - **Apple Touch Icon** (`180x180`) untuk Safari iOS.
  - **Favicon Multi-Resolusi** (`48x48`, `32x32`, `16x16`, `favicon.ico`).
- **Self-Folder Creation**: Otomatis membuat folder yang dibutuhkan (`public/pwa/icons/`, `storage/pwa/`, dll) secara rekursif jika belum ada di project.
- **Autentikasi Fleksibel & Terproteksi (Login Ready)**:
  - **Auto-Detect**: Langsung terintegrasi otomatis jika pengguna sudah login di Laravel (`Auth::check()`), CI4 (Shield atau Session), atau CI3 (`$ci->session`).
  - **Built-in Login Page**: Dilengkapi layar login siap pakai `/pwa-builder/login` untuk aplikasi yang belum memiliki auth atau jika ingin diproteksi kredensial khusus.
  - **Middleware Kustom**: Di Laravel dapat menambahkan middleware apapun via `.env` (contoh: `PWA_MIDDLEWARE=web,auth`).
- **Service Worker & Offline Fallback Pintar**:
  - 3 Pilihan Strategi Cache: **Network First** (disarankan untuk aplikasi dinamis/database), **Stale-While-Revalidate**, dan **Cache First**.
  - Dilengkapi halaman **Offline Fallback** (`offline.html`) otomatis yang responsif, dilengkapi tombol *"Coba Lagi"* dan sensor deteksi otomatis saat jaringan internet kembali online.
  - Versioning cache otomatis (`cache_version`) untuk memastikan aset ter-refresh saat aplikasi diupdate.
- **Dashboard UI Modern & Live Smartphone Simulator**:
  - UI responsif berbasis Bootstrap 5.
  - Preview interaktif smartphone secara *real-time* (mockup install banner, icon home screen, status bar theme color, dan splash background).

---

## 🖥️ Persyaratan Server (Server Requirements)

Sebelum menginstal package ini, pastikan server atau lingkungan hosting Anda memenuhi kriteria berikut:

| Komponen | Persyaratan Minimum | Rekomendasi / Keterangan |
|---|---|---|
| **PHP Version** | `>= 7.4` | Mendukung **PHP 8.0, 8.1, 8.2, 8.3, 8.4+** |
| **PHP GD Extension** (`ext-gd`) | **Wajib Aktif** | Digunakan untuk resize gambar, mempertahankan transparansi PNG, dan membuat adaptive maskable icon |
| **PHP JSON Extension** (`ext-json`) | **Wajib Aktif** | Digunakan untuk memproses `manifest.json` dan file konfigurasi |
| **PHP Fileinfo** (`ext-fileinfo`) | **Wajib Aktif** | Validasi MIME-type file logo yang diunggah |
| **Web Server** | Apache / Nginx / LiteSpeed | Modul `mod_rewrite` (Apache) atau try_files (Nginx) aktif |
| **Koneksi Jaringan (SSL)** | **HTTPS / SSL** *(Produksi)* | **Wajib oleh standar browser** untuk mendaftarkan Service Worker di domain publik. Di lingkungan lokal (`localhost`, `127.0.0.1`, Laragon self-signed) diizinkan tanpa SSL |
| **Folder Permission** | `chmod 0755` / Writeable | Server membutuhkan izin tulis ke folder `public/` (untuk menerbitkan `manifest.json`, `sw.js`, `offline.html`, dan `public/pwa/icons/`) serta folder storage framework |

---

## 📦 Instalasi

### 1. Via Composer (Packagist / VCS)

Jalankan perintah berikut di root project aplikasi Anda:

```bash
composer require cmdimu/pwa-builder
```

### 2. Instalasi Lokal / Path Repository

Jika package diletakkan di dalam folder internal project (misal: `packages/pwa-builder`), tambahkan konfigurasi berikut ke `composer.json` project utama:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/pwa-builder",
        "options": {
            "symlink": true
        }
    }
],
"require": {
    "cmdimu/pwa-builder": "*@dev"
}
```

Kemudian jalankan:
```bash
composer update cmdimu/pwa-builder
```

---

## ⚙️ Konfigurasi Environment (`.env`)

Tambahkan variabel berikut ke file `.env` project Anda (semua opsional dengan nilai default yang aman):

```env
# URL prefix untuk dashboard & rute PWA Builder (default: pwa-builder)
PWA_PREFIX=pwa-builder

# ==========================================
# PENGATURAN AUTENTIKASI DASHBOARD
# ==========================================
# Driver auth: auto, builtin, framework, atau none (default: auto)
# auto      : Gunakan session framework jika user sudah login, jika belum gunakan built-in login
# builtin   : Selalu gunakan halaman login khusus PWA Builder
# framework : Hanya izinkan jika user login lewat auth framework (Laravel Auth / CI Session)
# none      : Matikan proteksi login (hanya disarankan di local development)
PWA_AUTH_DRIVER=auto

# Kredensial untuk Built-in Login Dashboard (/pwa-builder/login)
PWA_AUTH_USERNAME=admin
PWA_AUTH_PASSWORD=PasswordAdminSuperAman123!

# (Khusus Laravel) Tambahan middleware untuk rute dashboard (default: web)
# Contoh jika ingin mewajibkan auth bawaan Laravel:
# PWA_MIDDLEWARE=web,auth
```

---

## 🛠️ Setup per Framework

### 1. Laravel (Laravel 8, 9, 10, 11, 12+)

#### A. Registrasi Otomatis & Perintah CLI
Package otomatis terdeteksi via **Laravel Package Discovery**. Anda dapat menjalankan perintah installer instan:

```bash
php artisan pwa:install
```
> Perintah ini otomatis menyiapkan folder public, membuat file aset PWA awal, dan akan **menanyakan apakah Anda ingin menyisipkan `@pwaHeader` dan `@pwaRegister` langsung ke file layout Blade Anda**.

#### B. Pemasangan Manual ke Blade Layout (Tinggal Pasang / Copas)
Jika Anda lebih suka memasang secara manual, Anda **tidak perlu mengetik tag HTML yang panjang**. Cukup **copas 2 baris** berikut ke file layout utama Anda (misalnya `resources/views/layouts/app.blade.php`):

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }}</title>

    {{-- 1. Copas di dalam tag <head> --}}
    @pwaHeader
</head>
<body>
    @yield('content')

    {{-- 2. Copas sebelum penutup tag </body> --}}
    @pwaRegister
</body>
</html>
```
*(Kode ini juga dapat Anda salin dengan 1-klik melalui tombol **"Copy"** di Tab "Panduan Integrasi" pada Web Dashboard).*

#### C. Publish Tampilan Dashboard (Opsional)
Jika ingin memodifikasi tampilan dashboard secara langsung di project Laravel Anda:
```bash
php artisan vendor:publish --tag=pwa-builder-views
```

#### D. Akses Dashboard
Buka di browser:
```
http://domain-anda.test/pwa-builder
```
*(Jika belum login, sistem akan otomatis mengarahkan ke `/pwa-builder/login`)*.

---

### 2. CodeIgniter 4 (CI4)

#### A. Registrasi Rute
CI4 versi 4.4+ otomatis mengenali rute paket melalui namespace discovery. Jika Anda ingin mendaftarkan secara manual atau pada CI4 versi sebelumnya, tambahkan 1 baris berikut di file `app/Config/Routes.php`:

```php
\PwaBuilder\Adapters\Ci4Routes::register($routes);
```

#### B. Integrasi ke View Layout (Tinggal Pasang / Copas)
Buka layout utama CI4 Anda (misalnya `app/Views/layouts/main.php`), cukup copas 2 baris berikut:

```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aplikasi Saya</title>

    <!-- 1. Tag Meta & Manifest PWA -->
    <?= pwa_header() ?>
</head>
<body>
    <?= $this->renderSection('content') ?>

    <!-- 2. Registrasi Service Worker -->
    <?= pwa_register() ?>
</body>
</html>
```

---

### 3. CodeIgniter 3 (CI3)

#### A. Aktifkan Composer Autoload
Pastikan autoloader Composer aktif di `application/config/config.php`:
```php
$config['composer_autoload'] = 'vendor/autoload.php';
```

#### B. Pasang Controller
Salin file controller dari `vendor/cmdimu/pwa-builder/src/ci3/controllers/PwaBuilderController.php` ke folder controller CI3 Anda:
`application/controllers/PwaBuilderController.php`.

Atau tambahkan rute di `application/config/routes.php`:
```php
$route['pwa-builder'] = 'PwaBuilderController/index';
$route['pwa-builder/(:any)'] = 'PwaBuilderController/$1';
$route['manifest.json'] = 'PwaBuilderController/manifest';
$route['sw.js'] = 'PwaBuilderController/sw';
$route['offline.html'] = 'PwaBuilderController/offline';
```

#### C. Integrasi ke Header & Footer View (Tinggal Pasang / Copas)
Di file `header.php`:
```html
<head>
    <?= pwa_header() ?>
</head>
```

Di file `footer.php`:
```html
    <?= pwa_register() ?>
</body>
</html>
```

---

## 🎨 Spesifikasi Icon yang Dibuat Otomatis

Saat Anda mengunggah 1 file gambar logo master di Dashboard, package otomatis menghasilkan format dan ukuran berikut ke folder `public/pwa/icons/`:

| Nama File | Ukuran | Peruntukan & Spesifikasi |
|---|---|---|
| `icon-72x72.png` | 72 × 72 px | Android layar low-DPI |
| `icon-96x96.png` | 96 × 96 px | Android launcher standar |
| `icon-128x128.png` | 128 × 128 px | Chrome Web Store / App Drawer |
| `icon-144x144.png` | 144 × 144 px | Windows Metro Tile |
| `icon-152x152.png` | 152 × 152 px | iPad / iOS Retina |
| `icon-192x192.png` | 192 × 192 px | **Standard PWA Home Screen** (Wajib PWA) |
| `icon-384x384.png` | 384 × 384 px | Layar ponsel Ultra-HD / XHDPI |
| `icon-512x512.png` | 512 × 512 px | **Standard PWA Splash Screen & Install Dialog** |
| `icon-maskable-512x512.png` | 512 × 512 px | **Adaptive Maskable Icon** (*Safe Zone 80%* agar icon tidak terpotong bentuk lingkaran/squircle di Android) |
| `apple-touch-icon.png` | 180 × 180 px | Apple iOS Safari Add to Home Screen |
| `favicon-48x48.png` | 48 × 48 px | Favicon browser High-DPI |
| `favicon-32x32.png` | 32 × 32 px | Favicon browser desktop standar |
| `favicon-16x16.png` | 16 × 16 px | Favicon browser tab kecil |
| `favicon.ico` | 32 × 32 px | Fallback favicon di root public |

---

## 📂 Struktur File Package

```text
pwa-builder/
├── src/
│   ├── Adapters/
│   │   ├── LaravelServiceProvider.php   # Auto-discovery, Routes, Blade Directives
│   │   └── Ci4Routes.php                # CI4 Route Helper
│   ├── Config/
│   │   └── Routes.php                   # CI4 Auto Route Discovery
│   ├── Core/
│   │   ├── AuthResolver.php             # Multi-framework & Built-in Auth Resolver
│   │   ├── ConfigResolver.php           # Public/Storage Path, Env, & CSRF Resolver
│   │   ├── IconGenerator.php            # GD-based Automatic Icon Resizer & Maskable Generator
│   │   └── PwaManager.php               # Manifest, Service Worker, & Offline Generator
│   ├── Facades/
│   │   └── PwaBuilder.php               # Laravel Facade Accessor
│   ├── Helpers/
│   │   └── pwa_helper.php               # Universal Global Helpers (pwa_header, pwa_register)
│   ├── Http/
│   │   └── Controllers/
│   │       └── PwaController.php        # Dashboard, Auth, Upload & Save Controller
│   ├── Views/
│   │   ├── index.php                    # Dashboard UI + Live Mobile Phone Simulator
│   │   └── login.php                    # Built-in Login UI
│   └── ci3/
│       └── controllers/
│           └── PwaBuilderController.php # CI3 Controller Bridge
├── composer.json
├── LICENSE
└── README.md
```

---

## 📄 Lisensi

Package ini dirilis di bawah lisensi [MIT](LICENSE). Silakan gunakan dan kembangkan secara bebas untuk project komersial maupun open-source.

