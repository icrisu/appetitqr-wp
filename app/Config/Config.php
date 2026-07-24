<?php
namespace AppetitQR\Config;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Utils\OptionUtils;

class Config {

    private static $instance = null;
    private $config_data = [];

    const DEFAULT_API_BASE_URL = 'https://appetitqr.com';
    const MENU_ENDPOINT        = '/api/wp/menu';
    const DEFAULT_CACHE_TTL    = 900; // 15 minutes

    /** Payload version this plugin is written against — see app/api/wp/menu/route.ts. */
    const SUPPORTED_PAYLOAD_VERSION = 1;

    function __construct() {
        $this->config_data = $this->getData();
    }

    private function getData(): array {
        return [
            'main_settings_page_slug' => 'appetitqr-settings-page',
            'shortcode_tag'           => 'wp_appetitqr',
        ];
    }

    function getSetting(string $key, string $defaultValue = ''): mixed {
        return $this->config_data[$key] ?? $defaultValue;
    }

    /**
     * Base URL of the AppetitQR app — always production. It is deliberately not read from
     * any stored option: there is no admin field for it, so a value saved before that field
     * was removed could otherwise linger in wp_options and silently redirect every request
     * with no way to clear it from the UI.
     *
     * A staging or local site can still override it in code via the `appetitqr_api_base_url`
     * filter, which persists nothing:
     *
     *     add_filter('appetitqr_api_base_url', fn() => 'http://localhost:3000');
     */
    static function getApiBaseUrl(): string {
        $baseUrl = (string) apply_filters('appetitqr_api_base_url', self::DEFAULT_API_BASE_URL);
        if (trim($baseUrl) === '') {
            $baseUrl = self::DEFAULT_API_BASE_URL;
        }

        return untrailingslashit($baseUrl);
    }

    static function getMenuEndpoint(): string {
        return self::getApiBaseUrl() . self::MENU_ENDPOINT;
    }

    /**
     * How long a fetched menu stays fresh. Clamped to a sane floor so a mistyped 0 in
     * the settings cannot turn every page view into an outbound HTTP request.
     */
    static function getCacheTtl(): int {
        $ttl = (int) OptionUtils::getInstance()->getOption('cache_ttl', self::DEFAULT_CACHE_TTL);
        if ($ttl < 60) {
            $ttl = self::DEFAULT_CACHE_TTL;
        }

        return $ttl;
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Config();
        }
        return self::$instance;
    }
}
