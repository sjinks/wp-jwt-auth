<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use WP_UnitTest_Factory;

/**
 * @covers \WildWolf\WordPress\JwtAuth\Admin
 */
class Test_Admin extends \WP_UnitTestCase {
	protected static int $admin_id = 0;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ): void {
		self::$admin_id = $factory->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( self::$admin_id );
	}

	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::$admin_id );
	}

	public function test_construct(): void {
		$admin = Admin::instance();

		self::assertEquals( 10, has_action( 'admin_init', [ $admin, 'admin_init' ] ) );
		self::assertEquals( 10, has_action( 'admin_menu', [ $admin, 'admin_menu' ] ) );
	}

	public function test_admin_init(): void {
		$admin = Admin::instance();
		$admin->admin_init();

		$plugin = plugin_basename( dirname( __DIR__ ) . '/plugin.php' );
		$filter = 'plugin_action_links_' . $plugin;
		self::assertEquals( 10, has_filter( $filter, [ $admin, 'plugin_action_links' ] ) );
	}

	/**
	 * @global bool[] $_registered_pages
	 */
	public function test_admin_menu(): void {
		/** @psalm-var array<string, bool> $_registered_pages */
		global $_registered_pages;

		Admin::instance()->init();
		do_action( 'admin_menu' );

		$key = 'admin_page_' . Admin::OPTIONS_MENU_SLUG;

		self::assertArrayHasKey( $key, $_registered_pages );
		self::assertTrue( $_registered_pages[ $key ] );
	}

	public function test_plugin_action_links(): void {
		$plugin = plugin_basename( dirname( __DIR__ ) . '/plugin.php' );
		$filter = 'plugin_action_links_' . $plugin;

		$plugin = Admin::instance();
		$plugin->admin_init();

		/** @var mixed */
		$links = apply_filters( $filter, [] );

		self::assertIsArray( $links );
		self::assertArrayHasKey( 'settings', $links );
		self::assertContains( 'options-general.php?page=' . Admin::OPTIONS_MENU_SLUG, $links['settings'] );
	}
}
