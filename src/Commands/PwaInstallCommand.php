<?php
namespace PwaBuilder\Commands;

use Illuminate\Console\Command;
use PwaBuilder\Core\PwaManager;
use PwaBuilder\Core\ConfigResolver;

class PwaInstallCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pwa:install {--force : Overwrite existing files if they exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inisialisasi PWA Builder, generate asset default, dan opsional pasang tag ke layout Blade';

    /**
     * Execute the console command.
     */
    public function handle() {
        $this->info('🚀 Menginisialisasi Progressive Web App (PWA) Builder...');

        $manager = PwaManager::getInstance();

        // 1. Buat folder public/pwa/icons jika belum ada
        $iconsDir = ConfigResolver::getPublicPath('pwa' . DIRECTORY_SEPARATOR . 'icons');
        ConfigResolver::ensureDirectoryExists($iconsDir);
        $this->line("📁 Direktori aset: <comment>{$iconsDir}</comment>");

        // 2. Generate aset PWA bawaan (manifest.json, sw.js, offline.html)
        $this->line('⚙️ Menulis berkas manifest.json, sw.js, dan offline.html...');
        $results = $manager->writeAssets();

        if ($results['manifest'] && $results['sw'] && $results['offline']) {
            $this->info('✅ Berkas aset PWA berhasil dibuat di folder public!');
        } else {
            foreach ($results['errors'] as $err) {
                $this->warn("⚠️ {$err}");
            }
        }

        // 3. Deteksi layout Blade untuk auto-inject
        $layoutCandidates = [
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/layout/app.blade.php'),
            resource_path('views/layouts/main.blade.php'),
            resource_path('views/app.blade.php'),
        ];

        $targetLayout = null;
        foreach ($layoutCandidates as $candidate) {
            if (file_exists($candidate)) {
                $targetLayout = $candidate;
                break;
            }
        }

        if ($targetLayout) {
            $content = file_get_contents($targetLayout);
            $hasHeader = str_contains($content, '@pwaHeader') || str_contains($content, 'pwa_header');
            $hasRegister = str_contains($content, '@pwaRegister') || str_contains($content, 'pwa_register');

            if ($hasHeader && $hasRegister) {
                $this->info("ℹ️ Tag PWA sudah terpasang di layout: <comment>{$targetLayout}</comment>");
            } else {
                if ($this->confirm("Apakah Anda ingin memasang tag @pwaHeader dan @pwaRegister secara otomatis ke [{$targetLayout}]?", true)) {
                    // Sisipkan @pwaHeader sebelum </head>
                    if (!$hasHeader && str_contains($content, '</head>')) {
                        $content = str_replace('</head>', "    @pwaHeader\n</head>", $content);
                    }

                    // Sisipkan @pwaRegister sebelum </body>
                    if (!$hasRegister && str_contains($content, '</body>')) {
                        $content = str_replace('</body>', "    @pwaRegister\n</body>", $content);
                    }

                    file_put_contents($targetLayout, $content);
                    $this->info("🎉 Sukses! Tag @pwaHeader dan @pwaRegister telah otomatis disisipkan ke layout.");
                } else {
                    $this->line("👉 Anda dapat mencopas secara manual:");
                    $this->line("   - Di dalam <head> : <comment>@pwaHeader</comment>");
                    $this->line("   - Sebelum </body> : <comment>@pwaRegister</comment>");
                }
            }
        } else {
            $this->line("\n💡 Tips Pemasangan View:");
            $this->line("   Cukup copas 2 baris berikut ke layout utama Blade Anda:");
            $this->line("   - Di dalam <head> : <comment>@pwaHeader</comment>");
            $this->line("   - Sebelum </body> : <comment>@pwaRegister</comment>");
        }

        $prefix = ConfigResolver::getEnv('PWA_PREFIX', 'pwa-builder');
        $this->newLine();
        $this->info("✨ Selesai! Buka dashboard PWA di browser Anda:");
        $this->line("   👉 <comment>" . url($prefix) . "</comment>");

        return 0;
    }
}

