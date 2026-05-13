<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gestión de Alumnos - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* ── Bachiller ──────────────────────────────────────────── */
        .bachiller-box {
            border: 1.5px solid #dee2e6;
            border-radius: 12px;
            padding: 18px 20px;
            background: #f8f9fa;
            margin-top: 10px;
        }
        .bachiller-check-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 700;
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 0;
        }
        .bachiller-check-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #c62828;
            flex-shrink: 0;
        }
        .bachiller-fields { display: none; margin-top: 16px; }
        .bachiller-fields.show { display: block; }
        .badge-bachiller {
            background: #e3f2fd;
            color: #1565c0;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .badge-no-bachiller { color: #bdbdbd; font-size: 0.8rem; }
        .sch-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: #e8f5e9; color: #2e7d32;
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 700;
        }

        /* ── Botones header catálogos ────────────────────────────── */
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .btn-catalogo {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #fff;
            border: 1.5px solid #c62828;
            color: #c62828;
            border-radius: 8px;
            padding: 7px 16px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .btn-catalogo:hover { background: #c62828; color: #fff; }

        /* ── Seminario cards en modal historial ─────────────────── */
        .seminario-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 12px;
            background: #fff;
            position: relative;
        }
        .seminario-card:last-child { margin-bottom: 0; }
        .seminario-card .sc-nombre {
            font-weight: 700;
            color: #c62828;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }
        .seminario-card .sc-meta {
            font-size: 0.82rem;
            color: #666;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 6px;
        }
        .seminario-card .sc-obs {
            font-size: 0.82rem;
            color: #444;
            background: #f5f5f5;
            border-radius: 6px;
            padding: 6px 10px;
        }
        .seminario-card .sc-delete {
            position: absolute;
            top: 10px; right: 10px;
            background: none; border: none;
            color: #bdbdbd; cursor: pointer;
            font-size: 1rem; padding: 2px 6px;
            border-radius: 4px; transition: color .2s;
        }
        .seminario-card .sc-delete:hover { color: #e53935; }
        .seminario-empty {
            text-align: center; color: #9e9e9e;
            padding: 30px 0; font-size: 0.9rem;
        }

        /* ── Tabs en modal historial ────────────────────────────── */
        .hist-tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 18px;
            gap: 4px;
        }
        .hist-tab {
            padding: 8px 20px; border: none;
            background: none; cursor: pointer;
            font-size: 0.9rem; color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px; font-weight: 600;
            transition: color .2s, border-color .2s;
        }
        .hist-tab.active { color: #c62828; border-bottom-color: #c62828; }
        .hist-panel { display: none; }
        .hist-panel.active { display: block; }

        /* ── Formulario añadir participación (modal historial) ──── */
        .add-seminario-form {
            background: #f8f9fa;
            border: 1.5px solid #dee2e6;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .add-seminario-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 10px;
        }
        .add-seminario-form .form-row.full { grid-template-columns: 1fr; }
        .add-seminario-form label {
            font-size: 0.82rem; font-weight: 600;
            color: #555; margin-bottom: 4px; display: block;
        }
        .add-seminario-form select,
        .add-seminario-form input,
        .add-seminario-form textarea {
            width: 100%; padding: 7px 10px;
            border: 1px solid #ccc; border-radius: 6px;
            font-size: 0.88rem; background: #fff;
            box-sizing: border-box;
        }
        .add-seminario-form textarea { resize: vertical; min-height: 60px; }

        /* Bloque campos nuevo seminario (oculto por defecto) */
        .nuevo-sem-fields {
            display: none;
            border-top: 1px dashed #ccc;
            padding-top: 12px;
            margin-top: 10px;
        }
        .nuevo-sem-fields.show { display: block; }
        .nuevo-sem-fields .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 10px;
        }
        .nuevo-sem-fields .form-row.full { grid-template-columns: 1fr; }

        .btn-add-sem {
            background: #c62828; color: #fff;
            border: none; border-radius: 7px;
            padding: 8px 18px; font-size: 0.88rem;
            font-weight: 600; cursor: pointer;
            display: inline-flex; align-items: center;
            gap: 6px; transition: background .2s;
        }
        .btn-add-sem:hover { background: #b71c1c; }
        .btn-link-sem {
            background: none; border: none;
            color: #1565c0; font-size: 0.82rem;
            cursor: pointer; text-decoration: underline;
            padding: 0; margin-left: 8px;
        }

        /* ── Modal catálogo de grados ────────────────────────────── */
        .cat-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .cat-table th {
            background: #c62828; color: #fff;
            padding: 8px 12px; text-align: left; font-size: 0.82rem;
        }
        .cat-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .cat-table tr:last-child td { border-bottom: none; }
        .cat-table tr:hover td { background: #fafafa; }
        .cat-form-inline {
            display: grid;
            grid-template-columns: 1fr 100px auto;
            gap: 10px;
            align-items: end;
            margin-bottom: 16px;
        }
        .cat-form-inline label {
            font-size: 0.82rem; font-weight: 600;
            color: #555; margin-bottom: 4px; display: block;
        }
        .cat-form-inline input {
            width: 100%; padding: 7px 10px;
            border: 1px solid #ccc; border-radius: 6px;
            font-size: 0.88rem; box-sizing: border-box;
        }
        .btn-edit-inline {
            background: none; border: none;
            color: #1565c0; cursor: pointer;
            font-size: 0.88rem; padding: 4px 8px;
            border-radius: 4px; transition: background .2s;
        }
        .btn-edit-inline:hover { background: #e3f2fd; }
        .btn-delete-inline {
            background: none; border: none;
            color: #bdbdbd; cursor: pointer;
            font-size: 0.88rem; padding: 4px 8px;
            border-radius: 4px; transition: color .2s;
        }
        .btn-delete-inline:hover { color: #e53935; }

        /* ── Toast notificación inline ───────────────────────────── */
        .toast-inline {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 12px;
            display: none;
        }
        .toast-inline.success { background: #e8f5e9; color: #2e7d32; display: block; }
        .toast-inline.error   { background: #ffebee; color: #c62828; display: block; }
    </style>
</head>

<body>
@include('includes.menu')

<div class="main-content">

    <header class="header">
        <div>
            <h1 class="header-title">
                <i class="bi bi-person-badge-fill"></i>
                Gestión de Alumnos
            </h1>
            <div class="breadcrumb">
                <a href="{{ route('principal') }}">Inicio</a>
                <i class="bi bi-chevron-right"></i>
                <span>Alumnos</span>
            </div>
            {{-- Botones catálogos — solo admin y sensei --}}
            @if(in_array(auth()->user()->rol ?? '', ['admin', 'sensei']))
            <div class="header-actions">
                <button class="btn-catalogo" onclick="abrirModalGrados()">
                    <i class="bi bi-award-fill"></i> Catálogo de Grados
                </button>
                <button class="btn-catalogo" onclick="abrirModalSeminarios()">
                    <i class="bi bi-journal-text"></i> Catálogo de Seminarios
                </button>
            </div>
            @endif
        </div>
    </header>

    <div class="content-wrapper">

        @if(session('success'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill alert-icon"></i>
                <div><strong>¡Éxito!</strong> {{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill alert-icon"></i>
                <div><strong>Error:</strong> {{ session('error') }}</div>
            </div>
        @endif

        <div class="info-card">
            <h4><i class="bi bi-info-circle-fill"></i> Información Importante</h4>
            <p>
                Los alumnos deben tener un usuario previamente registrado con rol "alumno".
                El grado se registra en el historial de grados. Si el alumno pertenece al bachiller,
                completa la sección correspondiente al momento de registrarlo o al editarlo.
                Usa los botones del encabezado para gestionar el catálogo de grados y seminarios.
            </p>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             FORMULARIO REGISTRAR ALUMNO
        ══════════════════════════════════════════════════════════ --}}
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Alumno</h2>
                <p>Complete la información del alumno y sus datos académicos en el dojo</p>
            </div>

            <form id="registroAlumno" method="POST" action="{{ route('alumnos.store') }}"
                  class="form-body" enctype="multipart/form-data">
                @csrf

                <h3 class="section-title-header">
                    <i class="bi bi-person-circle"></i> Información del Alumno
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="id_alumno">
                            Usuario del Alumno <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-person-badge input-icon"></i>
                            <select id="id_alumno" class="form-select" name="id_alumno" required>
                                <option value="">Seleccione un alumno</option>
                                @foreach($usuariosAlumno as $u)
                                    <option value="{{ $u->id_usuario }}">{{ $u->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <small style="color:#757575;margin-top:5px;display:block;">
                            Solo se muestran usuarios con rol "Alumno" sin inscripción previa
                        </small>
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-award-fill"></i> Información Académica
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="id_grado">
                            Grado Inicial <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-trophy input-icon"></i>
                            <select id="id_grado" class="form-select" name="id_grado" required>
                                <option value="">Seleccione un grado</option>
                                @foreach($grados as $grado)
                                    <option value="{{ $grado->id_grado }}">{{ $grado->nombreGrado }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fecha_inscripcion">
                            Fecha de Inscripción <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-calendar-check input-icon"></i>
                            <input type="date" id="fecha_inscripcion" class="form-input"
                                   name="fecha_inscripcion" required>
                        </div>
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-mortarboard-fill"></i> Datos de Bachiller
                    <small style="font-weight:400;color:#888;font-size:0.82rem;margin-left:8px;">
                        (opcional)
                    </small>
                </h3>
                <div class="form-grid full-width">
                    <div class="form-group">
                        <div class="bachiller-box">
                            <label class="bachiller-check-label">
                                <input type="checkbox" name="es_bachiller" id="esBachillerReg"
                                       value="1" onchange="toggleBachiller('reg', this.checked)">
                                ¿El alumno pertenece al bachiller?
                            </label>
                            <div class="bachiller-fields" id="bachillerFieldsReg">
                                <div class="form-grid" style="margin-top:0;">
                                    <div class="form-group">
                                        <label class="form-label">Número de Control</label>
                                        <div class="form-input-wrapper">
                                            <i class="bi bi-123 input-icon"></i>
                                            <input type="text" class="form-input" name="numero_control"
                                                   placeholder="Ej: 12345678" maxlength="20">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Grupo</label>
                                        <div class="form-input-wrapper">
                                            <i class="bi bi-people-fill input-icon"></i>
                                            <input type="text" class="form-input" name="grupo"
                                                   placeholder="Ej: 3A, 2B" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Especialidad</label>
                                        <div class="form-input-wrapper">
                                            <i class="bi bi-mortarboard-fill input-icon"></i>
                                            <input type="text" class="form-input" name="especialidad"
                                                   placeholder="Ej: Informática" maxlength="100">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Turno</label>
                                        <div class="form-input-wrapper">
                                            <i class="bi bi-clock-fill input-icon"></i>
                                            <select class="form-select" name="turno">
                                                <option value="">— Selecciona —</option>
                                                <option value="Matutino">Matutino</option>
                                                <option value="Vespertino">Vespertino</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-heart-pulse-fill"></i> Información Médica y Física
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="peso">
                            Peso <span style="font-weight:400;color:#888;">(kg, opcional)</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-speedometer2 input-icon"></i>
                            <input type="number" id="peso" class="form-input" name="peso"
                                   placeholder="Ej: 55.5" step="0.1" min="0" max="300">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="estatura">
                            Estatura <span style="font-weight:400;color:#888;">(metros, opcional)</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-arrows-vertical input-icon"></i>
                            <input type="number" id="estatura" class="form-input" name="estatura"
                                   placeholder="Ej: 1.65" step="0.01" min="0" max="3">
                        </div>
                    </div>
                </div>
                <div class="form-grid full-width">
                    <div class="form-group">
                        <label class="form-label" for="documento_medico">
                            Documento Médico (PDF) <span class="required">*</span>
                        </label>
                        <small style="color:#757575;margin-top:5px;display:block;">
                            Esta información es confidencial y solo será utilizada para garantizar
                            la seguridad del alumno
                        </small>
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-content">
                                <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                <p class="upload-text">Arrastra archivos aquí o haz clic para seleccionar</p>
                                <button type="button" class="btn-upload" id="selectFileBtn">
                                    Seleccionar Archivos
                                </button>
                                <small class="upload-info">Formato: PDF (máx. 5MB)</small>
                            </div>
                            <input type="file" id="documento_medico" name="documento_medico"
                                   accept=".pdf" style="display:none;" required>
                        </div>
                        <div id="file-preview" class="file-preview">
                            <div class="file-preview-content">
                                <i class="bi bi-file-earmark-pdf file-icon"></i>
                                <div class="file-details">
                                    <span id="file-name" class="file-name"></span>
                                    <span id="file-size" class="file-size"></span>
                                </div>
                                <button type="button" class="btn-remove" id="removeFileBtn">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary" onclick="resetBachillerReg()">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Registrar Alumno
                    </button>
                </div>
            </form>
        </div>{{-- /form-container --}}

       {{-- ══════════════════════════════════════════════════════════
             TABLA DE ALUMNOS
        ══════════════════════════════════════════════════════════ --}}
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="bi bi-table"></i>
                    Alumnos Registrados ({{ count($alumnos_registrados) }})
                </h2>
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput"
                           placeholder="Buscar por nombre o grado...">
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Grado Actual</th>
                            <th>Bachiller</th>
                            <th>Inscripción</th>
                            <th>Doc. Médico</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="alumnosTable">
                        @forelse($alumnos_registrados as $alumno)
                        <tr>
                            <td>{{ $alumno->nombre_alumno }}</td>

                            <td>{{ $alumno->nombreGrado ?? '— Sin asignar —' }}</td>

                            <td>
                                @if($alumno->numero_control)
                                    <span class="sch-pill">
                                        <i class="bi bi-mortarboard-fill"></i>
                                        {{ $alumno->numero_control }}
                                    </span>
                                    @if($alumno->grupo)
                                        <span class="badge-bachiller">{{ $alumno->grupo }}</span>
                                    @endif
                                @else
                                    <span class="badge-no-bachiller">—</span>
                                @endif
                            </td>

                            <td>
                                @if($alumno->fecha_inscripcion)
                                    {{ \Carbon\Carbon::parse($alumno->fecha_inscripcion)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                @if($alumno->certificado_medico)
                                    <a href="{{ asset('storage/' . $alumno->certificado_medico) }}"
                                       target="_blank" class="btn btn-sm btn-info"
                                       title="Ver documento médico">
                                        <i class="bi bi-file-earmark-pdf"></i> Ver PDF
                                    </a>
                                @else
                                    <span class="badge badge-secondary">Sin documento</span>
                                @endif
                            </td>

                            <td>
                                <span class="badge {{ $alumno->estado == 1 ? 'badge-success' : 'badge-danger' }}">
                                    {{ $alumno->estado == 1 ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <td>
                                {{-- Editar / actualizar grado --}}
                                <button type="button"
                                    class="action-btn btn-edit edit-alumno-btn"
                                    data-id="{{ $alumno->id_usuario }}"
                                    data-nombre="{{ $alumno->nombre_alumno }}"
                                    data-grado="{{ $alumno->id_grado }}"
                                    data-fecha="{{ $alumno->fecha_inscripcion }}"
                                    data-bachiller="{{ $alumno->numero_control ? '1' : '0' }}"
                                    data-numero_control="{{ $alumno->numero_control ?? '' }}"
                                    data-grupo="{{ $alumno->grupo ?? '' }}"
                                    data-especialidad="{{ $alumno->especialidad ?? '' }}"
                                    data-turno="{{ $alumno->turno ?? '' }}"
                                    data-peso="{{ $alumno->peso ?? '' }}"
                                    data-estatura="{{ $alumno->estatura ?? '' }}"
                                    title="Actualizar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>

                                {{-- Historial (grados + seminarios) --}}
                                <button type="button" class="action-btn btn-view"
                                    onclick="verHistorial({{ $alumno->id_usuario }}, '{{ addslashes($alumno->nombre_alumno) }}')"
                                    title="Ver historial">
                                    <i class="bi bi-clock-history"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                No hay alumnos registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>{{-- /table-container --}}

    </div>{{-- /content-wrapper --}}

    @include('includes.pie')
</div>{{-- /main-content --}}

{{-- ══════════════════════════════════════════════════════════════
     MODAL EDITAR ALUMNO
══════════════════════════════════════════════════════════════ --}}
<div id="editModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Actualizar Alumno
                </h2>
                <p class="modal-subtitle" id="editNombreAlumno"></p>
            </div>
            <button type="button" class="modal-close" onclick="closeEditModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <form id="editForm" method="POST" enctype="multipart/form-data" class="modal-body">
            @csrf
            @method('PUT')

            {{-- Grado --}}
            <div class="form-section">
                <h4 style="font-size:0.95rem;font-weight:700;color:#333;margin-bottom:12px;">
                    <i class="bi bi-award-fill"></i> Nuevo Grado
                </h4>
                <div class="form-row full-width">
                    <div class="form-field">
                        <label class="field-label" for="edit_id_grado">
                            Grado <span class="required">*</span>
                        </label>
                        <div class="field-wrapper">
                            <i class="bi bi-award-fill field-icon"></i>
                            <select id="edit_id_grado" name="id_grado" class="field-input" required>
                                @foreach($grados as $grado)
                                    <option value="{{ $grado->id_grado }}">{{ $grado->nombreGrado }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-row full-width">
                    <div class="form-field">
                        <label class="field-label" for="edit_fecha_obtencion">
                            Fecha de Obtención <span class="required">*</span>
                        </label>
                        <div class="field-wrapper">
                            <i class="bi bi-calendar-check field-icon"></i>
                            <input type="date" id="edit_fecha_obtencion" name="fecha_obtencion"
                                   class="field-input" required>
                        </div>
                    </div>
                </div>
                <div class="form-row full-width">
                    <div class="form-field">
                        <label class="field-label" for="edit_observaciones">Observaciones</label>
                        <div class="field-wrapper">
                            <i class="bi bi-chat-left-text field-icon"></i>
                            <input type="text" id="edit_observaciones" name="observaciones"
                                   class="field-input"
                                   placeholder="Ej: Aprobó examen con distinción">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bachiller en modal --}}
            <div class="form-section" style="margin-top:20px;">
                <h4 style="font-size:0.95rem;font-weight:700;color:#333;margin-bottom:12px;">
                    <i class="bi bi-mortarboard-fill"></i> Datos de Bachiller
                </h4>
                <div class="bachiller-box">
                    <label class="bachiller-check-label">
                        <input type="checkbox" name="es_bachiller" id="esBachillerEdit"
                               value="1" onchange="toggleBachiller('edit', this.checked)">
                        ¿El alumno pertenece al bachiller?
                    </label>
                    <div class="bachiller-fields" id="bachillerFieldsEdit">
                        <div class="form-grid" style="margin-top:12px;">
                            <div class="form-field">
                                <label class="field-label">Número de Control</label>
                                <div class="field-wrapper">
                                    <i class="bi bi-123 field-icon"></i>
                                    <input type="text" class="field-input" id="edit_numero_control"
                                           name="numero_control"
                                           placeholder="Ej: 12345678" maxlength="20">
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label">Grupo</label>
                                <div class="field-wrapper">
                                    <i class="bi bi-people-fill field-icon"></i>
                                    <input type="text" class="field-input" id="edit_grupo"
                                           name="grupo" placeholder="Ej: 3A" maxlength="10">
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label">Especialidad</label>
                                <div class="field-wrapper">
                                    <i class="bi bi-mortarboard-fill field-icon"></i>
                                    <input type="text" class="field-input" id="edit_especialidad"
                                           name="especialidad"
                                           placeholder="Ej: Informática" maxlength="100">
                                </div>
                            </div>
                            <div class="form-field">
                                <label class="field-label">Turno</label>
                                <div class="field-wrapper">
                                    <i class="bi bi-clock-fill field-icon"></i>
                                    <select class="field-input" id="edit_turno" name="turno">
                                        <option value="">— Selecciona —</option>
                                        <option value="Matutino">Matutino</option>
                                        <option value="Vespertino">Vespertino</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Datos físicos y documento --}}
            <div class="form-section" style="margin-top:20px;">
                <h4 style="font-size:0.95rem;font-weight:700;color:#333;margin-bottom:12px;">
                    <i class="bi bi-heart-pulse-fill"></i> Datos Físicos y Documento Médico
                </h4>
                <div class="form-row"
                     style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                    <div class="form-field">
                        <label class="field-label" for="edit_peso">
                            Peso <span style="font-weight:400;color:#888;">(kg)</span>
                        </label>
                        <div class="field-wrapper">
                            <i class="bi bi-speedometer2 field-icon"></i>
                            <input type="number" id="edit_peso" name="peso" class="field-input"
                                   placeholder="Ej: 55.5" step="0.1" min="0" max="300">
                        </div>
                    </div>
                    <div class="form-field">
                        <label class="field-label" for="edit_estatura">
                            Estatura <span style="font-weight:400;color:#888;">(metros)</span>
                        </label>
                        <div class="field-wrapper">
                            <i class="bi bi-arrows-vertical field-icon"></i>
                            <input type="number" id="edit_estatura" name="estatura" class="field-input"
                                   placeholder="Ej: 1.65" step="0.01" min="0" max="3">
                        </div>
                    </div>
                </div>
                <div class="form-row full-width">
                    <div class="form-field">
                        <label class="field-label" for="edit_documento_medico">
                            Actualizar Doc. Médico
                            <span style="font-weight:400;color:#888;">(opcional)</span>
                        </label>
                        <input type="file" id="edit_documento_medico" name="documento_medico"
                               class="field-input" accept=".pdf">
                        <small style="color:#757575;">
                            PDF máx. 5 MB — si no selecciona, se mantiene el actual
                        </small>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button type="submit" class="btn-modal btn-save">
                    <i class="bi bi-check-circle"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL CATÁLOGO DE GRADOS
══════════════════════════════════════════════════════════════ --}}
<div id="modalGrados" class="modal-overlay">
    <div class="modal-container" style="max-width:620px;">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <i class="bi bi-award-fill"></i> Catálogo de Grados
                </h2>
                <p class="modal-subtitle">Agrega o edita los grados del dojo</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModal('modalGrados')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">

            {{-- Toast notificación --}}
            <div id="toastGrados" class="toast-inline"></div>

            {{-- Formulario agregar nuevo grado --}}
            <div style="background:#f8f9fa;border:1.5px solid #dee2e6;border-radius:10px;
                        padding:16px;margin-bottom:20px;">
                <p style="font-size:0.88rem;font-weight:700;color:#555;margin-bottom:12px;">
                    <i class="bi bi-plus-circle-fill" style="color:#c62828;"></i>
                    Agregar nuevo grado
                </p>
                <div class="cat-form-inline">
                    <div>
                        <label for="nuevo_nombre_grado">Nombre del grado</label>
                        <input type="text" id="nuevo_nombre_grado"
                               placeholder="Ej: Cinturón Rojo" maxlength="100">
                    </div>
                    <div>
                        <label for="nuevo_orden_grado">Orden</label>
                        <input type="number" id="nuevo_orden_grado"
                               placeholder="Ej: 5" min="1">
                    </div>
                    <div>
                        <button type="button" class="btn-add-sem" onclick="agregarGrado()">
                            <i class="bi bi-plus-lg"></i> Agregar
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tabla del catálogo --}}
            <div id="tablaGradosContainer">
                <table class="cat-table" id="tablaGrados">
                    <thead>
                        <tr>
                            <th style="width:50px;">Orden</th>
                            <th>Nombre del Grado</th>
                            <th style="width:100px;text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyGrados">
                        @foreach($grados as $g)
                        <tr id="fila-grado-{{ $g->id_grado }}">
                            <td>
                                <span id="orden-txt-{{ $g->id_grado }}">{{ $g->orden }}</span>
                                <input type="number" id="orden-inp-{{ $g->id_grado }}"
                                       value="{{ $g->orden }}" min="1"
                                       style="display:none;width:60px;padding:4px 6px;
                                              border:1px solid #ccc;border-radius:4px;">
                            </td>
                            <td>
                                <span id="nombre-txt-{{ $g->id_grado }}">{{ $g->nombreGrado }}</span>
                                <input type="text" id="nombre-inp-{{ $g->id_grado }}"
                                       value="{{ $g->nombreGrado }}" maxlength="100"
                                       style="display:none;width:100%;padding:4px 6px;
                                              border:1px solid #ccc;border-radius:4px;
                                              box-sizing:border-box;">
                            </td>
                            <td style="text-align:center;">
                                {{-- Botón editar --}}
                                <button class="btn-edit-inline"
                                        id="btn-edit-grado-{{ $g->id_grado }}"
                                        onclick="editarGrado({{ $g->id_grado }})"
                                        title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                {{-- Botón guardar (oculto hasta editar) --}}
                                <button class="btn-edit-inline"
                                        id="btn-save-grado-{{ $g->id_grado }}"
                                        onclick="guardarGrado({{ $g->id_grado }})"
                                        title="Guardar"
                                        style="display:none;color:#2e7d32;">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                {{-- Botón cancelar edición (oculto hasta editar) --}}
                                <button class="btn-delete-inline"
                                        id="btn-cancel-grado-{{ $g->id_grado }}"
                                        onclick="cancelarEditarGrado({{ $g->id_grado }},
                                                 '{{ addslashes($g->nombreGrado) }}',
                                                 {{ $g->orden }})"
                                        title="Cancelar"
                                        style="display:none;">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>{{-- /modal-body --}}
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL CATÁLOGO DE SEMINARIOS
══════════════════════════════════════════════════════════════ --}}
<div id="modalSeminarios" class="modal-overlay">
    <div class="modal-container" style="max-width:700px;">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <i class="bi bi-journal-text"></i> Catálogo de Seminarios
                </h2>
                <p class="modal-subtitle">Agrega o edita los seminarios del dojo</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModal('modalSeminarios')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">

            {{-- Toast notificación --}}
            <div id="toastSeminarios" class="toast-inline"></div>

            {{-- Formulario agregar nuevo seminario --}}
            <div style="background:#f8f9fa;border:1.5px solid #dee2e6;border-radius:10px;
                        padding:16px;margin-bottom:20px;">
                <p style="font-size:0.88rem;font-weight:700;color:#555;margin-bottom:12px;">
                    <i class="bi bi-plus-circle-fill" style="color:#c62828;"></i>
                    Agregar nuevo seminario al catálogo
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px;">
                    <div>
                        <label style="font-size:0.82rem;font-weight:600;color:#555;
                                      margin-bottom:4px;display:block;">
                            Nombre del seminario <span style="color:#c62828;">*</span>
                        </label>
                        <input type="text" id="cat_nombre_seminario"
                               placeholder="Ej: Seminario Kihon 2025" maxlength="150"
                               style="width:100%;padding:7px 10px;border:1px solid #ccc;
                                      border-radius:6px;font-size:0.88rem;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.82rem;font-weight:600;color:#555;
                                      margin-bottom:4px;display:block;">
                            Fecha <span style="color:#c62828;">*</span>
                        </label>
                        <input type="date" id="cat_fecha_seminario"
                               style="width:100%;padding:7px 10px;border:1px solid #ccc;
                                      border-radius:6px;font-size:0.88rem;box-sizing:border-box;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:10px;">
                    <div>
                        <label style="font-size:0.82rem;font-weight:600;color:#555;
                                      margin-bottom:4px;display:block;">
                            Maestro <span style="color:#c62828;">*</span>
                        </label>
                        <input type="text" id="cat_maestro_seminario"
                               placeholder="Ej: Sensei Ramírez" maxlength="150"
                               style="width:100%;padding:7px 10px;border:1px solid #ccc;
                                      border-radius:6px;font-size:0.88rem;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="font-size:0.82rem;font-weight:600;color:#555;
                                      margin-bottom:4px;display:block;">
                            Resultado
                        </label>
                        <input type="text" id="cat_resultado_seminario"
                               placeholder="Ej: Aprobado" maxlength="50"
                               style="width:100%;padding:7px 10px;border:1px solid #ccc;
                                      border-radius:6px;font-size:0.88rem;box-sizing:border-box;">
                    </div>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:0.82rem;font-weight:600;color:#555;
                                  margin-bottom:4px;display:block;">
                        Descripción
                    </label>
                    <textarea id="cat_descripcion_seminario" rows="2"
                              placeholder="Descripción general del seminario (opcional)"
                              style="width:100%;padding:7px 10px;border:1px solid #ccc;
                                     border-radius:6px;font-size:0.88rem;
                                     box-sizing:border-box;resize:vertical;"></textarea>
                </div>
                <div style="text-align:right;">
                    <button type="button" class="btn-add-sem" onclick="agregarSeminarioCatalogo()">
                        <i class="bi bi-plus-lg"></i> Agregar al catálogo
                    </button>
                </div>
            </div>

            {{-- Tabla del catálogo de seminarios --}}
            <div id="tablaSeminariosContainer">
                <table class="cat-table" id="tablaSeminarios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th style="width:100px;">Fecha</th>
                            <th style="width:130px;">Maestro</th>
                            <th style="width:90px;">Resultado</th>
                            <th style="width:80px;text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodySeminarios">
                        @forelse($seminarios as $s)
                        <tr id="fila-sem-{{ $s->id_seminario }}">
                            <td>
                                <span id="sem-nombre-txt-{{ $s->id_seminario }}">
                                    {{ $s->nombre_seminario }}
                                </span>
                                <input type="text" id="sem-nombre-inp-{{ $s->id_seminario }}"
                                       value="{{ $s->nombre_seminario }}" maxlength="150"
                                       style="display:none;width:100%;padding:4px 6px;
                                              border:1px solid #ccc;border-radius:4px;
                                              box-sizing:border-box;">
                            </td>
                            <td>
                                <span id="sem-fecha-txt-{{ $s->id_seminario }}">
                                    {{ \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') }}
                                </span>
                                <input type="date" id="sem-fecha-inp-{{ $s->id_seminario }}"
                                       value="{{ $s->fecha }}"
                                       style="display:none;width:100%;padding:4px 6px;
                                              border:1px solid #ccc;border-radius:4px;
                                              box-sizing:border-box;">
                            </td>
                            <td>
                                <span id="sem-maestro-txt-{{ $s->id_seminario }}">
                                    {{ $s->maestro }}
                                </span>
                                <input type="text" id="sem-maestro-inp-{{ $s->id_seminario }}"
                                       value="{{ $s->maestro }}" maxlength="150"
                                       style="display:none;width:100%;padding:4px 6px;
                                              border:1px solid #ccc;border-radius:4px;
                                              box-sizing:border-box;">
                            </td>
                            <td>
                                <span id="sem-resultado-txt-{{ $s->id_seminario }}">
                                    {{ $s->resultado ?? '—' }}
                                </span>
                                <input type="text" id="sem-resultado-inp-{{ $s->id_seminario }}"
                                       value="{{ $s->resultado ?? '' }}" maxlength="50"
                                       style="display:none;width:100%;padding:4px 6px;
                                              border:1px solid #ccc;border-radius:4px;
                                              box-sizing:border-box;">
                            </td>
                            <td style="text-align:center;">
                                {{-- Botón editar --}}
                                <button class="btn-edit-inline"
                                        id="btn-edit-sem-{{ $s->id_seminario }}"
                                        onclick="editarSeminarioCatalogo({{ $s->id_seminario }})"
                                        title="Editar">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                {{-- Botón guardar (oculto hasta editar) --}}
                                <button class="btn-edit-inline"
                                        id="btn-save-sem-{{ $s->id_seminario }}"
                                        onclick="guardarSeminarioCatalogo({{ $s->id_seminario }})"
                                        title="Guardar"
                                        style="display:none;color:#2e7d32;">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                {{-- Botón cancelar (oculto hasta editar) --}}
                                <button class="btn-delete-inline"
                                        id="btn-cancel-sem-{{ $s->id_seminario }}"
                                        onclick="cancelarEditarSeminario({{ $s->id_seminario }})"
                                        title="Cancelar"
                                        style="display:none;">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                                {{-- Botón eliminar --}}
                                <button class="btn-delete-inline"
                                        id="btn-delete-sem-{{ $s->id_seminario }}"
                                        onclick="eliminarSeminarioCatalogo({{ $s->id_seminario }})"
                                        title="Eliminar seminario">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr id="fila-sem-vacia">
                            <td colspan="5" class="text-center" style="color:#9e9e9e;padding:20px;">
                                No hay seminarios en el catálogo.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>{{-- /modal-body --}}
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL HISTORIAL DEL ALUMNO (GRADOS + SEMINARIOS)
══════════════════════════════════════════════════════════════ --}}
<div id="historialModal" class="modal-overlay">
    <div class="modal-container" style="max-width:700px;">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <i class="bi bi-clock-history"></i> Historial del Alumno
                </h2>
                <p class="modal-subtitle" id="historialNombreAlumno"></p>
            </div>
            <button type="button" class="modal-close" onclick="closeHistorialModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">

            {{-- Tabs: Grados / Seminarios --}}
            <div class="hist-tabs">
                <button class="hist-tab active" onclick="switchTab('grados', this)">
                    <i class="bi bi-award-fill"></i> Grados
                </button>
                <button class="hist-tab" onclick="switchTab('seminarios', this)">
                    <i class="bi bi-journal-text"></i> Seminarios
                </button>
            </div>

            {{-- Panel: Grados --}}
            <div id="panelGrados" class="hist-panel active">
                <div id="historialGradosContent">
                    <p class="text-center">Cargando historial de grados...</p>
                </div>
            </div>

            {{-- Panel: Seminarios --}}
            <div id="panelSeminarios" class="hist-panel">

                {{-- Formulario agregar participación — solo admin/sensei --}}
                @if(in_array(auth()->user()->rol ?? '', ['admin', 'sensei']))
                <div class="add-seminario-form">
                    <p style="font-size:0.88rem;font-weight:700;color:#555;margin-bottom:12px;">
                        <i class="bi bi-plus-circle-fill" style="color:#c62828;"></i>
                        Registrar participación en seminario
                    </p>

                    {{-- Toast del panel seminarios --}}
                    <div id="toastHistSem" class="toast-inline"></div>

                    <div class="form-row">
                        <div>
                            <label for="sem_modo">Tipo</label>
                            <select id="sem_modo" onchange="toggleModoSeminario(this.value)">
                                <option value="existente">Seminario existente</option>
                                <option value="nuevo">+ Crear nuevo seminario</option>
                            </select>
                        </div>
                        <div>
                            <label for="sem_fecha_participacion">
                                Fecha de participación <span style="color:#c62828;">*</span>
                            </label>
                            <input type="date" id="sem_fecha_participacion">
                        </div>
                    </div>

                    {{-- Selector seminario existente --}}
                    <div id="bloqueSemExistente" class="form-row full">
                        <div>
                            <label for="sem_id_seminario">
                                Seminario <span style="color:#c62828;">*</span>
                            </label>
                            <select id="sem_id_seminario">
                                <option value="">— Selecciona —</option>
                                @foreach($seminarios as $s)
                                    <option value="{{ $s->id_seminario }}">
                                        {{ $s->nombre_seminario }}
                                        ({{ \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Campos nuevo seminario (ocultos por defecto) --}}
                    <div id="bloqueSemNuevo" class="nuevo-sem-fields">
                        <div class="form-row">
                            <div>
                                <label for="sem_nombre_nuevo">
                                    Nombre del seminario <span style="color:#c62828;">*</span>
                                </label>
                                <input type="text" id="sem_nombre_nuevo"
                                       placeholder="Ej: Seminario Kihon 2025" maxlength="150">
                            </div>
                            <div>
                                <label for="sem_fecha_nuevo">
                                    Fecha del seminario <span style="color:#c62828;">*</span>
                                </label>
                                <input type="date" id="sem_fecha_nuevo">
                            </div>
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="sem_maestro_nuevo">
                                    Maestro <span style="color:#c62828;">*</span>
                                </label>
                                <input type="text" id="sem_maestro_nuevo"
                                       placeholder="Ej: Sensei Ramírez" maxlength="150">
                            </div>
                            <div>
                                <label for="sem_resultado_nuevo">Resultado</label>
                                <input type="text" id="sem_resultado_nuevo"
                                       placeholder="Ej: Aprobado" maxlength="50">
                            </div>
                        </div>
                        <div class="form-row full">
                            <div>
                                <label for="sem_descripcion_nuevo">Descripción</label>
                                <textarea id="sem_descripcion_nuevo"
                                          placeholder="Descripción general del seminario (opcional)"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Observaciones de la participación --}}
                    <div class="form-row full" style="margin-top:10px;">
                        <div>
                            <label for="sem_observaciones">Observaciones de la participación</label>
                            <textarea id="sem_observaciones"
                                      placeholder="Ej: Completó con distinción"></textarea>
                        </div>
                    </div>

                    <div style="text-align:right;margin-top:10px;">
                        <button type="button" class="btn-add-sem"
                                onclick="registrarParticipacion()">
                            <i class="bi bi-plus-lg"></i> Agregar participación
                        </button>
                    </div>
                </div>
                @endif

                {{-- Lista de participaciones del alumno --}}
                <div id="historialSeminariosContent">
                    <p class="text-center">Cargando seminarios...</p>
                </div>

            </div>{{-- /panelSeminarios --}}

        </div>{{-- /modal-body --}}
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════ --}}
<script>
// ── Token CSRF global ──────────────────────────────────────────────────
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// ══════════════════════════════════════════════════════════════
//  UTILIDADES
// ══════════════════════════════════════════════════════════════

/**
 * Muestra un toast en un contenedor dado.
 * @param {string} id       — id del div .toast-inline
 * @param {string} mensaje  — texto a mostrar
 * @param {string} tipo     — 'success' | 'error'
 */
function mostrarToast(id, mensaje, tipo) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent  = mensaje;
    el.className    = 'toast-inline ' + tipo;
    clearTimeout(el._timer);
    el._timer = setTimeout(() => {
        el.className = 'toast-inline';
        el.textContent = '';
    }, 4000);
}

/**
 * Abre cualquier modal por id.
 */
function abrirModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Cierra cualquier modal por id.
 */
function cerrarModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

// ══════════════════════════════════════════════════════════════
//  DRAG & DROP — SUBIDA DE ARCHIVO PDF
// ══════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function () {

    const uploadArea = document.getElementById('uploadArea');
    const fileInput  = document.getElementById('documento_medico');
    const selectBtn  = document.getElementById('selectFileBtn');
    const preview    = document.getElementById('file-preview');
    const fileName   = document.getElementById('file-name');
    const fileSize   = document.getElementById('file-size');
    const removeBtn  = document.getElementById('removeFileBtn');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', e => {
            if (e.target !== selectBtn) fileInput.click();
        });
        selectBtn.addEventListener('click', e => {
            e.stopPropagation();
            fileInput.click();
        });
        uploadArea.addEventListener('dragover', e => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        uploadArea.addEventListener('dragleave', e => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
        });
        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });
        fileInput.addEventListener('change', e => {
            if (e.target.files[0]) handleFile(e.target.files[0]);
        });
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                fileInput.value = '';
                uploadArea.style.display  = 'block';
                preview.classList.remove('active');
            });
        }
    }

    function handleFile(file) {
        if (file.type !== 'application/pdf') {
            alert('Solo se aceptan archivos PDF.');
            fileInput.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('El archivo supera los 5 MB permitidos.');
            fileInput.value = '';
            return;
        }
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        uploadArea.style.display = 'none';
        preview.classList.add('active');
    }

    // ── Modal editar: poblar datos al abrir ────────────────────────────
    document.querySelectorAll('.edit-alumno-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id           = this.dataset.id;
            const nombre       = this.dataset.nombre;
            const grado        = this.dataset.grado;
            const fecha        = this.dataset.fecha;
            const esBachiller  = this.dataset.bachiller === '1';
            const numControl   = this.dataset.numero_control;
            const grupo        = this.dataset.grupo;
            const especialidad = this.dataset.especialidad;
            const turno        = this.dataset.turno;
            const peso         = this.dataset.peso;
            const estatura     = this.dataset.estatura;

            document.getElementById('editNombreAlumno').textContent   = nombre;
            document.getElementById('edit_id_grado').value            = grado   || '';
            document.getElementById('edit_fecha_obtencion').value     = fecha ? fecha.substring(0, 10) : '';
            document.getElementById('edit_observaciones').value       = '';
            document.getElementById('edit_peso').value                = peso     || '';
            document.getElementById('edit_estatura').value            = estatura || '';
            document.getElementById('editForm').action                = '/alumnos/' + id;

            const cbEdit = document.getElementById('esBachillerEdit');
            cbEdit.checked = esBachiller;
            toggleBachiller('edit', esBachiller);

            if (esBachiller) {
                document.getElementById('edit_numero_control').value = numControl   || '';
                document.getElementById('edit_grupo').value          = grupo        || '';
                document.getElementById('edit_especialidad').value   = especialidad || '';
                document.getElementById('edit_turno').value          = turno        || '';
            }

            abrirModal('editModal');
        });
    });

    // ── Búsqueda en tabla ──────────────────────────────────────────────
    $('#searchInput').on('keyup', function () {
        const txt = $(this).val().toLowerCase();
        $('#alumnosTable tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(txt));
        });
    });

    // ── Cierre modales con click fuera o ESC ───────────────────────────
    window.addEventListener('click', function (e) {
        ['editModal', 'historialModal', 'modalGrados', 'modalSeminarios'].forEach(id => {
            if (e.target === document.getElementById(id)) cerrarModal(id);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            ['editModal', 'historialModal', 'modalGrados', 'modalSeminarios'].forEach(id => {
                cerrarModal(id);
            });
        }
    });
});

