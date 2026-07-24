<?php
namespace AppetitQR\Hooks;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Services\MenuCacheService;
use AppetitQR\Utils\OptionUtils;
use AppetitQR\Utils\Sanitizer;

class ActionSettings implements IHook {

    static function register() {
        add_action('admin_init', [self::class, 'registerSettingsGroups']);

        // Refresh OptionUtils cache when settings are updated
        add_action('update_option_' . OptionUtils::OPTION_GROUP_SLUG, [self::class, 'onSettingsUpdated'], 10, 3);
    }

    /**
     * Refresh the OptionUtils singleton and drop cached menus. Changing the cache TTL
     * invalidates everything already stored under the old settings.
     */
    static function onSettingsUpdated($old_value, $new_value, $option): void {
        OptionUtils::getInstance()->refresh();
        MenuCacheService::purgeAll();
    }

    static function registerSettingsGroups(): void {
        $options_group_slug = OptionUtils::OPTION_GROUP_SLUG;
        register_setting($options_group_slug, $options_group_slug, [
            'type'              => 'array',
            'sanitize_callback' => [self::class, 'sanitizeOptions']
        ]);
    }

    static function sanitizeOptions($options): array {

        if (!is_array($options)) {
            return [];
        }

        if (isset($options['cache_ttl'])) {
            $options['cache_ttl'] = absint($options['cache_ttl']);
        }

        return Sanitizer::sanitize_data($options);
    }
}
