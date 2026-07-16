import React, { useState } from 'react';

const INITIAL_FORM = {
    nombre_completo: '',
    fecha_nacimiento: '',
    genero: '',
    grado_cinta: '',
    peso: '',
    dojo_procedencia: '',
    maestro_cargo: '',
    id_categoria_torneo: '',
    disciplina_inscrita: 'kata',
};

export default function CompetidorForm({ torneoId, categorias, onGuardar, onCancel }) {
    const [form, setForm] = useState(INITIAL_FORM);
    const [errores, setErrores] = useState({});

    const set = (campo, valor) => setForm({ ...form, [campo]: valor });

    const validar = () => {
        const e = {};
        if (!form.nombre_completo.trim()) e.nombre_completo = 'El nombre es obligatorio';
        if (!form.id_categoria_torneo) e.id_categoria_torneo = 'Selecciona una categoría';
        if (form.fecha_nacimiento) {
            const edad = Math.floor((Date.now() - new Date(form.fecha_nacimiento).getTime()) / 31557600000);
            if (edad < 0 || edad > 100) e.fecha_nacimiento = 'Fecha no válida';
        }
        setErrores(e);
        return Object.keys(e).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validar()) return;

        const payload = {
            nombre_completo: form.nombre_completo.trim(),
            id_categoria_torneo: parseInt(form.id_categoria_torneo),
            disciplina_inscrita: form.disciplina_inscrita,
        };
        if (form.fecha_nacimiento) payload.fecha_nacimiento = form.fecha_nacimiento;
        if (form.genero) payload.genero = form.genero;
        if (form.grado_cinta.trim()) payload.grado_cinta = form.grado_cinta.trim();
        if (form.peso) payload.peso = parseFloat(form.peso);
        if (form.dojo_procedencia.trim()) payload.dojo_procedencia = form.dojo_procedencia.trim();
        if (form.maestro_cargo.trim()) payload.maestro_cargo = form.maestro_cargo.trim();

        await onGuardar(payload);
        setForm(INITIAL_FORM);
    };

    return (
        <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="font-bold text-lg mb-4">Inscribir Competidor</h3>

            <div className="space-y-4">
                {/* Nombre completo */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                    <input type="text" value={form.nombre_completo}
                        onChange={e => set('nombre_completo', e.target.value)}
                        className="w-full border rounded-lg px-3 py-2" placeholder="Ej: Juan Pérez López" required />
                    {errores.nombre_completo && <p className="text-red-500 text-xs mt-1">{errores.nombre_completo}</p>}
                </div>

                {/* Fecha de nacimiento + Género */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Fecha de nacimiento</label>
                        <input type="date" value={form.fecha_nacimiento}
                            onChange={e => set('fecha_nacimiento', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2" max={new Date().toISOString().split('T')[0]} />
                        {errores.fecha_nacimiento && <p className="text-red-500 text-xs mt-1">{errores.fecha_nacimiento}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Género</label>
                        <select value={form.genero} onChange={e => set('genero', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2">
                            <option value="">Seleccionar...</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                        </select>
                    </div>
                </div>

                {/* Grado de cinta + Peso */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Grado / Cinta</label>
                        <select value={form.grado_cinta} onChange={e => set('grado_cinta', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2">
                            <option value="">Seleccionar...</option>
                            {['Blanca', 'Amarilla', 'Naranja', 'Verde', 'Azul', 'Morada', 'Marrón', 'Negra'].map(g => (
                                <option key={g} value={g}>{g}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                        <input type="number" step="0.01" min="0" max="300" value={form.peso}
                            onChange={e => set('peso', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2" placeholder="Ej: 65.5" />
                    </div>
                </div>

                {/* Dojo de procedencia + Maestro */}
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Dojo de procedencia</label>
                        <input type="text" value={form.dojo_procedencia}
                            onChange={e => set('dojo_procedencia', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2" placeholder="Ej: Dojo SMT" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Maestro a cargo</label>
                        <input type="text" value={form.maestro_cargo}
                            onChange={e => set('maestro_cargo', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2" placeholder="Ej: Sensei Ocaña" />
                    </div>
                </div>

                {/* Categoría */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                    <select required value={form.id_categoria_torneo}
                        onChange={e => set('id_categoria_torneo', e.target.value)}
                        className="w-full border rounded-lg px-3 py-2">
                        <option value="">Seleccionar categoría...</option>
                        {categorias.map(c => (
                            <option key={c.id_categoria_torneo} value={c.id_categoria_torneo}>
                                {c.nombre_categoria} ({c.tipo_disciplina})
                            </option>
                        ))}
                    </select>
                    {errores.id_categoria_torneo && <p className="text-red-500 text-xs mt-1">{errores.id_categoria_torneo}</p>}
                </div>

                {/* Disciplina */}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Disciplina a competir *</label>
                    <div className="flex gap-4">
                        {['kata', 'kumite', 'ambas'].map(d => (
                            <label key={d} className="flex items-center gap-2">
                                <input type="radio" name="disciplina" value={d}
                                    checked={form.disciplina_inscrita === d}
                                    onChange={e => set('disciplina_inscrita', e.target.value)} />
                                <span className="text-sm capitalize">{d}</span>
                            </label>
                        ))}
                    </div>
                </div>
            </div>

            <div className="flex gap-3 mt-6">
                <button type="button" onClick={onCancel}
                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit"
                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">
                    Inscribir
                </button>
            </div>
        </form>
    );
}
