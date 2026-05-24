<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Tutores - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* ── Tabla de alumnos dentro del card ─── */
        .alumnos-list { display:flex; flex-wrap:wrap; gap:6px; }
        .alumno-chip  {
            display:inline-flex; align-items:center; gap:5px;
            background:#e8f5e9; color:#2e7d32;
            padding:3px 10px; border-radius:20px;
            font-size:12px; font-weight:600;
        }

        /* ── Constructor de alumnos (formulario) ─── */
        .alumnos-builder { border:1.5px dashed #e0e0e0; border-radius:10px; padding:14px; margin-top:8px; }
        .alumno-row { align-items:center; margin-bottom:8px; }
        .alumno-row:last-child { margin-bottom:0; }
        .btn-remove-alumno {
            background:#ffebee; border:none; border-radius:8px;
            color:#c62828; width:36px; height:36px;
            cursor:pointer; display:flex; align-items:center; justify-content:center;
            font-size:16px; flex-shrink:0;
        }
        .btn-remove-alumno:hover { background:#ffcdd2; }
        .btn-add-alumno {
            display:inline-flex; align-items:center; gap:6px;
            background:#e8f5e9; color:#2e7d32; border:none;
            border-radius:8px; padding:7px 14px; cursor:pointer;
            font-size:13px; font-weight:600; margin-top:10px;
        }
        .btn-add-alumno:hover { background:#c8e6c9; }
        .no-alumnos-hint { color:#9e9e9e; font-size:12px; margin:6px 0; }

        /* ── Dropdown custom de parentesco ────────────────────── */
        .rel-dropdown { position:relative; cursor:pointer; user-select:none; }
        .rel-trigger {
            display:flex; align-items:center; gap:4px;
            height:40px; padding:0 10px;
            border:1.5px solid #ddd; border-radius:8px;
            background:#fff; font-size:13px; color:#1a1a1a;
            transition:border-color .2s;
        }
        .rel-dropdown.open .rel-trigger,
        .rel-trigger:hover { border-color:#c62828; }
        .rel-chevron { margin-left:auto; font-size:12px; color:#9e9e9e; transition:transform .2s; }
        .rel-dropdown.open .rel-chevron { transform:rotate(180deg); }
        .rel-panel {
            position:absolute; top:calc(100% + 4px); left:0; right:0;
            background:#fff; border:1.5px solid #e0e0e0; border-radius:10px;
            box-shadow:0 8px 24px rgba(0,0,0,.13);
            z-index:999;
            display:grid; grid-template-columns:1fr 1fr;
            opacity:0; pointer-events:none;
            transform:translateY(-6px); transition:opacity .18s, transform .18s;
        }
        .rel-dropdown.open .rel-panel { opacity:1; pointer-events:all; transform:translateY(0); }
        .rel-opt {
            display:flex; align-items:center; gap:8px;
            padding:10px 12px; font-size:13px; color:#333;
            cursor:pointer; transition:background .15s;
            border-bottom:1px solid #f5f5f5;
        }
        .rel-opt:nth-child(odd)  { border-right:1px solid #f5f5f5; }
        .rel-opt:hover { background:#fafafa; }
        .rel-opt-active { background:#fff8f8 !important; font-weight:700; }
        .rel-opt-active span { color:#c62828; }
    </style>
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
                Los tutores son responsables legales de los alumnos. Cada tutor puede tener
                uno o más hijos/alumnos relacionados, cada uno con su propio parentesco.
            </p>
        </div>

        {{-- FORMULARIO REGISTRAR TUTOR --}}
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-person-plus-fill"></i> Registrar Nuevo Tutor</h2>
                <p>Complete la información del tutor y sus alumnos a cargo</p>
            </div>

            <form id="registroTutor" method="POST" action="{{ route('tutor.store') }}" class="form-body">
                @csrf

                {{-- FILA 1: Tutor + Ocupación --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:8px;">

                    <div>
                        <h3 class="section-title-header" style="margin-top:0;">
                            <i class="bi bi-person-circle"></i> Usuario del Tutor
                        </h3>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="id_Tutor">
                                Usuario con rol Tutor <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-person-badge input-icon"></i>
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

                    <div>
                        <h3 class="section-title-header" style="margin-top:0;">
                            <i class="bi bi-briefcase-fill"></i> Información Laboral
                        </h3>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label" for="id_ocupacion">
                                Ocupación <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-briefcase input-icon"></i>
                                <select name="id_ocupacion" id="id_ocupacion" class="form-select" required>
                                    <option value="">Seleccione una ocupación</option>
                                    @foreach($ocupaciones as $ocu)
                                        <option value="{{ $ocu->id_ocupacion }}">{{ $ocu->nombre_ocupacion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Hidden relacion_estudiante: se sincroniza desde el dropdown de cada fila --}}
                <input type="hidden" name="relacion_estudiante" id="relacionInput" value="Tutor">

                {{-- Alumnos a Cargo (inmediatamente debajo de Usuario/Ocupación) --}}
                <h3 class="section-title-header" style="margin-top:20px;">
                    <i class="bi bi-people-fill"></i> Alumnos a Cargo
                    <small style="font-weight:400;color:#9e9e9e;font-size:12px;margin-left:6px;">(opcional)</small>
                </h3>
                <div class="alumnos-builder" id="alumnosBuilder">
                    <div id="alumnosContainer">
                        <p class="no-alumnos-hint" id="noAlumnosHint">
                            <i class="bi bi-info-circle"></i>
                            Aún no has agregado alumnos. Puedes vincularlos después desde esta misma pantalla.
                        </p>
                    </div>
                    <button type="button" class="btn-add-alumno" onclick="agregarFilaAlumno('reg')">
                        <i class="bi bi-plus-circle-fill"></i> Agregar alumno
                    </button>
                </div>

                <div class="form-actions" style="margin-top:20px;">
                    <button type="reset" class="btn btn-secondary" onclick="resetAlumnos()">
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
                            <th>Parentesco</th>
                            <th>Alumnos a Cargo</th>
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
                                        <span class="tutor-email">{{ $t->correo }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-occupation">{{ $t->ocupacion ?? '—' }}</span></td>
                            <td><span class="badge badge-relation">{{ $t->relacion_estudiante }}</span></td>
                            <td>
                                @if(count($t->alumnos_relacionados) > 0)
                                    <div class="alumnos-list">
                                        @foreach($t->alumnos_relacionados as $ar)
                                            <span class="alumno-chip">
                                                <i class="bi bi-person-badge-fill"></i>
                                                {{ $ar->nombre_alumno }}
                                                <span style="opacity:.65;font-weight:400">({{ $ar->relacion }})</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span style="color:#bdbdbd;font-size:13px">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn btn-edit"
                                        onclick="openEditModal(
                                            {{ $t->id_Tutor }},
                                            {{ $t->id_ocupacion ?? 'null' }},
                                            '{{ addslashes($t->relacion_estudiante) }}',
                                            {{ json_encode($t->alumnos_relacionados) }}
                                        )"
                                        title="Editar">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No hay tutores registrados.</td>
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
                        </div>
                    </div>
                </div>

                {{-- Hidden sincronizado con el dropdown de cada fila --}}
                <input type="hidden" name="relacion_estudiante" id="edit_relacion_estudiante" value="Tutor">

                {{-- Constructor de alumnos en edición --}}
                <div class="form-section">
                    <h3 class="form-section-title">
                        <i class="bi bi-people-fill"></i> Alumnos a Cargo
                        <small style="font-weight:400;color:#9e9e9e;font-size:12px;margin-left:6px;">(opcional)</small>
                    </h3>
                    <div class="alumnos-builder">
                        <div id="editAlumnosContainer">
                            <p class="no-alumnos-hint" id="editNoAlumnosHint">
                                <i class="bi bi-info-circle"></i> Sin alumnos asignados.
                            </p>
                        </div>
                        <button type="button" class="btn-add-alumno" onclick="agregarFilaAlumno('edit')">
                            <i class="bi bi-plus-circle-fill"></i> Agregar alumno
                        </button>
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

{{-- Datos de alumnos para JS --}}
<script>
const ALUMNOS_DISPONIBLES = @json($alumnos);

const RELACIONES_CONFIG = [
    { value: 'Padre',       icon: 'bi-person-fill',   color: '#1565c0' },
    { value: 'Madre',       icon: 'bi-person-fill',   color: '#ad1457' },
    { value: 'Abuelo/a',   icon: 'bi-person-heart',  color: '#4527a0' },
    { value: 'Tío/a',      icon: 'bi-people-fill',   color: '#00695c' },
    { value: 'Hermano/a',  icon: 'bi-people',         color: '#e65100' },
    { value: 'Tutor Legal', icon: 'bi-shield-check',  color: '#37474f' },
];

let regCounter  = 0;
let editCounter = 0;

// ── Cerrar todos los dropdowns abiertos ───────────────────────────────────────
function cerrarDropdowns(excepto = null) {
    document.querySelectorAll('.rel-dropdown.open').forEach(d => {
        if (d !== excepto) d.classList.remove('open');
    });
}
document.addEventListener('click', () => cerrarDropdowns());

// ── Construir el trigger + dropdown visual de parentesco ──────────────────────
function buildRelDropdown(name, selectedVal, rowId) {
    const actual = RELACIONES_CONFIG.find(r => r.value === selectedVal) || null;
    const triggerTxt = actual
        ? `<i class="bi ${actual.icon}" style="color:${actual.color};margin-right:6px;font-size:14px;"></i>${actual.value}`
        : `<span style="color:#9ca3af;">— Parentesco —</span>`;

    // Input hidden que guarda el valor real
    let html = `<input type="hidden" name="${name}" value="${selectedVal || ''}" id="hrel-${rowId}">`;

    // Trigger (parece un select)
    html += `
    <div class="rel-dropdown" id="reldrop-${rowId}" onclick="toggleRelDropdown('${rowId}', event)">
        <div class="rel-trigger" id="reltrig-${rowId}">${triggerTxt}<i class="bi bi-chevron-down rel-chevron"></i></div>
        <div class="rel-panel">`;

    RELACIONES_CONFIG.forEach(r => {
        const actClass = r.value === selectedVal ? ' rel-opt-active' : '';
        html += `
            <div class="rel-opt${actClass}" onclick="elegirRelacion('${rowId}','${r.value}','${r.icon}','${r.color}', event)">
                <i class="bi ${r.icon}" style="color:${r.color};font-size:16px;"></i>
                <span>${r.value}</span>
            </div>`;
    });
    html += `</div></div>`;
    return html;
}

function toggleRelDropdown(rowId, e) {
    e.stopPropagation();
    const drop = document.getElementById(`reldrop-${rowId}`);
    const isOpen = drop.classList.contains('open');
    cerrarDropdowns();
    if (!isOpen) drop.classList.add('open');
}

function elegirRelacion(rowId, valor, icon, color, e) {
    e.stopPropagation();
    // Actualizar hidden
    document.getElementById(`hrel-${rowId}`).value = valor;
    // Actualizar trigger visual
    document.getElementById(`reltrig-${rowId}`).innerHTML =
        `<i class="bi ${icon}" style="color:${color};margin-right:6px;font-size:14px;"></i>${valor}<i class="bi bi-chevron-down rel-chevron"></i>`;
    // Marcar activo
    const drop = document.getElementById(`reldrop-${rowId}`);
    drop.querySelectorAll('.rel-opt').forEach(o => o.classList.remove('rel-opt-active'));
    drop.querySelector(`.rel-opt[onclick*="'${valor}'"]`).classList.add('rel-opt-active');
    drop.classList.remove('open');
    // Sincronizar hidden global con la primera fila
    sincronizarHiddenRelacion(rowId);
}

// Sincroniza el hidden relacion_estudiante con el valor de la primera fila del ctx
function sincronizarHiddenRelacion(rowId) {
    const isEdit   = rowId.startsWith('edit-');
    const hiddenId = isEdit ? 'edit_relacion_estudiante' : 'relacionInput';
    const ctxId    = isEdit ? 'editAlumnosContainer'     : 'alumnosContainer';
    const primerHidden = document.querySelector(`#${ctxId} input[type="hidden"][id^="hrel-"]`);
    if (primerHidden) {
        document.getElementById(hiddenId).value = primerHidden.value || 'Tutor';
    }
}

// ── Construir select de alumno ────────────────────────────────────────────────
function buildAlumnoSelect(name, selectedId) {
    let html = `<select name="${name}" class="form-select" style="width:100%;height:40px;padding:0 10px;border:1.5px solid #ddd;border-radius:8px;font-size:13px;" required>
        <option value="">— Alumno —</option>`;
    ALUMNOS_DISPONIBLES.forEach(a => {
        const sel = a.id_usuario == selectedId ? 'selected' : '';
        html += `<option value="${a.id_usuario}" ${sel}>${a.nombre_completo}</option>`;
    });
    html += '</select>';
    return html;
}

// ── Agregar fila ──────────────────────────────────────────────────────────────
function agregarFilaAlumno(ctx, idAlumno = null, relacion = null) {
    const isEdit    = ctx === 'edit';
    const container = document.getElementById(isEdit ? 'editAlumnosContainer' : 'alumnosContainer');
    const hint      = document.getElementById(isEdit ? 'editNoAlumnosHint'    : 'noAlumnosHint');
    if (hint) hint.style.display = 'none';

    const idx   = isEdit ? editCounter++ : regCounter++;
    const rowId = `${ctx}-row-${idx}`;

    const div = document.createElement('div');
    div.className = 'alumno-row';
    div.id = rowId;
    // Layout: 65% alumno | 35% parentesco | 36px eliminar
    div.style.cssText = 'display:grid;grid-template-columns:1fr 1fr 36px;gap:8px;align-items:center;margin-bottom:8px;';
    div.innerHTML = `
        ${buildAlumnoSelect(`alumnos[${idx}][id_alumno]`, idAlumno)}
        ${buildRelDropdown(`alumnos[${idx}][relacion]`, relacion, rowId)}
        <button type="button" class="btn-remove-alumno" onclick="eliminarFilaAlumno('${rowId}','${ctx}')">
            <i class="bi bi-trash-fill"></i>
        </button>
    `;
    container.appendChild(div);
}

function eliminarFilaAlumno(rowId, ctx) {
    const row = document.getElementById(rowId);
    if (row) row.remove();
    const isEdit    = ctx === 'edit';
    const container = document.getElementById(isEdit ? 'editAlumnosContainer' : 'alumnosContainer');
    const hint      = document.getElementById(isEdit ? 'editNoAlumnosHint'    : 'noAlumnosHint');
    if (hint && container.querySelectorAll('.alumno-row').length === 0) hint.style.display = '';
    // Resincronizar tras eliminar
    const hiddenId = isEdit ? 'edit_relacion_estudiante' : 'relacionInput';
    const primerHidden = document.querySelector(`#${container.id} input[type="hidden"][id^="hrel-"]`);
    document.getElementById(hiddenId).value = primerHidden ? primerHidden.value || 'Tutor' : 'Tutor';
}

function resetAlumnos() {
    const container = document.getElementById('alumnosContainer');
    const hint      = document.getElementById('noAlumnosHint');
    container.querySelectorAll('.alumno-row').forEach(r => r.remove());
    regCounter = 0;
    if (hint) hint.style.display = '';
    document.getElementById('relacionInput').value = 'Tutor';
}

// ── Búsqueda ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    $('#searchInput').on('keyup', function() {
        const txt = $(this).val().toLowerCase();
        $('#tutoresTable tr').each(function() {
            $(this).toggle($(this).text().toLowerCase().includes(txt));
        });
    });
});

// ── Modal de edición ──────────────────────────────────────────────────────────
function openEditModal(id, idOcupacion, relacion, alumnosRelacionados) {
    document.getElementById('edit_id_Tutor').value     = id;
    document.getElementById('edit_id_ocupacion').value = idOcupacion || '';
    document.getElementById('edit_relacion_estudiante').value = relacion || 'Tutor';
    document.getElementById('editForm').action = '{{ url("/tutor") }}/' + id;

    const container = document.getElementById('editAlumnosContainer');
    container.querySelectorAll('.alumno-row').forEach(r => r.remove());
    editCounter = 0;
    document.getElementById('editNoAlumnosHint').style.display = '';

    if (Array.isArray(alumnosRelacionados) && alumnosRelacionados.length) {
        alumnosRelacionados.forEach(a => agregarFilaAlumno('edit', a.id_alumno, a.relacion));
        // Resincronizar hidden con el primer alumno cargado
        const primerHidden = container.querySelector('input[type="hidden"][id^="hrel-"]');
        if (primerHidden) document.getElementById('edit_relacion_estudiante').value = primerHidden.value || 'Tutor';
    }

    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() { document.getElementById('editModal').classList.remove('active'); }
document.getElementById('editModal').addEventListener('click', function(e) { if (e.target === this) closeEditModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });
</script>
</body>
</html>