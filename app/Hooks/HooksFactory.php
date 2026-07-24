<?php
namespace AppetitQR\Hooks;
if ( ! defined( 'ABSPATH' ) ) exit;


class HooksFactory {

    private const HOOKS_CLASSES = ['ActionEnqueueScripts', 'ActionAdminMenu', 'ActionAjax',
    'ActionShortcodes', 'ActionSettings'];

    static function registerHooks(): void {

        foreach (self::HOOKS_CLASSES as $className) {
            $classFull = 'AppetitQR\Hooks\\' . $className;
            if (class_exists($classFull) && method_exists($classFull, 'register')) {
                $classFull::register();
            }
        }
    }
}
