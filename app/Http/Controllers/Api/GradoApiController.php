<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
 
// ════════════════════════════════════════════════════════════════════════════
//  GradoApiController
//  GET /api/grados — catálogo de grados (público, sin auth)
// ════════════════════════════════════════════════════════════════════════════
class GradoApiController extends Controller
{
    public function index()
    {
        try {
            $grados = DB::table('grado')
                ->orderBy('id_grado', 'asc')
                ->select('id_grado', 'nombreGrado', 'orden')
                ->get();
 
            return response()->json([
                'success' => true,
                'data'    => $grados,
            ]);
 
        } catch (\Exception $e) {
            Log::error('GradoApi@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los grados.',
            ], 500);
        }
    }
}
