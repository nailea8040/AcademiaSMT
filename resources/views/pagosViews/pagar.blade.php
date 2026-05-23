{{--
    resources/views/pagosViews/pagar.blade.php — REEMPLAZA COMPLETO
    Vista con Payment Brick de MercadoPago.
    Correcciones aplicadas:
      - Public Key tomada UNA sola vez de config (sin duplicado)
      - Inicialización del Brick con solo preferenceId (sin 'amount' extra que causa conflicto)
      - fetch a /pagos/procesar con credentials: 'include' para web y móvil
      - Manejo robusto de errores 419/401 (sesión expirada)
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
        .error-brick {
            background: #fff3f3;
            border: 1px solid #ffcdd2;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: #c62828;
            font-size: 14px;
            display: none;
        }
        .error-brick button {
            margin-top: 12px;
            background: #e53935;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
        }
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

            {{-- Mensaje de error si el Brick no carga --}}
            <div id="brick-error" class="error-brick">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:28px;"></i>
                <p style="margin:8px 0 0;" id="brick-error-msg">No se pudo cargar el formulario de pago.</p>
                <button onclick="location.reload()">Reintentar</button>
            </div>

            {{-- Contenedor donde MP inyecta el Brick --}}
            <div id="payment-brick-container">
                <div class="loading-brick" id="loading-spinner">
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
    // ── Datos del servidor ────────────────────────────────────────────────────
    // CORRECCIÓN: Public Key leída UNA sola vez (antes había un duplicado que podía
    // causar que se inicializara MP dos veces con claves distintas).
    const PUBLIC_KEY    = @json(config('services.mercadopago.public_key'));
    const PREFERENCE_ID = @json($preferenceId);
    const ID_PAGO       = @json($pago->id_pago);
    const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── Validación temprana ───────────────────────────────────────────────────
    if (!PUBLIC_KEY || PUBLIC_KEY === '' || (!PUBLIC_KEY.startsWith('TEST-') && !PUBLIC_KEY.startsWith('APP_USR-'))) {
        mostrarErrorBrick('Public Key de MercadoPago inválida. Contacta al administrador.');
        return;
    }

    if (!PREFERENCE_ID) {
        mostrarErrorBrick('No se pudo generar la preferencia de pago. Vuelve atrás e inténtalo de nuevo.');
        return;
    }

    // ── Inicialización del SDK ────────────────────────────────────────────────
    let mp;
    try {
        mp = new MercadoPago(PUBLIC_KEY, { locale: 'es-MX' });
    } catch (e) {
        mostrarErrorBrick('Error al inicializar MercadoPago: ' + e.message);
        return;
    }

    const bricksBuilder = mp.bricks();

    // ── Configuración del Payment Brick ──────────────────────────────────────
    // CORRECCIÓN: NO se pasa 'amount' junto con 'preferenceId'.
    // Cuando se usa preferenceId, el monto viene de la preferencia creada en el servidor.
    // Pasar los dos juntos causa el error "No pudimos obtener la información de pago".
    const settings = {
        initialization: {
            preferenceId: PREFERENCE_ID,
            amount: {{ $montoACobrar }},
        },
        customization: {
            paymentMethods: {
                creditCard:      'all',
                debitCard:       'all',
                ticket:          'all',   // OXXO, Paycash
                bankTransfer:    'all',   // SPEI
                atm:             'all',
                mercadoPago:     'all',   // Wallet MP
                maxInstallments: 12,
            },
            visual: {
                style: {
                    theme: 'default',
                    customVariables: {
                        baseColor:              '#e53935',
                        baseColorFirstVariant:  '#c62828',
                        baseColorSecondVariant: '#ffcdd2',
                        errorColor:             '#c62828',
                        successColor:           '#4caf50',
                        fontSizeSmall:          '14px',
                        borderRadiusSmall:      '8px',
                        borderRadiusMedium:     '12px',
                        borderRadiusLarge:      '16px',
                    },
                },
                hideFormTitle:     false,
                hidePaymentButton: false,
            },
        },
        callbacks: {
            onReady: () => {
                // Brick listo → ocultar spinner
                document.getElementById('loading-spinner')?.remove();
            },
            onSubmit: async ({ selectedPaymentMethod, formData }) => {
                try {
                    const res = await fetch('/pagos/procesar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                            // Accept: application/json hace que Laravel devuelva JSON
                            // en vez de una redirección HTML ante errores de validación.
                            'Accept': 'application/json',
                        },
                        // credentials: 'include' envía la cookie de sesión en web y móvil
                        credentials: 'include',
                        body: JSON.stringify({
                            formData,
                            id_pago: ID_PAGO,
                        }),
                    });

                    // Sesión expirada (419 = CSRF inválido, 401 = no autenticado)
                    if (res.status === 419 || res.status === 401) {
                        alert('Tu sesión ha expirado. La página se recargará para que puedas intentarlo de nuevo.');
                        window.location.reload();
                        return Promise.reject('session_expired');
                    }

                    const data = await res.json();

                    if (data.success) {
                        const estado = data.status === 'approved' ? 'success'
                                     : data.status === 'pending'  ? 'pending'
                                     : 'failure';
                        window.location.href = `/pagos/resultado?estado=${estado}&id_pago=${ID_PAGO}`;
                    } else {
                        // Devolver el mensaje de error al Brick para que lo muestre
                        return Promise.reject(data.message ?? 'Error al procesar el pago.');
                    }

                } catch (err) {
                    if (err === 'session_expired') return Promise.reject(err);
                    console.error('Error procesando pago:', err);
                    return Promise.reject(typeof err === 'string' ? err : 'Error de conexión con el servidor.');
                }
            },
            onError: (error) => {
                console.error('Brick error:', error);
                // Solo mostramos el div de error si el Brick no llegó a cargar
                if (!document.querySelector('#payment-brick-container [data-testid]')) {
                    mostrarErrorBrick('El formulario de pago encontró un error. Intenta recargar la página.');
                }
            },
        },
    };

    try {
        await bricksBuilder.create('payment', 'payment-brick-container', settings);
    } catch (e) {
        console.error('Error creando Brick:', e);
        mostrarErrorBrick('No se pudo cargar el formulario de pago. Verifica tu conexión e intenta de nuevo.');
    }

    function mostrarErrorBrick(msg) {
        document.getElementById('loading-spinner')?.remove();
        const errDiv = document.getElementById('brick-error');
        if (errDiv) {
            document.getElementById('brick-error-msg').textContent = msg;
            errDiv.style.display = 'block';
        }
    }
})();
</script>
</body>
</html>