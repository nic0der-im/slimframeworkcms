<?php

namespace App\Models\Vehiculos;

use Illuminate\Database\Eloquent\Model;

class TipoFormulario extends Model
{
	protected $table = 'vehiculo_tipos_formulario';
	protected $fillable = [
		'nombre',
    'descripcion',
		'caducidad',
    'arancel',
		'tipo',
		'orden',
		'estado',
	];

	public function formularios(){
		return $this->hasMany('App\Models\Vehiculos\VehiculoFormulario', 'id_formulario', 'id')->orderBy('fecha','asc');
	}
}
