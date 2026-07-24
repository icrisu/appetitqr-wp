<?php
namespace AppetitQR\Hooks;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Controllers\IntegrationCtrl;

class ActionAjax implements IHook {

    static function register() {
        $actions = self::getActions();
        foreach ($actions as $action) {
            add_action('wp_ajax_' . $action['key'], $action['callback']);
            $supports_frontend = $action['supports_frontend'] ?? false;
            if ($supports_frontend) {
                add_action('wp_ajax_nopriv_' . $action['key'], $action['callback']);
            }
        }
    }

    static function getActions(): array {
        // Admin-only by design: both actions are settings-screen tools and neither has a
        // frontend caller, so nopriv stays off.
        return [
            ['key' => 'appetitqr_test_connection', 'callback' => [IntegrationCtrl::class, 'testConnection']],
            ['key' => 'appetitqr_clear_cache', 'callback' => [IntegrationCtrl::class, 'clearCache']],
        ];
    }
}
