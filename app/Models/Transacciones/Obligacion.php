<?php

namespace App\Models\Transacciones;
use Illuminate\Database\Eloquent\Model;

class Obligacion extends Model
{
	protected $table = 'tra_obligaciones';
	protected $fillable = [
		'id_sucursal',
		'id_movimiento',
		'emisor',
		'receptor',
		'importe',
		'estado',
		'fecha_inicio',
		'fecha_aproxfin',
		'fecha_fin',
		'id_usuario',
	];

	public function movimiento()
	{
		return $this->hasOne('App\Models\Transacciones\Movimiento','id','id_movimiento');
	}

	public function individuo()
	{
		return $this->hasOne('App\Models\Individuo','id_usuario','id_usuario');
	}
}
