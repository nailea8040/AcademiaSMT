import React, { useState, useEffect } from 'react';
import { api } from '../../services/torneoApi';

export default function PlantillaForm({ onGuardar, onCancel }) {
    const [nombre, setNombre] = useState('');
    const [descripcion, setDescripcion] = useState('');
    const [definiciones, setDefiniciones] = useState([{
        nombre_categoria: '', tipo_disciplina: 'kata', sexo: 'masculino',
        edad_min: '', edad_max: '', peso_min: '', peso_max: '', grado_min: '', grado_max: '',
    }]);

    const addDefinicion = () => {
        setDefiniciones([...definiciones, {
            nombre_categoria: '', tipo_disciplina: 'kata', sexo: 'masculino',
            edad_min: '', edad_max: '', peso_min: '', peso_max: '', grado_min: '', grado_max: '',
        }]);
    };

    const updateDef = (idx, campo, valor) => {
        const nuevas = [...definiciones];
        nuevas[idx][campo] = valor;
        setDefiniciones(nuevas);
    };

    const removeDef = (idx) => {
        if (definiciones.length <= 1) return;
        setDefiniciones(definiciones.filter((_, i) => i !== idx));
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const defs = definiciones.map(d => ({
            ...d,
            edad_min: d.edad_min ? parseInt(d.edad_min) : null,
            edad_max: d.edad_max ? parseInt(d.edad_max) : null,
            peso_min: d.peso_min ? parseFloat(d.peso_min) : null,
            peso_max: d.peso_max ? parseFloat(d.peso_max) : null,
            grado_min: d.grado_min ? parseInt(d.grado_min) : null,
            grado_max: d.grado_max ? parseInt(d.grado_max) : null,
        }));
        onGuardar({ nombre, descripcion, definiciones: defs });
    };

    return (
        <form onSubmit={handleSubmit} className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="font-bold text-lg mb-4">Nueva Plantilla de Categorías</h3>

            <div className="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" required value={nombre} onChange={e => setNombre(e.target.value)}
                        className="w-full border rounded-lg px-3 py-2" placeholder="Ej: Torneo Infantil" />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <input type="text" value={descripcion} onChange={e => setDescripcion(e.target.value)}
                        className="w-full border rounded-lg px-3 py-2" placeholder="Descripción opcional" />
                </div>
            </div>

            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <h4 className="font-semibold text-sm">Categorías ({definiciones.length})</h4>
                    <button type="button" onClick={addDefinicion}
                        className="text-sm text-red-600 hover:text-red-800 font-medium">
                        + Agregar categoría
                    </button>
                </div>

                {definiciones.map((def, idx) => (
                    <div key={idx} className="border rounded-lg p-3 bg-gray-50">
                        <div className="flex items-center gap-2 mb-2">
                            <span className="text-xs font-bold text-gray-400">#{idx + 1}</span>
                            {definiciones.length > 1 && (
                                <button type="button" onClick={() => removeDef(idx)}
                                    className="text-red-400 hover:text-red-600 text-xs">✕</button>
                            )}
                        </div>
                        <div className="grid grid-cols-4 gap-2">
                            <input type="text" placeholder="Nombre" required value={def.nombre_categoria}
                                onChange={e => updateDef(idx, 'nombre_categoria', e.target.value)}
                                className="col-span-4 border rounded px-2 py-1.5 text-sm" />
                            <select value={def.tipo_disciplina} onChange={e => updateDef(idx, 'tipo_disciplina', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm">
                                <option value="kata">Kata</option>
                                <option value="kumite">Kumite</option>
                                <option value="ambas">Ambas</option>
                            </select>
                            <select value={def.sexo} onChange={e => updateDef(idx, 'sexo', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm">
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                                <option value="mixto">Mixto</option>
                            </select>
                            <input type="number" placeholder="Edad min" value={def.edad_min}
                                onChange={e => updateDef(idx, 'edad_min', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm" />
                            <input type="number" placeholder="Edad max" value={def.edad_max}
                                onChange={e => updateDef(idx, 'edad_max', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm" />
                            <input type="number" step="0.1" placeholder="Peso min" value={def.peso_min}
                                onChange={e => updateDef(idx, 'peso_min', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm" />
                            <input type="number" step="0.1" placeholder="Peso max" value={def.peso_max}
                                onChange={e => updateDef(idx, 'peso_max', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm" />
                            <input type="number" placeholder="Grado min" value={def.grado_min}
                                onChange={e => updateDef(idx, 'grado_min', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm" />
                            <input type="number" placeholder="Grado max" value={def.grado_max}
                                onChange={e => updateDef(idx, 'grado_max', e.target.value)}
                                className="border rounded px-2 py-1.5 text-sm" />
                        </div>
                    </div>
                ))}
            </div>

            <div className="flex gap-3 mt-6">
                <button type="button" onClick={onCancel}
                    className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit"
                    className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium">Guardar Plantilla</button>
            </div>
        </form>
    );
}
