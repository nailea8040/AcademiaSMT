<nav class="sidebar">
    <div class="sidebar-logo">空手</div>

    @auth
        @php
            $rol = Auth::user()->rol; // 'admin', 'sensei', 'tutor', 'alumno'
        @endphp

        <ul>
            {{-- Inicio — visible para todos --}}
            <li>
                <a href="{{ route('principal') }}"
                   title="Inicio"
                   class="{{ Request::routeIs('principal') ? 'activo' : '' }}">
                    <i class="bi bi-house-door"></i>
                </a>
            </li>

            {{-- Gestión de Usuarios — solo admin --}}
            @if($rol === 'admin')
            <li>
                <a href="{{ route('usuarios.index') }}"
                   title="Usuarios"
                   class="{{ Request::routeIs('usuarios.*') ? 'activo' : '' }}">
                    <i class="bi bi-people"></i>
                </a>
            </li>
            @endif

            {{-- Tutores — admin y sensei --}}
            @if(in_array($rol, ['admin', 'sensei']))
            <li>
                <a href="{{ route('tutor.index') }}"
                   title="Tutores"
                   class="{{ Request::routeIs('tutor.*') ? 'activo' : '' }}">
                    <i class="bi bi-person-lines-fill"></i>
                </a>
            </li>
            @endif

            {{-- Alumnos — admin y sensei --}}
            @if(in_array($rol, ['admin', 'sensei']))
            <li>
                <a href="{{ route('alumnos.index') }}"
                   title="Alumnos"
                   class="{{ Request::routeIs('alumnos.*') ? 'activo' : '' }}">
                    <i class="bi bi-person-badge"></i>
                </a>
            </li>
            @endif

            {{-- Pagos — todos los roles --}}
            @if(in_array($rol, ['admin', 'sensei', 'tutor', 'alumno']))
            <li>
                <a href="{{ route('pagos.index') }}"
                   title="Pagos"
                   class="{{ Request::routeIs('pagos.*') ? 'activo' : '' }}">
                    <i class="bi bi-cash-coin"></i>
                </a>
            </li>
            @endif

            {{-- Calendario — todos los roles --}}
            <li>
                <a href="{{ route('calendario.index') }}"
                   title="Calendario de Eventos"
                   class="{{ Request::routeIs('calendario.*') ? 'activo' : '' }}">
                    <i class="bi bi-calendar3"></i>
                </a>
            </li>

            {{-- Galería — todos los roles --}}
            <li>
                <a href="{{ route('galeria.index') }}"
                   title="Galería Multimedia"
                   class="{{ Request::routeIs('galeria.*') ? 'activo' : '' }}">
                    <i class="bi bi-images"></i>
                </a>
            </li>

            {{-- Perfil — todos los roles --}}
            <li>
                <a href="{{ route('perfil') }}"
                   title="Mi Perfil"
                   class="{{ Request::routeIs('perfil') ? 'activo' : '' }}">
                    <i class="bi bi-person-circle"></i>
                </a>
            </li>

            {{-- Salir --}}
            <li>
                <a href="#"
                   title="Salir"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </li>
        </ul>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
            @csrf
        </form>

    @endauth

    @guest
        <ul>
            <li>
                <a href="{{ route('login') }}" title="Ingresar">
                    <i class="bi bi-box-arrow-in-right"></i>
                </a>
            </li>
        </ul>
    @endguest
</nav>