@extends('layouts.admin')

@section('content')
<h1 class="title">Contact Messages</h1>

<div class="card">
    <div class="card-content">
        <table class="table is-fullwidth is-striped is-hoverable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $message)
                <tr>
                    <td>{{ $message->created_at }}</td>
                    <td>{{ $message->name }}</td>
                    <td><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></td>
                    <td>{{ $message->subject }}</td>
                    <td title="{{ $message->message }}">{{ Illuminate\Support\Str::limit($message->message, 100) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="has-text-centered">No messages yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection