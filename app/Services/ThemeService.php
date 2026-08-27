<?php
namespace AppetitQR\Services;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Hooks\ActionEnqueueScripts;
use AppetitQR\Utils\Sanitizer;

/**
 * Translates the account's resolved palette into CSS custom properties scoped to a
 * single shortcode instance, so two menus on one page cannot overwrite each other and
 * nothing leaks into the host theme.
 */
class ThemeService {

    /** Mirrors the four template ids in app/m/[locationSlug]/_templates/registry.ts. */
    const KNOWN_TEMPLATES = ['default', 'modern', 'bistro', 'nocturnal'];

    const FALLBACK_PRIMARY   = '#cc1918';
    const FALLBACK_SECONDARY = '#F1F5F9';

    static function getTemplateId(array $theme): string {
        $webApp = isset($theme['webApp']) && is_string($theme['webApp']) ? $theme['webApp'] : 'default';

        return in_array($webApp, self::KNOWN_TEMPLATES, true) ? $webApp : 'default';
    }

    /**
     * The API resolves webAppColors (or the template defaults) for us; this only guards
     * against malformed values before they reach a CSS declaration.
     */
    static function getColors(array $theme): array {
        $colors = isset($theme['colors']) && is_array($theme['colors']) ? $theme['colors'] : [];

        $primary   = Sanitizer::sanitizeHexColor($colors['primaryColor'] ?? '', self::FALLBACK_PRIMARY);
        $secondary = Sanitizer::sanitizeHexColor($colors['secondaryColor'] ?? '', self::FALLBACK_SECONDARY);

        return ['primary' => $primary, 'secondary' => $secondary];
    }

    /**
     * Hands WordPress the palette for one instance as inline CSS, scoped to that
     * instance's wrapper id so two menus on one page keep their own colors.
     *
     * Every interpolated value is a literal the plugin controls: the id comes from an
     * internal counter (narrowed again by sanitize_html_class), and the colors have
     * already been through Sanitizer::sanitizeHexColor, which is what keeps an API
     * payload from breaking out of the declaration.
     */
    static function enqueueScopedStyles(string $instanceId, array $theme): void {
        $colors = self::getColors($theme);

        $css = sprintf(
            '#%1$s{--apq-primary:%2$s;--apq-secondary:%3$s;--apq-on-primary:%4$s;--apq-on-secondary:%5$s;}',
            sanitize_html_class($instanceId),
            $colors['primary'],
            $colors['secondary'],
            self::readableTextColor($colors['primary']),
            self::readableTextColor($colors['secondary'])
        );

        wp_enqueue_style(ActionEnqueueScripts::INLINE_STYLE_HANDLE);
        wp_add_inline_style(ActionEnqueueScripts::INLINE_STYLE_HANDLE, $css);
    }

    /**
     * Whether this palette's secondary colour is a dark surface.
     *
     * secondaryColor is not a pale accent in every template: Modern Noir (#0B1C30) and
     * Nocturnal (#131315) use it as the menu's page background, while Classic (#F1F5F9)
     * and Bistro (#EDE6D8) use it as a light tint. Treating it as light either way is
     * what makes chips render as unreadable dark-on-dark blocks, so the widget picks a
     * light or dark scheme from the colour itself rather than hard-coding template ids —
     * an account that customises its palette gets the right treatment too.
     */
    static function isDarkScheme(array $theme): bool {
        return self::relativeLuminance(self::getColors($theme)['secondary']) <= 0.45;
    }

    /**
     * Picks black or white for text sitting on the given color, using the WCAG
     * relative-luminance threshold, so a light brand color never yields white-on-white.
     */
    static function readableTextColor(string $hex): string {
        return self::relativeLuminance($hex) > 0.45 ? '#111111' : '#FFFFFF';
    }

    /** WCAG relative luminance, 0 (black) to 1 (white). Unparseable colors read as dark. */
    private static function relativeLuminance(string $hex): float {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return 0.0;
        }

        $channels = [];
        foreach ([0, 2, 4] as $offset) {
            $value      = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $value <= 0.03928 ? $value / 12.92 : pow(($value + 0.055) / 1.055, 2.4);
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
