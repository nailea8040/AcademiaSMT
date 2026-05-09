<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Alumnos - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
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
        .badge-no-bachiller {
            color: #bdbdbd;
            font-size: 0.8rem;
        }
        /* Indicador en tabla */
        .sch-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: #e8f5e9; color: #2e7d32;
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 700;
        }
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
            </p>
        </div>

        {{-- ══ FORMULARIO REGISTRAR ALUMNO ══ --}}
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Alumno</h2>
                <p>Complete la información del alumno y sus datos académicos en el dojo</p>
            </div>

            <form id="registroAlumno" method="POST" action="{{ route('alumnos.store') }}"
                  class="form-body" enctype="multipart/form-data">
                @csrf

                {{-- Información del alumno --}}
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

                {{-- Información académica --}}
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

                {{-- ── Sección Bachiller ── --}}
                <h3 class="section-title-header">
                    <i class="bi bi-mortarboard-fill"></i> Datos de Bachiller
                    <small style="font-weight:400;color:#888;font-size:0.82rem;margin-left:8px;">
                        (opcional — solo si el alumno pertenece al bachiller)
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
                                                <option value="Nocturno">Nocturno</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Información médica --}}
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
                            Esta información es confidencial y solo será utilizada para garantizar la seguridad del alumno
                        </small>
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-content">
                                <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                <p class="upload-text">Arrastra archivos aquí o haz clic para seleccionar</p>
                                <button type="button" class="btn-upload" id="selectFileBtn">Seleccionar Archivos</button>
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
        </div>

        {{-- ══ TABLA DE ALUMNOS ══ --}}
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

                            {{-- Columna Bachiller --}}
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
                                       target="_blank" class="btn btn-sm btn-info" title="Ver documento médico">
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
                                <button type="button" class="action-btn btn-edit edit-alumno-btn"
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

                                <button type="button" class="action-btn btn-view"
                                    onclick="verHistorial({{ $alumno->id_usuario }}, '{{ $alumno->nombre_alumno }}')"
                                    title="Ver historial de grados">
                                    <i class="bi bi-clock-history"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No hay alumnos registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('includes.pie')
</div>

{{-- ══ MODAL EDITAR ══ --}}
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

            {{-- ── Sección Bachiller en modal ── --}}
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
                                           name="numero_control" placeholder="Ej: 12345678" maxlength="20">
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
                                           name="especialidad" placeholder="Ej: Informática" maxlength="100">
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
                                        <option value="Nocturno">Nocturno</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Documento médico --}}
            <div class="form-section" style="margin-top:20px;">
                <h4 style="font-size:0.95rem;font-weight:700;color:#333;margin-bottom:12px;">
                    <i class="bi bi-heart-pulse-fill"></i> Datos Físicos y Documento Médico
                </h4>
                <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
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

