<?php
declare(strict_types = 1);

use WildWolf\JwtAuth\Settings;

class SettingTest extends WP_UnitTestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDefaults(): void
    {
        $expected = [
            'secret'    => '',
            'algorithm' => 'HS512',
            'lifetime'  => 86400,
        ];

        $actual = \get_option(Settings::OPTIONS_KEY);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider sanitizeDataProvider
     */
    public function testSanitize($value, array $expected): void
    {
        \update_option(Settings::OPTIONS_KEY, $value);
        $actual = \get_option(Settings::OPTIONS_KEY);
        $this->assertEquals($expected, $actual);
    }

    public function sanitizeDataProvider(): array
    {
        return [
            ['',                    ['secret' => '', 'algorithm' => 'HS512', 'lifetime' => 86400]],
            [['lifetime' => '100'], ['secret' => '', 'algorithm' => 'HS512', 'lifetime' => 100]],
            [['lifetime' => -1],    ['secret' => '', 'algorithm' => 'HS512', 'lifetime' => 86400]],
            [['algorithm' => 'xx'], ['secret' => '', 'algorithm' => 'HS512', 'lifetime' => 86400]],
            [['extra' => 'abcdef'], ['secret' => '', 'algorithm' => 'HS512', 'lifetime' => 86400]],
        ];
    }

    public function testGetters(): void
    {
        $settings = Settings::instance();
        $expected = [
            'secret'    => 'secret',
            'algorithm' => 'HS256',
            'lifetime'  => 3600,
        ];

        \update_option(Settings::OPTIONS_KEY, $expected);
        $this->assertEquals($expected['secret'], $settings->getSecret());
        $this->assertEquals($expected['algorithm'], $settings->getAlgorithm());
        $this->assertEquals($expected['lifetime'], $settings->getLifetime());
    }

    public function testGetOptionByKey(): void
    {
        $settings = Settings::instance();
        $expected = [
            'secret'    => 'secret',
            'algorithm' => 'HS256',
            'lifetime'  => 3600,
        ];

        \update_option(Settings::OPTIONS_KEY, $expected);
        $this->assertEquals($expected['secret'], $settings->getOptionByKey('secret'));
        $this->assertEquals($expected['algorithm'], $settings->getOptionByKey('algorithm'));
        $this->assertEquals($expected['lifetime'], $settings->getOptionByKey('lifetime'));
        $this->assertEquals('', $settings->getOptionByKey('no-such-option'));
    }
}
