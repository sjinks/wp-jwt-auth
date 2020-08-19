<?php
/*
    Plugin Name: WW JWT Auth
    Description: WordPress plugin for JWT authentication for the REST API
    Author: Volodymyr Kolesnykov
    Version: 1.0
    Author URI: https://wildwolf.name/
*/

defined('ABSPATH') || die();

if (defined('VENDOR_PATH')) {
    require constant('VENDOR_PATH') . '/vendor/autoload.php';
}
elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}
elseif (file_exists(ABSPATH . 'vendor/autoload.php')) {
    require ABSPATH . 'vendor/autoload.php';
}

WildWolf\JwtAuth\Plugin::instance();
