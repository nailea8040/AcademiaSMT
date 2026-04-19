<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{ asset('/css/estiloindex.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Botón outline — sin rellenar, con borde */
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

        /* Separador visual entre los dos botones outline */
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
            <input type="password" name="contra" placeholder="Contraseña" required>

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