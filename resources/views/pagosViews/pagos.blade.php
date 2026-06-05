{{--
    resources/views/pagosViews/pagos.blade.php — REEMPLAZA COMPLETO
    Bugs corregidos:
      - Modal tutor: usa fetch nativo (sin Bootstrap.Modal) — elimina el "Cargando pagos..." perpetuo
      - renderPagosAlumno: usa p.estado_pago / p.motivo_pago (snake_case) en lugar de camelCase
    Diseño rediseñado: layout más moderno, tarjetas de stats, tabla limpia, modales mejorados
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pagos — Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ══════════════════════════════════════════════
           BASE TOKENS
        ══════════════════════════════════════════════ */
        :root {
            --red:      #DC2626;
            --red-dark: #B91C1C;
            --red-light:#FEF2F2;
            --red-mid:  #FECACA;
            --green:    #16A34A;
            --green-bg: #DCFCE7;
            --amber:    #D97706;
            --amber-bg: #FEF3C7;
            --purple:   #7C3AED;
            --purple-bg:#F3E8FF;
            --blue:     #1D4ED8;
            --blue-bg:  #EFF6FF;
            --gray:     #6B7280;
            --gray-light:#F3F4F6;
            --border:   #E5E7EB;
            --text:     #111827;
            --text2:    #4B5563;
            --text3:    #9CA3AF;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
            --card-shadow-hover: 0 4px 12px rgba(0,0,0,0.10);
            --radius:   12px;
            --radius-sm:8px;
        }

        /* ══════════════════════════════════════════════
           LAYOUT GENERAL
        ══════════════════════════════════════════════ */
        .pagos-wrap { display:flex; flex-direction:column; gap:24px; max-width:1400px; }

        /* ══════════════════════════════════════════════
           STATS ROW
        ══════════════════════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }
        @media (max-width:900px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width:540px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }

        .stat-card {
            background:#fff;
            border-radius:var(--radius);
            padding:16px 18px;
            box-shadow: var(--card-shadow);
            display:flex;
            align-items:center;
            gap:14px;
            border:1px solid var(--border);
            transition: box-shadow .15s;
        }
        .stat-card:hover { box-shadow: var(--card-shadow-hover); }
        .stat-icon {
            width:44px; height:44px;
            border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:20px;
            flex-shrink:0;
        }
        .stat-label  { font-size:12px; color:var(--text3); margin-bottom:2px; }
        .stat-value  { font-size:22px; font-weight:700; color:var(--text); line-height:1; }
        .stat-value.green  { color:var(--green); }
        .stat-value.amber  { color:var(--amber); }
        .stat-value.red    { color:var(--red); }

        /* ══════════════════════════════════════════════
           PANEL FORMULARIO (card unificada)
        ══════════════════════════════════════════════ */
        .panel-card {
            background:#fff;
            border:1px solid var(--border);
            border-radius:var(--radius);
            box-shadow: var(--card-shadow);
            overflow:hidden;
        }
        .panel-head {
            padding:18px 24px;
            border-bottom:1px solid var(--border);
            display:flex; align-items:center; gap:12px;
        }
        .panel-head-icon {
            width:36px; height:36px; border-radius:8px;
            background:var(--red-light); color:var(--red);
            display:flex; align-items:center; justify-content:center;
            font-size:18px; flex-shrink:0;
        }
        .panel-head h2 { font-size:15px; font-weight:600; color:var(--text); margin:0; }
        .panel-head p  { font-size:12px; color:var(--text3); margin:2px 0 0; }
        .panel-body { padding:24px; }

        /* ══════════════════════════════════════════════
           TABS (admin)
        ══════════════════════════════════════════════ */
        .tabs-nav {
            display:flex; gap:0;
            border-bottom:1px solid var(--border);
            padding:0 24px;
            background:#FAFAFA;
        }
        .tab-btn {
            background:none; border:none;
            padding:12px 18px;
            font-size:13px; font-weight:600;
            color:var(--text3);
            cursor:pointer;
            border-bottom:2px solid transparent;
            margin-bottom:-1px;
            display:flex; align-items:center; gap:6px;
            transition:color .15s;
        }
        .tab-btn.active  { color:var(--red); border-bottom-color:var(--red); }
        .tab-btn:hover:not(.active) { color:var(--text2); }
        .tab-content     { display:none; }
        .tab-content.active { display:block; }

        /* ══════════════════════════════════════════════
           CONCEPTOS GRID
        ══════════════════════════════════════════════ */
        .conceptos-grid {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:14px;
        }
        @media (max-width:1100px) { .conceptos-grid { grid-template-columns:repeat(3,1fr); } }
        @media (max-width:800px)  { .conceptos-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:500px)  { .conceptos-grid { grid-template-columns:1fr; } }

        .concepto-card {
            border:1px solid var(--border);
            border-radius:var(--radius);
            padding:16px;
            background:#fff;
            display:flex; flex-direction:column; gap:8px;
            box-shadow: var(--card-shadow);
            transition:box-shadow .15s, transform .15s;
            position:relative; overflow:hidden;
        }
        .concepto-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:3px;
            background:linear-gradient(90deg,var(--red),#F87171);
        }
        .concepto-card.inactivo-card::before { background:var(--border); }
        .concepto-card:hover { box-shadow:var(--card-shadow-hover); transform:translateY(-1px); }
        .concepto-card-top { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
        .concepto-nombre   { font-weight:700; color:var(--text); font-size:14px; flex:1; line-height:1.3; }
        .concepto-estado   { font-size:11px; padding:2px 8px; border-radius:20px; font-weight:600; white-space:nowrap; }
        .concepto-activo   { background:var(--green-bg); color:var(--green); }
        .concepto-inactivo { background:#FEE2E2; color:var(--red-dark); }
        .concepto-desc     { font-size:12px; color:var(--text3); line-height:1.4; flex:1; min-height:30px; }
        .concepto-card-bottom {
            display:flex; justify-content:space-between; align-items:center;
            border-top:1px solid var(--border); padding-top:10px; margin-top:2px;
        }
        .concepto-monto          { font-size:20px; font-weight:800; color:var(--red); }
        .concepto-monto.sin-monto{ font-size:13px; font-weight:500; color:var(--text3); }
        .btn-edit-concepto {
            border:1px solid var(--border); border-radius:var(--radius-sm);
            padding:5px 12px; cursor:pointer; color:var(--text2);
            font-size:12px; font-weight:600; background:none;
            display:inline-flex; align-items:center; gap:4px; transition:all .15s;
        }
        .btn-edit-concepto:hover { background:var(--red-light); color:var(--red); border-color:var(--red-mid); }

        /* ══════════════════════════════════════════════
           BANNERS INFO
        ══════════════════════════════════════════════ */
        .info-banner {
            background:var(--blue-bg); border-left:3px solid var(--blue);
            border-radius:var(--radius-sm); padding:14px 18px;
            display:flex; align-items:flex-start; gap:10px; font-size:13px;
        }
        .info-banner i { color:var(--blue); font-size:16px; margin-top:1px; }
        .aviso-efectivo {
            background:var(--amber-bg); border-left:3px solid var(--amber);
            border-radius:var(--radius-sm); padding:11px 15px;
            font-size:13px; color:#92400E; display:none; margin-top:8px;
        }

        /* ══════════════════════════════════════════════
           TABLA DE PAGOS
        ══════════════════════════════════════════════ */
        .table-card {
            background:#fff; border:1px solid var(--border);
            border-radius:var(--radius); box-shadow:var(--card-shadow); overflow:hidden;
        }
        .table-head-row {
            padding:16px 20px;
            border-bottom:1px solid var(--border);
            display:flex; align-items:center; justify-content:space-between; gap:12px;
            flex-wrap:wrap;
        }
        .table-head-row h2 {
            font-size:14px; font-weight:600; color:var(--text);
            display:flex; align-items:center; gap:8px; margin:0;
        }
        .table-head-row h2 i { color:var(--red); }
        .table-filters { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .filter-select {
            padding:7px 10px; border:1px solid var(--border); border-radius:var(--radius-sm);
            font-size:12px; background:#fff; color:var(--text2); outline:none;
        }
        .search-wrap {
            display:flex; align-items:center; gap:6px;
            border:1px solid var(--border); border-radius:var(--radius-sm);
            padding:7px 10px; background:#fff;
        }
        .search-wrap input { border:none; background:transparent; font-size:12px; color:var(--text); outline:none; width:140px; }
        .search-wrap i     { color:var(--text3); font-size:14px; }

        table { width:100%; border-collapse:collapse; font-size:13px; }
        thead tr { background:#F9FAFB; }
        th { padding:10px 14px; text-align:left; font-size:11px; font-weight:600;
             color:var(--text3); text-transform:uppercase; letter-spacing:.04em;
             border-bottom:1px solid var(--border); white-space:nowrap; }
        td { padding:12px 14px; border-bottom:1px solid var(--border); vertical-align:middle; }
        tbody tr:last-child td { border-bottom:none; }
        tbody tr:hover td { background:#FAFAFA; }

        .student-cell { display:flex; align-items:center; gap:10px; }
        .student-avatar {
            width:34px; height:34px; border-radius:50%;
            background:linear-gradient(135deg,var(--red),var(--red-dark));
            color:#fff; font-weight:700; font-size:12px;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .student-name { font-weight:600; color:var(--text); white-space:nowrap; }

        .amount { font-weight:700; color:var(--text); }

        .progress-bar-wrap { width:100%; background:#F3F4F6; border-radius:4px; height:4px; margin-top:4px; }
        .progress-bar-fill { height:4px; border-radius:4px; background:var(--red); }

        /* Badges */
        .badge {
            display:inline-flex; align-items:center; gap:4px;
            padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600;
        }
        .badge-success    { background:var(--green-bg); color:var(--green); }
        .badge-warning    { background:var(--amber-bg); color:var(--amber); }
        .badge-danger     { background:#FEE2E2; color:var(--red-dark); }
        .badge-suspendido { background:var(--purple-bg); color:var(--purple); }
        .badge-rechazado  { background:#FEE2E2; color:var(--red-dark); }

        .saldo-badge {
            display:inline-flex; align-items:center; gap:4px;
            padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600;
        }
        .saldo-completado { background:var(--green-bg); color:var(--green); }
        .saldo-pendiente  { background:var(--amber-bg); color:var(--amber); }

        /* Botones de acción en tabla */
        .acciones-cell { display:flex; flex-wrap:wrap; gap:5px; align-items:center; }
        .btn-act {
            display:inline-flex; align-items:center; gap:4px;
            padding:5px 10px; border-radius:var(--radius-sm);
            font-size:11px; font-weight:600; cursor:pointer;
            border:1px solid transparent; transition:all .12s; text-decoration:none;
            white-space:nowrap;
        }
        .btn-pagar     { background:var(--red); color:#fff; border-color:var(--red); }
        .btn-pagar:hover { background:var(--red-dark); color:#fff; border-color:var(--red-dark); }
        .btn-completar { background:var(--green-bg); color:var(--green); border-color:#BBF7D0; }
        .btn-completar:hover { background:#BBF7D0; }
        .btn-abono     { background:var(--amber-bg); color:var(--amber); border-color:#FDE68A; }
        .btn-abono:hover { background:#FDE68A; }
        .btn-abonos    { background:var(--purple-bg); color:var(--purple); border-color:#DDD6FE; }
        .btn-abonos:hover { background:#DDD6FE; }
        .btn-suspender { background:#FFF7ED; color:#C2410C; border-color:#FDBA74; }
        .btn-suspender:hover { background:#FDBA74; }
        .btn-eliminar  { background:#FEE2E2; color:var(--red-dark); border-color:var(--red-mid); }
        .btn-eliminar:hover { background:var(--red-mid); }

        /* ══════════════════════════════════════════════
           SECCIÓN TUTORES — alumnos relacionados
        ══════════════════════════════════════════════ */
        .alumno-card-tutor {
            background:#fff; border-radius:var(--radius); padding:16px 18px;
            box-shadow:var(--card-shadow); display:flex; align-items:center;
            justify-content:space-between; gap:14px;
            border:1px solid var(--border);
            border-left:4px solid var(--red);
            transition:box-shadow .15s, transform .15s;
        }
        .alumno-card-tutor:hover { box-shadow:var(--card-shadow-hover); transform:translateY(-1px); }
        .alumno-avatar-sm {
            width:44px; height:44px; border-radius:50%;
            background:linear-gradient(135deg,var(--red),var(--red-dark));
            color:#fff; font-weight:700; font-size:15px;
            display:flex; align-items:center; justify-content:center; flex-shrink:0;
        }
        .alumno-info-sm { flex:1; }
        .alumno-info-sm .nombre   { font-weight:700; color:var(--text); font-size:14px; }
        .alumno-info-sm .relacion { font-size:12px; color:var(--text3); margin-top:2px; }
        .btn-ver-pagos {
            background:var(--red); color:#fff; border:none;
            border-radius:var(--radius-sm); padding:8px 16px;
            font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap;
            display:inline-flex; align-items:center; gap:5px; transition:background .15s;
        }
        .btn-ver-pagos:hover { background:var(--red-dark); }

        /* ══════════════════════════════════════════════
           MODALES (propios, sin Bootstrap)
        ══════════════════════════════════════════════ */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,0.45); z-index:9999;
            align-items:center; justify-content:center;
        }
        .modal-overlay.active { display:flex; }
        .modal-box {
            background:#fff; border-radius:16px; width:100%;
            max-width:520px; box-shadow:0 20px 60px rgba(0,0,0,0.18);
            max-height:90vh; overflow-y:auto; margin:16px;
        }
        .modal-box.modal-lg { max-width:720px; }
        .modal-header {
            display:flex; justify-content:space-between; align-items:center;
            padding:18px 22px; border-bottom:1px solid var(--border);
            position:sticky; top:0; background:#fff; z-index:1; border-radius:16px 16px 0 0;
        }
        .modal-header h3 {
            font-size:15px; font-weight:700; color:var(--text);
            margin:0; display:flex; align-items:center; gap:8px;
        }
        .modal-header h3 i { color:var(--red); }
        .modal-close {
            background:none; border:none; font-size:20px; cursor:pointer;
            color:var(--text3); width:32px; height:32px; border-radius:8px;
            display:flex; align-items:center; justify-content:center; transition:all .12s;
        }
        .modal-close:hover { background:var(--gray-light); color:var(--text); }
        .modal-body { padding:22px; }

        /* Resumen de abono */
        .resumen-abono {
            background:var(--gray-light); border-radius:var(--radius-sm);
            padding:14px 16px; margin-bottom:16px; font-size:13px;
            border:1px solid var(--border);
        }
        .resumen-row {
            display:flex; justify-content:space-between;
            padding:6px 0; border-bottom:1px solid var(--border);
        }
        .resumen-row:last-child { border-bottom:none; }
        .resumen-label { color:var(--text3); }
        .resumen-value { font-weight:700; color:var(--text); }

        /* Inputs modales */
        .modal-label { font-size:13px; font-weight:600; color:var(--text2); margin-bottom:5px; display:block; margin-top:14px; }
        .modal-input {
            width:100%; padding:10px 12px; border:1px solid var(--border);
            border-radius:var(--radius-sm); font-size:14px; color:var(--text);
            background:#fff; box-sizing:border-box; outline:none; transition:border-color .15s;
        }
        .modal-input:focus { border-color:var(--red); }
        .modal-select {
            width:100%; padding:10px 12px; border:1px solid var(--border);
            border-radius:var(--radius-sm); font-size:14px; color:var(--text);
            background:#fff; box-sizing:border-box; outline:none;
        }
        .modal-actions { display:flex; gap:10px; margin-top:22px; }
        .btn-modal-prim {
            flex:1; padding:12px; background:var(--red); color:#fff; border:none;
            border-radius:var(--radius-sm); font-size:14px; font-weight:700; cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:6px;
        }
        .btn-modal-prim:hover { background:var(--red-dark); }
        .btn-modal-prim.blue  { background:var(--blue); }
        .btn-modal-prim.blue:hover { background:#1e3a8a; }
        .btn-modal-sec {
            flex:1; padding:12px; background:var(--gray-light); color:var(--text2);
            border:1px solid var(--border); border-radius:var(--radius-sm);
            font-size:14px; font-weight:600; cursor:pointer;
        }
        .btn-modal-sec:hover { background:var(--border); }

        /* Historial abonos */
        .abonos-list    { max-height:260px; overflow-y:auto; }
        .abono-item     { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border); font-size:13px; }
        .abono-item:last-child { border-bottom:none; }
        .abono-tipo-badge { padding:2px 8px; border-radius:8px; font-size:11px; font-weight:600; }
        .tipo-efectivo    { background:var(--green-bg); color:var(--green); }
        .tipo-en_linea    { background:var(--blue-bg); color:var(--blue); }

        /* Modal pagos alumno (tutor) */
        .pago-row-tutor {
            display:flex; align-items:flex-start; justify-content:space-between;
            flex-wrap:wrap; gap:8px; padding:12px 0; border-bottom:1px solid var(--border);
        }
        .pago-row-tutor:last-child { border-bottom:none; }
        .badge-estado  { padding:3px 9px; border-radius:20px; font-size:11px; font-weight:700; display:inline-block; }
        .badge-Pendiente   { background:var(--amber-bg); color:var(--amber); }
        .badge-Completado  { background:var(--green-bg); color:var(--green); }
        .badge-Cancelado   { background:#FEE2E2; color:var(--red-dark); }
        .badge-Suspendido  { background:var(--purple-bg); color:var(--purple); }
        .form-nuevo-pago {
            background:var(--gray-light); border-radius:var(--radius); padding:18px;
            margin-top:20px; border:1px solid var(--border);
        }
        .form-nuevo-pago h6 { color:var(--red); font-weight:700; margin-bottom:14px; display:flex; align-items:center; gap:6px; }

        /* Concepto hint */
        .concepto-hint { font-size:12px; color:var(--text3); margin-top:4px; min-height:16px; }
        .concepto-hint strong { color:var(--red); }

        /* Aviso pago en línea / efectivo */
        .aviso-efectivo-modal {
            background:var(--amber-bg); border-left:3px solid var(--amber);
            border-radius:var(--radius-sm); padding:10px 14px;
            font-size:12px; color:#92400E; display:none; margin-top:8px;
        }

        /* Alert página */
        .page-alert {
            display:flex; align-items:center; gap:10px; padding:14px 18px;
            border-radius:var(--radius); font-size:14px; margin-bottom:8px;
        }
        .page-alert.success { background:var(--green-bg); color:#14532D; border:1px solid #BBF7D0; }
        .page-alert.error   { background:#FEE2E2; color:#7F1D1D; border:1px solid var(--red-mid); }
        .page-alert i { font-size:18px; }
    </style>
</head>
<body>
@include('includes.menu')

<div class="main-content">

    <header class="header">
        <div>
            <h1 class="header-title">
                <i class="bi bi-cash-coin"></i>
                @if(in_array($user->rol, ['admin', 'sensei'])) Gestión de Pagos
                @else Mis Pagos
                @endif
            </h1>
            <div class="breadcrumb">
                <a href="{{ route('principal') }}">Inicio</a>
                <i class="bi bi-chevron-right"></i>
                <span>Pagos</span>
            </div>
        </div>
    </header>

    <div class="content-wrapper">
    <div class="pagos-wrap">

        {{-- ── Alertas de sesión ─────────────────────────────────────── --}}
        @if(session('mensaje'))
            @php $ok = session('sessionInsertado') == 'true'; @endphp
            <div class="page-alert {{ $ok ? 'success' : 'error' }}">
                <i class="bi bi-{{ $ok ? 'check-circle-fill' : 'x-circle-fill' }}"></i>
                <div><strong>{{ $ok ? '¡Éxito!' : '¡Error!' }}</strong> {{ session('mensaje') }}</div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             STATS CARDS
        ══════════════════════════════════════════════════════════ --}}
        @php
            $totalP    = $pagos->count();
            $comp      = $pagos->where('estado_pago', 'Completado')->count();
            $pend      = $pagos->where('estado_pago', 'Pendiente')->count();
            $totPagado = $pagos->sum('monto_pagado');
            $totSaldo  = $pagos->sum('saldo_restante');
        @endphp
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#DCFCE7;color:#16A34A;"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <div class="stat-label">Completados</div>
                    <div class="stat-value green">{{ $comp }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#FEF3C7;color:#D97706;"><i class="bi bi-clock-fill"></i></div>
                <div>
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value amber">{{ $pend }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#EFF6FF;color:#1D4ED8;"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="stat-label">Total pagado</div>
                    <div class="stat-value">${{ number_format($totPagado, 0) }}</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#FEF2F2;color:#DC2626;"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div>
                    <div class="stat-label">Por cobrar</div>
                    <div class="stat-value red">${{ number_format($totSaldo, 0) }}</div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             BLOQUE ADMIN / SENSEI — formulario + conceptos
        ══════════════════════════════════════════════════════════ --}}
        @if(in_array($user->rol, ['admin', 'sensei']))
        <div class="panel-card">
            <div class="panel-head">
                <div class="panel-head-icon"><i class="bi bi-credit-card-fill"></i></div>
                <div>
                    <h2>Panel de Pagos</h2>
                    <p>Registra cargos a alumnos y gestiona el catálogo de conceptos.</p>
                </div>
            </div>
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="activarTab('tab-registro',this)">
                    <i class="bi bi-plus-circle"></i> Registrar Cargo
                </button>
                <button class="tab-btn" onclick="activarTab('tab-conceptos',this)">
                    <i class="bi bi-bookmarks"></i> Conceptos de Pago
                </button>
            </div>

            {{-- TAB 1: Registrar cargo --}}
            <div id="tab-registro" class="tab-content active">
                <div class="panel-body">
                    <form id="registroPago" method="POST" action="{{ route('pagos.store') }}" class="form-body" style="padding:0;">
                        @csrf
                        <h3 class="section-title-header" style="margin-top:0;">
                            <i class="bi bi-person-circle"></i> Alumno Destinatario
                        </h3>
                        <div class="form-grid full-width">
                            <div class="form-group">
                                <label class="form-label" for="id_alumno">Destinatario <span class="required">*</span></label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-person-badge input-icon"></i>
                                    <select name="id_alumno" id="id_alumno" class="form-select" required>
                                        <option value="">Seleccione un alumno</option>
                                        @foreach($alumnos as $alumno)
                                            <option value="{{ $alumno->id_usuario }}" {{ old('id_alumno') == $alumno->id_usuario ? 'selected' : '' }}>
                                                {{ $alumno->nombre_completo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('id_alumno')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <h3 class="section-title-header"><i class="bi bi-receipt-cutoff"></i> Detalles del Cargo</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="id_concepto_admin">Concepto <span class="required">*</span></label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-bookmark input-icon"></i>
                                    <select name="id_concepto" id="id_concepto_admin" class="form-select" required>
                                        <option value="">Seleccione un concepto</option>
                                        @foreach($conceptos as $concepto)
                                            <option value="{{ $concepto->id_concepto }}"
                                                data-monto="{{ $concepto->monto_sugerido }}"
                                                data-nombre="{{ $concepto->nombre }}"
                                                {{ old('id_concepto') == $concepto->id_concepto ? 'selected' : '' }}>
                                                {{ $concepto->nombre }}
                                                @if($concepto->monto_sugerido) — ${{ number_format($concepto->monto_sugerido, 2) }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="concepto-hint" id="conceptoHintAdmin"></div>
                                @error('id_concepto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="id_tipo_pago">Método de Pago <span class="required">*</span></label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-tag input-icon"></i>
                                    <select name="id_tipo_pago" id="id_tipo_pago" class="form-select" required>
                                        <option value="">Seleccione el método</option>
                                        @foreach($tipos_pago as $tipo)
                                            <option value="{{ $tipo->id_tipo_pago }}" {{ old('id_tipo_pago') == $tipo->id_tipo_pago ? 'selected' : '' }}>
                                                {{ $tipo->nombre_tipo }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('id_tipo_pago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="fechaPago">Fecha <span class="required">*</span></label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-calendar-check input-icon"></i>
                                    <input type="date" name="fechaPago" id="fechaPago" class="form-input"
                                           value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                                </div>
                                @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group" id="estadoWrapAdmin">
                                <label class="form-label" for="estadoPago">Estado inicial <span class="required">*</span></label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-check-circle input-icon"></i>
                                    <select name="estadoPago" id="estadoPago" class="form-select" required>
                                        <option value="Pendiente"  {{ old('estadoPago','Pendiente') == 'Pendiente'  ? 'selected':'' }}>Pendiente (el alumno pagará después)</option>
                                        <option value="Completado" {{ old('estadoPago') == 'Completado' ? 'selected':'' }}>Completado (recibí efectivo ahora)</option>
                                    </select>
                                </div>
                                @error('estadoPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="motivoPago_admin">Nota / Detalle adicional</label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-chat-left-text input-icon"></i>
                                    <input type="text" name="motivoPago" id="motivoPago_admin" class="form-input"
                                           placeholder="Ej: Mensualidad Mayo 2026" value="{{ old('motivoPago') }}">
                                </div>
                            </div>

                            <div class="form-group" id="refAdminWrap">
                                <label class="form-label" for="referenciaPago">Referencia (opcional)</label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-receipt input-icon"></i>
                                    <input type="text" name="referenciaPago" id="referenciaPago" class="form-input"
                                           placeholder="Número de recibo o voucher" value="{{ old('referenciaPago') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top:16px;background:var(--blue-bg);border:1px solid #BFDBFE;border-radius:var(--radius-sm);padding:14px 16px;display:flex;align-items:center;gap:12px;">
                            <input type="checkbox" name="pagar_en_linea" id="pagarEnLinea" value="1"
                                   style="width:18px;height:18px;accent-color:#009ee3;flex-shrink:0;"
                                   {{ old('pagar_en_linea') ? 'checked' : '' }}>
                            <div>
                                <label for="pagarEnLinea" style="cursor:pointer;font-weight:700;font-size:14px;color:#1e3a8a;display:flex;align-items:center;gap:8px;">
                                    <i class="bi bi-credit-card-2-front-fill" style="color:#009ee3;"></i>
                                    Pagar en línea con
                                    <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp" alt="MercadoPago" style="height:18px;vertical-align:middle;">
                                </label>
                                <p style="margin:3px 0 0;font-size:12px;color:#3B82F6;">Se crea el cargo y se abre el checkout. El estado se actualiza automático al completarse.</p>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="reset" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Limpiar</button>
                            <button type="submit" class="btn btn-primary" id="btnSubmitAdmin">
                                <i class="bi bi-check-lg"></i> Registrar Cargo
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- TAB 2: Conceptos --}}
            <div id="tab-conceptos" class="tab-content">
                <div class="panel-body">
                    <form method="POST" action="{{ route('conceptos.store') }}" class="form-body" style="padding:0;margin-bottom:28px;">
                        @csrf
                        <h3 class="section-title-header" style="margin-top:0;"><i class="bi bi-plus-circle"></i> Agregar Nuevo Concepto</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="nuevo_nombre">Nombre <span class="required">*</span></label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-bookmark input-icon"></i>
                                    <input type="text" name="nombre" id="nuevo_nombre" class="form-input" placeholder="Ej: Torneo Regional" required>
                                </div>
                                @error('nombre')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="nuevo_monto">Monto Sugerido</label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-currency-dollar input-icon"></i>
                                    <input type="number" step="0.01" name="monto_sugerido" id="nuevo_monto" class="form-input" placeholder="0.00 (opcional)">
                                </div>
                            </div>
                            <div class="form-group" style="grid-column:span 2;">
                                <label class="form-label" for="nuevo_desc">Descripción (opcional)</label>
                                <div class="form-input-wrapper">
                                    <i class="bi bi-text-left input-icon"></i>
                                    <input type="text" name="descripcion" id="nuevo_desc" class="form-input" placeholder="Breve descripción del concepto">
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Agregar Concepto</button>
                        </div>
                    </form>

                    <h3 class="section-title-header" style="margin-top:0;"><i class="bi bi-list-ul"></i> Conceptos Registrados</h3>
                    <div class="conceptos-grid">
                        @forelse($conceptos_todos as $c)
                            <div class="concepto-card {{ $c->activo ? '' : 'inactivo-card' }}">
                                <div class="concepto-card-top">
                                    <span class="concepto-nombre">{{ $c->nombre }}</span>
                                    <span class="concepto-estado {{ $c->activo ? 'concepto-activo' : 'concepto-inactivo' }}">
                                        <i class="bi bi-{{ $c->activo ? 'check-circle-fill' : 'dash-circle-fill' }}"></i>
                                        {{ $c->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                                <p class="concepto-desc">{{ $c->descripcion ?? 'Sin descripción.' }}</p>
                                <div class="concepto-card-bottom">
                                    @if($c->monto_sugerido)
                                        <span class="concepto-monto">${{ number_format($c->monto_sugerido, 2) }}</span>
                                    @else
                                        <span class="concepto-monto sin-monto">Sin monto</span>
                                    @endif
                                    <button type="button" class="btn-edit-concepto"
                                        onclick="abrirEditConcepto({{ $c->id_concepto }},'{{ addslashes($c->nombre) }}','{{ addslashes($c->descripcion ?? '') }}','{{ $c->monto_sugerido }}',{{ $c->activo ? 1 : 0 }})">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p style="color:var(--text3);grid-column:1/-1;padding:20px 0;">No hay conceptos registrados aún.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             BLOQUE ALUMNO / TUTOR — formulario propio
        ══════════════════════════════════════════════════════════ --}}
        @else
        <div class="panel-card">
            <div class="panel-head">
                <div class="panel-head-icon"><i class="bi bi-credit-card-fill"></i></div>
                <div>
                    <h2>Registrar Pago</h2>
                    <p>Elige el concepto, ajusta el monto si pagas parcialmente y selecciona cómo pagas.</p>
                </div>
            </div>
            <div class="panel-body">
                <div class="info-banner" style="margin-bottom:20px;">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>Si pagas en <strong>efectivo</strong>, tu registro quedará como <strong>Pendiente</strong> hasta que el administrador lo confirme.
                       Si pagas en <strong>línea</strong>, el estado se actualiza automáticamente.</p>
                </div>

                <form id="registroPagoAlumno" method="POST" action="{{ route('pagos.store') }}" class="form-body" style="padding:0;">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group" style="grid-column:span 2;">
                            <label class="form-label" for="id_concepto_alumno">Concepto <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-bookmark input-icon"></i>
                                <select name="id_concepto" id="id_concepto_alumno" class="form-select" required>
                                    <option value="">Seleccione un concepto</option>
                                    @foreach($conceptos as $concepto)
                                        <option value="{{ $concepto->id_concepto }}"
                                            data-monto="{{ $concepto->monto_sugerido }}"
                                            data-nombre="{{ $concepto->nombre }}"
                                            {{ old('id_concepto') == $concepto->id_concepto ? 'selected' : '' }}>
                                            {{ $concepto->nombre }}
                                            @if($concepto->monto_sugerido) — ${{ number_format($concepto->monto_sugerido, 2) }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="concepto-hint" id="conceptoHintAlumno"></div>
                            @error('id_concepto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="monto_alumno">Monto a Pagar <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-currency-dollar input-icon"></i>
                                <input type="number" step="0.01" name="monto" id="monto_alumno" class="form-input"
                                       placeholder="0.00" value="{{ old('monto') }}" required>
                            </div>
                            <div class="concepto-hint">Puedes ajustar si vas a hacer un abono parcial.</div>
                            @error('monto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="fechaPago_alumno">Fecha <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-calendar-check input-icon"></i>
                                <input type="date" name="fechaPago" id="fechaPago_alumno" class="form-input"
                                       value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                            </div>
                            @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="grid-column:span 2;">
                            <label class="form-label" for="id_tipo_pago_alumno">Método de Pago <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-tag input-icon"></i>
                                <select name="id_tipo_pago" id="id_tipo_pago_alumno" class="form-select" required
                                        onchange="mostrarAvisoEfectivo(this.value)">
                                    <option value="">Seleccione el método</option>
                                    @foreach($tipos_pago as $tipo)
                                        <option value="{{ $tipo->id_tipo_pago }}"
                                            data-nombre="{{ strtolower($tipo->nombre_tipo) }}"
                                            {{ old('id_tipo_pago') == $tipo->id_tipo_pago ? 'selected' : '' }}>
                                            {{ $tipo->nombre_tipo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('id_tipo_pago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                            <div class="aviso-efectivo" id="avisoEfectivo">
                                <i class="bi bi-info-circle-fill"></i>
                                Tu pago en efectivo quedará <strong>Pendiente</strong> hasta que el administrador lo confirme.
                            </div>
                        </div>

                        <div class="form-group" style="grid-column:span 2;">
                            <label class="form-label" for="motivoPago_alumno">Nota adicional (opcional)</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-chat-left-text input-icon"></i>
                                <input type="text" name="motivoPago" id="motivoPago_alumno" class="form-input"
                                       placeholder="Ej: Mensualidad Mayo 2026" value="{{ old('motivoPago') }}">
                            </div>
                        </div>
                    </div>

                    <div style="background:var(--blue-bg);border:1px solid #BFDBFE;border-radius:var(--radius-sm);padding:14px 16px;display:flex;align-items:center;gap:12px;margin-top:8px;">
                        <input type="checkbox" name="pagar_en_linea" id="pagarEnLineaAlumno" value="1"
                               style="width:18px;height:18px;accent-color:#009ee3;flex-shrink:0;"
                               {{ old('pagar_en_linea') ? 'checked' : '' }}
                               onchange="toggleEnLineaAlumno(this)">
                        <div>
                            <label for="pagarEnLineaAlumno" style="cursor:pointer;font-weight:700;font-size:14px;color:#1e3a8a;display:flex;align-items:center;gap:8px;">
                                <i class="bi bi-credit-card-2-front-fill" style="color:#009ee3;"></i>
                                Pagar en línea con
                                <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp" alt="MercadoPago" style="height:18px;vertical-align:middle;">
                            </label>
                            <p style="margin:3px 0 0;font-size:12px;color:#3B82F6;">Tarjeta, OXXO, SPEI. El pago se confirma automáticamente.</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary" onclick="resetFormAlumno()"><i class="bi bi-x-lg"></i> Limpiar</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitAlumno"><i class="bi bi-check-lg"></i> Registrar Pago</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             SECCIÓN EXCLUSIVA TUTOR — alumnos relacionados
        ══════════════════════════════════════════════════════════ --}}
        @if($user->rol === 'tutor')
        <div class="panel-card">
            <div class="panel-head">
                <div class="panel-head-icon"><i class="bi bi-people-fill"></i></div>
                <div>
                    <h2>Mis Alumnos Relacionados</h2>
                    <p>Consulta y registra pagos de los alumnos que están bajo tu tutela.</p>
                </div>
            </div>

            @if($alumnosRelacionados->isEmpty())
                <div class="panel-body">
                    <div class="info-banner">
                        <i class="bi bi-info-circle-fill"></i>
                        <p>No tienes alumnos relacionados en este momento. Contacta al administrador.</p>
                    </div>
                </div>
            @else
                <div class="panel-body" style="display:flex;flex-direction:column;gap:12px;">
                    @foreach($alumnosRelacionados as $alumno)
                    <div class="alumno-card-tutor">
                        <div class="alumno-avatar-sm">
                            {{ strtoupper(substr($alumno->primer_nombre,0,1)) }}{{ strtoupper(substr($alumno->primer_apellido,0,1)) }}
                        </div>
                        <div class="alumno-info-sm">
                            <div class="nombre">{{ $alumno->nombre_alumno }}</div>
                            <div class="relacion"><i class="bi bi-person-heart"></i> {{ ucfirst($alumno->relacion) }}</div>
                        </div>
                        <button class="btn-ver-pagos" type="button"
                                onclick="abrirPagosAlumno({{ $alumno->id_alumno }}, '{{ addslashes($alumno->nombre_alumno) }}')">
                            <i class="bi bi-credit-card-2-front-fill"></i> Ver Pagos
                        </button>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ✅ FIX: Modal completamente nativo (sin Bootstrap.Modal),
             elimina el "Cargando pagos..." perpetuo causado por Bootstrap no disponible --}}
        <div class="modal-overlay" id="modalPagosAlumno">
            <div class="modal-box modal-lg">
                <div class="modal-header">
                    <h3><i class="bi bi-credit-card-2-front-fill"></i> Pagos de <span id="nombreAlumnoModal">—</span></h3>
                    <button class="modal-close" onclick="cerrarModal('modalPagosAlumno')">×</button>
                </div>
                <div class="modal-body" id="cuerpoModalPagos">
                    <div style="text-align:center;padding:40px;color:var(--text3);">
                        <i class="bi bi-hourglass-split" style="font-size:28px;"></i>
                        <p style="margin-top:10px;">Cargando pagos...</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // ✅ FIX: abrirPagosAlumno usa el sistema de modales nativo (modal-overlay / .active),
        //    NO usa new bootstrap.Modal() — eso causaba que el spinner nunca desapareciera
        //    cuando Bootstrap JS no estaba disponible en la página.
        function abrirPagosAlumno(idAlumno, nombreAlumno) {
            document.getElementById('nombreAlumnoModal').textContent = nombreAlumno;
            const cuerpo = document.getElementById('cuerpoModalPagos');
            cuerpo.innerHTML = `
                <div style="text-align:center;padding:40px;color:var(--text3);">
                    <i class="bi bi-hourglass-split" style="font-size:28px;animation:spin 1s linear infinite;"></i>
                    <p style="margin-top:10px;">Cargando pagos...</p>
                </div>`;

            // ✅ Usar modal nativo, no Bootstrap
            abrirModal('modalPagosAlumno');

            fetch(`/pagos/alumno/${idAlumno}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    cuerpo.innerHTML = '<p style="color:var(--red);padding:20px;">Error al cargar los pagos.</p>';
                    return;
                }
                renderPagosAlumno(cuerpo, data, idAlumno);
            })
            .catch(() => {
                cuerpo.innerHTML = '<p style="color:var(--red);padding:20px;">Error de conexión.</p>';
            });
        }

        // ✅ FIX: renderPagosAlumno usa p.estado_pago / p.motivo_pago (snake_case)
        //    en lugar de p.estadoPago / p.motivoPago (camelCase) — los datos de la BD
        //    siempre llegan en snake_case y el badge nunca se renderizaba correctamente.
        function renderPagosAlumno(cuerpo, data, idAlumno) {
            const pagos     = data.pagos;
            const tipos     = data.tipos_pago;
            const conceptos = data.conceptos;

            let filasHtml = '';
            if (!pagos || pagos.length === 0) {
                filasHtml = `<div class="info-banner" style="margin-bottom:0;">
                    <i class="bi bi-info-circle-fill"></i>
                    <p>Este alumno no tiene pagos registrados aún.</p>
                </div>`;
            } else {
                pagos.forEach(p => {
                    // ✅ FIX: usar p.estado_pago (snake_case), no p.estadoPago
                    const estado     = p.estado_pago ?? 'Pendiente';
                    const motivo     = p.motivo_pago ?? '';
                    const concepto   = p.nombre_concepto ?? p.nombre_tipo ?? 'Sin concepto';
                    const badgeClass = 'badge-' + estado;
                    const monto      = parseFloat(p.monto_total ?? p.monto ?? 0);
                    filasHtml += `
                    <div class="pago-row-tutor">
                        <div>
                            <div style="font-weight:700;color:var(--text);font-size:14px;">${concepto}</div>
                            <div style="font-size:12px;color:var(--text3);margin-top:3px;">
                                <i class="bi bi-calendar3"></i> ${p.fecha_pago ? p.fecha_pago.substring(0,10) : '—'}
                                ${motivo ? '  ·  ' + motivo : ''}
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:800;color:var(--red);font-size:16px;">$${monto.toFixed(2)}</div>
                            <span class="badge-estado ${badgeClass}">${estado}</span>
                        </div>
                    </div>`;
                });
            }

            let opcionesTipo = '<option value="">— Tipo de pago —</option>';
            tipos.forEach(t => { opcionesTipo += `<option value="${t.id_tipo_pago}">${t.nombre_tipo}</option>`; });

            let opcionesConcepto = '<option value="">— Concepto (opcional) —</option>';
            conceptos.forEach(c => {
                opcionesConcepto += `<option value="${c.id_concepto}" data-monto="${c.monto_sugerido ?? ''}">${c.nombre}${c.monto_sugerido ? ' — $'+parseFloat(c.monto_sugerido).toFixed(2) : ''}</option>`;
            });

            cuerpo.innerHTML = `
                <h6 style="font-weight:700;font-size:14px;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-list-ul" style="color:var(--red);"></i> Historial de Pagos
                </h6>
                ${filasHtml}

                <div class="form-nuevo-pago">
                    <h6><i class="bi bi-plus-circle-fill"></i> Registrar Nuevo Pago</h6>
                    <form id="formNuevoPago" onsubmit="submitNuevoPago(event, ${idAlumno})">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <label class="modal-label" style="margin-top:0;">Concepto</label>
                                <select class="modal-select" id="np_concepto" onchange="autoMonto(this)">${opcionesConcepto}</select>
                            </div>
                            <div>
                                <label class="modal-label" style="margin-top:0;">Tipo de Pago <span style="color:var(--red);">*</span></label>
                                <select class="modal-select" id="np_tipo" required>${opcionesTipo}</select>
                            </div>
                            <div>
                                <label class="modal-label" style="margin-top:0;">Monto <span style="color:var(--red);">*</span></label>
                                <input type="number" step="0.01" min="0.01" id="np_monto" class="modal-input" style="margin-top:0;" placeholder="0.00" required>
                            </div>
                            <div>
                                <label class="modal-label" style="margin-top:0;">Fecha <span style="color:var(--red);">*</span></label>
                                <input type="date" id="np_fecha" class="modal-input" style="margin-top:0;" value="${new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div>
                                <label class="modal-label" style="margin-top:0;">Estado</label>
                                <select class="modal-select" id="np_estado">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Completado">Completado</option>
                                </select>
                            </div>
                            <div>
                                <label class="modal-label" style="margin-top:0;">Nota adicional</label>
                                <input type="text" id="np_motivo" class="modal-input" style="margin-top:0;" placeholder="Opcional" maxlength="255">
                            </div>
                            <div style="grid-column:1/-1;display:flex;justify-content:flex-end;gap:10px;padding-top:8px;border-top:1px solid var(--border);margin-top:4px;">
                                <button type="submit" class="btn-modal-prim" style="max-width:180px;padding:10px;">
                                    <i class="bi bi-save2-fill"></i> Guardar Pago
                                </button>
                            </div>
                        </div>
                    </form>
                </div>`;
        }

        function autoMonto(select) {
            const opt   = select.options[select.selectedIndex];
            const monto = opt.dataset.monto;
            if (monto) document.getElementById('np_monto').value = parseFloat(monto).toFixed(2);
        }

        function submitNuevoPago(e, idAlumno) {
            e.preventDefault();
            const tipo   = document.getElementById('np_tipo').value;
            const monto  = document.getElementById('np_monto').value;
            const fecha  = document.getElementById('np_fecha').value;
            const estado = document.getElementById('np_estado').value;
            const motivo = document.getElementById('np_motivo').value;

            if (!tipo || !monto || !fecha) { alert('Por favor completa los campos obligatorios.'); return; }

            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            fetch('/pagos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id_alumno: idAlumno, id_tipo_pago: tipo, monto, fechaPago: fecha, estadoPago: estado, motivoPago: motivo || null })
            })
            .then(r => {
                if (r.ok || r.redirected || r.status === 302 || r.status === 200) {
                    abrirPagosAlumno(idAlumno, document.getElementById('nombreAlumnoModal').textContent);
                    return;
                }
                return r.json().then(d => {
                    alert(d.message ?? 'Error al guardar el pago.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-save2-fill"></i> Guardar Pago';
                });
            })
            .catch(() => {
                alert('Error de conexión.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save2-fill"></i> Guardar Pago';
            });
        }
        </script>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             TABLA DE PAGOS — todos los roles
        ══════════════════════════════════════════════════════════ --}}
        <div class="table-card">
            <div class="table-head-row">
                <h2>
                    <i class="bi bi-table"></i>
                    @if(in_array($user->rol, ['admin', 'sensei']))
                        Historial de Pagos ({{ count($pagos) }})
                    @else
                        Mis Pagos ({{ count($pagos) }})
                    @endif
                </h2>
                <div class="table-filters">
                    <select class="filter-select" id="filterEstado">
                        <option value="">Todos los estados</option>
                        <option value="Completado">Completado</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Suspendido">Suspendido</option>
                        <option value="Fallido">Fallido</option>
                    </select>
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="pagosTable">
                    <thead>
                        <tr>
                            @if(in_array($user->rol, ['admin', 'sensei']))
                                <th>Alumno</th>
                            @endif
                            <th>Concepto</th>
                            <th>Método</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $pago)
                            @php
                                $montoTotal  = $pago->monto_total  ?? $pago->monto;
                                $montoPagado = $pago->monto_pagado ?? 0;
                                $saldo       = $montoTotal - $montoPagado;
                                $porcentaje  = $pago->estado_pago === 'Completado'
                                    ? 100
                                    : ($montoTotal > 0 ? min(100, ($montoPagado / $montoTotal) * 100) : 0);
                                $concepto    = $pago->nombre_concepto ?? $pago->motivo_pago ?? '—';
                            @endphp
                            <tr>
                                @if(in_array($user->rol, ['admin', 'sensei']))
                                <td>
                                    <div class="student-cell">
                                        <div class="student-avatar">
                                            {{ strtoupper(substr($pago->nombre_alumno ?? 'A', 0, 1)) }}{{ strtoupper(substr(strstr($pago->nombre_alumno ?? ' A', ' '), 1, 1)) }}
                                        </div>
                                        <span class="student-name">{{ $pago->nombre_alumno ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                @endif

                                <td>
                                    <span style="font-weight:600;color:var(--text);">{{ $concepto }}</span>
                                    @if($pago->motivo_pago && $pago->nombre_concepto && $pago->motivo_pago !== $pago->nombre_concepto)
                                        <br><small style="color:var(--text3);">{{ $pago->motivo_pago }}</small>
                                    @endif
                                </td>

                                <td><span style="color:var(--text2);font-size:12px;">{{ $pago->nombre_tipo ?? 'N/A' }}</span></td>

                                <td><span class="amount">${{ number_format($montoTotal, 2) }}</span></td>

                                <td>
                                    <span style="font-weight:600;color:#16A34A;">
                                        ${{ number_format($pago->estado_pago === 'Completado' ? $montoTotal : $montoPagado, 2) }}
                                    </span>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width:{{ $porcentaje }}%;"></div>
                                    </div>
                                    <small style="color:var(--text3);font-size:11px;">{{ number_format($porcentaje, 0) }}%</small>
                                </td>

                                <td>
                                    @if($pago->estado_pago === 'Completado' || $saldo <= 0)
                                        <span class="saldo-badge saldo-completado"><i class="bi bi-check-circle-fill"></i> Saldado</span>
                                    @else
                                        <span class="saldo-badge saldo-pendiente">${{ number_format($saldo, 2) }}</span>
                                    @endif
                                </td>

                                <td style="font-size:12px;color:var(--text2);">{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>

                                <td>
                                    @php $estado = $pago->estado_pago; @endphp
                                    @if($estado == 'Completado')
                                        <span class="badge badge-success"><i class="bi bi-check-circle-fill"></i> Completado</span>
                                    @elseif($estado == 'Pendiente')
                                        <span class="badge badge-warning"><i class="bi bi-clock-fill"></i> Pendiente</span>
                                    @elseif($estado == 'Suspendido')
                                        <span class="badge badge-suspendido"><i class="bi bi-pause-circle-fill"></i> Suspendido</span>
                                    @elseif($estado == 'Rechazado')
                                        <span class="badge badge-danger"><i class="bi bi-x-circle-fill"></i> Rechazado</span>
                                    @else
                                        <span class="badge badge-danger">{{ $estado ?? 'N/A' }}</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="acciones-cell">
                                        @if(!in_array($pago->estado_pago, ['Completado', 'Suspendido']) && $saldo > 0)
                                            <a href="{{ route('pagos.pagar', $pago->id_pago) }}" class="btn-act btn-pagar">
                                                <i class="bi bi-credit-card-fill"></i> Pagar
                                            </a>
                                        @endif

                                        @if(!in_array($pago->estado_pago, ['Completado', 'Suspendido']) && $saldo > 0)
                                            <button type="button" class="btn-act btn-abono"
                                                onclick="abrirModalAbono({{ $pago->id_pago }},'{{ addslashes($pago->nombre_alumno ?? ($user->nombre . ' ' . $user->apaterno)) }}',{{ $montoTotal }},{{ $montoPagado }},{{ $saldo }},'{{ $user->rol }}')">
                                                <i class="bi bi-plus-circle"></i> Abono
                                            </button>
                                        @endif

                                        @if(in_array($user->rol, ['admin', 'sensei']) && in_array($pago->estado_pago, ['Pendiente', 'Suspendido', 'Rechazado']))
                                            <button type="button" class="btn-act btn-completar"
                                                onclick="confirmarCompletar({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-check-circle-fill"></i> Completar
                                            </button>
                                            <form id="formCompletar-{{ $pago->id_pago }}" method="POST"
                                                  action="{{ route('pagos.completar', $pago->id_pago) }}" style="display:none;">
                                                @csrf
                                            </form>
                                        @endif

                                        <button type="button" class="btn-act btn-abonos"
                                            onclick="verAbonos({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                            <i class="bi bi-list-ul"></i> Abonos
                                        </button>

                                        @if($pago->estado_pago === 'Completado')
                                            <span style="color:#16A34A;font-size:12px;display:flex;align-items:center;gap:4px;">
                                                <i class="bi bi-check-circle-fill"></i> Pagado
                                            </span>
                                        @endif

                                        @if($user->rol === 'sensei' && $pago->estado_pago === 'Pendiente')
                                            <button type="button" class="btn-act btn-suspender"
                                                onclick="confirmarSuspender({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-pause-circle"></i> Suspender
                                            </button>
                                        @endif

                                        @if($user->rol === 'admin' && $pago->estado_pago !== 'Completado')
                                            <button type="button" class="btn-act btn-eliminar"
                                                onclick="confirmarEliminar({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-trash3"></i> Eliminar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ in_array($user->rol, ['admin','sensei']) ? 9 : 8 }}"
                                    style="text-align:center;padding:48px;color:var(--text3);">
                                    <i class="bi bi-cash-coin" style="font-size:36px;display:block;margin-bottom:10px;"></i>
                                    @if(in_array($user->rol, ['admin', 'sensei']))
                                        No hay pagos registrados aún.
                                    @else
                                        No tienes pagos registrados. Usa el formulario de arriba para registrar uno.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- .pagos-wrap --}}
    </div>{{-- .content-wrapper --}}

    @include('includes.pie')
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL REGISTRAR ABONO
══════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalAbono">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-plus-circle"></i> Registrar Abono</h3>
            <button class="modal-close" onclick="cerrarModal('modalAbono')">×</button>
        </div>
        <div class="modal-body">
            <div class="resumen-abono">
                <div class="resumen-row">
                    <span class="resumen-label">Alumno</span>
                    <strong class="resumen-value" id="abonoAlumnoNombre">—</strong>
                </div>
                <div class="resumen-row">
                    <span class="resumen-label">Total a pagar</span>
                    <strong class="resumen-value" id="abonoMontoTotal">—</strong>
                </div>
                <div class="resumen-row">
                    <span class="resumen-label">Ya pagado</span>
                    <strong class="resumen-value" style="color:#16A34A;" id="abonoMontoPagado">—</strong>
                </div>
                <div class="resumen-row">
                    <span class="resumen-label">Saldo restante</span>
                    <strong class="resumen-value" style="color:#D97706;" id="abonoSaldo">—</strong>
                </div>
            </div>

            <form id="formAbono" method="POST" action="">
                @csrf
                <label class="modal-label" for="monto_abono">Monto del abono <span style="color:var(--red);">*</span></label>
                <input type="number" step="0.01" min="1" name="monto_abono" id="monto_abono" class="modal-input" placeholder="0.00" required>

                <label class="modal-label" for="tipo_abono">Tipo de abono <span style="color:var(--red);">*</span></label>
                <select name="tipo_abono" id="tipo_abono" class="modal-select" required onchange="cambiarTipoAbono(this.value)">
                    <option value="en_linea">En línea (MercadoPago)</option>
                    <option value="efectivo" id="opcionEfectivo">Efectivo</option>
                </select>

                <div class="aviso-efectivo-modal" id="avisoEfectivoAbono">
                    <i class="bi bi-info-circle-fill"></i>
                    Tu abono en efectivo quedará <strong>Pendiente</strong> hasta que el administrador lo confirme.
                </div>

                <div id="referenciaWrap" style="display:none;">
                    <label class="modal-label" for="referencia_abono">Referencia (opcional)</label>
                    <input type="text" name="referencia" id="referencia_abono" class="modal-input" placeholder="Número de comprobante">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-sec" onclick="cerrarModal('modalAbono')">Cancelar</button>
                    <button type="submit" class="btn-modal-prim">
                        <i class="bi bi-check-lg"></i> <span id="textoSubmitAbono">Registrar Abono</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL VER HISTORIAL DE ABONOS
══════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalVerAbonos">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-list-ul" style="color:var(--purple);"></i> Historial de Abonos</h3>
            <button class="modal-close" onclick="cerrarModal('modalVerAbonos')">×</button>
        </div>
        <div class="modal-body">
            <p id="tituloVerAbonos" style="color:var(--text3);font-size:13px;margin-bottom:14px;"></p>
            <div class="abonos-list" id="listaAbonos">
                <p style="text-align:center;color:var(--text3);padding:20px;">Cargando...</p>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL EDITAR CONCEPTO
══════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalEditConcepto">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-pencil-square" style="color:var(--blue);"></i> Editar Concepto</h3>
            <button class="modal-close" onclick="cerrarModal('modalEditConcepto')">×</button>
        </div>
        <div class="modal-body">
            <form id="formEditConcepto" method="POST" action="">
                @csrf
                @method('PUT')
                <label class="modal-label" style="margin-top:0;">Nombre <span style="color:var(--red);">*</span></label>
                <input type="text" name="nombre" id="edit_nombre" class="modal-input" required>

                <label class="modal-label">Monto Sugerido</label>
                <input type="number" step="0.01" name="monto_sugerido" id="edit_monto" class="modal-input" placeholder="0.00 (dejar vacío si no aplica)">

                <label class="modal-label">Descripción</label>
                <input type="text" name="descripcion" id="edit_desc" class="modal-input" placeholder="Opcional">

                <div style="display:flex;align-items:center;gap:10px;margin-top:16px;margin-bottom:8px;">
                    <input type="checkbox" name="activo" id="edit_activo" value="1" style="width:18px;height:18px;accent-color:var(--red);">
                    <label for="edit_activo" style="font-size:14px;font-weight:600;color:var(--text2);cursor:pointer;">
                        Concepto activo (aparece en formulario de pago)
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal-sec" onclick="cerrarModal('modalEditConcepto')">Cancelar</button>
                    <button type="submit" class="btn-modal-prim blue"><i class="bi bi-check-lg"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Formularios ocultos eliminar / suspender --}}
<form id="formEliminarPago"  method="POST" action="" style="display:none;">@csrf @method('DELETE')</form>
<form id="formSuspenderPago" method="POST" action="" style="display:none;">@csrf @method('PATCH')</form>

<script>
const ROL_USUARIO = '{{ $user->rol }}';

document.addEventListener('DOMContentLoaded', function () {
    @if(session('sessionInsertado'))
        Swal.fire({
            icon:              '{{ session('sessionInsertado') == 'true' ? 'success' : 'error' }}',
            title:             '{{ addslashes(session('mensaje')) }}',
            showConfirmButton: false,
            timer:             3000,
        });
    @endif

    // Filtro estado
    document.getElementById('filterEstado').addEventListener('change', function () {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#pagosTable tbody tr').forEach(row => {
            row.style.display = (!val || row.textContent.toLowerCase().includes(val)) ? '' : 'none';
        });
    });

    // Búsqueda
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#pagosTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
        });
    });

    // Concepto admin → autocompletar hint
    const selectAdmin = document.getElementById('id_concepto_admin');
    if (selectAdmin) {
        selectAdmin.addEventListener('change', function () {
            const opt   = this.options[this.selectedIndex];
            const monto = opt.dataset.monto;
            const hint  = document.getElementById('conceptoHintAdmin');
            if (monto && parseFloat(monto) > 0) {
                hint.innerHTML = `Monto del concepto: <strong>$${parseFloat(monto).toFixed(2)}</strong>`;
            } else {
                hint.innerHTML = opt.value ? '<span style="color:#D97706;">Este concepto no tiene monto definido.</span>' : '';
            }
        });
        if (selectAdmin.value) selectAdmin.dispatchEvent(new Event('change'));
    }

    // Concepto alumno → autocompletar monto
    const selectAlumno = document.getElementById('id_concepto_alumno');
    if (selectAlumno) {
        selectAlumno.addEventListener('change', function () {
            const opt   = this.options[this.selectedIndex];
            const monto = opt.dataset.monto;
            const hint  = document.getElementById('conceptoHintAlumno');
            const campo = document.getElementById('monto_alumno');
            if (monto) {
                campo.value = monto;
                hint.innerHTML = `Monto sugerido: <strong>$${parseFloat(monto).toFixed(2)}</strong>. Puedes reducirlo si pagas parcialmente.`;
            } else {
                hint.innerHTML = opt.value ? 'Sin monto sugerido. Ingresa el monto que vas a pagar.' : '';
            }
        });
    }

    // Toggle pagar en línea — admin
    const chkAdmin = document.getElementById('pagarEnLinea');
    if (chkAdmin) {
        chkAdmin.addEventListener('change', function () {
            const btn     = document.getElementById('btnSubmitAdmin');
            const estado  = document.getElementById('estadoWrapAdmin');
            const refWrap = document.getElementById('refAdminWrap');
            if (this.checked) {
                btn.innerHTML             = '<i class="bi bi-credit-card-2-front-fill"></i> Crear cargo y Pagar en línea';
                btn.style.backgroundColor = '#009ee3';
                btn.style.borderColor     = '#009ee3';
                if (estado)  estado.style.opacity = '0.4';
                if (refWrap) refWrap.style.display = 'none';
            } else {
                btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Cargo';
                btn.style.backgroundColor = '';
                btn.style.borderColor     = '';
                if (estado)  estado.style.opacity = '1';
                if (refWrap) refWrap.style.display = '';
            }
        });
    }
});

// ── Tabs ──────────────────────────────────────────────────────
function activarTab(tabId, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}

// ── Aviso efectivo alumno ─────────────────────────────────────
function mostrarAvisoEfectivo(valor) {
    const aviso   = document.getElementById('avisoEfectivo');
    if (!aviso) return;
    const select  = document.getElementById('id_tipo_pago_alumno');
    const opt     = select ? select.options[select.selectedIndex] : null;
    const nombre  = opt ? opt.dataset.nombre : '';
    const enLinea = document.getElementById('pagarEnLineaAlumno')?.checked;
    aviso.style.display = (!enLinea && nombre === 'efectivo') ? 'block' : 'none';
}

function toggleEnLineaAlumno(chk) {
    const btn   = document.getElementById('btnSubmitAlumno');
    const aviso = document.getElementById('avisoEfectivo');
    if (chk.checked) {
        btn.innerHTML             = '<i class="bi bi-credit-card-2-front-fill"></i> Ir a MercadoPago';
        btn.style.backgroundColor = '#009ee3';
        btn.style.borderColor     = '#009ee3';
        if (aviso) aviso.style.display = 'none';
    } else {
        btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Pago';
        btn.style.backgroundColor = '';
        btn.style.borderColor     = '';
        const select = document.getElementById('id_tipo_pago_alumno');
        mostrarAvisoEfectivo(select ? select.value : '');
    }
}

// ── Modales nativos ───────────────────────────────────────────
function abrirModal(id)  { document.getElementById(id).classList.add('active'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('active'); }

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) cerrarModal(this.id);
    });
});

// ── Modal abono ───────────────────────────────────────────────
function abrirModalAbono(idPago, nombreAlumno, montoTotal, montoPagado, saldo, rol) {
    document.getElementById('abonoAlumnoNombre').textContent  = nombreAlumno;
    document.getElementById('abonoMontoTotal').textContent    = '$' + parseFloat(montoTotal).toFixed(2);
    document.getElementById('abonoMontoPagado').textContent   = '$' + parseFloat(montoPagado).toFixed(2);
    document.getElementById('abonoSaldo').textContent         = '$' + parseFloat(saldo).toFixed(2);

    document.getElementById('formAbono').action = '{{ url("pagos") }}/' + idPago + '/abono';

    const opcionEfectivo = document.getElementById('opcionEfectivo');
    if (opcionEfectivo) opcionEfectivo.style.display = '';

    const selectTipo = document.getElementById('tipo_abono');
    if (selectTipo) { selectTipo.value = 'en_linea'; cambiarTipoAbono('en_linea'); }

    const montoInput = document.getElementById('monto_abono');
    if (montoInput) montoInput.value = '';

    const refInput = document.getElementById('referencia_abono');
    if (refInput) refInput.value = '';

    abrirModal('modalAbono');
}

function cambiarTipoAbono(valor) {
    const referenciaWrap = document.getElementById('referenciaWrap');
    const avisoEfectivo  = document.getElementById('avisoEfectivoAbono');
    const textoBtn       = document.getElementById('textoSubmitAbono');
    const btn            = document.querySelector('.btn-modal-prim');
    if (valor === 'efectivo') {
        if (referenciaWrap) referenciaWrap.style.display = '';
        if (avisoEfectivo)  avisoEfectivo.style.display  = 'block';
        if (textoBtn)       textoBtn.textContent          = 'Registrar Abono en Efectivo';
        if (btn) { btn.style.backgroundColor = 'var(--red)'; btn.style.borderColor = 'var(--red)'; }
    } else {
        if (referenciaWrap) referenciaWrap.style.display = 'none';
        if (avisoEfectivo)  avisoEfectivo.style.display  = 'none';
        if (textoBtn)       textoBtn.textContent          = 'Ir a MercadoPago';
        if (btn) { btn.style.backgroundColor = '#009ee3'; btn.style.borderColor = '#009ee3'; }
    }
}

// ── Ver historial de abonos ───────────────────────────────────
function verAbonos(idPago, concepto) {
    document.getElementById('tituloVerAbonos').textContent = 'Pago: ' + concepto;
    document.getElementById('listaAbonos').innerHTML =
        '<p style="text-align:center;color:var(--text3);padding:20px;">Cargando...</p>';
    abrirModal('modalVerAbonos');

    fetch('{{ url("pagos") }}/' + idPago + '/abonos', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(abonos => {
        const lista = document.getElementById('listaAbonos');
        if (!abonos.length) {
            lista.innerHTML = '<p style="text-align:center;color:var(--text3);padding:20px;">Sin abonos registrados.</p>';
            return;
        }
        lista.innerHTML = abonos.map(a => `
            <div class="abono-item">
                <div>
                    <span class="abono-tipo-badge tipo-${a.tipo_abono}">${a.tipo_abono === 'en_linea' ? '💳 En línea' : '💵 Efectivo'}</span>
                    <small style="display:block;color:var(--text3);margin-top:3px;">${a.fecha_abono ? a.fecha_abono.substring(0,10) : '—'}</small>
                    ${a.referencia ? `<small style="color:var(--text3);">${a.referencia}</small>` : ''}
                    ${a.registrado_por_nombre ? `<small style="color:var(--text3);display:block;">Por: ${a.registrado_por_nombre}</small>` : ''}
                </div>
                <strong style="color:var(--red);font-size:15px;">$${parseFloat(a.monto_abono).toFixed(2)}</strong>
            </div>
        `).join('');
    })
    .catch(() => {
        document.getElementById('listaAbonos').innerHTML =
            '<p style="text-align:center;color:var(--red);padding:20px;">Error al cargar los abonos.</p>';
    });
}

// ── Confirmar completar ───────────────────────────────────────
function confirmarCompletar(idPago, concepto) {
    Swal.fire({
        title: '¿Marcar como completado?',
        html: `Se marcará el pago de <strong>${concepto}</strong> como completado.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16A34A',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, completar',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('formCompletar-' + idPago).submit();
    });
}

// ── Confirmar eliminar ────────────────────────────────────────
function confirmarEliminar(idPago, concepto) {
    Swal.fire({
        title: '¿Eliminar pago?',
        html: `Se eliminará el pago <strong>${concepto}</strong>. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('formEliminarPago');
            form.action = '{{ url("pagos") }}/' + idPago;
            form.submit();
        }
    });
}

// ── Confirmar suspender ───────────────────────────────────────
function confirmarSuspender(idPago, concepto) {
    Swal.fire({
        title: '¿Suspender pago?',
        html: `Se suspenderá el pago <strong>${concepto}</strong>.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#D97706',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, suspender',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('formSuspenderPago');
            form.action = '{{ url("pagos") }}/' + idPago + '/suspender';
            form.submit();
        }
    });
}

// ── Abrir editar concepto ─────────────────────────────────────
function abrirEditConcepto(id, nombre, descripcion, monto, activo) {
    document.getElementById('edit_nombre').value  = nombre;
    document.getElementById('edit_desc').value    = descripcion;
    document.getElementById('edit_monto').value   = monto || '';
    document.getElementById('edit_activo').checked = activo == 1;
    document.getElementById('formEditConcepto').action = '{{ url("conceptos-pago") }}/' + id;
    abrirModal('modalEditConcepto');
}

// ── Reset form alumno ─────────────────────────────────────────
function resetFormAlumno() {
    const hint  = document.getElementById('conceptoHintAlumno');
    const aviso = document.getElementById('avisoEfectivo');
    const btn   = document.getElementById('btnSubmitAlumno');
    if (hint)  hint.innerHTML            = '';
    if (aviso) aviso.style.display       = 'none';
    if (btn) {
        btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Pago';
        btn.style.backgroundColor = '';
        btn.style.borderColor     = '';
    }
}

// Animación spinner inline
const style = document.createElement('style');
style.textContent = '@keyframes spin { to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>

</body>
</html>