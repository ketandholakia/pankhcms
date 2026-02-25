<?php
session_start();
if (!isset($_SESSION['auth']['id'])) {
    header("Location: /admin/login");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="refresh" content="3;url=/admin">
<title>Installation Complete</title>
<style>
body{font-family:Arial;text-align:center;margin-top:80px;background:#f4f6f9}
.box{max-width:500px;margin:60px auto;background:#fff;padding:40px 30px;border-radius:8px;box-shadow:0 2px 8px #0001}
h1{color:#28a745;font-size:2.2em;margin-bottom:10px}
p{color:#333;font-size:1.1em}
a{color:#007bff;text-decoration:none;font-weight:bold}
</style>
</head>
<body>
<div class="box">
<h1>🎉 Installation Successful</h1>
<p>You are now logged in as administrator.</p>
<p>Redirecting to dashboard...</p>
<a href="/admin">Go to Dashboard Now</a>
</div>
</body>
</html>
