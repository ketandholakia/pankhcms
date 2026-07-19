<?php

namespace App\Middleware;

use App\Models\ApiToken;
use App\Core\Auth;

class RequireApiToken
{
    public function before()
    {
        $authHeader = \Flight::request()->getHeader('Authorization') ?: ($_SERVER['HTTP_AUTHORIZATION'] ?? null);

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            \Flight::json(['error' => 'Unauthorized: Missing or invalid token'], 401);
            exit;
        }

        $token = $matches[1];
        $hashedToken = hash('sha256', $token);

        $apiToken = ApiToken::with('user')->where('token', $hashedToken)->first();

        if (!$apiToken || !$apiToken->user) {
            \Flight::json(['error' => 'Unauthorized: Invalid token'], 401);
            exit;
        }

        // Update last used at
        $apiToken->last_used_at = \Illuminate\Support\Carbon::now();
        $apiToken->save();

        Auth::setStatelessUser($apiToken->user);
    }
}
