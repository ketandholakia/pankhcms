<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;

class AuthController
{
    public static function showLogin()
    {
        echo \Flight::get("blade")->render("admin.login");
    }

    public static function showForgotPassword()
    {
        echo \Flight::get("blade")->render("admin.forgot-password");
    }

    /**
     * Reset-link throttle. Deliberately more permissive than login (10
     * requests per window) and keyed on identifier + IP: it exists to
     * stop mass mail-out, not to leak whether an account exists — the
     * endpoint always answers with status=sent either way.
     */
    public static function resetLinkThrottled(string $identifier): bool
    {
        $key = login_throttle_key('password-reset', client_ip() . '|' . strtolower($identifier));
        if (!login_throttle_check($key)['allowed']) {
            return true;
        }

        // Count this request; the lock it may trigger applies to it too,
        // so exactly 10 requests per window are answered.
        $result = login_throttle_register_failure($key, 10);
        return !empty($result['locked']);
    }

    public static function sendResetLink()
    {
        $identifier = isset($_POST["identifier"]) ? trim((string) $_POST["identifier"]) : '';

        // Always answer identically; when throttled, just skip the work.
        if ($identifier !== '' && !self::resetLinkThrottled($identifier)) {
            $user = self::findUserByIdentifier($identifier);
            if ($user && filter_var((string) $user->email, FILTER_VALIDATE_EMAIL)) {
                $plainToken = self::issuePasswordResetToken((int) $user->id);
                self::sendPasswordResetEmail($user, $plainToken);
            }
        }

        \Flight::redirect('/admin/password/forgot?status=sent');
    }

    public static function showResetPassword()
    {
        $email = trim((string) (\Flight::request()->query->email ?? ''));
        $token = trim((string) (\Flight::request()->query->token ?? ''));

        $isValid = $email !== '' && $token !== '' && self::findValidPasswordReset($email, $token) !== null;

        echo \Flight::get("blade")->render("admin.reset-password", [
            'email' => $email,
            'token' => $token,
            'isValid' => $isValid,
        ]);
    }

    public static function resetPassword()
    {
        $email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
        $token = isset($_POST['token']) ? trim((string) $_POST['token']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $passwordConfirmation = isset($_POST['password_confirmation']) ? (string) $_POST['password_confirmation'] : '';

        if ($email === '' || $token === '') {
            \Flight::redirect('/admin/password/reset?status=invalid');
            return;
        }

        $reset = self::findValidPasswordReset($email, $token);
        if (!$reset) {
            \Flight::redirect('/admin/password/reset?status=invalid');
            return;
        }

        if ($password !== $passwordConfirmation) {
            \Flight::redirect('/admin/password/reset?token=' . urlencode($token) . '&email=' . urlencode($email) . '&status=mismatch');
            return;
        }

        $passwordErrors = password_policy_errors($password);
        if (!empty($passwordErrors)) {
            \Flight::redirect('/admin/password/reset?token=' . urlencode($token) . '&email=' . urlencode($email) . '&status=weak');
            return;
        }

        $user = User::find($reset->user_id);
        if (!$user) {
            \Flight::redirect('/admin/password/reset?status=invalid');
            return;
        }

        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        // A password reset proves the account owner lost control of the
        // credential: every API token issued under it is revoked.
        $user->apiTokens()->delete();

        Capsule::table('password_resets')->where('user_id', $user->id)->delete();

        \Flight::redirect('/admin/login?status=password-reset');
    }

    public static function login()
    {
        $identifier = isset($_POST["identifier"]) ? trim((string) $_POST["identifier"]) : '';
        $password = isset($_POST["password"]) ? (string) $_POST["password"] : '';

        $ip = client_ip();
        $account = $identifier !== '' ? $identifier : 'unknown';

        // Two independent throttles: one per (identifier, IP), one per
        // account alone with a higher threshold — so rotating IPs cannot
        // bypass the account-level lockout.
        $ipKey = login_throttle_key($account, $ip);
        $accountKey = login_throttle_key_account($account);

        $ipCheck = login_throttle_check($ipKey);
        $accountCheck = login_throttle_check($accountKey);
        if (!$ipCheck['allowed'] || !$accountCheck['allowed']) {
            $retry = (int) max($ipCheck['retry_after'], $accountCheck['retry_after']);
            \Flight::response()->status(429);
            echo 'Too many login attempts. Try again in ' . $retry . ' seconds.';
            return;
        }

        if (Auth::attempt($identifier, $password)) {
            login_throttle_clear($ipKey);
            login_throttle_clear($accountKey);
            \Flight::redirect("/admin");
            return;
        }

        // For unknown accounts, burn the same bcrypt cost as a real check
        // so response timing does not reveal whether the identifier exists.
        self::dummyPasswordVerify($password);

        $ipFailure = login_throttle_register_failure($ipKey);
        // Account-level lockout: 20 failures in the window before the
        // exponential backoff kicks in.
        $accountFailure = login_throttle_register_failure($accountKey, 20);

        $retryAfter = (int) max($ipFailure['retry_after'], $accountFailure['retry_after']);
        if (!empty($ipFailure['locked']) || !empty($accountFailure['locked'])) {
            \Flight::response()->status(429);
            echo 'Too many login attempts. Try again in ' . $retryAfter . ' seconds.';
            return;
        }

        \Flight::response()->status(401);
        echo "Invalid login";
    }

    /**
     * A bcrypt hash of an unrelated random string, verified once per
     * failed login against an unknown identifier so the CPU cost (and
     * therefore response time) matches a real credential check.
     */
    private static function dummyPasswordVerify(string $password): void
    {
        static $dummyHash = null;
        if ($dummyHash === null) {
            $dummyHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        }
        password_verify($password, $dummyHash);
    }

    public static function logout()
    {
        Auth::logout();
        \Flight::redirect("/admin/login");
        exit;
    }

    protected static function findUserByIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        return User::where('email', $identifier)
            ->orWhere('username', strtolower($identifier))
            ->first();
    }

