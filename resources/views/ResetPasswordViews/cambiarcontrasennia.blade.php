<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nueva Contraseña - Sistema Dojo</title>
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
                <i class="bi bi-key-fill"></i>
            </div>
            <h1>Nueva Contraseña</h1>
            <p>Ingresa tu nueva contraseña para restablecer el acceso a tu cuenta</p>
        </div>

        <div class="recovery-body">
            <div class="info-box">
                <p>
                    <i class="bi bi-info-circle-fill" style="margin-right: 8px; color: #e53935;"></i>
                    La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.
                </p>
            </div>

            <form id="registroForm" method="POST" action="{{route('password.update')}}">
                @csrf
                @method('PUT')
                
                <input type="hidden" name="mytoken" value="{{$token}}">
                
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" 
                               class="form-input" 
                               id="contrasennia" 
                               name="contrasennia" 
                               placeholder="Mínimo 8 caracteres"
                               required>
                        <span class="toggle-password" onclick="togglePassword('contrasennia', this)">
                            <i class="bi bi-eye-fill"></i>
                        </span>
                        <span class="invalid-feedback" style="display: none;"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" 
                               class="form-input" 
                               id="recontrasennia" 
                               name="recontrasennia" 
                               placeholder="Repite tu contraseña"
                               required>
                        <span class="toggle-password" onclick="togglePassword('recontrasennia', this)">
                            <i class="bi bi-eye-fill"></i>
                        </span>
                        <span class="invalid-feedback" style="display: none;"></span>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-circle-fill"></i>
                    Cambiar Contraseña
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
        // Función para mostrar/ocultar contraseña
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                iconElement.classList.remove('bi-eye-fill');
                iconElement.classList.add('bi-eye-slash-fill');
            } else {
                input.type = 'password';
                iconElement.classList.remove('bi-eye-slash-fill');
                iconElement.classList.add('bi-eye-fill');
            }
        }

        $(document).ready(function () {
            // Regex para validación de contraseña
            const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
            
            // SweetAlert Toast
            @if (session('sessionCambiarContrasennia') == 'false')
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

            // Métodos personalizados de validación
            $.validator.addMethod(
                "regexContrasennia",
                function(value, element) {
                    return this.optional(element) || regex.test(value);
                },
                "La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial."
            );

            $.validator.addMethod(
                "compararContrasennias",
                function(value, element) {
                    return value === $("#contrasennia").val();
                },
                "Las contraseñas no coinciden."
            );

            // Configuración de validación
            $.validator.setDefaults({
                submitHandler: function () {
                    $('#preloader').css('display', 'flex');
                    $('#registroForm')[0].submit();
                }
            });

            $('#registroForm').validate({
                rules: {
                    contrasennia: {
                        required: true,
                        minlength: 8,
                        regexContrasennia: true
                    },
                    recontrasennia: {
                        required: true,
                        minlength: 8,
                        compararContrasennias: true
                    }
                },
                messages: {
                    contrasennia: {
                        required: "Ingresa tu nueva contraseña",
                        minlength: "La contraseña debe tener al menos 8 caracteres"
                    },
                    recontrasennia: {
                        required: "Confirma tu contraseña",
                        minlength: "La contraseña debe tener al menos 8 caracteres"
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

    <style>
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .toggle-password:hover {
            color: #e53935;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            padding-right: 45px !important;
        }

        /* Ajustes para hacer el contenedor más compacto */
        .recovery-container {
            max-height: 95vh;
            overflow-y: auto;
            margin: 20px auto;
        }

        .recovery-header {
            padding: 25px 20px 20px !important;
        }

        .recovery-header h1 {
            font-size: 1.6rem !important;
            margin-bottom: 8px !important;
        }

        .recovery-header p {
            font-size: 0.9rem !important;
        }

        .icon-container {
            width: 70px !important;
            height: 70px !important;
            margin-bottom: 15px !important;
        }

        .icon-container i {
            font-size: 2rem !important;
        }

        .recovery-body {
            padding: 20px !important;
        }

        .info-box {
            padding: 12px 15px !important;
            margin-bottom: 20px !important;
            font-size: 0.85rem !important;
        }

        .form-group {
            margin-bottom: 18px !important;
        }

        .form-label {
            font-size: 0.9rem !important;
            margin-bottom: 6px !important;
        }

        .btn-submit {
            margin-top: 10px !important;
            padding: 12px !important;
        }

        .back-link {
            margin-top: 15px !important;
        }

        /* Barra de scroll personalizada */
        .recovery-container::-webkit-scrollbar {
            width: 8px;
        }

        .recovery-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .recovery-container::-webkit-scrollbar-thumb {
            background: rgba(229, 57, 53, 0.5);
            border-radius: 10px;
        }

        .recovery-container::-webkit-scrollbar-thumb:hover {
            background: rgba(229, 57, 53, 0.8);
        }
    </style>
</body>
</html>