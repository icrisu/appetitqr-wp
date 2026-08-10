<?php
namespace AppetitQR\Views\Admin\Settings;
if ( ! defined( 'ABSPATH' ) ) exit;

class Shortcodes extends BaseView implements IView {

    public function render(): void {
        $attributes = [
            ['api_key', 'apq_…', esc_html__('Required. The key generated for this location in the AppetitQR dashboard.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['lang', 'en', esc_html__('Menu language. Defaults to the account\'s default language. Ignored when the account has no such language.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['show_search', '1', esc_html__('Set to 0 to hide the search field.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['show_info', '1', esc_html__('Set to 0 to hide the restaurant info block (about, address, phone, hours).', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['show_cart', '1', esc_html__('Set to 0 to hide the cart entirely. Also depends on the location\'s ordering settings in AppetitQR.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['show_images', '1', esc_html__('Set to 0 to render a compact, text-only menu without product images.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['columns', '3', esc_html__('Maximum number of product columns on wide screens (1–4).', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['dinein', 'false', esc_html__('Set to true for a dine-in (table QR) page: guests build a wishlist to show their server and all ordering is disabled.', 'sakura-pixel-menu-embed-for-appetitqr')],
        ];
        ?>
        <h2><?php esc_html_e('Embedding a menu', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h2>
        <p><?php esc_html_e('Paste this shortcode into any page or post. Replace the key with the one from your AppetitQR dashboard:', 'sakura-pixel-menu-embed-for-appetitqr'); ?></p>
        <pre class="apq-code"><code>[wp_appetitqr api_key="apq_your_key_here"]</code></pre>

        <h3><?php esc_html_e('All attributes', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h3>
        <table class="widefat striped apq-attrs">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Attribute', 'sakura-pixel-menu-embed-for-appetitqr'); ?></th>
                    <th scope="col"><?php esc_html_e('Default', 'sakura-pixel-menu-embed-for-appetitqr'); ?></th>
                    <th scope="col"><?php esc_html_e('Description', 'sakura-pixel-menu-embed-for-appetitqr'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attributes as $attribute): ?>
                    <tr>
                        <td><code><?php echo esc_html($attribute[0]); ?></code></td>
                        <td><code><?php echo esc_html($attribute[1]); ?></code></td>
                        <td><?php echo esc_html($attribute[2]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3><?php esc_html_e('Examples', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h3>
        <pre class="apq-code"><code>[wp_appetitqr api_key="apq_your_key_here" lang="ro"]

[wp_appetitqr api_key="apq_your_key_here" show_cart="0" show_info="0"]

[wp_appetitqr api_key="apq_your_key_here" columns="2" show_images="0"]

[wp_appetitqr api_key="apq_your_key_here" dinein="true"]</code></pre>

        <h3><?php esc_html_e('Dine-in mode', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h3>
        <p><?php esc_html_e('With dinein="true" the menu behaves like a table QR code: guests save items to a wishlist they show their server, and every ordering feature is switched off — no phone or WhatsApp ordering, no delivery cost, no discounts and no minimum order. Put it on a separate page from your ordering menu and point your table QR codes at it.', 'sakura-pixel-menu-embed-for-appetitqr'); ?></p>
        <p><?php esc_html_e('The list is still controlled by the location: dine-in must have "allow temporary cart" enabled in your AppetitQR dashboard, otherwise no list appears.', 'sakura-pixel-menu-embed-for-appetitqr'); ?></p>

        <div class="apq-callout apq-callout-muted">
            <?php esc_html_e('The menu is fetched on the server and cached, so it appears in the page source and is indexable. Ordering, search and the product popup are added on top with JavaScript.', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
        </div>
        <?php
    }
}
