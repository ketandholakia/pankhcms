@extends('layouts.admin')

@section('content')
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold mb-4">Contact Messages</h1>

        @if(!empty($messages) && $messages->count())
            <div class="overflow-x-auto bg-white rounded-lg border">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">#</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Name</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Email</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Subject</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Message</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">IP</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-700">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $message)
                            <tr class="border-t align-top">
                                <td class="px-4 py-3 text-gray-600">{{ $message->id }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $message->name }}</td>
                                <td class="px-4 py-3">
                                    <a href="mailto:{{ $message->email }}" class="text-blue-600 hover:underline">{{ $message->email }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $message->subject ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-700 max-w-md whitespace-pre-wrap break-words">{{ $message->message }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $message->ip ?: '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $message->created_at ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-white border rounded-lg p-6 text-gray-600">
                No messages to display.
            </div>
        @endif
    </div>
@endsection
