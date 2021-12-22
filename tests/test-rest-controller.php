<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use Firebase\JWT\JWT;
use Spy_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_User;

/**
 * @covers \WildWolf\WordPress\JwtAuth\REST_Controller
 * @uses \WildWolf\WordPress\JwtAuth\Plugin
 * @uses \WildWolf\WordPress\JwtAuth\Settings
 * @uses \WildWolf\WordPress\JwtAuth\Settings_Validator
 */
class Test_REST_Controller extends \WP_Test_REST_TestCase {
	private const SECRET = 'secret';
	private const ALGO   = 'HS256';

	/**
	 * @global WP_REST_Server|null $wp_rest_server
	 */
	public function setUp(): void {
		parent::setUp();

		unset( $_SERVER['AUTH_BEARER_TOKEN'] );

		update_option( Settings::OPTIONS_KEY, [
			'secret'    => self::SECRET,
			'algorithm' => self::ALGO,
			'lifetime'  => 3600,
		] );

		Settings::instance()->refresh();

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = new Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
		REST_Controller::instance()->register_routes();
	}

	/**
	 * @global WP_REST_Server|null $wp_rest_server
	 */
	public function tearDown(): void {
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tearDown();
	}

	public static function tearDownAfterClass(): void {
		unset( $_SERVER['AUTH_BEARER_TOKEN'] );
		parent::tearDownAfterClass();
	}

	protected function dispatch_request( string $method, string $route, ?array $post = null ): WP_REST_Response {
		$route = '/' . ltrim( $route, '/' );

		$request = new WP_REST_Request( $method, $route );
		if ( $post ) {
			$request->set_body_params( $post );
		}

		return rest_do_request( $request );
	}

	public function test_validate_token_good(): void {
		$_SERVER['AUTH_BEARER_TOKEN'] = JWT::encode( [
			'sub' => 1,
			'iss' => get_bloginfo( 'url' ),
		], self::SECRET, self::ALGO );

		Plugin::instance()->get_user_by_token();
		$response = $this->dispatch_request( 'GET', REST_Controller::REST_NS . '/verify' );

		self::assertObjectHasAttribute( 'status', $response );
		self::assertEquals( 200, $response->status );
		self::assertObjectHasAttribute( 'data', $response );
		self::assertIsArray( $response->data );
		self::assertArrayHasKey( 'code', $response->data );
		self::assertEquals( 'jwt_auth_valid_token', $response->data['code'] );
	}

	/**
	 * @dataProvider data_validate_token_bad
	 */
	public function test_validate_token_bad( string $token ): void {
		$_SERVER['AUTH_BEARER_TOKEN'] = $token;

		Plugin::instance()->get_user_by_token();
		$response = $this->dispatch_request( 'GET', REST_Controller::REST_NS . '/verify' );
		self::assertObjectHasAttribute( 'status', $response );
		self::assertEquals( 403, $response->status );
		self::assertObjectHasAttribute( 'data', $response );
		self::assertIsArray( $response->data );
		self::assertArrayHasKey( 'code', $response->data );
		self::assertEquals( 'authentication_failed', $response->data['code'] );
	}

	/**
	 * @psalm-return iterable<string,array{string}>
	 */
	public function data_validate_token_bad(): iterable {
		$malformed = JWT::encode( [
			'abc' => 'def',
		], self::SECRET, self::ALGO );

		$bad_issuer = JWT::encode( [
			'sub' => -1,
			'iss' => 'https://www.cia.gov',
		], self::SECRET, self::ALGO );

		$bad_user = JWT::encode( [
			'sub' => -1,
			'iss' => get_bloginfo( 'url' ),
		], self::SECRET, self::ALGO );

		return [
			'no token'        => [ '' ],
			'bad token'       => [ 'xxx' ],
			'malformed token' => [ $malformed ],
			'bad issuer'      => [ $bad_issuer ],
			'bad user'        => [ $bad_user ],
		];
	}

	public function test_generate_token(): void {
		$post = [
			'username' => 'admin',
			'password' => 'password',
		];

		$response = $this->dispatch_request( 'POST', REST_Controller::REST_NS . '/generate', $post );
		self::assertObjectHasAttribute( 'status', $response );
		self::assertEquals( 200, $response->status );
		self::assertObjectHasAttribute( 'data', $response );
		self::assertIsArray( $response->data );
		self::assertArrayHasKey( 'token', $response->data );
		self::assertArrayHasKey( 'user_email', $response->data );
		self::assertArrayHasKey( 'user_nicename', $response->data );
		self::assertArrayHasKey( 'user_display_name', $response->data );
		self::assertArrayHasKey( 'display_name', $response->data );

		$user = new WP_User( 1 );
		self::assertEquals( $user->user_email, $response->data['user_email'] );
		self::assertEquals( $user->user_nicename, $response->data['user_nicename'] );
		self::assertEquals( $user->display_name, $response->data['user_display_name'] );
		self::assertEquals( $user->display_name, $response->data['display_name'] );
	}

	public function test_generate_token_fail(): void {
		$post = [
			'username' => 'bad-user',
			'password' => 'bad-password',
		];

		$response = $this->dispatch_request( 'POST', REST_Controller::REST_NS . '/generate', $post );
		self::assertObjectHasAttribute( 'status', $response );
		self::assertEquals( 403, $response->status );
		self::assertObjectHasAttribute( 'data', $response );
		self::assertIsArray( $response->data );
		self::assertArrayHasKey( 'code', $response->data );
		self::assertArrayHasKey( 'message', $response->data );
		self::assertArrayHasKey( 'data', $response->data );
	}

	public function test_generate_token_misconfiguration(): void {
		update_option( Settings::OPTIONS_KEY, [
			'secret'    => '',
			'algorithm' => self::ALGO,
			'lifetime'  => 3600,
		] );

		Settings::instance()->refresh();
		$post = [
			'username' => 'admin',
			'password' => 'password',
		];

		$response = $this->dispatch_request( 'POST', REST_Controller::REST_NS . '/generate', $post );
		self::assertObjectHasAttribute( 'status', $response );
		self::assertEquals( 500, $response->status );
		self::assertObjectHasAttribute( 'data', $response );
		self::assertIsArray( $response->data );
		self::assertArrayHasKey( 'code', $response->data );
		self::assertArrayHasKey( 'message', $response->data );
		self::assertArrayHasKey( 'data', $response->data );

		self::assertEquals( 'misconfiguration', $response->data['code'] );
	}

	public function test_generate_verify_interop(): void {
		$post = [
			'username' => 'admin',
			'password' => 'password',
		];

		$response = $this->dispatch_request( 'POST', REST_Controller::REST_NS . '/generate', $post );
		self::assertObjectHasAttribute( 'status', $response );
		self::assertEquals( 200, $response->status );
		self::assertObjectHasAttribute( 'data', $response );
		self::assertIsArray( $response->data );
		self::assertArrayHasKey( 'token', $response->data );

		$_SERVER['AUTH_BEARER_TOKEN'] = $response->data['token'];

		Plugin::instance()->get_user_by_token();
		$response = $this->dispatch_request( 'GET', REST_Controller::REST_NS . '/verify' );
		self::assertObjectHasAttribute( 'status', $response );
		self::assertEquals( 200, $response->status );
	}
}