// ══════════════════════════════════════════════════════════════
//  TOGGLE BACHILLER
// ══════════════════════════════════════════════════════════════

function toggleBachiller(scope, checked) {
    const suffix = scope === 'reg' ? 'Reg' : 'Edit';
    const fields = document.getElementById('bachillerFields' + suffix);
    if (!fields) return;
    if (checked) {
        fields.classList.add('show');
    } else {
        fields.classList.remove('show');
        fields.querySelectorAll('input, select').forEach(el => el.value = '');
    }
}

function resetBachillerReg() {
    const cb = document.getElementById('esBachillerReg');
    if (cb) { cb.checked = false; toggleBachiller('reg', false); }
}

// ══════════════════════════════════════════════════════════════
//  CERRAR MODALES ESPECÍFICOS
// ══════════════════════════════════════════════════════════════

function closeEditModal()      { cerrarModal('editModal'); }
function closeHistorialModal() {
    cerrarModal('historialModal');
    _historialAlumnoId  = null;
    _historialAlumnoNom = '';
}
function abrirModalGrados()    { abrirModal('modalGrados'); }
function abrirModalSeminarios(){ abrirModal('modalSeminarios'); }

// ══════════════════════════════════════════════════════════════
//  CATÁLOGO DE GRADOS
// ══════════════════════════════════════════════════════════════

