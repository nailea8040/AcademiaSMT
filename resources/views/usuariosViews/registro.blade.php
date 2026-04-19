<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Dojo</title>
    <link rel="stylesheet" href="{{ asset('/css/estiloindex.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)), url("{{ asset('img/registro.jpg') }}");
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding: 48px 16px;
        }

        /* ── Card blanca ────────────────────────────────────── */
        .reg-card {
            background: #ffffff;
            border-radius: 18px;
            width: 100%;
            max-width: 660px;
            overflow: hidden;
            box-shadow: 0 32px 100px rgba(0,0,0,.85);
        }

        /* ── Header ─────────────────────────────────────────── */
        .reg-header {
            background: linear-gradient(135deg, #c62828 0%, #7f0000 100%);
            padding: 28px 36px;
            position: relative;
            overflow: hidden;
        }
        .reg-header::before {
            content: '';
            position: absolute; top: -40px; right: -40px;
            width: 180px; height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }
        .reg-header h1 { color: #fff; font-size: 21px; font-weight: 700; position: relative; }
        .reg-header p  { color: rgba(255,255,255,.72); font-size: 13px; margin-top: 4px; position: relative; }

        /* ── Stepper ─────────────────────────────────────────── */
        .stepper {
            display: flex;
            padding: 0 32px;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
        }
        .step-item {
            display: flex; align-items: center; flex: 1;
            padding: 14px 0; gap: 10px; position: relative;
        }
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute; right: 0; top: 50%;
            transform: translateY(-50%);
            width: 1px; height: 26px; background: #ddd;
        }
        .step-circle {
            width: 30px; height: 30px; border-radius: 50%;
            background: #ddd; color: #999;
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: all .3s;
        }
        .step-item.active .step-circle { background: #c62828; color: #fff; box-shadow: 0 0 0 4px rgba(198,40,40,.18); }
        .step-item.done   .step-circle { background: #388e3c; color: #fff; }
        .step-label { font-size: 11.5px; color: #aaa; font-weight: 500; line-height: 1.35; transition: color .3s; }
        .step-item.active .step-label { color: #1a1a1a; font-weight: 600; }
        .step-item.done   .step-label { color: #388e3c; }

        /* ── Body ────────────────────────────────────────────── */
        .reg-body { padding: 30px 36px 24px; }

        .form-step { display: none; animation: fadeUp .28s ease; }
        .form-step.active { display: block; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .step-title {
            font-size: 13px; font-weight: 700; color: #1a1a1a;
            margin-bottom: 22px; padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
            display: flex; align-items: center; gap: 8px;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .step-title i { color: #c62828; font-size: 17px; }

        /* ── Grid ────────────────────────────────────────────── */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        @media (max-width: 520px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }

        .form-group label {
            font-size: 11px; font-weight: 700; color: #555;
            text-transform: uppercase; letter-spacing: .6px;
        }
        .req { color: #c62828; }

        /* ── Input wrapper con celda de icono separada ───────── */
        .input-wrap {
            display: flex; align-items: stretch;
            border: 1.5px solid #ddd; border-radius: 9px;
            overflow: hidden; background: #fff;
            transition: border-color .2s, box-shadow .2s;
        }
        .input-wrap:focus-within {
            border-color: #c62828;
            box-shadow: 0 0 0 3px rgba(198,40,40,.1);
        }
        .ico-cell {
            display: flex; align-items: center; justify-content: center;
            width: 42px; min-width: 42px;
            background: #f7f7f7;
            border-right: 1.5px solid #e8e8e8;
            color: #c62828; font-size: 15px;
            flex-shrink: 0;
        }
        .input-wrap input,
        .input-wrap select,
        .input-wrap textarea {
            flex: 1; border: none; outline: none;
            background: #fff; color: #1a1a1a;
            font-size: 14px; padding: 10px 12px;
            font-family: inherit; appearance: none; min-width: 0;
        }
        .input-wrap select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 32px; cursor: pointer;
        }
        .input-wrap textarea { min-height: 80px; resize: vertical; }
        .input-wrap input::placeholder { color: #bbb; }
        .input-wrap input[type="date"] { color: #333; }
        .input-wrap input[type="file"] {
            padding: 8px 12px; cursor: pointer;
            color: #555; font-size: 13px;
        }
        .input-wrap input[type="file"]::file-selector-button {
            background: #f0f0f0; border: 1px solid #ddd;
            border-radius: 5px; padding: 4px 10px;
            font-size: 12px; cursor: pointer;
            margin-right: 10px; color: #444;
        }
        .toggle-btn {
            background: none; border: none;
            padding: 0 12px; color: #aaa; cursor: pointer;
            font-size: 16px; display: flex; align-items: center;
            transition: color .2s;
        }
        .toggle-btn:hover { color: #c62828; }

        /* ── Notices ─────────────────────────────────────────── */
        .notice {
            background: #fff8e1; border: 1.5px solid #ffe082;
            border-radius: 9px; padding: 13px 16px;
            font-size: 13px; color: #5d4037;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .notice i { color: #f9a825; font-size: 16px; margin-top: 1px; flex-shrink: 0; }

        .notice-info {
            background: #e8f5e9; border: 1.5px solid #a5d6a7;
            border-radius: 9px; padding: 13px 16px;
            font-size: 13px; color: #1b5e20;
            display: flex; gap: 10px; align-items: flex-start;
        }
        .notice-info i { color: #388e3c; font-size: 16px; margin-top: 1px; flex-shrink: 0; }

        /* ── Sección alumno dentro de tutor ──────────────────── */
        .alumno-section {
            margin-top: 22px; padding-top: 18px;
            border-top: 2px dashed #e0e0e0;
        }
        .alumno-toggle {
            display: inline-flex; align-items: center; gap: 9px;
            cursor: pointer; font-size: 13px; font-weight: 700;
            color: #c62828; margin-bottom: 0;
            padding: 9px 14px; border-radius: 8px;
            border: 1.5px solid #f5c6c6; background: #fff5f5;
            transition: background .2s, border-color .2s;
            user-select: none;
        }
        .alumno-toggle:hover { background: #ffe8e8; border-color: #c62828; }
        .alumno-toggle i { font-size: 17px; }

        .alumno-fields { display: none; margin-top: 18px; }
        .alumno-fields.open { display: block; animation: fadeUp .25s ease; }

        /* ── Field error ─────────────────────────────────────── */
        .field-error {
            font-size: 11.5px; color: #c62828;
            display: flex; align-items: center; gap: 4px; margin-top: 2px;
        }

        /* ── Buttons ─────────────────────────────────────────── */
        .btn-row {
            display: flex; justify-content: space-between;
            align-items: center; margin-top: 28px; gap: 10px;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 11px 24px; border-radius: 9px;
            font-size: 14px; font-weight: 600;
            border: none; cursor: pointer;
            transition: all .2s; font-family: inherit;
        }
        .btn-outline { background: #f5f5f5; border: 1.5px solid #ddd; color: #555; }
        .btn-outline:hover { background: #eee; color: #333; }
        .btn-primary { background: #c62828; color: #fff; }
        .btn-primary:hover { background: #b71c1c; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(198,40,40,.3); }
        .btn-success { background: #2e7d32; color: #fff; }
        .btn-success:hover { background: #1b5e20; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(46,125,50,.3); }

        /* ── Footer ──────────────────────────────────────────── */
        .reg-footer {
            text-align: center; padding: 14px 36px 26px;
            font-size: 13px; color: #888;
            background: #fafafa; border-top: 1px solid #f0f0f0;
        }
        .reg-footer a { color: #c62828; text-decoration: none; font-weight: 600; }
        .reg-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="reg-card">

    <div class="reg-header">
        <h1><i class="bi bi-person-plus-fill"></i>&nbsp; Crear cuenta</h1>
        <p>Completa los pasos para registrarte en el sistema del Dojo</p>
    </div>

    <div class="stepper">
        <div class="step-item active" id="stp-1">
            <div class="step-circle" id="sc-1">1</div>
            <div class="step-label">Datos<br>personales</div>
        </div>
        <div class="step-item" id="stp-2">
            <div class="step-circle" id="sc-2">2</div>
            <div class="step-label">Cuenta &amp;<br>acceso</div>
        </div>
        <div class="step-item" id="stp-3">
            <div class="step-circle" id="sc-3">3</div>
            <div class="step-label">Info<br>adicional</div>
        </div>
    </div>

    <div class="reg-body">
    <form id="regForm" action="{{ route('registro.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- ══════════════ PASO 1 ══════════════ --}}
        <div class="form-step active" id="step-1">
            <div class="step-title"><i class="bi bi-person-circle"></i> Información personal</div>
            <div class="form-grid">

                <div class="form-group">
                    <label>Nombre(s) <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-person"></i></span>
                        <input type="text" name="nombre" placeholder="Nombre(s)" required value="{{ old('nombre') }}">
                    </div>
                    @error('nombre')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>Apellido Paterno <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-person"></i></span>
                        <input type="text" name="apaterno" placeholder="Apellido Paterno" required value="{{ old('apaterno') }}">
                    </div>
                    @error('apaterno')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>Apellido Materno <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-person"></i></span>
                        <input type="text" name="amaterno" placeholder="Apellido Materno" required value="{{ old('amaterno') }}">
                    </div>
                    @error('amaterno')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>Fecha de Nacimiento <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-calendar-date"></i></span>
                        <input type="date" name="fecha_naci" required value="{{ old('fecha_naci') }}">
                    </div>
                    @error('fecha_naci')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>Teléfono <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="tel" placeholder="10 dígitos" required
                               minlength="10" maxlength="10" pattern="[0-9]{10}"
                               value="{{ old('tel') }}">
                    </div>
                    @error('tel')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>Correo Electrónico <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="correo" placeholder="correo@ejemplo.com" required value="{{ old('correo') }}">
                    </div>
                    @error('correo')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

            </div>
            <div class="btn-row">
                <span></span>
                <button type="button" class="btn btn-primary" onclick="goTo(2)">
                    Siguiente <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ══════════════ PASO 2 ══════════════ --}}
        <div class="form-step" id="step-2">
            <div class="step-title"><i class="bi bi-shield-lock-fill"></i> Cuenta y acceso</div>
            <div class="form-grid">

                <div class="form-group">
                    <label>Contraseña <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-lock"></i></span>
                        <input type="password" name="pass" id="pass" placeholder="Mín. 8 caracteres"
                               required minlength="8"
                               pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&amp;*()_+\-=\[\]{};':\\|,.\/?]).{8,}"
                               title="Al menos 8 caracteres, 1 mayúscula y 1 símbolo.">
                        <button type="button" class="toggle-btn" onclick="togglePass('pass','iconP')">
                            <i class="bi bi-eye" id="iconP"></i>
                        </button>
                    </div>
                    @error('pass')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label>Confirmar contraseña <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" id="pass_confirm" placeholder="Repite la contraseña" required minlength="8">
                        <button type="button" class="toggle-btn" onclick="togglePass('pass_confirm','iconPC')">
                            <i class="bi bi-eye" id="iconPC"></i>
                        </button>
                    </div>
                    <span class="field-error" id="passMatchErr" style="display:none">
                        <i class="bi bi-exclamation-circle"></i> Las contraseñas no coinciden.
                    </span>
                </div>

                <div class="form-group full">
                    <label>Rol <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-person-badge"></i></span>
                        <select name="rol" id="rolSelect" required onchange="handleRolChange(this.value)">
                            <option value="">— Selecciona un rol —</option>
                            <option value="sensei" {{ old('rol')=='sensei' ?'selected':'' }}>Sensei</option>
                            <option value="tutor"  {{ old('rol')=='tutor'  ?'selected':'' }}>Tutor</option>
                            <option value="alumno" {{ old('rol')=='alumno' ?'selected':'' }}>Alumno</option>
                        </select>
                    </div>
                    @error('rol')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

                <div class="form-group full">
                    <label>Fecha de registro <span class="req">*</span></label>
                    <div class="input-wrap">
                        <span class="ico-cell"><i class="bi bi-calendar-check"></i></span>
                        <input type="date" name="fecha_registro" required value="{{ old('fecha_registro', date('Y-m-d')) }}">
                    </div>
                    @error('fecha_registro')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                </div>

            </div>
            <div class="btn-row">
                <button type="button" class="btn btn-outline" onclick="goTo(1)">
                    <i class="bi bi-arrow-left"></i> Atrás
                </button>
                <button type="button" class="btn btn-primary" onclick="goTo(3)">
                    Siguiente <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

        {{-- ══════════════ PASO 3 ══════════════ --}}
        <div class="form-step" id="step-3">
            <div class="step-title"><i class="bi bi-clipboard2-data-fill"></i> Información adicional</div>

            {{-- Sin rol --}}
            <div id="sec-ninguno">
                <div class="notice">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Regresa al paso anterior y elige tu rol para ver los campos correspondientes.
                </div>
            </div>

            {{-- Sensei --}}
            <div id="sec-sensei" style="display:none;">
                <div class="notice-info">
                    <i class="bi bi-check-circle-fill"></i>
                    Los senseis no requieren datos adicionales. Puedes finalizar tu registro.
                </div>
            </div>

            {{-- ─── TUTOR ─── --}}
            <div id="sec-tutor" style="display:none;">
                <div class="form-grid">

                    <div class="form-group full">
                        <label>Ocupación <span class="req">*</span></label>
                        <div class="input-wrap">
                            <span class="ico-cell"><i class="bi bi-briefcase"></i></span>
                            <select name="ocupacion">
                                <option value="">— Selecciona una ocupación —</option>
                                @foreach($ocupaciones ?? [] as $ocu)
                                    <option value="{{ $ocu->id_ocupacion }}" {{ old('ocupacion') == $ocu->id_ocupacion ? 'selected' : '' }}>
                                        {{ $ocu->nombre_ocupacion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('ocupacion')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group full">
                        <label>Relación con el estudiante <span class="req">*</span></label>
                        <div class="input-wrap">
                            <span class="ico-cell"><i class="bi bi-people"></i></span>
                            <select name="relacion_estudiante">
                                <option value="">— Selecciona —</option>
                                <option value="Padre"       {{ old('relacion_estudiante')=='Padre'       ?'selected':'' }}>Padre</option>
                                <option value="Madre"       {{ old('relacion_estudiante')=='Madre'       ?'selected':'' }}>Madre</option>
                                <option value="Abuelo/a"    {{ old('relacion_estudiante')=='Abuelo/a'    ?'selected':'' }}>Abuelo/a</option>
                                <option value="Tío/a"       {{ old('relacion_estudiante')=='Tío/a'       ?'selected':'' }}>Tío/a</option>
                                <option value="Tutor Legal" {{ old('relacion_estudiante')=='Tutor Legal' ?'selected':'' }}>Tutor Legal</option>
                                <option value="Otro"        {{ old('relacion_estudiante')=='Otro'        ?'selected':'' }}>Otro</option>
                            </select>
                        </div>
                        @error('relacion_estudiante')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                    </div>

                </div>

                {{-- Botón para agregar alumno --}}
                <div class="alumno-section">
                    <button type="button" class="alumno-toggle" onclick="toggleAlumno()">
                        <i class="bi bi-person-plus-fill" id="alumnoIco"></i>
                        <span id="alumnoTxt">¿Deseas registrar a un alumno a tu cargo ahora?</span>
                    </button>

                    <div class="alumno-fields" id="alumnoFields">
                        <div class="notice-info" style="margin-bottom:16px;">
                            <i class="bi bi-info-circle-fill"></i>
                            Los datos del alumno se vincularán automáticamente a tu cuenta como tutor.
                        </div>
                        <div class="form-grid">

                            <div class="form-group full">
                                <label>Nombre(s) del alumno <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" name="alumno_nombre" placeholder="Nombre(s) y apellidos" value="{{ old('alumno_nombre') }}">
                                </div>
                                @error('alumno_nombre')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Correo del alumno <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="alumno_correo" placeholder="correo@ejemplo.com" value="{{ old('alumno_correo') }}">
                                </div>
                                @error('alumno_correo')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Contraseña del alumno <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="alumno_pass" id="alumno_pass" placeholder="Mín. 8 caracteres"
                                           minlength="8"
                                           pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':\\|,.\/?]).{8,}"
                                           title="Al menos 8 caracteres, 1 mayúscula y 1 símbolo." value="{{ old('alumno_pass') }}">
                                    <button type="button" class="toggle-btn" onclick="togglePass('alumno_pass','iconAP')">
                                        <i class="bi bi-eye" id="iconAP"></i>
                                    </button>
                                </div>
                                @error('alumno_pass')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Confirmar contraseña <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" id="alumno_pass_confirm" placeholder="Repite la contraseña" minlength="8">
                                    <button type="button" class="toggle-btn" onclick="togglePass('alumno_pass_confirm','iconAPC')">
                                        <i class="bi bi-eye" id="iconAPC"></i>
                                    </button>
                                </div>
                                <span class="field-error" id="alumnoPassMatchErr" style="display:none">
                                    <i class="bi bi-exclamation-circle"></i> Las contraseñas no coinciden.
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Grado <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-bar-chart-steps"></i></span>
                                    <select name="alumno_grado">
                                        <option value="">— Selecciona —</option>
                                        @foreach($grados ?? [] as $grado)
                                            <option value="{{ $grado->id_grado }}" {{ old('alumno_grado')==$grado->id_grado?'selected':'' }}>
                                                {{ $grado->nombreGrado }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('alumno_grado')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Fecha de inscripción <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-calendar-event"></i></span>
                                    <input type="date" name="alumno_fecha_inscrip" value="{{ old('alumno_fecha_inscrip', date('Y-m-d')) }}">
                                </div>
                                @error('alumno_fecha_inscrip')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group full">
                                <label>Documento médico (PDF, máx. 5 MB) <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-file-earmark-medical"></i></span>
                                    <input type="file" name="alumno_documento_medico" accept=".pdf">
                                </div>
                                @error('alumno_documento_medico')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                        </div>
                    </div>{{-- /alumnoFields --}}
                </div>{{-- /alumno-section --}}
            </div>{{-- /sec-tutor --}}

            {{-- ─── ALUMNO ─── --}}
            {{-- ─── ALUMNO ─── --}}
            <div id="sec-alumno" style="display:none;">

                {{-- Aviso dinámico de mayoría de edad --}}
                <div id="notice-menor" class="notice" style="display:none; margin-bottom:18px;">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    El alumno es <strong>menor de edad</strong>. Se requiere un tutor responsable.
                </div>
                <div id="notice-mayor" class="notice-info" style="display:none; margin-bottom:18px;">
                    <i class="bi bi-check-circle-fill"></i>
                    El alumno es <strong>mayor de edad</strong>. El tutor es opcional.
                </div>

                <div class="form-grid">

                    {{-- Selector de tutor: obligatorio si menor, opcional si mayor --}}
                    <div class="form-group full" id="tutorSelectWrap">
                        <label>
                            Tutor responsable
                            <span class="req" id="tutorReqStar">*</span>
                            <span id="tutorOptTag" style="display:none; font-size:11px; color:#888; font-weight:400;">(Opcional)</span>
                        </label>
                        <div class="input-wrap">
                            <span class="ico-cell"><i class="bi bi-person-check"></i></span>
                            <select name="id_Tutor" id="id_Tutor_select">
                                <option value="">— Sin tutor / Selecciona un tutor —</option>
                                @foreach($tutores ?? [] as $tutor)
                                    <option value="{{ $tutor->id_Tutor }}" {{ old('id_Tutor')==$tutor->id_Tutor?'selected':'' }}>
                                        {{ $tutor->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('id_Tutor')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Grado <span class="req">*</span></label>
                        <div class="input-wrap">
                            <span class="ico-cell"><i class="bi bi-bar-chart-steps"></i></span>
                            <select name="grado">
                                <option value="">— Selecciona —</option>
                                @foreach($grados ?? [] as $grado)
                                    <option value="{{ $grado->id_grado }}" {{ old('grado')==$grado->id_grado?'selected':'' }}>
                                        {{ $grado->nombreGrado }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('grado')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label>Fecha de inscripción <span class="req">*</span></label>
                        <div class="input-wrap">
                            <span class="ico-cell"><i class="bi bi-calendar-event"></i></span>
                            <input type="date" name="Fecha_inscrip" value="{{ old('Fecha_inscrip', date('Y-m-d')) }}">
                        </div>
                        @error('Fecha_inscrip')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group full">
                        <label>Documento médico (PDF, máx. 5 MB) <span class="req">*</span></label>
                        <div class="input-wrap">
                            <span class="ico-cell"><i class="bi bi-file-earmark-medical"></i></span>
                            <input type="file" name="documento_medico" accept=".pdf">
                        </div>
                        @error('documento_medico')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                    </div>

                </div>

                {{-- Botón para registrar tutor nuevo --}}
                <div class="alumno-section">
                    <button type="button" class="alumno-toggle" onclick="toggleTutor()">
                        <i class="bi bi-person-plus-fill" id="tutorIco"></i>
                        <span id="tutorTxt">¿El tutor aún no está registrado? Regístralo aquí</span>
                    </button>

                    <div class="alumno-fields" id="tutorFields">
                        <div class="notice-info" style="margin-bottom:16px; margin-top:16px;">
                            <i class="bi bi-info-circle-fill"></i>
                            El tutor se registrará al mismo tiempo. Podrá iniciar sesión con su correo después.
                        </div>
                        <div class="form-grid">

                            <div class="form-group">
                                <label>Nombre(s) del tutor <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-person"></i></span>
                                    <input type="text" name="tutor_nombre" placeholder="Nombre(s)" value="{{ old('tutor_nombre') }}">
                                </div>
                                @error('tutor_nombre')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Apellido Paterno <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-person"></i></span>
                                    <input type="text" name="tutor_apaterno" placeholder="Apellido Paterno" value="{{ old('tutor_apaterno') }}">
                                </div>
                                @error('tutor_apaterno')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Apellido Materno <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-person"></i></span>
                                    <input type="text" name="tutor_amaterno" placeholder="Apellido Materno" value="{{ old('tutor_amaterno') }}">
                                </div>
                                @error('tutor_amaterno')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Correo del tutor <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="tutor_correo" placeholder="correo@ejemplo.com" value="{{ old('tutor_correo') }}">
                                </div>
                                @error('tutor_correo')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Teléfono del tutor <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="tutor_tel" placeholder="10 dígitos"
                                           minlength="10" maxlength="10" pattern="[0-9]{10}"
                                           value="{{ old('tutor_tel') }}">
                                </div>
                                @error('tutor_tel')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Ocupación <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-briefcase"></i></span>
                                    <select name="tutor_ocupacion">
                                        <option value="">— Selecciona una ocupación —</option>
                                        @foreach($ocupaciones ?? [] as $ocu)
                                            <option value="{{ $ocu->id_ocupacion }}" {{ old('tutor_ocupacion') == $ocu->id_ocupacion ? 'selected' : '' }}>
                                                {{ $ocu->nombre_ocupacion }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('tutor_ocupacion')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Contraseña del tutor <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-lock"></i></span>
                                    <input type="password" name="tutor_pass" id="tutor_pass" placeholder="Mín. 8 caracteres"
                                           minlength="8"
                                           pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':\\|,.\/?]).{8,}"
                                           title="Al menos 8 caracteres, 1 mayúscula y 1 símbolo." value="{{ old('tutor_pass') }}">
                                    <button type="button" class="toggle-btn" onclick="togglePass('tutor_pass','iconTP')">
                                        <i class="bi bi-eye" id="iconTP"></i>
                                    </button>
                                </div>
                                @error('tutor_pass')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                            <div class="form-group">
                                <label>Confirmar contraseña <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" id="tutor_pass_confirm" placeholder="Repite la contraseña" minlength="8">
                                    <button type="button" class="toggle-btn" onclick="togglePass('tutor_pass_confirm','iconTPC')">
                                        <i class="bi bi-eye" id="iconTPC"></i>
                                    </button>
                                </div>
                                <span class="field-error" id="tutorPassMatchErr" style="display:none">
                                    <i class="bi bi-exclamation-circle"></i> Las contraseñas no coinciden.
                                </span>
                            </div>

                            <div class="form-group full">
                                <label>Relación con el estudiante <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <span class="ico-cell"><i class="bi bi-people"></i></span>
                                    <select name="tutor_relacion">
                                        <option value="">— Selecciona —</option>
                                        <option value="Padre"       {{ old('tutor_relacion')=='Padre'       ?'selected':'' }}>Padre</option>
                                        <option value="Madre"       {{ old('tutor_relacion')=='Madre'       ?'selected':'' }}>Madre</option>
                                        <option value="Abuelo/a"    {{ old('tutor_relacion')=='Abuelo/a'    ?'selected':'' }}>Abuelo/a</option>
                                        <option value="Tío/a"       {{ old('tutor_relacion')=='Tío/a'       ?'selected':'' }}>Tío/a</option>
                                        <option value="Tutor Legal" {{ old('tutor_relacion')=='Tutor Legal' ?'selected':'' }}>Tutor Legal</option>
                                        <option value="Otro"        {{ old('tutor_relacion')=='Otro'        ?'selected':'' }}>Otro</option>
                                    </select>
                                </div>
                                @error('tutor_relacion')<span class="field-error"><i class="bi bi-exclamation-circle"></i>{{ $message }}</span>@enderror
                            </div>

                        </div>
                    </div>{{-- /tutorFields --}}
                </div>{{-- /alumno-section --}}
            </div>{{-- /sec-alumno --}}

            <div class="btn-row">
                <button type="button" class="btn btn-outline" onclick="goTo(2)">
                    <i class="bi bi-arrow-left"></i> Atrás
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check2-circle"></i> Finalizar registro
                </button>
            </div>
        </div>{{-- /step-3 --}}

    </form>
    </div>{{-- /reg-body --}}

    <div class="reg-footer">
        ¿Ya tienes cuenta? <a href="{{ route('verLogin') }}">Inicia sesión aquí</a>
    </div>
</div>

<script>
/* ── Stepper ─────────────────────────────────────────── */
let currentStep = 1;

function goTo(n) {
    if (n > 1 && currentStep === 1) {
        for (const c of ['nombre','apaterno','amaterno','fecha_naci','tel','correo']) {
            const el = document.querySelector(`[name="${c}"]`);
            if (!el || !el.checkValidity()) { el.reportValidity(); return; }
        }
    }
    if (n > 2 && currentStep === 2) {
        const pass = document.getElementById('pass');
        const conf = document.getElementById('pass_confirm');
        const rol  = document.getElementById('rolSelect');
        if (!pass.checkValidity()) { pass.reportValidity(); return; }
        if (pass.value !== conf.value) {
            document.getElementById('passMatchErr').style.display = 'flex'; conf.focus(); return;
        }
        document.getElementById('passMatchErr').style.display = 'none';
        if (!rol.checkValidity()) { rol.reportValidity(); return; }
        handleRolChange(rol.value);
    }

    // Transición
    const prev = document.getElementById(`stp-${currentStep}`);
    document.getElementById(`step-${currentStep}`).classList.remove('active');
    prev.classList.remove('active');
    if (n > currentStep) {
        prev.classList.add('done');
        document.getElementById(`sc-${currentStep}`).innerHTML = '<i class="bi bi-check-lg" style="font-size:13px"></i>';
    } else {
        prev.classList.remove('done');
        document.getElementById(`sc-${currentStep}`).textContent = currentStep;
    }
    currentStep = n;
    document.getElementById(`step-${n}`).classList.add('active');
    const next = document.getElementById(`stp-${n}`);
    next.classList.remove('done'); next.classList.add('active');
    document.getElementById(`sc-${n}`).textContent = n;
}

/* ── Calcular si el alumno es mayor de edad según fecha_naci ── */
function calcularEdadAlumno() {
    const fechaStr = document.querySelector('[name="fecha_naci"]').value;
    if (!fechaStr) return null;
    const hoy    = new Date();
    const nacido = new Date(fechaStr);
    let edad = hoy.getFullYear() - nacido.getFullYear();
    const m  = hoy.getMonth() - nacido.getMonth();
    if (m < 0 || (m === 0 && hoy.getDate() < nacido.getDate())) edad--;
    return edad;
}

/* ── Actualizar UI tutor según mayoría de edad ────────────── */
function actualizarTutorPorEdad() {
    const rol = document.getElementById('rolSelect').value;
    if (rol !== 'alumno') return;

    const edad         = calcularEdadAlumno();
    const esMayor      = edad !== null && edad >= 18;
    const notMenor     = document.getElementById('notice-menor');
    const notMayor     = document.getElementById('notice-mayor');
    const reqStar      = document.getElementById('tutorReqStar');
    const optTag       = document.getElementById('tutorOptTag');
    const selectTutor  = document.getElementById('id_Tutor_select');
    const btnToggle    = document.querySelector('.alumno-toggle[onclick="toggleTutor()"]');

    if (edad === null) {
        // Sin fecha aún: mostrar como menor por defecto
        if (notMenor) notMenor.style.display = 'none';
        if (notMayor) notMayor.style.display = 'none';
        setReq('id_Tutor', true);
        if (reqStar) reqStar.style.display = 'inline';
        if (optTag)  optTag.style.display  = 'none';
        return;
    }

    if (esMayor) {
        // Mayor de edad: tutor opcional, ocultar sección si no tiene tutor abierta
        if (notMenor) notMenor.style.display = 'none';
        if (notMayor) notMayor.style.display = 'flex';
        setReq('id_Tutor', false);
        if (selectTutor) selectTutor.required = false;
        if (reqStar) reqStar.style.display = 'none';
        if (optTag)  optTag.style.display  = 'inline';
        if (btnToggle) btnToggle.style.display = 'inline-flex';
    } else {
        // Menor de edad: tutor obligatorio
        if (notMenor) notMenor.style.display = 'flex';
        if (notMayor) notMayor.style.display = 'none';
        setReq('id_Tutor', true);
        if (selectTutor) selectTutor.required = true;
        if (reqStar) reqStar.style.display = 'inline';
        if (optTag)  optTag.style.display  = 'none';
        if (btnToggle) btnToggle.style.display = 'inline-flex';
    }
}

/* ── Rol → sección ───────────────────────────────────── */
function handleRolChange(rol) {
    ['ninguno','sensei','tutor','alumno'].forEach(s =>
        document.getElementById(`sec-${s}`).style.display = 'none'
    );
    const map = { sensei:'sec-sensei', tutor:'sec-tutor', alumno:'sec-alumno' };
    document.getElementById(map[rol] || 'sec-ninguno').style.display = 'block';

    setReq('ocupacion',           rol === 'tutor');
    setReq('relacion_estudiante', rol === 'tutor');
    setReq('grado',               rol === 'alumno');
    setReq('Fecha_inscrip',       rol === 'alumno');
    setReq('documento_medico',    rol === 'alumno');

    // Al mostrar sec-alumno recalcular tutor según edad ya ingresada
    if (rol === 'alumno') actualizarTutorPorEdad();
}

// Escuchar cambios en fecha_naci para actualizar aviso y required del tutor
document.querySelector('[name="fecha_naci"]').addEventListener('change', actualizarTutorPorEdad);

function setReq(name, val) {
    const el = document.querySelector(`[name="${name}"]`);
    if (el) el.required = val;
}

/* ── Toggle alumno dentro de tutor ───────────────────── */
let alumnoOpen = false;

function toggleAlumno() {
    alumnoOpen = !alumnoOpen;
    const fields = document.getElementById('alumnoFields');
    const ico    = document.getElementById('alumnoIco');
    const txt    = document.getElementById('alumnoTxt');

    if (alumnoOpen) {
        fields.classList.add('open');
        ico.className = 'bi bi-dash-circle-fill';
        txt.textContent = 'Cancelar — no registrar alumno ahora';
        ['alumno_nombre','alumno_correo','alumno_pass','alumno_grado','alumno_fecha_inscrip','alumno_documento_medico']
            .forEach(n => setReq(n, true));
    } else {
        fields.classList.remove('open');
        ico.className = 'bi bi-person-plus-fill';
        txt.textContent = '¿Deseas registrar a un alumno a tu cargo ahora?';
        ['alumno_nombre','alumno_correo','alumno_pass','alumno_grado','alumno_fecha_inscrip','alumno_documento_medico']
            .forEach(n => setReq(n, false));
    }
}

/* ── Toggle tutor nuevo dentro de alumno ─────────────── */
let tutorOpen = false;

function toggleTutor() {
    tutorOpen = !tutorOpen;
    const fields     = document.getElementById('tutorFields');
    const ico        = document.getElementById('tutorIco');
    const txt        = document.getElementById('tutorTxt');
    const selectWrap = document.getElementById('tutorSelectWrap');
    const selectEl   = document.getElementById('id_Tutor_select');

    const esMayorEdad = (calcularEdadAlumno() ?? 0) >= 18;

    if (tutorOpen) {
        fields.classList.add('open');
        ico.className = 'bi bi-dash-circle-fill';
        txt.textContent = 'Cancelar — seleccionar tutor existente';
        selectWrap.style.display = 'none';
        selectEl.required = false;
        selectEl.value = '';
        ['tutor_nombre','tutor_apaterno','tutor_amaterno','tutor_correo','tutor_tel','tutor_ocupacion','tutor_pass','tutor_relacion']
            .forEach(n => setReq(n, true));
    } else {
        fields.classList.remove('open');
        ico.className = 'bi bi-person-plus-fill';
        txt.textContent = '¿El tutor aún no está registrado? Regístralo aquí';
        selectWrap.style.display = '';
        // Si es mayor de edad el tutor vuelve a ser opcional; si es menor, obligatorio
        selectEl.required = !esMayorEdad;
        ['tutor_nombre','tutor_apaterno','tutor_amaterno','tutor_correo','tutor_tel','tutor_ocupacion','tutor_pass','tutor_relacion']
            .forEach(n => setReq(n, false));
    }
}

/* ── Toggle password ─────────────────────────────────── */
function togglePass(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    ico.classList.toggle('bi-eye');
    ico.classList.toggle('bi-eye-slash');
}

/* ── SweetAlert errores del servidor ─────────────────── */
@if ($errors->any())
    Swal.fire({
        title: 'Error en el registro',
        html: `{!! implode('<br>', $errors->all()) !!}`,
        icon: 'error',
        confirmButtonColor: '#c62828',
        confirmButtonText: 'Entendido'
    });
@endif

@if (session('success'))
    Swal.fire({
        title: '¡Registro exitoso!',
        text: '{{ session('success') }}',
        icon: 'success',
        confirmButtonColor: '#2e7d32',
        confirmButtonText: 'Ir al login'
    }).then(() => { window.location.href = '{{ route('verLogin') }}'; });
@endif

/* ── Validar contraseñas de sub-registros antes de enviar ── */
document.getElementById('regForm').addEventListener('submit', function(e) {
    // Validar contraseña alumno extra (dentro de tutor)
    if (alumnoOpen) {
        const ap  = document.getElementById('alumno_pass');
        const apc = document.getElementById('alumno_pass_confirm');
        if (ap && apc && ap.value !== apc.value) {
            document.getElementById('alumnoPassMatchErr').style.display = 'flex';
            ap.focus();
            e.preventDefault();
            return;
        }
        document.getElementById('alumnoPassMatchErr').style.display = 'none';
    }
    // Validar contraseña tutor nuevo (dentro de alumno)
    if (tutorOpen) {
        const tp  = document.getElementById('tutor_pass');
        const tpc = document.getElementById('tutor_pass_confirm');
        if (tp && tpc && tp.value !== tpc.value) {
            document.getElementById('tutorPassMatchErr').style.display = 'flex';
            tp.focus();
            e.preventDefault();
            return;
        }
        document.getElementById('tutorPassMatchErr').style.display = 'none';
    }
});
</script>

</body>
</html>