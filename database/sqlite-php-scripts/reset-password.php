<?php

require __DIR__ . "/../../vendor/autoload.php";

use App\Core\Bootstrap;
use App\Models\User;

// Initialize the application to get the database connection
Bootstrap::init();

$newPassword = $_ENV["ADMIN_PASSWORD"] ?? '';
if ($newPassword === '') {
    echo "❌ ADMIN_PASSWORD is required in your environment.\n";
    exit(1);
}

$policyErrors = password_policy_errors($newPassword);
if (!empty($policyErrors)) {
    echo "❌ ADMIN_PASSWORD does not meet the password policy:\n";
    foreach ($policyErrors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

$user = User::where("email", "admin@example.com")->first();

if ($user) {
    $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
    $user->save();
    echo "✅ Password for admin@example.com has been reset.\n";
} else {
    echo "⚠️ User admin@example.com not found. Please run seed.php first.\n";
}
