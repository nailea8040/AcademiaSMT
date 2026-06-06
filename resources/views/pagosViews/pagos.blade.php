{{--
    resources/views/pagosViews/pagos.blade.php — REEMPLAZA COMPLETO

    Admin/sensei:
      - Formulario para registrar cargos a cualquier alumno/tutor
      - Panel de gestión de conceptos (crear/editar)
      - Tabla con TODOS los pagos

    Alumno/tutor:
      - Formulario para registrar su propio pago (elige concepto, ajusta monto)
      - Tabla con SUS pagos

    Tutor (adicional):
      - Sección "Mis Alumnos Relacionados"
      - Modal por alumno: historial de pagos + historial de abonos + registrar nuevo pago
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pagos - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ── Saldo badges ── */
        .saldo-badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600; }
        .saldo-pendiente  { background:#fff3e0;color:#e65100; }
        .saldo-completado { background:#e8f5e9;color:#2e7d32; }

        /* ── Progress bar ── */
        .progress-bar-wrap { width:100%;background:#f0f0f0;border-radius:10px;height:6px;margin-top:4px; }
        .progress-bar-fill { height:6px;border-radius:10px;background:linear-gradient(90deg,#e53935,#ff7043);transition:width 0.4s; }

        /* ── Modales propios (sin Bootstrap) ── */
        .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:white;border-radius:20px;padding:32px;width:100%;max-width:500px;box-shadow:0 8px 32px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto; }
        .modal-box.modal-lg { max-width:720px; }
        .modal-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px; }
        .modal-header h3 { font-size:18px;color:#2d3748;margin:0; }
        .modal-close { background:none;border:none;font-size:22px;cursor:pointer;color:#9e9e9e; }
        .modal-close:hover { color:#e53935; }

        /* ── Lista de abonos ── */
        .abonos-list { max-height:260px;overflow-y:auto;margin-bottom:16px; }
        .abono-item { display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px; }
        .abono-item:last-child { border-bottom:none; }
        .abono-tipo-badge { padding:2px 8px;border-radius:8px;font-size:11px;font-weight:600; }
        .tipo-efectivo { background:#e8f5e9;color:#2e7d32; }
        .tipo-en_linea { background:#e3f2fd;color:#1565c0; }

        /* ── Form abono modal ── */
        .form-abono { margin-top:16px;border-top:1px solid #f0f0f0;padding-top:16px; }
        .form-abono label { font-size:13px;font-weight:600;color:#4a5568;margin-bottom:4px;display:block; }
        .form-abono input, .form-abono select { width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;margin-bottom:12px;box-sizing:border-box; }
        .btn-abono-submit { width:100%;padding:12px;background:#e53935;color:white;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer; }
        .btn-abono-submit:hover { background:#c62828; }

        /* ── Botones de acciones ── */
        .btn-completar { display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none; }
        .btn-completar:hover { background:#c8e6c9; }
        .btn-abono { display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#fff3e0;color:#e65100;border:1px solid #ffcc80;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer; }
        .btn-abono:hover { background:#ffe0b2; }
        .acciones-cell { display:flex;flex-wrap:wrap;gap:6px;align-items:center; }

        /* ── Botón Eliminar (admin) ── */
        .btn-eliminar { display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#fce4ec;color:#c62828;border:1px solid #ef9a9a;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer; }
        .btn-eliminar:hover { background:#ffcdd2; }

        /* ── Botón Suspender (sensei) ── */
        .btn-suspender { display:inline-flex;align-items:center;gap:5px;padding:5px 12px;background:#fff8e1;color:#f57f17;border:1px solid #ffe082;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer; }
        .btn-suspender:hover { background:#fff3cd; }

        /* ── Badge Suspendido ── */
        .badge-suspendido { background:#fff8e1;color:#f57f17;border:1px solid #ffe082; }

        /* ── Badge Rechazado ── */
        .badge-rechazado { background:#fce4ec;color:#c62828;border:1px solid #ef9a9a; }

        /* ── Concepto hint ── */
        .concepto-hint { font-size:12px;color:#718096;margin-top:4px;min-height:18px; }
        .concepto-hint strong { color:#e53935; }

        /* ── Panel gestión conceptos ── */
        .conceptos-panel { margin-top:8px; }

        /* Grid de tarjetas — 4 por fila */
        .conceptos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 4px;
        }
        @media (max-width: 900px) { .conceptos-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 540px) { .conceptos-grid { grid-template-columns: 1fr; } }

        .concepto-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
            padding: 16px 18px; display: flex; flex-direction: column; gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); transition: box-shadow 0.2s, transform 0.2s;
            position: relative; overflow: hidden;
        }
        .concepto-card:hover { box-shadow: 0 6px 20px rgba(229,57,53,0.10); transform: translateY(-2px); }
        .concepto-card::before { content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#e53935,#ff7043); }
        .concepto-card.inactivo-card::before { background:#e2e8f0; }
        .concepto-card-top { display:flex;justify-content:space-between;align-items:flex-start;gap:8px; }
        .concepto-nombre { font-weight:700;color:#2d3748;font-size:14px;line-height:1.3;flex:1; }
        .concepto-estado { font-size:11px;padding:3px 9px;border-radius:20px;font-weight:600;white-space:nowrap; }
        .concepto-activo   { background:#e8f5e9;color:#2e7d32; }
        .concepto-inactivo { background:#fce4ec;color:#c62828; }
        .concepto-desc { font-size:12px;color:#9e9e9e;line-height:1.4;flex:1;min-height:32px; }
        .concepto-card-bottom { display:flex;justify-content:space-between;align-items:center;margin-top:4px;padding-top:10px;border-top:1px solid #f0f0f0; }
        .concepto-monto { font-size:20px;font-weight:800;color:#e53935; }
        .concepto-monto.sin-monto { font-size:14px;font-weight:500;color:#b0bec5; }
        .btn-edit-concepto { background:none;border:1px solid #cbd5e0;border-radius:8px;padding:5px 12px;cursor:pointer;color:#718096;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px;transition:background .15s,color .15s; }
        .btn-edit-concepto:hover { background:#fff0f0;color:#e53935;border-color:#e53935; }

        /* ── Tabs (solo admin) ── */
        .tabs-nav { display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid #e2e8f0; }
        .tab-btn { background:none;border:none;padding:10px 20px;font-size:14px;font-weight:600;color:#718096;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px; }
        .tab-btn.active { color:#e53935;border-bottom-color:#e53935; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }

        /* ── Info banner alumno ── */
        .info-banner { background:#e3f2fd;border-left:4px solid #1565c0;border-radius:8px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px; }
        .info-banner i { font-size:20px;color:#1565c0;margin-top:2px; }
        .info-banner p { margin:0;font-size:14px;color:#1a237e;line-height:1.5; }

        /* ── Aviso pago efectivo alumno ── */
        .aviso-efectivo { background:#fff8e1;border-left:4px solid #f9a825;border-radius:8px;padding:12px 16px;margin-top:8px;font-size:13px;color:#7a5c00;display:none; }

        /* ── Sección tutores: alumnos relacionados ── */
        .alumno-card-tutor {
            background:#fff; border-radius:16px; padding:18px 20px;
            box-shadow:0 2px 10px rgba(0,0,0,0.07);
            display:flex; align-items:center; justify-content:space-between; gap:14px;
            border-left:5px solid #e53935; transition:transform .15s,box-shadow .15s;
        }
        .alumno-card-tutor:hover { transform:translateY(-2px);box-shadow:0 6px 18px rgba(229,57,53,.15); }
        .alumno-avatar-sm {
            width:46px;height:46px;border-radius:50%;
            background:linear-gradient(135deg,#e53935,#b71c1c);
            color:#fff;font-weight:700;font-size:16px;
            display:flex;align-items:center;justify-content:center;flex-shrink:0;
        }
        .alumno-info-sm { flex:1; }
        .alumno-info-sm .nombre  { font-weight:700;color:#2d3748;font-size:15px; }
        .alumno-info-sm .relacion{ font-size:12px;color:#718096;margin-top:2px; }
        .btn-ver-pagos { background:#e53935;color:#fff;border:none;border-radius:10px;padding:8px 16px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;transition:background .2s; }
        .btn-ver-pagos:hover { background:#b71c1c; }

        /* ── Modal pagos alumno (tutor) ── */
        .badge-estado { padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700; }
        .badge-Pendiente  { background:#fff3e0;color:#e65100; }
        .badge-Completado { background:#e8f5e9;color:#2e7d32; }
        .badge-Cancelado  { background:#fce4ec;color:#c62828; }
        .badge-Suspendido { background:#f3e5f5;color:#6a1b9a; }
        .pago-row { border-bottom:1px solid #f0f0f0;padding:12px 0; }
        .pago-row:last-child { border-bottom:none; }
        .form-nuevo-pago { background:#f8f9fa;border-radius:12px;padding:18px;margin-top:16px; }
        .form-nuevo-pago h6 { color:#e53935;font-weight:700;margin-bottom:14px; }

        /* ── Tabs dentro del modal de alumno ── */
        .modal-tabs-nav { display:flex;border-bottom:2px solid #e2e8f0;margin-bottom:16px; }
        .modal-tab-btn { background:none;border:none;padding:8px 16px;font-size:13px;font-weight:600;color:#718096;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px; }
        .modal-tab-btn.active { color:#e53935;border-bottom-color:#e53935; }
        .modal-tab-content { display:none; }
        .modal-tab-content.active { display:block; }
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

        @if(session('mensaje'))
            @php $isSuccess = session('sessionInsertado') == 'true'; @endphp
            <div class="alert {{ $isSuccess ? 'alert-success' : 'alert-danger' }}">
                <i class="bi bi-{{ $isSuccess ? 'check-circle-fill' : 'x-circle-fill' }} alert-icon"></i>
                <div><strong>{{ $isSuccess ? '¡Éxito!' : '¡Error!' }}</strong> {{ session('mensaje') }}</div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             BLOQUE ADMIN / SENSEI
        ══════════════════════════════════════════════════════════ --}}
        @if(in_array($user->rol, ['admin', 'sensei']))

        <div class="form-container form-theme-red">
            <div class="form-header">
                <h2><i class="bi bi-credit-card-fill"></i> Panel de Pagos</h2>
                <p>Registra cargos para los alumnos y gestiona el catálogo de conceptos.</p>
            </div>

            <div class="tabs-nav">
                <button class="tab-btn active" onclick="activarTab('tab-registro', this)">
                    <i class="bi bi-plus-circle"></i> Registrar Cargo
                </button>
                <button class="tab-btn" onclick="activarTab('tab-conceptos', this)">
                    <i class="bi bi-bookmarks"></i> Conceptos de Pago
                </button>
            </div>

            {{-- TAB 1: Registrar nuevo cargo --}}
            <div id="tab-registro" class="tab-content active">
                <form id="registroPago" method="POST" action="{{ route('pagos.store') }}" class="form-body">
                    @csrf

                    <h3 class="section-title-header">
                        <i class="bi bi-person-circle"></i> Alumno o Tutor Destinatario
                    </h3>
                    <div class="form-grid full-width">
                        <div class="form-group">
                            <label class="form-label" for="id_alumno">
                                Destinatario <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-person-badge input-icon"></i>
                                <select name="id_alumno" id="id_alumno" class="form-select" required>
                                    <option value="">Seleccione un alumno</option>
                                    @foreach($alumnos as $alumno)
                                        <option value="{{ $alumno->id_usuario }}"
                                            {{ old('id_alumno') == $alumno->id_usuario ? 'selected' : '' }}>
                                            {{ $alumno->nombre_completo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('id_alumno')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <h3 class="section-title-header">
                        <i class="bi bi-receipt-cutoff"></i> Detalles del Cargo
                    </h3>
                    <div class="form-grid">

                        <div class="form-group">
                            <label class="form-label" for="id_concepto_admin">
                                Concepto <span class="required">*</span>
                            </label>
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
                            <label class="form-label" for="id_tipo_pago">
                                Método de Pago <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-tag input-icon"></i>
                                <select name="id_tipo_pago" id="id_tipo_pago" class="form-select" required>
                                    <option value="">Seleccione el método</option>
                                    @foreach($tipos_pago as $tipo)
                                        <option value="{{ $tipo->id_tipo_pago }}"
                                            {{ old('id_tipo_pago') == $tipo->id_tipo_pago ? 'selected' : '' }}>
                                            {{ $tipo->nombre_tipo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('id_tipo_pago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="fechaPago">
                                Fecha <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-calendar-check input-icon"></i>
                                <input type="date" name="fechaPago" id="fechaPago" class="form-input"
                                       value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                            </div>
                            @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" id="estadoWrapAdmin">
                            <label class="form-label" for="estadoPago">
                                Estado <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-check-circle input-icon"></i>
                                <select name="estadoPago" id="estadoPago" class="form-select" required>
                                    <option value="Pendiente"  {{ old('estadoPago', 'Pendiente') == 'Pendiente'  ? 'selected' : '' }}>Pendiente (el alumno pagará después)</option>
                                    <option value="Completado" {{ old('estadoPago') == 'Completado' ? 'selected' : '' }}>Completado (recibí efectivo ahora)</option>
                                </select>
                            </div>
                            @error('estadoPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="motivoPago_admin">Nota / Detalle adicional</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-chat-left-text input-icon"></i>
                                <input type="text" name="motivoPago" id="motivoPago_admin" class="form-input"
                                       placeholder="Ej: Mensualidad Mayo 2026"
                                       value="{{ old('motivoPago') }}">
                            </div>
                        </div>

                        <div class="form-group" id="refAdminWrap">
                            <label class="form-label" for="referenciaPago">Referencia (opcional)</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-receipt input-icon"></i>
                                <input type="text" name="referenciaPago" id="referenciaPago" class="form-input"
                                       placeholder="Número de recibo o voucher"
                                       value="{{ old('referenciaPago') }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px;">
                            <input type="checkbox" name="pagar_en_linea" id="pagarEnLinea" value="1"
                                   style="width:18px;height:18px;accent-color:#009ee3;"
                                   {{ old('pagar_en_linea') ? 'checked' : '' }}>
                            <span>
                                <i class="bi bi-credit-card-2-front-fill" style="color:#009ee3;font-size:18px;"></i>
                                Pagar en línea ahora con
                                <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp"
                                     alt="MercadoPago" style="height:20px;vertical-align:middle;margin-left:4px;">
                            </span>
                        </label>
                        <p style="margin-left:28px;margin-top:4px;font-size:12px;color:#718096;">
                            Se creará el cargo y se abrirá la página de pago. El estado se actualiza automáticamente al completarse.
                        </p>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitAdmin">
                            <i class="bi bi-check-lg"></i> Registrar Cargo
                        </button>
                    </div>
                </form>
            </div>

            {{-- TAB 2: Gestión de conceptos de pago --}}
            <div id="tab-conceptos" class="tab-content">

                <form method="POST" action="{{ route('conceptos.store') }}" class="form-body" style="margin-bottom:24px;">
                    @csrf
                    <h3 class="section-title-header">
                        <i class="bi bi-plus-circle"></i> Agregar Nuevo Concepto
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="nuevo_nombre">Nombre <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-bookmark input-icon"></i>
                                <input type="text" name="nombre" id="nuevo_nombre" class="form-input"
                                       placeholder="Ej: Torneo Regional" required>
                            </div>
                            @error('nombre')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nuevo_monto">Monto Sugerido</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-currency-dollar input-icon"></i>
                                <input type="number" step="0.01" name="monto_sugerido" id="nuevo_monto"
                                       class="form-input" placeholder="0.00 (opcional)">
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label" for="nuevo_desc">Descripción (opcional)</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-text-left input-icon"></i>
                                <input type="text" name="descripcion" id="nuevo_desc" class="form-input"
                                       placeholder="Breve descripción del concepto">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Agregar Concepto
                        </button>
                    </div>
                </form>

                <div class="form-body" style="padding-top:0;margin-top:0;">
                    <h3 class="section-title-header" style="margin-top:0;">
                        <i class="bi bi-list-ul"></i> Conceptos Registrados
                    </h3>
                    <div class="conceptos-panel">
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
                                            onclick="abrirEditConcepto(
                                                {{ $c->id_concepto }},
                                                '{{ addslashes($c->nombre) }}',
                                                '{{ addslashes($c->descripcion ?? '') }}',
                                                '{{ $c->monto_sugerido }}',
                                                {{ $c->activo ? 1 : 0 }}
                                            )">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p style="color:#9e9e9e;text-align:center;padding:20px;grid-column:1/-1;">
                                    No hay conceptos registrados aún.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             BLOQUE ALUMNO / TUTOR
        ══════════════════════════════════════════════════════════ --}}
        @else

        <div class="form-container form-theme-red">
            <div class="form-header">
                <h2><i class="bi bi-credit-card-fill"></i> Registrar Pago</h2>
                <p>Elige el concepto, ajusta el monto si vas a hacer un pago parcial y selecciona cómo vas a pagar.</p>
            </div>

            <form id="registroPagoAlumno" method="POST" action="{{ route('pagos.store') }}" class="form-body">
                @csrf

                <h3 class="section-title-header">
                    <i class="bi bi-bookmark"></i> Concepto del Pago
                </h3>
                <div class="form-grid">

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="id_concepto_alumno">
                            Concepto <span class="required">*</span>
                        </label>
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
                        <label class="form-label" for="monto_alumno">
                            Monto a Pagar <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-currency-dollar input-icon"></i>
                            <input type="number" step="0.01" name="monto" id="monto_alumno"
                                   class="form-input" placeholder="0.00"
                                   value="{{ old('monto') }}" required>
                        </div>
                        <div class="concepto-hint">Puedes ajustar el monto si vas a hacer un abono parcial.</div>
                        @error('monto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaPago_alumno">
                            Fecha <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-calendar-check input-icon"></i>
                            <input type="date" name="fechaPago" id="fechaPago_alumno" class="form-input"
                                   value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                        </div>
                        @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="id_tipo_pago_alumno">
                            Método de Pago <span class="required">*</span>
                        </label>
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
                            <strong>Pago en efectivo:</strong> Tu registro quedará como <strong>Pendiente</strong>
                            hasta que el administrador o sensei lo confirme presencialmente.
                        </div>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="motivoPago_alumno">Nota adicional (opcional)</label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-chat-left-text input-icon"></i>
                            <input type="text" name="motivoPago" id="motivoPago_alumno" class="form-input"
                                   placeholder="Ej: Mensualidad Mayo 2026"
                                   value="{{ old('motivoPago') }}">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px;">
                        <input type="checkbox" name="pagar_en_linea" id="pagarEnLineaAlumno" value="1"
                               style="width:18px;height:18px;accent-color:#009ee3;"
                               {{ old('pagar_en_linea') ? 'checked' : '' }}
                               onchange="toggleEnLineaAlumno(this)">
                        <span>
                            <i class="bi bi-credit-card-2-front-fill" style="color:#009ee3;font-size:18px;"></i>
                            Pagar en línea con
                            <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp"
                                 alt="MercadoPago" style="height:20px;vertical-align:middle;margin-left:4px;">
                        </span>
                    </label>
                    <p style="margin-left:28px;margin-top:4px;font-size:12px;color:#718096;">
                        Al marcar esta opción serás redirigido al checkout de MercadoPago (tarjeta, OXXO, SPEI, etc.).
                        El pago se confirmará automáticamente.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary" onclick="resetFormAlumno()">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitAlumno">
                        <i class="bi bi-check-lg"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>

        @endif

        {{-- ══════════════════════════════════════════════════════════
             SECCIÓN EXCLUSIVA TUTOR — Mis Alumnos Relacionados
        ══════════════════════════════════════════════════════════ --}}
        @if($user->rol === 'tutor')

        <div class="table-container" style="margin-bottom:1.5rem;">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="bi bi-people-fill"></i> Mis Alumnos a Cargo
                </h2>
            </div>

            @if($alumnosRelacionados->isEmpty())
                <div class="alert alert-info d-flex align-items-center gap-2 m-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>No tienes alumnos a cargo en este momento.</span>
                </div>
            @else
                <div class="d-flex flex-column gap-3 p-3">
                    @foreach($alumnosRelacionados as $alumno)
                    <div class="alumno-card-tutor">
                        <div class="alumno-avatar-sm">
                            {{ strtoupper(substr($alumno->primer_nombre,0,1)) }}{{ strtoupper(substr($alumno->primer_apellido,0,1)) }}
                        </div>
                        <div class="alumno-info-sm">
                            <div class="nombre">{{ $alumno->nombre_alumno }}</div>
                            <div class="relacion">
                                <i class="bi bi-person-heart"></i> {{ ucfirst($alumno->relacion) }}
                            </div>
                        </div>
                        <button class="btn-ver-pagos" type="button"
                                onclick="abrirPagosAlumno({{ $alumno->id_alumno }}, '{{ addslashes($alumno->nombre_alumno) }}')">
                            <i class="bi bi-credit-card-2-front-fill me-1"></i> Ver Pagos
                        </button>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Modal pagos por alumno (sistema nativo, sin Bootstrap.Modal) ── --}}
        <div class="modal-overlay" id="modalPagosAlumno">
            <div class="modal-box modal-lg">
                <div class="modal-header">
                    <h3>
                        <i class="bi bi-credit-card-2-front-fill me-2" style="color:#e53935;"></i>
                        Pagos de <span id="nombreAlumnoModal">—</span>
                    </h3>
                    <button class="modal-close" onclick="cerrarModal('modalPagosAlumno')">×</button>
                </div>
                <div id="cuerpoModalPagos" style="padding:24px;">
                    <div style="text-align:center;padding:40px;color:#9e9e9e;">
                        <i class="bi bi-hourglass-split" style="font-size:28px;"></i>
                        <p style="margin-top:10px;">Cargando pagos...</p>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // ── Abrir modal (sistema nativo, sin Bootstrap.Modal) ─────────────────
        function abrirPagosAlumno(idAlumno, nombreAlumno) {
            document.getElementById('nombreAlumnoModal').textContent = nombreAlumno;
            const cuerpo = document.getElementById('cuerpoModalPagos');
            cuerpo.innerHTML = `
                <div style="text-align:center;padding:40px;color:#9e9e9e;">
                    <i class="bi bi-hourglass-split" style="font-size:28px;"></i>
                    <p style="margin-top:10px;">Cargando pagos...</p>
                </div>`;

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
                    cuerpo.innerHTML = '<p style="color:#e53935;padding:20px;">Error al cargar los pagos.</p>';
                    return;
                }
                renderPagosAlumno(cuerpo, data, idAlumno);
            })
            .catch(() => {
                cuerpo.innerHTML = '<p style="color:#e53935;padding:20px;">Error de conexión.</p>';
            });
        }

        // ── Renderizar contenido del modal con tabs ───────────────────────────
        function renderPagosAlumno(cuerpo, data, idAlumno) {
            const pagos     = data.pagos;
            const tipos     = data.tipos_pago;
            const conceptos = data.conceptos;

            // ── Tab 1: Historial de pagos con botón pagar + ver abonos ────────
            let filasHtml = '';
            if (!pagos || pagos.length === 0) {
                filasHtml = `<div class="alert alert-info d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Este alumno no tiene pagos registrados.</span>
                </div>`;
            } else {
                pagos.forEach(p => {
                    const estado     = p.estado_pago ?? 'Pendiente';
                    const motivo     = p.motivo_pago ?? '';
                    const concepto   = p.nombre_concepto ?? p.nombre_tipo ?? 'Sin concepto';
                    const badgeClass = 'badge-' + estado;
                    const montoTotal = parseFloat(p.monto_total ?? p.monto ?? 0);
                    const montoPag   = parseFloat(p.monto_pagado ?? 0);
                    const saldo      = Math.max(0, montoTotal - montoPag);
                    const pct        = montoTotal > 0 ? Math.min(100, (montoPag / montoTotal) * 100) : 0;
                    const esPendiente= estado === 'Pendiente' && saldo > 0;

                    const barraHtml = `
                        <div style="margin-top:6px;">
                            <div style="display:flex;justify-content:space-between;font-size:11px;color:#718096;margin-bottom:3px;">
                                <span>Pagado: <strong style="color:#2e7d32;">$${montoPag.toFixed(2)}</strong></span>
                                <span>Saldo: <strong style="color:${saldo > 0 ? '#e53935':'#2e7d32'};">$${saldo.toFixed(2)}</strong></span>
                            </div>
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:${pct.toFixed(0)}%;"></div></div>
                        </div>`;

                    const botonesHtml = esPendiente ? `
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
                            <a href="/pagos/${p.id_pago}/pagar" class="btn-completar" style="font-size:12px;">
                                <i class="bi bi-credit-card-fill"></i> Pagar en línea
                            </a>
                            <button type="button" class="btn-abono" style="font-size:12px;"
                                onclick="verAbonosModal(${p.id_pago}, '${concepto.replace(/'/g,"\\'")}')">
                                <i class="bi bi-list-ul"></i> Historial de abonos
                            </button>
                        </div>` : `
                        <div style="margin-top:8px;">
                            <button type="button" class="btn-abono" style="font-size:12px;"
                                onclick="verAbonosModal(${p.id_pago}, '${concepto.replace(/'/g,"\\'")}')">
                                <i class="bi bi-list-ul"></i> Historial de abonos
                            </button>
                        </div>`;

                    filasHtml += `
                    <div class="pago-row">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                            <div style="flex:1;">
                                <div class="fw-bold" style="color:#2d3748;font-size:14px;">${concepto}</div>
                                <div style="font-size:12px;color:#718096;margin-top:3px;">
                                    <i class="bi bi-calendar3"></i>
                                    ${p.fecha_pago ? p.fecha_pago.substring(0,10) : '—'}
                                    ${motivo ? '  ·  ' + motivo : ''}
                                </div>
                                ${barraHtml}
                            </div>
                            <div style="text-align:right;flex-shrink:0;">
                                <div style="font-weight:800;color:#e53935;font-size:16px;">$${montoTotal.toFixed(2)}</div>
                                <span class="badge-estado ${badgeClass}">${estado}</span>
                            </div>
                        </div>
                        ${botonesHtml}
                    </div>`;
                });
            }

            // ── Tab 2: Registrar nuevo pago ───────────────────────────────────
            let opcionesTipo = '<option value="">— Tipo de pago —</option>';
            tipos.forEach(t => { opcionesTipo += `<option value="${t.id_tipo_pago}">${t.nombre_tipo}</option>`; });

            let opcionesConcepto = '<option value="">— Concepto —</option>';
            conceptos.forEach(c => {
                opcionesConcepto += `<option value="${c.id_concepto}" data-monto="${c.monto_sugerido ?? ''}">${c.nombre}${c.monto_sugerido ? ' — $'+parseFloat(c.monto_sugerido).toFixed(2) : ''}</option>`;
            });

            cuerpo.innerHTML = `
                <div class="modal-tabs-nav">
                    <button class="modal-tab-btn active" onclick="activarModalTab('mtab-pagos', this)">
                        <i class="bi bi-list-ul"></i> Pagos del Alumno
                    </button>
                    <button class="modal-tab-btn" onclick="activarModalTab('mtab-nuevo', this)">
                        <i class="bi bi-plus-circle"></i> Registrar Nuevo Pago
                    </button>
                </div>

                <div id="mtab-pagos" class="modal-tab-content active">
                    ${filasHtml}
                </div>

                <div id="mtab-nuevo" class="modal-tab-content">
                    <div class="form-nuevo-pago">
                        <h6><i class="bi bi-plus-circle-fill me-1"></i> Registrar Nuevo Pago</h6>
                        <form id="formNuevoPago" onsubmit="submitNuevoPago(event, ${idAlumno})">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Concepto</label>
                                    <select class="form-select" id="np_concepto" onchange="autoMonto(this)">
                                        ${opcionesConcepto}
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tipo de Pago</label>
                                    <select class="form-select" id="np_tipo" required>${opcionesTipo}</select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" min="0.01" id="np_monto"
                                               class="form-control" placeholder="0.00" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                    <input type="date" id="np_fecha" class="form-control"
                                           value="${new Date().toISOString().split('T')[0]}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Estado</label>
                                    <select class="form-select" id="np_estado">
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="Completado">Completado</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Nota adicional</label>
                                    <input type="text" id="np_motivo" class="form-control"
                                           placeholder="Opcional" maxlength="255">
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-danger fw-bold px-4"
                                            style="border-radius:10px;">
                                        <i class="bi bi-save2-fill me-1"></i> Guardar Pago
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>`;
        }

        // ── Tabs dentro del modal de alumno ───────────────────────────────────
        function activarModalTab(tabId, btn) {
            document.querySelectorAll('.modal-tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.modal-tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            btn.classList.add('active');
        }

        // ── Ver historial de abonos de un pago específico ─────────────────────
        function verAbonosModal(idPago, concepto) {
            document.getElementById('tituloVerAbonos').textContent = 'Abonos del pago: ' + concepto;
            document.getElementById('listaAbonos').innerHTML =
                '<p style="text-align:center;color:#9e9e9e;padding:20px;">Cargando...</p>';

            abrirModal('modalVerAbonos');

            fetch('{{ url("pagos") }}/' + idPago + '/abonos', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(abonos => {
                const lista = document.getElementById('listaAbonos');
                if (!abonos.length) {
                    lista.innerHTML = '<p style="text-align:center;color:#9e9e9e;padding:20px;">Sin abonos registrados.</p>';
                    return;
                }
                lista.innerHTML = abonos.map(a => `
                    <div class="abono-item">
                        <div>
                            <span class="abono-tipo-badge tipo-${a.tipo_abono}">${a.tipo_abono === 'en_linea' ? '💳 En línea' : '💵 Efectivo'}</span>
                            <small style="display:block;color:#718096;margin-top:2px;">${a.fecha_abono ? a.fecha_abono.substring(0,10) : '—'}</small>
                            ${a.referencia ? `<small style="color:#9e9e9e;">${a.referencia}</small>` : ''}
                            ${a.registrado_por_nombre ? `<small style="color:#b0bec5;display:block;">Por: ${a.registrado_por_nombre}</small>` : ''}
                        </div>
                        <strong style="color:#e53935;">$${parseFloat(a.monto_abono).toFixed(2)}</strong>
                    </div>
                `).join('');
            })
            .catch(() => {
                document.getElementById('listaAbonos').innerHTML =
                    '<p style="text-align:center;color:#e53935;padding:20px;">Error al cargar los abonos.</p>';
            });
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

            if (!tipo || !monto || !fecha) {
                alert('Por favor completa los campos obligatorios.');
                return;
            }

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
                body: JSON.stringify({
                    id_alumno:    idAlumno,
                    id_tipo_pago: tipo,
                    monto:        monto,
                    fechaPago:    fecha,
                    estadoPago:   estado,
                    motivoPago:   motivo || null,
                })
            })
            .then(r => {
                if (r.ok || r.redirected || r.status === 302 || r.status === 200) {
                    abrirPagosAlumno(idAlumno, document.getElementById('nombreAlumnoModal').textContent);
                    return;
                }
                return r.json().then(d => {
                    alert(d.message ?? 'Error al guardar el pago.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-save2-fill me-1"></i> Guardar Pago';
                });
            })
            .catch(() => {
                alert('Error de conexión.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save2-fill me-1"></i> Guardar Pago';
            });
        }
        </script>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             TABLA DE PAGOS (todos los roles)
        ══════════════════════════════════════════════════════════ --}}
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
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
                        <option value="Fallido">Fallido</option>
                    </select>
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Buscar...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="pagosTable">
                    <thead>
                        <tr>
                            @if(in_array($user->rol, ['admin', 'sensei']))
                                <th>Alumno / Tutor</th>
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
                                    <span style="font-weight:600;color:#2d3748;">{{ $concepto }}</span>
                                    @if($pago->motivo_pago && $pago->nombre_concepto && $pago->motivo_pago !== $pago->nombre_concepto)
                                        <br><small style="color:#9e9e9e;">{{ $pago->motivo_pago }}</small>
                                    @endif
                                </td>

                                <td>{{ $pago->nombre_tipo ?? 'N/A' }}</td>

                                <td><span class="amount">${{ number_format($montoTotal, 2) }}</span></td>

                                <td>
                                    <span style="font-weight:600;color:#4caf50;">
                                        ${{ number_format($pago->estado_pago === 'Completado' ? $montoTotal : $montoPagado, 2) }}
                                    </span>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width:{{ $porcentaje }}%"></div>
                                    </div>
                                    <small style="color:#9e9e9e;font-size:11px;">{{ number_format($porcentaje, 0) }}%</small>
                                </td>

                                <td>
                                    @if($pago->estado_pago === 'Completado')
                                        <span class="saldo-badge saldo-completado">
                                            <i class="bi bi-check-circle-fill"></i> Saldado
                                        </span>
                                    @elseif($saldo <= 0)
                                        <span class="saldo-badge saldo-completado">
                                            <i class="bi bi-check-circle-fill"></i> Saldado
                                        </span>
                                    @else
                                        <span class="saldo-badge saldo-pendiente">
                                            ${{ number_format($saldo, 2) }}
                                        </span>
                                    @endif
                                </td>

                                <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>

                                <td>
                                    @php $estado = $pago->estado_pago; @endphp
                                    @if($estado == 'Completado')
                                        <span class="badge badge-success">Completado</span>
                                    @elseif($estado == 'Pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @elseif($estado == 'Suspendido')
                                        <span class="badge badge-suspendido">
                                            <i class="bi bi-pause-circle-fill"></i> Suspendido
                                        </span>
                                    @elseif($estado == 'Rechazado')
                                        <span class="badge badge-rechazado">
                                            <i class="bi bi-x-circle-fill"></i> Rechazado
                                        </span>
                                    @else
                                        <span class="badge badge-danger">{{ $estado ?? 'N/A' }}</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="acciones-cell">
                                        @if(!in_array($pago->estado_pago, ['Completado', 'Suspendido']) && $saldo > 0)
                                            <a href="{{ route('pagos.pagar', $pago->id_pago) }}"
                                               class="btn btn-primary"
                                               style="padding:5px 12px;font-size:12px;display:inline-flex;align-items:center;gap:5px;">
                                                <i class="bi bi-credit-card-fill"></i> Pagar
                                            </a>
                                        @endif

                                        @if(!in_array($pago->estado_pago, ['Completado', 'Suspendido']) && $saldo > 0)
                                            <button type="button" class="btn-abono"
                                                onclick="abrirModalAbono(
                                                    {{ $pago->id_pago }},
                                                    '{{ addslashes($pago->nombre_alumno ?? ($user->nombre . ' ' . $user->apaterno)) }}',
                                                    {{ $montoTotal }},
                                                    {{ $montoPagado }},
                                                    {{ $saldo }},
                                                    '{{ $user->rol }}'
                                                )">
                                                <i class="bi bi-plus-circle"></i> Abono
                                            </button>
                                        @endif

                                        @if(in_array($user->rol, ['admin', 'sensei']) && in_array($pago->estado_pago, ['Pendiente', 'Suspendido', 'Rechazado']))
                                            <button type="button" class="btn-completar"
                                                onclick="confirmarCompletar({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-check-circle-fill"></i> Completar
                                            </button>
                                            <form id="formCompletar-{{ $pago->id_pago }}" method="POST"
                                                  action="{{ route('pagos.completar', $pago->id_pago) }}"
                                                  style="display:none;">
                                                @csrf
                                            </form>
                                        @endif

                                        <button type="button"
                                            class="btn-abono"
                                            style="background:#f3e5f5;color:#6a1b9a;border-color:#ce93d8;"
                                            onclick="verAbonos({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                            <i class="bi bi-list-ul"></i> Abonos
                                        </button>

                                        @if($pago->estado_pago === 'Completado')
                                            <span style="color:#4caf50;font-size:12px;display:flex;align-items:center;gap:4px;">
                                                <i class="bi bi-check-circle-fill"></i> Pagado
                                            </span>
                                        @endif

                                        @if($user->rol === 'sensei' && $pago->estado_pago === 'Pendiente')
                                            <button type="button" class="btn-suspender"
                                                onclick="confirmarSuspender({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-pause-circle"></i> Suspender
                                            </button>
                                        @endif

                                        @if($user->rol === 'admin' && $pago->estado_pago !== 'Completado')
                                            <button type="button" class="btn-eliminar"
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
                                    class="text-center" style="padding:40px;color:#9e9e9e;">
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
            {{-- Controles de paginación --}}
            <div id="paginacionPagos" style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:0 4px;">
                <button id="btnAnteriorPagos" onclick="cambiarPaginaPagos(-1)"
                    style="display:flex;align-items:center;gap:6px;padding:8px 18px;border-radius:10px;border:1.5px solid #e53935;background:#fff;color:#e53935;font-weight:600;cursor:pointer;">
                    <i class="bi bi-chevron-left"></i> Anterior
                </button>
                <span id="infoPaginaPagos" style="font-size:14px;font-weight:600;color:#424242;"></span>
                <button id="btnSiguientePagos" onclick="cambiarPaginaPagos(1)"
                    style="display:flex;align-items:center;gap:6px;padding:8px 18px;border-radius:10px;border:1.5px solid #e53935;background:#fff;color:#e53935;font-weight:600;cursor:pointer;">
                    Siguiente <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    @include('includes.pie')
</div>

{{-- ══════════════════════════════════════════════════════════
     MODAL REGISTRAR ABONO
══════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalAbono">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-plus-circle" style="color:#e65100;"></i> Registrar Abono</h3>
            <button class="modal-close" onclick="cerrarModal('modalAbono')">×</button>
        </div>

        <div id="infoAbono" style="background:#f7fafc;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:#718096;">Alumno</span>
                <strong id="abonoAlumnoNombre">—</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:#718096;">Total a pagar</span>
                <strong id="abonoMontoTotal">—</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:#718096;">Ya pagado</span>
                <strong id="abonoMontoPagado" style="color:#4caf50;">—</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#718096;">Saldo restante</span>
                <strong id="abonoSaldo" style="color:#e65100;">—</strong>
            </div>
        </div>

        <form id="formAbono" method="POST" action="" class="form-abono">
            @csrf

            <label for="monto_abono">Monto del abono <span style="color:#e53935;">*</span></label>
            <input type="number" step="0.01" min="1" name="monto_abono" id="monto_abono"
                   placeholder="0.00" required>

            <div id="tipoAbonoWrap">
                <label for="tipo_abono">Tipo de abono <span style="color:#e53935;">*</span></label>
                <select name="tipo_abono" id="tipo_abono" required onchange="cambiarTipoAbono(this.value)">
                    <option value="en_linea">En línea (MercadoPago)</option>
                    <option value="efectivo" id="opcionEfectivo" style="display:none;">Efectivo</option>
                </select>
            </div>

            <div id="avisoEfectivoAbono" style="display:none;" class="aviso-efectivo">
                <i class="bi bi-info-circle-fill"></i>
                Tu abono en efectivo quedará <strong>Pendiente</strong> hasta que el administrador lo confirme.
            </div>

            <div id="referenciaWrap" style="display:none;">
                <label for="referencia_abono">Referencia (opcional)</label>
                <input type="text" name="referencia" id="referencia_abono" placeholder="Número de comprobante">
            </div>

            <button type="submit" class="btn-abono-submit">
                <i class="bi bi-check-lg"></i> <span id="textoSubmitAbono">Registrar Abono</span>
            </button>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     MODAL VER HISTORIAL DE ABONOS
══════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalVerAbonos">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-list-ul" style="color:#6a1b9a;"></i> Historial de Abonos</h3>
            <button class="modal-close" onclick="cerrarModal('modalVerAbonos')">×</button>
        </div>
        <p id="tituloVerAbonos" style="color:#718096;font-size:13px;margin-bottom:12px;"></p>
        <div class="abonos-list" id="listaAbonos">
            <p style="text-align:center;color:#9e9e9e;padding:20px;">Cargando...</p>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     MODAL EDITAR CONCEPTO (admin/sensei)
══════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalEditConcepto">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-pencil-square" style="color:#1565c0;"></i> Editar Concepto</h3>
            <button class="modal-close" onclick="cerrarModal('modalEditConcepto')">×</button>
        </div>
        <form id="formEditConcepto" method="POST" action="">
            @csrf
            @method('PUT')

            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:600;color:#4a5568;margin-bottom:4px;display:block;">
                    Nombre <span style="color:#e53935;">*</span>
                </label>
                <input type="text" name="nombre" id="edit_nombre"
                       style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box;"
                       required>
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:600;color:#4a5568;margin-bottom:4px;display:block;">
                    Monto Sugerido
                </label>
                <input type="number" step="0.01" name="monto_sugerido" id="edit_monto"
                       placeholder="0.00 (dejar vacío si no aplica)"
                       style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:600;color:#4a5568;margin-bottom:4px;display:block;">
                    Descripción
                </label>
                <input type="text" name="descripcion" id="edit_desc"
                       placeholder="Opcional"
                       style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <input type="checkbox" name="activo" id="edit_activo" value="1"
                       style="width:18px;height:18px;accent-color:#e53935;">
                <label for="edit_activo" style="font-size:14px;font-weight:600;color:#2d3748;cursor:pointer;">
                    Concepto activo (aparece en el formulario de pago)
                </label>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditConcepto')"
                        style="flex:1;padding:12px;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" style="flex:1;padding:12px;">
                    <i class="bi bi-check-lg"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<form id="formEliminarPago" method="POST" action="" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<form id="formSuspenderPago" method="POST" action="" style="display:none;">
    @csrf
    @method('PATCH')
</form>

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

        // ── Búsqueda + Filtro + Paginación ─────────────────────────────
        const PAGE_SIZE_PAGOS = 20;
        let paginaPagos = 0;

        function getFilasVisibelsPagos() {
            const txtBuscar = (document.getElementById('searchInput')?.value || '').toLowerCase();
            const txtEstado = (document.getElementById('filterEstado')?.value || '').toLowerCase();
            return Array.from(document.querySelectorAll('#pagosTable tbody tr')).filter(row => {
                const txt = row.textContent.toLowerCase();
                const matchBuscar = !txtBuscar || txt.includes(txtBuscar);
                const matchEstado = !txtEstado || txt.includes(txtEstado);
                return matchBuscar && matchEstado;
            });
        }

        function renderPaginaPagos() {
            const todasLasFilas = Array.from(document.querySelectorAll('#pagosTable tbody tr'));
            todasLasFilas.forEach(r => r.style.display = 'none');

            const filtradas    = getFilasVisibelsPagos();
            const total        = filtradas.length;
            const totalPaginas = Math.max(1, Math.ceil(total / PAGE_SIZE_PAGOS));
            if (paginaPagos >= totalPaginas) paginaPagos = totalPaginas - 1;

            filtradas.slice(paginaPagos * PAGE_SIZE_PAGOS, (paginaPagos + 1) * PAGE_SIZE_PAGOS)
                .forEach(r => r.style.display = '');

            const ctrl = document.getElementById('paginacionPagos');
            if (ctrl) {
                ctrl.style.display = total > PAGE_SIZE_PAGOS ? 'flex' : 'none';
                document.getElementById('infoPaginaPagos').textContent =
                    'Página ' + (paginaPagos + 1) + ' de ' + totalPaginas + ' (' + total + ' resultados)';
                document.getElementById('btnAnteriorPagos').disabled = paginaPagos === 0;
                document.getElementById('btnAnteriorPagos').style.opacity = paginaPagos === 0 ? '0.4' : '1';
                document.getElementById('btnSiguientePagos').disabled = paginaPagos >= totalPaginas - 1;
                document.getElementById('btnSiguientePagos').style.opacity = paginaPagos >= totalPaginas - 1 ? '0.4' : '1';
            }
        }

        window.cambiarPaginaPagos = function(delta) {
            paginaPagos += delta;
            renderPaginaPagos();
            document.getElementById('pagosTable')?.closest('.table-responsive')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        document.getElementById('filterEstado').addEventListener('change', function () {
            paginaPagos = 0;
            renderPaginaPagos();
        });

        document.getElementById('searchInput').addEventListener('keyup', function () {
            paginaPagos = 0;
            renderPaginaPagos();
        });

        renderPaginaPagos();

        const selectAdmin = document.getElementById('id_concepto_admin');
        if (selectAdmin) {
            selectAdmin.addEventListener('change', function () {
                const opt   = this.options[this.selectedIndex];
                const monto = opt.dataset.monto;
                const hint  = document.getElementById('conceptoHintAdmin');
                if (monto && parseFloat(monto) > 0) {
                    hint.innerHTML = `Monto del concepto: <strong>$${parseFloat(monto).toFixed(2)}</strong>`;
                } else {
                    hint.innerHTML = opt.value ? 'Este concepto no tiene monto definido.' : '';
                }
            });
            if (selectAdmin.value) selectAdmin.dispatchEvent(new Event('change'));
        }

        const selectAlumno = document.getElementById('id_concepto_alumno');
        if (selectAlumno) {
            selectAlumno.addEventListener('change', function () {
                const opt   = this.options[this.selectedIndex];
                const monto = opt.dataset.monto;
                const hint  = document.getElementById('conceptoHintAlumno');
                const campo = document.getElementById('monto_alumno');
                if (monto) {
                    campo.value = monto;
                    hint.innerHTML = `Monto sugerido: <strong>$${parseFloat(monto).toFixed(2)}</strong>. Puedes reducirlo si vas a hacer un abono parcial.`;
                } else {
                    hint.innerHTML = opt.value ? 'Sin monto sugerido. Ingresa el monto que vas a pagar.' : '';
                }
            });
        }

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

        const tipoAbono = document.getElementById('tipo_abono');
        if (tipoAbono) {
            tipoAbono.addEventListener('change', function () {
                cambiarTipoAbono(this.value);
            });
        }
    });

    function activarTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    function mostrarAvisoEfectivo(valor) {
        const aviso = document.getElementById('avisoEfectivo');
        if (!aviso) return;
        const select  = document.getElementById('id_tipo_pago_alumno');
        const opt     = select ? select.options[select.selectedIndex] : null;
        const nombre  = opt ? opt.dataset.nombre : '';
        const esEnLinea = document.getElementById('pagarEnLineaAlumno')?.checked;
        aviso.style.display = (!esEnLinea && nombre === 'efectivo') ? 'block' : 'none';
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

    function abrirModalAbono(idPago, nombreAlumno, montoTotal, montoPagado, saldo, rol) {
        document.getElementById('abonoAlumnoNombre').textContent  = nombreAlumno;
        document.getElementById('abonoMontoTotal').textContent    = '$' + parseFloat(montoTotal).toFixed(2);
        document.getElementById('abonoMontoPagado').textContent   = '$' + parseFloat(montoPagado).toFixed(2);
        document.getElementById('abonoSaldo').textContent         = '$' + parseFloat(saldo).toFixed(2);

        const form = document.getElementById('formAbono');
        form.action = '{{ url("pagos") }}/' + idPago + '/abono';

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
        const btn            = document.querySelector('.btn-abono-submit');

        if (valor === 'efectivo') {
            if (referenciaWrap) referenciaWrap.style.display = '';
            if (avisoEfectivo)  avisoEfectivo.style.display  = '';
            if (textoBtn)       textoBtn.textContent          = 'Registrar Abono en Efectivo';
            if (btn) { btn.style.backgroundColor = '#e53935'; btn.style.borderColor = '#e53935'; }
        } else {
            if (referenciaWrap) referenciaWrap.style.display = 'none';
            if (avisoEfectivo)  avisoEfectivo.style.display  = 'none';
            if (textoBtn)       textoBtn.textContent          = 'Ir a MercadoPago';
            if (btn) { btn.style.backgroundColor = '#009ee3'; btn.style.borderColor = '#009ee3'; }
        }
    }

    function verAbonos(idPago, concepto) {
        document.getElementById('tituloVerAbonos').textContent = 'Abonos del pago: ' + concepto;
        document.getElementById('listaAbonos').innerHTML =
            '<p style="text-align:center;color:#9e9e9e;padding:20px;">Cargando...</p>';

        abrirModal('modalVerAbonos');

        fetch('{{ url("pagos") }}/' + idPago + '/abonos', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(abonos => {
            const lista = document.getElementById('listaAbonos');
            if (!abonos.length) {
                lista.innerHTML = '<p style="text-align:center;color:#9e9e9e;padding:20px;">Sin abonos registrados.</p>';
                return;
            }
            lista.innerHTML = abonos.map(a => `
                <div class="abono-item">
                    <div>
                        <span class="abono-tipo-badge tipo-${a.tipo_abono}">${a.tipo_abono === 'en_linea' ? 'En línea' : 'Efectivo'}</span>
                        <small style="display:block;color:#718096;margin-top:2px;">${a.fecha_abono ? a.fecha_abono.substring(0,10) : '—'}</small>
                        ${a.referencia ? `<small style="color:#9e9e9e;">${a.referencia}</small>` : ''}
                        ${a.registrado_por_nombre ? `<small style="color:#b0bec5;display:block;">Por: ${a.registrado_por_nombre}</small>` : ''}
                    </div>
                    <strong style="color:#e53935;">$${parseFloat(a.monto_abono).toFixed(2)}</strong>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('listaAbonos').innerHTML =
                '<p style="text-align:center;color:#e53935;padding:20px;">Error al cargar los abonos.</p>';
        });
    }

    function abrirModal(id)  { document.getElementById(id).classList.add('active'); }
    function cerrarModal(id) { document.getElementById(id).classList.remove('active'); }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) cerrarModal(this.id);
        });
    });

    function confirmarCompletar(idPago, concepto) {
        Swal.fire({
            title: '¿Marcar como completado?',
            html: `Se marcará el pago de <strong>${concepto}</strong> como completado.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2e7d32',
            cancelButtonColor: '#9e9e9e',
            confirmButtonText: 'Sí, completar',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('formCompletar-' + idPago).submit();
            }
        });
    }

    function confirmarEliminar(idPago, concepto) {
        Swal.fire({
            title: '¿Eliminar pago?',
            html: `Se eliminará el pago <strong>${concepto}</strong>. Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c62828',
            cancelButtonColor: '#9e9e9e',
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

    function confirmarSuspender(idPago, concepto) {
        Swal.fire({
            title: '¿Suspender pago?',
            html: `Se suspenderá el pago <strong>${concepto}</strong>.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f57f17',
            cancelButtonColor: '#9e9e9e',
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

    function abrirEditConcepto(id, nombre, descripcion, monto, activo) {
        document.getElementById('edit_nombre').value   = nombre;
        document.getElementById('edit_desc').value     = descripcion;
        document.getElementById('edit_monto').value    = monto || '';
        document.getElementById('edit_activo').checked = activo == 1;

        const form = document.getElementById('formEditConcepto');
        form.action = '{{ url("conceptos-pago") }}/' + id;

        abrirModal('modalEditConcepto');
    }

    function resetFormAlumno() {
        document.getElementById('conceptoHintAlumno').innerHTML = '';
        document.getElementById('avisoEfectivo').style.display  = 'none';
        const btn = document.getElementById('btnSubmitAlumno');
        btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Pago';
        btn.style.backgroundColor = '';
        btn.style.borderColor     = '';
    }
</script>

</body>
</html>