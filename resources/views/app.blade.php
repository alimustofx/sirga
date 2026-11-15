<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#142B45">
        <meta name="format-detection" content="telephone=no">

        @php
            // Favicon memakai logo sekolah (Pengaturan › Identitas Sekolah); bila belum ada → ikon bawaan.
            try { $logoPath = \App\Models\SchoolSetting::current()->school_logo; } catch (\Throwable) { $logoPath = null; }
            $logoV = $logoPath ? substr(md5($logoPath), 0, 8) : null;
        @endphp
        @if ($logoV)
            <link rel="icon" type="image/png" sizes="64x64" href="/sekolah/favicon?v={{ $logoV }}">
            <link rel="apple-touch-icon" href="/sekolah/favicon?s=180&amp;v={{ $logoV }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'SIRGA ASN') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
