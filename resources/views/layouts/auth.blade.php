<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Visueco')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- CSS khusus per-halaman (ATURAN 1: isolasi kode) --}}
    @stack('styles')
</head>
<body class="bg-[#F8FAFC] font-sans text-[#0F172A] antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">
    @yield('content')

    {{-- JS khusus per-halaman (ATURAN 1: isolasi kode) --}}
    @stack('scripts')
</body>
</html>
