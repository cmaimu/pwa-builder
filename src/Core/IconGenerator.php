<?php
namespace PwaBuilder\Core;

class IconGenerator {
    /**
     * Daftar resolusi icon standar PWA yang akan digenerate
     */
    public const ICON_DEFINITIONS = [
        ['name' => 'icon-72x72.png', 'size' => 72, 'purpose' => 'any', 'label' => '72x72 (Android Legacy)'],
        ['name' => 'icon-96x96.png', 'size' => 96, 'purpose' => 'any', 'label' => '96x96 (Android Small)'],
        ['name' => 'icon-128x128.png', 'size' => 128, 'purpose' => 'any', 'label' => '128x128 (Launcher)'],
        ['name' => 'icon-144x144.png', 'size' => 144, 'purpose' => 'any', 'label' => '144x144 (Windows Tile)'],
        ['name' => 'icon-152x152.png', 'size' => 152, 'purpose' => 'any', 'label' => '152x152 (iPad / iOS)'],
        ['name' => 'icon-192x192.png', 'size' => 192, 'purpose' => 'any', 'label' => '192x192 (Standard PWA Home)'],
        ['name' => 'icon-384x384.png', 'size' => 384, 'purpose' => 'any', 'label' => '384x384 (High-Res Android)'],
        ['name' => 'icon-512x512.png', 'size' => 512, 'purpose' => 'any', 'label' => '512x512 (Splash & Install Prompt)'],
        ['name' => 'icon-maskable-512x512.png', 'size' => 512, 'purpose' => 'maskable', 'maskable' => true, 'label' => '512x512 (Maskable / Adaptive Safe Zone)'],
        ['name' => 'apple-touch-icon.png', 'size' => 180, 'purpose' => 'any', 'label' => '180x180 (Apple iOS Touch Icon)'],
        ['name' => 'favicon-48x48.png', 'size' => 48, 'purpose' => 'any', 'label' => '48x48 (Favicon High-DPI)'],
        ['name' => 'favicon-32x32.png', 'size' => 32, 'purpose' => 'any', 'label' => '32x32 (Browser Favicon)'],
        ['name' => 'favicon-16x16.png', 'size' => 16, 'purpose' => 'any', 'label' => '16x16 (Mini Favicon)'],
    ];

    /**
     * Memproses file upload logo dan generate semua ukuran icon
     */
    public static function generateFromUpload(array $file, string $backgroundColor = '#ffffff'): array {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException("Ekstensi PHP GD (ext-gd) tidak terpasang atau tidak aktif.");
        }

        if (empty($file['tmp_name']) || (!is_uploaded_file($file['tmp_name']) && !file_exists($file['tmp_name']))) {
            throw new \InvalidArgumentException("File upload tidak valid atau kosong.");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Terjadi kesalahan saat mengunggah file (Kode error: {$file['error']}).");
        }

