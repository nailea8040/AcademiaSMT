export function siguientePotenciaDe2(n) {
    let potencia = 1;
    while (potencia < n) potencia *= 2;
    return potencia;
}

export function calcularByes(n) {
    return siguientePotenciaDe2(n) - n;
}

export function generarBracketsLocal(inscritos) {
    const n = inscritos.length;
    const potencia = siguientePotenciaDe2(n);
    const byesNecesarios = potencia - n;
    const totalRondas = Math.log2(potencia);

    const ordenados = ordenarAntiDojo(inscritos);

    const slots = new Array(potencia).fill(null);

    for (let i = 0; i < byesNecesarios; i++) {
        slots[i * 2] = 'BYE';
    }

    let idxInscrito = 0;
    for (let i = 0; i < potencia; i++) {
        if (slots[i] === 'BYE') continue;
        if (idxInscrito < ordenados.length) {
            slots[i] = ordenados[idxInscrito].id_inscripcion || ordenados[idxInscrito].id;
            idxInscrito++;
        }
    }

    const rondas = [];
    let ordenJuego = 1;

    let llavesActuales = [];
    for (let i = 0; i < potencia; i += 2) {
        const insc1 = slots[i] === 'BYE' ? null : slots[i];
        const insc2 = slots[i + 1] === 'BYE' ? null : slots[i + 1];
        const esBye = slots[i] === 'BYE' || slots[i + 1] === 'BYE';

        let ganadorId = null;
        if (slots[i] === 'BYE' && slots[i + 1] !== 'BYE') ganadorId = slots[i + 1];
        else if (slots[i + 1] === 'BYE' && slots[i] !== 'BYE') ganadorId = slots[i];

        llavesActuales.push({
            tempId: `r${totalRondas}_p${(i / 2) + 1}`,
            ronda: totalRondas,
            posicion: (i / 2) + 1,
            id_inscripcion_1: insc1,
            id_inscripcion_2: insc2,
            ganador_id: ganadorId,
            es_bye: esBye,
            estado: ganadorId ? 'completada' : 'pendiente',
            orden_juego: ordenJuego++,
        });
    }

    rondas.push({ ronda: totalRondas, llaves: [...llavesActuales] });

    for (let r = totalRondas - 1; r >= 1; r--) {
        const nuevaRonda = [];
        for (let i = 0; i < llavesActuales.length; i += 2) {
            nuevaRonda.push({
                tempId: `r${r}_p${(i / 2) + 1}`,
                ronda: r,
                posicion: (i / 2) + 1,
                id_inscripcion_1: null,
                id_inscripcion_2: null,
                ganador_id: null,
                es_bye: false,
                estado: 'pendiente',
                orden_juego: ordenJuego++,
            });
        }
        rondas.push({ ronda: r, llaves: [...nuevaRonda] });
        llavesActuales = nuevaRonda;
    }

    const llaveFinal = llavesActuales[0];
    if (llaveFinal) {
        rondas.push({
            ronda: 0,
            llaves: [{
                tempId: 'final',
                ronda: 0,
                posicion: 1,
                id_inscripcion_1: null,
                id_inscripcion_2: null,
                ganador_id: null,
                es_bye: false,
                es_tercer_lugar: true,
                estado: 'pendiente',
                orden_juego: ordenJuego++,
            }],
        });
    }

    return rondas;
}

export function ordenarAntiDojo(inscritos) {
    const porDojo = {};
    for (const insc of inscritos) {
        const dojo = insc.dojo_procedencia || 'Sin Dojo';
        if (!porDojo[dojo]) porDojo[dojo] = [];
        porDojo[dojo].push(insc);
    }

    const dojosOrdenados = Object.entries(porDojo)
        .sort((a, b) => b[1].length - a[1].length);

    const maxEnDojo = dojosOrdenados[0]?.[1].length || 0;
    const totalInscritos = inscritos.length;

    if (maxEnDojo > totalInscritos / 2) {
        return [...inscritos].sort(() => Math.random() - 0.5);
    }

    const resultado = [];
    const maxLen = Math.max(...dojosOrdenados.map(([, g]) => g.length));

    for (let i = 0; i < maxLen; i++) {
        for (const [, grupo] of dojosOrdenados) {
            if (grupo[i]) resultado.push(grupo[i]);
        }
    }

    return resultado;
}

export function calcularTamanosBracket(numInscritos) {
    const potencia = siguientePotenciaDe2(numInscritos);
    const byes = potencia - numInscritos;
    const totalRondas = Math.log2(potencia);
    const totalLlaves = (potencia * 2) - 1;

    return {
        inscritos: numInscritos,
        potencia,
        byes,
        totalRondas,
        totalLlaves,
        rondas: Array.from({ length: totalRondas }, (_, i) => ({
            numero: totalRondas - i,
            nombre: i === totalRondas - 1 ? 'Primera Ronda' :
                    i === totalRondas - 2 ? 'Segunda Ronda' :
                    i === totalRondas - 3 ? 'Cuartos de Final' :
                    i === totalRondas - 4 ? 'Semifinal' : `Ronda ${totalRondas - i}`,
            combates: potencia / Math.pow(2, i + 1),
        })),
    };
}
