<?php

namespace App\Models\Pagina;
use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
	protected $table = 'paginas_bloques_versiones';
	public $timestamps = false;
	
	protected $fillable = [
		'id_bloque',
		'titulo',		
		'contenido',
		'enlace',
		'orden',
		'predeterminado',
		'id_vehiculo'
	];

	public function getVehiculo() {
		return $this->hasOne('App\Models\Precios\PreciosVehiculos', 'id', 'id_vehiculo');
	}
}