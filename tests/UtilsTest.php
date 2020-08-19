<?php
declare(strict_types = 1);

use WildWolf\JwtAuth\Utils;

class UtilsTest extends WP_UnitTestCase
{
    public function testIsGuest(): void
    {
        \wp_set_current_user(0);
        $this->assertTrue(Utils::isGuest());

        \wp_set_current_user(1);
        $this->assertFalse(Utils::isGuest());
    }

    public function testIsRestRequest(): void
    {
        $this->assertFalse(Utils::isRestRequest());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsRestRequest_positive(): void
    {
        \define('REST_REQUEST', true);
        $this->assertTrue(Utils::isRestRequest());
    }

    public function testIsApiRequest(): void
    {
        $this->assertFalse(Utils::isApiRequest());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIsApiRequest_positive(): void
    {
        \define('XMLRPC_REQUEST', true);
        $this->assertTrue(Utils::isApiRequest());
    }
}
