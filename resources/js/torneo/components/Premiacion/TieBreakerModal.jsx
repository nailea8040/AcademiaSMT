import React from 'react';

export default function TieBreakerModal({ show, empatados, onResolver, onCancel }) {
    if (!show || !empatados || empatados.length === 0) return null;

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
                <div className="bg-yellow-500 px-6 py-4">
                    <h3 className="text-white font-bold text-lg flex items-center gap-2">
                        <i className="bi bi-gavel"></i>
                        Resolución de Empate — Veredicto del Juez
                    </h3>
                </div>

                <div className="p-6">
                    <p className="text-gray-600 mb-4">
                        Los siguientes competidores están empatados en méritos. El juez debe designar al ganador.
                    </p>

                    <div className="space-y-4">
                        {empatados.map((e, i) => (
                            <div key={i} className="border rounded-lg p-4 hover:border-yellow-400 transition-colors">
                                <div className="flex items-center gap-4">
                                    <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i className="bi bi-person-fill text-2xl text-gray-400"></i>
                                    </div>
                                    <div className="flex-1">
                                        <h4 className="font-bold text-lg">{e.nombre_completo}</h4>
                                        <p className="text-sm text-gray-500">Dojo: {e.dojo_procedencia}</p>
                                        <div className="flex gap-3 mt-1 text-sm">
                                            <span>🥇 {e.oros} oros</span>
                                            <span>🥈 {e.platas} platas</span>
                                            <span>🥉 {e.bronces} bronces</span>
                                            <span className="font-bold">{e.total_podios} podios totales</span>
                                        </div>
                                    </div>
                                    <button
                                        onClick={() => onResolver(e)}
                                        className="px-4 py-2 bg-yellow-500 text-white rounded-lg font-bold hover:bg-yellow-600 transition-colors"
                                    >
                                        <i className="bi bi-trophy-fill mr-1"></i>
                                        Designar
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="flex justify-end mt-6">
                        <button
                            onClick={onCancel}
                            className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
