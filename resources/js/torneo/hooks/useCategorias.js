import { useState, useEffect, useCallback } from 'react';
import { api } from '../services/torneoApi';

export function useCategorias(torneoId) {
    const [categorias, setCategorias] = useState([]);
    const [loading, setLoading] = useState(true);

    const cargar = useCallback(async () => {
        if (!torneoId) return;
        try {
            setLoading(true);
            const res = await api.getTorneo(torneoId);
            setCategorias(res.data?.categorias || []);
        } catch {
            setCategorias([]);
        } finally {
            setLoading(false);
        }
    }, [torneoId]);

    useEffect(() => { cargar(); }, [cargar]);

    const crear = async (data) => {
        const res = await api.createCategoria(torneoId, data);
        await cargar();
        return res;
    };

    const actualizar = async (catId, data) => {
        const res = await api.updateCategoria(torneoId, catId, data);
        await cargar();
        return res;
    };

    const eliminar = async (catId) => {
        const res = await api.deleteCategoria(torneoId, catId);
        await cargar();
        return res;
    };

    return { categorias, loading, crear, actualizar, eliminar, recargar: cargar };
}
