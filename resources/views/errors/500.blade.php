<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error del Servidor — Academia Karate</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700;800&display=swap');

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --red:        #e53935;
            --red-dark:   #d32f2f;
            --red-glow:   rgba(229,57,53,0.18);
            --black:      #1a1a1a;
            --gray:       #888;
            --gray-light: #f5f5f5;
            --white:      #ffffff;
        }

        html, body {
            height: 100%;
            font-family: 'Nunito', 'Segoe UI', sans-serif;
            background: var(--white);
            color: var(--black);
            overflow: hidden;
        }

        /* ── TOPBAR ─────────────────────────────────────────── */
        .topbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 64px;
            background: var(--white);
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            z-index: 100;
            animation: slideDown .5s ease both;
        }

        @keyframes slideDown {
            from { transform: translateY(-64px); opacity: 0; }
            to   { transform: translateY(0);     opacity: 1; }
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--black);
        }

        .brand-logo {
            width: 38px; height: 38px;
            background: var(--red);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-logo img { width: 100%; height: 100%; object-fit: cover; }

        .brand-text {
            display: flex; flex-direction: column;
            line-height: 1;
        }

        .brand-text span:first-child {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--gray);
        }

        .brand-text span:last-child {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 20px;
            letter-spacing: .06em;
            color: var(--red);
        }

        .nav-home {
            display: flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
            color: var(--gray);
            font-size: 14px;
            font-weight: 600;
            transition: color .2s;
        }

        .nav-home:hover { color: var(--red); }

        .nav-home svg { width: 16px; height: 16px; }

        /* ── MAIN ───────────────────────────────────────────── */
        .page {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 64px;
        }

        .card {
            background: var(--white);
            border-radius: 24px;
            box-shadow: 0 24px 80px rgba(0,0,0,.10), 0 2px 8px rgba(0,0,0,.04);
            padding: 56px 52px 48px;
            max-width: 520px;
            width: 90%;
            text-align: center;
            animation: popIn .6s cubic-bezier(.34,1.56,.64,1) .15s both;
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(.88) translateY(24px); }
            to   { opacity: 1; transform: scale(1)   translateY(0); }
        }

        /* ── ILLUSTRATION ───────────────────────────────────── */
        .illustration {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 36px;
        }

        /* Red circle — pulses */
        .sun {
            position: absolute;
            top: 6px; left: 50%;
            transform: translateX(-50%);
            width: 140px; height: 140px;
            background: var(--red);
            border-radius: 50%;
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%,100% { transform: translateX(-50%) scale(1);    box-shadow: 0 0 0 0 var(--red-glow); }
            50%      { transform: translateX(-50%) scale(1.06); box-shadow: 0 0 0 18px transparent; }
        }

        /* Decorative clouds */
        .clouds { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; }
        .cloud {
            position: absolute;
            background: #e0e0e0;
            border-radius: 50px;
            opacity: .55;
            animation: drift 8s ease-in-out infinite;
        }
        .cloud::before, .cloud::after {
            content: '';
            position: absolute;
            background: inherit;
            border-radius: 50%;
        }
        .c1 { width:52px; height:16px; top:18px; left:2px; }
        .c1::before { width:24px; height:24px; top:-12px; left:8px; }
        .c1::after  { width:18px; height:18px; top:-8px;  left:26px;}
        .c2 { width:42px; height:14px; top:24px; right:4px; animation-delay:-3s; animation-direction:reverse; }
        .c2::before { width:20px; height:20px; top:-10px; left:6px; }
        .c2::after  { width:14px; height:14px; top:-6px;  left:22px;}

        @keyframes drift {
            0%,100% { transform: translateX(0); }
            50%      { transform: translateX(8px); }
        }

        /* Server stack */
        .server-wrap {
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-62%);
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%,100% { transform: translateX(-62%) translateY(0); }
            50%      { transform: translateX(-62%) translateY(-8px); }
        }

        .server-stack { display: flex; flex-direction: column; gap: 4px; }

        .server-unit {
            width: 80px;
            height: 22px;
            background: #2a2a2a;
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 8px;
            gap: 4px;
            position: relative;
        }

        .server-unit::before {
            content: '';
            width: 34px; height: 4px;
            background: #444;
            border-radius: 2px;
        }

        .led {
            width: 5px; height: 5px;
            border-radius: 50%;
            margin-left: auto;
            animation: blink 1.4s step-start infinite;
        }

        .led-r  { background: var(--red);   animation-delay: 0s; }
        .led-y  { background: #f5a623;      animation-delay: .4s; }
        .led-r2 { background: var(--red);   animation-delay: .8s; }

        @keyframes blink {
            0%,49% { opacity: 1; }
            50%,100%{ opacity: .15; }
        }

        /* Warning badge */
        .warn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            width: 40px; height: 40px;
            animation: shake 2.5s ease-in-out infinite;
        }

        @keyframes shake {
            0%,90%,100% { transform: rotate(0deg); }
            92%          { transform: rotate(-8deg); }
            95%          { transform: rotate(8deg); }
            97%          { transform: rotate(-4deg); }
        }

        /* ── TEXT ───────────────────────────────────────────── */
        .error-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 42px;
            letter-spacing: .03em;
            color: var(--black);
            line-height: 1.05;
            margin-bottom: 14px;
        }

        .error-title span { color: var(--red); }

        .error-desc {
            font-size: 15px;
            color: var(--gray);
            line-height: 1.65;
            margin-bottom: 36px;
        }

        /* ── BUTTON ─────────────────────────────────────────── */
        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: linear-gradient(135deg, var(--red) 0%, var(--red-dark) 100%);
            color: var(--white);
            border: none;
            border-radius: 12px;
            padding: 15px 36px;
            font-family: 'Nunito', sans-serif;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: transform .18s, box-shadow .18s;
            box-shadow: 0 6px 20px var(--red-glow);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(229,57,53,.32);
        }

        .btn-home:active { transform: translateY(0); }

        .btn-home svg { width: 16px; height: 16px; }

        /* ── FOOTER NOTE ────────────────────────────────────── */
        .footer-note {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 12px;
            color: var(--gray);
            text-align: left;
        }

        .footer-note svg { flex-shrink: 0; margin-top: 1px; width: 14px; height: 14px; }

        /* ── RESPONSIVE ─────────────────────────────────────── */
        @media (max-width: 480px) {
            .topbar { padding: 0 18px; }
            .card   { padding: 40px 28px 36px; border-radius: 20px; }
            .error-title { font-size: 34px; }
            .illustration { width: 160px; height: 160px; }
            .sun { width: 112px; height: 112px; }
        }
    </style>
