<?php

namespace App\Models\Precios;
use Illuminate\Database\Eloquent\Model;
use App\Models\Precios\PreciosVehiculosHistorial;

class PreciosVehiculos extends Model
{
	protected $table = 'precios_vehiculos';

	protected $fillable = [
		'id',
		'id_rio',
		'marca',		
		'nombre',
		'anio',
	];

	public $timestamps = false;

	public function historial() {
		return $this->hasMany('App\Models\Precios\PreciosVehiculosHistorial', 'id_vehiculo', 'id');
	}

	public function nombremarca() {
		return $this->hasOne('App\Models\Precios\PreciosVehiculosMarcas', 'id', 'marca');
	}

	public function scopeultimoprecio() {
		return PreciosVehiculosHistorial::where('id_vehiculo', $this->id)->orderBy('mes', 'DESC')->first();
	}

	protected $appends = [
		'precio',
	];

	public function getPrecioAttribute() {
    	return PreciosVehiculosHistorial::where('id_vehiculo', $this->id)->orderBy('mes', 'DESC')->first();
	}
}