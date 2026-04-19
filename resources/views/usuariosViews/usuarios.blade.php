<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Usuarios - Sistema Dojo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}"> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                                <input type="text" class="form-input" id="telefono" name="telefono" placeholder="10 dígitos" required 
                                minlength="10" 
                                maxlength="10" 
                                pattern="[0-9]{10}">
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
                                required 
                                minlength="8" 
                                pattern="(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?]).{8,}"
                                title="La contraseña debe tener al menos 8 caracteres, incluyendo al menos una letra mayúscula y un símbolo (o carácter especial)."
                                >
                                
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
                                    <option value="admin" {{ old('rol') == 'admin' ? 'selected' : '' }}>Administrador</option>
                                    <option value="sensei" {{ old('rol') == 'sensei' ? 'selected' : '' }}>Sensei</option>
                                    <option value="tutor" {{ old('rol') == 'tutor' ? 'selected' : '' }}>Tutor</option>
                                    <option value="alumno" {{ old('rol') == 'alumno' ? 'selected' : '' }}>Alumno</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid full-width">
                        <div class="form-group">
                            <label class="form-label">Fecha de Registro <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-calendar-check input-icon"></i>
                                <input type="date" class="form-input" id="fecha_registro" name="fecha_registro" required value="{{ date('Y-m-d') }}" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('registroForm').reset();">
                            <i class="bi bi-x-lg"></i>
                            Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i>
                            Registrar Usuario
                        </button>
                    </div>
                </form>
            </div>
            
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
                                <input type="text" class="search-input" name="buscar"
                                       placeholder="Buscar nombre, correo..."
                                       value="{{ $filtros['buscar'] ?? '' }}">
                            </div>
                            <select name="rol" class="form-select" style="width:140px;"
                                    onchange="document.getElementById('filtrosForm').submit()">
                                <option value="">Todos los roles</option>
                                <option value="admin"   {{ ($filtros['rol'] ?? '') === 'admin'   ? 'selected' : '' }}>Administrador</option>
                                <option value="sensei"  {{ ($filtros['rol'] ?? '') === 'sensei'  ? 'selected' : '' }}>Sensei</option>
                                <option value="tutor"   {{ ($filtros['rol'] ?? '') === 'tutor'   ? 'selected' : '' }}>Tutor</option>
                                <option value="alumno"  {{ ($filtros['rol'] ?? '') === 'alumno'  ? 'selected' : '' }}>Alumno</option>
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
                            @if(!empty(array_filter($filtros ?? [])))
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
                                            $badgeClass = '';
                                            switch($usuario->rol) {
                                                case 'admin': $badgeClass = 'badge-admin'; break;
                                                case 'sensei': $badgeClass = 'badge-sensei'; break;
                                                case 'tutor': $badgeClass = 'badge-tutor'; break;
                                                case 'alumno': default: $badgeClass = 'badge-alumno'; break;
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ ucfirst($usuario->rol) }}</span>
                                    </td>
                                    
                                    <td>{{ $usuario->telefono }}</td>
                                    <td>{{ date('d/m/Y', strtotime($usuario->fecha_naci)) }}</td>
                                    <td>{{ date('d/m/Y', strtotime($usuario->fecha_registro)) }}</td>
                                    
                                    <td class="text-center">
                                        <form id="toggleForm-{{ $usuario->id_usuario }}" action="{{ route('usuarios.toggleActive', $usuario->id_usuario) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <label class="switch" title="{{ $usuario->estado == 1 ? 'Activo (Clic para desactivar)' : 'Inactivo (Clic para activar)' }}">
                                                <input type="checkbox" name="activo" {{ $usuario->estado == 1 ? 'checked' : '' }} onchange="confirmarCambioEstado(event, {{ $usuario->id_usuario }}, '{{ $usuario->nombre }} {{ $usuario->apaterno }}', this.checked);">
                                                <span class="slider"></span>
                                            </label>
                                        </form>
                                        <span class="badge {{ $usuario->estado == 1 ? 'badge-active' : 'badge-inactive' }} mt-1 d-block">{{ $usuario->estado == 1 ? 'Activo' : 'Inactivo' }}</span>
                                    </td>
                                   {{-- usuariosViews/usuarios.blade.php (dentro del @foreach) --}}

