<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{ asset('/css/estiloindex.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Estilos para el contenedor del input y el ojo */
        .password-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 15px; /* Ajusta según tu diseño */
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 40px; /* Espacio para que el texto no se encime con el ojo */
            box-sizing: border-box;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 1.2rem;
            z-index: 10;
        }

        /* Tus estilos originales */
        .btn-outline-login {
            display: block;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1.5px solid #c62828;
            background: transparent;
            color: #c62828;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s, color .2s;
            font-family: inherit;
            margin-top: 6px;
        }
        .btn-outline-login:hover {
            background: rgba(198, 40, 40, 0.07);
        }

        .login-divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 4px 0;
            color: #aaa;
            font-size: 12px;
        }
        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ddd;
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <form class="login-form" action="{{ route('login.attempt') }}" method="POST">
            @csrf
            <h2>Iniciar sesión</h2>

            <input type="email" name="correo" placeholder="Correo" required>
            
            <div class="password-wrapper">
                <input type="password" name="contra" id="passwordInput" placeholder="Contraseña" required>
                <i class="bi bi-eye toggle-password" id="toggleIcon"></i>
            </div>

            <button type="submit">Ingresar</button>

            <a href="{{ route('password.request') }}" class="forgot-password-link">
                ¿Olvidaste tu contraseña?
            </a>

            <div class="login-divider">o</div>

            <a href="{{ route('registro.create') }}" class="btn-outline-login">
                Crear cuenta nueva
            </a>
        </form>
    </div>

    <script>
    // --- Lógica para ver/ocultar contraseña ---
    const passwordInput = document.getElementById('passwordInput');
    const toggleIcon = document.getElementById('toggleIcon');

    toggleIcon.addEventListener('click', function() {
        // Cambiar el tipo de input
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Cambiar el icono (ojo abierto / ojo tachado)
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });

    // --- Tus scripts de SweetAlert ---
    let errorTitle = null;
    let errorMessage = null;

    @if ($errors->has('cuenta_inactiva'))
        errorTitle = 'Acceso Denegado';
        errorMessage = '{{ $errors->first('cuenta_inactiva') }}';
    @elseif ($errors->has('login_fallido'))
        errorTitle = 'Error de Credenciales';
        errorMessage = '{{ $errors->first('login_fallido') }}';
    @elseif ($errors->any())
        errorTitle = 'Faltan datos';
        errorMessage = 'Por favor, corrige el error: {{ $errors->all()[0] }}';
    @endif

    if (errorMessage) {
        Swal.fire({
            title: errorTitle,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#e53935'
        });
    }

    @if (session('status'))
        Swal.fire({
            title: 'Éxito',
            text: '{{ session('status') }}',
            icon: 'success',
            confirmButtonText: 'Continuar',
            confirmButtonColor: '#4CAF50'
        });
    @endif
    </script>

</body>
</html>