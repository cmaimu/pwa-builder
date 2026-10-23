<?php
namespace PwaBuilder\Core;

class ConfigResolver {
    /**
     * Mengambil nilai konfigurasi dari environment atau helper framework
     */
    public static function getEnv(string $key, $default = null) {
        // 1. Laravel atau CI4 env() helper jika tersedia
        if (function_exists('env')) {
            $val = env($key);
            if ($val !== null) {
                return $val;
            }
        }

        // 2. $_ENV
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        // 3. $_SERVER
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        // 4. getenv()
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }

        return $default;
    }

    /**
     * Mendeteksi lokasi direktori public (web root) framework
     */
    public static function getPublicPath(string $subpath = ''): string {
        $publicDir = '';

        // 1. Laravel public_path()
        if (function_exists('public_path')) {
            try {
                $publicDir = public_path();
            } catch (\Throwable $e) {}
        }

        // 2. CodeIgniter 4 atau CI3 FCPATH
        if (empty($publicDir) && defined('FCPATH')) {
            $publicDir = rtrim(FCPATH, '/\\');
        }

        // 3. Fallback: $_SERVER['DOCUMENT_ROOT']
        if (empty($publicDir) && !empty($_SERVER['DOCUMENT_ROOT'])) {
            $publicDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
        }

        // 4. Fallback: cari folder 'public' di root project
        if (empty($publicDir)) {
            $candidate = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'public';
            if (is_dir($candidate)) {
                $publicDir = $candidate;
            } else {
                $publicDir = dirname(__DIR__, 4);
            }
        }

        $trimmedSub = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subpath), DIRECTORY_SEPARATOR);
        $fullPath = empty($trimmedSub) ? $publicDir : $publicDir . DIRECTORY_SEPARATOR . $trimmedSub;
        
        return $fullPath;
    }

    /**
     * Mendapatkan lokasi penyimpanan file konfigurasi PWA (pwa-config.json)
     */
    public static function getStoragePath(string $subpath = ''): string {
        $storageDir = '';

        // 1. Laravel storage_path()
        if (function_exists('storage_path')) {
            try {
                $storageDir = storage_path('pwa');
            } catch (\Throwable $e) {}
        }

        // 2. CodeIgniter 4 WRITEPATH
        if (empty($storageDir) && defined('WRITEPATH')) {
            $storageDir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'pwa';
        }

        // 3. CodeIgniter 3 APPPATH
        if (empty($storageDir) && defined('APPPATH')) {
            $storageDir = rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'pwa';
        }

        // 4. Fallback ke folder public/pwa
        if (empty($storageDir)) {
            $storageDir = self::getPublicPath('pwa');
        }

        self::ensureDirectoryExists($storageDir);

        $trimmedSub = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subpath), DIRECTORY_SEPARATOR);
        return empty($trimmedSub) ? $storageDir : $storageDir . DIRECTORY_SEPARATOR . $trimmedSub;
    }

    /**
     * Memastikan sebuah folder ada, jika belum ada otomatis dibuat (recursive)
     */
    public static function ensureDirectoryExists(string $dir, int $permissions = 0755): bool {
        if (!is_dir($dir)) {
            return @mkdir($dir, $permissions, true);
        }
        return true;
    }

    /**
     * Mendapatkan Base URL dari aplikasi aktif
     */
    public static function getBaseUrl(string $path = ''): string {
        $baseUrl = '';

        // 1. Laravel url()
        if (function_exists('url')) {
            try {
                $baseUrl = rtrim(url('/'), '/');
            } catch (\Throwable $e) {}
        }

        // 2. CodeIgniter 4 atau CI3 base_url()
        if (empty($baseUrl) && function_exists('base_url')) {
            try {
                $baseUrl = rtrim(base_url(), '/');
            } catch (\Throwable $e) {}
        }

        // 3. Fallback deteksi request HTTP
        if (empty($baseUrl)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . '://' . $host;
        }

        $trimmedPath = ltrim($path, '/');
        return empty($trimmedPath) ? $baseUrl : $baseUrl . '/' . $trimmedPath;
    }

    /**
     * Mengambil CSRF token untuk form POST sesuai framework
     */
    public static function getCsrfToken(): array {
        // A. Laravel CSRF
        if (function_exists('csrf_token')) {
            try {
                return [
                    'name'   => '_token',
                    'header' => 'X-CSRF-TOKEN',
                    'value'  => csrf_token()
                ];
            } catch (\Throwable $e) {}
        }

        // B. CodeIgniter 4 CSRF
        if (function_exists('csrf_token') && function_exists('csrf_hash')) {
            try {
                return [
                    'name'   => csrf_token(),
                    'header' => 'X-CSRF-TOKEN',
                    'value'  => csrf_hash()
                ];
            } catch (\Throwable $e) {}
        }

        // C. CodeIgniter 3 CSRF
        if (function_exists('get_instance')) {
            try {
                $ci = &get_instance();
                if (isset($ci->security)) {
                    return [
                        'name'   => $ci->security->get_csrf_token_name(),
                        'header' => 'X-CSRF-TOKEN',
                        'value'  => $ci->security->get_csrf_hash()
                    ];
                }
            } catch (\Throwable $e) {}
        }

        // D. Built-in Native Session Token
        AuthResolver::startSession();
        if (empty($_SESSION['_pwa_csrf_token'])) {
            $_SESSION['_pwa_csrf_token'] = bin2hex(random_bytes(16));
        }

        return [
            'name'   => '_pwa_csrf',
            'header' => 'X-CSRF-TOKEN',
            'value'  => $_SESSION['_pwa_csrf_token']
        ];
    }
}

