<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

/**
 * @covers WildWolf\WordPress\JwtAuth\Settings_Validator
 * @uses \WildWolf\WordPress\JwtAuth\Settings
 * @uses \WildWolf\WordPress\JwtAuth\InputFactory::__construct
 * @psalm-import-type SettingsArray from Settings
 */
class Test_Settings_Validator extends \WP_UnitTestCase {
	/**
	 * @dataProvider data_sanitize
	 * @uses \WildWolf\WordPress\JwtAuth\Admin_Settings
	 * @param mixed $value
	 * @psalm-param SettingsArray $expected
	 */
	public function test_sanitize( $value, array $expected ): void {
		Admin_Settings::instance()->register_settings();
		update_option( Settings::OPTIONS_KEY, $value );

		/** @var mixed */
		$actual = get_option( Settings::OPTIONS_KEY );
		self::assertEquals( $expected, $actual );
	}

	/**
	 * @psalm-return iterable<array-key, array{mixed, SettingsArray}>
	 */
	public function data_sanitize(): iterable {
		return [
			[
				'',
				[
					'secret'    => '',
					'algorithm' => 'HS512',
					'lifetime'  => 86400, 
				],
			],
			[
				[ 'lifetime' => '100' ],
				[
					'secret'    => '',
					'algorithm' => 'HS512',
					'lifetime'  => 100, 
				],
			],
			[
				[ 'lifetime' => -1 ],
				[
					'secret'    => '',
					'algorithm' => 'HS512',
					'lifetime'  => 86400, 
				],
			],
			[
				[ 'algorithm' => 'xx' ],
				[
					'secret'    => '',
					'algorithm' => 'HS512',
					'lifetime'  => 86400, 
				],
			],
			[
				[ 'extra' => 'abcdef' ],
				[
					'secret'    => '',
					'algorithm' => 'HS512',
					'lifetime'  => 86400, 
				],
			],
		];
	}

	/**
	 * @dataProvider data_ensure_data_shape
	 * @param mixed[] $value
	 * @psalm-param SettingsArray $expected
	 */
	public function test_ensure_data_shape( array $value, array $expected ): void {
		$actual = Settings_Validator::ensure_data_shape( $value );
		self::assertEquals( $expected, $actual );
	}

	/**
	 * @psalm-return iterable<array-key, array{mixed[], SettingsArray}>
	 */
	public function data_ensure_data_shape(): iterable {
		return [
			[
				[],
				Settings::defaults(),
			],
			[
				[
					'algorithm' => 'HS256',
					'lifetime'  => '20',
					'extra'     => true,
				],
				[
					'algorithm' => 'HS256',
					'lifetime'  => 20,
				] + Settings::defaults(),
			],
		];
	}
}
