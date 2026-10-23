<?php
namespace PwaBuilder\Http\Controllers;

use PwaBuilder\Core\ConfigResolver;
use PwaBuilder\Core\AuthResolver;
use PwaBuilder\Core\PwaManager;
use PwaBuilder\Core\IconGenerator;

class PwaController {
    private $manager;

    public function __construct() {
        $this->manager = PwaManager::getInstance();
    }

    /**
     * Dashboard Utama PWA Builder
     */
    public function index() {
        $routePrefix = ConfigResolver::getEnv('PWA_PREFIX', 'pwa-builder');
        $basePath = '/' . trim($routePrefix, '/');

        // Proteksi autentikasi
        if (!AuthResolver::isAuthenticated()) {
            header("Location: {$basePath}/login");
            exit;
        }

        $config = $this->manager->getConfig();
        $status = $this->manager->getStatus();
        $icons = IconGenerator::getExistingIcons();
        $csrf = ConfigResolver::getCsrfToken();
        $userName = AuthResolver::getUserName();

        require __DIR__ . '/../../Views/index.php';
    }

    /**
     * Halaman Login Dashboard
     */
    public function login() {
        $routePrefix = ConfigResolver::getEnv('PWA_PREFIX', 'pwa-builder');
        $basePath = '/' . trim($routePrefix, '/');

        // Jika sudah login, langsung alihkan ke dashboard
        if (AuthResolver::isAuthenticated()) {
            header("Location: {$basePath}");
            exit;
        }

        $errorMessage = '';
        $csrf = ConfigResolver::getCsrfToken();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = (string)($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');

            if (AuthResolver::attemptBuiltinLogin($username, $password)) {
                header("Location: {$basePath}");
                exit;
            } else {
                $errorMessage = 'Username atau Password tidak cocok. Silakan periksa pengaturan kredensial Anda di .env.';
            }
        }

        require __DIR__ . '/../../Views/login.php';
    }

    /**
     * Logout Session
     */
    public function logout() {
        AuthResolver::logout();
        $routePrefix = ConfigResolver::getEnv('PWA_PREFIX', 'pwa-builder');
        $basePath = '/' . trim($routePrefix, '/');
        header("Location: {$basePath}/login");
        exit;
    }

    /**
     * Menyimpan Konfigurasi PWA & Memperbarui Asset Otomatis
     */
    public function saveSettings() {
        header('Content-Type: application/json');

        if (!AuthResolver::isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Unauthorized: Sesi Anda telah berakhir.'
            ]);
            return;
        }

        try {
            $preCacheUrls = [];
            if (!empty($_POST['pre_cache_urls'])) {
                $rawUrls = explode("\n", (string)$_POST['pre_cache_urls']);
                foreach ($rawUrls as $u) {
                    $u = trim($u);
                    if ($u !== '') {
                        $preCacheUrls[] = $u;
                    }
                }
            }

            $settings = [
                'name'             => trim((string)($_POST['name'] ?? 'My PWA Application')),
                'short_name'       => trim((string)($_POST['short_name'] ?? 'MyPWA')),
                'description'      => trim((string)($_POST['description'] ?? '')),
                'start_url'        => trim((string)($_POST['start_url'] ?? '/')),
                'scope'            => trim((string)($_POST['scope'] ?? '/')),
                'display'          => trim((string)($_POST['display'] ?? 'standalone')),
                'orientation'      => trim((string)($_POST['orientation'] ?? 'portrait-primary')),
                'theme_color'      => trim((string)($_POST['theme_color'] ?? '#2563eb')),
                'background_color' => trim((string)($_POST['background_color'] ?? '#ffffff')),
                'status_bar'       => trim((string)($_POST['status_bar'] ?? 'default')),
                'cache_strategy'   => trim((string)($_POST['cache_strategy'] ?? 'network-first')),
                'cache_version'    => trim((string)($_POST['cache_version'] ?? 'v1.0.0')),
                'offline_title'    => trim((string)($_POST['offline_title'] ?? 'Koneksi Terputus')),
                'offline_message'  => trim((string)($_POST['offline_message'] ?? 'Aplikasi sedang berjalan dalam mode offline.')),
                'pre_cache_urls'   => $preCacheUrls,
            ];

            $this->manager->saveConfig($settings);

            // Otomatis tulis ulang manifest, sw.js, offline.html ke public
            $writeResults = $this->manager->writeAssets();

            echo json_encode([
                'status'        => 'success',
                'message'       => 'Pengaturan PWA berhasil disimpan dan asset telah diperbarui!',
                'write_results' => $writeResults,
                'pwa_status'    => $this->manager->getStatus()
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Upload Logo & Generate Semua Resolusi Icon Sekaligus
     */
    public function uploadLogo() {
        header('Content-Type: application/json');

        if (!AuthResolver::isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Unauthorized: Sesi Anda telah berakhir.'
            ]);
            return;
        }

        if (empty($_FILES['logo'])) {
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Tidak ada file logo yang dikirim.'
            ]);
            return;
        }

        try {
            $config = $this->manager->getConfig();
            $bgColor = $config['background_color'] ?? '#ffffff';

            $generated = IconGenerator::generateFromUpload($_FILES['logo'], $bgColor);

            // Otomatis update manifest.json & sw.js dengan icon baru
            $this->manager->writeAssets();

            echo json_encode([
                'status'     => 'success',
                'message'    => 'Logo berhasil diproses! ' . count($generated) . ' resolusi icon PWA berhasil digenerate.',
                'icons'      => IconGenerator::getExistingIcons(),
                'pwa_status' => $this->manager->getStatus()
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Gagal memproses logo: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Trigger Manual Generate Assets
     */
    public function generateAssets() {
        header('Content-Type: application/json');

        if (!AuthResolver::isAuthenticated()) {
            http_response_code(401);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Unauthorized: Sesi Anda telah berakhir.'
            ]);
            return;
        }

        try {
            $results = $this->manager->writeAssets();
            echo json_encode([
                'status'     => 'success',
                'message'    => 'Asset PWA (manifest.json, sw.js, offline.html) berhasil diperbarui!',
                'results'    => $results,
                'pwa_status' => $this->manager->getStatus()
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Gagal generate asset: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Menghapus Icon yang sudah digenerate
     */
    public function deleteIcons() {
        header('Content-Type: application/json');

        if (!AuthResolver::isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        try {
            IconGenerator::deleteGeneratedIcons();
            $this->manager->writeAssets();

            echo json_encode([
                'status'     => 'success',
                'message'    => 'Semua icon berhasil dihapus.',
                'icons'      => IconGenerator::getExistingIcons(),
                'pwa_status' => $this->manager->getStatus()
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Gagal menghapus icon: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Endpoint dinamis Manifest JSON
     */
    public function serveManifest() {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        echo $this->manager->generateManifestJson();
        exit;
    }

    /**
     * Endpoint dinamis Service Worker JS
     */
    public function serveServiceWorker() {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: /');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $this->manager->generateServiceWorkerJs();
        exit;
    }

    /**
     * Endpoint dinamis Offline HTML
     */
    public function serveOffline() {
        header('Content-Type: text/html; charset=utf-8');
        echo $this->manager->generateOfflineHtml();
        exit;
    }
}

