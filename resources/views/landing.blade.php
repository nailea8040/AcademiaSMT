<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dojo Karate-do | Academia</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">

    <style>
        :root {
            --karate-red: #e85654; /* Color de Hero.tsx */
            --karate-dark: #4A4A4A; /* Color de Header.tsx */
        }
        .section-padding { padding: 80px 0; }
        .btn-modern {
    background: linear-gradient(135deg, var(--karate-red), #d43f3d);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 15px 40px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(232, 86, 84, 0.4);
    position: relative;
    overflow: hidden;
}

.btn-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.btn-modern:hover::before {
    left: 100%;
}

.btn-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(232, 86, 84, 0.6);
    color: white;
}
        .btn-outline-modern {
    background: transparent;
    color: white;
    border: 2px solid white;
    border-radius: 50px;
    padding: 15px 40px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-outline-modern:hover {
    background: white;
    color: var(--karate-dark);
    transform: translateY(-3px);
}

        /* Hero Section (Adaptado de Hero.tsx) */
        .hero-custom {
            height: 85vh;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), 
                        url('https://images.unsplash.com/photo-1579331844418-fcd67e29b3d6?q=80&w=1080');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top" style="background-color: var(--karate-dark);">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white fw-bold" href="#inicio">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background-color: var(--karate-red);">
                    <span style="font-size: 1.2rem;">道</span>
                </div>
                ACADEMIA SMT
            </a>
            <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-3">
                    <li class="nav-item"><a class="nav-link text-white" href="#inicio">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#nosotros">Nosotros</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#galeria">Galería</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#clases">Clases</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="#horarios">Horarios</a></li>
                    <li class="nav-item"><a class="btn btn-modern btn-sm" href="#contacto">Contacto</a></li>
                    <li class="nav-item border-start ps-3">
                        <a class="nav-link text-white" href="{{ route('login') }}"><i class="bi bi-lock"></i> Acceso</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section id="inicio" class="hero-custom">
        <div class="container">
            <h1 class="display-3 fw-bold mb-4">Descubre el Camino del Samurai</h1>
            <p class="lead mb-5 mx-auto opacity-75" style="max-width: 750px;">
                Entrena cuerpo y mente en nuestro dojo. Aprende karate tradicional con un enfoque moderno para todas las edades.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#clases" class="btn btn-modern btn-lg">Ver Clases</a>
                <a href="#contacto" class="btn btn-outline-modern btn-lg">Contacto</a>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <section id="nosotros" class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-5" style="color: var(--karate-dark);">Sobre Nosotros</h2>
                <div class="mx-auto" style="width: 80px; height: 4px; background-color: var(--karate-red); margin-top: 10px;"></div>
                <p class="mt-4 text-muted mx-auto" style="max-width: 700px;">
                    Somos un dojo dedicado a la enseñanza del karate, con más de 20 años de experiencia formando campeones y, sobre todo, mejores personas.
                </p>
            </div>

            <div class="row g-4 align-items-center mb-5">
                <div class="col-lg-6">
                    <div class="rounded-4 overflow-hidden shadow-lg" style="height: 400px;">
                        <img src="img/SMT.png" class="w-100 h-100 object-fit-cover" alt="Entrenamiento de Karate">
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h3 class="fw-bold mb-4" style="color: var(--karate-dark);">Nuestra Historia</h3>
                    <p class="text-muted">Fundado en 2010, nuestro dojo nació con la misión de preservar y enseñar el karate en su forma más pura, adaptándolo a las necesidades modernas sin perder su esencia.</p>
                    <p class="text-muted">Hemos formado a estudiantes, desde niños hasta adultos, ayudándoles a desarrollar confianza, disciplina y habilidades para la vida a través del arte marcial.</p>
                </div>
            </div>

           <div class="row g-4 fade-in">
                <div class="col-md-6 col-lg-3">
                    <div class="card-modern text-center">
                        <div class="icon-box mx-auto">
                            <i class="bi bi-bullseye"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Disciplina</h5>
                        <p class="text-muted small mb-0">Formamos personas con autocontrol y determinación.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card-modern text-center">
                        <div class="icon-box mx-auto">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Respeto</h5>
                        <p class="text-muted small mb-0">Cultivamos valores de respeto mutuo y compañerismo.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card-modern text-center">
                        <div class="icon-box mx-auto">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Excelencia</h5>
                        <p class="text-muted small mb-0">Buscamos la mejora continua en cada entrenamiento.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card-modern text-center">
                        <div class="icon-box mx-auto">
                            <i class="bi bi-heart-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-3">Pasión</h5>
                        <p class="text-muted small mb-0">Transmitimos el amor por el arte marcial japonés.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="galeria" class="section-padding" style="background: linear-gradient(135deg, #d43f3d 0%, #1B263B 100%); position: relative; overflow: hidden;">
    <!-- Patrón decorativo -->
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background-image: url('data:image/svg+xml,<svg width=\"60\" height=\"60\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M30 0l30 30-30 30L0 30z\" fill=\"white\"/></svg>'); background-size: 60px 60px;"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div class="text-center mb-5">
            <span class="badge rounded-pill px-4 py-2 mb-3" style="background: rgba(255,255,255,0.2); color: white; font-size: 0.9rem; font-weight: 700; letter-spacing: 1px;">
                NUESTROS MOMENTOS
            </span>
            <h2 class="display-4 fw-bold text-white mb-3">
                Galería <span style="color: #ffd700;">Multimedia</span>
            </h2>
            <p class="text-white mx-auto opacity-75" style="max-width: 650px; font-size: 1.1rem;">
                Explora los momentos más destacados de nuestros entrenamientos, torneos y la vida en el Dojo
            </p>
        </div>

        <!-- Grid Moderno de Galería -->
        <div class="row g-4 mb-5">
            @php
                // Obtener últimos 6 archivos de la galería
                $galeriaItems = DB::table('evento')
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
                    ->get();
            @endphp

            @forelse($galeriaItems as $index => $item)
            <div class="col-md-6 col-lg-4">
                <div class="gallery-card" style="position: relative; border-radius: 25px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); transition: all 0.4s ease; cursor: pointer; height: 350px;" 
                     onmouseover="this.style.transform='translateY(-10px) scale(1.02)'; this.style.boxShadow='0 30px 80px rgba(0,0,0,0.4)';" 
                     onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 20px 60px rgba(0,0,0,0.3)';">
                    
                    @if($item->tipo === 'imagen')
                        <img src="{{ asset('storage/' . $item->ruta) }}" 
                             alt="{{ $item->titulo }}" 
                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;">
                    @else
                        <div style="width: 100%; height: 100%; position: relative; background: #000;">
                            <video style="width: 100%; height: 100%; object-fit: cover;">
                                <source src="{{ asset('storage/' . $item->ruta) }}" type="video/mp4">
                            </video>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255,255,255,0.9); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-play-fill" style="font-size: 2rem; color: var(--karate-dark); margin-left: 5px;"></i>
                            </div>
                        </div>
                    @endif

                    <!-- Overlay con información -->
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 1.5rem; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); color: white; transform: translateY(100%); transition: transform 0.3s ease;" 
                         onmouseenter="this.style.transform='translateY(0)';" 
                         onmouseleave="this.style.transform='translateY(100%)';">
                        <h5 class="fw-bold mb-2">{{ $item->titulo }}</h5>
                        <p class="small mb-0 opacity-75">
                            <i class="bi bi-calendar3 me-2"></i>
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}
                        </p>
                    </div>

                    <!-- Badge de tipo -->
                    <div style="position: absolute; top: 1rem; right: 1rem; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); color: white; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.85rem; font-weight: 700;">
                        @if($item->tipo === 'imagen')
                            <i class="bi bi-image-fill me-1"></i> Foto
                        @else
                            <i class="bi bi-play-circle-fill me-1"></i> Video
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <!-- Estado vacío con estilo -->
            <div class="col-12">
                <div class="text-center py-5">
                    <div style="width: 120px; height: 120px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
                        <i class="bi bi-images" style="font-size: 3rem; color: white; opacity: 0.5;"></i>
                    </div>
                    <h4 class="text-white fw-bold mb-3">Próximamente</h4>
                    <p class="text-white opacity-75">Estamos preparando contenido increíble para compartir contigo</p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Botón para ver toda la galería -->
        @if($galeriaItems->count() > 0)
        <div class="text-center">
            <a href="{{ route('galeria.index') }}" class="btn btn-lg rounded-pill px-5 py-3 fw-bold" style="background: white; color: var(--karate-dark); box-shadow: 0 15px 40px rgba(0,0,0,0.3); transition: all 0.3s ease;" 
               onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 20px 50px rgba(0,0,0,0.4)';" 
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 15px 40px rgba(0,0,0,0.3)';">
                <i class="bi bi-grid-3x3-gap me-2"></i>
                Ver Galería Completa
                <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
        @endif
    </div>
