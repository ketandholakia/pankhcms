
<?php

$root = realpath(__DIR__ . '/../..');
$lockFile = __DIR__ . '/lock';

if (file_exists($lockFile) || file_exists($root . '/.env')) {
    die("Installer is locked.");
}

/* ======================================================
   REQUIREMENTS
====================================================== */

$requirements = [
    'php' => [
        'title' => 'PHP Version',
        'required' => '8.1.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '8.1.0', '>=')
    ],
    'extensions' => [
        'title' => 'Required PHP Extensions',
        'required' => [
            'pdo',
            'pdo_mysql',
            'pdo_sqlite',
            'mbstring',
            'json',
            'openssl',
            'fileinfo',
            'curl',
            'zip'
        ]
    ],
    'permissions' => [
        'title' => 'Writable Directories',
        'paths' => [
            $root,
            $root . '/database',
            $root . '/storage',
            $root . '/public'
        ]
    ],
    'composer' => [
        'title' => 'Composer Autoload',
        'path' => $root . '/vendor/autoload.php'
    ]
];

/* ======================================================
   CHECK EXTENSIONS
====================================================== */

$extensionsStatus = [];
foreach ($requirements['extensions']['required'] as $ext) {
    $extensionsStatus[$ext] = extension_loaded($ext);
}

/* ======================================================
   CHECK PERMISSIONS
====================================================== */

$permissionsStatus = [];
foreach ($requirements['permissions']['paths'] as $path) {
    if (!file_exists($path)) {
        @mkdir($path, 0777, true);
    }
    $permissionsStatus[$path] = is_writable($path);
}

/* ======================================================
   COMPOSER CHECK
====================================================== */

$composerOk = file_exists($requirements['composer']['path']);

/* ======================================================
   FINAL STATUS
====================================================== */

$allOk = true;
if (!$requirements['php']['status']) $allOk = false;
foreach ($extensionsStatus as $ok)
    if (!$ok) $allOk = false;
foreach ($permissionsStatus as $ok)
    if (!$ok) $allOk = false;
if (!$composerOk) $allOk = false;

?>
<!DOCTYPE html>
<html>
<head>
<title>PankhCMS — System Check</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:0}
.box{max-width:800px;margin:40px auto;background:#fff;padding:30px;border-radius:8px}
h1{text-align:center;color:#007bff;margin-bottom:5px}
.subtitle{text-align:center;color:#666;margin-bottom:25px}
table{width:100%;border-collapse:collapse}
td,th{padding:10px;border-bottom:1px solid #eee}
.pass{color:#28a745;font-weight:bold}
.fail{color:#dc3545;font-weight:bold}
.button{display:inline-block;padding:12px 18px;background:#007bff;color:#fff;text-decoration:none;border-radius:5px;margin-top:20px}
.button.disabled{background:#aaa;pointer-events:none}
.path{font-size:12px;color:#666}
</style>
</head>
<body>

<div class="box">

<h1>PankhCMS Installer</h1>
<div class="subtitle">Pre-Installation System Check</div>

<table>

<tr><th colspan="3">PHP</th></tr>

<tr>
<td>PHP Version</td>
<td><?php echo PHP_VERSION; ?> (Required: <?php echo $requirements['php']['required']; ?>)</td>
<td class="<?php echo $requirements['php']['status'] ? 'pass':'fail'; ?>">
<?php echo $requirements['php']['status'] ? 'PASS':'FAIL'; ?>
</td>
</tr>

<tr><th colspan="3">Extensions</th></tr>

<?php foreach ($extensionsStatus as $ext => $ok): ?>
<tr>
<td><?php echo $ext; ?></td>
<td>Required</td>
<td class="<?php echo $ok ? 'pass':'fail'; ?>">
<?php echo $ok ? 'PASS':'FAIL'; ?>
</td>
</tr>
<?php endforeach; ?>

<tr><th colspan="3">Permissions</th></tr>

<?php foreach ($permissionsStatus as $path => $ok): ?>
<tr>
<td>Writable</td>
<td class="path"><?php echo $path; ?></td>
<td class="<?php echo $ok ? 'pass':'fail'; ?>">
<?php echo $ok ? 'PASS':'FAIL'; ?>
</td>
</tr>
<?php endforeach; ?>

<tr><th colspan="3">Composer</th></tr>

<tr>
<td>vendor/autoload.php</td>
<td>Required</td>
<td class="<?php echo $composerOk ? 'pass':'fail'; ?>">
<?php echo $composerOk ? 'PASS':'FAIL'; ?>
</td>
</tr>

</table>

<div style="text-align:center">
<?php if ($allOk): ?>
<a class="button" href="setup.php">Continue Installation →</a>
<?php else: ?>
<div class="button disabled">Fix issues to continue</div>
<?php endif; ?>
</div>

</div>

</body>
</html>
