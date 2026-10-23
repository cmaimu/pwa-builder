<?php
namespace PwaBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string header()
 * @method static string registerScript()
 * @method static array getConfig()
 * @method static bool saveConfig(array $data)
 * @method static array writeAssets()
 * @method static array getStatus()
 * 
 * @see \PwaBuilder\Core\PwaManager
 */
class PwaBuilder extends Facade {
    protected static function getFacadeAccessor() {
        return \PwaBuilder\Core\PwaManager::class;
    }
}

