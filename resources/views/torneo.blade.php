@extends('includes.menu')

@section('content')
<!-- Colocamos ambos IDs comunes para asegurar que React enganche sin importar cuál use tu main.jsx -->
<div id="torneo-app">
    <div id="torneo-root">
        <div id="root"></div>
    </div>
</div>

@viteReactRefresh
@vite(['resources/js/torneo/main.jsx'])
@endsection