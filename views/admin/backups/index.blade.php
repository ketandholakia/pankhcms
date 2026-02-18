@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-bold mb-4">Backups</h1>

<form method="post" action="/admin/backups/create" class="mb-6 flex gap-4 items-end">
    <div>
        <label class="block font-semibold mb-1">Backup Type</label>
        <select name="type" class="border rounded px-2 py-1">
            <option value="full">Full Backup</option>
            <option value="database">Database Only</option>
        </select>
    </div>
    <div>
        <label class="block font-semibold mb-1">Notes</label>
        <input type="text" name="notes" placeholder="Optional notes" class="border rounded px-2 py-1">
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Create Backup</button>
</form>

<h2 class="text-xl font-semibold mb-2">Existing Backups</h2>
<table class="min-w-full bg-white border rounded">
    <tr><th class="p-2 border-b">File</th><th class="p-2 border-b">Actions</th></tr>
    <?php foreach ($backups as $file): ?>
        <tr>
            <td class="p-2 border-b"><?= basename($file) ?></td>
            <td class="p-2 border-b">
                <a href="/admin/backups/download/<?= urlencode(basename($file)) ?>" class="text-blue-600 hover:underline">Download</a>
                <form method="post" action="/admin/backups/delete/<?= urlencode(basename($file)) ?>" style="display:inline;">
                    <button type="submit" onclick="return confirm('Delete this backup?')" class="text-red-600 ml-2">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
@endsection
