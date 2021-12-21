<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use ArrayAccess;
use LogicException;
use WildWolf\Utils\Singleton;

/**
 * @psalm-type SettingsArray = array{secret: string, algorithm: string, lifetime: positive-int}
 * @template-implements ArrayAccess<string, scalar>
 */
final class Settings implements ArrayAccess {
	use Singleton;

	public const OPTIONS_KEY = 'jwt_auth';

	/** @psalm-var SettingsArray */
	private static $defaults = [
		'secret'    => '',
		'algorithm' => 'HS512',
		'lifetime'  => 86400,
	];

	/**
	 * @var array
	 * @psalm-var SettingsArray
	 */
	private $options;

	// @codeCoverageIgnoreStart
	private function __construct() {
		if ( defined( '\\JWT_AUTH_SECRET_KEY' ) ) {
			self::$defaults['secret'] = (string) constant( '\\JWT_AUTH_SECRET_KEY' );
		}

		$this->refresh();
	}
	// @codeCoverageIgnoreEnd

	public function refresh(): void {
		/** @var mixed */
		$settings      = get_option( self::OPTIONS_KEY );
		$this->options = Settings_Validator::ensure_data_shape( is_array( $settings ) ? $settings : [] );
	}

	/**
	 * @return array
	 * @psalm-return SettingsArray
	 */
	public static function defaults(): array {
		return self::$defaults;
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->options[ (string) $offset ] );
	}

	/**
	 * @param mixed $offset
	 * @return int|string|null
	 */
	public function offsetGet( $offset ) {
		return $this->options[ (string) $offset ] ?? null;
	}

	/**
	 * @param mixed $_offset
	 * @param mixed $_value
	 * @return void
	 * @psalm-return never
	 * @throws LogicException
	 */
	public function offsetSet( $_offset, $_value ): void {
		throw new LogicException();
	}

	/**
	 * @param mixed $_offset
	 * @return void
	 * @psalm-return never
	 * @throws LogicException
	 */
	public function offsetUnset( $_offset ): void {
		throw new LogicException();
	}

	public function get_secret(): string {
		return $this->options['secret'];
	}

	public static function get_algorithms(): array {
		return [
			'HS256' => 'HS256',
			'HS384' => 'HS384',
			'HS512' => 'HS512',
		];
	}

	public function get_algorithm(): string {
		return $this->options['algorithm'];
	}

	public function get_lifetime(): int {
		return $this->options['lifetime'];
	}
}
