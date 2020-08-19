<?php
declare(strict_types = 1);

namespace WildWolf\JwtAuth;

final class Settings
{
    const OPTIONS_KEY = 'jwt_auth';

    public static function instance(): self
    {
        static $self = null;
        if (!$self) {
            // @codeCoverageIgnoreStart
            $self = new self();
            // @codeCoverageIgnoreEnd
        }

        return $self;
    }

    // @codeCoverageIgnoreStart
    private function __construct()
    {
        \register_setting(
            'ww-jwt-auth',
            self::OPTIONS_KEY,
            [
                'default' => [],
                'description' => 'JWT Auth Settings',
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeCallback'],
            ]);

        $settings = \get_option(self::OPTIONS_KEY, []);
        if (!\is_array($settings)) {
            $settings = [];
        }

        $settings = self::checkSettings($settings);
        if (\is_array($settings)) {
            \update_option(self::OPTIONS_KEY, $settings);
        }
    }
    // @codeCoverageIgnoreEnd

    public function sanitizeCallback($settings): array
    {
        if (!\is_array($settings)) {
            $settings = [];
        }

        $settings['lifetime'] = (int)($settings['lifetime'] ?? 0);
        if ($settings['lifetime'] < 1) {
            unset($settings['lifetime']);
        }

        $algorithm  = $settings['algorithm'] ?? '';
        $algorithms = $this->getAlgorithms();
        if (!isset($algorithms[$algorithm])) {
            unset($settings['algorithm']);
        }

        return self::checkSettings($settings) ?? $settings;
    }

    /**
     * Checks settings array
     * 
     * @param array $settings
     * @return array|null null if $settings are OK, array with the corrected settings if not
     */
    public static function checkSettings(array $settings): ?array
    {
        $defaults = [
            'secret'    => \defined('\\JWT_AUTH_SECRET_KEY') ? constant('\\JWT_AUTH_SECRET_KEY') : '',
            'algorithm' => 'HS512',
            'lifetime'  => 86400,
        ];

        $dirty = false;
        foreach ($settings as $key => $value) {
            if (!isset($defaults[$key]) || \gettype($value) !== \gettype($defaults[$key])) {
                $dirty = true;
                unset($settings[$key]);
            }
        }

        foreach ($defaults as $key => $value) {
            if (!isset($settings[$key])) {
                $dirty = true;
                $settings[$key] = $value;
            }
        }

        return $dirty ? $settings : null;
    }

    public function getSecret(): string
    {
        $settings = \get_option(self::OPTIONS_KEY, []);
        return (string)$settings['secret'];
    }

    public function getAlgorithms(): array
    {
        return ['HS256' => 'HS256', 'HS384' => 'HS384', 'HS512' => 'HS512'];
    }

    public function getAlgorithm(): string
    {
        $allowed = $this->getAlgorithms();
        $settings = \get_option(self::OPTIONS_KEY, []);
        $algorithm = $settings['algorithm'];

        return isset($allowed[$algorithm]) ? $algorithm : 'HS512';
    }

    public function getLifetime(): int
    {
        $settings = \get_option(self::OPTIONS_KEY, []);
        $lifetime = (int)$settings['lifetime'];
        return $lifetime > 1 ? $lifetime : 86400;
    }

    public function getOptionByKey(string $key)
    {
        $settings = \get_option(self::OPTIONS_KEY, []);
        return isset($settings[$key]) ? (string)$settings[$key] : '';
    }
}
