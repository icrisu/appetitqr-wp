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
        </div>
        <?php
    }
}
