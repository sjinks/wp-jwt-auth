<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

// phpcs:disable WordPress.Security.ValidatedSanitizedInput

/**
 * @covers \WildWolf\WordPress\JwtAuth\Http_Authorization
 */
class Test_Http_AuthorizationTest extends \WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['AUTH_BEARER_TOKEN'] );
	}

	public static function tearDownAfterClass(): void {
		unset( $_SERVER['HTTP_AUTHORIZATION'], $_SERVER['AUTH_BEARER_TOKEN'] );
	}

	/**
	 * @dataProvider data_set_bearer_token_success
	 */
	public function test_set_bearer_token_success( string $auth, string $expected ): void {
		self::assertArrayNotHasKey( 'HTTP_AUTHORIZATION', $_SERVER );
		self::assertArrayNotHasKey( 'AUTH_BEARER_TOKEN', $_SERVER );

		$_SERVER['HTTP_AUTHORIZATION'] = $auth;
		Http_Authorization::set_bearer_token();

		self::assertArrayHasKey( 'AUTH_BEARER_TOKEN', $_SERVER );
		self::assertEquals( $expected, $_SERVER['AUTH_BEARER_TOKEN'] );
	}

	/**
	 * @psalm-return iterable<array-key, array{string, string}>
	 */
	public function data_set_bearer_token_success(): iterable {
		return [
			[ 'Bearer token', 'token' ],
			[ 'Bearer  token  ', 'token' ],
			[ 'Bearer long token', 'long token' ],
		];
	}

	/**
	 * @dataProvider data_set_bearer_token_failure
	 */
	public function test_set_bearer_token_failure( string $auth ): void {
		self::assertArrayNotHasKey( 'HTTP_AUTHORIZATION', $_SERVER );
		self::assertArrayNotHasKey( 'AUTH_BEARER_TOKEN', $_SERVER );

		if ( $auth ) {
			$_SERVER['HTTP_AUTHORIZATION'] = $auth;
		}

		Http_Authorization::set_bearer_token();

		self::assertArrayNotHasKey( 'AUTH_BEARER_TOKEN', $_SERVER );
	}

	/**
	 * @psalm-return iterable<array-key, array{string}>
	 */
	public function data_set_bearer_token_failure(): iterable {
		return [
			[ '' ],
			[ 'Teddy Bear' ],
		];
	}
}
