<?php
declare(strict_types = 1);

namespace WildWolf\JwtAuth;

final class AdminSettings
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
        \add_settings_section('jwtauth', \__('JWT Authentication Settings', 'ww-jwt-auth'), '__return_null', 'ww-jwt-auth');

        \add_settings_field(
            'secret',
            \__('JWT Secret', 'ww-jwt-auth'),
            [$this, 'input_field'],
            'ww-jwt-auth',
            'jwtauth',
            [
                'required' => 'required',
                'label_for' => 'secret'
            ]
        );

        \add_settings_field(
            'algorithm',
            \__('JWT Algorithm', 'ww-jwt-auth'),
            [$this, 'select_field'],
            'ww-jwt-auth',
            'jwtauth',
            [
                'label_for' => 'algorithm',
                'options' => Settings::instance()->getAlgorithms()
            ]
        );

        \add_settings_field(
            'lifetime',
            \__('JWT Token Lifetime', 'ww-jwt-auth'),
            [$this, 'input_field'],
            'ww-jwt-auth',
            'jwtauth',
            [
                'required' => 'required',
                'label_for' => 'lifetime',
                'type' => 'number',
                'min' => 60,
            ]
        );
    }

    public function input_field(array $args): void
    {
        $name  = Settings::OPTIONS_KEY;
        $id    = \esc_attr($args['label_for']);
        $value = \esc_attr(Settings::instance()->getOptionByKey($id));

        unset($args['label_for']);
        $attrs = [];
        foreach ($args as $key => $val) {
            $attrs[] = $key . '="' . \esc_attr($val) . '"';
        }

        $attrs = \join(' ', $attrs);
        
        echo <<< EOT
<input name="{$name}[{$id}]" id="{$id}" value="{$value}" {$attrs}/>
EOT;
    }

    public function select_field(array $args): void
    {
        $name     = Settings::OPTIONS_KEY;
        $id       = \esc_attr($args['label_for']);
        $selected = Settings::instance()->getOptionByKey($id);
        $options  = $args['options'] ?? [];

        unset($args['label_for'], $args['options']);
        $attrs = [];
        foreach ($args as $key => $val) {
            $attrs[] = $key . '="' . \esc_attr($val) . '"';
        }

        $attrs = \join(' ', $attrs);

        echo <<< EOT
<select name="{$name}[{$id}]" id="{$id}" {$attrs}>
EOT;
        foreach ($options as $key => $val) {
            echo '<option value="', \esc_attr($key), '"', \selected($selected, $key, false), '>', \esc_html($val), '</option>';
        }

        echo '</select>';
    }

    public static function settingsPage()
    {
        if (\current_user_can('manage_options')) {
            require __DIR__ . '/../views/settings.php';
        }
    }
}
