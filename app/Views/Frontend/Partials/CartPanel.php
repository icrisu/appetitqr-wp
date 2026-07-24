<?php
namespace AppetitQR\Views\Frontend\Partials;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Services\LabelService;
use AppetitQR\Services\PriceFormatter;
use AppetitQR\Utils\Sanitizer;

/**
 * Slide-over panel holding the guest's items. Renders one of two modes:
 *
 * - **order** — a single cart. Ordering never hits a server: the cart lives in
 *   localStorage and the order is handed to the restaurant over a tel: or wa.me deep
 *   link (see OrderCartPage.tsx). Nothing is written back and no payment is handled.
 * - **dine-in** — a wishlist the guest shows their server. There is no ordering path at
 *   all, so every ordering affordance is omitted rather than merely hidden: no phone,
 *   no WhatsApp, no shipping, no discount, no minimum-order gate. Mirrors MyListPage.tsx,
 *   including its history of timestamped lists, each with its own estimated total.
 */
class CartPanel {

    static function render(array $location, LabelService $labels, PriceFormatter $prices, bool $isDineIn = false): void {
        $isDineIn
            ? self::renderDineIn($location, $labels)
            : self::renderOrder($location, $labels, $prices);
    }

    /**
     * Dine-in: wishlist only. The script fills [data-apq-lists] with one card per stored
     * list, so nothing order-related is ever emitted into the page.
     */
    private static function renderDineIn(array $location, LabelService $labels): void {
        $config = [
            'mode'         => 'dinein',
            'locationName' => $location['name'] ?? '',
            'labels'       => [
                'quantity'    => $labels->get('quantity_label', __('Qty:', 'appetitqr')),
                'total'       => $labels->get('total_estimated', __('Total (estimated)', 'appetitqr')),
                'currentList' => $labels->get('current_list', __('Current', 'appetitqr')),
                'emptyList'   => $labels->get('empty_list', __('Empty list', 'appetitqr')),
                'empty'       => $labels->get('list_empty', __('Your list is empty. Start adding items!', 'appetitqr')),
                'deleteList'  => __('Delete list', 'appetitqr'),
            ],
        ];
        ?>
        <div class="apq-cart apq-cart-dinein" data-apq-cart hidden data-apq-cart-config="<?php echo esc_attr(wp_json_encode($config)); ?>">
            <div class="apq-cart-backdrop" data-apq-cart-close></div>
            <aside class="apq-cart-panel" role="dialog" aria-modal="true" aria-labelledby="apq-cart-title">
                <header class="apq-cart-header">
                    <h3 class="apq-cart-title" id="apq-cart-title">
                        <?php echo esc_html($labels->get('my_list_title', __('My List', 'appetitqr'))); ?>
                    </h3>
                    <button
                        type="button"
                        class="apq-cart-close"
                        data-apq-cart-close
                        aria-label="<?php echo esc_attr($labels->get('close', __('Close', 'appetitqr'))); ?>"
                    >&times;</button>
                </header>

                <p class="apq-cart-note">
                    <?php echo esc_html($labels->get('wishlist_info', __("Build a wishlist of items you'd like to order. Share it with your server!", 'appetitqr'))); ?>
                </p>

                <div class="apq-lists" data-apq-lists></div>

                <p class="apq-cart-empty" data-apq-cart-empty>
                    <?php echo esc_html($labels->get('list_empty', __('Your list is empty. Start adding items!', 'appetitqr'))); ?>
                </p>
            </aside>
        </div>
        <?php
    }

