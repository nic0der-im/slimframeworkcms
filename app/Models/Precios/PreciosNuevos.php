<?php

namespace App\Models\Precios;
use Illuminate\Database\Eloquent\Model;
use App\Models\Precios\PreciosNuevos;

class PreciosNuevos extends Model
{
	protected $table = 'precios_0km';

	protected $fillable = [
		'seg',
		'modelo',
		'motor',		
		'carroceria',
		'catalogo',
		'precio_sugerido',
		'marca',
		'bonificacion',
		'flete-patentamiento',
	];

	public $timestamps = false;

	public function getMarca()
	{
		return $this->hasOne('App\Models\Vehiculos\Marca', 'id', 'marca');
	}
}