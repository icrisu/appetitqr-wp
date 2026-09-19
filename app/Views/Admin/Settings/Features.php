<?php
namespace AppetitQR\Views\Admin\Settings;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * "The toolkit" feature grid, mirroring the AppetitQR homepage section
 * (FeatureGrid.tsx / FEATURE_META_HOMEPAGE in the web app).
 */
class Features extends BaseView implements IView {

    /** Inner SVG markup of the Lucide icons the homepage uses (ISC licence). Static, trusted markup. */
    private const ICONS = [
        'digital-menus'   => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'qr-codes'        => '<rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3"/><path d="M21 21v.01"/><path d="M12 7v3a2 2 0 0 1-2 2H7"/><path d="M3 12h.01"/><path d="M12 3h.01"/><path d="M12 16v.01"/><path d="M16 12h1"/><path d="M21 12v.01"/><path d="M12 21v-1"/>',
        'ordering'        => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
        'push-promotions' => '<path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/>',
        'pwa'             => '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
        'fast-app-like'   => '<path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/>',
        'ai-import'       => '<path d="M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z"/><path d="M20 2v4"/><path d="M22 4h-4"/><circle cx="4" cy="20" r="2"/>',
        'ai-translation'  => '<path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/>',
        'multi-location'  => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    ];

    public function render(): void {
        $items = [
            ['digital-menus', esc_html__('Digital menus', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Digital menus', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Categories, products, image galleries, drag-and-drop ordering, featured badges, availability toggles. Always up to date - never a reprint.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['qr-codes', esc_html__('Dine-in', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('One scan to the table', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('A branded dine-in QR code per location - guests scan at the table and your menu opens instantly. Custom colors, embedded logo.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['ordering', esc_html__('Ordering', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Take orders without the middleman', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('A dedicated ordering QR opens your ordering app - guests build their order and send it via WhatsApp or phone. Zero commission.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['push-promotions', esc_html__('Push promotions', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Bring guests back', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Guests opt in from the storefront, owners broadcast promos via web push.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['pwa', esc_html__('Installable menu (PWA)', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('A menu that installs like an app', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Each location\'s menu can installs to the home screen with its own icon and theme colors.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['fast-app-like', esc_html__('Fast & app-like', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Feels like a native app', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('A mobile-first storefront that loads instantly and stays smooth as guests browse - the responsiveness of a native app, straight from the web.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['ai-import', esc_html__('AI menu import', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('From PDF to live menu in minutes', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Upload an existing PDF or Word menu. AI extracts every category, products, price. Review, then publish.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['ai-translation', esc_html__('AI translation', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Serve every guest in their language', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Add languages and translate the whole menu: dishes, descriptions, variations, storefront labels - with one click.', 'sakura-pixel-menu-embed-for-appetitqr')],
            ['multi-location', esc_html__('Multi-location', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Every location, one account', 'sakura-pixel-menu-embed-for-appetitqr'), esc_html__('Run every location from one account - each with its own menu, branding, contact, and opening hours.', 'sakura-pixel-menu-embed-for-appetitqr')],
        ];
        $count = count($items);
        ?>
        <div class="apq-toolkit">
            <p class="apq-toolkit-eyebrow"><?php esc_html_e('The toolkit', 'sakura-pixel-menu-embed-for-appetitqr'); ?></p>
            <h2 class="apq-toolkit-title"><?php esc_html_e('Everything you need to serve.', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h2>

            <div class="apq-toolkit-grid">
                <?php foreach ($items as $i => [$key, $title, $headline, $description]) :
                    // Same grid-line logic as the web app: drop the right border on the last
                    // column and the bottom border on the last row, at 1, 2 and 3 columns.
                    $classes = ['apq-toolkit-item'];
                    if ($i % 2 === 1) $classes[] = 'apq-nbr-2';
                    if ($i % 3 === 2) $classes[] = 'apq-nbr-3';
                    if ($i === $count - 1) $classes[] = 'apq-nbb-1';
                    if ($i >= $count - ($count % 2 ?: 2)) $classes[] = 'apq-nbb-2';
                    if ($i >= $count - ($count % 3 ?: 3)) $classes[] = 'apq-nbb-3';
                    ?>
                    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
                        <div class="apq-toolkit-head">
                            <span class="apq-toolkit-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?php echo self::ICONS[$key]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG constant ?></svg>
                            </span>
                            <span class="apq-toolkit-label"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></span>
                        </div>
                        <h3><?php echo $headline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></h3>
                        <p><?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
