export const FASES = {
    BORRADOR: 'borrador',
    INSCRIPCION: 'inscripcion',
    GRAFICACION: 'graficacion',
    MESAS: 'mesas',
    PREMIACION: 'premiacion',
    MEMORIA: 'memoria',
    FINALIZADO: 'finalizado',
};

export const FASE_LABELS = {
    borrador: 'Borrador',
    inscripcion: 'Inscripción',
    graficacion: 'Graficación',
    mesas: 'Mesas / Tatamis',
    premiacion: 'Premiación',
    memoria: 'Memoria',
    finalizado: 'Finalizado',
};

export const FASE_COLORS = {
    borrador: 'bg-gray-100 text-gray-700',
    inscripcion: 'bg-blue-100 text-blue-700',
    graficacion: 'bg-yellow-100 text-yellow-700',
    mesas: 'bg-orange-100 text-orange-700',
    premiacion: 'bg-green-100 text-green-700',
    memoria: 'bg-purple-100 text-purple-700',
    finalizado: 'bg-red-100 text-red-700',
};

export const FASE_RESPONSABLES = {
    graficacion: 'Sensei Ocaña',
    mesas: 'Sensei / Juez Gabriel',
    premiacion: 'Sensei / Sempai Daniel',
    memoria: 'Sensei / Sempai Jaime',
};

export const PUNTOS = {
    '1ro': 100,
    '2do': 75,
    '3ro': 50,
};

export const COLORES_AKA_AO = {
    rojo: { bg: '#ef4444', text: 'white', label: 'Aka (Rojo)' },
    azul: { bg: '#3b82f6', text: 'white', label: 'Ao (Azul)' },
};

export const ESTADO_COMBATE = {
    PENDIENTE: 'pendiente',
    EN_CURSO: 'en_curso',
    COMPLETADA: 'completada',
};
