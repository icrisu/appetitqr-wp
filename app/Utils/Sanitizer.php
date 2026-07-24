<?php

namespace AppetitQR\Utils;
if (!defined('ABSPATH')) exit;


class Sanitizer {

    public static function sanitize_data(mixed $data) {
        if (is_array($data)) {
            // Recursively sanitize each element in the array.
            return array_map([__CLASS__, 'sanitize_data'], $data);
        }

        // Sanitize single value.
        return sanitize_text_field(wp_unslash($data));
    }

    /**
     * Hex color coming back from the API. Anything that is not a plain #rgb/#rrggbb is
     * dropped rather than echoed, since these values are interpolated into a <style>
     * block where a crafted value could otherwise break out of the declaration.
     */
    public static function sanitizeHexColor(mixed $color, string $fallback = ''): string {
        if (!is_string($color)) {
            return $fallback;
        }

        $color = trim($color);
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
            return $color;
        }

        return $fallback;
    }

    /**
     * Reduces a phone number to the digits tel:/wa.me accept, keeping a leading plus.
     */
    public static function sanitizePhone(mixed $phone): string {
        if (!is_string($phone)) {
            return '';
        }

        $trimmed = trim($phone);
        $plus    = str_starts_with($trimmed, '+') ? '+' : '';

        return $plus . preg_replace('/[^0-9]/', '', $trimmed);
    }
}
