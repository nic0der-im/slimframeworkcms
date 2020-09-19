<?php

namespace App\Models\Precios;
use Illuminate\Database\Eloquent\Model;

class PreciosVehiculosHistorial extends Model
{
	protected $table = 'precios_vehiculos_historial';

	protected $fillable = [
		'id_vehiculo',		
		'mes',
		'precio',
	];
	
	public $timestamps = false;
}