function agregarGrado() {
    const nombre = document.getElementById('nuevo_nombre_grado').value.trim();
    const orden  = document.getElementById('nuevo_orden_grado').value.trim();

    if (!nombre || !orden) {
        mostrarToast('toastGrados', 'El nombre y el orden son obligatorios.', 'error');
        return;
    }

    fetch('/grados', {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ nombreGrado: nombre, orden: parseInt(orden) }),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            const msg = res.errors
                ? Object.values(res.errors).flat().join(' ')
                : (res.message || 'Error al agregar el grado.');
            mostrarToast('toastGrados', msg, 'error');
            return;
        }

        const g = res.grado;

        // Quitar fila vacía si existe
        const filaVacia = document.getElementById('fila-grado-vacia');
        if (filaVacia) filaVacia.remove();

        // Insertar nueva fila en la tabla
        const tbody = document.getElementById('tbodyGrados');
        const fila  = document.createElement('tr');
        fila.id     = 'fila-grado-' + g.id_grado;
        fila.innerHTML = `
            <td>
                <span id="orden-txt-${g.id_grado}">${g.orden}</span>
                <input type="number" id="orden-inp-${g.id_grado}" value="${g.orden}" min="1"
                       style="display:none;width:60px;padding:4px 6px;
                              border:1px solid #ccc;border-radius:4px;">
            </td>
            <td>
                <span id="nombre-txt-${g.id_grado}">${g.nombreGrado}</span>
                <input type="text" id="nombre-inp-${g.id_grado}" value="${g.nombreGrado}"
                       maxlength="100"
                       style="display:none;width:100%;padding:4px 6px;
                              border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
            </td>
            <td style="text-align:center;">
                <button class="btn-edit-inline" id="btn-edit-grado-${g.id_grado}"
                        onclick="editarGrado(${g.id_grado})" title="Editar">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="btn-edit-inline" id="btn-save-grado-${g.id_grado}"
                        onclick="guardarGrado(${g.id_grado})" title="Guardar"
                        style="display:none;color:#2e7d32;">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button class="btn-delete-inline" id="btn-cancel-grado-${g.id_grado}"
                        onclick="cancelarEditarGrado(${g.id_grado},'${g.nombreGrado}',${g.orden})"
                        title="Cancelar" style="display:none;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;
        tbody.appendChild(fila);

        // Agregar al selector de grados del formulario principal y del modal editar
        ['id_grado', 'edit_id_grado'].forEach(selId => {
            const sel = document.getElementById(selId);
            if (sel) {
                const opt   = document.createElement('option');
                opt.value   = g.id_grado;
                opt.text    = g.nombreGrado;
                opt.id      = 'opt-grado-' + g.id_grado;
                sel.appendChild(opt);
            }
        });

        // Limpiar campos
        document.getElementById('nuevo_nombre_grado').value = '';
        document.getElementById('nuevo_orden_grado').value  = '';
        mostrarToast('toastGrados', 'Grado "' + g.nombreGrado + '" agregado con éxito.', 'success');
    })
    .catch(() => mostrarToast('toastGrados', 'Error de conexión.', 'error'));
}

function editarGrado(id) {
    document.getElementById('nombre-txt-' + id).style.display  = 'none';
    document.getElementById('orden-txt-' + id).style.display   = 'none';
    document.getElementById('nombre-inp-' + id).style.display  = '';
    document.getElementById('orden-inp-' + id).style.display   = '';
    document.getElementById('btn-edit-grado-' + id).style.display   = 'none';
    document.getElementById('btn-save-grado-' + id).style.display   = '';
    document.getElementById('btn-cancel-grado-' + id).style.display = '';
}

function cancelarEditarGrado(id, nombreOriginal, ordenOriginal) {
    document.getElementById('nombre-inp-' + id).value          = nombreOriginal;
    document.getElementById('orden-inp-' + id).value           = ordenOriginal;
    document.getElementById('nombre-txt-' + id).style.display  = '';
    document.getElementById('orden-txt-' + id).style.display   = '';
    document.getElementById('nombre-inp-' + id).style.display  = 'none';
    document.getElementById('orden-inp-' + id).style.display   = 'none';
    document.getElementById('btn-edit-grado-' + id).style.display   = '';
    document.getElementById('btn-save-grado-' + id).style.display   = 'none';
    document.getElementById('btn-cancel-grado-' + id).style.display = 'none';
}

function guardarGrado(id) {
    const nombre = document.getElementById('nombre-inp-' + id).value.trim();
    const orden  = document.getElementById('orden-inp-' + id).value.trim();

    if (!nombre || !orden) {
        mostrarToast('toastGrados', 'El nombre y el orden son obligatorios.', 'error');
        return;
    }

    fetch('/grados/' + id, {
        method:  'PUT',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ nombreGrado: nombre, orden: parseInt(orden) }),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            const msg = res.errors
                ? Object.values(res.errors).flat().join(' ')
                : (res.message || 'Error al actualizar.');
            mostrarToast('toastGrados', msg, 'error');
            return;
        }

        const g = res.grado;

        // Actualizar textos en la fila
        document.getElementById('nombre-txt-' + id).textContent = g.nombreGrado;
        document.getElementById('orden-txt-' + id).textContent  = g.orden;

        // Actualizar opción en los selectores de grado
        ['id_grado', 'edit_id_grado'].forEach(selId => {
            const opt = document.querySelector('#' + selId + ' option[value="' + id + '"]');
            if (opt) opt.text = g.nombreGrado;
        });

        cancelarEditarGrado(id, g.nombreGrado, g.orden);
        mostrarToast('toastGrados', 'Grado actualizado.', 'success');
    })
    .catch(() => mostrarToast('toastGrados', 'Error de conexión.', 'error'));
}

// ══════════════════════════════════════════════════════════════
//  CATÁLOGO DE SEMINARIOS
// ══════════════════════════════════════════════════════════════

// Guarda los valores originales de cada fila para cancelar edición
const _semOriginales = {};

function agregarSeminarioCatalogo() {
    const nombre      = document.getElementById('cat_nombre_seminario').value.trim();
    const fecha       = document.getElementById('cat_fecha_seminario').value;
    const maestro     = document.getElementById('cat_maestro_seminario').value.trim();
    const resultado   = document.getElementById('cat_resultado_seminario').value.trim();
    const descripcion = document.getElementById('cat_descripcion_seminario').value.trim();

    if (!nombre || !fecha || !maestro) {
        mostrarToast('toastSeminarios', 'Nombre, fecha y maestro son obligatorios.', 'error');
        return;
    }

    fetch('/seminarios', {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            nombre_seminario: nombre,
            fecha:            fecha,
            maestro:          maestro,
            resultado:        resultado   || null,
            descripcion:      descripcion || null,
        }),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            mostrarToast('toastSeminarios', res.message || 'Error al agregar.', 'error');
            return;
        }

        const s = res.seminario;

        // Formatear fecha para mostrar
        const fechaPartes = s.fecha.split('-');
        const fechaFmt    = fechaPartes[2] + '/' + fechaPartes[1] + '/' + fechaPartes[0];

        // Quitar fila vacía si existe
        const filaVacia = document.getElementById('fila-sem-vacia');
        if (filaVacia) filaVacia.remove();

        // Guardar originales para cancelar
        _semOriginales[s.id_seminario] = {
            nombre:    s.nombre_seminario,
            fecha:     s.fecha,
            fechaFmt:  fechaFmt,
            maestro:   s.maestro,
            resultado: s.resultado || '',
        };

        // Insertar nueva fila en la tabla del catálogo
        const tbody = document.getElementById('tbodySeminarios');
        const fila  = document.createElement('tr');
        fila.id     = 'fila-sem-' + s.id_seminario;
        fila.innerHTML = `
            <td>
                <span id="sem-nombre-txt-${s.id_seminario}">${s.nombre_seminario}</span>
                <input type="text" id="sem-nombre-inp-${s.id_seminario}"
                       value="${s.nombre_seminario}" maxlength="150"
                       style="display:none;width:100%;padding:4px 6px;
                              border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
            </td>
            <td>
                <span id="sem-fecha-txt-${s.id_seminario}">${fechaFmt}</span>
                <input type="date" id="sem-fecha-inp-${s.id_seminario}"
                       value="${s.fecha}"
                       style="display:none;width:100%;padding:4px 6px;
                              border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
            </td>
            <td>
                <span id="sem-maestro-txt-${s.id_seminario}">${s.maestro}</span>
                <input type="text" id="sem-maestro-inp-${s.id_seminario}"
                       value="${s.maestro}" maxlength="150"
                       style="display:none;width:100%;padding:4px 6px;
                              border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
            </td>
            <td>
                <span id="sem-resultado-txt-${s.id_seminario}">${s.resultado || '—'}</span>
                <input type="text" id="sem-resultado-inp-${s.id_seminario}"
                       value="${s.resultado || ''}" maxlength="50"
                       style="display:none;width:100%;padding:4px 6px;
                              border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
            </td>
            <td style="text-align:center;">
                <button class="btn-edit-inline" id="btn-edit-sem-${s.id_seminario}"
                        onclick="editarSeminarioCatalogo(${s.id_seminario})" title="Editar">
                    <i class="bi bi-pencil-fill"></i>
                </button>
                <button class="btn-edit-inline" id="btn-save-sem-${s.id_seminario}"
                        onclick="guardarSeminarioCatalogo(${s.id_seminario})" title="Guardar"
                        style="display:none;color:#2e7d32;">
                    <i class="bi bi-check-lg"></i>
                </button>
                <button class="btn-delete-inline" id="btn-cancel-sem-${s.id_seminario}"
                        onclick="cancelarEditarSeminario(${s.id_seminario})"
                        title="Cancelar" style="display:none;">
                    <i class="bi bi-x-lg"></i>
                </button>
                <button class="btn-delete-inline" id="btn-delete-sem-${s.id_seminario}"
                        onclick="eliminarSeminarioCatalogo(${s.id_seminario})"
                        title="Eliminar seminario">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>`;
        tbody.appendChild(fila);

        // Agregar al selector del modal historial
        agregarOpcionSelectorHistorial(s);

        // Limpiar campos
        document.getElementById('cat_nombre_seminario').value    = '';
        document.getElementById('cat_fecha_seminario').value     = '';
        document.getElementById('cat_maestro_seminario').value   = '';
        document.getElementById('cat_resultado_seminario').value = '';
        document.getElementById('cat_descripcion_seminario').value = '';

        mostrarToast('toastSeminarios',
            'Seminario "' + s.nombre_seminario + '" agregado al catálogo.', 'success');
    })
    .catch(() => mostrarToast('toastSeminarios', 'Error de conexión.', 'error'));
}

function editarSeminarioCatalogo(id) {
    // Guardar originales en memoria
    _semOriginales[id] = {
        nombre:   document.getElementById('sem-nombre-txt-' + id).textContent.trim(),
        fechaFmt: document.getElementById('sem-fecha-txt-' + id).textContent.trim(),
        fecha:    document.getElementById('sem-fecha-inp-' + id).value,
        maestro:  document.getElementById('sem-maestro-txt-' + id).textContent.trim(),
        resultado:document.getElementById('sem-resultado-txt-' + id).textContent.trim(),
    };

    ['nombre', 'fecha', 'maestro', 'resultado'].forEach(campo => {
        document.getElementById('sem-' + campo + '-txt-' + id).style.display = 'none';
        document.getElementById('sem-' + campo + '-inp-' + id).style.display = '';
    });
    document.getElementById('btn-edit-sem-' + id).style.display   = 'none';
    document.getElementById('btn-delete-sem-' + id).style.display = 'none';
    document.getElementById('btn-save-sem-' + id).style.display   = '';
    document.getElementById('btn-cancel-sem-' + id).style.display = '';
}

function cancelarEditarSeminario(id) {
    const orig = _semOriginales[id] || {};
    if (orig.nombre)    document.getElementById('sem-nombre-inp-' + id).value    = orig.nombre;
    if (orig.fecha)     document.getElementById('sem-fecha-inp-' + id).value     = orig.fecha;
    if (orig.maestro)   document.getElementById('sem-maestro-inp-' + id).value   = orig.maestro;
    if (orig.resultado) document.getElementById('sem-resultado-inp-' + id).value = orig.resultado !== '—' ? orig.resultado : '';

    ['nombre', 'fecha', 'maestro', 'resultado'].forEach(campo => {
        document.getElementById('sem-' + campo + '-txt-' + id).style.display = '';
        document.getElementById('sem-' + campo + '-inp-' + id).style.display = 'none';
    });
    document.getElementById('btn-edit-sem-' + id).style.display   = '';
    document.getElementById('btn-delete-sem-' + id).style.display = '';
    document.getElementById('btn-save-sem-' + id).style.display   = 'none';
    document.getElementById('btn-cancel-sem-' + id).style.display = 'none';
}

function guardarSeminarioCatalogo(id) {
    const nombre    = document.getElementById('sem-nombre-inp-' + id).value.trim();
    const fecha     = document.getElementById('sem-fecha-inp-' + id).value;
    const maestro   = document.getElementById('sem-maestro-inp-' + id).value.trim();
    const resultado = document.getElementById('sem-resultado-inp-' + id).value.trim();

    if (!nombre || !fecha || !maestro) {
        mostrarToast('toastSeminarios', 'Nombre, fecha y maestro son obligatorios.', 'error');
        return;
    }

    fetch('/seminarios/' + id, {
        method:  'PUT',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            nombre_seminario: nombre,
            fecha:            fecha,
            maestro:          maestro,
            resultado:        resultado || null,
        }),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            mostrarToast('toastSeminarios', res.message || 'Error al actualizar.', 'error');
            return;
        }

        const s         = res.seminario;
        const fechaPartes = s.fecha.split('-');
        const fechaFmt    = fechaPartes[2] + '/' + fechaPartes[1] + '/' + fechaPartes[0];

        // Actualizar textos en la fila
        document.getElementById('sem-nombre-txt-' + id).textContent    = s.nombre_seminario;
        document.getElementById('sem-fecha-txt-' + id).textContent     = fechaFmt;
        document.getElementById('sem-maestro-txt-' + id).textContent   = s.maestro;
        document.getElementById('sem-resultado-txt-' + id).textContent = s.resultado || '—';

        // Actualizar opción en el selector del modal historial
        const opt = document.querySelector('#sem_id_seminario option[value="' + id + '"]');
        if (opt) {
            opt.text = s.nombre_seminario + ' (' + fechaFmt + ')';
        }

        cancelarEditarSeminario(id);
        mostrarToast('toastSeminarios', 'Seminario actualizado.', 'success');
    })
    .catch(() => mostrarToast('toastSeminarios', 'Error de conexión.', 'error'));
}

function eliminarSeminarioCatalogo(id) {
    if (!confirm('¿Eliminar este seminario del catálogo?\n' +
                 'También se eliminarán todas las participaciones registradas de alumnos en este seminario.')) return;

    fetch('/seminarios/' + id, {
        method:  'DELETE',
        headers: {
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            mostrarToast('toastSeminarios', res.message || 'Error al eliminar.', 'error');
            return;
        }

        // Eliminar fila de la tabla
        const fila = document.getElementById('fila-sem-' + id);
        if (fila) fila.remove();

        // Eliminar opción del selector en el modal historial
        const opt = document.querySelector('#sem_id_seminario option[value="' + id + '"]');
        if (opt) opt.remove();

        // Si la tabla quedó vacía, mostrar fila vacía
        const tbody = document.getElementById('tbodySeminarios');
        if (tbody && !tbody.querySelector('tr')) {
            tbody.innerHTML = '<tr id="fila-sem-vacia">' +
                '<td colspan="5" class="text-center" style="color:#9e9e9e;padding:20px;">' +
                'No hay seminarios en el catálogo.' +
                '</td></tr>';
        }

        mostrarToast('toastSeminarios', 'Seminario eliminado.', 'success');
    })
    .catch(() => mostrarToast('toastSeminarios', 'Error de conexión.', 'error'));
}

/**
 * Agrega una opción al selector #sem_id_seminario del modal historial.
 * Se llama al crear un seminario nuevo desde el catálogo o desde el propio modal.
 */
function agregarOpcionSelectorHistorial(s) {
    const sel = document.getElementById('sem_id_seminario');
    if (!sel) return;
    const fechaPartes = s.fecha.split('-');
    const fechaFmt    = fechaPartes[2] + '/' + fechaPartes[1] + '/' + fechaPartes[0];
    const opt   = document.createElement('option');
    opt.value   = s.id_seminario;
    opt.text    = s.nombre_seminario + ' (' + fechaFmt + ')';
    sel.appendChild(opt);
}

// ══════════════════════════════════════════════════════════════
//  MODAL HISTORIAL DEL ALUMNO
// ══════════════════════════════════════════════════════════════

let _historialAlumnoId  = null;
let _historialAlumnoNom = '';

function verHistorial(idAlumno, nombre) {
    _historialAlumnoId  = idAlumno;
    _historialAlumnoNom = nombre;

    document.getElementById('historialNombreAlumno').textContent = nombre;

    // Resetear al primer tab
    document.querySelectorAll('.hist-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.hist-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.hist-tab')[0].classList.add('active');
    document.getElementById('panelGrados').classList.add('active');

    // Resetear contenidos
    document.getElementById('historialGradosContent').innerHTML =
        '<p class="text-center">Cargando...</p>';
    document.getElementById('historialSeminariosContent').innerHTML =
        '<p class="text-center">Cargando...</p>';

    // Resetear formulario de participación
    const toastHS = document.getElementById('toastHistSem');
    if (toastHS) { toastHS.className = 'toast-inline'; toastHS.textContent = ''; }
    const modoSel = document.getElementById('sem_modo');
    if (modoSel) { modoSel.value = 'existente'; toggleModoSeminario('existente'); }
    const semSel = document.getElementById('sem_id_seminario');
    if (semSel)  semSel.value = '';
    const semFecha = document.getElementById('sem_fecha_participacion');
    if (semFecha)  semFecha.value = '';
    const semObs = document.getElementById('sem_observaciones');
    if (semObs)  semObs.value = '';

    abrirModal('historialModal');

    // Cargar ambos historiales en paralelo
    cargarHistorialGrados(idAlumno);
    cargarHistorialSeminarios(idAlumno);
}

function switchTab(tab, btn) {
    document.querySelectorAll('.hist-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.hist-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel' + tab.charAt(0).toUpperCase() + tab.slice(1))
            .classList.add('active');
}

function toggleModoSeminario(modo) {
    const bloqueExistente = document.getElementById('bloqueSemExistente');
    const bloqueNuevo     = document.getElementById('bloqueSemNuevo');
    if (!bloqueExistente || !bloqueNuevo) return;
    if (modo === 'nuevo') {
        bloqueExistente.style.display = 'none';
        bloqueNuevo.classList.add('show');
    } else {
        bloqueExistente.style.display = '';
        bloqueNuevo.classList.remove('show');
    }
}

// ── Cargar historial de grados ─────────────────────────────────────────
function cargarHistorialGrados(idAlumno) {
    fetch('/alumnos/' + idAlumno + '/historial')
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
                document.getElementById('historialGradosContent').innerHTML =
                    '<p class="seminario-empty"><i class="bi bi-award"></i><br>' +
                    'Sin historial de grados registrado.</p>';
                return;
            }
            let html = '<table style="width:100%;border-collapse:collapse;">' +
                '<thead><tr>' +
                '<th style="background:#c62828;color:#fff;padding:8px 10px;font-size:0.82rem;text-align:left;">Grado</th>' +
                '<th style="background:#c62828;color:#fff;padding:8px 10px;font-size:0.82rem;text-align:left;">Orden</th>' +
                '<th style="background:#c62828;color:#fff;padding:8px 10px;font-size:0.82rem;text-align:left;">Fecha</th>' +
                '<th style="background:#c62828;color:#fff;padding:8px 10px;font-size:0.82rem;text-align:left;">Observaciones</th>' +
                '</tr></thead><tbody>';
            data.forEach((r, i) => {
                const bg = i % 2 === 0 ? '#fff' : '#f9f9f9';
                html += '<tr style="background:' + bg + '">' +
                    '<td style="padding:8px 10px;font-size:0.88rem;">' + (r.nombreGrado     || '—') + '</td>' +
                    '<td style="padding:8px 10px;font-size:0.88rem;">' + (r.orden           || '—') + '</td>' +
                    '<td style="padding:8px 10px;font-size:0.88rem;">' + (r.fecha_obtencion || '—') + '</td>' +
                    '<td style="padding:8px 10px;font-size:0.88rem;">' + (r.observaciones   || '—') + '</td>' +
                    '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('historialGradosContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('historialGradosContent').innerHTML =
                '<p class="text-center" style="color:#e53935;">Error al cargar el historial de grados.</p>';
        });
}

// ── Cargar historial de seminarios ─────────────────────────────────────
function cargarHistorialSeminarios(idAlumno) {
    fetch('/alumnos/' + idAlumno + '/historial-seminarios')
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
                document.getElementById('historialSeminariosContent').innerHTML =
                    '<p class="seminario-empty"><i class="bi bi-journal-text"></i><br>' +
                    'Este alumno no tiene participaciones en seminarios.</p>';
                return;
            }

            const canDelete = {{ in_array(auth()->user()->rol ?? '', ['admin', 'sensei']) ? 'true' : 'false' }};
            let html = '';

            data.forEach(s => {
                const deleteBtn = canDelete
                    ? '<button class="sc-delete" title="Eliminar participación" ' +
                      'onclick="eliminarParticipacion(' + s.id + ', this)" ' +
                      'data-url="/alumnos/historial-seminarios/' + s.id + '">' +
                      '<i class="bi bi-trash3"></i></button>'
                    : '';

                const resultado = s.resultado
                    ? '<span><i class="bi bi-check2-circle"></i> ' + s.resultado + '</span>' : '';

                const obs = s.observaciones
                    ? '<div class="sc-obs">' + s.observaciones + '</div>' : '';

                html += '<div class="seminario-card" id="sc-' + s.id + '">' +
                    deleteBtn +
                    '<div class="sc-nombre"><i class="bi bi-journal-text"></i> ' + s.nombre_seminario + '</div>' +
                    '<div class="sc-meta">' +
                    '<span><i class="bi bi-calendar3"></i> ' + s.fecha + '</span>' +
                    '<span><i class="bi bi-person-fill"></i> ' + s.maestro + '</span>' +
                    '<span><i class="bi bi-calendar-check"></i> Participó: ' + s.fecha_participacion + '</span>' +
                    resultado +
                    '</div>' + obs + '</div>';
            });

            document.getElementById('historialSeminariosContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('historialSeminariosContent').innerHTML =
                '<p class="text-center" style="color:#e53935;">Error al cargar el historial de seminarios.</p>';
        });
}

// ── Registrar participación (existente o nuevo seminario) ──────────────
function registrarParticipacion() {
    const modo               = document.getElementById('sem_modo').value;
    const fechaParticipacion = document.getElementById('sem_fecha_participacion').value;
    const observaciones      = document.getElementById('sem_observaciones').value.trim();

    if (!fechaParticipacion) {
        mostrarToast('toastHistSem', 'La fecha de participación es obligatoria.', 'error');
        return;
    }

    const body = { modo, fecha_participacion: fechaParticipacion, observaciones: observaciones || null };

    if (modo === 'existente') {
        const idSem = document.getElementById('sem_id_seminario').value;
        if (!idSem) {
            mostrarToast('toastHistSem', 'Selecciona un seminario.', 'error');
            return;
        }
        body.id_seminario = parseInt(idSem);
    } else {
        const nombre      = document.getElementById('sem_nombre_nuevo').value.trim();
        const fechaSem    = document.getElementById('sem_fecha_nuevo').value;
        const maestro     = document.getElementById('sem_maestro_nuevo').value.trim();
        const resultado   = document.getElementById('sem_resultado_nuevo').value.trim();
        const descripcion = document.getElementById('sem_descripcion_nuevo').value.trim();

        if (!nombre || !fechaSem || !maestro) {
            mostrarToast('toastHistSem', 'Nombre, fecha y maestro del seminario son obligatorios.', 'error');
            return;
        }
        body.nombre_seminario = nombre;
        body.fecha_seminario  = fechaSem;
        body.maestro          = maestro;
        body.resultado        = resultado   || null;
        body.descripcion      = descripcion || null;
    }

    fetch('/alumnos/' + _historialAlumnoId + '/historial-seminarios', {
        method:  'POST',
        headers: {
            'Content-Type':     'application/json',
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            const msg = res.errors
                ? Object.values(res.errors).flat().join(' ')
                : (res.message || 'Error al registrar.');
            mostrarToast('toastHistSem', msg, 'error');
            return;
        }

        // Si se creó un seminario nuevo, agregarlo al catálogo y al selector
        if (res.seminario_nuevo) {
            agregarOpcionSelectorHistorial(res.seminario_nuevo);
        }

        // Renderizar el nuevo card en la lista
        const s         = res.registro;
        const canDelete = {{ in_array(auth()->user()->rol ?? '', ['admin', 'sensei']) ? 'true' : 'false' }};

        const deleteBtn = canDelete
            ? '<button class="sc-delete" title="Eliminar participación" ' +
              'onclick="eliminarParticipacion(' + s.id + ', this)" ' +
              'data-url="/alumnos/historial-seminarios/' + s.id + '">' +
              '<i class="bi bi-trash3"></i></button>'
            : '';

        const resultado = s.resultado
            ? '<span><i class="bi bi-check2-circle"></i> ' + s.resultado + '</span>' : '';
        const obs = s.observaciones
            ? '<div class="sc-obs">' + s.observaciones + '</div>' : '';

        const nuevoCard = '<div class="seminario-card" id="sc-' + s.id + '">' +
            deleteBtn +
            '<div class="sc-nombre"><i class="bi bi-journal-text"></i> ' + s.nombre_seminario + '</div>' +
            '<div class="sc-meta">' +
            '<span><i class="bi bi-calendar3"></i> ' + s.fecha + '</span>' +
            '<span><i class="bi bi-person-fill"></i> ' + s.maestro + '</span>' +
            '<span><i class="bi bi-calendar-check"></i> Participó: ' + s.fecha_participacion + '</span>' +
            resultado + '</div>' + obs + '</div>';

        const contenedor = document.getElementById('historialSeminariosContent');
        // Si estaba vacío, reemplazar; si no, prepend
        if (contenedor.querySelector('.seminario-empty')) {
            contenedor.innerHTML = nuevoCard;
        } else {
            contenedor.insertAdjacentHTML('afterbegin', nuevoCard);
        }

        // Limpiar formulario
        document.getElementById('sem_fecha_participacion').value = '';
        document.getElementById('sem_observaciones').value       = '';
        document.getElementById('sem_id_seminario').value        = '';
        document.getElementById('sem_modo').value                = 'existente';
        toggleModoSeminario('existente');
        if (document.getElementById('sem_nombre_nuevo'))
            document.getElementById('sem_nombre_nuevo').value    = '';
        if (document.getElementById('sem_fecha_nuevo'))
            document.getElementById('sem_fecha_nuevo').value     = '';
        if (document.getElementById('sem_maestro_nuevo'))
            document.getElementById('sem_maestro_nuevo').value   = '';
        if (document.getElementById('sem_resultado_nuevo'))
            document.getElementById('sem_resultado_nuevo').value = '';
        if (document.getElementById('sem_descripcion_nuevo'))
            document.getElementById('sem_descripcion_nuevo').value = '';

        mostrarToast('toastHistSem', 'Participación registrada con éxito.', 'success');
    })
    .catch(() => mostrarToast('toastHistSem', 'Error de conexión.', 'error'));
}

// ── Eliminar participación ─────────────────────────────────────────────
function eliminarParticipacion(historialId, btn) {
    if (!confirm('¿Eliminar esta participación del historial?')) return;

    const url  = btn.dataset.url;
    const card = document.getElementById('sc-' + historialId);

    fetch(url, {
        method:  'DELETE',
        headers: {
            'X-CSRF-TOKEN':     CSRF,
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'No se pudo eliminar la participación.');
            return;
        }
        card.style.transition = 'opacity 0.3s ease';
        card.style.opacity    = '0';
        setTimeout(() => {
            card.remove();
            const contenedor = document.getElementById('historialSeminariosContent');
            if (contenedor && !contenedor.querySelector('.seminario-card')) {
                contenedor.innerHTML =
                    '<p class="seminario-empty"><i class="bi bi-journal-text"></i><br>' +
                    'Este alumno no tiene participaciones en seminarios.</p>';
            }
        }, 300);
    })
    .catch(() => alert('Error al conectar con el servidor.'));
}
</script>
</body>
</html>