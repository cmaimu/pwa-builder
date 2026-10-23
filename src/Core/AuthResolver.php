<?php
namespace PwaBuilder\Core;

class AuthResolver {
    /**
     * Memulai session jika belum dimulai (aman untuk semua framework)
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
    }

    /**
     * Memeriksa apakah user saat ini terautentikasi
     */
    public static function isAuthenticated(): bool {
        $driver = strtolower((string) ConfigResolver::getEnv('PWA_AUTH_DRIVER', 'auto'));

        // Jika user secara eksplisit mematikan auth
        if ($driver === 'none' || ConfigResolver::getEnv('PWA_AUTH_ENABLED', 'true') === 'false') {
            return true;
        }

        // 1. Cek Framework Auth (jika driver 'auto' atau 'framework')
        if ($driver === 'auto' || $driver === 'framework') {
            if (self::checkFrameworkAuth()) {
                return true;
            }
            if ($driver === 'framework') {
                return false;
            }
        }

        // 2. Cek Built-in Session Auth (jika driver 'auto' atau 'builtin')
        return self::checkBuiltinSession();
    }

    /**
     * Cek autentikasi bawaan framework (Laravel, CI4, CI3)
     */
    public static function checkFrameworkAuth(): bool {
        // A. Laravel Auth Facade
        if (class_exists('\Illuminate\Support\Facades\Auth')) {
            try {
                if (\Illuminate\Support\Facades\Auth::check()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Lanjut jika auth facade belum terinisialisasi
            }
        }

        // B. CodeIgniter 4 (Shield atau Session)
        if (function_exists('service')) {
            try {
                // CI4 Shield
                $auth = service('auth');
                if ($auth && method_exists($auth, 'loggedIn') && $auth->loggedIn()) {
                    return true;
                }
            } catch (\Throwable $e) {}

            try {
                // CI4 Session
                $session = service('session');
                if ($session && ($session->has('user_id') || $session->has('logged_in') || $session->has('user'))) {
                    return true;
                }
            } catch (\Throwable $e) {}
        }

        // C. CodeIgniter 3 Session
        if (function_exists('get_instance')) {
            try {
                $ci = &get_instance();
                if (isset($ci->session)) {
                    if ($ci->session->userdata('user_id') || $ci->session->userdata('logged_in')) {
                        return true;
                    }
                }
            } catch (\Throwable $e) {}
        }

        return false;
    }

    /**
     * Cek session login bawaan PWA Builder
     */
    public static function checkBuiltinSession(): bool {
        // Cek Laravel session helper jika ada
        if (function_exists('session')) {
            try {
                if (session('pwa_builder_authenticated') === true) {
                    return true;
                }
            } catch (\Throwable $e) {}
        }

        // Cek PHP Native Session
        self::startSession();
        return !empty($_SESSION['pwa_builder_authenticated']);
    }

    /**
     * Memproses login menggunakan kredensial bawaan (username & password)
     */
    public static function attemptBuiltinLogin(string $username, string $password): bool {
        $expectedUser = (string) ConfigResolver::getEnv('PWA_AUTH_USERNAME', 'admin');
        
        // Password dari PWA_AUTH_PASSWORD atau fallback ke PWA_SECRET_KEY / APP_KEY
        $expectedPass = (string) ConfigResolver::getEnv('PWA_AUTH_PASSWORD', '');
        if (empty($expectedPass)) {
            $expectedPass = (string) ConfigResolver::getEnv('PWA_SECRET_KEY', '');
        }

        // Jika password di .env belum diset, default password dev 'admin123' hanya jika PWA_AUTH_PASSWORD belum diset
        if (empty($expectedPass)) {
            $expectedPass = 'admin123';
        }

        // Verifikasi username dan password
        $userValid = hash_equals($expectedUser, $username);
        $passValid = hash_equals($expectedPass, $password);

        if ($userValid && $passValid) {
            // Set ke Laravel session jika tersedia
            if (function_exists('session')) {
                try {
                    session(['pwa_builder_authenticated' => true, 'pwa_builder_user' => $username]);
                } catch (\Throwable $e) {}
            }

            // Set ke native session
            self::startSession();
            $_SESSION['pwa_builder_authenticated'] = true;
            $_SESSION['pwa_builder_user'] = $username;
            return true;
        }

        return false;
    }

    /**
     * Logout session bawaan
     */
    public static function logout(): void {
        if (function_exists('session')) {
            try {
                session()->forget('pwa_builder_authenticated');
                session()->forget('pwa_builder_user');
            } catch (\Throwable $e) {}
        }

        self::startSession();
        unset($_SESSION['pwa_builder_authenticated']);
        unset($_SESSION['pwa_builder_user']);
    }

    /**
     * Mendapatkan username atau info user aktif
     */
    public static function getUserName(): string {
        // Laravel
        if (class_exists('\Illuminate\Support\Facades\Auth')) {
            try {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user && isset($user->name)) {
                    return (string) $user->name;
                }
                if ($user && isset($user->email)) {
                    return (string) $user->email;
                }
            } catch (\Throwable $e) {}
        }

        // CI4
        if (function_exists('service')) {
            try {
                $session = service('session');
                if ($session && $session->has('username')) {
                    return (string) $session->get('username');
                }
            } catch (\Throwable $e) {}
        }

        // Native / Built-in
        self::startSession();
        return $_SESSION['pwa_builder_user'] ?? 'Administrator';
    }
}

