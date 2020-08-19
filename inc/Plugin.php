<?php
declare(strict_types = 1);

namespace WildWolf\JwtAuth;

use Firebase\JWT\JWT;

final class Plugin
{
    const REST_NS = 'wildwolf/jwtauth/v1';
    const COMPAT_NS = 'jwt-auth/v1';

    /**
     * @var \Exception|null
     */
    private $jwt_error = null;

    /**
     * @var int|null
     */
    private $jwt_user_id = null;

    public static function instance()
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
        \add_action('plugins_loaded', [HttpAuthorization::class, 'setBearerToken']);
        \add_action('init',           [$this, 'init']);
    }

    public function init(): void
    {
        \load_plugin_textdomain('ww-jwt-auth', /** @scrutinizer ignore-type */ false, \plugin_basename(\dirname(__DIR__)) . '/lang/');

        Settings::instance();

        \add_filter('authenticate',           [$this, 'authenticate'], 10, 3);
        \add_filter('determine_current_user', [$this, 'determine_current_user'], 15);
        \add_action('rest_api_init',          [$this, 'rest_api_init'], 10, 1);

        if (\is_admin()) {
            Admin::instance();
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * @param null|\WP_User|\WP_Error $user WP_User if the user is authenticated. WP_Error or null otherwise.
     * @return null|\WP_User|\WP_Error
     */
    public function authenticate($user)
    {
        if (!Utils::isApiRequest() || \is_wp_error($user)) {
            return $user;
        }

        return $this->getUserByToken() ?? $user;
    }

    /**
     * @param int|bool $user_id User ID if one has been determined, false otherwise
     * @return int|bool
     */
    public function determine_current_user($user_id)
    {
        if (!empty($user_id)) {
            return $user_id;
        }

        $u = $this->authenticate(null);
        return ($u instanceof \WP_User) ? $u->ID : false;
    }

    public function rest_api_init(): void
    {
        if (Utils::isRestRequest() && Utils::isGuest()) {
            global $current_user;
            $current_user = null;
        }

        RESTController::instance();
    }

    /**
     * @return \WP_User|\WP_Error|null
     */
    public function getUserByToken()
    {
        $token  = $_SERVER['AUTH_BEARER_TOKEN'] ?? '';
        $secret = Settings::instance()->getSecret();

        $this->jwt_error   = null;
        $this->jwt_user_id = null;

        if ($token && $secret) {
            try {
                $decoded = JWT::decode($token, $secret, [Settings::instance()->getAlgorithm()]);
                if (empty($decoded->iss) || empty($decoded->sub)) {
                    throw new \UnexpectedValueException(\__('Malformed token', 'ww-jwt-auth'));
                }

                if ($decoded->iss !== \get_bloginfo('url')) {
                    throw new \UnexpectedValueException(\__('Token issuer does not match the server', 'ww-jwt-auth'));
                }

                $user = new \WP_User($decoded->sub);
                if ($user->ID > 0) {
                    $this->jwt_user_id = $user->ID;
                    return $user;
                }

                throw new \UnexpectedValueException(\__('No such user', 'ww-jwt-auth'));
            } catch (\Exception $e) {
                $this->jwt_error = $e;
                return new \WP_Error('authentication_failed', $e->getMessage(), ['status' => 403]);
            }
        }

        return null;
    }

    public function getJwtError(): ?\Exception
    {
        return $this->jwt_error;
    }

    public function getJwtUserId(): ?int
    {
        return $this->jwt_user_id;
    }
}
