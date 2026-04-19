<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    public function index()
    {
        //
        $usuario = DB::connection('mysql')
            ->table('usuario')
            //->where('rol', '!=', 'administrador')
            ->get();
        
        //
        return view('usuariosViews.usuarios', ['usuarios' => $usuario]);
    }

    //
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string',
            'apaterno' => 'required|string',
            'amaterno' => 'required|string',
            'fecha_naci' => 'required|date',
            'tel' => 'required|string|max:10',
            'correo' => 'required|email|unique:usuario,correo',
            'pass' => 'required|min:6',
            'rol' => 'required|in:admin,sensei,tutor,alumno',
            'fecha_registro' => 'required|date',
            'estado' => 1,
        ]);

        try {
            DB::connection('mysql')
                ->table('usuario')
                ->insert([
                    'nombre' => $validated['nombre'],
                    'apaterno' => $validated['apaterno'],
                    'amaterno' => $validated['amaterno'],
                    'fecha_naci' => $validated['fecha_naci'],
                    'tel' => $validated['tel'],
                    'correo' => $validated['correo'],
                    'pass' => Hash::make($validated['pass']), 
                    'rol' => $validated['rol'],
                    'fecha_registro' => $validated['fecha_registro'],
                    'estado' => 1,

                ]);

        
            return redirect()
                ->route('usuarios.index') 
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Usuario registrado con éxito!');

        } catch (\Exception $e) {
            Log::error('Error al registrar usuario: ' . $e->getMessage());
            
            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Hubo un error en el servidor al intentar registrar el usuario.');
        }
    }
    
    public function VerLogin(){
   
        return view('login');
    }
    public function show(){}
    public function edit($id){
     // 
        $usuario = DB::connection('mysql')
            ->table('usuario')
            ->where('id_usuario', $id) 
            ->first();

        if (!$usuario) {
            return redirect()->route('usuarios.index')
                             ->with('sessionInsertado', 'false')
                             ->with('mensaje', 'Usuario no encontrado para edición.');
        }

        
        return view('usuariosViews.editarUsu', compact('usuario'));
    }
    public function update(Request $request, $id){
     
        $validated = $request->validate([
            'nombre' => 'required|string',
            'apaterno' => 'required|string',
            'amaterno' => 'required|string',
            'fecha_naci' => 'required|date',
            'tel' => 'required|string|max:20',
            'correo' => 'required|email|unique:usuario,correo,'.$id.',id_usuario', 
            'rol' => 'required|in:admin,sensei,tutor,alumno',
            'pass' => 'nullable|min:6', 
        ]);

        try {
            $dataToUpdate = [
                'nombre' => $validated['nombre'],
                'apaterno' => $validated['apaterno'],
                'amaterno' => $validated['amaterno'],
                'fecha_naci' => $validated['fecha_naci'],
                'tel' => $validated['tel'],
                'correo' => $validated['correo'],
                'rol' => $validated['rol'],
            ];

        
            if (!empty($validated['pass'])) {
                $dataToUpdate['pass'] = Hash::make($validated['pass']);
            }

            
            $updated = DB::connection('mysql')
                ->table('usuario')
                ->where('id_usuario', $id)
                ->update($dataToUpdate);

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Usuario con ID ' . $id . ' actualizado con éxito!');

        } catch (\Exception $e) {
            Log::error("Error al actualizar usuario ID $id: " . $e->getMessage());
            
            return redirect()
                ->route('editarUsu', $id) 
                ->withInput()
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    
    }
    public function destroy($id){
        try {
            //
            $deleted = DB::connection('mysql')
                ->table('usuario')
                ->where('id_usuario', $id)
                ->delete();

            if ($deleted) {
                $mensaje = '¡Usuario con ID ' . $id . ' eliminado con éxito!';
                $session = 'true';
            } else {
                // 
                $mensaje = 'No se encontró el usuario con ID ' . $id . ' para eliminar.';
                $session = 'false';
            }

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', $session)
                ->with('mensaje', $mensaje);

        } catch (\Exception $e) {
           
            Log::error("Error al eliminar usuario ID $id: " . $e->getMessage());

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al eliminar el usuario. Es posible que tenga registros relacionados.');
        }
    }

    public function toggleActive($id)
    {
        try {
            // 1. Obtener el usuario actual
            $usuario = DB::connection('mysql')
                ->table('usuario')
                ->where('id_usuario', $id)
                ->first();

            if (!$usuario) {
                return redirect()->route('usuarios.index')
                    ->with('sessionInsertado', 'false')
                    ->with('mensaje', 'Usuario no encontrado.');
            }

            // 2. Determinar el nuevo estado
            // Si estaba activo (1), el nuevo estado es inactivo (0), y viceversa.
            $nuevoEstado = $usuario->estado == 1 ? 0 : 1;
            $accion = $nuevoEstado == 1 ? 'Activado' : 'Inactivo';

            // 3. Actualizar el estado en la base de datos
            DB::connection('mysql')
                ->table('usuario')
                ->where('id_usuario', $id)
                ->update(['estado' => $nuevoEstado]);

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', "¡Usuario ID $id ha sido $accion con éxito!");

        } catch (\Exception $e) {
            Log::error("Error al cambiar estado de usuario ID $id: " . $e->getMessage());
            
            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', "Error al cambiar el estado del usuario.");
        }
    }

    public function ProcesarLogin(Request $request)
{
    // 1. Validar las credenciales (Nota: el campo de contraseña se llama 'contra' en tu vista)
    $request->validate([
        'correo' => 'required|email',
        'contra' => 'required',
    ]);

    $correo = $request->input('correo');
    $password = $request->input('contra'); // Usamos 'contra'

    // 2. Buscar al usuario por correo
    $usuario = DB::connection('mysql')
        ->table('usuario')
        ->where('correo', $correo)
        ->first();

    // 3. Verificar si el usuario existe y si la contraseña es correcta
    if ($usuario && Hash::check($password, $usuario->pass)) {
        
        // ===============================================
        // ⭐ RESTRICCIÓN DE ESTADO INACTIVO
        // ===============================================
        if (isset($usuario->estado) && $usuario->estado == 0) {
            // El usuario existe y está inactivo (estado = 0)
            return redirect()
                ->route('verLogin')
                // Usamos 'error_login' para el mensaje de SweetAlert
                ->with('error_login', 'Tu cuenta ha sido desactivada. Por favor, contacta al administrador.');
        }
        // ===============================================
        
        // 4. Iniciar sesión (Guardar en sesión)
        $request->session()->put('usuario_logueado', [
            'id' => $usuario->id_usuario,
            'nombre' => $usuario->nombre,
            'rol' => $usuario->rol,
            'estado' => $usuario->estado,
        ]);

        // Redirección al dashboard o página principal
        return redirect()->route('dashboard.index'); // Asume que tienes una ruta llamada 'dashboard.index'
        
    } else {
        // Credenciales inválidas
        return redirect()
            ->route('verLogin')
            // Mensaje genérico para mantener la seguridad (no especificar si es el correo o la contraseña)
            ->with('error_login', 'Credenciales inválidas. Verifica tu correo y contraseña.');
    }
}

    public function confirmMail($correo){} // Mantener si es necesaria

}