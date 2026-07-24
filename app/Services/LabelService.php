<?php
namespace AppetitQR\Services;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Reads the account's custom frontend labels for the active language.
 *
 * The API already layered custom label → shipped translation → English default, so a
 * lookup here either hits or falls back to the plugin's own translated string.
 */
class LabelService {

    private array $labels;
    private string $lang;

    function __construct(array $labels, string $lang) {
        $this->labels = $labels;
        $this->lang   = $lang;
    }

    function get(string $formKey, string $fallback = ''): string {
        $value = $this->labels[$this->lang][$formKey] ?? null;

        if (is_string($value) && trim($value) !== '') {
            return $value;
        }

        return $fallback;
    }

    function getLang(): string {
        return $this->lang;
    }
}
