<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Torneos | Academia Karate-Do SMT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/torneo/main.jsx'])
</head>
<body>
    @include('includes.menu')

    <div class="main-content">
        <div id="torneo-app"></div>
    </div>
</body>
</html>
