<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use Firebase\JWT\JWT;
use WildWolf\WordPress\JwtAuth\Plugin;
use WildWolf\WordPress\JwtAuth\REST_Controller;
use WildWolf\WordPress\JwtAuth\Settings;
use WildWolf\WordPress\Test\Constant_Mocker;
use WP_Error;
use WP_REST_Server;
use WP_User;

/**
 * @covers \WildWolf\WordPress\JwtAuth\Plugin
 * @uses \WildWolf\WordPress\JwtAuth\Settings
 * @uses \WildWolf\WordPress\JwtAuth\Settings_Validator
 */
class Test_Plugin extends \WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();
		unset( $_SERVER['AUTH_BEARER_TOKEN'] );
		Constant_Mocker::clear();
	}

	public static function tearDownAfterClass(): void {
		unset( $_SERVER['AUTH_BEARER_TOKEN'] );
	}

	public function test_default_request(): void {
		self::assertNull( Plugin::instance()->get_user_by_token() );
		self::assertEquals( 1, Plugin::instance()->determine_current_user( 1 ) );
		self::assertFalse( Plugin::instance()->determine_current_user( false ) );
		self::assertNull( Plugin::instance()->get_jwt_error() );
	}

	public function test_authenticate_api(): void {
		define( 'REST_REQUEST', true );
		self::assertNull( Plugin::instance()->authenticate( null ) );
	}

	public function test_get_user_by_token(): void {
		update_option( Settings::OPTIONS_KEY, [
			'secret'    => 'secret',
			'algorithm' => 'HS256',
			'lifetime'  => 3600,
		] );

		Settings::instance()->refresh();

		$_SERVER['AUTH_BEARER_TOKEN'] = JWT::encode( [
			'sub' => 1,
			'iss' => get_bloginfo( 'url' ),
		], 'secret', 'HS256' );

		$result = Plugin::instance()->get_user_by_token();
		self::assertInstanceOf( WP_User::class, $result );
		self::assertEquals( 1, $result->ID );
		self::assertNull( Plugin::instance()->get_jwt_error() );
	}

	/**
	 * @dataProvider data_get_user_by_token_error
	 */
	public function test_get_user_by_token_error( string $token, string $error ): void {
		update_option( Settings::OPTIONS_KEY, [
			'secret'    => 'secret',
			'algorithm' => 'HS256',
			'lifetime'  => 3600,
		] );

		$_SERVER['AUTH_BEARER_TOKEN'] = $token;

		$result = Plugin::instance()->get_user_by_token();
		self::assertInstanceOf( WP_Error::class, $result );
		self::assertNotNull( Plugin::instance()->get_jwt_error() );
		self::assertEquals( $error, $result->get_error_message() );
	}

	/**
	 * @psalm-return iterable<array-key, array{string, string}>
	 */
	public function data_get_user_by_token_error(): iterable {
		$malformed  = JWT::encode( [], 'secret', 'HS256' );
		$bad_issuer = JWT::encode( [
			'sub' => 1,
			'iss' => 'http://somesite.local',
		], 'secret', 'HS256' );
		$bad_user   = JWT::encode( [
			'sub' => 100,
			'iss' => \get_bloginfo( 'url' ),
		], 'secret', 'HS256' );

		return [
			[ 'invalid', 'Wrong number of segments' ],
			[ $malformed, 'Malformed token' ],
			[ $bad_issuer, 'Token issuer does not match the server' ],
			[ $bad_user, 'No such user' ],
		];
	}

	/**
	 * @uses \WildWolf\WordPress\JwtAuth\REST_Controller
	 */
	public function test_rest_api_init(): void {
		self::assertEquals( 0, did_action( 'rest_api_init' ) );

		define( 'REST_REQUEST', true );

		global $wp_rest_server;

		wp_set_current_user( 0 );
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		$routes = $wp_rest_server->get_routes();
		self::assertArrayHasKey( '/' . REST_Controller::COMPAT_NS . '/token', $routes );
		self::assertArrayHasKey( '/' . REST_Controller::COMPAT_NS . '/token/validate', $routes );
		self::assertArrayHasKey( '/' . REST_Controller::REST_NS . '/generate', $routes );
		self::assertArrayHasKey( '/' . REST_Controller::REST_NS . '/verify', $routes );
	}
}
