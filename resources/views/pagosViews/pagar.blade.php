{{-- resources/views/pagosViews/pagar.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pagar - Academia Karate-Do SMT</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
        .btn-pagar-mp {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            background: #009ee3;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px 24px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
            margin-bottom: 12px;
        }
        .btn-pagar-mp:hover { background: #0082c0; color: white; }
        .btn-pagar-mp img { height: 24px; filter: brightness(0) invert(1); }
        .aviso-sandbox {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #795548;
            margin-bottom: 20px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .aviso-sandbox i { font-size: 16px; color: #f57c00; margin-top: 1px; flex-shrink: 0; }
        .mp-seguro {
            display: flex; align-items: center; justify-content: center;
            gap: 6px; margin-top: 16px; color: #9e9e9e; font-size: 12px;
        }
        .btn-volver {
            display: inline-flex; align-items: center; gap: 8px;
            color: #718096; font-size: 14px; text-decoration: none;
            margin-bottom: 20px; padding: 8px 0;
        }
        .btn-volver:hover { color: #e53935; }
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

        <div class="pagar-body">
            {{-- Resumen del pago --}}
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
                    <span class="monto">${{ number_format($montoACobrar, 2) }} MXN</span>
                </div>
            </div>

            {{-- Aviso solo en sandbox --}}
            @if(config('services.mercadopago.sandbox', false) || str_starts_with(config('services.mercadopago.access_token', ''), 'TEST-'))
            <div class="aviso-sandbox">
                <i class="bi bi-info-circle-fill"></i>
                <span>Modo de prueba activo. Serás redirigido a la página de MercadoPago para completar el pago de prueba.</span>
            </div>
            @endif

            {{-- Botón principal — redirige a MercadoPago --}}
            @php
                $isSandbox = config('services.mercadopago.sandbox', false)
                    || str_starts_with(config('services.mercadopago.access_token', ''), 'TEST-');
                $mpUrl = $isSandbox
                    ? ($preferencia['sandbox_init_point'] ?? $preferencia['init_point'])
                    : $preferencia['init_point'];
            @endphp

            <a href="{{ $mpUrl }}" class="btn-pagar-mp">
                <i class="bi bi-credit-card-fill" style="font-size:20px;"></i>
                Pagar ${{ number_format($montoACobrar, 2) }} MXN con MercadoPago
            </a>

            <div class="mp-seguro">
                <i class="bi bi-lock-fill"></i>
                <span>Pago procesado de forma segura por MercadoPago</span>
            </div>
        </div>
    </div>

</div>
@include('includes.pie')
</div>
</body>
</html>