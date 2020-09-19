<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;

class Creditos extends Model
{
	protected $table = 'creditos';

	protected $fillable = [
		'year',
		'banco',
		'porcentaje',
	];
}

