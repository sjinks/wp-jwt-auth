<?php

namespace WildWolf\WordPress\Test {
	use InvalidArgumentException;

	abstract class Constant_Mocker {
		/**
		 * @var mixed[]
		 * @psalm-var array<string,mixed>
		 */
		private static $constants = [];

		public static function clear(): void {
			self::$constants = [];
		}

		/**
		 * @param string $constant
		 * @param mixed $value
		 * @return void
		 * @throws InvalidArgumentException
		 */
		public static function define( string $constant, $value ): void {
			if ( isset( self::$constants[ $constant ] ) ) {
				throw new InvalidArgumentException( sprintf( 'Constant "%s" is already defined', $constant ) );
			}

			/** @psalm-suppress MixedAssignment */
			self::$constants[ $constant ] = $value;
		}

		public static function defined( string $constant ): bool {
			return isset( self::$constants[ $constant ] );
		}

		/**
		 * @param string $constant
		 * @return mixed
		 * @throws InvalidArgumentException
		 */
		public static function constant( string $constant ) {
			if ( ! isset( self::$constants[ $constant ] ) ) {
				throw new InvalidArgumentException( sprintf( 'Constant "%s" is not defined', $constant ) );
			}

			return self::$constants[ $constant ];
		}
	}
}

namespace WildWolf\WordPress\JwtAuth {
	use InvalidArgumentException;
	use WildWolf\WordPress\Test\Constant_Mocker;

	/**
	 * @param string $constant
	 * @param mixed $value
	 * @return void
	 * @throws InvalidArgumentException
	 */
	function define( $constant, $value ) {
		Constant_Mocker::define( $constant, $value );
	}

	/**
	 * @param string $constant 
	 * @return bool 
	 */
	function defined( $constant ) {
		return Constant_Mocker::defined( $constant );
	}

	/**
	 * @param string $constant 
	 * @return mixed 
	 * @throws InvalidArgumentException 
	 */
	function constant( $constant ) {
		return Constant_Mocker::constant( $constant );
	}
}

namespace WildWolf\WordPress {
	use InvalidArgumentException;
	use WildWolf\WordPress\Test\Constant_Mocker;

	/**
	 * @param string $constant
	 * @param mixed $value
	 * @return void
	 * @throws InvalidArgumentException
	 */
	function define( $constant, $value ) {
		Constant_Mocker::define( $constant, $value );
	}

	/**
	 * @param string $constant 
	 * @return bool 
	 */
	function defined( $constant ) {
		return Constant_Mocker::defined( $constant );
	}

	/**
	 * @param string $constant 
	 * @return mixed 
	 * @throws InvalidArgumentException 
	 */
	function constant( $constant ) {
		return Constant_Mocker::constant( $constant );
	}
}
