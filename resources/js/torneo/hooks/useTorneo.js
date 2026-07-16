import { useState, useEffect, useCallback } from 'react';
import { api } from '../services/torneoApi';

export function useTorneo(torneoId) {
    const [torneo, setTorneo] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const cargar = useCallback(async () => {
        if (!torneoId) {
            setLoading(false);
            return;
        }
        try {
            setLoading(true);
            const res = await api.getTorneo(torneoId);
            setTorneo(res.data);
        } catch (err) {
            setError(err.mensaje || 'Error al cargar torneo');
        } finally {
            setLoading(false);
        }
    }, [torneoId]);

    useEffect(() => { cargar(); }, [cargar]);

    const cambiarFase = async (fase, nip) => {
        const res = await api.cambiarFase(torneoId, { fase, nip });
        setTorneo(res.data);
        return res;
    };

    return { torneo, loading, error, recargar: cargar, cambiarFase };
}
