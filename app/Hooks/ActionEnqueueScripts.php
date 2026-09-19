<?php
namespace AppetitQR\Hooks;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Config\Config;
use AppetitQR\Controllers\IntegrationCtrl;
use AppetitQR\Hooks\IHook;

class ActionEnqueueScripts implements IHook {

    /** Menu stylesheet, and the companion handle its per-instance palette rides on. */
    const STYLE_HANDLE        = 'appetitqr-menu';
    const INLINE_STYLE_HANDLE = 'appetitqr-menu-inline';
    const SCRIPT_HANDLE       = 'appetitqr-menu';
    const FUSE_HANDLE         = 'appetitqr-fuse';

    private static bool $frontendRegistered = false;

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

    /**
     * Version admin assets by file mtime: the server sends no Cache-Control, so a fixed
     * plugin version lets browsers keep serving a stale copy after the file changes.
     */
    private static function assetVersion(string $relativePath): string {
        $mtime = @filemtime(APPETITQR_APP_PATH . ltrim($relativePath, '/'));

        return $mtime ? APPETITQR_VERSION . '.' . $mtime : APPETITQR_VERSION;
    }

    private static function loadAdminAssets() {
        $jsPath  = 'assets/admin/appetit-settings/dist/index.js';
        $cssPath = 'assets/admin/appetit-settings/dist/index.css';

        wp_register_script('appetitqr-admin-settings', APPETITQR_APP_PUBLIC_URL . '/' . $jsPath, ['jquery'], self::assetVersion($jsPath), true);

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
        wp_enqueue_style('appetitqr-admin-settings', APPETITQR_APP_PUBLIC_URL . '/' . $cssPath, [], self::assetVersion($cssPath));
    }

    /**
     * Handles are registered on every frontend request, but enqueued only where the menu
     * actually appears, so a site with one menu page does not carry this CSS/JS everywhere.
     */
    static function loadFrontendAssets() {
        self::registerFrontendAssets();

        if (self::currentPostHasShortcode()) {
            self::enqueueFrontendAssets();
        }
    }

    /**
     * Registration is deliberately separate from enqueueing: the shortcode enqueues these
     * handles itself (MenuView::render) for the placements the content sniff below cannot
     * see — a widget, a template part, or a block that builds its content at render time.
     */
    static function registerFrontendAssets(): void {
        if (self::$frontendRegistered) {
            return;
        }
        self::$frontendRegistered = true;

        $frontendUrl = APPETITQR_APP_PUBLIC_URL . '/app/Views/Frontend/assets';

        wp_register_style(self::STYLE_HANDLE, $frontendUrl . '/dist/index.css', [], APPETITQR_VERSION);

        // Src-less carrier for the per-instance palette ThemeService builds while the
        // shortcode renders. By then the menu stylesheet has usually been printed, and a
        // finished handle accepts no further inline CSS, so the palette needs a handle of
        // its own: this one is enqueued late and printed with the footer styles.
        wp_register_style(self::INLINE_STYLE_HANDLE, false, [self::STYLE_HANDLE], APPETITQR_VERSION);

        wp_register_script(self::FUSE_HANDLE, $frontendUrl . '/vendor/fuse.js@7.1.0.js', [], '7.1.0', true);
        wp_register_script(self::SCRIPT_HANDLE, $frontendUrl . '/dist/index.js', [self::FUSE_HANDLE], APPETITQR_VERSION, true);

        // Only strings the script builds at runtime need to cross over; everything else
        // is already rendered (and translated) server-side.
        wp_localize_script(self::SCRIPT_HANDLE, 'AppetitQRMenu', [
            'labels' => [
                /* translators: %s: formatted minimum order amount */
                'minimumOrder' => esc_html__('Minimum order: %s', 'sakura-pixel-menu-embed-for-appetitqr'),
            ],
        ]);
    }

    static function enqueueFrontendAssets(): void {
        self::registerFrontendAssets();

        wp_enqueue_style(self::STYLE_HANDLE);
        wp_enqueue_script(self::SCRIPT_HANDLE);
    }

    private static function currentPostHasShortcode(): bool {
        global $post;

        if (!isset($post->post_content) || !is_string($post->post_content)) {
            return false;
        }

        $tag = Config::getInstance()->getSetting('shortcode_tag', 'wp_appetitqr');

        return has_shortcode($post->post_content, $tag);
    }
}
