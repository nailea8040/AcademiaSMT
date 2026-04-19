<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Alumnos - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                El grado se registra en el historial de grados. El tutor se selecciona del catálogo de tutores registrados.
            </p>
        </div>

        {{-- FORMULARIO REGISTRAR ALUMNO --}}
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Alumno</h2>
                <p>Complete la información del alumno y sus datos académicos en el dojo</p>
            </div>

            <form id="registroAlumno" method="POST" action="{{ route('alumnos.store') }}" class="form-body" enctype="multipart/form-data">
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
                            {{-- Controller devuelve alumnos con rol='alumno' sin registro previo --}}
                            <select id="id_alumno" class="form-select" name="id_alumno" required>
                                <option value="">Seleccione un alumno</option>
                                @foreach($alumnos_registrados as $alumno)
                                    {{-- Mostrar alumnos sin historial de grado (sin inscripción previa) --}}
                                    <option value="{{ $alumno->id_usuario }}">{{ $alumno->nombre_alumno }}</option>
                                @endforeach
                            </select>
                        </div>
                        <small style="color:#757575;margin-top:5px;display:block;">
                            Solo se muestran usuarios con rol "Alumno"
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
                        {{-- Nombre del campo alineado con AlumnoController@store: 'fecha_inscripcion' --}}
                        <label class="form-label" for="fecha_inscripcion">
                            Fecha de Inscripción <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-calendar-check input-icon"></i>
                            <input type="date" id="fecha_inscripcion" class="form-input" name="fecha_inscripcion" required>
                        </div>
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-heart-pulse-fill"></i> Información Médica
                </h3>
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
                            <input type="file" id="documento_medico" name="documento_medico" accept=".pdf" style="display:none;" required>
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
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Registrar Alumno
                    </button>
                </div>
            </form>
        </div>

        {{-- TABLA DE ALUMNOS --}}
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="bi bi-table"></i>
                    Alumnos Registrados ({{ count($alumnos_registrados) }})
                </h2>
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Buscar por nombre o grado...">
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Grado Actual</th>
                            <th>Inscripción</th>
                            <th>Doc. Médico</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="alumnosTable">
                        @forelse($alumnos_registrados as $alumno)
                        <tr>
                            {{-- Campo: nombre_alumno (CONCAT en controller) --}}
                            <td>{{ $alumno->nombre_alumno }}</td>

                            {{-- Campo: nombreGrado (JOIN historial_grados → grado) --}}
                            <td>{{ $alumno->nombreGrado ?? '— Sin asignar —' }}</td>

                            {{-- Campo: fecha_inscripcion (registro_fisico.fecha_registro) --}}
                            <td>
                                @if($alumno->fecha_inscripcion)
                                    {{ \Carbon\Carbon::parse($alumno->fecha_inscripcion)->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Campo: certificado_medico (registro_fisico) --}}
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
                                {{-- data-grado usa id_grado del JOIN con historial_grados --}}
                                <button type="button" class="action-btn btn-edit edit-alumno-btn"
                                    data-id="{{ $alumno->id_usuario }}"
                                    data-nombre="{{ $alumno->nombre_alumno }}"
                                    data-grado="{{ $alumno->id_grado }}"
                                    data-fecha="{{ $alumno->fecha_inscripcion }}"
                                    title="Actualizar grado">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>

                                {{-- Botón historial de grados --}}
                                <button type="button" class="action-btn btn-view"
                                    onclick="verHistorial({{ $alumno->id_usuario }}, '{{ $alumno->nombre_alumno }}')"
                                    title="Ver historial de grados">
                                    <i class="bi bi-clock-history"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay alumnos registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('includes.pie')
</div>

