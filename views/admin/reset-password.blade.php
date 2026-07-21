<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        @php $status = $_GET['status'] ?? null; @endphp
        <form method="POST" action="/admin/password/reset" class="bg-white shadow-md rounded-xl px-8 pt-6 pb-8 mb-4">
            {!! csrf_field() !!}
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <img src="/assets/pankhcms_logo.png" alt="PankhCMS Admin" class="mx-auto mb-6" style="max-height:120px;">

            <h1 class="mb-2 text-center text-2xl font-bold text-gray-900">Reset Password</h1>
            <p class="mb-6 text-center text-sm text-gray-600">Set a new password for {{ $email ?: 'your account' }}.</p>

            @if(!$isValid || $status === 'invalid')
                <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    This reset link is invalid or has expired.
                </div>
            @elseif($status === 'mismatch')
                <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    Password and confirmation do not match.
                </div>
            @elseif($status === 'weak')
                <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    Password must be at least 10 characters and include uppercase, lowercase, number, and symbol.
                </div>
            @endif

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    New Password
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" type="password" placeholder="New password" required {{ $isValid ? '' : 'disabled' }}>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password_confirmation">
                    Confirm Password
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password_confirmation" name="password_confirmation" type="password" placeholder="Confirm password" required {{ $isValid ? '' : 'disabled' }}>
                <p class="mt-2 text-xs text-gray-500">Minimum 10 characters with uppercase, lowercase, number, and symbol.</p>
            </div>

            <div class="flex items-center justify-between">
                <a href="/admin/login" class="text-sm text-gray-600 hover:underline">Back to login</a>
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline disabled:cursor-not-allowed disabled:bg-gray-300" type="submit" {{ $isValid ? '' : 'disabled' }}>
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
