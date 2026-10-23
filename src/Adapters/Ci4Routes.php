<?php
namespace PwaBuilder\Adapters;

use PwaBuilder\Core\ConfigResolver;

class Ci4Routes {
    /**
     * Mendaftarkan rute PWA Builder ke RouteCollection CI4
     */
    public static function register($routes): void {
        $prefix = ConfigResolver::getEnv('PWA_PREFIX', 'pwa-builder');

        $routes->group($prefix, function($routes) {
            $routes->get('/', '\PwaBuilder\Http\Controllers\PwaController::index');
            $routes->match(['get', 'post'], 'login', '\PwaBuilder\Http\Controllers\PwaController::login');
            $routes->match(['get', 'post'], 'logout', '\PwaBuilder\Http\Controllers\PwaController::logout');
            $routes->post('save', '\PwaBuilder\Http\Controllers\PwaController::saveSettings');
            $routes->post('upload-logo', '\PwaBuilder\Http\Controllers\PwaController::uploadLogo');
            $routes->post('generate', '\PwaBuilder\Http\Controllers\PwaController::generateAssets');
            $routes->post('delete-icons', '\PwaBuilder\Http\Controllers\PwaController::deleteIcons');
            $routes->get('manifest.json', '\PwaBuilder\Http\Controllers\PwaController::serveManifest');
            $routes->get('sw.js', '\PwaBuilder\Http\Controllers\PwaController::serveServiceWorker');
            $routes->get('offline.html', '\PwaBuilder\Http\Controllers\PwaController::serveOffline');
        });

        $routes->get('manifest.json', '\PwaBuilder\Http\Controllers\PwaController::serveManifest');
        $routes->get('sw.js', '\PwaBuilder\Http\Controllers\PwaController::serveServiceWorker');
        $routes->get('offline.html', '\PwaBuilder\Http\Controllers\PwaController::serveOffline');
    }
}

