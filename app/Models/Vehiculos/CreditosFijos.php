<?php

namespace App\Models\Vehiculos;
use Illuminate\Database\Eloquent\Model;

class CreditosFijos extends Model
{
	protected $table = 'creditos_fijos';

	protected $fillable = [
		'transferencia',
		'otorgamiento',
		'prenda',
	];
}