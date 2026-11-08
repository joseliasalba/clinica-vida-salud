<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        $paciente = $this->patient($request);
        $salud = DB::table('perfiles_salud_declarados')->where('paciente_id', $paciente->id)->first();
        $contacto = DB::table('contactos_emergencia')->where('paciente_id', $paciente->id)->where('principal', true)->orderBy('id')->first();

        return view('perfil.edit', compact('paciente', 'salud', 'contacto'));
    }

    public function update(Request $request): RedirectResponse
    {
        $telefono = ['string', 'max:50', 'regex:/^\+?[0-9][0-9 ()\-.]{4,49}$/'];
        $data = $request->validate([
            'telefono' => ['required', ...$telefono],
            'direccion' => ['nullable', 'string', 'max:255'],
            'tipo_sangre' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'estado_alergias' => ['required', Rule::in(['desconocido', 'ninguna', 'declaradas'])],
            'detalle_alergias' => ['exclude_unless:estado_alergias,declaradas', 'required', 'string', 'max:255'],
            'medicamentos' => ['nullable', 'string', 'max:2000'],
            'antecedentes_personales' => ['nullable', 'string', 'max:2000'],
            'antecedentes_familiares' => ['nullable', 'string', 'max:2000'],
            'contacto_nombres' => ['nullable', 'required_with:contacto_apellidos,contacto_parentesco,contacto_telefono', 'string', 'max:255'],
            'contacto_apellidos' => ['nullable', 'required_with:contacto_nombres,contacto_parentesco,contacto_telefono', 'string', 'max:255'],
            'contacto_parentesco' => ['nullable', 'required_with:contacto_nombres,contacto_apellidos,contacto_telefono', 'string', 'max:255'],
            'contacto_telefono' => ['nullable', 'required_with:contacto_nombres,contacto_apellidos,contacto_parentesco', ...$telefono],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $paciente = $this->patient($request, lock: true);
            $paciente->update(['telefono' => $data['telefono'], 'direccion' => $data['direccion'] ?? null]);
            DB::table('perfiles_salud_declarados')->updateOrInsert(['paciente_id' => $paciente->id], [
                'tipo_sangre' => $data['tipo_sangre'] ?? null,
                'estado_alergias' => $data['estado_alergias'],
                'detalle_alergias' => $data['detalle_alergias'] ?? null,
                'medicamentos' => $data['medicamentos'] ?? null,
                'antecedentes_personales' => $data['antecedentes_personales'] ?? null,
                'antecedentes_familiares' => $data['antecedentes_familiares'] ?? null,
                'fecha_actualizacion' => now(),
            ]);
            $principal = DB::table('contactos_emergencia')->where('paciente_id', $paciente->id)->where('principal', true);
            if (! isset($data['contacto_nombres'])) {
                $principal->delete();

                return;
            }

            $contacto = [
                'paciente_id' => $paciente->id,
                'nombres' => $data['contacto_nombres'],
                'apellidos' => $data['contacto_apellidos'],
                'parentesco' => $data['contacto_parentesco'],
                'telefono' => $data['contacto_telefono'],
                'principal' => true,
            ];
            $id = $principal->orderBy('id')->value('id');
            if ($id) {
                DB::table('contactos_emergencia')->where('id', $id)->update($contacto);
            } else {
                DB::table('contactos_emergencia')->insert($contacto);
            }
        });

        return redirect()->route('perfil.edit')->with('status', 'Tu perfil fue actualizado.');
    }

    private function patient(Request $request, bool $lock = false): Paciente
    {
        return Paciente::where('usuario_id', $request->user()->id)->where('activo', true)
            ->when($lock, fn ($query) => $query->lockForUpdate())->firstOrFail();
    }
}
