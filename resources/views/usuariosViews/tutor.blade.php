<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Tutores - Dojo</title>
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
                <i class="bi bi-person-lines-fill"></i> Gestión de Tutores
            </h1>
            <div class="breadcrumb">
                <a href="{{ route('principal') }}">Inicio</a>
                <i class="bi bi-chevron-right"></i>
                <span>Tutores</span>
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

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li><i class="bi bi-exclamation-circle"></i> {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="info-card">
            <h4><i class="bi bi-info-circle-fill"></i> Información sobre Tutores</h4>
            <p>
                Los tutores son responsables legales de los alumnos. Cada tutor debe tener un usuario
                con rol "tutor" registrado previamente. La ocupación se selecciona del catálogo.
            </p>
        </div>

        {{-- FORMULARIO REGISTRAR TUTOR --}}
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Tutor</h2>
                <p>Complete la información del tutor y su relación con el estudiante</p>
            </div>

            <form id="registroTutor" method="POST" action="{{ route('tutor.store') }}" class="form-body">
                @csrf

                <h3 class="section-title-header">
                    <i class="bi bi-person-circle"></i> Usuario del Tutor
                </h3>
                <div class="form-grid full-width">
                    <div class="form-group">
                        <label class="form-label" for="id_Tutor">
                            Usuario con rol Tutor <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-person-badge input-icon"></i>
                            {{-- Variable corregida: $usuarios_sin_perfil (TutorController@index) --}}
                            <select name="id_Tutor" id="id_Tutor" class="form-select" required>
                                <option value="">Seleccione un usuario</option>
                                @foreach($usuarios_sin_perfil as $u)
                                    <option value="{{ $u->id_Tutor }}">{{ $u->nombre_completo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <small style="color:#757575;margin-top:5px;display:block;">
                            Solo se muestran usuarios con rol "Tutor" sin perfil asignado
                        </small>
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-briefcase-fill"></i> Información Laboral
                </h3>
                <div class="form-grid full-width">
                    <div class="form-group">
                        {{-- Campo corregido: id_ocupacion (del catálogo BD, no texto libre) --}}
                        <label class="form-label" for="id_ocupacion">
                            Ocupación <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-briefcase input-icon"></i>
                            <select name="id_ocupacion" id="id_ocupacion" class="form-select" required>
                                <option value="">Seleccione una ocupación</option>
                                {{-- $ocupaciones viene del controller: id_ocupacion, nombre_ocupacion --}}
                                @foreach($ocupaciones as $ocu)
                                    <option value="{{ $ocu->id_ocupacion }}">{{ $ocu->nombre_ocupacion }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- NOTA: columna 'empresa' NO existe en BD. Si se necesita, hacer ALTER TABLE --}}
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-heart-fill"></i> Relación con el Estudiante
                </h3>
                <div class="form-grid full-width">
                    <div class="form-group">
                        <label class="form-label">Parentesco <span class="required">*</span></label>
                        <div class="relation-options" id="relationOptions">
                            <div class="relation-option" data-value="Padre">
                                <i class="bi bi-person-fill"></i><span>Padre</span>
                            </div>
                            <div class="relation-option" data-value="Madre">
                                <i class="bi bi-person-fill"></i><span>Madre</span>
                            </div>
                            <div class="relation-option" data-value="Abuelo/a">
                                <i class="bi bi-person-heart"></i><span>Abuelo/a</span>
                            </div>
                            <div class="relation-option" data-value="Tío/a">
                                <i class="bi bi-people-fill"></i><span>Tío/a</span>
                            </div>
                            <div class="relation-option" data-value="Hermano/a">
                                <i class="bi bi-people"></i><span>Hermano/a</span>
                            </div>
                            <div class="relation-option" data-value="Tutor Legal">
                                <i class="bi bi-shield-check"></i><span>Tutor Legal</span>
                            </div>
                        </div>
                        <input type="hidden" name="relacion_estudiante" id="relacionInput" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Registrar Tutor
                    </button>
                </div>
            </form>
        </div>

        {{-- TABLA DE TUTORES --}}
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="bi bi-table"></i>
                    Tutores Registrados ({{ count($tutores_registrados) }})
                </h2>
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" id="searchInput" placeholder="Buscar tutor...">
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tutor</th>
                            <th>Ocupación</th>
                            <th>Relación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tutoresTable">
                        @forelse ($tutores_registrados as $t)
                        <tr>
                            <td>
                                <div class="tutor-info">
                                    <div class="tutor-avatar">
                                        {{ strtoupper(substr($t->nombre_completo, 0, 1)) }}{{ strtoupper(substr(strstr($t->nombre_completo, ' '), 1, 1)) }}
                                    </div>
                                    <div class="tutor-details">
                                        <span class="tutor-name">{{ $t->nombre_completo }}</span>
                                        {{-- Campo: correo (usuario.correo) --}}
                                        <span class="tutor-email">{{ $t->correo }}</span>
                                    </div>
                                </div>
                            </td>
                            {{-- Campo: ocupacion (ocupacion.nombre_ocupacion) --}}
                            <td><span class="badge badge-occupation">{{ $t->ocupacion ?? '—' }}</span></td>
                            <td><span class="badge badge-relation">{{ $t->relacion_estudiante }}</span></td>
                            <td>
                                <div class="action-buttons">
                                    {{-- Pasa id_ocupacion (no texto) al modal de edición --}}
                                    <button class="action-btn btn-edit"
                                        onclick="openEditModal({{ $t->id_Tutor }}, {{ $t->id_ocupacion ?? 'null' }}, '{{ $t->relacion_estudiante }}')"
                                        title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No hay tutores registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('includes.pie')
</div>

{{-- MODAL EDICIÓN --}}
<div class="modal-overlay" id="editModal">
    <div class="modal-container">
        <div class="modal-header">
            <div>
                <h2 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Tutor</h2>
                <p class="modal-subtitle">Modifique la información del tutor</p>
            </div>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- TutorController@update recibe: id (ruta), id_ocupacion, relacion_estudiante --}}
        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')
            <input type="hidden" name="id_Tutor" id="edit_id_Tutor">

            <div class="modal-body">
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-briefcase-fill"></i> Información Laboral
                    </h3>
                    <div class="form-row full-width">
                        <div class="form-field">
                            {{-- Campo corregido: id_ocupacion (catálogo, no texto libre) --}}
                            <label class="field-label">Ocupación <span class="required">*</span></label>
                            <div class="field-wrapper">
                                <i class="bi bi-briefcase field-icon"></i>
                                <select name="id_ocupacion" id="edit_id_ocupacion" class="field-input" required>
                                    <option value="">Seleccione una ocupación</option>
                                    @foreach($ocupaciones as $ocu)
                                        <option value="{{ $ocu->id_ocupacion }}">{{ $ocu->nombre_ocupacion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Campo 'empresa' eliminado — no existe en BD --}}
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-heart-fill"></i> Relación con el Estudiante
                    </h3>
                    <div class="form-row full-width">
                        <div class="form-field">
                            <label class="field-label">Parentesco <span class="required">*</span></label>
                            <div class="relation-options" id="editRelationOptions">
                                <div class="relation-option" data-value="Padre"><i class="bi bi-person-fill"></i><span>Padre</span></div>
                                <div class="relation-option" data-value="Madre"><i class="bi bi-person-fill"></i><span>Madre</span></div>
                                <div class="relation-option" data-value="Abuelo/a"><i class="bi bi-person-heart"></i><span>Abuelo/a</span></div>
                                <div class="relation-option" data-value="Tío/a"><i class="bi bi-people-fill"></i><span>Tío/a</span></div>
                                <div class="relation-option" data-value="Hermano/a"><i class="bi bi-people"></i><span>Hermano/a</span></div>
                                <div class="relation-option" data-value="Tutor Legal"><i class="bi bi-shield-check"></i><span>Tutor Legal</span></div>
                            </div>
                            <input type="hidden" name="relacion_estudiante" id="edit_relacion_estudiante" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                    <i class="bi bi-x-lg"></i> Cancelar
                </button>
                <button type="submit" class="btn-modal btn-save">
                    <i class="bi bi-check-lg"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Selección de parentesco en formulario
    document.querySelectorAll('#relationOptions .relation-option').forEach(opt => {
        opt.addEventListener('click', function() {
            document.querySelectorAll('#relationOptions .relation-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('relacionInput').value = this.dataset.value;
        });
    });

    // Selección de parentesco en modal
    document.querySelectorAll('#editRelationOptions .relation-option').forEach(opt => {
        opt.addEventListener('click', function() {
            document.querySelectorAll('#editRelationOptions .relation-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('edit_relacion_estudiante').value = this.dataset.value;
        });
    });

    // Búsqueda en tabla
    $('#searchInput').on('keyup', function() {
        const txt = $(this).val().toLowerCase();
        $('#tutoresTable tr').each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(txt));
        });
    });
});

// Abrir modal de edición — recibe id_ocupacion (número, no texto)
function openEditModal(id, idOcupacion, relacion) {
    document.getElementById('edit_id_Tutor').value = id;
    document.getElementById('edit_id_ocupacion').value = idOcupacion || '';
    document.getElementById('edit_relacion_estudiante').value = relacion;

    // Marcar la opción de relación seleccionada
    document.querySelectorAll('#editRelationOptions .relation-option').forEach(opt => {
        opt.classList.toggle('selected', opt.dataset.value === relacion);
    });

    // URL dinámica: TutorController@update recibe $id por ruta
    document.getElementById('editForm').action = '{{ url("/tutor") }}/' + id;
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });
</script>
</body>
</html>