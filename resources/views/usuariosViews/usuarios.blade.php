<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Usuarios - Sistema Dojo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

   @include('includes.menu')

    <div class="main-content">
        <header class="header">
            <div>
                <h1 class="header-title">
                    <i class="bi bi-people-fill"></i>
                    Gestión de Usuarios
                </h1>
                <div class="breadcrumb">
                    <a href="#">Inicio</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Usuarios</span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">

            @if(session('mensaje'))
                <div
                    class="alert {{ session('sessionInsertado') == 'true' ? 'alert-success' : 'alert-error' }}"
                    id="alerta-temp"
                    role="alert"
                    style="display: flex;">
                    <i class="bi {{ session('sessionInsertado') == 'true' ? 'bi-check-circle-fill' : 'bi-x-octagon-fill' }} alert-icon"></i>
                    <div>
                        <strong>{{ session('sessionInsertado') == 'true' ? '¡Éxito!' : '¡Error!' }}</strong>
                        {{ session('mensaje') }}
                    </div>
                </div>
            @endif

            {{-- ── Formulario de registro ─────────────────────────────────── --}}
            <div class="form-container">
                <div class="form-header">
                    <h2>
                        <i class="bi bi-person-plus-fill"></i>
                        Registrar Nuevo Usuario
                    </h2>
                    <p>Complete todos los campos requeridos para crear un nuevo usuario en el sistema</p>
                </div>

                <form id="registroForm" class="form-body" action="{{ route('usuarios.store') }}" method="POST">
                    @csrf

                    <h3 style="margin-bottom: 20px; color: #2d2d2d; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-person-circle"></i>
                        Información Personal
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nombre(s) <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" class="form-input" id="nombre" name="nombre" placeholder="Nombre(s)" required value="{{ old('nombre') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido Paterno <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" class="form-input" id="apaterno" name="apaterno" placeholder="Apellido Paterno" required value="{{ old('apaterno') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Apellido Materno <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" class="form-input" id="amaterno" name="amaterno" placeholder="Apellido Materno" required value="{{ old('amaterno') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Nacimiento <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-calendar input-icon"></i>
                                <input type="date" class="form-input" id="fecha_naci" name="fecha_naci" required value="{{ old('fecha_naci') }}">
                            </div>
                        </div>
                    </div>

                    <h3 style="margin: 30px 0 20px; color: #2d2d2d; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-envelope-fill"></i>
                        Información de Contacto
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Correo Electrónico <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-envelope input-icon"></i>
                                <input type="email" class="form-input" id="correo" name="correo" placeholder="correo@ejemplo.com" required value="{{ old('correo') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-telephone input-icon"></i>
                                <input type="text" class="form-input" id="tel" name="tel" placeholder="10 dígitos" required
                                    minlength="10" maxlength="10" pattern="[0-9]{10}">
                            </div>
                        </div>
                    </div>

                    <h3 style="margin: 30px 0 20px; color: #2d2d2d; font-size: 18px; display: flex; align-items: center; gap: 10px;">
                        <i class="bi bi-shield-lock-fill"></i>
                        Información de Cuenta
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Contraseña <span class="required">*</span></label>
                            <div class="form-input-wrapper password-wrapper">
                                <i class="bi bi-lock input-icon"></i>
                                <input type="password" class="form-input" id="pass" name="pass"
                                    required minlength="8"
                                    pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':\\|,.<>\/?]).{8,}"
                                    title="La contraseña debe tener al menos 8 caracteres, incluyendo al menos una letra mayúscula y un símbolo.">
                                <button type="button" class="toggle-password" onclick="togglePassword()">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Rol de Usuario <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-person-badge input-icon"></i>
                                <select class="form-select" id="rol" name="rol" required>
                                    <option value="">Seleccione un rol</option>
                                    {{-- Sensei no puede crear administradores --}}
                                    @if(Auth::user()->rol === 'admin')
                                    <option value="admin"   {{ old('rol') == 'admin'   ? 'selected' : '' }}>Administrador</option>
                                    @endif
                                    <option value="sensei"  {{ old('rol') == 'sensei'  ? 'selected' : '' }}>Sensei</option>
                                    <option value="tutor"   {{ old('rol') == 'tutor'   ? 'selected' : '' }}>Tutor</option>
                                    <option value="alumno"  {{ old('rol') == 'alumno'  ? 'selected' : '' }}>Alumno</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('registroForm').reset();">
                            <i class="bi bi-x-lg"></i> Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Registrar Usuario
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── Tabla de usuarios ──────────────────────────────────────── --}}
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">
                        <i class="bi bi-table"></i>
                        Usuarios Registrados ({{ count($usuarios) }})
                    </h2>

                    <div class="table-actions" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;width:100%;">
                        <form method="GET" action="{{ route('usuarios.index') }}" id="filtrosForm"
                              style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;width:100%;">
                            <div class="search-box" style="flex:1;min-width:180px;">
                                <i class="bi bi-search search-icon"></i>
                                <input type="text" class="search-input" id="buscar" name="buscar"
                                       placeholder="Buscar nombre, correo..."
                                       value="{{ $filtros['buscar'] ?? '' }}">
                            </div>
                            <select name="rol" class="form-select" style="width:140px;"
                                    onchange="document.getElementById('filtrosForm').submit()">
                                <option value="">Todos los roles</option>
                                {{-- Sensei no puede filtrar por admin --}}
                                @if(Auth::user()->rol === 'admin')
                                <option value="admin"  {{ ($filtros['rol'] ?? '') === 'admin'  ? 'selected' : '' }}>Administrador</option>
                                @endif
                                <option value="sensei" {{ ($filtros['rol'] ?? '') === 'sensei' ? 'selected' : '' }}>Sensei</option>
                                <option value="tutor"  {{ ($filtros['rol'] ?? '') === 'tutor'  ? 'selected' : '' }}>Tutor</option>
                                <option value="alumno" {{ ($filtros['rol'] ?? '') === 'alumno' ? 'selected' : '' }}>Alumno</option>
                            </select>
                            <select name="estado" class="form-select" style="width:130px;"
                                    onchange="document.getElementById('filtrosForm').submit()">
                                <option value="">Todos</option>
                                <option value="1" {{ ($filtros['estado'] ?? '') === '1' ? 'selected' : '' }}>Activos</option>
                                <option value="0" {{ ($filtros['estado'] ?? '') === '0' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                            <button type="submit" class="btn btn-primary" style="white-space:nowrap;">
                                <i class="bi bi-funnel-fill"></i> Filtrar
                            </button>
                            @php
                                $hayFiltros = ($filtros['buscar'] ?? '') !== ''
                                           || ($filtros['rol']    ?? '') !== ''
                                           || ($filtros['estado'] ?? '') !== '';
                            @endphp
                            @if($hayFiltros)
                                <a href="{{ route('usuarios.index') }}" class="btn btn-secondary" style="white-space:nowrap;">
                                    <i class="bi bi-x-lg"></i> Limpiar
                                </a>
                            @endif
                        </form>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Teléfono</th>
                                <th>Fecha Nac.</th>
                                <th>Fecha Reg.</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $SUPERUSUARIO_CORREO = 'nailea8040@gmail.com';
                            @endphp
                            @foreach($usuarios as $usuario)
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">{{ strtoupper(substr($usuario->nombre, 0, 1) . substr($usuario->apaterno, 0, 1)) }}</div>
                                            <div class="user-details">
                                                <span class="user-name">{{ $usuario->nombre }} {{ $usuario->apaterno }} {{ $usuario->amaterno }}</span>
                                                <span class="user-email">{{ $usuario->correo }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        @php
                                            $badgeClass = match($usuario->rol) {
                                                'admin'  => 'badge-admin',
                                                'sensei' => 'badge-sensei',
                                                'tutor'  => 'badge-tutor',
                                                default  => 'badge-alumno',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($usuario->rol) }}</span>
                                    </td>

                                    <td>{{ $usuario->telefono }}</td>
                                    <td>{{ date('d/m/Y', strtotime($usuario->fecha_naci)) }}</td>
                                    <td>{{ date('d/m/Y', strtotime($usuario->fecha_registro)) }}</td>

                                    <td class="text-center">
                                        @php
                                            $esMiPropioUsuario  = Auth::id() === $usuario->id_usuario;
                                            $esSuperUsuario     = strtolower(trim($usuario->correo)) === $SUPERUSUARIO_CORREO;
                                            $authEsSuperUsuario = strtolower(trim(Auth::user()->correo)) === $SUPERUSUARIO_CORREO;
                                        @endphp

                                        @if($esMiPropioUsuario || $esSuperUsuario)
                                            {{-- Propio usuario o superusuario: toggle bloqueado --}}
                                            <label class="switch"
                                                   title="{{ $esSuperUsuario ? 'El superusuario no puede ser desactivado' : 'No puedes cambiar tu propio estado de acceso' }}"
                                                   style="cursor:not-allowed;opacity:0.5;">
                                                <input type="checkbox" name="activo"
                                                       {{ $usuario->estado == 1 ? 'checked' : '' }}
                                                       disabled>
                                                <span class="slider"></span>
                                            </label>
                                        @else
                                            <form id="toggleForm-{{ $usuario->id_usuario }}"
                                                  action="{{ route('usuarios.toggleActive', $usuario->id_usuario) }}"
                                                  method="POST" style="display:inline;">
                                                @csrf
                                                <label class="switch" title="{{ $usuario->estado == 1 ? 'Activo (Clic para desactivar)' : 'Inactivo (Clic para activar)' }}">
                                                    <input type="checkbox" name="activo"
                                                           {{ $usuario->estado == 1 ? 'checked' : '' }}
                                                           onchange="confirmarCambioEstado(event, {{ $usuario->id_usuario }}, '{{ $usuario->nombre }} {{ $usuario->apaterno }}', this.checked);">
                                                    <span class="slider"></span>
                                                </label>
                                            </form>
                                        @endif

                                        <span class="badge {{ $usuario->estado == 1 ? 'badge-active' : 'badge-inactive' }} mt-1 d-block">
                                            {{ $usuario->estado == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-buttons">
                                            {{-- Botón editar: superusuario solo lo edita él mismo; admin edita todos los demás --}}
                                            @if(!$esSuperUsuario || $authEsSuperUsuario)
                                            @if(Auth::user()->rol === 'admin' || $authEsSuperUsuario || $usuario->rol !== 'admin')
                                            <button type="button" class="action-btn btn-edit edit-user-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUserModal"
                                                data-id="{{ $usuario->id_usuario }}"
                                                data-nombre="{{ $usuario->nombre }}"
                                                data-apaterno="{{ $usuario->apaterno }}"
                                                data-amaterno="{{ $usuario->amaterno }}"
                                                data-fecha_naci="{{ $usuario->fecha_naci }}"
                                                data-telefono="{{ $usuario->telefono }}"
                                                data-correo="{{ $usuario->correo }}"
                                                data-rol="{{ $usuario->rol }}"
                                                title="Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </button>
                                            @endif
                                            @endif

                                            {{-- Botón eliminar: solo admin puede eliminar, nunca al superusuario --}}
                                            @if(Auth::user()->rol === 'admin')
                                                @if($esSuperUsuario)
                                                    {{-- Superusuario: botón bloqueado siempre --}}
                                                    <button type="button" class="action-btn btn-disabled"
                                                            title="El superusuario no puede ser eliminado" disabled
                                                            style="cursor:not-allowed;opacity:0.6;background-color:#f8d7da;">
                                                        <i class="bi bi-shield-lock-fill"></i>
                                                    </button>
                                                @elseif($usuario->rol !== 'admin')
                                                    <form action="{{ route('usuarios.destroy', $usuario->id_usuario) }}"
                                                          method="POST" style="display:inline;"
                                                          onsubmit="return confirmarEliminacion(event);">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="action-btn btn-delete" title="Eliminar">
                                                            <i class="bi bi-trash-fill"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <button type="button" class="action-btn btn-disabled"
                                                            title="No se puede eliminar este usuario" disabled
                                                            style="cursor:not-allowed;opacity:0.6;background-color:#f8d7da;">
                                                        <i class="bi bi-x-octagon-fill"></i>
                                                    </button>
                                                @endif
                                            @endif
                                            {{-- Sensei: sin botón de eliminar --}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <footer class="footer">
            <p>© 2025 Sistema de Gestión del Dojo</p>
        </footer>
    </div>

    {{-- ── Modal Editar Usuario ────────────────────────────────────────────── --}}
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_id_usuario" name="id_usuario">

                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre(s)</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_apaterno" class="form-label">Apellido Paterno</label>
                            <input type="text" class="form-control" id="edit_apaterno" name="apaterno" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_amaterno" class="form-label">Apellido Materno</label>
                            <input type="text" class="form-control" id="edit_amaterno" name="amaterno" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_fecha_naci" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="edit_fecha_naci" name="fecha_naci" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="edit_telefono" name="telefono" maxlength="20" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_correo" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="edit_correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_pass" class="form-label">Contraseña (dejar vacío para no cambiar)</label>
                            <input type="password" class="form-control" id="edit_pass" name="pass" minlength="6" placeholder="******">
                        </div>
                        <div class="mb-3">
                            <label for="edit_rol" class="form-label">Rol</label>
                            <select id="edit_rol" name="rol" class="form-select" required>
                                {{-- Sensei no puede asignar rol admin --}}
                                @if(Auth::user()->rol === 'admin')
                                <option value="admin">Administrador</option>
                                @endif
                                <option value="sensei">Sensei</option>
                                <option value="tutor">Tutor</option>
                                <option value="alumno">Alumno</option>
                            </select>
                            {{-- Aviso visual cuando se edita al superusuario (el JS lo muestra/oculta) --}}
                            <small id="edit_rol_lock_msg" class="text-danger d-none">
                                <i class="bi bi-shield-lock-fill"></i>
                                El rol del superusuario no puede modificarse.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex justify-content-between w-100 gap-2">
                            <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-primary w-50">Guardar Cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Alertas de sesión (solo para acciones que NO usan AJAX, ej: eliminar, toggleActive) ──
        @if(session('sessionInsertado'))
            Swal.fire({
                icon:  '{{ session('sessionInsertado') == 'true' ? 'success' : 'error' }}',
                title: '{{ session('mensaje') }}',
                showConfirmButton: false,
                timer: 2000
            });
            const alertaTemp = document.getElementById('alerta-temp');
            if (alertaTemp) alertaTemp.style.display = 'none';
        @endif

        // ── Confirmar eliminación ────────────────────────────────────────────
        function confirmarEliminacion(event) {
            event.preventDefault();
            const form = event.target;
            Swal.fire({
                title: '¿Estás seguro de eliminar?',
                text: '¡No podrás recuperar este registro! Esta es una eliminación permanente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, ¡Eliminar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
            return false;
        }

        // ── Confirmar cambio de estado ───────────────────────────────────────
        function confirmarCambioEstado(event, userId, userName, isChecked) {
            event.preventDefault();
            const form   = document.getElementById(`toggleForm-${userId}`);
            const accion = isChecked ? 'ACTIVAR' : 'DESACTIVAR';
            const mensaje = isChecked
                ? `¿Estás seguro de **activar** al usuario ${userName}? El usuario podrá ingresar al sistema.`
                : `¿Estás seguro de **desactivar** al usuario ${userName}? El usuario NO podrá ingresar al sistema.`;

            Swal.fire({
                title: `Confirmar ${accion} Usuario`,
                html: mensaje,
                icon: isChecked ? 'question' : 'warning',
                showCancelButton: true,
                confirmButtonColor: isChecked ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Sí, ${accion}`,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                } else {
                    event.target.checked = !isChecked;
                }
            });
        }

        // ── Mostrar/ocultar contraseña (form registro) ───────────────────────
        function togglePassword() {
            const passwordInput = document.getElementById('pass');
            const toggleIcon    = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        $(document).ready(function () {

            // ── Poblar modal al hacer clic en "Editar" ───────────────────────
            $('.edit-user-btn').on('click', function () {
                const userId    = $(this).data('id');
                const nombre    = $(this).data('nombre');
                const apaterno  = $(this).data('apaterno');
                const amaterno  = $(this).data('amaterno');
                const fechaNaci = $(this).data('fecha_naci');
                const telefono  = $(this).data('telefono');
                const correo    = $(this).data('correo');
                const rol       = $(this).data('rol');

                const SUPERUSUARIO_CORREO = 'nailea8040@gmail.com';
                const esSuperUsuario = correo.trim().toLowerCase() === SUPERUSUARIO_CORREO;

                $('#edit_id_usuario').val(userId);
                $('#edit_nombre').val(nombre);
                $('#edit_apaterno').val(apaterno);
                $('#edit_amaterno').val(amaterno);
                $('#edit_fecha_naci').val(fechaNaci);
                $('#edit_telefono').val(telefono);
                $('#edit_correo').val(correo);
                $('#edit_rol').val(rol);
                $('#editForm').data('userId', userId);
                $('#edit_pass').val('');
                $('#editForm .edit-error-msg').remove();

                // Bloquear/desbloquear el select de rol según si es superusuario
                if (esSuperUsuario) {
                    $('#edit_rol').prop('disabled', true);
                    $('#edit_rol_lock_msg').removeClass('d-none');
                } else {
                    $('#edit_rol').prop('disabled', false);
                    $('#edit_rol_lock_msg').addClass('d-none');
                }
            });

            // ── Envío AJAX del modal editar ──────────────────────────────────
            $('#editForm').on('submit', function (e) {
                e.preventDefault();

                const userId  = $(this).data('userId');
                const url     = `/usuarios/${userId}`;
                const formData = new FormData(this);
                // Laravel espera _method=PUT para rutas update
                formData.set('_method', 'PUT');

                // Si el select de rol está deshabilitado (superusuario),
                // el navegador no lo incluye en FormData — lo agregamos manualmente.
                if ($('#edit_rol').prop('disabled')) {
                    formData.set('rol', $('#edit_rol').val());
                }

                // Deshabilitar botón mientras procesa
                const btnGuardar = $(this).find('button[type="submit"]');
                btnGuardar.prop('disabled', true).text('Guardando...');

                fetch(url, {
                    method: 'POST',       // usamos POST + _method=PUT (Laravel)
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(async res => {
                    const data = await res.json();

                    if (data.ok) {
                        // ── Éxito: cerrar modal, actualizar fila, SweetAlert ──
                        bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();

                        const u = data.usuario;

                        // Actualizar la fila en la tabla sin recargar
                        const btn = $(`.edit-user-btn[data-id="${userId}"]`);
                        btn.data('nombre',    u.nombre);
                        btn.data('apaterno',  u.apaterno);
                        btn.data('amaterno',  u.amaterno);
                        btn.data('fecha_naci', u.fecha_naci);
                        btn.data('telefono',  u.telefono);
                        btn.data('correo',    u.correo);
                        btn.data('rol',       u.rol);

                        // Actualizar texto visible en la tabla
                        const fila = btn.closest('tr');
                        fila.find('.user-name').text(`${u.nombre} ${u.apaterno} ${u.amaterno}`);
                        fila.find('.user-email').text(u.correo);

                        // Actualizar badge de rol
                        const badgeClases = {admin:'badge-admin', sensei:'badge-sensei', tutor:'badge-tutor', alumno:'badge-alumno'};
                        const badgeEl = fila.find('.badge').first();
                        badgeEl.attr('class', `badge ${badgeClases[u.rol] || 'badge-alumno'}`);
                        badgeEl.text(u.rol.charAt(0).toUpperCase() + u.rol.slice(1));

                        Swal.fire({
                            icon: 'success',
                            title: data.mensaje,
                            showConfirmButton: false,
                            timer: 2000
                        });

                    } else {
                        // ── Error: SweetAlert, modal sigue abierto ────────────
                        Swal.fire({
                            icon:  'error',
                            title: 'No se pudo guardar',
                            text:   data.mensaje,
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon:  'error',
                        title: 'Error de red',
                        text:  'No se pudo conectar con el servidor. Intenta de nuevo.',
                    });
                })
                .finally(() => {
                    btnGuardar.prop('disabled', false).text('Guardar Cambios');
                });
            });

            // ── Envío AJAX del formulario de registro ────────────────────────
            $('#registroForm').on('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);
                const btnReg   = $(this).find('button[type="submit"]');
                btnReg.prop('disabled', true);

                fetch('{{ route('usuarios.store') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(async res => {
                    const data = await res.json();

                    if (data.ok) {
                        Swal.fire({
                            icon: 'success',
                            title: data.mensaje,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            // Recargar para mostrar el nuevo usuario en la tabla
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon:  'error',
                            title: 'Error al registrar',
                            text:   data.mensaje,
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon:  'error',
                        title: 'Error de red',
                        text:  'No se pudo conectar con el servidor.',
                    });
                })
                .finally(() => {
                    btnReg.prop('disabled', false);
                });
            });

            // ── Buscar con Enter ─────────────────────────────────────────────
            $('#buscar').on('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    $('#filtrosForm').submit();
                }
            });
        });
    </script>
</body>
</html>