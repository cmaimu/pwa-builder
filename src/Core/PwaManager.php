<?php
namespace PwaBuilder\Core;

class PwaManager {
    private static $instance = null;
    private $config = null;

    public function __construct() {
        $this->loadConfig();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Mengambil konfigurasi default aplikasi
     */
    public function getDefaultConfig(): array {
        $appName = ConfigResolver::getEnv('APP_NAME', 'My Progressive Web App');
        $shortName = substr(preg_replace('/[^a-zA-Z0-9]/', '', (string)$appName), 0, 12);
        if (empty($shortName)) {
            $shortName = 'MyPWA';
        }

        return [
            'name'                 => $appName,
            'short_name'           => $shortName,
            'description'          => 'A progressive web application built with cmdimu/pwa-builder',
            'start_url'            => '/?utm_source=pwa',
            'scope'                => '/',
            'display'              => 'standalone', // standalone, fullscreen, minimal-ui, browser
            'orientation'          => 'portrait-primary', // any, natural, portrait, portrait-primary, landscape
            'theme_color'          => '#2563eb',
            'background_color'     => '#ffffff',
            'status_bar'           => 'default', // default, black, black-translucent
            'cache_strategy'       => 'network-first', // network-first, cache-first, stale-while-revalidate
            'cache_version'        => 'v1.0.0',
            'offline_url'          => '/offline.html',
            'offline_title'        => 'Koneksi Internet Terputus',
            'offline_message'      => 'Aplikasi saat ini berjalan dalam mode offline. Silakan periksa kembali jaringan internet Anda.',
            'pre_cache_urls'       => [
                '/',
                '/offline.html',
            ],
            'shortcuts'            => [
                [
                    'name'        => 'Beranda',
                    'short_name'  => 'Home',
                    'description' => 'Buka Halaman Utama',
                    'url'         => '/',
                    'icons'       => [
                        [
                            'src'   => '/pwa/icons/icon-96x96.png',
                            'sizes' => '96x96'
                        ]
                    ]
                ]
            ],
            'last_generated_at'    => null
        ];
    }

    /**
     * Memuat konfigurasi dari pwa-config.json atau default
     */
    public function loadConfig(): array {
        $configFile = ConfigResolver::getStoragePath('pwa-config.json');

        if (file_exists($configFile)) {
            $json = @file_get_contents($configFile);
            $parsed = json_decode($json, true);
            if (is_array($parsed)) {
                $this->config = array_merge($this->getDefaultConfig(), $parsed);
                return $this->config;
            }
        }

        $this->config = $this->getDefaultConfig();
        return $this->config;
    }

    /**
     * Menyimpan konfigurasi ke file pwa-config.json
     */
    public function saveConfig(array $data): bool {
        $configFile = ConfigResolver::getStoragePath('pwa-config.json');
        ConfigResolver::ensureDirectoryExists(dirname($configFile));

        $merged = array_merge($this->loadConfig(), $data);
        $merged['updated_at'] = date('Y-m-d H:i:s');

        $success = @file_put_contents(
            $configFile,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ) !== false;

        if ($success) {
            $this->config = $merged;
        }

        return $success;
    }

    /**
     * Mengambil konfigurasi saat ini
     */
    public function getConfig(): array {
        if ($this->config === null) {
            $this->loadConfig();
        }
        return $this->config;
    }

    /**
     * Generate array struktur manifest.json sesuai spesifikasi W3C
     */
    public function generateManifestArray(): array {
        $config = $this->getConfig();
        $existingIcons = IconGenerator::getExistingIcons();

        $icons = [];
        foreach ($existingIcons as $icon) {
            if ($icon['exists']) {
                $iconData = [
                    'src'   => '/pwa/icons/' . $icon['name'],
                    'sizes' => $icon['size'],
                    'type'  => 'image/png'
                ];
                if (!empty($icon['purpose'])) {
                    $iconData['purpose'] = $icon['purpose'];
                }
                $icons[] = $iconData;
            }
        }

        $manifest = [
            'name'             => $config['name'],
            'short_name'       => $config['short_name'],
            'description'      => $config['description'],
            'start_url'        => $config['start_url'] ?? '/',
            'scope'            => $config['scope'] ?? '/',
            'display'          => $config['display'] ?? 'standalone',
            'orientation'      => $config['orientation'] ?? 'portrait-primary',
            'theme_color'      => $config['theme_color'] ?? '#2563eb',
            'background_color' => $config['background_color'] ?? '#ffffff',
            'icons'            => $icons,
        ];

        if (!empty($config['shortcuts']) && is_array($config['shortcuts'])) {
            $manifest['shortcuts'] = $config['shortcuts'];
        }

        return $manifest;
    }

    /**
     * Generate string JSON manifest
     */
    public function generateManifestJson(): string {
        return json_encode(
            $this->generateManifestArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Generate script Service Worker (sw.js)
     */
    public function generateServiceWorkerJs(): string {
        $config = $this->getConfig();
        $cacheName = 'pwa-cache-' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $config['cache_version'] ?? 'v1.0.0');
        $strategy = $config['cache_strategy'] ?? 'network-first';
        $offlineUrl = $config['offline_url'] ?? '/offline.html';

        $preCache = array_unique(array_filter(array_merge(
            ['/', $offlineUrl, '/manifest.json'],
            is_array($config['pre_cache_urls'] ?? null) ? $config['pre_cache_urls'] : []
        )));
        $preCacheJson = json_encode(array_values($preCache), JSON_UNESCAPED_SLASHES);

        $js = <<<JS
/**
 * Progressive Web App Service Worker
 * Generated automatically by cmdimu/pwa-builder
 * Cache Version: {$config['cache_version']}
 * Strategy: {$strategy}
 */

const CACHE_NAME = '{$cacheName}';
const OFFLINE_URL = '{$offlineUrl}';

const PRECACHE_ASSETS = {$preCacheJson};

// 1. Install Event: Cache critical assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[PWA] Pre-caching partial failed:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// 2. Activate Event: Clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cache) => {
                    if (cache !== CACHE_NAME && cache.startsWith('pwa-cache-')) {
                        console.log('[PWA] Deleting old cache:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch Event Strategy Handler
self.addEventListener('fetch', (event) => {
    // Hanya tangani GET requests
    if (event.request.method !== 'GET') return;

    // Abaikan permintaan ke Chrome extension atau skema non-http
    if (!event.request.url.startsWith('http')) return;

    const requestUrl = new URL(event.request.url);

    // Jangan cache endpoint admin pwa-builder atau API
    if (requestUrl.pathname.includes('/pwa-builder') || requestUrl.pathname.includes('/api/')) {
        return;
    }

JS;

        if ($strategy === 'cache-first') {
            $js .= <<<JS

    // Cache-First Strategy
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(event.request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
                }
                return networkResponse;
            }).catch(() => {
                if (event.request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }
            });
        })
    );
JS;
        } elseif ($strategy === 'stale-while-revalidate') {
            $js .= <<<JS

    // Stale-While-Revalidate Strategy
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
            const fetchPromise = fetch(event.request).then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
                }
                return networkResponse;
            }).catch(() => {
                if (event.request.mode === 'navigate' && !cachedResponse) {
                    return caches.match(OFFLINE_URL);
                }
            });

