<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar Contraseña - Sistema Dojo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
</head>
<body class="page-recovery">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="recovery-container">
        <div class="recovery-header">
            <div class="icon-container">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h1>Recuperar Contraseña</h1>
            <p>No te preocupes, te ayudaremos a recuperar el acceso a tu cuenta</p>
        </div>

        <div class="recovery-body">
            <div class="info-box">
                <p>
                    <i class="bi bi-info-circle-fill" style="margin-right: 8px; color: #e53935;"></i>
                    Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                </p>
            </div>

            <form id="registroForm" action="{{ route('password.email') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope-fill input-icon"></i>
                        <input type="email" 
                               class="form-input" 
                               id="correo" 
                               name="correo" 
                               placeholder="tu@ejemplo.com"
                               required>
                        <span class="invalid-feedback" style="display: none;"></span>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-send-fill"></i>
                    Enviar Enlace de Recuperación
                </button>
            </form>

            <div class="back-link">
                <a href="{{ route('login') }}">
                    <i class="bi bi-arrow-left"></i>
                    Volver a Iniciar Sesión
                </a>
            </div>
        </div>
    </div>

    <div id="preloader">
        <div class="spinner"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

    <script>
        $(document).ready(function () {
            // SweetAlert Toast (Lógica sin cambios)
            @if (session('sessionRecuperarContrasennia') == 'true')
                Swal.fire({
                    icon: 'success',
                    title: '{{ session('mensaje') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            @elseif (session('sessionRecuperarContrasennia') == 'false')
                Swal.fire({
                    icon: 'error',
                    title: '{{ session('mensaje') }}',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            @endif

            // Validación del formulario (Lógica sin cambios)
            $.validator.setDefaults({
                submitHandler: function () {
                    $('#preloader').css('display', 'flex');
                    $('#registroForm')[0].submit();
                }
            });

            $('#registroForm').validate({
                rules: {
                    correo: {
                        required: true,
                        email: true
                    }
                },
                messages: {
                    correo: {
                        required: "Ingresa tu correo electrónico",
                        email: "Ingresa un correo electrónico válido"
                    }
                },
                errorElement: 'span',
                errorPlacement: function (error, element) {
                    error.addClass('invalid-feedback');
                    element.closest('.input-wrapper').append(error);
                },
                highlight: function (element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function (element) {
                    $(element).removeClass('is-invalid');
                }
            });
        });
    </script>
</body>
</html>