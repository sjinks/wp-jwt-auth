<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use LogicException;

/**
 * @psalm-import-type SettingsArray from Settings
 * @covers \WildWolf\WordPress\JwtAuth\Settings
 */
class Test_Settings extends \WP_UnitTestCase {
	public function test_defaults(): void {
		$actual = Settings::defaults();

		self::assertIsArray( $actual );
		self::assertArrayHasKey( 'secret', $actual );
		self::assertArrayHasKey( 'algorithm', $actual );
		self::assertArrayHasKey( 'lifetime', $actual );

		$algoritms = Settings::get_algorithms();
		self::assertIsInt( $actual['lifetime'] );
		self::assertIsString( $actual['secret'] );
		self::assertContains( $actual['algorithm'], array_keys( $algoritms ) );
	}

	/**
	 * @uses \WildWolf\WordPress\JwtAuth\Settings_Validator::ensure_data_shape
	 */
	public function test_getters(): void {
		$settings = Settings::instance();
		$expected = [
			'secret'    => 'secret',
			'algorithm' => 'HS256',
			'lifetime'  => 3600,
		];

		update_option( Settings::OPTIONS_KEY, $expected );
		$settings->refresh();

		self::assertEquals( $expected['secret'], $settings->get_secret() );
		self::assertEquals( $expected['algorithm'], $settings->get_algorithm() );
		self::assertEquals( $expected['lifetime'], $settings->get_lifetime() );
	}

	/**
	 * @uses \WildWolf\WordPress\JwtAuth\Settings_Validator::ensure_data_shape
	 */
	public function test_offset_get(): void {
		$settings = Settings::instance();
		$expected = [
			'secret'    => 'secret',
			'algorithm' => 'HS256',
			'lifetime'  => 3600,
		];

		update_option( Settings::OPTIONS_KEY, $expected );
		$settings->refresh();

		self::assertEquals( $expected['secret'], $settings['secret'] );
		self::assertEquals( $expected['algorithm'], $settings['algorithm'] );
		self::assertEquals( $expected['lifetime'], $settings['lifetime'] );

		self::assertNull( $settings['this_key_does_not_exist'] );
	}

	public function test_offset_exists(): void {
		$settings = Settings::instance();

		self::assertTrue( isset( $settings['secret'] ) );
		self::assertTrue( isset( $settings['algorithm'] ) );
		self::assertTrue( isset( $settings['lifetime'] ) );

		self::assertFalse( isset( $settings['this_key_does_not_exist'] ) );
	}

	public function test_offset_set(): void {
		$this->expectException( LogicException::class );

		$settings           = Settings::instance();
		$settings['secret'] = '';
	}

	public function test_offset_unset(): void {
		$this->expectException( LogicException::class );

		$settings = Settings::instance();
		unset( $settings['secret'] );
	}
}
