<?php
namespace AppetitQR\Views\Frontend\Partials;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Services\LabelService;

class SearchBar {

    static function render(LabelService $labels): void {
        $placeholder = $labels->get('search_placeholder', __('Search…', 'sakura-pixel-menu-embed-for-appetitqr'));
        ?>
        <div class="apq-search">
            <label class="screen-reader-text" for="apq-search-input">
                <?php echo esc_html($placeholder); ?>
            </label>
            <input
                type="search"
                class="apq-search-input"
                id="apq-search-input"
                data-apq-search
                placeholder="<?php echo esc_attr($placeholder); ?>"
                autocomplete="off"
            />
            <button type="button" class="apq-search-clear" data-apq-search-clear hidden aria-label="<?php esc_attr_e('Clear search', 'sakura-pixel-menu-embed-for-appetitqr'); ?>">
                &times;
            </button>
        </div>
        <?php
    }
}
