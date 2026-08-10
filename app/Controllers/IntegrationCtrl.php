<?php
namespace AppetitQR\Controllers;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Services\MenuApiService;
use AppetitQR\Services\MenuCacheService;

class IntegrationCtrl {

    const NONCE_ACTION = 'appetitqr_app_settings_nonce';

    /**
     * Validates a pasted API key against the live endpoint and reports what it resolved
     * to, so an admin can confirm the connection before publishing a shortcode.
     */
    static function testConnection(): void {
        self::requireManageOptions();

        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => esc_html__('Security check failed. Please reload the page.', 'sakura-pixel-menu-embed-for-appetitqr')], 400);
        }

        $apiKey = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

        if ($apiKey === '') {
            wp_send_json_error(['message' => esc_html__('Please enter an API key to test.', 'sakura-pixel-menu-embed-for-appetitqr')]);
        }

        $result = MenuApiService::fetchMenu($apiKey);

        if (!$result['success']) {
            wp_send_json_error(['message' => MenuApiService::describeError($result['error'])]);
        }

        $data     = $result['data'];
        $location = $data['location'] ?? [];

        wp_send_json_success([
            'message'    => esc_html__('Connection successful.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'location'   => isset($location['name']) ? sanitize_text_field($location['name']) : '',
            'products'   => is_array($data['products'] ?? null) ? count($data['products']) : 0,
            'categories' => is_array($data['categories'] ?? null) ? count($data['categories']) : 0,
            'template'   => sanitize_text_field($data['theme']['webApp'] ?? ''),
        ]);
    }

    static function clearCache(): void {
        self::requireManageOptions();

        if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
            wp_send_json_error(['message' => esc_html__('Security check failed. Please reload the page.', 'sakura-pixel-menu-embed-for-appetitqr')], 400);
        }

        MenuCacheService::purgeAll();

        wp_send_json_success(['message' => esc_html__('Menu cache cleared.', 'sakura-pixel-menu-embed-for-appetitqr')]);
    }

    /**
     * Both endpoints are settings-screen tools: require the same capability that gates
     * the settings page itself. Each handler additionally verifies the request nonce.
     */
    private static function requireManageOptions(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('You are not allowed to do this.', 'sakura-pixel-menu-embed-for-appetitqr')], 403);
        }
    }
}
