<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;

class TiposEstado extends Model
{
	protected $table = 'veh_tipos-estado';
	protected $fillable = [
		'nombre',
	];

	public $timestamps = false;
}