{{-- ══ MODAL HISTORIAL DE GRADOS ══ --}}
<div id="historialModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <i class="bi bi-clock-history"></i> Historial de Grados
                </h2>
                <p class="modal-subtitle" id="historialNombreAlumno"></p>
            </div>
            <button type="button" class="modal-close" onclick="closeHistorialModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="historialContent">
                <p class="text-center">Cargando historial...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Drag & Drop subida de archivo ──────────────────────────────────
    const uploadArea = document.getElementById('uploadArea');
    const fileInput  = document.getElementById('documento_medico');
    const selectBtn  = document.getElementById('selectFileBtn');
    const preview    = document.getElementById('file-preview');
    const fileName   = document.getElementById('file-name');
    const fileSize   = document.getElementById('file-size');
    const removeBtn  = document.getElementById('removeFileBtn');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', e => { if (e.target !== selectBtn) fileInput.click(); });
        selectBtn.addEventListener('click', e => { e.stopPropagation(); fileInput.click(); });
        uploadArea.addEventListener('dragover',  e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
        uploadArea.addEventListener('dragleave', e => { e.preventDefault(); uploadArea.classList.remove('drag-over'); });
        uploadArea.addEventListener('drop', e => {
            e.preventDefault(); uploadArea.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; handleFile(e.dataTransfer.files[0]); }
        });
        fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
        if (removeBtn) removeBtn.addEventListener('click', () => {
            fileInput.value = '';
            uploadArea.style.display = 'block';
            preview.classList.remove('active');
        });
    }

    function handleFile(file) {
        if (file.type !== 'application/pdf') { alert('Solo se aceptan archivos PDF'); fileInput.value = ''; return; }
        if (file.size > 5 * 1024 * 1024)    { alert('El archivo supera los 5MB');    fileInput.value = ''; return; }
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        uploadArea.style.display = 'none';
        preview.classList.add('active');
    }

    // ── Modal editar: poblar datos ─────────────────────────────────────
    document.querySelectorAll('.edit-alumno-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id            = this.dataset.id;
            const nombre        = this.dataset.nombre;
            const grado         = this.dataset.grado;
            const fecha         = this.dataset.fecha;
            const esBachiller   = this.dataset.bachiller === '1';
            const numControl    = this.dataset.numero_control;
            const grupo         = this.dataset.grupo;
            const especialidad  = this.dataset.especialidad;
            const turno         = this.dataset.turno;
            const peso          = this.dataset.peso;
            const estatura      = this.dataset.estatura;

            document.getElementById('editNombreAlumno').textContent    = nombre;
            document.getElementById('edit_id_grado').value             = grado || '';
            document.getElementById('edit_fecha_obtencion').value      = fecha ? fecha.substring(0, 10) : '';
            document.getElementById('edit_observaciones').value        = '';
            document.getElementById('edit_peso').value                 = peso || '';
            document.getElementById('edit_estatura').value             = estatura || '';
            document.getElementById('editForm').action                 = '{{ url("/alumnos") }}/' + id;

            // Datos de bachiller
            const cbEdit = document.getElementById('esBachillerEdit');
            cbEdit.checked = esBachiller;
            toggleBachiller('edit', esBachiller);

            if (esBachiller) {
                document.getElementById('edit_numero_control').value = numControl || '';
                document.getElementById('edit_grupo').value          = grupo       || '';
                document.getElementById('edit_especialidad').value   = especialidad|| '';
                document.getElementById('edit_turno').value          = turno        || '';
            }

            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
});

// ── Toggle sección bachiller ───────────────────────────────────────────
function toggleBachiller(scope, checked) {
    const fields = document.getElementById('bachillerFields' + (scope === 'reg' ? 'Reg' : 'Edit'));
    if (!fields) return;
    if (checked) {
        fields.classList.add('show');
    } else {
        fields.classList.remove('show');
        // Limpiar campos al desmarcar
        fields.querySelectorAll('input, select').forEach(el => el.value = '');
    }
}

function resetBachillerReg() {
    const cb = document.getElementById('esBachillerReg');
    if (cb) { cb.checked = false; toggleBachiller('reg', false); }
}

// ── Modales ────────────────────────────────────────────────────────────
function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
    document.body.style.overflow = '';
}

function closeHistorialModal() {
    document.getElementById('historialModal').classList.remove('active');
    document.body.style.overflow = '';
}

function verHistorial(idAlumno, nombre) {
    document.getElementById('historialNombreAlumno').textContent = nombre;
    document.getElementById('historialContent').innerHTML        = '<p class="text-center">Cargando...</p>';
    document.getElementById('historialModal').classList.add('active');
    document.body.style.overflow = 'hidden';

    fetch(`/alumnos/${idAlumno}/historial`)
        .then(r => r.json())
        .then(data => {
            if (!data.length) {
                document.getElementById('historialContent').innerHTML = '<p class="text-center">Sin historial de grados.</p>';
                return;
            }
            let html = '<table style="width:100%;border-collapse:collapse;">';
            html += '<thead><tr><th>Grado</th><th>Orden</th><th>Fecha</th><th>Observaciones</th></tr></thead><tbody>';
            data.forEach(r => {
                html += `<tr>
                    <td>${r.nombreGrado}</td>
                    <td>${r.orden}</td>
                    <td>${r.fecha_obtencion}</td>
                    <td>${r.observaciones || '—'}</td>
                </tr>`;
            });
            html += '</tbody></table>';
            document.getElementById('historialContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('historialContent').innerHTML =
                '<p class="text-center text-danger">Error al cargar historial.</p>';
        });
}

window.addEventListener('click', e => {
    if (e.target === document.getElementById('editModal'))     closeEditModal();
    if (e.target === document.getElementById('historialModal')) closeHistorialModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeEditModal(); closeHistorialModal(); }
});

// Búsqueda en tabla
$(document).ready(function () {
    $('#searchInput').on('keyup', function () {
        const txt = $(this).val().toLowerCase();
        $('#alumnosTable tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(txt));
        });
    });
});
</script>
</body>
</html>