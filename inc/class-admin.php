<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use WildWolf\Utils\Singleton;

final class Admin {
	use Singleton;

	public const OPTIONS_MENU_SLUG = 'ww-jwt-auth';

	private function __construct() {
		$this->init();
	}

	public function init(): void {
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	public function admin_init(): void {
		Admin_Settings::instance();

		$plugin = plugin_basename( dirname( __DIR__ ) . '/plugin.php' );
		add_filter( 'plugin_action_links_' . $plugin, [ $this, 'plugin_action_links' ] );
	}

	public function admin_menu(): void {
		add_options_page(
			__( 'JWT Authentication', 'ww-jwt-auth' ),
			__( 'JWT Auth', 'ww-jwt-auth' ),
			'manage_options',
			self::OPTIONS_MENU_SLUG,
			[ Admin_Settings::class, 'settings_page' ]
		);
	}

	public function plugin_action_links( array $links ): array {
		$url               = admin_url( sprintf( 'options-general.php?page=%s', self::OPTIONS_MENU_SLUG ) );
		$link              = '<a href="' . esc_url( $url ) . '">' . __( 'Settings', 'ww-jwt-auth' ) . '</a>';
		$links['settings'] = $link;
		return $links;
	}
}
