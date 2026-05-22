{{--
    resources/views/pagosViews/pagos.blade.php — REEMPLAZA COMPLETO

    Admin/sensei:
      - Formulario para registrar cargos a cualquier alumno/tutor
      - Panel de gestión de conceptos (crear/editar)
      - Tabla con TODOS los pagos

    Alumno/tutor:
      - Formulario para registrar su propio pago (elige concepto, ajusta monto)
      - Tabla con SUS pagos
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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

        /* ── Modales ── */
        .modal-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center; }
        .modal-overlay.active { display:flex; }
        .modal-box { background:white;border-radius:20px;padding:32px;width:100%;max-width:500px;box-shadow:0 8px 32px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto; }
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

        /* ── Concepto hint ── */
        .concepto-hint { font-size:12px;color:#718096;margin-top:4px;min-height:18px; }
        .concepto-hint strong { color:#e53935; }

        /* ── Panel gestión conceptos ── */
        .conceptos-panel { margin-top:8px; }

        /* Grid de tarjetas */
        .conceptos-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-top: 4px;
        }
        @media (max-width: 900px) {
            .conceptos-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 540px) {
            .conceptos-grid { grid-template-columns: 1fr; }
        }
        .concepto-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .concepto-card:hover {
            box-shadow: 0 6px 20px rgba(229,57,53,0.10);
            transform: translateY(-2px);
        }
        .concepto-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, #e53935, #ff7043);
        }
        .concepto-card.inactivo-card::before {
            background: #e2e8f0;
        }
        .concepto-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }
        .concepto-nombre {
            font-weight: 700;
            color: #2d3748;
            font-size: 14px;
            line-height: 1.3;
            flex: 1;
        }
        .concepto-estado {
            font-size: 11px;
            padding: 3px 9px;
            border-radius: 20px;
            font-weight: 600;
            white-space: nowrap;
        }
        .concepto-activo   { background:#e8f5e9; color:#2e7d32; }
        .concepto-inactivo { background:#fce4ec; color:#c62828; }
        .concepto-desc {
            font-size: 12px;
            color: #9e9e9e;
            line-height: 1.4;
            flex: 1;
            min-height: 32px;
        }
        .concepto-card-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
        }
        .concepto-monto {
            font-size: 20px;
            font-weight: 800;
            color: #e53935;
        }
        .concepto-monto.sin-monto {
            font-size: 14px;
            font-weight: 500;
            color: #b0bec5;
        }
        .btn-edit-concepto {
            background: none;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            padding: 5px 12px;
            cursor: pointer;
            color: #718096;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s, color 0.15s;
        }
        .btn-edit-concepto:hover { background:#fff0f0; color:#e53935; border-color:#e53935; }

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
             - Tab 1: Registrar cargo a un alumno
             - Tab 2: Gestionar catálogo de conceptos
        ══════════════════════════════════════════════════════════ --}}
        @if(in_array($user->rol, ['admin', 'sensei']))

        <div class="form-container form-theme-red">
            <div class="form-header">
                <h2><i class="bi bi-credit-card-fill"></i> Panel de Pagos</h2>
                <p>Registra cargos para los alumnos y gestiona el catálogo de conceptos.</p>
            </div>

            {{-- Tabs de navegación --}}
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="activarTab('tab-registro', this)">
                    <i class="bi bi-plus-circle"></i> Registrar Cargo
                </button>
                <button class="tab-btn" onclick="activarTab('tab-conceptos', this)">
                    <i class="bi bi-bookmarks"></i> Conceptos de Pago
                </button>
            </div>

            {{-- ── TAB 1: Registrar nuevo cargo ── --}}
            <div id="tab-registro" class="tab-content active">
                <form id="registroPago" method="POST" action="{{ route('pagos.store') }}" class="form-body">
                    @csrf

                    {{-- Destinatario --}}
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
                                    <option value="">Seleccione alumno o tutor</option>
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

                    {{-- Concepto y detalles --}}
                    <h3 class="section-title-header">
                        <i class="bi bi-receipt-cutoff"></i> Detalles del Cargo
                    </h3>
                    <div class="form-grid">

                        {{-- Concepto predefinido --}}
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

                        {{-- Método de pago --}}
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

                        {{-- Monto --}}
                        <div class="form-group">
                            <label class="form-label" for="monto_admin">
                                Monto Total <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-currency-dollar input-icon"></i>
                                <input type="number" step="0.01" name="monto" id="monto_admin"
                                       class="form-input" placeholder="0.00"
                                       value="{{ old('monto') }}" required>
                            </div>
                            @error('monto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fecha --}}
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

                        {{--
                            Estado inicial:
                            - Pendiente  → alumno pagará después
                            - Completado → admin recibe efectivo en este momento
                            "Fallido" no aparece aquí — lo asigna MercadoPago automáticamente.
                        --}}
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

                        {{-- Nota adicional --}}
                        <div class="form-group">
                            <label class="form-label" for="motivoPago_admin">Nota / Detalle adicional</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-chat-left-text input-icon"></i>
                                <input type="text" name="motivoPago" id="motivoPago_admin" class="form-input"
                                       placeholder="Ej: Mensualidad Mayo 2026"
                                       value="{{ old('motivoPago') }}">
                            </div>
                        </div>

                        {{-- Referencia --}}
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

                    {{-- Toggle pago en línea --}}
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

            {{-- ── TAB 2: Gestión de conceptos de pago ── --}}
            <div id="tab-conceptos" class="tab-content">

                {{-- Formulario para nuevo concepto --}}
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

                {{-- Lista de conceptos existentes --}}
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
                                <p class="concepto-desc">
                                    {{ $c->descripcion ?? 'Sin descripción.' }}
                                </p>
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

        {{-- ══════════════════════════════════════════════════════════
             BLOQUE ALUMNO / TUTOR
             Formulario para registrar su propio pago
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

                    {{-- Concepto del catálogo --}}
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

                    {{--
                        Monto: el alumno puede ajustarlo (para abonos parciales).
                        Se autocompleta con el monto sugerido del concepto.
                    --}}
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

                    {{-- Fecha --}}
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

                    {{-- Método de pago --}}
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

                        {{-- Aviso si el alumno elige efectivo --}}
                        <div class="aviso-efectivo" id="avisoEfectivo">
                            <i class="bi bi-info-circle-fill"></i>
                            <strong>Pago en efectivo:</strong> Tu registro quedará como <strong>Pendiente</strong>
                            hasta que el administrador o sensei lo confirme presencialmente.
                        </div>
                    </div>

                    {{-- Nota opcional --}}
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

                {{-- Toggle pago en línea --}}
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
                                $porcentaje  = $montoTotal > 0 ? min(100, ($montoPagado / $montoTotal) * 100) : 0;
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
                                        ${{ number_format($montoPagado, 2) }}
                                    </span>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width:{{ $porcentaje }}%"></div>
                                    </div>
                                    <small style="color:#9e9e9e;font-size:11px;">{{ number_format($porcentaje, 0) }}%</small>
                                </td>

                                <td>
                                    @if($saldo <= 0)
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
                                    @else
                                        <span class="badge badge-danger">{{ $estado ?? 'N/A' }}</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="acciones-cell">

                                        {{-- Pagar con MP: cualquier rol si hay saldo --}}
                                        @if($pago->estado_pago !== 'Completado' && $saldo > 0)
                                            <a href="{{ route('pagos.pagar', $pago->id_pago) }}"
                                               class="btn btn-primary"
                                               style="padding:5px 12px;font-size:12px;display:inline-flex;align-items:center;gap:5px;">
                                                <i class="bi bi-credit-card-fill"></i> Pagar
                                            </a>
                                        @endif

                                        {{-- Abono: cualquier rol si hay saldo --}}
                                        @if($pago->estado_pago !== 'Completado' && $saldo > 0)
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

                                        {{-- Completar: solo admin/sensei --}}
                                        @if(in_array($user->rol, ['admin', 'sensei']) && $pago->estado_pago === 'Pendiente')
                                            <form method="POST"
                                                  action="{{ route('pagos.completar', $pago->id_pago) }}"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('¿Confirmas que verificaste este pago y deseas marcarlo como Completado?')">
                                                @csrf
                                                <button type="submit" class="btn-completar">
                                                    <i class="bi bi-check-circle-fill"></i> Completar
                                                </button>
                                            </form>
                                        @endif

                                        {{-- Ver abonos: cualquier rol --}}
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
        </div>
    </div>

    @include('includes.pie')
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MODAL REGISTRAR ABONO
══════════════════════════════════════════════════════════════════════ --}}
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
                    {{-- Efectivo: visible para todos; el JS lo controla por rol --}}
                    <option value="efectivo" id="opcionEfectivo" style="display:none;">Efectivo</option>
                </select>
            </div>

            {{-- Aviso si alumno elige efectivo en el modal --}}
            <div id="avisoEfectivoAbono" style="display:none;"
                 class="aviso-efectivo" style="display:none;margin-bottom:8px;">
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

{{-- ══════════════════════════════════════════════════════════════════════
     MODAL VER HISTORIAL DE ABONOS
══════════════════════════════════════════════════════════════════════ --}}
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

{{-- ══════════════════════════════════════════════════════════════════════
     MODAL EDITAR CONCEPTO (admin/sensei)
══════════════════════════════════════════════════════════════════════ --}}
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

<script>
    // ── Variables globales ─────────────────────────────────────────────
    const ROL_USUARIO = '{{ $user->rol }}';

    // ── SweetAlert al cargar ───────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('sessionInsertado'))
            Swal.fire({
                icon:              '{{ session('sessionInsertado') == 'true' ? 'success' : 'error' }}',
                title:             '{{ addslashes(session('mensaje')) }}',
                showConfirmButton: false,
                timer:             3000,
            });
        @endif

        // ── Filtro por estado ──────────────────────────────────────────
        document.getElementById('filterEstado').addEventListener('change', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#pagosTable tbody tr').forEach(row => {
                row.style.display = (!val || row.textContent.toLowerCase().includes(val)) ? '' : 'none';
            });
        });

        // ── Búsqueda ──────────────────────────────────────────────────
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#pagosTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
            });
        });

        // ── Concepto admin → autocompletar monto ──────────────────────
        const selectAdmin = document.getElementById('id_concepto_admin');
        if (selectAdmin) {
            selectAdmin.addEventListener('change', function () {
                const opt   = this.options[this.selectedIndex];
                const monto = opt.dataset.monto;
                const hint  = document.getElementById('conceptoHintAdmin');
                const campo = document.getElementById('monto_admin');
                if (monto) {
                    campo.value = monto;
                    hint.innerHTML = `Monto sugerido autocompletado: <strong>$${parseFloat(monto).toFixed(2)}</strong>. Puedes modificarlo.`;
                } else {
                    hint.innerHTML = opt.value ? 'Sin monto sugerido. Ingresa el monto manualmente.' : '';
                }
            });
        }

        // ── Concepto alumno → autocompletar monto ─────────────────────
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

        // ── Admin: pagar en línea → ocultar estado y referencia ───────
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

        // ── Tipo de abono modal → mostrar referencia / aviso ──────────
        const tipoAbono = document.getElementById('tipo_abono');
        if (tipoAbono) {
            tipoAbono.addEventListener('change', function () {
                cambiarTipoAbono(this.value);
            });
        }
    });

    // ── Tabs admin ─────────────────────────────────────────────────────
    function activarTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    // ── Aviso efectivo alumno (formulario principal) ───────────────────
    function mostrarAvisoEfectivo(valor) {
        const aviso = document.getElementById('avisoEfectivo');
        if (!aviso) return;
        const select  = document.getElementById('id_tipo_pago_alumno');
        const opt     = select ? select.options[select.selectedIndex] : null;
        const nombre  = opt ? opt.dataset.nombre : '';
        // Mostrar aviso si elige "Efectivo" (o cualquier método que no sea en línea)
        const esEnLinea = document.getElementById('pagarEnLineaAlumno')?.checked;
        aviso.style.display = (!esEnLinea && nombre === 'efectivo') ? 'block' : 'none';
    }

    // ── Toggle en línea alumno ─────────────────────────────────────────
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
            // Volver a revisar si hay aviso de efectivo
            const select = document.getElementById('id_tipo_pago_alumno');
            mostrarAvisoEfectivo(select ? select.value : '');
        }
    }

    // ── Reset formulario alumno ────────────────────────────────────────
    function resetFormAlumno() {
        document.getElementById('conceptoHintAlumno').innerHTML = '';
        document.getElementById('avisoEfectivo').style.display  = 'none';
        const btn = document.getElementById('btnSubmitAlumno');
        btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Pago';
        btn.style.backgroundColor = '';
        btn.style.borderColor     = '';
    }

    // ── Modal Abono ───────────────────────────────────────────────────
    function abrirModalAbono(idPago, nombreAlumno, montoTotal, montoPagado, saldo, rol) {
        document.getElementById('abonoAlumnoNombre').textContent = nombreAlumno;
        document.getElementById('abonoMontoTotal').textContent   = '$' + montoTotal.toFixed(2);
        document.getElementById('abonoMontoPagado').textContent  = '$' + montoPagado.toFixed(2);
        document.getElementById('abonoSaldo').textContent        = '$' + saldo.toFixed(2);
        document.getElementById('monto_abono').max              = saldo;
        document.getElementById('monto_abono').value            = '';

        // Efectivo visible para todos los roles;
        // el aviso de "quedará Pendiente" aparece para alumno/tutor
        document.getElementById('opcionEfectivo').style.display  = '';
        document.getElementById('tipo_abono').value              = 'en_linea';
        document.getElementById('referenciaWrap').style.display  = 'none';
        document.getElementById('avisoEfectivoAbono').style.display = 'none';
        document.getElementById('textoSubmitAbono').textContent  = 'Ir a MercadoPago';

        document.getElementById('formAbono').action = '/pagos/' + idPago + '/abono';
        document.getElementById('modalAbono').classList.add('active');
    }

    // ── Cambio de tipo de abono en el modal ───────────────────────────
    function cambiarTipoAbono(valor) {
        const refWrap   = document.getElementById('referenciaWrap');
        const aviso     = document.getElementById('avisoEfectivoAbono');
        const textoBtn  = document.getElementById('textoSubmitAbono');
        const btnSubmit = document.getElementById('formAbono').querySelector('button[type=submit]');

        if (valor === 'efectivo') {
            refWrap.style.display  = 'block';
            textoBtn.textContent   = 'Registrar Abono';
            btnSubmit.style.backgroundColor = '#4caf50';
            // Mostrar aviso solo para alumno/tutor
            if (ROL_USUARIO === 'alumno' || ROL_USUARIO === 'tutor') {
                aviso.style.display = 'block';
            }
        } else {
            refWrap.style.display  = 'none';
            aviso.style.display    = 'none';
            textoBtn.textContent   = 'Ir a MercadoPago';
            btnSubmit.style.backgroundColor = '#e53935';
        }
    }

    // ── Modal Ver Abonos ──────────────────────────────────────────────
    function verAbonos(idPago, concepto) {
        document.getElementById('tituloVerAbonos').textContent = 'Concepto: ' + concepto;
        document.getElementById('listaAbonos').innerHTML =
            '<p style="text-align:center;color:#9e9e9e;padding:20px;">Cargando...</p>';
        document.getElementById('modalVerAbonos').classList.add('active');

        fetch('/pagos/' + idPago + '/abonos', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            const lista = document.getElementById('listaAbonos');
            if (!data || data.length === 0) {
                lista.innerHTML = '<p style="text-align:center;color:#9e9e9e;padding:20px;">No hay abonos registrados.</p>';
                return;
            }
            lista.innerHTML = data.map(a => `
                <div class="abono-item">
                    <div>
                        <div style="font-weight:600;color:#2d3748;">$${parseFloat(a.monto_abono).toFixed(2)}</div>
                        <div style="color:#9e9e9e;font-size:11px;margin-top:2px;">
                            ${a.fecha_abono ? a.fecha_abono.substring(0,10) : '—'}
                            ${a.registrado_por_nombre ? ' · ' + a.registrado_por_nombre : ''}
                        </div>
                        ${a.referencia ? `<div style="color:#9e9e9e;font-size:11px;">Ref: ${a.referencia}</div>` : ''}
                    </div>
                    <span class="abono-tipo-badge tipo-${a.tipo_abono}">
                        ${a.tipo_abono === 'efectivo' ? '💵 Efectivo' : '💳 En línea'}
                    </span>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('listaAbonos').innerHTML =
                '<p style="text-align:center;color:#e53935;padding:20px;">Error al cargar abonos.</p>';
        });
    }

    // ── Modal Editar Concepto ─────────────────────────────────────────
    function abrirEditConcepto(id, nombre, descripcion, monto, activo) {
        document.getElementById('edit_nombre').value   = nombre;
        document.getElementById('edit_desc').value     = descripcion;
        document.getElementById('edit_monto').value    = monto || '';
        document.getElementById('edit_activo').checked = activo === 1;
        document.getElementById('formEditConcepto').action = '/conceptos-pago/' + id;
        document.getElementById('modalEditConcepto').classList.add('active');
    }

    // ── Cerrar cualquier modal ────────────────────────────────────────
    function cerrarModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    document.addEventListener('click', function (e) {
        ['modalAbono', 'modalVerAbonos', 'modalEditConcepto'].forEach(id => {
            const modal = document.getElementById(id);
            if (modal && e.target === modal) modal.classList.remove('active');
        });
    });
</script>
</body>
</html>