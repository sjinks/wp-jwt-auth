<?php
/*
	Plugin Name: WW JWT Auth
	Description: WordPress plugin for JWT authentication for the REST API
	Author: Volodymyr Kolesnykov
	Version: 2.0
	Author URI: https://wildwolf.name/
*/

if ( defined( 'ABSPATH' ) ) {
	if ( defined( 'VENDOR_PATH' ) ) {
		/** @psalm-suppress UnresolvableInclude, MixedOperand */
		require constant( 'VENDOR_PATH' ) . '/vendor/autoload.php';
	} elseif ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require __DIR__ . '/vendor/autoload.php';
	} elseif ( file_exists( ABSPATH . 'vendor/autoload.php' ) ) {
		/** @psalm-suppress UnresolvableInclude */
		require ABSPATH . 'vendor/autoload.php';
	}

	WildWolf\WordPress\JwtAuth\Plugin::instance();
}
