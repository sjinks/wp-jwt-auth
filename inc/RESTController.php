<?php
declare(strict_types=1);

namespace WildWolf\JwtAuth;

use Firebase\JWT\JWT;

class RESTController
{
    public static function instance()
    {
        static $self = null;

        if (!$self) {
            $self = new self();
        }

        return $self;
    }

    private function __construct()
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        \register_rest_route(Plugin::COMPAT_NS, 'token', [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generateToken'],
            'permission_callback' => '__return_true',
        ]);

        \register_rest_route(Plugin::COMPAT_NS, 'token/validate', [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'validateToken'],
            'permission_callback' => '__return_true',
        ]);

        \register_rest_route(Plugin::REST_NS, 'generate', [
            'methods'  => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generateToken'],
            'permission_callback' => '__return_true',
        ]);

        \register_rest_route(Plugin::REST_NS, 'verify', [
            'methods'  => \WP_REST_Server::READABLE,
            'callback' => [$this, 'validateToken'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function generateToken(\WP_REST_Request $request)
    {
        $key = Settings::instance()->getSecret();
        if (empty($key)) {
            return new \WP_Error('misconfiguration', 'JWT_AUTH_SECRET_KEY is not set', ['status' => 500]);
        }

        $body = $request->get_body_params();
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        $user = \wp_authenticate($username, $password);
        if (\is_wp_error($user)) {
            /** @var \WP_Error $user */
            $data = $user->get_error_data();
            if (!\is_array($data)) {
                $data = [];
            }

            $data['status'] = 403;
            $user->add_data($data);
            return $user;
        }

        $issuedAt = \time();
        $notBefore = \apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
        $notAfter  = \apply_filters('jwt_auth_not_after', $issuedAt + Settings::instance()->getLifetime(), $issuedAt);

        $token = [
            'iss' => \get_bloginfo('url'),
            'iat' => $issuedAt,
            'nbf' => $notBefore,
            'exp' => $notAfter,
            'sub' => $user->ID,
        ];

        $token   = \apply_filters('jwt_auth_token_before_sign', $token, $user);
        $encoded = JWT::encode($token, $key, Settings::instance()->getAlgorithm());
        $data    = [
            'token'             => $encoded,
            'user_email'        => $user->user_email,
            'user_nicename'     => $user->user_nicename,
            'user_display_name' => $user->display_name,
        ];

        return \apply_filters('jwt_auth_token_before_dispatch', $data, $user);
    }

    public function validateToken()
    {
        if (!isset($_SERVER['AUTH_BEARER_TOKEN'])) {
            return new \WP_Error('authentication_failed', 'Authorization header is missing or invalid', ['status' => 403]);
        }

        $error = Plugin::instance()->getJwtError();
        if ($error) {
            return new \WP_Error('authentication_failed', $error->getMessage(), ['status' => 403]);
        }

        $userID = Plugin::instance()->getJwtUserId();
        if (!$userID) {
            return new \WP_Error('authentication_failed', 'JWT token is invalid', ['status' => 403]);
        }

        return new \WP_REST_Response(['code' => 'jwt_auth_valid_token']);
    }
}
