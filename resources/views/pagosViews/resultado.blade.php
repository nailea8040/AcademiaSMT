<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Resultado de Pago - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
@include('includes.menu')

<div class="main-content">
    <div class="content-wrapper" style="display:flex; justify-content:center; align-items:center; min-height:70vh;">
        <div style="background:white; border-radius:20px; padding:48px 40px; text-align:center; max-width:480px; width:100%; box-shadow:0 4px 24px rgba(0,0,0,0.08);">

            @if($estado === 'success')
                <i class="bi bi-check-circle-fill" style="font-size:64px; color:#4caf50;"></i>
                <h2 style="margin-top:16px; color:#2d3748;">¡Pago exitoso!</h2>
                <p style="color:#718096; margin-top:8px;">Tu pago fue procesado correctamente por MercadoPago.</p>
                @if($pago)
                    <div style="background:#f7fafc; border-radius:12px; padding:16px; margin-top:20px; text-align:left;">
                        <p style="margin:4px 0;"><strong>Folio:</strong> #{{ $pago->id_pago }}</p>
                        <p style="margin:4px 0;"><strong>Monto:</strong> ${{ number_format($pago->monto, 2) }}</p>
                        <p style="margin:4px 0;"><strong>Estado:</strong> {{ $pago->estado_pago }}</p>
                        @if($pago->motivo_pago)
                            <p style="margin:4px 0;"><strong>Concepto:</strong> {{ $pago->motivo_pago }}</p>
                        @endif
                    </div>
                @endif

            @elseif($estado === 'pending')
                <i class="bi bi-clock-fill" style="font-size:64px; color:#ff9800;"></i>
                <h2 style="margin-top:16px; color:#2d3748;">Pago pendiente</h2>
                <p style="color:#718096; margin-top:8px;">Tu pago está siendo procesado. Recibirás confirmación cuando se acredite.</p>

            @else
                <i class="bi bi-x-circle-fill" style="font-size:64px; color:#f44336;"></i>
                <h2 style="margin-top:16px; color:#2d3748;">Pago no completado</h2>
                <p style="color:#718096; margin-top:8px;">Hubo un problema al procesar el pago. Puedes intentarlo de nuevo.</p>
            @endif

            <div style="margin-top:32px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
                <a href="{{ route('pagos.index') }}"
                   style="background:#e53935; color:white; padding:12px 28px; border-radius:10px; text-decoration:none; font-weight:600;">
                    <i class="bi bi-cash-coin"></i> Ver mis pagos
                </a>
                <a href="{{ route('principal') }}"
                   style="background:#f0f0f0; color:#424242; padding:12px 28px; border-radius:10px; text-decoration:none; font-weight:600;">
                    <i class="bi bi-house"></i> Inicio
                </a>
            </div>
        </div>
    </div>
    @include('includes.pie')
</div>
</body>
</html>