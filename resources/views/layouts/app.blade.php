<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Agents Knowledge Hub')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-5xl px-6 py-4">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight">Agents Knowledge Hub</a>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            @yield('content')
        </main>
    </div>
</body>
</html>
