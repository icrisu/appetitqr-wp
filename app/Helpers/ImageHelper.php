<?php
namespace AppetitQR\Helpers;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mirrors buildSrcSet()/getImageVariantKey() in lib/image-utils.ts so the plugin
 * requests the same pre-generated width variants the diner-facing menu does, instead
 * of shipping the full-size original to every phone.
 */
class ImageHelper {

    static function variantUrl(string $url, int $width): string {
        $lastDot = strrpos($url, '.');
        if ($lastDot === false) {
            return $url . '_w' . $width;
        }

        return substr($url, 0, $lastDot) . '_w' . $width . substr($url, $lastDot);
    }

    static function buildSrcSet(?string $url, array $widths): string {
        if (!is_string($url) || $url === '' || empty($widths)) {
            return '';
        }

        $parts = [];
        foreach ($widths as $width) {
            $width = (int) $width;
            if ($width > 0) {
                $parts[] = esc_url(self::variantUrl($url, $width)) . ' ' . $width . 'w';
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Prints the src/srcset/sizes attributes for a menu image. Kept as one helper so
     * every image in the plugin gets identical lazy-loading and sizing behaviour.
     */
    static function printImageAttrs(?string $url, array $widths, string $sizes): void {
        if (!is_string($url) || $url === '') {
            return;
        }

        echo ' src="' . esc_url($url) . '"';

        $srcset = self::buildSrcSet($url, $widths);
        if ($srcset !== '') {
            echo ' srcset="' . esc_attr($srcset) . '" sizes="' . esc_attr($sizes) . '"';
        }

        echo ' loading="lazy" decoding="async"';
    }
}
