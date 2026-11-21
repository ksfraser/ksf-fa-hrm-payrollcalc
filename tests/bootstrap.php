<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Unit/DbStub.php';

if (!defined('TB_PREF')) {
    define('TB_PREF', '0_');
}

if (!class_exists('hooks', false)) {
    class hooks
    {
        public $module_name = '';
        public $version = '';
        public function install_tabs($app) {}
        public function install_options($app) {}
        public function install_access() { return array(array(), array()); }
        public function activate_extension($company, $check_only = true) { return true; }
    }
}
