<?php
namespace PwaBuilder\Adapters;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use PwaBuilder\Core\ConfigResolver;
use PwaBuilder\Core\PwaManager;
use PwaBuilder\Http\Controllers\PwaController;

class LaravelServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register() {
        $this->app->singleton('PwaBuilder', function() {
            return PwaManager::getInstance();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot() {
        $prefix = ConfigResolver::getEnv('PWA_PREFIX', 'pwa-builder');

        // Middleware kustom dari .env (default: 'web', dapat diganti ke 'web,auth' dll)
        $middlewareEnv = (string) ConfigResolver::getEnv('PWA_MIDDLEWARE', 'web');
        $webMiddleware = array_values(array_filter(array_map('trim', explode(',', $middlewareEnv))));

        // Registrasi Route Dashboard & Pengaturan PWA
        Route::group(['prefix' => $prefix, 'middleware' => $webMiddleware], function() {
            Route::get('/', [PwaController::class, 'index']);
            Route::get('/login', [PwaController::class, 'login']);
            Route::post('/login', [PwaController::class, 'login']);
            Route::get('/logout', [PwaController::class, 'logout']);
            Route::post('/logout', [PwaController::class, 'logout']);
            Route::post('/save', [PwaController::class, 'saveSettings']);
            Route::post('/upload-logo', [PwaController::class, 'uploadLogo']);
            Route::post('/generate', [PwaController::class, 'generateAssets']);
            Route::post('/delete-icons', [PwaController::class, 'deleteIcons']);
            
            // Dynamic endpoints jika file static belum terbit
            Route::get('/manifest.json', [PwaController::class, 'serveManifest']);
            Route::get('/sw.js', [PwaController::class, 'serveServiceWorker']);
            Route::get('/offline.html', [PwaController::class, 'serveOffline']);
        });

        // Dynamic root routes untuk manifest, sw, offline jika file statis belum digenerate
        Route::get('/manifest.json', [PwaController::class, 'serveManifest']);
        Route::get('/sw.js', [PwaController::class, 'serveServiceWorker']);
        Route::get('/offline.html', [PwaController::class, 'serveOffline']);

        // Registrasi Blade Directives
        Blade::directive('pwaHeader', function() {
            return '<?php echo \PwaBuilder\Facades\PwaBuilder::header(); ?>';
        });

        Blade::directive('pwaRegister', function() {
            return '<?php echo \PwaBuilder\Facades\PwaBuilder::registerScript(); ?>';
        });

        // Vendor publish & Artisan commands jika dijalankan via console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \PwaBuilder\Commands\PwaInstallCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../Views' => resource_path('views/vendor/pwa-builder'),
            ], 'pwa-builder-views');
        }
    }
}