{{-- MODAL EDITAR / ASIGNAR NUEVO GRADO --}}
<div id="editModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 class="modal-title">
                    <i class="bi bi-award-fill"></i> Asignar Nuevo Grado
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

            <div class="form-section">
                <div class="form-row full-width">
                    <div class="form-field">
                        <label class="field-label" for="edit_id_grado">
                            Nuevo Grado <span class="required">*</span>
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
                        {{-- Nombre alineado con AlumnoController@update: 'fecha_obtencion' --}}
                        <label class="field-label" for="edit_fecha_obtencion">
                            Fecha de Obtención <span class="required">*</span>
                        </label>
                        <div class="field-wrapper">
                            <i class="bi bi-calendar-check field-icon"></i>
                            <input type="date" id="edit_fecha_obtencion" name="fecha_obtencion" class="field-input" required>
                        </div>
                    </div>
                </div>

                <div class="form-row full-width">
                    <div class="form-field">
                        <label class="field-label" for="edit_observaciones">Observaciones</label>
                        <div class="field-wrapper">
                            <i class="bi bi-chat-left-text field-icon"></i>
                            <input type="text" id="edit_observaciones" name="observaciones" class="field-input"
                                   placeholder="Ej: Aprobó examen de grado con distinción">
                        </div>
                    </div>
                </div>

                <div class="form-row full-width">
                    <div class="form-field">
                        <label class="field-label" for="edit_documento_medico">
                            Actualizar Doc. Médico (Opcional)
                        </label>
                        <input type="file" id="edit_documento_medico" name="documento_medico"
                               class="field-input" accept=".pdf">
                        <small style="color:#757575;">PDF máx. 5MB — si no selecciona, se mantiene el actual</small>
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

{{-- MODAL HISTORIAL DE GRADOS --}}
<div id="historialModal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 class="modal-title"><i class="bi bi-clock-history"></i> Historial de Grados</h2>
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
document.addEventListener('DOMContentLoaded', function() {
    // Drag & Drop para subida de archivo
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
        uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
        uploadArea.addEventListener('dragleave', e => { e.preventDefault(); uploadArea.classList.remove('drag-over'); });
        uploadArea.addEventListener('drop', e => {
            e.preventDefault(); uploadArea.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) { fileInput.files = e.dataTransfer.files; handleFile(e.dataTransfer.files[0]); }
        });
        fileInput.addEventListener('change', e => { if (e.target.files[0]) handleFile(e.target.files[0]); });
        if (removeBtn) removeBtn.addEventListener('click', () => {
            fileInput.value = ''; uploadArea.style.display = 'block'; preview.classList.remove('active');
        });
    }

    function handleFile(file) {
        if (file.type !== 'application/pdf') { alert('Solo se aceptan archivos PDF'); fileInput.value = ''; return; }
        if (file.size > 5 * 1024 * 1024) { alert('El archivo supera los 5MB'); fileInput.value = ''; return; }
        fileName.textContent = file.name;
        fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        uploadArea.style.display = 'none';
        preview.classList.add('active');
    }

    // Modal editar
    document.querySelectorAll('.edit-alumno-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id    = this.dataset.id;
            const nombre = this.dataset.nombre;
            const grado  = this.dataset.grado;
            const fecha  = this.dataset.fecha;

            document.getElementById('editNombreAlumno').textContent = nombre;
            document.getElementById('edit_id_grado').value = grado || '';
            document.getElementById('edit_fecha_obtencion').value = fecha ? fecha.substring(0,10) : '';
            // URL → AlumnoController@update con PK id_usuario
            document.getElementById('editForm').action = '{{ url("/alumnos") }}/' + id;
            document.getElementById('editModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
});

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
    document.body.style.overflow = '';
}

function closeHistorialModal() {
    document.getElementById('historialModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Consultar historial de grados vía JSON (AlumnoController@historialGrados)
function verHistorial(idAlumno, nombre) {
    document.getElementById('historialNombreAlumno').textContent = nombre;
    document.getElementById('historialContent').innerHTML = '<p class="text-center">Cargando...</p>';
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
            document.getElementById('historialContent').innerHTML = '<p class="text-center text-danger">Error al cargar historial.</p>';
        });
}

window.addEventListener('click', e => {
    if (e.target === document.getElementById('editModal')) closeEditModal();
    if (e.target === document.getElementById('historialModal')) closeHistorialModal();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeEditModal(); closeHistorialModal(); }
});

// Búsqueda en tabla
$(document).ready(function() {
    $('#searchInput').on('keyup', function() {
        const txt = $(this).val().toLowerCase();
        $('#alumnosTable tr').each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(txt));
        });
    });
});
</script>
</body>
</html>