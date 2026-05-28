<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – DCFM Court System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background: #faf8f3; font-family: system-ui, sans-serif; }</style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-stone-900 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-amber-200 text-2xl">⚖</span>
            </div>
            <h1 class="text-3xl font-semibold text-stone-900" style="font-family:Georgia,serif;">DCFM Court System</h1>
            <p class="text-sm text-stone-500 uppercase tracking-widest mt-1">Differentiated Case Flow Management</p>
        </div>

        <div class="bg-white border border-stone-200 p-8 shadow-sm">
            <div class="border-t-2 border-double border-stone-300 pt-6 mb-6">
                <h2 class="text-xl font-semibold" style="font-family:Georgia,serif;">Create Account</h2>
                <p class="text-sm text-stone-500 mt-1">You will be registered as a litigant by default.</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500 @error('name') border-red-400 @enderror">
                    @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500 @error('email') border-red-400 @enderror">
                    @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">Password</label>
                    <input type="password" name="password" required
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500 @error('password') border-red-400 @enderror">
                    @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-stone-600 mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" required
                        class="w-full border border-stone-300 px-3 py-2 text-sm focus:outline-none focus:border-stone-500">
                </div>

                <button type="submit"
                    class="w-full bg-stone-900 text-white py-2.5 text-sm font-medium hover:bg-stone-800 transition">
                    Create Account
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-stone-400 mt-4">
            Already have an account? <a href="{{ route('login') }}" class="underline text-stone-600">Sign in</a>
        </p>
    </div>
</body>
</html>
