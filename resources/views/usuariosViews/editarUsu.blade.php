<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Editar Usuario - {{ $usuario->nombre }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/estiloU.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #bachillerSection {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 16px;
            margin-top: 8px;
            background: #f8f9fa;
        }
        .bachiller-toggle-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: 600;
            color: #333;
        }
        .bachiller-toggle-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #c62828;
            cursor: pointer;
        }
    </style>
</head>
<body>
    @include('includes.menu')

    <div class="main-content">
        <header>
            <h1>Editar Usuario: {{ $usuario->nombre }} {{ $usuario->apaterno }}</h1>
        </header>

        <div class="content p-4">
            <div class="card p-4 mx-auto" style="max-width: 650px;">
                <h2 class="text-center mb-4" style="color: #111;">Formulario de Edición</h2>

                <form id="edicionForm"
                      action="{{ route('usuarios.update', $usuario->id_usuario) }}"
                      method="POST">
                    @csrf
                    @method('PUT')

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ── Datos personales ── --}}
                    <label for="nombre" class="form-label mt-2">Nombre(s)</label>
                    <input type="text" id="nombre" name="nombre" class="form-control"
                           value="{{ old('nombre', $usuario->nombre) }}" required>

                    <label for="apaterno" class="form-label mt-2">Apellido Paterno</label>
                    <input type="text" id="apaterno" name="apaterno" class="form-control"
                           value="{{ old('apaterno', $usuario->apaterno) }}" required>

                    <label for="amaterno" class="form-label mt-2">Apellido Materno</label>
                    <input type="text" id="amaterno" name="amaterno" class="form-control"
                           value="{{ old('amaterno', $usuario->amaterno) }}" required>

                    <label for="correo" class="form-label mt-2">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" class="form-control"
                           value="{{ old('correo', $usuario->correo) }}" required>

                    <label for="telefono" class="form-label mt-2">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" class="form-control" maxlength="20"
                           value="{{ old('telefono', $usuario->telefono) }}" required>

                    {{-- ── Rol ── --}}
                    @php
                        $esSuperUsuario = strtolower(trim($usuario->correo)) === strtolower(trim(config('app.super_admin_email', '')));
                    @endphp
                    <label for="rol" class="form-label mt-2">Rol</label>
                    <select id="rol" name="rol" class="form-select" required
                            onchange="mostrarBachiller()"
                            {{ $esSuperUsuario ? 'disabled' : '' }}>
                        <option value="">Selecciona tipo de usuario</option>
                        @php $currentRol = old('rol', $usuario->rol); @endphp
                        <option value="admin"  {{ $currentRol == 'admin'  ? 'selected' : '' }}>Administrador</option>
                        <option value="sensei" {{ $currentRol == 'sensei' ? 'selected' : '' }}>Sensei</option>
                        <option value="tutor"  {{ $currentRol == 'tutor'  ? 'selected' : '' }}>Tutor</option>
                        <option value="alumno" {{ $currentRol == 'alumno' ? 'selected' : '' }}>Alumno</option>
                    </select>
                    @if($esSuperUsuario)
                        <input type="hidden" name="rol" value="{{ $usuario->rol }}">
                        <small class="text-danger">
                            <i class="bi bi-shield-lock-fill"></i>
                            El rol del superusuario no puede modificarse.
                        </small>
                    @endif

                    <label for="fecha_naci" class="form-label mt-2">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_naci" name="fecha_naci" class="form-control"
                           value="{{ old('fecha_naci', $usuario->fecha_naci instanceof \Carbon\Carbon ? $usuario->fecha_naci->format('Y-m-d') : $usuario->fecha_naci) }}"
                           required>

                    <label for="pass" class="form-label mt-2">
                        Nueva Contraseña
                        <small class="text-muted">(dejar vacío para no cambiar)</small>
                    </label>
                    <input type="password" id="pass" name="pass" class="form-control"
                           placeholder="Mínimo 6 caracteres" minlength="6">

                    <label class="form-label mt-2">Fecha de Registro</label>
                    <input type="text" class="form-control"
                           value="{{ $usuario->fecha_registro }}" disabled>

                    {{-- ── Sección bachiller ── --}}
                    <div class="mt-3" id="bachillerWrapper"
                         style="display: {{ in_array(old('rol', $usuario->rol), ['admin','sensei','tutor','alumno']) ? 'block' : 'none' }}">

                        <label class="bachiller-toggle-label">
                            @php $tieneBachiller = !empty($usuario->numero_control); @endphp
                            <input type="checkbox"
                                   name="es_bachiller"
                                   id="esBachillerCheck"
                                   value="1"
                                   onchange="toggleBachiller(this.checked)"
                                   {{ old('es_bachiller', $tieneBachiller) ? 'checked' : '' }}>
                            ¿El usuario pertenece al bachiller?
                        </label>

                        <div id="bachillerSection"
                             style="display: {{ old('es_bachiller', $tieneBachiller) ? 'block' : 'none' }}">

                            <div class="row g-3 mt-1">
                                <div class="col-md-6">
                                    <label class="form-label">Número de Control</label>
                                    <input type="text" name="numero_control" class="form-control"
                                           placeholder="Ej: 12345678" maxlength="20"
                                           value="{{ old('numero_control', $usuario->numero_control) }}">
                                </div>

                                {{-- GRUPO — select fijo 1A-6B --}}
                                <div class="col-md-6">
                                    <label class="form-label">Grupo</label>
                                    <select name="grupo" class="form-select">
                                        <option value="">— Selecciona —</option>
                                        @foreach(['1A','1B','2A','2B','3A','3B','4A','4B','5A','5B','6A','6B'] as $g)
                                            <option value="{{ $g }}"
                                                {{ old('grupo', $usuario->grupo) == $g ? 'selected' : '' }}>
                                                {{ $g }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- ESPECIALIDAD — select fijo --}}
                                <div class="col-md-6">
                                    <label class="form-label">Especialidad</label>
                                    <select name="especialidad" class="form-select">
                                        <option value="">— Selecciona —</option>
                                        @foreach([
                                            'Análisis clínicos',
                                            'Programación',
                                            'Mecánica',
                                            'Logística',
                                            'Producción digital',
                                            'Ciberseguridad',
                                            'Soporte y mantenimiento',
                                        ] as $esp)
                                            <option value="{{ $esp }}"
                                                {{ old('especialidad', $usuario->especialidad) == $esp ? 'selected' : '' }}>
                                                {{ $esp }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- TURNO --}}
                                <div class="col-md-6">
                                    <label class="form-label">Turno</label>
                                    <select name="turno" class="form-select">
                                        <option value="">— Selecciona —</option>
                                        @foreach(['Matutino','Vespertino'] as $t)
                                            <option value="{{ $t }}"
                                                {{ old('turno', $usuario->turno) == $t ? 'selected' : '' }}>
                                                {{ $t }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>{{-- /bachillerSection --}}
                    </div>{{-- /bachillerWrapper --}}

                    {{-- ── Botones ── --}}
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="{{ route('usuarios.index') }}"
                           class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary"
                                style="background-color:#c62828; border-color:#c62828;">
                            Guardar Cambios
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <footer class="footer">
            <p>© 2025 Sistema de Gestión del Dojo</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar sección bachiller según el rol seleccionado
        // CORRECCIÓN: todos los roles pueden pertenecer al bachiller
        function mostrarBachiller() {
            const rol     = document.getElementById('rol').value;
            const wrapper = document.getElementById('bachillerWrapper');
            wrapper.style.display = rol ? 'block' : 'none';

            if (!rol) {
                document.getElementById('esBachillerCheck').checked = false;
                toggleBachiller(false);
            }
        }

        // Mostrar/ocultar campos bachiller
        function toggleBachiller(checked) {
            const section = document.getElementById('bachillerSection');
            section.style.display = checked ? 'block' : 'none';

            if (!checked) {
                // Limpiar número de control
                const nc = document.querySelector('[name="numero_control"]');
                if (nc) nc.value = '';
                // Limpiar selects
                ['grupo', 'especialidad', 'turno'].forEach(function(name) {
                    const el = document.querySelector('[name="' + name + '"]');
                    if (el) el.value = '';
                });
            }
        }
    </script>
    <script>
        @if(session('sessionInsertado') === 'false')
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ session('mensaje') }}',
            });
        @endif

        @if(session('sessionInsertado') === 'true')
            Swal.fire({
                icon: 'success',
                title: '¡Listo!',
                text: '{{ session('mensaje') }}',
            });
        @endif

        @if($errors->any())
            Swal.fire({
                icon: 'warning',
                title: 'Revisa el formulario',
                html: `<ul style="text-align:left">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>`,
            });
        @endif
    </script>
</body>
</html>