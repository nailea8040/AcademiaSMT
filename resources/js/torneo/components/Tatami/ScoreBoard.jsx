import React, { useState } from 'react';
import { COLORES_AKA_AO } from '../../utils/constants';

export default function ScoreBoard({ combate, onFinalizar }) {
    const [puntosR, setPuntosR] = useState(combate?.puntos_rojo || 0);
    const [puntosA, setPuntosA] = useState(combate?.puntos_azul || 0);
    const [ipponR, setIpponR] = useState(combate?.ippon_rojo || false);
    const [ipponA, setIpponA] = useState(combate?.ippon_azul || false);
    const [wazariR, setWazariR] = useState(combate?.wazari_rojo || false);
    const [wazariA, setWazariA] = useState(combate?.wazari_azul || false);

    const ganador = puntosR > puntosA ? 'rojo' : puntosA > puntosR ? 'azul' : null;

    const handleFinalizar = () => {
        if (!ganador) return;
        onFinalizar({
            puntos_rojo: puntosR,
            puntos_azul: puntosA,
            ganador,
            ippon_rojo: ipponR ? 1 : 0,
            ippon_azul: ipponA ? 1 : 0,
            wazari_rojo: wazariR ? 1 : 0,
            wazari_azul: wazariA ? 1 : 0,
        });
    };

    const Puntuador = ({ label, valor, setValor, color }) => (
        <div className="flex items-center gap-2">
            <span className="text-xs w-12 text-right" style={{ color }}>{label}</span>
            <button
                onClick={() => setValor(Math.max(0, valor - 1))}
                className="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300 font-bold text-lg"
            >−</button>
            <span className="w-8 text-center font-bold text-xl">{valor}</span>
            <button
                onClick={() => setValor(valor + 1)}
                className="w-8 h-8 rounded bg-gray-200 hover:bg-gray-300 font-bold text-lg"
            >+</button>
        </div>
    );

    return (
        <div className="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div className="bg-gray-800 text-white text-center py-2 text-sm font-semibold">
                {combate?.llave?.es_tercer_lugar ? '🥉 Combate por 3° Lugar' : '⚔️ Combate'}
            </div>

            <div className="grid grid-cols-2 gap-0">
                <div className="p-4 border-r" style={{ backgroundColor: `${COLORES_AKA_AO.rojo.bg}15` }}>
                    <div className="text-center mb-3">
                        <div className="w-12 h-12 rounded-full mx-auto mb-2 flex items-center justify-center" style={{ backgroundColor: COLORES_AKA_AO.rojo.bg }}>
                            <span className="text-white font-bold text-sm">赤</span>
                        </div>
                        <h4 className="font-bold text-sm">{combate?.competidorRojo?.nombre_completo || 'Aka'}</h4>
                        <p className="text-xs text-gray-500">ROJO</p>
                    </div>
                    <div className="space-y-2">
                        <Puntuador label="Pts" valor={puntosR} setValor={setPuntosR} color={COLORES_AKA_AO.rojo.bg} />
                        <div className="flex gap-2 justify-center">
                            <button
                                onClick={() => setIpponR(!ipponR)}
                                className={`px-3 py-1 rounded text-xs font-bold ${ipponR ? 'bg-red-600 text-white' : 'bg-gray-200'}`}
                            >IPPON</button>
                            <button
                                onClick={() => setWazariR(!wazariR)}
                                className={`px-3 py-1 rounded text-xs font-bold ${wazariR ? 'bg-red-400 text-white' : 'bg-gray-200'}`}
                            >WAZARI</button>
                        </div>
                    </div>
                </div>

                <div className="p-4" style={{ backgroundColor: `${COLORES_AKA_AO.azul.bg}15` }}>
                    <div className="text-center mb-3">
                        <div className="w-12 h-12 rounded-full mx-auto mb-2 flex items-center justify-center" style={{ backgroundColor: COLORES_AKA_AO.azul.bg }}>
                            <span className="text-white font-bold text-sm">青</span>
                        </div>
                        <h4 className="font-bold text-sm">{combate?.competidorAzul?.nombre_completo || 'Ao'}</h4>
                        <p className="text-xs text-gray-500">AZUL</p>
                    </div>
                    <div className="space-y-2">
                        <Puntuador label="Pts" valor={puntosA} setValor={setPuntosA} color={COLORES_AKA_AO.azul.bg} />
                        <div className="flex gap-2 justify-center">
                            <button
                                onClick={() => setIpponA(!ipponA)}
                                className={`px-3 py-1 rounded text-xs font-bold ${ipponA ? 'bg-blue-600 text-white' : 'bg-gray-200'}`}
                            >IPPON</button>
                            <button
                                onClick={() => setWazariA(!wazariA)}
                                className={`px-3 py-1 rounded text-xs font-bold ${wazariA ? 'bg-blue-400 text-white' : 'bg-gray-200'}`}
                            >WAZARI</button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="p-4 border-t bg-gray-50">
                {ganador && (
                    <div className="text-center mb-3">
                        <span className="inline-block px-4 py-2 rounded-lg text-white font-bold" style={{
                            backgroundColor: ganador === 'rojo' ? COLORES_AKA_AO.rojo.bg : COLORES_AKA_AO.azul.bg,
                        }}>
                            Ganador: {ganador === 'rojo' ? 'AKA (Rojo)' : 'AO (Azul)'}
                        </span>
                    </div>
                )}
                <button
                    onClick={handleFinalizar}
                    disabled={!ganador}
                    className="w-full py-3 rounded-lg font-bold text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    style={{ backgroundColor: ganador ? '#16a34a' : '#9ca3af' }}
                >
                    {ganador ? '✓ Finalizar Combate' : 'Empate — Seleccione ganador'}
                </button>
            </div>
        </div>
    );
}
