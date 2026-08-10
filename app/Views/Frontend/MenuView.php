<?php
namespace AppetitQR\Views\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Helpers\TranslationHelper;
use AppetitQR\Services\LabelService;
use AppetitQR\Services\MenuApiService;
use AppetitQR\Services\MenuCacheService;
use AppetitQR\Services\PriceFormatter;
use AppetitQR\Services\ThemeService;
use AppetitQR\Views\Frontend\Partials\CartPanel;
use AppetitQR\Views\Frontend\Partials\CategoryNav;
use AppetitQR\Views\Frontend\Partials\InfoBlock;
use AppetitQR\Views\Frontend\Partials\ProductCard;
use AppetitQR\Views\Frontend\Partials\ProductModal;
use AppetitQR\Views\Frontend\Partials\SearchBar;

/**
 * Entry point for [wp_appetitqr].
 *
 * The menu is rendered server-side from a cached API payload, so it is present in the
 * page source for search engines and readable without JavaScript. The bundled script
 * only enhances it (search, category scroll-spy, product popup, cart).
 */
class MenuView {

    private static int $instanceCount = 0;

    static function render(array $atts = [], mixed $content = null) {
        $atts = shortcode_atts([
            'api_key'     => '',
            'lang'        => '',
            'show_search' => '1',
            'show_info'   => '1',
            'show_cart'   => '1',
            'show_images' => '1',
            'columns'     => '3',
            'dinein'      => 'false',
        ], $atts, 'wp_appetitqr');

        $apiKey = sanitize_text_field($atts['api_key']);

        if ($apiKey === '') {
            return self::renderNotice(esc_html__('Add your AppetitQR API key to the shortcode: [wp_appetitqr api_key="…"]', 'sakura-pixel-menu-embed-for-appetitqr'));
        }

        $result = MenuCacheService::getMenu($apiKey);

        if (!$result['success']) {
            return self::renderNotice(MenuApiService::describeError($result['error']));
        }

        $data       = $result['data'];
        $location   = is_array($data['location'] ?? null) ? $data['location'] : [];
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $products   = is_array($data['products'] ?? null) ? $data['products'] : [];
        $languages  = is_array($data['languages'] ?? null) ? $data['languages'] : [];
        $theme      = is_array($data['theme'] ?? null) ? $data['theme'] : [];
        $widths     = is_array($data['imageWidths'] ?? null) ? $data['imageWidths'] : [];
        $nutrition  = is_array($data['nutrition'] ?? null) ? $data['nutrition'] : [];

        $lang     = TranslationHelper::resolveLang($languages, sanitize_text_field($atts['lang']));
        $isRtl    = TranslationHelper::isRtl($languages, $lang);
        $labels   = new LabelService(is_array($data['labels'] ?? null) ? $data['labels'] : [], $lang);
        $prices   = new PriceFormatter(is_array($data['currency'] ?? null) ? $data['currency'] : []);

        $showSearch = self::isTruthy($atts['show_search']);
        $showInfo   = self::isTruthy($atts['show_info']);
        $showImages = self::isTruthy($atts['show_images']);
        $columns    = max(1, min(4, absint($atts['columns'])));

        // Dine-in is a different product, not a display option: the guest builds a wishlist
        // to show their server, and there is no ordering path at all. It is opt-in per
        // shortcode so a restaurant can publish a dedicated table-QR page.
        $isDineIn = self::isTruthy($atts['dinein']);

        // The list is opt-in on both sides: the shortcode may hide it, and the location's
        // own settings in AppetitQR decide whether it is available — each mode has its
        // own gate, mirroring MenuClient.tsx.
        $cartAllowed = $isDineIn
            ? !empty($location['dineInAllowTemporaryCart'])
            : !empty($location['allowTemporaryCart']);
        $showCart    = self::isTruthy($atts['show_cart']) && $cartAllowed;

        self::$instanceCount++;
        $instanceId = 'appetitqr-app-' . self::$instanceCount;

        $productsByCategory = self::groupByCategory($categories, $products, $lang);

        ob_start();

        ThemeService::printScopedStyles($instanceId, $theme);
        ?>
        <div
            id="<?php echo esc_attr($instanceId); ?>"
            class="appetitqr-app apq-tpl-<?php echo esc_attr(ThemeService::getTemplateId($theme)); ?><?php echo ThemeService::isDarkScheme($theme) ? ' apq-scheme-dark' : ''; ?><?php echo $showImages ? '' : ' apq-no-images'; ?><?php echo $isDineIn ? ' apq-mode-dinein' : ''; ?>"
            data-apq-instance="<?php echo esc_attr($instanceId); ?>"
            data-apq-slug="<?php echo esc_attr($location['slug'] ?? ''); ?>"
            data-apq-lang="<?php echo esc_attr($lang); ?>"
            data-apq-columns="<?php echo esc_attr($columns); ?>"
            data-apq-has-cart="<?php echo $showCart ? '1' : '0'; ?>"
            data-apq-mode="<?php echo $isDineIn ? 'dinein' : 'order'; ?>"
            data-apq-currency="<?php echo esc_attr(wp_json_encode([
                'symbol'   => $prices->getSymbol(),
                'position' => $prices->getPosition(),
                'decimals' => $prices->getDecimals(),
            ])); ?>"
            <?php if ($isRtl): ?>dir="rtl"<?php endif; ?>
        >
            <?php if ($result['stale']): ?>
                <?php // Served from the stale copy because the API was unreachable. Only editors see why. ?>
                <?php if (current_user_can('edit_posts')): ?>
                    <div class="apq-admin-notice">
                        <?php esc_html_e('AppetitQR: showing a cached copy of this menu — the API could not be reached.', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php InfoBlock::renderHeader($location, $widths, $lang, ThemeService::getTemplateId($theme)); ?>

            <div class="apq-toolbar">
                <?php if ($showSearch): ?>
                    <?php SearchBar::render($labels); ?>
                <?php endif; ?>
                <?php if ($showCart): ?>
                    <button type="button" class="apq-cart-toggle" data-apq-open-cart aria-haspopup="dialog">
                        <span class="apq-cart-toggle-label">
                            <?php echo esc_html($isDineIn
                                ? $labels->get('my_list_title', __('My List', 'sakura-pixel-menu-embed-for-appetitqr'))
                                : $labels->get('cart', __('Cart', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </span>
                        <span class="apq-cart-count" data-apq-cart-count hidden>0</span>
                    </button>
                <?php endif; ?>
            </div>

            <?php CategoryNav::render($productsByCategory, $instanceId); ?>

            <div class="apq-empty-search" data-apq-empty hidden>
                <?php echo esc_html($labels->get('no_products_found', __('No products found.', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
            </div>

            <div class="apq-menu" data-apq-menu>
                <?php if (empty($productsByCategory)): ?>
                    <p class="apq-empty">
                        <?php echo esc_html($labels->get('no_items_available', __('No items available.', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($productsByCategory as $group): ?>
                        <section
                            class="apq-category"
                            id="<?php echo esc_attr($instanceId . '-cat-' . $group['id']); ?>"
                            data-apq-category="<?php echo esc_attr($group['id']); ?>"
                        >
                            <h3 class="apq-category-title"><?php echo esc_html($group['name']); ?></h3>
                            <div class="apq-products" style="--apq-columns:<?php echo esc_attr($columns); ?>">
                                <?php foreach ($group['products'] as $product): ?>
                                    <?php ProductCard::render($product, $lang, $prices, $labels, $widths, $showImages, $showCart, $isDineIn, $nutrition); ?>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($showInfo): ?>
                <?php InfoBlock::render($location, $labels, $lang); ?>
            <?php endif; ?>

            <?php ProductModal::renderShell($labels, $isDineIn); ?>

            <?php if ($showCart): ?>
                <?php CartPanel::render($location, $labels, $prices, $isDineIn); ?>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Buckets products under their categories, preserving the account's category order
     * and dropping empty categories. A product in several categories appears in each,
     * matching the diner-facing menu.
     */
    private static function groupByCategory(array $categories, array $products, string $lang): array {
        $groups = [];

        foreach ($categories as $category) {
            $categoryId = $category['id'] ?? null;
            if (!$categoryId) {
                continue;
            }

            $matching = [];
            foreach ($products as $product) {
                foreach (($product['categories'] ?? []) as $link) {
                    if (($link['categoryId'] ?? null) === $categoryId) {
                        $matching[] = $product;
                        break;
                    }
                }
            }

            if (empty($matching)) {
                continue;
            }

            $groups[] = [
                'id'       => $categoryId,
                'name'     => self::categoryName($category, $lang),
                'products' => $matching,
            ];
        }

        // Products with no category would silently vanish otherwise.
        $uncategorized = [];
        foreach ($products as $product) {
            if (empty($product['categories'])) {
                $uncategorized[] = $product;
            }
        }
        if (!empty($uncategorized)) {
            $groups[] = [
                'id'       => 'uncategorized',
                'name'     => __('Other', 'sakura-pixel-menu-embed-for-appetitqr'),
                'products' => $uncategorized,
            ];
        }

        return $groups;
    }

    /**
     * Category rows carry their own translations; `name` is the untranslated fallback.
     */
    private static function categoryName(array $category, string $lang): string {
        $translated = TranslationHelper::pick($category['translations'] ?? null, $lang, 'name');

        return $translated !== '' ? $translated : ($category['name'] ?? '');
    }

    private static function isTruthy(mixed $value): bool {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Configuration problems are shown to users who can fix them; visitors see nothing
     * rather than a broken-looking page.
     */
    private static function renderNotice(string $message): string {
        if (!current_user_can('edit_posts')) {
            return '';
        }

        return '<div class="appetitqr-app apq-notice">'
            . '<strong>' . esc_html__('AppetitQR', 'sakura-pixel-menu-embed-for-appetitqr') . ':</strong> '
            . esc_html($message)
            . '</div>';
    }
}
