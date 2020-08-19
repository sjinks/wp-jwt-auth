<?php
declare(strict_types = 1);

use WildWolf\JwtAuth\Admin;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AdminTest extends WP_UnitTestCase
{
    protected static $admin_id = 0;

    /**
     * @param \WP_UnitTest_Factory $factory
     */
    public static function wpSetUpBeforeClass($factory)
    {
        self::$admin_id = $factory->user->create(['role' => 'administrator']);
        \grant_super_admin(self::$admin_id);
    }

    public function setUp()
    {
        parent::setUp();
        \wp_set_current_user(self::$admin_id);
    }

    public function testConstruct(): void
    {
        $admin = Admin::instance();

        $this->assertEquals(10, \has_action('admin_init', [$admin, 'admin_init']));
        $this->assertEquals(10, \has_action('admin_menu', [$admin, 'admin_menu']));
    }

    public function testAdminInit(): void
    {
        $admin = Admin::instance();
        $admin->admin_init();

        $plugin = \plugin_basename(\dirname(__DIR__) . '/plugin.php');
        $filter = 'plugin_action_links_' . $plugin;
        $this->assertEquals(10, \has_filter($filter, [$admin, 'plugin_action_links']));
    }

    public function testAdminMenu(): void
    {
        global $_registered_pages;
        
        Admin::instance();
        \do_action('admin_menu');

        $this->assertArrayHasKey('admin_page_ww-jwt-auth', $_registered_pages);
        $this->assertTrue($_registered_pages['admin_page_ww-jwt-auth']);
    }

    public function testPluginActionLinks(): void
    {
        $plugin = \plugin_basename(\dirname(__DIR__) . '/plugin.php');
        $filter = 'plugin_action_links_' . $plugin;

        $plugin = Admin::instance();
        $plugin->admin_init();

        $links = \apply_filters($filter, []);

        $this->assertArrayHasKey('settings', $links);
        $this->assertContains('options-general.php?page=ww-jwt-auth', $links['settings']);
    }
}
