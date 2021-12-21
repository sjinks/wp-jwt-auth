<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

/**
 * @covers \WildWolf\WordPress\JwtAuth\Admin_Settings
 * @uses \WildWolf\WordPress\JwtAuth\InputFactory
 * @uses \WildWolf\WordPress\JwtAuth\Settings
 */
class Test_Admin_Settings extends \WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( 1 );
	}

	/**
	 * @global mixed[] $wp_settings_sections
	 * @global mixed[] $wp_settings_fields
	 */
	public function test_construct(): void {
		global $wp_settings_sections;
		global $wp_settings_fields;

		/** @psalm-var array<string, array<string, mixed>> $wp_settings_sections */
		/** @psalm-var array<string, array<string, mixed>> $wp_settings_fields */

		Admin_Settings::instance();

		self::assertArrayHasKey( Admin_Settings::OPTION_GROUP, $wp_settings_sections );
		self::assertArrayHasKey( 'jwtauth', $wp_settings_sections[ Admin_Settings::OPTION_GROUP ] );

		self::assertArrayHasKey( Admin_Settings::OPTION_GROUP, $wp_settings_fields );
		self::assertArrayHasKey( 'jwtauth', $wp_settings_fields[ Admin_Settings::OPTION_GROUP ] );

		self::assertArrayHasKey( 'secret', $wp_settings_fields[ Admin_Settings::OPTION_GROUP ]['jwtauth'] );
		self::assertArrayHasKey( 'algorithm', $wp_settings_fields[ Admin_Settings::OPTION_GROUP ]['jwtauth'] );
		self::assertArrayHasKey( 'lifetime', $wp_settings_fields[ Admin_Settings::OPTION_GROUP ]['jwtauth'] );
	}

	public function testSettingsPage_guest(): void {
		wp_set_current_user( 0 );
		ob_start();
		Admin_Settings::instance()->settings_page();
		$contents = ob_get_clean();

		self::assertEmpty( $contents );
	}

	public function testSettingsPage_admin(): void {
		ob_start();
		Admin_Settings::instance()->settings_page();
		$contents = ob_get_clean();

		self::assertNotEmpty( $contents );
	}
}