<td class="text-center">
    <div class="action-buttons">
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
        
        @if ($usuario->rol !== 'admin')
            <form action="{{ route('usuarios.destroy', $usuario->id_usuario) }}" method="POST" style="display:inline;" onsubmit="return confirmarEliminacion(event);">
                @csrf
                @method('DELETE') 
                
                <button type="submit" class="action-btn btn-delete" title="Eliminar">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </form>
        @else
            <button type="button" class="action-btn btn-disabled" title="No se puede eliminar este usuario" disabled style="cursor: not-allowed; opacity: 0.6; background-color: #f8d7da;">
                <i class="bi bi-x-octagon-fill"></i>
            </button>
        @endif
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
    
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST" action="">
                    @csrf
                    @method('PUT') 
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
                            <label for="edit_pass" class="form-label">Contraseña (Dejar vacío para no cambiar)</label>
                            <input type="password" class="form-control" id="edit_pass" name="pass" minlength="6" placeholder="******">
                        </div>
                        <div class="mb-3">
                            <label for="edit_rol" class="form-label">Rol</label>
                            <select id="edit_rol" name="rol" class="form-select" required>
                                <option value="admin">Administrador</option>
                                <option value="sensei">Sensei</option>
                                <option value="tutor">Tutor</option>
                                <option value="alumno">Alumno</option>
                            </select>
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
        // ... (Tu código JavaScript para SweetAlert, eliminación y búsqueda se mantiene) ...

        @if(session('sessionInsertado'))
            const icono = '{{ session('sessionInsertado') == 'true' ? 'success' : 'error' }}';
            const titulo = '{{ session('mensaje') }}';
            Swal.fire({
                icon: icono,
                title: titulo,
                showConfirmButton: false,
                timer: 2000
            });

            const alertaTemp = document.getElementById('alerta-temp');
            if (alertaTemp) {
                alertaTemp.style.display = 'none';
            }
        @endif
        
        function confirmarEliminacion(event) {
            event.preventDefault();
            const form = event.target;
            
            Swal.fire({
                title: '¿Estás seguro de eliminar?',
                text: "¡No podrás recuperar este registro! Esta es una eliminación permanente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, ¡Eliminar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }

        function confirmarCambioEstado(event, userId, userName, isChecked) {
            event.preventDefault(); 
            const form = document.getElementById(`toggleForm-${userId}`);
            
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
        
        function togglePassword() {
            const passwordInput = document.getElementById('pass'); 
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        }

        $(document).ready(function() {
            $('.edit-user-btn').on('click', function() {
                const userId = $(this).data('id');
                const nombre = $(this).data('nombre');
                const apaterno = $(this).data('apaterno');
                const amaterno = $(this).data('amaterno');
                const fechaNaci = $(this).data('fecha_naci');
                const telefono = $(this).data('telefono');
                const correo = $(this).data('correo');
                const rol = $(this).data('rol');

                $('#edit_id_usuario').val(userId);
                $('#edit_nombre').val(nombre);
                $('#edit_apaterno').val(apaterno);
                $('#edit_amaterno').val(amaterno);
                $('#edit_fecha_naci').val(fechaNaci);
                $('#edit_telefono').val(telefono);
                $('#edit_correo').val(correo);
                $('#edit_rol').val(rol);

                const updateUrl = `/usuarios/${userId}`; 
                $('#editForm').attr('action', updateUrl);
                
                $('#edit_pass').val('');
            });
            
            $('#searchInput').on('keyup', function() {
                const searchText = $(this).val().toLowerCase();
                $('#usersTable tbody tr').each(function() {
                    const rowText = $(this).text().toLowerCase();
                    if (rowText.includes(searchText)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
</body>
</html>