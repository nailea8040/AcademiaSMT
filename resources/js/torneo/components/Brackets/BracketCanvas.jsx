import React from 'react';
import MatchCard from './MatchCard';

export default function BracketCanvas({ rondas, onNodoClick, onDragDrop, editable }) {
    if (!rondas || rondas.length === 0) {
        return (
            <div className="text-center py-12 text-gray-500">
                <i className="bi bi-diagram-3 text-4xl mb-3 block"></i>
                <p>No hay brackets generados para esta categoría.</p>
            </div>
        );
    }

    const rondasNormales = rondas.filter(r => r.ronda > 0).sort((a, b) => b.ronda - a.ronda);
    const rondaFinal = rondas.find(r => r.ronda === 0);

    const getRondaLabel = (numero, total) => {
        const diff = total - numero;
        if (diff === 0) return 'Primera Ronda';
        if (diff === 1) return 'Segunda Ronda';
        if (diff === 2) return 'Cuartos de Final';
        if (diff === 3) return 'Semifinal';
        if (diff === 4) return 'FINAL';
        return `Ronda ${numero}`;
    };

    return (
        <div className="overflow-x-auto pb-4">
            <div className="flex items-start gap-0 min-w-max">
                {rondasNormales.map((ronda, idx) => (
                    <div key={ronda.ronda} className="flex flex-col items-center">
                        <div className="text-center mb-3 px-4">
                            <span className="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                {getRondaLabel(ronda.ronda, rondasNormales[0]?.ronda || 1)}
                            </span>
                            <span className="block text-[10px] text-gray-400">
                                {ronda.llaves.length} combate{ronda.llaves.length !== 1 ? 's' : ''}
                            </span>
                        </div>

                        <div
                            className="flex flex-col justify-around px-2"
                            style={{
                                gap: `${Math.max(20, 60 * Math.pow(2, idx))}px`,
                                minHeight: `${Math.max(200, ronda.llaves.length * 120)}px`,
                            }}
                        >
                            {ronda.llaves.map((llave) => (
                                <div key={llave.tempId || llave.id_llave} className="w-56">
                                    <MatchCard
                                        llave={llave}
                                        onClick={onNodoClick}
                                        onDrop={onDragDrop}
                                        editable={editable}
                                    />
                                </div>
                            ))}
                        </div>

                        {idx < rondasNormales.length - 1 && (
                            <div className="flex items-center justify-center mx-1">
                                <div className="w-8 h-px bg-gray-300"></div>
                                <i className="bi bi-chevron-compact-right text-gray-300"></i>
                            </div>
                        )}
                    </div>
                ))}

                {rondaFinal && (
                    <div className="flex flex-col items-center">
                        <div className="text-center mb-3 px-4">
                            <span className="text-xs font-bold text-red-600 uppercase tracking-wider">
                                CAMPEÓN
                            </span>
                        </div>
                        <div className="flex items-center justify-center" style={{ minHeight: '100px' }}>
                            <div className="w-64 border-2 border-yellow-400 rounded-xl bg-yellow-50 p-4 text-center">
                                <i className="bi bi-trophy-fill text-3xl text-yellow-500 mb-2 block"></i>
                                <span className="text-sm font-bold text-yellow-800">
                                    {rondaFinal.llaves[0]?.ganador?.nombre_completo || 'Por definir'}
                                </span>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
