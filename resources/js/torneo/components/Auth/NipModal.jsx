import React, { useState } from 'react';
import { FASE_RESPONSABLES } from '../../utils/constants';

export default function NipModal({ show, fase, onConfirm, onCancel, loading, error }) {
    const [nip, setNip] = useState('');

    if (!show || !fase) return null;

    const handleSubmit = (e) => {
        e.preventDefault();
        if (nip.length >= 4 && nip.length <= 8) {
            onConfirm(nip);
        }
    };

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
                <div className="bg-red-600 px-6 py-4">
                    <h3 className="text-white font-bold text-lg flex items-center gap-2">
                        <i className="bi bi-shield-lock-fill"></i>
                        Autorización de Fase
                    </h3>
                </div>

                <form onSubmit={handleSubmit} className="p-6">
                    <div className="text-center mb-4">
                        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i className="bi bi-key-fill text-2xl text-red-600"></i>
                        </div>
                        <p className="text-gray-700 font-medium">
                            Transición a: <strong className="text-red-600">{fase}</strong>
                        </p>
                        <p className="text-sm text-gray-500 mt-1">
                            Responsable: {FASE_RESPONSABLES[fase] || 'No definido'}
                        </p>
                        <p className="text-xs text-gray-400 mt-1">
                            Ingrese el NIP de autorización (4-8 caracteres)
                        </p>
                    </div>

                    {error && (
                        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg mb-4 text-sm">
                            {error}
                        </div>
                    )}

                    <input
                        type="password"
                        value={nip}
                        onChange={(e) => setNip(e.target.value)}
                        maxLength={8}
                        autoFocus
                        className="w-full border border-gray-300 rounded-lg px-4 py-3 text-center text-2xl tracking-[0.5em] font-mono focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="····"
                    />

                    <div className="flex gap-3 mt-6">
                        <button
                            type="button"
                            onClick={() => { setNip(''); onCancel(); }}
                            className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            disabled={nip.length < 4 || loading}
                            className="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {loading ? (
                                <span className="flex items-center justify-center gap-2">
                                    <i className="bi bi-arrow-repeat animate-spin"></i>
                                    Validando...
                                </span>
                            ) : (
                                'Autorizar'
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
