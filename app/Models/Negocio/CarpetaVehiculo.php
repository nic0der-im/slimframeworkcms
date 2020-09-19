<?php

namespace App\Models\Negocio;
use Illuminate\Database\Eloquent\Model;

class CarpetaVehiculo extends Model
{
	protected $table = 'carpeta_vehiculo';

	protected $fillable = [
		'id_carpeta',
		'id_vehiculo',
		'id_usuario',
		'estado',
	];

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function vehiculo() {
		return $this->hasOne('App\Models\Vehiculos\Vehiculo', 'id', 'id_vehiculo');
	}

}