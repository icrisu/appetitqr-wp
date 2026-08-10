<?php
namespace AppetitQR\Services;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Config\Config;

/**
 * Thin client over the AppetitQR public menu endpoint.
 *
 * Every method returns a structured array rather than throwing, so the shortcode can
 * degrade to a cached copy (or a quiet notice) instead of taking the page down.
 */
class MenuApiService {

    const TIMEOUT = 15;

    /**
     * @return array{success: bool, data: ?array, error: ?string, status: int}
     */
    static function fetchMenu(string $apiKey): array {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            return self::failure('missing_api_key', 0);
        }

        $response = wp_remote_get(Config::getMenuEndpoint(), [
            'timeout' => self::TIMEOUT,
            'headers' => [
                'X-Appetit-Api-Key' => $apiKey,
                'Accept'            => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return self::failure('request_failed', 0);
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);

        if ($status !== 200) {
            // The endpoint distinguishes an unusable key from a location that is merely
            // switched off or past due, so the admin sees an actionable message.
            $reason = match ($status) {
                401     => 'invalid_api_key',
                402     => 'subscription_required',
                404     => 'not_found',
                410     => 'location_unavailable',
                default => 'http_error',
            };

            return self::failure($reason, $status);
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return self::failure('invalid_json', $status);
        }

        if (!isset($decoded['location']) || !is_array($decoded['location'])) {
            return self::failure('invalid_payload', $status);
        }

        // A newer app can add fields safely, but a bumped major version means the shape
        // this plugin renders is no longer guaranteed.
        $version = isset($decoded['version']) ? (int) $decoded['version'] : 0;
        if ($version !== Config::SUPPORTED_PAYLOAD_VERSION) {
            return self::failure('unsupported_version', $status);
        }

        return ['success' => true, 'data' => $decoded, 'error' => null, 'status' => $status];
    }

    private static function failure(string $error, int $status): array {
        return ['success' => false, 'data' => null, 'error' => $error, 'status' => $status];
    }

    /**
     * Human-readable reason, shown to editors/admins only.
     */
    static function describeError(?string $error): string {
        return match ($error) {
            'missing_api_key'       => esc_html__('No API key was provided in the shortcode.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'invalid_api_key'       => esc_html__('This API key was rejected. Generate a new one in your AppetitQR dashboard and update the shortcode.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'subscription_required' => esc_html__('The AppetitQR subscription for this location is not active.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'not_found'             => esc_html__('This location no longer exists in AppetitQR.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'location_unavailable'  => esc_html__('This location is currently deactivated in AppetitQR.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'unsupported_version'   => esc_html__('The AppetitQR API returned a format this plugin version does not support. Please update the plugin.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'invalid_json',
            'invalid_payload'       => esc_html__('The AppetitQR API returned an unexpected response.', 'sakura-pixel-menu-embed-for-appetitqr'),
            'request_failed'        => esc_html__('Could not reach the AppetitQR API. Check the API URL in the plugin settings.', 'sakura-pixel-menu-embed-for-appetitqr'),
            default                 => esc_html__('The menu could not be loaded.', 'sakura-pixel-menu-embed-for-appetitqr'),
        };
    }
}
