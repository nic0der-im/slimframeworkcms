<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;

class UsadoTercero extends Model
{
	protected $table = 'usados_terceros';
	protected $fillable = [
		'dominio',
		'id_owner',
		'id_vehiculo',
		'kilometraje',
		'observaciones',
		'color'
	];

	public $incrementing = false;

	public function publicacion() {
		return $this->hasOne('App\Models\Publicacion', 'id_vehiculo','id_vehiculo');
	}
}