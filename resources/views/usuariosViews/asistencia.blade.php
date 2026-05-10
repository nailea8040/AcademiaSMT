<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Asistencia por Día - Academia</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
@include('includes.menu')

<div class="main-content">

    <header class="header">
        <div>
            <h1 class="header-title">
                <i class="bi bi-calendar-check"></i> Lista de Asistencia
            </h1>
            <div class="breadcrumb">
                <a href="{{ route('principal') }}">Inicio</a>
                <i class="bi bi-chevron-right"></i>
                <span>Asistencia</span>
            </div>
        </div>
    </header>

    <div class="content-wrapper">

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="bi bi-x-circle-fill alert-icon"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- ── Selector de fecha y botones de descarga ── --}}
        <div class="form-container form-theme-red" style="margin-bottom:24px">
            <div class="form-header">
                <h2><i class="bi bi-funnel-fill"></i> Filtrar por Fecha</h2>
                <p>Selecciona el día para ver y exportar la lista de asistencia</p>
            </div>

            <div class="form-body">
                {{-- Formulario de búsqueda --}}
                <form method="GET" action="{{ route('asistencia.index') }}" class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            Fecha <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-calendar3 input-icon"></i>
                            <input type="date"
                                   name="fecha"
                                   class="form-input"
                                   value="{{ $fecha }}"
                                   max="{{ now()->toDateString() }}"
                                   required>
                        </div>
                    </div>
                    <div class="form-group" style="align-self:flex-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>

                {{-- Selector de tipo de exportación --}}
                <div style="margin-top:16px">
                    <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:8px">
                        <i class="bi bi-funnel"></i> Incluir en la descarga:
                    </label>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:8px 16px;border-radius:20px;border:2px solid #e53935;background:#e53935;color:white;font-size:13px;font-weight:600;transition:all .2s">
                            <input type="radio" name="filtro_descarga" value="todos" checked style="display:none" id="rdTodos">
                            <i class="bi bi-people-fill"></i> Todos
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:8px 16px;border-radius:20px;border:2px solid #e53935;background:white;color:#e53935;font-size:13px;font-weight:600;transition:all .2s" id="lblBachiller">
                            <input type="radio" name="filtro_descarga" value="bachiller" style="display:none" id="rdBachiller">
                            <i class="bi bi-mortarboard-fill"></i> Solo Bachiller
                        </label>
                    </div>

                    {{-- Botones de descarga --}}
                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center">
                        {{-- PDF --}}
                        <a id="btnPdf"
                           href="{{ route('asistencia.pdf', ['fecha' => $fecha, 'filtro' => 'todos']) }}"
                           class="btn"
                           style="background:#dc3545;color:white;display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600">
                            <i class="bi bi-file-earmark-pdf-fill" style="font-size:18px"></i>
                            Descargar PDF
                        </a>

                        {{-- Excel / CSV --}}
                        <a id="btnExcel"
                           href="{{ route('asistencia.excel', ['fecha' => $fecha, 'filtro' => 'todos']) }}"
                           class="btn"
                           style="background:#1d6f42;color:white;display:flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;text-decoration:none;font-weight:600">
                            <i class="bi bi-file-earmark-excel-fill" style="font-size:18px"></i>
                            Descargar Excel
                        </a>

                        {{-- Contador --}}
                        <span style="display:flex;align-items:center;color:#555;font-size:14px;gap:6px">
                            <i class="bi bi-people-fill" style="color:#e53935"></i>
                            <strong>{{ $asistencias->count() }}</strong> asistencia(s) registrada(s)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tabla de asistencia ── --}}
        <div class="table-container">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="bi bi-table"></i>
                    Asistencia del {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                </h2>

                {{-- Filtro bachiller/todos --}}
                <div style="display:flex;gap:8px">
                    <button class="filter-btn active" id="btnTodos"
                            onclick="filtrar('todos')"
                            style="padding:6px 14px;border-radius:20px;border:1px solid #e53935;background:#e53935;color:white;cursor:pointer;font-size:13px">
                        Todos
                    </button>
                    <button class="filter-btn" id="btnBachiller"
                            onclick="filtrar('bachiller')"
                            style="padding:6px 14px;border-radius:20px;border:1px solid #e53935;background:white;color:#e53935;cursor:pointer;font-size:13px">
                        Solo Bachiller
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tablaAsistencia">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre Completo</th>
                            <th>Hora</th>
                            <th>N° Control</th>
                            <th>Grupo</th>
                            <th>Especialidad</th>
                            <th>Turno</th>
                            <th>Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($asistencias as $i => $a)
                            <tr class="{{ $a->numero_control ? 'es-bachiller' : 'no-bachiller' }}">
                                <td>{{ $i + 1 }}</td>

                                <td>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div style="width:36px;height:36px;border-radius:50%;background:#e53935;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:14px;flex-shrink:0">
                                            {{ strtoupper(substr(trim($a->nombre_completo), 0, 1)) }}
                                        </div>
                                        <span>{{ trim($a->nombre_completo) }}</span>
                                    </div>
                                </td>

                                <td>
                                    <span style="font-family:monospace;color:#555">
                                        {{ $a->hora_registro ?? substr($a->fecha, 11, 5) }}
                                    </span>
                                </td>

                                {{-- Datos bachiller — muestran '—' si son NULL --}}
                                <td>{{ $a->numero_control ?? '—' }}</td>
                                <td>{{ $a->grupo          ?? '—' }}</td>
                                <td>{{ $a->especialidad   ?? '—' }}</td>

                                <td>
                                    @if($a->turno)
                                        @php
                                            $turnoColor = match($a->turno) {
                                                'Matutino'   => '#1565c0',
                                                'Vespertino' => '#e65100',
                                                'Nocturno'   => '#212121',
                                                default      => '#757575',
                                            };
                                        @endphp
                                        <span style="background:{{ $turnoColor }}20;color:{{ $turnoColor }};padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600">
                                            {{ $a->turno }}
                                        </span>
                                    @else
                                        <span style="color:#bdbdbd">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if($a->numero_control)
                                        <span style="background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:600">
                                            Bachiller
                                        </span>
                                    @else
                                        <span style="background:#f5f5f5;color:#757575;padding:3px 10px;border-radius:10px;font-size:12px">
                                            General
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align:center;padding:40px;color:#9e9e9e">
                                    <i class="bi bi-calendar-x" style="font-size:32px;display:block;margin-bottom:8px"></i>
                                    No hay asistencias registradas para este día.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- /content-wrapper --}}

    @include('includes.pie')
