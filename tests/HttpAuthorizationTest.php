<?php
declare(strict_types = 1);

use WildWolf\JwtAuth\HttpAuthorization;

class HttpAuthorizationTest extends WP_UnitTestCase
{
    public function setUp()
    {
        parent::setUp();
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['AUTH_BEARER_TOKEN']);
    }

    public static function tearDownAfterClass()
    {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['AUTH_BEARER_TOKEN']);
    }

    /**
     * @dataProvider setBearerTokenSuccessDataProvider
     */
    public function testSetBearerToken_success(string $auth, string $expected): void
    {
        $this->assertArrayNotHasKey('HTTP_AUTHORIZATION', $_SERVER);
        $this->assertArrayNotHasKey('AUTH_BEARER_TOKEN', $_SERVER);

        $_SERVER['HTTP_AUTHORIZATION'] = $auth;
        HttpAuthorization::setBearerToken();

        $this->assertArrayHasKey('AUTH_BEARER_TOKEN', $_SERVER);
        $this->assertEquals($expected, $_SERVER['AUTH_BEARER_TOKEN']);
    }

    public function setBearerTokenSuccessDataProvider(): array
    {
        return [
            ['Bearer token', 'token'],
            ['Bearer  token  ', 'token'],
            ['Bearer long token', 'long token'],
        ];
    }

    /**
     * @dataProvider setBearerTokenFailureDataProvider
     */
    public function testSetBearerToken_failure(string $auth): void
    {
        $this->assertArrayNotHasKey('HTTP_AUTHORIZATION', $_SERVER);
        $this->assertArrayNotHasKey('AUTH_BEARER_TOKEN', $_SERVER);

        if ($auth) {
            $_SERVER['HTTP_AUTHORIZATION'] = $auth;
        }

        HttpAuthorization::setBearerToken();

        $this->assertArrayNotHasKey('AUTH_BEARER_TOKEN', $_SERVER);
    }

    public function setBearerTokenFailureDataProvider(): array
    {
        return [
            [''],
            ['Teddy Bear'],
        ];
    }
}
