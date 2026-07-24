<?php
namespace AppetitQR\Hooks;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Config\Config;
use AppetitQR\Hooks\IHook;
use AppetitQR\Views\Frontend\MenuView;

class ActionShortcodes implements IHook {

    static function register() {
        add_shortcode(
            Config::getInstance()->getSetting('shortcode_tag', 'wp_appetitqr'),
            [MenuView::class, 'render']
        );
    }
}
