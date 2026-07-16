import React from 'react';
import { FASES, FASE_LABELS } from '../../utils/constants';

const ORDEN_FASES = [
    FASES.BORRADOR, FASES.INSCRIPCION, FASES.GRAFICACION,
    FASES.MESAS, FASES.PREMIACION, FASES.MEMORIA, FASES.FINALIZADO
];

export default function PhaseGuard({ estadoActual, faseRequerida, children, mensaje }) {
    const idxActual = ORDEN_FASES.indexOf(estadoActual);
    const idxRequerido = ORDEN_FASES.indexOf(faseRequerida);

    if (idxActual < idxRequerido) {
        return (
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                <i className="bi bi-lock-fill text-4xl text-yellow-500 mb-3 block"></i>
                <h3 className="text-lg font-semibold text-yellow-800 mb-2">Fase bloqueada</h3>
                <p className="text-yellow-600">
                    {mensaje || `Se requiere estar en fase "${FASE_LABELS[faseRequerida]}" para acceder a esta sección.`}
                </p>
                <p className="text-sm text-yellow-500 mt-2">
                    Estado actual: <strong>{FASE_LABELS[estadoActual]}</strong>
                </p>
            </div>
        );
    }

    return children;
}
