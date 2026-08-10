<?php
namespace AppetitQR\Views\Frontend\Partials;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Horizontal category strip. Anchors are real links to the section ids so the nav works
 * without JavaScript; the bundled script upgrades them to smooth scroll + scroll-spy.
 */
class CategoryNav {

    static function render(array $groups, string $instanceId): void {
        if (count($groups) < 2) {
            return;
        }
        ?>
        <nav class="apq-category-nav" data-apq-category-nav aria-label="<?php esc_attr_e('Menu categories', 'sakura-pixel-menu-embed-for-appetitqr'); ?>">
            <ul class="apq-category-nav-list">
                <?php foreach ($groups as $index => $group): ?>
                    <li>
                        <a
                            class="apq-category-link<?php echo $index === 0 ? ' is-active' : ''; ?>"
                            href="#<?php echo esc_attr($instanceId . '-cat-' . $group['id']); ?>"
                            data-apq-category-link="<?php echo esc_attr($group['id']); ?>"
                        >
                            <?php echo esc_html($group['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
    }
}
