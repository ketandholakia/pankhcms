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

    public static function sendResetLink()
    {
        $identifier = isset($_POST["identifier"]) ? trim((string) $_POST["identifier"]) : '';

        if ($identifier !== '') {
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

        Capsule::table('password_resets')->where('user_id', $user->id)->delete();

        \Flight::redirect('/admin/login?status=password-reset');
    }

    public static function login()
    {
        $identifier = isset($_POST["identifier"]) ? trim((string) $_POST["identifier"]) : '';
        $password = isset($_POST["password"]) ? (string) $_POST["password"] : '';

        $ip = client_ip();
        $key = login_throttle_key($identifier ?: 'unknown', $ip);
        $check = login_throttle_check($key);
        if (!$check['allowed']) {
            \Flight::response()->status(429);
            $retry = (int) $check['retry_after'];
            echo 'Too many login attempts. Try again in ' . $retry . ' seconds.';
            return;
        }

        if (Auth::attempt($identifier, $password)) {
            login_throttle_clear($key);
            \Flight::redirect("/admin");
            return;
        }

        $failure = login_throttle_register_failure($key);
        if (!empty($failure['locked']) && !empty($failure['retry_after'])) {
            \Flight::response()->status(429);
            echo 'Too many login attempts. Try again in ' . (int) $failure['retry_after'] . ' seconds.';
            return;
        }

        \Flight::response()->status(401);
        echo "Invalid login";
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
        self::ensurePasswordResetTable();

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
        self::ensurePasswordResetTable();

        $hashedToken = hash('sha256', $plainToken);

        return Capsule::table('password_resets')
            ->join('users', 'users.id', '=', 'password_resets.user_id')
            ->where('users.email', $email)
            ->where('password_resets.token', $hashedToken)
            ->where('password_resets.expires_at', '>', date('Y-m-d H:i:s'))
            ->select('password_resets.*')
            ->first();
    }

    protected static function ensurePasswordResetTable(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('password_resets')) {
            return;
        }

        $schema->create('password_resets', function ($t) {
            $t->increments('id');
            $t->integer('user_id')->unsigned();
            $t->string('token', 64)->unique();
            $t->timestamp('expires_at');
            $t->timestamps();

            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    protected static function sendPasswordResetEmail(User $user, string $plainToken): void
    {
        $recipient = trim((string) $user->email);
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $baseUrl = rtrim((string) env('APP_URL', ''), '/');
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . '://' . $host;
        }

        $link = $baseUrl . '/admin/password/reset?token=' . urlencode($plainToken) . '&email=' . urlencode($recipient);
        $siteName = (string) setting('site_name', 'PankhCMS');
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
