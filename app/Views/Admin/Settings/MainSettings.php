<?php
namespace AppetitQR\Views\Admin\Settings;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Utils\OptionUtils;

class MainSettings extends BaseView implements IView {

    public function render(): void {
        $options_group_slug = OptionUtils::OPTION_GROUP_SLUG;
        ?>
        <div class="wrap appetitqr-settings">
            <h1 class="apq-settings-title"><?php esc_html_e('AppetitQR', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h1>

            <div class="apq-panel">
                <form method="post" action="options.php">
                    <?php settings_fields($options_group_slug); ?>
                    <?php (new GeneralSettings())->render() ?>
                </form>
            </div>

            <div class="apq-panel">
                <?php (new Shortcodes())->render() ?>
            </div>

            <div class="apq-panel">
                <h2><?php esc_html_e('Documentation', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h2>
                <div class="apq-callout apq-callout-info">
                    <p><strong><?php esc_html_e('How to connect a location:', 'sakura-pixel-menu-embed-for-appetitqr'); ?></strong></p>
                    <ol class="apq-callout-list">
                        <li><?php esc_html_e('Open your AppetitQR dashboard and go to Locations → your location → Settings.', 'sakura-pixel-menu-embed-for-appetitqr'); ?></li>
                        <li><?php esc_html_e('In the Integrations card, click "Generate API Key".', 'sakura-pixel-menu-embed-for-appetitqr'); ?></li>
                        <li><?php esc_html_e('Copy the shortcode shown under the key.', 'sakura-pixel-menu-embed-for-appetitqr'); ?></li>
                        <li><?php esc_html_e('Paste it into any WordPress page or post and publish.', 'sakura-pixel-menu-embed-for-appetitqr'); ?></li>
                    </ol>
                </div>
                <div class="apq-callout apq-callout-muted">
                    <?php esc_html_e('Menu changes made in AppetitQR appear on your WordPress page once the cache expires. Use "Clear menu cache" above to see them immediately.', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
                </div>
            </div>
        </div>
        <?php
    }
}
