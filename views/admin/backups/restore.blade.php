@extends('layouts.admin')

@section('content')
<div class="w-full p-8 bg-gray-50 min-h-screen">
    <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Restore Backup</h1>

        @if(isset($_GET['error']) && $_GET['error'] === 'confirm')
            <div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-3 text-sm mb-4">
                You must tick the confirmation box to restore.
            </div>
        @elseif(isset($_GET['error']) && $_GET['error'] === 'restore_failed')
            <div class="rounded border border-red-300 bg-red-50 text-red-800 px-4 py-3 text-sm mb-4">
                The restore failed. Check the application logs for details.
            </div>
        @endif

        <div class="rounded border border-yellow-300 bg-yellow-50 text-yellow-800 px-4 py-3 text-sm mb-4">
            <p class="font-bold mb-1">This replaces the current database with the contents of
                <code>{{ $filename }}</code>.</p>
            <p>All pages, users, settings and messages created after this backup will be lost.
                Uploaded files and themes are not touched. Maintenance mode is enabled while
                the restore runs.</p>
        </div>

        <form method="POST" action="/admin/backups/restore">
            {!! csrf_field() !!}
            <input type="hidden" name="file" value="{{ $filename }}">

            <label class="flex items-start gap-2 mb-4 text-sm text-gray-700">
                <input type="checkbox" name="confirm" value="yes" class="mt-1">
                <span>I understand this will overwrite the current database and want to continue.</span>
            </label>

            <div class="flex gap-2">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded">
                    Restore Database
                </button>
                <a href="/admin/backups" class="text-sm text-gray-600 hover:text-gray-900 px-4 py-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
