<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Mensaje de Contacto</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #e85654 0%, #d43f3d 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .body {
            padding: 30px;
        }
        .info-block {
            background: #f9f9f9;
            border-left: 4px solid #e85654;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .info-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .info-value {
            color: #666;
            margin-bottom: 10px;
            word-break: break-word;
        }
        .mensaje-box {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            line-height: 1.6;
            color: #333;
        }
        .footer {
            background: #f5f5f5;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 12px;
            border-top: 1px solid #ddd;
        }
        .btn-reply {
            display: inline-block;
            background: #e85654;
            color: white;
            padding: 12px 30px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 600;
        }
        .btn-reply:hover {
            background: #d43f3d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Nuevo Mensaje de Contacto</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Academia de Karate-do San Martín Texmelucan</p>
        </div>

        <div class="body">
            <p style="color: #333; margin-bottom: 20px;">
                ¡Hola! Has recibido un nuevo mensaje desde el formulario de contacto de tu sitio web.
            </p>

            <div class="info-block">
                <div class="info-label">👤 Nombre:</div>
                <div class="info-value">{{ $nombre }}</div>
            </div>

            <div class="info-block">
                <div class="info-label">📧 Correo Electrónico:</div>
                <div class="info-value">
                    <a href="mailto:{{ $correo }}" style="color: #e85654; text-decoration: none;">{{ $correo }}</a>
                </div>
            </div>

            <div class="info-block">
                <div class="info-label">📱 Teléfono:</div>
                <div class="info-value">{{ $telefono }}</div>
            </div>

            <div style="margin-top: 25px;">
                <div class="info-label" style="margin-bottom: 10px;">💬 Mensaje:</div>
                <div class="mensaje-box">
                    {{ nl2br($mensaje) }}
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #666; font-size: 14px; margin: 0 0 15px 0;">
                    Para responder a este mensaje, usa el botón de respuesta en tu cliente de correo o haz clic en el enlace de contacto.
                </p>
                <a href="mailto:{{ $correo }}" class="btn-reply">Responder Mensaje</a>
            </div>
        </div>

        <div class="footer">
            <p>Este es un mensaje automático del formulario de contacto de la Academia de Karate-do SMT.</p>
            <p>Por favor, no respondas a este correo. Si necesitas ayuda, contacta con el equipo administrativo.</p>
            <p style="margin-top: 15px; border-top: 1px solid #ddd; padding-top: 15px;">
                © 2025 Academia de Karate-do San Martín Texmelucan. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>