</div>

<script>
// ── Filtro tabla ─────────────────────────────────────────────────────────────
function filtrar(tipo) {
    const filas        = document.querySelectorAll('#tablaAsistencia tbody tr');
    const btnTodos     = document.getElementById('btnTodos');
    const btnBachiller = document.getElementById('btnBachiller');

    filas.forEach(fila => {
        fila.style.display = (tipo === 'todos' || fila.classList.contains('es-bachiller')) ? '' : 'none';
    });

    if (tipo === 'todos') {
        btnTodos.style.background     = '#e53935';
        btnTodos.style.color          = 'white';
        btnBachiller.style.background = 'white';
        btnBachiller.style.color      = '#e53935';
    } else {
        btnBachiller.style.background = '#e53935';
        btnBachiller.style.color      = 'white';
        btnTodos.style.background     = 'white';
        btnTodos.style.color          = '#e53935';
    }
}

// ── Filtro descarga PDF/Excel ────────────────────────────────────────────────
(function () {
    const fecha    = "{{ $fecha }}";
    const urlPdf   = "{{ route('asistencia.pdf',   ['fecha' => $fecha]) }}";
    const urlExcel = "{{ route('asistencia.excel', ['fecha' => $fecha]) }}";

    const btnPdf   = document.getElementById('btnPdf');
    const btnExcel = document.getElementById('btnExcel');
    const rdTodos      = document.getElementById('rdTodos');
    const rdBachiller  = document.getElementById('rdBachiller');
    const lblTodos     = rdTodos.closest('label');
    const lblBachiller = document.getElementById('lblBachiller');

    function actualizarLinks() {
        const filtro = rdBachiller.checked ? 'bachiller' : 'todos';
        btnPdf.href   = urlPdf   + '&filtro=' + filtro;
        btnExcel.href = urlExcel + '&filtro=' + filtro;
    }

    function actualizarEstiloRadios() {
        if (rdTodos.checked) {
            lblTodos.style.background     = '#e53935';
            lblTodos.style.color          = 'white';
            lblBachiller.style.background = 'white';
            lblBachiller.style.color      = '#e53935';
        } else {
            lblBachiller.style.background = '#e53935';
            lblBachiller.style.color      = 'white';
            lblTodos.style.background     = 'white';
            lblTodos.style.color          = '#e53935';
        }
        actualizarLinks();
    }

    // Hacer clic en el label completo activa el radio oculto
    lblTodos.addEventListener('click', () => {
        rdTodos.checked = true;
        actualizarEstiloRadios();
    });
    lblBachiller.addEventListener('click', () => {
        rdBachiller.checked = true;
        actualizarEstiloRadios();
    });

    // Estado inicial
    actualizarEstiloRadios();
})();
</script>

</body>
</html>