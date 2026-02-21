<?php

require __DIR__ . "/../../vendor/autoload.php";

use App\Core\Bootstrap;

use App\Models\User;

Bootstrap::init();

$adminEmail = $_ENV["ADMIN_EMAIL"] ?? "admin@site.com";
$adminPassword = $_ENV["ADMIN_PASSWORD"] ?? "";

if ($adminPassword === '') {
    echo "❌ ADMIN_PASSWORD is required in your environment.\n";
    exit(1);
}

$policyErrors = password_policy_errors($adminPassword);
if (!empty($policyErrors)) {
    echo "❌ ADMIN_PASSWORD does not meet the password policy:\n";
    foreach ($policyErrors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

// Check if admin already exists
if (User::where("email", $adminEmail)->exists()) {
    echo "⚠️ Admin already exists\n";
    exit();
}

// Create admin
$user = User::create([
    "email" => $adminEmail,
    "password" => password_hash($adminPassword, PASSWORD_DEFAULT),
]);

echo "✅ Admin created! ID: {$user->id} ({$adminEmail})\n";