    /**
     * Order mode: single cart plus the tel:/wa.me hand-off.
     */
    private static function renderOrder(array $location, LabelService $labels, PriceFormatter $prices): void {
        $allowPhone    = !empty($location['allowPhoneCallButton']) && !empty($location['orderPhoneNumber']);
        $allowWhatsApp = !empty($location['allowWhatsApp']) && !empty($location['orderWhatsAppNumber']);

        $shipping    = isset($location['shippingPrice']) && is_numeric($location['shippingPrice']) ? (int) $location['shippingPrice'] : 0;
        $minOrder    = !empty($location['minOrderValueEnabled']) && is_numeric($location['minOrderValue'] ?? null) ? (int) $location['minOrderValue'] : 0;
        $discountPct = !empty($location['totalDiscountEnabled']) && is_numeric($location['totalDiscountPercentage'] ?? null) ? (int) $location['totalDiscountPercentage'] : 0;

        $config = [
            'mode'          => 'order',
            'shipping'      => $shipping,
            'minOrder'      => $minOrder,
            'discountPct'   => $discountPct,
            'phone'         => $allowPhone ? Sanitizer::sanitizePhone($location['orderPhoneNumber']) : '',
            'whatsapp'      => $allowWhatsApp ? Sanitizer::sanitizePhone($location['orderWhatsAppNumber']) : '',
            'locationName'  => $location['name'] ?? '',
            'labels'        => [
                'quantity' => $labels->get('quantity_label', __('Qty:', 'appetitqr')),
                'subtotal' => $labels->get('subtotal', __('Subtotal', 'appetitqr')),
                'shipping' => $labels->get('shipping', __('Shipping', 'appetitqr')),
                'free'     => $labels->get('shipping_free', __('FREE', 'appetitqr')),
                'discount' => $labels->get('discount', __('Discount', 'appetitqr')),
                'total'    => $labels->get('total_estimated', __('Total (estimated)', 'appetitqr')),
                'empty'    => $labels->get('list_empty', __('Your list is empty. Start adding items!', 'appetitqr')),
            ],
        ];
        ?>
        <div class="apq-cart" data-apq-cart hidden data-apq-cart-config="<?php echo esc_attr(wp_json_encode($config)); ?>">
            <div class="apq-cart-backdrop" data-apq-cart-close></div>
            <aside class="apq-cart-panel" role="dialog" aria-modal="true" aria-labelledby="apq-cart-title">
                <header class="apq-cart-header">
                    <h3 class="apq-cart-title" id="apq-cart-title">
                        <?php echo esc_html($labels->get('cart', __('Cart', 'appetitqr'))); ?>
                    </h3>
                    <button
                        type="button"
                        class="apq-cart-close"
                        data-apq-cart-close
                        aria-label="<?php echo esc_attr($labels->get('close', __('Close', 'appetitqr'))); ?>"
                    >&times;</button>
                </header>

                <div class="apq-cart-items" data-apq-cart-items></div>

                <p class="apq-cart-empty" data-apq-cart-empty>
                    <?php echo esc_html($labels->get('list_empty', __('Your list is empty. Start adding items!', 'appetitqr'))); ?>
                </p>

                <div class="apq-cart-summary" data-apq-cart-summary hidden></div>

                <?php if (!empty($location['discountInfoMessage'])): ?>
                    <p class="apq-cart-note"><?php echo esc_html($location['discountInfoMessage']); ?></p>
                <?php endif; ?>

                <p class="apq-cart-note apq-cart-min-warning" data-apq-cart-min hidden></p>

                <footer class="apq-cart-actions">
                    <?php if ($allowPhone): ?>
                        <a class="apq-cart-action apq-cart-action-phone" data-apq-order-phone href="tel:<?php echo esc_attr(Sanitizer::sanitizePhone($location['orderPhoneNumber'])); ?>">
                            <?php echo esc_html($labels->get('phone_order', __('Phone order', 'appetitqr'))); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($allowWhatsApp): ?>
                        <a class="apq-cart-action apq-cart-action-whatsapp" data-apq-order-whatsapp href="#" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($labels->get('whatsapp_order', __('WhatsApp order', 'appetitqr'))); ?>
                        </a>
                    <?php endif; ?>

                    <?php if (!$allowPhone && !$allowWhatsApp): ?>
                        <p class="apq-cart-note">
                            <?php esc_html_e('Ordering is not enabled for this location. Your list is saved on this device only.', 'appetitqr'); ?>
                        </p>
                    <?php endif; ?>
                </footer>
            </aside>
        </div>
        <?php
    }
}
