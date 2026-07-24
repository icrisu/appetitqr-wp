<?php
namespace AppetitQR\Views\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) exit;

interface IView {
    public function render(): void;
}
