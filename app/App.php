<?php
namespace AppetitQR;

if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Hooks\HooksFactory;
use AppetitQR\Services\MenuCacheService;
use AppetitQR\Utils\OptionUtils;

class App {

    static function run(mixed $file): void {
        HooksFactory::registerHooks();

        register_activation_hook($file, [self::class, 'onActivate']);
        register_uninstall_hook($file, [self::class, 'onUninstall']);
    }

    static function onActivate(): void {
        // Nothing to provision — the plugin holds no tables and no cron jobs. Menus are
        // pulled on demand and cached in transients, so a fresh install starts empty.
        MenuCacheService::purgeAll();
    }

    static function onUninstall(): void {
        delete_option(OptionUtils::OPTION_GROUP_SLUG);
        MenuCacheService::purgeAll();
    }
}
