<?php
namespace AppetitQR\Hooks;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Config\Config;
use AppetitQR\Controllers\IntegrationCtrl;
use AppetitQR\Hooks\IHook;

class ActionEnqueueScripts implements IHook {

    static function register() {
        add_action('admin_enqueue_scripts', [self::class, 'loadAdminScripts']);
        add_action('wp_enqueue_scripts', [self::class, 'loadFrontendAssets']);
    }

    static function loadAdminScripts() {
        $current_screen      = get_current_screen();
        $isAdminSettingsPage = $current_screen && $current_screen->id === 'toplevel_page_' . Config::getInstance()->getSetting('main_settings_page_slug');

        if ($isAdminSettingsPage) {
            wp_enqueue_script('jquery');
            self::loadAdminAssets();
        }
    }

    private static function loadAdminAssets() {
        wp_register_script('appetitqr-admin-settings', APPETITQR_APP_PUBLIC_URL . '/assets/admin/appetit-settings/dist/index.js', ['jquery'], APPETITQR_VERSION, true);

        wp_localize_script('appetitqr-admin-settings', 'APPETITQR_ADMIN_SETTINGS', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce(IntegrationCtrl::NONCE_ACTION),
            'labels'   => [
                'testing'       => esc_html__('Testing…', 'sakura-pixel-menu-embed-for-appetitqr'),
                'testConnection'=> esc_html__('Test connection', 'sakura-pixel-menu-embed-for-appetitqr'),
                'clearing'      => esc_html__('Clearing…', 'sakura-pixel-menu-embed-for-appetitqr'),
                'clearCache'    => esc_html__('Clear menu cache', 'sakura-pixel-menu-embed-for-appetitqr'),
                'requestFailed' => esc_html__('The request failed. Please try again.', 'sakura-pixel-menu-embed-for-appetitqr'),
                'confirmClear'  => esc_html__('Clear every cached menu? The next page view will fetch a fresh copy.', 'sakura-pixel-menu-embed-for-appetitqr'),
                'location'      => esc_html__('Location', 'sakura-pixel-menu-embed-for-appetitqr'),
                'template'      => esc_html__('Template', 'sakura-pixel-menu-embed-for-appetitqr'),
                'products'      => esc_html__('Products', 'sakura-pixel-menu-embed-for-appetitqr'),
                'categories'    => esc_html__('Categories', 'sakura-pixel-menu-embed-for-appetitqr'),
            ],
        ]);

        wp_enqueue_script('appetitqr-admin-settings');
        wp_enqueue_style('appetitqr-admin-settings', APPETITQR_APP_PUBLIC_URL . '/assets/admin/appetit-settings/dist/index.css', null, APPETITQR_VERSION);
    }

    /**
     * Frontend assets load only on content that actually contains the shortcode, so a
     * site with one menu page does not carry this CSS/JS everywhere.
     */
    static function loadFrontendAssets() {
        if (!self::currentPostHasShortcode()) {
            return;
        }

        $frontendUrl = APPETITQR_APP_PUBLIC_URL . '/app/Views/Frontend/assets';

        wp_enqueue_style('appetitqr-menu', $frontendUrl . '/dist/index.css', [], APPETITQR_VERSION);

        wp_enqueue_script('appetitqr-fuse', $frontendUrl . '/vendor/fuse.js@7.1.0.js', [], '7.1.0', true);
        wp_enqueue_script('appetitqr-menu', $frontendUrl . '/dist/index.js', ['appetitqr-fuse'], APPETITQR_VERSION, true);

        // Only strings the script builds at runtime need to cross over; everything else
        // is already rendered (and translated) server-side.
        wp_localize_script('appetitqr-menu', 'AppetitQRMenu', [
            'labels' => [
                /* translators: %s: formatted minimum order amount */
                'minimumOrder' => esc_html__('Minimum order: %s', 'sakura-pixel-menu-embed-for-appetitqr'),
            ],
        ]);
    }

    private static function currentPostHasShortcode(): bool {
        global $post;

        if (!isset($post->post_content) || !is_string($post->post_content)) {
            return false;
        }

        $tag = Config::getInstance()->getSetting('shortcode_tag', 'wp_appetitqr');

        return has_shortcode($post->post_content, $tag);
    }

    static function enqueRemoteStyle($key, $url, $version = 'v1'): void {
        $protocol = is_ssl() ? 'https' : 'http';
        wp_enqueue_style($key, $protocol . $url, null, $version);
    }

    static function enqueRemoteScript($key, $url, array $dependencies = [], string $version = 'v1', bool $inFooter = true): void {
        $protocol = is_ssl() ? 'https' : 'http';
        wp_enqueue_script($key, $protocol . $url, $dependencies, $version, $inFooter);
    }
}
