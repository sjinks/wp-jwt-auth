<?php
declare(strict_types=1);

namespace WildWolf\JwtAuth;

abstract class HttpAuthorization
{
    public static $keys = [
        'HTTP_AUTHORIZATION',
        'REDIRECT_HTTP_AUTHORIZATION',
    ];

    public static function setBearerToken(): void
    {
        foreach (self::$keys as $idx) {
            if (isset($_SERVER[$idx])) {
                self::parseAuthorizationHeader($_SERVER[$idx]);
                return;
            }
        }

        // @codeCoverageIgnoreStart
        self::parseAuthorizationHeader(self::getAuthFromApache());
        // @codeCoverageIgnoreEnd
    }

    // @codeCoverageIgnoreStart
    private static function getAuthFromApache(): string
    {
        $headers = null;
        if (\function_exists('apache_get_headers')) {
            $headers = \array_change_key_case((array)\apache_request_headers(), \CASE_UPPER);
        }

        return $headers['AUTHORIZATION'] ?? '';
    }
    // @codeCoverageIgnoreEnd

    private static function parseAuthorizationHeader(string $auth = null): void
    {
        if (\substr($auth, 0, strlen('Bearer ')) === 'Bearer ') {
            $token = \trim(\substr($auth, \strlen('Bearer' )));
            $_SERVER['AUTH_BEARER_TOKEN'] = $token;
        }
    }
}
