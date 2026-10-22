<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\UserAdministration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('auth.permisos.index', ['permissions' => DB::table('permisos')
            ->whereIn('codigo', array_keys(config('permisos.catalogo')))->orderBy('codigo')->get()]);
    }

    public function update(Request $request, int $permiso, UserAdministration $administration): RedirectResponse
    {
        $data = $request->validate(['activo' => ['required', 'boolean']], ['activo.required' => 'Indica el estado del permiso.', 'activo.boolean' => 'El estado no es válido.']);
        $administration->setPermissionState($request->user(), $permiso, (bool) $data['activo']);

        return redirect()->route('permisos.index')->with('status', 'Estado del permiso actualizado.');
    }
}
