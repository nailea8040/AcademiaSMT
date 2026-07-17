const API_BASE = '/api';

async function request(method, url, data = null) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const config = {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
        },
    };

    if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
        config.body = JSON.stringify(data);
    }

    const res = await fetch(`${API_BASE}${url}`, config);
    const json = await res.json();

    if (!res.ok) {
        throw { status: res.status, ...json };
    }

    return json;
}

export const api = {
    // Torneos
    getTorneos: () => request('GET', '/torneos'),
    getTorneo: (id) => request('GET', `/torneos/${id}`),
    createTorneo: (data) => request('POST', '/torneos', data),
    updateTorneo: (id, data) => request('PUT', `/torneos/${id}`, data),
    deleteTorneo: (id) => request('DELETE', `/torneos/${id}`),
    cambiarFase: (id, data) => request('POST', `/torneos/${id}/fase`, data),

    // Responsables de fase
    getResponsables: () => request('GET', '/fase-responsables'),
    storeResponsable: (data) => request('POST', '/fase-responsables', data),

    // Plantillas
    getPlantillas: () => request('GET', '/plantillas'),
    createPlantilla: (data) => request('POST', '/plantillas', data),
    updatePlantilla: (id, data) => request('PUT', `/plantillas/${id}`, data),
    deletePlantilla: (id) => request('DELETE', `/plantillas/${id}`),

    // Categorías del torneo
    createCategoria: (torneoId, data) => request('POST', `/torneos/${torneoId}/categorias`, data),
    updateCategoria: (torneoId, catId, data) => request('PUT', `/torneos/${torneoId}/categorias/${catId}`, data),
    deleteCategoria: (torneoId, catId) => request('DELETE', `/torneos/${torneoId}/categorias/${catId}`),
    importarPlantilla: (torneoId, data) => request('POST', `/torneos/${torneoId}/importar-plantilla`, data),

    // Inscripciones
    getInscripciones: (torneoId, params = '') => request('GET', `/torneos/${torneoId}/inscripciones${params ? '?' + params : ''}`),
    createInscripcion: (torneoId, data) => request('POST', `/torneos/${torneoId}/inscripciones`, data),
    updateInscripcion: (torneoId, insId, data) => request('PUT', `/torneos/${torneoId}/inscripciones/${insId}`, data),
    deleteInscripcion: (torneoId, insId) => request('DELETE', `/torneos/${torneoId}/inscripciones/${insId}`),

    // Brackets
    getBrackets: (torneoId, catId) => request('GET', `/torneos/${torneoId}/brackets/${catId}`),
    generarBrackets: (torneoId, catId) => request('POST', `/torneos/${torneoId}/brackets/${catId}/generar`),
    updateNodo: (torneoId, llaveId, data) => request('PUT', `/torneos/${torneoId}/brackets/${llaveId}`, data),
    dragDrop: (torneoId, data) => request('POST', `/torneos/${torneoId}/brackets/drag-drop`, data),

    // Combates
    getCombates: (torneoId, catId) => request('GET', `/torneos/${torneoId}/combates/${catId}`),
    createCombate: (torneoId, llaveId, data) => request('POST', `/torneos/${torneoId}/combates/${llaveId}`, data),
    updateCombate: (torneoId, combateId, data) => request('PUT', `/torneos/${torneoId}/combates/combate/${combateId}`, data),

    // Resultados
    getResultados: (torneoId) => request('GET', `/torneos/${torneoId}/resultados`),
    finalizarCategoria: (torneoId, catId) => request('POST', `/torneos/${torneoId}/resultados/${catId}/finalizar`),
    getPuntajeDojo: (torneoId) => request('GET', `/torneos/${torneoId}/puntaje-dojo`),
    getMejorCompetidor: (torneoId) => request('GET', `/torneos/${torneoId}/mejor-competidor`),
    resolverEmpate: (torneoId, data) => request('POST', `/torneos/${torneoId}/resolver-empate`, data),
};
