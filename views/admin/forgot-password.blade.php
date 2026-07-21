<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        @php $status = $_GET['status'] ?? null; @endphp
        <form method="POST" action="/admin/password/forgot" class="bg-white shadow-md rounded-xl px-8 pt-6 pb-8 mb-4">
            {!! csrf_field() !!}
            <img src="/assets/pankhcms_logo.png" alt="PankhCMS Admin" class="mx-auto mb-6" style="max-height:120px;">

            <h1 class="mb-2 text-center text-2xl font-bold text-gray-900">Forgot Password</h1>
            <p class="mb-6 text-center text-sm text-gray-600">Enter your email or username and we will send a reset link if the account exists.</p>

            @if($status === 'sent')
                <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    If the account exists, a password reset link has been sent.
                </div>
            @endif

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="identifier">
                    Email or Username
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="identifier" name="identifier" type="text" placeholder="Email or username" autocomplete="username" required>
            </div>

            <div class="flex items-center justify-between">
                <a href="/admin/login" class="text-sm text-gray-600 hover:underline">Back to login</a>
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Send Reset Link
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
