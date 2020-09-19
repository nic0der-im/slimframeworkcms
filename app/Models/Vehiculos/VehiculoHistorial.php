<?php

namespace App\Models\Vehiculos;

use Illuminate\Database\Eloquent\Model;

class VehiculoHistorial extends Model
{
	protected $table = 'vehiculo_historial';
	protected $fillable = [
		'id_vehiculo',
		'id_usuario',
		'descripcion',
		'id_estado',
		'estado',
	];

	public function vehiculo(){
		return $this->hasOne('App\Models\Vehiculos\Vehiculo', 'id', 'id_vehiculo');
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function tipoEstado() {
		return $this->hasOne('App\Models\Vehiculos\TiposEstado', 'id', 'id_estado');
	}

}
