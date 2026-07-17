@extends('includes.menu')

@section('content')
<div id="torneo-app"></div>

@viteReactRefresh
@vite(['resources/js/torneo/main.jsx'])
@endsection