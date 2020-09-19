<?php

namespace App\Models\Vehiculos;

use Illuminate\Database\Eloquent\Model;

class VehiculoFormulario extends Model
{
	protected $table = 'vehiculo_formulario';
	protected $fillable = [
		'id_vehiculo',
    'id_formulario',
		'id_usuario',
    'fecha',
    'fecha_vto',
    'responsable',
    'arancel',
		'observaciones',
		'estado',
	];

	public function vehiculo(){
		return $this->hasOne('App\Models\Vehiculos\Vehiculo', 'id', 'id_vehiculo');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

  public function tipo(){
		return $this->hasOne('App\Models\Vehiculos\TipoFormulario', 'id', 'id_formulario');
	}

}
