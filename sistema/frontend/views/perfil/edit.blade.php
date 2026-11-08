@extends('layouts.app')
@section('title', 'Mi perfil')
@section('content')
<div class="admin-page">
    <h1>Mi perfil</h1>
    <p>{{ $paciente->nombres }} {{ $paciente->apellidos }} · {{ $paciente->codigo }}</p>
    <x-messages />
    <form method="POST" action="{{ route('perfil.update') }}">
        @csrf
        @method('PATCH')
        <fieldset class="form-grid">
            <legend>Datos de contacto</legend>
            <div class="field"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $paciente->telefono) }}" required></div>
            <div class="field"><label for="direccion">Dirección</label><input id="direccion" name="direccion" value="{{ old('direccion', $paciente->direccion) }}"></div>
        </fieldset>
        <fieldset class="form-grid">
            <legend>Salud declarada</legend>
            <div class="field"><label for="tipo_sangre">Tipo de sangre</label><select id="tipo_sangre" name="tipo_sangre"><option value="">No declarado</option>@foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $tipo)<option @selected(old('tipo_sangre', $salud?->tipo_sangre) === $tipo)>{{ $tipo }}</option>@endforeach</select></div>
            <div class="field"><label for="estado_alergias">Alergias</label><select id="estado_alergias" name="estado_alergias">@foreach(['desconocido' => 'No lo sé', 'ninguna' => 'Ninguna conocida', 'declaradas' => 'Sí, tengo alergias'] as $estado => $label)<option value="{{ $estado }}" @selected(old('estado_alergias', $salud?->estado_alergias) === $estado)>{{ $label }}</option>@endforeach</select></div>
            @foreach(['detalle_alergias' => 'Detalle de las alergias declaradas', 'medicamentos' => 'Medicamentos', 'antecedentes_personales' => 'Antecedentes personales y enfermedades crónicas', 'antecedentes_familiares' => 'Antecedentes familiares'] as $field => $label)
                <div class="field"><label for="{{ $field }}">{{ $label }}</label><textarea id="{{ $field }}" name="{{ $field }}" maxlength="{{ $field === 'detalle_alergias' ? 255 : 2000 }}">{{ old($field, $salud?->{$field}) }}</textarea></div>
            @endforeach
        </fieldset>
        <fieldset class="form-grid">
            <legend>Contacto de emergencia</legend>
            <p>Si no deseas guardar un contacto principal, deja los cuatro campos vacíos.</p>
            @foreach(['nombres' => 'Nombres', 'apellidos' => 'Apellidos', 'parentesco' => 'Parentesco', 'telefono' => 'Teléfono de emergencia'] as $field => $label)
                <div class="field"><label for="contacto_{{ $field }}">{{ $label }}</label><input id="contacto_{{ $field }}" name="contacto_{{ $field }}" value="{{ old('contacto_'.$field, $contacto?->{$field}) }}"></div>
            @endforeach
        </fieldset>
        <button class="button primary" type="submit">Guardar perfil</button>
    </form>
</div>
@endsection
