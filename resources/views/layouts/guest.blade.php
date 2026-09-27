<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ورود') | {{ config('app.name', 'مدیریت فروشگاه موبایل') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <main class="relative grid min-h-screen place-items-center overflow-hidden px-4 py-10">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(13,148,136,0.28),_transparent_42%),radial-gradient(circle_at_bottom_left,_rgba(14,116,144,0.2),_transparent_38%)]" aria-hidden="true"></div>
        <div class="relative w-full max-w-md">
            @yield('content')
        </div>
    </main>
</body>
</html>
