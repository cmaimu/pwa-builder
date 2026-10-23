<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWA Builder Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php if (!empty($csrf['header']) && !empty($csrf['value'])): ?>
    <meta name="csrf-token-name" content="<?= htmlspecialchars($csrf['header']) ?>">
    <meta name="csrf-token-value" content="<?= htmlspecialchars($csrf['value']) ?>">
    <?php endif; ?>
    <style>
        body { background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; }
        .card-custom { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .nav-pills .nav-link { font-weight: 600; color: #64748b; border-radius: 8px; padding: 10px 18px; }
        .nav-pills .nav-link.active { background-color: #2563eb; color: #fff; }
        .form-label { font-weight: 600; font-size: 0.875rem; color: #334155; }
        .phone-mockup {
            width: 280px;
            height: 540px;
            border-radius: 36px;
            border: 10px solid #1e293b;
            background: #ffffff;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
        }
        .phone-notch {
            width: 120px;
            height: 18px;
            background: #1e293b;
            border-radius: 0 0 12px 12px;
            margin: 0 auto;
        }
        .icon-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .icon-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.06);
        }
        .color-picker-box {
            width: 44px;
            height: 38px;
            padding: 2px;
            border-radius: 6px;
        }
        .code-block {
            background: #1e293b;
            color: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            position: relative;
            overflow-x: auto;
        }
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
        }
        .toast-container {
            z-index: 1080;
        }
    </style>
</head>
<body class="p-3 p-md-4">
<div class="container-fluid" style="max-width: 1280px;">

    <!-- Top Navigation Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4 pb-3 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-2" style="width: 44px; height: 44px;">
                <i class="bi bi-phone-vibrate-fill fs-4"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold text-dark">PWA Builder</h4>
                <small class="text-muted">Progressive Web App Generator & Management Dashboard</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button id="btnGenerateAssetsTop" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-arrow-repeat me-1"></i> Re-generate Asset
            </button>
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-person-circle text-primary me-1"></i><?= htmlspecialchars($userName ?? 'Admin') ?>
            </span>
            <a href="<?= '/' . trim($routePrefix, '/') ?>/logout" class="btn btn-outline-danger btn-sm" title="Keluar">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>

    <!-- Status Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Status PWA</span>
                    <i class="bi bi-patch-check-fill fs-5 <?= $status['is_ready'] ? 'text-success' : 'text-warning' ?>"></i>
                </div>
                <h5 class="fw-bold mt-2 mb-0 <?= $status['is_ready'] ? 'text-success' : 'text-warning' ?>">
                    <?= $status['is_ready'] ? 'Siap Di-install' : 'Belum Lengkap' ?>
                </h5>
                <small class="text-muted mt-1"><?= $status['is_ready'] ? 'PWA memenuhi syarat browser' : 'Upload logo untuk kesiapan optimal' ?></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Manifest File</span>
                    <i class="bi <?= $status['manifest'] ? 'bi-filetype-json text-success' : 'bi-x-circle text-danger' ?> fs-5"></i>
                </div>
                <h5 class="fw-bold mt-2 mb-0 <?= $status['manifest'] ? 'text-dark' : 'text-danger' ?>">
                    <?= $status['manifest'] ? 'manifest.json' : 'Belum Dibuat' ?>
                </h5>
                <small class="text-muted mt-1"><a href="/manifest.json" target="_blank" class="text-decoration-none">Lihat File <i class="bi bi-box-arrow-up-right"></i></a></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Service Worker</span>
                    <i class="bi <?= $status['service_worker'] ? 'bi-cpu-fill text-success' : 'bi-x-circle text-danger' ?> fs-5"></i>
                </div>
                <h5 class="fw-bold mt-2 mb-0 <?= $status['service_worker'] ? 'text-dark' : 'text-danger' ?>">
                    <?= $status['service_worker'] ? 'sw.js Aktif' : 'Belum Dibuat' ?>
                </h5>
                <small class="text-muted mt-1"><a href="/sw.js" target="_blank" class="text-decoration-none">Lihat Worker <i class="bi bi-box-arrow-up-right"></i></a></small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-custom p-3 bg-white h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold">Icon PWA</span>
                    <i class="bi bi-images fs-5 <?= $status['icons_ready'] ? 'text-success' : 'text-secondary' ?>"></i>
                </div>
                <h5 class="fw-bold mt-2 mb-0 text-dark">
                    <?= $status['icons_count'] ?> / <?= $status['icons_total'] ?> Ukuran
                </h5>
                <small class="text-muted mt-1"><?= $status['icons_ready'] ? 'Resolusi lengkap tersedia' : 'Unggah 1 logo untuk semua' ?></small>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row g-4">
        <!-- Left Side: Tabs & Configuration Form -->
        <div class="col-lg-8">
            <div class="card card-custom bg-white p-4">
                <!-- Tab Headers -->
                <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3" id="pwaTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="settings-tab" data-bs-toggle="pill" data-bs-target="#tab-settings" type="button" role="tab">
                            <i class="bi bi-sliders me-1"></i> Pengaturan Aplikasi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="logo-tab" data-bs-toggle="pill" data-bs-target="#tab-logo" type="button" role="tab">
                            <i class="bi bi-image me-1"></i> Upload Logo & Icons
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sw-tab" data-bs-toggle="pill" data-bs-target="#tab-sw" type="button" role="tab">
                            <i class="bi bi-wifi-off me-1"></i> Cache & Offline Mode
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="guide-tab" data-bs-toggle="pill" data-bs-target="#tab-guide" type="button" role="tab">
                            <i class="bi bi-code-slash me-1"></i> Panduan Integrasi
                        </button>
                    </li>
                </ul>

                <!-- Tab Contents -->
                <div class="tab-content" id="pwaTabContent">
                    
                    <!-- TAB 1: General Settings -->
                    <div class="tab-pane fade show active" id="tab-settings" role="tabpanel">
                        <form id="formSettings">
                            <?php if (!empty($csrf['name']) && !empty($csrf['value'])): ?>
                            <input type="hidden" name="<?= htmlspecialchars($csrf['name']) ?>" value="<?= htmlspecialchars($csrf['value']) ?>">
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Nama Aplikasi (Full Name) <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="inputName" class="form-control" required value="<?= htmlspecialchars($config['name'] ?? '') ?>" placeholder="Contoh: Toko Online Modern">
                                    <div class="form-text small">Nama lengkap yang muncul saat instalasi dan splash screen.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nama Pendek (Short Name) <span class="text-danger">*</span></label>
                                    <input type="text" name="short_name" id="inputShortName" class="form-control" required maxlength="15" value="<?= htmlspecialchars($config['short_name'] ?? '') ?>" placeholder="Contoh: TokoKu">
                                    <div class="form-text small">Nama di bawah icon layar utama hp (max ~12 huruf).</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Deskripsi Aplikasi</label>
                                    <textarea name="description" class="form-control" rows="2" placeholder="Deskripsi singkat aplikasi PWA Anda"><?= htmlspecialchars($config['description'] ?? '') ?></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Start URL</label>
                                    <input type="text" name="start_url" class="form-control" value="<?= htmlspecialchars($config['start_url'] ?? '/') ?>" placeholder="/?utm_source=pwa">
                                    <div class="form-text small">Halaman awal saat aplikasi PWA dibuka dari icon hp.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Scope</label>
                                    <input type="text" name="scope" class="form-control" value="<?= htmlspecialchars($config['scope'] ?? '/') ?>" placeholder="/">
                                    <div class="form-text small">Cakupan navigasi aplikasi yang diizinkan (default: /).</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mode Tampilan (Display Mode)</label>
                                    <select name="display" id="selectDisplay" class="form-select">
                                        <option value="standalone" <?= ($config['display'] ?? '') === 'standalone' ? 'selected' : '' ?>>Standalone (Menyerupai Native App - Populer)</option>
                                        <option value="fullscreen" <?= ($config['display'] ?? '') === 'fullscreen' ? 'selected' : '' ?>>Fullscreen (Tanpa Status Bar / Layar Penuh)</option>
                                        <option value="minimal-ui" <?= ($config['display'] ?? '') === 'minimal-ui' ? 'selected' : '' ?>>Minimal UI (Navigasi Minimal Browser)</option>
                                        <option value="browser" <?= ($config['display'] ?? '') === 'browser' ? 'selected' : '' ?>>Browser (Tab Standar)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Orientasi Layar (Orientation)</label>
                                    <select name="orientation" class="form-select">
                                        <option value="portrait-primary" <?= ($config['orientation'] ?? '') === 'portrait-primary' ? 'selected' : '' ?>>Portrait (Vertikal - HP Standar)</option>
                                        <option value="landscape" <?= ($config['orientation'] ?? '') === 'landscape' ? 'selected' : '' ?>>Landscape (Horizontal)</option>
                                        <option value="any" <?= ($config['orientation'] ?? '') === 'any' ? 'selected' : '' ?>>Any (Bebas / Otomatis Rotate)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Warna Tema (Theme Color)</label>
                                    <div class="input-group">
                                        <input type="color" id="pickerThemeColor" class="form-control color-picker-box flex-grow-0" value="<?= htmlspecialchars($config['theme_color'] ?? '#2563eb') ?>">
                                        <input type="text" name="theme_color" id="inputThemeColor" class="form-control" value="<?= htmlspecialchars($config['theme_color'] ?? '#2563eb') ?>">
                                    </div>
                                    <div class="form-text small">Warna toolbar atas browser / status bar Android.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Warna Latar (Background Color)</label>
                                    <div class="input-group">
                                        <input type="color" id="pickerBgColor" class="form-control color-picker-box flex-grow-0" value="<?= htmlspecialchars($config['background_color'] ?? '#ffffff') ?>">
                                        <input type="text" name="background_color" id="inputBgColor" class="form-control" value="<?= htmlspecialchars($config['background_color'] ?? '#ffffff') ?>">
                                    </div>
                                    <div class="form-text small">Warna splash screen sebelum halaman web selesai dimuat.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">iOS Status Bar Style</label>
                                    <select name="status_bar" class="form-select">
                                        <option value="default" <?= ($config['status_bar'] ?? '') === 'default' ? 'selected' : '' ?>>Default (Sesuai Sistem)</option>
                                        <option value="black" <?= ($config['status_bar'] ?? '') === 'black' ? 'selected' : '' ?>>Black (Gelap)</option>
                                        <option value="black-translucent" <?= ($config['status_bar'] ?? '') === 'black-translucent' ? 'selected' : '' ?>>Black Translucent (Transparan)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSaveSettings">
                                    <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan & Generate
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 2: Upload Logo & Icons -->
                    <div class="tab-pane fade" id="tab-logo" role="tabpanel">
                        <div class="p-4 bg-light rounded-3 border mb-4">
                            <h6 class="fw-bold mb-1"><i class="bi bi-cloud-arrow-up-fill text-primary me-2"></i>Upload Sekali, Generate Semua Resolusi</h6>
                            <p class="small text-muted mb-3">Unggah 1 file master logo (resolusi minimal 512x512 PNG/JPEG transparan direkomendasikan). Sistem otomatis men-generate seluruh ukuran icon standar PWA, favicon, dan Apple Touch Icon.</p>
                            
                            <form id="formLogoUpload" enctype="multipart/form-data">
                                <?php if (!empty($csrf['name']) && !empty($csrf['value'])): ?>
                                <input type="hidden" name="<?= htmlspecialchars($csrf['name']) ?>" value="<?= htmlspecialchars($csrf['value']) ?>">
                                <?php endif; ?>

                                <div class="row g-3 align-items-center">
                                    <div class="col-md-8">
                                        <input type="file" name="logo" id="inputLogoFile" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary w-100 fw-semibold" id="btnUploadLogo">
                                            <i class="bi bi-magic me-1"></i> Generate Icon PWA
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Icon Gallery -->
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-bold">Daftar Resolusi Icon PWA (<?= count($icons) ?>)</span>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeleteIcons">
                                <i class="bi bi-trash3 me-1"></i> Reset Icon
                            </button>
                        </div>

                        <div class="row g-2" id="iconGallery">
                            <?php foreach ($icons as $icon): ?>
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="card icon-card p-2 text-center h-100 bg-white">
                                    <div class="d-flex align-items-center justify-content-center bg-light rounded p-2 mb-2" style="height: 80px;">
                                        <?php if ($icon['exists']): ?>
                                            <img src="<?= $icon['url'] ?>" alt="<?= htmlspecialchars($icon['name']) ?>" style="max-height: 64px; max-width: 64px; object-fit: contain;">
                                        <?php else: ?>
                                            <i class="bi bi-image text-muted fs-2"></i>
                                        <?php endif; ?>
                                    </div>
                                    <small class="fw-bold text-truncate d-block mb-1" title="<?= htmlspecialchars($icon['name']) ?>">
                                        <?= htmlspecialchars($icon['name']) ?>
                                    </small>
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <span class="badge bg-light text-secondary border" style="font-size: 10px;"><?= $icon['size'] ?></span>
                                        <?php if ($icon['purpose'] === 'maskable'): ?>
                                        <span class="badge bg-warning text-dark" style="font-size: 10px;">Maskable</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- TAB 3: Cache & Offline Mode -->
                    <div class="tab-pane fade" id="tab-sw" role="tabpanel">
                        <form id="formSwSettings">
                            <?php if (!empty($csrf['name']) && !empty($csrf['value'])): ?>
                            <input type="hidden" name="<?= htmlspecialchars($csrf['name']) ?>" value="<?= htmlspecialchars($csrf['value']) ?>">
                            <?php endif; ?>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Strategi Cache Service Worker</label>
                                    <select name="cache_strategy" class="form-select">
                                        <option value="network-first" <?= ($config['cache_strategy'] ?? '') === 'network-first' ? 'selected' : '' ?>>Network First (Ambil data terbaru dari server, fallback cache)</option>
                                        <option value="stale-while-revalidate" <?= ($config['cache_strategy'] ?? '') === 'stale-while-revalidate' ? 'selected' : '' ?>>Stale-While-Revalidate (Tampilkan cache langsung, update background)</option>
                                        <option value="cache-first" <?= ($config['cache_strategy'] ?? '') === 'cache-first' ? 'selected' : '' ?>>Cache First (Utamakan cache, hemat kuota)</option>
                                    </select>
                                    <div class="form-text small">Untuk aplikasi database/transaksional, <strong>Network First</strong> sangat disarankan.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Versi Cache</label>
                                    <input type="text" name="cache_version" class="form-control" value="<?= htmlspecialchars($config['cache_version'] ?? 'v1.0.0') ?>" placeholder="v1.0.0">
                                    <div class="form-text small">Ubah versi ini saat Anda merilis update website agar cache lama pengguna otomatis dibersihkan.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Judul Halaman Offline</label>
                                    <input type="text" name="offline_title" class="form-control" value="<?= htmlspecialchars($config['offline_title'] ?? 'Koneksi Terputus') ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Pesan Halaman Offline</label>
                                    <textarea name="offline_message" class="form-control" rows="2"><?= htmlspecialchars($config['offline_message'] ?? '') ?></textarea>
                                    <div class="form-text small">Halaman ini otomatis muncul jika user membuka website saat internet mati.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">URL Tambahan untuk Pre-cache (1 URL per baris)</label>
                                    <textarea name="pre_cache_urls" class="form-control font-monospace" rows="3" placeholder="/css/app.css&#10;/js/app.js"><?= htmlspecialchars(implode("\n", (array)($config['pre_cache_urls'] ?? []))) ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold" id="btnSaveSw">
                                    <i class="bi bi-check-lg me-1"></i> Simpan & Perbarui Service Worker
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB 4: Integration Guide -->
                    <div class="tab-pane fade" id="tab-guide" role="tabpanel">
                        <h6 class="fw-bold mb-2">Pasang PWA di Aplikasi Anda</h6>
                        <p class="small text-muted mb-4">Cukup sisipkan tag berikut ke dalam file layout utama aplikasi Anda (seperti <code>app.blade.php</code> di Laravel, atau <code>layout.php</code> / <code>header.php</code> di CodeIgniter).</p>

                        <!-- Laravel Section -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold text-danger"><i class="bi bi-box-seam me-1"></i> 1. Laravel (Blade Directives)</span>
                            </div>
                            <div class="code-block">
                                <button class="btn btn-sm btn-outline-light copy-btn" onclick="copyCode(this)">Copy</button>
<pre class="m-0"><code>{{-- Tambahkan di dalam tag &lt;head&gt; --}}
@pwaHeader

{{-- Tambahkan sebelum penutup tag &lt;/body&gt; --}}
@pwaRegister</code></pre>
                            </div>
                        </div>

                        <!-- CodeIgniter 4 Section -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold text-warning-emphasis"><i class="bi bi-fire me-1"></i> 2. CodeIgniter 4 / CI3 (Helper Functions)</span>
                            </div>
                            <div class="code-block">
                                <button class="btn btn-sm btn-outline-light copy-btn" onclick="copyCode(this)">Copy</button>
<pre class="m-0"><code>&lt;!-- Tambahkan di dalam tag &lt;head&gt; --&gt;
&lt;?= pwa_header() ?&gt;

&lt;!-- Tambahkan sebelum penutup tag &lt;/body&gt; --&gt;
&lt;?= pwa_register() ?&gt;</code></pre>
                            </div>
                        </div>

                        <!-- HTML Manual Section -->
                        <div class="mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold text-info-emphasis"><i class="bi bi-filetype-html me-1"></i> 3. HTML Standar (Universal)</span>
                            </div>
                            <div class="code-block">
                                <button class="btn btn-sm btn-outline-light copy-btn" onclick="copyCode(this)">Copy</button>
<pre class="m-0"><code>&lt;link rel="manifest" href="/manifest.json"&gt;
&lt;meta name="theme-color" content="<?= htmlspecialchars($config['theme_color'] ?? '#2563eb') ?>"&gt;
&lt;meta name="apple-mobile-web-app-capable" content="yes"&gt;
&lt;meta name="apple-mobile-web-app-status-bar-style" content="default"&gt;
&lt;link rel="apple-touch-icon" href="/pwa/icons/apple-touch-icon.png"&gt;

&lt;script&gt;
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
&lt;/script&gt;</code></pre>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Right Side: Live Interactive Phone Simulator -->
        <div class="col-lg-4">
            <div class="card card-custom bg-white p-3 p-md-4 text-center">
                <h6 class="fw-bold mb-3 text-start"><i class="bi bi-phone me-1 text-primary"></i> Live Mobile Preview</h6>

                <!-- Mockup Phone -->
                <div class="phone-mockup" id="phoneMockup">
                    <div class="phone-notch"></div>
                    
                    <!-- Simulated Status Bar -->
                    <div id="simulatedStatusBar" class="d-flex justify-content-between px-3 py-1 text-white small" style="background-color: <?= htmlspecialchars($config['theme_color'] ?? '#2563eb') ?>; font-size: 11px;">
                        <span>09:41</span>
                        <div>
                            <i class="bi bi-wifi me-1"></i>
                            <i class="bi bi-battery-full"></i>
                        </div>
                    </div>

                    <!-- Screen Content -->
                    <div class="flex-grow-1 p-3 d-flex flex-column justify-content-between" id="simulatedScreen" style="background-color: <?= htmlspecialchars($config['background_color'] ?? '#ffffff') ?>;">
                        
                        <!-- Install Banner Simulator -->
                        <div class="p-2 rounded border shadow-sm text-start bg-white d-flex align-items-center gap-2">
                            <div id="simulatedIconBox" class="rounded bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; overflow: hidden;">
                                <?php 
                                    $previewIconUrl = '/pwa/icons/icon-192x192.png';
                                    $hasPreviewIcon = file_exists(ConfigResolver::getPublicPath('pwa/icons/icon-192x192.png'));
                                ?>
                                <img id="previewAppIcon" src="<?= $hasPreviewIcon ? $previewIconUrl : '' ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; <?= $hasPreviewIcon ? '' : 'display: none;' ?>">
                                <i id="previewIconPlaceholder" class="bi bi-app text-primary fs-4" style="<?= $hasPreviewIcon ? 'display: none;' : '' ?>"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <strong id="previewAppName" class="d-block text-truncate small text-dark"><?= htmlspecialchars($config['name'] ?? 'App Name') ?></strong>
                                <span class="badge bg-primary" style="font-size: 9px;">PWA Ready</span>
                            </div>
                            <button class="btn btn-primary btn-sm px-2 py-0" style="font-size: 11px;">Install</button>
                        </div>

                        <!-- Home Screen Icon Preview -->
                        <div class="my-auto text-center">
                            <div class="mx-auto rounded-3 shadow d-flex align-items-center justify-content-center mb-2" style="width: 72px; height: 72px; background: #fff; overflow: hidden;">
                                <img id="previewHomeIcon" src="<?= $hasPreviewIcon ? $previewIconUrl : '' ?>" alt="Icon" style="width: 100%; height: 100%; object-fit: cover; <?= $hasPreviewIcon ? '' : 'display: none;' ?>">
                                <i id="previewHomePlaceholder" class="bi bi-phone-vibrate text-primary fs-1" style="<?= $hasPreviewIcon ? 'display: none;' : '' ?>"></i>
                            </div>
                            <span id="previewAppShortName" class="fw-semibold small text-dark d-block text-truncate"><?= htmlspecialchars($config['short_name'] ?? 'MyPWA') ?></span>
                        </div>

                        <!-- Bottom Simulated Navigation -->
                        <div class="text-center text-muted small pb-2">
                            <div class="rounded-pill bg-secondary bg-opacity-25 mx-auto" style="width: 80px; height: 4px;"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-start small text-muted">
                    <i class="bi bi-info-circle me-1"></i> Preview diperbarui otomatis saat Anda mengubah nama, warna tema, atau mengunggah logo.
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">Operasi berhasil dilakukan.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const basePath = '<?= '/' . trim($routePrefix, '/') ?>';
    const toastEl = document.getElementById('liveToast');
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });

    function showToast(message, isError = false) {
        document.getElementById('toastMessage').innerText = message;
        toastEl.className = 'toast align-items-center text-white border-0 ' + (isError ? 'bg-danger' : 'bg-success');
        toast.show();
    }

    function copyCode(btn) {
        const pre = btn.nextElementSibling;
        navigator.clipboard.writeText(pre.innerText).then(() => {
            const originalText = btn.innerText;
            btn.innerText = 'Copied!';
            setTimeout(() => btn.innerText = originalText, 1500);
        });
    }

    // Live preview updates
    const inputName = document.getElementById('inputName');
    const inputShortName = document.getElementById('inputShortName');
    const pickerThemeColor = document.getElementById('pickerThemeColor');
    const inputThemeColor = document.getElementById('inputThemeColor');
    const pickerBgColor = document.getElementById('pickerBgColor');
    const inputBgColor = document.getElementById('inputBgColor');

    inputName.addEventListener('input', (e) => {
        document.getElementById('previewAppName').innerText = e.target.value || 'App Name';
    });

    inputShortName.addEventListener('input', (e) => {
        document.getElementById('previewAppShortName').innerText = e.target.value || 'MyPWA';
    });

    pickerThemeColor.addEventListener('input', (e) => {
        inputThemeColor.value = e.target.value;
        document.getElementById('simulatedStatusBar').style.backgroundColor = e.target.value;
    });

    inputThemeColor.addEventListener('input', (e) => {
        pickerThemeColor.value = e.target.value;
        document.getElementById('simulatedStatusBar').style.backgroundColor = e.target.value;
    });

    pickerBgColor.addEventListener('input', (e) => {
        inputBgColor.value = e.target.value;
        document.getElementById('simulatedScreen').style.backgroundColor = e.target.value;
    });

    inputBgColor.addEventListener('input', (e) => {
        pickerBgColor.value = e.target.value;
        document.getElementById('simulatedScreen').style.backgroundColor = e.target.value;
    });

    // Handle Settings Form Save
    document.getElementById('formSettings').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveSettings');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        try {
            const formData = new FormData(this);
            const res = await fetch(basePath + '/save', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
            } else {
                showToast(data.message || 'Gagal menyimpan.', true);
            }
        } catch (err) {
            showToast('Terjadi kesalahan jaringan.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Simpan Pengaturan & Generate';
        }
    });

    // Handle SW Settings Form Save
    document.getElementById('formSwSettings').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveSw');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        try {
            const formData = new FormData(this);
            // Append current name & short_name so they aren't overwritten
            formData.append('name', inputName.value);
            formData.append('short_name', inputShortName.value);
            formData.append('theme_color', inputThemeColor.value);
            formData.append('background_color', inputBgColor.value);

            const res = await fetch(basePath + '/save', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast('Pengaturan Service Worker berhasil diperbarui!');
            } else {
                showToast(data.message || 'Gagal menyimpan.', true);
            }
        } catch (err) {
            showToast('Terjadi kesalahan jaringan.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Simpan & Perbarui Service Worker';
        }
    });

    // Handle Logo Upload Form
    document.getElementById('formLogoUpload').addEventListener('submit', async function(e) {
        e.preventDefault();
        const fileInput = document.getElementById('inputLogoFile');
        if (!fileInput.files.length) return;

        const btn = document.getElementById('btnUploadLogo');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

        try {
            const formData = new FormData(this);
            const res = await fetch(basePath + '/upload-logo', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(data.message || 'Gagal memproses logo.', true);
            }
        } catch (err) {
            showToast('Terjadi kesalahan saat upload logo.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-magic me-1"></i> Generate Icon PWA';
        }
    });

    // Handle Delete Icons
    document.getElementById('btnDeleteIcons').addEventListener('click', async function() {
        if (!confirm('Apakah Anda yakin ingin menghapus semua icon yang pernah digenerate?')) return;

        try {
            const res = await fetch(basePath + '/delete-icons', { method: 'POST' });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message, true);
            }
        } catch (err) {
            showToast('Terjadi kesalahan.', true);
        }
    });

    // Re-generate Assets Top Button
    document.getElementById('btnGenerateAssetsTop').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

        try {
            const res = await fetch(basePath + '/generate', { method: 'POST' });
            const data = await res.json();
            if (data.status === 'success') {
                showToast(data.message);
            } else {
                showToast(data.message, true);
            }
        } catch (err) {
            showToast('Terjadi kesalahan jaringan.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Re-generate Asset';
        }
    });
</script>
</body>
</html>

