<?php
namespace AppetitQR\Views\Admin\Settings;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Config\Config;
use AppetitQR\Utils\OptionUtils;

class GeneralSettings extends BaseView implements IView {

    public function render(): void {
        $options    = OptionUtils::getInstance();
        $groupSlug  = OptionUtils::OPTION_GROUP_SLUG;
        $cacheTtl   = (int) $options->getOption('cache_ttl', Config::DEFAULT_CACHE_TTL);
        ?>
        <div class="apq-callout apq-callout-info">
            <?php esc_html_e('Generate an API key in your AppetitQR dashboard under Locations → your location → Settings → Integrations, then paste it into the shortcode on any page.', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
        </div>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="appetitqr-cache-ttl"><?php esc_html_e('Menu cache lifetime (seconds)', 'sakura-pixel-menu-embed-for-appetitqr'); ?></label>
                </th>
                <td>
                    <input
                        type="number"
                        min="60"
                        step="60"
                        class="small-text"
                        id="appetitqr-cache-ttl"
                        name="<?php echo esc_attr($groupSlug); ?>[cache_ttl]"
                        value="<?php echo esc_attr($cacheTtl); ?>"
                    />
                    <p class="description">
                        <?php esc_html_e('How long a fetched menu is reused before the plugin calls the API again. Minimum 60 seconds; defaults to 900 (15 minutes).', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php $this->renderSettingsSaveBtn(); ?>

        <hr class="apq-divider" />

        <h2><?php esc_html_e('Test a connection', 'sakura-pixel-menu-embed-for-appetitqr'); ?></h2>
        <p class="description"><?php esc_html_e('Check an API key against the AppetitQR API before you publish the shortcode. Nothing is saved.', 'sakura-pixel-menu-embed-for-appetitqr'); ?></p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="appetitqr-test-key"><?php esc_html_e('API key', 'sakura-pixel-menu-embed-for-appetitqr'); ?></label>
                </th>
                <td>
                    <input type="text" class="regular-text" id="appetitqr-test-key" placeholder="apq_…" autocomplete="off" />
                </td>
            </tr>
        </table>

        <p class="apq-actions">
            <button type="button" class="button button-primary" id="appetitqr-test-connection">
                <?php esc_html_e('Test connection', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
            </button>
            <button type="button" class="button button-secondary" id="appetitqr-clear-cache">
                <?php esc_html_e('Clear menu cache', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
            </button>
        </p>

        <div id="appetitqr-test-result" class="apq-result" hidden></div>
        <?php
    }
}
