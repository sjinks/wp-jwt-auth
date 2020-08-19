<?php
declare(strict_types=1);

namespace WildWolf\JwtAuth;

final class Admin
{
    public static function instance(): self
    {
        static $self = null;
        if (!$self) {
            $self = new self();
        }

        return $self;
    }

    private function __construct()
    {
        \load_plugin_textdomain('ww-jwt-auth', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(__DIR__)) . '/lang/');

        \add_action('admin_init', [$this, 'admin_init']);
        \add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function admin_init(): void
    {
        AdminSettings::instance();

        $plugin = \plugin_basename(\dirname(__DIR__) . '/plugin.php');
        \add_filter('plugin_action_links_' . $plugin, [$this, 'plugin_action_links']);
    }

    public function admin_menu()
    {
        \add_options_page('JWT Authentication', 'JWT Auth', 'manage_options', 'ww-jwt-auth', [AdminSettings::class, 'settingsPage']);
    }

    public function plugin_action_links(array $links) : array
    {
        $url  = \esc_attr(\admin_url('options-general.php?page=ww-jwt-auth'));
        $link = '<a href="' . $url . '">' . \__('Settings', 'ww-jwt-auth') . '</a>';
        $links['settings'] = $link;
        return $links;
    }
}
