import { useState } from 'react';
import { api } from '../services/torneoApi';
import { FASES, FASE_RESPONSABLES } from '../utils/constants';

const TRANSICIONES = {
    [FASES.INSCRIPCION]: { hacia: FASES.GRAFICACION, fase: 'graficacion' },
    [FASES.GRAFICACION]: { hacia: FASES.MESAS, fase: 'mesas' },
    [FASES.MESAS]:       { hacia: FASES.PREMIACION, fase: 'premiacion' },
    [FASES.PREMIACION]:  { hacia: FASES.MEMORIA, fase: 'memoria' },
};

export function useFase() {
    const [showNipModal, setShowNipModal] = useState(false);
    const [fasePendiente, setFasePendiente] = useState(null);
    const [loading, setLoading] = useState(false);

    const solicitarTransicion = (estadoActual) => {
        const transicion = TRANSICIONES[estadoActual];
        if (!transicion) return null;

        setFasePendiente(transicion);
        setShowNipModal(true);
        return transicion;
    };

    const confirmarTransicion = async (torneoId, nip) => {
        if (!fasePendiente) return;

        setLoading(true);
        try {
            const res = await api.cambiarFase(torneoId, {
                fase: fasePendiente.fase,
                nip,
            });
            setShowNipModal(false);
            setFasePendiente(null);
            return res;
        } catch (err) {
            throw err;
        } finally {
            setLoading(false);
        }
    };

    const cancelar = () => {
        setShowNipModal(false);
        setFasePendiente(null);
    };

    const puedeTransicionar = (estado) => {
        return !!TRANSICIONES[estado];
    };

    const getResponsableFase = (fase) => {
        return FASE_RESPONSABLES[fase] || 'No definido';
    };

    return {
        showNipModal,
        fasePendiente,
        loading,
        solicitarTransicion,
        confirmarTransicion,
        cancelar,
        puedeTransicionar,
        getResponsableFase,
    };
}
