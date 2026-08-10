<?php
namespace AppetitQR\Views\Frontend\Partials;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Helpers\ImageHelper;
use AppetitQR\Helpers\TranslationHelper;
use AppetitQR\Services\LabelService;
use AppetitQR\Services\PriceFormatter;

class ProductCard {

    const IMAGE_SIZES = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';

    static function render(
        array $product,
        string $lang,
        PriceFormatter $prices,
        LabelService $labels,
        array $widths,
        bool $showImages,
        bool $showCart,
        bool $isDineIn = false,
        array $nutrition = []
    ): void {
        $name        = TranslationHelper::pick($product['translations'] ?? null, $lang, 'name');
        $description = TranslationHelper::pick($product['translations'] ?? null, $lang, 'description');
        $variations  = is_array($product['variations'] ?? null) ? $product['variations'] : [];
        $isAvailable = !isset($product['isAvailable']) || $product['isAvailable'];
        $image       = $product['imageSquare'] ?? $product['imageLandscape'] ?? null;

        // The card carries everything the popup and cart need, so opening a product
        // never costs another request.
        $payload = self::buildPayload($product, $lang, $name, $description, $prices, $nutrition);

        $classes = 'apq-product';
        if (!$isAvailable) {
            $classes .= ' is-unavailable';
        }
        if (!empty($product['isFeatured'])) {
            $classes .= ' is-featured';
        }
        ?>
        <?php
        // The whole card opens the product, as on the diner-facing menu, where the card
        // is a role="button" wrapper (MenuClient.tsx:390). role + tabindex keep it
        // reachable by keyboard; the script handles Enter/Space and ignores clicks that
        // came from the add-to-cart button nested inside.
        ?>
        <article
            class="<?php echo esc_attr($classes); ?>"
            data-apq-product="<?php echo esc_attr($product['id'] ?? ''); ?>"
            data-apq-search-text="<?php echo esc_attr(self::searchText($name, $description)); ?>"
            data-apq-open-product
            role="button"
            tabindex="0"
            aria-label="<?php echo esc_attr($name); ?>"
        >
            <?php if ($showImages && $image): ?>
                <div class="apq-product-media">
                    <div class="apq-product-media-btn">
                        <img
                            <?php ImageHelper::printImageAttrs($image, $widths, self::IMAGE_SIZES); ?>
                            alt="<?php echo esc_attr($name); ?>"
                        />
                    </div>
                    <?php if (!$isAvailable): ?>
                        <span class="apq-badge apq-badge-unavailable">
                            <?php echo esc_html($labels->get('unavailable', __('Unavailable', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="apq-product-body">
                <h4 class="apq-product-title">
                    <?php echo esc_html($name); ?>
                </h4>

                <?php if ($description !== ''): ?>
                    <p class="apq-product-description"><?php echo esc_html($description); ?></p>
                <?php endif; ?>

                <?php self::renderTags($product); ?>

                <div class="apq-product-footer">
                    <div class="apq-product-price">
                        <?php self::renderPrice($variations, $prices); ?>
                    </div>

                    <?php if ($showCart && $isAvailable && !empty($variations)): ?>
                        <button
                            type="button"
                            class="apq-add-to-cart"
                            data-apq-add-to-cart
                            data-apq-variation="<?php echo esc_attr($variations[0]['id'] ?? ''); ?>"
                        >
                            <?php echo esc_html($isDineIn
                                ? $labels->get('save_to_list', __('Save to list', 'sakura-pixel-menu-embed-for-appetitqr'))
                                : $labels->get('add_to_cart', __('Add to cart', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </button>
                    <?php elseif (!$isAvailable): ?>
                        <span class="apq-unavailable-note">
                            <?php echo esc_html($labels->get('unavailable', __('Unavailable', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <script type="application/json" data-apq-product-data>
                <?php echo wp_json_encode($payload); ?>
            </script>
        </article>
        <?php
    }

    /**
     * A product with one variation shows a single price; several show the lowest as a
     * "from" price, matching the diner-facing card.
     */
    private static function renderPrice(array $variations, PriceFormatter $prices): void {
        if (empty($variations)) {
            return;
        }

        if (count($variations) === 1) {
            $variation = $variations[0];
            $hasSale   = isset($variation['salePrice']) && is_numeric($variation['salePrice']);

            if ($hasSale) {
                ?>
                <span class="apq-price-original"><?php echo esc_html($prices->format($variation['price'] ?? 0)); ?></span>
                <span class="apq-price-sale"><?php echo esc_html($prices->format($variation['salePrice'])); ?></span>
                <?php
            } else {
                ?>
                <span class="apq-price"><?php echo esc_html($prices->format($variation['price'] ?? 0)); ?></span>
                <?php
            }

            return;
        }
        ?>
        <span class="apq-price apq-price-from">
            <?php
            printf(
                /* translators: %s: lowest price across a product's variations */
                esc_html__('from %s', 'sakura-pixel-menu-embed-for-appetitqr'),
                esc_html($prices->formatFrom($variations))
            );
            ?>
        </span>
        <?php
    }

    private static function renderTags(array $product): void {
        $dietary = is_array($product['dietaryTags'] ?? null) ? $product['dietaryTags'] : [];
        if (empty($dietary)) {
            return;
        }
        ?>
        <ul class="apq-tags">
            <?php foreach (array_slice($dietary, 0, 3) as $tag): ?>
                <?php if (is_string($tag)): ?>
                    <li class="apq-tag"><?php echo esc_html($tag); ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    private static function searchText(string $name, string $description): string {
        return trim($name . ' ' . $description);
    }

    /**
     * Turns the stored {"FAT":8} map into display-ready chips: "🥑 Total Fat 8g".
     *
     * The stored map has no order, no units and bare codes for names, so it is walked in
     * the canonical order the API sends (rather than JSON key order, which is arbitrary)
     * and each entry paired with its translated label and unit. Mirrors how the product
     * page renders them in ProductDetailPage.tsx.
     */
    private static function buildNutrition(mixed $values, array $nutrition, string $lang): array {
        if (!is_array($values) || empty($values)) {
            return [];
        }

        $order  = is_array($nutrition['order'] ?? null) ? $nutrition['order'] : array_keys($values);
        $units  = is_array($nutrition['units'] ?? null) ? $nutrition['units'] : [];
        $emojis = is_array($nutrition['emojis'] ?? null) ? $nutrition['emojis'] : [];
        $labels = $nutrition['labels'][$lang] ?? ($nutrition['labels']['en'] ?? []);

        $entries = [];
        foreach ($order as $code) {
            if (!isset($values[$code]) || !is_numeric($values[$code])) {
                continue;
            }

            $entries[] = [
                'label' => is_string($labels[$code] ?? null) ? $labels[$code] : $code,
                'value' => $values[$code] . (is_string($units[$code] ?? null) ? $units[$code] : ''),
                'emoji' => is_string($emojis[$code] ?? null) ? $emojis[$code] : '',
            ];
        }

        return $entries;
    }

    /**
     * Everything the popup and cart render, pre-translated and pre-formatted so the
     * client never has to repeat the language/price logic.
     */
    private static function buildPayload(array $product, string $lang, string $name, string $description, PriceFormatter $prices, array $nutrition = []): array {
        $variations = [];
        foreach ((is_array($product['variations'] ?? null) ? $product['variations'] : []) as $index => $variation) {
            $hasSale = isset($variation['salePrice']) && is_numeric($variation['salePrice']);

            $variations[] = [
                'id'             => $variation['id'] ?? '',
                'name'           => TranslationHelper::variationName($product['translations'] ?? null, $lang, $index, $variation['name'] ?? ''),
                'price'          => (int) ($variation['price'] ?? 0),
                'salePrice'      => $hasSale ? (int) $variation['salePrice'] : null,
                'effectivePrice' => (int) ($hasSale ? $variation['salePrice'] : ($variation['price'] ?? 0)),
                'priceLabel'     => $prices->format($hasSale ? $variation['salePrice'] : ($variation['price'] ?? 0)),
                'portionSize'    => $variation['portionSize'] ?? null,
                'portionUnit'    => $variation['portionUnit'] ?? null,
            ];
        }

        return [
            'id'                => $product['id'] ?? '',
            'name'              => $name,
            'description'       => $description,
            'additionalInfo'    => TranslationHelper::pick($product['translations'] ?? null, $lang, 'additionalInfo'),
            'isAvailable'       => !isset($product['isAvailable']) || (bool) $product['isAvailable'],
            'image'             => $product['imageSquare'] ?? $product['imageLandscape'] ?? null,
            'gallery'           => is_array($product['galleryImages'] ?? null) ? $product['galleryImages'] : [],
            'allergens'         => is_array($product['allergens'] ?? null) ? $product['allergens'] : [],
            'dietaryTags'       => is_array($product['dietaryTags'] ?? null) ? $product['dietaryTags'] : [],
            'nutrition'         => self::buildNutrition($product['nutritionalValues'] ?? null, $nutrition, $lang),
            'variations'        => $variations,
        ];
    }
}
