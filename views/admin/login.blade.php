<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        @php $status = $_GET['status'] ?? null; @endphp
        <form method="POST" action="/admin/login" class="bg-white shadow-md rounded-xl px-8 pt-6 pb-8 mb-4">
            {!! csrf_field() !!}
            <img src="/assets/pankhcms_logo.png" alt="PankhCMS Admin" class="mx-auto mb-6" style="max-height:120px;">

            @if($status === 'password-reset')
                <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    Your password has been reset. Sign in with the new password.
                </div>
            @endif
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="identifier">
                    Email or Username
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="identifier" name="identifier" type="text" placeholder="Email or username" autocomplete="username">
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" type="password" placeholder="******************">
                <div class="text-right">
                    <a href="/admin/password/forgot" class="text-sm text-blue-600 hover:underline">Forgot password?</a>
                </div>
            </div>

            <div class="flex justify-center">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
