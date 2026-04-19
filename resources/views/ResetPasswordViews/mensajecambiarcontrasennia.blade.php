<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperacion de Contraseña</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            padding: 20px;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -30px -30px 20px -30px;
        }
        .button {
            background-color: #dc3545;
            color: #ffffff;
            padding: 14px 25px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .button:hover {
            background-color: #c82333;
        }
        .link-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2 style="margin: 0;">Academia Karate-Do</h2>
        <p style="margin: 5px 0 0 0;">Recuperación de Contraseña</p>
    </div>

    <p>Estimado/a <strong>{{ $nombreCompleto }}</strong>,</p>
    
    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Para proceder con el cambio de contraseña, haz clic en el siguiente botón:</p>

    <div style="text-align: center;">
        <a href="{{ env('APP_URL') }}/password/reset/{{ $token }}" class="button">
            Restablecer Contraseña
        </a>
    </div>

    <p style="margin-top: 30px;">Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
    
    <div class="link-box">
        <a href="{{ env('APP_URL') }}/password/reset/{{ $token }}" style="color: #007bff;">
            {{ env('APP_URL') }}/password/reset/{{ $token }}
        </a>
    </div>

    <div style="background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 4px; margin: 20px 0;">
        <strong>⚠️ Importante:</strong>
        <ul style="margin: 10px 0; padding-left: 20px;">
            <li>Este enlace expirara en <strong>10 minutos</strong></li>
            <li>Si no solicitaste este cambio, ignora este correo</li>
            <li>Tu contraseña actual seguirá siendo válida</li>
        </ul>
    </div>

    <div class="footer">
        <p>Saludos cordiales,<br><strong>El equipo de Academia Karate-Do</strong></p>
        <p style="margin-top: 15px; font-size: 11px;">
            Este es un correo automático, por favor no respondas a este mensaje.
        </p>
    </div>
</div>

</body>
</html>