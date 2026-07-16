import React from 'react';

export default function BestCompetitor({ mejorMasculino, mejorFemenino, hayEmpate, empatados, onResolverEmpate }) {
    const CompetidorCard = ({ titulo, competidor, color }) => (
        <div className={`rounded-xl border-2 p-6 text-center`} style={{ borderColor: color }}>
            <h4 className="text-sm font-semibold text-gray-500 mb-2">{titulo}</h4>
            {competidor ? (
                <>
                    <div className="w-20 h-20 rounded-full mx-auto mb-3 flex items-center justify-center" style={{ backgroundColor: `${color}30` }}>
                        <i className="bi bi-person-fill text-3xl" style={{ color }}></i>
                    </div>
                    <h3 className="font-bold text-lg text-gray-800">{competidor.nombre_completo}</h3>
                    <p className="text-sm text-gray-500">{competidor.dojo_procedencia}</p>
                    <div className="flex justify-center gap-4 mt-3">
                        <span className="text-yellow-500 font-bold">🥇 {competidor.oros}</span>
                        <span className="text-gray-400 font-bold">🥈 {competidor.platas}</span>
                        <span className="text-orange-400 font-bold">🥉 {competidor.bronces}</span>
                    </div>
                    <p className="text-sm text-gray-600 mt-2">{competidor.total_podios} podios totales</p>
                </>
            ) : (
                <p className="text-gray-400">Sin datos</p>
            )}
        </div>
    );

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-2 gap-6">
                <CompetidorCard titulo="Mejor Competidor Masculino" competidor={mejorMasculino} color="#3b82f6" />
                <CompetidorCard titulo="Mejor Competidor Femenino" competidor={mejorFemenino} color="#ec4899" />
            </div>

            {hayEmpate && (
                <div className="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                    <h4 className="font-bold text-yellow-800 flex items-center gap-2 mb-3">
                        <i className="bi bi-exclamation-triangle-fill"></i>
                        Empate Técnico Detectado
                    </h4>
                    <div className="space-y-3">
                        {empatados.map((e, i) => (
                            <div key={i} className="flex items-center gap-3 bg-white p-3 rounded-lg border">
                                <span className="font-semibold">{e.nombre_completo}</span>
                                <span className="text-sm text-gray-500">({e.dojo_procedencia})</span>
                                <span className="text-sm">🥇{e.oros} 🥈{e.platas} 🥉{e.bronces}</span>
                                <button
                                    onClick={() => onResolverEmpate(e)}
                                    className="ml-auto px-3 py-1 bg-yellow-500 text-white rounded text-sm font-semibold hover:bg-yellow-600"
                                >
                                    Designar Ganador
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
