<?php
namespace AppetitQR\Helpers;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Language resolution for menu content.
 *
 * Mirrors getTranslatedName()/getTranslatedDescription() in the diner-facing templates:
 * the requested language wins, otherwise the first available translation is used rather
 * than showing an empty product.
 */
class TranslationHelper {

    static function pick(mixed $translations, string $lang, string $field): string {
        if (!is_array($translations) || empty($translations)) {
            return '';
        }

        foreach ($translations as $translation) {
            if (($translation['language'] ?? null) === $lang) {
                $value = $translation[$field] ?? null;
                if (is_string($value) && $value !== '') {
                    return $value;
                }
                break;
            }
        }

        $first = $translations[0][$field] ?? null;

        return is_string($first) ? $first : '';
    }

    /**
     * Variation names are stored as a parallel array on the translation row, indexed the
     * same way as the product's variations.
     */
    static function variationName(mixed $translations, string $lang, int $index, string $fallback): string {
        if (!is_array($translations)) {
            return $fallback;
        }

        $names = null;
        foreach ($translations as $translation) {
            if (($translation['language'] ?? null) === $lang && is_array($translation['variationNames'] ?? null)) {
                $names = $translation['variationNames'];
                break;
            }
        }

        if ($names === null && is_array($translations[0]['variationNames'] ?? null)) {
            $names = $translations[0]['variationNames'];
        }

        $name = $names[$index] ?? null;

        return is_string($name) && $name !== '' ? $name : $fallback;
    }

    /**
     * Resolves the language actually used for rendering: the shortcode's `lang` when the
     * account has it, otherwise the account's default, otherwise the first available.
     */
    static function resolveLang(array $languages, string $requested): string {
        $codes = [];
        foreach ($languages as $language) {
            if (isset($language['code']) && is_string($language['code'])) {
                $codes[] = $language['code'];
            }
        }

        if ($requested !== '' && in_array($requested, $codes, true)) {
            return $requested;
        }

        foreach ($languages as $language) {
            if (!empty($language['isDefault']) && isset($language['code'])) {
                return $language['code'];
            }
        }

        return $codes[0] ?? 'en';
    }

    static function isRtl(array $languages, string $lang): bool {
        foreach ($languages as $language) {
            if (($language['code'] ?? null) === $lang) {
                return !empty($language['isRtl']);
            }
        }

        return false;
    }
}
