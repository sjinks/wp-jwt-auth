<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use Firebase\JWT\JWT;
use WildWolf\Utils\Singleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class REST_Controller {
	use Singleton;

	public const REST_NS   = 'wildwolf/jwtauth/v1';
	public const COMPAT_NS = 'jwt-auth/v1';

	// @codeCoverageIgnoreStart
	private function __construct() {
		$this->register_routes();
	}
	// @codeCoverageIgnoreEnd

	public function register_routes(): void {
		register_rest_route( self::COMPAT_NS, 'token', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'generate_token' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::COMPAT_NS, 'token/validate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'validate_token' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::REST_NS, 'generate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'generate_token' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route( self::REST_NS, 'verify', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'validate_token' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * @return WP_REST_Response|WP_Error
	 */
	public function generate_token( WP_REST_Request $request ) {
		$key = Settings::instance()->get_secret();
		if ( empty( $key ) ) {
			return new WP_Error( 'misconfiguration', 'JWT_AUTH_SECRET_KEY is not set', [ 'status' => 500 ] );
		}

		$body     = $request->get_body_params();
		$username = (string) ( $body['username'] ?? '' );
		$password = (string) ( $body['password'] ?? '' );

		$user = wp_authenticate( $username, $password );
		if ( is_wp_error( $user ) ) {
			/** @var mixed */
			$data = $user->get_error_data();
			if ( ! is_array( $data ) ) {
				$data = [];
			}

			$data['status'] = 403;
			$user->add_data( $data );
			return $user;
		}

		$issued_at  = time();
		$not_before = (int) apply_filters( 'jwt_auth_not_before', $issued_at, $issued_at );
		$not_after  = (int) apply_filters( 'jwt_auth_not_after', $issued_at + Settings::instance()->get_lifetime(), $issued_at );

		$token = [
			'iss' => get_bloginfo( 'url' ),
			'iat' => $issued_at,
			'nbf' => $not_before,
			'exp' => $not_after,
			'sub' => $user->ID,
		];

		/** @var mixed[] */
		$token   = apply_filters( 'jwt_auth_token_before_sign', $token, $user );
		$encoded = JWT::encode( $token, $key, Settings::instance()->get_algorithm() );
		$data    = [
			'token'             => $encoded,
			'user_email'        => $user->user_email,
			'user_nicename'     => $user->user_nicename,
			'user_display_name' => $user->display_name,
		];

		/** @var WP_REST_Response */
		return rest_ensure_response( apply_filters( 'jwt_auth_token_before_dispatch', $data, $user ) );
	}

	/**
	 * @return WP_Error|WP_REST_Response
	 */
	public function validate_token() {
		if ( empty( $_SERVER['AUTH_BEARER_TOKEN'] ) ) {
			return new WP_Error( 'authentication_failed', 'Authorization header is missing or invalid', [ 'status' => 403 ] );
		}

		$error = Plugin::instance()->get_jwt_error();
		if ( $error ) {
			return new WP_Error( 'authentication_failed', $error->getMessage(), [ 'status' => 403 ] );
		}

		/** @var WP_REST_Response */
		return rest_ensure_response( [ 'code' => 'jwt_auth_valid_token' ] );
	}
}
