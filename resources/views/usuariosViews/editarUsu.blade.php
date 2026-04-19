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
</head>
<body>
    @include('includes.menu') 

    <div class="main-content">
        <header>
            <h1>Editar Usuario: {{ $usuario->nombre }} {{ $usuario->apaterno }}</h1>
        </header>

        <div class="content p-4">
            
            <div class="card p-4 mx-auto" style="max-width: 600px;">
                <h2 class="text-center mb-4" style="color: #111;">Formulario de Edición</h2>

                <form id="edicionForm" action="{{ route('usuarios.update', $usuario->id_usuario) }}" method="POST">
                    @csrf
                    @method('PUT') 

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

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

                    <label for="tel" class="form-label mt-2">Teléfono</label>
                    <input type="text" id="tel" name="tel" class="form-control" maxlength="20"
                           value="{{ old('tel', $usuario->tel) }}" required>

                    <label for="rol" class="form-label mt-2">Rol</label>
                    <select id="rol" name="rol" class="form-select" required>
                        <option value="">Selecciona tipo de usuario</option>
                        
                        @php $currentRol = old('rol', $usuario->rol); @endphp
                        
                        <option value="administrador" {{ $currentRol == 'administrador' ? 'selected' : '' }}>Administrador</option>
                        <option value="sensei" {{ $currentRol == 'sensei' ? 'selected' : '' }}>Sensei</option>
                        <option value="tutor" {{ $currentRol == 'tutor' ? 'selected' : '' }}>Tutor</option>
                        <option value="alumno" {{ $currentRol == 'alumno' ? 'selected' : '' }}>Alumno</option>
                    </select>

                    <label for="fecha_naci" class="form-label mt-2">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_naci" name="fecha_naci" class="form-control"
                           value="{{ old('fecha_naci', $usuario->fecha_naci) }}" required>

                    <label for="pass" class="form-label mt-2">Nueva Contraseña (Dejar vacío para no cambiar)</label>
                    <input type="password" id="pass" name="pass" class="form-control" 
                           placeholder="Contraseña (mín. 6 caracteres)" minlength="6">

                    <label for="fecha_registro" class="form-label mt-2">Fecha de Registro</label>
                    <input type="date" id="fecha_registro" class="form-control" 
                           value="{{ $usuario->fecha_registro }}" disabled>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary" style="background-color: #e56717; border-color: #e56717;">Guardar Cambios</button>
                    </div>
                </form>

            </div>
        </div>

        <footer class="footer">
            <p>© 2025 Sistema de Gestión del Dojo</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>