        // Validasi tipe gambar
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException("File yang diunggah bukan format gambar yang valid.");
        }

        $sourceImage = self::createImageResource($file['tmp_name'], $imageInfo[2]);
        if (!$sourceImage) {
            throw new \RuntimeException("Gagal membaca gambar. Pastikan format PNG, JPEG, atau WebP valid.");
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        // Siapkan direktori penyimpanan (public/pwa/icons)
        $outputDir = ConfigResolver::getPublicPath('pwa' . DIRECTORY_SEPARATOR . 'icons');
        ConfigResolver::ensureDirectoryExists($outputDir);

        $results = [];
        $rgbBg = self::hexToRgb($backgroundColor);

        foreach (self::ICON_DEFINITIONS as $def) {
            $destSize = $def['size'];
            $destFile = $outputDir . DIRECTORY_SEPARATOR . $def['name'];
            $isMaskable = !empty($def['maskable']);

            $destCanvas = imagecreatetruecolor($destSize, $destSize);

            if ($isMaskable) {
                // Untuk maskable icon: beri background warna dan beri margin aman (safe-zone 80%)
                $bgColor = imagecolorallocate($destCanvas, $rgbBg['r'], $rgbBg['g'], $rgbBg['b']);
                imagefilledrectangle($destCanvas, 0, 0, $destSize, $destSize, $bgColor);

                $targetSize = (int) round($destSize * 0.78);
                $offsetX = (int) round(($destSize - $targetSize) / 2);
                $offsetY = (int) round(($destSize - $targetSize) / 2);

                imagealphablending($destCanvas, true);
                imagecopyresampled(
                    $destCanvas,
                    $sourceImage,
                    $offsetX,
                    $offsetY,
                    0,
                    0,
                    $targetSize,
                    $targetSize,
                    $sourceWidth,
                    $sourceHeight
                );
            } else {
                // Icon transparan standar
                imagealphablending($destCanvas, false);
                imagesavealpha($destCanvas, true);
                $transparent = imagecolorallocatealpha($destCanvas, 0, 0, 0, 127);
                imagefilledrectangle($destCanvas, 0, 0, $destSize, $destSize, $transparent);
                imagealphablending($destCanvas, true);

                imagecopyresampled(
                    $destCanvas,
                    $sourceImage,
                    0,
                    0,
                    0,
                    0,
                    $destSize,
                    $destSize,
                    $sourceWidth,
                    $sourceHeight
                );
            }

            // Simpan gambar PNG
            imagepng($destCanvas, $destFile, 8);
            imagedestroy($destCanvas);

            $webUrl = ConfigResolver::getBaseUrl('pwa/icons/' . $def['name']);
            $results[] = [
                'name'     => $def['name'],
                'size'     => "{$destSize}x{$destSize}",
                'purpose'  => $def['purpose'],
                'url'      => $webUrl,
                'path'     => $destFile,
                'filesize' => file_exists($destFile) ? filesize($destFile) : 0
            ];
        }

        // Salin favicon standard ke root public jika diinginkan
        $publicFavicon = ConfigResolver::getPublicPath('favicon.ico');
        $generatedFavicon = $outputDir . DIRECTORY_SEPARATOR . 'favicon-32x32.png';
        if (file_exists($generatedFavicon) && !file_exists($publicFavicon)) {
            @copy($generatedFavicon, $publicFavicon);
        }

        // Simpan juga logo original sebagai referensi
        $originalExt = image_type_to_extension($imageInfo[2]);
        $originalPath = $outputDir . DIRECTORY_SEPARATOR . 'original-logo' . $originalExt;
        @copy($file['tmp_name'], $originalPath);

        imagedestroy($sourceImage);

        return $results;
    }

    /**
     * Mengambil daftar icon yang sudah tersedia di folder public/pwa/icons
     */
    public static function getExistingIcons(): array {
        $outputDir = ConfigResolver::getPublicPath('pwa' . DIRECTORY_SEPARATOR . 'icons');
        $icons = [];

        foreach (self::ICON_DEFINITIONS as $def) {
            $destFile = $outputDir . DIRECTORY_SEPARATOR . $def['name'];
            $exists = file_exists($destFile);
            $webUrl = ConfigResolver::getBaseUrl('pwa/icons/' . $def['name']);

            $icons[] = [
                'name'     => $def['name'],
                'size'     => "{$def['size']}x{$def['size']}",
                'purpose'  => $def['purpose'],
                'label'    => $def['label'],
                'exists'   => $exists,
                'url'      => $exists ? $webUrl . '?v=' . filemtime($destFile) : null,
                'filesize' => $exists ? filesize($destFile) : 0
            ];
        }

        return $icons;
    }

    /**
     * Menghapus semua icon yang pernah digenerate
     */
    public static function deleteGeneratedIcons(): bool {
        $outputDir = ConfigResolver::getPublicPath('pwa' . DIRECTORY_SEPARATOR . 'icons');
        if (!is_dir($outputDir)) {
            return true;
        }

        $files = glob($outputDir . DIRECTORY_SEPARATOR . '*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        return true;
    }

    /**
     * Membaca file gambar ke GD Resource
     */
    private static function createImageResource(string $path, int $type) {
        switch ($type) {
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($path);
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($path);
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    return @imagecreatefromwebp($path);
                }
                break;
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($path);
        }
        return false;
    }

    /**
     * Konversi kode warna Hex ke RGB
     */
    public static function hexToRgb(string $hex): array {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return ['r' => 255, 'g' => 255, 'b' => 255];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
}
