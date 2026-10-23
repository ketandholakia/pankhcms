@extends('layouts.admin')

@section('content')
<div class="w-full p-8 bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="bg-white border rounded shadow p-10 text-center max-w-md">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">403</h1>
        <p class="text-gray-600 mb-6">{{ $message ?? 'Forbidden.' }}</p>
        <a href="/admin" class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Back to Dashboard</a>
    </div>
</div>
@endsection
