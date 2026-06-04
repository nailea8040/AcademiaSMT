{{--
    resources/views/pagosViews/pagos.blade.php — REEMPLAZA COMPLETO

    Admin/sensei:
      - Formulario para registrar cargos a cualquier alumno/tutor
      - Panel de gestión de conceptos (crear/editar)
      - Tabla con TODOS los pagos

    Alumno/tutor:
      - Formulario para registrar su propio pago (elige concepto, ajusta monto)
      - Tabla con SUS pagos
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pagos - Dojo</title>
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ═══════════════════════════════════════════════════════════════
           PAGOS — Dashboard SaaS rediseño
        ═══════════════════════════════════════════════════════════════ */

        /* ── Variables ── */
        :root {
            --red:      #e53935;
            --red-dark: #b71c1c;
            --red-soft: #fff5f5;
            --green:    #22c55e;
            --amber:    #f59e0b;
            --blue:     #3b82f6;
            --purple:   #8b5cf6;
            --text:     #1e293b;
            --muted:    #64748b;
            --border:   #e2e8f0;
            --surface:  #ffffff;
            --bg:       #f8fafc;
            --radius:   16px;
            --shadow:   0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
            --shadow-md:0 4px 24px rgba(0,0,0,0.10);
        }

        /* ── Layout general ── */
        body { background: var(--bg); }

        .content-wrapper {
            padding: 0 28px 40px;
            max-width: 1400px;
        }

        /* ── Header premium ── */
        .page-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 28px 0 20px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .page-hero-left h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text);
            margin: 0 0 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-hero-left h1 .hero-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--red), #ff6b6b);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
        }
        .page-hero-left .breadcrumb {
            font-size: 13px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .page-hero-left .breadcrumb a { color: var(--muted); text-decoration:none; }
        .page-hero-left .breadcrumb a:hover { color: var(--red); }

        /* ── Stats grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 540px)  { .stats-grid { grid-template-columns: 1fr; } }

        .stat-card {
            background: var(--surface);
            border-radius: var(--radius);
            padding: 20px 22px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: flex-start;
            gap: 14px;
            position: relative;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.red::after    { background: linear-gradient(90deg, var(--red), #ff6b6b); }
        .stat-card.green::after  { background: linear-gradient(90deg, var(--green), #86efac); }
        .stat-card.amber::after  { background: linear-gradient(90deg, var(--amber), #fcd34d); }
        .stat-card.blue::after   { background: linear-gradient(90deg, var(--blue), #93c5fd); }

        .stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stat-card.red   .stat-icon { background:#fef2f2; color:var(--red); }
        .stat-card.green .stat-icon { background:#f0fdf4; color:var(--green); }
        .stat-card.amber .stat-icon { background:#fffbeb; color:var(--amber); }
        .stat-card.blue  .stat-icon { background:#eff6ff; color:var(--blue); }

        .stat-body { flex: 1; min-width: 0; }
        .stat-label { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .stat-value { font-size: 24px; font-weight: 800; color: var(--text); line-height: 1; }
        .stat-sub   { font-size: 12px; color: var(--muted); margin-top: 4px; }

        /* ── Card / panel base ── */
        .panel {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .panel-header {
            padding: 18px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .panel-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        .panel-title i { color: var(--red); font-size: 17px; }
        .panel-body { padding: 24px; }

        /* ── Tabs mejorados ── */
        .tabs-nav {
            display: flex;
            gap: 2px;
            border-bottom: 2px solid var(--border);
            margin-bottom: 24px;
            overflow-x: auto;
        }
        .tab-btn {
            background: none; border: none;
            padding: 10px 18px;
            font-size: 13px; font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            transition: color .15s, border-color .15s;
            display: flex; align-items: center; gap: 6px;
        }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active { color: var(--red); border-bottom-color: var(--red); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* ── Form mejorado ── */
        .form-section-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .7px;
            margin: 0 0 14px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .form-section-title i { color: var(--red); font-size: 14px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .form-grid.full-width { grid-template-columns: 1fr; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-label { font-size: 12px; font-weight: 700; color: var(--text); letter-spacing: .2px; }
        .required { color: var(--red); }

        .form-input-wrapper { position: relative; }
        .input-icon {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--muted); font-size: 14px; pointer-events: none;
        }
        .form-input, .form-select {
            width: 100%;
            padding: 10px 12px 10px 36px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            color: var(--text);
            background: var(--bg);
            box-sizing: border-box;
            transition: border-color .15s, box-shadow .15s;
            appearance: none;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(229,57,53,.10);
            background: white;
        }

        /* ── Botones ── */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px; border-radius: 10px;
            font-size: 14px; font-weight: 700;
            cursor: pointer; border: none;
            transition: all .15s;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--red), #ef5350);
            color: white;
            box-shadow: 0 2px 8px rgba(229,57,53,.30);
        }
        .btn-primary:hover { background: linear-gradient(135deg, var(--red-dark), var(--red)); }
        .btn-secondary {
            background: var(--bg); color: var(--muted);
            border: 1.5px solid var(--border);
        }
        .btn-secondary:hover { background: var(--border); color: var(--text); }

        .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px; }

        /* ── Badges de estado ── */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 700;
        }
        .badge-success    { background:#f0fdf4; color:#16a34a; }
        .badge-warning    { background:#fffbeb; color:#d97706; }
        .badge-danger     { background:#fef2f2; color:#dc2626; }
        .badge-suspendido { background:#fdf4ff; color:#9333ea; }
        .badge-rechazado  { background:#fef2f2; color:#dc2626; }

        /* ── Progress ── */
        .progress-bar-wrap { width:100%; background:#f1f5f9; border-radius:10px; height:5px; margin-top:5px; }
        .progress-bar-fill { height:5px; border-radius:10px; background:linear-gradient(90deg,var(--red),#ff6b6b); transition:width .4s; }

        /* ── Saldo badges ── */
        .saldo-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .saldo-pendiente  { background:#fffbeb; color:#d97706; }
        .saldo-completado { background:#f0fdf4; color:#16a34a; }

        /* ── Tabla ── */
        .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .table-filters { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-select {
            padding: 8px 12px 8px 12px;
            border: 1.5px solid var(--border); border-radius: 10px;
            font-size: 13px; color: var(--text); background: white;
            cursor: pointer;
        }
        .filter-select:focus { outline: none; border-color: var(--red); }
        .search-box { position: relative; }
        .search-icon { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:13px; }
        .search-input {
            padding: 8px 12px 8px 32px;
            border: 1.5px solid var(--border); border-radius: 10px;
            font-size: 13px; color: var(--text); background: white; width: 200px;
        }
        .search-input:focus { outline:none; border-color:var(--red); }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: var(--bg); border-bottom: 2px solid var(--border); }
        th {
            padding: 11px 16px;
            font-size: 11px; font-weight: 700;
            color: var(--muted); text-transform: uppercase; letter-spacing: .5px;
            text-align: left; white-space: nowrap;
        }
        td { padding: 14px 16px; font-size: 13px; color: var(--text); border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tbody tr:hover { background: #fafbfc; }
        tbody tr:last-child td { border-bottom: none; }

        .amount { font-weight: 800; font-size: 14px; color: var(--text); }
        .student-cell { display: flex; align-items: center; gap: 10px; }
        .student-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--red), #ff6b6b);
            color: white; font-size: 12px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .student-name { font-weight: 600; color: var(--text); }

        /* ── Botones de acciones en tabla ── */
        .acciones-cell { display:flex; flex-wrap:wrap; gap:5px; align-items:center; }
        .btn-sm {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 8px;
            font-size: 11px; font-weight: 700;
            cursor: pointer; border: none; transition: all .15s;
            text-decoration: none;
        }
        .btn-completar  { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; }
        .btn-completar:hover  { background:#dcfce7; }
        .btn-abono      { background:#fffbeb; color:#d97706; border:1px solid #fed7aa; }
        .btn-abono:hover      { background:#fef3c7; }
        .btn-abonos-ver { background:#fdf4ff; color:#9333ea; border:1px solid #e9d5ff; }
        .btn-abonos-ver:hover { background:#f3e8ff; }
        .btn-eliminar   { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
        .btn-eliminar:hover   { background:#fee2e2; }
        .btn-suspender  { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
        .btn-suspender:hover  { background:#fef9c3; }

        /* ── Info banner ── */
        .info-banner { background:#eff6ff; border-left:4px solid var(--blue); border-radius:10px; padding:14px 18px; margin-bottom:20px; display:flex; align-items:flex-start; gap:12px; }
        .info-banner i { font-size:18px; color:var(--blue); margin-top:1px; }
        .info-banner p { margin:0; font-size:13px; color:#1e40af; line-height:1.5; }

        /* ── Aviso efectivo ── */
        .aviso-efectivo { background:#fffbeb; border-left:4px solid var(--amber); border-radius:8px; padding:12px 16px; margin-top:8px; font-size:13px; color:#92400e; display:none; }

        /* ── Concepto hint ── */
        .concepto-hint { font-size:12px; color:var(--muted); margin-top:4px; min-height:18px; }
        .concepto-hint strong { color:var(--red); }

        /* ── Modales ── */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(2px); }
        .modal-overlay.active { display:flex; }
        .modal-box { background:white; border-radius:20px; padding:28px; width:100%; max-width:500px; box-shadow:0 20px 60px rgba(0,0,0,0.20); max-height:90vh; overflow-y:auto; }
        .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .modal-header h3 { font-size:17px; font-weight:800; color:var(--text); margin:0; display:flex; align-items:center; gap:8px; }
        .modal-close { background:none; border:none; font-size:22px; cursor:pointer; color:var(--muted); width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; transition:background .15s; }
        .modal-close:hover { background:var(--border); color:var(--text); }

        /* ── Abonos ── */
        .abonos-list { max-height:260px; overflow-y:auto; margin-bottom:16px; }
        .abono-item { display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border); font-size:13px; }
        .abono-item:last-child { border-bottom:none; }
        .abono-tipo-badge { padding:3px 9px; border-radius:8px; font-size:11px; font-weight:700; }
        .tipo-efectivo { background:#f0fdf4; color:#16a34a; }
        .tipo-en_linea { background:#eff6ff; color:#1d4ed8; }

        .form-abono { margin-top:16px; border-top:1px solid var(--border); padding-top:16px; }
        .form-abono label { font-size:12px; font-weight:700; color:var(--text); margin-bottom:4px; display:block; }
        .form-abono input, .form-abono select { width:100%; padding:10px 12px; border:1.5px solid var(--border); border-radius:10px; font-size:14px; margin-bottom:12px; box-sizing:border-box; transition:border-color .15s; }
        .form-abono input:focus, .form-abono select:focus { outline:none; border-color:var(--red); }
        .btn-abono-submit { width:100%; padding:12px; background:linear-gradient(135deg,var(--red),#ef5350); color:white; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(229,57,53,.30); }
        .btn-abono-submit:hover { background:linear-gradient(135deg,var(--red-dark),var(--red)); }

        /* ── Info panel pago ── */
        .pago-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; background:var(--bg); border-radius:12px; padding:14px; margin-bottom:16px; }
        .pago-info-item { display:flex; flex-direction:column; gap:2px; }
        .pago-info-label { font-size:11px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.4px; }
        .pago-info-value { font-size:14px; font-weight:700; color:var(--text); }
        .pago-info-value.danger { color:var(--red); }
        .pago-info-value.success { color:var(--green); }

        /* ── Empty state ── */
        .empty-state { text-align:center; padding:48px 24px; color:var(--muted); }
        .empty-state i { font-size:40px; color:var(--border); display:block; margin-bottom:12px; }
        .empty-state p { margin:0; font-size:14px; }

        /* ── Concepto cards ── */
        .conceptos-panel { margin-top: 8px; }
        .conceptos-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-top:4px; }
        @media (max-width:900px) { .conceptos-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:540px) { .conceptos-grid { grid-template-columns:1fr; } }

        .concepto-card {
            background:white; border:1.5px solid var(--border); border-radius:14px;
            padding:16px 18px; display:flex; flex-direction:column; gap:8px;
            box-shadow:var(--shadow); transition:all .2s; position:relative; overflow:hidden;
        }
        .concepto-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); border-color:#fecaca; }
        .concepto-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--red),#ff6b6b); }
        .concepto-card.inactivo-card::before { background:var(--border); }
        .concepto-card-top { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
        .concepto-nombre { font-weight:700; color:var(--text); font-size:14px; line-height:1.3; flex:1; }
        .concepto-estado { font-size:11px; padding:3px 9px; border-radius:20px; font-weight:700; white-space:nowrap; }
        .concepto-activo   { background:#f0fdf4; color:#16a34a; }
        .concepto-inactivo { background:#fef2f2; color:#dc2626; }
        .concepto-desc { font-size:12px; color:var(--muted); line-height:1.4; flex:1; min-height:32px; }
        .concepto-card-bottom { display:flex; justify-content:space-between; align-items:center; margin-top:4px; padding-top:10px; border-top:1px solid var(--border); }
        .concepto-monto { font-size:20px; font-weight:800; color:var(--red); }
        .concepto-monto.sin-monto { font-size:14px; font-weight:500; color:#cbd5e1; }
        .btn-edit-concepto { background:none; border:1.5px solid var(--border); border-radius:8px; padding:5px 12px; cursor:pointer; color:var(--muted); font-size:12px; font-weight:700; display:inline-flex; align-items:center; gap:4px; transition:all .15s; }
        .btn-edit-concepto:hover { background:var(--red-soft); color:var(--red); border-color:var(--red); }

        /* ── Alert ── */
        .alert { display:flex; align-items:flex-start; gap:12px; padding:14px 18px; border-radius:12px; margin-bottom:20px; font-size:14px; }
        .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
        .alert-danger  { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
        .alert-icon    { font-size:18px; margin-top:1px; flex-shrink:0; }

        /* ── Sección tutor colapsable ── */
        .tutor-section {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .tutor-section-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 22px;
            cursor: pointer;
            background: white;
            border: none;
            width: 100%;
            text-align: left;
            transition: background .15s;
            gap: 12px;
        }
        .tutor-section-toggle:hover { background: var(--bg); }
        .tutor-section-toggle-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tutor-section-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--red), #ff6b6b);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 16px; flex-shrink: 0;
        }
        .tutor-section-title { font-size: 15px; font-weight: 700; color: var(--text); }
        .tutor-section-subtitle { font-size: 12px; color: var(--muted); margin-top: 1px; }
        .tutor-section-badge {
            background: linear-gradient(135deg, var(--red), #ff6b6b);
            color: white;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .tutor-chevron { color: var(--muted); transition: transform .25s; font-size: 16px; }
        .tutor-chevron.open { transform: rotate(180deg); }

        .tutor-section-body {
            display: none;
            padding: 0 22px 20px;
            border-top: 1px solid var(--border);
        }
        .tutor-section-body.open { display: block; }

        .alumno-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 14px;
            padding-top: 18px;
        }
        .alumno-card {
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all .2s;
        }
        .alumno-card:hover { border-color: #fecaca; background: var(--red-soft); box-shadow: 0 4px 14px rgba(229,57,53,.10); }
        .alumno-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            background: linear-gradient(135deg, var(--red), #ff6b6b);
            color: white; font-weight: 800; font-size: 15px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .alumno-info { flex: 1; min-width: 0; }
        .alumno-nombre { font-weight: 700; color: var(--text); font-size: 14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .alumno-relacion { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .btn-ver-pagos {
            background: linear-gradient(135deg, var(--red), #ef5350);
            color: white; border: none; border-radius: 10px;
            padding: 8px 14px; font-size: 12px; font-weight: 700;
            cursor: pointer; white-space: nowrap;
            display: inline-flex; align-items: center; gap: 5px;
            box-shadow: 0 2px 6px rgba(229,57,53,.25);
            transition: all .15s;
        }
        .btn-ver-pagos:hover { background: linear-gradient(135deg, var(--red-dark), var(--red)); }

        /* Modal pagos del alumno */
        .modal-pagos-alumno .modal-box { max-width: 620px; }
        .modal-pagos-header {
            background: linear-gradient(135deg, var(--red), #ef5350);
            margin: -28px -28px 20px;
            padding: 22px 28px;
            border-radius: 20px 20px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-pagos-header h3 { color: white; font-size: 17px; font-weight: 800; margin: 0; }
        .modal-pagos-header .btn-close-white { background: rgba(255,255,255,.2); border:none; color:white; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:18px; display:flex; align-items:center; justify-content:center; transition: background .15s; }
        .modal-pagos-header .btn-close-white:hover { background: rgba(255,255,255,.35); }
        .badge-Pendiente  { background:#fffbeb; color:#d97706; }
        .badge-Completado { background:#f0fdf4; color:#16a34a; }
        .badge-Cancelado  { background:#fef2f2; color:#dc2626; }
        .badge-Suspendido { background:#fdf4ff; color:#9333ea; }
        .badge-estado { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; }
        .pago-row { border-bottom:1px solid var(--border); padding:12px 0; }
        .pago-row:last-child { border-bottom:none; }
        .form-nuevo-pago { background:var(--bg); border-radius:14px; padding:18px; margin-top:16px; border:1.5px solid var(--border); }
        .form-nuevo-pago h6 { color:var(--red); font-weight:800; margin-bottom:14px; font-size:14px; }
        .form-nuevo-pago select, .form-nuevo-pago input[type=text], .form-nuevo-pago input[type=number], .form-nuevo-pago input[type=date] {
            width:100%; padding:9px 12px; border:1.5px solid var(--border); border-radius:10px; font-size:13px; box-sizing:border-box; margin-bottom:10px; transition:border-color .15s;
        }
        .form-nuevo-pago select:focus, .form-nuevo-pago input:focus { outline:none; border-color:var(--red); }
        .form-nuevo-pago label { font-size:12px; font-weight:700; color:var(--text); display:block; margin-bottom:3px; }
        .btn-guardar-pago { width:100%; padding:11px; background:linear-gradient(135deg,var(--red),#ef5350); color:white; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 2px 8px rgba(229,57,53,.25); }
        .btn-guardar-pago:hover { background:linear-gradient(135deg,var(--red-dark),var(--red)); }
    </style>
</head>
<body>
@include('includes.menu')

<div class="main-content">

    <div class="page-hero">
        <div class="page-hero-left">
            <h1>
                <span class="hero-icon"><i class="bi bi-cash-coin"></i></span>
                @if(in_array($user->rol, ['admin', 'sensei'])) Gestión de Pagos
                @else Mis Pagos
                @endif
            </h1>
            <div class="breadcrumb">
                <a href="{{ route('principal') }}">Inicio</a>
                <i class="bi bi-chevron-right"></i>
                <span>Pagos</span>
            </div>
        </div>
    </div>

    <div class="content-wrapper">

        @if(session('mensaje'))
            @php $isSuccess = session('sessionInsertado') == 'true'; @endphp
            <div class="alert {{ $isSuccess ? 'alert-success' : 'alert-danger' }}">
                <i class="bi bi-{{ $isSuccess ? 'check-circle-fill' : 'x-circle-fill' }} alert-icon"></i>
                <div><strong>{{ $isSuccess ? '¡Éxito!' : '¡Error!' }}</strong> {{ session('mensaje') }}</div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             BLOQUE ADMIN / SENSEI
             - Tab 1: Registrar cargo a un alumno
             - Tab 2: Gestionar catálogo de conceptos
        ══════════════════════════════════════════════════════════ --}}
        {{-- ── Stats cards ──────────────────────────────────────────────────── --}}
        @php
            $totalPagos     = count($pagos);
            $completados    = collect($pagos)->where('estado_pago','Completado')->count();
            $pendientes     = collect($pagos)->where('estado_pago','Pendiente')->count();
            $totalRecaudado = collect($pagos)->where('estado_pago','Completado')->sum('monto_total') ?: 0;
            $totalPendiente = collect($pagos)->where('estado_pago','Pendiente')->sum('monto') ?: 0;
        @endphp
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="stat-card red">
                <div class="stat-icon"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Total Pagos</div>
                    <div class="stat-value">{{ $totalPagos }}</div>
                    <div class="stat-sub">registros</div>
                </div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Completados</div>
                    <div class="stat-value">{{ $completados }}</div>
                    <div class="stat-sub">pagos saldados</div>
                </div>
            </div>
            <div class="stat-card amber">
                <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Pendientes</div>
                    <div class="stat-value">{{ $pendientes }}</div>
                    <div class="stat-sub">por cobrar</div>
                </div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="stat-body">
                    <div class="stat-label">Recaudado</div>
                    <div class="stat-value">${{ number_format($totalRecaudado, 0) }}</div>
                    <div class="stat-sub">en completados</div>
                </div>
            </div>
        </div>

        @if(in_array($user->rol, ['admin', 'sensei']))

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-credit-card-fill"></i> Panel de Pagos</h2>
                <span style="font-size:13px;color:var(--muted);">Registra cargos y gestiona conceptos</span>
            </div>
            <div class="panel-body">

            {{-- Tabs de navegación --}}
            <div class="tabs-nav">
                <button class="tab-btn active" onclick="activarTab('tab-registro', this)">
                    <i class="bi bi-plus-circle"></i> Registrar Cargo
                </button>
                <button class="tab-btn" onclick="activarTab('tab-conceptos', this)">
                    <i class="bi bi-bookmarks"></i> Conceptos de Pago
                </button>
            </div>

            {{-- ── TAB 1: Registrar nuevo cargo ── --}}
            <div id="tab-registro" class="tab-content active">
                <form id="registroPago" method="POST" action="{{ route('pagos.store') }}" class="form-body">
                    @csrf

                    {{-- Destinatario --}}
                    <h3 class="form-section-title">
                        <i class="bi bi-person-circle"></i> Alumno o Tutor Destinatario
                    </h3>
                    <div class="form-grid full-width">
                        <div class="form-group">
                            <label class="form-label" for="id_alumno">
                                Destinatario <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-person-badge input-icon"></i>
                                <select name="id_alumno" id="id_alumno" class="form-select" required>
                                    <option value="">Seleccione un alumno</option>
                                    @foreach($alumnos as $alumno)
                                        <option value="{{ $alumno->id_usuario }}"
                                            {{ old('id_alumno') == $alumno->id_usuario ? 'selected' : '' }}>
                                            {{ $alumno->nombre_completo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('id_alumno')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Concepto y detalles --}}
                    <h3 class="form-section-title">
                        <i class="bi bi-receipt-cutoff"></i> Detalles del Cargo
                    </h3>
                    <div class="form-grid">

                        {{-- Concepto predefinido --}}
                        <div class="form-group">
                            <label class="form-label" for="id_concepto_admin">
                                Concepto <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-bookmark input-icon"></i>
                                <select name="id_concepto" id="id_concepto_admin" class="form-select" required>
                                    <option value="">Seleccione un concepto</option>
                                    @foreach($conceptos as $concepto)
                                        <option value="{{ $concepto->id_concepto }}"
                                            data-monto="{{ $concepto->monto_sugerido }}"
                                            data-nombre="{{ $concepto->nombre }}"
                                            {{ old('id_concepto') == $concepto->id_concepto ? 'selected' : '' }}>
                                            {{ $concepto->nombre }}
                                            @if($concepto->monto_sugerido) — ${{ number_format($concepto->monto_sugerido, 2) }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="concepto-hint" id="conceptoHintAdmin"></div>
                            @error('id_concepto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Método de pago --}}
                        <div class="form-group">
                            <label class="form-label" for="id_tipo_pago">
                                Método de Pago <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-tag input-icon"></i>
                                <select name="id_tipo_pago" id="id_tipo_pago" class="form-select" required>
                                    <option value="">Seleccione el método</option>
                                    @foreach($tipos_pago as $tipo)
                                        <option value="{{ $tipo->id_tipo_pago }}"
                                            {{ old('id_tipo_pago') == $tipo->id_tipo_pago ? 'selected' : '' }}>
                                            {{ $tipo->nombre_tipo }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('id_tipo_pago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Fecha --}}
                        <div class="form-group">
                            <label class="form-label" for="fechaPago">
                                Fecha <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-calendar-check input-icon"></i>
                                <input type="date" name="fechaPago" id="fechaPago" class="form-input"
                                       value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                            </div>
                            @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Estado inicial --}}
                        <div class="form-group" id="estadoWrapAdmin">
                            <label class="form-label" for="estadoPago">
                                Estado <span class="required">*</span>
                            </label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-check-circle input-icon"></i>
                                <select name="estadoPago" id="estadoPago" class="form-select" required>
                                    <option value="Pendiente"  {{ old('estadoPago', 'Pendiente') == 'Pendiente'  ? 'selected' : '' }}>Pendiente (el alumno pagará después)</option>
                                    <option value="Completado" {{ old('estadoPago') == 'Completado' ? 'selected' : '' }}>Completado (recibí efectivo ahora)</option>
                                </select>
                            </div>
                            @error('estadoPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>

                        {{-- Nota adicional --}}
                        <div class="form-group">
                            <label class="form-label" for="motivoPago_admin">Nota / Detalle adicional</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-chat-left-text input-icon"></i>
                                <input type="text" name="motivoPago" id="motivoPago_admin" class="form-input"
                                       placeholder="Ej: Mensualidad Mayo 2026"
                                       value="{{ old('motivoPago') }}">
                            </div>
                        </div>

                        {{-- Referencia --}}
                        <div class="form-group" id="refAdminWrap">
                            <label class="form-label" for="referenciaPago">Referencia (opcional)</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-receipt input-icon"></i>
                                <input type="text" name="referenciaPago" id="referenciaPago" class="form-input"
                                       placeholder="Número de recibo o voucher"
                                       value="{{ old('referenciaPago') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Toggle pago en línea --}}
                    <div class="form-group" style="margin-top:16px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px;">
                            <input type="checkbox" name="pagar_en_linea" id="pagarEnLinea" value="1"
                                   style="width:18px;height:18px;accent-color:#009ee3;"
                                   {{ old('pagar_en_linea') ? 'checked' : '' }}>
                            <span>
                                <i class="bi bi-credit-card-2-front-fill" style="color:#009ee3;font-size:18px;"></i>
                                Pagar en línea ahora con
                                <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp"
                                     alt="MercadoPago" style="height:20px;vertical-align:middle;margin-left:4px;">
                            </span>
                        </label>
                        <p style="margin-left:28px;margin-top:4px;font-size:12px;color:#718096;">
                            Se creará el cargo y se abrirá la página de pago. El estado se actualiza automáticamente al completarse.
                        </p>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitAdmin">
                            <i class="bi bi-check-lg"></i> Registrar Cargo
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── TAB 2: Gestión de conceptos de pago ── --}}
            <div id="tab-conceptos" class="tab-content">

                {{-- Formulario para nuevo concepto --}}
                <form method="POST" action="{{ route('conceptos.store') }}" class="form-body" style="margin-bottom:24px;">
                    @csrf
                    <h3 class="form-section-title">
                        <i class="bi bi-plus-circle"></i> Agregar Nuevo Concepto
                    </h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="nuevo_nombre">Nombre <span class="required">*</span></label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-bookmark input-icon"></i>
                                <input type="text" name="nombre" id="nuevo_nombre" class="form-input"
                                       placeholder="Ej: Torneo Regional" required>
                            </div>
                            @error('nombre')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="nuevo_monto">Monto Sugerido</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-currency-dollar input-icon"></i>
                                <input type="number" step="0.01" name="monto_sugerido" id="nuevo_monto"
                                       class="form-input" placeholder="0.00 (opcional)">
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label class="form-label" for="nuevo_desc">Descripción (opcional)</label>
                            <div class="form-input-wrapper">
                                <i class="bi bi-text-left input-icon"></i>
                                <input type="text" name="descripcion" id="nuevo_desc" class="form-input"
                                       placeholder="Breve descripción del concepto">
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Agregar Concepto
                        </button>
                    </div>
                </form>

                {{-- Lista de conceptos existentes --}}
                <div class="form-body" style="padding-top:0; margin-top:0;">
                <h3 class="form-section-title" style="margin-top:0;">
                    <i class="bi bi-list-ul"></i> Conceptos Registrados
                </h3>
                <div class="conceptos-panel">
                    <div class="conceptos-grid">
                        @forelse($conceptos_todos as $c)
                            <div class="concepto-card {{ $c->activo ? '' : 'inactivo-card' }}">
                                <div class="concepto-card-top">
                                    <span class="concepto-nombre">{{ $c->nombre }}</span>
                                    <span class="concepto-estado {{ $c->activo ? 'concepto-activo' : 'concepto-inactivo' }}">
                                        <i class="bi bi-{{ $c->activo ? 'check-circle-fill' : 'dash-circle-fill' }}"></i>
                                        {{ $c->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                                <p class="concepto-desc">
                                    {{ $c->descripcion ?? 'Sin descripción.' }}
                                </p>
                                <div class="concepto-card-bottom">
                                    @if($c->monto_sugerido)
                                        <span class="concepto-monto">${{ number_format($c->monto_sugerido, 2) }}</span>
                                    @else
                                        <span class="concepto-monto sin-monto">Sin monto</span>
                                    @endif
                                    <button type="button" class="btn-edit-concepto"
                                        onclick="abrirEditConcepto(
                                            {{ $c->id_concepto }},
                                            '{{ addslashes($c->nombre) }}',
                                            '{{ addslashes($c->descripcion ?? '') }}',
                                            '{{ $c->monto_sugerido }}',
                                            {{ $c->activo ? 1 : 0 }}
                                        )">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p style="color:#9e9e9e;text-align:center;padding:20px;grid-column:1/-1;">
                                No hay conceptos registrados aún.
                            </p>
                        @endforelse
                    </div>
                </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════
             BLOQUE ALUMNO / TUTOR
             Formulario para registrar su propio pago
        ══════════════════════════════════════════════════════════ --}}
        @else

        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title"><i class="bi bi-wallet2"></i> Registrar Pago</h2>
                <span style="font-size:13px;color:var(--muted);">Elige concepto, ajusta el monto y selecciona método de pago</span>
            </div>
            <div class="panel-body">

            <form id="registroPagoAlumno" method="POST" action="{{ route('pagos.store') }}" class="form-body">
                @csrf

                <h3 class="form-section-title">
                    <i class="bi bi-bookmark"></i> Concepto del Pago
                </h3>
                <div class="form-grid">

                    {{-- Concepto del catálogo --}}
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="id_concepto_alumno">
                            Concepto <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-bookmark input-icon"></i>
                            <select name="id_concepto" id="id_concepto_alumno" class="form-select" required>
                                <option value="">Seleccione un concepto</option>
                                @foreach($conceptos as $concepto)
                                    <option value="{{ $concepto->id_concepto }}"
                                        data-monto="{{ $concepto->monto_sugerido }}"
                                        data-nombre="{{ $concepto->nombre }}"
                                        {{ old('id_concepto') == $concepto->id_concepto ? 'selected' : '' }}>
                                        {{ $concepto->nombre }}
                                        @if($concepto->monto_sugerido) — ${{ number_format($concepto->monto_sugerido, 2) }} @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="concepto-hint" id="conceptoHintAlumno"></div>
                        @error('id_concepto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- Monto --}}
                    <div class="form-group">
                        <label class="form-label" for="monto_alumno">
                            Monto a Pagar <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-currency-dollar input-icon"></i>
                            <input type="number" step="0.01" name="monto" id="monto_alumno"
                                   class="form-input" placeholder="0.00"
                                   value="{{ old('monto') }}" required>
                        </div>
                        <div class="concepto-hint">Puedes ajustar el monto si vas a hacer un abono parcial.</div>
                        @error('monto')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- Fecha --}}
                    <div class="form-group">
                        <label class="form-label" for="fechaPago_alumno">
                            Fecha <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-calendar-check input-icon"></i>
                            <input type="date" name="fechaPago" id="fechaPago_alumno" class="form-input"
                                   value="{{ old('fechaPago', date('Y-m-d')) }}" required>
                        </div>
                        @error('fechaPago')<div class="text-danger mt-1">{{ $message }}</div>@enderror
                    </div>

                    {{-- Método de pago --}}
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="id_tipo_pago_alumno">
                            Método de Pago <span class="required">*</span>
                        </label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-tag input-icon"></i>
                            <select name="id_tipo_pago" id="id_tipo_pago_alumno" class="form-select" required
                                    onchange="mostrarAvisoEfectivo(this.value)">
                                <option value="">Seleccione el método</option>
                                @foreach($tipos_pago as $tipo)
                                    <option value="{{ $tipo->id_tipo_pago }}"
                                        data-nombre="{{ strtolower($tipo->nombre_tipo) }}"
                                        {{ old('id_tipo_pago') == $tipo->id_tipo_pago ? 'selected' : '' }}>
                                        {{ $tipo->nombre_tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('id_tipo_pago')<div class="text-danger mt-1">{{ $message }}</div>@enderror

                        {{-- Aviso si el alumno elige efectivo --}}
                        <div class="aviso-efectivo" id="avisoEfectivo">
                            <i class="bi bi-info-circle-fill"></i>
                            <strong>Pago en efectivo:</strong> Tu registro quedará como <strong>Pendiente</strong>
                            hasta que el administrador o sensei lo confirme presencialmente.
                        </div>
                    </div>

                    {{-- Nota opcional --}}
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label" for="motivoPago_alumno">Nota adicional (opcional)</label>
                        <div class="form-input-wrapper">
                            <i class="bi bi-chat-left-text input-icon"></i>
                            <input type="text" name="motivoPago" id="motivoPago_alumno" class="form-input"
                                   placeholder="Ej: Mensualidad Mayo 2026"
                                   value="{{ old('motivoPago') }}">
                        </div>
                    </div>
                </div>

                {{-- Toggle pago en línea --}}
                <div class="form-group" style="margin-top:16px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px;">
                        <input type="checkbox" name="pagar_en_linea" id="pagarEnLineaAlumno" value="1"
                               style="width:18px;height:18px;accent-color:#009ee3;"
                               {{ old('pagar_en_linea') ? 'checked' : '' }}
                               onchange="toggleEnLineaAlumno(this)">
                        <span>
                            <i class="bi bi-credit-card-2-front-fill" style="color:#009ee3;font-size:18px;"></i>
                            Pagar en línea con
                            <img src="https://http2.mlstatic.com/storage/logos-api-admin/0be7e630-3454-11ec-9874-2d2a4f2ed7de-xl.webp"
                                 alt="MercadoPago" style="height:20px;vertical-align:middle;margin-left:4px;">
                        </span>
                    </label>
                    <p style="margin-left:28px;margin-top:4px;font-size:12px;color:#718096;">
                        Al marcar esta opción serás redirigido al checkout de MercadoPago (tarjeta, OXXO, SPEI, etc.).
                        El pago se confirmará automáticamente.
                    </p>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary" onclick="resetFormAlumno()">
                        <i class="bi bi-x-lg"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitAlumno">
                        <i class="bi bi-check-lg"></i> Registrar Pago
                    </button>
                </div>
            </form>
            </div>{{-- /panel-body --}}
        </div>{{-- /panel --}}

        @endif

        {{-- ══════════════════════════════════════════════════════════
             SECCIÓN EXCLUSIVA PARA TUTORES — alumnos relacionados
        ══════════════════════════════════════════════════════════ --}}
        @if($user->rol === 'tutor')
        <div class="tutor-section">
            <button class="tutor-section-toggle" onclick="toggleTutorSection()" type="button">
                <div class="tutor-section-toggle-left">
                    <div class="tutor-section-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="tutor-section-title">Mis Alumnos Relacionados</div>
                        <div class="tutor-section-subtitle">Consulta y gestiona los pagos de tus alumnos</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="tutor-section-badge">{{ $alumnosRelacionados->count() }} alumno{{ $alumnosRelacionados->count() != 1 ? 's' : '' }}</span>
                    <i class="bi bi-chevron-down tutor-chevron" id="tutorChevron"></i>
                </div>
            </button>

            <div class="tutor-section-body" id="tutorSectionBody">
                @if($alumnosRelacionados->isEmpty())
                    <div style="text-align:center;padding:32px;color:var(--muted);">
                        <i class="bi bi-person-x" style="font-size:36px;color:var(--border);display:block;margin-bottom:10px;"></i>
                        <p style="margin:0;font-size:14px;">No tienes alumnos relacionados en este momento.</p>
                    </div>
                @else
                    <div class="alumno-grid">
                        @foreach($alumnosRelacionados as $alumno)
                        <div class="alumno-card">
                            <div class="alumno-avatar">
                                {{ strtoupper(substr($alumno->nombre_alumno,0,1)) }}
                            </div>
                            <div class="alumno-info">
                                <div class="alumno-nombre">{{ $alumno->nombre_alumno }}</div>
                                <div class="alumno-relacion">
                                    <i class="bi bi-heart-fill" style="color:var(--red);font-size:10px;"></i>
                                    {{ ucfirst($alumno->relacion) }}
                                </div>
                            </div>
                            <button class="btn-ver-pagos"
                                    onclick="abrirPagosAlumno({{ $alumno->id_alumno }}, '{{ addslashes($alumno->nombre_alumno) }}')"
                                    type="button">
                                <i class="bi bi-card-list"></i> Ver Pagos
                            </button>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Modal pagos por alumno ───────────────────────────────────────── --}}
        <div class="modal fade modal-pagos-alumno" id="modalPagosAlumno" tabindex="-1"
             aria-labelledby="modalPagosAlumnoLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content" style="border-radius:16px;overflow:hidden">

                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="modalPagosAlumnoLabel">
                            <i class="bi bi-credit-card-2-front-fill me-2"></i>
                            Pagos de <span id="nombreAlumnoModal">—</span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body" id="cuerpoModalPagos">
                        <div class="text-center py-4">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2 text-muted">Cargando pagos...</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <script>
        // ── Abrir modal y cargar pagos del alumno ─────────────────────────────
        function abrirPagosAlumno(idAlumno, nombreAlumno) {
            document.getElementById('nombreAlumnoModal').textContent = nombreAlumno;
            const cuerpo = document.getElementById('cuerpoModalPagos');
            cuerpo.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2 text-muted">Cargando pagos...</p>
                </div>`;

            const modal = new bootstrap.Modal(document.getElementById('modalPagosAlumno'));
            modal.show();

            fetch(`/pagos/alumno/${idAlumno}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) { cuerpo.innerHTML = '<p class="text-danger p-3">Error al cargar pagos.</p>'; return; }
                renderPagosAlumno(cuerpo, data, idAlumno);
            })
            .catch(() => { cuerpo.innerHTML = '<p class="text-danger p-3">Error de conexión.</p>'; });
        }

        // ── Renderizar lista de pagos + formulario nuevo pago ─────────────────
        function renderPagosAlumno(cuerpo, data, idAlumno) {
            const pagos     = data.pagos;
            const tipos     = data.tipos_pago;
            const conceptos = data.conceptos;

            // Filas de pagos
            let filasHtml = '';
            if (pagos.length === 0) {
                filasHtml = `<div class="alert alert-info d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Este alumno no tiene pagos registrados.</span>
                </div>`;
            } else {
                pagos.forEach(p => {
                    const badgeClass = 'badge-' + (p.estadoPago || 'Pendiente');
                    filasHtml += `
                    <div class="pago-row d-flex align-items-start justify-content-between flex-wrap gap-2">
                        <div>
                            <div class="fw-bold" style="color:#2d3748">${p.nombre_tipo ?? 'Sin tipo'}</div>
                            <div class="text-muted" style="font-size:13px">
                                <i class="bi bi-calendar3"></i> ${p.fecha_pago ?? '—'}
                                ${p.motivoPago ? ' &nbsp;|&nbsp; ' + p.motivoPago : ''}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold" style="color:#e53935;font-size:16px">$${parseFloat(p.monto).toFixed(2)}</div>
                            <span class="badge-estado ${badgeClass}">${p.estadoPago}</span>
                        </div>
                    </div>`;
                });
            }

            // Opciones de tipos de pago
            let opcionesTipo = '<option value="">— Tipo de pago —</option>';
            tipos.forEach(t => { opcionesTipo += `<option value="${t.id_tipo_pago}">${t.nombre_tipo}</option>`; });

            // Opciones de conceptos
            let opcionesConcepto = '<option value="">— Concepto —</option>';
            conceptos.forEach(c => {
                opcionesConcepto += `<option value="${c.id_concepto}" data-monto="${c.monto_sugerido ?? ''}">${c.nombre}${c.monto_sugerido ? ' — $'+parseFloat(c.monto_sugerido).toFixed(2) : ''}</option>`;
            });

            cuerpo.innerHTML = `
                <h6 class="fw-bold mb-3" style="color:#2d3748">
                    <i class="bi bi-list-ul me-1"></i> Historial de Pagos
                </h6>
                ${filasHtml}

                <div class="form-nuevo-pago mt-4">
                    <h6><i class="bi bi-plus-circle-fill me-1"></i> Registrar Nuevo Pago</h6>
                    <form id="formNuevoPago" onsubmit="submitNuevoPago(event, ${idAlumno})">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Concepto</label>
                                <select class="form-select" id="np_concepto" onchange="autoMonto(this)">
                                    ${opcionesConcepto}
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo de Pago</label>
                                <select class="form-select" id="np_tipo" required>
                                    ${opcionesTipo}
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0.01" id="np_monto"
                                           class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                                <input type="date" id="np_fecha" class="form-control"
                                       value="${new Date().toISOString().split('T')[0]}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estado</label>
                                <select class="form-select" id="np_estado">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Completado">Completado</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Nota adicional</label>
                                <input type="text" id="np_motivo" class="form-control"
                                       placeholder="Opcional" maxlength="255">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-danger fw-bold px-4"
                                        style="border-radius:10px">
                                    <i class="bi bi-save2-fill me-1"></i> Guardar Pago
                                </button>
                            </div>
                        </div>
                    </form>
                </div>`;
        }

        // ── Autocompletar monto desde concepto ────────────────────────────────
        function autoMonto(select) {
            const opt   = select.options[select.selectedIndex];
            const monto = opt.dataset.monto;
            if (monto) document.getElementById('np_monto').value = parseFloat(monto).toFixed(2);
        }

        // ── Enviar nuevo pago vía fetch ───────────────────────────────────────
        function submitNuevoPago(e, idAlumno) {
            e.preventDefault();

            const tipo   = document.getElementById('np_tipo').value;
            const monto  = document.getElementById('np_monto').value;
            const fecha  = document.getElementById('np_fecha').value;
            const estado = document.getElementById('np_estado').value;
            const motivo = document.getElementById('np_motivo').value;

            if (!tipo || !monto || !fecha) {
                alert('Por favor completa los campos obligatorios.');
                return;
            }

            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            fetch('/pagos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    id_alumno:    idAlumno,
                    id_tipo_pago: tipo,
                    monto:        monto,
                    fechaPago:    fecha,
                    estadoPago:   estado,
                    motivoPago:   motivo || null,
                })
            })
            .then(r => {
                if (r.ok || r.redirected || r.status === 302 || r.status === 200) {
                    const nombreActual = document.getElementById('nombreAlumnoModal').textContent;
                    abrirPagosAlumno(idAlumno, nombreActual);
                    return;
                }
                return r.json().then(data => {
                    alert(data.message ?? 'Error al guardar el pago.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-save2-fill me-1"></i> Guardar Pago';
                });
            })
            .catch(() => {
                alert('Error de conexión.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save2-fill me-1"></i> Guardar Pago';
            });
        }
        </script>
        @endif

        {{-- ══════════════════════════════════════════════════════════
             TABLA DE PAGOS (todos los roles)
        ══════════════════════════════════════════════════════════ --}}
        <div class="panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    <i class="bi bi-table"></i>
                    @if(in_array($user->rol, ['admin', 'sensei']))
                        Historial de Pagos
                        <span style="background:var(--bg);color:var(--muted);font-size:12px;padding:2px 10px;border-radius:20px;border:1px solid var(--border);">{{ count($pagos) }}</span>
                    @else
                        Mis Pagos
                        <span style="background:var(--bg);color:var(--muted);font-size:12px;padding:2px 10px;border-radius:20px;border:1px solid var(--border);">{{ count($pagos) }}</span>
                    @endif
                </h2>
                <div class="table-filters">
                    <select class="filter-select" id="filterEstado">
                        <option value="">Todos los estados</option>
                        <option value="Completado">Completado</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Suspendido">Suspendido</option>
                        <option value="Rechazado">Rechazado</option>
                    </select>
                    <div class="search-box">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Buscar concepto, alumno...">
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="padding:0 24px 24px;">
                <table id="pagosTable">
                    <thead>
                        <tr>
                            @if(in_array($user->rol, ['admin', 'sensei']))
                                <th>Alumno / Tutor</th>
                            @endif
                            <th>Concepto</th>
                            <th>Método</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pagos as $pago)
                            @php
                                $montoTotal  = $pago->monto_total  ?? $pago->monto;
                                $montoPagado = $pago->monto_pagado ?? 0;
                                $saldo       = $montoTotal - $montoPagado;
                                $porcentaje  = $pago->estado_pago === 'Completado'
                                    ? 100
                                    : ($montoTotal > 0 ? min(100, ($montoPagado / $montoTotal) * 100) : 0);
                                $concepto    = $pago->nombre_concepto ?? $pago->motivo_pago ?? '—';
                            @endphp
                            <tr>
                                @if(in_array($user->rol, ['admin', 'sensei']))
                                <td>
                                    <div class="student-cell">
                                        <div class="student-avatar">
                                            {{ strtoupper(substr($pago->nombre_alumno ?? 'A', 0, 1)) }}{{ strtoupper(substr(strstr($pago->nombre_alumno ?? ' A', ' '), 1, 1)) }}
                                        </div>
                                        <span class="student-name">{{ $pago->nombre_alumno ?? 'N/A' }}</span>
                                    </div>
                                </td>
                                @endif

                                <td>
                                    <span style="font-weight:600;color:#2d3748;">{{ $concepto }}</span>
                                    @if($pago->motivo_pago && $pago->nombre_concepto && $pago->motivo_pago !== $pago->nombre_concepto)
                                        <br><small style="color:#9e9e9e;">{{ $pago->motivo_pago }}</small>
                                    @endif
                                </td>

                                <td>{{ $pago->nombre_tipo ?? 'N/A' }}</td>

                                <td><span class="amount">${{ number_format($montoTotal, 2) }}</span></td>

                                <td>
                                    <span style="font-weight:600;color:#4caf50;">
                                        ${{ number_format($pago->estado_pago === 'Completado' ? $montoTotal : $montoPagado, 2) }}
                                    </span>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width:{{ $porcentaje }}%"></div>
                                    </div>
                                    <small style="color:#9e9e9e;font-size:11px;">{{ number_format($porcentaje, 0) }}%</small>
                                </td>

                                <td>
                                    @if($pago->estado_pago === 'Completado')
                                        <span class="saldo-badge saldo-completado">
                                            <i class="bi bi-check-circle-fill"></i> Saldado
                                        </span>
                                    @elseif($saldo <= 0)
                                        <span class="saldo-badge saldo-completado">
                                            <i class="bi bi-check-circle-fill"></i> Saldado
                                        </span>
                                    @else
                                        <span class="saldo-badge saldo-pendiente">
                                            ${{ number_format($saldo, 2) }}
                                        </span>
                                    @endif
                                </td>

                                <td>{{ \Carbon\Carbon::parse($pago->fecha_pago)->format('d/m/Y') }}</td>

                                <td>
                                    @php $estado = $pago->estado_pago; @endphp
                                    @if($estado == 'Completado')
                                        <span class="badge badge-success">Completado</span>
                                    @elseif($estado == 'Pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @elseif($estado == 'Suspendido')
                                        <span class="badge badge-suspendido">
                                            <i class="bi bi-pause-circle-fill"></i> Suspendido
                                        </span>
                                    @elseif($estado == 'Rechazado')
                                        <span class="badge badge-rechazado">
                                            <i class="bi bi-x-circle-fill"></i> Rechazado
                                        </span>
                                    @else
                                        <span class="badge badge-danger">{{ $estado ?? 'N/A' }}</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="acciones-cell">
                                        @if(!in_array($pago->estado_pago, ['Completado', 'Suspendido']) && $saldo > 0)
                                            <a href="{{ route('pagos.pagar', $pago->id_pago) }}"
                                               class="btn-sm btn-primary" style="">
                                                <i class="bi bi-credit-card-fill"></i> Pagar
                                            </a>
                                        @endif

                                        @if(!in_array($pago->estado_pago, ['Completado', 'Suspendido']) && $saldo > 0)
                                            <button type="button" class="btn-sm btn-abono"
                                                onclick="abrirModalAbono(
                                                    {{ $pago->id_pago }},
                                                    '{{ addslashes($pago->nombre_alumno ?? ($user->nombre . ' ' . $user->apaterno)) }}',
                                                    {{ $montoTotal }},
                                                    {{ $montoPagado }},
                                                    {{ $saldo }},
                                                    '{{ $user->rol }}'
                                                )">
                                                <i class="bi bi-plus-circle"></i> Abono
                                            </button>
                                        @endif

                                        @if(in_array($user->rol, ['admin', 'sensei']) && in_array($pago->estado_pago, ['Pendiente', 'Suspendido', 'Rechazado']))
                                            <button type="button" class="btn-sm btn-completar"
                                                onclick="confirmarCompletar({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-check-circle-fill"></i> Completar
                                            </button>
                                            <form id="formCompletar-{{ $pago->id_pago }}" method="POST"
                                                  action="{{ route('pagos.completar', $pago->id_pago) }}"
                                                  style="display:none;">
                                                @csrf
                                            </form>
                                        @endif

                                        <button type="button"
                                            class="btn-sm btn-abonos-ver"
                                            onclick="verAbonos({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                            <i class="bi bi-list-ul"></i> Abonos
                                        </button>

                                        @if($pago->estado_pago === 'Completado')
                                            <span style="color:#4caf50;font-size:12px;display:flex;align-items:center;gap:4px;">
                                                <i class="bi bi-check-circle-fill"></i> Pagado
                                            </span>
                                        @endif

                                        @if($user->rol === 'sensei' && $pago->estado_pago === 'Pendiente')
                                            <button type="button" class="btn-sm btn-suspender"
                                                onclick="confirmarSuspender({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-pause-circle"></i> Suspender
                                            </button>
                                        @endif

                                        @if($user->rol === 'admin' && $pago->estado_pago !== 'Completado')
                                            <button type="button" class="btn-sm btn-eliminar"
                                                onclick="confirmarEliminar({{ $pago->id_pago }}, '{{ addslashes($concepto) }}')">
                                                <i class="bi bi-trash3"></i> Eliminar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ in_array($user->rol, ['admin','sensei']) ? 9 : 8 }}">
                                    <div class="empty-state">
                                        <i class="bi bi-receipt"></i>
                                        <p>
                                            @if(in_array($user->rol, ['admin', 'sensei']))
                                                No hay pagos registrados aún.
                                            @else
                                                No tienes pagos registrados. Usa el formulario de arriba para registrar uno.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('includes.pie')
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MODAL REGISTRAR ABONO
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalAbono">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-plus-circle" style="color:#e65100;"></i> Registrar Abono</h3>
            <button class="modal-close" onclick="cerrarModal('modalAbono')">×</button>
        </div>

        <div id="infoAbono" style="background:#f7fafc;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:#718096;">Alumno</span>
                <strong id="abonoAlumnoNombre">—</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:#718096;">Total a pagar</span>
                <strong id="abonoMontoTotal">—</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="color:#718096;">Ya pagado</span>
                <strong id="abonoMontoPagado" style="color:#4caf50;">—</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span style="color:#718096;">Saldo restante</span>
                <strong id="abonoSaldo" style="color:#e65100;">—</strong>
            </div>
        </div>

        <form id="formAbono" method="POST" action="" class="form-abono">
            @csrf

            <label for="monto_abono">Monto del abono <span style="color:#e53935;">*</span></label>
            <input type="number" step="0.01" min="1" name="monto_abono" id="monto_abono"
                   placeholder="0.00" required>

            <div id="tipoAbonoWrap">
                <label for="tipo_abono">Tipo de abono <span style="color:#e53935;">*</span></label>
                <select name="tipo_abono" id="tipo_abono" required onchange="cambiarTipoAbono(this.value)">
                    <option value="en_linea">En línea (MercadoPago)</option>
                    <option value="efectivo" id="opcionEfectivo" style="display:none;">Efectivo</option>
                </select>
            </div>

            <div id="avisoEfectivoAbono" style="display:none;" class="aviso-efectivo">
                <i class="bi bi-info-circle-fill"></i>
                Tu abono en efectivo quedará <strong>Pendiente</strong> hasta que el administrador lo confirme.
            </div>

            <div id="referenciaWrap" style="display:none;">
                <label for="referencia_abono">Referencia (opcional)</label>
                <input type="text" name="referencia" id="referencia_abono" placeholder="Número de comprobante">
            </div>

            <button type="submit" class="btn-abono-submit">
                <i class="bi bi-check-lg"></i> <span id="textoSubmitAbono">Registrar Abono</span>
            </button>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MODAL VER HISTORIAL DE ABONOS
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalVerAbonos">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-list-ul" style="color:#6a1b9a;"></i> Historial de Abonos</h3>
            <button class="modal-close" onclick="cerrarModal('modalVerAbonos')">×</button>
        </div>
        <p id="tituloVerAbonos" style="color:#718096;font-size:13px;margin-bottom:12px;"></p>
        <div class="abonos-list" id="listaAbonos">
            <p style="text-align:center;color:#9e9e9e;padding:20px;">Cargando...</p>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     MODAL EDITAR CONCEPTO (admin/sensei)
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal-overlay" id="modalEditConcepto">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="bi bi-pencil-square" style="color:#1565c0;"></i> Editar Concepto</h3>
            <button class="modal-close" onclick="cerrarModal('modalEditConcepto')">×</button>
        </div>
        <form id="formEditConcepto" method="POST" action="">
            @csrf
            @method('PUT')

            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:600;color:#4a5568;margin-bottom:4px;display:block;">
                    Nombre <span style="color:#e53935;">*</span>
                </label>
                <input type="text" name="nombre" id="edit_nombre"
                       style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box;"
                       required>
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:600;color:#4a5568;margin-bottom:4px;display:block;">
                    Monto Sugerido
                </label>
                <input type="number" step="0.01" name="monto_sugerido" id="edit_monto"
                       placeholder="0.00 (dejar vacío si no aplica)"
                       style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:14px;">
                <label style="font-size:13px;font-weight:600;color:#4a5568;margin-bottom:4px;display:block;">
                    Descripción
                </label>
                <input type="text" name="descripcion" id="edit_desc"
                       placeholder="Opcional"
                       style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;box-sizing:border-box;">
            </div>

            <div style="margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                <input type="checkbox" name="activo" id="edit_activo" value="1"
                       style="width:18px;height:18px;accent-color:#e53935;">
                <label for="edit_activo" style="font-size:14px;font-weight:600;color:#2d3748;cursor:pointer;">
                    Concepto activo (aparece en el formulario de pago)
                </label>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditConcepto')"
                        style="flex:1;padding:12px;">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" style="flex:1;padding:12px;">
                    <i class="bi bi-check-lg"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<form id="formEliminarPago" method="POST" action="" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<form id="formSuspenderPago" method="POST" action="" style="display:none;">
    @csrf
    @method('PATCH')
</form>

<script>
    const ROL_USUARIO = '{{ $user->rol }}';

    document.addEventListener('DOMContentLoaded', function () {
        @if(session('sessionInsertado'))
            Swal.fire({
                icon:              '{{ session('sessionInsertado') == 'true' ? 'success' : 'error' }}',
                title:             '{{ addslashes(session('mensaje')) }}',
                showConfirmButton: false,
                timer:             3000,
            });
        @endif

        document.getElementById('filterEstado').addEventListener('change', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#pagosTable tbody tr').forEach(row => {
                row.style.display = (!val || row.textContent.toLowerCase().includes(val)) ? '' : 'none';
            });
        });

        document.getElementById('searchInput').addEventListener('keyup', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('#pagosTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
            });
        });

        const selectAdmin = document.getElementById('id_concepto_admin');
        if (selectAdmin) {
            selectAdmin.addEventListener('change', function () {
                const opt  = this.options[this.selectedIndex];
                const monto = opt.dataset.monto;
                const hint  = document.getElementById('conceptoHintAdmin');

                if (monto && parseFloat(monto) > 0) {
                    hint.innerHTML = `Monto del concepto: <strong>$${parseFloat(monto).toFixed(2)}</strong>`;
                } else {
                    hint.innerHTML = opt.value ? 'Este concepto no tiene monto definido.' : '';
                }
            });

            if (selectAdmin.value) selectAdmin.dispatchEvent(new Event('change'));
        }

        const selectAlumno = document.getElementById('id_concepto_alumno');
        if (selectAlumno) {
            selectAlumno.addEventListener('change', function () {
                const opt   = this.options[this.selectedIndex];
                const monto = opt.dataset.monto;
                const hint  = document.getElementById('conceptoHintAlumno');
                const campo = document.getElementById('monto_alumno');
                if (monto) {
                    campo.value = monto;
                    hint.innerHTML = `Monto sugerido: <strong>$${parseFloat(monto).toFixed(2)}</strong>. Puedes reducirlo si vas a hacer un abono parcial.`;
                } else {
                    hint.innerHTML = opt.value ? 'Sin monto sugerido. Ingresa el monto que vas a pagar.' : '';
                }
            });
        }

        const chkAdmin = document.getElementById('pagarEnLinea');
        if (chkAdmin) {
            chkAdmin.addEventListener('change', function () {
                const btn     = document.getElementById('btnSubmitAdmin');
                const estado  = document.getElementById('estadoWrapAdmin');
                const refWrap = document.getElementById('refAdminWrap');
                if (this.checked) {
                    btn.innerHTML             = '<i class="bi bi-credit-card-2-front-fill"></i> Crear cargo y Pagar en línea';
                    btn.style.backgroundColor = '#009ee3';
                    btn.style.borderColor     = '#009ee3';
                    if (estado)  estado.style.opacity = '0.4';
                    if (refWrap) refWrap.style.display = 'none';
                } else {
                    btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Cargo';
                    btn.style.backgroundColor = '';
                    btn.style.borderColor     = '';
                    if (estado)  estado.style.opacity = '1';
                    if (refWrap) refWrap.style.display = '';
                }
            });
        }

        const tipoAbono = document.getElementById('tipo_abono');
        if (tipoAbono) {
            tipoAbono.addEventListener('change', function () {
                cambiarTipoAbono(this.value);
            });
        }
    });

    function activarTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    function mostrarAvisoEfectivo(valor) {
        const aviso = document.getElementById('avisoEfectivo');
        if (!aviso) return;
        const select  = document.getElementById('id_tipo_pago_alumno');
        const opt     = select ? select.options[select.selectedIndex] : null;
        const nombre  = opt ? opt.dataset.nombre : '';
        const esEnLinea = document.getElementById('pagarEnLineaAlumno')?.checked;
        aviso.style.display = (!esEnLinea && nombre === 'efectivo') ? 'block' : 'none';
    }

    function toggleEnLineaAlumno(chk) {
        const btn   = document.getElementById('btnSubmitAlumno');
        const aviso = document.getElementById('avisoEfectivo');
        if (chk.checked) {
            btn.innerHTML             = '<i class="bi bi-credit-card-2-front-fill"></i> Ir a MercadoPago';
            btn.style.backgroundColor = '#009ee3';
            btn.style.borderColor     = '#009ee3';
            if (aviso) aviso.style.display = 'none';
        } else {
            btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Pago';
            btn.style.backgroundColor = '';
            btn.style.borderColor     = '';
            const select = document.getElementById('id_tipo_pago_alumno');
            mostrarAvisoEfectivo(select ? select.value : '');
        }
    }

    // ── Abrir modal de abono ──────────────────────────────────────────
    function abrirModalAbono(idPago, nombreAlumno, montoTotal, montoPagado, saldo, rol) {
        document.getElementById('abonoAlumnoNombre').textContent = nombreAlumno;
        document.getElementById('abonoMontoTotal').textContent   = '$' + parseFloat(montoTotal).toFixed(2);
        document.getElementById('abonoMontoPagado').textContent  = '$' + parseFloat(montoPagado).toFixed(2);
        document.getElementById('abonoSaldo').textContent        = '$' + parseFloat(saldo).toFixed(2);

        // Poner el action correcto con la ruta de Laravel
        const form = document.getElementById('formAbono');
        form.action = '{{ url("pagos") }}/' + idPago + '/abono';

        // Opción efectivo: visible siempre en el modal de abono
        const opcionEfectivo = document.getElementById('opcionEfectivo');
        if (opcionEfectivo) opcionEfectivo.style.display = '';

        // Resetear el select al valor por defecto (en_linea) y disparar cambio visual
        const selectTipo = document.getElementById('tipo_abono');
        if (selectTipo) {
            selectTipo.value = 'en_linea';
            cambiarTipoAbono('en_linea');
        }

        // Limpiar el campo de monto
        const montoInput = document.getElementById('monto_abono');
        if (montoInput) montoInput.value = '';

        // Limpiar referencia
        const refInput = document.getElementById('referencia_abono');
        if (refInput) refInput.value = '';

        abrirModal('modalAbono');
    }

    // ── Cambiar tipo de abono (efectivo / en_linea) ───────────────────
    function cambiarTipoAbono(valor) {
        const referenciaWrap = document.getElementById('referenciaWrap');
        const avisoEfectivo  = document.getElementById('avisoEfectivoAbono');
        const textoBtn       = document.getElementById('textoSubmitAbono');
        const btn            = document.querySelector('.btn-abono-submit');

        if (valor === 'efectivo') {
            if (referenciaWrap) referenciaWrap.style.display = '';
            if (avisoEfectivo)  avisoEfectivo.style.display  = '';
            if (textoBtn)       textoBtn.textContent          = 'Registrar Abono en Efectivo';
            if (btn) { btn.style.backgroundColor = '#e53935'; btn.style.borderColor = '#e53935'; }
        } else {
            if (referenciaWrap) referenciaWrap.style.display = 'none';
            if (avisoEfectivo)  avisoEfectivo.style.display  = 'none';
            if (textoBtn)       textoBtn.textContent          = 'Ir a MercadoPago';
            if (btn) { btn.style.backgroundColor = '#009ee3'; btn.style.borderColor = '#009ee3'; }
        }
    }

    // ── Ver historial de abonos ───────────────────────────────────────
    function verAbonos(idPago, concepto) {
        document.getElementById('tituloVerAbonos').textContent = 'Abonos del pago: ' + concepto;
        document.getElementById('listaAbonos').innerHTML =
            '<p style="text-align:center;color:#9e9e9e;padding:20px;">Cargando...</p>';

        abrirModal('modalVerAbonos');

        fetch('{{ url("pagos") }}/' + idPago + '/abonos', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(abonos => {
            const lista = document.getElementById('listaAbonos');
            if (!abonos.length) {
                lista.innerHTML = '<p style="text-align:center;color:#9e9e9e;padding:20px;">Sin abonos registrados.</p>';
                return;
            }
            lista.innerHTML = abonos.map(a => `
                <div class="abono-item">
                    <div>
                        <span class="abono-tipo-badge tipo-${a.tipo_abono}">${a.tipo_abono === 'en_linea' ? 'En línea' : 'Efectivo'}</span>
                        <small style="display:block;color:#718096;margin-top:2px;">${a.fecha_abono ? a.fecha_abono.substring(0,10) : '—'}</small>
                        ${a.referencia ? `<small style="color:#9e9e9e;">${a.referencia}</small>` : ''}
                        ${a.registrado_por_nombre ? `<small style="color:#b0bec5;display:block;">Por: ${a.registrado_por_nombre}</small>` : ''}
                    </div>
                    <strong style="color:#e53935;">$${parseFloat(a.monto_abono).toFixed(2)}</strong>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('listaAbonos').innerHTML =
                '<p style="text-align:center;color:#e53935;padding:20px;">Error al cargar los abonos.</p>';
        });
    }

    // ── Abrir / cerrar modales ────────────────────────────────────────
    function abrirModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function cerrarModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    // Cerrar modal al hacer clic fuera del box
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) cerrarModal(this.id);
        });
    });

    // ── Confirmar completar pago ──────────────────────────────────────
    function confirmarCompletar(idPago, concepto) {
        Swal.fire({
            title: '¿Marcar como completado?',
            html: `Se marcará el pago de <strong>${concepto}</strong> como completado.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2e7d32',
            cancelButtonColor: '#9e9e9e',
            confirmButtonText: 'Sí, completar',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('formCompletar-' + idPago).submit();
            }
        });
    }

    // ── Confirmar eliminar pago (admin) ───────────────────────────────
    function confirmarEliminar(idPago, concepto) {
        Swal.fire({
            title: '¿Eliminar pago?',
            html: `Se eliminará el pago <strong>${concepto}</strong>. Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c62828',
            cancelButtonColor: '#9e9e9e',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.getElementById('formEliminarPago');
                form.action = '{{ url("pagos") }}/' + idPago;
                form.submit();
            }
        });
    }

    // ── Confirmar suspender pago (sensei) ─────────────────────────────
    function confirmarSuspender(idPago, concepto) {
        Swal.fire({
            title: '¿Suspender pago?',
            html: `Se suspenderá el pago <strong>${concepto}</strong>.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f57f17',
            cancelButtonColor: '#9e9e9e',
            confirmButtonText: 'Sí, suspender',
            cancelButtonText: 'Cancelar',
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.getElementById('formSuspenderPago');
                form.action = '{{ url("pagos") }}/' + idPago + '/suspender';
                form.submit();
            }
        });
    }

    // ── Abrir modal editar concepto ───────────────────────────────────
    function abrirEditConcepto(id, nombre, descripcion, monto, activo) {
        document.getElementById('edit_nombre').value  = nombre;
        document.getElementById('edit_desc').value    = descripcion;
        document.getElementById('edit_monto').value   = monto || '';
        document.getElementById('edit_activo').checked = activo == 1;

        const form = document.getElementById('formEditConcepto');
        form.action = '{{ url("conceptos-pago") }}/' + id;

        abrirModal('modalEditConcepto');
    }

    // ── Reset form alumno ─────────────────────────────────────────────
    function resetFormAlumno() {
        document.getElementById('conceptoHintAlumno').innerHTML = '';
        document.getElementById('avisoEfectivo').style.display  = 'none';
        const btn = document.getElementById('btnSubmitAlumno');
        btn.innerHTML             = '<i class="bi bi-check-lg"></i> Registrar Pago';
        btn.style.backgroundColor = '';
        btn.style.borderColor     = '';
    }

    // ── Sección tutor colapsable ───────────────────────────────────────
    function toggleTutorSection() {
        const body    = document.getElementById('tutorSectionBody');
        const chevron = document.getElementById('tutorChevron');
        if (!body) return;
        const isOpen = body.classList.toggle('open');
        chevron.classList.toggle('open', isOpen);
    }
</script>

</body>
</html>