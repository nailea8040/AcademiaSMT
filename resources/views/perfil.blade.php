<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Dojo Karate</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    
    <style>
        .profile-wrapper {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header del Perfil */
        .profile-header {
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
            border-radius: 25px;
            padding: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(229, 57, 53, 0.3);
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .profile-header-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .profile-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 5px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .profile-info h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .profile-role {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .profile-meta {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .profile-meta-item i {
            font-size: 1.2rem;
        }

        /* Tarjetas de Información */
        .info-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .info-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .section-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--color-primary-light-bg), rgba(229, 57, 53, 0.05));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary);
            font-size: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-primary-dark);
            margin: 0;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--color-primary);
        }

        .info-item:hover {
            background: #fff;
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .info-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #757575;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d2d2d;
            word-break: break-word;
        }

        /* Estado Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .status-badge.active {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
        }

        .status-badge.inactive {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse-status 2s infinite;
        }

        .status-indicator.active {
            background: #28a745;
        }

        .status-indicator.inactive {
            background: #dc3545;
        }

        @keyframes pulse-status {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card-profile {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            border-top: 4px solid var(--color-primary);
        }

        .stat-card-profile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-icon-profile {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.8rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #757575;
            font-weight: 600;
        }

        /* Botón de Editar */
        .btn-edit-profile {
            position: absolute;
            top: 2rem;
            right: 2rem;
            z-index: 3;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-edit-profile:hover {
            background: white;
            color: var(--color-primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        /* Timeline de Actividad */
        .activity-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--color-primary), transparent);
        }

        .activity-item {
            position: relative;
            padding: 1rem 0;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -2.5rem;
            top: 1.5rem;
            width: 15px;
            height: 15px;
            background: var(--color-primary);
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 3px var(--color-primary-light-bg);
        }

        .activity-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-header-content {
                flex-direction: column;
                text-align: center;
            }

            .profile-avatar-large {
                width: 120px;
                height: 120px;
                font-size: 3rem;
            }

            .profile-info h1 {
                font-size: 1.8rem;
            }

            .profile-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-edit-profile {
                position: static;
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    @include('includes.menu')

    <div class="main-content">
        <header class="header">
            <div>
                <h1 class="header-title">
                    <i class="bi bi-person-circle"></i>
                    Mi Perfil
                </h1>
                <div class="breadcrumb">
                    <a href="{{ route('principal') }}">Inicio</a>
                    <i class="bi bi-chevron-right"></i>
                    <span>Perfil</span>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <div class="profile-wrapper">
                
                <!-- Header del Perfil -->
                <div class="profile-header">
                    
                    <div class="profile-header-content">
                        <div class="profile-avatar-large">
                            {{ strtoupper(substr(auth()->user()->nombre, 0, 1) . substr(auth()->user()->apaterno, 0, 1)) }}
                        </div>
                        <div class="profile-info">
                            <h1>{{ auth()->user()->nombre }} {{ auth()->user()->apaterno }} {{ auth()->user()->amaterno }}</h1>
                            <div class="profile-role">
                                @php
                                    $roleIcon = [
                                        'admin' => 'bi-shield-fill-check',
                                        'sensei' => 'bi-award-fill',
                                        'tutor' => 'bi-person-lines-fill',
                                        'alumno' => 'bi-person-badge'
                                    ];
                                @endphp
                                <i class="bi {{ $roleIcon[auth()->user()->rol] ?? 'bi-person' }}"></i>
                                {{ ucfirst(auth()->user()->rol) }}
                            </div>
                            <div class="profile-meta">
                                <div class="profile-meta-item">
                                    <i class="bi bi-envelope-fill"></i>
                                    {{ auth()->user()->correo }}
                                </div>
                                <div class="profile-meta-item">
                                    <i class="bi bi-telephone-fill"></i>
                                    {{ auth()->user()->telefono }}
                                </div>
                                <div class="profile-meta-item">
                                    <i class="bi bi-calendar-check-fill"></i>
                                    Miembro desde {{ \Carbon\Carbon::parse(auth()->user()->fecha_registro)->format('M Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="stats-container">
                    <div class="stat-card-profile">
    <div class="stat-icon-profile">
        <i class="bi bi-calendar3"></i>
    </div>
    <div class="stat-value">
        @php
            $fechaRegistro = \Carbon\Carbon::parse(auth()->user()->fecha_registro);
            $ahora = now();
            $anios = (int) $fechaRegistro->diffInYears($ahora);
            $meses = (int) $fechaRegistro->diffInMonths($ahora);
            $dias = (int) $fechaRegistro->diffInDays($ahora);
        @endphp

        @if($anios >= 1)
            {{ $anios }} {{ $anios == 1 ? 'Año' : 'Años' }}
        @elseif($meses >= 1)
            {{ $meses }} {{ $meses == 1 ? 'Mes' : 'Meses' }}
        @else
            {{ $dias }} {{ $dias == 1 ? 'Día' : 'Días' }}
        @endif
    </div>
    <div class="stat-label">Tiempo en el Dojo</div>
</div>
                    
                    <div class="stat-card-profile">
                        <div class="stat-icon-profile">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="stat-value">{{ auth()->user()->estado == 1 ? 'Activo' : 'Inactivo' }}</div>
                        <div class="stat-label">Estado Actual</div>
                    </div>
                    
                    <div class="stat-card-profile">
                        <div class="stat-icon-profile">
                            <i class="bi bi-award"></i>
                        </div>
                        <div class="stat-value">{{ ucfirst(auth()->user()->rol) }}</div>
                        <div class="stat-label">Rol en el Sistema</div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Información Personal -->
                    <div class="col-lg-6">
                        <div class="info-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <h2 class="section-title">Información Personal</h2>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-person"></i>
                                        Nombre(s)
                                    </div>
                                    <div class="info-value">{{ auth()->user()->nombre }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-person"></i>
                                        Apellido Paterno
                                    </div>
                                    <div class="info-value">{{ auth()->user()->apaterno }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-person"></i>
                                        Apellido Materno
                                    </div>
                                    <div class="info-value">{{ auth()->user()->amaterno }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-cake2"></i>
                                        Fecha de Nacimiento
                                    </div>
                                    <div class="info-value">{{ \Carbon\Carbon::parse(auth()->user()->fecha_naci)->format('d/m/Y') }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-hourglass-split"></i>
                                        Edad
                                    </div>
                                    <div class="info-value">{{ \Carbon\Carbon::parse(auth()->user()->fecha_naci)->age }} años</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="col-lg-6">
                        <div class="info-section">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="bi bi-envelope"></i>
                                </div>
                                <h2 class="section-title">Información de Contacto</h2>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-envelope-at"></i>
                                        Correo Electrónico
                                    </div>
                                    <div class="info-value">{{ auth()->user()->correo }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-telephone"></i>
                                        Teléfono
                                    </div>
                                    <div class="info-value">{{ auth()->user()->telefono }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado de Cuenta -->
                        <div class="info-section mt-4">
                            <div class="section-header">
                                <div class="section-icon">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h2 class="section-title">Estado de Cuenta</h2>
                            </div>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-calendar-plus"></i>
                                        Fecha de Registro
                                    </div>
                                    <div class="info-value">{{ \Carbon\Carbon::parse(auth()->user()->fecha_registro)->format('d/m/Y') }}</div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-activity"></i>
                                        Estado Actual
                                    </div>
                                    <div class="info-value">
                                        <span class="status-badge {{ auth()->user()->estado == 1 ? 'active' : 'inactive' }}">
                                            <span class="status-indicator {{ auth()->user()->estado == 1 ? 'active' : 'inactive' }}"></span>
                                            {{ auth()->user()->estado == 1 ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="bi bi-person-badge"></i>
                                        Rol de Usuario
                                    </div>
                                    <div class="info-value">
                                        @php
                                            $badgeClass = [
                                                'admin' => 'badge-admin',
                                                'sensei' => 'badge-sensei',
                                                'tutor' => 'badge-tutor',
                                                'alumno' => 'badge-alumno'
                                            ];
                                        @endphp
                                        <span class="badge {{ $badgeClass[auth()->user()->rol] ?? 'badge-alumno' }}">
                                            {{ ucfirst(auth()->user()->rol) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente (Ejemplo) -->
                

            </div>
        </div>

        <footer class="footer">
            <p>© 2025 Sistema de Gestión del Dojo</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>