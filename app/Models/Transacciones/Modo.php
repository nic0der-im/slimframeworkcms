<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class Modo extends Model
{
	protected $table = 'tra_modos';
	protected $fillable = [
		'tipo',
		'sucursales',
		'modo',
		'estado_modo',
		'especial',
	];

	public $timestamps = false;
}