</section>


    <section id="clases" class="section-padding bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="text-karate fw-bold text-uppercase" style="letter-spacing: 2px;">Nuestras Clases</span>
            <h2 class="fw-bold display-4 mt-2">Formación Integral</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">Selecciona el programa que mejor se adapte a tus objetivos y nivel de experiencia.</p>
        </div>

        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="card program-card h-100 p-4">
                    <div class="program-icon-wrapper">
                        <i class="bi bi-person-arms-up"></i>
                    </div>
                    <div class="card-body text-center p-0">
                        <h3 class="program-title h4 mb-2">Karate Kids</h3>
                        <span class="badge bg-dark rounded-pill mb-4 px-3">4 - 12 AÑOS</span>
                        <p class="text-muted small">Fomentamos la disciplina y el respeto a través del movimiento y el juego.</p>
                        
                        <ul class="program-benefit-list text-start">
                            <li><i class="bi bi-check-circle-fill"></i> Coordinación motriz</li>
                            <li><i class="bi bi-check-circle-fill"></i> Manejo de bullying</li>
                            <li><i class="bi bi-check-circle-fill"></i> Concentración escolar</li>
                        </ul>
                        
                        <a href="#contacto" class="btn btn-outline-danger w-100 mt-4 rounded-pill">Más Información</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card program-card h-100 p-4 shadow-lg border-danger" style="border: 2px solid var(--karate-red) !important;">
                    
                    <div class="program-icon-wrapper" style="background: var(--karate-red); color: white;">
                        <i class="bi bi-fire"></i>
                    </div>
                    <div class="card-body text-center p-0">
                        <h3 class="program-title h4 mb-2">Adultos</h3>
                        <span class="badge bg-dark rounded-pill mb-4 px-3">18+ AÑOS</span>
                        <p class="text-muted small">Entrenamiento de alto impacto para defensa personal y salud mental.</p>
                        
                        <ul class="program-benefit-list text-start">
                            <li><i class="bi bi-check-circle-fill"></i> Defensa personal real</li>
                            <li><i class="bi bi-check-circle-fill"></i> Reducción de estrés</li>
                            <li><i class="bi bi-check-circle-fill"></i> Acondicionamiento físico</li>
                        </ul>
                        
                       <a href="#contacto" class="btn btn-outline-danger w-100 mt-4 rounded-pill">Inscribirme Ahora</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="card program-card h-100 p-4">
                    <div class="program-icon-wrapper">
                        <i class="bi bi-award"></i>
                    </div>
                    <div class="card-body text-center p-0">
                        <h3 class="program-title h4 mb-2">Alto Rendimiento</h3>
                        <span class="badge bg-dark rounded-pill mb-4 px-3">COMPETICIÓN</span>
                        <p class="text-muted small">Preparación técnica y física para atletas de nivel competitivo.</p>
                        
                        <ul class="program-benefit-list text-start">
                            <li><i class="bi bi-check-circle-fill"></i> Kumite deportivo</li>
                            <li><i class="bi bi-check-circle-fill"></i> Katas de competencia</li>
                            <li><i class="bi bi-check-circle-fill"></i> Viajes a torneos</li>
                        </ul>
                        
                        <a href="#contacto" class="btn btn-outline-danger w-100 mt-4 rounded-pill">Ver Requisitos</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

   <section id="horarios" class="section-padding" style="background-color: #fcfcfc;">
    <div class="container">
        <div class="row align-items-end mb-5">
            <div class="col-md-8">
                <span class="text-karate fw-bold text-uppercase" style="letter-spacing: 2px;">Planifica tu entreno</span>
                <h2 class="fw-bold display-5 mt-2">Horarios Semanales</h2>
            </div>
            <div class="col-md-4 text-md-end">
                <p class="text-muted small"><i class="bi bi-info-circle me-2"></i>Sujetos a cambios según nivel de cinta.</p>
            </div>
        </div>

        <div class="row g-4">
            @php
                $dias = [
                    ['nombre' => 'Lunes', 'icono' => 'bi-calendar-date', 'clases' => [
                        ['16:00 - 17:00', 'Karate Niños (4-8 años)'],
                        ['17:30 - 18:30', 'Karate Niños (9-12 años)'],
                        ['19:00 - 20:30', 'Karate Adultos']
                    ]],
                    ['nombre' => 'Martes', 'icono' => 'bi-calendar-date', 'clases' => [
                        ['17:00 - 18:00', 'Karate Adolescentes'],
                        ['18:30 - 20:00', 'Karate Avanzado']
                    ]],
                    ['nombre' => 'Miércoles', 'icono' => 'bi-calendar-date', 'clases' => [
                        ['16:00 - 17:00', 'Karate Niños (4-8 años)'],
                        ['17:30 - 18:30', 'Karate Niños (9-12 años)'],
                        ['19:00 - 20:30', 'Karate Adultos']
                    ]],
                    ['nombre' => 'Jueves', 'icono' => 'bi-calendar-date', 'clases' => [
                        ['16:00 - 17:00', 'Karate Niños (4-8 años)'],
                        ['17:30 - 18:30', 'Karate Niños (9-12 años)'],
                        ['19:00 - 20:30', 'Karate Adultos']
                    ]],
                    ['nombre' => 'Viernes', 'icono' => 'bi-calendar-date', 'clases' => [
                        ['16:00 - 17:00', 'Karate Niños (4-8 años)'],
                        ['17:30 - 18:30', 'Karate Niños (9-12 años)'],
                        ['19:00 - 20:30', 'Karate Adultos']
                    ]],
                    ['nombre' => 'Sabado', 'icono' => 'bi-calendar-date', 'clases' => [
                        ['16:00 - 17:00', 'Karate Niños (4-8 años)'],
                        ['17:30 - 18:30', 'Karate Niños (9-12 años)'],
                        ['19:00 - 20:30', 'Karate Adultos']
                    ]]
                ];
            @endphp

            @foreach($dias as $index => $dia)
            <div class="col-lg-4 col-md-6">
                <div class="card schedule-card h-100 shadow-sm {{ $index == 0 ? 'active-day' : '' }}">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="fw-bold m-0" style="color: var(--karate-dark);">{{ $dia['nombre'] }}</h4>
                            <i class="bi {{ $dia['icono'] }} schedule-icon"></i>
                        </div>
                        
                        <div class="sessions-list">
                            @foreach($dia['clases'] as $clase)
                            <div class="session-item mb-4 last-child-mb-0">
                                <div class="time-badge mb-2">
                                    <i class="bi bi-clock me-2"></i>{{ $clase[0] }}
                                </div>
                                <p class="class-name">{{ $clase[1] }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-5 p-4 rounded-4 shadow-sm d-flex flex-column flex-md-row align-items-center justify-content-between" style="background-color: var(--karate-dark);">
            <div class="text-white text-center text-md-start mb-3 mb-md-0">
                <h4 class="fw-bold mb-1 text-white">¿Tienes dudas sobre los horarios?</h4>
                <p class="mb-0 text-white-50">Contáctanos y te ayudaremos a encontrar el horario perfecto para ti.</p>
            </div>
            <a href="#contacto" class="btn btn-modern px-4">Consultar ahora</a>
        </div>
    </div>
</section>

    
    <section id="instructores" class="section-padding bg-dark-custom">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold display-5 text-black">Nuestros Maestros</h2>
                <div class="mx-auto" style="width: 80px; height: 4px; background-color: var(--karate-red); margin-top: 10px;"></div>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-md-5 col-lg-4">
                    <div class="card border-0 shadow-lg overflow-hidden h-100">
                        <img src="https://images.unsplash.com/photo-1550759807-6419ff64a5e9?q=80&w=500" class="card-img-top object-fit-cover" style="height: 300px;" alt="Sensei Carlos">
                        <div class="card-body p-4 text-dark">
                            <h4 class="fw-bold mb-1">Sensei Carlos Martínez</h4>
                            <span class="badge mb-3" style="background-color: var(--karate-red);">6º Dan Cinta Negra</span>
                            <p class="small text-muted mb-3"><strong>Especialidad:</strong> Kata y Kumite</p>
                            <ul class="list-unstyled small">
                                <li class="mb-1"><i class="bi bi-patch-check-fill text-karate me-2"></i>Campeón Nacional 2015</li>
                                <li class="mb-1"><i class="bi bi-patch-check-fill text-karate me-2"></i>Certificado Internacional JKA</li>
                                <li><i class="bi bi-patch-check-fill text-karate me-2"></i>20 años de experiencia</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-5 col-lg-4">
                    <div class="card border-0 shadow-lg overflow-hidden h-100">
                        <img src="https://images.unsplash.com/photo-1725813961320-151288b4c4db?q=80&w=500" class="card-img-top object-fit-cover" style="height: 300px;" alt="Sensei Ana">
                        <div class="card-body p-4 text-dark">
                            <h4 class="fw-bold mb-1">Sensei Ana López</h4>
                            <span class="badge mb-3" style="background-color: var(--karate-red);">5º Dan Cinta Negra</span>
                            <p class="small text-muted mb-3"><strong>Especialidad:</strong> Karate Infantil</p>
                            <ul class="list-unstyled small">
                                <li class="mb-1"><i class="bi bi-patch-check-fill text-karate me-2"></i>Psicopedagogía del deporte</li>
                                <li class="mb-1"><i class="bi bi-patch-check-fill text-karate me-2"></i>Especialista en Psicomotricidad</li>
                                <li><i class="bi bi-patch-check-fill text-karate me-2"></i>15 años formando niños</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contacto" class="section-padding bg-light">
    <div class="container">
        <div class="row g-5 align-items-center">
            
            <div class="col-lg-7">
                <div class="contact-form-container">
                    <div class="mb-4">
                        <h2 class="fw-bold mb-2" style="color: var(--karate-dark);">Envíanos un mensaje</h2>
                        <p class="text-muted">¿Tienes alguna duda? Estamos listos para ayudarte en tu camino marcial.</p>
                    </div>

                    <form action="{{ route('contacto.enviar') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Escribe tu nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo" placeholder="ejemplo@correo.com" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Teléfono de Contacto</label>
                            <input type="tel" class="form-control" name="telefono" placeholder="10 dígitos" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">¿En qué podemos ayudarte?</label>
                            <textarea class="form-control" name="mensaje" rows="4" placeholder="Escribe tu mensaje aquí..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-send-contact w-100">
                                <i class="bi bi-send-fill me-2"></i> Enviar Mensaje
                            </button>
                        </div>
                    </form>
                </div>
            </div>

             <div class="col-lg-5 fade-in">
                    <div class="contact-info-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="contact-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Ubicación</h6>
                                <p class="small text-muted mb-0">Calle Estado de México, 46, San Martín Texmelucan, Puebla</p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-info-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="contact-icon">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Teléfono</h6>
                                <p class="small text-muted mb-0">+52 912 345 678</p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-info-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="contact-icon">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0">Horario</h6>
                                <p class="small text-muted mb-0">Lun - Vie: 15:00 - 21:00</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-4 overflow-hidden shadow-sm" style="height: 200px;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3741.7265123456!2d-98.3856!3d19.2895!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85cdc8e8b8e8b8b%3A0x1234567890!2sCalle%20Estado%20de%20M%C3%A9xico%2046%2C%20San%20Mart%C3%ADn%20Texmelucan!5e0!3m2!1ses!2smx!4v1234567890" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>

                    <div class="mt-3">
                        <a href="https://www.google.com/maps/search/Calle+Estado+de+M%C3%A9xico+46,+San+Mart%C3%ADn+Texmelucan,+Puebla/@19.2895,-98.3856,15z" target="_blank" class="btn btn-primary w-100 rounded-pill">
                            <i class="bi bi-map me-2"></i>Ver en Google Maps
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

    <footer class="footer-modern">
        <div class="container text-center">
            <div class="mb-4">
                <span class="text-white fs-4 fw-bold">Academia San Marín Texmelucan</span>
            </div>
            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="https://www.facebook.com/share/1GWJZ5VaGR/" class="social-icon">
                    <i class="bi bi-facebook"></i>
                </a>
                <a href="#" class="social-icon">
                    <i class="bi bi-instagram"></i>
                </a>
                <a href="#" class="social-icon">
                    <i class="bi bi-whatsapp"></i>
                </a>
            </div>
            <hr class="border-secondary opacity-25">
            <p class="text-secondary small mb-0">© 2025 Academia de Karate-do San Martín Texmelucan. Disciplina y Honor.</p>
        </div>
    </footer>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    const navHeight = document.querySelector('.navbar').offsetHeight;
                    window.scrollTo({
                        top: targetElement.offsetTop - navHeight,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>

</body>
</html>