<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – DCFM Court System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #faf8f3; font-family: system-ui, sans-serif; }
        .font-display { font-family: Georgia, serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">

        {{-- Logo / Header --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-stone-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-amber-200 text-2xl">⚖</span>
            </div>
            <h1 class="font-display text-3xl font-semibold text-stone-900">DCFM Court System</h1>
            <p class="text-sm text-stone-500 uppercase tracking-widest mt-1">Differentiated Case Flow Management</p>
        </div>

        <div class="bg-white border border-stone-200 p-8 shadow-sm">
            <div class="border-t-2 border-double border-stone-300 pt-6 mb-6">
                <h2 class="font-display text-xl font-semibold">Sign in to your account</h2>
            </div>

            @if (session('status'))
                <div class="bg-green-50 border-l-4 border-green-600 text-green-800 text-sm p-3 mb-4">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500 @error('email') border-red-400 @enderror">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500 @error('password') border-red-400 @enderror">
                    @error('password')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 text-sm text-stone-600">
                        <input type="checkbox" name="remember" class="rounded">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="text-xs text-stone-500 underline">Forgot password?</a>
                </div>

                <button type="submit"
                    class="w-full bg-stone-900 text-white py-2.5 text-sm font-medium hover:bg-stone-800 transition">
                    Sign In
                </button>
            </form>
        </div>

        {{-- Demo credentials box --}}
        <div class="mt-4 bg-amber-50 border border-amber-200 p-4 text-xs">
            <p class="font-semibold text-amber-900 mb-2">Demo Credentials</p>
            <table class="w-full text-amber-800">
                <tr><td class="py-0.5 font-medium">Admin</td><td>admin@dcfm.test</td><td>password</td></tr>
                <tr><td class="py-0.5 font-medium">Judge</td><td>judge1@dcfm.test</td><td>password</td></tr>
                <tr><td class="py-0.5 font-medium">Lawyer</td><td>lawyer@dcfm.test</td><td>password</td></tr>
                <tr><td class="py-0.5 font-medium">Staff</td><td>staff@dcfm.test</td><td>password</td></tr>
            </table>
        </div>

        <p class="text-center text-xs text-stone-400 mt-4">
            New user? <a href="{{ route('register') }}" class="underline text-stone-600">Create account</a>
        </p>
    </div>
</body>
</html>
