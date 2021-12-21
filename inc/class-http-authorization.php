<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

abstract class Http_Authorization {
	/** @var string[] */
	public static $keys = [
		'HTTP_AUTHORIZATION',
		'REDIRECT_HTTP_AUTHORIZATION',
	];

	public static function set_bearer_token(): void {
		foreach ( self::$keys as $idx ) {
			if ( isset( $_SERVER[ $idx ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				self::parse_authorization_header( (string) $_SERVER[ $idx ] );
				return;
			}
		}

		// @codeCoverageIgnoreStart
		self::parse_authorization_header( self::get_auth_from_apache() );
		// @codeCoverageIgnoreEnd
	}

	// @codeCoverageIgnoreStart
	private static function get_auth_from_apache(): string {
		$headers = null;
		if ( function_exists( 'apache_get_headers' ) ) {
			$headers = array_change_key_case( (array) apache_request_headers(), CASE_UPPER );
		}

		return (string) ( $headers['AUTHORIZATION'] ?? '' );
	}
	// @codeCoverageIgnoreEnd

	private static function parse_authorization_header( ?string $auth = null ): void {
		$bearer = 'Bearer ';
		if ( $auth && substr( $auth, 0, strlen( $bearer ) ) === $bearer ) {
			$token                        = trim( substr( $auth, strlen( $bearer ) ) );
			$_SERVER['AUTH_BEARER_TOKEN'] = $token;
		}
	}
}
