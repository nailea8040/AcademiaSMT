import { useState, useEffect, useCallback } from 'react';
import { api } from '../services/torneoApi';

export function useInscripciones(torneoId) {
    const [inscripciones, setInscripciones] = useState([]);
    const [loading, setLoading] = useState(true);

    const cargar = useCallback(async (params = '') => {
        if (!torneoId) return;
        try {
            setLoading(true);
            const res = await api.getInscripciones(torneoId, params);
            setInscripciones(res.data || []);
        } catch {
            setInscripciones([]);
        } finally {
            setLoading(false);
        }
    }, [torneoId]);

    useEffect(() => { cargar(); }, [cargar]);

    const crear = async (data) => {
        const res = await api.createInscripcion(torneoId, data);
        await cargar();
        return res;
    };

    const actualizar = async (insId, data) => {
        const res = await api.updateInscripcion(torneoId, insId, data);
        await cargar();
        return res;
    };

    const eliminar = async (insId) => {
        const res = await api.deleteInscripcion(torneoId, insId);
        await cargar();
        return res;
    };

    return { inscripciones, loading, crear, actualizar, eliminar, recargar: cargar };
}
