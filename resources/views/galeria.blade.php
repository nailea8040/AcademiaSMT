<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Galería Multimedia - Dojo Karate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <style>
        :root { --kr: #e85654; --kd: #2d2d2d; }
        body { background: #f4f6f9; min-height: 100vh; }

        .section-heading {
            display: flex; align-items: center; gap: 12px;
            margin: 2rem 0 1.25rem;
        }
        .section-heading h2 { font-size: 1.35rem; font-weight: 800; color: var(--kd); margin: 0; }
        .section-heading .line { flex: 1; height: 2px; background: #e9ecef; border-radius: 2px; }
        .section-heading .count-pill {
            background: var(--kr); color: white;
            font-size: 0.78rem; font-weight: 700;
            padding: 3px 10px; border-radius: 20px;
        }

        .evento-card {
            background: white; border-radius: 20px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            transition: transform 0.3s, box-shadow 0.3s; cursor: pointer;
        }
        .evento-card:hover { transform: translateY(-6px); box-shadow: 0 12px 36px rgba(0,0,0,0.13); }
        .evento-mosaic {
            display: grid; grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 90px); gap: 3px; background: #e9ecef;
        }
        .evento-mosaic .tile { overflow: hidden; position: relative; background: #dee2e6; }
        .evento-mosaic .tile img, .evento-mosaic .tile video {
            width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.4s;
        }
        .evento-card:hover .tile img, .evento-card:hover .tile video { transform: scale(1.06); }
        .evento-mosaic .tile .video-play {
            position: absolute; inset: 0; display: flex; align-items: center;
            justify-content: center; background: rgba(0,0,0,0.3);
        }
        .evento-mosaic .tile .video-play i { color: white; font-size: 1.4rem; }
        .evento-mosaic .tile-more {
            display: flex; align-items: center; justify-content: center;
            background: rgba(0,0,0,0.55); color: white; font-weight: 800; font-size: 1.15rem;
        }
        .evento-info { padding: 14px 16px 12px; }
        .evento-info h3 {
            font-size: 0.95rem; font-weight: 800; color: var(--kd);
            margin: 0 0 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .evento-stats { display: flex; gap: 12px; font-size: 0.78rem; color: #888; }
        .evento-stats span { display: flex; align-items: center; gap: 4px; }
        .evento-stats i { font-size: 0.85rem; }
        .evento-actions { display: flex; gap: 6px; padding: 0 16px 14px; }
        .btn-evento {
            flex: 1; padding: 7px 0; border-radius: 10px; font-size: 0.8rem;
            font-weight: 700; border: none; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .btn-evento-ver { background: var(--kr); color: white; }
        .btn-evento-ver:hover { background: #d43f3d; }
        .btn-evento-del { background: #f8f9fa; color: #dc3545; border: 1.5px solid #f5c6cb; }
        .btn-evento-del:hover { background: #f8d7da; }

        .eventos-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1.5rem;
        }

        .ev-lightbox {
            display: none; position: fixed; inset: 0; z-index: 10000;
            background: rgba(0,0,0,0.97); flex-direction: column;
        }
        .ev-lightbox.show { display: flex; }
        .ev-lb-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 24px; flex-shrink: 0;
        }
        .ev-lb-header h4 { color: white; margin: 0; font-size: 1.05rem; font-weight: 700; }
        .ev-lb-header .ev-lb-counter { color: rgba(255,255,255,0.6); font-size: 0.9rem; }
        .ev-lb-close {
            background: rgba(255,255,255,0.15); border: none; color: white;
            width: 40px; height: 40px; border-radius: 50%; font-size: 1.2rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .ev-lb-close:hover { background: var(--kr); }
        .ev-lb-body {
            flex: 1; display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden; min-height: 0;
        }
        .ev-lb-media {
            max-width: 90%; max-height: 100%;
            display: flex; align-items: center; justify-content: center;
        }
        .ev-lb-media img, .ev-lb-media video {
            max-width: 100%; max-height: calc(100vh - 180px);
            border-radius: 10px; object-fit: contain;
        }
        .ev-lb-nav {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(255,255,255,0.15); border: none; color: white;
            width: 46px; height: 46px; border-radius: 50%; font-size: 1.4rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s; z-index: 2;
        }
        .ev-lb-nav:hover { background: rgba(255,255,255,0.3); }
        .ev-lb-prev { left: 16px; }
        .ev-lb-next { right: 16px; }
        .ev-lb-thumbs {
            display: flex; gap: 6px; padding: 12px 20px; overflow-x: auto;
            flex-shrink: 0; background: rgba(0,0,0,0.4);
            scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.2) transparent;
        }
        .ev-lb-thumb {
            width: 60px; height: 48px; border-radius: 8px; overflow: hidden;
            cursor: pointer; flex-shrink: 0; opacity: 0.55; border: 2px solid transparent;
            transition: all 0.2s;
        }
        .ev-lb-thumb.active { opacity: 1; border-color: var(--kr); }
        .ev-lb-thumb img, .ev-lb-thumb video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .ev-lb-thumb .thumb-vid-icon {
            position: absolute; inset: 0; display: flex;
            align-items: center; justify-content: center; background: rgba(0,0,0,0.35);
        }

        .filter-pills { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .filter-pill {
            background: white; border: 2px solid transparent; border-radius: 50px;
            padding: 0.55rem 1.4rem; font-weight: 700; font-size: 0.88rem;
            cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; gap: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .filter-pill:hover { border-color: var(--kr); color: var(--kr); }
        .filter-pill.active {
            background: var(--kr); color: white;
            border-color: var(--kr); box-shadow: 0 6px 20px rgba(232,86,84,0.35);
        }

        .ind-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.25rem;
        }
        .ind-card {
            position: relative; border-radius: 16px; overflow: hidden; background: #dee2e6;
            box-shadow: 0 4px 16px rgba(0,0,0,0.09); cursor: pointer; transition: all 0.35s;
            aspect-ratio: 4/3;
        }
        .ind-card:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 12px 32px rgba(0,0,0,0.18); }
        .ind-card img, .ind-card video { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.35s; }
        .ind-card:hover img, .ind-card:hover video { transform: scale(1.07); }
        .ind-badge {
            position: absolute; top: 10px; right: 10px;
            background: rgba(0,0,0,0.72); color: white;
            font-size: 0.72rem; font-weight: 700;
            padding: 3px 9px; border-radius: 20px;
            display: flex; align-items: center; gap: 4px;
        }
        .ind-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 55%);
            display: flex; flex-direction: column; justify-content: flex-end;
            padding: 12px; opacity: 0; transition: opacity 0.3s;
        }
        .ind-card:hover .ind-overlay { opacity: 1; }
        .ind-title { color: white; font-size: 0.82rem; font-weight: 700; margin-bottom: 6px; }
        .ind-actions { display: flex; gap: 6px; }
        .btn-ind {
            background: rgba(255,255,255,0.2); backdrop-filter: blur(8px);
            border: none; color: white; width: 34px; height: 34px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1rem; cursor: pointer; transition: all 0.2s;
        }
        .btn-ind:hover { background: rgba(255,255,255,0.35); transform: scale(1.12); }
        .btn-ind.del:hover { background: var(--kr); }

        .lb {
            display: none; position: fixed; inset: 0; z-index: 9999;
            background: rgba(0,0,0,0.96); align-items: center; justify-content: center;
            backdrop-filter: blur(6px);
        }
        .lb.show { display: flex; }
        .lb-inner { max-width: 92%; max-height: 92%; position: relative; }
        .lb-inner img, .lb-inner video { max-width: 100%; max-height: 90vh; border-radius: 12px; }
        .lb-close {
            position: absolute; top: -46px; right: 0;
            background: rgba(255,255,255,0.18); border: none; color: white;
            width: 40px; height: 40px; border-radius: 50%; font-size: 1.2rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.25s;
        }
        .lb-close:hover { background: var(--kr); transform: rotate(90deg); }

        .upload-tabs { display: flex; border-radius: 12px; overflow: hidden; border: 1.5px solid #dee2e6; margin-bottom: 1.25rem; }
        .upload-tab { flex: 1; padding: 0.65rem; border: none; background: #f8f9fa; font-weight: 700; font-size: 0.88rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; color: #555; }
        .upload-tab.active { background: var(--kr); color: white; }
        .upload-pane { display: none; }
        .upload-pane.active { display: block; }
        .drop-zone {
            border: 2px dashed #dee2e6; border-radius: 14px;
            padding: 1.75rem; text-align: center; cursor: pointer;
            transition: all 0.25s; background: #fafafa;
        }
        .drop-zone:hover, .drop-zone.over { border-color: var(--kr); background: rgba(232,86,84,0.04); }
        .drop-zone i { font-size: 2.2rem; color: #adb5bd; display: block; margin-bottom: 8px; }
        .drop-zone p { color: #6c757d; font-size: 0.88rem; margin: 0 0 4px; }
        .drop-zone small { color: #adb5bd; font-size: 0.78rem; }
        .fprev { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; max-height: 180px; overflow-y: auto; }
        .fprev-item { display: flex; align-items: center; gap: 8px; background: #f8f9fa; border-radius: 8px; padding: 7px 11px; font-size: 0.82rem; }
        .fprev-item i { color: var(--kr); font-size: 1.1rem; flex-shrink: 0; }
        .fprev-item .fn { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #333; }
        .fprev-item .fs { color: #999; flex-shrink: 0; font-size: 0.75rem; }
        .fprev-item .fr { background: none; border: none; color: #bbb; cursor: pointer; font-size: 0.9rem; flex-shrink: 0; }
        .fprev-item .fr:hover { color: var(--kr); }
        .empty-state { text-align: center; padding: 3rem 1rem; color: #adb5bd; }
        .empty-state i { font-size: 4rem; display: block; margin-bottom: 1rem; }

        @media(max-width:768px){
            .eventos-grid { grid-template-columns: 1fr 1fr; gap: 1rem; }
            .ind-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
        }
        @media(max-width:480px){
            .eventos-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

@include('includes.menu')

@php
    $rolActual     = Auth::check() ? Auth::user()->rol : null;
    $esAdmin       = $rolActual === 'admin';
    $esSensei      = $rolActual === 'sensei';
    $puedeSubir    = $esAdmin || $esSensei;   // subir archivos
    $puedeEliminar = $esAdmin;                // solo admin elimina
@endphp

<div class="main-content">
    <header class="header mb-0">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div>
                <h1 class="header-title"><i class="bi bi-images"></i> Galería Multimedia</h1>
                <div class="breadcrumb">
                    <a href="{{ route('principal') }}">Inicio</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Galería</span>
                </div>
            </div>
            {{-- Botón subir: visible para admin y sensei --}}
            @if($puedeSubir)
            <button class="btn btn-danger rounded-pill px-4 py-2 d-flex align-items-center gap-2"
                    data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="bi bi-cloud-upload"></i> Subir Archivo
            </button>
            @endif
        </div>
    </header>

    @if(session('mensaje'))
    <div class="alert alert-success mx-4 mt-3 d-flex gap-2 align-items-center">
        <i class="bi bi-check-circle-fill"></i> {{ session('mensaje') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger mx-4 mt-3 d-flex gap-2 align-items-center">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Aviso de enlace simbólico roto (solo admin) --}}
    @if($esAdmin && !file_exists(public_path('storage')))
    <div class="alert alert-warning mx-4 mt-3 d-flex gap-2 align-items-center">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>
            <strong>Las imágenes no se verán correctamente.</strong>
            El enlace simbólico <code>public/storage</code> no existe.
            Ejecuta en el servidor: <code>php artisan storage:link</code>
        </div>
    </div>
    @endif

    <div class="content-wrapper">

        {{-- ══ SECCIÓN 1: EVENTOS (galerías agrupadas) ══ --}}
        <div class="section-heading">
            <h2><i class="bi bi-collection me-2" style="color:var(--kr)"></i>Eventos</h2>
            <span class="count-pill">{{ count($eventos) }}</span>
            <div class="line"></div>
        </div>

        @if(count($eventos) > 0)
        <div class="eventos-grid">
            @foreach($eventos as $evento)
            <div class="evento-card" onclick="abrirEvento('{{ addslashes($evento->nombre) }}')">

                <div class="evento-mosaic">
                    @foreach($evento->miniaturas as $i => $mini)
                        @if($i < 7)
                        <div class="tile {{ $i === 0 ? 'tile-main' : '' }}">
                            @if($mini->tipo === 'imagen')
                                <img src="{{ asset('storage/' . $mini->ruta) }}" alt="{{ $mini->titulo }}" loading="lazy">
                            @else
                                <video preload="none">
                                    <source src="{{ asset('storage/' . $mini->ruta) }}" type="video/mp4">
                                </video>
                                <div class="video-play"><i class="bi bi-play-circle-fill"></i></div>
                            @endif
                        </div>
                        @elseif($i === 7)
                        <div class="tile tile-more">+{{ $evento->total - 7 }}</div>
                        @endif
                    @endforeach
                    @for($p = $evento->miniaturas->count(); $p < 8; $p++)
                    <div class="tile" style="background:#e9ecef;"></div>
                    @endfor
                </div>

                <div class="evento-info">
                    <h3>{{ $evento->nombre }}</h3>
                    <div class="evento-stats">
                        @if($evento->total_fotos > 0)
                        <span><i class="bi bi-image"></i> {{ $evento->total_fotos }} foto{{ $evento->total_fotos > 1 ? 's' : '' }}</span>
                        @endif
                        @if($evento->total_videos > 0)
                        <span><i class="bi bi-play-circle"></i> {{ $evento->total_videos }} video{{ $evento->total_videos > 1 ? 's' : '' }}</span>
                        @endif
                    </div>
                </div>

                <div class="evento-actions" onclick="event.stopPropagation()">
                    <button class="btn-evento btn-evento-ver"
                            onclick="abrirEvento('{{ addslashes($evento->nombre) }}')">
                        <i class="bi bi-eye"></i> Ver galería
                    </button>
                    {{-- Eliminar evento: solo admin --}}
                    @if($puedeEliminar)
                    <button class="btn-evento btn-evento-del"
                            onclick="eliminarEvento('{{ addslashes($evento->nombre) }}')">
                        <i class="bi bi-trash3"></i>
                    </button>
                    @endif
                    @if($evento->destacado ?? false)
                    <span title="Tiene archivos destacados en el landing"
                          style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#b8860b;font-weight:600;padding:4px 10px;background:#fff9e6;border-radius:20px;border:1px solid #ffd700;">
                        <i class="bi bi-star-fill"></i> En landing
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <i class="bi bi-collection"></i>
            <p style="font-size:1rem; font-weight:600; color:#6c757d;">No hay galerías de eventos todavía</p>
            @if($puedeSubir)
            <p style="font-size:0.88rem; color:#adb5bd;">Usa "Subir Archivo" → pestaña "Galería de evento"</p>
            @endif
        </div>
        @endif


        {{-- ══ SECCIÓN 2: ARCHIVOS INDIVIDUALES ══ --}}
        <div class="section-heading" style="margin-top:2.5rem;">
            <h2><i class="bi bi-file-earmark-image me-2" style="color:var(--kr)"></i>Archivos Individuales</h2>
            <span class="count-pill">{{ count($individuales) }}</span>
            <div class="line"></div>
        </div>

        <div class="filter-pills">
            <button class="filter-pill active" data-filter="all">
                <i class="bi bi-grid-3x3-gap"></i> Todos ({{ count($individuales) }})
            </button>
            <button class="filter-pill" data-filter="imagen">
                <i class="bi bi-image"></i> Imágenes ({{ count($imagenes_ind) }})
            </button>
            <button class="filter-pill" data-filter="video">
                <i class="bi bi-play-circle"></i> Videos ({{ count($videos_ind) }})
            </button>
        </div>

        @if(count($individuales) > 0)
        <div class="ind-grid" id="indGrid">
            @foreach($individuales as $archivo)
            <div class="ind-card" data-type="{{ $archivo->tipo }}"
                 onclick="openLb('{{ asset('storage/' . $archivo->ruta) }}','{{ $archivo->tipo }}')">
                <div class="ind-badge">
                    @if($archivo->tipo === 'imagen')
                        <i class="bi bi-image-fill"></i> Imagen
                    @else
                        <i class="bi bi-play-circle-fill"></i> Video
                    @endif
                </div>
                @if($archivo->tipo === 'imagen')
                    <img src="{{ asset('storage/' . $archivo->ruta) }}" alt="{{ $archivo->titulo }}" loading="lazy">
                @else
                    <video preload="none">
                        <source src="{{ asset('storage/' . $archivo->ruta) }}" type="video/mp4">
                    </video>
                @endif
                <div class="ind-overlay" onclick="event.stopPropagation()">
                    <div class="ind-title">{{ $archivo->titulo }}</div>
                    <div class="ind-actions">
                        <button class="btn-ind"
                                onclick="openLb('{{ asset('storage/' . $archivo->ruta) }}','{{ $archivo->tipo }}')">
                            <i class="bi bi-zoom-in"></i>
                        </button>
                        {{-- Eliminar archivo: solo admin --}}
                        @if($puedeEliminar)
                        <button class="btn-ind del"
                                onclick="delArchivo({{ $archivo->id_evento }},'{{ addslashes($archivo->titulo) }}')">
                            <i class="bi bi-trash3"></i>
                        </button>
                        @endif
                        {{-- Destacado: admin y sensei --}}
                        @if($puedeSubir)
                        <button class="btn-ind"
                                onclick="toggleDestacado(this)"
                                data-url="{{ route('galeria.destacado', $archivo->id_evento) }}"
                                data-destacado="{{ $archivo->destacado ?? 0 }}"
                                title="{{ ($archivo->destacado ?? 0) ? 'Quitar del landing' : 'Mostrar en landing' }}"
                                style="background:{{ ($archivo->destacado ?? 0) ? '#ffd700' : 'rgba(255,255,255,0.2)' }};">
                            <i class="bi bi-star-fill" style="color:{{ ($archivo->destacado ?? 0) ? '#333' : 'white' }}"></i>
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <i class="bi bi-file-earmark-image"></i>
            <p style="font-size:1rem; font-weight:600; color:#6c757d;">No hay archivos individuales</p>
            @if($puedeSubir)
            <p style="font-size:0.88rem; color:#adb5bd;">Usa "Subir Archivo" → pestaña "Archivo único"</p>
            @endif
        </div>
        @endif

    </div>{{-- /content-wrapper --}}
    @include('includes.pie')
</div>

{{-- ── Lightbox individual ──────────────────────────────────── --}}
<div class="lb" id="lb" onclick="closeLb()">
    <div class="lb-inner" onclick="event.stopPropagation()">
        <button class="lb-close" onclick="closeLb()"><i class="bi bi-x-lg"></i></button>
        <div id="lbMedia"></div>
    </div>
</div>

{{-- ── Lightbox de evento (carousel) ──────────────────────────── --}}
<div class="ev-lightbox" id="evLb">
    <div class="ev-lb-header">
        <div>
            <h4 id="evLbTitle"></h4>
            <span class="ev-lb-counter" id="evLbCounter"></span>
        </div>
        <button class="ev-lb-close" onclick="closeEvLb()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="ev-lb-body">
        <button class="ev-lb-nav ev-lb-prev" onclick="evNavegar(-1)"><i class="bi bi-chevron-left"></i></button>
        <div class="ev-lb-media" id="evLbMedia"></div>
        <button class="ev-lb-nav ev-lb-next" onclick="evNavegar(1)"><i class="bi bi-chevron-right"></i></button>
    </div>
    <div class="ev-lb-thumbs" id="evLbThumbs"></div>
</div>

{{-- ── Modal Subida (admin y sensei) ───────────────────────────── --}}
@if($puedeSubir)
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:22px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header text-white"
                 style="background:linear-gradient(135deg,var(--kr),#d43f3d);border-radius:22px 22px 0 0;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-cloud-upload me-2"></i>Subir Contenido Multimedia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="upForm" action="{{ route('galeria.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="modo" id="upModo" value="individual">
                <div class="modal-body p-4">

                    <div class="upload-tabs" id="upTabs">
                        <button type="button" class="upload-tab active" data-modo="individual" onclick="setModo('individual')">
                            <i class="bi bi-file-earmark-image"></i> Archivo único
                        </button>
                        <button type="button" class="upload-tab" data-modo="evento" onclick="setModo('evento')">
                            <i class="bi bi-collection"></i> Galería de evento
                        </button>
                    </div>

                    <div id="wrap-titulo" class="mb-3">
                        <label class="form-label fw-semibold">Título del archivo</label>
                        <input type="text" name="titulo" id="upTitulo" class="form-control"
                               placeholder="Ej: Entrenamiento Viernes">
                    </div>

                    <div id="wrap-evento" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold">
                            Nombre del evento
                            <span class="text-muted fw-normal" style="font-size:0.82rem;">
                                (los archivos se agruparán bajo este nombre)
                            </span>
                        </label>
                        <input type="text" name="nombre_evento" id="upEvento" class="form-control"
                               placeholder="Ej: Torneo Regional 2025">
                        <div class="mt-2">
                            <label class="form-label fw-semibold" style="font-size:0.82rem;">
                                ¿Añadir a evento existente?
                            </label>
                            <select id="eventoExistente" class="form-select form-select-sm"
                                    onchange="if(this.value) document.getElementById('upEvento').value=this.value">
                                <option value="">— Crear nuevo evento —</option>
                                @foreach($eventos as $ev)
                                <option value="{{ $ev->nombre }}">{{ $ev->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tipo de contenido</label>
                        <select name="tipo" id="upTipo" class="form-select" required onchange="updateAccept()">
                            <option value="">— Selecciona —</option>
                            <option value="imagen">Imagen (JPG, PNG)</option>
                            <option value="video">Video (MP4)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Descripción <span class="text-muted fw-normal">(opcional)</span>
                        </label>
                        <textarea name="descripcion" class="form-control" rows="2"
                                  placeholder="Notas sobre este contenido..."></textarea>
                    </div>

                    {{-- Destacado: admin y sensei ven esta opción --}}
                    @if($puedeSubir)
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="destacado" id="upDestacado" value="1">
                        <label class="form-check-label fw-semibold" for="upDestacado">
                            <i class="bi bi-star-fill text-warning me-1"></i> Mostrar en el landing
                        </label>
                    </div>
                    @endif

                    <div class="drop-zone" id="dropZone" onclick="document.getElementById('upFile').click()">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <p id="dzText">Haz clic o arrastra archivos aquí</p>
                        <small id="dzHint">Selecciona el tipo primero</small>
                    </div>
                    <input type="file" name="archivos[]" id="upFile" class="d-none"
                           onchange="previewFiles(this)">

                    <div id="filePreview" class="fprev"></div>

                    <div id="countWrap" style="display:none;margin-top:8px;">
                        <span class="badge bg-danger rounded-pill" id="countTxt"></span>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnUp" class="btn btn-danger px-4 rounded-pill" disabled>
                        <i class="bi bi-upload me-2"></i>
                        <span id="btnUpTxt">Subir archivo</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Form oculto para eliminar evento completo --}}
<form id="delEventoForm" method="POST" action="{{ route('galeria.destroyEvento') }}" style="display:none;">
    @csrf @method('DELETE')
    <input type="hidden" name="nombre_evento" id="delEventoNombre">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const eventosData  = @json($eventosData);
const puedeEliminar = {{ $puedeEliminar ? 'true' : 'false' }};

// ── Filtros individuales ──────────────────────────────────────────────
document.querySelectorAll('.filter-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const f = btn.dataset.filter;
        document.querySelectorAll('#indGrid .ind-card').forEach(card => {
            card.style.display = (f === 'all' || card.dataset.type === f) ? '' : 'none';
        });
    });
});

// ── Lightbox individual ───────────────────────────────────────────────
function openLb(src, tipo) {
    document.getElementById('lbMedia').innerHTML = tipo === 'imagen'
        ? `<img src="${src}" alt="">`
        : `<video controls autoplay style="max-width:100%;max-height:88vh;border-radius:10px;"><source src="${src}" type="video/mp4"></video>`;
    document.getElementById('lb').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeLb() {
    const v = document.querySelector('#lbMedia video');
    if (v) v.pause();
    document.getElementById('lb').classList.remove('show');
    document.body.style.overflow = '';
    setTimeout(() => document.getElementById('lbMedia').innerHTML = '', 300);
}

// ── Lightbox de evento (carousel) ────────────────────────────────────
let evArchivos = [], evIdx = 0;

function abrirEvento(nombre) {
    evArchivos = eventosData[nombre] || [];
    if (!evArchivos.length) return;
    evIdx = 0;
    document.getElementById('evLbTitle').textContent = nombre;
    renderEvThumbs();
    renderEvMedia();
    document.getElementById('evLb').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEvLb() {
    const v = document.querySelector('#evLbMedia video');
    if (v) v.pause();
    document.getElementById('evLb').classList.remove('show');
    document.body.style.overflow = '';
}

function renderEvMedia() {
    const a = evArchivos[evIdx];
    const c = document.getElementById('evLbMedia');
    if (a.tipo === 'imagen') {
        c.innerHTML = `<img src="${a.src}" alt="${a.titulo}" style="max-width:100%;max-height:calc(100vh - 180px);border-radius:10px;object-fit:contain;">`;
    } else {
        c.innerHTML = `<video controls autoplay style="max-width:100%;max-height:calc(100vh - 180px);border-radius:10px;">
            <source src="${a.src}" type="video/mp4"></video>`;
    }
    document.getElementById('evLbCounter').textContent = `${evIdx + 1} / ${evArchivos.length}`;
    document.querySelectorAll('.ev-lb-thumb').forEach((t, i) => t.classList.toggle('active', i === evIdx));
    const thumbEl = document.querySelector('.ev-lb-thumb.active');
    if (thumbEl) thumbEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
}

function renderEvThumbs() {
    const c = document.getElementById('evLbThumbs');
    c.innerHTML = evArchivos.map((a, i) => `
        <div class="ev-lb-thumb ${i===0?'active':''}" onclick="evGoTo(${i})" style="position:relative;">
            ${a.tipo==='imagen'
                ? `<img src="${a.src}" alt="" loading="lazy">`
                : `<video preload="none"><source src="${a.src}" type="video/mp4"></video>
                   <div class="thumb-vid-icon"><i class="bi bi-play-fill" style="color:white;font-size:0.9rem;"></i></div>`
            }
        </div>`).join('');
}

function evNavegar(dir) {
    const v = document.querySelector('#evLbMedia video');
    if (v) v.pause();
    evIdx = (evIdx + dir + evArchivos.length) % evArchivos.length;
    renderEvMedia();
}

function evGoTo(idx) {
    const v = document.querySelector('#evLbMedia video');
    if (v) v.pause();
    evIdx = idx;
    renderEvMedia();
}

document.addEventListener('keydown', e => {
    if (document.getElementById('evLb').classList.contains('show')) {
        if (e.key === 'ArrowLeft')  evNavegar(-1);
        if (e.key === 'ArrowRight') evNavegar(1);
        if (e.key === 'Escape')     closeEvLb();
    } else if (document.getElementById('lb').classList.contains('show')) {
        if (e.key === 'Escape') closeLb();
    }
});

// ── Eliminar ──────────────────────────────────────────────────────────
function delArchivo(id, titulo) {
    if (!puedeEliminar) return;
    if (!confirm(`Eliminar "${titulo}"?\n\nEsta acción no se puede deshacer.`)) return;
    const f = document.createElement('form');
    f.method = 'POST'; f.action = `/galeria/${id}`;
    const t = document.createElement('input'); t.type='hidden'; t.name='_token';
    t.value = document.querySelector('meta[name="csrf-token"]').content;
    const m = document.createElement('input'); m.type='hidden'; m.name='_method'; m.value='DELETE';
    f.appendChild(t); f.appendChild(m); document.body.appendChild(f); f.submit();
}

function eliminarEvento(nombre) {
    if (!puedeEliminar) return;
    if (!confirm(`Eliminar el evento "${nombre}" y TODOS sus archivos?\n\nEsta acción no se puede deshacer.`)) return;
    document.getElementById('delEventoNombre').value = nombre;
    document.getElementById('delEventoForm').submit();
}

// ── Modal subida ──────────────────────────────────────────────────────
function setModo(modo) {
    document.getElementById('upModo').value = modo;
    document.querySelectorAll('[data-modo]').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-modo') === modo);
    });
    document.getElementById('wrap-titulo').style.display = modo === 'individual' ? '' : 'none';
    document.getElementById('wrap-evento').style.display = modo === 'evento'     ? '' : 'none';
    const fileInput = document.getElementById('upFile');
    if (fileInput) fileInput.multiple = (modo === 'evento');
    resetUpload();
}

function updateAccept() {
    const t  = document.getElementById('upTipo').value;
    const el = document.getElementById('upFile');
    if (t === 'imagen') el.accept = 'image/jpeg,image/jpg,image/png';
    else if (t === 'video') el.accept = 'video/mp4';
    else el.removeAttribute('accept');
    document.getElementById('dzHint').textContent = t==='imagen'
        ? 'JPG, PNG — máx. 10 MB'
        : t==='video' ? 'MP4 — máx. 50 MB' : 'Selecciona el tipo primero';
    checkBtn();
}

function previewFiles(input) {
    const c = document.getElementById('filePreview');
    c.innerHTML = '';
    Array.from(input.files).forEach((f, i) => {
        const isImg = f.type.startsWith('image/');
        const div   = document.createElement('div');
        div.className = 'fprev-item';
        div.innerHTML = `<i class="bi ${isImg?'bi-file-earmark-image':'bi-file-earmark-play'}"></i>
            <span class="fn">${f.name}</span>
            <span class="fs">${(f.size/(1024*1024)).toFixed(1)} MB</span>
            <button type="button" class="fr" onclick="removeFile(${i})"><i class="bi bi-x-lg"></i></button>`;
        c.appendChild(div);
    });
    const n    = input.files.length;
    const wrap = document.getElementById('countWrap');
    wrap.style.display = n > 1 ? 'block' : 'none';
    if (n > 1) document.getElementById('countTxt').textContent = `${n} archivos seleccionados`;
    checkBtn();
}

function removeFile(idx) {
    const inp = document.getElementById('upFile');
    const dt  = new DataTransfer();
    Array.from(inp.files).forEach((f, i) => { if(i!==idx) dt.items.add(f); });
    inp.files = dt.files;
    previewFiles(inp);
}

function checkBtn() {
    const modo  = document.getElementById('upModo').value;
    const tipo  = document.getElementById('upTipo').value;
    const campo = modo === 'individual'
        ? document.getElementById('upTitulo').value.trim()
        : document.getElementById('upEvento').value.trim();
    const n  = document.getElementById('upFile').files.length;
    const ok = tipo && campo && n > 0;
    document.getElementById('btnUp').disabled = !ok;
    document.getElementById('btnUpTxt').textContent = n > 1 ? `Subir ${n} archivos` : 'Subir archivo';
}

document.getElementById('upTitulo')?.addEventListener('input', checkBtn);
document.getElementById('upEvento')?.addEventListener('input', checkBtn);

function resetUpload() {
    document.getElementById('upFile').value = '';
    document.getElementById('filePreview').innerHTML = '';
    document.getElementById('countWrap').style.display = 'none';
    checkBtn();
}

// Drag & Drop
const dz = document.getElementById('dropZone');
if (dz) {
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('over'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('over'));
    dz.addEventListener('drop', e => {
        e.preventDefault(); dz.classList.remove('over');
        const inp = document.getElementById('upFile');
        const dt  = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        inp.files = dt.files;
        previewFiles(inp);
    });
}

document.getElementById('uploadModal')?.addEventListener('hidden.bs.modal', () => {
    document.getElementById('upForm').reset();
    resetUpload();
    setModo('individual');
});

// ── Toggle destacado (solo admin) ─────────────────────────────────────
async function toggleDestacado(btn) {
    const url       = btn.getAttribute('data-url');
    const csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';
    const estrella  = btn.querySelector('i');

    btn.disabled = true;
    btn.style.opacity = '0.5';

    try {
        const res = await fetch(url, {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-CSRF-TOKEN':     csrfToken,
                'Accept':           'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            alert('Error ' + res.status + ': ' + res.statusText);
            return;
        }

        const data = await res.json();

        if (data.success) {
            btn.setAttribute('data-destacado', data.destacado);
            if (data.destacado) {
                btn.style.background = '#ffd700';
                btn.title = 'Quitar del landing';
                if (estrella) estrella.style.color = '#333';
            } else {
                btn.style.background = 'rgba(255,255,255,0.2)';
                btn.title = 'Mostrar en landing';
                if (estrella) estrella.style.color = 'white';
            }
            const toast = document.createElement('div');
            toast.textContent = data.message;
            toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#222;color:#fff;padding:10px 20px;border-radius:8px;z-index:9999;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,.3);transition:opacity .3s';
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 2200);
        } else {
            alert(data.message || 'Error al actualizar.');
        }
    } catch(e) {
        console.error('toggleDestacado error:', e);
        alert('Error de conexión. Revisa la consola.');
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
    }
}
</script>
</body>
</html>