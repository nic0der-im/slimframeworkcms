<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;

class ImagenesVeh extends Model
{
	protected $table = 'veh_imagenes';
	protected $fillable = [
		'id_vehiculo',
		'id_usuario',
		'id_tipo_imagen',
		'archivo',
		'orden',
		'estado',
		'estado',
	];

	public function ComprobarExistencia(){
		$imagenes = ImagenesVeh::where('id_vehiculo', $this->id)-get();
		print_r($imagenes);
		die();
	}

	public function individuo() {
		return $this->hasOne('App\Models\Individuo', 'id_usuario', 'id_usuario');
	}

	public function tipo() {
		return $this->hasOne('App\Models\Vehiculos\TipoImagenVeh', 'id', 'id_tipo_imagen');
	}

}