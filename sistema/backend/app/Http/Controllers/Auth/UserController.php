<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SaveUserRequest;
use App\Models\User;
use App\Services\Auth\UserAdministration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])]]);
        $query = User::query()->select(['id', 'nombre_usuario', 'correo', 'activo', 'created_at']);
        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']).'%';
            $query->where(fn ($query) => $query->where('nombre_usuario', 'ilike', $term)->orWhere('correo', 'ilike', $term));
        }
        if (! empty($filters['estado'])) {
            $query->where('activo', $filters['estado'] === 'activo');
        }
        $users = $query->orderBy('nombre_usuario')->orderBy('id')->paginate(15)->withQueryString();
        $assignments = DB::table('usuario_rol')->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
            ->whereIn('usuario_id', $users->pluck('id'))->where('usuario_rol.activo', true)
            ->select('usuario_rol.*', 'roles.nombre', 'roles.activo as rol_activo')->orderBy('roles.nombre')->get()->groupBy('usuario_id');

        return view('auth.usuarios.index', compact('users', 'assignments'));
    }

    public function create(): View
    {
        return view('auth.usuarios.form', ['usuario' => new User, 'roles' => DB::table('roles')->orderBy('nombre')->get(), 'selectedRoles' => []]);
    }

    public function edit(User $usuario): View
    {
        $selectedRoles = DB::table('usuario_rol')->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
            ->where('usuario_id', $usuario->id)->where('usuario_rol.activo', true)->where('roles.activo', true)
            ->where('fecha_asignacion', '<=', now())
            ->where(fn ($query) => $query->whereNull('fecha_expiracion')->orWhere('fecha_expiracion', '>', now()))->pluck('rol_id')->all();

        return view('auth.usuarios.form', ['usuario' => $usuario, 'roles' => DB::table('roles')->orderBy('nombre')->get(), 'selectedRoles' => $selectedRoles]);
    }

    public function store(SaveUserRequest $request, UserAdministration $administration): RedirectResponse
    {
        $administration->saveUser($request->user(), $request->validated());

        return redirect()->route('usuarios.index')->with('status', 'Usuario creado correctamente.');
    }

    public function update(SaveUserRequest $request, User $usuario, UserAdministration $administration): RedirectResponse
    {
        $administration->saveUser($request->user(), $request->validated(), $usuario->id);
        if ($request->user()->id === $usuario->id && $request->filled('password')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Contraseña actualizada. Ingresa nuevamente.');
        }

        return redirect()->route('usuarios.index')->with('status', 'Usuario actualizado correctamente.');
    }
}
