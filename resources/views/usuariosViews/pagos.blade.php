<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestión de Pagos - Dojo</title>
    
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
@include('includes.menu') 

<div class="main-content">
    
    {{-- HEADER MODERNO --}}
    <header class="header">
        <div>
            <h1 class="header-title">
                <i class="bi bi-cash-coin"></i>
                Gestión de Pagos
            </h1>
            <div class="breadcrumb">
                <a href="{{ route('principal') }}">Inicio</a>
                <i class="bi bi-chevron-right"></i>
                <span>Pagos</span>
            </div>
        </div>
    </header>

    <div class="content-wrapper">
        
        {{-- ALERTA DE ÉXITO/ERROR (Usando Blade para mensajes de sesión) --}}
        @if(session('mensaje'))
            @php
                $isSuccess = session('sessionInsertado') == 'true';
            @endphp
            <div class="alert {{ $isSuccess ? 'alert-success' : 'alert-danger' }}">
                <i class="bi bi-{{ $isSuccess ? 'check-circle-fill' : 'x-circle-fill' }} alert-icon"></i>
                <div>
                    <strong>{{ $isSuccess ? '¡Éxito!' : '¡Error!' }}</strong> {{ session('mensaje') }}
                </div>
            </div>
        @endif

        {{-- ESTADÍSTICAS --}}
        

        {{-- FORMULARIO --}}
        <div class="form-container form-theme-red">
            <div class="form-header">
                <h2>
                    <i class="bi bi-credit-card-fill"></i>
                    Registrar Nuevo Pago
                </h2>
                <p>Complete la información del pago realizado por el alumno</p>
            </div>
            
            <form id="registroPago" method="POST" action="{{ route('pagos.store') }}" class="form-body">
                @csrf
                
                <h3 class="section-title-header">
                    <i class="bi bi-person-circle"></i>
                    Información del Alumno
                </h3>
                <div class="form-grid full-width">
                    <div class="form-group">
                        <label class="form-label" for="id_alumno">
                            Alumno <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-person-badge input-icon"></i>
                            <select name="id_alumno" id="id_alumno" class="form-select" required>
                                <option value="">Seleccione Alumno</option>
                                @foreach($alumnos as $alumno)
                                    <option value="{{ $alumno->id_alumno }}" {{ old('id_alumno') == $alumno->id_alumno ? 'selected' : '' }}>
                                        {{ $alumno->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('id_alumno')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-receipt-cutoff"></i>
                    Detalles del Pago
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="tipo">
                            Tipo de Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-tag input-icon"></i>
                            <select name="id_tipo_pago" id="id_tipo_pago" class="form-select" required>
                                <option value="">Seleccione el tipo</option>
                                @foreach($tipos_pago as $tp)
                                    <option value="{{ $tp->id_tipo_pago }}" {{ old('id_tipo_pago') == $tp->id_tipo_pago ? 'selected' : '' }}>
                                        {{ $tp->nombre_tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('tipo')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="monto">
                            Monto <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-currency-dollar input-icon"></i>
                            <input type="number" step="0.01" name="monto" id="monto" class="form-input" 
                                   placeholder="0.00" value="{{ old('monto') }}" required>
                        </div>
                        @error('monto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="fechaPago">
                            Fecha de Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-calendar-check input-icon"></i>
                            <input type="date" name="fechaPago" id="fechaPago" class="form-input" 
                                   value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                        </div>
                        @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="estadoPago">
                            Estado del Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-check-circle input-icon"></i>
                            <select name="estadoPago" id="estadoPago" class="form-select" required>
                                <option value="">Seleccionar Estado</option>
                                <option value="Completado" {{ old('estadoPago') == 'Completado' ? 'selected' : '' }}>Completado</option>
                                <option value="Pendiente" {{ old('estadoPago') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="Fallido" {{ old('estadoPago') == 'Fallido' ? 'selected' : '' }}>Fallido</option>
                            </select>
                        </div>
                        @error('estadoPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <h3 class="section-title-header">
                    <i class="bi bi-credit-card"></i>
                    Método y Referencia
                </h3>
                <div class="form-grid full-width">
                    <div class="form-group">
                        <label class="form-label">
                            Método de Pago <span class="required">*</span>
                        </label>
                        <div class="payment-methods" id="paymentMethods">
                            <div class="payment-method" data-value="Efectivo">
                                <i class="bi bi-cash"></i>
                                <span>Efectivo</span>
                            </div>
                            <div class="payment-method" data-value="Tarjeta">
                                <i class="bi bi-credit-card"></i>
                                <span>Tarjeta</span>
                            </div>
                            <div class="payment-method" data-value="Transferencia">
                                <i class="bi bi-bank"></i>
                                <span>Transferencia</span>
                            </div>
                            <div class="payment-method" data-value="Otro">
                                <i class="bi bi-wallet2"></i>
                                <span>Otro</span>
                            </div>
                        </div>
                        <input type="hidden" name="metodoPago" id="metodoPagoInput" value="{{ old('metodoPago') }}" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="motivoPago">
                            Motivo del Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-chat-left-text input-icon"></i>
                            <input type="text" name="motivoPago" id="motivoPago" class="form-input" 
                                    placeholder="Ej: Mensualidad Diciembre 2024" value="{{ old('motivoPago') }}" required>
                        </div>
                        @error('motivoPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="referenciaPago">
                            Referencia de Pago (Opcional)
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-receipt input-icon"></i>
                            <input type="text" name="referenciaPago" id="referenciaPago" class="form-input" 
                                    placeholder="Número de referencia o voucher" value="{{ old('referenciaPago') }}">
                        </div>
                        @error('referenciaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i>
                        Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Registrar Pago
                    </button>
                </div>
            </form>
        </div>

        {{-- TABLA DE PAGOS --}}
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="bi bi-table"></i>
                    Historial de Pagos ({{ count($pagos) }})
                </h2>
                <div class="table-filters">
                    <select class="filter-select" id="filterEstado">
                        <option value="">Todos los estados</option>
                        <option value="Completado">Completado</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Fallido">Fallido</option>
                    </select>
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Buscar pagos...">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $pago)
                            <tr>
                                <td>
                                    <div class="student-cell">
                                        {{-- Iniciales del alumno --}}
                                        <div class="student-avatar">{{ substr($pago->nombre_alumno, 0, 1) . substr(strstr($pago->nombre_alumno, ' '), 1, 1) }}</div>
                                        <span class="student-name">{{ $pago->nombre_alumno ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                <td>{{ $pago->nombre_tipo }}</td>
                                <td><span class="amount">${{ number_format($pago->monto, 2) }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>
                                <td><span class="badge badge-info">{{ 'N/A' }}</span></td>
                                <td>
                                    @if($pago->estado_pago == 'Completado')
                                        <span class="badge badge-success">Completado</span>
                                    @elseif($pago->estado_pago == 'Pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @else
                                        <span class="badge badge-danger">Fallido</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn btn-view" title="Ver detalles">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        <button class="action-btn btn-edit" title="Editar">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button class="action-btn btn-delete" title="Eliminar">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay pagos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pie de página --}}
    @include('includes.pie')
</div>

{{-- Script de selección de método de pago y SweetAlert --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Lógica de selección de método de pago ---
        const paymentMethods = document.querySelectorAll('.payment-method');
        const metodoPagoInput = document.getElementById('metodoPagoInput');
        
        // Función para manejar la selección
        const handlePaymentSelection = (selectedValue) => {
            paymentMethods.forEach(option => {
                option.classList.remove('selected');
                if (option.getAttribute('data-value') === selectedValue) {
                    option.classList.add('selected');
                }
            });
            metodoPagoInput.value = selectedValue;
        };

        paymentMethods.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                handlePaymentSelection(value);
            });
        });

        // Restaurar selección si hay un valor anterior (ej: después de una validación fallida)
        if (metodoPagoInput.value) {
            handlePaymentSelection(metodoPagoInput.value);
        }

        // --- Lógica de SweetAlert del Código 1 ---
        @if(session('sessionInsertado'))
            const icono = '{{ session('sessionInsertado') == 'true' ? 'success' : 'error' }}';
            const titulo = '{{ session('mensaje') }}';
            
            Swal.fire({
                icon: icono,
                title: titulo,
                showConfirmButton: false,
                timer: 2000
            });
        @endif
    });
</script>

</body>
</html>