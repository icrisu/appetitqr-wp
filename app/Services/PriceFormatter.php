<?php
namespace AppetitQR\Services;
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Mirrors formatPrice() in the diner-facing templates
 * (app/m/[locationSlug]/_templates/default/MenuClient.tsx) so a price rendered in
 * WordPress reads identically to the same price on /m/<slug>.
 *
 * Prices arrive as integers in the currency's smallest unit, scaled by the account's
 * priceDecimalPlaces.
 */
class PriceFormatter {

    private string $symbol;
    private string $position;
    private int $decimals;

    function __construct(array $currency) {
        $this->symbol   = isset($currency['symbol']) && is_string($currency['symbol']) ? $currency['symbol'] : '';
        $this->position = ($currency['position'] ?? 'prefix') === 'suffix' ? 'suffix' : 'prefix';
        $decimals       = isset($currency['decimals']) ? (int) $currency['decimals'] : 2;
        $this->decimals = max(0, min(4, $decimals));
    }

    function format(mixed $amount): string {
        $cents  = is_numeric($amount) ? (int) $amount : 0;
        $value  = number_format($cents / pow(10, $this->decimals), $this->decimals, '.', '');

        return $this->position === 'suffix'
            ? $value . ' ' . $this->symbol
            : $this->symbol . ' ' . $value;
    }

    /**
     * Lowest active price across a product's variations — what the card shows when a
     * product has several sizes. Sale price wins when present, matching the templates.
     */
    function formatFrom(array $variations): string {
        $prices = [];
        foreach ($variations as $variation) {
            $price = isset($variation['salePrice']) && is_numeric($variation['salePrice'])
                ? $variation['salePrice']
                : ($variation['price'] ?? null);

            if (is_numeric($price)) {
                $prices[] = (int) $price;
            }
        }

        if (empty($prices)) {
            return '';
        }

        return $this->format(min($prices));
    }

    function getDecimals(): int {
        return $this->decimals;
    }

    function getSymbol(): string {
        return $this->symbol;
    }

    function getPosition(): string {
        return $this->position;
    }
}
