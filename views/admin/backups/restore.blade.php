@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-bold mb-4">Restore Backup</h1>

<form method="get" action="/admin/backups/restore" class="mb-6">
    <label class="block font-semibold mb-1">Select Backup:
        <select name="file" onchange="this.form.submit()" class="border rounded px-2 py-1 ml-2">
            <option value="">-- Choose backup --</option>
            <?php foreach ($backups as $file): ?>
                <option value="<?= basename($file) ?>" <?= ($selected == basename($file)) ? 'selected' : '' ?>><?= basename($file) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<?php if ($meta): ?>
    <h2 class="text-xl font-semibold mb-2">Backup Metadata</h2>
    <pre class="bg-gray-100 p-2 rounded border text-xs mb-4"><?= htmlspecialchars(json_encode($meta, JSON_PRETTY_PRINT)) ?></pre>
    <form method="post" action="/admin/backups/restore" onsubmit="return confirm('Are you sure you want to restore this backup? This will overwrite your current site data.');">
        <input type="hidden" name="file" value="<?= htmlspecialchars($selected) ?>">
        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Restore from this backup</button>
    </form>
<?php elseif ($selected): ?>
    <p class="text-red-600">Could not read metadata from backup.</p>
<?php endif; ?>
@endsection
