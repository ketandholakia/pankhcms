<?php

require __DIR__ . "/../../vendor/autoload.php";

use App\Core\Bootstrap;
use App\Models\User;

// Initialize the application to get the database connection
Bootstrap::init();

$user = User::where("email", "admin@example.com")->first();

if ($user) {
    $user->password = password_hash("password", PASSWORD_DEFAULT);
    $user->save();
    echo "✅ Password for admin@example.com has been reset to 'password'.\n";
} else {
    echo "⚠️ User admin@example.com not found. Please run seed.php first.\n";
}
