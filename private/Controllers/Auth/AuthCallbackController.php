<?php

namespace Teslapp\Controllers\Auth;

/**
 * Authentification Controller
 *
 * This controller handles authentication through Tesla
 *
 */
final class AuthCallbackController
{
    public function callback(): void {
        parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $p);
        $code = $p['code'];
        // Extract code query param from request URI
        echo $code;
    }
}
