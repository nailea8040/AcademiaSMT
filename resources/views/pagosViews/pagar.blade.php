{{--
    resources/views/pagosViews/pagar.blade.php
    Vista dedicada al formulario de pago embebido con Payment Brick de MercadoPago.
    El alumno llega aquí después de que admin/sensei registró el pago con estado 'Pendiente'.
    URL: /pagos/{id_pago}/pagar
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pagar - Academia Karate-Do SMT</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- SDK de MercadoPago v2 — OBLIGATORIO para Payment Brick --}}
    <script src="https://sdk.mercadopago.com/js/v2"></script>

    <style>
        .pagar-wrapper {
            min-height: 100vh;
            background: #f5f5f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 40px 16px 60px;
        }
        .pagar-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 560px;
            overflow: hidden;
        }
        .pagar-header {
            background: #e53935;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .pagar-header-icon {
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            width: 48px; height: 48px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white;
        }
        .pagar-header h1 { font-size: 20px; color: white; margin: 0; }
        .pagar-header p  { font-size: 13px; color: rgba(255,255,255,0.8); margin: 4px 0 0; }
        .pagar-body { padding: 28px 32px; }
        .resumen-pago {
            background: #f7fafc;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .resumen-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 14px;
            color: #4a5568;
            border-bottom: 1px solid #edf2f7;
        }
        .resumen-row:last-child { border-bottom: none; }
        .resumen-row .label { color: #718096; }
        .resumen-row .value { font-weight: 600; color: #2d3748; }
        .resumen-monto {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
        }
        .resumen-monto .label { font-size: 15px; font-weight: 700; color: #2d3748; }
        .resumen-monto .monto { font-size: 26px; font-weight: 800; color: #e53935; }
        #payment-brick-container { min-height: 300px; }
        .loading-brick {
            display: flex; align-items: center; justify-content: center;
            padding: 60px 0; gap: 12px; color: #9e9e9e;
        }
        .spinner {
            width: 28px; height: 28px;
            border: 3px solid #f0f0f0;
            border-top-color: #e53935;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-volver {
            display: inline-flex; align-items: center; gap: 8px;
            color: #718096; font-size: 14px; text-decoration: none;
            margin-bottom: 20px; padding: 8px 0;
        }
        .btn-volver:hover { color: #e53935; }
        .mp-logo {
            display: flex; align-items: center; justify-content: center;
            gap: 8px; margin-top: 20px; color: #9e9e9e; font-size: 12px;
        }
        .mp-logo img { height: 18px; opacity: 0.6; }
    </style>
</head>
<body>
@include('includes.menu')

<div class="main-content">
<div class="pagar-wrapper">

    <div style="width:100%; max-width:560px;">
        <a href="{{ route('pagos.index') }}" class="btn-volver">
            <i class="bi bi-arrow-left"></i> Volver a pagos
        </a>
    </div>

    <div class="pagar-card">
        {{-- Header --}}
        <div class="pagar-header">
            <div class="pagar-header-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div>
                <h1>Pago seguro</h1>
                <p>Academia Karate-Do SMT · Powered by MercadoPago</p>
            </div>
        </div>

        {{-- Resumen --}}
        <div class="pagar-body">
            <div class="resumen-pago">
                <div class="resumen-row">
                    <span class="label">Alumno</span>
                    <span class="value">{{ $pago->nombre_alumno }}</span>
                </div>
                @if($pago->motivo_pago)
                <div class="resumen-row">
                    <span class="label">Concepto</span>
                    <span class="value">{{ $pago->motivo_pago }}</span>
                </div>
                @endif
                <div class="resumen-row">
                    <span class="label">Folio</span>
                    <span class="value">#{{ $pago->id_pago }}</span>
                </div>
                <div class="resumen-monto">
                    <span class="label">Total a pagar</span>
                    <span class="monto">${{ number_format($pago->monto, 2) }} MXN</span>
                </div>
            </div>

            {{-- Contenedor donde MP inyecta el Brick --}}
            <div id="payment-brick-container">
                <div class="loading-brick">
                    <div class="spinner"></div>
                    <span>Cargando métodos de pago...</span>
                </div>
            </div>

            <div class="mp-logo">
                <i class="bi bi-lock-fill"></i>
                <span>Pago procesado por</span>
                <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp" alt="MercadoPago">
            </div>
        </div>
    </div>
</div>
@include('includes.pie')
</div>

<script>
(async function () {
    // Public Key desde el backend (nunca expongas el Access Token aquí)
    const PUBLIC_KEY     = "{{ config('services.mercadopago.public_key') }}";
    const PREFERENCE_ID  = "{{ $preferenceId }}";
    const ID_PAGO        = {{ $pago->id_pago }};

    const mp = new MercadoPago(PUBLIC_KEY, { locale: 'es-MX' });

    const bricksBuilder = mp.bricks();

    const settings = {
        initialization: {
            amount:       {{ $pago->monto }},
            //preferenceId: PREFERENCE_ID,
        },
        customization: {
            // Muestra TODOS los métodos disponibles para MX:
            // tarjeta crédito/débito, OXXO, transferencia SPEI, etc.
            paymentMethods: {
                creditCard:          'all',
                debitCard:           'all',
                ticket:              'all',   // OXXO, Paycash
                bankTransfer:        'all',   // SPEI
                atm:                 'all',
                mercadoPago:         'all',   // Wallet MP
                maxInstallments:     12,
            },
            visual: {
                style: {
                    theme:         'default',
                    customVariables: {
                        baseColor:       '#e53935',
                        baseColorFirstVariant:  '#c62828',
                        baseColorSecondVariant: '#ffcdd2',
                        errorColor:      '#c62828',
                        successColor:    '#4caf50',
                        fontSizeSmall:   '14px',
                        borderRadiusSmall: '8px',
                        borderRadiusMedium: '12px',
                        borderRadiusLarge: '16px',
                    },
                },
                hideFormTitle:       false,
                hidePaymentButton:   false,
            },
        },
        callbacks: {
            onReady: () => {
                // Brick listo — ocultar spinner
                document.querySelector('.loading-brick')?.remove();
            },
            onSubmit: async ({ selectedPaymentMethod, formData }) => {
                // Enviar al backend para procesar el pago
                try {
                    // 1. Cambiamos la URL a la que definiste en web.php
                    const res = await fetch('/pagos/procesar', { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            // 2. Usamos el TOKEN CSRF (Obligatorio en web.php)
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        // 3. Ya NO enviamos la cabecera 'Authorization', 
                        // porque web.php usa la sesión de tu navegador (Cookie).
                        credentials: 'include',
                        body: JSON.stringify({
                            formData,
                            id_pago: ID_PAGO,
                        }),
                    });

                    // Si el servidor responde 401 es que la sesión de Laravel expiró
                    if (res.status === 401 || res.status === 419) {
                        alert("Tu sesión ha expirado. Por favor, recarga la página e intenta de nuevo.");
                        return Promise.reject();
                    }

                    const data = await res.json();

                    if (data.success) {
                        // Redirigir según el estado del pago
                        const estado = data.status === 'approved' ? 'success'
                                     : data.status === 'pending'  ? 'pending'
                                     : 'failure';
                        window.location.href = `/pagos/resultado?estado=${estado}&id_pago=${ID_PAGO}`;
                    } else {
                        // Devolver el error al Brick para que lo muestre en el formulario
                        return Promise.reject(data.message ?? 'Error al procesar el pago.');
                    }
                } catch (err) {
                    console.error('Error procesando pago:', err);
                    return Promise.reject("Error de conexión con el servidor.");
                }
            },
            onError: (error) => {
                console.error('Brick error:', error);
            },
        },
    };

    await bricksBuilder.create('payment', 'payment-brick-container', settings);
})();
</script>
</body>
</html>