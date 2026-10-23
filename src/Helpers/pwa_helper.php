<?php

use PwaBuilder\Core\PwaManager;

if (!function_exists('pwa_header')) {
    /**
     * Render tag meta dan link PWA di dalam tag <head> HTML
     */
    function pwa_header(): string {
        return PwaManager::getInstance()->header();
    }
}

if (!function_exists('pwa_register')) {
    /**
     * Render script registrasi Service Worker
     */
    function pwa_register(): string {
        return PwaManager::getInstance()->registerScript();
    }
}

if (!function_exists('pwa_config')) {
    /**
     * Mengambil array konfigurasi PWA
     */
    function pwa_config(?string $key = null, $default = null) {
        $config = PwaManager::getInstance()->getConfig();
        if ($key === null) {
            return $config;
        }
        return $config[$key] ?? $default;
    }
}

if (!function_exists('pwa_status')) {
    /**
     * Mengambil status kesiapan PWA
     */
    function pwa_status(): array {
        return PwaManager::getInstance()->getStatus();
    }
}

