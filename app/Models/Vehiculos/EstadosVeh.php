<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;

class EstadosVeh extends Model
{
	protected $table = 'veh_lista-estados';
	protected $fillable = [
		'nombre',
	];

	public $timestamps = false;
}