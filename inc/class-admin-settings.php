<?php
declare(strict_types = 1);

namespace WildWolf\WordPress\JwtAuth;

use WildWolf\Utils\Singleton;

final class Admin_Settings {
	use Singleton;

	public const OPTION_GROUP = 'ww-jwt-auth';

	/** @var InputFactory */
	private $input_factory;

	private function __construct() {
		$this->input_factory = new InputFactory( Settings::OPTIONS_KEY, Settings::instance() );
		$this->register_settings();
	}

	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			Settings::OPTIONS_KEY,
			[
				'default'           => [],
				'description'       => __( 'JWT Auth Settings', 'ww-jwt-auth' ),
				'type'              => 'array',
				'sanitize_callback' => [ Settings_Validator::class, 'sanitize' ],
			]
		);

		$settings_section = 'jwtauth';
		add_settings_section( $settings_section, __( 'JWT Authentication Settings', 'ww-jwt-auth' ), '__return_null', Admin::OPTIONS_MENU_SLUG );

		add_settings_field(
			'secret',
			__( 'JWT Secret', 'ww-jwt-auth' ),
			[ $this->input_factory, 'input' ],
			Admin::OPTIONS_MENU_SLUG,
			$settings_section,
			[
				'required'  => 'required',
				'label_for' => 'secret',
			]
		);

		add_settings_field(
			'algorithm',
			__( 'JWT Algorithm', 'ww-jwt-auth' ),
			[ $this->input_factory, 'select' ],
			Admin::OPTIONS_MENU_SLUG,
			$settings_section,
			[
				'label_for' => 'algorithm',
				'options'   => Settings::get_algorithms(),
			]
		);

		add_settings_field(
			'lifetime',
			__( 'JWT Token Lifetime', 'ww-jwt-auth' ),
			[ $this->input_factory, 'input' ],
			Admin::OPTIONS_MENU_SLUG,
			$settings_section,
			[
				'required'  => 'required',
				'label_for' => 'lifetime',
				'type'      => 'number',
				'min'       => 60,
			]
		);
	}

	public static function settings_page(): void {
		if ( current_user_can( 'manage_options' ) ) {
			require __DIR__ . '/../views/settings.php';
		}
	}
}
