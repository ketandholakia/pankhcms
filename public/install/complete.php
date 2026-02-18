<?php
if (!file_exists(__DIR__ . '/lock')) {
    header('Location: /install');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Installation Complete</title></head>
<body style="font-family:Arial;text-align:center;margin-top:80px">
<h1>✅ Installation Complete</h1>
<p>Your CMS is ready.</p>
<p><a href="/">Go to Homepage</a></p>
<p><a href="/admin/login">Admin Login</a></p>
</body></html>
