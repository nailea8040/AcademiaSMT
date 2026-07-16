import React from 'react';
import { FASES, FASE_LABELS, FASE_COLORS } from '../../utils/constants';

export default function TorneoLayout({ children, torneo, onNavigate, rutaActual }) {
    const menuItems = [
        { key: 'general', label: 'General', icon: 'bi-trophy' },
        { key: 'categorias', label: 'Categorías', icon: 'bi-list-check', requires: [FASES.BORRADOR, FASES.INSCRIPCION] },
        { key: 'inscripciones', label: 'Inscripciones', icon: 'bi-people', requires: [FASES.INSCRIPCION] },
        { key: 'brackets', label: 'Brackets', icon: 'bi-diagram-3', requires: [FASES.GRAFICACION, FASES.MESAS] },
        { key: 'tatamis', label: 'Tatamis', icon: 'bi-easel', requires: [FASES.MESAS] },
        { key: 'premiacion', label: 'Premiación', icon: 'bi-award', requires: [FASES.PREMIACION] },
        { key: 'memoria', label: 'Memoria', icon: 'bi-graph-up', requires: [FASES.MEMORIA, FASES.FINALIZADO] },
    ];

    const itemsVisibles = menuItems.filter(item =>
        !item.requires || item.requires.includes(torneo?.estado)
    );

    return (
        <div className="flex min-h-screen bg-gray-50">
            <aside className="w-64 bg-white shadow-md flex flex-col">
                <div className="p-4 border-b">
                    <h2 className="font-bold text-lg text-gray-800 truncate">{torneo?.nombre || 'Cargando...'}</h2>
                    <span className={`inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold ${FASE_COLORS[torneo?.estado] || ''}`}>
                        {FASE_LABELS[torneo?.estado] || torneo?.estado}
                    </span>
                    {torneo?.fecha && (
                        <p className="text-xs text-gray-500 mt-1">{new Date(torneo.fecha).toLocaleDateString('es-MX')}</p>
                    )}
                </div>

                <nav className="flex-1 p-2 space-y-1">
                    {itemsVisibles.map(item => (
                        <button
                            key={item.key}
                            onClick={() => onNavigate(item.key)}
                            className={`w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors ${
                                rutaActual === item.key
                                    ? 'bg-red-50 text-red-700 font-semibold'
                                    : 'text-gray-600 hover:bg-gray-100'
                            }`}
                        >
                            <i className={`bi ${item.icon}`}></i>
                            {item.label}
                        </button>
                    ))}
                </nav>

                <div className="p-3 border-t">
                    <div className="text-xs text-gray-400">
                        Tatamis: {torneo?.tatami_asignado || 1}
                    </div>
                </div>
            </aside>

            <main className="flex-1 overflow-auto">
                <div className="p-6">
                    {children}
                </div>
            </main>
        </div>
    );
}