</head>
<body>

    <!-- TOP BAR -->
    <header class="topbar">
        <a href="/" class="brand">
            <div class="brand-logo">
                <img src="/img/logo.png" alt="Academia Karate logo">
            </div>
            <div class="brand-text">
                <span>Academia</span>
                <span>Karate</span>
            </div>
        </a>
        <a href="/" class="nav-home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Volver al inicio
        </a>
    </header>

    <!-- MAIN CONTENT -->
    <main class="page">
        <div class="card">

            <!-- ILLUSTRATION -->
            <div class="illustration">
                <div class="sun"></div>

                <div class="clouds">
                    <div class="cloud c1"></div>
                    <div class="cloud c2"></div>
                </div>

                <div class="server-wrap">
                    <div class="server-stack">
                        <div class="server-unit"><div class="led led-r"></div></div>
                        <div class="server-unit"><div class="led led-y"></div></div>
                        <div class="server-unit"><div class="led led-r2"></div></div>
                    </div>
                </div>

                <!-- Warning triangle SVG -->
                <svg class="warn" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="20,4 38,36 2,36" fill="#fff" stroke="#ccc" stroke-width="2.5"/>
                    <text x="20" y="31" text-anchor="middle" font-size="17" font-weight="700" fill="#555">!</text>
                </svg>
            </div>

            <!-- TEXT -->
            <h1 class="error-title">Error del <span>Servidor</span></h1>
            <p class="error-desc">
                Estamos teniendo problemas técnicos en este momento.<br>
                Por favor, inténtalo nuevamente más tarde.
            </p>

            <!-- BUTTON -->
            <a href="/" class="btn-home">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Volver al inicio
            </a>

            <!-- FOOTER NOTE -->
            <div class="footer-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                Si el problema persiste, contacta al administrador del sistema.
            </div>

        </div>
    </main>

</body>
</html>