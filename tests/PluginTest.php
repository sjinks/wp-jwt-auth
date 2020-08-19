<?php
declare(strict_types = 1);

use Firebase\JWT\JWT;
use WildWolf\JwtAuth\Plugin;
use WildWolf\JwtAuth\Settings;

class PluginTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();
        unset($_SERVER['AUTH_BEARER_TOKEN']);
    }

    public static function tearDownAfterClass()
    {
        unset($_SERVER['AUTH_BEARER_TOKEN']);
    }

    public function testDefaultRequest(): void
    {
        $this->assertNull(Plugin::instance()->getUserByToken());
        $this->assertEquals(1, Plugin::instance()->determine_current_user(1));
        $this->assertNull(Plugin::instance()->determine_current_user(null));
        $this->assertNull(Plugin::instance()->getJwtError());
        $this->assertNull(Plugin::instance()->getJwtUserId());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAuthenticateApi(): void
    {
        \define('REST_REQUEST', true);
        $this->assertNull(Plugin::instance()->authenticate(null));
    }

    public function testGetUserByToken(): void
    {
        \update_option(Settings::OPTIONS_KEY, ['secret' => 'secret', 'algorithm' => 'HS256', 'lifetime' => 3600]);
        $_SERVER['AUTH_BEARER_TOKEN'] = JWT::encode(['sub' => 1, 'iss' => \get_bloginfo('url')], 'secret', 'HS256');
        $result = Plugin::instance()->getUserByToken();
        $this->assertInstanceOf(\WP_User::class, $result);
        $this->assertEquals(1, $result->ID);
        $this->assertEquals(1, Plugin::instance()->getJwtUserId());
        $this->assertNull(Plugin::instance()->getJwtError());
    }

    /**
     * @dataProvider getUserByTokenErrorDataProvider
     */
    public function testGetUserByToken_error(string $token, string $error): void
    {
        \update_option(Settings::OPTIONS_KEY, ['secret' => 'secret', 'algorithm' => 'HS256', 'lifetime' => 3600]);
        $_SERVER['AUTH_BEARER_TOKEN'] = $token;

        $result = Plugin::instance()->getUserByToken();
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertNotNull(Plugin::instance()->getJwtError());
        $this->assertNull(Plugin::instance()->getJwtUserId());
        $this->assertEquals($error, $result->get_error_message());
    }

    public function getUserByTokenErrorDataProvider(): array
    {
        $malformed = JWT::encode([], 'secret', 'HS256');
        $badIssuer = JWT::encode(['sub' => 1, 'iss' => 'http://somesite.local'], 'secret', 'HS256');
        $badUser   = JWT::encode(['sub' => 100, 'iss' => \get_bloginfo('url')], 'secret', 'HS256');

        return [
            ['invalid', 'Wrong number of segments'],
            [$malformed, 'Malformed token'],
            [$badIssuer, 'Token issuer does not match the server'],
            [$badUser, 'No such user'],
        ];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRestApiInit(): void
    {
        $this->assertEquals(0, \did_action('rest_api_init'));

        \define('REST_REQUEST', true);

        global $wp_rest_server;

        \wp_set_current_user(0);
        $wp_rest_server = new \WP_REST_Server();
        \do_action('rest_api_init');

        $routes = $wp_rest_server->get_routes();
        $this->assertArrayHasKey('/' . Plugin::COMPAT_NS . '/token', $routes);
        $this->assertArrayHasKey('/' . Plugin::COMPAT_NS . '/token/validate', $routes);
        $this->assertArrayHasKey('/' . Plugin::REST_NS . '/generate', $routes);
        $this->assertArrayHasKey('/' . Plugin::REST_NS . '/verify', $routes);
    }
}
