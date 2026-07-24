<?php
namespace AppetitQR\Hooks;

if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Hooks\IHook;
use AppetitQR\Config\Config;
use AppetitQR\Views\Admin\Settings\MainSettings;

class ActionAdminMenu implements IHook {

    static function register() {
        add_action('admin_menu', [self::class, 'adminMenuHandler']);
    }

    static function adminMenuHandler(): void {
        $mainSettings         = new MainSettings();
        $mainSettingsPageSlug = Config::getInstance()->getSetting('main_settings_page_slug', '');

        add_menu_page(
            esc_html__('AppetitQR Settings', 'appetitqr'),
            esc_html__('AppetitQR', 'appetitqr'),
            'manage_options',
            $mainSettingsPageSlug,
            [$mainSettings, 'render'],
            'dashicons-food',
            81
        );
    }
}
