<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>ICMIS — Integrated Case Management Information System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Space+Grotesk:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet" />
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="h-full bg-chalk font-sans antialiased">
    <div id="app" class="h-full"></div>
</body>
</html>
