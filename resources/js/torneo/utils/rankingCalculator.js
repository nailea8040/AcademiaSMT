export function calcularPuntajeDojo(resultados) {
    const dojoMap = {};

    for (const r of resultados) {
        const dojo = r.dojo_nombre;
        if (!dojo) continue;

        if (!dojoMap[dojo]) {
            dojoMap[dojo] = { dojo_nombre: dojo, puntos_1ro: 0, puntos_2do: 0, puntos_3ro: 0, total_puntos: 0 };
        }

        dojoMap[dojo].puntos_1ro += r.puntos_1ro || 0;
        dojoMap[dojo].puntos_2do += r.puntos_2do || 0;
        dojoMap[dojo].puntos_3ro += r.puntos_3ro || 0;
    }

    for (const key in dojoMap) {
        const d = dojoMap[key];
        d.total_puntos = (d.puntos_1ro * 100) + (d.puntos_2do * 75) + (d.puntos_3ro * 50);
    }

    return Object.values(dojoMap).sort((a, b) => b.total_puntos - a.total_puntos);
}

export function calcularMejorCompetidor(resultados) {
    const atletaMap = {};

    for (const r of resultados) {
        const id = r.id_inscripcion;
        if (!atletaMap[id]) {
            atletaMap[id] = {
                id_inscripcion: id,
                nombre_completo: r.nombre_completo,
                genero: r.genero,
                dojo_procedencia: r.dojo_procedencia,
                oros: 0,
                platas: 0,
                bronces: 0,
                total_podios: 0,
                categorias: [],
            };
        }

        atletaMap[id].total_podios++;

        if (r.puesto === '1ro') {
            atletaMap[id].oros++;
        } else if (r.puesto === '2do') {
            atletaMap[id].platas++;
        } else if (r.puesto === '3ro') {
            atletaMap[id].bronces++;
        }

        if (r.nombre_categoria) {
            atletaMap[id].categorias.push({
                nombre: r.nombre_categoria,
                puesto: r.puesto,
            });
        }
    }

    const competidores = Object.values(atletaMap)
        .sort((a, b) => b.total_podios - a.total_podios || b.oros - a.oros);

    const masculino = competidores.filter(c => c.genero === 'masculino');
    const femenino = competidores.filter(c => c.genero === 'femenino');

    const mejorM = masculino[0] || null;
    const mejorF = femenino[0] || null;

    const empatesM = mejorM ? masculino.filter(c => c.total_podios === mejorM.total_podios && c.oros === mejorM.oros) : [];
    const empatesF = mejorF ? femenino.filter(c => c.total_podios === mejorF.total_podios && c.oros === mejorF.oros) : [];

    return {
        mejor_masculino: mejorM,
        mejor_femenino: mejorF,
        hay_empate: empatesM.length > 1 || empatesF.length > 1,
        empatados: [...(empatesM.length > 1 ? empatesM : []), ...(empatesF.length > 1 ? empatesF : [])],
    };
}
