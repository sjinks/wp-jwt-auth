# wp-jwt-auth

WordPress plugin for JWT authentication for the REST / XML-RPC API

## Installation

### Composer Way

```bash
composer require wildwolf/wp-jwt-auth
```

Then go to Admin Dashboard, Plugins, find and activate the "WW JWT Auth" plugin

### Manual Way

Grab the plugin zip file from Releases, then go to Admin Dashboard, Plugins, Add New, Upload Plugin. Select the zip file, the click Install Now. Finally, activate the plugin.

## REST API Authentication

The plugin uses *bearer authentication* (also known as *token authentication*) to authenticate the user.

To authenticate the request, you need to have a JWT. Then you need to set the `Authorization` header:

```
Authorization: Bearer jwt-goes-here
```

## API

### REST API

#### Generate Token

`POST /wp-json/wildwolf/jwtauth/v1/generate`

or

`POST /wp-json/jwt-auth/v1/token`

Request body:
* `username`: login;
* `password`: password.

Successful Response:
Status code: 200
JSON Object:
* `token`: JWT token;
* `user_email`: email address of the user;
* `display_name`: display name of the user.

Failure:
Status code: 403 for authentication failures, 500 for misconfiguration (JWT secret is not set)

#### Validate Token

`GET /wp-json/wildwolf/jwtauth/v1/verify`

or

`GET /wp-json/jwt-auth/v1/token/validate`

Required headers:
* `Authorization: Bearer JWT-goes-here`

Successfule response:
Status code: 200

Failure:
Status code: 403

### WordPress Filters

#### jwt_auth_not_before

Used to filter the `nbf` (not before) claim for the token.
Parameters:
* `int $value`: filtered value; set to the value of the `iat` (issued at) claim;
* `int $iat`: the original unfiltered `iat` value.
Expects: integer, the new value for the `nbf` claim.

### jwt_auth_not_after

Used to filter the `exp` (expiration) claim for the token.
Parameters:
* `int $value`: filtered value; set to the value of the `iat` (issued at) claim plus the value of the JWT TTL setting;
* `int $iat`: the `iat` value;
* `int $ttl`: the JWT time to live value.
Expects: integer, the new value for the `exp` claim.

### jwt_auth_token_before_sign

Used to filter the JWT payload before signing.
Parameters:
* `array $token`: JWT payload. By default, it contains the following fields:
  * `iss`: token issuer; equal to the URL of the site (`get_bloginfo( 'url' )`);
  * `iat`: time the token was issued at;
  * `nbf`: time before which the JWT must not be accepted for processing;
  * `exp`: expiration time on or after which the JWT must not be accepted for processing;
  * `sub`: the ID of the user
* `WP_User $user`: the authenticated user.
Expects: array, the new payload to sign.
