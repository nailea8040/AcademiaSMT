import React from 'react';
import { PUNTOS } from '../../utils/constants';

export default function DojoChampion({ puntajes }) {
    if (!puntajes || puntajes.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                <i className="bi bi-bar-chart text-3xl mb-2 block"></i>
                <p>No hay datos de puntaje aún.</p>
            </div>
        );
    }

    const maxPuntos = Math.max(...punajes.map(p => p.total_puntos));

    return (
        <div className="bg-white rounded-xl shadow-lg overflow-hidden">
            <div className="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4">
                <h3 className="text-white font-bold text-lg flex items-center gap-2">
                    <i className="bi bi-trophy-fill"></i>
                    Copa Jaguar — Tabla de Posiciones
                </h3>
                <p className="text-red-200 text-xs mt-1">
                    1° = {PUNTOS['1ro']} pts | 2° = {PUNTOS['2do']} pts | 3° = {PUNTOS['3ro']} pts
                </p>
            </div>

            <div className="p-4">
                <div className="space-y-3">
                    {puntajes.map((p, idx) => (
                        <div key={p.id_puntaje || idx} className={`flex items-center gap-4 p-3 rounded-lg ${
                            idx === 0 ? 'bg-yellow-50 border border-yellow-200' :
                            idx === 1 ? 'bg-gray-50 border border-gray-200' :
                            idx === 2 ? 'bg-orange-50 border border-orange-200' :
                            'bg-white border border-gray-100'
                        }`}>
                            <span className={`w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm ${
                                idx === 0 ? 'bg-yellow-400 text-white' :
                                idx === 1 ? 'bg-gray-400 text-white' :
                                idx === 2 ? 'bg-orange-400 text-white' :
                                'bg-gray-200 text-gray-600'
                            }`}>
                                {idx + 1}
                            </span>

                            <div className="flex-1">
                                <p className="font-semibold text-sm">{p.dojo_nombre}</p>
                                <div className="flex gap-3 text-xs text-gray-500 mt-0.5">
                                    <span>🥇 {p.puntos_1ro} × {PUNTOS['1ro']} = {p.puntos_1ro * PUNTOS['1ro']}</span>
                                    <span>🥈 {p.puntos_2do} × {PUNTOS['2do']} = {p.puntos_2do * PUNTOS['2do']}</span>
                                    <span>🥉 {p.puntos_3ro} × {PUNTOS['3ro']} = {p.puntos_3ro * PUNTOS['3ro']}</span>
                                </div>
                            </div>

                            <div className="text-right">
                                <span className="text-2xl font-black text-red-600">{p.total_puntos}</span>
                                <span className="text-xs text-gray-400 block">pts</span>
                            </div>

                            <div className="w-32 bg-gray-200 rounded-full h-2.5">
                                <div
                                    className="h-2.5 rounded-full transition-all"
                                    style={{
                                        width: `${maxPuntos > 0 ? (p.total_puntos / maxPuntos) * 100 : 0}%`,
                                        backgroundColor: idx === 0 ? '#f59e0b' : idx === 1 ? '#9ca3af' : idx === 2 ? '#cd7f32' : '#ef4444',
                                    }}
                                ></div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
