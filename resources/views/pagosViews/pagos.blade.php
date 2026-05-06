<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Pagos - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .saldo-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .saldo-pendiente { background: #fff3e0; color: #e65100; }
        .saldo-completado { background: #e8f5e9; color: #2e7d32; }
        .progress-bar-wrap {
            width: 100%;
            background: #f0f0f0;
            border-radius: 10px;
            height: 6px;
            margin-top: 4px;
        }
        .progress-bar-fill {
            height: 6px;
            border-radius: 10px;
            background: linear-gradient(90deg, #e53935, #ff7043);
            transition: width 0.4s;
        }
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white;
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .modal-header h3 { font-size: 18px; color: #2d3748; margin: 0; }
        .modal-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #9e9e9e;
        }
        .modal-close:hover { color: #e53935; }
        .abonos-list { max-height: 260px; overflow-y: auto; margin-bottom: 16px; }
        .abono-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }
        .abono-item:last-child { border-bottom: none; }
        .abono-monto { font-weight: 700; color: #2d3748; }
        .abono-tipo-badge {
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
        }
        .tipo-efectivo { background: #e8f5e9; color: #2e7d32; }
        .tipo-en_linea { background: #e3f2fd; color: #1565c0; }
        .form-abono { margin-top: 16px; border-top: 1px solid #f0f0f0; padding-top: 16px; }
        .form-abono label { font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 4px; display: block; }
        .form-abono input, .form-abono select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 12px;
            box-sizing: border-box;
        }
        .btn-abono-submit {
            width: 100%;
            padding: 12px;
            background: #e53935;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-abono-submit:hover { background: #c62828; }
        .btn-completar {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-completar:hover { background: #c8e6c9; }
        .btn-abono {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-abono:hover { background: #ffe0b2; }
        .acciones-cell { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    </style>
</head>
<body>
@include('includes.menu')

<div class="main-content">

    <header class="header">
        <div>
            <h1 class="header-title">
                <i class="bi bi-cash-coin"></i> Gestión de Pagos
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
                <div>
                    <strong>{{ $isSuccess ? '¡Éxito!' : '¡Error!' }}</strong> {{ session('mensaje') }}
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             FORMULARIO DE REGISTRO — solo admin y sensei
        ══════════════════════════════════════════════════════════ --}}
        @if(in_array($user->rol, ['admin', 'sensei']))
        <div class="form-container form-theme-red">
            <div class="form-header">
                <h2><i class="bi bi-credit-card-fill"></i> Registrar Nuevo Pago</h2>
                <p>Complete la información del pago realizado por el alumno</p>
            </div>

            <form id="registroPago" method="POST" action="{{ route('pagos.store') }}" class="form-body">
                @csrf

                <h3 class="section-title-header">
                    <i class="bi bi-person-circle"></i> Información del Alumno
                </h3>
                <div class="form-grid full-width">
                    <div class="form-group">
                        <label class="form-label" for="id_alumno">
                            Alumno <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-person-badge input-icon"></i>
                            <select name="id_alumno" id="id_alumno" class="form-select" required>
                                <option value="">Seleccione Alumno</option>
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
                    <i class="bi bi-receipt-cutoff"></i> Detalles del Pago
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="id_tipo_pago">
                            Tipo de Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-tag input-icon"></i>
                            <select name="id_tipo_pago" id="id_tipo_pago" class="form-select" required>
                                <option value="">Seleccione el tipo</option>
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
                        <label class="form-label" for="monto">
                            Monto Total <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-currency-dollar input-icon"></i>
                            <input type="number" step="0.01" name="monto" id="monto"
                                   class="form-input" placeholder="0.00"
                                   value="{{ old('monto') }}" required>
                        </div>
                        @error('monto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="fechaPago">
                            Fecha de Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-calendar-check input-icon"></i>
                            <input type="date" name="fechaPago" id="fechaPago" class="form-input"
                                   value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                        </div>
                        @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="estadoPago">
                            Estado del Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-check-circle input-icon"></i>
                            <select name="estadoPago" id="estadoPago" class="form-select" required>
                                <option value="">Seleccionar Estado</option>
                                {{-- Pendiente por defecto: el alumno aún no ha pagado --}}
                                <option value="Pendiente"
                                    {{ old('estadoPago', 'Pendiente') == 'Pendiente' ? 'selected' : '' }}>
                                    Pendiente
                                </option>
                                {{-- Completado: admin recibe efectivo en mano ahora mismo --}}
                                <option value="Completado"
                                    {{ old('estadoPago') == 'Completado' ? 'selected' : '' }}>
                                    Completado
                                </option>
                                <option value="Fallido"
                                    {{ old('estadoPago') == 'Fallido' ? 'selected' : '' }}>
                                    Fallido
                                </option>
                            </select>
                        </div>
                        @error('estadoPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-credit-card"></i> Motivo y Referencia
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="motivoPago">Motivo del Pago</label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-chat-left-text input-icon"></i>
                            <input type="text" name="motivoPago" id="motivoPago" class="form-input"
                                   placeholder="Ej: Mensualidad Enero 2026"
                                   value="{{ old('motivoPago') }}">
                        </div>
                        @error('motivoPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="referenciaPago">Referencia (Opcional)</label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-receipt input-icon"></i>
                            <input type="text" name="referenciaPago" id="referenciaPago" class="form-input"
                                   placeholder="Número de referencia o voucher"
                                   value="{{ old('referenciaPago') }}">
                        </div>
                        @error('referenciaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Opción pago en línea con MercadoPago --}}
                <div class="form-group" style="margin-top:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px;">
                        <input type="checkbox" name="pagar_en_linea" id="pagarEnLinea" value="1"
                               style="width:18px;height:18px;accent-color:#e53935;"
                               {{ old('pagar_en_linea') ? 'checked' : '' }}>
                        <span>
                            <i class="bi bi-credit-card-2-front-fill" style="color:#009ee3;font-size:18px;"></i>
                            Pagar en línea con
                            <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp"
                                 alt="MercadoPago" style="height:20px;vertical-align:middle;margin-left:4px;">
                        </span>
                    </label>
                    <p style="margin-left:28px;margin-top:4px;font-size:12px;color:#718096;">
                        Al marcar esta opción el alumno será redirigido al checkout de MercadoPago.
                        El estado se actualizará automáticamente al completar el pago.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="bi bi-check-lg"></i> Registrar Pago
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             TABLA DE PAGOS
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
                        <input type="text" class="search-input" id="searchInput" placeholder="Buscar pagos...">
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
                            <th>Tipo</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Motivo</th>
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
                            @endphp
                            <tr>
                                {{-- Columna alumno: solo visible para admin/sensei --}}
                                @if(in_array($user->rol, ['admin', 'sensei']))
                                <td>
                                    <div class="student-cell">
                                        <div class="student-avatar">
                                            {{ strtoupper(substr($pago->nombre_alumno, 0, 1)) }}{{ strtoupper(substr(strstr($pago->nombre_alumno, ' '), 1, 1)) }}
                                        </div>
                                        <span class="student-name">{{ $pago->nombre_alumno ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                @endif

                                <td>{{ $pago->nombre_tipo ?? 'N/A' }}</td>

                                {{-- Monto total --}}
                                <td>
                                    <span class="amount">${{ number_format($montoTotal, 2) }}</span>
                                </td>

                                {{-- Monto pagado con barra de progreso --}}
                                <td>
                                    <span style="font-weight:600; color:#4caf50;">
                                        ${{ number_format($montoPagado, 2) }}
                                    </span>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width:{{ $porcentaje }}%"></div>
                                    </div>
                                    <small style="color:#9e9e9e; font-size:11px;">{{ number_format($porcentaje, 0) }}%</small>
                                </td>

                                {{-- Saldo restante --}}
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

                                {{-- Estado --}}
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

                                <td>{{ $pago->motivo_pago ?? '—' }}</td>

                                {{-- Acciones --}}
                                <td>
                                    <div class="acciones-cell">

                                        {{-- Botón Pagar: disponible para cualquier rol si está Pendiente --}}
                                        @if($pago->estado_pago !== 'Completado' && $saldo > 0)
                                            <a href="{{ route('pagos.pagar', $pago->id_pago) }}"
                                               class="btn btn-primary"
                                               style="padding:5px 12px;font-size:12px;display:inline-flex;align-items:center;gap:5px;">
                                                <i class="bi bi-credit-card-fill"></i> Pagar
                                            </a>
                                        @endif

                                        {{-- Botón Abono: cualquier rol si hay saldo --}}
                                        @if($pago->estado_pago !== 'Completado' && $saldo > 0)
                                            <button type="button"
                                                class="btn-abono"
                                                onclick="abrirModalAbono(
                                                    {{ $pago->id_pago }},
                                                    '{{ addslashes($pago->nombre_alumno ?? 'Alumno') }}',
                                                    {{ $montoTotal }},
                                                    {{ $montoPagado }},
                                                    {{ $saldo }},
                                                    '{{ $user->rol }}'
                                                )">
                                                <i class="bi bi-plus-circle"></i> Abono
                                            </button>
                                        @endif

                                        {{-- Botón Completar: solo admin/sensei, solo si está Pendiente --}}
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
                                            onclick="verAbonos({{ $pago->id_pago }}, '{{ addslashes($pago->motivo_pago ?? 'Pago #' . $pago->id_pago) }}')">
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
                                    class="text-center">
                                    No hay pagos registrados.
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
                <select name="tipo_abono" id="tipo_abono" required>
                    <option value="en_linea">En línea (MercadoPago)</option>
                    {{-- efectivo solo se muestra si es admin/sensei (controlado por JS) --}}
                    <option value="efectivo" id="opcionEfectivo" style="display:none;">Efectivo</option>
                </select>
            </div>

            <div id="referenciaWrap" style="display:none;">
                <label for="referencia_abono">Referencia (opcional)</label>
                <input type="text" name="referencia" id="referencia_abono" placeholder="Número de comprobante">
            </div>

            <button type="submit" class="btn-abono-submit">
                <i class="bi bi-check-lg"></i> Registrar Abono
            </button>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MODAL VER ABONOS
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

<script>
    // ── Variables globales ────────────────────────────────────────────
    let pagoIdActual = null;

    // ── SweetAlert al cargar si hay sesión ───────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        @if(session('sessionInsertado'))
            Swal.fire({
                icon:              '{{ session('sessionInsertado') == 'true' ? 'success' : 'error' }}',
                title:             '{{ session('mensaje') }}',
                showConfirmButton: false,
                timer:             2500,
            });
        @endif

        // Filtro por estado
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

        // Cambio en checkbox pagar en línea
        const chk = document.getElementById('pagarEnLinea');
        if (chk) {
            chk.addEventListener('change', function () {
                const btn = document.getElementById('btnSubmit');
                if (this.checked) {
                    btn.innerHTML = '<i class="bi bi-credit-card-2-front-fill"></i> Continuar a MercadoPago';
                    btn.style.backgroundColor = '#009ee3';
                    btn.style.borderColor     = '#009ee3';
                } else {
                    btn.innerHTML = '<i class="bi bi-check-lg"></i> Registrar Pago';
                    btn.style.backgroundColor = '';
                    btn.style.borderColor     = '';
                }
            });
        }

        // Mostrar/ocultar referencia según tipo de abono
        const tipoAbono = document.getElementById('tipo_abono');
        if (tipoAbono) {
            tipoAbono.addEventListener('change', function () {
                document.getElementById('referenciaWrap').style.display =
                    this.value === 'efectivo' ? 'block' : 'none';
            });
        }
    });

    // ── Modal Abono ───────────────────────────────────────────────────
    function abrirModalAbono(idPago, nombreAlumno, montoTotal, montoPagado, saldo, rol) {
        pagoIdActual = idPago;

        document.getElementById('abonoAlumnoNombre').textContent = nombreAlumno;
        document.getElementById('abonoMontoTotal').textContent   = '$' + montoTotal.toFixed(2);
        document.getElementById('abonoMontoPagado').textContent  = '$' + montoPagado.toFixed(2);
        document.getElementById('abonoSaldo').textContent        = '$' + saldo.toFixed(2);

        // Limitar monto máximo al saldo restante
        document.getElementById('monto_abono').max   = saldo;
        document.getElementById('monto_abono').value = '';

        // Mostrar opción "Efectivo" solo para admin/sensei
        const opcionEfectivo = document.getElementById('opcionEfectivo');
        if (rol === 'admin' || rol === 'sensei') {
            opcionEfectivo.style.display = '';
        } else {
            opcionEfectivo.style.display = 'none';
            document.getElementById('tipo_abono').value = 'en_linea';
        }

        // Ocultar referencia al abrir
        document.getElementById('referenciaWrap').style.display = 'none';

        // Asignar action del form
        document.getElementById('formAbono').action = '/pagos/' + idPago + '/abono';

        document.getElementById('modalAbono').classList.add('active');
    }

    // ── Modal Ver Abonos ──────────────────────────────────────────────
    function verAbonos(idPago, motivo) {
        document.getElementById('tituloVerAbonos').textContent = 'Pago: ' + motivo;
        document.getElementById('listaAbonos').innerHTML =
            '<p style="text-align:center;color:#9e9e9e;padding:20px;">Cargando...</p>';

        document.getElementById('modalVerAbonos').classList.add('active');

        fetch('/pagos/' + idPago + '/abonos', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
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

    // ── Cerrar modales ────────────────────────────────────────────────
    function cerrarModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // Cerrar modal al hacer clic fuera
    document.addEventListener('click', function (e) {
        ['modalAbono', 'modalVerAbonos'].forEach(id => {
            const modal = document.getElementById(id);
            if (e.target === modal) modal.classList.remove('active');
        });
    });
</script>
</body>
</html>