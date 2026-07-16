import React, { useState, useEffect, useCallback } from 'react';
import { api } from './services/torneoApi';
import { useTorneo } from './hooks/useTorneo';
import { useCategorias } from './hooks/useCategorias';
import { useInscripciones } from './hooks/useInscripciones';
import { useFase } from './hooks/useFase';
import TorneoLayout from './components/Layout/TorneoLayout';
import PhaseGuard from './components/Layout/PhaseGuard';
import NipModal from './components/Auth/NipModal';
import BracketCanvas from './components/Brackets/BracketCanvas';
import ScoreBoard from './components/Tatami/ScoreBoard';
import PodiumDisplay from './components/Tatami/PodiumDisplay';
import DojoChampion from './components/Premiacion/DojoChampion';
import BestCompetitor from './components/Premiacion/BestCompetitor';
import TieBreakerModal from './components/Premiacion/TieBreakerModal';
import PlantillaForm from './components/Admin/PlantillaForm';
import CompetidorForm from './components/Admin/CompetidorForm';
import { FASES, FASE_LABELS } from './utils/constants';

function App() {
    const urlParams = new URLSearchParams(window.location.search);
    const pathParts = window.location.pathname.split('/');
    const torneoId = pathParts[pathParts.length - 1] !== 'torneos'
        ? pathParts[pathParts.length - 1]
        : urlParams.get('id');

    const [ruta, setRuta] = useState('general');
    const [torneos, setTorneos] = useState([]);
    const [listaLoading, setListaLoading] = useState(true);
    const [brackets, setBrackets] = useState([]);
    const [bracketLoading, setBracketLoading] = useState(false);
    const [categoriaSel, setCategoriaSel] = useState(null);
    const [puntajeDojo, setPuntajeDojo] = useState([]);
    const [mejorComp, setMejorComp] = useState(null);
    const [showPlantilla, setShowPlantilla] = useState(false);
    const [showCompetidor, setShowCompetidor] = useState(false);
    const [error, setError] = useState(null);

    const { torneo, loading: torneoLoading, recargar, cambiarFase } = useTorneo(torneoId);
    const { categorias, loading: catLoading, crear: crearCat, eliminar: eliminarCat } = useCategorias(torneoId);
    const { inscripciones, loading: inscLoading, crear: crearInsc, eliminar: eliminarInsc } = useInscripciones(torneoId);
    const { showNipModal, fasePendiente, loading: nipLoading, solicitarTransicion, confirmarTransicion, cancelar, puedeTransicionar, getResponsableFase } = useFase();

    const cargarTorneos = async () => {
        try {
            setListaLoading(true);
            const res = await api.getTorneos();
            setTorneos(res.data || []);
        } catch { setTorneos([]); }
        finally { setListaLoading(false); }
    };

    useEffect(() => { if (!torneoId) cargarTorneos(); }, [torneoId]);

    const cargarBrackets = async (catId) => {
        if (!torneoId || !catId) return;
        try {
            setBracketLoading(true);
            const res = await api.getBrackets(torneoId, catId);
            setBrackets(res.data || []);
            setCategoriaSel(catId);
        } catch { setBrackets([]); }
        finally { setBracketLoading(false); }
    };

    const generarBrackets = async (catId) => {
        try {
            await api.generarBrackets(torneoId, catId);
            await cargarBrackets(catId);
            await recargar();
        } catch (err) {
            setError(err.mensaje || 'Error al generar brackets');
            setTimeout(() => setError(null), 3000);
        }
    };

    const registrarResultado = async (llaveId, data) => {
        try {
            await api.updateNodo(torneoId, llaveId, { ganador_id: data.ganador_id });
            if (categoriaSel) await cargarBrackets(categoriaSel);
        } catch (err) {
            setError(err.mensaje || 'Error al registrar resultado');
            setTimeout(() => setError(null), 3000);
        }
    };

    const finalizarCategoria = async (catId) => {
        try {
            await api.finalizarCategoria(torneoId, catId);
            await cargarPuntaje();
            await cargarMejorCompetidor();
            await recargar();
        } catch (err) {
            setError(err.mensaje || 'Error al finalizar');
            setTimeout(() => setError(null), 3000);
        }
    };

    const cargarPuntaje = async () => {
        try {
            const res = await api.getPuntajeDojo(torneoId);
            setPuntajeDojo(res.data || []);
        } catch { setPuntajeDojo([]); }
    };

    const cargarMejorCompetidor = async () => {
        try {
            const res = await api.getMejorCompetidor(torneoId);
            setMejorComp(res.data);
        } catch { setMejorComp(null); }
    };

    const handleTransicion = async (nip) => {
        try {
            await confirmarTransicion(torneoId, nip);
            await recargar();
        } catch (err) {
            setError(err.mensaje || 'NIP incorrecto');
            setTimeout(() => setError(null), 3000);
        }
    };

    const handleNipConfirm = handleTransicion;

    if (!torneoId) {
        return (
            <div className="min-h-screen bg-gray-50 p-6">
                <h1 className="text-3xl font-bold text-gray-800 mb-6">
                    <i className="bi bi-trophy-fill text-red-600 mr-2"></i>
                    Módulo de Torneos
                </h1>
                {listaLoading ? (
                    <div className="text-center py-12"><i className="bi bi-arrow-repeat animate-spin text-3xl text-gray-400"></i></div>
                ) : torneos.length === 0 ? (
                    <div className="text-center py-12 text-gray-500">
                        <i className="bi bi-calendar-x text-4xl mb-3 block"></i>
                        <p>No hay torneos creados.</p>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {torneos.map(t => (
                            <a key={t.id_torneo} href={`/torneos/${t.id_torneo}`}
                                className="bg-white rounded-xl shadow hover:shadow-md transition-shadow p-4 border border-gray-100">
                                <h3 className="font-bold text-lg">{t.nombre}</h3>
                                <p className="text-sm text-gray-500">{new Date(t.fecha).toLocaleDateString('es-MX')}</p>
                                <span className="inline-block mt-2 px-2 py-0.5 rounded text-xs font-semibold bg-gray-100">
                                    {FASE_LABELS[t.estado] || t.estado}
                                </span>
                            </a>
                        ))}
                    </div>
                )}
            </div>
        );
    }

    if (torneoLoading) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <i className="bi bi-arrow-repeat animate-spin text-4xl text-red-600"></i>
            </div>
        );
    }

    if (!torneo) {
        return (
            <div className="min-h-screen flex items-center justify-center text-gray-500">
                <div className="text-center">
                    <i className="bi bi-exclamation-circle text-4xl mb-3 block"></i>
                    <p>Torneo no encontrado.</p>
                    <a href="/torneos" className="text-red-600 underline mt-2 inline-block">Volver a torneos</a>
                </div>
            </div>
        );
    }

    return (
        <>
            <TorneoLayout torneo={torneo} onNavigate={setRuta} rutaActual={ruta}>
                {error && (
                    <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm flex items-center gap-2">
                        <i className="bi bi-exclamation-triangle-fill"></i>
                        {error}
                    </div>
                )}

                {puedeTransicionar(torneo.estado) && (
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex items-center justify-between">
                        <div>
                            <p className="font-semibold text-blue-800">Siguiente fase disponible</p>
                            <p className="text-sm text-blue-600">
                                Transicionar a: <strong>{FASE_LABELS[
                                    { inscripcion: 'graficacion', graficacion: 'mesas', mesas: 'premiacion', premiacion: 'memoria' }[torneo.estado]
                                ]}</strong>
                                — Requiere NIP de: {getResponsableFase(
                                    { inscripcion: 'graficacion', graficacion: 'mesas', mesas: 'premiacion', premiacion: 'memoria' }[torneo.estado]
                                )}
                            </p>
                        </div>
                        <button
                            onClick={() => {
                                const siguiente = { inscripcion: 'graficacion', graficacion: 'mesas', mesas: 'premiacion', premiacion: 'memoria' }[torneo.estado];
                                solicitarTransicion(torneo.estado);
                            }}
                            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                        >
                            Avanzar Fase
                        </button>
                    </div>
                )}

                {ruta === 'general' && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-bold">Información del Torneo</h2>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="bg-white rounded-lg p-4 border">
                                <p className="text-sm text-gray-500">Nombre</p>
                                <p className="font-semibold">{torneo.nombre}</p>
                            </div>
                            <div className="bg-white rounded-lg p-4 border">
                                <p className="text-sm text-gray-500">Fecha</p>
                                <p className="font-semibold">{new Date(torneo.fecha).toLocaleDateString('es-MX')}</p>
                            </div>
                            <div className="bg-white rounded-lg p-4 border">
                                <p className="text-sm text-gray-500">Categorías</p>
                                <p className="font-semibold">{categorias.length}</p>
                            </div>
                            <div className="bg-white rounded-lg p-4 border">
                                <p className="text-sm text-gray-500">Inscritos</p>
                                <p className="font-semibold">{inscripciones.length}</p>
                            </div>
                        </div>
                    </div>
                )}

                {ruta === 'categorias' && (
                    <PhaseGuard estadoActual={torneo.estado} faseRequerida={FASES.BORRADOR}>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xl font-bold">Categorías</h2>
                                <div className="flex gap-2">
                                    <button onClick={() => setShowPlantilla(true)}
                                        className="px-3 py-1.5 bg-gray-100 rounded-lg text-sm font-medium hover:bg-gray-200">
                                        <i className="bi bi-plus-lg mr-1"></i>Nueva Plantilla
                                    </button>
                                </div>
                            </div>
                            {catLoading ? (
                                <div className="text-center py-8"><i className="bi bi-arrow-repeat animate-spin text-2xl text-gray-400"></i></div>
                            ) : categorias.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">No hay categorías definidas.</p>
                            ) : (
                                <div className="space-y-2">
                                    {categorias.map(c => (
                                        <div key={c.id_categoria_torneo} className="bg-white rounded-lg p-3 border flex items-center justify-between">
                                            <div>
                                                <span className="font-semibold text-sm">{c.nombre_categoria}</span>
                                                <span className="ml-2 text-xs text-gray-500">
                                                    {c.tipo_disciplina} · {c.sexo}
                                                    {c.edad_min && ` · ${c.edad_min}-${c.edad_max} años`}
                                                </span>
                                            </div>
                                            <span className={`text-xs px-2 py-0.5 rounded ${
                                                c.estado === 'finalizada' ? 'bg-green-100 text-green-700' :
                                                c.estado === 'en_curso' ? 'bg-orange-100 text-orange-700' :
                                                'bg-gray-100 text-gray-600'
                                            }`}>{c.estado}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </PhaseGuard>
                )}

                {ruta === 'inscripciones' && (
                    <PhaseGuard estadoActual={torneo.estado} faseRequerida={FASES.INSCRIPCION}>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xl font-bold">Inscripciones ({inscripciones.length})</h2>
                                <button onClick={() => setShowCompetidor(true)}
                                    className="px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700">
                                    <i className="bi bi-plus-lg mr-1"></i>Inscribir
                                </button>
                            </div>
                            {inscLoading ? (
                                <div className="text-center py-8"><i className="bi bi-arrow-repeat animate-spin text-2xl text-gray-400"></i></div>
                            ) : inscripciones.length === 0 ? (
                                <p className="text-gray-500 text-center py-8">No hay inscripciones.</p>
                            ) : (
                                <div className="bg-white rounded-lg border overflow-hidden">
                                    <table className="w-full text-sm">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="text-left px-4 py-2">Nombre</th>
                                                <th className="text-left px-4 py-2">Dojo</th>
                                                <th className="text-left px-4 py-2">Categoría</th>
                                                <th className="text-left px-4 py-2">Disciplina</th>
                                                <th className="text-left px-4 py-2">Edad</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {inscripciones.map(i => (
                                                <tr key={i.id_inscripcion} className="border-t hover:bg-gray-50">
                                                    <td className="px-4 py-2 font-medium">{i.nombre_completo}</td>
                                                    <td className="px-4 py-2 text-gray-500">{i.dojo_procedencia || '—'}</td>
                                                    <td className="px-4 py-2">{categorias.find(c => c.id_categoria_torneo === i.id_categoria_torneo)?.nombre_categoria || '—'}</td>
                                                    <td className="px-4 py-2 capitalize">{i.disciplina_inscrita}</td>
                                                    <td className="px-4 py-2">{i.fecha_nacimiento ? Math.floor((Date.now() - new Date(i.fecha_nacimiento).getTime()) / 31557600000) : '—'}</td>
                                                    <td className="px-4 py-2">
                                                        <button onClick={() => eliminarInsc(i.id_inscripcion)}
                                                            className="text-red-400 hover:text-red-600">
                                                            <i className="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </PhaseGuard>
                )}

                {ruta === 'brackets' && (
                    <PhaseGuard estadoActual={torneo.estado} faseRequerida={FASES.GRAFICACION}>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <h2 className="text-xl font-bold">Brackets / Llaves</h2>
                                <select value={categoriaSel || ''} onChange={e => cargarBrackets(parseInt(e.target.value))}
                                    className="border rounded-lg px-3 py-2 text-sm">
                                    <option value="">Seleccionar categoría...</option>
                                    {categorias.map(c => (
                                        <option key={c.id_categoria_torneo} value={c.id_categoria_torneo}>
                                            {c.nombre_categoria} ({inscripciones.filter(i => i.id_categoria_torneo === c.id_categoria_torneo).length} inscritos)
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {categoriaSel && (
                                <div className="flex gap-2">
                                    <button onClick={() => generarBrackets(categoriaSel)}
                                        className="px-3 py-1.5 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600">
                                        <i className="bi bi-diagram-3 mr-1"></i>Generar Brackets
                                    </button>
                                </div>
                            )}

                            {bracketLoading ? (
                                <div className="text-center py-12"><i className="bi bi-arrow-repeat animate-spin text-3xl text-gray-400"></i></div>
                            ) : brackets.length > 0 ? (
                                <BracketCanvas
                                    rondas={brackets.reduce((acc, llave) => {
                                        let ronda = acc.find(r => r.ronda === llave.ronda);
                                        if (!ronda) { ronda = { ronda: llave.ronda, llaves: [] }; acc.push(ronda); }
                                        ronda.llaves.push(llave);
                                        return acc;
                                    }, []).sort((a, b) => b.ronda - a.ronda)}
                                    editable={[FASES.GRAFICACION, FASES.MESAS].includes(torneo.estado)}
                                    onNodoClick={(comp, llave) => {
                                        console.log('Nodo clickeado:', comp, llave);
                                    }}
                                    onDragDrop={(e, llave, lado) => {
                                        try {
                                            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                                            console.log('Drop:', data, llave, lado);
                                        } catch {}
                                    }}
                                />
                            ) : (
                                <div className="text-center py-12 text-gray-400">
                                    <p>Selecciona una categoría para ver los brackets.</p>
                                </div>
                            )}
                        </div>
                    </PhaseGuard>
                )}

                {ruta === 'tatamis' && (
                    <PhaseGuard estadoActual={torneo.estado} faseRequerida={FASES.MESAS}>
                        <div className="space-y-4">
                            <h2 className="text-xl font-bold">Tatamis — Control en Vivo</h2>
                            <p className="text-gray-500">Selecciona una categoría y tatami para controlar los combates en vivo.</p>

                            <select value={categoriaSel || ''} onChange={e => cargarBrackets(parseInt(e.target.value))}
                                className="border rounded-lg px-3 py-2 text-sm">
                                <option value="">Seleccionar categoría...</option>
                                {categorias.filter(c => c.estado !== 'finalizada').map(c => (
                                    <option key={c.id_categoria_torneo} value={c.id_categoria_torneo}>
                                        {c.nombre_categoria}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </PhaseGuard>
                )}

                {ruta === 'premiacion' && (
                    <PhaseGuard estadoActual={torneo.estado} faseRequerida={FASES.PREMIACION}>
                        <div className="space-y-6">
                            <h2 className="text-xl font-bold">Premiación</h2>
                            <DojoChampion puntajes={puntajeDojo} />
                            {mejorComp && (
                                <BestCompetitor
                                    mejorMasculino={mejorComp.mejor_masculino}
                                    mejorFemenino={mejorComp.mejor_femenino}
                                    hayEmpate={mejorComp.hay_empate}
                                    empatados={mejorComp.empatados}
                                    onResolverEmpate={(e) => console.log('Resolver empate:', e)}
                                />
                            )}
                        </div>
                    </PhaseGuard>
                )}

                {ruta === 'memoria' && (
                    <PhaseGuard estadoActual={torneo.estado} faseRequerida={FASES.MEMORIA}>
                        <div className="space-y-6">
                            <h2 className="text-xl font-bold">Memoria del Torneo</h2>
                            <div className="bg-white rounded-xl p-6 border text-center">
                                <i className="bi bi-graph-up text-4xl text-purple-500 mb-3 block"></i>
                                <p className="text-gray-600">Estadísticas finales y cierre del torneo.</p>
                                <p className="text-sm text-gray-400 mt-2">
                                    Esta sección se habilitará una vez completada la fase de premiación.
                                </p>
                            </div>
                        </div>
                    </PhaseGuard>
                )}
            </TorneoLayout>

            <NipModal
                show={showNipModal}
                fase={fasePendiente?.fase}
                onConfirm={handleNipConfirm}
                onCancel={cancelar}
                loading={nipLoading}
                error={error}
            />
        </>
    );
}

export default App;
