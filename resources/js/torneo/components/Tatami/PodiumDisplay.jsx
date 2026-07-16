import React from 'react';

export default function PodiumDisplay({ resultados }) {
    if (!resultados || resultados.length === 0) return null;

    const oro = resultados.find(r => r.puesto === '1ro');
    const plata = resultados.find(r => r.puesto === '2do');
    const bronces = resultados.filter(r => r.puesto === '3ro');

    const PodiumSlot = ({ puesto, resultado, color, height }) => (
        <div className="flex flex-col items-center">
            <div className="w-12 h-12 rounded-full flex items-center justify-center mb-2 text-white font-bold text-lg"
                style={{ backgroundColor: color }}>
                {puesto === '1ro' ? '🥇' : puesto === '2do' ? '🥈' : '🥉'}
            </div>
            <p className="font-bold text-sm text-center leading-tight">
                {resultado?.inscripcion?.nombre_completo || '—'}
            </p>
            <p className="text-xs text-gray-500">
                {resultado?.inscripcion?.dojo_procedencia || ''}
            </p>
            <div className="mt-2 rounded-t-lg flex items-end justify-center px-4"
                style={{ height, backgroundColor: color }}>
                <span className="text-white font-bold text-xl mb-2">{puesto}</span>
            </div>
        </div>
    );

    return (
        <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-bold text-center mb-6 text-gray-800">Pódium de Categoría</h3>
            <div className="flex items-end justify-center gap-6">
                <PodiumSlot puesto="2do" resultado={plata} color="#9ca3af" height="80px" />
                <PodiumSlot puesto="1ro" resultado={oro} color="#f59e0b" height="120px" />
                {bronces.map((b, i) => (
                    <PodiumSlot key={i} puesto="3ro" resultado={b} color="#cd7f32" height="60px" />
                ))}
            </div>
        </div>
    );
}
