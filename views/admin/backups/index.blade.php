@extends('layouts.admin')

@section('content')
<div class="w-full p-8 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Backups</h1>
            <p class="text-sm text-gray-500">Manage and download your backups</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full mb-8">
        <form method="post" action="/admin/backups/create" class="flex flex-col md:flex-row gap-4 items-end p-6">
            <div>
                <label class="block font-semibold mb-1">Backup Type</label>
                <select name="type" class="border rounded px-3 py-2">
                    <option value="full">Full Backup</option>
                    <option value="database">Database Only</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold mb-1">Notes</label>
                <input type="text" name="notes" placeholder="Optional notes" class="border rounded px-3 py-2">
            </div>
            <button type="submit" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition-all shadow-sm">
                <i data-lucide="plus" class="h-5 w-5"></i>
                Create Backup
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden w-full">
        <h2 class="text-xl font-semibold px-6 pt-6 mb-2">Existing Backups</h2>
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider">File</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($backups as $file): ?>
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 text-sm text-gray-800"><?= basename($file) ?></td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="/admin/backups/download/<?= urlencode(basename($file)) ?>" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 font-medium text-sm">
                            <i data-lucide="download" class="h-4 w-4 mr-1"></i>
                            Download
                        </a>
                        <span class="text-gray-300">|</span>
                        <form method="post" action="/admin/backups/delete/<?= urlencode(basename($file)) ?>" class="inline" style="display:inline;">
                            <button type="submit" onclick="return confirm('Delete this backup?')" class="inline-flex items-center text-red-600 hover:text-red-900 font-medium text-sm">
                                <i data-lucide="trash-2" class="h-4 w-4 mr-1"></i>
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
@endsection
