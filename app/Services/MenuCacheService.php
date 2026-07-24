<?php
namespace AppetitQR\Services;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Config\Config;

/**
 * Transient cache in front of MenuApiService.
 *
 * Two entries are kept per API key: the fresh one that expires on the configured TTL,
 * and a long-lived "stale" copy. When the API is unreachable the stale copy is served
 * so a restaurant's page keeps showing its menu through an outage instead of an error.
 */
class MenuCacheService {

    const FRESH_PREFIX = 'appetitqr_menu_';
    const STALE_PREFIX = 'appetitqr_stale_';
    const INDEX_OPTION = 'appetitqr_cache_index';

    /** Stale copies outlive the fresh TTL by a wide margin — one week. */
    const STALE_TTL = WEEK_IN_SECONDS;

    /**
     * @return array{success: bool, data: ?array, error: ?string, stale: bool}
     */
    static function getMenu(string $apiKey, bool $forceRefresh = false): array {
        $hash        = self::hashKey($apiKey);
        $freshKey    = self::FRESH_PREFIX . $hash;
        $staleKey    = self::STALE_PREFIX . $hash;

        if (!$forceRefresh) {
            $cached = get_transient($freshKey);
            if (is_array($cached)) {
                return ['success' => true, 'data' => $cached, 'error' => null, 'stale' => false];
            }
        }

        $result = MenuApiService::fetchMenu($apiKey);

        if ($result['success']) {
            set_transient($freshKey, $result['data'], Config::getCacheTtl());
            set_transient($staleKey, $result['data'], self::STALE_TTL);
            self::rememberKey($hash);

            return ['success' => true, 'data' => $result['data'], 'error' => null, 'stale' => false];
        }

        // An invalid key is a configuration error, not an outage: serving a stale menu
        // for a revoked key would keep publishing data the owner just cut off.
        $isAuthError = in_array($result['error'], ['invalid_api_key', 'missing_api_key', 'location_unavailable'], true);

        if (!$isAuthError) {
            $stale = get_transient($staleKey);
            if (is_array($stale)) {
                return ['success' => true, 'data' => $stale, 'error' => $result['error'], 'stale' => true];
            }
        }

        return ['success' => false, 'data' => null, 'error' => $result['error'], 'stale' => false];
    }

    static function purge(string $apiKey): void {
        $hash = self::hashKey($apiKey);
        delete_transient(self::FRESH_PREFIX . $hash);
        delete_transient(self::STALE_PREFIX . $hash);
        self::forgetKey($hash);
    }

    /**
     * Drops every cached menu. Transients have no wildcard delete, so the hashes handed
     * out so far are tracked in an option and walked here.
     */
    static function purgeAll(): void {
        $index = get_option(self::INDEX_OPTION, []);
        if (is_array($index)) {
            foreach ($index as $hash) {
                delete_transient(self::FRESH_PREFIX . $hash);
                delete_transient(self::STALE_PREFIX . $hash);
            }
        }

        delete_option(self::INDEX_OPTION);
    }

    /**
     * The cache key is a hash so a raw API key is never written into option or transient
     * names, which are readable anywhere the options table is exposed.
     */
    private static function hashKey(string $apiKey): string {
        return md5(trim($apiKey));
    }

    private static function rememberKey(string $hash): void {
        $index = get_option(self::INDEX_OPTION, []);
        if (!is_array($index)) {
            $index = [];
        }

        if (!in_array($hash, $index, true)) {
            $index[] = $hash;
            update_option(self::INDEX_OPTION, $index, false);
        }
    }

    private static function forgetKey(string $hash): void {
        $index = get_option(self::INDEX_OPTION, []);
        if (!is_array($index)) {
            return;
        }

        $filtered = array_values(array_diff($index, [$hash]));
        update_option(self::INDEX_OPTION, $filtered, false);
    }
}
