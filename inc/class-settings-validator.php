<?php

namespace WildWolf\WordPress\JwtAuth;

/**
 * @psalm-import-type SettingsArray from Settings
 */
abstract class Settings_Validator {
	/**
	 * @psalm-param mixed[] $settings
	 * @psalm-return SettingsArray
	 */
	public static function ensure_data_shape( array $settings ): array {
		$defaults = Settings::defaults();
		$result   = $settings + $defaults;
		foreach ( $result as $key => $_value ) {
			if ( ! isset( $defaults[ $key ] ) ) {
				unset( $result[ $key ] );
			}
		}

		/** @var mixed $value */
		foreach ( $result as $key => $value ) {
			$my_type    = gettype( $value );
			$their_type = gettype( $defaults[ $key ] );
			if ( $my_type !== $their_type ) {
				settype( $result[ $key ], $their_type );
			}
		}

		/** @psalm-var SettingsArray */
		return $result;
	}

	/**
	 * @param mixed $settings
	 * @psalm-return SettingsArray $settings
	 */
	public static function sanitize( $settings ): array {
		if ( is_array( $settings ) ) {
			$defaults = Settings::defaults();
			$settings = self::ensure_data_shape( $settings );

			$settings['lifetime'] = filter_var( $settings['lifetime'], FILTER_VALIDATE_INT, [
				'options' => [
					'default'   => $defaults['lifetime'],
					'min_range' => 1,
					'max_range' => PHP_INT_MAX,
				],
			] );

			if ( ! in_array( $settings['algorithm'], array_keys( Settings::get_algorithms() ), true ) ) {
				$settings['algorithm'] = $defaults['algorithm'];
			}

			return $settings;
		}

		return Settings::defaults();
	}
}
