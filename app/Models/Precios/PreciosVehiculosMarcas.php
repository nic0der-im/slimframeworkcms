<?php

namespace App\Models\Precios;
use Illuminate\Database\Eloquent\Model;

class PreciosVehiculosMarcas extends Model
{
	protected $table = 'precios_vehiculos_marcas';

	protected $fillable = [
		'id',		
		'nombre',
	];
	
	public $timestamps = false;
}