            return cachedResponse || fetchPromise;
        })
    );
JS;
        } else {
            // Default: network-first
            $js .= <<<JS

    // Network-First Strategy (Optimal for dynamic web apps)
    event.respondWith(
        fetch(event.request)
            .then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
                }
                return networkResponse;
            })
            .catch(() => {
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (event.request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                });
            })
    );
JS;
        }

        $js .= "\n});\n";
        return $js;
    }

    /**
     * Generate HTML halaman fallback offline (offline.html)
     */
    public function generateOfflineHtml(): string {
        $config = $this->getConfig();
        $appName = htmlspecialchars($config['name'] ?? 'PWA Application');
        $title = htmlspecialchars($config['offline_title'] ?? 'Koneksi Terputus');
        $message = htmlspecialchars($config['offline_message'] ?? 'Anda sedang berada dalam mode offline.');
        $themeColor = htmlspecialchars($config['theme_color'] ?? '#2563eb');

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - {$appName}</title>
    <meta name="theme-color" content="{$themeColor}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            text-align: center;
        }
        .offline-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px 24px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #e2e8f0;
        }
        .offline-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #fee2e2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 12px; color: #0f172a; }
        p { font-size: 14px; line-height: 1.6; color: #64748b; margin-bottom: 24px; }
        .btn-retry {
            display: inline-block;
            background: {$themeColor};
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-retry:hover { opacity: 0.9; }
        .status-badge {
            display: inline-block;
            margin-top: 18px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="offline-icon">📡</div>
        <h1>{$title}</h1>
        <p>{$message}</p>
        <button class="btn-retry" onclick="window.location.reload()">Coba Muat Ulang</button>
        <br>
        <span class="status-badge" id="onlineStatus">Mendeteksi jaringan...</span>
    </div>

    <script>
        function checkStatus() {
            const badge = document.getElementById('onlineStatus');
            if (navigator.onLine) {
                badge.innerText = 'Koneksi kembali! Memuat ulang...';
                badge.style.color = '#10b981';
                setTimeout(() => window.location.reload(), 1000);
            } else {
                badge.innerText = 'Perangkat tidak terhubung ke internet.';
            }
        }
        window.addEventListener('online', checkStatus);
        window.addEventListener('offline', checkStatus);
        checkStatus();
    </script>
</body>
</html>
HTML;
    }

    /**
     * Menulis dan memperbarui semua asset PWA ke folder public
     * (manifest.json, sw.js, offline.html)
     */
    public function writeAssets(): array {
        $publicDir = ConfigResolver::getPublicPath();
        ConfigResolver::ensureDirectoryExists($publicDir);

        $results = [
            'manifest' => false,
            'sw'       => false,
            'offline'  => false,
            'errors'   => []
        ];

        // 1. Tulis manifest.json
        $manifestPath = $publicDir . DIRECTORY_SEPARATOR . 'manifest.json';
        $manifestContent = $this->generateManifestJson();
        if (@file_put_contents($manifestPath, $manifestContent) !== false) {
            $results['manifest'] = true;
        } else {
            $results['errors'][] = "Gagal menulis {$manifestPath}. Periksa permission direktori public.";
        }

        // Salin juga manifest.json ke public/pwa/manifest.json sebagai fallback
        $pwaDir = ConfigResolver::getPublicPath('pwa');
        ConfigResolver::ensureDirectoryExists($pwaDir);
        @file_put_contents($pwaDir . DIRECTORY_SEPARATOR . 'manifest.json', $manifestContent);

        // 2. Tulis sw.js (di root public agar scope mencakup seluruh domain '/')
        $swPath = $publicDir . DIRECTORY_SEPARATOR . 'sw.js';
        $swContent = $this->generateServiceWorkerJs();
        if (@file_put_contents($swPath, $swContent) !== false) {
            $results['sw'] = true;
        } else {
            $results['errors'][] = "Gagal menulis {$swPath}. Periksa permission direktori public.";
        }

        // 3. Tulis offline.html
        $offlinePath = $publicDir . DIRECTORY_SEPARATOR . 'offline.html';
        $offlineContent = $this->generateOfflineHtml();
        if (@file_put_contents($offlinePath, $offlineContent) !== false) {
            $results['offline'] = true;
        } else {
            $results['errors'][] = "Gagal menulis {$offlinePath}.";
        }

        // Update timestamp generate
        $this->saveConfig(['last_generated_at' => date('Y-m-d H:i:s')]);

        return $results;
    }

    /**
     * Memeriksa status kesiapan PWA
     */
    public function getStatus(): array {
        $publicDir = ConfigResolver::getPublicPath();
        $manifestExists = file_exists($publicDir . DIRECTORY_SEPARATOR . 'manifest.json');
        $swExists = file_exists($publicDir . DIRECTORY_SEPARATOR . 'sw.js');
        $offlineExists = file_exists($publicDir . DIRECTORY_SEPARATOR . 'offline.html');

        $icons = IconGenerator::getExistingIcons();
        $availableIcons = count(array_filter($icons, fn($i) => !empty($i['exists'])));
        $totalIcons = count($icons);

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                   (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
                   (isset($_SERVER['HTTP_HOST']) && str_starts_with($_SERVER['HTTP_HOST'], 'localhost')) ||
                   (isset($_SERVER['HTTP_HOST']) && str_starts_with($_SERVER['HTTP_HOST'], '127.0.0.1'));

        return [
            'manifest'         => $manifestExists,
            'service_worker'   => $swExists,
            'offline_page'     => $offlineExists,
            'icons_count'      => $availableIcons,
            'icons_total'      => $totalIcons,
            'icons_ready'      => $availableIcons >= 3,
            'is_https'         => $isHttps,
            'is_ready'         => ($manifestExists && $swExists && $availableIcons >= 2)
        ];
    }

    /**
     * Render tag meta dan link di bagian <head> HTML
     */
    public function header(): string {
        $config = $this->getConfig();
        $themeColor = htmlspecialchars($config['theme_color'] ?? '#2563eb');
        $appName = htmlspecialchars($config['name'] ?? 'PWA');
        $shortName = htmlspecialchars($config['short_name'] ?? 'PWA');
        $statusBar = htmlspecialchars($config['status_bar'] ?? 'default');

        $html = "<!-- Progressive Web App Meta Tags (cmdimu/pwa-builder) -->\n";
        $html .= '<link rel="manifest" href="/manifest.json">' . "\n";
        $html .= '<meta name="theme-color" content="' . $themeColor . '">' . "\n";
        $html .= '<meta name="mobile-web-app-capable" content="yes">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-status-bar-style" content="' . $statusBar . '">' . "\n";
        $html .= '<meta name="apple-mobile-web-app-title" content="' . $shortName . '">' . "\n";
        $html .= '<meta name="application-name" content="' . $appName . '">' . "\n";

        // Apple Touch Icon
        if (file_exists(ConfigResolver::getPublicPath('pwa/icons/apple-touch-icon.png'))) {
            $html .= '<link rel="apple-touch-icon" href="/pwa/icons/apple-touch-icon.png">' . "\n";
        } elseif (file_exists(ConfigResolver::getPublicPath('pwa/icons/icon-192x192.png'))) {
            $html .= '<link rel="apple-touch-icon" href="/pwa/icons/icon-192x192.png">' . "\n";
        }

        // Favicons
        if (file_exists(ConfigResolver::getPublicPath('pwa/icons/favicon-32x32.png'))) {
            $html .= '<link rel="icon" type="image/png" sizes="32x32" href="/pwa/icons/favicon-32x32.png">' . "\n";
        }
        if (file_exists(ConfigResolver::getPublicPath('pwa/icons/favicon-16x16.png'))) {
            $html .= '<link rel="icon" type="image/png" sizes="16x16" href="/pwa/icons/favicon-16x16.png">' . "\n";
        }

        return $html;
    }

    /**
     * Render script registrasi Service Worker
     */
    public function registerScript(): string {
        return <<<HTML
<!-- Progressive Web App Service Worker Registration -->
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(function(registration) {
                console.log('[PWA] ServiceWorker registered successfully with scope: ', registration.scope);
            })
            .catch(function(err) {
                console.error('[PWA] ServiceWorker registration failed: ', err);
            });
    });
}
</script>
HTML;
    }
}

