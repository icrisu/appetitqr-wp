<?php

namespace AppetitQR\Utils;
if (!defined('ABSPATH')) exit;


class OptionUtils {

    const OPTION_GROUP_SLUG = 'appetitqr_settings';

    private static $instance = null;
    private $options;

    private function __construct() {
        $this->options = get_option(self::OPTION_GROUP_SLUG, []);
    }

    public function getOption(?string $optsKey, $default = '') {
        return (isset($this->options[$optsKey])) ? $this->options[$optsKey] : $default;
    }

    public function setOption(?string $optsKey, $val) {
        $updated = array_merge($this->options, [$optsKey => $val]);
        $result  = update_option(self::OPTION_GROUP_SLUG, $updated);

        // Refresh cached options after update
        if ($result) {
            $this->refresh();
        }

        return $result;
    }

    /**
     * Refresh cached options from database
     */
    public function refresh() {
        $this->options = get_option(self::OPTION_GROUP_SLUG, []);
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new OptionUtils();
        }
        return self::$instance;
    }
}
