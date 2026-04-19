{{-- 
    CAMBIOS RESPECTO AL ORIGINAL:
    1. JS: e.fecha comparado solo con los primeros 10 chars (YYYY-MM-DD) — la BD puede devolver datetime
    2. JS: idSeguro usa id_cal (PK real de tabla calendario) en lugar de id_evento  
    3. URLs de editar/eliminar usan /calendario/{id} en lugar de /eventos/{id}
    4. Formularios apuntan a route('calendario.store'), route('calendario.update'), route('calendario.destroy')
    5. Los valores de 'tipo' son texto libre (VARCHAR 50) — se mantienen como estaban
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Calendario de Actividades - Dojo Karate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/estilo2.css') }}">
    <style>
        :root { --karate-red: #e85654; --karate-dark: #4A4A4A; }
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .calendar-wrapper { background: white; border-radius: 30px; padding: 2.5rem; box-shadow: 0 20px 60px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .calendar-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; padding: 2rem; background: linear-gradient(135deg, #1B263B 0%, #d43f3d 100%); border-radius: 20px; }
        .calendar-title { font-size: 2rem; font-weight: 900; color: white; margin: 0; display: flex; align-items: center; gap: 1rem; }
        .calendar-nav { display: flex; gap: 0.75rem; }
        .btn-calendar-nav { background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); border-radius: 15px; padding: 0.75rem 1.5rem; font-weight: 700; color: white; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.3s; }
        .btn-calendar-nav:hover { background: rgba(255,255,255,0.3); transform: translateY(-3px); }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; background: #e9ecef; border-radius: 20px; overflow: hidden; }
        .calendar-header { background: linear-gradient(135deg, var(--karate-dark), #5a5a5a); color: white; padding: 1.25rem; text-align: center; font-weight: 800; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1.5px; }
        .calendar-day { background: white; min-height: 140px; padding: 1rem; position: relative; transition: all 0.3s; cursor: pointer; border: 2px solid transparent; }
        .calendar-day:hover { background: linear-gradient(135deg, #f8f9fa, #e9ecef); transform: scale(1.03); z-index: 10; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-color: var(--karate-red); }
        .calendar-day.other-month { background: #fafafa; opacity: 0.4; }
        .calendar-day.today { background: linear-gradient(135deg, rgba(232,86,84,0.15), rgba(232,86,84,0.05)); border: 3px solid var(--karate-red); }
        .day-number { font-size: 1.1rem; font-weight: 800; color: var(--karate-dark); display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; margin-bottom: 0.5rem; }
        .calendar-day.today .day-number { background: var(--karate-red); color: white; }
        .event-badge { font-size: 0.75rem; padding: 0.5rem 0.7rem; border-radius: 10px; margin-top: 0.4rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 0.4rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-badge:hover { transform: translateX(5px) scale(1.08); }
        .event-badge.event-tournament { background: linear-gradient(135deg, #ff6b6b, #ee5a52); color: white; border-left: 4px solid #c92a2a; }
        .event-badge.event-exam      { background: linear-gradient(135deg, #4c6ef5, #364fc7); color: white; border-left: 4px solid #1c7ed6; }
        .event-badge.event-class     { background: linear-gradient(135deg, #20c997, #12b886); color: white; border-left: 4px solid #0ca678; }
        .event-badge.event-seminar   { background: linear-gradient(135deg, #fab005, #f59f00); color: white; border-left: 4px solid #e67700; }
        .event-popup { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%) scale(0.8); width: 90%; max-width: 550px; background: white; border-radius: 30px; box-shadow: 0 30px 90px rgba(0,0,0,0.4); z-index: 10000; opacity: 0; visibility: hidden; transition: all 0.4s; }
        .event-popup.show { opacity: 1; visibility: visible; transform: translate(-50%,-50%) scale(1); }
        .popup-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.7); z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.3s; }
        .popup-overlay.show { opacity: 1; visibility: visible; }
        .popup-header { background: linear-gradient(135deg, var(--karate-red), #d43f3d); color: white; padding: 2rem; border-radius: 30px 30px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .popup-header h3 { margin: 0; font-size: 1.8rem; font-weight: 900; }
        .btn-close-popup { background: rgba(255,255,255,0.2); border: none; color: white; width: 42px; height: 42px; border-radius: 50%; font-size: 1.5rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; }
        .popup-body { padding: 2.5rem; }
        .event-detail-item { display: flex; align-items: flex-start; gap: 1.25rem; margin-bottom: 1.5rem; padding: 1.25rem; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 15px; transition: all 0.3s; }
        .event-detail-icon { width: 50px; height: 50px; background: linear-gradient(135deg, var(--karate-red), #d43f3d); color: white; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
        .event-detail-label { font-size: 0.8rem; text-transform: uppercase; color: #6c757d; font-weight: 700; letter-spacing: 1px; margin-bottom: 0.4rem; }
        .event-detail-value { font-size: 1.1rem; color: var(--karate-dark); font-weight: 700; }
        .calendar-legend { display: flex; flex-wrap: wrap; gap: 1.5rem; margin-top: 2.5rem; padding: 2rem; background: linear-gradient(135deg, #f8f9fa, white); border-radius: 20px; }
        .legend-item { display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; font-weight: 600; padding: 0.5rem 1rem; background: white; border-radius: 12px; }
        .legend-color { width: 24px; height: 24px; border-radius: 8px; }
    </style>
</head>
<body>

    @include('includes.menu')

    <div class="main-content">
        <header class="header mb-4">
            <div class="d-flex justify-content-between align-items-center w-100">
                <div>
                    <h1 class="header-title">
                        <i class="bi bi-calendar3"></i> Calendario de Eventos
                    </h1>
                    <div class="breadcrumb">
                        <a href="{{ route('principal') }}">Inicio</a>
                        <i class="bi bi-chevron-right"></i>
                        <span>Calendario</span>
                    </div>
                </div>
                @if(Auth::check() && Auth::user()->rol == 'admin')
                <button class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addEventModal">
                    <i class="bi bi-plus-circle me-2"></i> Nuevo Evento
                </button>
                @endif
            </div>
        </header>

        <div class="content-wrapper">
            <div class="calendar-wrapper">
                <div class="calendar-controls">
                    <h2 class="calendar-title">
                        <i class="bi bi-calendar-event"></i>
                        <span id="currentMonth"></span>
                    </h2>
                    <div class="calendar-nav">
                        <button class="btn-calendar-nav" onclick="previousMonth()">
                            <i class="bi bi-chevron-left"></i> Anterior
                        </button>
                        <button class="btn-calendar-nav" onclick="nextMonth()">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="calendar-grid" id="calendarGrid">
                    <div class="calendar-header">Lun</div>
                    <div class="calendar-header">Mar</div>
                    <div class="calendar-header">Mié</div>
                    <div class="calendar-header">Jue</div>
                    <div class="calendar-header">Vie</div>
                    <div class="calendar-header">Sáb</div>
                    <div class="calendar-header">Dom</div>
                </div>

                <div class="calendar-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background:linear-gradient(135deg,#20c997,#12b886);"></div>
                        <span>📚 Clase Especial</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:linear-gradient(135deg,#ff6b6b,#ee5a52);"></div>
                        <span>🏆 Torneo</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:linear-gradient(135deg,#4c6ef5,#364fc7);"></div>
                        <span>🥋 Examen de Grado</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background:linear-gradient(135deg,#fab005,#f59f00);"></div>
                        <span>🛡️ Seminario</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Popup detalles -->
    <div class="popup-overlay" id="popupOverlay" onclick="closePopup()"></div>
    <div class="event-popup" id="eventPopup">
        <div class="popup-header" id="popupHeader">
            <h3 id="popupTitle"></h3>
            <button class="btn-close-popup" onclick="closePopup()">×</button>
        </div>
        <div class="popup-body">
            <div class="event-detail-item">
                <div class="event-detail-icon"><i class="bi bi-calendar-check"></i></div>
                <div><div class="event-detail-label">Fecha</div><div class="event-detail-value" id="popupDate"></div></div>
            </div>
            <div class="event-detail-item">
                <div class="event-detail-icon"><i class="bi bi-clock"></i></div>
                <div><div class="event-detail-label">Hora</div><div class="event-detail-value" id="popupTime"></div></div>
            </div>
            <div class="event-detail-item">
                <div class="event-detail-icon"><i class="bi bi-geo-alt"></i></div>
                <div><div class="event-detail-label">Ubicación</div><div class="event-detail-value" id="popupLocation"></div></div>
            </div>
            <div style="background:#fff;padding:1.5rem;border-radius:15px;border-left:5px solid var(--karate-red);margin-top:1rem;">
                <div class="event-detail-label mb-2"><i class="bi bi-card-text me-2"></i>Descripción</div>
                <p id="popupDescription" style="margin:0;color:#495057;line-height:1.8;"></p>
            </div>
            @if(Auth::check() && Auth::user()->rol == 'admin')
            <div id="adminButtons" style="display:none;gap:1rem;margin-top:2rem;" data-event-id="" data-event-title="">
                <button onclick="editEvent()" class="btn btn-primary rounded-pill px-4" style="flex:1;">
                    <i class="bi bi-pencil-square me-2"></i>Editar
                </button>
                <button onclick="deleteEvent()" class="btn btn-danger rounded-pill px-4" style="flex:1;">
                    <i class="bi bi-trash3 me-2"></i>Eliminar
                </button>
            </div>
            @endif
        </div>
    </div>

    @if(Auth::check() && Auth::user()->rol == 'admin')
    <!-- Modal Agregar Evento — apunta a CalendarioController@store -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:25px;border:none;">
                <div class="modal-header text-white" style="background:linear-gradient(135deg,var(--karate-red),#d43f3d);border-radius:25px 25px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Nuevo Evento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                {{-- Route: calendario.store → CalendarioController@store --}}
                <form action="{{ route('calendario.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título</label>
                            <input type="text" name="titulo" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Fecha</label>
                                <input type="date" id="eventDateInput" name="fecha" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hora</label>
                                <input type="time" name="hora" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ubicación</label>
                            <input type="text" name="ubicacion" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Categoría</label>
                            <select name="tipo" class="form-select">
                                <option value="class">📚 Clase Especial</option>
                                <option value="tournament">🏆 Torneo</option>
                                <option value="exam">🥋 Examen de Grado</option>
                                <option value="seminar">🛡️ Seminario</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger px-4 rounded-pill">Registrar Evento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Evento — acción dinámica vía JS -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:25px;border:none;">
                <div class="modal-header text-white" style="background:linear-gradient(135deg,#4c6ef5,#364fc7);border-radius:25px 25px 0 0;">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Editar Evento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editEventForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Título</label>
                            <input type="text" id="editTitulo" name="titulo" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Fecha</label>
                                <input type="date" id="editFecha" name="fecha" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hora</label>
                                <input type="time" id="editHora" name="hora" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ubicación</label>
                            <input type="text" id="editUbicacion" name="ubicacion" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Categoría</label>
                            <select id="editTipo" name="tipo" class="form-select">
                                <option value="class">📚 Clase Especial</option>
                                <option value="tournament">🏆 Torneo</option>
                                <option value="exam">🥋 Examen de Grado</option>
                                <option value="seminar">🛡️ Seminario</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea id="editDescripcion" name="descripcion" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-pill">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pasar eventos de PHP a JS — datos de tabla 'calendario'
        const eventosData = @json($eventos);

        let currentDate  = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear  = currentDate.getFullYear();

        const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        function renderCalendar() {
            const firstDay       = new Date(currentYear, currentMonth, 1);
            const lastDay        = new Date(currentYear, currentMonth + 1, 0);
            const prevLastDay    = new Date(currentYear, currentMonth, 0);
            const firstDayIndex  = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
            const lastDayDate    = lastDay.getDate();
            const prevLastDayDate = prevLastDay.getDate();
            const nextDays       = 7 - (lastDay.getDay() === 0 ? 7 : lastDay.getDay());

            document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;

            let days = '';
            const icons = { tournament:'🏆', exam:'🥋', class:'📚', seminar:'🛡️' };

            for (let x = firstDayIndex; x > 0; x--) {
                days += `<div class="calendar-day other-month"><span class="day-number">${prevLastDayDate - x + 1}</span></div>`;
            }

            for (let i = 1; i <= lastDayDate; i++) {
                const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
                const today   = new Date();
                const isToday = i === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear();

                // CORRECCIÓN: comparar solo los primeros 10 chars (fecha sin hora)
                const dayEvents = eventosData.filter(e => (e.fecha ? e.fecha.substring(0,10) : '') === dateStr);

                let badges = '';
                dayEvents.forEach(e => {
                    // CORRECCIÓN: PK real es id_cal (no id_evento)
                    const idSeguro = e.id_cal !== undefined ? e.id_cal : 'null';
                    badges += `<div class="event-badge event-${e.tipo}"
                        onclick="event.stopPropagation(); showEvent(
                            '${e.titulo.replace(/'/g,"\\'")}',
                            '${formatDate(e.fecha.substring(0,10))}',
                            '${e.hora}',
                            '${(e.ubicacion||'').replace(/'/g,"\\'")}',
                            '${e.descripcion ? escapeQuotes(e.descripcion) : ''}',
                            '${e.tipo}',
                            ${idSeguro}
                        )">
                        ${icons[e.tipo] || '📅'} ${e.titulo}
                    </div>`;
                });

                const isAdmin = {{ Auth::check() && Auth::user()->rol == 'admin' ? 'true' : 'false' }};
                const clickHandler = isAdmin ? `onclick="openAddEvent('${dateStr}')"` : '';

                days += `<div class="calendar-day ${isToday ? 'today' : ''}" ${clickHandler}>
                    <span class="day-number">${i}</span>${badges}
                </div>`;
            }

            for (let j = 1; j <= (nextDays < 7 ? nextDays : 0); j++) {
                days += `<div class="calendar-day other-month"><span class="day-number">${j}</span></div>`;
            }

            document.getElementById('calendarGrid').innerHTML = `
                <div class="calendar-header">Lun</div><div class="calendar-header">Mar</div>
                <div class="calendar-header">Mié</div><div class="calendar-header">Jue</div>
                <div class="calendar-header">Vie</div><div class="calendar-header">Sáb</div>
                <div class="calendar-header">Dom</div>${days}`;
        }

        function formatDate(d) {
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        }

        function previousMonth() {
            currentMonth--;
            if (currentMonth < 0) { currentMonth = 11; currentYear--; }
            renderCalendar();
        }

        function nextMonth() {
            currentMonth++;
            if (currentMonth > 11) { currentMonth = 0; currentYear++; }
            renderCalendar();
        }

        function openAddEvent(date) {
            const modal = new bootstrap.Modal(document.getElementById('addEventModal'));
            document.getElementById('eventDateInput').value = date;
            modal.show();
        }

        function showEvent(title, date, time, location, description, type, eventId) {
            if (event) event.stopPropagation();
            document.getElementById('popupTitle').innerHTML = getEventIcon(type) + ' ' + title;
            document.getElementById('popupDate').textContent = date;
            document.getElementById('popupTime').textContent = time;
            document.getElementById('popupLocation').textContent = location || '—';
            document.getElementById('popupDescription').textContent = description || 'Sin descripción disponible';
            document.getElementById('popupHeader').style.background = getEventColor(type);

            const adminButtons = document.getElementById('adminButtons');
            if (adminButtons) {
                adminButtons.style.display = 'flex';
                adminButtons.setAttribute('data-event-id', eventId);
                adminButtons.setAttribute('data-event-title', title);
                adminButtons.setAttribute('data-event-date', date);
                adminButtons.setAttribute('data-event-time', time);
                adminButtons.setAttribute('data-event-location', location || '');
                adminButtons.setAttribute('data-event-description', description || '');
                adminButtons.setAttribute('data-event-type', type);
            }
            document.getElementById('popupOverlay').classList.add('show');
            document.getElementById('eventPopup').classList.add('show');
        }

        function closePopup() {
            document.getElementById('popupOverlay').classList.remove('show');
            document.getElementById('eventPopup').classList.remove('show');
            const ab = document.getElementById('adminButtons');
            if (ab) ab.style.display = 'none';
        }

        function getEventColor(type) {
            const c = { tournament:'linear-gradient(135deg,#ff6b6b,#ee5a52)', exam:'linear-gradient(135deg,#4c6ef5,#364fc7)', class:'linear-gradient(135deg,#20c997,#12b886)', seminar:'linear-gradient(135deg,#fab005,#f59f00)' };
            return c[type] || 'linear-gradient(135deg,var(--karate-red),#d43f3d)';
        }

        function getEventIcon(type) {
            const i = { tournament:'🏆', exam:'🥋', class:'📚', seminar:'🛡️' };
            return i[type] || '📅';
        }

        function escapeQuotes(str) {
            return str ? str.replace(/'/g,"\\'").replace(/"/g,'\\"') : '';
        }

        function editEvent() {
            const ab = document.getElementById('adminButtons');
            const eventId    = ab.getAttribute('data-event-id');
            const title      = ab.getAttribute('data-event-title');
            const dateFormatted = ab.getAttribute('data-event-date');
            const time       = ab.getAttribute('data-event-time');
            const location   = ab.getAttribute('data-event-location');
            const description = ab.getAttribute('data-event-description');
            const type       = ab.getAttribute('data-event-type');

            const parts  = dateFormatted.split('/');
            const dateISO = `${parts[2]}-${parts[1]}-${parts[0]}`;

            document.getElementById('editTitulo').value      = title;
            document.getElementById('editFecha').value       = dateISO;
            document.getElementById('editHora').value        = time;
            document.getElementById('editUbicacion').value   = location;
            document.getElementById('editDescripcion').value = description;
            document.getElementById('editTipo').value        = type;

            // CORRECCIÓN: URL usa /calendario/{id} (CalendarioController@update, PK id_cal)
            document.getElementById('editEventForm').action = `/calendario/${eventId}`;

            closePopup();
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }

        function deleteEvent() {
            const ab = document.getElementById('adminButtons');
            const eventId = ab.getAttribute('data-event-id');
            const title   = ab.getAttribute('data-event-title');

            if (!eventId || isNaN(eventId)) { alert('Error: ID de evento no válido.'); return; }

            if (confirm(`¿Eliminar el evento "${title}"? Esta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                // CORRECCIÓN: URL usa /calendario/{id} (no /eventos/{id})
                form.action = `/calendario/${eventId}`;

                const csrf = document.createElement('input');
                csrf.type = 'hidden'; csrf.name = '_token';
                csrf.value = document.querySelector('meta[name="csrf-token"]').content;

                const method = document.createElement('input');
                method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';

                form.appendChild(csrf); form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
        }

        document.addEventListener('DOMContentLoaded', renderCalendar);
    </script>
</body>
</html>