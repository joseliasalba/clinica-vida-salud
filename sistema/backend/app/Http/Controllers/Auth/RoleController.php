<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SaveRoleRequest;
use App\Services\Auth\UserAdministration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = DB::table('roles')->orderBy('nombre')->paginate(15);
        $permissions = DB::table('rol_permiso')->join('permisos', 'permisos.id', '=', 'rol_permiso.permiso_id')
            ->whereIn('rol_id', $roles->pluck('id'))->where('rol_permiso.activo', true)->where('permisos.activo', true)
            ->select('rol_id', 'codigo', 'descripcion')->orderBy('codigo')->get()->groupBy('rol_id');

        return view('auth.roles.index', compact('roles', 'permissions'));
    }

    public function create(): View
    {
        return $this->form();
    }

    public function edit(int $rol): View
    {
        return $this->form($rol);
    }

    private function form(?int $id = null): View
    {
        $role = $id ? DB::table('roles')->where('id', $id)->first() : null;
        abort_if($id && ! $role, 404);
        $selectedPermissions = $id ? DB::table('rol_permiso')->where('rol_id', $id)->where('activo', true)->pluck('permiso_id')->all() : [];

        return view('auth.roles.form', [
            'role' => $role, 'selectedPermissions' => $selectedPermissions,
            'permissions' => DB::table('permisos')->whereIn('codigo', array_keys(config('permisos.catalogo')))->orderBy('codigo')->get(),
        ]);
    }

    public function store(SaveRoleRequest $request, UserAdministration $administration): RedirectResponse
    {
        $administration->saveRole($request->user(), $request->validated());

        return redirect()->route('roles.index')->with('status', 'Rol creado correctamente.');
    }

    public function update(SaveRoleRequest $request, int $rol, UserAdministration $administration): RedirectResponse
    {
        $administration->saveRole($request->user(), $request->validated(), $rol);

        return redirect()->route('roles.index')->with('status', 'Rol actualizado correctamente.');
    }
}
