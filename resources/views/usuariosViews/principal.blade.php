<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sistema de Gestión de Dojo</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    
    {{-- Menú Lateral Compacto --}}
    @include('includes.menu')

    <div class="top-bar">
        <div class="top-bar-title">
            <i class="bi bi-grid-fill"></i>
            Sistema de Gestión de Dojo
        </div>
        <div class="breadcrumb-top">
            Bienvenido, {{ Auth::user()->nombre ?? 'Usuario del Sistema' }}
        </div>
    </div>

    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Bienvenido al Panel de Control</h1>
            <p class="hero-subtitle">
                Revisa las estadísticas clave del Dojo y accede a las secciones de gestión rápidamente.
            </p>

            <div class="stats-container">
                <div class="stat-card-hero">
                    {{-- Usamos la variable de Laravel para el valor --}}
                    <span class="stat-number" data-target="{{ $totalAlumnos ?? 120 }}">0</span>
                    <span class="stat-label">Alumnos Activos</span>
                </div>
                <div class="stat-card-hero">
                    <span class="stat-number" data-target="{{ $mesesTrayectoria ?? 15 }}">0</span>
                    <span class="stat-label">Años de Trayectoria</span>
                </div>
                <div class="stat-card-hero">
                    <span class="stat-number" data-target="{{ $totalMaestros ?? 3 }}">0</span>
                    <span class="stat-label">Maestros / Tutores</span>
                </div>
            </div>

            <div class="cta-buttons">
                <a href="{{ route('alumnos.index') }}" class="btn-cta btn-primary-hero">
                    <i class="bi bi-person-badge-fill"></i>
                    Gestión de Alumnos
                </a>
                <a href="#features" class="btn-cta btn-secondary-hero">
                    <i class="bi bi-cash-coin"></i>
                    Ver Módulo de Pagos
                </a>
            </div>
        </div>

        <div class="scroll-indicator">
            <i class="bi bi-chevron-down"></i>
        </div>
    </section>

    <section class="features-section" id="features">
        <div class="features-container">
            <h2 class="section-title">Atajos y Módulos Principales</h2>
            <p class="section-subtitle">
                Accede rápidamente a las funciones clave del sistema.
            </p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <h3 class="feature-title">Gestión de Tutores</h3>
                    <p class="feature-description">
                        Registra y administra la información de los tutores y responsables de alumnos.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <h3 class="feature-title">Registrar Pagos</h3>
                    <p class="feature-description">
                        Registro rápido de pagos de mensualidades, inscripciones y otros servicios.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <h3 class="feature-title">Asistencias</h3>
                    <p class="feature-description">
                        Controla la asistencia diaria y genera reportes de puntualidad por alumno.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                    </div>
                    <h3 class="feature-title">Generar Reportes</h3>
                    <p class="feature-description">
                        Exporta informes detallados de desempeño, pagos y asistencia.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-bell-fill"></i>
                    </div>
                    <h3 class="feature-title">Alertas y Notificaciones</h3>
                    <p class="feature-description">
                        Recibe avisos sobre pagos vencidos y cumpleaños próximos.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-gear-fill"></i>
                    </div>
                    <h3 class="feature-title">Configuración</h3>
                    <p class="feature-description">
                        Administra cinturones, grados, horarios de clase y usuarios del sistema.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Script de Animación --}}
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animación de números
        function animateNumber(element) {
            const target = parseInt(element.dataset.target);
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target + (target >= 100 ? '+' : '');
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current) + (target >= 100 ? '+' : '');
                }
            }, 30);
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    document.querySelectorAll('.stat-number').forEach(animateNumber);
                    observer.unobserve(entry.target);
                }
            });
        });

        observer.observe(document.querySelector('.stats-container'));
    </script>
</body>
</html>