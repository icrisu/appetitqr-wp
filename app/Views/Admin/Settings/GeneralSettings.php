<?php

namespace AppetitQR\Views\Admin\Settings;

if (! defined('ABSPATH')) exit;

use AppetitQR\Config\Config;
use AppetitQR\Utils\OptionUtils;

class GeneralSettings extends BaseView implements IView
{

    public function render(): void
    {
        $options    = OptionUtils::getInstance();
        $groupSlug  = OptionUtils::OPTION_GROUP_SLUG;
        $cacheTtl   = (int) $options->getOption('cache_ttl', Config::DEFAULT_CACHE_TTL);
?>
        <nav class="nav-tab-wrapper apq-tabs" role="tablist">
            <a href="#appetit-features" class="nav-tab nav-tab-active" role="tab" aria-selected="true" aria-controls="appetit-features" data-apq-tab="appetit-features">
                <?php esc_html_e('Features', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
            </a>
            <a href="#appetit-settings" class="nav-tab" role="tab" aria-selected="false" aria-controls="appetit-settings" data-apq-tab="appetit-settings">
                <?php esc_html_e('Settings', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
            </a>
        </nav>

        <div id="appetit-features" class="apq-tab-panel" role="tabpanel">
            <div class="appetit-features" style="margin-bottom: 30px;border-style: solid; border-width: 1px; border-color: #CCC;">
                <img style="width: 100%;" src="<?php echo esc_attr(APPETITQR_APP_PUBLIC_URL . '/assets/img/splashscreen.webp') ?>" alt="">
                <div style="margin: 20px 0px; display: flex; justify-content: center">
                    <a href="https://appetitqr.com" target="_blank" class="button button-primary" id="appetitqr-home">
                        <?php esc_html_e('Appetit Home', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
                    </a>
                </div>
            </div>
        </div>

        <div id="appetit-settings" class="apq-tab-panel" role="tabpanel" hidden>
            <div class="apq-callout apq-callout-info">
                <?php esc_html_e('Generate an API key in your AppetitQR dashboard under Locations → your location → Settings → Integrations, then paste it into the shortcode on any page.', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
            </div>

            <table class="form-table appetit-settings" role="presentation">
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
                            value="<?php echo esc_attr($cacheTtl); ?>" />
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

            <hr class="apq-divider" />

            <?php (new Shortcodes())->render() ?>

            <hr class="apq-divider" />

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
<?php
    }
}
