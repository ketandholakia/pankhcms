<?php

use Illuminate\Database\Capsule\Manager as Capsule;

// Bring existing installs up to the canonical role & permission set
// (Administrator / Editor / Author / Viewer with sensible permissions,
// including manage_backups and manage_messages).
require_once __DIR__ . '/../seeds/roles.php';
seed_default_roles_and_permissions();
