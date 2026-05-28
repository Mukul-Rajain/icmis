<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DCFM Court System')</title>

    {{-- Distinctive serif/sans pairing — judicial gravitas without being stuffy --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Use Vite-built assets in production, CDN fallback for quick setup --}}
    @if(app()->environment('production') || file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    @livewireStyles

    <style>
        :root {
            --color-ink: #1a1a1f;
            --color-ink-soft: #4a4a52;
            --color-paper: #faf8f3;
            --color-paper-edge: #f0ece1;
            --color-accent: #8b1a1a;       /* judicial maroon */
            --color-accent-soft: #c84545;
            --color-rule: #d4cfc0;
            --color-success: #2d6a4f;
            --color-warn: #b07d2c;
            --color-danger: #9d2424;
        }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--color-paper);
            color: var(--color-ink);
            font-feature-settings: "ss01", "cv11";
        }

        .font-display {
            font-family: 'Fraunces', Georgia, serif;
            font-optical-sizing: auto;
            letter-spacing: -0.02em;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Decorative rule for headers — court paper aesthetic */
        .double-rule {
            border-top: 3px double var(--color-rule);
        }

        /* Track pills */
        .track-fast { background: #fef3f2; color: #9d2424; border: 1px solid #fecaca; }
        .track-standard { background: #fef9e7; color: #92580d; border: 1px solid #fde68a; }
        .track-complex { background: #ecf3f8; color: #1e4d6b; border: 1px solid #b6dbf4; }
    </style>
</head>
<body class="min-h-screen">

    {{-- Header — emphasises the judicial nature without being overdone --}}
    <header class="bg-white border-b-2 border-stone-800">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-stone-900 flex items-center justify-center">
                    <span class="font-display text-amber-100 text-lg font-semibold">⚖</span>
                </div>
                <div>
                    <h1 class="font-display text-xl font-semibold tracking-tight text-stone-900">DCFM Court System</h1>
                    <p class="text-xs text-stone-500 tracking-wide uppercase">Differentiated Case Flow Management</p>
                </div>
            </div>

            @auth
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('dashboard') }}" class="text-stone-600 hover:text-stone-900">Dashboard</a>
                <a href="{{ route('cases.index') }}" class="text-stone-600 hover:text-stone-900">Cases</a>
                <a href="{{ route('cause-lists.index') }}" class="text-stone-600 hover:text-stone-900">Cause Lists</a>
                <a href="{{ route('analytics') }}" class="text-stone-600 hover:text-stone-900">Analytics</a>
                <div class="border-l border-stone-300 pl-6 flex items-center gap-3">
                    <span class="text-xs text-stone-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="text-xs text-stone-500 hover:text-red-700">Logout</button>
                    </form>
                </div>
            </nav>
            @endauth
        </div>
    </header>

    @if(session('success'))
        <div class="max-w-7xl mx-auto px-6 mt-4">
            <div class="bg-emerald-50 border-l-4 border-emerald-700 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto px-6 py-8">
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
