{{-- resources/views/usuariosViews/principal.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sistema de Gestión de Dojo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        /* ── Hero ──────────────────────────────────────────────── */
        .hero-section {
            background: linear-gradient(135deg, #b71c1c 0%, #e53935 60%, #ef9a9a 100%);
            padding: 56px 32px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .hero-title   { font-size: clamp(1.6rem,4vw,2.4rem); color:#fff; font-weight:800; margin:0 0 8px; }
        .hero-subtitle{ font-size:1rem; color:rgba(255,255,255,.8); margin-bottom:32px; }
        .stats-grid   { display:flex; justify-content:center; gap:20px; flex-wrap:wrap; margin-bottom:32px; }
        .stat-hero    {
            background:rgba(255,255,255,.15); backdrop-filter:blur(4px);
            border:1px solid rgba(255,255,255,.25); border-radius:16px;
            padding:20px 28px; min-width:140px; text-align:center; color:#fff;
        }
        .stat-hero .num { font-size:2rem; font-weight:900; display:block; }
        .stat-hero .lbl { font-size:.75rem; opacity:.85; text-transform:uppercase; letter-spacing:.05em; }
        .cta-buttons  { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
        .btn-hero-primary {
            background:#fff; color:#e53935; font-weight:700; padding:12px 28px;
            border-radius:12px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;
            transition:.2s;
        }
        .btn-hero-primary:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.15); }
        .btn-hero-secondary {
            background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.4);
            font-weight:600; padding:12px 28px; border-radius:12px; text-decoration:none;
            display:inline-flex; align-items:center; gap:8px; transition:.2s;
        }
        .btn-hero-secondary:hover { background:rgba(255,255,255,.25); }

        /* ── Dashboard grid ─────────────────────────────────────── */
        .dashboard-wrap { padding:32px 24px; max-width:1400px; margin:0 auto; }
        .section-title  { font-size:1.3rem; font-weight:800; color:#1a1a2e; margin:0 0 20px; display:flex; align-items:center; gap:10px; }
        .section-title i{ color:#e53935; }

        /* KPI cards */
        .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:32px; }
        .kpi-card {
            background:#fff; border-radius:16px; padding:20px 22px;
            box-shadow:0 2px 12px rgba(0,0,0,.06); border-left:4px solid #e53935;
            display:flex; flex-direction:column; gap:4px;
        }
        .kpi-card.green  { border-color:#43a047; }
        .kpi-card.orange { border-color:#fb8c00; }
        .kpi-card.purple { border-color:#7b1fa2; }
        .kpi-card.blue   { border-color:#1565c0; }
        .kpi-val  { font-size:1.9rem; font-weight:900; color:#1a1a2e; }
        .kpi-lbl  { font-size:.75rem; color:#718096; text-transform:uppercase; letter-spacing:.05em; }
        .kpi-icon { font-size:1.4rem; margin-bottom:4px; }
        .kpi-card.green  .kpi-icon { color:#43a047; }
        .kpi-card.orange .kpi-icon { color:#fb8c00; }
        .kpi-card.purple .kpi-icon { color:#7b1fa2; }
        .kpi-card.blue   .kpi-icon { color:#1565c0; }
        .kpi-card        .kpi-icon { color:#e53935; }

        /* Chart cards */
        .charts-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr)); gap:20px; margin-bottom:32px; }
        .chart-card  {
            background:#fff; border-radius:20px; padding:24px;
            box-shadow:0 2px 16px rgba(0,0,0,.07);
        }
        .chart-card.full { grid-column:1/-1; }
        .chart-title { font-size:1rem; font-weight:700; color:#2d3748; margin:0 0 18px; display:flex; align-items:center; gap:8px; }
        .chart-title i { color:#e53935; }
        .chart-wrap  { position:relative; }

        /* Tabla estancados */
        .table-card { background:#fff; border-radius:20px; padding:24px; box-shadow:0 2px 16px rgba(0,0,0,.07); margin-bottom:32px; }
        .stancados-table { width:100%; border-collapse:collapse; font-size:.875rem; }
        .stancados-table th {
            background:#fef2f2; color:#c62828; font-weight:700; text-transform:uppercase;
            font-size:.7rem; letter-spacing:.05em; padding:10px 14px; text-align:left;
        }
        .stancados-table td { padding:12px 14px; border-bottom:1px solid #f0f0f0; color:#374151; }
        .stancados-table tr:last-child td { border-bottom:none; }
        .stancados-table tr:hover td { background:#fef9f9; }
        .badge-meses {
            display:inline-block; padding:3px 10px; border-radius:999px; font-size:.7rem; font-weight:700;
        }
        .badge-warn  { background:#fff3e0; color:#e65100; }
        .badge-alert { background:#ffebee; color:#c62828; }
        .empty-state { text-align:center; padding:32px; color:#9e9e9e; }
        .empty-state i { font-size:2rem; display:block; margin-bottom:8px; }

        /* Responsive */
        @media(max-width:640px) {
            .dashboard-wrap { padding:16px 12px; }
            .kpi-grid { grid-template-columns:1fr 1fr; }
        }
    </style>
</head>
<body>
    @include('includes.menu')

    <div class="top-bar">
        <div class="top-bar-title">
            <i class="bi bi-grid-fill"></i> Sistema de Gestión de Dojo
        </div>
        <div class="breadcrumb-top">
            Bienvenido, {{ Auth::user()->nombre ?? 'Usuario del Sistema' }}
        </div>
    </div>

    {{-- ════ HERO ════ --}}
    <section class="hero-section">
        <div style="position:relative;z-index:1">
            <h1 class="hero-title">Panel de Control del Dojo</h1>
            <p class="hero-subtitle">Estadísticas en tiempo real · Gestión centralizada</p>

            <div class="stats-grid">
                <div class="stat-hero">
                    <span class="num" data-target="{{ $totalAlumnos }}">0</span>
                    <span class="lbl">Alumnos activos</span>
                </div>
                <div class="stat-hero">
                    <span class="num" data-target="{{ $mesesTrayectoria }}">0</span>
                    <span class="lbl">Años trayectoria</span>
                </div>
                <div class="stat-hero">
                    <span class="num" data-target="{{ $totalMaestros }}">0</span>
                    <span class="lbl">Sensei / Tutores</span>
                </div>
                <div class="stat-hero">
                    <span class="num" data-target="{{ $pagosPendientes }}">0</span>
                    <span class="lbl">Pagos pendientes</span>
                </div>
            </div>

            <div class="cta-buttons">
                <a href="{{ route('alumnos.index') }}" class="btn-hero-primary">
                    <i class="bi bi-person-badge-fill"></i> Gestión de Alumnos
                </a>
                <a href="{{ route('pagos.index') }}" class="btn-hero-secondary">
                    <i class="bi bi-cash-coin"></i> Módulo de Pagos
                </a>
            </div>
        </div>
    </section>

    {{-- ════ DASHBOARD ════ --}}
    <div class="main-content">
    <div class="dashboard-wrap">

        {{-- ── KPIs ── --}}
        <h2 class="section-title"><i class="bi bi-bar-chart-fill"></i> Resumen General</h2>
        <div class="kpi-grid">
            <div class="kpi-card">
                <i class="bi bi-people-fill kpi-icon"></i>
                <span class="kpi-val">{{ $totalAlumnos }}</span>
                <span class="kpi-lbl">Alumnos activos</span>
            </div>
            <div class="kpi-card green">
                <i class="bi bi-cash-stack kpi-icon"></i>
                <span class="kpi-val">${{ number_format(collect($ingresosPorMes)->sum('total_ingresos'), 0) }}</span>
                <span class="kpi-lbl">Ingresos últimos 12 meses</span>
            </div>
            <div class="kpi-card orange">
                <i class="bi bi-clock-history kpi-icon"></i>
                <span class="kpi-val">{{ $pagosPendientes }}</span>
                <span class="kpi-lbl">Pagos pendientes</span>
            </div>
            <div class="kpi-card purple">
                <i class="bi bi-currency-dollar kpi-icon"></i>
                <span class="kpi-val">${{ number_format($montoPendiente, 0) }}</span>
                <span class="kpi-lbl">Monto por cobrar</span>
            </div>
            <div class="kpi-card blue">
                <i class="bi bi-person-x-fill kpi-icon"></i>
                <span class="kpi-val">{{ count($alumnosEstancados) }}</span>
                <span class="kpi-lbl">Alumnos sin avance (+12 m)</span>
            </div>
            <div class="kpi-card">
                <i class="bi bi-award-fill kpi-icon"></i>
                <span class="kpi-val">{{ count($alumnosPorGrado) }}</span>
                <span class="kpi-lbl">Grados con alumnos</span>
            </div>
        </div>

        {{-- ── Gráficas principales ── --}}
        <h2 class="section-title"><i class="bi bi-graph-up-arrow"></i> Finanzas y Matrículas</h2>
        <div class="charts-grid">

            {{-- Ingresos por mes --}}
            <div class="chart-card full">
                <p class="chart-title"><i class="bi bi-cash-coin"></i> Ingresos por Mes (últimos 12 meses)</p>
                <div class="chart-wrap" style="height:260px">
                    <canvas id="chartIngresos"></canvas>
                </div>
            </div>

            {{-- Nuevos alumnos por mes --}}
            <div class="chart-card">
                <p class="chart-title"><i class="bi bi-person-plus-fill"></i> Alumnos Nuevos por Mes</p>
                <div class="chart-wrap" style="height:240px">
                    <canvas id="chartAlumnos"></canvas>
                </div>
            </div>

            {{-- Ingresos por concepto --}}
            <div class="chart-card">
                <p class="chart-title"><i class="bi bi-pie-chart-fill"></i> Ingresos por Concepto</p>
                <div class="chart-wrap" style="height:240px">
                    <canvas id="chartConceptos"></canvas>
                </div>
            </div>

        </div>

        {{-- ── Alumnos por grado ── --}}
        <h2 class="section-title"><i class="bi bi-diagram-3-fill"></i> Distribución por Grado</h2>
        <div class="charts-grid">
            <div class="chart-card full">
                <p class="chart-title"><i class="bi bi-bar-chart-steps"></i> Alumnos Actuales por Grado</p>
                <div class="chart-wrap" style="height:280px">
                    <canvas id="chartGrados"></canvas>
                </div>
            </div>
        </div>

        {{-- ── Tabla estancados ── --}}
        <h2 class="section-title"><i class="bi bi-exclamation-triangle-fill"></i> Alumnos Sin Avance de Grado (12+ meses)</h2>
        <div class="table-card">
            @if(count($alumnosEstancados) > 0)
                <div style="overflow-x:auto">
                    <table class="stancados-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Alumno</th>
                                <th>Grado Actual</th>
                                <th>Desde</th>
                                <th>Tiempo en grado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($alumnosEstancados as $i => $a)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <div style="width:34px;height:34px;border-radius:50%;background:#e53935;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0">
                                            {{ strtoupper(mb_substr($a->nombre_completo, 0, 1)) }}
                                        </div>
                                        {{ $a->nombre_completo }}
                                    </div>
                                </td>
                                <td>{{ $a->grado_actual }}</td>
                                <td>{{ \Carbon\Carbon::parse($a->fecha_obtencion)->format('d/m/Y') }}</td>
                                <td>
                                    @php $m = $a->meses_en_grado; @endphp
                                    <span class="badge-meses {{ $m >= 24 ? 'badge-alert' : 'badge-warn' }}">
                                        {{ $m >= 12 ? floor($m/12).'a ' : '' }}{{ $m % 12 }}m
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-check-circle-fill" style="color:#43a047"></i>
                    <p>¡Todos los alumnos han avanzado de grado en el último año!</p>
                </div>
            @endif
        </div>

    </div>{{-- /dashboard-wrap --}}
    @include('includes.pie')
    </div>{{-- /main-content --}}

    {{-- ═══ SCRIPTS ═══ --}}
    <script>
    // ── Datos desde Laravel ─────────────────────────────────────────────
    const dataIngresos = @json($ingresosPorMes);
    const dataAlumnos  = @json($alumnosPorMes);
    const dataGrados   = @json($alumnosPorGrado);
    const dataConceptos= @json($ingresosPorConcepto);

    // ── Paleta ──────────────────────────────────────────────────────────
    const RED    = '#e53935';
    const RED_L  = 'rgba(229,57,53,0.12)';
    const GREEN  = '#43a047';
    const GREEN_L= 'rgba(67,160,71,0.12)';
    const CINTAS = [
        '#f5f5f5','#fdd835','#ffb300','#43a047','#2e7d32',
        '#7b1fa2','#4a148c','#795548','#4e342e','#3e2723',
        '#212121','#37474f',
    ];
    const MULTI  = ['#e53935','#fb8c00','#43a047','#1565c0','#7b1fa2','#00838f','#f06292','#558b2f'];

    // ── Opciones base ────────────────────────────────────────────────────
    const baseOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a1a2e',
                titleFont: { size: 13, weight: '700' },
                bodyFont:  { size: 12 },
                padding: 12,
                cornerRadius: 10,
            }
        },
    };

    // ── 1. Ingresos por mes (bar + line overlay) ─────────────────────────
    (function() {
        const labels  = dataIngresos.map(d => d.mes_label);
        const montos  = dataIngresos.map(d => parseFloat(d.total_ingresos));
        const npagos  = dataIngresos.map(d => parseInt(d.total_pagos));

        if (!labels.length) {
            document.getElementById('chartIngresos').closest('.chart-card').innerHTML +=
                '<p style="text-align:center;color:#9e9e9e;padding:16px">Sin datos de ingresos aún.</p>';
            return;
        }

        new Chart(document.getElementById('chartIngresos'), {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Ingresos ($)',
                        data: montos,
                        backgroundColor: RED,
                        borderRadius: 8,
                        borderSkipped: false,
                        order: 2,
                        yAxisID: 'y',
                    },
                    {
                        label: 'N° Pagos',
                        data: npagos,
                        type: 'line',
                        borderColor: GREEN,
                        backgroundColor: GREEN_L,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: GREEN,
                        order: 1,
                        yAxisID: 'y2',
                    }
                ]
            },
            options: {
                ...baseOpts,
                plugins: {
                    ...baseOpts.plugins,
                    legend: {
                        display: true,
                        position: 'top',
                        labels: { usePointStyle: true, padding: 16, font: { size: 12 } }
                    },
                    tooltip: {
                        ...baseOpts.plugins.tooltip,
                        callbacks: {
                            label: ctx => ctx.datasetIndex === 0
                                ? ` $${ctx.raw.toLocaleString('es-MX', {minimumFractionDigits:2})}`
                                : ` ${ctx.raw} pagos`
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: {
                        position: 'left',
                        ticks: {
                            callback: v => '$' + (v >= 1000 ? (v/1000).toFixed(0)+'k' : v),
                            font: { size: 11 }
                        },
                        grid: { color: '#f0f0f0' }
                    },
                    y2: {
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    })();

    // ── 2. Alumnos nuevos por mes ────────────────────────────────────────
    (function() {
        const labels = dataAlumnos.map(d => d.mes_label);
        const vals   = dataAlumnos.map(d => parseInt(d.total_alumnos));

        if (!labels.length) {
            document.getElementById('chartAlumnos').closest('.chart-card').innerHTML +=
                '<p style="text-align:center;color:#9e9e9e;padding:16px">Sin registros de alumnos aún.</p>';
            return;
        }

        new Chart(document.getElementById('chartAlumnos'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Alumnos nuevos',
                    data: vals,
                    borderColor: RED,
                    backgroundColor: RED_L,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: RED,
                    borderWidth: 2.5,
                }]
            },
            options: {
                ...baseOpts,
                plugins: {
                    ...baseOpts.plugins,
                    tooltip: {
                        ...baseOpts.plugins.tooltip,
                        callbacks: { label: ctx => ` ${ctx.raw} alumno(s)` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 } },
                        grid: { color: '#f0f0f0' }
                    }
                }
            }
        });
    })();

    // ── 3. Ingresos por concepto (doughnut) ─────────────────────────────
    (function() {
        const labels = dataConceptos.map(d => d.concepto);
        const vals   = dataConceptos.map(d => parseFloat(d.total));

        if (!labels.length) {
            document.getElementById('chartConceptos').closest('.chart-card').innerHTML +=
                '<p style="text-align:center;color:#9e9e9e;padding:16px">Sin datos de conceptos aún.</p>';
            return;
        }

        new Chart(document.getElementById('chartConceptos'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: vals,
                    backgroundColor: MULTI,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: { font: { size: 11 }, padding: 10, usePointStyle: true }
                    },
                    tooltip: {
                        ...baseOpts.plugins.tooltip,
                        callbacks: {
                            label: ctx => {
                                const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                                const pct   = ((ctx.raw/total)*100).toFixed(1);
                                return ` $${ctx.raw.toLocaleString('es-MX',{minimumFractionDigits:2})} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    })();

    // ── 4. Alumnos por grado (horizontal bar) ───────────────────────────
    (function() {
        const labels = dataGrados.map(d => d.grado);
        const vals   = dataGrados.map(d => parseInt(d.total_alumnos));
        const colors = labels.map((_, i) => CINTAS[i] ?? '#9e9e9e');
        // Texto oscuro para cintas claras (blanca, amarilla)
        const textColors = labels.map((lbl) =>
            ['Blanca','Amarilla','Amarilla Avanzada'].includes(lbl) ? '#333' : '#fff'
        );

        if (!labels.length) {
            document.getElementById('chartGrados').closest('.chart-card').innerHTML +=
                '<p style="text-align:center;color:#9e9e9e;padding:16px">Sin historial de grados registrado.</p>';
            return;
        }

        new Chart(document.getElementById('chartGrados'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Alumnos',
                    data: vals,
                    backgroundColor: colors,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                ...baseOpts,
                indexAxis: 'y',
                plugins: {
                    ...baseOpts.plugins,
                    tooltip: {
                        ...baseOpts.plugins.tooltip,
                        callbacks: { label: ctx => ` ${ctx.raw} alumno(s)` }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 11 } },
                        grid: { color: '#f0f0f0' }
                    },
                    y: { grid: { display: false }, ticks: { font: { size: 12, weight: '600' } } }
                }
            }
        });
    })();

    // ── Animación contadores hero ────────────────────────────────────────
    document.querySelectorAll('.stat-hero .num').forEach(el => {
        const target = parseInt(el.dataset.target) || 0;
        if (!target) { el.textContent = '0'; return; }
        let current = 0;
        const step  = Math.ceil(target / 50);
        const t     = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current + (target >= 100 ? '+' : '');
            if (current >= target) clearInterval(t);
        }, 30);
    });
    </script>
</body>
</html>