<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use Exception;
use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use UnexpectedValueException;
use WildWolf\Utils\Singleton;
use WildWolf\WordPress\WP_Request_Context;
use WP_Error;
use WP_User;

final class Plugin {
	use Singleton;

	private ?Exception $jwt_error = null;
	private ?int $jwt_user_id     = null;

	// @codeCoverageIgnoreStart
	private function __construct() {
		add_action( 'plugins_loaded', [ Http_Authorization::class, 'set_bearer_token' ] );
		add_action( 'init', [ $this, 'init' ] );
	}

	public function init(): void {
		load_plugin_textdomain( 'ww-jwt-auth', false, plugin_basename( dirname( __DIR__ ) ) . '/lang/' );

		Settings::instance();

		add_filter( 'authenticate', [ $this, 'authenticate' ], 10, 3 );
		add_filter( 'determine_current_user', [ $this, 'determine_current_user' ], 15 );
		add_action( 'rest_api_init', [ $this, 'rest_api_init' ], 10, 1 );

		if ( is_admin() ) {
			Admin::instance();
		}
	}
	// @codeCoverageIgnoreEnd

	/**
	 * @param null|WP_User|WP_Error $user WP_User if the user is authenticated. WP_Error or null otherwise.
	 * @return null|WP_User|WP_Error
	 */
	public function authenticate( $user ) {
		if ( ! WP_Request_Context::is_api_request() || is_wp_error( $user ) ) {
			return $user;
		}

		return $this->get_user_by_token() ?? $user;
	}

	/**
	 * @param int|bool $user_id User ID if one has been determined, false otherwise
	 * @return int|bool
	 */
	public function determine_current_user( $user_id ) {
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		$u = $this->authenticate( null );
		return ( $u instanceof \WP_User ) ? $u->ID : false;
	}

	public function rest_api_init(): void {
		global $current_user;
		if ( WP_Request_Context::is_rest() && $current_user instanceof WP_User && $current_user->ID < 1 ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$current_user = null;
		}

		REST_Controller::instance();
	}

	/**
	 * @return WP_User|WP_Error|null
	 */
	public function get_user_by_token() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$token  = (string) ( $_SERVER['AUTH_BEARER_TOKEN'] ?? '' );
		$secret = Settings::instance()->get_secret();

		$this->jwt_error   = null;
		$this->jwt_user_id = null;

		if ( $token && $secret ) {
			try {
				$decoded = JWT::decode( $token, new Key( $secret, Settings::instance()->get_algorithm() ) );
				if ( empty( $decoded->iss ) || empty( $decoded->sub ) ) {
					throw new UnexpectedValueException( __( 'Malformed token', 'ww-jwt-auth' ) );
				}

				if ( get_bloginfo( 'url' ) !== $decoded->iss ) {
					throw new UnexpectedValueException( __( 'Token issuer does not match the server', 'ww-jwt-auth' ) );
				}

				$user = new WP_User( (int) $decoded->sub );
				if ( $user->ID > 0 ) {
					$this->jwt_user_id = $user->ID;
					return $user;
				}

				throw new UnexpectedValueException( __( 'No such user', 'ww-jwt-auth' ) );
			} catch ( Exception $e ) {
				$this->jwt_error = $e;
				return new WP_Error( 'authentication_failed', $e->getMessage(), [ 'status' => 403 ] );
			}
		}

		return null;
	}

	public function get_jwt_error(): ?Exception {
		return $this->jwt_error;
	}
}
