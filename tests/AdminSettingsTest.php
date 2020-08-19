<?php
declare(strict_types = 1);

use WildWolf\JwtAuth\AdminSettings;
use WildWolf\JwtAuth\Settings;

class AdminSettingTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();
        \wp_set_current_user(1);
    }

    public static function tearDownAfterClass()
    {
        \wp_set_current_user(1);
    }

    public function testConstruct(): void
    {
        global $wp_settings_sections;
        global $wp_settings_fields;

        AdminSettings::instance();

        $this->assertArrayHasKey('ww-jwt-auth', $wp_settings_sections);
        $this->assertArrayHasKey('jwtauth', $wp_settings_sections['ww-jwt-auth']);

        $this->assertArrayHasKey('ww-jwt-auth', $wp_settings_fields);
        $this->assertArrayHasKey('jwtauth', $wp_settings_fields['ww-jwt-auth']);

        $this->assertArrayHasKey('secret', $wp_settings_fields['ww-jwt-auth']['jwtauth']);
        $this->assertArrayHasKey('algorithm', $wp_settings_fields['ww-jwt-auth']['jwtauth']);
        $this->assertArrayHasKey('lifetime', $wp_settings_fields['ww-jwt-auth']['jwtauth']);
    }

    public function testSettingsPage_guest(): void
    {
        \wp_set_current_user(0);
        \ob_start();
        AdminSettings::instance()->settingsPage();
        $contents = \ob_get_clean();

        $this->assertEmpty($contents);
    }

    public function testSettingsPage_admin(): void
    {
        \ob_start();
        AdminSettings::instance()->settingsPage();
        $contents = \ob_get_clean();

        $this->assertNotEmpty($contents);
    }

    public function testInputField(): void
    {
        \ob_start();
        AdminSettings::instance()->input_field([
            'label_for' => 'secret',
            'required' => 'required',
            'type' => 'password',
        ]);
        $contents = \ob_get_clean();

        $expected = '<input name="' . Settings::OPTIONS_KEY . '[secret]" id="secret" value="" required="required" type="password"/>';
        $this->assertEquals($expected, $contents);
    }

    public function testSelectField(): void
    {
        \ob_start();
        AdminSettings::instance()->select_field([
            'label_for' => 'algorithm',
            'required' => 'required',
            'options' => Settings::instance()->getAlgorithms(),
        ]);
        $contents = \ob_get_clean();

        $expected_1 = '<select name="' . Settings::OPTIONS_KEY . '[algorithm]" id="algorithm" required="required">';
        $expected_2 = '<option value="HS512" selected=\'selected\'>HS512</option>';
        $this->assertContains($expected_1, $contents);
        $this->assertContains($expected_2, $contents);
    }
}
