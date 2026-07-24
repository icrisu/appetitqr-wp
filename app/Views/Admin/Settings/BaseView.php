<?php
namespace AppetitQR\Views\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) exit;

class BaseView {
    function __construct(protected array $viewData = []) {}

    protected function renderSettingsSaveBtn(): void {
        submit_button(__('Save', 'appetitqr'));
    }
}