    protected static function issuePasswordResetToken(int $userId): string
    {
        $plainToken = bin2hex(random_bytes(32));

        Capsule::table('password_resets')->where('user_id', $userId)->delete();
        Capsule::table('password_resets')->insert([
            'user_id' => $userId,
            'token' => hash('sha256', $plainToken),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $plainToken;
    }

    protected static function findValidPasswordReset(string $email, string $plainToken): ?object
    {
        $hashedToken = hash('sha256', $plainToken);

        return Capsule::table('password_resets')
            ->join('users', 'users.id', '=', 'password_resets.user_id')
            ->where('users.email', $email)
            ->where('password_resets.token', $hashedToken)
            ->where('password_resets.expires_at', '>', date('Y-m-d H:i:s'))
            ->select('password_resets.*')
            ->first();
    }

    protected static function sendPasswordResetEmail(User $user, string $plainToken): void
    {
        $recipient = trim((string) $user->email);
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Reset links must use the configured APP_URL only — the Host
        // header is client-controlled and could produce poisoned links.
        $baseUrl = rtrim((string) env('APP_URL', ''), '/');
        if ($baseUrl === '') {
            error_log('Cannot send password reset email: APP_URL is not configured.');
            return;
        }

        $link = $baseUrl . '/admin/password/reset?token=' . urlencode($plainToken) . '&email=' . urlencode($recipient);
        $siteName = str_replace(["\r", "\n"], ' ', (string) setting('site_name', 'PankhCMS'));
        $subject = '[' . $siteName . '] Password Reset';
        $body = implode("\n", [
            'A password reset was requested for your account.',
            '',
            'Username: ' . ($user->username ?: '-'),
            'Email: ' . $recipient,
            '',
            'Open this link to set a new password:',
            $link,
            '',
            'This link expires in 60 minutes.',
            'If you did not request this, you can ignore this email.',
        ]);

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $siteName . ' <no-reply@localhost>',
            'X-Mailer: PHP/' . phpversion(),
        ];

        @mail($recipient, $subject, $body, implode("\r\n", $headers));
    }
}
