import React from 'react';
import { COLORES_AKA_AO } from '../../utils/constants';

export default function MatchCard({ llave, onDragStart, onDrop, onClick, editable }) {
    if (!llave) return null;

    const comp1 = llave.competidor1;
    const comp2 = llave.competidor2;
    const ganador = llave.ganador;
    const esBye = llave.es_bye;

    if (esBye && !comp1 && !comp2) return null;

    const renderCompetidor = (comp, lado, esGanador) => {
        const color = lado === 1 ? COLORES_AKA_AO.rojo : COLORES_AKA_AO.azul;
        const esEmpty = !comp;

        return (
            <div
                draggable={editable && !esEmpty && !esGanador}
                onDragStart={(e) => {
                    if (editable && !esGanador) {
                        e.dataTransfer.setData('text/plain', JSON.stringify({
                            inscripcionId: comp?.id_inscripcion || comp?.id,
                            llaveId: llave.id_llave,
                            lado,
                        }));
                    }
                }}
                onDragOver={(e) => e.preventDefault()}
                onDrop={(e) => {
                    e.preventDefault();
                    if (onDrop) onDrop(e, llave, lado);
                }}
                onClick={() => !esEmpty && onClick && onClick(comp, llave)}
                className={`flex items-center gap-2 px-3 py-2 text-sm rounded transition-all ${
                    esGanador
                        ? 'bg-yellow-50 border-2 border-yellow-400 font-bold'
                        : esEmpty
                        ? 'bg-gray-50 border border-dashed border-gray-300 text-gray-400 italic'
                        : 'bg-white border border-gray-200 hover:border-gray-400 cursor-pointer'
                }`}
            >
                <span
                    className="w-3 h-3 rounded-full flex-shrink-0"
                    style={{ backgroundColor: color.bg }}
                ></span>
                <span className="flex-1 truncate">
                    {comp?.nombre_completo || comp?.nombre || 'BYE'}
                </span>
                {comp?.dojo_procedencia && (
                    <span className="text-xs text-gray-400 truncate hidden lg:inline">
                        {comp.dojo_procedencia}
                    </span>
                )}
                {esGanador && (
                    <i className="bi bi-star-fill text-yellow-500 text-xs"></i>
                )}
            </div>
        );
    };

    return (
        <div className={`rounded-lg shadow-sm border transition-all ${
            llave.estado === 'completada' ? 'border-green-200 bg-green-50/30' :
            llave.estado === 'en_curso' ? 'border-orange-300 bg-orange-50/30' :
            'border-gray-200 bg-white'
        }`}>
            <div className="px-3 py-1.5 bg-gray-50 border-b rounded-t-lg flex items-center justify-between">
                <span className="text-xs font-semibold text-gray-500">
                    {llave.es_tercer_lugar ? '🥉 3° Lugar' : `R${llave.ronda} · #${llave.posicion}`}
                </span>
                {llave.estado === 'en_curso' && (
                    <span className="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></span>
                )}
            </div>

            <div className="p-2 space-y-1">
                {renderCompetidor(comp1, 1, ganador?.id_inscripcion === (comp1?.id_inscripcion || comp1?.id))}
                {renderCompetidor(comp2, 2, ganador?.id_inscripcion === (comp2?.id_inscripcion || comp2?.id))}
            </div>
        </div>
    );
}
