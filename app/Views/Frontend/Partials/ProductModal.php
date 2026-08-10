<?php
namespace AppetitQR\Views\Frontend\Partials;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Services\LabelService;

/**
 * Empty dialog shell, filled in by the script from the card's embedded JSON.
 *
 * On the diner-facing menu a product is its own route; inside a WordPress page that
 * would hijack navigation, so the detail view is an in-page dialog instead.
 */
class ProductModal {

    static function renderShell(LabelService $labels, bool $isDineIn = false): void {
        ?>
        <div class="apq-modal" data-apq-modal hidden>
            <div class="apq-modal-backdrop" data-apq-modal-close></div>
            <div class="apq-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="apq-modal-title" tabindex="-1">
                <button
                    type="button"
                    class="apq-modal-close"
                    data-apq-modal-close
                    aria-label="<?php echo esc_attr($labels->get('close', __('Close', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>"
                >&times;</button>

                <div class="apq-modal-media" data-apq-modal-media></div>

                <div class="apq-modal-body">
                    <h3 class="apq-modal-title" id="apq-modal-title" data-apq-modal-title></h3>
                    <p class="apq-modal-description" data-apq-modal-description></p>

                    <div class="apq-modal-section" data-apq-modal-variations-wrap hidden>
                        <h4 class="apq-modal-section-title">
                            <?php echo esc_html($labels->get('select_variant', __('Select variant', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </h4>
                        <div class="apq-modal-variations" data-apq-modal-variations></div>
                    </div>

                    <div class="apq-modal-section" data-apq-modal-allergens-wrap hidden>
                        <h4 class="apq-modal-section-title">
                            <?php echo esc_html($labels->get('allergens', __('Allergens', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </h4>
                        <ul class="apq-chips" data-apq-modal-allergens></ul>
                    </div>

                    <div class="apq-modal-section" data-apq-modal-dietary-wrap hidden>
                        <h4 class="apq-modal-section-title">
                            <?php echo esc_html($labels->get('dietary', __('Dietary', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </h4>
                        <ul class="apq-chips" data-apq-modal-dietary></ul>
                    </div>

                    <div class="apq-modal-section" data-apq-modal-nutrition-wrap hidden>
                        <h4 class="apq-modal-section-title">
                            <?php echo esc_html($labels->get('nutritional_values', __('Nutritional Values', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </h4>
                        <ul class="apq-nutrition" data-apq-modal-nutrition></ul>
                    </div>

                    <div class="apq-modal-section" data-apq-modal-additional-wrap hidden>
                        <h4 class="apq-modal-section-title">
                            <?php echo esc_html($labels->get('additional_info', __('Additional info', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </h4>
                        <p data-apq-modal-additional></p>
                    </div>

                    <div class="apq-modal-footer">
                        <span class="apq-modal-price" data-apq-modal-price></span>
                        <button type="button" class="apq-add-to-cart" data-apq-modal-add hidden>
                            <?php echo esc_html($isDineIn
                                ? $labels->get('save_to_list', __('Save to list', 'sakura-pixel-menu-embed-for-appetitqr'))
                                : $labels->get('add_to_cart', __('Add to cart', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
