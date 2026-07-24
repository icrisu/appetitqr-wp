<?php
namespace AppetitQR\Hooks;
if ( ! defined( 'ABSPATH' ) ) exit;

interface IHook {
    static function register();
}
